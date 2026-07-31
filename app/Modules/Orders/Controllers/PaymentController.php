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
        $query = Payment::with(['order.party', 'invoice']);

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($subQuery) use ($s) {
                $subQuery->where('payment_no', 'LIKE', "%{$s}%")
                    ->orWhere('transaction_id', 'LIKE', "%{$s}%")
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

        $allowedSorts = ['id', 'payment_no', 'amount', 'payment_date', 'payment_method', 'status'];
        if (in_array($sortField, $allowedSorts, true)) {
            $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('id', 'desc');
        }

        $payments = $query->paginate($request->integer('limit', 10));

        if ($request->wantsJson() || $request->ajax()) {
            $user = $request->user();

            // Scope stats to what the requesting user can see
            $userOrderIds = null;
            if ($user && !$user->hasAnyRole(['Super Admin', 'Admin']) && !$user->can('view-all-data')) {
                $userOrderIds = \App\Modules\Orders\Models\Order::where('created_by', $user->id)->pluck('id');
            }

            $statsQuery = fn () => $userOrderIds
                ? Payment::whereIn('order_id', $userOrderIds)
                : Payment::query();

            $stats = [
                'total_volume'       => (float) $statsQuery()->sum('amount'),
                'completed_amount'   => (float) $statsQuery()->where('status', 'completed')->sum('amount'),
                'authorized_amount'  => (float) $statsQuery()->whereIn('status', ['authorized', 'pending'])->sum('amount'),
                'failed_amount'      => (float) $statsQuery()->where('status', 'failed')->sum('amount'),
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
            'invoice.payments' => fn ($query) => $query->orderByDesc('payment_date')->orderByDesc('id'),
            'order.payments' => fn ($query) => $query->orderByDesc('payment_date')->orderByDesc('id'),
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

        DB::transaction(function () use ($validated) {
            $payments = Payment::whereIn('id', $validated['ids'])->get();
            foreach ($payments as $payment) {
                // Updating model instance ensures boot / saved events trigger accounting & invoice updates
                $payment->update(['status' => $validated['status']]);
            }
        });

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

        $user = $request->user();

        // Scope to payments the user is authorised to see:
        // Super Admin / Admin / view-all-data see everything.
        // Others only see payments for orders they created.
        $query = Payment::whereIn('id', $validated['ids'])
            ->with(['invoice.order.party', 'order.party']);

        if ($user && !$user->hasAnyRole(['Super Admin', 'Admin']) && !$user->can('view-all-data')) {
            $query->where(function ($q) use ($user) {
                $q->whereHas('order', fn ($oq) => $oq->where('created_by', $user->id))
                  ->orWhereHas('invoice.order', fn ($oq) => $oq->where('created_by', $user->id));
            });
        }

        $payments = $query->get();

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
}
