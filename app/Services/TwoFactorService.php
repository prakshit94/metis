<?php

declare(strict_types=1);

namespace App\Services;

use App\Modules\Users\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

/** Secrets live outside audited/serialized User models. All consumptions lock the settings row. */
final class TwoFactorService
{
    public function __construct(private Totp $totp, private TwoFactorEmail $email) {}

    public function enabled(User $user): bool
    {
        return DB::table('user_two_factor_settings')->where('user_id', $user->id)->whereNotNull('confirmed_at')->exists();
    }

    public function status(User $user): array
    {
        $row = DB::table('user_two_factor_settings')->where('user_id', $user->id)->first();

        return ['required' => (bool) User::whereKey($user->id)->value('two_factor_required'), 'enabled' => (bool) $row?->confirmed_at, 'confirmed_at' => $row?->confirmed_at,
            'recovery_codes_remaining' => count(json_decode($row?->recovery_hashes ?? '[]', true)),
            'method' => $row?->confirmed_at ? $row->method : null,
            'email_available' => $this->email->available(),
            'email_hint' => $row?->email_address ? $this->maskEmail($row->email_address) : null,
            'sms_available' => false];
    }

    public function audit(int $userId, string $event, Request $request): void
    {
        DB::table('two_factor_security_events')->insert(['user_id' => $userId, 'event' => $event,
            'ip_address' => $request->ip(), 'created_at' => now()]);
    }

    public function fingerprint(User $user): string
    {
        return hash_hmac('sha256', $user->getAuthPassword().'|'.$user->email.'|'.$user->password_changed_at?->toISOString().'|'.(int) $user->two_factor_required, config('app.key'));
    }

    public function begin(User $user, Request $request, string $channel): ?string
    {
        return DB::transaction(function () use ($user, $request, $channel) {
            $user = $this->lockAccount($user, $request);
            DB::table('user_two_factor_settings')->insertOrIgnore(['user_id' => $user->id, 'version' => Str::random(64)]);
            $row = DB::table('user_two_factor_settings')->where('user_id', $user->id)->lockForUpdate()->first();
            if (! $row?->confirmed_at && ! $user->two_factor_required) {
                return null;
            }
            DB::table('two_factor_login_challenges')->where('user_id', $user->id)->where('expires_at', '<=', now())->delete();
            // Only the latest password-verified challenge per channel remains usable.
            DB::table('two_factor_login_challenges')->where('user_id', $user->id)->where('channel', $channel)->delete();
            $token = bin2hex(random_bytes(32));
            DB::table('two_factor_login_challenges')->insert(['id' => hash('sha256', $token),
                'user_id' => $user->id, 'credential_fingerprint' => $this->fingerprint($user),
                'version' => $row->version, 'channel' => $channel, 'purpose' => $row->confirmed_at ? 'login' : 'enrollment', 'remember' => $request->boolean('remember'),
                'expires_at' => now()->addMinutes(5)]);

            return $token;
        });
    }

    public function verifyLogin(string $token, string $code, string $channel, Request $request): ?array
    {
        $id = hash('sha256', $token);
        $pending = DB::table('two_factor_login_challenges')->where('id', $id)->first();
        if (! $pending) {
            return null;
        }
        $this->throttle($pending->user_id);

        return DB::transaction(function () use ($id, $pending, $code, $channel, $request) {
            $user = User::whereKey($pending->user_id)->lockForUpdate()->first();
            if (!$user || !$user->isActive() || $user->isSuspended()) return null;
            $row = DB::table('user_two_factor_settings')->where('user_id', $pending->user_id)->lockForUpdate()->first();
            $challenge = DB::table('two_factor_login_challenges')->where('id', $id)->lockForUpdate()->first();
            $enrolling = $challenge?->purpose === 'enrollment';
            if (! $challenge || ! $row || ($enrolling ? (!$user->two_factor_required || $row->confirmed_at) : !$row->confirmed_at) || ! $user || ! $user->isActive() || $user->isSuspended()
                || $challenge->channel !== $channel || $challenge->attempts >= 5
                || now()->gte($challenge->expires_at) || ! hash_equals($row->version, $challenge->version)
                || ! hash_equals($this->fingerprint($user), $challenge->credential_fingerprint)) {
                return null;
            }
            DB::table('two_factor_login_challenges')->where('id', $id)->increment('attempts');
            $codes = null;
            if ($enrolling) {
                try {
                    $settings = $this->change($user, 'confirm', $code, $request, false);
                    $codes = $settings['recovery_codes'];
                } catch (ValidationException) {
                    return null; // Commit failed attempts and audit records.
                }
            } elseif (! $this->consume($row, $code, $request, 'login', $id, $user)) {
                $this->audit($user->id, 'challenge_failed', $request);

                return null;
            }
            DB::table('two_factor_login_challenges')->where('user_id', $user->id)->delete();
            $this->audit($user->id, 'challenge_verified', $request);

            return ['user' => $user, 'remember' => (bool) $challenge->remember] + ($codes !== null ? ['recovery_codes' => $codes] : []);
        });
    }

