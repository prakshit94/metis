<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$controller = app()->make('App\Modules\Orders\Controllers\PromotionsController');
$request = Illuminate\Http\Request::create('/api/promotions/coupons', 'GET');
$response = $controller->couponsIndex($request);
echo $response->content();
