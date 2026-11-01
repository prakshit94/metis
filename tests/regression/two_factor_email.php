<?php

// Included by two_factor.php inside its disposable schema; never use application mail transport.
use App\Mail\TwoFactorCode;
use App\Services\TwoFactorEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

$token = json_decode($login->getContent(), true)['token'];
config(['mail.default' => 'log', 'two_factor.email_mailer' => null]);
check(! app(TwoFactorEmail::class)->available(), 'email factor rejects log transport');
config(['two_factor.email_mailer' => 'failover']);
check(! app(TwoFactorEmail::class)->available(), 'email factor rejects fallback transports that can expose OTPs in logs');
config(['two_factor.email_mailer' => null]);
clear2fa();
$response = http2fa('/api/security/two-factor/setup', 'POST', ['current_password' => 'Example123!', 'method' => 'email'], $token);
check($response->getStatusCode() === 503 && ! $service->enabled($user), 'missing delivery configuration cannot enable email protection');
Mail::fake();
function clearEmailCooldown(): void
{
    global $uid;
    RateLimiter::clear('two-factor-email-cooldown:'.$uid);
}
function lastEmailCode(): string
{
    return Mail::sent(TwoFactorCode::class)->last()->code;
}
clearEmailCooldown();
$setupEmail = management('setup', ['method' => 'email'], $token);
check($setupEmail['method'] === 'email' && ! isset($setupEmail['secret']) && ! isset($setupEmail['code']), 'email enrollment response contains no verification secret or code');
check(Mail::sent(TwoFactorCode::class)->count() === 1, 'email enrollment sends one mail through the mailer');
$emailCode = lastEmailCode();
$storedCode = DB::table('two_factor_email_codes')->where('user_id', $uid)->first();
check(strlen($storedCode->code_hash) === 64 && ! str_contains(json_encode($storedCode), $emailCode), 'email OTP stored as a context-bound keyed hash');
check(! $service->enabled($user), 'email is not enabled before ownership confirmation');
$enabledEmail = management('confirm', ['code' => $emailCode], $token);
check($enabledEmail['enabled'] && $enabledEmail['method'] === 'email' && count($enabledEmail['recovery_codes']) === 10, 'confirmed email factor has real recovery codes');
$emailRecovery = $enabledEmail['recovery_codes'];
check(DB::table('two_factor_email_codes')->where('user_id', $uid)->count() === 0, 'enrollment OTP is removed on confirmation');
clear2fa();
clearEmailCooldown();
$loginEmail = http2fa('/api/auth/login', 'POST', ['email' => $user->email, 'password' => 'Example123!']);
$challengeData = json_decode($loginEmail->getContent(), true);
check($loginEmail->getStatusCode() === 202 && $challengeData['method'] === 'email' && $challengeData['email_delivery_error'] === null, 'mobile password step sends email and exposes only method and masked address');
$challenge = $challengeData['challenge_token'];
$codeOne = lastEmailCode();
$countBeforeResend = Mail::sent(TwoFactorCode::class)->count();
$resend = http2fa('/api/auth/two-factor/email', 'POST', ['challenge_token' => $challenge]);
check($resend->getStatusCode() === 429 && Mail::sent(TwoFactorCode::class)->count() === $countBeforeResend, 'email resend enforces 60-second cooldown');
clearEmailCooldown();
$resend = http2fa('/api/auth/two-factor/email', 'POST', ['challenge_token' => $challenge]);
check($resend->getStatusCode() === 200, 'native resend accepts only the pending challenge');
$codeTwo = lastEmailCode();
// An old code must be invalid unless secure random generation happened to repeat its value.
if ($codeOne !== $codeTwo) {
    check(http2fa('/api/auth/two-factor/challenge', 'POST', ['challenge_token' => $challenge, 'code' => $codeOne])->getStatusCode() === 422, 'resend invalidates the previous email code');
}
$verified = http2fa('/api/auth/two-factor/challenge', 'POST', ['challenge_token' => $challenge, 'code' => $codeTwo]);
$verifiedData = json_decode($verified->getContent(), true);
check($verified->getStatusCode() === 200 && isset($verifiedData['token']), 'email OTP completes normal native sign-in');
$token = $verifiedData['token'];
check(http2fa('/api/auth/two-factor/challenge', 'POST', ['challenge_token' => $challenge, 'code' => $codeTwo])->getStatusCode() === 422, 'email OTP and login challenge cannot be replayed');
clear2fa();
clearEmailCooldown();
$requestSettings = http2fa('/api/security/two-factor/email', 'POST', ['action' => 'recovery', 'current_password' => 'Example123!'], $token);
check($requestSettings->getStatusCode() === 200, 'sensitive settings send email only after password reauthentication');
$settingsCode = lastEmailCode();
$response = http2fa('/api/security/two-factor', 'POST', ['action' => 'disable', 'current_password' => 'Example123!', 'code' => $settingsCode], $token);
check($response->getStatusCode() === 422 && $service->enabled($user), 'recovery-regeneration email cannot authorize disabling 2FA');
$regeneratedEmail = management('recovery', ['code' => $settingsCode], $token);
check(count($regeneratedEmail['recovery_codes']) === 10, 'email verification authorizes intended recovery regeneration');
$emailRecovery = $regeneratedEmail['recovery_codes'];
clear2fa();
clearEmailCooldown();
$expiredLogin = json_decode(http2fa('/api/auth/login', 'POST', ['email' => $user->email, 'password' => 'Example123!'])->getContent(), true);
$expiredCode = lastEmailCode();
DB::table('two_factor_email_codes')->where('user_id', $uid)->update(['expires_at' => now()->subSecond()]);
check(http2fa('/api/auth/two-factor/challenge', 'POST', ['challenge_token' => $expiredLogin['challenge_token'], 'code' => $expiredCode])->getStatusCode() === 422, 'expired email code rejected even while login challenge is valid');
// Never use an old verified address after a profile/email change.
DB::table('users')->where('id', $uid)->update(['email' => 'changed@example.invalid']);
$user->refresh();
clear2fa();
clearEmailCooldown();
$changedLogin = json_decode(http2fa('/api/auth/login', 'POST', ['email' => $user->email, 'password' => 'Example123!'])->getContent(), true);
check($changedLogin['two_factor_required'] && str_contains($changedLogin['email_delivery_error'], 'email changed'), 'changed account address cannot silently receive or bypass second-factor email');
$response = http2fa('/api/auth/two-factor/challenge', 'POST', ['challenge_token' => $changedLogin['challenge_token'], 'code' => $emailRecovery[0]]);
check($response->getStatusCode() === 200, 'recovery-code sign-in remains available after email changes');
$token = json_decode($response->getContent(), true)['token'];
// Browser delivery and resend share the same enforcement.
clear2fa();
management('disable', ['code' => $emailRecovery[1]], $token);
clearEmailCooldown();
management('setup', ['method' => 'email'], $token);
management('confirm', ['code' => lastEmailCode()], $token);
clear2fa();
clearEmailCooldown();
app('session')->driver()->invalidate(); // Fresh browser session; array sessions cannot be revoked via the DB table.
$webCookies = [];
$webLoginEmail = $web('/admin/login', 'POST', ['email' => $user->email, 'password' => 'Example123!']);
check($webLoginEmail->getStatusCode() === 302 && str_ends_with($webLoginEmail->headers->get('Location'), '/admin/login/two-factor'), 'browser email login stops at verification');
$browserCode = lastEmailCode();
$page = $web('/admin/login/two-factor');
check($page->getStatusCode() === 200 && str_contains($page->getContent(), 'Resend verification email') && str_contains($page->getContent(), 'c***@example.invalid'), 'browser challenge shows masked address and resend control');
$browserVerified = $web('/admin/login/two-factor', 'POST', ['code' => $browserCode]);
check($browserVerified->getStatusCode() === 302 && ! session()->has('two_factor_challenge'), 'browser email verification completes existing session flow');
$eventJson = DB::table('two_factor_security_events')->where('user_id', $uid)->get()->toJson();
check(str_contains($eventJson, 'email_login_code_sent') && ! str_contains($eventJson, $browserCode), 'email audit records delivery events without verification codes');
check(Mail::sent(TwoFactorCode::class)->every(fn ($mail) => preg_match('/^\d{6}$/D', $mail->code) && count($mail->to) === 1), 'all verification mails contain six-digit codes and a single account recipient');
