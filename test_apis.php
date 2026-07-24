<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$user = \App\Modules\Users\Models\User::first();
\Illuminate\Support\Facades\Auth::login($user);

echo "Testing Coupon Creation (Free Product)...\n";
$request = Illuminate\Http\Request::create('/api/promotions/coupons', 'POST', [
    'code' => 'TESTFREEPROD' . time(),
    'type' => 'free_product',
    'min_spend' => 1000,
    'free_product_id' => 1,
    'free_qty' => 2
]);
$request->headers->set('Accept', 'application/json');
$response = $kernel->handle($request);
echo "Status: " . $response->getStatusCode() . "\n";
echo "Response: " . substr($response->getContent(), 0, 500) . "\n\n";

echo "Testing Offer Creation (Category Discount)...\n";
$request2 = Illuminate\Http\Request::create('/api/promotions/offers', 'POST', [
    'name' => 'Test Cat Discount',
    'type' => 'category_discount',
    'discount_type' => 'percentage',
    'value' => 15,
    'min_spend' => 500,
    'applicable_categories' => [1, 2]
]);
$request2->headers->set('Accept', 'application/json');
$response2 = $kernel->handle($request2);
echo "Status: " . $response2->getStatusCode() . "\n";
echo "Response: " . substr($response2->getContent(), 0, 500) . "\n";
