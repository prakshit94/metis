<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

$order = \App\Modules\Orders\Models\Order::find(1);
if($order) {
    echo "Old status: " . $order->status . "\n";
    echo "Old is_draft: " . $order->is_draft . "\n";
} else {
    echo "Order 1 not found.\n";
}
