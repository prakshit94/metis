<?php

namespace App\Modules\Orders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Invoice extends Model implements Auditable
{
    use LogsActivity;
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

    /** Read-only payment/refund status; never changes the stored invoice state. */
    public function scopeWithPaymentStatus($query)
    {
        return $query->selectRaw($this->paymentStatusExpression().' as payment_status');
    }

    public function scopeWherePaymentStatusIn($query, array $statuses)
    {
        return $query->whereIn(DB::raw($this->paymentStatusExpression()), $statuses);
    }

    private function paymentStatusExpression(): string
    {
        $paid = "(SELECT COALESCE(SUM(payments.amount), 0) FROM payments WHERE payments.invoice_id = invoices.id AND payments.status = 'completed' AND payments.deleted_at IS NULL)";

        $refunded = "(SELECT COALESCE(SUM(refunds.amount), 0) FROM refunds WHERE refunds.invoice_id = invoices.id AND refunds.status = 'completed')";

        // Cancelled invoices remain cancelled; returns alone do not reverse payments.
        return "CASE WHEN invoices.status = 'cancelled' THEN 'cancelled' "
            ."WHEN {$refunded} > 0 AND {$refunded} >= {$paid} THEN 'refunded' "
            ."WHEN {$refunded} > 0 THEN 'partially_refunded' "
            ."WHEN {$paid} >= invoices.net_amount THEN 'paid' "
            ."WHEN {$paid} > 0 THEN 'partially_paid' ELSE 'unpaid' END";
    }

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
        if ($this->relationLoaded('payments')) {
            return $this->payments->where('status', 'completed')->sum('amount');
        }

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
        if (array_key_exists('refunded_amount_total', $this->attributes)) {
            return $this->attributes['refunded_amount_total'] ?? 0;
        }

        if ($this->relationLoaded('refunds')) {
            return $this->refunds->where('status', 'completed')->sum('amount');
        }

        return $this->refunds()->where('status', 'completed')->sum('amount');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
