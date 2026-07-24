<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$rescheduleReasons = App\Modules\Orders\Models\RescheduleReason::where('is_active', true)->orderBy('id')->get();
$html = view('orders.index', [
    'orders' => collect(),
    'stats' => [],
    'statusesList' => [],
    'productsList' => collect(),
    'statesList' => [],
    'districtsList' => [],
    'talukasList' => [],
    'villagesList' => [],
    'services' => collect(),
    'carriersList' => collect(),
    'returnReasons' => App\Modules\Orders\Models\ReturnReason::where('is_active', true)->orderBy('id')->get(),
    'rescheduleReasons' => $rescheduleReasons,
    'deliveryFailureReasons' => App\Modules\Orders\Models\DeliveryFailureReason::where('is_active', true)->orderBy('id')->get(),
    'trendsData' => []
])->render();

preg_match('/x-model="scheduleReason"[^>]*>([\s\S]*?)<\/select>/', $html, $matches);
echo "Schedule Reason HTML:\n";
print_r($matches[1] ?? 'NOT FOUND');

preg_match('/x-model="returnReason"[^>]*>([\s\S]*?)<\/select>/', $html, $matches);
echo "\nReturn Reason HTML:\n";
print_r($matches[1] ?? 'NOT FOUND');
