<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Modules\Users\Models\User::where('email', 'agent.one@example.com')->first();
auth()->login($user);

$controller = app(\App\Modules\Orders\Controllers\OrderController::class);
$request = Illuminate\Http\Request::create('/orders', 'GET');
$response = $controller->index($request);

$content = json_decode($response->getContent(), true);
echo "Total returned orders: " . $content['total'] . "\n";

