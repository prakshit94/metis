<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$provider = new \App\Services\Shipping\Providers\IndiaPostProvider();
echo "Bulk ID: " . config('shipping.providers.india_post.bulk_customer_id') . "\n";
echo "Contract BP: " . config('shipping.providers.india_post.contracts.BUSINESS_PARCEL') . "\n";
