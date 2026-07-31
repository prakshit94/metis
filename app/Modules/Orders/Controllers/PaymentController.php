<?php

namespace App\Modules\Orders\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Modules\Orders\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class PaymentController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:orders.view', only: ['index', 'show', 'exportSelected']),
            new Middleware('permission:orders.receipt', only: ['store', 'update', 'bulkStatus']),
        ];
    }

    public function index(Request $request)
    {
        $query = Payment::withTrashed()->with(['order.party', 'invoice.payments', 'invoice.refunds', 'recorder']);

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

        $payments = $query->paginate($request->integer('limit', 10));

        if ($request->wantsJson() || $request->ajax()) {
            $stats = [
                'total_volume'       => (float) $statsQuery()->whereNull('deleted_at')->sum('amount'),
                'completed_amount'   => (float) $statsQuery()->where('status', 'completed')->whereNull('deleted_at')->sum('amount'),
                'authorized_amount'  => (float) $statsQuery()->whereIn('status', ['authorized', 'pending'])->whereNull('deleted_at')->sum('amount'),
                'failed_amount'      => (float) $statsQuery()->where('status', 'failed')->whereNull('deleted_at')->sum('amount'),
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
            'ids'    => 'required|array',
            'ids.*'  => 'exists:payments,id',
            'status' => 'required|in:pending,authorized,completed,failed,refunded',
        ]);

        DB::transaction(function () use ($validated, &$errorResponse) {
            $payments = Payment::whereIn('id', $validated['ids'])->get();
            foreach ($payments as $payment) {
                if ($payment->status === 'refunded' && $validated['status'] !== 'refunded') {
                    $errorResponse = response()->json([
                        'success' => false, 
                        'message' => "Payment #{$payment->payment_no} has already been refunded and cannot be modified."
                    ], 422);
                    return false; // Break transaction
                }
                if ($validated['status'] === 'refunded' && $payment->status !== 'completed') {
                    $errorResponse = response()->json([
                        'success' => false, 
                        'message' => "Payment #{$payment->payment_no} cannot be refunded because it is not completed."
                    ], 422);
                    return false; // Break transaction
                }
                
                // Updating model instance ensures boot / saved events trigger accounting & invoice updates
                $payment->update(['status' => $validated['status']]);
            }
        });

        if (isset($errorResponse)) {
            return $errorResponse;
        }

        return response()->json([
            'success' => true,
            'message' => 'Selected payments updated successfully.',
        ]);
    }

    public function update(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'transaction_id' => 'nullable|string',
            'status' => 'required|in:pending,authorized,completed,failed,refunded',
            'payment_date' => 'required|date',
        ]);

        $payment->update($validated);

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

    public function destroy(Payment $payment, \App\Services\FinancialService $financialService)
    {
        try {
            $financialService->revertPayment($payment);

            return response()->json([
                'success' => true,
                'message' => 'Payment reverted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to revert payment: ' . $e->getMessage()
            ], 500);
        }
    }
}
