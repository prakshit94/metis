<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

DB::enableQueryLog();

$query = \App\Modules\Inventory\Models\Stock::query()
    ->select('stocks.*')
    ->selectRaw('(SELECT COALESCE(SUM(quantity), 0) FROM order_items INNER JOIN orders ON orders.id = order_items.order_id WHERE order_items.product_id = stocks.product_id AND orders.warehouse_id = stocks.warehouse_id AND orders.status = ? AND orders.deleted_at IS NULL) as pending_qty', ['pending'])
    ->selectRaw('(SELECT COALESCE(SUM(quantity), 0) FROM order_items INNER JOIN orders ON orders.id = order_items.order_id WHERE order_items.product_id = stocks.product_id AND orders.warehouse_id = stocks.warehouse_id AND orders.status IN (?, ?) AND orders.deleted_at IS NULL) as raw_delivered_qty', ['delivered', 'completed'])
    ->selectRaw('(SELECT COALESCE(SUM(received_qty), 0) FROM order_return_items INNER JOIN order_returns ON order_returns.id = order_return_items.order_return_id INNER JOIN orders ON orders.id = order_returns.order_id WHERE order_return_items.product_id = stocks.product_id AND orders.warehouse_id = stocks.warehouse_id AND order_returns.status = ?) as returned_qty', ['completed'])
    ->selectRaw('(SELECT COALESCE(SUM(requested_qty), 0) FROM order_return_items INNER JOIN order_returns ON order_returns.id = order_return_items.order_return_id INNER JOIN orders ON orders.id = order_returns.order_id WHERE order_return_items.product_id = stocks.product_id AND orders.warehouse_id = stocks.warehouse_id AND order_returns.status IN (?, ?, ?)) as return_requested_qty', ['pending', 'received', 'qc_in_progress'])
    ->limit(1)
    ->get();

print_r(DB::getQueryLog());
