<?php

declare(strict_types=1);

namespace App\Modules\Customers\Models;

use App\Modules\Orders\Models\Order;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Customer extends Model
{
    use SoftDeletes;

    protected $table = 'parties';

    protected $fillable = [
        'uuid',
        'party_code',
        'type',
        'firstname',
        'middlename',
        'lastname',
        'email',
        'phone',
        'alternatemobile',
        'relative_mobile',
        'relative_phone',
        'source',
        'category',
        'company_name',
        'gst_no',
        'pan_no',
        'tax_no',
        'land_area',
        'land_unit',
        'crops',
        'irrigation_type',
        'credit_limit',
        'credit_days',
        'outstanding_balance',
        'credit_valid_till',
        'aadhaar_last4',
        'kyc_completed',
        'kyc_verified_at',
        'first_purchase_at',
        'last_purchase_at',
        'orders_count',
        'status',
        'is_active',
        'is_blacklisted',
        'internal_notes',
        'tags',
        'account_type_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'credit_limit'        => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
        'land_area'           => 'decimal:2',
        'credit_days'         => 'integer',
        'orders_count'        => 'integer',
        'is_active'           => 'boolean',
        'is_blacklisted'      => 'boolean',
        'kyc_completed'       => 'boolean',
        'crops'               => 'array',
        'tags'                => 'array',
        'source'              => 'array',
        'irrigation_type'     => 'array',
        'credit_valid_till'   => 'date',
        'first_purchase_at'   => 'date',
        'last_purchase_at'    => 'date',
        'kyc_verified_at'     => 'datetime',
        'created_at'          => 'datetime',
        'updated_at'          => 'datetime',
    ];

    protected $appends = ['name'];

    /**
     * Always scope to customer type.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('customer', function (Builder $builder) {
            $builder->where('type', 'customer');
        });

        static::creating(function (Customer $customer) {
            $customer->type = 'customer';
        });
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function addresses(): HasMany
    {
        return $this->hasMany(PartyAddress::class, 'party_id');
    }

    public function defaultAddress(): HasOne
    {
        return $this->hasOne(PartyAddress::class, 'party_id')->where('is_default', true);
    }

    public function orders(): HasMany
    {
        if (class_exists(\App\Modules\Orders\Models\Order::class)) {
            return $this->hasMany(\App\Modules\Orders\Models\Order::class, 'party_id');
        }
        return $this->hasMany(self::class, 'id')->whereRaw('0=1'); // empty relation fallback
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')->where('is_active', true);
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('firstname', 'like', "%{$search}%")
              ->orWhere('lastname', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%")
              ->orWhere('gst_no', 'like', "%{$search}%")
              ->orWhere('company_name', 'like', "%{$search}%")
              ->orWhere('party_code', 'like', "%{$search}%");
        });
    }

    // ─── Helpers ────────────────────────────────────────────────────────────────
    public function getNameAttribute(): string
    {
        return trim(collect([$this->firstname, $this->middlename, $this->lastname])
            ->filter()->implode(' '));
    }

    public function initials(): string
    {
        $first = $this->firstname[0] ?? '';
        $last  = $this->lastname[0]  ?? '';
        return strtoupper($first . $last);
    }
}
