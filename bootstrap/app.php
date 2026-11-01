<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Services\ApiClientContext;
use App\Services\TeamContext as TeamContextMiddleware;
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
            ApiClientContext::class,
            EnsureFrontendRequestsAreStateful::class,
            ThrottleRequests::class.':api',
        ]);

        $middleware->web(prepend: [\App\Http\Middleware\SelectWebSession::class]);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('dashboard'));
        $middleware->api(append: [
            TeamContextMiddleware::class,
            EnsureUserIsActive::class,
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
        $exceptions->dontFlash(['current_password', 'security_current_password', 'security_code', 'code', 'challenge_token', 'api_key', 'aws_key', 'aws_secret']);
        $exceptions->shouldRenderJsonWhen(fn ($request, $error) => $request->is('api/*') || $request->expectsJson());
        $exceptions->renderable(function (\Illuminate\Database\UniqueConstraintViolationException $error, $request) {
            if ($request->is('api/teams', 'api/teams/*', 'api/offices', 'api/offices/*')) {
                return response()->json(['message' => 'The state or office name/code already exists. Refresh the list and use a unique value.'], 422);
            }
            return null;
        });
        // Keep browser OTP lockouts on the challenge form; mobile clients retain HTTP 429.
        $exceptions->renderable(function (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $error, $request) {
            if ($error->getStatusCode() !== 429 || !$request->isMethod('POST')
                || !$request->is('admin/login/two-factor', 'admin/login/two-factor/*') || $request->expectsJson()) {
                return null;
            }
            $seconds = max(1, (int) ($error->getHeaders()['Retry-After'] ?? 300));
            $message = 'Too many verification requests. Wait '.$seconds.' seconds before trying again. '
                .'If your sign-in request expires, return to sign in and request a fresh code.';

            return redirect()->route('login.two-factor')->with('error', $message)
                ->header('Retry-After', (string) $seconds)->header('Cache-Control', 'no-store');
        });
        $exceptions->renderable(function (UnauthorizedException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'You do not have the required authorization.'], 403);
            }

            if (auth()->check()) {
                // A denied dashboard must never redirect to itself. Security is owner-accessible.
                if ($request->routeIs('security')) {
                    return response()->view('errors.403', [], 403)->header('Cache-Control', 'no-store');
                }
                $canDashboard = auth()->user()->can('dashboard-view') || auth()->user()->can('warehouse-dashboard-view');
                $destination = !$request->routeIs('dashboard') && $canDashboard ? 'dashboard' : 'security';
                return redirect()->route($destination)->with('error', 'You do not have the required authorization.');
            }

            return redirect()->route('login')->with('error', 'You do not have the required authorization.');
        });
    })->create();
