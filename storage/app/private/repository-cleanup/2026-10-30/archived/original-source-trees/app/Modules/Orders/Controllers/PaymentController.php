<?php

namespace App\Modules\Orders\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Modules\Orders\Models\Invoice;
use App\Modules\Orders\Models\Payment;
use App\Services\EnsureRecordVisibility;
use App\Services\FinancialService;
use App\Services\RecordVisibility;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(EnsureRecordVisibility::class),
            new Middleware('permission:payments.view', only: ['index', 'show']),
            new Middleware('permission:payments.export', only: ['exportSelected']),
            new Middleware('permission:payments.create', only: ['store']),
            new Middleware('permission:payments.edit', only: ['update', 'bulkStatus']),
            new Middleware('permission:payments.delete', only: ['destroy']),
        ];
    }

    public function store(Request $request, FinancialService $financialService)
    {
        $validated = $request->validate([
            'invoice_id' => 'required_without:order_id|nullable|integer|exists:invoices,id',
            'order_id' => 'required_without:invoice_id|nullable|integer|exists:orders,id',
            'amount' => 'required|numeric|gt:0', 'payment_method' => 'required|string|max:255',
            'transaction_id' => 'nullable|string|max:255', 'payment_date' => 'nullable|date',
        ]);
        $invoice = isset($validated['invoice_id']) ? Invoice::findOrFail($validated['invoice_id'])
            : Invoice::where('order_id', $validated['order_id'])->latest('id')->firstOrFail();
        if (isset($validated['order_id']) && (int) $invoice->order_id !== (int) $validated['order_id']) {
            throw ValidationException::withMessages(['invoice_id' => 'The invoice does not belong to this order.']);
        }
        $payment = $financialService->processPayment($invoice, (float) $validated['amount'], $validated['payment_method'], $validated['transaction_id'] ?? null, $validated['payment_date'] ?? null);

        return response()->json(['success' => true, 'payment' => $payment], 201);
    }

    public function index(Request $request)
    {
        $query = Payment::withTrashed()->with(['order.party', 'order.orderReturns', 'order.shipments', 'invoice.payments', 'invoice.refunds', 'recorder']);
        app(RecordVisibility::class)->financial($query);

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($subQuery) use ($s) {
                $subQuery->where('payment_no', 'LIKE', "%{$s}%")
                    ->orWhere('transaction_id', 'LIKE', "%{$s}%")
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
            if ($request->status === 'reverted') {
                $query->onlyTrashed();
            } else {
                $query->where('status', $request->status)->whereNull('deleted_at');
            }
        }

        $sortField = $request->input('sort_field', 'id');
        $sortDirection = $request->input('sort_direction', 'desc');

        $allowedSorts = ['id', 'payment_no', 'amount', 'payment_date', 'payment_method', 'status'];
        if (in_array($sortField, $allowedSorts, true)) {
            $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('id', 'desc');
        }

        $payments = $query->paginate(min(max($request->integer('limit', 10), 1), 200));

        $payments->getCollection()->each(fn ($payment) => $payment->order?->makeHidden(['orderReturns', 'shipments']));

        if ($request->wantsJson() || $request->ajax()) {
            $amounts = $statsQuery()->whereNull('deleted_at')->toBase()->selectRaw("
                COALESCE(SUM(amount), 0) as total_volume,
                COALESCE(SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END), 0) as completed_amount,
                COALESCE(SUM(CASE WHEN status IN ('authorized', 'pending') THEN amount ELSE 0 END), 0) as authorized_amount,
                COALESCE(SUM(CASE WHEN status = 'failed' THEN amount ELSE 0 END), 0) as failed_amount
            ")->first();
            $stats = [
                'total_volume' => (float) $amounts->total_volume,
                'completed_amount' => (float) $amounts->completed_amount,
                'authorized_amount' => (float) $amounts->authorized_amount,
                'failed_amount' => (float) $amounts->failed_amount,
            ];

            return response()->json([
                'payments' => $payments,
                'stats' => $stats,
            ]);
        }

        return view('orders.payments.index', compact('payments'));
    }

    public function show(Payment $payment)
    {
        $payment->load([
            'order.party',
            'invoice',
            'recorder',
            'reverter',
            'invoice.payments' => fn ($query) => $query->with(['recorder', 'reverter'])->orderByDesc('payment_date')->orderByDesc('id'),
            'order.payments' => fn ($query) => $query->with(['recorder', 'reverter'])->orderByDesc('payment_date')->orderByDesc('id'),
        ]);

        $history = $payment->invoice
            ? $payment->invoice->payments
            : ($payment->order?->payments ?? collect());

        return response()->json([
            'payment' => $payment,
            'payment_history' => $history,
        ]);
    }

    public function bulkStatus(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:payments,id',
            'status' => 'required|in:pending,authorized,completed,failed,refunded',
        ]);

        DB::transaction(function () use ($validated) {
            $payments = Payment::whereIn('id', $validated['ids'])->orderBy('invoice_id')->orderBy('id')->get();
            foreach ($payments as $payment) {
                app(FinancialService::class)->amendPayment($payment, ['status' => $validated['status']]);
            }
        }, 3);

        return response()->json([
            'success' => true,
            'message' => 'Selected payments updated successfully.',
        ]);
    }

    public function update(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'transaction_id' => 'nullable|string',
            'status' => 'required|in:pending,authorized,completed,failed,refunded',
            'payment_date' => 'required|date',
        ]);

        $payment = app(FinancialService::class)->amendPayment($payment, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Payment updated successfully.',
            'payment' => $payment,
        ]);
    }

    public function exportSelected(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:payments,id',
        ]);

        $payments = Payment::whereIn('id', $validated['ids'])
            ->with(['invoice.order.party', 'order.party'])
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="payments_export.csv"',
        ];

        $columns = [
            'payment_no', 'reference_type', 'reference_no', 'amount', 'payment_method', 'transaction_id', 'payment_date', 'status',
            'customer_name',
        ];

        $callback = function () use ($columns, $payments) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($payments as $payment) {
                $refType = $payment->invoice_id ? 'invoice' : ($payment->order_id ? 'order' : '');
                $refNo = $payment->invoice ? $payment->invoice->invoice_no : ($payment->order ? $payment->order->order_no : '');

                $party = null;
                if ($payment->invoice && $payment->invoice->order) {
                    $party = $payment->invoice->order->party;
                } elseif ($payment->order) {
                    $party = $payment->order->party;
                }

                $customerName = $party ? trim($party->firstname.' '.$party->lastname) : '';

                $row = [
                    $payment->payment_no,
                    $refType,
                    $refNo,
                    number_format($payment->amount, 2, '.', ''),
                    $payment->payment_method,
                    $payment->transaction_id,
                    $payment->payment_date ? $payment->payment_date->format('Y-m-d H:i:s') : '',
                    $payment->status,
                    $customerName,
                ];
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    public function destroy(Payment $payment, FinancialService $financialService)
    {
        try {
            $financialService->revertPayment($payment);

            return response()->json([
                'success' => true,
                'message' => 'Payment reverted successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to revert payment: '.$e->getMessage(),
            ], 500);
        }
    }
}
