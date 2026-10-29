<?php

namespace Storefront\Providers;

use App\Providers\EventServiceProvider;

/** Register the existing business observers without staff authentication listeners. */
class DomainEventsServiceProvider extends EventServiceProvider
{
    protected $subscribe = [];
}
