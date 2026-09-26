<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Controllers;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Warehouse;
use App\Modules\Core\Controllers\Controller;
use App\Modules\Inventory\Models\Stock;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\ValidationException;

class StockManagementController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:stockmanagement-view', only: ['index', 'show', 'warehouseOptions']),
            new Middleware('permission:stockmanagement-edit', only: ['setStock']),
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
            ->with(['product:id,name,sku,status,image_path,grade,allow_overselling,overselling_qty', 'warehouse:id,name,code'])
            ->select('stocks.*')
            ->selectRaw('(SELECT COALESCE(SUM(quantity), 0) FROM order_items INNER JOIN orders ON orders.id = order_items.order_id WHERE order_items.product_id = stocks.product_id AND orders.warehouse_id = stocks.warehouse_id AND orders.status = ? AND orders.deleted_at IS NULL) as pending_qty', ['pending'])
            ->selectRaw('(SELECT COALESCE(SUM(quantity), 0) FROM order_items INNER JOIN orders ON orders.id = order_items.order_id WHERE order_items.product_id = stocks.product_id AND orders.warehouse_id = stocks.warehouse_id AND orders.status = ? AND orders.deleted_at IS NULL) as future_order_qty', ['future_order'])
            ->selectRaw('(SELECT COALESCE(SUM(quantity), 0) FROM order_items INNER JOIN orders ON orders.id = order_items.order_id WHERE order_items.product_id = stocks.product_id AND orders.warehouse_id = stocks.warehouse_id AND orders.status = ? AND orders.deleted_at IS NULL) as pending_confirmation_qty', ['pending_confirmation'])
            ->selectRaw('(SELECT COALESCE(SUM(quantity), 0) FROM order_items INNER JOIN orders ON orders.id = order_items.order_id WHERE order_items.product_id = stocks.product_id AND orders.warehouse_id = stocks.warehouse_id AND orders.status = ? AND orders.deleted_at IS NULL) as confirmed_qty', ['confirmed'])
            ->selectRaw('(SELECT COALESCE(SUM(quantity), 0) FROM order_items INNER JOIN orders ON orders.id = order_items.order_id WHERE order_items.product_id = stocks.product_id AND orders.warehouse_id = stocks.warehouse_id AND orders.status = ? AND orders.deleted_at IS NULL) as processing_qty', ['processing'])
            ->selectRaw('(SELECT COALESCE(SUM(quantity), 0) FROM order_items INNER JOIN orders ON orders.id = order_items.order_id WHERE order_items.product_id = stocks.product_id AND orders.warehouse_id = stocks.warehouse_id AND orders.status = ? AND orders.deleted_at IS NULL) as ready_to_ship_qty', ['ready_to_ship'])
            ->selectRaw('(SELECT COALESCE(SUM(quantity), 0) FROM order_items INNER JOIN orders ON orders.id = order_items.order_id WHERE order_items.product_id = stocks.product_id AND orders.warehouse_id = stocks.warehouse_id AND orders.status = ? AND orders.deleted_at IS NULL AND EXISTS (SELECT 1 FROM shipments WHERE shipments.order_id = orders.id AND shipments.delivery_attempts > 0)) as delivery_attempted_qty', ['dispatched'])
            ->selectRaw('(SELECT COALESCE(SUM(quantity), 0) FROM order_items INNER JOIN orders ON orders.id = order_items.order_id WHERE order_items.product_id = stocks.product_id AND orders.warehouse_id = stocks.warehouse_id AND orders.status = ? AND orders.deleted_at IS NULL) as cancelled_qty', ['cancelled'])
            ->selectRaw('(SELECT COALESCE(SUM(quantity), 0) FROM order_items INNER JOIN orders ON orders.id = order_items.order_id WHERE order_items.product_id = stocks.product_id AND orders.warehouse_id = stocks.warehouse_id AND orders.status IN (?, ?) AND orders.deleted_at IS NULL) as raw_delivered_qty', ['delivered', 'completed'])
            ->selectRaw('(SELECT COALESCE(SUM(received_qty), 0) FROM order_return_items INNER JOIN order_returns ON order_returns.id = order_return_items.order_return_id INNER JOIN orders ON orders.id = order_returns.order_id WHERE order_return_items.product_id = stocks.product_id AND orders.warehouse_id = stocks.warehouse_id AND order_returns.status = ?) as returned_qty', ['completed'])
            ->selectRaw('(SELECT COALESCE(SUM(requested_qty), 0) FROM order_return_items INNER JOIN order_returns ON order_returns.id = order_return_items.order_return_id INNER JOIN orders ON orders.id = order_returns.order_id WHERE order_return_items.product_id = stocks.product_id AND orders.warehouse_id = stocks.warehouse_id AND order_returns.status IN (?, ?, ?, ?)) as return_requested_qty', ['pending', 'approved', 'received', 'qc_in_progress'])
            ->selectRaw('(SELECT COALESCE(SUM(requested_qty), 0) FROM order_return_items INNER JOIN order_returns ON order_returns.id = order_return_items.order_return_id INNER JOIN orders ON orders.id = order_returns.order_id WHERE order_return_items.product_id = stocks.product_id AND orders.warehouse_id = stocks.warehouse_id AND order_returns.status = ?) as return_pending_qty', ['pending'])
            ->selectRaw('(SELECT COALESCE(SUM(requested_qty), 0) FROM order_return_items INNER JOIN order_returns ON order_returns.id = order_return_items.order_return_id INNER JOIN orders ON orders.id = order_returns.order_id WHERE order_return_items.product_id = stocks.product_id AND orders.warehouse_id = stocks.warehouse_id AND order_returns.status = ?) as return_approved_qty', ['approved'])
            ->selectRaw('(SELECT COALESCE(SUM(requested_qty), 0) FROM order_return_items INNER JOIN order_returns ON order_returns.id = order_return_items.order_return_id INNER JOIN orders ON orders.id = order_returns.order_id WHERE order_return_items.product_id = stocks.product_id AND orders.warehouse_id = stocks.warehouse_id AND order_returns.status = ?) as return_received_qty', ['received'])
            ->selectRaw('(SELECT COALESCE(SUM(requested_qty), 0) FROM order_return_items INNER JOIN order_returns ON order_returns.id = order_return_items.order_return_id INNER JOIN orders ON orders.id = order_returns.order_id WHERE order_return_items.product_id = stocks.product_id AND orders.warehouse_id = stocks.warehouse_id AND order_returns.status = ?) as return_qc_qty', ['qc_in_progress'])
            ->selectRaw('(SELECT COALESCE(SUM(requested_qty), 0) FROM order_return_items INNER JOIN order_returns ON order_returns.id = order_return_items.order_return_id INNER JOIN orders ON orders.id = order_returns.order_id WHERE order_return_items.product_id = stocks.product_id AND orders.warehouse_id = stocks.warehouse_id AND order_returns.status = ?) as return_rejected_qty', ['rejected'])
            ->whereHas('product')
            ->whereHas('warehouse', function ($wq) use ($request) {
                if ($lobState = $request->user()?->lob_state_name) {
                    $wq->where('state', $lobState);
                }
            });

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('product', fn ($p) => $p->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%"))
                    ->orWhereHas('warehouse', fn ($w) => $w->where('name', 'like', "%{$search}%"));
            });
        }

        if ($warehouseId = $request->query('warehouse_id')) {
            $this->inventoryService->ensureWarehouseStockCoverage((int) $warehouseId);
            $query->where('warehouse_id', $warehouseId);
        }

        if ($stockLevel = $request->query('stock_level')) {
            if ($stockLevel === 'in_stock') {
                $query->whereRaw('quantity - reserved_qty > (SELECT COALESCE(min_stock_level, 5) FROM products WHERE products.id = stocks.product_id)')
                    ->where('quantity', '>', 0);
            } elseif ($stockLevel === 'low_stock') {
                $query->whereRaw('quantity - reserved_qty <= (SELECT COALESCE(min_stock_level, 5) FROM products WHERE products.id = stocks.product_id)')
                    ->where('quantity', '>', 0);
            } elseif ($stockLevel === 'out_of_stock') {
                $query->where('quantity', '<=', 0);
            }
        }

        $sortBy = $request->query('sort_by', 'id');
        $sortDir = $request->query('sort_dir', 'desc');

        if ($sortBy === 'available') {
            $query->orderByRaw('(quantity - reserved_qty - pending_qty) '.$sortDir);
        } elseif ($sortBy === 'delivered_qty') {
            $query->orderByRaw('(raw_delivered_qty - returned_qty) '.$sortDir);
        } elseif ($sortBy === 'dispatched_qty') {
            $query->orderByRaw('(dispatched_qty + in_transit_qty) '.$sortDir);
        } elseif (in_array($sortBy, ['id', 'product_id', 'warehouse_id', 'quantity', 'reserved_qty', 'in_transit_qty', 'damaged_qty', 'pending_qty', 'return_requested_qty'])) {
            $query->orderBy($sortBy, $sortDir);
        }

        $perPage = min(max((int) $request->query('per_page', 25), 1), 1000);

        $paginator = $query->paginate($perPage);

        $paginator->getCollection()->transform(function ($stock) {
            $stock->delivered_qty = max(0.0, (float) $stock->raw_delivered_qty - (float) $stock->returned_qty);
            unset($stock->raw_delivered_qty, $stock->returned_qty);

            return $stock;
        });

        $statsBaseQuery = Stock::query()->whereHas('product')->whereHas('warehouse', function ($wq) use ($request) {
            if ($lobState = $request->user()?->lob_state_name) {
                $wq->where('state', $lobState);
            }
        });

        if ($search = $request->query('search')) {
            $statsBaseQuery->where(function ($q) use ($search) {
                $q->whereHas('product', fn ($p) => $p->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%"))
                    ->orWhereHas('warehouse', fn ($w) => $w->where('name', 'like', "%{$search}%"));
            });
        }

        if ($warehouseId = $request->query('warehouse_id')) {
            $statsBaseQuery->where('warehouse_id', $warehouseId);
        }

        $statsRow = (clone $statsBaseQuery)->selectRaw("
            COUNT(DISTINCT product_id) as total_products,
            COUNT(DISTINCT warehouse_id) as total_warehouses,
            SUM(quantity) as total_units,
            SUM(CASE WHEN quantity - reserved_qty > (SELECT COALESCE(min_stock_level, 5) FROM products WHERE products.id = stocks.product_id) AND quantity > 0 THEN 1 ELSE 0 END) as in_stock,
            SUM(CASE WHEN quantity - reserved_qty <= (SELECT COALESCE(min_stock_level, 5) FROM products WHERE products.id = stocks.product_id) AND quantity > 0 THEN 1 ELSE 0 END) as low_stock_count,
            SUM(CASE WHEN quantity <= 0 THEN 1 ELSE 0 END) as out_of_stock
        ")->first();

        $stats = [
            'total_products' => (int) ($statsRow->total_products ?? 0),
            'total_warehouses' => (int) ($statsRow->total_warehouses ?? 0),
            'total_units' => (float) ($statsRow->total_units ?? 0),
            'in_stock' => (int) ($statsRow->in_stock ?? 0),
            'low_stock_count' => (int) ($statsRow->low_stock_count ?? 0),
            'out_of_stock' => (int) ($statsRow->out_of_stock ?? 0),
        ];

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
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
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => [
                'required',
                'exists:warehouses,id',
                function ($attribute, $value, $fail) use ($request) {
                    if ($lobState = $request->user()?->lob_state_name) {
                        $wh = Warehouse::find($value);
                        if ($wh && $wh->state !== $lobState) {
                            $fail('Unauthorized warehouse selection.');
                        }
                    }
                }
            ],
            'quantity' => 'required|numeric|min:0',
            'damaged_qty' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
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
                'data' => $stock->load(['product:id,name,sku,status,image_path,grade,allow_overselling,overselling_qty', 'warehouse:id,name,code']),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
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
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => [
                'required',
                'exists:warehouses,id',
                function ($attribute, $value, $fail) use ($request) {
                    if ($lobState = $request->user()?->lob_state_name) {
                        $wh = Warehouse::find($value);
                        if ($wh && $wh->state !== $lobState) {
                            $fail('Unauthorized warehouse selection.');
                        }
                    }
                }
            ],
        ]);

        $stock = $this->inventoryService->getStock(
            (int) $request->product_id,
            (int) $request->warehouse_id
        );

        return response()->json([
            'data' => $stock->load(['product:id,name,sku,status,image_path,grade,allow_overselling,overselling_qty', 'warehouse:id,name,code']),
        ]);
    }

    /**
     * Get warehouse options for filtering.
     */
    public function warehouseOptions(Request $request): JsonResponse
    {
        $this->authorize('product-view');

        $warehouses = Warehouse::query()
            ->when($request->user()?->lob_state_name, function ($query, $state) {
                $query->where('state', $state);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return response()->json(['data' => $warehouses]);
    }
}
