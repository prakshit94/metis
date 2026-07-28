<?php

namespace App\Modules\Orders\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Modules\Orders\Models\Refund;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class RefundController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:orders.view', only: ['index']),
            new Middleware('permission:orders.edit', only: ['bulkStatus']),
        ];
    }

    public function index(Request $request)
    {
        $query = Refund::with(['order.party', 'invoice', 'orderReturn']);

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($subQuery) use ($s) {
                $subQuery->where('refund_no', 'LIKE', "%{$s}%")
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
            $status = $request->status;
            if ($status === 'processed') {
                $status = 'completed';
            }
            $query->where('status', $status);
        }

        $sortField = $request->input('sort_field', 'id');
        $sortDirection = $request->input('sort_direction', 'desc');

        $allowedSorts = ['id', 'refund_no', 'amount', 'created_at', 'status'];
        if (in_array($sortField, $allowedSorts, true)) {
            $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('id', 'desc');
        }

        $refunds = $query->paginate($request->integer('limit', 10));

        // Map status 'completed' -> 'processed' for the UI
        $refunds->getCollection()->transform(function ($refund) {
            if ($refund->status === 'completed') {
                $refund->status = 'processed';
            }

            return $refund;
        });

        if ($request->wantsJson() || $request->ajax()) {
            $stats = [
                'total_refunded' => (float) Refund::where('status', 'completed')->sum('amount'),
                'pending' => Refund::where('status', 'pending')->count(),
                'failed' => Refund::where('status', 'failed')->count(),
                'processed_today' => Refund::where('status', 'completed')->whereDate('updated_at', today())->count(),
            ];

            return response()->json([
                'refunds' => $refunds,
                'stats' => $stats,
            ]);
        }

        return view('orders.refunds.index', compact('refunds'));
    }

    public function bulkStatus(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:refunds,id',
            'status' => 'required|in:pending,processed,failed',
        ]);

        $status = $validated['status'];
        if ($status === 'processed') {
            $status = 'completed';
        }

        Refund::whereIn('id', $validated['ids'])->get()->each->update(['status' => $status]);

        return response()->json([
            'success' => true,
            'message' => 'Selected refunds updated successfully.',
        ]);
    }
}
