import fs from 'node:fs';
import vm from 'node:vm';
import assert from 'node:assert/strict';
let passed = 0;
const check = (value, label) => {
  assert.ok(value, label);
  console.log('PASS: ' + label);
  passed++;
};
let source = fs.readFileSync('resources/views/chat/index.blade.php', 'utf8');
source = source
  .slice(source.indexOf('    function chatWorkspace(initial)'), source.lastIndexOf('</script>'))
  .replaceAll('@js(auth()->id())', '1');
const storage = new Map();
const window = {
  location: { origin: 'http://chat.test' },
  chatStorage: {
    getItem: (k) => storage.get(k),
    setItem: (k, v) => storage.set(k, v),
    removeItem: (k) => storage.delete(k),
  },
  dispatchEvent() {},
};
const document = { hidden: false };
const context = vm.createContext({
  window,
  document,
  URL,
  FormData,
  CustomEvent: class {},
  setInterval,
  clearInterval,
  console: { error() {} },
});
vm.runInContext(source, context);
const make = () => {
  const c = context.chatWorkspace({ canSend: true, canManageGroups: true });
  c.$refs = {};
  c.$nextTick = (f) => f();
  c.$watch = () => {};
  c.scrollToBottom = () => {};
  return c;
};
let calls = 0;
window.chatHttp = {
  get: async () => {
    calls++;
    return { data: { data: [] } };
  },
  post: async () => {
    calls++;
  },
};
let c = make();
document.hidden = true;
await c.poll();
check(calls === 0, 'hidden tab performs no polling requests');
document.hidden = false;
c = make();
c.stopped = true;
await c.poll();
check(calls === 0, 'destroyed or expired workspace stops polling');
c = make();
c.retryAt = Date.now() + 10000;
await c.poll();
check(calls === 0, 'rate-limit backoff suppresses premature requests');
c = make();
c.activeConversation = { id: 1 };
c.draft = 'First draft';
await c.selectConversation({ id: 2 });
c.draft = 'Second draft';
await c.selectConversation({ id: 1 });
check(c.draft === 'First draft', 'conversation switches preserve separate drafts');
c = make();
let resolve;
window.chatHttp.get = () => new Promise((r) => (resolve = r));
const older = c.selectConversation({ id: 1 });
c.selectionVersion++;
c.activeConversation = { id: 2 };
resolve({ data: { data: { data: [{ id: 100 }] } } });
await older;
check(c.messages.length === 0, 'late history cannot overwrite another chat');
c = make();
c.activeConversation = { id: 1 };
c.messages = [{ id: 5 }, { id: 6 }];
c.hasOlderMessages = true;
let before;
window.chatHttp.get = async (u, o) => {
  before = o.params.before_id;
  return { data: { data: { data: [{ id: 5 }, { id: 4 }, { id: 3 }], next_page_url: null } } };
};
await c.loadOlderMessages();
check(
  before === 5 && c.messages.map((m) => m.id).join(',') === '3,4,5,6',
  'earlier history uses stable cursors and deduplicates'
);
c = make();
c.activeConversation = { id: 1 };
c.draft = 'Retry';
const ids = [];
window.chatHttp.post = async (u, data) => {
  ids.push(data.get('client_id'));
  throw Error('network');
};
await c.sendMessage();
await c.sendMessage();
check(ids[0] === ids[1] && c.draft === 'Retry', 'failed sends preserve draft and retry identity');
c.draft = 'Changed';
await c.sendMessage();
check(ids[2] !== ids[1], 'edited retry draft gets a distinct identity');
c = make();
c.canSend = false;
c.activeConversation = { id: 1 };
c.draft = 'No access';
calls = 0;
window.chatHttp.post = async () => {
  calls++;
};
await c.sendMessage();
check(calls === 0, 'read-only users cannot submit from composer');
c = make();
c.groupBusy = true;
await c.createGroup();
check(calls === 0, 'duplicate group submits are ignored while busy');
c = make();
c.activeConversation = { id: 1, is_pinned: false };
let finish;
window.chatHttp.post = () => new Promise((r) => (finish = r));
const pin = c.togglePin();
c.activeConversation = { id: 2, is_pinned: false };
finish({});
await pin;
check(
  c.activeConversation.id === 2 && !c.activeConversation.is_pinned,
  'late pin response cannot modify another chat'
);
c = make();
let directory = 0;
window.chatHttp = {
  get: async (url) => {
    if (url === '/api/chat/users') {
      directory++;
      return {
        data: {
          data: [
            { id: 1, name: 'Self' },
            { id: 2, name: 'Colleague' },
          ],
        },
      };
    }
    return { data: { data: [], meta: { has_more: false, unread_messages: 0 } } };
  },
  post: async () => ({}),
};
await c.poll();
check(
  directory === 1 && c.users.length === 1 && c.selfUser.id === 1,
  'one directory read refreshes colleagues and self'
);
c = make();
window.chatHttp.get = async () => {
  const e = Error('limited');
  e.response = { status: 429, headers: { 'retry-after': '20' } };
  throw e;
};
const start = Date.now();
await c.poll();
check(
  c.retryAt >= start + 20000,
  '429 response respects carrier-independent chat retry-after delay'
);
let transport = fs
  .readFileSync('resources/js/utils/chat-http.js', 'utf8')
  .replaceAll('export const', 'var');
