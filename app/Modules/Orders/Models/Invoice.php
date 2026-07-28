<?php

namespace App\Modules\Orders\Models;

use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model implements Auditable
{
    use AuditableTrait;
    use SoftDeletes;


    protected $fillable = [
        'invoice_no',
        'order_id',
        'invoice_date',
        'total_amount',
        'tax_amount',
        'net_amount',
        'status',
    ];

    protected $appends = [
        'paid_amount',
        'due_amount',
        'refunded_amount',
    ];

    protected $casts = [
        'invoice_date' => 'datetime',
        'total_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function getPaidAmountAttribute()
    {
        return $this->payments()->where('status', 'completed')->sum('amount');
    }

    public function getDueAmountAttribute()
    {
        return max(0, $this->net_amount - $this->paid_amount);
    }

    public function refunds()
    {
        return $this->hasMany(Refund::class);
    }

    public function creditNotes()
    {
        return $this->hasMany(CreditNote::class);
    }

    public function getRefundedAmountAttribute()
    {
        return $this->refunds()->where('status', 'completed')->sum('amount');
    }
}
