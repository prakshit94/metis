<?php

declare(strict_types=1);

namespace App\Services;

use App\Modules\Orders\Models\CreditNote;
use App\Modules\Orders\Models\Invoice;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderReturn;
use App\Modules\Orders\Models\Payment;
use App\Modules\Orders\Models\Refund;
use App\Notifications\FinancialAlertNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
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

            if ($invoice->status === 'cancelled') {
                throw ValidationException::withMessages(['invoice' => 'Cannot record payment for a cancelled invoice.']);
            }

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
            $count = Payment::withTrashed()->where('order_id', $order->id)->lockForUpdate()->count();
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
                'recorded_by' => auth()->id(),
                'recorded_at' => now(),
            ]);

            return $payment;
        }, 3);
    }

    /**
     * Revert a mistakenly recorded payment.
     */
    public function revertPayment(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            if ($payment->invoice_id) {
                Invoice::whereKey($payment->invoice_id)->lockForUpdate()->first();
            }
            $payment = Payment::lockForUpdate()->findOrFail($payment->id);
            $payment->update([
                'reverted_by' => auth()->id(),
                'reverted_at' => now(),
            ]);

            $payment->delete();
        });
    }

    public function amendPayment(Payment $payment, array $attributes): Payment
    {
        return DB::transaction(function () use ($payment, $attributes) {
            $invoice = $payment->invoice_id ? Invoice::lockForUpdate()->findOrFail($payment->invoice_id) : null;
            $payment = Payment::lockForUpdate()->findOrFail($payment->id);
            $status = $attributes['status'] ?? $payment->status;
            if ($payment->status === 'refunded' && $attributes !== [] && ($status !== 'refunded' || array_diff_assoc($attributes, $payment->only(array_keys($attributes))))) {
                throw ValidationException::withMessages(['status' => 'Refunded payments cannot be modified.']);
            }
            if ($status === 'refunded' && ! in_array($payment->status, ['completed', 'refunded'], true)) {
                throw ValidationException::withMessages(['status' => 'Only completed payments can be refunded.']);
            }
            if ($status === 'completed' && $invoice) {
                if ($invoice->status === 'cancelled') {
                    throw ValidationException::withMessages(['invoice' => 'The invoice is cancelled.']);
                }
                $otherPaid = $invoice->payments()->whereKeyNot($payment->id)->where('status', 'completed')->sum('amount');
                if ((float) ($attributes['amount'] ?? $payment->amount) > max(0, (float) $invoice->net_amount - (float) $otherPaid) + 0.001) {
                    throw ValidationException::withMessages(['amount' => 'Payment amount cannot exceed the remaining invoice balance.']);
                }
            }
            $payment->update($attributes);

            return $payment;
        }, 3);
    }

    /** All compensation channels consume the same locked order entitlement. */
    public function remainingCompensation(OrderReturn $return): float
    {
        $order = Order::lockForUpdate()->findOrFail($return->order_id);
        $return = OrderReturn::lockForUpdate()->findOrFail($return->id);
        if ($return->status !== 'completed') {
            throw ValidationException::withMessages(['return' => 'Complete quality check before financial settlement.']);
        }
        $refunds = Refund::where('order_id', $order->id)->whereIn('status', ['pending', 'completed'])->sum('amount');
        $credits = CreditNote::where('status', '!=', 'cancelled')
            ->where(function ($q) use ($order) {
                $q->whereHas('invoice', fn ($q) => $q->where('order_id', $order->id))
                    ->orWhereHas('orderReturn', fn ($q) => $q->where('order_id', $order->id));
            })->whereNotExists(function ($q) {
                $q->selectRaw('1')->from('refunds')->whereColumn('refunds.credit_note_id', 'credit_notes.id')->whereIn('refunds.status', ['pending', 'completed']);
            })->sum('amount');
        $returnValue = $this->returnValue($return);
        $usedOnReturn = Refund::where('order_return_id', $return->id)->whereIn('status', ['pending', 'completed'])->sum('amount')
            + CreditNote::where('order_return_id', $return->id)->where('status', '!=', 'cancelled')
                ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('refunds')->whereColumn('refunds.credit_note_id', 'credit_notes.id')->whereIn('refunds.status', ['pending', 'completed']))->sum('amount');

        return max(0, min((float) $order->net_amount - $refunds - $credits, $returnValue - $usedOnReturn));
    }

    public function returnValue(OrderReturn $return): float
    {
        $lines = $return->order->items()->get()->groupBy('product_id');

        return (float) $return->items()->get()->sum(function ($item) use ($lines) {
            $sold = $lines->get($item->product_id, collect());
            $quantity = (float) $sold->sum('quantity');

            return $quantity > 0 ? (float) $sold->sum('total_amount') / $quantity * (float) $item->received_qty : 0;
        });
    }

    public function syncReturnFinancials(OrderReturn $return): void
    {
        $refunded = (float) $return->refunds()->where('status', 'completed')->whereNull('credit_note_id')->sum('amount');
        $credited = (float) CreditNote::where('order_return_id', $return->id)->where('status', '!=', 'cancelled')->sum('amount');
        $value = $this->returnValue($return);
        $return->update(['refund_amount' => $refunded, 'credit_note_amount' => $credited,
            'financial_status' => $credited > 0 ? 'credited' : ($refunded <= 0 ? 'pending' : ($refunded + 0.001 >= $value ? 'fully_refunded' : 'partial_refund'))]);
    }

    public function changeRefundStatus(Refund $refund, string $status): void
    {
        DB::transaction(function () use ($refund, $status) {
            Order::lockForUpdate()->findOrFail($refund->order_id);
            $refund = Refund::lockForUpdate()->findOrFail($refund->id);
            if ($refund->credit_note_id && $refund->status !== $status) {
                throw ValidationException::withMessages(['status' => 'A credit-note settlement cannot be changed through refund status.']);
            }
            if ($status === 'completed' && ! $refund->credit_note_id) {
                $received = (float) $refund->order->payments()->where('status', 'completed')->sum('amount');
                $other = (float) Refund::where('order_id', $refund->order_id)->whereKeyNot($refund->id)->whereIn('status', ['pending', 'completed'])->sum('amount');
                if ((float) $refund->amount > max(0, $received - $other) + 0.001) {
                    throw ValidationException::withMessages(['amount' => 'Refund exceeds the remaining cash received.']);
                }
            }
            // Reopening a failed obligation must pass the same shared cap.
            if ($refund->status === 'failed' && $status !== 'failed' && $refund->orderReturn) {
                if ((float) $refund->amount > $this->remainingCompensation($refund->orderReturn) + 0.001) {
                    throw ValidationException::withMessages(['amount' => 'Refund exceeds the remaining refundable balance.']);
                }
            }
            $refund->update(['status' => $status, 'processed_by' => $status === 'completed' ? auth()->id() : null,
                'processed_at' => $status === 'completed' ? now() : null]);
            if ($refund->orderReturn) {
                $this->syncReturnFinancials($refund->orderReturn);
            }
        }, 3);
    }

    /**
     * Process a refund for a return.
     */
    public function processRefund(OrderReturn $return, float $amount, string $method, ?string $transactionId = null): Refund
    {
        return DB::transaction(function () use ($return, $amount, $method, $transactionId) {
            Order::lockForUpdate()->findOrFail($return->order_id);
            $return = OrderReturn::with('order')->lockForUpdate()->findOrFail($return->id);

            if ($amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'Refund amount must be greater than zero.']);
            }

            $pending = Refund::where('order_return_id', $return->id)->where('status', 'pending')->whereNull('credit_note_id')->orderBy('id')->lockForUpdate()->first();
            if ($pending) {
                if (abs((float) $pending->amount - $amount) > 0.001) {
                    throw ValidationException::withMessages(['amount' => 'Settle the existing pending refund amount before creating another obligation.']);
                }
                $cashReceived = (float) $return->order->payments()->where('status', 'completed')->sum('amount');
                $otherRefunds = (float) Refund::where('order_id', $return->order_id)->whereKeyNot($pending->id)->whereIn('status', ['pending', 'completed'])->sum('amount');
                if ($amount > max(0, $cashReceived - $otherRefunds) + 0.001) {
                    throw ValidationException::withMessages(['amount' => 'Refund exceeds the remaining cash received.']);
                }
                $pending->update(['payment_method' => $method, 'transaction_id' => $transactionId]);
                $this->changeRefundStatus($pending, 'completed');

                return $pending->refresh();
            }

            // Prevent over-refunding: total refunded must not exceed the original order amount
            $maxRefundable = min($this->remainingCompensation($return), max(0, (float) $return->order->payments()->where('status', 'completed')->sum('amount') - (float) Refund::where('order_id', $return->order_id)->whereIn('status', ['pending', 'completed'])->sum('amount')));
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
                'processed_by' => auth()->id(),
                'processed_at' => now(),
            ]);

            $this->syncReturnFinancials($return);

            // Dispatch Notification (fail-safe: never break core flow)
            try {
                $admins = app(NotificationRecipients::class)->forRoles(['Admin', 'Super Admin', 'Finance Admin']);
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
            Order::lockForUpdate()->findOrFail($return->order_id);
            $return = OrderReturn::with('order')->lockForUpdate()->findOrFail($return->id);

            if ($amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'Credit Note amount must be greater than zero.']);
            }

            $pending = Refund::where('order_return_id', $return->id)->where('status', 'pending')->whereNull('credit_note_id')->orderBy('id')->lockForUpdate()->first();
            if ($pending && abs((float) $pending->amount - $amount) > 0.001) {
                throw ValidationException::withMessages(['amount' => 'The credit note must match the pending refund being converted.']);
            }
            // Prevent over-crediting: total credit notes must not exceed the original order amount
            $order = $return->order;
            $maxCreditable = $this->remainingCompensation($return) + (float) ($pending?->amount ?? 0);
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

            if ($pending) {
                $pending->update(['credit_note_id' => $creditNote->id, 'status' => 'completed', 'processed_by' => auth()->id(), 'processed_at' => now()]);
            }
            $this->syncReturnFinancials($return);

            return $creditNote;
        }, 3);
    }
}
