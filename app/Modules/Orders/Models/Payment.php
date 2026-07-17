<?php

namespace App\Modules\Orders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Payment extends Model
{
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
    ];

    protected $casts = [
        'payment_date' => 'datetime',
        'amount' => 'decimal:2',
    ];

    protected static function booted()
    {
        $syncInvoice = function ($payment) {
            if ($payment->invoice_id) {
                $invoice = $payment->invoice;
                if ($invoice) {
                    $paid = $invoice->payments()->whereIn('status', ['completed', 'captured'])->sum('amount');
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
            if (!Schema::hasTable('accounting_transactions')) {
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
                        'transaction_no' => 'ACC-' . strtoupper(Str::random(8)),
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
            if (!Schema::hasTable('accounting_transactions')) {
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
}
