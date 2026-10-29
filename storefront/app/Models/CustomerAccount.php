<?php

namespace Storefront\Models;

use App\Modules\Customers\Models\Customer;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class CustomerAccount extends Authenticatable
{
    use Notifiable;

    // Provisioning is a trusted server operation, never customer profile mass assignment.
    protected $guarded = ['*'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'email_verified_at' => 'datetime', 'login_enabled' => 'boolean', 'ordering_enabled' => 'boolean'];
    }

    public function customer()
    {
        // Customer identity is shared across every office.
        return $this->belongsTo(Customer::class);
    }

    public function setEmailAttribute(string $value): void
    {
        $this->attributes['email'] = mb_strtolower(trim($value));
    }

    public function canPlaceOrders(): bool
    {
        // Query again so account/customer suspension takes effect without a stale relation.
        return static::query()->whereKey($this->getKey())->where('ordering_enabled', true)->storefrontEligible()->exists();
    }

    public function scopeStorefrontEligible($query)
    {
        return $query->where('login_enabled', true)->whereNotNull('password')->whereNotNull('email_verified_at')
            ->whereHas('customer', fn ($customer) => $customer->where('status', 'active')->where('is_active', true)->where('is_blacklisted', false));
    }
}