    public function reauthenticate(Request $request): void
    {
        abort_if($request->hasSession() && $request->session()->has('impersonated_by'), 403,
            'Stop impersonating before managing two-factor authentication.');
        $user = User::findOrFail($request->user()->id);
        $key = 'two-factor-password:'.$user->id;
        abort_if(RateLimiter::tooManyAttempts($key, 5), 429, 'Too many attempts. Try again in five minutes.');
        if (! Hash::check((string) $request->input('current_password'), $user->getAuthPassword())) {
            RateLimiter::hit($key, 300);
            throw ValidationException::withMessages(['current_password' => 'Your current password is incorrect.']);
        }
        $request->attributes->set('two_factor_reauthenticated', $this->fingerprint($user));
        RateLimiter::clear($key); // OTP guessing has its own independent account limiter.
    }

    public function setup(User $user, Request $request, string $method = 'authenticator', bool $replace = false): array
    {
        return DB::transaction(function () use ($user, $request, $method, $replace) {
            $user = $this->lockAccount($user, $request);
            DB::table('user_two_factor_settings')->insertOrIgnore(['user_id' => $user->id, 'version' => Str::random(64)]);
            $row = DB::table('user_two_factor_settings')->where('user_id', $user->id)->lockForUpdate()->first();
            abort_if($row->confirmed_at && !$replace, 409, 'Two-factor authentication is already enabled.');
            abort_if($replace && !$row->confirmed_at, 422, 'Set up two-step authentication before switching methods.');
            if ($method === 'email') {
                $version = Str::random(64);
                DB::table('user_two_factor_settings')->where('user_id', $user->id)->update([
                    'pending_method' => 'email', 'pending_email' => $user->email, 'pending_secret' => null,
                    'pending_expires_at' => now()->addMinutes(10), 'version' => $version,
                ]);
                $this->email->send($user->id, $user->email, 'confirm', $version);
                $this->audit($user->id, 'email_enrollment_started', $request);

                return ['method' => 'email', 'email_hint' => $this->maskEmail($user->email), 'expires_in' => 300];
            }
            $secret = $this->totp->secret();
            DB::table('user_two_factor_settings')->where('user_id', $user->id)->update([
                'pending_secret' => Crypt::encryptString($secret), 'pending_expires_at' => now()->addMinutes(10),
                'pending_method' => 'authenticator', 'pending_email' => null]);
            $this->audit($user->id, 'enrollment_started', $request);
            $issuer = (string) config('app.name', 'Metis');

            return ['method' => 'authenticator', 'secret' => $secret, 'otpauth_uri' => 'otpauth://totp/'.rawurlencode($issuer.':'.$user->email)
                .'?secret='.$secret.'&issuer='.rawurlencode($issuer).'&algorithm=SHA1&digits=6&period=30',
                'expires_in' => 600];
        });
    }

