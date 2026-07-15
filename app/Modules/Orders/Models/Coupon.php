<?php

namespace App\Modules\Orders\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use HasFactory, SoftDeletes;

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($coupon) {
            if (is_null($coupon->min_spend)) {
                $coupon->min_spend = 0.00;
            }
            if (is_null($coupon->used_count)) {
                $coupon->used_count = 0;
            }
            if (is_null($coupon->is_active)) {
                $coupon->is_active = true;
            }
        });
    }

    protected $fillable = [
        'code',
        'type',
        'value',
        'min_spend',
        'max_discount',
        'expiry_date',
        'usage_limit',
        'used_count',
        'status',
        'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_spend' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'expiry_date' => 'date',
        'usage_limit' => 'integer',
        'used_count' => 'integer',
        'is_active' => 'boolean',
    ];
}
