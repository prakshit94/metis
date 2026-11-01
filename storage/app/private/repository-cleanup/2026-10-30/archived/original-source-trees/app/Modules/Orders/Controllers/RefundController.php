<?php

namespace App\Modules\Orders\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Modules\Orders\Models\Refund;
use App\Services\EnsureRecordVisibility;
use App\Services\FinancialService;
use App\Services\RecordVisibility;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class RefundController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(EnsureRecordVisibility::class),
            new Middleware('permission:refunds.view', only: ['index', 'show']),
            new Middleware('permission:refunds.edit', only: ['bulkStatus']),
        ];
    }

    public function index(Request $request)
    {
        $query = Refund::with(['order.party', 'order.orderReturns', 'order.shipments', 'invoice.payments', 'invoice.refunds', 'orderReturn', 'processedBy']);
        app(RecordVisibility::class)->financial($query);

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

        $refunds = $query->paginate(min(max($request->integer('limit', 10), 1), 200));

        $refunds->getCollection()->each(fn ($refund) => $refund->order?->makeHidden(['orderReturns', 'shipments']));

        // Map status 'completed' -> 'processed' for the UI
        $refunds->getCollection()->transform(function ($refund) {
            if ($refund->status === 'completed') {
                $refund->status = 'processed';
            }

            return $refund;
        });

        if ($request->wantsJson() || $request->ajax()) {
            $counts = app(RecordVisibility::class)->financial(Refund::query())->selectRaw("
                COALESCE(SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END), 0) as total_refunded,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
                COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed,
                COUNT(CASE WHEN status = 'completed' AND updated_at >= ? AND updated_at < ? THEN 1 END) as processed_today",
                [today(), today()->addDay()]
            )->first();
            $stats = [
                'total_refunded' => (float) $counts->total_refunded,
                'pending' => (int) $counts->pending,
                'failed' => (int) $counts->failed,
                'processed_today' => (int) $counts->processed_today,
            ];

            return response()->json([
                'refunds' => $refunds,
                'stats' => $stats,
            ]);
        }

        return view('orders.refunds.index', compact('refunds'));
    }

    public function show(Refund $refund)
    {
        $refund->load(['order.party', 'invoice.payments', 'invoice.refunds', 'orderReturn', 'processedBy']);

        return response()->json(['refund' => $refund]);
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

        DB::transaction(function () use ($validated, $status) {
            foreach (Refund::whereIn('id', $validated['ids'])->orderBy('order_id')->orderBy('id')->get() as $refund) {
                app(FinancialService::class)->changeRefundStatus($refund, $status);
            }
        }, 3);

        return response()->json([
            'success' => true,
            'message' => 'Selected refunds updated successfully.',
        ]);
    }
}
