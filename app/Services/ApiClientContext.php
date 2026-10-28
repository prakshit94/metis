<?php

namespace App\Services;

use Illuminate\Http\Request;

class ApiClientContext
{
    public function handle(Request $request, \Closure $next)
    {
        // API routes always return JSON for errors and hybrid controllers, even without Accept.
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
