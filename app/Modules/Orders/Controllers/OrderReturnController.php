<?php

namespace App\Modules\Orders\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderReturn;
use App\Modules\Orders\Models\OrderReturnItem;
use App\Services\InventoryService;
use App\Services\FinancialService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderReturnController extends Controller
{
    public function index(Request $request)
    {
        $query = OrderReturn::with(['order.party', 'order.payments', 'items.product', 'refunds', 'creditNote']);

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
            ]);
        }

        return view('orders.returns.index', compact('returns'));
    }

    public function store(Request $request, Order $order)
    {
        $validated = $request->validate([
            'reason' => 'required|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.requested_qty' => 'required|numeric|gt:0',
        ]);

        $return = DB::transaction(function () use ($validated, $order) {
            $return = OrderReturn::create([
                'order_id' => $order->id,
                'return_no' => 'RET-' . strtoupper(Str::random(8)),
                'status' => 'pending',
                'reason' => $validated['reason'],
                'notes' => $validated['notes'],
            ]);

            foreach ($validated['items'] as $item) {
                OrderReturnItem::create([
                    'order_return_id' => $return->id,
                    'product_id' => $item['product_id'],
                    'requested_qty' => $item['requested_qty'],
                ]);
            }

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
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:order_return_items,id',
            'items.*.received_qty' => 'required|numeric|min:0',
            'items.*.restocked_qty' => 'required|numeric|min:0',
            'items.*.damaged_qty' => 'required|numeric|min:0',
            'items.*.qc_notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $return, $inventoryService) {
            foreach ($validated['items'] as $itemData) {
                $item = OrderReturnItem::where('order_return_id', $return->id)
                    ->findOrFail($itemData['id']);

                $item->update([
                    'received_qty' => $itemData['received_qty'],
                    'restocked_qty' => $itemData['restocked_qty'],
                    'damaged_qty' => $itemData['damaged_qty'],
                    'qc_notes' => $itemData['qc_notes'],
                    'qc_status' => 'passed', // simplistically assuming it passed QC if we recorded quantities
                ]);

                // Inventory Update
                if ($return->order->warehouse_id) {
                    $inventoryService->processReturnItem(
                        $item->product_id,
                        $return->order->warehouse_id,
                        (float)$itemData['restocked_qty'],
                        (float)$itemData['damaged_qty'],
                        $return->id
                    );
                }
            }

            $return->update(['status' => 'completed']);
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
                    (float)$validated['amount'],
                    $validated['payment_method'],
                    $validated['transaction_id'] ?? null
                );
                $message = 'Refund processed successfully.';
            } else {
                $financialService->issueCreditNote($return, (float)$validated['amount']);
                $message = 'Credit Note issued successfully.';
            }

            return response()->json(['success' => true, 'message' => $message]);
            
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }
}
