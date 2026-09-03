<?php

declare(strict_types=1);

namespace App\Modules\Users\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Modules\Users\Models\User;
use App\Modules\Users\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class SecurityController extends Controller
{
    /**
     * Get active sessions for the authenticated user.
     * Includes personal access tokens and database sessions.
     */
    public function sessions(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $tokens = $user->tokens()->orderByDesc('last_used_at')->get()->map(function ($token) {
            return [
                'id' => 'token_' . $token->id,
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
                    'id' => 'session_' . $session->id,
                    'device' => $deviceType,
                    'deviceIcon' => $icon,
                    'location' => 'Unknown Location',
                    'ip' => $session->ip_address,
                    'lastActive' => \Carbon\Carbon::createFromTimestamp($session->last_activity)->diffForHumans(),
                    'current' => $session->id === $request->session()->getId(),
                ];
            });
        }

        $allSessions = $tokens->concat($sessions)->sortByDesc('lastActive')->values();

        return response()->json([
            'data' => $allSessions
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
                    'details' => $log->user_agent . ' from ' . $log->ip_address,
                ];
            });

        return response()->json(['data' => $history]);
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
                ->where('id', '!=', $request->session()->getId())
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
            \Illuminate\Support\Facades\DB::table('personal_access_tokens')->delete();

            if (config('session.driver') === 'database') {
                \Illuminate\Support\Facades\DB::table(config('session.table', 'sessions'))->delete();
            }

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json(['message' => 'System-wide emergency lockdown initiated. All users have been logged out.']);
        }

        // Personal account lockdown
        $user->update(['password_changed_at' => now()]);
        $user->tokens()->delete();

        if (config('session.driver') === 'database') {
            \Illuminate\Support\Facades\DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->delete();
        }
        
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Emergency lockdown initiated. You have been logged out.']);
    }

    /**
     * Impersonate another user (Super Admin only).
     */
    public function impersonate(Request $request, User $user)
    {
        abort_unless($request->user()->hasRole('Super Admin'), 403, 'Only Super Admins can impersonate.');
        abort_if($user->id === $request->user()->id, 400, 'Cannot impersonate yourself.');

        // Store original ID in session
        Session::put('impersonated_by', $request->user()->id);
        
        // Log in as the target user using the web guard
        Auth::guard('web')->login($user);

        return response()->json(['message' => 'Impersonating ' . $user->name, 'redirect' => '/']);
    }

    /**
     * Stop impersonating and return to original user.
     */
    public function stopImpersonating(Request $request)
    {
        if (!Session::has('impersonated_by')) {
            return response()->json(['message' => 'Not currently impersonating anyone.'], 400);
        }

        $originalUserId = Session::get('impersonated_by');
        $originalUser = User::find($originalUserId);

        Session::forget('impersonated_by');

        if ($originalUser) {
            Auth::guard('web')->login($originalUser);
            return response()->json(['message' => 'Welcome back, ' . $originalUser->name, 'redirect' => '/users']);
        }

        return response()->json(['message' => 'Original user not found. Logging out.'], 400);
    }

    /**
     * Clone a Role.
     */
    public function cloneRole(Request $request, Role $role): JsonResponse
    {
        abort_unless($request->user()->can('role-create'), 403);

        $newRoleName = $request->input('new_name', $role->name . ' (Copy)');
        
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
