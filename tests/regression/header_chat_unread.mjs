import fs from 'node:fs';
import vm from 'node:vm';
import assert from 'node:assert/strict';
let passed = 0;
const check = (value, label) => {
  assert.ok(value, label);
  passed++;
  console.log('PASS: ' + label);
};
const source = fs
  .readFileSync('resources/js/utils/header-chat-unread.js', 'utf8')
  .replace(/^import.*\n/, '')
  .replace('export function', 'function');
function fixture(page = 'dashboard') {
  const events = new Map(),
    docEvents = new Map(),
    timers = new Set(),
    store = new Map([['metis_chat_unread_1', '999']]);
  let calls = 0;
  const context = {
    window: {
      addEventListener: (k, f) => events.set(k, f),
      removeEventListener: (k) => events.delete(k),
    },
    document: {
      hidden: false,
      body: { dataset: { page } },
      addEventListener: (k, f) => docEvents.set(k, f),
      removeEventListener: (k) => docEvents.delete(k),
    },
    setInterval: (f) => {
      timers.add(f);
      return f;
    },
    clearInterval: (f) => timers.delete(f),
    chatStorage: { setItem: (k, v) => store.set(k, v) },
    chatHttp: {
      get: async () => {
        calls++;
        return { data: { unread: 2, unread_messages: 5 } };
      },
    },
  };
  vm.createContext(context);
  vm.runInContext(source, context);
  return {
    context,
    events,
    docEvents,
    timers,
    store,
    count: () => calls,
    create: (n = 0, enabled = true) => context.headerChatUnread(n, 1, enabled),
  };
}
let f = fixture(),
  c = f.create(3);
check(c.chatUnread === 3, 'fresh server count takes priority over stale cached count');
c.init();
await new Promise((resolve) => setImmediate(resolve));
check(f.count() === 1 && c.chatUnread === 5, 'other pages fetch unread messages automatically');
check(f.timers.size === 1, 'one refresh timer is installed');
c.init();
check(f.timers.size === 1, 'repeated init does not duplicate polling');
f.events.get('metis:chat-unread')({ detail: { count: 8 } });
check(
  c.chatUnread === 8 && f.store.get(c.key) === '8',
  'same-tab chat updates synchronize badge and cache'
);
f.events.get('storage')({ key: c.key, newValue: '1' });
check(c.chatUnread === 1, 'cross-tab reads reduce badge count');
f.events.get('storage')({ key: 'metis_chat_unread_2', newValue: '90' });
check(c.chatUnread === 1, 'other users storage keys are ignored');
f.context.document.hidden = true;
const count = f.count();
await c.refresh();
check(f.count() === count, 'hidden pages do not poll');
f.context.document.hidden = false;
f.docEvents.get('visibilitychange')();
await new Promise((resolve) => setImmediate(resolve));
check(f.count() === count + 1, 'returning to a page refreshes the badge');
c.destroy();
check(
  f.events.size === 0 && f.docEvents.size === 0 && f.timers.size === 0,
  'destroy removes timers and listeners'
);
await c.refresh();
check(f.count() === count + 1, 'destroyed headers cannot fetch again');
f = fixture('chat');
c = f.create();
c.init();
await Promise.resolve();
check(f.count() === 0 && f.timers.size === 0, 'chat page reuses workspace polling');
c.destroy();
f = fixture();
c = f.create(0, false);
c.init();
await c.refresh();
check(f.count() === 0, 'disabled chat creates no polling requests');
f = fixture();
c = f.create();
let resolve;
f.context.chatHttp.get = () => new Promise((r) => (resolve = r));
c.init();
f.events.get('metis:chat-unread')({ detail: { count: 11 } });
resolve({ data: { unread_messages: 2 } });
await new Promise((resolve) => setImmediate(resolve));
check(c.chatUnread === 11, 'late HTTP response cannot overwrite newer chat events');
c.destroy();
f = fixture();
c = f.create();
f.context.chatHttp.get = async () => {
  const error = Error('expired');
  error.response = { status: 401 };
  throw error;
};
c.init();
await new Promise((resolve) => setImmediate(resolve));
check(c.stopped, 'expired sessions stop background requests');
c.destroy();
f = fixture();
c = f.create();
f.context.chatHttp.get = async () => {
  const error = Error('rate');
  error.response = { status: 429, headers: { 'retry-after': '60' } };
  throw error;
};
const now = Date.now();
await c.refresh();
check(c.retryAt >= now + 60000, 'throttled header obeys retry-after');
f = fixture();
c = f.create(7);
c.setCount('invalid');
c.setCount(-1);
check(c.chatUnread === 7, 'invalid counts cannot corrupt badge');
console.log(`${passed} header notification checks passed.`);