    public function change(User $user, string $action, string $code, Request $request, bool $countAttempt = true): array
    {
        if ($countAttempt) $this->throttle($user->id);
        $result = DB::transaction(function () use ($user, $action, $code, $request) {
            $user = $this->lockAccount($user, $request);
            abort_if($action === 'disable' && $user->two_factor_required, 422, 'Two-step login is required by your administrator. You can switch methods without turning protection off.');
            $row = DB::table('user_two_factor_settings')->where('user_id', $user->id)->lockForUpdate()->first();
            if ($action === 'confirm') {
                if (! $row || !$row->pending_method || !$row->pending_expires_at || now()->gte($row->pending_expires_at)) {
                    return null;
                }
                $emailSetup = $row->pending_method === 'email';
                $step = -1;
                if ($emailSetup) {
                    $valid = $row->pending_email === $user->email && $this->email->consume($user->id, $user->email, 'confirm', $row->version, $code);
                } else {
                    $step = $row->pending_secret ? $this->totp->matchingStep(Crypt::decryptString($row->pending_secret), $code) : null;
                    $valid = $step !== null;
                }
                if (! $valid) {
                    $this->audit($user->id, 'enrollment_failed', $request);

                    return null;
                }
                $codes = $this->newRecoveryCodes();
                DB::table('user_two_factor_settings')->where('user_id', $user->id)->update([
                    'secret' => $emailSetup ? null : $row->pending_secret, 'confirmed_at' => now(), 'pending_secret' => null,
                    'method' => $emailSetup ? 'email' : 'authenticator', 'email_address' => $emailSetup ? $row->pending_email : null,
                    'pending_method' => null, 'pending_email' => null,
                    'pending_expires_at' => null, 'last_used_step' => $step, 'recovery_hashes' => $this->hashCodes($codes),
                    'version' => Str::random(64)]);
                $event = 'enabled';
            } else {
                if (! $row?->confirmed_at || ! $this->consume($row, $code, $request, $action, $row->version, $user)) {
                    $this->audit($user->id, 'settings_verification_failed', $request);

                    return null;
                }
                $codes = $action === 'recovery' ? $this->newRecoveryCodes() : [];
                $updates = ['version' => Str::random(64), 'recovery_hashes' => $this->hashCodes($codes)];
                if ($action === 'disable') {
                    $updates += ['secret' => null, 'confirmed_at' => null, 'pending_secret' => null, 'pending_expires_at' => null, 'last_used_step' => -1, 'email_address' => null, 'pending_method' => null, 'pending_email' => null];
                }
                DB::table('user_two_factor_settings')->where('user_id', $user->id)->update($updates);
                $event = $action === 'disable' ? 'disabled' : 'recovery_codes_regenerated';
            }
            DB::table('two_factor_login_challenges')->where('user_id', $user->id)->delete();
            DB::table('two_factor_email_codes')->where('user_id', $user->id)->delete();
            $this->revokeOtherCredentials($user, $request);
            $this->audit($user->id, $event, $request);

            return ['recovery_codes' => $codes, 'message' => 'Two-factor settings updated.'];
        });
        if ($result === null) {
            throw ValidationException::withMessages(['code' => 'Invalid, expired or already used code. Use a fresh verification code or an unused recovery code.']);
        }

        return $result + $this->status($user);
    }

    public function authorizeSensitiveChange(User $user, string $code, Request $request, string $purpose): void
    {
        $valid = DB::transaction(function () use ($user, $code, $request, $purpose) {
            $user = $this->lockAccount($user, $request);
            $row = DB::table('user_two_factor_settings')->where('user_id', $user->id)->lockForUpdate()->first();
            if (! $row?->confirmed_at) {
                return true;
            }
            $this->throttle($user->id);
            if ($this->consume($row, trim($code), $request, $purpose, $row->version, $user)) {
                return true;
            }
            $this->audit($user->id, $purpose.'_verification_failed', $request);

            return false;
        });
        if (! $valid) {
            throw ValidationException::withMessages(['code' => 'Enter a fresh verification code or unused recovery code to authorize this security change.']);
        }
    }

    private function throttle(int $userId): void
    {
        $key = 'two-factor-code:'.$userId;
        abort_if(RateLimiter::tooManyAttempts($key, 5), 429, 'Too many verification attempts. Try again in five minutes.',
            ['Retry-After' => (string) max(1, RateLimiter::availableIn($key))]);
        RateLimiter::hit($key, 300);
    }

