<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::create('/login', 'POST', [
    'email' => 'wrong@example.com',
    'password' => 'wrongpassword'
]);

$response = $kernel->handle($request);
echo "Status: " . $response->getStatusCode() . "\n";
echo "Redirect: " . $response->headers->get('Location') . "\n";
$kernel->terminate($request, $response);
