<?php

require __DIR__.'/bootstrap.php';

use App\Modules\Users\Controllers\RosterController;
use App\Modules\Users\Models\User;
use App\Modules\Users\Services\RosterImport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;

Artisan::call('migrate', ['--force' => true]);
$admin = User::forceCreate(['name' => 'Roster admin', 'email' => 'roster-admin@example.invalid', 'password' => bcrypt('test')]);
setPermissionsTeamId(null);
$admin->assignRole(Role::findOrCreate('Super Admin', 'web'));
auth()->setUser($admin);
$employee = User::forceCreate(['name' => 'First employee', 'employee_id' => '15451', 'email' => 'roster-one@example.invalid', 'password' => bcrypt('test')]);
$second = User::forceCreate(['name' => 'Second employee', 'employee_id' => '16464', 'email' => 'roster-two@example.invalid', 'password' => bcrypt('test')]);
$paths = [];
register_shutdown_function(function () use (&$paths) {
    foreach ($paths as $path) {
        @unlink($path);
    }
});
function rosterFile(string $content): UploadedFile
{
    global $paths;
    $path = tempnam(sys_get_temp_dir(), 'roster-test-');
    $paths[] = $path;
    file_put_contents($path, $content);

    return new UploadedFile($path, 'roster.csv', 'text/csv', null, true);
}
function rosterRequest(UploadedFile $file, array $data = [])
{
    $request = requestFor($data);
    $request->files->set('file', $file);

    return $request;
}
$csv = "EMP. ID,Advisor Name,LOB,02-Nov-26,03-Nov-26,04-Nov-26,05-Nov-26\n,,,Monday,Tuesday,Wednesday,Thursday\n15451,First employee,Sales,08:00-17:00,WO,C/OFF,L\n16464,Second employee,APP,22:00-06:00,08:00-17:01,,WO\n";
$file = rosterFile($csv);
$controller = app(RosterController::class);
$preview = $controller->import(rosterRequest($file))->getData(true);
check(count($preview['data']) === 7 && DB::table('employee_rosters')->count() === 0, 'multi-employee preview expands cells without writes and skips blanks');
$controller->import(rosterRequest($file, ['commit' => 1, 'preview_hash' => $preview['preview_hash']]));
check(DB::table('employee_rosters')->count() === 7, 'matrix commit saves all employee dates atomically');
check(DB::table('employee_rosters')->where('user_id', $second->id)->where('date', '2026-11-02')->value('shift_end') === '06:00:00', 'overnight shift times are preserved');
check(DB::table('leaves')->count() === 0 && DB::table('leave_balances')->count() === 0 && DB::table('attendances')->count() === 0, 'L and C/OFF leave attendance, approvals and balances untouched');
$preview = $controller->import(rosterRequest($file))->getData(true);
check(count(array_filter($preview['data'], fn ($r) => $r['action'] === 'Unchanged')) === 7, 'identical reimport is unchanged');
$events = DB::table('roster_events')->count();
$controller->import(rosterRequest($file, ['commit' => 1, 'preview_hash' => $preview['preview_hash']]));
check(DB::table('roster_events')->count() === $events, 'unchanged reimport creates no duplicate audit events');
foreach ([str_replace('Tuesday', 'Sunday', $csv), str_replace('03-Nov-26', '02-Nov-26', $csv), str_replace('08:00-17:00', '25:00-17:00', $csv), str_replace('15451,First', '99999,First', $csv), $csv."15451,Duplicate,Sales,WO,WO,WO,WO\n", str_replace('02-Nov-26', '31-Feb-26', $csv)] as $bad) {
    invalid(fn () => $controller->import(rosterRequest(rosterFile($bad))), 'invalid weekday/date/time/employee or duplicate blocks the import');
}
try {
    $controller->import(rosterRequest($file, ['commit' => 1, 'preview_hash' => hash_file('sha256', $file->getRealPath())]));
    throw new RuntimeException('Accepted raw file hash');
} catch (HttpException $e) {
    check($e->getStatusCode() === 422, 'a raw file hash cannot bypass preview');
}
DB::table('employee_rosters')->where('user_id', $employee->id)->where('date', '2026-11-03')->update(['co_status' => 'Pending']);
invalid(fn () => $controller->import(rosterRequest(rosterFile(str_replace(',WO,C/OFF,L', ',L,C/OFF,L', $csv)))), 'pending CO remains protected from day-type changes');
try {
    $controller->import(rosterRequest($file, ['commit' => 1, 'preview_hash' => $preview['preview_hash']]));
    throw new RuntimeException('Accepted stale preview');
} catch (HttpException $e) {
    check($e->getStatusCode() === 422, 'changed stored data invalidates the preview');
}
$legacy = rosterFile("employee_id,date,day_type\n15451,2026-11-02,Working\n");
$oldPreview = $controller->import(rosterRequest($legacy))->getData(true);
check($oldPreview['data'][0]['shift_start'] === '08:00', 'legacy day-only CSV preserves saved Working shifts');
$system = rosterFile("user_id,date,day_type\n{$employee->id},2026-11-09,Week Off\n");
check(count($controller->import(rosterRequest($system))->getData(true)['data']) === 1, 'legacy system-ID imports remain compatible');
$book = new Spreadsheet;
$book->getActiveSheet()->fromArray([['EMP. ID', 'Advisor Name', 'LOB', 46328], ['15451', 'Employee', 'Sales', '08:00-17:00']]);
$path = tempnam(sys_get_temp_dir(), 'roster-xlsx-');
$paths[] = $path;
(new Xlsx($book))->save($path);
$book->disconnectWorksheets();
$parsed = app(RosterImport::class)->parse(new UploadedFile($path, 'roster.xlsx', null, null, true));
check(count($parsed) === 1 && $parsed[0]['date'] === '2026-11-02', 'real XLSX numeric date headers are supported');
$leading = User::forceCreate(['name' => '=SUM(1,1)', 'employee_id' => '00123', 'email' => 'roster-leading@example.invalid', 'password' => bcrypt('test')]);
$beforeExport = DB::table('roster_events')->count();
$export = $controller->export(requestFor(['start_date' => '2026-11-02', 'end_date' => '2026-11-05']));
check($export->headers->get('Content-Type') === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'editable roster export is a real XLSX download');
$exportPath = tempnam(sys_get_temp_dir(), 'roster-roundtrip-'); $paths[] = $exportPath;
file_put_contents($exportPath, $export->getContent());
$exportFile = new UploadedFile($exportPath, 'editable-roster.xlsx', null, null, true);
$roundtrip = $controller->import(rosterRequest($exportFile))->getData(true);
check(count($roundtrip['data']) === 7 && count(array_filter($roundtrip['data'], fn ($row) => $row['action'] === 'Unchanged')) === 7, 'exported grid reimports directly with unchanged shifts and all off/leave codes');
$controller->import(rosterRequest($exportFile, ['commit' => 1, 'preview_hash' => $roundtrip['preview_hash']]));
check(DB::table('roster_events')->count() === $beforeExport && DB::table('leaves')->count() === 0, 'export/reimport does not mutate unchanged rosters or leave rules');
$exportBook = \PhpOffice\PhpSpreadsheet\IOFactory::load($exportPath);
$exportSheet = $exportBook->getActiveSheet();
check($exportSheet->getCell('A3')->getValue() === '00123' && $exportSheet->getCell('A3')->getDataType() === 's' && $exportSheet->getCell('B3')->getDataType() === 's', 'XLSX preserves leading-zero IDs and keeps formula-like names as text');
$exportBook->disconnectWorksheets();
invalid(fn () => $controller->export(requestFor(['start_date' => '2026-11-02', 'end_date' => '2026-11-01'])), 'export rejects reversed dates');
invalid(fn () => $controller->export(requestFor(['start_date' => '2026-01-01', 'end_date' => '2027-01-02'])), 'export keeps the import date-column limit');
$restricted = User::forceCreate(['name' => 'No access', 'email' => 'roster-no-access@example.invalid', 'password' => bcrypt('test')]);
auth()->setUser($restricted);
try {
    $controller->import(rosterRequest($file));
    throw new RuntimeException('Allowed unprivileged import');
} catch (HttpException $e) {
    check($e->getStatusCode() === 403, 'import permission is still required');
}

try {
    $controller->export(requestFor(['start_date' => '2026-11-02', 'end_date' => '2026-11-05']));
    throw new RuntimeException('Allowed unprivileged export');
} catch (HttpException $e) {
    check($e->getStatusCode() === 403, 'roster export requires existing export and view permissions');
}
