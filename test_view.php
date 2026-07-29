<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $stats = ['total' => 0, 'pending' => 0, 'completed' => 0];
    $suppliers = collect();
    $warehouses = collect();
    $products = collect();
    echo view('procurement.purchase-orders.index', compact('stats', 'suppliers', 'warehouses', 'products'))->render();
    echo "SUCCESS";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine();
}
