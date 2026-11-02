import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
const source = fs.readFileSync(new URL('../../resources/js/components/attendances.js', import.meta.url), 'utf8').replace(/^import .*;\n/gm, '');
const factories = {};
const context = {
  Alpine: {data: (name, factory) => factories[name] = factory},
  document: {addEventListener: (event, callback) => callback(), querySelector: () => ({content: 'csrf'}), getElementById: () => null},
  window: {location: {search: ''}, addEventListener() {}, removeEventListener() {}, dispatchEvent() {}},
  URLSearchParams, Date, Intl, AbortController, setTimeout, clearTimeout,
  Swal: {fire: async () => ({isConfirmed: false})}, Modal: {},
};
vm.createContext(context);
vm.runInContext(source, context);
const table = factories.attendancesTable(1, {timeZone: 'Asia/Kolkata'});
table.currentMonthDate = new Date(2026, 9, 1);
table.generateCalendarDays();
assert.equal(table.calendarDays.length, 42);
table.usersList = [{id: 1, name: 'Employee', joining_date: '2026-10-01'}];
table.holidays = [{id: 1, date: '2026-10-02', name: 'Holiday'}];
table.rosters = [{id: 1, user_id: 1, date: '2026-10-05', day_type: 'Week Off'}, {id: 2, user_id: 1, date: '2026-10-10', day_type: 'Working'}, {id: 3, user_id: 1, date: '2026-10-07', day_type: 'Planned Leave'}, {id: 4, user_id: 1, date: '2026-10-08', day_type: 'Comp Off'}];
table.generateCalendarDays();
table.mapEventsToCalendar();
const day = date => table.calendarDays.find(day => day.date === date);
assert.equal(day('2026-10-02').events.length, 0, 'holiday must not invent an absence');
assert.equal(day('2026-10-05').events.length, 0, 'rostered day off must not invent an absence');
assert.equal(day('2026-10-06').events[0].status, 'No record');
assert.equal(day('2026-10-07').events.length, 0, 'planned leave must not invent missing attendance');
assert.equal(day('2026-10-08').events.length, 0, 'planned compensatory leave must not invent missing attendance');
assert.equal(day('2026-10-10').events[0].status, 'No record', 'explicit working weekends remain visible');
table.items = [{id: 1, user_id: 1, date: '2026-10-06', status: 'Absent', check_in: null, check_out: null}];
table.totalItems = 200;
table.calculateStats();
table.mapEventsToCalendar();
assert.equal(table.stats.total, 1, 'loaded counts must not report the paginated total');
assert.equal(day('2026-10-06').events[0].status, 'Absent', 'recorded absence is preserved');
table.items = [
  {id: 1, user_id: 1, date: '2026-10-06', status: 'Present', check_in: '22:00', check_out: '06:00', total_time: '8h 00m'},
  {id: 2, user_id: 1, date: '2026-10-06', status: 'Present', check_in: null, check_out: '17:00', total_time: null},
];
table.mapEventsToCalendar();
assert.equal(day('2026-10-06').events[0].checkOut, '06:00', 'overnight checkout is later than a same-day afternoon checkout');
table.rangeStart = '2026-11-02'; table.rangeEnd = '2026-11-01';
table.loadItems = () => { throw Error('Invalid range must not fetch'); };
table.filterItems();
assert.match(table.error, /end date/);
const leaveForm = factories.leaveForm(1);
leaveForm.form.leave_type = 'Casual'; leaveForm.form.start_date = '2026-11-02'; leaveForm.form.end_date = '2026-11-01';
await leaveForm.saveItem();
assert.equal(leaveForm.saving, false);
assert.match(leaveForm.error, /valid start and end dates/);

const template = fs.readFileSync(new URL('../../resources/views/users/leaves.blade.php', import.meta.url), 'utf8');
const leaveTableSource = template.slice(template.indexOf("        Alpine.data('leavesTable'"), template.indexOf("        Alpine.data('balancesTable'"))
  .replace(/\{\{ \$isGlobalHr \? 'true' : 'false' \}\}/g, 'false').replace(/\{\{ auth\(\)->id\(\) \}\}/g, '1');
context.hrFetch = async () => { throw Error('The server is unavailable.'); };
vm.runInContext(leaveTableSource, context);
const leaves = factories.leavesTable('pending');
await leaves.loadItems();
assert.equal(leaves.isLoading, false);
assert.equal(leaves.error, 'The server is unavailable.');
leaves.fromDate = '2026-11-02'; leaves.toDate = '2026-11-01';
leaves.loadItems = () => { throw Error('Invalid range must not fetch'); };
leaves.filterItems();
assert.match(leaves.error, /end date/);
leaves.items = [{id: 1, user_id: 1, status: 'Approved'}]; leaves.selectedItems = ['1'];
assert.equal(leaves.canDeleteSelection, false, 'processed requests cannot be bulk deleted');
console.log('PASS: calendar dates, roster/holiday gaps, recorded absence, loaded counts, invalid dates, inline failures and processed leave guards');

