<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Warehouse;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stock extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'quantity',
        'reserved_qty',
        'dispatched_qty',
        'committed_qty',
        'in_transit_qty',
        'damaged_qty',
        'status',
    ];

    protected $casts = [
        'quantity'        => 'float',
        'reserved_qty'    => 'float',
        'dispatched_qty'  => 'float',
        'committed_qty'   => 'float',
        'in_transit_qty'  => 'float',
        'damaged_qty'     => 'float',
    ];

    // ─── Computed Attributes ───────────────────────────────────────────────

    /**
     * Available qty = physical stock − reserved stock.
     * This is what can actually be sold/allocated right now.
     */
    public function getAvailableQtyAttribute(): float
    {
        return max(0.0, (float) $this->quantity - (float) $this->reserved_qty);
    }

    /**
     * Delivered qty = sum of delivered and completed order items for this product and warehouse.
     */
    public function getDeliveredQtyAttribute(): float
    {
        return (float) \App\Modules\Orders\Models\OrderItem::where('product_id', $this->product_id)
            ->whereHas('order', function ($q) {
                $q->where('warehouse_id', $this->warehouse_id)
                  ->whereIn('status', ['delivered', 'completed']);
            })
            ->sum('quantity');
    }

    // ─── Relationships ─────────────────────────────────────────────────────

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(StockReservation::class, 'product_id', 'product_id')
            ->where('warehouse_id', $this->warehouse_id ?? -1);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'product_id', 'product_id')
            ->where('warehouse_id', $this->warehouse_id ?? -1);
    }

    public function pendingOrderItems(): HasMany
    {
        return $this->hasMany(\App\Modules\Orders\Models\OrderItem::class, 'product_id', 'product_id')
            ->whereHas('order', function ($query) {
                $query->whereColumn('orders.warehouse_id', 'stocks.warehouse_id')
                      ->where('orders.status', 'pending');
            });
    }
}
