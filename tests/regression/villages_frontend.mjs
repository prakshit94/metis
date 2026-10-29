import fs from 'node:fs';
import vm from 'node:vm';
import assert from 'node:assert/strict';
const factories = new Map(); let fetchImpl; let hidden = 0; let confirmation = false;
const sandbox = {
  console, URLSearchParams, AbortController, setTimeout, clearTimeout,
  window: { villageStateScope: 'Gujarat', backendSyncing: false, addEventListener(){}, dispatchEvent(){} },
  document: { addEventListener(name, fn){if(name==='alpine:init')fn();}, querySelector(selector){return selector==='meta[name="csrf-token"]'?{content:'synthetic'}:{};}, getElementById(){return null;} },
  Alpine: { data(name, factory){factories.set(name,factory);}, $data(){return sandbox.table;} },
  Modal: {getOrCreateInstance(){return {hide(){hidden++;}, show(){}};}},
  Swal: {async fire(){return {isConfirmed:confirmation};}},
  createUserActionMenu(){return {};}, ApexCharts: class {}, CustomEvent: class {},
  fetch: (...args)=>fetchImpl(...args),
};
vm.createContext(sandbox);
vm.runInContext(fs.readFileSync('resources/js/components/villages.js','utf8').replace(/^import .*;\n/gm,''),sandbox);
const response = data=>({ok:true,status:200,text:async()=>JSON.stringify(data)});
const row = (id,name)=>({id,village_name:name,pincode:'380001',state_name:'Gujarat',mappings:[]});
const t=factories.get('villageTable')();t.$nextTick=()=>{};
let resolveOld, resolveNew;let calls=0;
fetchImpl=()=>new Promise(resolve=>{if(++calls===1)resolveOld=resolve;else resolveNew=resolve;});
const old=t.loadVillages();const fresh=t.loadVillages();
resolveNew(response({pagination:{data:[row(2,'New response')],current_page:1,last_page:1,total:1}}));await fresh;
resolveOld(response({pagination:{data:[row(1,'Stale response')],current_page:1,last_page:1,total:1}}));await old;
assert.equal(t.villages[0].id,2);assert.equal(t.isLoading,false);console.log('PASS: older filter responses cannot overwrite newer results');
t.selectedVillages=['999'];assert.equal(t.allVisibleSelected,false);
t.selectedVillages=['999','2'];assert.equal(t.allVisibleSelected,true);
t.villages.push(row(3,'Unselected visible'));assert.equal(t.someVisibleSelected,true);assert.equal(t.allVisibleSelected,false);
console.log('PASS: header selection reflects visible rows when other pages are selected');
const form=factories.get('villageForm')();form.resetForm();form.form.village_name='Manual';form.form.pincode='380001';
let requests=[];fetchImpl=async(url,options)=>{requests.push([url,options]);return response({message:'Saved',data:row(1,'Manual')});};
await form.saveVillage();assert.equal(requests.length,1);assert.equal(requests[0][0],'/api/villages');assert.equal(requests[0][1].method,'POST');assert.equal(hidden,1);console.log('PASS: saving the create form sends only the save request, never sync');
let syncCalls=0;sandbox.table={async syncPincodes(){syncCalls++;}};
form.loadVillage(row(1,'Manual'));form.form.village_name='Unsaved';await form.syncPincode();assert.equal(syncCalls,0);console.log('PASS: manual form sync is blocked while edits are unsaved');
form.loadVillage(row(1,'Original'));let resolveLookup;fetchImpl=()=>new Promise(r=>resolveLookup=r);
const syncing=form.syncPincode();await new Promise(r=>setTimeout(r,0));form.loadVillage(row(2,'New editor'));resolveLookup(response({data:row(1,'Old editor response')}));await syncing;
assert.equal(form.editingVillageId,2);assert.equal(form.form.village_name,'New editor');assert.equal(form.syncing,false);console.log('PASS: an older sync response cannot replace a newly opened editor');
requests=[];fetchImpl=async(url,o)=>{requests.push(url);return response({finished:true,success:true});};confirmation=false;await t.syncPincodes('380001');assert.equal(requests.length,0);console.log('PASS: cancelling explicit sync confirmation sends no mutation request');
assert.equal(form.form.state_name,'Gujarat');console.log('Village frontend checks complete.');
