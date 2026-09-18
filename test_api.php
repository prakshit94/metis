<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$user = \App\Modules\Users\Models\User::where('email', 'admin@example.com')->first();
$token = $user->createToken('test')->plainTextToken;
echo "TOKEN=$token\n";
