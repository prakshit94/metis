<?php

namespace App\Modules\Orders\Models;

use App\Modules\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class OrderReturnItem extends Model implements Auditable
{
    use AuditableTrait;

    protected $fillable = [
        'order_return_id',
        'product_id',
        'requested_qty',
        'received_qty',
        'restocked_qty',
        'damaged_qty',
        'qc_status',
        'qc_notes',
    ];

    public function orderReturn(): BelongsTo
    {
        return $this->belongsTo(OrderReturn::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
