<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\TwoFactorCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Testing\Fakes\MailFake;

/** Call under the account's factor-row lock, maintaining a consistent lock order. */
final class TwoFactorEmail
{
    public function __construct(private SecurityMailSettings $settings) {}

    public function available(): bool
    {
        if (app()->environment('testing') && Mail::getFacadeRoot() instanceof MailFake) {
            return true;
        }

        return $this->settings->isDeliverable($this->mailer());
    }

    private function mailer(): string
    {
        return $this->settings->resolveMailer();
    }

    public function id(int $userId, string $purpose, string $context): string
    {
        return hash('sha256', $userId.'|'.$purpose.'|'.$context);
    }

    public function send(int $userId, string $email, string $purpose, string $context): void
    {
        abort_unless($this->available(), 503, 'Email verification requires a configured delivery mailer. Ask an administrator to configure Mail Configuration on the Security page; log mailers are not supported.');
        $hourKey = 'two-factor-email-hour:'.$userId;
        $cooldownKey = 'two-factor-email-cooldown:'.$userId;
        abort_if(RateLimiter::tooManyAttempts($hourKey, 10) || RateLimiter::tooManyAttempts($cooldownKey, 1), 429,
            'Please wait at least 60 seconds between emails. A maximum of ten verification emails can be requested per hour.');
        RateLimiter::hit($hourKey, 3600);
        RateLimiter::hit($cooldownKey, 60);
        $id = $this->id($userId, $purpose, $context);
        $previous = DB::table('two_factor_email_codes')->where('id', $id)->lockForUpdate()->first();
        abort_if($previous && $previous->attempts >= 5, 429, 'Too many attempts for this request. Start a new request after the verification lockout.');
        DB::table('two_factor_email_codes')->where('user_id', $userId)->where('expires_at', '<=', now())->where('id', '!=', $id)->delete();
        do {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while ($previous && hash_equals($previous->code_hash, $this->digest($id, $code)));
        // A new send invalidates the old code without resetting its attempt budget.
        DB::table('two_factor_email_codes')->updateOrInsert(['id' => $id], [
            'user_id' => $userId, 'purpose' => $purpose, 'email_address' => $email,
            'code_hash' => $this->digest($id, $code), 'attempts' => $previous?->attempts ?? 0,
            'expires_at' => now()->addMinutes(5), 'sent_at' => now(),
        ]);
        try {
            $message = new TwoFactorCode($code, match ($purpose) {
                'login' => 'sign-in', 'confirm' => 'email enrollment', 'disable' => 'disable two-factor authentication',
                'mail_settings' => 'mail configuration', 'user_security' => 'user two-step requirements', 'switch' => 'change your two-step method',
                default => 'recovery-code replacement',
            });
            $this->settings->applySender($message);
            Mail::mailer($this->mailer())->to($email)->send($message);
        } catch (\Throwable) {
            DB::table('two_factor_email_codes')->where('id', $id)->delete();
            // Transport errors may include credentials or message contents; do not report/log them.
            abort(503, 'The verification email could not be sent. Try again later or use an unused recovery code.');
        }
    }

    public function consume(int $userId, string $email, string $purpose, string $context, string $code): bool
    {
        $id = $this->id($userId, $purpose, $context);
        $row = DB::table('two_factor_email_codes')->where('id', $id)->lockForUpdate()->first();
        if (! $row || $row->email_address !== $email || now()->gte($row->expires_at) || $row->attempts >= 5) {
            return false;
        }
        DB::table('two_factor_email_codes')->where('id', $id)->increment('attempts');
        if (! preg_match('/^\d{6}$/D', $code) || ! hash_equals($row->code_hash, $this->digest($id, $code))) {
            return false;
        }
        DB::table('two_factor_email_codes')->where('id', $id)->delete();

        return true;
    }

    private function digest(string $id, string $code): string
    {
        return hash_hmac('sha256', $id.'|'.$code, config('app.key'));
    }
}
