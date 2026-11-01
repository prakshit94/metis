<?php

use App\Providers\AppServiceProvider;
use App\Providers\EventServiceProvider;

return [
    AppServiceProvider::class,
    EventServiceProvider::class,
    \App\Storefront\Providers\StorefrontServiceProvider::class,
];
