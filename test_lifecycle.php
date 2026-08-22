<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Modules\Inventory\Models\PurchaseOrder;
use App\Modules\Inventory\Models\Supplier;
use App\Modules\Catalog\Models\Warehouse;
use App\Modules\Catalog\Models\Product;
use Illuminate\Http\Request;
use App\Modules\Inventory\Controllers\PurchaseOrderController;
use App\Modules\Inventory\Controllers\GoodsReceiptController;
use App\Modules\Users\Models\User;

$user = User::first();
auth()->login($user);

$supplier = Supplier::first();
$warehouse = Warehouse::first();
$product = Product::first();

if (!$supplier || !$warehouse || !$product) {
    die("Need basic data\n");
}

echo "Testing Lifecycle...\n";

// 1. Create PO
$poController = new PurchaseOrderController();
$request = Request::create('/procurement/purchase-orders', 'POST', [
    'supplier_id' => $supplier->id,
    'warehouse_id' => $warehouse->id,
    'items' => [
        [
            'product_id' => $product->id,
            'quantity' => 10,
            'unit_price' => 100,
        ]
    ]
]);

$response = $poController->store($request);
if ($response->getStatusCode() !== 201) {
    die("Failed to create PO: " . $response->getContent() . "\n");
}
$poData = json_decode($response->getContent(), true)['data'];
$poId = $poData['id'];
echo "PO Created ID: $poId\n";

// 2. Approve PO
$po = PurchaseOrder::find($poId);
$response = $poController->approve($po);
if ($response->getStatusCode() !== 200) {
    die("Failed to approve PO: " . $response->getContent() . "\n");
}
echo "PO Approved\n";

// 3. Receive PO
$po = PurchaseOrder::with('items')->find($poId);
$poItemId = $po->items->first()->id;

$grnController = new GoodsReceiptController();
$request = Request::create("/procurement/purchase-orders/{$poId}/receive", 'POST', [
    'received_date' => date('Y-m-d'),
    'items' => [
        [
            'purchase_order_item_id' => $poItemId,
            'accepted_qty' => 10,
            'rejected_qty' => 0,
            'batch_number' => 'BATCH-123',
            'manufacturing_date' => date('Y-m-d', strtotime('-1 month')),
            'expiry_date' => date('Y-m-d', strtotime('+1 year')),
        ]
    ]
]);

$response = $grnController->store($request, $poId);
if ($response->getStatusCode() !== 201) {
    die("Failed to receive PO: " . $response->getContent() . "\n");
}

echo "PO Received Successfully!\n";
