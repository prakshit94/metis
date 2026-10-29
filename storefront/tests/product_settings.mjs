import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
const components=new Map();
const context={
 Alpine:{data:(name,factory)=>components.set(name,factory),store:()=>null},
 document:{addEventListener:(event,fn)=>{if(event==='alpine:init')fn();}},
 createSearchComponent:()=>()=>({}),window:{},console,
};
vm.runInNewContext(fs.readFileSync(new URL('../../resources/js/components/products.js',import.meta.url),'utf8').replace(/^import .*;\n/gm,''),context);
const form=components.get('productForm')();
assert.equal(form.form.storefront_visible,false);
assert.equal(form.form.min_order_qty,1);
form.form.storefront_visible=true;form.resetForm();
assert.equal(form.form.storefront_visible,false);
assert.equal(form.form.max_order_qty,1000);
const mapped=components.get('productTable')()._mapProductForForm({id:1,name:'Sample',storefront_visible:true,is_featured:true,min_order_qty:2,max_order_qty:6});
assert.equal(mapped.storefront_visible,true);
assert.equal(mapped.is_featured,true);
assert.equal(mapped.min_order_qty,2);
assert.equal(mapped.max_order_qty,6);
console.log('PASS: storefront product settings survive edit mapping and reset safely for new products');
const table=components.get('productTable')();
context.document.querySelector=()=>null;
table._getProductForm=()=>form;
table.fetchProductDetails=async product=>product;
await table.cloneProduct({id:7,name:'Source',sku:'SOURCE',storefront_visible:true,is_featured:true,status:'published',min_order_qty:2,max_order_qty:6});
assert.equal(form.form.storefront_visible,false);
assert.equal(form.form.is_featured,false);
assert.equal(form.form.status,'draft');
assert.equal(form.form.image,'/assets/images/product-placeholder.svg');
let resolveOld;
table.fetchProductDetails=product=>product.id===1?new Promise(resolve=>resolveOld=resolve):Promise.resolve(product);
const oldEdit=table.editProduct({id:1,name:'Old',sku:'OLD'});
await table.editProduct({id:2,name:'New',sku:'NEW'});
resolveOld({id:1,name:'Old',sku:'OLD'});await oldEdit;
assert.equal(form.editingProductId,2,'stale details must not overwrite a newer edit');
table.warehouseFilter='';table.stockFilter='';table.skuFilter='';
table.products=[{id:1,name:'Listed',storefront_visible:true,storefront_available:true},{id:2,name:'Private',storefront_visible:false,storefront_available:false},{id:3,name:'Draft',storefront_visible:true,storefront_available:false}];
for(const [filter,id]of[['listed',1],['hidden',2],['blocked',3]]){table.storefrontFilter=filter;table.filterProducts();assert.equal(table.filteredProducts.length,1);assert.equal(table.filteredProducts[0].id,id);}
const requests=[];
context.FormData=FormData;
context.fetch=async(url,options)=>{requests.push({url,body:JSON.parse(options.body)});return{ok:true,text:async()=>JSON.stringify({message:'Updated'})};};
vm.runInNewContext('showToast = () => {}',context);
table.loadProductsFromApi=async()=>{};
for(const [action,visible]of[['show_shop',true],['hide_shop',false]]){table.selectedProducts=[1];await table.bulkAction(action);assert.equal(requests.at(-1).url,'/api/products/bulk-storefront');assert.equal(requests.at(-1).body.storefront_visible,visible);}
console.log('PASS: clone privacy, stale edit protection, storefront filters and bulk visibility requests');

const exactPrice=components.get('productTable')()._mapProductForForm({selling_price:1694.92,selling_price_including_tax:'2000.00'});
assert.equal(exactPrice.selling_price_inc_gst,'2000.00');
console.log('PASS: edit form preserves authoritative inclusive price without recomputing rounded base');
