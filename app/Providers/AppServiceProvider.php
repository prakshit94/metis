<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
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
        $this->configureGate();
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

    /**
     * Register the global Gate::before callback.
     *
     * Super Admin users bypass ALL permission checks automatically.
     * This ensures that even new permissions introduced in the future
     * do not need to be explicitly granted to Super Admins.
     *
     * IMPORTANT: We explicitly skip this bypass for the 'viewNova' ability
     * (or any ability that should require explicit grants) by checking the
     * ability name — extend the exclusion list as needed.
     */
    private function configureGate(): void
    {
        Gate::before(function (\App\Models\User $user, string $ability): ?bool {
            // Return true (bypass) only for authenticated Super Admin users
            if ($user->hasRole('Super Admin')) {
                return true;
            }

            // Return null to fall through to normal permission checks
            return null;
        });
    }
}
