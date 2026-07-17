<?php

declare(strict_types=1);

namespace App\Services;

use App\Modules\Orders\Models\Invoice;
use App\Modules\Orders\Models\OrderReturn;
use App\Modules\Orders\Models\Payment;
use App\Modules\Orders\Models\Refund;
use App\Modules\Orders\Models\CreditNote;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class FinancialService
{
    /**
     * Process a payment for an invoice.
     */
    public function processPayment(Invoice $invoice, float $amount, string $method, ?string $transactionId = null, ?string $paymentDate = null): Payment
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Payment amount must be greater than zero.']);
        }

        if ($amount > $invoice->due_amount) {
            throw ValidationException::withMessages(['amount' => 'Payment amount cannot exceed the due amount.']);
        }

        return DB::transaction(function () use ($invoice, $amount, $method, $transactionId, $paymentDate) {
            $order = $invoice->order;
            $baseNo = str_replace('ORD-', 'PAY-', $order->order_no);
            if ($baseNo === $order->order_no) {
                $baseNo = 'PAY-' . $order->order_no;
            }
            $count = Payment::where('order_id', $order->id)->count();
            $paymentNo = $count > 0 ? $baseNo . '-' . ($count + 1) : $baseNo;

            $payment = Payment::create([
                'payment_no' => $paymentNo,
                'invoice_id' => $invoice->id,
                'order_id' => $invoice->order_id,
                'amount' => $amount,
                'payment_method' => $method,
                'transaction_id' => $transactionId,
                'payment_date' => $paymentDate ? \Carbon\Carbon::parse($paymentDate) : now(),
                'status' => 'captured',
            ]);

            return $payment;
        });
    }

    /**
     * Process a refund for a return.
     */
    public function processRefund(OrderReturn $return, float $amount, string $method, ?string $transactionId = null): Refund
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Refund amount must be greater than zero.']);
        }

        return DB::transaction(function () use ($return, $amount, $method, $transactionId) {
            $order = $return->order;
            $invoice = $order->invoice;

            $baseNo = str_replace('ORD-', 'REF-', $order->order_no);
            if ($baseNo === $order->order_no) {
                $baseNo = 'REF-' . $order->order_no;
            }
            $count = Refund::where('order_id', $order->id)->count();
            $refundNo = $count > 0 ? $baseNo . '-' . ($count + 1) : $baseNo;

            $refund = Refund::create([
                'refund_no' => $refundNo,
                'order_id' => $order->id,
                'invoice_id' => $invoice?->id,
                'order_return_id' => $return->id,
                'amount' => $amount,
                'payment_method' => $method,
                'transaction_id' => $transactionId,
                'status' => 'completed',
            ]);

            $return->refund_amount = (float)$return->refund_amount + $amount;
            
            // Basic financial status logic
            if ($return->refund_amount > 0) {
                $return->financial_status = 'partial_refund';
            }
            
            $return->save();

            return $refund;
        });
    }

    /**
     * Issue a credit note for a return.
     */
    public function issueCreditNote(OrderReturn $return, float $amount): CreditNote
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Credit Note amount must be greater than zero.']);
        }

        return DB::transaction(function () use ($return, $amount) {
            $order = $return->order;
            $invoice = $order->invoice;

            $creditNote = CreditNote::create([
                'customer_id' => $order->party_id,
                'invoice_id' => $invoice?->id,
                'order_return_id' => $return->id,
                'amount' => $amount,
                'balance_remaining' => $amount,
                'status' => 'active',
            ]);

            $return->credit_note_amount = (float)$return->credit_note_amount + $amount;
            $return->financial_status = 'credited';
            $return->save();

            return $creditNote;
        });
    }
}
