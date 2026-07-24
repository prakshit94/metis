<?php

namespace App\Modules\Orders\Models;

use App\Modules\Customers\Models\Party;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditNote extends Model
{
    protected $fillable = [
        'customer_id',
        'invoice_id',
        'order_return_id',
        'amount',
        'balance_remaining',
        'status',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'customer_id');
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
