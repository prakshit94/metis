<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::create(
        '/api/customers/6/addresses', 'POST',
        [
            'label' => 'Home',
            'is_default' => '1',
            'status' => 'active',
            'address_line_1' => '123 Test St',
            'city' => 'Test City',
            'state' => 'Test State',
            'pincode' => '123456',
        ]
    )
);
echo $response->getContent();
