<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TeamContextMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (session()->has('team_id')) {
            setPermissionsTeamId(session('team_id'));
        } elseif ($user = $request->user()) {
            if ($teamId = $user->lob_team_id) {
                setPermissionsTeamId($teamId);
                if ($request->hasSession()) {
                    session(['team_id' => $teamId]);
                }
            } else {
                setPermissionsTeamId(null);
            }
        } else {
            setPermissionsTeamId(null);
        }

        if ($user = $request->user()) {
            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');
        }

        return $next($request);
    }
}
