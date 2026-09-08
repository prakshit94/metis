<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$totalCount = \App\Modules\Core\Models\Village::count();
echo "Total Villages in DB: " . $totalCount . "\n";
