<?php

require __DIR__.'/bootstrap.php';

use App\Mail\SecurityMailTest;
use App\Mail\TwoFactorCode;
use App\Modules\Users\Controllers\SecurityMailController;
use App\Modules\Users\Models\Permission;
use App\Modules\Users\Models\User;
use App\Services\SecurityMailSettings;
use App\Services\Totp;
use App\Services\TwoFactorEmail;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;

Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email');
    $table->string('password');
    $table->boolean('is_active')->default(true);
    $table->timestamp('suspended_until')->nullable();
    $table->timestamp('password_changed_at')->nullable();
    $table->rememberToken();
    $table->timestamps();
    $table->softDeletes();
});
$files = [
    '2025_01_01_000001_create_personal_access_tokens_table.php',
    '2025_01_01_000002_create_permissions_table.php', '2025_01_01_000002a_create_roles_table.php',
    '2025_01_01_000002b_create_model_has_permissions_table.php', '2025_01_01_000002c_create_model_has_roles_table.php',
    '2025_01_01_000002d_create_role_has_permissions_table.php',
    '2026_10_28_100000_create_two_factor_security_tables.php', '2026_10_28_110000_add_email_two_factor_authentication.php',
    '2026_10_28_120000_create_security_mail_settings.php',
    '2026_10_28_130000_add_user_two_factor_requirement.php',
];
foreach ($files as $file) {
    (require dirname(__DIR__, 2).'/database/migrations/'.$file)->up();
}
$uid = DB::table('users')->insertGetId(['name' => 'Mail admin', 'email' => 'mailadmin@example.invalid', 'password' => bcrypt('Example123!')]);
$otherId = DB::table('users')->insertGetId(['name' => 'Ordinary user', 'email' => 'other@example.invalid', 'password' => bcrypt('Example123!')]);
$user = User::findOrFail($uid);
$user->assignRole(\App\Modules\Users\Models\Role::findOrCreate('Super Admin', 'web'));
Permission::create(['name' => 'settings-edit', 'guard_name' => 'web']);
$user->givePermissionTo('settings-edit');
app(\App\Services\PermissionCatalogSync::class)->run();
$token = $user->createToken('fixture')->plainTextToken;
$otherToken = User::findOrFail($otherId)->createToken('fixture')->plainTextToken;
function mailHttp(string $method, string $uri, array $body = [], ?string $bearer = null)
{
    global $app;
    auth()->forgetGuards();
    auth()->shouldUse('web');

    return $app->make(Kernel::class)->handle(Request::create($uri, $method, $body, [], [], $bearer ? ['HTTP_AUTHORIZATION' => 'Bearer '.$bearer] : []));
}
$delegated = User::findOrFail($otherId);
$delegated->assignRole(\App\Modules\Users\Models\Role::findOrCreate('Super Admin', 'web'));
foreach (['GET' => '/api/security/mail', 'POST' => '/api/security/mail/test'] as $method => $uri) {
    check(mailHttp($method, $uri, ['current_password'=>'Example123!'], $otherToken)->getStatusCode() === 403, 'non-master Super Admin cannot '.$method.' mail configuration');
}
check(mailHttp('POST','/api/security/mail',['enabled'=>false,'current_password'=>'Example123!'],$otherToken)->getStatusCode()===403, 'non-master Super Admin cannot save shared mail configuration');
$delegated->removeRole('Super Admin');
$settings = app(SecurityMailSettings::class);
check(mailHttp('GET', '/api/security/mail')->getStatusCode() === 401, 'mail settings require authentication');
check(mailHttp('GET', '/api/security/mail', [], $otherToken)->getStatusCode() === 403, 'ordinary account cannot read system mail configuration');
check(mailHttp('POST', '/api/security/mail', [], $otherToken)->getStatusCode() === 403, 'ordinary account cannot change system mail configuration');
$response = mailHttp('GET', '/api/security/mail', [], $token);
check($response->getStatusCode() === 200 && ! json_decode($response->getContent(), true)['settings']['enabled'], 'migrated mail-view permission grants default configuration view');
check(str_contains($response->headers->get('Cache-Control'), 'no-store'), 'mail configuration response is never cached');
$data = ['enabled' => true, 'provider' => 'smtp', 'host' => 'smtp.example.invalid', 'port' => 587,
    'encryption' => 'starttls', 'username' => 'smtp-fixture', 'password' => 'PrivateFixtureSecret!', 'timeout' => 12,
    'from_name' => 'Test sender', 'from_address' => 'sender@example.invalid', 'reply_to' => 'reply@example.invalid',
    'current_password' => 'Example123!'];
