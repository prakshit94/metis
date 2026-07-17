<?php

namespace App\Modules\Orders\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Modules\Orders\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with(['order.party', 'payments']);

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($subQuery) use ($s) {
                $subQuery->where('invoice_no', 'LIKE', "%{$s}%")
                    ->orWhereHas('order', function ($q) use ($s) {
                        $q->where('order_no', 'LIKE', "%{$s}%")
                          ->orWhereHas('party', function ($q2) use ($s) {
                              $q2->where('firstname', 'LIKE', "%{$s}%")
                                 ->orWhere('lastname', 'LIKE', "%{$s}%");
                          });
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $sortField = $request->input('sort_field', 'id');
        $sortDirection = $request->input('sort_direction', 'desc');

        $allowedSorts = ['id', 'invoice_no', 'net_amount', 'due_date', 'created_at'];
        if (in_array($sortField, $allowedSorts, true)) {
            $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('id', 'desc');
        }

        $invoices = $query->paginate($request->integer('limit', 10));

        if ($request->wantsJson() || $request->ajax()) {
            $stats = [
                'total_invoiced' => (float) Invoice::sum('net_amount'),
                'paid' => Invoice::where('status', 'paid')->count(),
                'unpaid' => Invoice::whereIn('status', ['unpaid', 'partially_paid'])->count(),
                'avg_value' => (float) (Invoice::avg('net_amount') ?? 0),
            ];

            return response()->json([
                'invoices' => $invoices,
                'stats' => $stats,
            ]);
        }

        return view('orders.invoices.index', compact('invoices'));
    }

    public function bulkStatus(Request $request, \App\Services\FinancialService $financialService)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:invoices,id',
            'status' => 'required|in:paid,unpaid,cancelled',
        ]);

        $invoices = Invoice::whereIn('id', $validated['ids'])->get();

        foreach ($invoices as $invoice) {
            if ($validated['status'] === 'paid' && $invoice->due_amount > 0) {
                $financialService->processPayment($invoice, $invoice->due_amount, 'bank_transfer', 'BULK-AUTO');
            } else {
                $invoice->update(['status' => $validated['status']]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Selected invoices updated successfully.',
        ]);
    }

    public function recordPayment(Request $request, Invoice $invoice, \App\Services\FinancialService $financialService)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'transaction_id' => 'nullable|string',
            'payment_date' => 'nullable|date',
        ]);

        try {
            $payment = $financialService->processPayment(
                $invoice,
                (float)$validated['amount'],
                $validated['payment_method'],
                $validated['transaction_id'] ?? null,
                $validated['payment_date'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Payment recorded successfully.',
                'payment' => $payment
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to record payment: ' . $e->getMessage()], 500);
        }
    }
}
