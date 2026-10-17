// Run: node tests/regression/files_ui.cjs. No application or file mutations.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
let factory, request;
const notices = [];
const context = {Alpine:{data(name, value){if(name === 'filesComponent') factory = value;}},
  createSearchComponent:()=>({}), document:{addEventListener(name, fn){if(name==='alpine:init')fn();},querySelector:()=>null},
  console:{error(){}}, fetch:(...args)=>request(...args), alert(){}, confirm:()=>true};
vm.createContext(context);
vm.runInContext(fs.readFileSync('resources/js/components/files.js','utf8').replace(/^import .*;\n/gm,''),context);
const component = () => { const state = factory(); state.showNotification = (message,type)=>notices.push({message,type}); return state; };
const files = [{id:'1',name:'Alpha.pdf',type:'document',typeLabel:'Document',size:'2 KB',sizeBytes:2048,modifiedDate:'Oct 01, 2026 10:00 AM'},
{id:'2',name:'Beta.png',type:'image',typeLabel:'Image',size:'1 KB',sizeBytes:1024,modifiedDate:'Oct 17, 2026 10:00 AM'},
{id:'asset:logo.png',name:'Logo.png',isAsset:true,type:'image',typeLabel:'Image',size:'1 KB',sizeBytes:1024,modifiedDate:'Sep 01, 2026 10:00 AM'}];
(async()=>{
  const state = component(); let loads=0;
  request = async()=>{loads++;return {ok:true,json:async()=>files};};
  await Promise.all([state.init(),state.init()]); assert.equal(loads,1);
  assert.equal(state.storageUsed,'4 KB');
  state.sortBy='date';state.sortFiles();assert.equal(state.currentFiles[0].id,'2');
  state.searchQuery='Alpha';state.filterFiles();state.selectedFiles=['1'];
  await state.refreshFiles();assert.equal(state.currentFiles.length,1);assert.equal(state.allVisibleSelected,true);
  state.searchQuery='Beta';state.filterFiles();assert.equal(state.selectedFiles.length,0);assert.equal(state.allVisibleSelected,false);
  state.selectAll();assert.equal(state.selectedFiles[0],'2');state.selectAll();assert.equal(state.selectedFiles.length,0);
  const old = state.allFiles;notices.length=0;
  request=async()=>({ok:false,status:500,json:async()=>({})});
  await state.refreshFiles();assert.equal(state.allFiles,old);assert.ok(notices.every(item=>item.type!=='success'));assert.equal(state.loading,false);
  state.selectedFiles=['2'];notices.length=0;
  assert.equal(await state.performFileDelete(files[1]),false);assert.equal(state.selectedFiles.length,1);
  await state.setLoginBackground(files[1]);assert.ok(notices.every(item=>item.type!=='success'));
  const deleted=[];
  state.currentFiles=files;state.selectedFiles=files.map(file=>file.id);state.loadFiles=async()=>true;
  request=async(url,options)=>{deleted.push(JSON.parse(options.body).id);return {ok:true};};
  await state.performSelectedDelete();assert.deepEqual(deleted,['1','2']);assert.equal(state.selectedFiles[0],'asset:logo.png');
  assert.equal(state.escapeHtml('<img onerror="x">'),'&lt;img onerror=&quot;x&quot;&gt;');
  const upload = component(); let refreshed = 0;
  upload.loadFiles = async()=>{refreshed++;return true;};
  context.FormData = class {append(key,value){this.file=value;}};
  context.Swal = {fire:async()=>({isConfirmed:true,value:[{name:'good'},{name:'bad'}]})};
  request = async(url, options)=>options.body.file.name==='good'
    ? {ok:true,json:async()=>({})}
    : {ok:false,json:async()=>({message:'Upload rejected'})};
  notices.length=0;upload.uploadFile();await new Promise(resolve=>setImmediate(resolve));
  assert.equal(refreshed,1,'partial upload refreshes successful files');
  assert.ok(notices.some(item=>item.type==='success' && item.message.startsWith('1 file')));
  assert.ok(notices.some(item=>item.type==='error' && item.message==='Upload rejected'));
  delete context.Swal;
  const race=component();let resolveOld;
  request=()=>new Promise(resolve=>{resolveOld=resolve;});const first=race.loadFiles();
  request=async()=>({ok:true,json:async()=>[files[1]]});await race.loadFiles();
  resolveOld({ok:true,json:async()=>[files[0]]});await first;assert.equal(race.allFiles[0].id,'2');
  console.log('PASS: Files initialization, refresh, search, sorting, real size, selection, request races, failed actions, protected asset deletion, and escaped file details.');
})().catch(error=>{console.error(error);process.exitCode=1;});