$bad = mailHttp('POST', '/api/security/mail', array_replace($data, ['current_password' => 'wrong']), $token);
check($bad->getStatusCode() === 422 && ! DB::table('security_mail_settings')->exists(), 'incorrect password cannot save mail credentials');
$saved = mailHttp('POST', '/api/security/mail', $data, $token);
check($saved->getStatusCode() === 200, 'administrator saves SMTP configuration through native-compatible endpoint');
$public = json_decode($saved->getContent(), true);
check($public['settings']['password_present'] && ! isset($public['settings']['password']) && ! str_contains($saved->getContent(), 'PrivateFixtureSecret!'), 'saved credentials are redacted in mutation responses');
$encrypted = DB::table('security_mail_settings')->value('encrypted_payload');
check(! str_contains($encrypted, 'PrivateFixtureSecret!') && json_decode(Crypt::decryptString($encrypted), true)['password'] === 'PrivateFixtureSecret!', 'SMTP credentials are encrypted at rest');
check(! str_contains(Crypt::decryptString($encrypted), 'current_password'), 'account reauthentication password is never persisted in mail configuration');
$data['password'] = '';
$data['host'] = 'smtp2.example.invalid';
check(mailHttp('POST', '/api/security/mail', $data, $token)->getStatusCode() === 200, 'blank SMTP password preserves existing saved credential');
check(json_decode(Crypt::decryptString(DB::table('security_mail_settings')->value('encrypted_payload')), true)['password'] === 'PrivateFixtureSecret!', 'blank credential update retains exact original secret');
$globalDefault = config('mail.default');
$globalFrom = config('mail.from');
$mailer = $settings->resolveMailer();
check($mailer === 'security_database' && config('mail.mailers.'.$mailer.'.host') === 'smtp2.example.invalid', '2FA transport uses stored SMTP host without env edits');
check(config('mail.mailers.'.$mailer.'.require_tls') === true && config('mail.mailers.'.$mailer.'.timeout') === 12, 'stored SMTP transport enforces TLS and configured timeout');
check(config('mail.default') === $globalDefault && config('mail.from') === $globalFrom, 'dedicated mailer leaves business mail defaults unchanged');
$invalidHost = mailHttp('POST', '/api/security/mail', array_replace($data, ['host' => 'https://smtp.example.invalid/path']), $token);
check($invalidHost->getStatusCode() === 422, 'SMTP host rejects URL/path injection');
check(mailHttp('POST', '/api/security/mail', array_replace($data, ['port' => 70000]), $token)->getStatusCode() === 422, 'SMTP port bounds validated');
check(mailHttp('POST', '/api/security/mail', array_replace($data, ['encryption' => 'none']), $token)->getStatusCode() === 422, 'UI cannot disable transport encryption');
check(mailHttp('POST', '/api/security/mail', array_replace($data, ['provider' => 'existing', 'existing_mailer' => 'failover']), $token)->getStatusCode() === 422, 'existing mailer with log fallback cannot receive OTPs');
Mail::fake();
$test = mailHttp('POST', '/api/security/mail/test', ['current_password' => 'Example123!'], $token);
check($test->getStatusCode() === 200, 'administrator can request a saved-configuration test email');
$mail = Mail::sent(SecurityMailTest::class)->last();
check($mail && $mail->hasTo($user->email) && $mail->hasFrom('sender@example.invalid', 'Test sender') && $mail->hasReplyTo('reply@example.invalid'), 'test mail uses saved sender/reply-to and only current account recipient');
app(TwoFactorEmail::class)->send($uid, $user->email, 'login', 'smtp-routing-fixture');
$factorMail = Mail::sent(TwoFactorCode::class)->last();
check($factorMail && $factorMail->mailer === 'security_database' && $factorMail->hasFrom('sender@example.invalid'), 'verification emails use the saved database mailer and sender');
$req = requestFor([]);
$req->setUserResolver(fn () => $user);
$req->setLaravelSession(app('session')->driver());
$req->session()->put('impersonated_by', $otherId);
try {
    app(SecurityMailController::class)->show($req, $settings);
    throw new RuntimeException('Impersonation can read mail credentials');
} catch (HttpException $error) {
    check($error->getStatusCode() === 403, 'impersonation cannot manage SMTP configuration');
}
$req->session()->forget('impersonated_by');
// Enrolled administrators also need their factor; failed attempts must persist.
$secret = app(Totp::class)->secret();
DB::table('user_two_factor_settings')->insert(['user_id' => $uid, 'secret' => Crypt::encryptString($secret),
    'confirmed_at' => now(), 'version' => str_repeat('a', 64), 'recovery_hashes' => '[]']);
