<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // This global middleware runs before route middleware. Resolve Sanctum
        // explicitly so a deactivated bearer-token user is not missed because
        // the application's default guard is the web session guard.
        $user = $request->user('sanctum') ?? Auth::guard('web')->user();

        if ($user && (! $user->isActive() || $user->isSuspended())) {
            
            // Log out the user
            if ($request->bearerToken() || $request->is('api/*')) {
                // If it's an API request, revoke current token
                $request->user('sanctum')?->currentAccessToken()?->delete();
                // Auth::guard('sanctum')->logout() is not strictly a method but revoking token is enough
            } else {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            $message = $user->isSuspended()
                ? 'Your account is temporarily suspended.'
                : 'Your account has been deactivated.';

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => $message], 403);
            }

            return redirect()->route('login')->withErrors(['email' => $message]);
        }

        return $next($request);
    }
}
