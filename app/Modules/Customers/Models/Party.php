<?php

declare(strict_types=1);

namespace App\Modules\Customers\Models;

use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

use App\Modules\Orders\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Party extends Model implements Auditable
{
    use AuditableTrait;
    use SoftDeletes;


    protected $table = 'parties';

    protected $fillable = [
        'uuid',
        'party_code',
        'type',
        'firstname',
        'middlename',
        'lastname',
        'referral_code',
        'referred_by',
        'email',
        'phone',
        'alternatemobile',
        'relative_name',
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
        'status',
        'is_active',
        'is_blacklisted',
        'internal_notes',
        'tags',
        'account_type_id',
        'created_by',
        'updated_by',
        'orders_count',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
        'land_area' => 'decimal:2',
        'credit_days' => 'integer',
        'orders_count' => 'integer',
        'is_active' => 'boolean',
        'is_blacklisted' => 'boolean',
        'kyc_completed' => 'boolean',
        'crops' => 'array',
        'irrigation_type' => 'array',
        'source' => 'array',
        'tags' => 'array',
        'credit_valid_till' => 'date',
        'first_purchase_at' => 'date',
        'last_purchase_at' => 'date',
        'kyc_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['name'];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'party_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(PartyAddress::class, 'party_id');
    }

    public function callLogs(): HasMany
    {
        return $this->hasMany(\App\Models\CallLog::class, 'customer_id');
    }

    public function getNameAttribute(): string
    {
        return trim(collect([$this->firstname, $this->middlename, $this->lastname])
            ->filter()->implode(' '));
    }

    public function referrer()
    {
        return $this->belongsTo(self::class, 'referred_by');
    }

    public function referrals()
    {
        return $this->hasMany(self::class, 'referred_by');
    }

    public function referredOrders(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(Order::class, self::class, 'referred_by', 'party_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($party) {
            if (empty($party->referral_code)) {
                $party->referral_code = 'REF-' . strtoupper(\Illuminate\Support\Str::random(6));
            }
        });
    }
}
