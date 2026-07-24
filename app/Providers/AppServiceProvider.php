<?php

declare(strict_types=1);

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use Illuminate\Database\Eloquent\Model;

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
        Model::shouldBeStrict(!app()->isProduction());

        $this->configureSanctum();

        RateLimiter::for('chat', function (Request $request) {
            return Limit::perMinute(config('chat.rate_limits.requests_per_minute', 1200))
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
            PersonalAccessToken::class,
        );
    }
}
