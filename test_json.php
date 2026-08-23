<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$offers = \App\Modules\Orders\Models\Offer::with('product')->active()->orderByDesc('priority')->orderBy('id')->get();
$mapped = $offers->map(fn($o) => array_merge($o->toArray(), ['product_name' => $o->product?->name ?? '']));
echo "Mapped keys type: " . gettype(array_keys($mapped->toArray())[0]) . "\n";
echo "Is JSON array? " . (json_encode($mapped)[0] === '[' ? 'Yes' : 'No') . "\n";
