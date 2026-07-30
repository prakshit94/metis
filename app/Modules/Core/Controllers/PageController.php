<?php

namespace App\Modules\Core\Controllers;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Service;
use App\Modules\Customers\Models\Party;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PageController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = auth()->user();
        
        // Redirect warehouse personnel directly to their command center
        if ($user && $user->can('warehouse-dashboard-view') && !$user->can('dashboard-view')) {
            return redirect()->route('inventory.dashboard');
        }

        $isGlobalView = $user && ($user->hasRole(['Super Admin', 'Admin']) || $user->can('view-all-data'));

        $customerQuery = Party::where('type', 'customer');
        $orderQuery = Order::query();

        if (! $isGlobalView) {
            $customerQuery->where('created_by', $user->id);
            $orderQuery->where('created_by', $user->id);
        }

        $filter = $request->input('filter', 'today');
        if ($filter === 'today') {
            $orderQuery->whereDate('order_date', Carbon::today());
            $customerQuery->whereDate('created_at', Carbon::today());
        } elseif ($filter === 'yesterday') {
            $orderQuery->whereDate('order_date', Carbon::yesterday());
            $customerQuery->whereDate('created_at', Carbon::yesterday());
        } elseif ($filter === 'this_week') {
            $orderQuery->whereBetween('order_date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
            $customerQuery->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
        } elseif ($filter === 'this_month') {
            $orderQuery->whereMonth('order_date', Carbon::now()->month)->whereYear('order_date', Carbon::now()->year);
            $customerQuery->whereMonth('created_at', Carbon::now()->month)->whereYear('created_at', Carbon::now()->year);
        } elseif ($filter === 'prev_month') {
            $prevMonth = Carbon::now()->subMonthNoOverflow();
            $orderQuery->whereMonth('order_date', $prevMonth->month)->whereYear('order_date', $prevMonth->year);
            $customerQuery->whereMonth('created_at', $prevMonth->month)->whereYear('created_at', $prevMonth->year);
        } elseif ($filter === 'this_year') {
            $orderQuery->whereYear('order_date', Carbon::now()->year);
            $customerQuery->whereYear('created_at', Carbon::now()->year);
        }

        // 1. Top Metrics
        $totalCustomers = (clone $customerQuery)->count();
        $totalRevenue = (clone $orderQuery)->whereNotIn('status', ['cancelled'])
            ->whereDoesntHave('orderReturns', function ($q) {
                $q->where('status', 'completed');
            })->sum('net_amount');
        $totalOrders = (clone $orderQuery)->count();

        $totalProducts = (int) OrderItem::whereIn('order_id', (clone $orderQuery)->select('id'))->sum('quantity');

        // 1.5 Order Performance Metrics
        $totalDelivered = (clone $orderQuery)->whereIn('status', ['delivered', 'completed'])
            ->whereDoesntHave('orderReturns', function ($q) {
                $q->where('status', 'completed');
            })->count();
        $totalReturned = (clone $orderQuery)->whereHas('orderReturns', function ($q) {
            $q->where('status', 'completed');
        })->count();
        $revDelivered = (clone $orderQuery)->whereIn('status', ['delivered', 'completed'])
            ->whereDoesntHave('orderReturns', function ($q) {
                $q->where('status', 'completed');
            })->sum('net_amount');
        $revReturned = (clone $orderQuery)->whereHas('orderReturns', function ($q) {
            $q->where('status', 'completed');
        })->sum('net_amount');

        $deliveredPercent = $totalOrders > 0 ? round(($totalDelivered / $totalOrders) * 100) : 0;
        $returnedPercent = $totalOrders > 0 ? round(($totalReturned / $totalOrders) * 100) : 0;

        // 2. Chart Data

        // Revenue (Last 12 Months for simplicity)
        $revenueData = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $revenue = (clone $orderQuery)->whereNotIn('status', ['cancelled', 'returned'])
                ->whereYear('order_date', $month->year)
                ->whereMonth('order_date', $month->month)
                ->sum('net_amount');
            $profit = $revenue * 0.2; // Simulated profit margin for UI

            $revenueData[] = [
                'month' => $month->format('M Y'),
                'revenue' => round($revenue, 2),
                'profit' => round($profit, 2),
            ];
        }

        // Daily Revenue (Last 30 Days)
        $dailyRevenueData = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $revenue = (clone $orderQuery)->whereNotIn('status', ['cancelled', 'returned'])
                ->whereDate('order_date', $date->toDateString())
                ->sum('net_amount');
            $profit = $revenue * 0.2;

            $dailyRevenueData[] = [
                'month' => $date->format('M d'),
                'revenue' => round($revenue, 2),
                'profit' => round($profit, 2),
            ];
        }

        // Customer Growth (Last 30 Days)
        $customerGrowth = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $newUsers = (clone $customerQuery)
                ->whereDate('created_at', $date->toDateString())
                ->count();

            $customerGrowth[] = [
                'day' => 30 - $i,
                'newUsers' => $newUsers,
                'activeUsers' => rand(100, 500), // Keep some simulation for active users if not tracked
            ];
        }

        $returnedByStatus = (clone $orderQuery)->whereHas('orderReturns', function ($q) {
            $q->where('status', 'completed');
        })
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status')
            ->toArray();

        $orderStatusRaw = (clone $orderQuery)->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status')
            ->toArray();

        foreach ($returnedByStatus as $status => $count) {
            if ($status !== 'returned') {
                $orderStatusRaw[$status] = max(0, ($orderStatusRaw[$status] ?? 0) - $count);
                $orderStatusRaw['returned'] = ($orderStatusRaw['returned'] ?? 0) + $count;
            }
        }

        $orderStatusDistribution = [
            'completed' => ($orderStatusRaw['delivered'] ?? 0) + ($orderStatusRaw['completed'] ?? 0),
            'processing' => ($orderStatusRaw['processing'] ?? 0) + ($orderStatusRaw['ready_to_ship'] ?? 0) + ($orderStatusRaw['dispatched'] ?? 0),
            'pending' => ($orderStatusRaw['pending'] ?? 0) + ($orderStatusRaw['confirmed'] ?? 0),
            'cancelled' => ($orderStatusRaw['cancelled'] ?? 0) + ($orderStatusRaw['returned'] ?? 0),
        ];

        $orderStatusPercent = [];
        $statusesToTrack = ['pending', 'confirmed', 'processing', 'ready_to_ship', 'dispatched', 'shipped', 'completed', 'cancelled'];
        foreach ($statusesToTrack as $s) {
            $orderStatusPercent[$s] = $totalOrders > 0 ? round((($orderStatusRaw[$s] ?? 0) / $totalOrders) * 100) : 0;
        }

        // Sales by Location
        $salesByLocationRaw = (clone $orderQuery)->where(function ($q) {
            $q->whereNotNull('shipping_district')->orWhereNotNull('shipping_city')->orWhereNotNull('shipping_state');
        })
            ->select(DB::raw('COALESCE(shipping_district, shipping_city, shipping_state) as location_name'), DB::raw('SUM(net_amount) as total_sales'))
            ->whereNotIn('status', ['cancelled', 'returned'])
            ->groupBy('location_name')
            ->orderByDesc('total_sales')
            ->limit(10)
            ->get();

        $salesByLocation = $salesByLocationRaw->map(function ($item) {
            return [
                'name' => $item->location_name,
                'value' => round($item->total_sales, 2),
            ];
        })->toArray();

        // Recent Orders
        $recentOrdersRaw = (clone $orderQuery)->with(['party', 'items.product'])
            ->latest('order_date')
            ->take(5)
            ->get();

        $recentOrders = $recentOrdersRaw->map(function ($order) {
            $statusClass = match ($order->lifecycleStatus()) {
                'delivered', 'completed' => 'bg-success',
                'pending', 'confirmed' => 'bg-warning',
                'dispatched', 'shipped', 'ready_to_ship', 'processing' => 'bg-info',
                'cancelled', 'returned' => 'bg-danger',
                default => 'bg-secondary',
            };

            $itemsList = $order->items->map(function($item) {
                $name = $item->product ? $item->product->name : 'Unknown Product';
                return $item->quantity . 'x ' . $name;
            })->implode(', ');

            return [
                'id' => $order->order_no,
                'customer' => $order->party ? $order->party->name : 'Unknown',
                'items' => $itemsList,
                'amount' => 'Rs '.number_format($order->net_amount, 2),
                'status' => [
                    'text' => $order->statusLabel(),
                    'class' => $statusClass,
                ],
                'date' => $order->order_date ? $order->order_date->format('M d, Y h:i A') : 'N/A',
            ];
        })->toArray();

        $dashboardData = [
            'revenue_monthly' => $revenueData,
            'revenue_daily' => $dailyRevenueData,
            'users' => $customerGrowth,
            'orders' => $orderStatusDistribution,
            'salesByLocation' => $salesByLocation,
            'recentOrders' => $recentOrders,
        ];

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'filter' => $filter,
                'metrics' => [
                    'totalCustomers' => $totalCustomers,
                    'totalRevenue' => $totalRevenue,
                    'totalOrders' => $totalOrders,
                    'totalProducts' => $totalProducts,
                    'totalDelivered' => $totalDelivered,
                    'totalReturned' => $totalReturned,
                    'revDelivered' => $revDelivered,
                    'revReturned' => $revReturned,
                    'deliveredPercent' => $deliveredPercent,
                    'returnedPercent' => $returnedPercent,
                ],
                'dashboardData' => $dashboardData,
                'orderStatusRaw' => $orderStatusRaw,
                'orderStatusPercent' => $orderStatusPercent,
            ]);
        }

        return view('dashboard', compact(
            'filter',
            'totalCustomers',
            'totalRevenue',
            'totalOrders',
            'totalProducts',
            'totalDelivered',
            'totalReturned',
            'revDelivered',
            'revReturned',
            'deliveredPercent',
            'returnedPercent',
            'dashboardData',
            'orderStatusRaw',
            'orderStatusPercent'
        ));
    }

    public function login()
    {
        return view('login');
    }

    public function analytics()
    {
        return view('analytics');
    }

    public function users()
    {
        return view('users.index');
    }

    public function customers()
    {
        return view('customers.index');
    }

    public function villages()
    {
        return view('villages.index');
    }

    public function rolesPermissions()
    {
        return view('users.roles-permissions');
    }

    public function products()
    {
        return view('products');
    }

    public function orders()
    {
        return view('orders.index');
    }

    public function stockManagement()
    {
        return view('inventory.stock-management');
    }

    public function stockTransfers()
    {
        return view('inventory.stock-transfers');
    }

    public function inventoryAdjustments()
    {
        return view('inventory.adjustments');
    }

    public function reports(Request $request)
    {
        if ($request->wantsJson() || $request->ajax()) {
            $period = $request->get('period', '30d');
            $days = 30;
            if ($period == '7d') {
                $days = 7;
            } elseif ($period == '90d') {
                $days = 90;
            } elseif ($period == '1y') {
                $days = 365;
            }

            $startDate = now()->subDays($days)->startOfDay();

            // KPIs
            $revenue = Order::whereNotIn('status', ['cancelled', 'returned'])->where('order_date', '>=', $startDate)->sum('net_amount');
            $ordersCount = Order::where('order_date', '>=', $startDate)->count();
            $customersCount = Party::where('type', 'customer')->where('created_at', '>=', $startDate)->count();

            // Previous Period KPIs for % change
            $prevStartDate = now()->subDays($days * 2)->startOfDay();
            $prevEndDate = now()->subDays($days)->endOfDay();
            $prevRevenue = Order::whereNotIn('status', ['cancelled', 'returned'])->whereBetween('order_date', [$prevStartDate, $prevEndDate])->sum('net_amount');
            $prevOrders = Order::whereBetween('order_date', [$prevStartDate, $prevEndDate])->count();
            $prevCustomers = Party::where('type', 'customer')->whereBetween('created_at', [$prevStartDate, $prevEndDate])->count();

            $revenueChange = $prevRevenue > 0 ? round((($revenue - $prevRevenue) / $prevRevenue) * 100, 1) : ($revenue > 0 ? 100 : 0);
            $ordersChange = $prevOrders > 0 ? round((($ordersCount - $prevOrders) / $prevOrders) * 100, 1) : ($ordersCount > 0 ? 100 : 0);
            $customersChange = $prevCustomers > 0 ? round((($customersCount - $prevCustomers) / $prevCustomers) * 100, 1) : ($customersCount > 0 ? 100 : 0);

            // Top Products
            $topProducts = OrderItem::select('product_id', DB::raw('SUM(order_items.total_amount) as revenue'), DB::raw('SUM(order_items.quantity) as units'))
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('orders.order_date', '>=', $startDate)
                ->whereNotIn('orders.status', ['cancelled', 'returned'])
                ->groupBy('product_id')
                ->orderByDesc('revenue')
                ->limit(5)
                ->with('product:id,name')
                ->get()
                ->map(function ($item) {
                    return [
                        'name' => $item->product ? $item->product->name : 'Unknown Product',
                        'revenue' => round($item->revenue, 2),
                        'units' => $item->units.' sold',
                    ];
                });

            // Revenue Trends
            $trendsQuery = DB::table('orders')
                ->where('order_date', '>=', $startDate)
                ->whereNotIn('status', ['cancelled', 'returned'])
                ->groupBy(DB::raw('DATE(order_date)'))
                ->orderBy(DB::raw('DATE(order_date)'))
                ->get([
                    DB::raw('DATE(order_date) as date'),
                    DB::raw('SUM(net_amount) as revenue'),
                ]);

            // Region Sales
            $regionSales = DB::table('orders')
                ->where('order_date', '>=', $startDate)
                ->where(function ($q) {
                    $q->whereNotNull('shipping_district')->orWhereNotNull('shipping_city')->orWhereNotNull('shipping_state');
                })
                ->whereNotIn('status', ['cancelled', 'returned'])
                ->groupBy(DB::raw('COALESCE(shipping_district, shipping_city, shipping_state)'))
                ->orderByDesc(DB::raw('SUM(net_amount)'))
                ->limit(6)
                ->get([
                    DB::raw('COALESCE(shipping_district, shipping_city, shipping_state) as shipping_state'), // Alias kept for frontend compatibility if needed
                    DB::raw('SUM(net_amount) as revenue'),
                ]);

            return response()->json([
                'kpis' => [
                    'revenue' => round($revenue, 2),
                    'revenueChange' => $revenueChange,
                    'orders' => $ordersCount,
                    'ordersChange' => $ordersChange,
                    'customers' => $customersCount,
                    'customersChange' => $customersChange,
                    'conversionRate' => 3.4,
                    'conversionChange' => 0.5,
                ],
                'topProducts' => $topProducts,
                'trends' => $trendsQuery,
                'regionSales' => $regionSales,
            ]);
        }

        return view('reports');
    }

    public function exportReports(Request $request)
    {
        $type = $request->get('type', 'sales_overview');
        $dateFrom = $request->get('from');
        $dateTo = $request->get('to');
        
        return match ($type) {
            'sales_overview' => $this->exportSalesOverview($dateFrom, $dateTo),
            'product_sales' => $this->exportProductSales(),
            'sales_region' => $this->exportSalesRegion(),
            'payment_reconciliation' => $this->exportPaymentReconciliation(),
            'stock_valuation' => $this->exportStockValuation(),
            'stock_ledger' => $this->exportStockLedger(),
            'low_stock' => $this->exportLowStock(),
            'po_fulfillment' => $this->exportPOFulfillment(),
            'grn_discrepancy' => $this->exportGRNDiscrepancy(),
            'call_performance' => $this->exportCallPerformance(),
            'call_tagging' => $this->exportCallTagging(),
            'customer_retention' => $this->exportCustomerRetention(),
            'return_analysis' => $this->exportReturnAnalysis(),
            'audit_trail' => $this->exportAuditTrail(),
            default => abort(404, 'Report type not yet implemented.'),
        };
    }

    private function exportStockValuation()
    {
        $fileName = 'stock_valuation_' . now()->format('Y-m-d_H-i-s') . '.csv';
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Warehouse', 'SKU', 'Product Name', 'Current Qty', 'Reserved Qty', 'Available Qty'];
        
        $callback = function() use($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            
            \App\Modules\Inventory\Models\Stock::with(['warehouse', 'product'])->chunk(100, function ($stocks) use ($file) {
                foreach ($stocks as $stock) {
                    $row = [
                        $stock->warehouse ? $stock->warehouse->name : '',
                        $stock->product ? $stock->product->sku : '',
                        $stock->product ? $stock->product->name : '',
                        $stock->quantity,
                        $stock->reserved_quantity,
                        $stock->available_quantity
                    ];
                    fputcsv($file, $row);
                }
            });
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportSalesOverview($dateFrom, $dateTo)
    {
        $query = Order::query()->with(['party', 'creator', 'updater', 'warehouse', 'items.product']);
        
        if ($dateFrom) {
            $query->where('order_date', '>=', Carbon::parse($dateFrom));
        }
        if ($dateTo) {
            $query->where('order_date', '<=', Carbon::parse($dateTo));
        }
        
        $fileName = 'sales_report_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = [
            'Sale Order Id', 'Farmer Id', 'Farmer Name', 'Mobile', 
            'Created On Date', 'Created On Time', 'Created By', 'Modified On Date', 
            'Modified On Time', 'Modified By', 'Advance Order Date', 'Village', 
            'PostOffice', 'Taluka', 'District', 'PinCode', 'State', 'Grand Total', 
            'Status', 'Item Sku', 'Item Name', 'Item Quantity', 'Item Unit Price',
            'Item Total Price', 'Item Retail Store Discount', 
            'Order Type', 'FC Name'
        ];

        $callback = function() use($query, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            $query->chunk(100, function ($orders) use ($file) {
                foreach ($orders as $order) {
                    
                    if ($order->items->isEmpty()) {
                        $row = [
                            $order->order_no, // Sale Order Id
                            $order->party_id, // Farmer Id
                            $order->party ? $order->party->name : '', // Farmer Name
                            $order->party ? $order->party->mobile : '', // Mobile
                            $order->created_at ? $order->created_at->format('Y-m-d') : '', // Created On Date
                            $order->created_at ? $order->created_at->format('H:i:s') : '', // Created On Time
                            $order->creator ? $order->creator->name : '', // Created By
                            $order->updated_at ? $order->updated_at->format('Y-m-d') : '', // Modified On Date
                            $order->updated_at ? $order->updated_at->format('H:i:s') : '', // Modified On Time
                            $order->updater ? $order->updater->name : '', // Modified By
                            $order->future_order_date ? \Carbon\Carbon::parse($order->future_order_date)->format('Y-m-d') : '', // Advance Order Date
                            $order->shipping_village_name, // Village
                            $order->shipping_post_office, // PostOffice
                            $order->shipping_taluka, // Taluka
                            $order->shipping_district, // District
                            $order->shipping_pincode, // PinCode
                            $order->shipping_state, // State
                            $order->net_amount, // Grand Total
                            $order->status, // Status
                            '', // Item Sku
                            '', // Item Name
                            0, // Item Quantity
                            0, // Item Unit Price
                            0, // Item Total Price
                            $order->discount_amount, // Item Retail Store Discount
                            $order->type, // Order Type
                            $order->warehouse ? $order->warehouse->name : '', // FC Name
                        ];
                        fputcsv($file, $row);
                        continue;
                    }

                    foreach ($order->items as $item) {
                        $row = [
                            $order->order_no, // Sale Order Id
                            $order->party_id, // Farmer Id
                            $order->party ? $order->party->name : '', // Farmer Name
                            $order->party ? $order->party->mobile : '', // Mobile
                            $order->created_at ? $order->created_at->format('Y-m-d') : '', // Created On Date
                            $order->created_at ? $order->created_at->format('H:i:s') : '', // Created On Time
                            $order->creator ? $order->creator->name : '', // Created By
                            $order->updated_at ? $order->updated_at->format('Y-m-d') : '', // Modified On Date
                            $order->updated_at ? $order->updated_at->format('H:i:s') : '', // Modified On Time
                            $order->updater ? $order->updater->name : '', // Modified By
                            $order->future_order_date ? \Carbon\Carbon::parse($order->future_order_date)->format('Y-m-d') : '', // Advance Order Date
                            $order->shipping_village_name, // Village
                            $order->shipping_post_office, // PostOffice
                            $order->shipping_taluka, // Taluka
                            $order->shipping_district, // District
                            $order->shipping_pincode, // PinCode
                            $order->shipping_state, // State
                            $order->net_amount, // Grand Total
                            $order->status, // Status
                            $item->product ? $item->product->sku : '', // Item Sku
                            $item->product ? $item->product->name : '', // Item Name
                            $item->quantity, // Item Quantity
                            $item->unit_price, // Item Unit Price
                            $item->total_amount, // Item Total Price
                            $order->discount_amount, // Item Retail Store Discount
                            $order->type, // Order Type
                            $order->warehouse ? $order->warehouse->name : '', // FC Name
                        ];
                        fputcsv($file, $row);
                    }
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function getCsvHeaders($fileName)
    {
        return [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];
    }

    private function exportProductSales()
    {
        $headers = $this->getCsvHeaders('product_sales_' . now()->format('Ymd_His') . '.csv');
        $columns = ['Product Name', 'SKU', 'Total Units Sold', 'Avg Unit Price', 'Total Revenue'];
        $callback = function() use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            DB::table('order_items')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->select('products.name', 'products.sku', DB::raw('SUM(order_items.quantity) as total_qty'), DB::raw('AVG(order_items.unit_price) as avg_price'), DB::raw('SUM(order_items.total_amount) as total_revenue'))
                ->groupBy('products.id', 'products.name', 'products.sku')
                ->orderByDesc('total_qty')
                ->chunk(100, function ($rows) use ($file) {
                    foreach ($rows as $row) {
                        fputcsv($file, [$row->name, $row->sku, $row->total_qty, round((float)$row->avg_price, 2), $row->total_revenue]);
                    }
                });
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }

    private function exportSalesRegion()
    {
        $headers = $this->getCsvHeaders('sales_region_' . now()->format('Ymd_His') . '.csv');
        $columns = ['State', 'District', 'Taluka', 'Village', 'Order Count', 'Total Revenue', 'AOV'];
        $callback = function() use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            DB::table('orders')
                ->select('shipping_state', 'shipping_district', 'shipping_taluka', 'shipping_village_name', DB::raw('COUNT(id) as order_count'), DB::raw('SUM(net_amount) as total_revenue'), DB::raw('AVG(net_amount) as aov'))
                ->groupBy('shipping_state', 'shipping_district', 'shipping_taluka', 'shipping_village_name')
                ->chunk(100, function ($rows) use ($file) {
                    foreach ($rows as $row) {
                        fputcsv($file, [$row->shipping_state, $row->shipping_district, $row->shipping_taluka, $row->shipping_village_name, $row->order_count, $row->total_revenue, round((float)$row->aov, 2)]);
                    }
                });
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }

    private function exportPaymentReconciliation()
    {
        $headers = $this->getCsvHeaders('payment_reconciliation_' . now()->format('Ymd_His') . '.csv');
        $columns = ['Payment ID', 'Order No', 'Amount', 'Status', 'Transaction ID', 'Payment Date'];
        $callback = function() use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            DB::table('payments')
                ->leftJoin('orders', 'payments.order_id', '=', 'orders.id')
                ->select('payments.id', 'orders.order_no', 'payments.amount', 'payments.status', 'payments.transaction_id', 'payments.created_at')
                ->orderBy('payments.id')
                ->chunk(100, function ($rows) use ($file) {
                    foreach ($rows as $row) {
                        fputcsv($file, [$row->id, $row->order_no, $row->amount, $row->status, $row->transaction_id, $row->created_at]);
                    }
                });
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }

    private function exportStockLedger()
    {
        $headers = $this->getCsvHeaders('stock_ledger_' . now()->format('Ymd_His') . '.csv');
        $columns = ['Date', 'Type', 'SKU', 'Product Name', 'Warehouse', 'Qty Before', 'Qty Moved', 'Qty After'];
        $callback = function() use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            DB::table('stock_movements')
                ->leftJoin('products', 'stock_movements.product_id', '=', 'products.id')
                ->leftJoin('warehouses', 'stock_movements.warehouse_id', '=', 'warehouses.id')
                ->select('stock_movements.created_at', 'stock_movements.type', 'products.sku', 'products.name', 'warehouses.name as warehouse_name', 'stock_movements.quantity_before', 'stock_movements.quantity', 'stock_movements.quantity_after')
                ->orderBy('stock_movements.id')
                ->chunk(100, function ($rows) use ($file) {
                    foreach ($rows as $row) {
                        fputcsv($file, [$row->created_at, $row->type, $row->sku, $row->name, $row->warehouse_name, $row->quantity_before, $row->quantity, $row->quantity_after]);
                    }
                });
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }

    private function exportLowStock()
    {
        $headers = $this->getCsvHeaders('low_stock_' . now()->format('Ymd_His') . '.csv');
        $columns = ['SKU', 'Product Name', 'Warehouse', 'Current Qty', 'Reserved Qty', 'Available Qty'];
        $callback = function() use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            DB::table('stocks')
                ->leftJoin('products', 'stocks.product_id', '=', 'products.id')
                ->leftJoin('warehouses', 'stocks.warehouse_id', '=', 'warehouses.id')
                ->select('products.sku', 'products.name', 'warehouses.name as warehouse_name', 'stocks.quantity', 'stocks.reserved_quantity', 'stocks.available_quantity')
                ->where('stocks.available_quantity', '<', 10)
                ->orderBy('stocks.id')
                ->chunk(100, function ($rows) use ($file) {
                    foreach ($rows as $row) {
                        fputcsv($file, [$row->sku, $row->name, $row->warehouse_name, $row->quantity, $row->reserved_quantity, $row->available_quantity]);
                    }
                });
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }

    private function exportPOFulfillment()
    {
        $headers = $this->getCsvHeaders('po_fulfillment_' . now()->format('Ymd_His') . '.csv');
        $columns = ['PO Number', 'Supplier', 'Order Date', 'Expected Delivery', 'Total Amount', 'Status'];
        $callback = function() use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            DB::table('purchase_orders')
                ->leftJoin('parties', 'purchase_orders.party_id', '=', 'parties.id')
                ->select('purchase_orders.po_number', 'parties.name as supplier_name', 'purchase_orders.order_date', 'purchase_orders.expected_delivery_date', 'purchase_orders.net_amount', 'purchase_orders.status')
                ->orderBy('purchase_orders.id')
                ->chunk(100, function ($rows) use ($file) {
                    foreach ($rows as $row) {
                        fputcsv($file, [$row->po_number, $row->supplier_name, $row->order_date, $row->expected_delivery_date, $row->net_amount, $row->status]);
                    }
                });
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }

    private function exportGRNDiscrepancy()
    {
        $headers = $this->getCsvHeaders('grn_discrepancy_' . now()->format('Ymd_His') . '.csv');
        $columns = ['GRN Number', 'SKU', 'Ordered Qty', 'Received Qty', 'Rejected Qty'];
        $callback = function() use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            DB::table('goods_receipt_items')
                ->leftJoin('goods_receipts', 'goods_receipt_items.goods_receipt_id', '=', 'goods_receipts.id')
                ->leftJoin('products', 'goods_receipt_items.product_id', '=', 'products.id')
                ->select('goods_receipts.receipt_no as grn_number', 'products.sku', 'goods_receipt_items.ordered_quantity', 'goods_receipt_items.received_quantity', 'goods_receipt_items.rejected_quantity')
                ->orderBy('goods_receipt_items.id')
                ->chunk(100, function ($rows) use ($file) {
                    foreach ($rows as $row) {
                        fputcsv($file, [$row->grn_number, $row->sku, $row->ordered_quantity, $row->received_quantity, $row->rejected_quantity]);
                    }
                });
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }

    private function exportCallPerformance()
    {
        $headers = $this->getCsvHeaders('call_performance_' . now()->format('Ymd_His') . '.csv');
        $columns = ['Call ID', 'Agent Name', 'Customer ID', 'Level 1 Tag', 'Level 2 Tag', 'Level 3 Tag', 'Notes', 'Logged Date'];
        $callback = function() use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            \App\Models\CallLog::with(['agent', 'tagL1', 'tagL2', 'tagL3'])->chunk(100, function ($logs) use ($file) {
                foreach ($logs as $log) {
                    fputcsv($file, [
                        $log->id, 
                        $log->agent ? $log->agent->name : '', 
                        $log->customer_id, 
                        $log->tagL1 ? $log->tagL1->name : '', 
                        $log->tagL2 ? $log->tagL2->name : '', 
                        $log->tagL3 ? $log->tagL3->name : '', 
                        $log->notes, 
                        $log->created_at
                    ]);
                }
            });
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }

    private function exportCallTagging()
    {
        $headers = $this->getCsvHeaders('call_tagging_' . now()->format('Ymd_His') . '.csv');
        $columns = ['Call ID', 'Agent Name', 'Customer ID', 'Level 1 Tag', 'Level 2 Tag', 'Level 3 Tag', 'Dynamic Form Data', 'Notes', 'Logged Date'];
        $callback = function() use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            
            \App\Models\CallLog::with(['agent', 'tagL1', 'tagL2', 'tagL3', 'metas'])->chunk(100, function ($logs) use ($file) {
                foreach ($logs as $log) {
                    $metaString = $log->metas->map(fn($m) => $m->key . ': ' . $m->value)->implode(' | ');
                    
                    fputcsv($file, [
                        $log->id, 
                        $log->agent ? $log->agent->name : '', 
                        $log->customer_id, 
                        $log->tagL1 ? $log->tagL1->name : '', 
                        $log->tagL2 ? $log->tagL2->name : '', 
                        $log->tagL3 ? $log->tagL3->name : '', 
                        $metaString,
                        $log->notes, 
                        $log->created_at
                    ]);
                }
            });
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }

    private function exportCustomerRetention()
    {
        $headers = $this->getCsvHeaders('customer_retention_' . now()->format('Ymd_His') . '.csv');
        $columns = ['Customer ID', 'Name', 'Mobile', 'Joined Date', 'Total Orders', 'LTV'];
        $callback = function() use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            DB::table('parties')
                ->leftJoin('orders', 'parties.id', '=', 'orders.party_id')
                ->select('parties.id', 'parties.name', 'parties.mobile', 'parties.created_at', DB::raw('COUNT(orders.id) as total_orders'), DB::raw('SUM(orders.net_amount) as ltv'))
                ->where('parties.type', 'customer')
                ->groupBy('parties.id', 'parties.name', 'parties.mobile', 'parties.created_at')
                ->orderByDesc('ltv')
                ->chunk(100, function ($rows) use ($file) {
                    foreach ($rows as $row) {
                        fputcsv($file, [$row->id, $row->name, $row->mobile, $row->created_at, $row->total_orders, $row->ltv]);
                    }
                });
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }

    private function exportReturnAnalysis()
    {
        $headers = $this->getCsvHeaders('return_analysis_' . now()->format('Ymd_His') . '.csv');
        $columns = ['Return ID', 'Order No', 'Reason', 'Status', 'Refund Amount'];
        $callback = function() use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            DB::table('order_returns')
                ->leftJoin('orders', 'order_returns.order_id', '=', 'orders.id')
                ->leftJoin('return_reasons', 'order_returns.return_reason_id', '=', 'return_reasons.id')
                ->select('order_returns.id', 'orders.order_no', 'return_reasons.name as reason', 'order_returns.status', 'order_returns.refund_amount')
                ->orderBy('order_returns.id')
                ->chunk(100, function ($rows) use ($file) {
                    foreach ($rows as $row) {
                        fputcsv($file, [$row->id, $row->order_no, $row->reason, $row->status, $row->refund_amount]);
                    }
                });
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }

    private function exportAuditTrail()
    {
        $headers = $this->getCsvHeaders('audit_trail_' . now()->format('Ymd_His') . '.csv');
        $columns = ['Timestamp', 'User', 'Action', 'Subject Type', 'Subject ID'];
        $callback = function() use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            DB::table('activity_log')
                ->leftJoin('users', 'activity_log.causer_id', '=', 'users.id')
                ->select('activity_log.created_at', 'users.name as user_name', 'activity_log.description', 'activity_log.subject_type', 'activity_log.subject_id')
                ->orderByDesc('activity_log.id')
                ->chunk(100, function ($rows) use ($file) {
                    foreach ($rows as $row) {
                        fputcsv($file, [$row->created_at, $row->user_name, $row->description, $row->subject_type, $row->subject_id]);
                    }
                });
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function messages()
    {
        return view('messages');
    }

    public function calendar()
    {
        return view('calendar');
    }

    public function files()
    {
        return view('files');
    }

    public function forms()
    {
        return view('forms');
    }

    public function settings()
    {
        return view('settings');
    }

    public function security()
    {
        return view('security');
    }

    public function help()
    {
        return view('help');
    }

    public function elementsOverview()
    {
        return view('elements.overview');
    }

    public function elementsAlerts()
    {
        return view('elements.alerts');
    }

    public function elementsBadges()
    {
        return view('elements.badges');
    }

    public function elementsButtons()
    {
        return view('elements.buttons');
    }

    public function elementsCards()
    {
        return view('elements.cards');
    }

    public function elementsForms()
    {
        return view('elements.forms');
    }

    public function elementsModals()
    {
        return view('elements.modals');
    }

    public function elementsTables()
    {
        return view('elements.tables');
    }

    public function shipments()
    {
        $services = Service::active()->get();
        $carriersList = $services->pluck('name')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return view('shipping.shipments', compact('carriersList'));
    }

    public function shippingServices()
    {
        return view('shipping.services');
    }
}
