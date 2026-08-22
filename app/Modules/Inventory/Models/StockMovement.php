<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Warehouse;
use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockMovement extends Model implements Auditable
{
    use AuditableTrait;
    use SoftDeletes;


    protected $fillable = [
        'product_id',
        'warehouse_id',
        'reference_type',
        'reference_id',
        'quantity',
        'type',
        'status',
        'performed_by',
        'batch_number',
    ];

    protected $casts = [
        'quantity' => 'float',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function reference()
    {
        return $this->morphTo();
    }

    public function getReferenceLabelAttribute(): string
    {
        if (empty($this->reference_type)) {
            return 'Manual Entry';
        }
        $base = class_basename($this->reference_type);
        // Add spaces before uppercase letters for better readability (e.g., OrderReturn -> Order Return)
        return trim(preg_replace('/(?<!^)([A-Z])/', ' $1', $base));
    }

    public function getReferenceNumberAttribute(): string
    {
        if (!$this->relationLoaded('reference') || !$this->reference) {
            return (string) $this->reference_id;
        }

        return $this->reference->order_no 
            ?? $this->reference->reference_no 
            ?? $this->reference->po_number 
            ?? $this->reference->receipt_no 
            ?? (string) $this->reference->id;
    }
}
