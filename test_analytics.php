<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Modules\Users\Models\User::first();
auth()->login($user);

$request = Illuminate\Http\Request::create('/analytics/data', 'GET', ['period' => 'last_month']);
$response = app()->handle($request);
echo $response->getContent();
