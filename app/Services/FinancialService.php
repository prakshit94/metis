<?php

declare(strict_types=1);

namespace App\Services;

use App\Modules\Orders\Models\CreditNote;
use App\Modules\Orders\Models\Invoice;
use App\Modules\Orders\Models\OrderReturn;
use App\Notifications\FinancialAlertNotification;
use Illuminate\Support\Facades\Notification;
use App\Modules\Orders\Models\Payment;
use App\Modules\Orders\Models\Refund;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinancialService
{
    /**
     * Process a payment for an invoice.
     */
    public function processPayment(Invoice $invoice, float $amount, string $method, ?string $transactionId = null, ?string $paymentDate = null): Payment
    {
        return DB::transaction(function () use ($invoice, $amount, $method, $transactionId, $paymentDate) {
            $invoice = Invoice::lockForUpdate()->findOrFail($invoice->id);

            if ($amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'Payment amount must be greater than zero.']);
            }

            if ($amount > $invoice->due_amount) {
                throw ValidationException::withMessages(['amount' => 'Payment amount cannot exceed the due amount.']);
            }
            $order = $invoice->order;
            $baseNo = str_replace('ORD-', 'PAY-', $order->order_no);
            if ($baseNo === $order->order_no) {
                $baseNo = 'PAY-'.$order->order_no;
            }
            $count = Payment::where('order_id', $order->id)->lockForUpdate()->count();
            $paymentNo = $count > 0 ? $baseNo.'-'.($count + 1) : $baseNo;

            $payment = Payment::create([
                'payment_no' => $paymentNo,
                'invoice_id' => $invoice->id,
                'order_id' => $invoice->order_id,
                'amount' => $amount,
                'payment_method' => $method,
                'transaction_id' => $transactionId,
                'payment_date' => $paymentDate ? Carbon::parse($paymentDate) : now(),
                'status' => 'completed',
            ]);

            return $payment;
        }, 3);
    }

    /**
     * Process a refund for a return.
     */
    public function processRefund(OrderReturn $return, float $amount, string $method, ?string $transactionId = null): Refund
    {
        return DB::transaction(function () use ($return, $amount, $method, $transactionId) {
            $return = OrderReturn::with('order')->lockForUpdate()->findOrFail($return->id);

            if ($amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'Refund amount must be greater than zero.']);
            }

            // Prevent over-refunding: total refunded must not exceed the original order amount
            $maxRefundable = (float) ($return->order->net_amount ?? 0) - (float) $return->refund_amount;
            if ($amount > $maxRefundable + 0.001) { // small epsilon for float precision
                throw ValidationException::withMessages([
                    'amount' => 'Refund amount cannot exceed the refundable balance of ₹'.number_format($maxRefundable, 2).'.',
                ]);
            }
            $order = $return->order;
            $invoice = $order->invoice;

            $baseNo = str_replace('ORD-', 'REF-', $order->order_no);
            if ($baseNo === $order->order_no) {
                $baseNo = 'REF-'.$order->order_no;
            }
            $count = Refund::where('order_id', $order->id)->count();
            $refundNo = $count > 0 ? $baseNo.'-'.($count + 1) : $baseNo;

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

            $return->refund_amount = (float) $return->refund_amount + $amount;

            // Basic financial status logic
            if ($return->refund_amount > 0) {
                $return->financial_status = 'partial_refund';
            }

            $return->save();

            // Dispatch Notification (fail-safe: never break core flow)
            try {
                $admins = \App\Modules\Users\Models\User::role(['Admin', 'Super Admin', 'Finance Admin'])->get();
                Notification::send($admins, new FinancialAlertNotification('refund', (float) $amount, $refund->refund_no));
            } catch (\Throwable) {
                // Silently fail — notification delivery is non-critical
            }

            return $refund;
        }, 3);
    }

    /**
     * Issue a credit note for a return.
     */
    public function issueCreditNote(OrderReturn $return, float $amount): CreditNote
    {
        return DB::transaction(function () use ($return, $amount) {
            $return = OrderReturn::with('order')->lockForUpdate()->findOrFail($return->id);

            if ($amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'Credit Note amount must be greater than zero.']);
            }

            // Prevent over-crediting: total credit notes must not exceed the original order amount
            $order = $return->order;
            $maxCreditable = (float) ($order->net_amount ?? 0) - (float) $return->credit_note_amount;
            if ($amount > $maxCreditable + 0.001) {
                throw ValidationException::withMessages([
                    'amount' => 'Credit note amount cannot exceed the remaining creditable balance of ₹'.number_format($maxCreditable, 2).'.',
                ]);
            }

            $invoice = $order->invoice;

            $creditNote = CreditNote::create([
                'customer_id' => $order->party_id,
                'invoice_id' => $invoice?->id,
                'order_return_id' => $return->id,
                'amount' => $amount,
                'balance_remaining' => $amount,
                'status' => 'active',
            ]);

            $return->credit_note_amount = (float) $return->credit_note_amount + $amount;
            $return->financial_status = 'credited';
            $return->save();

            return $creditNote;
        }, 3);
    }
}
