<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Modules\Inventory\Models\Stock;
use App\Modules\Inventory\Models\StockReservation;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Orders\Models\OrderItem;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'sku',
        'slug',
        'category_id',
        'brand_id',
        'tax_rate_id',
        'hsn_code_id',
        'uom_id',
        'default_warehouse_id',
        'barcode',
        'image_path',
        'weight',
        'purchase_price',
        'mrp',
        'selling_price',
        'default_discount',
        'default_discount_type',
        'min_stock_level',
        'batch_tracking',
        'expiry_tracking',
        'allow_overselling',
        'overselling_qty',
        'manage_stock',
        'is_sku_enabled',
        'is_active',
        'application_instructions',
        'status',
        'grade',
        'description',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'mrp' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'default_discount' => 'decimal:2',
        'min_stock_level' => 'integer',
        'overselling_qty' => 'integer',
        'batch_tracking' => 'boolean',
        'expiry_tracking' => 'boolean',
        'allow_overselling' => 'boolean',
        'manage_stock' => 'boolean',
        'is_sku_enabled' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }

    public function hsnCode(): BelongsTo
    {
        return $this->belongsTo(HsnCode::class);
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'uom_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'default_warehouse_id');
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductAttributeValue::class,
            'product_attribute_mapping',
            'product_id',
            'attribute_value_id',
        );
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function stockReservations(): HasMany
    {
        return $this->hasMany(StockReservation::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function pendingOrderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class)
            ->whereHas('order', function ($query) {
                $query->where('status', 'pending');
            });
    }

    // ─── Computed Attributes ───────────────────────────────────────────────

    /**
     * Total physical on-hand qty across all warehouses.
     */
    public function getTotalStockAttribute(): float
    {
        return (float) $this->stocks()->sum('quantity');
    }

    /**
     * Alias for total_stock to maintain backwards compatibility 
     * with APIs/frontend expecting stock_quantity.
     */
    public function getStockQuantityAttribute(): float
    {
        return $this->total_stock;
    }

    /**
     * Total reserved qty across all warehouses (held for confirmed orders).
     */
    public function getTotalReservedAttribute(): float
    {
        return (float) $this->stocks()->sum('reserved_qty');
    }

    /**
     * Running dispatch total across all warehouses.
     */
    public function getTotalDispatchedAttribute(): float
    {
        return (float) $this->stocks()->sum('dispatched_qty');
    }

    /**
     * Net available = total_stock − total_reserved.
     */
    public function getAvailableStockAttribute(): float
    {
        return max(0.0, $this->total_stock - $this->total_reserved);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? asset('storage/' . $this->image_path) : null;
    }
}
