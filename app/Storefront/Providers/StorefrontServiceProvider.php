<?php

namespace App\Storefront\Providers;

use App\Storefront\Auth\CustomerUserProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class StorefrontServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Auth::provider('customer-eloquent', fn ($app, $config) => new CustomerUserProvider($app['hash'], $config['model']));
    }
}
