<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Keep shopping and staff sessions independent on the same domain. */
class SelectWebSession
{
    public function handle(Request $request, Closure $next)
    {
        $originalCookie = config('session.cookie');
        $originalLockStore = config('session.block_store');
        $cookie = $request->routeIs('shop.*') ? 'metis_storefront_session' : $originalCookie;
        config(['session.cookie' => $cookie]);
        if ($request->routeIs('shop.*')) config(['session.block_store' => 'file']);
        $session = app('session')->driver();
        $session->setName($cookie);
        if ($request->routeIs('shop.*')) {
            // A staff remember-me cookie also lives on this domain. Never let
            // shared order services resolve it while handling a customer action.
            $request->cookies->remove(auth()->guard('web')->getRecallerName());
        }

        try {
            return $next($request);
        } finally {
            config(['session.cookie' => $originalCookie, 'session.block_store' => $originalLockStore]);
            $session->setName($originalCookie);
        }
    }
}
