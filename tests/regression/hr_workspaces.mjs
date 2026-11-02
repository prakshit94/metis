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
table.rosters = [{id: 1, user_id: 1, date: '2026-10-05', day_type: 'Week Off'}, {id: 2, user_id: 1, date: '2026-10-10', day_type: 'Working'}];
table.generateCalendarDays();
table.mapEventsToCalendar();
const day = date => table.calendarDays.find(day => day.date === date);
assert.equal(day('2026-10-02').events.length, 0, 'holiday must not invent an absence');
assert.equal(day('2026-10-05').events.length, 0, 'rostered day off must not invent an absence');
assert.equal(day('2026-10-06').events[0].status, 'No record');
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
