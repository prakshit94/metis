<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::create('/customers/8', 'GET');
$request->headers->set('Accept', 'application/json');
// auth
$user = App\Modules\Users\Models\User::first();
auth()->login($user);
$response = $kernel->handle($request);
echo $response->getContent();
