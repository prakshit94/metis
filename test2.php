<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$paginator = new \Illuminate\Pagination\LengthAwarePaginator([1, 2, 3], 3, 15);
echo json_encode(['data' => $paginator]);
