<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$nullCount = \App\Modules\Core\Models\Village::whereNull('office_type_code')->count();
$invalidCount = \App\Modules\Core\Models\Village::where('office_type_code', 'INVALID')->count();
$failedCount = \App\Modules\Core\Models\Village::where('office_type_code', 'FAILED')->count();
$apiErrorCount = \App\Modules\Core\Models\Village::where('office_type_code', 'API_ERROR')->count();
$otherCount = \App\Modules\Core\Models\Village::whereNotIn('office_type_code', ['INVALID', 'FAILED', 'API_ERROR'])->whereNotNull('office_type_code')->count();
$totalCount = \App\Modules\Core\Models\Village::count();

echo "Total: $totalCount\n";
echo "Null: $nullCount\n";
echo "INVALID: $invalidCount\n";
echo "FAILED: $failedCount\n";
echo "API_ERROR: $apiErrorCount\n";
echo "Other: $otherCount\n";

$otherTypes = \App\Modules\Core\Models\Village::whereNotIn('office_type_code', ['INVALID', 'FAILED', 'API_ERROR'])->whereNotNull('office_type_code')->select('office_type_code')->distinct()->pluck('office_type_code')->toArray();
echo "Other Types: " . implode(', ', $otherTypes) . "\n";
