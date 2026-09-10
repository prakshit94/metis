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

    public function boot(): void
    {
        parent::boot();

        \App\Modules\Orders\Models\Order::observe(\App\Modules\Orders\Observers\OrderObserver::class);
        \App\Modules\Orders\Models\Payment::observe(\App\Modules\Orders\Observers\PaymentObserver::class);
        \App\Modules\Orders\Models\Invoice::observe(\App\Modules\Orders\Observers\InvoiceObserver::class);
    }
}
