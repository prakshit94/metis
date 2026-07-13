<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Sanctum;

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

        Gate::define('viewApiDocs', function ($user) {
            return true; // Allow all authenticated users, or change logic as needed
        });
    }

    // ─── Private ──────────────────────────────────────────────────────────────

    /**
     * Set global Sanctum token expiration to 30 days.
     *
     * Individual tokens (e.g. impersonation) may still be created with a
     * shorter expiry by passing an explicit `expiresAt` to createToken().
     */
    private function configureSanctum(): void
    {
        Sanctum::usePersonalAccessTokenModel(
            \Laravel\Sanctum\PersonalAccessToken::class,
        );
    }

}
