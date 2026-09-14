<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Modules\Catalog\Models\Service;

$services = Service::active()->get();
$carriersList = $services->pluck('name')
    ->filter()
    ->unique()
    ->sort()
    ->values();

echo json_encode($carriersList);
