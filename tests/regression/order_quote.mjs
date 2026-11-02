import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
const view = fs.readFileSync(new URL('../../resources/views/orders/create.blade.php', import.meta.url), 'utf8');
const methods = view.slice(view.indexOf('        validateOrderDraft() {'), view.indexOf('        async saveOrderData() {')).replace("@json(route('orders.preview', [], false))", "'/admin/orders/preview'");
const timers = new Set();
const context = { AbortController, setTimeout: callback => { timers.add(callback); return callback; }, clearTimeout: callback => timers.delete(callback) };
vm.createContext(context);
const app = vm.runInContext(`({ partyId: 1, warehouseId: 1, shippingAddressId: 1, sameAsShipping: true, cart: [{quantity: 1}], orderDate: '2026-11-02', formErrors: [], quoting: false, serverQuote: null, buildOrderPayload() { return {items: this.cart}; }, ${methods} })`, context);
const quote = {quote_hash: 'a'.repeat(64), grand_total: 100};
context.fetch = async (url, options) => {
    assert.equal(url, '/admin/orders/preview');
    assert.equal(options.credentials, 'same-origin');
    return {ok: true, json: async () => ({quote})};
};
context.document = {querySelector: () => ({content: 'csrf'})};
assert.equal((await app.fetchServerQuote()).grand_total, 100);
assert.equal(timers.size, 0);
for (const [status, message] of [[401, /session/], [403, /permission/], [419, /session/], [429, /Too many/], [500, /server/]]) {
    context.fetch = async () => ({ok: false, status, json: async () => { throw Error('HTML response'); }});
    assert.equal(await app.fetchServerQuote(), null);
    assert.match(app.formErrors[0], message);
    assert.equal(app.serverQuote, null);
    assert.equal(app.quoting, false);
    assert.equal(timers.size, 0);
}
context.fetch = async () => ({ok: false, status: 422, json: async () => ({errors: {shipping_address_id: ['Address needs a village.']}})});
await app.fetchServerQuote();
assert.equal(app.formErrors[0], 'Address needs a village.');
context.fetch = async () => ({ok: true, json: async () => ({})});
await app.fetchServerQuote();
assert.match(app.formErrors[0], /invalid order quote/);
context.fetch = () => new Promise((resolve, reject) => { /* timeout releases request below */ context.reject = reject; });
const pending = app.fetchServerQuote();
context.reject(Object.assign(new Error('timeout'), {name: 'AbortError'}));
await pending;
assert.match(app.formErrors[0], /timed out/);
context.fetch = () => { throw Error('Must not fetch invalid cart'); };
for (const quantity of [0, -1, 'invalid', Infinity]) {
    app.cart = [{quantity}];
    await app.fetchServerQuote();
    assert.match(app.formErrors[0], /quantity/);
}
console.log('PASS: admin quote route, session failures, validation errors, malformed quotes, timeout cleanup and invalid quantities');
