<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Modules\Users\Models\User::where('email', 'agent.one@example.com')->first();
auth()->login($user);

$controller = app(\App\Modules\Orders\Controllers\OrderController::class);
$request = Illuminate\Http\Request::create('/orders', 'GET', [], [], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest', 'HTTP_ACCEPT' => 'application/json']);
$response = $controller->index($request);

$content = json_decode($response->getContent(), true);
echo json_encode($content);

