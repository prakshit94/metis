// Run: node tests/regression/orders_ui.cjs. No application/database writes.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const source = fs.readFileSync(path.join(__dirname, '../../resources/js/components/orders.js'), 'utf8')
  .replace(/^import .*;\n/gm, '');
let factory;
const watchers = {};
const ticks = [];
let observed = 0;
let disconnected = 0;
const context = {
  Alpine: {data: (name, value) => { if (name === 'orderTable') factory = value; }},
  createSearchComponent: () => ({}),
  document: {
    addEventListener: (name, fn) => { if (name === 'alpine:init') fn(); },
    getElementById: () => null,
    documentElement: {},
  },
  window: {
    location: {pathname: '/orders', search: '?status=pending,unfulfillable,pending_confirmation,confirmed,processing,ready_to_ship&date=30d&from_date=2026-09-16&to_date=2026-10-16&warehouse=1,2&limit=25&page=1&sort_field=id&sort_direction=desc'},
    addEventListener: () => {},
  },
  MutationObserver: class {observe() {observed++;} disconnect() {disconnected++;}},
  URLSearchParams,
  URL,
  setTimeout: () => 1,
  clearTimeout: () => {},
};
vm.createContext(context);
vm.runInContext(source, context);
const ui = factory();
ui.$nextTick = callback => ticks.push(callback);
ui.$watch = (name, callback) => { watchers[name] = callback; };
let loads = 0;
ui.loadOrders = () => loads++;
ui.init();
ui.warehousesList = [{id: 1, name: 'First'}, {id: 2, name: 'Second'}];
ui.allowedFilterStatuses = ['pending','unfulfillable','pending_confirmation','confirmed','processing','ready_to_ship'];
ui.init();
assert.equal(ticks.length, 1, 'Automatic init and x-init share one initialization');
ticks.shift()();
assert.equal(loads, 1);
assert.equal(observed, 1);
assert.deepEqual(Array.from(ui.warehouseFilter), [1, 2]);
assert.equal(ui.statusFilter.length, 6);
assert.equal(ui.fromDate, '2026-09-16');
assert.equal(ui.toDate, '2026-10-16');
assert.equal(ui.itemsPerPage, 25);
assert.equal(ui.sortField, 'id');
assert.equal(ui.sortDirection, 'desc');
watchers.warehouseFilter([1, 2]);
watchers.visibleWarehouseStat('');
assert.deepEqual(Array.from(ui.warehouseFilter), [1, 2], 'Combined view preserves multiple warehouse filters');
watchers.visibleWarehouseStat('First');
assert.deepEqual(Array.from(ui.warehouseFilter), [1]);
watchers.warehouseFilter(ui.warehouseFilter);
assert.equal(ui.visibleWarehouseStat, 'First');
watchers.visibleWarehouseStat('');
assert.deepEqual(Array.from(ui.warehouseFilter), []);
ui.destroy();
assert.equal(disconnected, 1);
console.log('PASS: initialize once after server lists; requested filters preserved; warehouse selections stay arrays; multi-select survives combined view; theme observer cleaned up.');

context.window.location.search = '?warehouse=2';
const singleWarehouse = factory();
singleWarehouse.warehousesList = [{id: 2, name: 'Second'}];
singleWarehouse.$nextTick = callback => callback();
singleWarehouse.$watch = () => {};
singleWarehouse.loadOrders = () => {};
singleWarehouse.init();
assert.equal(singleWarehouse.visibleWarehouseStat, 'Second', 'Warehouse URL must initialize the overview selector');
singleWarehouse.destroy();
console.log('PASS: single warehouse URL initializes the overview selection.');

assert.equal(ui.creatorPhotoUrl(null), '/assets/images/default_avatar.jpeg');
assert.equal(ui.creatorPhotoUrl('users/photos/user.jpg'), '/storage/users/photos/user.jpg');
assert.equal(ui.creatorPhotoUrl('public/storage/users/photos/user.jpg'), '/storage/users/photos/user.jpg');
assert.equal(ui.creatorPhotoUrl('storage/users/photos/user.jpg'), '/storage/users/photos/user.jpg');
assert.equal(ui.creatorPhotoUrl('http://127.0.0.1:8000/storage/users/photos/user.jpg'), '/storage/users/photos/user.jpg');
assert.equal(ui.creatorPhotoUrl('https://images.example.com/user.jpg'), 'https://images.example.com/user.jpg');
console.log('PASS: creator avatars normalize upload paths and local URLs while preserving external images.');

context.window.location.search = '?payment_status=paid,partially_paid,no_invoice,invalid&warehouse=2';
const paymentUi = factory();
paymentUi.$nextTick = callback => callback();
paymentUi.$watch = () => {};
paymentUi.loadOrders = () => {};
paymentUi.init();
assert.equal(paymentUi.paymentStatusFilter.join(','), 'paid,partially_paid,no_invoice');
assert.equal(paymentUi.paymentStatusInfo('partially_paid').label, 'Partially Paid');
assert.equal(paymentUi.paymentStatusInfo('no_invoice').label, 'No Invoice');
assert.equal(paymentUi.paymentStatusInfo('cancelled').label, 'Invoice Cancelled');
assert.equal(paymentUi.paymentStatusInfo('unknown').label, 'Unknown');
assert.equal(paymentUi.hasActiveAdvancedFilters(), true, 'Payment selection highlights the Filters button');
paymentUi.currentPage = 4;
paymentUi.filterOrders();
assert.equal(paymentUi.currentPage, 1);
paymentUi.clearFilters();
assert.equal(paymentUi.paymentStatusFilter.length, 0);
paymentUi.destroy();
console.log('PASS: payment filter URL restoration, allowed values, labels, pagination reset and clearing.');
