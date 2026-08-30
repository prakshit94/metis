<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Modules\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class PurchaseOrderItem extends Model
 implements Auditable{
    use AuditableTrait;

    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'quantity',
        'received_qty',
        'unit_price',
        'tax_rate',
        'tax_amount',
        'discount_amount',
        'total_price',
        'net_amount',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'received_qty' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_price' => 'decimal:2',
        'net_amount' => 'decimal:2',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
