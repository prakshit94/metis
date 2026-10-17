import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
const view = fs.readFileSync(new URL('../../resources/views/chat/index.blade.php', import.meta.url), 'utf8');
const source = view.slice(view.indexOf('    function chatWorkspace(initial)'), view.lastIndexOf('</script>')).replaceAll('@js(auth()->id())', '1');
let get, post, timers = 0, revoked = [], created = 0;
const context = vm.createContext({
    axios: { get: (...args) => get(...args), post: (...args) => post(...args) },
    localStorage: { getItem: () => null, setItem() {}, removeItem() {} },
    window: { dispatchEvent() {}, location:{origin:'http://127.0.0.1:8000',hostname:'127.0.0.1'} },
    document: {querySelector:()=>({content:''})}, CustomEvent: class {}, console,
    setInterval: () => ++timers, clearInterval() {},
    FormData: class { append() {} },
    URL: { createObjectURL: () => `blob:${++created}`, revokeObjectURL: url => revoked.push(url) },
});
vm.runInContext(fs.readFileSync(new URL('../../resources/js/utils/profile-images.js', import.meta.url), 'utf8').replace(/^export /gm, ''), context);
vm.runInContext('window.ProfileImages = {userPhotoUrl, defaultUserPhoto, handlePhotoError};', context);
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
{
    const s = workspace();
    s.users = [{id:2,is_online:false}, {id:3,is_online:true}, {id:'1',is_online:false}];
    s.selfUser = {id:1,isMe:true,is_online:true};
    assert.equal(s.presenceUsers.length, 3, 'presence totals include self exactly once across ID types');
    assert.equal(s.onlineLabel, '2 online now', 'both headers count the same online users as the sidebar');
    s.userFilter = 'online';
    assert.equal(s.visibleUsers.length, 2, 'online filter agrees with header count');
    assert.equal(s.getUserPhotoUrl({photo:'public/storage/photos/a.jpg'}), '/storage/photos/a.jpg');
    assert.equal(s.getUserPhotoUrl({photo:'storage/photos/a.jpg'}), '/storage/photos/a.jpg');
    assert.equal(s.getUserPhotoUrl({photo:'uploads\\a.jpg'}), '/storage/uploads/a.jpg');
    assert.equal(s.getUserPhotoUrl({photo:'assets/images/default_male.png'}), '/assets/images/default_male.png');
    const image = {getAttribute: () => '/storage/missing.jpg',setAttribute(name,value){this[name]=value;},style:{}};
    s.handlePhotoError({target:image});
    assert.equal(image.src, '/assets/images/default_avatar.jpeg');
    assert.equal(s.getUserPhotoUrl({photo:'missing.jpg',gender:'Female'}), '/assets/images/default_female.png');
    assert.equal(s.getUserPhotoUrl({photo:null}), '/assets/images/default_avatar.jpeg');
}
console.log('Chat avatar and presence count regression checks passed.');
{
    const s = workspace();
    get = async () => page([{id:10,content:'Loaded'}]);
    post = async () => { throw new Error('Read receipt unavailable'); };
    const originalError = console.error; console.error = () => {};
    try {
        const conversation = {id:1,unread_count:2};
        await s.selectConversation(conversation);
        assert.equal(s.messages[0].id,10);
        assert.equal(s.errorMessage,'','read receipt failures do not report message loading failure');
        assert.equal(conversation.unread_count,2,'failed receipt preserves unread count');
        get = async () => { throw {response:{status:419}}; };
        await s.selectConversation({id:2});
        assert.match(s.errorMessage,/session has expired/);
    } finally { console.error = originalError; }
}
console.log('Message loading and read receipt failure checks passed.');
{
    const s = workspace();
    s.conversations = [
        {id:1,type:'direct',unread_count:2,is_pinned:false,updated_at:'2026-10-17'},
        {id:2,type:'group',name:'Support',unread_count:1,is_pinned:true,updated_at:'2026-10-16'},
        {id:3,type:'group',name:'Operations',unread_count:0,is_pinned:false,updated_at:'2026-10-15'},
    ];
    s.users=[{id:2,name:'Support user'}];
    assert.equal(s.filteredConversations.length,3);
    s.conversationType='direct';assert.equal(s.filteredConversations.length,1);
    s.conversationType='group';assert.equal(s.filteredConversations.length,2);
    s.filter='unread';assert.equal(s.filteredConversations.length,1);
    s.filter='pinned';assert.equal(s.filteredConversations[0].id,2);
    s.filter='all';s.search='Support';assert.equal(s.filteredConversations.length,1);
    assert.equal(s.searchResults.users.length,0,'group searches exclude individual people');
    s.conversationType='all';assert.equal(s.searchResults.users.length,1);
    assert.equal(s.conversations[0].id,1,'filtering does not reorder the source collection');
}
console.log('Chat type filters compose with search, unread and pinned filters.');
