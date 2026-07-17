<?php

namespace App\Modules\Orders\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Modules\Orders\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
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
            $stats = [
                'total_volume' => (float) Payment::where('status', 'captured')->sum('amount'),
                'captured' => Payment::where('status', 'captured')->count(),
                'authorized' => Payment::where('status', 'authorized')->count(),
                'failed' => Payment::where('status', 'failed')->count(),
            ];

            return response()->json([
                'payments' => $payments,
                'stats' => $stats,
            ]);
        }

        return view('orders.payments.index', compact('payments'));
    }

    public function bulkStatus(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:payments,id',
            'status' => 'required|in:pending,authorized,captured,failed,refunded',
        ]);

        $payments = Payment::whereIn('id', $validated['ids'])->get();

        foreach ($payments as $payment) {
            // Updating model instance ensures boot / saved events trigger accounting & invoice updates
            $payment->update(['status' => $validated['status']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Selected payments updated successfully.',
        ]);
    }

    public function update(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'transaction_id' => 'nullable|string',
            'status' => 'required|in:pending,authorized,captured,failed,refunded',
            'payment_date' => 'required|date',
        ]);

        $payment->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Payment updated successfully.',
            'payment' => $payment
        ]);
    }
}
