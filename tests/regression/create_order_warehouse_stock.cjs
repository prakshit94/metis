// Real order-page methods, with isolated products/cart; no browser or database writes.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('resources/views/orders/create.blade.php', 'utf8');
const method = (start, end) => source.slice(source.indexOf(start), source.indexOf(end, source.indexOf(start)));
const context = {window: {}};
vm.createContext(context);
const ui = vm.runInContext(`({warehouseId: 1, cart: [],
${method('        getTotalWarehouseStock(p) {', '        getWarehouseStock(p) {')}
${method('        getOversellStock(p) {', '        canAddToCart(p) {')}
${method('        get filteredProducts() {', '        async searchProducts(')}
})`, context);
const product = {id: 101, stock_qty: 0, reserved_qty: 0, pending_qty: 0, is_sku_enabled: true, allow_overselling: false, overselling_qty: 0, available_stock: 100,
 warehouse_stocks: [{warehouse_id: 1, available: 0, allow_overselling: true, overselling_qty: 100, is_sku_enabled: true}]};
ui.products = [product]; ui.stockFilter = 'in-stock';
assert.equal(ui.filteredProducts.length, 1, 'Warehouse-oversell product must remain visible under In Stock');
assert.equal(ui.getMaxAllowedStock(product), 100);
assert.equal(ui.getOversellStock(product), 100);
ui.cart = [{id: 101, quantity: 4}];
assert.equal(ui.getOversellStock(product), 96);
ui.cart = [];
product.warehouse_stocks[0].overselling_qty = 0;
product.available_stock = 0;
assert.equal(ui.getMaxAllowedStock(product), 0, 'Explicit local zero cannot become 999');
assert.equal(ui.filteredProducts.length, 0);
product.allow_overselling = true; product.overselling_qty = 20;
product.warehouse_stocks[0].allow_overselling = null;
product.warehouse_stocks[0].overselling_qty = null;
assert.equal(ui.getMaxAllowedStock(product), 20, 'Null must inherit global settings');
product.warehouse_stocks[0].overselling_qty = 5;
assert.equal(ui.getMaxAllowedStock(product), 5, 'A quantity override is independent of the inherited allow switch');
product.warehouse_stocks[0].allow_overselling = false;
assert.equal(ui.getMaxAllowedStock(product), 0, 'Local false must override global true');
console.log('PASS: order-page visibility, warehouse overselling, quantity limits, cart allowance, explicit zero, inherited rules and local false.');
