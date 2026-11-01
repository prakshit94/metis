<?php

declare(strict_types=1);

namespace App\Modules\Users\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Modules\Core\Controllers\Controller;
use App\Modules\Users\Models\Attendance;
use App\Modules\Users\Models\LoginHistory;
use App\Modules\Users\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Handles all authentication flows for both Web (session) and Mobile (Sanctum
 * token) contexts within a single, unified controller.
 */
class AuthController extends Controller
{
    // ─── Constants ────────────────────────────────────────────────────────────

    private const MAX_ATTEMPTS = 5;

    private const LOCKOUT_WINDOW_MIN = 5;

    private const SUSPENSION_MIN = 15;

    private const TOKEN_EXPIRY_DAYS = 30;

    private const GENERIC_ERROR = 'Invalid email or password.';

    /** Current identity and effective grants for native navigation after token restoration. */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => $user,
            'roles' => $user->getRoleNames()->values(),
            'permissions' => $user->getAllPermissions()->pluck('name')->values(),
            'team_id' => getPermissionsTeamId(),
        ]);
    }

    // ─── Login ────────────────────────────────────────────────────────────────

    /**
     * Authenticate a user for either web (session) or mobile (token) context.
     *
     * Security measures applied:
     *  1. Rate-limiting: 5 failed attempts per email+IP in 5 minutes
     *  2. Account suspension: triggered on rate-limit breach (15 minutes)
     *  3. User enumeration prevention: generic error for ALL failure types
     *  4. Dual-context response: JSON token for mobile, redirect for web
     *
     * @throws ValidationException
     */
    public function login(LoginRequest $request): JsonResponse|RedirectResponse
    {
        $email = $request->validated('email');
        $ip = $request->ip() ?? '0.0.0.0';

        $emailKey = 'login_email:'.Str::lower($email);
        $ipKey = 'login_ip:'.$ip;

        // ── 1. Check rate limit BEFORE attempting auth ──────────────────────
        $emailTooManyAttempts = RateLimiter::tooManyAttempts($emailKey, self::MAX_ATTEMPTS);
        $ipTooManyAttempts = RateLimiter::tooManyAttempts($ipKey, self::MAX_ATTEMPTS);
        // An account-specific threshold blocks this account before checking
        // credentials. An IP threshold is enforced against failed guesses
        // below, so a valid user behind a shared IP can still sign in.
        if ($emailTooManyAttempts) {
            // Only an account-specific threshold should suspend an account.
            // An IP-only threshold may be shared by multiple people and must
            // not lock whichever account name happens to be submitted next.
            $target = User::where('email', $email)->first();
            if ($emailTooManyAttempts && $target !== null && ! $target->isSuspended()) {
                $target->suspend(self::SUSPENSION_MIN);
            }

            $this->recordThrottledAttempt($request, $email, $ip, $target);

            return $this->throttledFailure($request, $emailKey, $ipKey, $target);
        }

        // ── 2. Attempt authentication ────────────────────────────────────────
        $user = User::where('email', $email)->first();

        // Validate credentials — use Hash::check to avoid timing side-channels
        $isValid = false;
        if ($user !== null) {
            try {
                $isValid = Hash::check($request->validated('password'), $user->password);
            } catch (\RuntimeException $e) {
                // Ignore exception for legacy or invalid hashes in the DB
                $isValid = false;
            }
        } else {
            // Dummy hash to prevent timing-based user enumeration attacks
            // Must be a valid 60-character bcrypt string or password_get_info will return 'unknown'
            $dummyHash = in_array(config('hashing.driver'), ['argon2id', 'argon2i'])
                ? '$argon2id$v=19$m=65536,t=4,p=1$Z29uOU56Slc0SWRJbFAvYg$ILca1T1tS+yPIT6WNfrXcA6t0S0XqX9wngZa+PXrZj4'
                : '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

            try {
                Hash::check($request->validated('password'), $dummyHash);
            } catch (\RuntimeException $e) {
                // Ignore exception if the dummy hash is still somehow considered invalid
            }
        }

        if ($user === null || ! $isValid) {
            if ($ipTooManyAttempts) {
                $this->recordThrottledAttempt($request, $email, $ip, $user);

                return $this->throttledFailure($request, $emailKey, $ipKey);
            }

            RateLimiter::hit($emailKey, self::LOCKOUT_WINDOW_MIN * 60);
            RateLimiter::hit($ipKey, self::LOCKOUT_WINDOW_MIN * 60);

            // Fire the native Failed event so our listener captures it
            Event::dispatch(new Failed('web', $user, ['email' => $email]));

            return $this->credentialFailureWithAttempts($request, $emailKey, $ipKey);
        }

        // ── 3. Guard: suspended or inactive accounts ─────────────────────────
        if ($user->isSuspended()) {
            RateLimiter::hit($emailKey, self::LOCKOUT_WINDOW_MIN * 60);
            RateLimiter::hit($ipKey, self::LOCKOUT_WINDOW_MIN * 60);

            Event::dispatch(new Failed('web', $user, ['email' => $email]));

            $message = $this->retryNotice($emailKey, $ipKey, $user);

            return $this->suspensionFailure($request, $message);
        }

        if (! $user->isActive()) {
            RateLimiter::hit($emailKey, self::LOCKOUT_WINDOW_MIN * 60);
            RateLimiter::hit($ipKey, self::LOCKOUT_WINDOW_MIN * 60);

            Event::dispatch(new Failed('web', $user, ['email' => $email]));

            return $this->genericFailure($request);
        }

        // Clear rate limiter on successful authentication
        RateLimiter::clear($emailKey);
        RateLimiter::clear($ipKey);

        // Hold the settings lock through issuance so concurrent enrollment cannot
        // turn a password-only sign-in into a second-factor bypass.
        return DB::transaction(function () use ($request, $user) {
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if (!Hash::check($request->validated('password'), $user->password) || !$user->isActive() || $user->isSuspended()) {
                return $this->genericFailure($request);
            }
            $twoFactor = app(TwoFactorService::class);
            $mobile = $this->isMobileRequest($request);
            $challenge = $twoFactor->begin($user, $request, $mobile ? 'mobile' : 'web');
            if ($challenge !== null) {
                $details = $twoFactor->challengeInfo($challenge, $mobile ? 'mobile' : 'web');
                $deliveryError = null;
                if (!$details['enrollment_required'] && $details['method'] === 'email') {
                    try {
                        $twoFactor->sendLoginEmail($challenge, $mobile ? 'mobile' : 'web', $request);
                    } catch (HttpException $error) {
                        $deliveryError = $error->getMessage(); // Keep recovery-code sign-in available during delivery outages.
                    }
                }
                if ($mobile) {
                    return response()->json(['two_factor_required' => true, 'challenge_token' => $challenge,
                        'expires_in' => 300, 'enrollment_required' => $details['enrollment_required'],
                        'email_available' => $details['email_available'] ?? null,
                        'message' => $details['enrollment_required'] ? 'Your administrator requires two-step login. Enroll and verify your own method to continue.' : 'Enter a verification or recovery code.',
                        'method' => $details['method'], 'email_hint' => $details['email_hint'], 'email_delivery_error' => $deliveryError], 202)
                        ->header('Cache-Control', 'no-store');
                }
                $request->session()->regenerate();
                $request->session()->put('two_factor_challenge', $challenge);

                return redirect()->route('login.two-factor')->with('error', $deliveryError);
            }

            // ── 4. Issue credentials ─────────────────────────────────────────────
            if ($this->isMobileRequest($request)) {
                return $this->issueMobileToken($request, $user);
            }

            return $this->issueWebSession($request, $user);
        });
    }

    public function twoFactorForm(Request $request)
    {
        if (! $request->session()->has('two_factor_challenge')) {
            return redirect()->route('login');
        }

        try {
            $details = app(TwoFactorService::class)->challengeInfo($request->session()->get('two_factor_challenge'), 'web');
            return response()->view($details['enrollment_required'] ? 'auth.two-factor-enroll' : 'auth.two-factor-challenge', $details)->header('Cache-Control', 'no-store');
        } catch (HttpException) {
            $request->session()->forget('two_factor_challenge');
            return redirect()->route('login')->with('error', 'Your verification request expired or account security changed. Sign in again.');
        }
    }

    public function twoFactorEnroll(Request $request, TwoFactorService $twoFactor): JsonResponse|RedirectResponse
    {
        $mobile = $this->isMobileRequest($request);
        $request->validate(['method' => 'required|in:authenticator,email']);
        $token = $mobile ? $request->input('challenge_token') : $request->session()->get('two_factor_challenge');
        abort_unless(is_string($token) && strlen($token) === 64, 422, 'Sign in again to enroll two-step authentication.');
        try {
            $data = $twoFactor->setupForLogin($token, $mobile ? 'mobile' : 'web', $request->input('method'), $request);
            return $mobile ? response()->json($data)->header('Cache-Control', 'no-store')
                : redirect()->route('login.two-factor');
        } catch (HttpException $error) {
            if ($mobile) throw $error;
            return redirect()->route('login.two-factor')->with('error', $error->getMessage());
        }
    }

    public function twoFactorEmail(Request $request, TwoFactorService $twoFactor): JsonResponse|RedirectResponse
    {
        $mobile = $this->isMobileRequest($request);
        $token = $mobile ? $request->input('challenge_token') : $request->session()->get('two_factor_challenge');
        abort_unless(is_string($token) && strlen($token) === 64, 422, 'Sign in again to request a verification email.');
        if ($mobile) {
            $remaining = $twoFactor->sendLoginEmail($token, 'mobile', $request);

            return response()->json(['message' => 'Verification email sent.', 'expires_in' => $remaining])->header('Cache-Control', 'no-store');
        }
        try {
            $twoFactor->sendLoginEmail($token, 'web', $request);

            return redirect()->route('login.two-factor')->with('status', 'Verification email sent.');
        } catch (HttpException $error) {
            return redirect()->route('login.two-factor')->with('error', $error->getMessage());
        }
    }

    public function twoFactorChallenge(Request $request, TwoFactorService $twoFactor): JsonResponse|RedirectResponse|Response
    {
        // Keep factor consumption and credential issuance in the same transaction.
        return DB::transaction(function () use ($request, $twoFactor) {
            // Validate manually so Laravel never flashes an OTP or challenge bearer into old input.
            $mobile = $this->isMobileRequest($request);
            $token = $mobile ? $request->input('challenge_token') : $request->session()->get('two_factor_challenge');
            $code = $request->input('code');
            $result = null;
            if (is_string($token) && strlen($token) === 64 && is_string($code) && strlen($code) <= 64) {
                $result = $twoFactor->verifyLogin($token, trim($code), $mobile ? 'mobile' : 'web', $request);
            }
            if ($result === null) {
                $message = 'Invalid, expired or already used verification code. Try a fresh code, or sign in again.';

                return $mobile ? response()->json(['message' => $message], 422)
                    : redirect()->route('login.two-factor')->with('error', $message);
            }
            $request->merge(['remember' => $result['remember']]);
            if ($mobile) {
                $response = $this->issueMobileToken($request, $result['user']);
                if (isset($result['recovery_codes'])) $response->setData($response->getData(true) + ['recovery_codes' => $result['recovery_codes']]);
                return $response->header('Cache-Control', 'no-store');
            }
            $request->session()->forget('two_factor_challenge');

            $login = $this->issueWebSession($request, $result['user']);
            if (!isset($result['recovery_codes'])) return $login;
            $response = response()->view('auth.two-factor-recovery', ['codes' => $result['recovery_codes'], 'continue_url' => $login->getTargetUrl()])->header('Cache-Control', 'no-store');
            foreach ($login->headers->getCookies() as $cookie) $response->withCookie($cookie);
            return $response;
        });
    }

    private function recordThrottledAttempt(Request $request, string $email, string $ip, ?User $user): void
    {
        LoginHistory::create([
            'user_id' => $user?->id,
            'email_attempted' => $email,
            'ip_address' => $ip,
            'user_agent' => $request->userAgent(),
            'device_type' => $this->deviceType($request),
            'status' => 'failed',
            'failure_reason' => 'throttled',
            'attempted_at' => Carbon::now(),
        ]);
    }

    private function throttledFailure(
        Request $request,
        string $emailKey,
        string $ipKey,
        ?User $user = null
    ): JsonResponse|RedirectResponse {
        $message = $this->retryNotice($emailKey, $ipKey, $user).' '.$this->attemptSummary($emailKey, $ipKey);

        return $this->isMobileRequest($request)
            ? response()->json(['message' => $message], 429)
            : back()->with('error', $message)->withInput($request->except(['password', 'password_confirmation']));
    }

    private function credentialFailureWithAttempts(
        Request $request,
        string $emailKey,
        string $ipKey
    ): JsonResponse|RedirectResponse {
        $message = self::GENERIC_ERROR.' '.$this->attemptSummary($emailKey, $ipKey);

        return $this->isMobileRequest($request)
            ? response()->json(['message' => $message], 401)
            : back()->with('error', $message)->withInput($request->except(['password', 'password_confirmation']));
    }

    private function attemptSummary(string $emailKey, string $ipKey): string
    {
        // Either limiter can exhaust the budget for an unsuccessful sign-in.
        $used = min(self::MAX_ATTEMPTS, max(
            RateLimiter::attempts($emailKey),
            RateLimiter::attempts($ipKey),
        ));
        $remaining = self::MAX_ATTEMPTS - $used;

        return "Failed attempts: {$used}/".self::MAX_ATTEMPTS.". Remaining: {$remaining}.";
    }

    private function retryNotice(string $emailKey, string $ipKey, ?User $user = null): string
    {
        $wait = 0;
        foreach ([$emailKey, $ipKey] as $key) {
            if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
                $wait = max($wait, RateLimiter::availableIn($key));
            }
        }
        $retryAt = now()->addSeconds($wait);
        $format = fn ($date) => $date->copy()->timezone(config('app.timezone'))->format('M j, Y, g:i A');

        if ($user?->isSuspended()) {
            $until = $user->suspended_until;
            $message = 'Suspended until '.$format($until).'.';
            // A separately active rate limit can outlast an existing suspension.
            if ($retryAt->gt($until)) {
                $message .= ' Try again at '.$format($retryAt).'.';
            }

            return $message;
        }

        return 'Too many sign-in attempts. Try again at '.$format($retryAt).'.';
    }

    // ─── Logout ───────────────────────────────────────────────────────────────

    /**
     * Invalidate the current session or revoke the current API token.
     */
    public function logout(Request $request): JsonResponse|RedirectResponse
    {
        // Leaving a borrowed identity must not check out the real employee or
        // rotate their remember token on their other devices.
        if ($request->hasSession() && $request->session()->has('impersonated_by') && ! $request->bearerToken()) {
            Auth::guard('web')->logoutCurrentDevice();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $this->isMobileRequest($request)
                ? response()->json(['message' => 'Logged out successfully.'])
                : redirect()->route('login');
        }

        $apiUser = $request->user('sanctum');
        $user = $apiUser ?? Auth::guard('web')->user();
        if ($user) {
            $this->recordAttendanceCheckOut($user);
        }

        if ($this->isMobileRequest($request)) {
            $apiUser?->currentAccessToken()?->delete();

            return response()->json(['message' => 'Logged out successfully.']);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    // ─── Token Management ─────────────────────────────────────────────────────

    /**
     * Revoke all Sanctum tokens for the authenticated user EXCEPT the current
     * one. Useful when a user logs in on a new device and wants to clear stale
     * sessions.
     */
    public function revokeOtherTokens(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');
        $currentToken = $user->currentAccessToken();

        $tokens = $user->tokens();
        if ($currentToken !== null) {
            $tokens->where('id', '!=', $currentToken->id);
        }

        $revokedCount = $tokens->delete();

        return response()->json([
            'message' => 'Other tokens revoked successfully.',
            'revoked_count' => $revokedCount,
        ]);
    }

    // ─── Private Helpers ──────────────────────────────────────────────────────

    /**
     * Determine if the request is from a mobile/API client.
     */
    private function isMobileRequest(Request $request): bool
    {
        return strtolower((string) $request->header('X-App-Source', '')) === 'mobile'
            || str_starts_with($request->path(), 'api/');
    }

    /**
     * Resolve the device type label from the request.
     */
    private function deviceType(Request $request): string
    {
        return $this->isMobileRequest($request) ? 'mobile' : 'web';
    }

    /**
     * Issue a Sanctum token and return a JSON response.
     * The token is named after the device UA and expires in 30 days.
     */
    private function issueMobileToken(Request $request, User $user): JsonResponse
    {
        Event::dispatch(new Login('sanctum', $user, false)); // fires the Login event → LogAuthenticationAttempts

        // One account has one active login. This clears prior browser sessions,
        // API tokens, and remember-me cookies before issuing the new token.
        $this->invalidateExistingCredentials($user);

        $deviceName = $this->extractDeviceName($request);
        $expiresAt = Carbon::now()->addDays(self::TOKEN_EXPIRY_DAYS);

        $token = $user->createToken(
            name: $deviceName,
            abilities: ['*'],
            expiresAt: $expiresAt,
        );

        $this->recordAttendanceCheckIn($user);

        return response()->json([
            'message' => 'Login successful.',
            'token' => $token->plainTextToken,
            'expires_at' => $expiresAt->toIso8601String(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
            ],
        ]);
    }

    /**
     * Log in via session and redirect to the dashboard.
     */
    private function issueWebSession(Request $request, User $user): RedirectResponse
    {
        // A new browser login invalidates every older browser and mobile login.
        $this->invalidateExistingCredentials($user);

        if ($request->session()->has('impersonated_by')) {
            $request->session()->forget(['impersonated_by', 'impersonation_original_team_id',
                'impersonation_original_password_hash', 'team_id', 'password_hash_web']);
        }

        $remember = (bool) $request->boolean('remember');
        Auth::guard('web')->login($user, $remember);

        $this->recordAttendanceCheckIn($user);

        $request->session()->regenerate();

        $teamId = app(\App\Services\TeamContext::class)->teamId($user);
        setPermissionsTeamId($teamId);
        $user->unsetRelation('roles')->unsetRelation('permissions');
        $request->session()->forget('team_id');
        if ($teamId !== null) $request->session()->put('team_id', $teamId);

        $response = redirect()->intended(route('dashboard'));

        if ($remember) {
            // Save the email in a cookie for 30 days so the form can pre-fill it later
            $response->cookie('remembered_email', $user->email, 60 * 24 * 30);
        } else {
            $response->withoutCookie('remembered_email');
        }

        return $response;
    }

    /**
     * Enforce the application's single-active-login policy.
     *
     * Rotating the remember token is required in addition to deleting database
     * sessions: an old "remember me" cookie could otherwise create a fresh
     * session after its original session was removed.
     */
    private function invalidateExistingCredentials(User $user): void
    {
        $user->tokens()->delete();

        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->delete();
        }

        $user->setRememberToken(Str::random(60));
        $user->saveQuietly();
    }

    /**
     * Return a generic, user-enumeration-safe failure response.
     *
     * @throws ValidationException (web context)
     */
    private function genericFailure(Request $request): JsonResponse|RedirectResponse
    {
        $message = self::GENERIC_ERROR.' '.$this->attemptSummary(
            'login_email:'.Str::lower((string) $request->input('email')),
            'login_ip:'.($request->ip() ?? '0.0.0.0'),
        );
        if ($this->isMobileRequest($request)) {
            return response()->json(['message' => $message], 401);
        }

        throw ValidationException::withMessages([
            'email' => [$message],
        ]);
    }

    /** Return a clear lockout notice after the submitted password was verified. */
    private function suspensionFailure(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($this->isMobileRequest($request)) {
            return response()->json(['message' => $message], 423);
        }

        return back()->with('error', $message)->withInput($request->except(['password', 'password_confirmation']));
    }

    /**
     * Extract a meaningful device name from the User-Agent string.
     * Truncated to 255 chars to respect the DB column length.
     */
    private function extractDeviceName(Request $request): string
    {
        $ua = $request->userAgent() ?? 'Unknown Device';

        return mb_substr($ua, 0, 255);
    }

    private function recordAttendanceCheckIn(User $user): void
    {
        $today = Carbon::today()->toDateString();
        $now = Carbon::now()->toTimeString();

        // Check if user is already checked in for today
        $existingOpenAttendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->whereNull('check_out')
            ->exists();

        if ($existingOpenAttendance) {
            return;
        }

        // Create a new session entry for this login
        Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'status' => 'Present',
            'check_in' => $now,
        ]);
    }

    private function recordAttendanceCheckOut(User $user): void
    {
        $today = Carbon::today()->toDateString();
        $now = Carbon::now()->toTimeString();

        // Find the latest open session for today and stamp it
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->whereNull('check_out')
            ->latest('id')
            ->first();

        if ($attendance) {
            $attendance->check_out = $now;
            $attendance->save();
        }
    }
}