    private function consume(object $row, string $code, Request $request, string $purpose, string $context, User $user): bool
    {
        if ($row->method === 'email' && $row->email_address === $user->email
            && $this->email->consume($user->id, $row->email_address, $purpose, $context, $code)) {
            return true;
        }
        $step = $row->method === 'authenticator' && $row->secret
            ? $this->totp->matchingStep(Crypt::decryptString($row->secret), $code, $row->last_used_step) : null;
        if ($step !== null) {
            DB::table('user_two_factor_settings')->where('user_id', $row->user_id)->update(['last_used_step' => $step]);

            return true;
        }
        $hashes = json_decode($row->recovery_hashes ?? '[]', true);
        $normalized = strtolower(str_replace(['-', ' '], '', trim($code)));
        if (! preg_match('/^[a-f0-9]{20}$/D', $normalized)) {
            return false;
        }
        foreach ($hashes as $index => $hash) {
            if (hash_equals($hash, hash('sha256', $normalized))) {
                unset($hashes[$index]);
                DB::table('user_two_factor_settings')->where('user_id', $row->user_id)->update(['recovery_hashes' => json_encode(array_values($hashes))]);
                $this->audit($row->user_id, 'recovery_code_used', $request);

                return true;
            }
        }

        return false;
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2);

        return mb_substr($local, 0, 1).'***@'.$domain;
    }

    private function pending(string $token, string $channel): ?object
    {
        $challenge = DB::table('two_factor_login_challenges')->where('id', hash('sha256', $token))->first();
        if (! $challenge || $challenge->channel !== $channel || $challenge->attempts >= 5 || now()->gte($challenge->expires_at)) {
            return null;
        }
        $user = User::find($challenge->user_id);
        $row = DB::table('user_two_factor_settings')->where('user_id', $challenge->user_id)->first();
        if (! $user || ! $user->isActive() || $user->isSuspended() || ! $row
            || ($challenge->purpose === 'enrollment' ? (!$user->two_factor_required || $row->confirmed_at) : !$row->confirmed_at)
            || ! hash_equals($row->version, $challenge->version) || ! hash_equals($this->fingerprint($user), $challenge->credential_fingerprint)) {
            return null;
        }

        return $challenge;
    }

    public function challengeInfo(string $token, string $channel): array
    {
        $challenge = $this->pending($token, $channel);
        abort_unless($challenge, 422, 'This sign-in request has expired. Sign in again.');
        $row = DB::table('user_two_factor_settings')->where('user_id', $challenge->user_id)->first();

        if ($challenge->purpose === 'enrollment') {
            $user = User::findOrFail($challenge->user_id);
            $started = $row->pending_method && $row->pending_expires_at && now()->lt($row->pending_expires_at);
            $secret = $started && $row->pending_method === 'authenticator' && $row->pending_secret ? Crypt::decryptString($row->pending_secret) : null;
            return ['enrollment_required' => true, 'enrollment_started' => (bool)$started,
                'method' => $started ? $row->pending_method : null, 'email_available' => $this->email->available(),
                'email_hint' => $this->maskEmail($user->email), 'enrollment_secret' => $secret,
                'enrollment_uri' => $secret ? $this->enrollmentUri($user, $secret) : null];
        }

        return ['enrollment_required' => false, 'method' => $row->method, 'email_hint' => $row->method === 'email' ? $this->maskEmail($row->email_address) : null];
    }

    public function sendLoginEmail(string $token, string $channel, Request $request): int
    {
        $challenge = $this->pending($token, $channel);
        abort_unless($challenge, 422, 'This sign-in request has expired. Sign in again.');

        return DB::transaction(function () use ($challenge, $token, $channel, $request) {
            $user = $this->lockAccount(User::findOrFail($challenge->user_id), $request);
            $row = DB::table('user_two_factor_settings')->where('user_id', $challenge->user_id)->lockForUpdate()->first();
            $current = $this->pending($token, $channel);
            if ($current?->purpose === 'enrollment') {
                abort_unless($row->pending_method === 'email' && $row->pending_email === $user->email && $row->pending_expires_at && now()->lt($row->pending_expires_at), 422, 'Choose email enrollment first.');
                $this->email->send($user->id, $user->email, 'confirm', $row->version);
                return max(0, min(300, (int) now()->diffInSeconds($current->expires_at, false)));
            }
            abort_unless($current && $row->method === 'email', 422, 'Email verification is not available for this request.');
            abort_unless($row->email_address === $user->email, 422, 'The account email changed. Use a recovery code, then set up email verification again.');
            $this->email->send($user->id, $row->email_address, 'login', $current->id);
            $this->audit($user->id, 'email_login_code_sent', $request);

            return max(0, min(300, (int) now()->diffInSeconds($current->expires_at, false)));
        });
    }

    public function sendSettingsEmail(User $user, string $action, Request $request): void
    {
        DB::transaction(function () use ($user, $action, $request) {
            $user = $this->lockAccount($user, $request);
            $row = DB::table('user_two_factor_settings')->where('user_id', $user->id)->lockForUpdate()->first();
            $enrolling = $action === 'confirm';
            abort_unless($row && ($enrolling
                ? ($row->pending_method === 'email' && now()->lt($row->pending_expires_at) && $row->pending_email === $user->email)
                : ($row->confirmed_at && $row->method === 'email' && $row->email_address === $user->email)), 422, 'Email verification is not available for this request.');
            $this->email->send($user->id, $user->email, $action, $row->version);
            $this->audit($user->id, 'email_settings_code_sent', $request);
        });
    }

    private function lockAccount(User $user, Request $request): User
    {
        $fresh = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
        if ($token = $user->currentAccessToken()) $fresh->withAccessToken($token);
        abort_unless($fresh->isActive() && !$fresh->isSuspended(), 403, 'This account is not available.');
        $proof = $request->attributes->get('two_factor_reauthenticated');
        abort_if($proof && !hash_equals($proof, $this->fingerprint($fresh)), 422, 'Account security changed. Confirm your current password again.');
        return $fresh;
    }

    private function enrollmentUri(User $user, string $secret): string
    {
        $issuer = (string) config('app.name', 'Metis');
        return 'otpauth://totp/'.rawurlencode($issuer.':'.$user->email).'?secret='.$secret.'&issuer='.rawurlencode($issuer).'&algorithm=SHA1&digits=6&period=30';
    }

    public function setupForLogin(string $token, string $channel, string $method, Request $request): array
    {
        $challenge = $this->pending($token, $channel);
        abort_unless($challenge && $challenge->purpose === 'enrollment', 422, 'Sign in again to enroll two-step authentication.');
        return DB::transaction(function () use ($challenge, $token, $channel, $method, $request) {
            $user = $this->lockAccount(User::findOrFail($challenge->user_id), $request);
            DB::table('user_two_factor_settings')->where('user_id', $user->id)->lockForUpdate()->first();
            $current = $this->pending($token, $channel);
            abort_unless($current && $current->purpose === 'enrollment', 422, 'Sign in again to enroll two-step authentication.');
            $data = $this->setup($user, $request, $method);
            DB::table('two_factor_login_challenges')->where('id', $current->id)->update([
                'version' => DB::table('user_two_factor_settings')->where('user_id', $user->id)->value('version'),
            ]);
            return array_merge($data, ['enrollment_required' => true, 'expires_in' => max(0, min(300, (int) now()->diffInSeconds($current->expires_at, false)))]);
        });
    }

    private function newRecoveryCodes(): array
    {
        return array_map(fn () => implode('-', str_split(bin2hex(random_bytes(10)), 5)), range(1, 10));
    }

    private function hashCodes(array $codes): string
    {
        return json_encode(array_map(fn ($code) => hash('sha256', str_replace('-', '', $code)), $codes));
    }

    private function revokeOtherCredentials(User $user, Request $request): void
    {
        $current = $user->currentAccessToken();
        $user->tokens()->when($current instanceof PersonalAccessToken,
            fn ($query) => $query->where('id', '!=', $current->id))->delete();
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)
                ->when($request->hasSession(), fn ($query) => $query->where('id', '!=', $request->session()->getId()))->delete();
        }
        $user->setRememberToken(Str::random(60));
        $user->saveQuietly();
    }
}
