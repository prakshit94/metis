<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Modules\Catalog\Models\Warehouse;
use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class PurchaseOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'po_number',
        'supplier_id',
        'warehouse_id',
        'status',
        'expected_delivery_date',
        'total_amount',
        'tax_amount',
        'discount_amount',
        'net_amount',
        'notes',
        'created_by',
        'updated_by',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'invoice_path',
    ];

    protected $casts = [
        'expected_delivery_date' => 'date',
        'total_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    protected $appends = ['invoice_url'];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getInvoiceUrlAttribute(): ?string
    {
        return $this->invoice_path ? Storage::url($this->invoice_path) : null;
    }
}
