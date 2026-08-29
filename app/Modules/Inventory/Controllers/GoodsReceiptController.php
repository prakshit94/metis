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
            new Middleware('permission:goodsreceipt-view', only: ['index']),
            new Middleware('permission:goodsreceipt-create', only: ['store']),
        ];
    }

    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            $query = GoodsReceipt::with(['purchaseOrder.supplier', 'warehouse', 'creator', 'items.product'])->latest();

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

        $stats = [
            'total' => GoodsReceipt::count(),
            'this_month' => GoodsReceipt::whereMonth('received_date', date('m'))->whereYear('received_date', date('Y'))->count(),
            'pending' => 0, // Placeholder if you integrate putaway flow later
            'discrepancies' => GoodsReceiptItem::where('rejected_qty', '>', 0)->count(),
        ];

        $pendingPOs = PurchaseOrder::with(['items.product', 'warehouse', 'supplier', 'creator'])
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
            'items.*.purchase_order_item_id' => 'required|exists:purchase_order_items,id',
            'items.*.accepted_qty' => 'required|numeric|min:0',
            'items.*.rejected_qty' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string',
            'items.*.batch_number' => 'nullable|string|max:255',
            'items.*.manufacturing_date' => 'nullable|date',
            'items.*.expiry_date' => 'nullable|date|after_or_equal:items.*.manufacturing_date',
        ]);

        try {
            DB::beginTransaction();

            $po = PurchaseOrder::with(['items.product'])->findOrFail($orderId);

            if (! in_array($po->status, ['approved', 'partially_received'])) {
                return response()->json(['message' => 'Purchase order cannot be received in its current status.'], 400);
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

            $allFullyReceived = true;

            foreach ($validated['items'] as $itemData) {
                $poItem = $po->items->where('id', $itemData['purchase_order_item_id'])->first();
                if (! $poItem) {
                    throw new \Exception("PO Item {$itemData['purchase_order_item_id']} not found in this Purchase Order.");
                }

                $acceptedQty = (float) $itemData['accepted_qty'];
                $rejectedQty = (float) $itemData['rejected_qty'];
                $totalReceived = $acceptedQty + $rejectedQty;

                if ($totalReceived > 0) {
                    $grnItem = $grn->items()->create([
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
                        $stock = Stock::firstOrCreate(
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

                        $stock->increment('quantity', $acceptedQty);

                        // Handle Batch Tracking
                        if ($poItem->product && $poItem->product->batch_tracking && ! empty($itemData['batch_number'])) {
                            $batch = StockBatch::firstOrCreate(
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
                            $batch->increment('quantity', $acceptedQty);
                            $batchMovementId = $batch->id;
                        } else {
                            $batchMovementId = null;
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

                // Check if PO item is fully received (only accepted_qty counts towards fulfillment)
                if ($poItem->received_qty < $poItem->quantity) {
                    $allFullyReceived = false;
                }
            }

            $po->status = $allFullyReceived ? 'received' : 'partially_received';
            $po->save();

            DB::commit();

            return response()->json([
                'message' => 'Goods Receipt processed successfully.',
                'data' => $grn,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'Failed to process GRN: '.$e->getMessage()], 500);
        }
    }

    public function downloadPdf(GoodsReceipt $receipt)
    {
        $receipt->load(['purchaseOrder.supplier', 'warehouse', 'items.product.taxRate', 'creator']);

        $pdf = Pdf::loadView('procurement.goods-receipts.pdf', compact('receipt'));

        return $pdf->download($receipt->grn_number.'.pdf');
    }

    public function destroy(GoodsReceipt $receipt): JsonResponse
    {
        $receipt->delete();

        return response()->json(['message' => 'Goods Receipt deleted successfully.']);
    }

    public function restore($id): JsonResponse
    {
        $receipt = GoodsReceipt::withTrashed()->findOrFail($id);
        $receipt->restore();

        return response()->json(['message' => 'Goods Receipt restored successfully.']);
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|string|in:delete,restore',
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $action = $validated['action'];
        $ids = $validated['ids'];

        if ($action === 'delete') {
            GoodsReceipt::whereIn('id', $ids)->delete();

            return response()->json(['message' => 'Selected Goods Receipts deleted successfully.']);
        }

        if ($action === 'restore') {
            GoodsReceipt::withTrashed()->whereIn('id', $ids)->restore();

            return response()->json(['message' => 'Selected Goods Receipts restored successfully.']);
        }

        return response()->json(['message' => 'Invalid action.'], 400);
    }
}
