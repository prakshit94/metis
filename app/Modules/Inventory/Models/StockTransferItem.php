<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

use App\Modules\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class StockTransferItem extends Model implements Auditable
{
    use AuditableTrait;
    protected $fillable = [
        'stock_transfer_id',
        'product_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'float',
    ];

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class, 'stock_transfer_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
