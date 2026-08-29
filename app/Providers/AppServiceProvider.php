<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Crop;
use App\Models\IrrigationType;
use App\Models\LandUnit;
use App\Models\LeadSource;
use Dedoc\Scramble\Scramble;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\PersonalAccessToken;
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
        Model::shouldBeStrict(! app()->isProduction());

        $this->configureSanctum();

        RateLimiter::for('chat', function (Request $request) {
            return Limit::perMinute(config('chat.rate_limits.requests_per_minute', 1200))
                ->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(300)->by($request->user()?->id ?: $request->ip());
        });

        Gate::define('viewApiDocs', function ($user) {
            return $user->hasAnyRole(['Super Admin', 'Admin']);
        });

        // Implicitly grant "Super Admin" role all permissions
        Gate::before(function ($user, $ability) {
            return $user->hasRole('Super Admin') ? true : null;
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

        View::composer('components.add-customer-modal', function ($view) {
            $dynamicCrops = Cache::remember('dynamic_crops', 3600, fn () => Crop::where('is_active', true)->pluck('name'));
            $dynamicLeadSources = Cache::remember('dynamic_lead_sources', 3600, fn () => LeadSource::where('is_active', true)->pluck('name'));
            $dynamicIrrigationTypes = Cache::remember('dynamic_irrigation_types', 3600, fn () => IrrigationType::where('is_active', true)->pluck('name'));
            $dynamicLandUnits = Cache::remember('dynamic_land_units', 3600, fn () => LandUnit::where('is_active', true)->pluck('name'));

            $view->with(compact('dynamicCrops', 'dynamicLeadSources', 'dynamicIrrigationTypes', 'dynamicLandUnits'));
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
