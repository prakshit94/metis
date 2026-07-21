<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Sanctum;
use Dedoc\Scramble\Scramble;
use Illuminate\Routing\Route;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureSanctum();

        \Illuminate\Support\Facades\RateLimiter::for('chat', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(config('chat.rate_limits.requests_per_minute', 1200))
                ->by($request->user()?->id ?: $request->ip());
        });

        Gate::define('viewApiDocs', function ($user) {
            return true; // Allow all authenticated users, or change logic as needed
        });

        Scramble::routes(function (Route $route) {
            $uri = $route->uri();
            $name = $route->getName() ?? '';

            // 1. Filter out pure HTML view endpoints and file downloads
            if (preg_match('/(\.create|\.edit|\.pdf|export)$/', $name)) {
                return false;
            }

            // 2. Include natively structured Sanctum API routes
            if (str_starts_with($uri, 'api/')) {
                return true;
            }

            // 3. Include Hybrid Modules (Orders, Inventory, Customers, etc.)
            return preg_match('/^(orders|returns|refunds|payments|invoices|promotions|customers|catalog|inventory|shipping|villages|products)/', $uri);
        });


    }

    // ─── Private ──────────────────────────────────────────────────────────────

    /**
     * Set global Sanctum token expiration to 30 days.
     *
     * Individual tokens may still be created with a
     * shorter expiry by passing an explicit `expiresAt` to createToken().
     */
    private function configureSanctum(): void
    {
        Sanctum::usePersonalAccessTokenModel(
            \Laravel\Sanctum\PersonalAccessToken::class,
        );
    }

}
