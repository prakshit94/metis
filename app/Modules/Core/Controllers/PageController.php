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

        $totalProducts = Product::count();

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

        // Order Status Distribution
        $orderStatusRaw = (clone $orderQuery)->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status')
            ->toArray();

        $orderStatusDistribution = [
            'completed' => ($orderStatusRaw['delivered'] ?? 0) + ($orderStatusRaw['completed'] ?? 0),
            'processing' => ($orderStatusRaw['processing'] ?? 0) + ($orderStatusRaw['ready_to_ship'] ?? 0) + ($orderStatusRaw['dispatched'] ?? 0),
            'pending' => ($orderStatusRaw['pending'] ?? 0) + ($orderStatusRaw['confirmed'] ?? 0),
            'cancelled' => ($orderStatusRaw['cancelled'] ?? 0) + ($orderStatusRaw['returned'] ?? 0),
        ];

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
        $recentOrdersRaw = (clone $orderQuery)->with('party')
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

            return [
                'id' => $order->order_no,
                'customer' => $order->party ? $order->party->name : 'Unknown',
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
            'orderStatusRaw'
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
