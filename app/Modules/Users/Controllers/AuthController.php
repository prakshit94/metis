<?php

declare(strict_types=1);

namespace App\Modules\Users\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Modules\Core\Controllers\Controller;
use App\Modules\Users\Models\Attendance;
use App\Modules\Users\Models\LoginHistory;
use App\Modules\Users\Models\User;
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

/**
 * Handles all authentication flows for both Web (session) and Mobile (Sanctum
 * token) contexts within a single, unified controller.
 */
class AuthController extends Controller
{
    // ─── Constants ────────────────────────────────────────────────────────────

    private const int MAX_ATTEMPTS = 5;

    private const int LOCKOUT_WINDOW_MIN = 5;

    private const int SUSPENSION_MIN = 15;

    private const int TOKEN_EXPIRY_DAYS = 30;

    private const string GENERIC_ERROR = 'These credentials do not match our records.';

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
        $tooManyAttempts = RateLimiter::tooManyAttempts($emailKey, self::MAX_ATTEMPTS) ||
                           RateLimiter::tooManyAttempts($ipKey, self::MAX_ATTEMPTS);

        if ($tooManyAttempts) {
            // Suspend any matching active user account
            $target = User::where('email', $email)->first();
            if ($target !== null) {
                $target->suspend(self::SUSPENSION_MIN);
            }

            // Log the throttled attempt directly (no Auth::attempt needed)
            LoginHistory::create([
                'user_id' => $target?->id,
                'email_attempted' => $email,
                'ip_address' => $ip,
                'user_agent' => $request->userAgent(),
                'device_type' => $this->deviceType($request),
                'status' => 'failed',
                'failure_reason' => 'throttled',
                'attempted_at' => Carbon::now(),
            ]);

            return $this->isMobileRequest($request)
                ? response()->json(['message' => 'Too many login attempts. Please try again later.'], 429)
                : back()->withErrors(['email' => 'Too many login attempts. Please try again later.'])->withInput();
        }

        // ── 2. Attempt authentication ────────────────────────────────────────
        $user = User::where('email', $email)->first();

        // Validate credentials — use Hash::check to avoid timing side-channels
        $isValid = false;
        if ($user !== null) {
            $isValid = Hash::check($request->validated('password'), $user->password);
        } else {
            // Dummy hash to prevent timing-based user enumeration attacks
            $dummyHash = in_array(config('hashing.driver'), ['argon2id', 'argon2i'])
                ? '$argon2id$v=19$m=65536,t=4,p=1$Z29uOU56Slc0SWRJbFAvYg$ILca1T1tS+yPIT6WNfrXcA6t0S0XqX9wngZa+PXrZj4'
                : '$2y$12$R.vP3sZq1WJ/1q0kK8M9V.Qn6Qx.X.vP9Q..';
            Hash::check($request->validated('password'), $dummyHash);
        }

        if ($user === null || ! $isValid) {
            RateLimiter::hit($emailKey, self::LOCKOUT_WINDOW_MIN * 60);
            RateLimiter::hit($ipKey, self::LOCKOUT_WINDOW_MIN * 60);

            // Fire the native Failed event so our listener captures it
            Event::dispatch(new Failed('web', $user, ['email' => $email, 'password' => $request->validated('password')]));

            return $this->genericFailure($request);
        }

        // ── 3. Guard: suspended or inactive accounts ─────────────────────────
        if ($user->isSuspended()) {
            RateLimiter::hit($emailKey, self::LOCKOUT_WINDOW_MIN * 60);
            RateLimiter::hit($ipKey, self::LOCKOUT_WINDOW_MIN * 60);

            Event::dispatch(new Failed('web', $user, ['email' => $email, 'password' => $request->validated('password')]));

            return $this->genericFailure($request);
        }

        if (! $user->isActive()) {
            RateLimiter::hit($emailKey, self::LOCKOUT_WINDOW_MIN * 60);
            RateLimiter::hit($ipKey, self::LOCKOUT_WINDOW_MIN * 60);

            Event::dispatch(new Failed('web', $user, ['email' => $email, 'password' => $request->validated('password')]));

            return $this->genericFailure($request);
        }

        // Clear rate limiter on successful authentication
        RateLimiter::clear($emailKey);
        RateLimiter::clear($ipKey);

        // ── 4. Issue credentials ─────────────────────────────────────────────
        if ($this->isMobileRequest($request)) {
            return $this->issueMobileToken($request, $user);
        }

        return $this->issueWebSession($request, $user);
    }

    // ─── Logout ───────────────────────────────────────────────────────────────

    /**
     * Invalidate the current session or revoke the current API token.
     */
    public function logout(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user() ?? Auth::guard('web')->user();
        if ($user) {
            $this->recordAttendanceCheckOut($user);
        }

        if ($this->isMobileRequest($request)) {
            $request->user()?->currentAccessToken()?->delete();

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
        $user = $request->user();
        $currentToken = $user->currentAccessToken();

        $revokedCount = $user->tokens()
            ->where('id', '!=', $currentToken->id)
            ->delete();

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

        // Revoke all existing mobile tokens for this user
        $user->tokens()->delete();

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
        // Delete any existing sessions for this user from the database
        DB::table('sessions')->where('user_id', $user->id)->delete();

        $remember = (bool) $request->boolean('remember');
        Auth::guard('web')->login($user, $remember);

        $this->recordAttendanceCheckIn($user);

        $request->session()->regenerate();

        if ($teamId = $user->lob_team_id) {
            $request->session()->put('team_id', $teamId);
        }

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
     * Return a generic, user-enumeration-safe failure response.
     *
     * @throws ValidationException (web context)
     */
    private function genericFailure(Request $request): JsonResponse|RedirectResponse
    {
        if ($this->isMobileRequest($request)) {
            return response()->json(['message' => self::GENERIC_ERROR], 401);
        }

        throw ValidationException::withMessages([
            'email' => [self::GENERIC_ERROR],
        ]);
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
