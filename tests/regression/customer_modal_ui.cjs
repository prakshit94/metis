const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const template = fs.readFileSync('resources/views/components/add-customer-modal.blade.php', 'utf8');
let factory, queue = [], selectors = [];
const source = template.split('<script>')[1].split('</script>')[0]
  .replace(/\{!![\s\S]*?!!\}/g, '[]')
  .replace(/\{\{ asset\([\s\S]*?\) \}\}/g, '/avatar.png');
vm.runInNewContext(source, {
  document: { addEventListener: (_, callback) => callback(), querySelector: selector => { selectors.push(selector); return { value: '' }; } },
  Alpine: { data: (_, value) => { factory = value; } },
  fetch: () => new Promise(resolve => queue.push(resolve)), console, Date, encodeURIComponent,
});
const response = data => ({ ok: true, json: async () => data });
(async () => {
  const ui = factory();
  ui.form.referred_by_code = 'FIRST'; const first = ui.checkReferralCode();
  ui.form.referred_by_code = 'SECOND'; const second = ui.checkReferralCode();
  queue[1](response({valid:true, name:'Latest'})); await second;
  queue[0](response({valid:true, name:'Stale'})); await first;
  assert.equal(ui.referralName, 'Latest');
  assert.equal(ui.referralCheckedCode, 'SECOND');
  queue = [];
  ui.villageSearchQuery = 'first'; const village1 = ui.searchVillages();
  ui.villageSearchQuery = 'second'; const village2 = ui.searchVillages();
  queue[1](response({data:[{id:2}]})); await village2;
  queue[0](response({data:[{id:1}]})); await village1;
  assert.equal(ui.villageResults[0].id, 2);
  queue = [];
  ui.villageSearchQuery = 'pending'; const pending = ui.searchVillages();
  ui.cropSearch = 'old'; ui.showSourceDropdown = true; ui.showIrrigationDropdown = true; ui.showCropsDropdown = true;
  ui.resetForm();
  queue[0](response({data:[{id:3}]})); await pending;
  assert.equal(ui.villageResults.length, 0);
  assert.equal(ui.isSearchingVillages, false);
  assert.equal(ui.cropSearch, '');
  assert.equal(ui.showSourceDropdown || ui.showIrrigationDropdown || ui.showCropsDropdown, false);
  assert.equal(selectors.at(-1), '#addCustomerForm input[name="avatar"]');
  ui.isSubmitting = true; queue = []; await ui.submitForm({}); assert.equal(queue.length, 0);
  console.log('Customer modal: stale lookups, reset state, scoped avatar and duplicate-submit guard passed.');
})().catch(error => { console.error(error); process.exitCode = 1; });
