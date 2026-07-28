<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

use App\Modules\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class InventoryAdjustmentItem extends Model implements Auditable
{
    use AuditableTrait;
    protected $fillable = [
        'adjustment_id',
        'product_id',
        'current_qty',
        'new_qty',
        'difference',
    ];

    protected $casts = [
        'current_qty' => 'float',
        'new_qty' => 'float',
        'difference' => 'float',
    ];

    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(InventoryAdjustment::class, 'adjustment_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
