<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Services;

use App\Modules\Catalog\Models\Warehouse;
use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Models\Stock;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Models\StockTransfer;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderReturn;
use App\Modules\Inventory\Models\PurchaseOrder;
use App\Modules\Inventory\Models\GoodsReceipt;
use App\Modules\Inventory\Models\InventoryAdjustment;
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

        $ordersQuery = Order::query();
        $returnsQuery = OrderReturn::query();
        $transfersQuery = StockTransfer::query();
        $stocksQuery = Stock::query();
        $posQuery = PurchaseOrder::query();

        if ($warehouseId) {
            $ordersQuery->where('warehouse_id', $warehouseId);
            $transfersQuery->where('to_warehouse_id', $warehouseId)
                           ->orWhere('from_warehouse_id', $warehouseId);
            $stocksQuery->where('warehouse_id', $warehouseId);
            $posQuery->where('warehouse_id', $warehouseId);
        }

        if ($dateStart) {
            $ordersQuery->where('created_at', '>=', $dateStart);
            $returnsQuery->where('created_at', '>=', $dateStart);
            $transfersQuery->where('created_at', '>=', $dateStart);
            $posQuery->where('created_at', '>=', $dateStart);
        }

        return [
            'total_orders' => $ordersQuery->count(),
            'pending_orders' => (clone $ordersQuery)->whereIn('status', ['pending', 'processing', 'ready_to_ship'])->count(),
            'pending_transfers' => (clone $transfersQuery)->where('status', 'pending')->count(),
            'pending_returns' => (clone $returnsQuery)->whereIn('status', ['requested', 'in_transit'])->count(),
            'low_stock_items' => (clone $stocksQuery)
                ->join('products', 'stocks.product_id', '=', 'products.id')
                ->whereRaw('stocks.quantity <= COALESCE(products.min_stock_level, 10)')
                ->count(),
            'pending_purchase_orders' => (clone $posQuery)->whereIn('status', ['pending', 'approved'])->count(),
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
            $movementsQuery->where(function($q) use ($search) {
                $q->whereHas('product', fn($q) => $q->where('name', 'like', "%{$search}%"))
                  ->orWhere('reference_id', 'like', "%{$search}%")
                  ->orWhereHasMorph('reference', '*', function ($q, $type) use ($search) {
                       if ($type === \App\Modules\Orders\Models\Order::class) {
                           $q->where('order_no', 'like', "%{$search}%");
                       } elseif ($type === \App\Modules\Procurement\Models\PurchaseOrder::class) {
                           $q->where('po_number', 'like', "%{$search}%");
                       }
                  });
            });

            $grQuery->where(function($q) use ($search) {
                $q->where('grn_number', 'like', "%{$search}%")
                  ->orWhere('status', 'like', "%{$search}%")
                  ->orWhereHas('purchaseOrder', fn($q) => $q->where('po_number', 'like', "%{$search}%"));
            });

            $adjQuery->where(function($q) use ($search) {
                $q->where('reference_no', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%")
                  ->orWhere('status', 'like', "%{$search}%");
            });
        }

        $movements = $movementsQuery->take($limit)->get()->map(function($m) {
            $m->feed_type = 'movement';
            return $m;
        });

        $receipts = $grQuery->take($limit)->get()->map(function($r) {
            $r->feed_type = 'receipt';
            return $r;
        });

        $adjustments = $adjQuery->take($limit)->get()->map(function($a) {
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
        
        $query = DB::table('inventory_adjustment_items')
            ->join('inventory_adjustments', 'inventory_adjustment_items.inventory_adjustment_id', '=', 'inventory_adjustments.id')
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

        $query = Order::query();
        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }
        if ($dateStart) {
            $query->where('created_at', '>=', $dateStart);
        }

        $total = $query->count();
        $delivered = (clone $query)->whereIn('status', ['delivered', 'completed'])->count();
        $cancelled = (clone $query)->where('status', 'cancelled')->count();
        
        $rate = $total > 0 ? round(($delivered / $total) * 100, 1) : 0;

        return [
            'total' => $total,
            'delivered' => $delivered,
            'cancelled' => $cancelled,
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

        $query = Order::query();
        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }
        if ($dateStart) {
            $query->where('created_at', '>=', $dateStart);
        }

        $orders = $query->get(['status', 'net_amount']);
        $pipeline = [
            'pending' => ['count' => 0, 'amount' => 0],
            'confirmed' => ['count' => 0, 'amount' => 0],
            'processing' => ['count' => 0, 'amount' => 0],
            'ready_to_ship' => ['count' => 0, 'amount' => 0],
            'dispatched' => ['count' => 0, 'amount' => 0],
            'delivered' => ['count' => 0, 'amount' => 0],
            'returned' => ['count' => 0, 'amount' => 0],
            'cancelled' => ['count' => 0, 'amount' => 0],
        ];

        foreach ($orders as $order) {
            if (isset($pipeline[$order->status])) {
                $pipeline[$order->status]['count']++;
                $pipeline[$order->status]['amount'] += (float) $order->net_amount;
            }
        }
        
        $returnsReqQuery = OrderReturn::query();
        if ($warehouseId) {
            $returnsReqQuery->whereHas('order', function($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            });
        }
        if ($dateStart) {
            $returnsReqQuery->where('created_at', '>=', $dateStart);
        }
        $returnsRequested = $returnsReqQuery->whereIn('status', ['requested', 'in_transit'])->get(['refund_amount']);
        $pipeline['returns_requested'] = [
            'count' => $returnsRequested->count(),
            'amount' => (float) $returnsRequested->sum('refund_amount')
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
}
