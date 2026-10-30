<?php

require __DIR__.'/bootstrap.php';

use App\Models\Crop;
use App\Modules\Users\Models\User;
use App\Services\ActivityFeedService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Facades\Excel;
use OwenIt\Auditing\Models\Audit;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Picqer\Barcode\BarcodeGenerator;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\Mime\Email;

Artisan::call('migrate', ['--force' => true]);
config(['activitylog.enabled' => true, 'audit.enabled' => true, 'audit.console' => true]);
$userId = DB::table('users')->insertGetId([
    'name' => 'Package audit fixture', 'email' => 'package-audit@example.invalid',
    'password' => bcrypt('FixturePassword123!'), 'created_at' => now(), 'updated_at' => now(),
]);
auth()->setUser(User::findOrFail($userId));
$crop = Crop::create(['name' => 'Original fixture crop', 'is_active' => true]);
$crop->update(['name' => 'Renamed fixture crop']);
$audit = Audit::where('auditable_type', Crop::class)->where('auditable_id', $crop->id)->where('event', 'updated')->firstOrFail();
$activity = Activity::where('subject_type', Crop::class)->where('subject_id', $crop->id)->where('event', 'updated')->firstOrFail();
check($audit->old_values['name'] === 'Original fixture crop' && $audit->new_values['name'] === 'Renamed fixture crop', 'audit history preserves old/new model values');
check($activity->properties['old']['name'] === 'Original fixture crop' && $activity->properties['attributes']['name'] === 'Renamed fixture crop', 'activity feed preserves its existing property format');
check((int) $audit->user_id === $userId && (int) $activity->causer_id === $userId, 'both histories preserve the acting user');
$auditCount = Audit::count();
$activityCount = Activity::count();
$crop->save();
check(Audit::count() === $auditCount && Activity::count() === $activityCount, 'unchanged save creates no empty history records');
$feed = app(ActivityFeedService::class);
$user = auth()->user();
$beforeRead = $feed->unreadCount($user);
$feed->markRead($user, [$activity->id]);
$feed->markRead($user, [$activity->id]);
check($feed->unreadCount($user) === $beforeRead - 1, 'activity notifications and idempotent read state remain working');
$crop->delete();
check(Audit::where('auditable_id', $crop->id)->where('auditable_type', Crop::class)->where('event', 'deleted')->exists(), 'deletion remains visible in audit history');
check(Activity::where('subject_id', $crop->id)->where('subject_type', Crop::class)->where('event', 'deleted')->exists(), 'deletion remains visible in activity history');

$pdf = app('dompdf.wrapper')->loadHTML('<html><body>Package fixture invoice</body></html>')->output();
check(str_starts_with($pdf, '%PDF-'), 'PDF renderer remains functional');
$barcode = (new BarcodeGeneratorPNG)->getBarcode('CX079641581IN', BarcodeGenerator::TYPE_CODE_128);
check(str_starts_with($barcode, "\x89PNG\r\n\x1a\n"), 'COD barcode generator remains functional');
$xlsx = Excel::raw(new class implements FromArray
{
    public function array(): array
    {
        return [['Product', 'Quantity'], ['Fixture', 2]];
    }
}, Maatwebsite\Excel\Excel::XLSX);
$file = tempnam(sys_get_temp_dir(), 'metis-package-xlsx-');
try {
    file_put_contents($file, $xlsx);
    $workbook = IOFactory::load($file);
    check($workbook->getActiveSheet()->getCell('B2')->getValue() === 2, 'spreadsheet export/readback preserves numeric cells');
    $workbook->disconnectWorksheets();
} finally {
    unlink($file);
}
$email = (new Email)->from('fixture@example.invalid')->to('receiver@example.invalid')
    ->subject('Fixture invoice')->text('Fixture body')->attach('fixture,data', 'invoice.csv', 'text/csv');
check(str_contains($email->toString(), 'invoice.csv'), 'patched MIME library serializes ordinary email attachments');

if ($path = getenv('METIS_OPENAPI_FILE')) {
    $document = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
    check(count($document['paths']) >= 399, 'documentation retains existing route coverage');
    $schema = fn ($path) => $document['paths'][$path]['post']['requestBody']['content']['application/json']['schema'];
    $qc = $schema('/api/returns/{return}/qc');
    check(in_array('items', $qc['required'], true) && $qc['properties']['items']['items']['properties']['id']['type'] === 'integer', 'QC documentation preserves required items and integer identity');
    check($qc['properties']['items']['items']['properties']['damaged_qty']['minimum'] === 0, 'QC documentation preserves quantity bounds');
    $shipment = $schema('/api/shipping/shipments/{shipment}/status');
    check(in_array('status', $shipment['required'], true) && in_array('delivered', $shipment['properties']['status']['enum'], true), 'shipment documentation preserves status requirements and enum');
    check($shipment['properties']['skip_order_sync']['type'] === 'boolean', 'shipment documentation includes its optional synchronization flag');
    $returns = $schema('/orders/{order}/returns');
    check(in_array('reason', $returns['required'], true) && in_array('items', $returns['required'], true), 'return documentation preserves required reason and items');
    $targets = $schema('/api/targets');
    check(isset($targets['properties']['target_year'], $targets['properties']['start_date']) && ! in_array('target_year', $targets['required'], true), 'target documentation preserves conditional period fields');
    $warehouse = $document['paths']['/api/warehouses/{id}']['put']['requestBody']['content']['application/json']['schema'];
    check($warehouse['properties']['code']['maxLength'] === 50, 'interpolated uniqueness rules retain structural length constraints');
}

echo "Composer integration regressions complete.\n";
