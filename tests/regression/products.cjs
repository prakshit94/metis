// Run the real Alpine components with isolated fetch/FormData fixtures; no live records change.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const factories = {};
let table, pending = [], submitted = [];
const context = {
  Alpine: {data:(name,factory)=>factories[name]=factory, store:()=>table},
  document: {addEventListener:(_,fn)=>fn(),querySelector:()=>null,getElementById:()=>null},
  window: {userContext:{}}, console, URLSearchParams, AbortController, FormData, File,
  setTimeout, clearTimeout, createSearchComponent:()=>()=>({}),
  fetch: async (url,options) => {
    if (options.method === 'POST') {
      submitted.push(options.body);
      return {ok:true,text:async()=>'{"data":{}}'};
    }
    return new Promise(resolve=>pending.push({resolve,signal:options.signal}));
  },
};
vm.createContext(context);
vm.runInContext(fs.readFileSync('resources/js/components/products.js','utf8').replace(/^import .*;\n/gm,''),context);
(async()=>{
  table=factories.productTable();
  table.options.taxRates=[{id:1,rate:18}];
  const product={id:1,purchase_price:51.27,mrp:199.95,selling_price:100.25,tax_rate_id:1};
  const mapped=table._mapProductForForm(product);
  assert.equal(mapped.purchase_price,'51.27');
  assert.equal(mapped.mrp,'199.95');
  assert.equal(mapped.selling_price_inc_gst,'118.30');
  const multi={...product,warehouse_id:1,allow_overselling:false,overselling_qty:10,is_sku_enabled:true,stock_qty:25,reserved_qty:3,pending_qty:7,warehouse_stocks:[
    {warehouse_id:1,quantity:5,reserved_qty:0,pending_qty:2,allow_overselling:null,overselling_qty:null,is_sku_enabled:null},
    {warehouse_id:2,quantity:20,reserved_qty:3,pending_qty:5,allow_overselling:true,overselling_qty:0,is_sku_enabled:false},
  ]};
  assert.equal(table.getEffectiveStock(multi),15);
  assert.equal(table.isOversellingAllowed(multi),true,'All-warehouse badge must include warehouse overrides');
  assert.equal(table.getOversellingLimit(multi),0,'All-warehouse limits must reflect local overrides');
  table.warehouseFilter='2';
  assert.equal(table.getEffectiveStock(multi),12);
  assert.equal(table.getOversellingLimit(multi),0,'An explicit zero is not the global unlimited fallback');
  assert.equal(table.getRemainingOversell(multi),0);
  assert.equal(table.isSkuEnabled(multi),false);
  const empty={...multi,warehouse_stocks:[{...multi.warehouse_stocks[1],quantity:0}]};
  assert.equal(table.getEffectiveStock(empty),0,'Zero override must not create 999 sellable units');
  const watchers={}; const warehouseForm=factories.productForm();
  warehouseForm.$watch=(key,fn)=>watchers[key]=fn;
  warehouseForm.init();warehouseForm.editingProductId=1;warehouseForm.originalProduct=multi;
  watchers['form.warehouse_config_id']('2');
  assert.equal(warehouseForm.form.warehouse_is_sku_enabled,false);
  assert.equal(warehouseForm.form.warehouse_overselling_qty,0);
  assert.equal(warehouseForm.form.default_warehouse_id,'','Selecting rules must not change the default warehouse');
  watchers['form.warehouse_config_id']('1');
  assert.equal(warehouseForm.form.warehouse_is_sku_enabled,null,'Changing warehouses must restore inherited rules');
  assert.equal(warehouseForm.form.warehouse_overselling_qty,null);
  table.warehouseFilter='';
  const first=table.loadProductsFromApi();
  const second=table.loadProductsFromApi();
  pending[0].resolve({ok:true,text:async()=>'{"data":[{"id":1}]}'});
  await first;
  assert.equal(table.isLoading,true,'An aborted request must not hide the current loading state');
  pending[1].resolve({ok:true,text:async()=>'{"data":[{"id":2}]}'});
  await second;
  assert.equal(table.products[0].id,2);
  assert.equal(table.isLoading,false);
  table.loadProductsFromApi=async()=>{};
  table.filterProducts=()=>{};table.calculateStats=()=>{};
  const form=factories.productForm();
  form.editingProductId=1;
  Object.assign(form.form,{name:'Fixture',sku:'FIXTURE',category_id:'1',uom_id:'1',tax_rate_id:'1',hsn_code_id:'1',default_warehouse_id:'1',weight_g:'1',length_cm:'1',width_cm:'1',height_cm:'1',purchase_price:'51.27',mrp:'199.95',selling_price_inc_gst:'118.30',is_sku_enabled:false,grade:'',warehouse_allow_overselling:null,warehouse_overselling_qty:null,warehouse_is_sku_enabled:null});
  const save=form.saveProduct();
  const duplicate=form.saveProduct();
  await Promise.all([save,duplicate]);
  assert.equal(submitted.length,1,'Concurrent saves must submit only once');
  assert.equal(submitted[0].get('is_sku_enabled'),'0');
  assert.equal(submitted[0].get('grade'),'');
  for(const field of ['warehouse_allow_overselling','warehouse_overselling_qty','warehouse_is_sku_enabled']) assert.equal(submitted[0].get(field),'','Clear overrides explicitly');
  assert.equal(submitted[0].get('selling_price'),'100.25','Editing must preserve tax-exclusive price');
  assert.equal(form.saving,false);
  console.log('PASS: decimal prices, GST round trip, load cancellation, duplicate save prevention, disabled SKU payload, grade and warehouse override clearing, per-warehouse availability/SKU, zero limits, all-warehouse badges and warehouse switching.');
})().catch(error=>{console.error(error);process.exitCode=1;});
