<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Modules\Inventory\Models\Stock;
use App\Modules\Catalog\Models\Warehouse;
use App\Modules\Catalog\Models\Product;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class StockManagementController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:stockmanagement-view', only: ['index', 'show']),
        ];
    }

    public function __construct(protected InventoryService $inventoryService) {}

    /**
     * List all stocks with product and warehouse details.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('product-view');

        $query = Stock::query()
            ->with(['product:id,name,sku,status', 'warehouse:id,name,code'])
            ->withSum('pendingOrderItems as pending_qty', 'quantity')
            ->withSum('deliveredOrderItems as delivered_qty', 'quantity')
            ->whereHas('product')
            ->whereHas('warehouse');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('product', fn($p) => $p->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%"))
                  ->orWhereHas('warehouse', fn($w) => $w->where('name', 'like', "%{$search}%"));
            });
        }

        if ($warehouseId = $request->query('warehouse_id')) {
            $this->inventoryService->ensureWarehouseStockCoverage((int) $warehouseId);
            $query->where('warehouse_id', $warehouseId);
        }

        if ($stockLevel = $request->query('stock_level')) {
            if ($stockLevel === 'in_stock') {
                $query->whereRaw('quantity - reserved_qty > 5 AND quantity > 0');
            } elseif ($stockLevel === 'low_stock') {
                $query->whereRaw('quantity - reserved_qty <= 5 AND quantity > 0');
            } elseif ($stockLevel === 'out_of_stock') {
                $query->where('quantity', '<=', 0);
            }
        }

        $sortBy  = $request->query('sort_by', 'id');
        $sortDir = $request->query('sort_dir', 'desc');

        if ($sortBy === 'available') {
            $query->orderByRaw('(quantity - reserved_qty) ' . $sortDir);
        } elseif (in_array($sortBy, ['id', 'product_id', 'warehouse_id', 'quantity', 'reserved_qty', 'dispatched_qty', 'delivered_qty', 'in_transit_qty', 'damaged_qty'])) {
            $query->orderBy($sortBy, $sortDir);
        }

        $perPage = min(max((int) $request->query('per_page', 25), 1), 1000);

        $paginator = $query->paginate($perPage);

        $stats = [
            'total_products'    => Stock::select('product_id')->distinct()->count(),
            'total_warehouses'  => Stock::select('warehouse_id')->distinct()->count(),
            'low_stock_count'   => Stock::whereRaw('quantity - reserved_qty <= 5 AND quantity > 0')->count(),
            'out_of_stock'      => Stock::where('quantity', 0)->count(),
        ];

        return response()->json([
            'data'  => $paginator->items(),
            'meta'  => [
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * Set (override) the stock quantity for a product/warehouse combination.
     */
    public function setStock(Request $request): JsonResponse
    {
        $this->authorize('product-edit');

        $validated = $request->validate([
            'product_id'   => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'quantity'     => 'required|numeric|min:0',
            'damaged_qty'  => 'nullable|numeric|min:0',
            'notes'        => 'nullable|string|max:500',
        ]);

        try {
            $stock = $this->inventoryService->setStock(
                (int) $validated['product_id'],
                (int) $validated['warehouse_id'],
                (float) $validated['quantity'],
                isset($validated['damaged_qty']) ? (float) $validated['damaged_qty'] : null
            );

            return response()->json([
                'message' => 'Stock updated successfully.',
                'data'    => $stock->load(['product:id,name,sku', 'warehouse:id,name,code']),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first(),
                'errors'  => $e->errors(),
            ], 422);
        }
    }

    /**
     * Get stock details for a specific product/warehouse.
     */
    public function show(Request $request): JsonResponse
    {
        $this->authorize('product-view');

        $request->validate([
            'product_id'   => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
        ]);

        $stock = $this->inventoryService->getStock(
            (int) $request->product_id,
            (int) $request->warehouse_id
        );

        return response()->json([
            'data' => $stock->load(['product:id,name,sku', 'warehouse:id,name,code']),
        ]);
    }

    /**
     * Get warehouse options for filtering.
     */
    public function warehouseOptions(): JsonResponse
    {
        $this->authorize('product-view');

        $warehouses = Warehouse::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return response()->json(['data' => $warehouses]);
    }
}
