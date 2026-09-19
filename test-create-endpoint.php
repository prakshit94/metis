<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$user = \App\Modules\Users\Models\User::where('email', 'admin@example.com')->first();
auth()->login($user);

$request = Illuminate\Http\Request::create('/orders/create?customer_id=6', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
$response = $kernel->handle($request);
echo $response->status() . "\n";
if ($response->status() !== 200) {
    echo substr($response->getContent(), 0, 500);
}
