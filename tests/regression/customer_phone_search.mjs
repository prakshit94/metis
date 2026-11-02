import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
const source = fs.readFileSync(new URL('../../resources/views/dashboard.blade.php', import.meta.url), 'utf8');
const factory = source.slice(source.indexOf('window.customerSearchApp = function()'), source.indexOf('</script>', source.indexOf('window.customerSearchApp = function()')));
for (const phone of ['8980145587', '+91 89801 45587', '08980145587']) {
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
console.log('PASS: plain, country-prefixed and leading-zero phone searches open the returned customer profile.');
