<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Modules\Users\Models\LoginHistory;
use App\Modules\Users\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Event listener that records every authentication attempt into the
 * login_histories table for security auditing.
 *
 * Subscribed events:
 *   - Illuminate\Auth\Events\Login  → status=success
 *   - Illuminate\Auth\Events\Failed → status=failed
 */
class LogAuthenticationAttempts
{
    public function __construct(
        private readonly Request $request,
    ) {}

    // ─── Event Handlers ───────────────────────────────────────────────────────

    /**
     * Handle a successful login event.
     */
    public function handleLogin(Login $event): void
    {
        /** @var User $user */
        $user = $event->user;

        LoginHistory::create([
            'user_id'        => $user->id,
            'email_attempted' => $user->email,
            'ip_address'     => $this->request->ip() ?? '0.0.0.0',
            'user_agent'     => $this->request->userAgent(),
            'device_type'    => $this->resolveDeviceType(),
            'status'         => 'success',
            'failure_reason' => null,
            'attempted_at'   => Carbon::now(),
        ]);
    }

    /**
     * Handle a failed login event.
     *
     * The credentials array always contains 'email'; we use it even if the
     * user model is null (attempt against a non-existent account).
     */
    public function handleFailed(Failed $event): void
    {
        $email = (string) ($event->credentials['email'] ?? 'unknown');

        /** @var User|null $user */
        $user = $event->user instanceof User ? $event->user : null;

        LoginHistory::create([
            'user_id'        => $user?->id,
            'email_attempted' => $email,
            'ip_address'     => $this->request->ip() ?? '0.0.0.0',
            'user_agent'     => $this->request->userAgent(),
            'device_type'    => $this->resolveDeviceType(),
            'status'         => 'failed',
            'failure_reason' => $this->resolveFailureReason($user),
            'attempted_at'   => Carbon::now(),
        ]);
    }

    // ─── Subscription ─────────────────────────────────────────────────────────

    /**
     * Register the listeners for the subscriber.
     *
     * @return array<string, string>
     */
    public function subscribe(): array
    {
        return [
            Login::class  => 'handleLogin',
            Failed::class => 'handleFailed',
        ];
    }

    // ─── Private Helpers ──────────────────────────────────────────────────────

    /**
     * Determine whether the request originates from a mobile client.
     *
     * Detection strategy (in priority order):
     *  1. X-App-Source header equals 'mobile'
     *  2. Request path starts with /api/
     */
    private function resolveDeviceType(): string
    {
        if (strtolower((string) $this->request->header('X-App-Source', '')) === 'mobile') {
            return 'mobile';
        }

        if (str_starts_with($this->request->path(), 'api/')) {
            return 'mobile';
        }

        return 'web';
    }

    /**
     * Infer the human-readable failure reason from the user's state.
     *
     * Note: 'throttled' is written directly by AuthController before the
     * Failed event is fired, so it won't appear here. This covers the
     * remaining cases.
     */
    private function resolveFailureReason(?User $user): string
    {
        if ($user === null) {
            return 'invalid_credentials';
        }

        if ($user->isSuspended()) {
            return 'account_suspended';
        }

        if (! $user->isActive()) {
            return 'account_inactive';
        }

        return 'invalid_credentials';
    }
}
