<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Login as admin
$user = \App\Models\User::where('email', 'admin@example.com')->first();
auth()->login($user);

$request = Illuminate\Http\Request::create('/orders', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
$response = $kernel->handle($request);
$data = json_decode($response->getContent(), true);

echo "Districts Count: " . count($data['districts'] ?? []) . "\n";
echo "Talukas Count: " . count($data['talukas'] ?? []) . "\n";
echo "Villages Count: " . count($data['villages'] ?? []) . "\n";
