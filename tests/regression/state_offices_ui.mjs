import fs from 'node:fs';
import vm from 'node:vm';
import assert from 'node:assert/strict';
const factories=new Map(), pending=[], notices=[];
const context={
 document:{addEventListener:(_,f)=>f(),querySelector:()=>({content:'fixture-csrf'}),getElementById:()=>({addEventListener(){}})},
 Alpine:{data:(name,f)=>factories.set(name,f)},
 window:{AdminApp:{notificationManager:{show:(...args)=>notices.push(args)}},confirm:()=>true},
 bootstrap:{Modal:{getOrCreateInstance:()=>({show(){},hide(){}})}},
 URLSearchParams,
 fetch:(url,options)=>new Promise(resolve=>pending.push({url,options,resolve})),
};
const blade=fs.readFileSync(new URL('../../resources/views/teams/index.blade.php',import.meta.url),'utf8');
vm.runInNewContext(blade.match(/<script>([\s\S]*?)<\/script>/)[1],context);
const state=factories.get('organizationTree')();
const respond=(n,data,ok=true)=>pending[n].resolve({ok,json:async()=>data});
const page={data:[{id:1,name:'Gujarat',code:'GJ',members_count:2,offices_count:2}],states:['Gujarat'],state_codes:{GJ:'Gujarat'},current_page:1,last_page:1,from:1,to:1,total:1};
const first=state.loadStates();state.search='Gujarat';const latest=state.loadStates();respond(1,page);await latest;respond(0,{...page,data:[{id:9}]});await first;
assert.equal(state.teams[0].id,1);assert.equal(state.from,1);assert.equal(state.loading,false);
console.log('PASS: state tree discards stale searches and uses accurate pagination');
let start=pending.length;const oldOffice=state.loadOffices(1,1);const newOffice=state.loadOffices(1,2);respond(start+1,{data:[{id:2}],current_page:2,last_page:2,total:11});await newOffice;respond(start,{data:[{id:1}],current_page:1,last_page:2,total:11});await oldOffice;assert.equal(state.offices[1][0].id,2);
assert.equal(state.officePages[1],2);
console.log('PASS: per-state office paging discards old responses independently');
state.codes={GJ:'Gujarat'};state.openState();state.form.code='gj';state.syncStateCode();assert.equal(state.form.state_name,'Gujarat');
state.openOffice({id:1,name:'Gujarat',code:'GJ'});state.form.name='Ahmedabad';state.form.code='amd';
start=pending.length;const save=state.save();await state.save();assert.equal(pending.length,start+1);
const payload=JSON.parse(pending[start].options.body);assert.equal(payload.team_id,1);assert.equal(payload.code,'AMD');assert.equal('state_name' in payload,false);assert.equal(pending[start].options.headers['X-CSRF-TOKEN'],'fixture-csrf');
respond(start,{message:'Office already exists'},false);await save;assert.equal(state.formError,'Office already exists');assert.equal(state.saving,false);
console.log('PASS: office modal sends parent state, prevents duplicate submissions, and retains validation errors');
assert.equal(blade.includes('dropdown-menu'),false);
assert.ok(blade.includes('modal-dialog-scrollable'));
assert.ok(blade.includes('state-wide visibility'));
console.log('PASS: tree uses visible action buttons and scrollable mobile form while explaining unchanged visibility');

state.openOffice({id:1,name:'Gujarat',code:'GJ',resolved_state_name:'Gujarat'},{id:6,name:'Legacy office',address:'Existing street',city:'Ahmedabad',pincode:'380001'});
assert.equal(state.form.address_line_1,'Existing street');assert.equal(state.form.city,'Ahmedabad');assert.equal(state.form.state,'Gujarat');
state.form.address_line_2='Landmark';state.villageSearchQuery='ab';
start=pending.length;await state.searchVillages();assert.equal(pending.length,start);
state.villageSearchQuery='Office';const oldSearch=state.searchVillages();
assert.ok(pending[start].url.startsWith('/api/villages/search?'));
state.openOffice({id:2,name:'Rajasthan',code:'RJ',resolved_state_name:'Rajasthan'});
respond(start,{data:[{id:88,village_name:'Old search'}]});await oldSearch;
assert.equal(state.villageResults.length,0);assert.equal(state.parentId,2);
state.selectVillage({id:9,village_name:'Fixture village',post_so_name:'Fixture PO',taluka_name:'Fixture Taluka',district_name:'Fixture District',state_name:'Rajasthan',pincode:'302001'});
assert.equal(state.form.village_id,9);assert.equal(state.form.city,'Fixture District');assert.equal(state.form.post_office,'Fixture PO');assert.equal(state.parentId,2);assert.equal(state.parentCode,'RJ');
state.form.name='New office';state.form.code='NEW';state.form.address_line_1='Street';state.form.address_line_2='Landmark';
start=pending.length;const addressSave=state.save();
const addressPayload=JSON.parse(pending[start].options.body);
assert.equal(addressPayload.address,'Street');assert.equal(addressPayload.address_line_2,'Landmark');assert.equal(addressPayload.village_id,9);assert.equal(addressPayload.state,'Rajasthan');assert.equal(addressPayload.team_id,2);
respond(start,{message:'Fixture error'},false);await addressSave;
state.clearVillage();assert.equal(state.form.village_id,null);assert.equal(state.form.city,'Fixture District');assert.equal(state.form.address_line_2,'Landmark');
state.villageSearchQuery='Error';start=pending.length;const failedSearch=state.searchVillages();respond(start,{message:'Forbidden'},false);await failedSearch;
assert.ok(state.villageError.includes('manually'));assert.equal(state.villageLoading,false);
assert.ok(blade.includes('Address Details'));assert.ok(blade.includes('Address Line 2'));assert.ok(blade.includes('bg-body-tertiary'));assert.ok(blade.includes('modal-lg'));
console.log('PASS: office address preserves legacy fields, uses Users village lookup/autofill, ignores stale modal responses and keeps parent state unchanged');
