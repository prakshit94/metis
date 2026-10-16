<?php

// Route matching only: does not dispatch requests or access business records.
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$contracts = [
    '/api/orders/export' => [App\Modules\Orders\Controllers\OrderController::class, 'bulkExport'],
    '/api/orders/bulk-print' => [App\Modules\Orders\Controllers\OrderController::class, 'bulkPrint'],
    '/api/complaints/export' => [App\Modules\Orders\Controllers\OrderComplaintController::class, 'bulkExport'],
    '/api/orders/123' => [App\Modules\Orders\Controllers\OrderController::class, 'show'],
    '/api/complaints/123' => [App\Modules\Orders\Controllers\OrderComplaintController::class, 'show'],
];
foreach ($contracts as $path => [$class, $method]) {
    $route = app('router')->getRoutes()->match(Illuminate\Http\Request::create('http://localhost'.$path));
    if ($route->getActionName() !== $class.'@'.$method) {
        throw new RuntimeException('Wrong route handler: '.$path);
    }
}
echo "PASS: export/print paths reach their intended handlers; record detail routes retain their bindings.\n";
