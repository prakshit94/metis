import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
const source = fs.readFileSync(new URL('../../resources/js/components/products.js', import.meta.url), 'utf8').replace(/^import .*;\n/gm, '');
const factories = {}, revoked = [];
let sequence = 0;
const context = {
  Alpine: {data: (name, factory) => factories[name] = factory, store: () => null},
  document: {addEventListener: (event, callback) => callback(), querySelector: () => ({content:'csrf'}), getElementById: () => null},
  window: {location: {search: ''}},
  URL: {createObjectURL: () => 'blob:test-' + ++sequence, revokeObjectURL: url => revoked.push(url)},
  URLSearchParams, FormData, File, Blob, AbortController, setTimeout, clearTimeout,
  createSearchComponent: () => () => ({}), Swal: {}, Modal: {},
};
vm.createContext(context); vm.runInContext(source, context);
const form = factories.productForm();
const image = name => new File(['sample'], name, {type: 'image/png'});
const select = files => form.handleImageUpload({target: {files, value:'selected'}});
select([image('a.png'), image('b.png')]);
assert.equal(form.images.length, 2);
assert.equal(form.primaryImageId, form.images[0].id);
select([image('c.png')]);
assert.equal(form.images.length, 3, 'successive selections append rather than replace');
form.primaryImageId = form.images[2].id;
form.removeImage(form.images[0].id);
let payload = new FormData(); form.appendImages(payload);
assert.equal(payload.getAll('images[]').length, 2);
assert.equal(payload.get('primary_image_id'), 'upload:1', 'new references are reindexed after removal');
assert.equal(revoked.length, 1);
const primaryId = form.primaryImageId;
form.removeImage(primaryId);
assert.equal(form.primaryImageId, form.images[0].id);
form.setImages([{id:'a'.repeat(64),url:'/storage/products/a.png',is_primary:true},{id:'b'.repeat(64),url:'/storage/products/b.png'}]);
assert.equal(revoked.length, 3, 'reloading edit form releases every pending preview');
form.removeImage('a'.repeat(64));
payload = new FormData(); form.appendImages(payload);
assert.equal(payload.get('primary_image_id'), 'b'.repeat(64));
assert.deepEqual(payload.getAll('remove_image_ids[]'), ['a'.repeat(64)]);
assert.equal(payload.getAll('images[]').length, 0, 'saved images are not reuploaded');
select([new File(['<svg>'], 'bad.svg',{type:'image/svg+xml'})]);
assert.equal(form.images.length, 1);
assert.match(form.mediaError, /JPG/);
select(Array.from({length:10},(_,i)=>image(`${i}.png`)));
assert.equal(form.images.length, 1);
assert.match(form.mediaError, /up to 10/);
const tooBig = image('large.png'); Object.defineProperty(tooBig,'size',{value:5*1024*1024+1});
select([tooBig]); assert.match(form.mediaError, /5 MB/);
const fiveMb = Array.from({length:6},(_,i)=>{const f=image(`${i}.png`);Object.defineProperty(f,'size',{value:5*1024*1024});return f;});
select(fiveMb); assert.match(form.mediaError,/25 MB/);
form.resetForm();
assert.equal(form.images.length,0); assert.equal(form.removedImageIds.length,0); assert.equal(form.primaryImageId,'');
context.fetch = async () => ({ok:false,status:403,text:async()=>JSON.stringify({message:'Permission denied'})});
await assert.rejects(context.apiFetch('/api/products'), /Permission denied/);
assert.equal(context.window.location.href, undefined, 'permission failures preserve the current form instead of reporting success');
context.fetch = async () => ({status:413});
await assert.rejects(context.apiFetch('/api/products'), /request limit/);
context.fetch = async () => ({redirected:true,status:200});
await assert.rejects(context.apiFetch('/api/products'), /session expired/);
console.log('PASS: multiple selections, primary selection/removal, multipart references, file/count/total limits, preview cleanup, reset, permission and session failures');
