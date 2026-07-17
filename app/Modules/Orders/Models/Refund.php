<?php

namespace App\Modules\Orders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
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
                $refund->refund_no = 'REF-' . strtoupper(\Illuminate\Support\Str::random(8));
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
