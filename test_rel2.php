<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

\App\Modules\Catalog\Models\Product::resolveRelationUsing('pendingOrderItems', function ($product) {
    return $product->hasMany(\App\Modules\Orders\Models\OrderItem::class, 'product_id', 'id')
        ->whereHas('order', function ($q) {
            $q->where('orders.status', 'pending');
        });
});

$p = \App\Modules\Catalog\Models\Product::withSum('pendingOrderItems', 'quantity')->first();
dump($p->pending_order_items_sum_quantity);
