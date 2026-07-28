<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

use App\Modules\Catalog\Models\Warehouse;
use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class InventoryAdjustment extends Model implements Auditable
{
    use AuditableTrait;
    protected $fillable = [
        'reference_no',
        'warehouse_id',
        'adjusted_by',
        'reason',
        'status',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryAdjustmentItem::class, 'adjustment_id');
    }
}
