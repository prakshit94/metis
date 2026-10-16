<?php

// Controller-level authentication regression checks. All record writes go to
// temporary schema-only MySQL copies; rate limits and sessions use memory.
declare(strict_types=1);

use App\Http\Requests\Auth\LoginRequest;
use App\Modules\Users\Controllers\AuthController;
use App\Modules\Users\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
function authCheck(bool $condition, string $message): void {
    if (! $condition) { throw new RuntimeException($message); }
}
function authRequest(string $path, string $email, string $password, string $ip = '192.0.2.10', array $extra = []): array {
    global $app;
    Auth::forgetGuards();
    $session = new Store('isolated-auth', new ArraySessionHandler(120));
    $session->start();
    $app->instance('session.store', $session);
    $request = LoginRequest::create('http://localhost'.$path, 'POST', array_merge(['email' => $email, 'password' => $password], $extra), [], [], ['REMOTE_ADDR' => $ip, 'HTTP_REFERER' => 'http://localhost/login']);
    $request->setContainer($app)->setRedirector(app('redirect'));
    $request->setLaravelSession($session);
    $app->instance('request', $request);
    app('redirect')->setSession($session);
    $request->validateResolved();
    return [$request, $session];
}
function authLogin(string $path, string $email, string $password, string $ip = '192.0.2.10', array $extra = []): array {
    [$request, $session] = authRequest($path, $email, $password, $ip, $extra);
    return [(new AuthController)->login($request), $session, $request];
}
$source = DB::connection();
authCheck($source->getDriverName() === 'mysql', 'MySQL is required.');
$pdo = $source->getPdo();
$sourceName = str_replace('`', '``', $source->getDatabaseName());
$database = 'metis_auth_test_'.bin2hex(random_bytes(6));
$config = $source->getConfig(); $config['database'] = $database; unset($config['url'], $config['name']);
$pdo->exec("CREATE DATABASE `{$database}`");
$failed = false;
try {
    foreach (['users', 'teams', 'roles', 'permissions', 'model_has_roles', 'model_has_permissions', 'role_has_permissions', 'personal_access_tokens', 'sessions', 'attendances', 'login_histories', 'activity_log', 'audits'] as $table) {
        $pdo->exec("CREATE TABLE `{$database}`.`{$table}` LIKE `{$sourceName}`.`{$table}`");
    }
    config(['database.connections.mysql' => $config, 'database.default' => 'mysql', 'cache.default' => 'array', 'cache.limiter' => 'array', 'permission.cache.store' => 'array', 'session.driver' => 'database']);
    DB::purge('mysql');
    $app->forgetInstance(\Illuminate\Cache\RateLimiter::class);
    RateLimiter::clearResolvedInstance(\Illuminate\Cache\RateLimiter::class);
    app(Spatie\Permission\PermissionRegistrar::class)->initializeCache();
    authCheck(DB::connection()->getDatabaseName() === $database, 'Fixture is not isolated.');
    Carbon::setTestNow('2026-10-17 09:00:00');
    $password = 'Synthetic-password-only-42';
    foreach ([1, 2] as $id) {
        DB::table('users')->insert(['id' => $id, 'name' => 'Synthetic '.$id, 'email' => 'auth'.$id.'@example.invalid', 'password' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 4]), 'is_active' => true]);
    }
    foreach (['/login', '/api/auth/login'] as $path) {
        Cache::store('array')->flush();
        [$response, $session] = authLogin($path, '  AUTH1@example.invalid  ', 'wrong-password', extra: ['password_confirmation' => 'not-to-be-flashed']);
        $message = $path === '/login' ? $session->get('error') : $response->getData(true)['message'];
        authCheck($message === 'Invalid email or password. Failed attempts: 1/5. Remaining: 4.', 'Credential message is not short and generic.');
        authCheck($response->getStatusCode() === ($path === '/login' ? 302 : 401), 'Credential failure status changed.');
        authCheck(RateLimiter::attempts('login_email:auth1@example.invalid') === 1 && RateLimiter::attempts('login_ip:192.0.2.10') === 1, 'Failure counters or email normalization changed.');
        if ($path === '/login') {
            authCheck($session->getOldInput('email') === 'auth1@example.invalid', 'Retry email was not retained.');
            authCheck(! array_key_exists('password', $session->getOldInput()) && ! array_key_exists('password_confirmation', $session->getOldInput()), 'Password was flashed into session input.');
        }
    }
    echo "PASS: short web/API messages, unchanged statuses/counters, email normalization and no password flash.\n";

    Cache::store('array')->flush();
    for ($i = 0; $i < 5; $i++) { [$lastFailure] = authLogin('/api/auth/login', 'auth1@example.invalid', 'wrong-password'); }
    authCheck($lastFailure->getStatusCode() === 401 && $lastFailure->getData(true)['message'] === 'Invalid email or password. Failed attempts: 5/5. Remaining: 0.', 'Last available attempt count changed.');
    [$blocked] = authLogin('/api/auth/login', 'auth1@example.invalid', $password);
    authCheck($blocked->getStatusCode() === 429 && $blocked->getData(true)['message'] === 'Suspended until Oct 17, 2026, 9:15 AM. Failed attempts: 5/5. Remaining: 0.', 'Account threshold response changed.');
    authCheck(User::findOrFail(1)->suspended_until->equalTo(now()->addMinutes(15)), 'Account suspension duration changed.');
    authCheck(RateLimiter::attempts('login_email:auth1@example.invalid') === 5, 'Blocked attempt changed the failure counter.');
    [$blockedWeb, $blockedSession] = authLogin('/login', 'auth1@example.invalid', $password);
    authCheck($blockedWeb->getStatusCode() === 302 && ! array_key_exists('password', $blockedSession->getOldInput()), 'Throttled browser response retained a password.');
    Carbon::setTestNow(now()->addMinutes(6));
    authCheck(! RateLimiter::tooManyAttempts('login_email:auth1@example.invalid', 5), 'Five-minute counter expiry changed.');
    [$suspended] = authLogin('/api/auth/login', 'auth1@example.invalid', $password);
    authCheck($suspended->getStatusCode() === 423 && $suspended->getData(true)['message'] === 'Suspended until Oct 17, 2026, 9:15 AM.', 'Suspension guard/status changed.');
    [$suspendedWeb, $suspendedSession] = authLogin('/login', 'auth1@example.invalid', $password);
    authCheck($suspendedWeb->getStatusCode() === 302 && ! array_key_exists('password', $suspendedSession->getOldInput()), 'Suspended browser response retained a password.');
    $notice = new ReflectionMethod(AuthController::class, 'retryNotice');
    $displayUser = User::findOrFail(1);
    $displayUser->suspended_until = now()->addMinute();
    $displayEmailKey = 'display:email';
    $displayIpKey = 'display:ip';
    for ($i = 0; $i < 5; $i++) { RateLimiter::hit($displayEmailKey, 300); }
    authCheck($notice->invoke(new AuthController, $displayEmailKey, $displayIpKey, $displayUser) === 'Suspended until Oct 17, 2026, 9:07 AM. Try again at Oct 17, 2026, 9:11 AM.', 'Longer limiter expiry was omitted from the retry notice.');
    config(['app.timezone' => 'UTC']);
    authCheck($notice->invoke(new AuthController, 'unused:email', 'unused:ip', User::findOrFail(1)) === 'Suspended until Oct 17, 2026, 3:45 AM.', 'Retry time did not use the configured timezone.');
    config(['app.timezone' => 'Asia/Kolkata']);
    authCheck(User::findOrFail(1)->suspended_until->format('H:i') === '09:15', 'Formatting modified the suspension timestamp.');
    echo "PASS: attempt counts, exact retry dates, configured timezone, longer limiter expiry, unchanged suspension and lockout statuses.\n";

    Cache::store('array')->flush();
    for ($i = 0; $i < 5; $i++) { authLogin('/api/auth/login', 'unknown'.$i.'@example.invalid', 'wrong-password'); }
    [$ipBlocked] = authLogin('/api/auth/login', 'auth2@example.invalid', 'wrong-password');
    authCheck($ipBlocked->getStatusCode() === 429 && ! User::findOrFail(2)->isSuspended(), 'Shared-IP limits suspended an unrelated account.');
    authCheck($ipBlocked->getData(true)['message'] === 'Too many sign-in attempts. Try again at Oct 17, 2026, 9:11 AM. Failed attempts: 5/5. Remaining: 0.', 'Network retry time or shared attempt budget is incorrect.');
    DB::table('sessions')->insert(['id' => 'old-browser-session', 'user_id' => 2, 'payload' => '', 'last_activity' => now()->timestamp]);
    $oldToken = User::findOrFail(2)->createToken('old-device')->accessToken->id;
    [$success] = authLogin('/api/auth/login', 'auth2@example.invalid', $password);
    authCheck($success->getStatusCode() === 200 && isset($success->getData(true)['token']), 'Valid credentials behind shared IP were blocked.');
    authCheck(! DB::table('personal_access_tokens')->where('id', $oldToken)->exists() && DB::table('personal_access_tokens')->where('tokenable_id', 2)->count() === 1, 'Single-active-token behavior changed.');
    authCheck(! DB::table('sessions')->where('user_id', 2)->exists(), 'Old browser sessions were not revoked.');
    authCheck(RateLimiter::attempts('login_ip:192.0.2.10') === 0 && DB::table('attendances')->where('user_id', 2)->count() === 1, 'Success counter reset or attendance changed.');
    [$webSuccess, $webSession] = authLogin('/login', 'auth2@example.invalid', $password, extra: ['remember' => '1']);
    authCheck($webSuccess->getStatusCode() === 302 && Auth::guard('web')->id() === 2, 'Browser session login changed.');
    authCheck(DB::table('personal_access_tokens')->where('tokenable_id', 2)->count() === 0 && DB::table('attendances')->where('user_id', 2)->count() === 1, 'Browser login revocation or attendance deduplication changed.');
    $cookieNames = array_map(fn ($cookie) => $cookie->getName(), $webSuccess->headers->getCookies());
    authCheck(in_array('remembered_email', $cookieNames, true), 'Remember-email cookie changed.');
    echo "PASS: shared-IP valid login, token/session invalidation, browser login, remember cookie and attendance deduplication.\n";

    Cache::store('array')->flush();
    DB::table('users')->where('id', 2)->update(['is_active' => false]);
    [$inactive] = authLogin('/api/auth/login', 'auth2@example.invalid', $password);
    authCheck($inactive->getStatusCode() === 401 && $inactive->getData(true)['message'] === 'Invalid email or password. Failed attempts: 1/5. Remaining: 4.', 'Inactive-user protection or generic error changed.');
    [$unknown] = authLogin('/api/auth/login', 'absent@example.invalid', $password, '192.0.2.11');
    authCheck($unknown->getStatusCode() === 401 && $unknown->getData(true)['message'] === 'Invalid email or password. Failed attempts: 1/5. Remaining: 4.', 'Unknown-account generic error changed.');
    authCheck(DB::table('login_histories')->where('status', 'failed')->exists() && DB::table('login_histories')->where('status', 'success')->exists(), 'Authentication auditing stopped.');
    echo "PASS: inactive/unknown account protection and authentication auditing.\n";
    $middleware = new \App\Http\Middleware\EnsureUserIsActive;
    $inactiveUser = User::findOrFail(2)->withAccessToken(new \Laravel\Sanctum\TransientToken);
    $apiRequest = \Illuminate\Http\Request::create('http://localhost/api/test');
    $apiRequest->setUserResolver(fn () => $inactiveUser);
    $denied = $middleware->handle($apiRequest, fn () => throw new RuntimeException('Inactive session reached the controller.'));
    authCheck($denied->getStatusCode() === 403 && $denied->getData(true)['message'] === 'Your account has been deactivated.', 'Inactive session denial changed or crashed.');
    $current = $inactiveUser->createToken('current-denied-device')->accessToken;
    $other = $inactiveUser->createToken('other-device')->accessToken;
    $inactiveUser->withAccessToken($current);
    $apiRequest->headers->set('Authorization', 'Bearer synthetic');
    $denied = $middleware->handle($apiRequest, fn () => throw new RuntimeException('Inactive token reached the controller.'));
    authCheck($denied->getStatusCode() === 403 && ! $current->fresh() && $other->fresh() !== null, 'Inactive bearer handling did not revoke only the current token.');
    $activeUser = User::findOrFail(1);
    $activeUser->is_active = true;
    $activeUser->suspended_until = null;
    $apiRequest->setUserResolver(fn () => $activeUser);
    authCheck($middleware->handle($apiRequest, fn () => response('allowed'))->getStatusCode() === 200, 'Active user was blocked.');
    $activeUser->suspended_until = now()->addMinutes(10);
    $activeUser->withAccessToken(new \Laravel\Sanctum\TransientToken);
    $denied = $middleware->handle($apiRequest, fn () => throw new RuntimeException('Suspended session reached the controller.'));
    authCheck($denied->getStatusCode() === 403 && $denied->getData(true)['message'] === 'Your account is temporarily suspended.', 'Suspended browser API denial changed or crashed.');
    echo "PASS: inactive/suspended browser API sessions return 403, inactive bearer tokens revoke only the current token, active users proceed.\n";
} catch (Throwable $e) {
    $failed = true;
    fwrite(STDERR, 'FAIL: '.$e->getMessage().PHP_EOL);
} finally {
    Carbon::setTestNow();
    DB::disconnect('mysql');
    $pdo->exec("DROP DATABASE `{$database}`");
}
if ($failed) { exit(1); }
