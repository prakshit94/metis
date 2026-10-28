<?php

require __DIR__.'/bootstrap.php';

use App\Modules\Users\Models\User;
use App\Services\Totp;
use App\Services\TwoFactorService;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Session\Store;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

$totp = app(Totp::class);
$secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';
foreach ([59 => '94287082', 1111111109 => '07081804', 1111111111 => '14050471', 1234567890 => '89005924', 2000000000 => '69279037', 20000000000 => '65353130'] as $timestamp => $expected) {
    check($totp->code($secret, intdiv($timestamp, 30), 8) === $expected, 'RFC 6238 SHA-1 vector '.$timestamp);
}
foreach ([0 => '755224', 1 => '287082', 2 => '359152', 3 => '969429', 4 => '338314', 5 => '254676', 6 => '287922', 7 => '162583', 8 => '399871', 9 => '520489'] as $counter => $expected) {
    check($totp->code($secret, $counter) === $expected, 'RFC 4226 counter '.$counter);
}
check(strlen($totp->secret()) === 32, 'generated setup key has 160 bits of randomness');
Artisan::call('migrate', ['--force' => true]);
check(DB::table('migrations')->count() === count(glob(dirname(__DIR__, 2).'/database/migrations/*.php')), 'all migrations including 2FA execute');
$uid = DB::table('users')->insertGetId(['name' => '2FA Test', 'email' => 'twofactor@example.invalid', 'password' => bcrypt('Example123!'), 'is_active' => 1]);
$user = User::findOrFail($uid);
$service = app(TwoFactorService::class);
function http2fa(string $uri, string $method = 'GET', array $body = [], ?string $bearer = null, ?Store $session = null): Response
{
    global $app;
    auth()->forgetGuards();
    auth()->shouldUse('web');
    if ($session) {
        $app->instance('session.store', $session);
        $app['session']->setDefaultDriver('array');
        $app['session']->extend('array', fn () => $session);
    }
    $request = Request::create($uri, $method, $body, [], [], $bearer ? ['HTTP_AUTHORIZATION' => 'Bearer '.$bearer] : []);

    return $app->make(Kernel::class)->handle($request);
}
function clear2fa(): void
{
    global $uid;
    RateLimiter::clear('two-factor-code:'.$uid);
    RateLimiter::clear('two-factor-password:'.$uid);
}
function management(string $action, array $data, string $token): array
{
    $uri = '/api/security/two-factor'.($action === 'setup' ? '/setup' : '');
    $response = http2fa($uri, 'POST', ['current_password' => 'Example123!', 'action' => $action] + $data, $token);
    check($response->getStatusCode() === 200, $action.' succeeds (HTTP '.$response->getStatusCode().')');

    return json_decode($response->getContent(), true);
}
$token = $user->createToken('Test mobile')->plainTextToken;
check(http2fa('/api/security/two-factor')->getStatusCode() === 401, '2FA settings require authentication');
$status = http2fa('/api/security/two-factor', 'GET', [], $token);
check(json_decode($status->getContent(), true)['enabled'] === false, '2FA defaults disabled on existing accounts');
check(http2fa('/api/security/two-factor/setup', 'POST', ['current_password' => 'wrong'], $token)->getStatusCode() === 422, 'setup rejects wrong current password');
$setup = management('setup', [], $token);
check(str_starts_with($setup['otpauth_uri'], 'otpauth://totp/'), 'mobile authenticator enrollment URI returned');
$row = DB::table('user_two_factor_settings')->where('user_id', $uid)->first();
check(! str_contains($row->pending_secret, $setup['secret']) && Crypt::decryptString($row->pending_secret) === $setup['secret'], 'setup secret encrypted at rest');
check(! $service->enabled($user), 'unconfirmed setup cannot enable 2FA');
$secret = $setup['secret'];
$code = $totp->code($secret, intdiv(now()->timestamp, 30));
$otherToken = $user->createToken('Old mobile')->plainTextToken;
$enabled = management('confirm', ['code' => $code], $token);
check(count($enabled['recovery_codes']) === 10 && $enabled['enabled'], 'enrollment issues ten real recovery codes');
$recovery = $enabled['recovery_codes'];
check(http2fa('/api/security/two-factor', 'GET', [], $token)->getStatusCode() === 200, 'enrollment preserves current native credential');
check(http2fa('/api/security/two-factor', 'GET', [], $otherToken)->getStatusCode() === 401, 'enrollment revokes older native credential');
$stored = DB::table('user_two_factor_settings')->where('user_id', $uid)->first();
check(! str_contains(json_encode($stored), $secret) && ! str_contains($stored->recovery_hashes, $recovery[0]), 'confirmed secrets encrypted and recovery codes hashed');
check(! str_contains($user->toJson(), $secret) && ! str_contains($status->getContent(), 'secret'), 'User serialization and status do not disclose secrets');
$beforeAttendance = DB::table('attendances')->where('user_id', $uid)->count();
$beforeHistory = DB::table('login_histories')->where('user_id', $uid)->where('status', 'success')->count();
$login = http2fa('/api/auth/login', 'POST', ['email' => $user->email, 'password' => 'Example123!']);
$pending = json_decode($login->getContent(), true);
check($login->getStatusCode() === 202 && $pending['two_factor_required'] && ! isset($pending['token']), 'mobile password login returns challenge without full token');
check(http2fa('/api/security/two-factor', 'GET', [], $token)->getStatusCode() === 200, 'first factor does not revoke previous credential');
check(DB::table('attendances')->where('user_id', $uid)->count() === $beforeAttendance && DB::table('login_histories')->where('user_id', $uid)->where('status', 'success')->count() === $beforeHistory, 'first factor does not check in attendance or record successful login');
clear2fa();
$challenge = $pending['challenge_token'];
$reject = http2fa('/api/auth/two-factor/challenge', 'POST', ['challenge_token' => $challenge, 'code' => $code]);
check($reject->getStatusCode() === 422, 'enrollment TOTP cannot be replayed at login');
check(DB::table('two_factor_login_challenges')->where('id', hash('sha256', $challenge))->value('attempts') === 1, 'failed challenge attempt persisted');
$verified = http2fa('/api/auth/two-factor/challenge', 'POST', ['challenge_token' => $challenge, 'code' => $recovery[0]]);
$data = json_decode($verified->getContent(), true);
check($verified->getStatusCode() === 200 && isset($data['token']), 'mobile recovery code completes existing token login');
$token = $data['token'];
check(DB::table('attendances')->where('user_id', $uid)->count() === $beforeAttendance + 1, 'attendance recorded only after second factor');
check($service->status($user)['recovery_codes_remaining'] === 9, 'recovery code consumed exactly once');
check(http2fa('/api/auth/two-factor/challenge', 'POST', ['challenge_token' => $challenge, 'code' => $recovery[1]])->getStatusCode() === 422, 'consumed login challenge cannot be replayed');
clear2fa();
$req = requestFor([]);
$ch = $service->begin($user, $req, 'mobile');
check($service->verifyLogin($ch, $recovery[0], 'mobile', $req) === null, 'used recovery code rejected by later challenge');
check($service->verifyLogin($ch, $recovery[1], 'web', $req) === null, 'native challenge cannot cross to browser channel');
DB::table('two_factor_login_challenges')->where('id', hash('sha256', $ch))->update(['expires_at' => now()->subSecond()]);
check($service->verifyLogin($ch, $recovery[1], 'mobile', $req) === null, 'expired challenge rejected');
clear2fa();
$ch = $service->begin($user, $req, 'mobile');
DB::table('users')->where('id', $uid)->update(['password_changed_at' => now()->addSecond()]);
check($service->verifyLogin($ch, $recovery[1], 'mobile', $req) === null, 'password change invalidates pending challenge');
$user->refresh();
clear2fa();
$ch = $service->begin($user, $req, 'mobile');
DB::table('users')->where('id', $uid)->update(['is_active' => false]);
check($service->verifyLogin($ch, $recovery[1], 'mobile', $req) === null, 'deactivated user cannot finish pending sign-in');
DB::table('users')->where('id', $uid)->update(['is_active' => true]);
clear2fa();
$ch = $service->begin($user, $req, 'mobile');
for ($i = 0; $i < 5; $i++) {
    check($service->verifyLogin($ch, 'invalid', 'mobile', $req) === null, 'invalid code attempt '.($i + 1));
}
$newCh = $service->begin($user, $req, 'mobile');
try {
    $service->verifyLogin($newCh, $recovery[1], 'mobile', $req);
    throw new RuntimeException('Account throttle bypassed');
} catch (HttpException $error) {
    check($error->getStatusCode() === 429, 'new password challenge cannot reset account OTP throttle');
}
clear2fa();
$regenerated = management('recovery', ['code' => $recovery[1]], $token);
check(count($regenerated['recovery_codes']) === 10, 'regeneration issues replacement recovery set');
$ch = $service->begin($user, $req, 'mobile');
check($service->verifyLogin($ch, $recovery[2], 'mobile', $req) === null, 'replacement invalidates old recovery codes');
clear2fa();
Carbon::setTestNow(now()->addSeconds(90));
$freshCode = $totp->code($secret, intdiv(now()->timestamp, 30));
$ch = $service->begin($user, $req, 'mobile');
check($service->verifyLogin($ch, $freshCode, 'mobile', $req) !== null, 'fresh TOTP successfully verifies');
$ch = $service->begin($user, $req, 'mobile');
check($service->verifyLogin($ch, $freshCode, 'mobile', $req) === null, 'TOTP replay rejected across challenges');
Carbon::setTestNow();
clear2fa();
$webCookies = [];
$web = function (string $uri, string $method = 'GET', array $body = []) use ($app, &$webCookies) {
    auth()->forgetGuards();
    auth()->shouldUse('web');
    $request = Request::create($uri, $method, $body, $webCookies);
    $response = $app->make(Kernel::class)->handle($request);
    foreach ($response->headers->getCookies() as $cookie) {
        $webCookies[$cookie->getName()] = $cookie->getValue();
    }

    return $response;
};
$webLogin = $web('/login', 'POST', ['email' => $user->email, 'password' => 'Example123!', 'remember' => 1]);
check($webLogin->getStatusCode() === 302 && str_ends_with($webLogin->headers->get('Location'), '/login/two-factor'), 'browser password login redirects to second factor');
check($web('/login/two-factor')->getStatusCode() === 200, 'browser challenge renders with its pending session');
$wrong = $web('/login/two-factor', 'POST', ['code' => 'bad']);
check($wrong->getStatusCode() === 302 && ! array_key_exists('code', session()->getOldInput()), 'browser failure redirects without flashing the submitted code');
$webSuccess = $web('/login/two-factor', 'POST', ['code' => $regenerated['recovery_codes'][1]]);
check($webSuccess->getStatusCode() === 302 && ! str_ends_with($webSuccess->headers->get('Location'), '/login/two-factor'), 'browser recovery verification completes web sign-in');
check(isset($webCookies['remembered_email']), 'browser remember preference survives the second factor');
check(! session()->has('two_factor_challenge'), 'successful browser verification clears pending challenge');
check($web('/security')->getStatusCode() === 200, 'authenticated security page renders after second factor');
// Security mutation must not be possible while borrowing another identity.
$impersonated = requestFor(['current_password' => 'Example123!']);
$impersonated->setUserResolver(fn () => $user->fresh());
$impersonated->setLaravelSession(app('session')->driver());
$impersonated->session()->put('impersonated_by', 999);
try {
    $service->reauthenticate($impersonated);
    throw new RuntimeException('Impersonated account can mutate 2FA');
} catch (HttpException $error) {
    check($error->getStatusCode() === 403, 'impersonation cannot manage another account two-factor credentials');
}
$impersonated->session()->forget('impersonated_by');
// This disposable token isolates the remaining management test from web single-login revocation.
$token = $user->fresh()->createToken('Management after web test')->plainTextToken;
clear2fa();
$disabled = management('disable', ['code' => $regenerated['recovery_codes'][0]], $token);
check(! $disabled['enabled'] && $disabled['recovery_codes_remaining'] === 0, 'disable clears secret and recovery codes');
$login = http2fa('/api/auth/login', 'POST', ['email' => $user->email, 'password' => 'Example123!']);
check($login->getStatusCode() === 200 && isset(json_decode($login->getContent(), true)['token']), 'password-only accounts retain original mobile login');
$events = DB::table('two_factor_security_events')->where('user_id', $uid)->get()->toJson();
check(str_contains($events, 'challenge_verified') && str_contains($events, 'recovery_code_used') && str_contains($events, 'disabled'), 'security audit records real factor lifecycle and recovery usage');
check(! str_contains($events, $secret) && ! str_contains($events, $recovery[0]) && ! str_contains($events, 'Example123!') && ! str_contains($events, $challenge), 'audit records contain no passwords, secrets, OTPs, recovery codes or challenge bearers');
require __DIR__.'/two_factor_email.php';

require __DIR__.'/two_factor_required.php';

Artisan::call('migrate:rollback', ['--path' => [
    'database/migrations/2026_10_28_100000_create_two_factor_security_tables.php',
    'database/migrations/2026_10_28_110000_add_email_two_factor_authentication.php',
    'database/migrations/2026_10_28_120000_create_security_mail_settings.php',
    'database/migrations/2026_10_28_130000_add_user_two_factor_requirement.php',
], '--force' => true]);
check(! Schema::hasTable('user_two_factor_settings'), '2FA migration rolls back cleanly');
echo "2FA regression checks passed.\n";
