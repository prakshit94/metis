<?php

require __DIR__.'/bootstrap.php';

use App\Modules\Users\Controllers\AttendanceController;
use App\Modules\Users\Controllers\LeaveController;
use App\Modules\Users\Models\Attendance;
use App\Modules\Users\Models\Leave;
use App\Modules\Users\Models\LeaveBalance;
use App\Modules\Users\Models\User;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpKernel\Exception\HttpException;

Artisan::call('migrate', ['--force' => true]);
$admin = User::forceCreate(['name' => 'HR reviewer', 'email' => 'hr-reviewer@example.invalid', 'password' => bcrypt('Test-only-password')]);
setPermissionsTeamId(null);
$admin->assignRole(Role::findOrCreate('Super Admin', 'web'));
auth()->setUser($admin);
$employee = User::forceCreate(['name' => 'Sample employee', 'employee_id' => 'HR-001', 'email' => 'hr-employee@example.invalid', 'password' => bcrypt('Test-only-password')]);
$attendance = app(AttendanceController::class);
$leaves = app(LeaveController::class);
$draft = ['user_id' => $employee->id, 'date' => '2026-11-02', 'status' => 'Present', 'check_in_time' => '2026-11-02T22:00', 'check_out_time' => '2026-11-03T06:00'];
$record = $attendance->store(requestFor($draft))->getData(true)['data'];
check($record['total_time'] === '8h 00m', 'overnight attendance keeps the existing eight-hour calculation');
foreach ([['check_in_time' => '2026-11-01T22:00'], ['check_out_time' => '2026-11-04T06:00'], ['check_out_time' => '2026-11-02T21:00'], ['user_id' => ['bad']]] as $bad) {
    invalid(fn () => $attendance->store(requestFor(array_replace($draft, $bad))), 'invalid timestamps and malformed employee IDs return validation errors');
}
$incomplete = $attendance->store(requestFor(array_replace($draft, ['check_in_time' => null, 'check_out_time' => '2026-11-02T17:00'])))->getData(true)['data'];
check($incomplete['total_time'] === null, 'check-out-only records remain supported for diagnostics and corrections');
invalid(fn () => $attendance->update(requestFor(array_replace($draft, ['check_in_time' => '2026-11-01T22:00'])), $record['id']), 'attendance editing uses the same date validation');
invalid(fn () => $attendance->index(requestFor(['sort_by' => ['invalid']])), 'malformed sort filters cannot trigger a server error');
$report = $attendance->index(requestFor(['user_id' => $employee->id, 'per_page' => 1]))->getData(true);
check((int) $report['summary']['records'] === 2, 'attendance totals include records beyond the current page');
$balance = LeaveBalance::create(['user_id' => $employee->id, 'leave_type' => 'Casual', 'total_leaves' => 12, 'used_leaves' => 0, 'is_active' => true]);
$leaveDraft = ['user_id' => $employee->id, 'leave_type' => 'Casual', 'start_date' => '2026-11-03', 'end_date' => '2026-11-03', 'reason' => 'Sample leave request'];
$leaveData = $leaves->store(requestFor($leaveDraft))->getData(true)['data'];
$leave = Leave::findOrFail($leaveData['id']);
invalid(fn () => $leaves->store(requestFor(array_replace($leaveDraft, ['end_date' => '2026-11-02']))), 'reversed leave dates are rejected');
invalid(fn () => $leaves->store(requestFor(array_replace($leaveDraft, ['leave_type' => str_repeat('x', 256)]))), 'oversized leave types fail validation before database insertion');
$leaves->updateStatus(requestFor(['status' => 'Approved']), $leave);
check((float) $balance->fresh()->used_leaves === 1.0, 'one-day approval deducts exactly one day');
check($leaves->updateStatus(requestFor(['status' => 'Approved']), $leave)->getStatusCode() === 422 && (float) $balance->fresh()->used_leaves === 1.0, 'processed leave cannot be approved twice');
check($leaves->destroy(requestFor([]), $leave)->getStatusCode() === 422, 'processed leave remains locked against deletion');
$pendingData = $leaves->store(requestFor(array_replace($leaveDraft, ['start_date' => '2026-11-04', 'end_date' => '2026-11-05'])))->getData(true)['data'];
$leaveReport = $leaves->index(requestFor([]))->getData(true);
check((int) $leaveReport['summary']['requested_days'] === 3, 'leave reports preserve inclusive calendar-day totals');

if ($directory = getenv('METIS_HR_RENDER_DIR')) {
    if (!is_dir($directory)) mkdir($directory, 0755, true);
    $request = requestFor([]);
    app()->instance('request', $request);
    app('session')->driver()->start();
    foreach (['attendances', 'leaves'] as $page) {
        file_put_contents($directory.'/'.$page.'.html', view('users.'.$page)->render());
    }
    file_put_contents($directory.'/attendances.json', $attendance->index(requestFor(['user_id' => $employee->id, 'per_page' => -1, 'start_date' => '2026-11-01', 'end_date' => '2026-12-12']))->getContent());
    file_put_contents($directory.'/leaves.json', $leaves->index(requestFor([]))->getContent());
    file_put_contents($directory.'/users.json', json_encode(['data' => [$admin->only(['id', 'name']), $employee->only(['id', 'name', 'employee_id'])], 'last_page' => 1]));
    foreach (['leave-view', 'leave-create', 'attendance-view'] as $permission) Permission::findOrCreate($permission, 'web');
    $employee->givePermissionTo(['leave-view', 'leave-create', 'attendance-view']);
    auth()->setUser($employee);
    $restricted = view('users.leaves')->render();
    check(!str_contains($restricted, 'x-data="balancesTable"') && !str_contains($restricted, 'x-data="holidaysTable"'), 'unavailable resource tabs do not initialize unauthorized requests');
    check(str_contains($restricted, 'id="leaveType"'), 'leave request form keeps its leave type selector');
    file_put_contents($directory.'/leaves-restricted.html', $restricted);
    auth()->setUser($admin);

}

auth()->setUser($employee);
try {
    $leaves->updateStatus(requestFor(['status' => 'Approved']), Leave::findOrFail($pendingData['id']));
    throw new RuntimeException('Unauthorized review was accepted');
} catch (HttpException $error) {
    check($error->getStatusCode() === 403, 'approval permission remains required');
}
check((float) $balance->fresh()->used_leaves === 1.0, 'unauthorized actions leave the balance unchanged');
