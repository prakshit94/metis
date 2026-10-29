import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
const factories=new Map();
vm.runInNewContext(fs.readFileSync(new URL('../../resources/js/components/roles-permissions.js',import.meta.url),'utf8').replace(/^import .*;\n/gm,''),{
 Alpine:{data:(name,factory)=>factories.set(name,factory)},document:{addEventListener:(name,fn)=>{if(name==='alpine:init')fn();}},console,
});
const table=factories.get('rolesPermissionsTable')();
table.roles=[{id:1,isDeleted:false},{id:2,isDeleted:true}];table.activeTab='roles';
table.toggleAll(true);
assert.deepEqual(Array.from(table.selectedItems),[1,2]);
assert.equal(table.selectedRows.length,2);
assert.equal(table.hasSelectedDeletedItems,true);assert.equal(table.hasSelectedActiveItems,true);
table.toggleItem(1);assert.deepEqual(Array.from(table.selectedItems),[2]);
table.toggleAll(false);assert.equal(table.selectedItems.length,0);
table.permissions=[{id:3,isDeleted:false}];table.activeTab='permissions';table.toggleAll(true);table.toggleAll(true);
assert.deepEqual(Array.from(table.selectedItems),[3]);assert.equal(table.selectedRows.length,1);
table.toggleItem(3);assert.equal(table.selectedItems.length,0);
console.log('PASS: role/permission bulk and manual selection use the same integer IDs without duplicates or missing selected rows.');
