<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Modules\Inventory\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            $query = PurchaseOrder::with(['supplier', 'warehouse', 'items', 'items.product'])->latest();
            
            if ($request->has('search') && !empty($request->query('search'))) {
                $search = $request->query('search');
                $query->where(function($q) use ($search) {
                    $q->where('po_number', 'like', "%{$search}%")
                      ->orWhereHas('supplier', function($sq) use ($search) {
                          $sq->where('firstname', 'like', "%{$search}%")
                            ->orWhere('company_name', 'like', "%{$search}%");
                      });
                });
            }
            
            if ($request->has('status') && !empty($request->query('status'))) {
                $query->where('status', $request->query('status'));
            }

            return response()->json($query->paginate(15));
        }

        // Stats for cards
        $stats = [
            'total' => PurchaseOrder::count(),
            'pending' => PurchaseOrder::whereIn('status', ['draft', 'sent'])->count(),
            'completed' => PurchaseOrder::where('status', 'received')->count(),
        ];
        
        $suppliers = \App\Modules\Customers\Models\Party::select('id', 'company_name', 'firstname', 'lastname')->get(); // Adjust type if needed
        $warehouses = \App\Modules\Catalog\Models\Warehouse::select('id', 'name')->where('is_active', true)->get();
        $products = \App\Modules\Catalog\Models\Product::select('id', 'name', 'sku', 'image_path')->where('is_active', true)->get();

        return view('procurement.purchase-orders.index', compact('stats', 'suppliers', 'warehouses', 'products'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'supplier_id' => ['required', \Illuminate\Validation\Rule::exists('parties', 'id')->where('type', 'supplier')],
            'warehouse_id' => 'required|exists:warehouses,id',
            'expected_delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        \DB::beginTransaction();
        try {
            $poNumber = 'PO-' . date('Ymd') . '-' . strtoupper(\Str::random(4));
            
            $po = PurchaseOrder::create([
                'po_number' => $poNumber,
                'supplier_id' => $validated['supplier_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            $totalAmount = 0;
            foreach ($validated['items'] as $item) {
                $totalPrice = $item['quantity'] * $item['unit_price'];
                $totalAmount += $totalPrice;

                $po->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $totalPrice,
                ]);
            }

            $po->update(['total_amount' => $totalAmount]);

            \DB::commit();
            return response()->json(['message' => 'Purchase Order created successfully', 'data' => $po], 201);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(['message' => 'Failed to create PO: ' . $e->getMessage()], 500);
        }
    }
}

