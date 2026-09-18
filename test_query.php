<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

DB::enableQueryLog();

class TempStock2 extends \App\Modules\Inventory\Models\Stock {
    protected $table = 'stocks';
    
    public function pendingOrderItemsJoin()
    {
        return $this->hasMany(\App\Modules\Orders\Models\OrderItem::class, 'product_id', 'product_id');
    }
}

$query = TempStock2::query()
    ->withSum(['pendingOrderItemsJoin as pending_qty' => function ($q) {
        $q->join('orders', 'orders.id', '=', 'order_items.order_id')
          ->whereColumn('orders.warehouse_id', 'stocks.warehouse_id')
          ->where('orders.status', 'pending');
    }], 'order_items.quantity')
    ->limit(1)
    ->get();

print_r(DB::getQueryLog());
