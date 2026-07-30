<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::create('/login', 'POST', [
    'email' => 'wrong@example.com',
    'password' => 'wrongpassword'
]);

// Since the controller validates, we need to bind the request to the container
$app->instance('request', $request);

try {
    $loginRequest = \App\Http\Requests\Auth\LoginRequest::createFrom($request);
    $loginRequest->setContainer($app);
    $loginRequest->validateResolved();

    $controller = $app->make(\App\Modules\Users\Controllers\AuthController::class);
    $response = $controller->login($loginRequest);
    echo "Login successful? " . get_class($response) . "\n";
} catch (\Illuminate\Validation\ValidationException $e) {
    echo "ValidationException caught: " . json_encode($e->errors()) . "\n";
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

