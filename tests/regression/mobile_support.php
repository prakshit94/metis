<?php

use App\Modules\Users\Models\Permission;
use App\Modules\Users\Models\User;

// Included by audit_fixes.php inside its isolated transaction and real HTTP kernel.
foreach (['/api/auth/me', '/api/targets', '/api/reports', '/api/reports/export', '/api/inventory/dashboard', '/api/inventory/dashboard/activities', '/api/files', '/api/customer-settings/crop', '/api/payments/import/sample'] as $uri) {
    $response = $http('GET', $uri);
    check($response->getStatusCode() === 401 && str_contains($response->headers->get('Content-Type', ''), 'application/json'), 'native workflow requires authentication: '.$uri);
}
foreach (['/api/auth/me', '/api/targets', '/api/reports', '/api/inventory/dashboard', '/api/inventory/dashboard/activities', '/api/files', '/api/customer-settings/crop', '/api/orders/'.$oid.'/receipt', '/api/orders/create-options'] as $uri) {
    $response = $http('GET', $uri, [], $token);
    check($response->getStatusCode() === 200 && str_contains($response->headers->get('Content-Type', ''), 'application/json'), 'native workflow returns JSON without browser headers: '.$uri.' ('.$response->getStatusCode().')');
}
$response = $http('GET', '/api/reports', [], $token);
$reportCatalog = json_decode($response->getContent(), true)['data'];
check(count($reportCatalog) === 17 && str_contains($reportCatalog[0]['download_url'], '/api/reports/export'), 'native report catalog exposes every existing CSV report');
foreach (['/api/payments/import/sample', '/api/orders/import-deliver-template', '/api/orders/import-return-template', '/api/targets/import-template', '/api/reports/export?type=stock_valuation'] as $uri) {
    $response = $http('GET', $uri, [], $token);
    check($response->getStatusCode() === 200 && str_contains($response->headers->get('Content-Type', ''), 'csv'), 'native workflow streams existing CSV: '.$uri);
    ob_start();
    $response->sendContent();
    $csv = ob_get_clean();
    check(strlen($csv) > 0, 'native CSV includes headers: '.$uri);
}
$response = $http('POST', '/api/targets', ['targetable_type' => 'User', 'targetable_ids' => [$uid], 'metric_type' => 'orders_count', 'period_type' => 'daily', 'target_amount' => 12, 'start_date' => '2026-10-27', 'end_date' => '2026-10-27'], $token);
check($response->getStatusCode() === 200 && ! $response->headers->has('Location'), 'native target creation reuses target chain without redirect');
$targetId = DB::table('targets')->where('targetable_id', $uid)->where('metric_type', 'orders_count')->value('id');
$response = $http('PUT', '/api/targets/'.$targetId, ['target_amount' => 15, 'status' => 'active'], $token);
check($response->getStatusCode() === 200 && (float) DB::table('targets')->where('id', $targetId)->value('target_amount') === 15.0, 'native target update retains existing calculation service');
$response = $http('POST', '/api/targets/recalculate', ['period_type' => 'daily'], $token);
check($response->getStatusCode() === 200 && isset(json_decode($response->getContent(), true)['count']), 'native target recalculation returns count');
$response = $http('DELETE', '/api/targets/'.$targetId, [], $token);
check($response->getStatusCode() === 200 && DB::table('targets')->where('id', $targetId)->value('deleted_at') !== null, 'native target deletion preserves soft delete');
foreach (['/api/targets/import', '/api/payments/import/preview', '/api/orders/import-deliver', '/api/orders/import-return', '/api/files/upload'] as $uri) {
    $response = $http('POST', $uri, [], $token);
    check($response->getStatusCode() === 422 && str_contains($response->headers->get('Content-Type', ''), 'application/json'), 'native upload validation returns JSON: '.$uri);
}
$response = $http('GET', '/api/orders/'.$ownPending.'/shipping-label', [], $token);
check($response->getStatusCode() === 422 && ! $response->headers->has('Location'), 'missing native shipping label returns actionable JSON');
foreach ($reportCatalog as $report) {
    $response = $http('GET', '/api/reports/export?type='.$report['type'], [], $token);
    check($response->getStatusCode() === 200, 'native report download: '.$report['type']);
    ob_start();
    try {
        $response->sendContent();
        $csv = ob_get_contents();
    } finally {
        ob_end_clean();
    }
    check(strlen($csv) > 0, 'native report generates CSV: '.$report['type']);
}
$programBody = ['name' => 'Mobile referral', 'is_active' => false, 'milestones' => [['required_referrals' => 1, 'reward_type' => 'wallet', 'reward_value' => '10']]];
$response = $http('POST', '/api/promotions/referral-programs', $programBody, $token);
check($response->getStatusCode() === 200 && ! $response->headers->has('Location'), 'native referral creation returns JSON');
$programId = DB::table('referral_programs')->where('name', 'Mobile referral')->value('id');
foreach (['PUT' => '', 'PATCH' => '/toggle', 'DELETE' => ''] as $method => $suffix) {
    $response = $http($method, '/api/promotions/referral-programs/'.$programId.$suffix, $method === 'PUT' ? $programBody : [], $token);
    check($response->getStatusCode() === 200 && str_contains($response->headers->get('Content-Type', ''), 'application/json'), 'native referral mutation returns JSON: '.$method);
}
$response = $http('GET', '/api/procurement/purchase-orders/bulk-pdf', [], $token);
check($response->getStatusCode() === 422 && ! $response->headers->has('Location'), 'native PDF selection errors return JSON');
$response = $http('GET', '/api/call-tags-admin', [], $token);
check($response->getStatusCode() === 200 && isset(json_decode($response->getContent(), true)['tags']), 'native call-tag configuration returns data instead of HTML');
config(['session.driver' => 'database']);
DB::table('sessions')->insert(['id' => 'mobile-regression-session', 'user_id' => $uid, 'payload' => '', 'last_activity' => time(), 'user_agent' => 'Regression Browser']);
$response = $http('GET', '/api/security/sessions', [], $token);
check($response->getStatusCode() === 200 && count(json_decode($response->getContent(), true)['data']) >= 2, 'native session listing works without a browser session');
$response = $http('POST', '/api/security/sessions/terminate-other', [], $token);
check($response->getStatusCode() === 200 && DB::table('sessions')->where('id', 'mobile-regression-session')->doesntExist(), 'native terminate-other preserves current token and removes browser sessions');
config(['session.driver' => 'array']);
$limited = User::create(['name' => 'Mobile limited', 'email' => 'mobile-limited@example.invalid', 'password' => bcrypt('Example123!')]);
$limitedToken = $limited->createToken('mobile-regression')->plainTextToken;
foreach (['/api/targets', '/api/files', '/api/customer-settings/crop', '/api/inventory/dashboard'] as $uri) {
    $response = $http('GET', $uri, [], $limitedToken);
    check($response->getStatusCode() === 403, 'native workflow preserves existing permission requirement: '.$uri);
}
$limited->givePermissionTo(Permission::findOrCreate('target-edit', 'web'));
$foreignTarget = $targetId;
DB::table('targets')->where('id', $foreignTarget)->update(['deleted_at' => null, 'target_amount' => 1]);
$response = $http('PUT', '/api/targets/'.$foreignTarget, ['target_amount' => 999, 'status' => 'active'], $limitedToken);
check($response->getStatusCode() === 403 && (json_decode($response->getContent(), true)['message'] ?? '') === 'Unauthorized action.' && (float) DB::table('targets')->where('id', $foreignTarget)->value('target_amount') === 1.0, 'native target editor cannot change another assignee target');

$response = $http('POST', '/api/security/emergency-lockdown', [], $limitedToken);
check($response->getStatusCode() === 200 && $limited->tokens()->count() === 0 && $limited->fresh()->password_changed_at !== null, 'native personal lockdown revokes tokens without requiring a web session');
