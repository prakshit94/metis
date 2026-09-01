<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Warehouse;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Orders\Models\OrderReturnItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Stock extends Model implements Auditable
{
    use LogsActivity;
    use AuditableTrait;
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
        'allow_overselling',
        'overselling_qty',
        'status',
        'is_sku_enabled',
        'deleted_at',
    ];

    protected $casts = [
        'quantity' => 'float',
        'reserved_qty' => 'float',
        'dispatched_qty' => 'float',
        'committed_qty' => 'float',
        'in_transit_qty' => 'float',
        'damaged_qty' => 'float',
        'allow_overselling' => 'boolean',
        'overselling_qty' => 'integer',
        'is_sku_enabled' => 'boolean',
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
     * Delivered qty = sum of delivered/completed order items minus processed returned items.
     */
    public function getDeliveredQtyAttribute(): float
    {
        $delivered = (float) OrderItem::where('product_id', $this->product_id)
            ->whereHas('order', function ($q) {
                $q->where('warehouse_id', $this->warehouse_id)
                    ->whereIn('status', ['delivered', 'completed']);
            })
            ->sum('quantity');

        $returned = (float) OrderReturnItem::where('product_id', $this->product_id)
            ->whereHas('orderReturn', function ($q) {
                $q->where('status', 'completed')
                    ->whereHas('order', function ($q2) {
                        $q2->where('warehouse_id', $this->warehouse_id)
                            ->whereIn('status', ['delivered', 'completed']);
                    });
            })
            ->sum('received_qty');

        return max(0.0, $delivered - $returned);
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
            ->where(function ($query) {
                if ($this->warehouse_id) {
                    $query->where('stock_reservations.warehouse_id', $this->warehouse_id);
                } else {
                    $query->whereColumn('stock_reservations.warehouse_id', 'stocks.warehouse_id');
                }
            });
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'product_id', 'product_id')
            ->where(function ($query) {
                if ($this->warehouse_id) {
                    $query->where('stock_movements.warehouse_id', $this->warehouse_id);
                } else {
                    $query->whereColumn('stock_movements.warehouse_id', 'stocks.warehouse_id');
                }
            });
    }

    public function pendingOrderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'product_id', 'product_id')
            ->whereHas('order', function ($query) {
                if ($this->warehouse_id) {
                    $query->where('orders.warehouse_id', $this->warehouse_id);
                } else {
                    $query->whereColumn('orders.warehouse_id', 'stocks.warehouse_id');
                }
                $query->where('orders.status', 'pending');
            });
    }

    public function deliveredOrderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'product_id', 'product_id')
            ->whereHas('order', function ($query) {
                if ($this->warehouse_id) {
                    $query->where('orders.warehouse_id', $this->warehouse_id);
                } else {
                    $query->whereColumn('orders.warehouse_id', 'stocks.warehouse_id');
                }
                $query->whereIn('orders.status', ['delivered', 'completed']);
            });
    }

    public function returnedOrderItems(): HasMany
    {
        return $this->hasMany(OrderReturnItem::class, 'product_id', 'product_id')
            ->whereHas('orderReturn', function ($query) {
                $query->where('status', 'completed')
                    ->whereHas('order', function ($q) {
                        if ($this->warehouse_id) {
                            $q->where('orders.warehouse_id', $this->warehouse_id);
                        } else {
                            $q->whereColumn('orders.warehouse_id', 'stocks.warehouse_id');
                        }
                        $q->whereIn('orders.status', ['delivered', 'completed']);
                    });
            });
    }

    public function returnRequestedOrderItems(): HasMany
    {
        return $this->hasMany(OrderReturnItem::class, 'product_id', 'product_id')
            ->whereHas('orderReturn', function ($query) {
                $query->whereIn('status', ['pending', 'received', 'qc_in_progress'])
                    ->whereHas('order', function ($q) {
                        if ($this->warehouse_id) {
                            $q->where('orders.warehouse_id', $this->warehouse_id);
                        } else {
                            $q->whereColumn('orders.warehouse_id', 'stocks.warehouse_id');
                        }
                    });
            });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