let timeoutCallback;
let cleared = false;
context.setTimeout = callback => { timeoutCallback = callback; return 1; };
context.clearTimeout = () => { cleared = true; };
context.fetch = (url, {signal}) => new Promise((resolve, reject) => signal.addEventListener('abort', () => reject(Object.assign(new Error('Aborted'), {name: 'AbortError'}))));
const stalled = context.apiFetch('/api/attendances');
timeoutCallback();
await assert.rejects(stalled, /timed out/);
assert.equal(cleared, true);
console.log('PASS: stalled attendance requests time out and clean up their timers');

const roster = factories.rosterManager();
roster.choose({target: {files: [{name: 'roster.jpeg', size: 100}]}});
assert.equal(roster.file, null);
assert.match(roster.error, /CSV or XLSX/);
roster.choose({target: {files: [{name: 'roster.xlsx', size: 1024}]}});
assert.equal(roster.error, '');
roster.preview = Array.from({length: 205}, (_, id) => ({id}));
assert.equal(roster.previewPages, 3);
roster.previewPage = 3;
assert.equal(roster.visiblePreview.length, 5);
console.log('PASS: roster file validation and bounded preview pagination');

let downloaded;
context.Blob = Blob;
context.URL = {createObjectURL(blob) { downloaded = blob; return 'blob:test'; }, revokeObjectURL() {}};
context.document.createElement = () => ({click() {}});
roster.templateMode = 'month'; roster.templateMonth = '2028-02'; roster.matrixTemplate();
let csv = await downloaded.text();
assert.equal(csv.split('\n')[0].split(',').length, 32, 'leap February has 29 date columns');
assert.match(csv, /2028-02-29/);
assert.doesNotMatch(csv, /2028-03-01/);
assert.equal(csv.trim().split('\n').length, 7, 'template includes five sample employees');
for (const value of ['08:00-17:00', '22:00-06:00', 'WO', 'C/OFF', 'L']) assert.ok(csv.includes(value), 'template demonstrates ' + value);
roster.templateMode = 'custom'; roster.templateStart = '2026-11-29'; roster.templateEnd = '2026-12-02'; roster.matrixTemplate();
csv = await downloaded.text();
assert.equal(csv.split('\n')[0].split(',').length, 7, 'custom range spans month boundaries inclusively');
roster.templateStart = '2026-11-02'; roster.templateEnd = '2026-11-02'; roster.matrixTemplate();
csv = await downloaded.text();
for (const value of ['08:00-17:00', '22:00-06:00', 'WO', 'C/OFF', 'L']) assert.ok(csv.includes(value), 'one-day template demonstrates ' + value);
roster.templateStart = '2026-02-30'; roster.matrixTemplate();
assert.match(roster.error, /valid date range/);
roster.previewDates = ['2026-11-02', '2026-11-03'];
roster.preview = [{user_id: 1, employee_id:'EMP1', name:'Employee', lob:'Sales', date:'2026-11-02', day_type:'Working', shift_start:'08:00', shift_end:'17:00'}, {user_id: 1, date:'2026-11-03', day_type:'Week Off'}];
assert.equal(roster.gridEmployees.length, 1);
assert.equal(roster.gridEmployees[0].lob, 'Sales');
assert.equal(roster.cellLabel(roster.gridEmployees[0].days['2026-11-02']), '08:00–17:00');
assert.equal(roster.cellLabel(roster.gridEmployees[0].days['2026-11-03']), 'WO');
assert.equal(roster.cellLabel(undefined), '—');
context.FormData = class { append() {} };
context.CustomEvent = class {};
const toasts = [];
context.document.getElementById = () => ({appendChild(el) { toasts.push(el); }});
context.document.createElement = () => ({setAttribute() {}, querySelector() { return {}; }, remove() {}});
context.fetch = async () => ({ok:true, json:async () => ({data:roster.preview, dates:roster.previewDates, message:'Roster imported.'})});
roster.hash = 'signed-preview'; await roster.upload(true);
assert.equal(toasts.length, 1, 'successful commit shows a toast');
assert.equal(roster.hash, '', 'successful commit clears confirmation token');
context.fetch = async () => ({ok:false, json:async () => ({message:'Invalid roster.'})});
roster.hash = 'signed-preview'; await roster.upload(true);
assert.equal(toasts.length, 1, 'failed imports never show a success toast');
console.log('PASS: monthly/custom templates, employee matrix grouping and commit-only success toast');

let exportedUrl;
context.document.createElement = () => ({click() {}});
context.fetch = async url => { exportedUrl = url; return {ok:true, blob:async () => new Blob(['workbook'])}; };
table.currentMonthDate = new Date(2026, 10, 15); table.currentView = 'calendar'; table.userFilter = '1';
await table.exportRoster();
assert.match(exportedUrl, /start_date=2026-11-01/);
assert.match(exportedUrl, /end_date=2026-11-30/);
assert.match(exportedUrl, /user_id=1/);
table.currentView = 'list'; table.dateFilter = ''; table.rangeStart = '2026-11-20'; table.rangeEnd = '2026-12-05';
await table.exportRoster();
assert.match(exportedUrl, /start_date=2026-11-20/);
assert.match(exportedUrl, /end_date=2026-12-05/);
roster.templateMode = 'month'; roster.templateMonth = '2028-02';
await roster.matrixTemplate(true);
assert.match(exportedUrl, /end_date=2028-02-29/);
console.log('PASS: editable roster exports use displayed month, employee and custom date range');
