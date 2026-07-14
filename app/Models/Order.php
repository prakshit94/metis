<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use SoftDeletes;

    /** Canonical order lifecycle (warehouse → customer). */
    public const LIFECYCLE_STATUSES = [
        'pending',
        'confirmed',
        'processing',
        'ready_to_ship',
        'dispatched',
        'delivered',
    ];

    /** Statuses where stock has left the warehouse (in transit to customer). */
    public static function inTransitStatuses(): array
    {
        return ['dispatched', 'shipped'];
    }

    protected $appends = [
        'lifecycle_status',
        'status_label',
    ];

    /** Map legacy DB value and normalize for UI / stepper logic. */
    public function lifecycleStatus(): string
    {
        if ($this->is_draft && $this->status === 'pending') {
            return 'future_order';
        }
        return $this->status === 'shipped' ? 'dispatched' : $this->status;
    }

    public function statusLabel(): string
    {
        return match ($this->lifecycleStatus()) {
            'future_order'  => 'Future Order',
            'ready_to_ship' => 'Ready to Ship',
            'dispatched'    => 'Dispatched',
            'delivered'     => 'Delivered',
            'processing'    => 'Processing',
            'confirmed'     => 'Confirmed',
            'cancelled'     => 'Cancelled',
            'returned'      => 'Returned',
            'return_requested' => 'Return Requested',
            default         => ucfirst(str_replace('_', ' ', $this->lifecycleStatus())),
        };
    }

    public function getLifecycleStatusAttribute(): string
    {
        return $this->lifecycleStatus();
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->statusLabel();
    }

    protected $fillable = [
        'order_no', 'type', 'party_id', 'order_date', 'total_amount', 
        'tax_amount', 'discount_amount', 'coupon_code', 'applied_offer_id', 'net_amount', 'status', 'warehouse_id',
        'shipping_address_id', 'billing_address_id', 'billing_address', 'shipping_address',
        'is_draft', 'future_order_date', 'created_by', 'updated_by'
    ];

    protected $casts = [
        'order_date' => 'datetime',
        'applied_offer_id' => 'integer',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function appliedOffer(): BelongsTo
    {
        return $this->belongsTo(Offer::class, 'applied_offer_id');
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'party_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(PartyAddress::class, 'shipping_address_id');
    }

    public function billingAddress(): BelongsTo
    {
        return $this->belongsTo(PartyAddress::class, 'billing_address_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function invoice(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Invoice::class)->latestOfMany();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

}
