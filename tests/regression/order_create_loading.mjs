import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const view = fs.readFileSync(new URL('../../resources/views/orders/create.blade.php', import.meta.url), 'utf8');
const method = view.slice(view.indexOf('        async searchProducts(reset = false) {'), view.indexOf('        get productVisiblePages()'));
const notices = [];
const timers = new Set();
const context = {
    AbortController, URLSearchParams, Date,
    window: { dispatchEvent: event => notices.push(event.detail) },
    CustomEvent: class { constructor(type, options) { this.detail = options.detail; } },
    setTimeout: callback => { timers.add(callback); return callback; },
    clearTimeout: callback => timers.delete(callback),
};
vm.createContext(context);
const app = vm.runInContext(`const createOrderSearchControllers = new WeakMap(); ({ searching: false, products: [], productQuery: '', categoryFilter: '', stockFilter: 'in-stock', perPage: 10, productPage: 1, warehouseId: 1, ${method} })`, context);
context.fetch = async () => ({ ok: true, json: async () => ({ data: [{ id: 1 }], total: 1, from: 1, to: 1, last_page: 1 }) });
await app.searchProducts();
assert.equal(app.searching, false);
assert.equal(app.products[0].id, 1);
assert.equal(timers.size, 0);
console.log('PASS: successful product load clears spinner and timeout');

context.fetch = (url, { signal }) => new Promise((resolve, reject) => signal.addEventListener('abort', () => reject(Object.assign(new Error('Aborted'), { name: 'AbortError' }))));
const stalled = app.searchProducts();
assert.equal(app.searching, true);
[...timers][0]();
await stalled;
assert.equal(app.searching, false);
assert.equal(app.products.length, 0);
assert.match(notices.at(-1).message, /timed out/);
assert.equal(timers.size, 0);
console.log('PASS: stalled request times out, reports failure and clears spinner');

const previous = app.searchProducts();
context.fetch = async () => ({ ok: true, json: async () => ({ data: [{ id: 2 }], total: 1 }) });
await app.searchProducts();
await previous;
assert.equal(app.products[0].id, 2);
assert.equal(app.searching, false);
assert.equal(notices.length, 1);
assert.equal(timers.size, 0);
console.log('PASS: cancelled search preserves newer results without a false error');

context.fetch = async () => { throw new Error('Offline'); };
await app.searchProducts();
assert.equal(app.searching, false);
assert.match(notices.at(-1).message, /Failed to load/);
assert.equal(timers.size, 0);
console.log('PASS: network failure clears spinner and timeout');
