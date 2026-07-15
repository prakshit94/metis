<?php

declare(strict_types=1);

namespace App\Modules\Customers\Models;

use App\Modules\Orders\Models\Order;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Party extends Model
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
        'phone_number_2',
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
        'credit_limit'        => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
        'land_area'           => 'decimal:2',
        'credit_days'         => 'integer',
        'orders_count'        => 'integer',
        'is_active'           => 'boolean',
        'is_blacklisted'      => 'boolean',
        'kyc_completed'       => 'boolean',
        'crops'               => 'array',
        'irrigation_type'     => 'array',
        'source'              => 'array',
        'tags'                => 'array',
        'credit_valid_till'   => 'date',
        'first_purchase_at'   => 'date',
        'last_purchase_at'    => 'date',
        'kyc_verified_at'     => 'datetime',
        'created_at'          => 'datetime',
        'updated_at'          => 'datetime',
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

    public function getNameAttribute(): string
    {
        return trim(collect([$this->firstname, $this->middlename, $this->lastname])
            ->filter()->implode(' '));
    }
}
