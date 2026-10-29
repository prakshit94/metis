<?php

namespace Storefront\Services;

use App\Modules\Customers\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Storefront\Models\CustomerAccount;
use Storefront\Notifications\CustomerLoginCode;

class CustomerEmailLoginCodes
{
    public const SESSION_KEY = 'shop.email_login_challenge';

    public const MAX_ATTEMPTS = 5;

    public const LIFETIME = 300;

    private function key(string $id): string
    {
        return 'shop-email-login:challenge:'.$id;
    }

    private function fingerprint(CustomerAccount $account): string
    {
        return hash('sha256', implode('|', [$account->customer_id, $account->email, $account->getAuthPassword(), $account->email_verified_at?->getTimestamp()]));
    }

    private function invalid(): never
    {
        throw ValidationException::withMessages(['code' => 'This sign-in attempt has expired or is no longer valid. Sign in again.']);
    }

    public function clear(Request $request): void
    {
        $id = $request->session()->pull(self::SESSION_KEY);
        if (is_string($id)) {
            Cache::forget($this->key($id));
        }
    }

    public function pending(Request $request): array
    {
        $id = $request->session()->get(self::SESSION_KEY);
        $challenge = is_string($id) ? Cache::get($this->key($id)) : null;
        if (! is_array($challenge) || $challenge['expires_at'] <= now()->getTimestamp() || $challenge['attempts'] >= self::MAX_ATTEMPTS) {
            $this->clear($request);
            $this->invalid();
        }

        return $challenge;
    }

    private function persist(string $id, array $challenge): void
    {
        Cache::put($this->key($id), $challenge, max(1, $challenge['expires_at'] - now()->getTimestamp()));
    }

    private function send(Request $request, string $email, ?CustomerAccount $account, string $code): void
    {
        // Account-wide limit also covers resends and repeated new challenges.
        $key = 'shop-email-login:send:'.hash('sha256', $email);
        if (RateLimiter::tooManyAttempts($key, 3)) {
            throw ValidationException::withMessages(['code' => 'Too many code requests. Wait five minutes before requesting another code.']);
        }
        RateLimiter::hit($key, self::LIFETIME);
        if (! $account) {
            return; // Match the public response without revealing account eligibility.
        }
        try {
            $account->notify(new CustomerLoginCode($code));
        } catch (\Throwable $error) {
            $this->clear($request);
            report($error);
            throw ValidationException::withMessages(['email' => 'Unable to send your sign-in code. Try again later or contact support.']);
        }
    }

    public function start(Request $request, string $email, ?CustomerAccount $account, string $destination): void
    {
        Cache::lock('shop-email-login:send-lock:'.hash('sha256', $email), 10)->block(5, function () use ($request, $email, $account, $destination) {
            $this->clear($request);
            $id = Str::random(64);
            $code = (string) random_int(100000, 999999);
            $now = now()->getTimestamp();
            $challenge = ['account' => $account?->id, 'customer' => $account?->customer_id, 'email' => $email, 'fingerprint' => $account ? $this->fingerprint($account) : null, 'hash' => Hash::make($code),
                'attempts' => 0, 'expires_at' => $now + self::LIFETIME, 'resend_at' => $now + 60, 'destination' => $destination];
            $request->session()->regenerate();
            $request->session()->put(self::SESSION_KEY, $id);
            $this->persist($id, $challenge);
            try {
                $this->send($request, $email, $account, $code);
            } catch (\Throwable $error) {
                $this->clear($request);
                throw $error;
            }
        });
    }

    public function details(Request $request): array
    {
        $challenge = $this->pending($request);
        [$local, $domain] = explode('@', $challenge['email'], 2);

        return ['email' => mb_substr($local, 0, 1).'***@'.$domain, 'resendIn' => max(0, $challenge['resend_at'] - now()->getTimestamp())];
    }

    public function verify(Request $request, string $code): string
    {
        $id = $request->session()->get(self::SESSION_KEY);
        if (! is_string($id)) {
            $this->invalid();
        }

        return Cache::lock('shop-email-login:verify-lock:'.$id, 10)->block(5, function () use ($request, $code, $id) {
            $challenge = $this->pending($request);
            if (! Hash::check($code, $challenge['hash'])) {
                $challenge['attempts']++;
                if ($challenge['attempts'] >= self::MAX_ATTEMPTS) {
                    $this->clear($request);
                } else {
                    $this->persist($id, $challenge); // Never extend expiry after a wrong guess.
                }
                throw ValidationException::withMessages(['code' => $challenge['attempts'] >= self::MAX_ATTEMPTS ? 'Too many incorrect codes. Sign in again.' : 'The code is incorrect. Please try again.']);
            }

            return DB::transaction(function () use ($request, $challenge) {
                // Match the customer/account lock order used by checkout and CRM.
                $customer = Customer::whereKey($challenge['customer'])->lockForUpdate()->first();
                $account = CustomerAccount::storefrontEligible()->whereKey($challenge['account'])->lockForUpdate()->first();
                if (! $customer || ! $customer->is_active || $customer->is_blacklisted || ! $account || ! is_string($challenge['fingerprint']) || ! hash_equals($challenge['fingerprint'], $this->fingerprint($account))) {
                    $this->clear($request);
                    $this->invalid();
                }
                $this->clear($request); // Consume before establishing a customer session.
                Auth::guard('customer')->login($account);
                $request->session()->regenerate();
                $request->session()->forget('shop.login_return_to');

                return $challenge['destination'];
            });
        });
    }

    public function resend(Request $request): void
    {
        $id = $request->session()->get(self::SESSION_KEY);
        if (! is_string($id)) {
            $this->invalid();
        }
        Cache::lock('shop-email-login:verify-lock:'.$id, 10)->block(5, function () use ($request, $id) {
            $challenge = $this->pending($request);
            if ($challenge['resend_at'] > now()->getTimestamp()) {
                throw ValidationException::withMessages(['code' => 'Wait one minute between code requests.']);
            }
            $account = CustomerAccount::storefrontEligible()->whereKey($challenge['account'])->first();
            if ($account && (! is_string($challenge['fingerprint']) || ! hash_equals($challenge['fingerprint'], $this->fingerprint($account)))) {
                $account = null;
            }
            $email = $challenge['email'];
            Cache::lock('shop-email-login:send-lock:'.hash('sha256', $email), 10)->block(5, function () use ($request, $email, $account, $id, $challenge) {
                $code = (string) random_int(100000, 999999);
                $this->send($request, $email, $account, $code);
                $challenge['hash'] = Hash::make($code);
                $challenge['resend_at'] = now()->getTimestamp() + 60;
                $this->persist($id, $challenge); // Preserve attempts and original absolute expiry.
            });
        });
    }
}
