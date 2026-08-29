<?php

namespace App\Modules\Orders\Controllers;

use App\Modules\Catalog\Models\Service;
use App\Modules\Core\Controllers\Controller;
use App\Modules\Inventory\Models\Stock;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Orders\Models\OrderReturn;
use App\Modules\Orders\Models\OrderReturnItem;
use App\Modules\Orders\Models\Refund;
use App\Services\FinancialService;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class OrderReturnController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:orders.view', only: ['index', 'show']),
            new Middleware('permission:orders.return', only: ['store', 'process', 'processQc', 'processFinancials']),
        ];
    }

    public function index(Request $request)
    {
        $query = OrderReturn::with(['order.party', 'order.payments', 'order.shipments', 'items.product', 'refunds', 'creditNote']);

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($subQuery) use ($s) {
                $subQuery->where('return_no', 'LIKE', "%{$s}%")
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

        if ($request->filled('financial_status')) {
            $query->where('financial_status', $request->financial_status);
        }

        if ($request->filled('shipping_service')) {
            $query->whereHas('order.shipments', function ($q) use ($request) {
                $q->where('carrier_name', $request->shipping_service);
            });
        }

        $sortField = $request->input('sort_field', 'id');
        $sortDirection = $request->input('sort_direction', 'desc');

        $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');

        $returns = $query->paginate($request->integer('limit', 15));

        if ($request->wantsJson() || $request->ajax()) {
            $stats = [
                'total' => OrderReturn::count(),
                'pending_qc' => OrderReturn::whereIn('status', ['pending', 'received', 'qc_in_progress'])->count(),
                'completed' => OrderReturn::where('status', 'completed')->count(),
                'rejected' => OrderReturn::where('status', 'rejected')->count(),
                'total_refunded' => OrderReturn::sum('refund_amount'),
                'total_credited' => OrderReturn::sum('credit_note_amount'),
            ];

            return response()->json([
                'returns' => $returns,
                'stats' => $stats,
                'statuses' => ['pending', 'received', 'qc_in_progress', 'completed', 'rejected'],
                'financial_statuses' => ['pending', 'partial_refund', 'fully_refunded', 'credited'],
                'shipping_services' => Service::active()->select('name')->pluck('name'),
            ]);
        }

        return view('orders.returns.index', compact('returns'));
    }

    public function store(Request $request, Order $order)
    {
        // Ensure order items are loaded (used in product ownership validation below)
        $order->loadMissing('items');
        $orderProductIds = $order->items->pluck('product_id')->toArray();

        $validated = $request->validate([
            'reason' => 'required|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => [
                'required',
                'integer',
                'exists:products,id',
                // Ensure the product actually belongs to this order
                function ($attribute, $value, $fail) use ($orderProductIds) {
                    if (! in_array((int) $value, $orderProductIds)) {
                        $fail('Product ID '.$value.' was not part of this order and cannot be returned.');
                    }
                },
            ],
            'items.*.requested_qty' => 'required|numeric|gt:0',
        ]);

        $return = DB::transaction(function () use ($validated, $order) {
            $baseNo = str_replace('ORD-', 'RET-', $order->order_no);
            if ($baseNo === $order->order_no) {
                $baseNo = 'RET-'.$order->order_no;
            }
            $count = OrderReturn::where('order_id', $order->id)->count();
            $returnNo = $count > 0 ? $baseNo.'-'.($count + 1) : $baseNo;

            $return = OrderReturn::create([
                'order_id' => $order->id,
                'return_no' => $returnNo,
                'status' => 'pending',
                'reason' => $validated['reason'],
                'notes' => $validated['notes'],
            ]);

            $wasInTransit = in_array($order->status, Order::inTransitStatuses(), true);

            foreach ($validated['items'] as $item) {
                OrderReturnItem::create([
                    'order_return_id' => $return->id,
                    'product_id' => $item['product_id'],
                    'requested_qty' => $item['requested_qty'],
                ]);

                if ($wasInTransit && $order->warehouse_id) {
                    $stock = Stock::where('product_id', $item['product_id'])
                        ->where('warehouse_id', $order->warehouse_id)
                        ->lockForUpdate()
                        ->first();

                    if ($stock) {
                        $stock->dispatched_qty = max(0.0, (float) $stock->dispatched_qty - (float) $item['requested_qty']);
                        $stock->save();
                    }
                }
            }

            // Update the main order status and log it
            $order->update([
                'status' => 'return_requested',
                'updated_by' => auth()->id(),
            ]);

            $order->statusLogs()->create([
                'status' => 'return_requested',
                'notes' => 'Return initiated. Reason: '.$validated['reason'],
                'changed_by' => auth()->id(),
            ]);

            return $return;
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Return request initiated.', 'return' => $return]);
        }

        return back()->with('success', 'Return request initiated successfully.');
    }

    public function show(OrderReturn $return)
    {
        $return->load(['order.party', 'order.invoice', 'order.payments', 'items.product', 'refunds', 'creditNote']);

        return response()->json(['return' => $return]);
    }

    public function processQc(Request $request, OrderReturn $return, InventoryService $inventoryService)
    {
        // Guard: only allow QC on returns that have not already been completed or rejected
        if (! in_array($return->status, ['pending', 'received', 'qc_in_progress'])) {
            return response()->json(['message' => 'Cannot process QC on a return that is already completed or rejected.'], 422);
        }

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:order_return_items,id',
            'items.*.received_qty' => 'required|numeric|min:0',
            'items.*.restocked_qty' => 'required|numeric|min:0',
            'items.*.damaged_qty' => 'required|numeric|min:0',
            'items.*.qc_notes' => 'nullable|string',
        ]);

        // Validate that restocked + damaged does not exceed received for each item
        foreach ($validated['items'] as $index => $itemData) {
            $totalProcessed = $itemData['restocked_qty'] + $itemData['damaged_qty'];
            if ($totalProcessed > $itemData['received_qty']) {
                return response()->json([
                    'message' => "Item #{$index}: restocked_qty + damaged_qty cannot exceed received_qty.",
                ], 422);
            }
        }

        DB::transaction(function () use ($validated, $return, $inventoryService) {
            $refundAmount = 0;

            $returnItemIds = collect($validated['items'])->pluck('id');
            $returnItems = OrderReturnItem::where('order_return_id', $return->id)
                ->whereIn('id', $returnItemIds)
                ->get()
                ->keyBy('id');

            $orderItems = OrderItem::where('order_id', $return->order_id)
                ->get()
                ->keyBy('product_id');

            foreach ($validated['items'] as $itemData) {
                $item = $returnItems->get($itemData['id']);
                if (! $item) {
                    continue;
                }

                $item->update([
                    'received_qty' => $itemData['received_qty'],
                    'restocked_qty' => $itemData['restocked_qty'],
                    'damaged_qty' => $itemData['damaged_qty'],
                    'qc_notes' => $itemData['qc_notes'],
                    'qc_status' => 'passed', // simplistically assuming it passed QC if we recorded quantities
                ]);

                // Calculate proportional refund amount based on received qty
                $orderItem = $orderItems->get($item->product_id);
                if ($orderItem && $orderItem->quantity > 0) {
                    $refundAmount += ((float) $orderItem->total_amount / (float) $orderItem->quantity) * (float) $itemData['received_qty'];
                }

                // Inventory Update
                if ($return->order->warehouse_id) {
                    $inventoryService->processReturnItem(
                        $item->product_id,
                        $return->order->warehouse_id,
                        (float) $itemData['restocked_qty'],
                        (float) $itemData['damaged_qty'],
                        $return->id
                    );
                }
            }

            $return->update(['status' => 'completed']);
            $return->load('order.invoice');

            if ($refundAmount > 0 && $return->order->invoice && $return->order->invoice->paid_amount > 0) {
                $refundable = min($refundAmount, $return->order->invoice->paid_amount);

                $baseNo = str_replace('ORD-', 'REF-', $return->order->order_no);
                if ($baseNo === $return->order->order_no) {
                    $baseNo = 'REF-'.$return->order->order_no;
                }
                $count = Refund::where('order_id', $return->order_id)->count();
                $refundNo = $count > 0 ? $baseNo.'-'.($count + 1) : $baseNo;

                Refund::create([
                    'refund_no' => $refundNo,
                    'order_id' => $return->order_id,
                    'invoice_id' => $return->order->invoice->id,
                    'order_return_id' => $return->id,
                    'amount' => $refundable,
                    'status' => 'pending',
                ]);
            }

            // Update main order status to returned and log it
            $return->order->update([
                'status' => 'returned',
                'updated_by' => auth()->id(),
            ]);

            $return->order->statusLogs()->create([
                'status' => 'returned',
                'notes' => 'Return QC completed successfully.',
                'changed_by' => auth()->id(),
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Quality Check processed successfully.']);
    }

    public function processFinancials(Request $request, OrderReturn $return, FinancialService $financialService)
    {
        $validated = $request->validate([
            'action' => 'required|in:refund,credit_note',
            'amount' => 'required|numeric|gt:0',
            'payment_method' => 'nullable|string|required_if:action,refund',
            'transaction_id' => 'nullable|string',
        ]);

        try {
            if ($validated['action'] === 'refund') {
                $financialService->processRefund(
                    $return,
                    (float) $validated['amount'],
                    $validated['payment_method'],
                    $validated['transaction_id'] ?? null
                );
                $message = 'Refund processed successfully.';
            } else {
                $financialService->issueCreditNote($return, (float) $validated['amount']);
                $message = 'Credit Note issued successfully.';
            }

            return response()->json(['success' => true, 'message' => $message]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }
}
