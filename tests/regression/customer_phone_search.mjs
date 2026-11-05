import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
const source = fs.readFileSync(new URL('../../resources/views/dashboard.blade.php', import.meta.url), 'utf8');
const factory = source.slice(source.indexOf('window.customerSearchApp = function()'), source.indexOf('</script>', source.indexOf('window.customerSearchApp = function()')));
for (const phone of ['8980145587']) {
  const window = {location: {href: ''}};
  let requested;
  vm.runInNewContext(factory, {window, fetch: async url => {requested = url; return {ok:true, json:async () => ({found:true, redirect:'/admin/orders/create?customer_id=42'})};}, setTimeout});
  const app = window.customerSearchApp();
  app.searchPhone = phone;
  app.searchCustomer();
  await new Promise(resolve => setImmediate(resolve));
  assert.equal(requested, '/admin/customers/search-by-phone?phone=8980145587');
  assert.equal(window.location.href, '/admin/orders/create?customer_id=42');
}
for (const phone of ['', '123456789', '12345678901', '+91 89801 45587', '08980145587', '89801 45587', 'abcdefghij', '8980145587\n']) {
  const window = {};
  let requested = false;
  vm.runInNewContext(factory, {window, fetch: () => { requested = true; }, setTimeout});
  const app = window.customerSearchApp();
  app.searchPhone = phone;
  app.searchCustomer();
  assert.equal(requested, false, phone);
  assert.equal(app.searchPhone, phone, 'invalid numbers must not be silently shortened or normalized');
  assert.equal(app.errorMsg, 'Please enter exactly 10 digits.');
}
console.log('PASS: exactly 10 digits searches successfully; short, long and non-digit inputs never request a search.');
