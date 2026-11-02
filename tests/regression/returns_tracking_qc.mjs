import fs from 'node:fs';
import vm from 'node:vm';
import assert from 'node:assert/strict';
const factories = {};
let download;
const context = {
  Alpine: {data(name, factory) { factories[name] = factory; }},
  document: {addEventListener(event, callback) { callback(); }, createElement() { return {click() {}}; }, body: {appendChild() {}, removeChild() {}}},
  Blob, URL: {createObjectURL(blob) { download = blob; return 'blob:test'; }, revokeObjectURL() {}},
  Modal: {}, Swal: {},
};
vm.createContext(context);
vm.runInContext(fs.readFileSync('resources/js/components/returns.js','utf8').replace(/^import .*;\n/gm,''), context);
const table = factories.returnsTable();
table.downloadBulkQcTemplate();
assert.equal(await download.text(), 'order_id\n');
table.qcIdentifier = 'tracking_id'; table.downloadBulkQcTemplate();
assert.equal(await download.text(), 'tracking_id\n');
console.log('PASS: returns templates support both order ID and tracking ID headers');

context.document.getElementById = () => null;
context.document.querySelector = () => ({content:'csrf'});
context.console = console;
table.$nextTick = callback => callback();
const ret = (id, status) => ({id, status, return_no:'RMA-'+id, items:[{id:id*10,product_id:1,requested_qty:2,product:{id:1,name:'Product',sku:'SKU'}}]});
table.importReviewData = {returns:[ret(1,'approved'),ret(2,'pending'),ret(3,'pending')], preview:[1,2,3].map(id => ({return_id:id,sku:'SKU',received_qty:2,restocked_qty:2,damaged_qty:0}))};
table.bypassReturnIds = ['2'];
table.continueImportedQc();
assert.deepEqual(Array.from(table.selectedReturnsForBulk, ret => ret.id), [1,2]);
assert.equal(table.bulkQcItems[0].received_qty,4,'unchecked pending return must not contribute QC quantities');
const submitted=[];
context.fetch = async (url, options) => { submitted.push({url,body:JSON.parse(options.body)}); return {ok:true,text:async () => '{"success":true}'}; };
table.closeBulkQcModal = () => {};
table.loadReturns = () => {};
await table.submitBulkQc();
assert.equal(submitted.length,2);
assert.equal(submitted[0].body.bypass_approval,undefined);
assert.equal(submitted[1].body.bypass_approval,true);
assert.ok(submitted.every(row => !row.url.includes('/3/')));
table.returns=[ret(2,'pending')]; table.selectedReturns=['2'];
table.openBulkQcModal();
assert.equal(table.bypassReturnIds.length,0,'new selection must not inherit old bypass consent');
assert.equal(table.pendingImportedReturns.length,1);
console.log('PASS: pending QC excludes unchecked returns, flags only checked returns, and resets bypass selection');
