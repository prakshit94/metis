const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const factories = {};
let pending = [];
const context = {
  Alpine: {data:(name, factory)=>factories[name]=factory}, createSearchComponent:()=>({}),
  document:{addEventListener:(_,fn)=>fn(),querySelector:()=>null,getElementById:()=>null},
  window:{location:{href:''}}, URLSearchParams, console,
  fetch:url=>new Promise(resolve=>pending.push({url,resolve})),
};
vm.createContext(context);
for (const file of ['users','roles-permissions']) {
  vm.runInContext(fs.readFileSync(`resources/js/components/${file}.js`,'utf8').replace(/^import .*;\n/gm,''),context);
}
const reply = (request,data,ok=true) => request.resolve({ok,status:ok?200:403,text:async()=>JSON.stringify(data),json:async()=>data});
(async()=>{
  const users=factories.userTable(); users.$nextTick=()=>{}; users._mapUser=u=>u;
  const old=users.loadUsers(); const latest=users.loadUsers();
  reply(pending[1],{data:[{id:2}],total:1}); await latest;
  reply(pending[0],{data:[{id:1}],total:100}); await old;
  assert.equal(users.users[0].id,2); assert.equal(users.totalUsers,1);
  pending=[]; const roles=factories.rolesPermissionsTable(); roles.$nextTick=()=>{};
  const oldRole=roles.loadRoles(); const newRole=roles.loadRoles();
  reply(pending[1],{data:[{id:2,name:'Latest'}],total:1}); await newRole;
  reply(pending[0],{data:[{id:1,name:'Stale'}],total:20}); await oldRole;
  assert.equal(roles.roles[0].name,'Latest'); assert.equal(roles.totalItems,1);
  pending=[]; roles.canViewPermissions=false; await roles.loadPermissions(); assert.equal(pending.length,0);
  const form=factories.userForm(); form.form={is_active:false,permissions:['existing']};
  const roleLoading=form.loadRoles(); reply(pending[0],[]); await Promise.resolve(); await Promise.resolve();
  // loadRoles also loads the permission options after its first await.
  while(pending.length<2) await Promise.resolve();
  reply(pending[1],[]); await roleLoading;
  assert.equal(form.form.is_active,false); assert.equal(form.form.permissions[0],'existing');
  pending=[];
  const hr=form.loadHrData();
  const waitRequest=async index=>{while(pending.length<=index) await Promise.resolve();};
  await waitRequest(0); reply(pending[0],{data:[]});
  await waitRequest(1); reply(pending[1],{data:[{id:1}],last_page:2});
  await waitRequest(2); reply(pending[2],{data:[{id:2}],last_page:2});
  await waitRequest(3); reply(pending[3],[]);
  await waitRequest(4); reply(pending[4],[]); await hr;
  assert.equal(form.managers.length,2,'All manager pages must be loaded');
  pending=[]; form.villageSearchQuery='old'; const oldVillage=form.searchVillages();
  form.resetForm(); reply(pending[0],{data:[{id:1}]}); await oldVillage;
  assert.equal(form.villageResults.length,0,'Closing/resetting a form invalidates village results');
  pending=[]; form.saving=true; await form.saveUser();
  const accessForm=factories.accessForm();
  accessForm.form.permissions=['outside'];
  const group={items:[{name:'one'},{name:'two'}]};
  accessForm.togglePermissionGroup(group);
  assert.deepEqual(Array.from(accessForm.form.permissions),['outside','one','two']);
  accessForm.togglePermissionGroup(group);
  assert.deepEqual(Array.from(accessForm.form.permissions),['outside']);
  accessForm.saving=true; await accessForm.saveItem(); assert.equal(pending.length,0);
  // A denied options request must throw, rather than return undefined as success.
  const denied=users.loadRoles(); reply(pending[0],{message:'Forbidden'},false); await denied;
  assert.equal(context.window.location.href,''); assert.equal(users.availableRoles.length,0);
  const revoked=[];let previews=0;
  context.URL={createObjectURL:()=>`blob:preview-${++previews}`,revokeObjectURL:url=>revoked.push(url)};
  const photoForm=factories.userForm();
  const photoEvent=()=>({target:{files:[{name:'photo.png',type:'image/png',size:100}],value:'photo.png'}});
  photoForm.handlePhotoUpload(photoEvent());photoForm.handlePhotoUpload(photoEvent());
  assert.equal(revoked[0],'blob:preview-1');
  photoForm.resetForm();assert.equal(revoked[1],'blob:preview-2');assert.equal(photoForm.form.photoFile,null);
  console.log('PASS: user/access request races, authorized directory loading, edit-state preservation, duplicate save guards, denied options.');
})().catch(err=>{console.error(err);process.exitCode=1;});
