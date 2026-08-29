<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            EnsureFrontendRequestsAreStateful::class,
            ThrottleRequests::class.':api',
        ]);

        if (env('APP_ENV') === 'testing' || (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'testing')) {
            $middleware->validateCsrfTokens(except: ['*']);
        }

        // Alias Spatie's middleware for convenient use in route definitions
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(function (UnauthorizedException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'You do not have the required authorization.'], 403);
            }

            if (auth()->check()) {
                return redirect()->route('dashboard')->with('error', 'You do not have the required authorization.');
            }

            return redirect()->route('login')->with('error', 'You do not have the required authorization.');
        });
    })->create();
