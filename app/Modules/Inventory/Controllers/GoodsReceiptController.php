<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Modules\Inventory\Models\GoodsReceipt;
use App\Modules\Inventory\Models\PurchaseOrder;
use App\Modules\Inventory\Models\Stock;
use App\Modules\Inventory\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GoodsReceiptController extends Controller
{
    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            $query = GoodsReceipt::with(['purchaseOrder.supplier', 'warehouse', 'creator'])->latest();
            
            if ($request->has('search') && !empty($request->query('search'))) {
                $search = $request->query('search');
                $query->where(function($q) use ($search) {
                    $q->where('grn_number', 'like', "%{$search}%")
                      ->orWhereHas('purchaseOrder', function($q2) use ($search) {
                          $q2->where('po_number', 'like', "%{$search}%");
                      });
                });
            }

            return response()->json($query->paginate(15));
        }

        $stats = [
            'total' => GoodsReceipt::count(),
            'this_month' => GoodsReceipt::whereMonth('received_date', date('m'))->whereYear('received_date', date('Y'))->count(),
        ];

        return view('procurement.goods-receipts.index', compact('stats'));
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
        ]);

        try {
            DB::beginTransaction();

            $po = PurchaseOrder::with('items')->findOrFail($orderId);

            if (!in_array($po->status, ['draft', 'sent', 'partially_received'])) {
                return response()->json(['message' => 'Purchase order cannot be received in its current status.'], 400);
            }

            $grnNumber = 'GRN-' . date('Ymd') . '-' . strtoupper(\Str::random(4));

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
                if (!$poItem) {
                    throw new \Exception("PO Item {$itemData['purchase_order_item_id']} not found in this Purchase Order.");
                }

                $acceptedQty = (float) $itemData['accepted_qty'];
                $rejectedQty = (float) $itemData['rejected_qty'];
                $totalReceived = $acceptedQty + $rejectedQty;

                if ($totalReceived > 0) {
                    $grn->items()->create([
                        'purchase_order_item_id' => $poItem->id,
                        'product_id' => $poItem->product_id,
                        'received_qty' => $totalReceived,
                        'accepted_qty' => $acceptedQty,
                        'rejected_qty' => $rejectedQty,
                        'notes' => $itemData['notes'] ?? null,
                    ]);

                    $poItem->received_qty = (float)$poItem->received_qty + $acceptedQty;
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

                        // Log Stock Movement
                        StockMovement::create([
                            'product_id' => $poItem->product_id,
                            'warehouse_id' => $po->warehouse_id,
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
                'data' => $grn
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to process GRN: ' . $e->getMessage()], 500);
        }
    }
}
