<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

\App\Modules\Inventory\Models\Stock::resolveRelationUsing('pendingOrderItems', function ($stock) {
    return $stock->hasMany(\App\Modules\Orders\Models\OrderItem::class, 'product_id', 'product_id')
        ->whereHas('order', function ($q) {
            $q->whereColumn('orders.warehouse_id', 'stocks.warehouse_id')
              ->where('orders.status', 'pending');
        });
});

$stock = \App\Modules\Inventory\Models\Stock::withSum('pendingOrderItems', 'quantity')->first();
dump($stock->pending_order_items_sum_quantity);
