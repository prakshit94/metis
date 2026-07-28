<?php

declare(strict_types=1);

namespace App\Modules\Orders\Models;

use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

use App\Modules\Catalog\Models\Warehouse;
use App\Modules\Core\Models\Village;
use App\Modules\Customers\Models\Party;
use App\Modules\Customers\Models\PartyAddress;
use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model implements Auditable
{
    use AuditableTrait;
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

        $latestReturn = $this->relationLoaded('orderReturns')
            ? $this->orderReturns->sortByDesc('id')->first()
            : $this->orderReturns()->latest('id')->first();

        if ($latestReturn) {
            if ($latestReturn->status === 'completed') {
                return 'returned';
            }
            if ($latestReturn->status !== 'rejected') {
                return 'return_requested';
            }
        }

        return $this->status === 'shipped' ? 'dispatched' : $this->status;
    }

    public function statusLabel(): string
    {
        return match ($this->lifecycleStatus()) {
            'future_order' => 'Future Order',
            'ready_to_ship' => 'Ready to Ship',
            'dispatched' => 'Dispatched',
            'delivered' => 'Delivered',
            'processing' => 'Processing',
            'confirmed' => 'Confirmed',
            'cancelled' => 'Cancelled',
            'returned' => 'Returned',
            'return_requested' => 'Return Requested',
            default => ucfirst(str_replace('_', ' ', $this->lifecycleStatus())),
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
        'shipping_address_id', 'shipping_address_line_1', 'shipping_address_line_2',
        'shipping_village_id', 'shipping_village_name', 'shipping_post_office', 'shipping_taluka',
        'shipping_district', 'shipping_city', 'shipping_state', 'shipping_pincode',
        'billing_address_id', 'billing_address_line_1', 'billing_address_line_2',
        'billing_village_id', 'billing_village_name', 'billing_post_office', 'billing_taluka',
        'billing_district', 'billing_city', 'billing_state', 'billing_pincode',
        'is_draft', 'future_order_date', 'scheduled_confirmation_date', 'confirmation_attempts', 'created_by', 'updated_by',
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

    public function shippingVillage(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'shipping_village_id');
    }

    public function billingVillage(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'billing_village_id');
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

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class)->latestOfMany();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function orderReturns(): HasMany
    {
        return $this->hasMany(OrderReturn::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(OrderStatusLog::class)->orderBy('created_at', 'desc');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function getTotalPaidAttribute(): float
    {
        return (float) $this->payments()->where('status', 'completed')->sum('amount');
    }

    public function getTotalRefundedAttribute(): float
    {
        return (float) $this->refunds()->where('status', 'completed')->sum('amount');
    }
}
