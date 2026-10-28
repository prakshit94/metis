<?php

require __DIR__.'/bootstrap.php';

use App\Mail\TwoFactorCode;
use App\Services\TwoFactorEmail;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Testing\Fakes\MailFake;
use Symfony\Component\HttpKernel\Exception\HttpException;

Schema::create('users', function (Blueprint $table) {
    $table->id();
});
$id = DB::table('users')->insertGetId([]);
$base = require dirname(__DIR__, 2).'/database/migrations/2026_10_28_100000_create_two_factor_security_tables.php';
$base->up();
$migration = require dirname(__DIR__, 2).'/database/migrations/2026_10_28_110000_add_email_two_factor_authentication.php';
$migration->up();
(require dirname(__DIR__, 2).'/database/migrations/2026_10_28_130000_add_user_two_factor_requirement.php')->up();
Mail::fake();
$sender = app(TwoFactorEmail::class);
$context = 'fixture-context';
$sender->send($id, 'fixture@example.invalid', 'login', $context);
$code = Mail::sent(TwoFactorCode::class)->last()->code;
check(str_contains((new TwoFactorCode('123456', 'sign-in'))->render(), '123456'), 'verification mailable renders its text template');
$sender->consume($id, 'fixture@example.invalid', 'login', $context, 'wrong');
RateLimiter::clear('two-factor-email-cooldown:'.$id);
$sender->send($id, 'fixture@example.invalid', 'login', $context);
$row = DB::table('two_factor_email_codes')->where('user_id', $id)->first();
check($row->attempts === 1, 'resending preserves failed attempt budget');
$replacement = Mail::sent(TwoFactorCode::class)->last()->code;
check($replacement !== $code, 'resend always changes the previous code value');
for ($i = 0; $i < 4; $i++) {
    $sender->consume($id, 'fixture@example.invalid', 'login', $context, 'wrong');
}
check(! $sender->consume($id, 'fixture@example.invalid', 'login', $context, $replacement), 'exhausted email attempt budget rejects even a correct code');
RateLimiter::clear('two-factor-email-cooldown:'.$id);
try {
    $sender->send($id, 'fixture@example.invalid', 'login', $context);
    throw new RuntimeException('Resend reset attempt budget');
} catch (HttpException $error) {
    check($error->getStatusCode() === 429, 'resend cannot reset an exhausted attempt budget');
}
RateLimiter::clear('two-factor-email-cooldown:'.$id);
Mail::swap(new class(Mail::getFacadeRoot()->manager) extends MailFake
{
    public function send($view, array $data = [], $callback = null)
    {
        throw new RuntimeException('fixture transport failure: do not expose message or credentials');
    }
});
try {
    $sender->send($id, 'fixture@example.invalid', 'disable', 'fixture-failure');
    throw new RuntimeException('Delivery failure accepted');
} catch (HttpException $error) {
    check($error->getStatusCode() === 503 && ! str_contains($error->getMessage(), 'fixture transport'), 'mail failure is reported without transport exception details');
}
check(! DB::table('two_factor_email_codes')->where('purpose', 'disable')->exists(), 'failed delivery cannot leave a newly usable settings code');
$migration->down();
check(Schema::hasTable('user_two_factor_settings') && ! Schema::hasColumn('user_two_factor_settings', 'method'), 'email migration rolls back without removing existing authenticator settings');
$base->down();
