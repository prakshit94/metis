<?php

declare(strict_types=1);

namespace App\Modules\Users\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Modules\Users\Models\Role;
use App\Modules\Users\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SecurityController extends Controller
{
    /**
     * Get active sessions for the authenticated user.
     * Includes personal access tokens and database sessions.
     */
    public function sessions(Request $request): JsonResponse
    {
        $user = $request->user();

        $tokens = $user->tokens()->orderByDesc('last_used_at')->get()->map(function ($token) use ($request) {
            return [
                'id' => 'token_'.$token->id,
                'device' => $token->name,
                'deviceIcon' => str_contains(strtolower($token->name), 'mobile') ? 'bi-phone' : 'bi-laptop',
                'location' => 'Unknown Location', // Normally requires a GeoIP service
                'ip' => 'N/A', // Sanctum tokens don't track IP by default unless customized
                'lastActive' => $token->last_used_at ? $token->last_used_at->diffForHumans() : 'Never',
                'current' => $token->id === $request->user()->currentAccessToken()?->id,
            ];
        });

        $sessions = [];
        if (config('session.driver') === 'database') {
            $dbSessions = DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->orderByDesc('last_activity')
                ->get();

            $sessions = $dbSessions->map(function ($session) use ($request) {
                $agent = $session->user_agent;
                $isMobile = preg_match('/Mobile|Android|BlackBerry|iPhone|iPad|iPod|Opera Mini|IEMobile/i', $agent);
                $deviceType = $isMobile ? 'Mobile Device' : 'Desktop Browser';
                $icon = $isMobile ? 'bi-phone' : 'bi-laptop';

                return [
                    'id' => 'session_'.$session->id,
                    'device' => $deviceType,
                    'deviceIcon' => $icon,
                    'location' => 'Unknown Location',
                    'ip' => $session->ip_address,
                    'lastActive' => Carbon::createFromTimestamp($session->last_activity)->diffForHumans(),
                    'current' => $request->hasSession() && $session->id === $request->session()->getId(),
                ];
            });
        }

        $allSessions = $tokens->concat($sessions)->sortByDesc('lastActive')->values();

        return response()->json([
            'data' => $allSessions,
        ]);
    }

    /**
     * Get security activity log for the authenticated user.
     */
    public function activity(Request $request): JsonResponse
    {
        $history = $request->user()->loginHistories()
            ->orderByDesc('attempted_at')
            ->limit(50)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'type' => $log->status === 'success' ? 'login_success' : 'failed_login',
                    'message' => $log->status === 'success' ? 'Successful login' : 'Failed login attempt',
                    'timestamp' => $log->attempted_at,
                    'severity' => $log->status === 'success' ? 'success' : 'danger',
                    'icon' => $log->status === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle',
                    'details' => $log->user_agent.' from '.$log->ip_address,
                ];
            });

        $events = DB::table('two_factor_security_events')->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')->limit(50)->get()->map(fn ($event) => [
                'id' => 'two_factor_'.$event->id, 'type' => 'two_factor_'.$event->event,
                'message' => 'Two-factor: '.str_replace('_', ' ', $event->event),
                'timestamp' => Carbon::parse($event->created_at),
                'severity' => str_contains($event->event, 'failed') ? 'danger' : 'info',
                'icon' => 'bi-shield-lock', 'details' => 'IP: '.$event->ip_address,
            ]);

        return response()->json(['data' => $history->concat($events)->sortByDesc('timestamp')->take(50)->values()]);
    }

    /**
     * Terminate a specific session.
     */
    public function terminateSession(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        if (str_starts_with($id, 'token_')) {
            $tokenId = str_replace('token_', '', $id);
            $user->tokens()->where('id', $tokenId)->delete();
        } elseif (str_starts_with($id, 'session_') && config('session.driver') === 'database') {
            $sessionId = str_replace('session_', '', $id);
            DB::table(config('session.table', 'sessions'))
                ->where('id', $sessionId)
                ->where('user_id', $user->id)
                ->delete();
        } else {
            return response()->json(['message' => 'Invalid session ID'], 400);
        }

        return response()->json(['message' => 'Session terminated successfully']);
    }

    /**
     * Terminate all OTHER sessions and tokens.
     */
    public function terminateOtherSessions(Request $request): JsonResponse
    {
        $user = $request->user();

        // Delete other Sanctum tokens
        $currentTokenId = $user->currentAccessToken()?->id;
        $user->tokens()->where('id', '!=', $currentTokenId)->delete();

        // Delete other web sessions
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->when($request->hasSession(), fn ($query) => $query->where('id', '!=', $request->session()->getId()))
                ->delete();
        }

        return response()->json(['message' => 'All other sessions terminated successfully']);
    }

    /**
     * Emergency Lockdown: Logout all sessions and require password reset.
     */
    public function emergencyLockdown(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasRole('Super Admin')) {
            // System-wide emergency lockdown
            DB::table('two_factor_login_challenges')->delete();
            DB::table('personal_access_tokens')->delete();

            if (config('session.driver') === 'database') {
                DB::table(config('session.table', 'sessions'))->delete();
            }

            if ($request->hasSession()) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return response()->json(['message' => 'System-wide emergency lockdown initiated. All users have been logged out.']);
        }

        // Personal account lockdown
        DB::table('two_factor_login_challenges')->where('user_id', $user->id)->delete();
        $user->update(['password_changed_at' => now()]);
        $user->tokens()->delete();

        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->delete();
        }

        if ($request->hasSession()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json(['message' => 'Emergency lockdown initiated. You have been logged out.']);
    }

    /**
     * Impersonate another user (Super Admin only).
     */
    public function impersonate(Request $request, User $user): JsonResponse
    {
        $guard = Auth::guard('web');
        abort_unless($request->hasSession() && $guard->check() && ! $request->bearerToken()
            && (string) $guard->id() === (string) $request->user()?->id, 403, 'Impersonation requires a signed-in browser session.');
        abort_if($request->session()->has('impersonated_by'), 409, 'Stop the current impersonation before starting another.');
        abort_unless($request->user()->hasRole('Super Admin'), 403, 'Only Super Admins can impersonate.');
        abort_if((string) $user->id === (string) $request->user()->id, 400, 'Cannot impersonate yourself.');
        abort_if($user->trashed() || ! $user->isActive() || $user->isSuspended(), 422, 'Cannot impersonate a deleted, inactive or suspended user.');

        $originalId = $guard->id();
        $originalPasswordHash = $guard->hashPasswordForCookie($guard->user()->getAuthPassword());
        $originalTeamId = $request->session()->get('team_id');
        $this->switchImpersonationUser($request, $user);
        $request->session()->put('impersonated_by', $originalId);
        $request->session()->put('impersonation_original_team_id', $originalTeamId);
        $request->session()->put('impersonation_original_password_hash', $originalPasswordHash);

        return response()->json(['message' => 'Impersonating '.$user->name, 'redirect' => '/admin']);
    }

    /**
     * Stop impersonating and return to the original eligible Super Admin.
     */
    public function stopImpersonating(Request $request): JsonResponse
    {
        abort_unless($request->hasSession() && ! $request->bearerToken(), 403, 'Impersonation requires a signed-in browser session.');
        if (! $request->session()->has('impersonated_by')) {
            return response()->json(['message' => 'Not currently impersonating anyone.'], 400);
        }

        $originalUser = User::find($request->session()->get('impersonated_by'));
        $originalTeamId = $request->session()->get('impersonation_original_team_id');
        $originalPasswordHash = $request->session()->get('impersonation_original_password_hash');
        // The target account's team context must not hide the original admin's roles.
        $canReturn = $originalUser && $originalUser->isActive() && ! $originalUser->isSuspended()
            && is_string($originalPasswordHash)
            && hash_equals(Auth::guard('web')->hashPasswordForCookie($originalUser->getAuthPassword()), $originalPasswordHash)
            && $originalUser->allRoles()->where('name', 'Super Admin')->where('guard_name', 'web')->exists();

        if (! $canReturn) {
            Auth::guard('web')->logoutCurrentDevice();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            setPermissionsTeamId(null);

            return response()->json(['message' => 'The original account is no longer available for impersonation. You have been logged out.', 'redirect' => '/admin/login']);
        }

        $this->switchImpersonationUser($request, $originalUser);
        if ($originalTeamId !== null) {
            $request->session()->put('team_id', $originalTeamId);
            setPermissionsTeamId($originalTeamId);
        }

        return response()->json(['message' => 'Welcome back, '.$originalUser->name, 'redirect' => '/admin/users']);
    }

    /** Keep identities, CSRF tokens, and permission/session data separate. */
    private function switchImpersonationUser(Request $request, User $user): void
    {
        Auth::guard('web')->logoutCurrentDevice();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        setPermissionsTeamId(null);
        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');
        Auth::guard('web')->login($user);
        Auth::shouldUse('web');
    }

    /**
     * Clone a Role.
     */
    public function cloneRole(Request $request, Role $role): JsonResponse
    {
        abort_unless($request->user()->can('role-create'), 403);

        $newRoleName = $request->input('new_name', $role->name.' (Copy)');

        if (Role::where('name', $newRoleName)->exists()) {
            return response()->json(['message' => 'A role with this name already exists.'], 422);
        }

        $newRole = Role::create([
            'name' => $newRoleName,
            'guard_name' => $role->guard_name,
            'team_id' => null, // Force global role clone
        ]);

        $newRole->syncPermissions($role->permissions);

        return response()->json(['message' => 'Role cloned successfully.', 'data' => $newRole]);
    }
}
