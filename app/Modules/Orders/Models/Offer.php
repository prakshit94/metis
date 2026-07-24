<?php

namespace App\Modules\Orders\Models;

use App\Modules\Catalog\Models\Product;
use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Offer extends Model
{
    use HasFactory;

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($offer) {
            if ($offer->type === 'bogo') {
                if (is_null($offer->value)) {
                    $offer->value = 0.00;
                }
                if (is_null($offer->discount_type)) {
                    $offer->discount_type = 'fixed';
                }
            } else {
                if (is_null($offer->buy_qty)) {
                    $offer->buy_qty = 1;
                }
                if (is_null($offer->get_qty)) {
                    $offer->get_qty = 1;
                }
            }
            if (is_null($offer->min_spend)) {
                $offer->min_spend = 0.00;
            }
            if (is_null($offer->priority)) {
                $offer->priority = 0;
            }
            if (is_null($offer->is_active)) {
                $offer->is_active = true;
            }
            if (is_null($offer->used_count)) {
                $offer->used_count = 0;
            }
        });
    }

    protected $fillable = [
        'name',
        'type',
        'discount_type',
        'value',
        'min_spend',
        'max_discount',
        'product_id',
        'buy_qty',
        'get_qty',
        'starts_at',
        'ends_at',
        'priority',
        'is_active',
        'created_by',
        'updated_by',
        'used_count',
        'applicable_categories',
        'applicable_products',
        'excluded_categories',
        'excluded_products',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_spend' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'buy_qty' => 'integer',
        'get_qty' => 'integer',
        'priority' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
        'used_count' => 'integer',
        'applicable_categories' => 'array',
        'applicable_products' => 'array',
        'excluded_categories' => 'array',
        'excluded_products' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive($query)
    {
        $now = now();

        return $query
            ->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            });
    }
}
