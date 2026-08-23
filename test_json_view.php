<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$offers = \App\Modules\Orders\Models\Offer::with('product')->active()->orderByDesc('priority')->orderBy('id')->get();
$mapped = $offers->map(fn($o) => array_merge($o->toArray(), ['product_name' => $o->product?->name ?? '']));

$json = json_encode($mapped);
echo "JSON First 50 chars: " . substr($json, 0, 50) . "\n";
