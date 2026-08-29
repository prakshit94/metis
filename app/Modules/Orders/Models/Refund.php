<?php

namespace App\Modules\Orders\Models;

use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class Refund extends Model implements Auditable
{
    use AuditableTrait;

    protected $fillable = [
        'refund_no',
        'order_id',
        'invoice_id',
        'order_return_id',
        'amount',
        'payment_method',
        'transaction_id',
        'status',
        'notes',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($refund) {
            if (empty($refund->refund_no)) {
                $refund->refund_no = 'REF-'.strtoupper(Str::random(8));
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function orderReturn(): BelongsTo
    {
        return $this->belongsTo(OrderReturn::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
