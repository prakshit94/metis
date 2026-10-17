// Run: TZ=Asia/Kolkata node tests/regression/hr_ui.cjs
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const factories = {}, events = {}, requests = [], opened = [];
const modal = {show() {}, hide() {}};
const documentStub = {
  addEventListener: (_, fn) => fn(),
  querySelector: selector => selector.includes('csrf') ? {content:'test', getAttribute:()=> 'test'} : selector.includes('Label') ? {innerHTML:''} : null,
  getElementById: () => null,
};
const context = {
  Alpine:{data:(name,factory)=> factories[name]=factory}, document:documentStub,
  Modal:{getOrCreateInstance:()=>modal}, bootstrap:{Modal:{getOrCreateInstance:()=>modal}},
  window:{addEventListener:(name,fn)=>{events[name]=fn;},removeEventListener(){},dispatchEvent(){},open:url=>opened.push(url), Swal:{fire:async()=>({isConfirmed:false})}},
  Swal:{fire:async()=>({isConfirmed:false})},
  URLSearchParams, Date, console, setTimeout, CustomEvent:class {constructor(name,options){this.type=name;this.detail=options?.detail;}},
  fetch:url=>new Promise(resolve=>requests.push({url,resolve})),
};
vm.createContext(context);
vm.runInContext(fs.readFileSync('resources/js/components/attendances.js','utf8').replace(/^import .*;\n/gm,''),context);
const reply = (req,data) => req.resolve({ok:true,status:200,text:async()=>JSON.stringify(data),json:async()=>data});
(async()=>{
  assert.equal(vm.runInContext('localDate(new Date(2026, 9, 17, 0, 5))',context),'2026-10-17');
  const table = factories.attendancesTable(1);
  table.currentMonthDate = new Date(2026,9,1);
  table.generateCalendarDays();
  assert.equal(table.calendarDays[0].date,'2026-09-27');
  assert.equal(new Set(table.calendarDays.map(day=>day.date)).size,42);
  table.userFilter='2'; table.exportSummary(); table.exportDetailed();
  assert.ok(opened.every(url=>url.includes('user_id=2')));
  const old = table.loadItems(), latest = table.loadItems();
  reply(requests[1],{data:[{id:2,date:'2026-10-17',user_id:2,status:'Present'}],total:1}); await latest;
  reply(requests[0],{data:[{id:1,date:'2026-10-16',user_id:1,status:'Absent'}],total:99}); await old;
  assert.equal(table.items[0].id,2); assert.equal(table.totalItems,1);
  table.items=[{id:1,user_id:1,date:'2026-10-17',status:'Present',check_in:'08:00',check_out:'09:00',total_time:'1h 00m'}, {id:2,user_id:2,date:'2026-10-17',status:'Late',check_in:'10:00',check_out:'12:00',total_time:'2h 00m'}];
  table.mapEventsToCalendar(); table.calculateStats();
  const day=table.calendarDays.find(day=>day.date==='2026-10-17');
  assert.equal(day.events.length,2); assert.equal(day.events[0].totalTime,'1h 00m'); assert.equal(day.events[1].totalTime,'2h 00m');
  assert.equal(table.stats.present,1); assert.equal(table.stats.late,1);
  const form=factories.attendanceForm(1); form.loadUsers=()=>{};form.$watch=()=>{};form.init();
  events['open-attendance-modal']({detail:{date:'2026-10-12',user_id:2}});
  assert.equal(form.editingId,null); assert.equal(form.form.date,'2026-10-12');assert.equal(form.form.user_id,2);
  events['open-attendance-modal']({detail:{id:9,date:'2026-10-12',user_id:2,check_in:'22:00',check_out:'06:00',status:'Present'}});
  assert.equal(form.form.check_out_time,'2026-10-13T06:00');
  form.saving=true; const count=requests.length;await form.saveItem();assert.equal(requests.length,count);
  requests.length=0;
  const leave=factories.leaveForm(1); leave.$nextTick=fn=>fn();
  const first=leave.fetchBalances(1), second=leave.fetchBalances(2);
  reply(requests[1],{data:[{id:2,leave_type:'Casual'}]});await second;
  reply(requests[0],{data:[{id:1,leave_type:'Sick'}]});await first;
  assert.equal(leave.userBalances[0].id,2);
  let inline=fs.readFileSync('resources/views/users/leaves.blade.php','utf8').split('<script>')[1].split('</script>')[0]
    .replace("{{ $isGlobalHr ? 'true' : 'false' }}", 'true')
    .replace(/@json\(\['id' => auth\(\)->id\(\), 'name' => auth\(\)->user\(\)->name\]\)/g,'{"id":1,"name":"Self"}');
  vm.runInContext(inline,context);
  const history=factories.leavesTable('history');history.totalPages=10;history.currentPage=5;
  assert.ok(Array.from(history.visiblePages).includes(10));assert.ok(Array.from(history.visiblePages).includes(5));
  assert.equal(vm.runInContext("Math.max(1, Math.round((new Date('2026-10-17') - new Date('2026-10-17')) / 86400000) + 1)",context),1);
  requests.length=0; const load=history.loadItems();assert.ok(requests[0].url.includes('history=1'));reply(requests[0],{data:[],total:0});await load;
  console.log('PASS: local dates, calendar uniqueness, employee exports, stale responses, per-employee summaries, date-only create, overnight edit, duplicate-save guard, balance races and history pagination.');
})().catch(error=>{console.error(error);process.exitCode=1;});
