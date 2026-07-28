<?php

namespace App\Modules\Orders\Models;

use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;


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
}
