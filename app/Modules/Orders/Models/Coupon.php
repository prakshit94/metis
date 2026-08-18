<?php

namespace App\Modules\Orders\Models;

use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model implements Auditable
{
    use AuditableTrait;
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
        'created_by',
        'updated_by',
        'applicable_categories',
        'applicable_products',
        'excluded_categories',
        'excluded_products',
        'free_product_id',
        'free_qty',
        'cashback_percent',
        'cashback_fixed',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_spend' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'expiry_date' => 'date',
        'usage_limit' => 'integer',
        'used_count' => 'integer',
        'is_active' => 'boolean',
        'applicable_categories' => 'array',
        'applicable_products' => 'array',
        'excluded_categories' => 'array',
        'excluded_products' => 'array',
        'free_product_id' => 'integer',
        'free_qty' => 'integer',
        'cashback_percent' => 'decimal:2',
        'cashback_fixed' => 'decimal:2',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
