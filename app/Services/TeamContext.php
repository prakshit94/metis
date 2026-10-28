<?php

namespace App\Services;

use App\Modules\Users\Models\User;
use Illuminate\Http\Request;

class TeamContext
{
    /** Session state is a cache, never authority for the user's permission scope. */
    public function teamId(User $user): ?int
    {
        $roles = $user->allRoles()->get();
        $globalAdmin = $roles->contains(fn ($role) => $role->pivot->team_id === null
            && in_array($role->name, ['Super Admin', 'Admin'], true));
        if ($globalAdmin) return null;
        $role = $roles->first(fn ($role) => $role->pivot->team_id !== null);
        return $role ? (int)$role->pivot->team_id : null;
    }

    public function handle(Request $request, \Closure $next)
    {
        $user = $request->user('sanctum') ?? $request->user();
        $teamId = $user ? $this->teamId($user) : null;
        setPermissionsTeamId($teamId);
        $user?->unsetRelation('roles');
        $user?->unsetRelation('permissions');

        // Bearer requests never consume or modify a browser's selected context.
        if (!$request->bearerToken() && $request->hasSession()) {
            if ($teamId === null) $request->session()->forget('team_id');
            else $request->session()->put('team_id', $teamId);
        }

        return $next($request);
    }
}
