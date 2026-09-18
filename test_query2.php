<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

DB::enableQueryLog();

$query = \App\Modules\Inventory\Models\Stock::query()
    ->withSum(['pendingOrderItems as pending_qty' => function ($q) {
        $q->join('orders', 'orders.id', '=', 'order_items.order_id');
    }], 'order_items.quantity')
    ->limit(1)
    ->get();

print_r(DB::getQueryLog());
