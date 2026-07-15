<?php

namespace App\Services;

use App\Modules\Orders\Models\Invoice;
use App\Modules\Orders\Models\Order;
use Illuminate\Support\Str;

/**
 * InvoiceService – SINGLE SOURCE OF TRUTH for invoice creation.
 *
 * All controllers must use generateForOrder() instead of creating
 * Invoice records directly.
 */
class InvoiceService
{
    /**
     * Get or create the invoice for an order.
     * Idempotent: will return existing invoice if one already exists.
     */
    public function generateForOrder(Order $order): Invoice
    {
        $existing = $order->invoices()->latest()->first();

        if ($existing) {
            return $existing;
        }

        $invoiceNo = 'INV-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));

        $invoice = Invoice::create([
            'invoice_no'    => $invoiceNo,
            'order_id'      => $order->id,
            'invoice_date'  => now(),
            'total_amount'  => $order->total_amount,
            'tax_amount'    => $order->tax_amount,
            'net_amount'    => $order->net_amount,
            'status'        => 'unpaid',
        ]);

        return $invoice;
    }

    /**
     * Get an existing invoice or throw 404.
     */
    public function findForOrder(Order $order): ?Invoice
    {
        return $order->invoices()->latest()->first();
    }
}