$before = DB::table('security_mail_settings')->value('encrypted_payload');
check(mailHttp('POST', '/api/security/mail', $data, $token)->getStatusCode() === 422, 'enrolled administrator cannot change SMTP without second factor');
check(DB::table('security_mail_settings')->value('encrypted_payload') === $before, 'failed factor leaves saved SMTP configuration unchanged');
check(DB::table('two_factor_security_events')->where('event', 'mail_settings_verification_failed')->exists(), 'failed configuration verification is audited outside rollback');
$code = app(Totp::class)->code($secret, intdiv(now()->timestamp, 30));
check(mailHttp('POST', '/api/security/mail', $data + ['code' => $code], $token)->getStatusCode() === 200, 'fresh authenticator code authorizes mail settings update');
check(mailHttp('POST', '/api/security/mail', $data + ['code' => $code], $token)->getStatusCode() === 422, 'SMTP updates reject reused authenticator codes');
$events = DB::table('two_factor_security_events')->get()->toJson();
check(str_contains($events, 'mail_settings_updated') && ! str_contains($events, 'PrivateFixtureSecret!') && ! str_contains($events, 'Example123!'), 'mail audit events never include stored or submitted credentials');
DB::table('user_two_factor_settings')->where('user_id', $uid)->update(['method' => 'email', 'email_address' => $user->email]);
RateLimiter::clear('two-factor-code:'.$uid);
RateLimiter::clear('two-factor-email-cooldown:'.$uid);
$emailRequest = mailHttp('POST', '/api/security/two-factor/email', ['current_password' => 'Example123!', 'action' => 'mail_settings'], $token);
check($emailRequest->getStatusCode() === 200, 'email-enrolled administrator can request purpose-bound mail-configuration verification');
$emailCode = Mail::sent(TwoFactorCode::class)->last()->code;
$wrong = mailHttp('POST', '/api/security/mail', $data + ['code' => 'wrong'], $token);
$codeId = app(TwoFactorEmail::class)->id($uid, 'mail_settings', str_repeat('a', 64));
check($wrong->getStatusCode() === 422 && DB::table('two_factor_email_codes')->where('id', $codeId)->value('attempts') === 1, 'failed email verification attempt persists after configuration rejection');
check(mailHttp('POST', '/api/security/mail', $data + ['code' => $emailCode], $token)->getStatusCode() === 200, 'purpose-bound email code authorizes mail configuration update');
check(mailHttp('POST', '/api/security/mail', $data + ['code' => $emailCode], $token)->getStatusCode() === 422, 'consumed email code cannot authorize a second configuration update');
// Clear credentials explicitly only while disabling this override.
DB::table('user_two_factor_settings')->where('user_id', $uid)->update(['confirmed_at' => null]);
$data['enabled'] = false;
$data['clear_credentials'] = true;
$cleared = mailHttp('POST', '/api/security/mail', $data, $token);
check($cleared->getStatusCode() === 200 && ! json_decode($cleared->getContent(), true)['settings']['password_present'], 'explicit disabled save clears stored credentials');
check($settings->resolveMailer() === (config('two_factor.email_mailer') ?: config('mail.default')), 'disabled override restores previous application mailer selection');
$settings->save(['enabled' => false, 'provider' => 'postmark', 'api_key' => 'ProviderSpecificFixtureKey'], $uid);
$settings->save(['enabled' => false, 'provider' => 'resend', 'api_key' => ''], $uid);
check(! $settings->publicSettings()['settings']['api_key_present'], 'switching API providers never reuses another provider API key');
(require dirname(__DIR__, 2).'/database/migrations/2026_10_28_120000_create_security_mail_settings.php')->down();
check(Schema::hasTable('user_two_factor_settings') && ! Schema::hasTable('security_mail_settings'), 'SMTP settings migration rolls back without removing enrolled factors');
echo "Mail configuration checks passed.\n";
