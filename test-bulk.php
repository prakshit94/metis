<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$user = \App\Modules\Users\Models\User::first();
$leave = \App\Modules\Users\Models\Leave::first() ?? \App\Modules\Users\Models\Leave::create([
    'user_id' => $user->id,
    'leave_type' => 'Sick',
    'start_date' => '2026-08-16',
    'end_date' => '2026-08-17',
    'status' => 'Pending',
    'applied_by' => $user->id
]);

$request = \Illuminate\Http\Request::create('/api/leaves/bulk-action', 'POST', [], [], [], ['HTTP_ACCEPT' => 'application/json', 'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'], json_encode(['action' => 'approve', 'ids' => [(string)$leave->id]]));
$request->headers->set('Content-Type', 'application/json');

// Simulate stateful Sanctum by setting the user resolver
$request->setUserResolver(function() use ($user) { return $user; });

$response = $kernel->handle($request);
echo "Status: " . $response->getStatusCode() . "\n";
echo "Content: " . $response->getContent() . "\n";