let captured;
const http = vm.createContext({
  window,
  document: { querySelector: () => ({ content: 'csrf' }) },
  URL,
  FormData,
  localStorage: {
    getItem() {
      throw Error('blocked');
    },
    setItem() {
      throw Error('blocked');
    },
    removeItem() {
      throw Error('blocked');
    },
  },
  fetch: async (url, options) => {
    captured = options;
    return new Response('{"ok":true}');
  },
});
vm.runInContext(transport, http);
await http.chatHttp.post('/api/chat/messages', { content: 'test' });
check(
  captured.headers['X-CSRF-TOKEN'] === 'csrf' && captured.credentials === 'same-origin',
  'transport includes CSRF and same-origin credentials'
);
await http.chatHttp.post('/api/chat/messages', new FormData());
check(!captured.headers['Content-Type'], 'multipart boundary is set by the browser');
let denied = false;
try {
  await http.chatHttp.get('https://other.test/messages');
} catch {
  denied = true;
}
check(denied, 'chat transport blocks external destinations');
http.fetch = async () => new Response('<html>Sign in</html>');
denied = false;
try {
  await http.chatHttp.get('/api/chat/messages');
} catch {
  denied = true;
}
check(denied, 'unexpected HTML does not count as successful chat data');
check(http.chatStorage.getItem('x') === null, 'unavailable browser storage does not crash chat');
c = make();
check(
  c.compareMessages(
    { id: 99, created_at: '2020-01-01T00:00:00Z' },
    { id: 1, created_at: '2021-01-01T00:00:00Z' }
  ) < 0 &&
    c.compareMessages({ id: 99, created_at: null }, { id: 1, created_at: '2020-01-01T00:00:00Z' }) <
      0,
  'history merges preserve timestamp order for backdated IDs and null dates'
);

c = make();
c.activeConversation = { id: 1, unread_count: 0 };
let rejectHistory;
window.chatHttp = {
  get: async (url) => {
    if (url.includes('/messages'))
      return new Promise((resolve, reject) => {
        rejectHistory = reject;
      });
    if (url === '/api/chat/users') return { data: { data: [] } };
    return { data: { data: [{ id: 1, unread_count: 0 }], meta: { has_more: false } } };
  },
  post: async () => ({}),
};
const failedPoll = c.poll();
while (!rejectHistory) await Promise.resolve();
c.activeConversation = { id: 2 };
c.selectionVersion++;
const missing = Error('gone');
missing.response = { status: 404 };
rejectHistory(missing);
await failedPoll;
check(
  c.activeConversation?.id === 2,
  'stale poll not-found cannot close another selected conversation'
);
c = make();
c.activeConversation = { id: 1, unread_count: 1 };
window.chatHttp = {
  get: async (url) =>
    url.includes('/messages')
      ? {
          data: {
            data: { data: [{ id: 7, created_at: '2026-01-01T00:00:00Z' }], next_page_url: null },
          },
        }
      : url === '/api/chat/users'
        ? { data: { data: [] } }
        : { data: { data: [{ id: 1, unread_count: 1 }], meta: { has_more: false } } },
  post: async (url) => {
    if (url.endsWith('/read')) throw missing;
    return {};
  },
};
await c.poll();
check(
  c.activeConversation?.id === 1 && c.messages.length === 1,
  'deleted read boundary does not close a valid conversation'
);
console.log(`${passed} frontend chat checks passed.`);
