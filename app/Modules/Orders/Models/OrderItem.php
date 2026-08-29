<?php

declare(strict_types=1);

namespace App\Modules\Orders\Models;

use App\Modules\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class OrderItem extends Model implements Auditable
{
    use AuditableTrait;

    protected $fillable = [
        'order_id', 'product_id', 'product_variant_id', 'quantity', 'unit_price',
        'tax_rate', 'tax_amount', 'discount_amount', 'total_amount', 'batch_number',
    ];

    protected $casts = [
        'quantity' => 'float',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
