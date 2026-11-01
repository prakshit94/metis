<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Controllers;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Warehouse;
use App\Modules\Core\Controllers\Controller;
use App\Modules\Inventory\Models\Stock;
use App\Services\InventoryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
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

        // Aggregate order and return quantities once per product/warehouse instead
        // of running a separate correlated subquery for every stock row.
        $orderQuantities = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereNull('orders.deleted_at')
            ->select('order_items.product_id', 'orders.warehouse_id')
            ->selectRaw("SUM(CASE WHEN orders.status = 'pending' THEN order_items.quantity ELSE 0 END) as pending_qty")
            ->selectRaw("SUM(CASE WHEN orders.status = 'future_order' THEN order_items.quantity ELSE 0 END) as future_order_qty")
            ->selectRaw("SUM(CASE WHEN orders.status = 'pending_confirmation' THEN order_items.quantity ELSE 0 END) as pending_confirmation_qty")
            ->selectRaw("SUM(CASE WHEN orders.status = 'confirmed' THEN order_items.quantity ELSE 0 END) as confirmed_qty")
            ->selectRaw("SUM(CASE WHEN orders.status = 'processing' THEN order_items.quantity ELSE 0 END) as processing_qty")
            ->selectRaw("SUM(CASE WHEN orders.status = 'ready_to_ship' THEN order_items.quantity ELSE 0 END) as ready_to_ship_qty")
            ->selectRaw("SUM(CASE WHEN orders.status = 'dispatched' AND EXISTS (SELECT 1 FROM shipments WHERE shipments.order_id = orders.id AND shipments.delivery_attempts > 0) THEN order_items.quantity ELSE 0 END) as delivery_attempted_qty")
            ->selectRaw("SUM(CASE WHEN orders.status = 'cancelled' THEN order_items.quantity ELSE 0 END) as cancelled_qty")
            ->selectRaw("SUM(CASE WHEN orders.status IN ('delivered', 'completed') THEN order_items.quantity ELSE 0 END) as raw_delivered_qty")
            ->groupBy('order_items.product_id', 'orders.warehouse_id');

        $returnQuantities = DB::table('order_return_items')
            ->join('order_returns', 'order_returns.id', '=', 'order_return_items.order_return_id')
            ->join('orders', 'orders.id', '=', 'order_returns.order_id')
            ->select('order_return_items.product_id', 'orders.warehouse_id')
            ->selectRaw("SUM(CASE WHEN order_returns.status = 'completed' THEN order_return_items.received_qty ELSE 0 END) as returned_qty")
            ->selectRaw("SUM(CASE WHEN order_returns.status IN ('pending', 'approved', 'received', 'qc_in_progress') THEN order_return_items.requested_qty ELSE 0 END) as return_requested_qty")
            ->selectRaw("SUM(CASE WHEN order_returns.status = 'pending' THEN order_return_items.requested_qty ELSE 0 END) as return_pending_qty")
            ->selectRaw("SUM(CASE WHEN order_returns.status = 'approved' THEN order_return_items.requested_qty ELSE 0 END) as return_approved_qty")
            ->selectRaw("SUM(CASE WHEN order_returns.status = 'received' THEN order_return_items.requested_qty ELSE 0 END) as return_received_qty")
            ->selectRaw("SUM(CASE WHEN order_returns.status = 'qc_in_progress' THEN order_return_items.requested_qty ELSE 0 END) as return_qc_qty")
            ->selectRaw("SUM(CASE WHEN order_returns.status = 'rejected' THEN order_return_items.requested_qty ELSE 0 END) as return_rejected_qty")
            ->groupBy('order_return_items.product_id', 'orders.warehouse_id');

        $query = Stock::query()
            ->with(['product:id,name,sku,status,image_path,grade,allow_overselling,overselling_qty', 'warehouse:id,name,code'])
            ->select('stocks.*')
            ->leftJoinSub($orderQuantities, 'order_quantities', function ($join) {
                $join->on('order_quantities.product_id', '=', 'stocks.product_id')
                    ->on('order_quantities.warehouse_id', '=', 'stocks.warehouse_id');
            })
            ->leftJoinSub($returnQuantities, 'return_quantities', function ($join) {
                $join->on('return_quantities.product_id', '=', 'stocks.product_id')
                    ->on('return_quantities.warehouse_id', '=', 'stocks.warehouse_id');
            })
            ->addSelect([
                DB::raw('COALESCE(order_quantities.pending_qty, 0) as pending_qty'),
                DB::raw('COALESCE(order_quantities.future_order_qty, 0) as future_order_qty'),
                DB::raw('COALESCE(order_quantities.pending_confirmation_qty, 0) as pending_confirmation_qty'),
                DB::raw('COALESCE(order_quantities.confirmed_qty, 0) as confirmed_qty'),
                DB::raw('COALESCE(order_quantities.processing_qty, 0) as processing_qty'),
                DB::raw('COALESCE(order_quantities.ready_to_ship_qty, 0) as ready_to_ship_qty'),
                DB::raw('COALESCE(order_quantities.delivery_attempted_qty, 0) as delivery_attempted_qty'),
                DB::raw('COALESCE(order_quantities.cancelled_qty, 0) as cancelled_qty'),
                DB::raw('COALESCE(order_quantities.raw_delivered_qty, 0) as raw_delivered_qty'),
                DB::raw('COALESCE(return_quantities.returned_qty, 0) as returned_qty'),
                DB::raw('COALESCE(return_quantities.return_requested_qty, 0) as return_requested_qty'),
                DB::raw('COALESCE(return_quantities.return_pending_qty, 0) as return_pending_qty'),
                DB::raw('COALESCE(return_quantities.return_approved_qty, 0) as return_approved_qty'),
                DB::raw('COALESCE(return_quantities.return_received_qty, 0) as return_received_qty'),
                DB::raw('COALESCE(return_quantities.return_qc_qty, 0) as return_qc_qty'),
                DB::raw('COALESCE(return_quantities.return_rejected_qty, 0) as return_rejected_qty'),
            ])
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
            $query->where('stocks.warehouse_id', $warehouseId);
            $this->applyWarehouseSkuScope($query, (int) $warehouseId, true);
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
        $sortDir = strtolower((string) $request->query('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        if ($sortBy === 'available') {
            $query->orderByRaw('(stocks.quantity - stocks.reserved_qty - COALESCE(order_quantities.pending_qty, 0)) '.$sortDir);
        } elseif ($sortBy === 'delivered_qty') {
            $query->orderByRaw('(COALESCE(order_quantities.raw_delivered_qty, 0) - COALESCE(return_quantities.returned_qty, 0)) '.$sortDir);
        } elseif ($sortBy === 'dispatched_qty') {
            $query->orderByRaw('(stocks.dispatched_qty + stocks.in_transit_qty) '.$sortDir);
        } elseif (in_array($sortBy, ['id', 'product_id', 'warehouse_id', 'quantity', 'reserved_qty', 'in_transit_qty', 'damaged_qty', 'pending_qty', 'return_requested_qty'])) {
            $sortColumn = match ($sortBy) {
                'pending_qty' => 'COALESCE(order_quantities.pending_qty, 0)',
                'return_requested_qty' => 'COALESCE(return_quantities.return_requested_qty, 0)',
                default => 'stocks.'.$sortBy,
            };
            $query->orderByRaw($sortColumn.' '.$sortDir);
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
            $statsBaseQuery->where('stocks.warehouse_id', $warehouseId);
            $this->applyWarehouseSkuScope($statsBaseQuery, (int) $warehouseId, false);
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
     * Ignore zero-stock rows that were created only to seed warehouse coverage.
     * Keep rows with a warehouse assignment, stock activity, or related order/return
     * history so legitimate out-of-stock and pipeline records remain visible.
     */
    private function applyWarehouseSkuScope(Builder $query, int $warehouseId, bool $aggregateJoinsPresent): void
    {
        $query->where(function (Builder $eligible) use ($warehouseId, $aggregateJoinsPresent) {
            $eligible
                ->where('stocks.quantity', '>', 0)
                ->orWhere('stocks.reserved_qty', '>', 0)
                ->orWhere('stocks.dispatched_qty', '>', 0)
                ->orWhere('stocks.committed_qty', '>', 0)
                ->orWhere('stocks.in_transit_qty', '>', 0)
                ->orWhere('stocks.damaged_qty', '>', 0)
                ->orWhereHas('product', fn (Builder $product) => $product->where('default_warehouse_id', $warehouseId))
                ->orWhereHas('movements')
                ->orWhereHas('reservations');

            if ($aggregateJoinsPresent) {
                $eligible
                    ->orWhereNotNull('order_quantities.product_id')
                    ->orWhereNotNull('return_quantities.product_id');

                return;
            }

            $eligible
                ->orWhereExists(function ($orders) {
                    $orders->selectRaw('1')
                        ->from('order_items')
                        ->join('orders', 'orders.id', '=', 'order_items.order_id')
                        ->whereColumn('order_items.product_id', 'stocks.product_id')
                        ->whereColumn('orders.warehouse_id', 'stocks.warehouse_id')
                        ->whereNull('orders.deleted_at');
                })
                ->orWhereExists(function ($returns) {
                    $returns->selectRaw('1')
                        ->from('order_return_items')
                        ->join('order_returns', 'order_returns.id', '=', 'order_return_items.order_return_id')
                        ->join('orders', 'orders.id', '=', 'order_returns.order_id')
                        ->whereColumn('order_return_items.product_id', 'stocks.product_id')
                        ->whereColumn('orders.warehouse_id', 'stocks.warehouse_id');
                });
        });
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
            ->where('status', 'active')
            ->when($request->user()?->lob_state_name, function ($query, $state) {
                $query->where('state', $state);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'is_default']);

        return response()->json(['data' => $warehouses]);
    }
}
