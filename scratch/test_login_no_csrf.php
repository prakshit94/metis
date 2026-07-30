<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

// Remove CSRF middleware from web group for testing
$app->make('router')->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::create('/login', 'POST', [
    'email' => 'wrong@example.com',
    'password' => 'wrongpassword'
]);
$request->headers->set('Accept', 'application/json'); // Make it expect JSON to avoid redirect

$response = $kernel->handle($request);
echo "Status: " . $response->getStatusCode() . "\n";
echo "Content: " . $response->getContent() . "\n";
echo "Location: " . $response->headers->get('Location') . "\n";
$kernel->terminate($request, $response);
