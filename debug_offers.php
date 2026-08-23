<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$offers = \App\Modules\Orders\Models\Offer::with('product')->active()->orderByDesc('priority')->orderBy('id')->get();
$coupons = \App\Modules\Orders\Models\Coupon::where('is_active', true)->get();

echo "Offers count: " . $offers->count() . "\n";
echo "Coupons count: " . $coupons->count() . "\n";
