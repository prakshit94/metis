<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Services;

use App\Modules\Catalog\Models\Warehouse;
use App\Modules\Inventory\Models\GoodsReceipt;
use App\Modules\Inventory\Models\InventoryAdjustment;
use App\Modules\Inventory\Models\PurchaseOrder;
use App\Modules\Inventory\Models\Stock;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Models\StockTransfer;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderReturn;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class WarehouseAnalyticsService
{
    /**
     * Get aggregated KPI metrics for the warehouse operations dashboard.
     */
    public function getExecutiveKPIs(?int $warehouseId, string $dateRange): array
    {
        $dateStart = $this->getDateStart($dateRange);
        $dateEnd = $this->getDateEnd($dateRange);

        $ordersQuery = Order::query();
        $returnsQuery = OrderReturn::query();
        $transfersQuery = StockTransfer::query();
        $stocksQuery = Stock::query();
        $posQuery = PurchaseOrder::query();

        if ($warehouseId) {
            $ordersQuery->where('warehouse_id', $warehouseId);
            $transfersQuery->where(function ($query) use ($warehouseId) {
                $query->where('to_warehouse_id', $warehouseId)
                    ->orWhere('from_warehouse_id', $warehouseId);
            });
            $stocksQuery->where('warehouse_id', $warehouseId);
            $posQuery->where('warehouse_id', $warehouseId);
        }

        if ($dateStart) {
            $ordersQuery->where('created_at', '>=', $dateStart);
            $returnsQuery->where('created_at', '>=', $dateStart);
            $transfersQuery->where('created_at', '>=', $dateStart);
            $posQuery->where('created_at', '>=', $dateStart);
        }
        
        if ($dateEnd) {
            $ordersQuery->where('created_at', '<=', $dateEnd);
            $returnsQuery->where('created_at', '<=', $dateEnd);
            $transfersQuery->where('created_at', '<=', $dateEnd);
            $posQuery->where('created_at', '<=', $dateEnd);
        }

        $orderCounts = $ordersQuery->selectRaw("COUNT(*) as total, COUNT(CASE WHEN status IN ('pending', 'processing', 'ready_to_ship') THEN 1 END) as pending")->first();

        return [
            'total_orders' => (int) $orderCounts->total,
            'pending_orders' => (int) $orderCounts->pending,
            'pending_transfers' => (clone $transfersQuery)->where('status', 'pending')->count(),
            'pending_returns' => (clone $returnsQuery)->whereIn('status', ['requested', 'in_transit'])->count(),
            'low_stock_items' => (clone $stocksQuery)
                ->join('products', 'stocks.product_id', '=', 'products.id')
                ->whereRaw('stocks.quantity <= COALESCE(products.min_stock_level, 10)')
                ->count(),
            'pending_purchase_orders' => (clone $posQuery)->whereIn('status', ['draft', 'sent'])->count(),
        ];
    }

    /**
     * Get real time recent activities for the feed.
     */
    public function getRecentActivity(?int $warehouseId, int $limit = 50, ?string $search = null)
    {
        $movementsQuery = StockMovement::with(['product', 'warehouse', 'performer', 'reference'])->latest();
        $grQuery = GoodsReceipt::with(['warehouse', 'creator', 'purchaseOrder'])->latest();
        $adjQuery = InventoryAdjustment::with(['warehouse', 'user'])->latest();

        if ($warehouseId) {
            $movementsQuery->where('warehouse_id', $warehouseId);
            $grQuery->where('warehouse_id', $warehouseId);
            $adjQuery->where('warehouse_id', $warehouseId);
        }

        if ($search) {
            // Historical movements can store either morph aliases or full class names.
            $referenceTypes = StockMovement::query()->distinct()->pluck('reference_type')->filter();
            $movementsQuery->where(function ($q) use ($search, $referenceTypes) {
                $q->whereHas('product', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                    ->orWhere('reference_id', 'like', "%{$search}%")
                    ->orWhere(function ($references) use ($search, $referenceTypes) {
                        $references->whereRaw('1 = 0');
                        foreach ($referenceTypes as $storedType) {
                            $type = \Illuminate\Database\Eloquent\Relations\Relation::getMorphedModel($storedType) ?? $storedType;
                            if (! is_subclass_of($type, \Illuminate\Database\Eloquent\Model::class)) {
                                continue;
                            }
                            $reference = new $type;
                            $column = match ($type) {
                                Order::class => 'order_no',
                                PurchaseOrder::class => 'po_number',
                                StockTransfer::class => 'transfer_no',
                                InventoryAdjustment::class => 'reference_no',
                                GoodsReceipt::class => 'grn_number',
                                OrderReturn::class => 'return_no',
                                default => $reference->getKeyName(),
                            };
                            $matchingReferences = $reference->newQuery()->selectRaw('1')
                                ->whereColumn($reference->getQualifiedKeyName(), 'stock_movements.reference_id')
                                ->where($column, 'like', "%{$search}%");
                            $references->orWhere(function ($branch) use ($storedType, $matchingReferences) {
                                $branch->where('stock_movements.reference_type', $storedType)
                                    ->whereExists($matchingReferences->toBase());
                            });
                        }
                    });
            });

            $grQuery->where(function ($q) use ($search) {
                $q->where('grn_number', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhereHas('purchaseOrder', fn ($q) => $q->where('po_number', 'like', "%{$search}%"));
            });

            $adjQuery->where(function ($q) use ($search) {
                $q->where('reference_no', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            });
        }

        $movements = $movementsQuery->take($limit)->get()->map(function ($m) {
            $m->feed_type = 'movement';

            return $m;
        });

        $receipts = $grQuery->take($limit)->get()->map(function ($r) {
            $r->feed_type = 'receipt';

            return $r;
        });

        $adjustments = $adjQuery->take($limit)->get()->map(function ($a) {
            $a->feed_type = 'adjustment';

            return $a;
        });

        return $movements->concat($receipts)->concat($adjustments)
            ->sortByDesc('created_at')
            ->take($limit)
            ->values();
    }

    /**
     * Get adjustment shrinkage/gain value over period
     */
    public function getShrinkageValue(?int $warehouseId, string $dateRange): float
    {
        $dateStart = $this->getDateStart($dateRange);
        $dateEnd = $this->getDateEnd($dateRange);

        $query = DB::table('inventory_adjustment_items')
            ->join('inventory_adjustments', 'inventory_adjustment_items.adjustment_id', '=', 'inventory_adjustments.id')
            ->join('products', 'inventory_adjustment_items.product_id', '=', 'products.id')
            ->where('inventory_adjustments.status', 'completed')
            ->whereNull('inventory_adjustments.deleted_at')
            ->whereNull('products.deleted_at');

        if ($warehouseId) {
            $query->where('inventory_adjustments.warehouse_id', $warehouseId);
        }
        if ($dateStart) {
            $query->where('inventory_adjustments.created_at', '>=', $dateStart);
        }
        if ($dateEnd) {
            $query->where('inventory_adjustments.created_at', '<=', $dateEnd);
        }

        return (float) $query->sum(DB::raw('inventory_adjustment_items.difference * COALESCE(products.purchase_price, products.selling_price, 0)'));
    }

    /**
     * Get details for low stock items for the alerts widget
     */
    public function getLowStockAlerts(?int $warehouseId, int $limit = 5)
    {
        $query = Stock::with(['product', 'warehouse'])
            ->join('products', 'stocks.product_id', '=', 'products.id')
            ->whereRaw('stocks.quantity <= COALESCE(products.min_stock_level, 10)')
            ->select('stocks.*');

        if ($warehouseId) {
            $query->where('stocks.warehouse_id', $warehouseId);
        }

        return $query->take($limit)->get();
    }

    /**
     * Get Fulfillment performance metrics.
     */
    public function getFulfillmentPerformance(?int $warehouseId, string $dateRange): array
    {
        $dateStart = $this->getDateStart($dateRange);
        $dateEnd = $this->getDateEnd($dateRange);

        $query = Order::query();
        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }
        if ($dateStart) {
            $query->where('created_at', '>=', $dateStart);
        }
        if ($dateEnd) {
            $query->where('created_at', '<=', $dateEnd);
        }

        $counts = $query->selectRaw("
            COUNT(*) as total,
            COUNT(CASE WHEN status IN ('delivered', 'completed', 'returned') THEN 1 END) as delivered,
            COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled,
            COUNT(CASE WHEN status = 'returned' THEN 1 END) as returned
        ")->first();
        $total = (int) $counts->total;
        $delivered = (int) $counts->delivered;
        $cancelled = (int) $counts->cancelled;
        $returned = (int) $counts->returned;

        $validTotal = $total - $cancelled;
        $rate = $validTotal > 0 ? round(($delivered / $validTotal) * 100, 1) : 0;

        return [
            'total' => $total,
            'delivered' => $delivered,
            'cancelled' => $cancelled,
            'returned' => $returned,
            'fulfillment_rate' => $rate,
        ];
    }

    /**
     * Get stock valuation
     */
    public function getInventoryValue(?int $warehouseId): float
    {
        $query = DB::table('stocks')
            ->join('products', 'stocks.product_id', '=', 'products.id')
            ->whereNull('stocks.deleted_at')
            ->whereNull('products.deleted_at');

        if ($warehouseId) {
            $query->where('stocks.warehouse_id', $warehouseId);
        }

        return (float) $query->sum(DB::raw('stocks.quantity * COALESCE(products.purchase_price, products.selling_price, 0)'));
    }

    /**
     * Get Fulfillment Pipeline metrics (Counts and Amounts).
     */
    public function getFulfillmentPipeline(?int $warehouseId, string $dateRange): array
    {
        $dateStart = $this->getDateStart($dateRange);
        $dateEnd = $this->getDateEnd($dateRange);

        $query = Order::query();
        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }
        if ($dateStart) {
            $query->where('created_at', '>=', $dateStart);
        }
        if ($dateEnd) {
            $query->where('created_at', '<=', $dateEnd);
        }

        $aggregates = $query->selectRaw('status, COUNT(*) as count, SUM(net_amount) as amount')
            ->groupBy('status')
            ->get();

        $pipeline = [
            'future_order' => ['count' => 0, 'amount' => 0],
            'pending' => ['count' => 0, 'amount' => 0],
            'pending_confirmation' => ['count' => 0, 'amount' => 0],
            'confirmed' => ['count' => 0, 'amount' => 0],
            'processing' => ['count' => 0, 'amount' => 0],
            'ready_to_ship' => ['count' => 0, 'amount' => 0],
            'dispatched' => ['count' => 0, 'amount' => 0],
            'delivered' => ['count' => 0, 'amount' => 0],
            'returned' => ['count' => 0, 'amount' => 0],
            'cancelled' => ['count' => 0, 'amount' => 0],
        ];

        foreach ($aggregates as $agg) {
            if (isset($pipeline[$agg->status])) {
                $pipeline[$agg->status]['count'] = (int) $agg->count;
                $pipeline[$agg->status]['amount'] = (float) $agg->amount;
            }
        }

        $returnsReqQuery = OrderReturn::query();
        if ($warehouseId) {
            $returnsReqQuery->whereHas('order', function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            });
        }
        if ($dateStart) {
            $returnsReqQuery->where('created_at', '>=', $dateStart);
        }
        if ($dateEnd) {
            $returnsReqQuery->where('created_at', '<=', $dateEnd);
        }
        
        $returnsRequested = $returnsReqQuery->whereIn('status', ['requested', 'in_transit'])
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(refund_amount), 0) as amount')->first();
        $pipeline['returns_requested'] = [
            'count' => (int) $returnsRequested->total,
            'amount' => (float) $returnsRequested->amount,
        ];

        return $pipeline;
    }

    private function getDateStart(string $dateRange): ?Carbon
    {
        return match ($dateRange) {
            'today' => Carbon::today(),
            'yesterday' => Carbon::yesterday(),
            'this_week' => Carbon::now()->startOfWeek(),
            'this_month' => Carbon::now()->startOfMonth(),
            'prev_month' => Carbon::now()->subMonth()->startOfMonth(),
            default => null,
        };
    }

    private function getDateEnd(string $dateRange): ?Carbon
    {
        return match ($dateRange) {
            'yesterday' => Carbon::yesterday()->endOfDay(),
            'prev_month' => Carbon::now()->subMonth()->endOfMonth(),
            default => null,
        };
    }
}
