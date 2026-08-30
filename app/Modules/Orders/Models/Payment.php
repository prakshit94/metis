<?php

namespace App\Modules\Orders\Models;

use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Payment extends Model implements Auditable
{
    use LogsActivity;
    use AuditableTrait;
    use SoftDeletes;

    protected $fillable = [
        'payment_no',
        'invoice_id',
        'order_id',
        'amount',
        'payment_method',
        'transaction_id',
        'payment_date',
        'status',
        'recorded_by',
        'recorded_at',
        'reverted_by',
        'reverted_at',
    ];

    protected $casts = [
        'payment_date' => 'datetime',
        'recorded_at' => 'datetime',
        'reverted_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function reverter()
    {
        return $this->belongsTo(User::class, 'reverted_by');
    }

    protected static function booted()
    {
        $syncInvoice = function ($payment) {
            if ($payment->invoice_id) {
                $invoice = $payment->invoice;
                if ($invoice) {
                    $paid = $invoice->payments()->where('status', 'completed')->sum('amount');
                    if ($paid >= $invoice->net_amount) {
                        $invoice->update(['status' => 'paid']);
                    } elseif ($paid > 0) {
                        $invoice->update(['status' => 'partially_paid']);
                    } else {
                        $invoice->update(['status' => 'unpaid']);
                    }
                }
            }
        };

        $syncAccounting = function ($payment) {
            if (! Schema::hasTable('accounting_transactions')) {
                return;
            }

            $existingTxn = DB::table('accounting_transactions')->where('reference_no', $payment->id)->first();
            if ($existingTxn) {
                DB::table('accounting_entries')->where('transaction_id', $existingTxn->id)->delete();
                DB::table('accounting_transactions')->where('id', $existingTxn->id)->delete();
            }

            if ($payment->status === 'completed') {
                $cashLedger = DB::table('ledgers')->where('code', 'CASH-001')->first();
                $salesLedger = DB::table('ledgers')->where('code', 'SALES-001')->first();

                if ($cashLedger && $salesLedger) {
                    $order = $payment->order;
                    $orderNo = $order ? $order->order_no : 'Unknown';

                    $txnId = DB::table('accounting_transactions')->insertGetId([
                        'transaction_no' => 'ACC-'.strtoupper(Str::random(8)),
                        'reference_no' => $payment->id,
                        'transaction_date' => now(),
                        'description' => "Payment received for Order #{$orderNo}",
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('accounting_entries')->insert([
                        ['transaction_id' => $txnId, 'ledger_id' => $cashLedger->id, 'debit' => $payment->amount, 'credit' => 0, 'created_at' => now(), 'updated_at' => now()],
                        ['transaction_id' => $txnId, 'ledger_id' => $salesLedger->id, 'debit' => 0, 'credit' => $payment->amount, 'created_at' => now(), 'updated_at' => now()],
                    ]);
                }
            }
        };

        $deleteAccounting = function ($payment) {
            if (! Schema::hasTable('accounting_transactions')) {
                return;
            }

            $existingTxn = DB::table('accounting_transactions')->where('reference_no', $payment->id)->first();
            if ($existingTxn) {
                DB::table('accounting_entries')->where('transaction_id', $existingTxn->id)->delete();
                DB::table('accounting_transactions')->where('id', $existingTxn->id)->delete();
            }
        };

        static::saved($syncInvoice);
        static::saved($syncAccounting);
        static::deleted($syncInvoice);
        static::deleted($deleteAccounting);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
