<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Modules\Users\Models\Leave;
use App\Modules\Users\Models\LeaveBalance;
use App\Models\User;

$user = User::first();
if (!$user) {
    echo "No user found.\n";
    exit;
}

// Create a balance of 12 for Sick Leave
$balance = LeaveBalance::updateOrCreate(
    ['user_id' => $user->id, 'leave_type' => 'Sick'],
    ['balance' => 12, 'total_leaves' => 12, 'is_active' => 1]
);

echo "Initial Balance: " . $balance->balance . "\n";

// Create a Pending Leave
$leave = Leave::create([
    'user_id' => $user->id,
    'leave_type' => 'Sick',
    'start_date' => now()->toDateString(),
    'end_date' => now()->addDays(2)->toDateString(),
    'reason' => 'Test',
    'status' => 'Pending',
    'applied_by' => $user->id
]);

// Simulate the controller update logic
$request = Request::create("/api/leaves/{$leave->id}", 'PUT', [
    'user_id' => $user->id,
    'leave_type' => 'Sick',
    'start_date' => $leave->start_date,
    'end_date' => $leave->end_date,
    'reason' => 'Test',
    'status' => 'Approved'
]);
$request->setUserResolver(function () use ($user) {
    return $user;
});

$controller = app(\App\Modules\Users\Controllers\LeaveController::class);
try {
    $response = $controller->update($request, $leave);
    echo "Response Status: " . $response->getStatusCode() . "\n";
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

$balance->refresh();
echo "Final Balance: " . $balance->balance . "\n";
