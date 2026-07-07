<?php

declare(strict_types=1);

namespace App\Providers;

use App\Listeners\LogAuthenticationAttempts;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

/**
 * Registers event listeners for the application.
 *
 * LogAuthenticationAttempts is a subscriber — its subscribe() method
 * maps both Login and Failed events internally, so we register it
 * via $subscribe rather than $listen.
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * The subscriber classes to register.
     *
     * @var list<class-string>
     */
    protected $subscribe = [
        LogAuthenticationAttempts::class,
    ];

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
