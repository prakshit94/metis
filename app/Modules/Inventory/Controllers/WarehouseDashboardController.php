<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Modules\Catalog\Models\Warehouse;
use App\Modules\Inventory\Services\WarehouseAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WarehouseDashboardController extends Controller
{
    protected WarehouseAnalyticsService $analyticsService;

    public function __construct(WarehouseAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Display the warehouse operations dashboard.
     */
    public function index(Request $request): View
    {
        $warehouseId = $request->input('warehouse_id');
        if ($warehouseId) {
            $warehouseId = (int) $warehouseId;
        } else {
            $warehouseId = null;
        }

        $dateRange = $request->input('date_range', 'this_week');

        $kpis = $this->analyticsService->getExecutiveKPIs($warehouseId, $dateRange);
        $fulfillment = $this->analyticsService->getFulfillmentPerformance($warehouseId, $dateRange);
        $recentActivity = $this->analyticsService->getRecentActivity($warehouseId);
        $inventoryValue = $this->analyticsService->getInventoryValue($warehouseId);
        $shrinkageValue = $this->analyticsService->getShrinkageValue($warehouseId, $dateRange);
        $lowStockAlerts = $this->analyticsService->getLowStockAlerts($warehouseId);

        $warehouses = \Illuminate\Support\Facades\Cache::remember('active_warehouses_list', 3600, function () {
            return Warehouse::active()->get();
        });

        $pipeline = $this->analyticsService->getFulfillmentPipeline($warehouseId, $dateRange);

        return view('inventory.dashboard.index', compact(
            'warehouses',
            'warehouseId',
            'dateRange',
            'kpis',
            'fulfillment',
            'recentActivity',
            'inventoryValue',
            'shrinkageValue',
            'lowStockAlerts',
            'pipeline'
        ));
    }

    /**
     * Fetch activities via AJAX for live search.
     */
    public function activities(Request $request)
    {
        $warehouseId = $request->input('warehouse_id');
        if ($warehouseId) {
            $warehouseId = (int) $warehouseId;
        } else {
            $warehouseId = null;
        }

        $search = $request->input('search');
        $recentActivity = $this->analyticsService->getRecentActivity($warehouseId, 50, $search);

        return view('inventory.dashboard.partials.activity-feed-items', compact('recentActivity'))->render();
    }
}
