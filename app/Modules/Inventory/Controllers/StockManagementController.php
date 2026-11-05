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
            new Middleware('permission:stockmanagement-view', only: ['index', 'show', 'warehouseOptions', 'export']),
            new Middleware('permission:stockmanagement-edit', only: ['setStock']),
        ];
    }

    public function __construct(protected InventoryService $inventoryService) {}

    /**
     * List all stocks with product and warehouse details.
     */
    private function validateFilters(Request $request): void
    {
        if (is_string($request->input('sort_dir'))) {
            $request->merge(['sort_dir' => strtolower($request->input('sort_dir'))]);
        }
        $request->validate([
            'search' => 'nullable|string|max:200',
            'warehouse_id' => 'nullable|integer|min:1|exists:warehouses,id,deleted_at,NULL',
            'stock_level' => 'nullable|string|in:in_stock,low_stock,out_of_stock',
            'sort_by' => 'nullable|string|in:id,product_id,warehouse_id,quantity,reserved_qty,in_transit_qty,damaged_qty,pending_qty,return_requested_qty,available,delivered_qty,dispatched_qty',
            'sort_dir' => 'nullable|string|in:asc,desc',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:1000',
            'ids' => 'nullable|array|min:1|max:1000',
            'ids.*' => 'integer|min:1|distinct',
        ]);
    }

    /** Availability is the existing page's forecast; allocation/reservation services are unchanged. */
    private function stockQuery(Request $request): array
    {
        // Aggregate order and return quantities once per product/warehouse instead
        // of running a separate correlated subquery for every stock row.
        $orderQuantities = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereNull('orders.deleted_at')
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('orders.warehouse_id', $request->integer('warehouse_id')))
            ->select('order_items.product_id', 'orders.warehouse_id')
            ->selectRaw("SUM(CASE WHEN orders.status = 'pending' THEN order_items.quantity ELSE 0 END) as pending_qty")
            ->selectRaw("SUM(CASE WHEN orders.status = 'future_order' THEN order_items.quantity ELSE 0 END) as future_order_qty")
            ->selectRaw("SUM(CASE WHEN orders.status = 'pending_confirmation' THEN order_items.quantity ELSE 0 END) as pending_confirmation_qty")
            ->selectRaw("SUM(CASE WHEN orders.status = 'confirmed' THEN order_items.quantity ELSE 0 END) as confirmed_qty")
            ->selectRaw("SUM(CASE WHEN orders.status = 'processing' THEN order_items.quantity ELSE 0 END) as processing_qty")
            ->selectRaw("SUM(CASE WHEN orders.status = 'ready_to_ship' THEN order_items.quantity ELSE 0 END) as ready_to_ship_qty")
            ->selectRaw("SUM(CASE WHEN orders.status = 'dispatched' AND EXISTS (SELECT 1 FROM shipments WHERE shipments.order_id = orders.id AND shipments.delivery_attempts > 0 AND shipments.deleted_at IS NULL) THEN order_items.quantity ELSE 0 END) as delivery_attempted_qty")
            ->selectRaw("SUM(CASE WHEN orders.status = 'cancelled' THEN order_items.quantity ELSE 0 END) as cancelled_qty")
            ->selectRaw("SUM(CASE WHEN orders.status IN ('delivered', 'completed') THEN order_items.quantity ELSE 0 END) as raw_delivered_qty")
            ->groupBy('order_items.product_id', 'orders.warehouse_id');

        $returnQuantities = DB::table('order_return_items')
            ->join('order_returns', 'order_returns.id', '=', 'order_return_items.order_return_id')
            ->join('orders', 'orders.id', '=', 'order_returns.order_id')
            ->whereNull('orders.deleted_at')
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('orders.warehouse_id', $request->integer('warehouse_id')))
            ->select('order_return_items.product_id', 'orders.warehouse_id')
            ->selectRaw("SUM(CASE WHEN order_returns.status = 'completed' AND orders.status IN ('delivered', 'completed') THEN order_return_items.received_qty ELSE 0 END) as returned_qty")
            ->selectRaw("SUM(CASE WHEN order_returns.status IN ('pending', 'approved', 'received', 'qc_in_progress') THEN order_return_items.requested_qty ELSE 0 END) as return_requested_qty")
            ->selectRaw("SUM(CASE WHEN order_returns.status = 'pending' THEN order_return_items.requested_qty ELSE 0 END) as return_pending_qty")
            ->selectRaw("SUM(CASE WHEN order_returns.status = 'approved' THEN order_return_items.requested_qty ELSE 0 END) as return_approved_qty")
            ->selectRaw("SUM(CASE WHEN order_returns.status = 'received' THEN order_return_items.requested_qty ELSE 0 END) as return_received_qty")
            ->selectRaw("SUM(CASE WHEN order_returns.status = 'qc_in_progress' THEN order_return_items.requested_qty ELSE 0 END) as return_qc_qty")
            ->selectRaw("SUM(CASE WHEN order_returns.status = 'rejected' THEN order_return_items.requested_qty ELSE 0 END) as return_rejected_qty")
            ->groupBy('order_return_items.product_id', 'orders.warehouse_id');

        $query = Stock::query()
            ->with(['product:id,name,sku,status,image_path,grade,min_stock_level,allow_overselling,overselling_qty', 'warehouse:id,name,code'])
            ->join('products as stock_product', 'stock_product.id', '=', 'stocks.product_id')
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
                DB::raw('GREATEST(0, COALESCE(order_quantities.raw_delivered_qty, 0) - COALESCE(return_quantities.returned_qty, 0)) as delivered_qty'),
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

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('product', fn ($p) => $p->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%"))
                    ->orWhereHas('warehouse', fn ($w) => $w->where('name', 'like', "%{$search}%"));
            });
        }

        if ($warehouseId = $request->input('warehouse_id')) {
            $query->where('stocks.warehouse_id', $warehouseId);
            $this->applyWarehouseSkuScope($query, (int) $warehouseId, true);
        }

        $rawAvailable = '(stocks.quantity - stocks.reserved_qty - COALESCE(order_quantities.pending_qty, 0))';
        $available = "GREATEST(0, $rawAvailable + CASE WHEN COALESCE(stocks.allow_overselling, stock_product.allow_overselling, 0) = 1 THEN COALESCE(stocks.overselling_qty, stock_product.overselling_qty, 999) ELSE 0 END)";
        $threshold = 'COALESCE(stock_product.min_stock_level, 5)';
        $query->selectRaw("$available AS available_for_sell, GREATEST(0, stocks.quantity - stocks.reserved_qty) AS physical_available, $threshold AS min_stock_level");
        if ($request->filled('ids')) {
            $query->whereIn('stocks.id', $request->input('ids'));
        }
        $summary = clone $query; // Summary cards cover all levels within the warehouse/search scope.
        match ($request->input('stock_level')) {
            'in_stock' => $query->whereRaw("$available > $threshold"),
            'low_stock' => $query->whereRaw("$available > 0 AND $available <= $threshold"),
            'out_of_stock' => $query->whereRaw("$available <= 0"),
            default => null,
        };
        $sort = $request->input('sort_by') ?: 'id';
        $direction = $request->input('sort_dir') ?: 'desc';
        $expression = match ($sort) {
            'available' => $available,
            'delivered_qty' => 'GREATEST(0, COALESCE(order_quantities.raw_delivered_qty, 0) - COALESCE(return_quantities.returned_qty, 0))',
            'dispatched_qty' => '(stocks.dispatched_qty + stocks.in_transit_qty)',
            'pending_qty' => 'COALESCE(order_quantities.pending_qty, 0)',
            'return_requested_qty' => 'COALESCE(return_quantities.return_requested_qty, 0)',
            default => 'stocks.'.$sort,
        };
        $query->orderByRaw($expression.' '.$direction);
        if ($sort !== 'id') {
            $query->orderBy('stocks.id', $direction);
        }

        return [$query, $summary, $available, $threshold];
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('product-view');
        $this->validateFilters($request);
        [$query, $summary, $available, $threshold] = $this->stockQuery($request);
        $paginator = $query->paginate((int) ($request->input('per_page') ?: 25));
        $row = $summary->toBase()->select([])->selectRaw("COUNT(DISTINCT stocks.product_id) AS total_products, COUNT(DISTINCT stocks.warehouse_id) AS total_warehouses, SUM(stocks.quantity) AS total_units, SUM($available > $threshold) AS in_stock, SUM($available > 0 AND $available <= $threshold) AS low_stock_count, SUM($available <= 0) AS out_of_stock")->first();
        $stats = [];
        foreach (['total_products', 'total_warehouses', 'in_stock', 'low_stock_count', 'out_of_stock'] as $field) {
            $stats[$field] = (int) ($row->$field ?? 0);
        }
        $stats['total_units'] = (float) ($row->total_units ?? 0);

        return response()->json(['data' => $paginator->items(), 'meta' => ['total' => $paginator->total(), 'per_page' => $paginator->perPage(), 'current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage()], 'stats' => $stats]);
    }

    public function export(Request $request)
    {
        $this->authorize('product-view');
        $this->authorize('product-export');
        $this->validateFilters($request);
        [$query] = $this->stockQuery($request);

        return response()->streamDownload(function () use ($query) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Product Name', 'SKU', 'Warehouse', 'In Stock', 'Reserved', 'Available For Sell', 'Bad Qty', 'Future', 'Order Placed', 'Unfulfillable', 'Pending Conf', 'Confirmed', 'Processing', 'Ready to Ship', 'Dispatched', 'Delivery Att', 'Delivered', 'Cancelled', 'Return Req', 'Ret Pending', 'Ret Approved', 'Ret Received', 'Ret QC', 'Ret Rejected', 'Alert Level', 'Status', 'Physical Available']);
            DB::transaction(function () use ($query, $file) {
                $query->chunk(500, function ($items) use ($file) {
                    foreach ($items as $item) {
                        $available = (float) $item->available_for_sell;
                        $threshold = (float) $item->min_stock_level;
                        $row = [$item->product->name, $item->product->sku, $item->warehouse->name, $item->quantity, $item->reserved_qty, $available, $item->damaged_qty, $item->future_order_qty, $item->pending_qty, max(0, (float) $item->pending_qty - (float) $item->physical_available), $item->pending_confirmation_qty, $item->confirmed_qty, $item->processing_qty, $item->ready_to_ship_qty, $item->dispatched_qty + $item->in_transit_qty, $item->delivery_attempted_qty, $item->delivered_qty, $item->cancelled_qty, $item->return_requested_qty, $item->return_pending_qty, $item->return_approved_qty, $item->return_received_qty, $item->return_qc_qty, $item->return_rejected_qty, $threshold, $available <= 0 ? 'Out of Stock' : ($available <= $threshold ? 'Low Stock' : 'In Stock'), $item->physical_available];
                        foreach ([0, 1, 2] as $column) {
                            if (preg_match('/^[\s]*[=+@-]/u', $row[$column])) {
                                $row[$column] = "'".$row[$column];
                            }
                        }
                        fputcsv($file, $row);
                    }
                });
            });
            fclose($file);
        }, 'stock-levels-export.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
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
            'product_id' => 'bail|required|integer|min:1|exists:products,id,deleted_at,NULL',
            'warehouse_id' => [
                'bail',
                'required',
                'integer',
                'min:1',
                'exists:warehouses,id,deleted_at,NULL',
                function ($attribute, $value, $fail) use ($request) {
                    if ($lobState = $request->user()?->lob_state_name) {
                        $wh = Warehouse::find($value);
                        if ($wh && $wh->state !== $lobState) {
                            $fail('Unauthorized warehouse selection.');
                        }
                    }
                },
            ],
            'quantity' => 'required|numeric|min:0|max:99999999999.9999|decimal:0,4',
            'damaged_qty' => 'nullable|numeric|min:0|max:99999999999.9999|decimal:0,4',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $stock = DB::transaction(function () use ($validated, $request) {
                $stock = $this->inventoryService->setStock(
                    (int) $validated['product_id'],
                    (int) $validated['warehouse_id'],
                    (float) $validated['quantity'],
                    isset($validated['damaged_qty']) ? (float) $validated['damaged_qty'] : null
                );
                if (isset($validated['notes']) && trim($validated['notes']) !== '') {
                    activity()->performedOn($stock)->causedBy($request->user())
                        ->withProperties(['notes' => $validated['notes'], 'source' => 'stock-management'])
                        ->log('Stock override note');
                }

                return $stock;
            }, 3);

            return response()->json([
                'message' => 'Stock updated successfully.',
                'data' => $stock->load(['product:id,name,sku,status,image_path,grade,min_stock_level,allow_overselling,overselling_qty', 'warehouse:id,name,code']),
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
            'product_id' => 'bail|required|integer|min:1|exists:products,id,deleted_at,NULL',
            'warehouse_id' => [
                'bail',
                'required',
                'integer',
                'min:1',
                'exists:warehouses,id,deleted_at,NULL',
                function ($attribute, $value, $fail) use ($request) {
                    if ($lobState = $request->user()?->lob_state_name) {
                        $wh = Warehouse::find($value);
                        if ($wh && $wh->state !== $lobState) {
                            $fail('Unauthorized warehouse selection.');
                        }
                    }
                },
            ],
        ]);

        $stock = Stock::with(['product:id,name,sku,status,image_path,grade,min_stock_level,allow_overselling,overselling_qty', 'warehouse:id,name,code'])
            ->where('product_id', $request->integer('product_id'))->where('warehouse_id', $request->integer('warehouse_id'))->first();

        return response()->json(['data' => $stock]);
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
