<?php

namespace App\Modules\Orders\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Modules\Orders\Models\Invoice;
use App\Modules\Orders\Models\Payment;
use App\Services\FinancialService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:orders.view', only: ['index', 'show', 'exportSelected', 'downloadPdf']),
            new Middleware('permission:orders.receipt', only: ['bulkStatus', 'recordPayment']),
        ];
    }

    public function index(Request $request)
    {
        $query = Invoice::with(['order.party', 'payments.recorder']);

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($subQuery) use ($s) {
                $subQuery->where('invoice_no', 'LIKE', "%{$s}%")
                    ->orWhereHas('order', function ($q) use ($s) {
                        $q->where('order_no', 'LIKE', "%{$s}%");
                        if (is_numeric($s)) {
                            $q->orWhere('id', $s);
                        }
                        $q->orWhereHas('party', function ($q2) use ($s) {
                            $q2->where('firstname', 'LIKE', "%{$s}%")
                                ->orWhere('lastname', 'LIKE', "%{$s}%");
                        });
                    });
            });
        }

        // Clone base stats query before paginating or applying status filters
        $baseStatsQuery = clone $query;
        $statsQuery = fn () => clone $baseStatsQuery;

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
            $totalInvoiced = (float) $statsQuery()->sum('net_amount');

            // Subquery prevents packet limit / bindings explosion compared to pluck('id')
            $collectedAmount = (float) Payment::where('status', 'completed')
                ->whereIn('invoice_id', $statsQuery()->select('id'))
                ->sum('amount');

            $pendingAmount = max(0, $totalInvoiced - $collectedAmount);

            $avgValue = (float) $statsQuery()->avg('net_amount');

            $stats = [
                'total_invoiced' => $totalInvoiced,
                'collected_amount' => $collectedAmount,
                'pending_amount' => $pendingAmount,
                'avg_value' => $avgValue ?: 0,
            ];

            return response()->json([
                'invoices' => $invoices,
                'stats' => $stats,
            ]);
        }

        return view('orders.invoices.index', compact('invoices'));
    }

    public function show(Invoice $invoice)
    {
        $invoice->load([
            'order.party',
            'payments' => fn ($query) => $query->with(['recorder', 'reverter'])->orderByDesc('payment_date')->orderByDesc('id'),
        ]);

        return response()->json(['invoice' => $invoice]);
    }

    public function bulkStatus(Request $request, FinancialService $financialService)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:invoices,id',
            'status' => 'required|in:paid,unpaid,cancelled',
        ]);

        if (in_array($validated['status'], ['paid', 'unpaid'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice status is automatically derived from payments. You cannot manually force an invoice to be paid or unpaid without recording an actual payment.',
            ], 422);
        }

        $user = $request->user();

        // Scope: prevent users from updating invoices for orders they don't own
        $query = Invoice::whereIn('id', $validated['ids']);

        $invoices = $query->get();

        if ($validated['status'] === 'cancelled') {
            foreach ($invoices as $invoice) {
                if ($invoice->paid_amount > 0) {
                    return response()->json([
                        'success' => false,
                        'message' => "Cannot cancel Invoice #{$invoice->invoice_no} because it has completed payments. Please refund all associated payments first.",
                    ], 422);
                }
            }
        }

        DB::transaction(function () use ($invoices, $validated) {
            foreach ($invoices as $invoice) {
                $invoice->update(['status' => $validated['status']]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Selected invoices updated successfully.',
        ]);
    }

    public function recordPayment(Request $request, Invoice $invoice, FinancialService $financialService)
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
                (float) $validated['amount'],
                $validated['payment_method'],
                $validated['transaction_id'] ?? null,
                $validated['payment_date'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Payment recorded successfully.',
                'payment' => $payment,
            ]);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to record payment: '.$e->getMessage()], 500);
        }
    }

    public function exportSelected(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:invoices,id',
        ]);

        $user = $request->user();

        $query = Invoice::whereIn('id', $validated['ids'])->with(['order.party', 'payments']);

        $invoices = $query->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="invoices_export.csv"',
        ];

        $columns = [
            'reference_type', 'reference_no', 'amount', 'payment_method', 'transaction_id', 'payment_date',
            'order_no', 'customer_name', 'invoice_date', 'due_date', 'total_amount', 'tax_amount', 'net_amount', 'paid_amount', 'status',
        ];

        $callback = function () use ($columns, $invoices) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($invoices as $invoice) {
                $order = $invoice->order;
                $party = $order ? $order->party : null;
                $customerName = $party ? trim($party->firstname.' '.$party->lastname) : '';

                $latestPayment = $invoice->payments()->latest('payment_date')->first();

                $row = [
                    'invoice',
                    $invoice->invoice_no,
                    number_format($invoice->due_amount, 2, '.', ''),
                    $latestPayment ? $latestPayment->payment_method : '', // payment_method
                    $latestPayment ? $latestPayment->transaction_id : '', // transaction_id
                    $latestPayment && $latestPayment->payment_date ? $latestPayment->payment_date->format('Y-m-d H:i:s') : '', // payment_date
                    $order ? $order->order_no : '',
                    $customerName,
                    $invoice->invoice_date ? $invoice->invoice_date->format('Y-m-d') : '',
                    $invoice->due_date ? $invoice->due_date->format('Y-m-d') : '',
                    number_format($invoice->total_amount, 2, '.', ''),
                    number_format($invoice->tax_amount, 2, '.', ''),
                    number_format($invoice->net_amount, 2, '.', ''),
                    number_format($invoice->paid_amount, 2, '.', ''),
                    $invoice->status,
                ];
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}
