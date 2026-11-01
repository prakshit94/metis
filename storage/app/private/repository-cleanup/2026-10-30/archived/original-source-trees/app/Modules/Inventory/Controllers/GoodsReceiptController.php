<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Modules\Inventory\Models\GoodsReceipt;
use App\Modules\Inventory\Models\GoodsReceiptItem;
use App\Modules\Inventory\Models\PurchaseOrder;
use App\Modules\Inventory\Models\Stock;
use App\Modules\Inventory\Models\StockBatch;
use App\Modules\Inventory\Models\StockMovement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class GoodsReceiptController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:goodsreceipt-view', only: ['index', 'downloadPdf']),
            new Middleware('permission:goodsreceipt-create', only: ['store']),
            new Middleware('permission:goodsreceipt-delete', only: ['destroy']),
            new Middleware('permission:goodsreceipt-restore', only: ['restore']),
        ];
    }

    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            $query = GoodsReceipt::with(['purchaseOrder.supplier', 'warehouse', 'creator', 'items.product'])->latest();

            if ($state = $request->user()?->lob_state_name) {
                $query->whereHas('warehouse', fn ($q) => $q->where('state', $state));
            }

            if ($request->has('trashed')) {
                if ($request->query('trashed') === 'only') {
                    $query->onlyTrashed();
                } elseif ($request->query('trashed') === 'with') {
                    $query->withTrashed();
                }
            }

            if ($request->has('search') && ! empty($request->query('search'))) {
                $search = $request->query('search');
                $query->where(function ($q) use ($search) {
                    $q->where('grn_number', 'like', "%{$search}%")
                        ->orWhereHas('purchaseOrder', function ($q2) use ($search) {
                            $q2->where('po_number', 'like', "%{$search}%");
                        });
                });
            }

            return response()->json($query->paginate(15));
        }

        $monthStart = now()->startOfMonth();
        $receiptQuery = GoodsReceipt::query()->when($request->user()?->lob_state_name, fn ($q, $state) => $q->whereHas('warehouse', fn ($w) => $w->where('state', $state)));
        $totals = (clone $receiptQuery)->toBase()->selectRaw("
            COUNT(*) as total,
            COALESCE(SUM(CASE WHEN received_date >= ? AND received_date < ? THEN 1 ELSE 0 END), 0) as this_month
        ", [$monthStart->toDateString(), $monthStart->copy()->addMonth()->toDateString()])->first();
        $stats = [
            'total' => (int) $totals->total,
            'this_month' => (int) $totals->this_month,
            'pending' => 0, // Placeholder if you integrate putaway flow later
            'discrepancies' => GoodsReceiptItem::where('rejected_qty', '>', 0)->whereIn('goods_receipt_id', (clone $receiptQuery)->select('id'))->count(),
        ];

        $pendingPOs = PurchaseOrder::with(['items.product', 'warehouse', 'supplier', 'creator'])
            ->when($request->user()?->lob_state_name, fn ($q, $state) => $q->whereHas('warehouse', fn ($w) => $w->where('state', $state)))
            ->whereIn('status', ['approved', 'partially_received'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('procurement.goods-receipts.index', compact('stats', 'pendingPOs'));
    }

    public function store(Request $request, $orderId): JsonResponse
    {
        $validated = $request->validate([
            'received_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.purchase_order_item_id' => 'required|integer|distinct|exists:purchase_order_items,id',
            'items.*.accepted_qty' => 'required|numeric|min:0|decimal:0,2',
            'items.*.rejected_qty' => 'required|numeric|min:0|decimal:0,2',
            'items.*.notes' => 'nullable|string',
            'items.*.batch_number' => 'nullable|string|max:255',
            'items.*.manufacturing_date' => 'nullable|date',
            'items.*.expiry_date' => 'nullable|date|after_or_equal:items.*.manufacturing_date',
        ]);

        $grn = DB::transaction(function () use ($validated, $orderId) {
            $po = PurchaseOrder::lockForUpdate()->findOrFail($orderId);
            $this->authorizeWarehouse($po->warehouse_id);
            $po->load(['items' => fn ($q) => $q->lockForUpdate()->with('product')]);

            if (! in_array($po->status, ['approved', 'partially_received'])) {
                throw \Illuminate\Validation\ValidationException::withMessages(['order' => 'Purchase order cannot be received in its current status.']);
            }

            if (! collect($validated['items'])->contains(fn ($item) => (float) $item['accepted_qty'] + (float) $item['rejected_qty'] > 0)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['items' => 'Enter at least one positive received quantity.']);
            }

            $grnNumber = 'GRN-'.date('Ymd').'-'.strtoupper(\Str::random(4));

            $grn = GoodsReceipt::create([
                'grn_number' => $grnNumber,
                'purchase_order_id' => $po->id,
                'warehouse_id' => $po->warehouse_id,
                'received_date' => $validated['received_date'],
                'status' => 'completed',
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $itemData) {
                $poItem = $po->items->where('id', $itemData['purchase_order_item_id'])->first();
                if (! $poItem) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['items' => 'A selected item does not belong to this purchase order.']);
                }

                $acceptedQty = (float) $itemData['accepted_qty'];
                $rejectedQty = (float) $itemData['rejected_qty'];
                $totalReceived = $acceptedQty + $rejectedQty;

                if ($acceptedQty > max(0, (float) $poItem->quantity - (float) $poItem->received_qty) + 0.000001) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['items' => 'Accepted quantity cannot exceed the remaining purchase order quantity.']);
                }
                if ($totalReceived > 0 && $poItem->product?->batch_tracking
                    && (empty($itemData['batch_number']) || empty($itemData['manufacturing_date']) || empty($itemData['expiry_date']))) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['items' => 'Batch-tracked products require batch number, manufacturing date, and expiry date.']);
                }

                if ($totalReceived > 0) {
                    $grn->items()->create([
                        'purchase_order_item_id' => $poItem->id,
                        'product_id' => $poItem->product_id,
                        'received_qty' => $totalReceived,
                        'accepted_qty' => $acceptedQty,
                        'rejected_qty' => $rejectedQty,
                        'notes' => $itemData['notes'] ?? null,
                        'batch_number' => $itemData['batch_number'] ?? null,
                        'manufacturing_date' => $itemData['manufacturing_date'] ?? null,
                        'expiry_date' => $itemData['expiry_date'] ?? null,
                    ]);

                    $poItem->received_qty = (float) $poItem->received_qty + $acceptedQty;
                    $poItem->save();

                    if ($acceptedQty > 0) {
                        // Update Physical Stock
                        $stock = Stock::withTrashed()->firstOrCreate(
                            [
                                'product_id' => $poItem->product_id,
                                'warehouse_id' => $po->warehouse_id,
                            ],
                            [
                                'quantity' => 0,
                                'reserved_qty' => 0,
                                'dispatched_qty' => 0,
                                'status' => 'active',
                            ]
                        );

                        if ($stock->trashed()) { $stock->restore(); }
                        $stock->increment('quantity', $acceptedQty);

                        // Handle Batch Tracking
                        if ($poItem->product && $poItem->product->batch_tracking && ! empty($itemData['batch_number'])) {
                            $batch = StockBatch::withTrashed()->firstOrCreate(
                                [
                                    'product_id' => $poItem->product_id,
                                    'warehouse_id' => $po->warehouse_id,
                                    'batch_number' => $itemData['batch_number'],
                                ],
                                [
                                    'quantity' => 0,
                                    'manufacturing_date' => $itemData['manufacturing_date'] ?? null,
                                    'expiry_date' => $itemData['expiry_date'] ?? null,
                                    'status' => 'active',
                                ]
                            );
                            if ($batch->trashed()) { $batch->restore(); }
                            $batch->increment('quantity', $acceptedQty);
                        }

                        // Log Stock Movement
                        StockMovement::create([
                            'product_id' => $poItem->product_id,
                            'warehouse_id' => $po->warehouse_id,
                            'batch_number' => $itemData['batch_number'] ?? null,
                            'reference_type' => GoodsReceipt::class,
                            'reference_id' => $grn->id,
                            'quantity' => $acceptedQty,
                            'type' => 'in',
                            'status' => 'completed',
                            'performed_by' => auth()->id(),
                        ]);
                    }
                }

            }

            $allFullyReceived = true;
            foreach ($po->items as $item) {
                if ($item->received_qty < $item->quantity) {
                    $allFullyReceived = false;
                    break;
                }
            }

            $po->status = $allFullyReceived ? 'received' : 'partially_received';
            $po->save();

            return $grn;
        }, 3);

        return response()->json([
            'message' => 'Goods Receipt processed successfully.',
            'data' => $grn,
            'purchase_order' => PurchaseOrder::with('items.product')->findOrFail($orderId),
        ], 201);
    }

    public function downloadPdf(GoodsReceipt $receipt)
    {
        $this->authorizeWarehouse($receipt->warehouse_id);
        $receipt->load(['purchaseOrder.supplier', 'warehouse', 'items.product.taxRate', 'items.purchaseOrderItem', 'creator']);

        $pdf = Pdf::loadView('procurement.goods-receipts.pdf', compact('receipt'));

        return $pdf->download($receipt->grn_number.'.pdf');
    }

    public function destroy(GoodsReceipt $receipt): JsonResponse
    {
        $this->authorizeWarehouse($receipt->warehouse_id);
        $receipt->delete();

        return response()->json(['message' => 'Goods Receipt deleted successfully.']);
    }

    public function restore($id): JsonResponse
    {
        $receipt = GoodsReceipt::withTrashed()->findOrFail($id);
        $this->authorizeWarehouse($receipt->warehouse_id);
        $receipt->restore();

        return response()->json(['message' => 'Goods Receipt restored successfully.']);
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|string|in:delete,restore',
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|distinct|exists:goods_receipts,id',
        ]);

        abort_unless($request->user()?->can('goodsreceipt-'.$validated['action']), 403);
        GoodsReceipt::withTrashed()->whereIn('id', $validated['ids'])->get()->each(fn ($receipt) => $this->authorizeWarehouse($receipt->warehouse_id));

        $action = $validated['action'];
        $ids = $validated['ids'];

        return DB::transaction(function () use ($action, $ids) {
            $receipts = GoodsReceipt::withTrashed()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
            $affected = 0;
            foreach ($receipts as $receipt) {
                if ($action === 'delete' && ! $receipt->trashed()) { $receipt->delete(); $affected++; }
                if ($action === 'restore' && $receipt->trashed()) { $receipt->restore(); $affected++; }
            }
            return response()->json(['message' => "{$affected} goods receipt(s) {$action}d successfully.", 'affected' => $affected]);
        });
    }

    private function authorizeWarehouse($warehouseId): void
    {
        if ($state = auth()->user()?->lob_state_name) {
            abort_unless(\App\Modules\Catalog\Models\Warehouse::withTrashed()->whereKey($warehouseId)->where('state', $state)->exists(), 403);
        }
    }
}
