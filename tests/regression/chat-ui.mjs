import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
const view = fs.readFileSync(new URL('../../resources/views/chat/index.blade.php', import.meta.url), 'utf8');
const source = view.slice(view.indexOf('    function chatWorkspace(initial)'), view.lastIndexOf('</script>')).replaceAll('@js(auth()->id())', '1');
let get, post, timers = 0, revoked = [], created = 0;
const context = vm.createContext({
    axios: { get: (...args) => get(...args), post: (...args) => post(...args) },
    localStorage: { getItem: () => null, setItem() {}, removeItem() {} },
    window: { dispatchEvent() {} }, CustomEvent: class {}, console,
    setInterval: () => ++timers, clearInterval() {},
    FormData: class { append() {} },
    URL: { createObjectURL: () => `blob:${++created}`, revokeObjectURL: url => revoked.push(url) },
});
vm.runInContext(source, context);
function workspace() {
    const state = context.chatWorkspace({ conversations: [{id: 1}, {id: 2}] });
    state.$watch = () => {}; state.$refs = {}; state.$nextTick = fn => fn();
    return state;
}
const deferred = () => { let resolve; const promise = new Promise(r => resolve = r); return {promise, resolve}; };
const page = messages => ({data: {data: {data: messages}}});
{
    const s = workspace(); s.poll = () => {}; s.init(); s.init();
    assert.equal(timers, 1, 'initialization starts one poll timer');
}
{
    const a = deferred(), b = deferred(); get = url => url.includes('/1/') ? a.promise : b.promise;
    const s = workspace(); const first = s.selectConversation({id:1});
    s.draft = 'Draft for first chat';
    const second = s.selectConversation({id:2});
    assert.equal(s.draft, '', 'draft does not leak into another conversation');
    b.resolve(page([{id:22}])); await second;
    a.resolve(page([{id:11}])); await first;
    assert.equal(s.messages[0].id, 22, 'late selection response cannot overwrite current chat');
    get = async () => page([]); await s.selectConversation({id:1});
    assert.equal(s.draft, 'Draft for first chat', 'returning restores unsent draft');
}
{
    const s = workspace(); s.activeConversation = {id:1}; s.draft = 'hello';
    const pending = deferred(); let sends = 0;
    post = () => { sends++; return pending.promise; };
    const sending = s.sendMessage(); await s.sendMessage();
    assert.equal(sends, 1, 'repeated Enter submits once');
    await s.selectConversation({id:2}); assert.equal(s.activeConversation.id, 1);
    s.draft = 'next draft'; pending.resolve({data:{data:{id:10,created_at:'2026-10-17'}}});
    await sending; assert.equal(s.draft, 'next draft', 'typing during send is preserved');
    assert.equal(s.messages.length, 1);
}
{
    const s = workspace(); s.activeConversation = {id:1}; s.messages = [{id:9, content:'old'}];
    get = async url => url.endsWith('/messages') ? page([{id:9,content:'edited'}]) : {data:{data:[]}};
    post = async () => ({}); await s.poll();
    assert.equal(s.messages[0].content, 'edited', 'edits refresh even when last message ID is unchanged');
}
{
    const pending = deferred(); const s = workspace(); s.activeConversation = {id:1};
    let requests = 0; const entered = deferred();
    get = async url => { requests++; if (url.endsWith('/messages')) { entered.resolve(); return pending.promise; } return {data:{data:[]}}; };
    post = async () => ({});
    const polling = s.poll(); await entered.promise;
    await s.poll();
    assert.equal(requests, 4, 'overlapping poll does not dispatch another request batch');
    s.activeConversation = {id:2}; s.selectionVersion++; s.messages = [{id:22}];
    pending.resolve(page([{id:11}])); await polling;
    assert.equal(s.messages[0].id, 22, 'late poll cannot display messages in the wrong chat');
    assert.equal(s.polling, false);
}
{
    const s = workspace();
    s.handleAttachmentSelection({target:{files:Array.from({length:12},(_,i)=>({name:`${i}.png`,type:'image/png'})),value:'test'}});
    assert.equal(created,10,'rejected attachments do not allocate preview URLs');
    s.conversationDrafts[2] = {draft:'',attachments:[{previewUrl:'blob:other'}]};
    s.destroy(); assert.equal(revoked.length,11,'destroy releases current and saved attachment previews');
}
assert.ok(!view.includes('x-init="init()"'));
assert.ok(!view.includes('@keydown.enter.prevent'));
console.log('Chat UI regression checks passed (initialization, races, drafts, sending, polling, attachments, keyboard).');
