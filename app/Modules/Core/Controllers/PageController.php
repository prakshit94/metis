<?php

namespace App\Modules\Core\Controllers;

use App\Models\CallLog;
use App\Modules\Catalog\Models\Service;
use App\Modules\Customers\Models\Party;
use App\Modules\Inventory\Models\Stock;
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
        if ($user && $user->can('warehouse-dashboard-view') && ! $user->can('dashboard-view')) {
            return redirect()->route('inventory.dashboard');
        }

        $isGlobalView = $user && ($user->hasRole(['Super Admin', 'Admin']) || $user->can('view-all-data'));

        $customerQuery = Party::where('type', 'customer');
        $orderQuery = Order::query();

        if (! $isGlobalView) {
            if ($user && $user->can('view_all_customer')) {
                // Can see all customers
            } else {
                $customerQuery->where('created_by', $user->id);
            }

            $allowedStatuses = [];
            if ($user) {
                if ($user->can('orders.view.future_order')) $allowedStatuses[] = 'future_order';
                if ($user->can('orders.view.pending')) {
                    $allowedStatuses[] = 'pending';
                    $allowedStatuses[] = 'pending_confirmation';
                }
                if ($user->can('orders.view.confirmed')) $allowedStatuses[] = 'confirmed';
                if ($user->can('orders.view.processing')) $allowedStatuses[] = 'processing';
                if ($user->can('orders.view.ready_to_ship')) $allowedStatuses[] = 'ready_to_ship';
                if ($user->can('orders.view.dispatched')) $allowedStatuses[] = 'dispatched';
                if ($user->can('orders.view.delivered')) $allowedStatuses[] = 'delivered';
                if ($user->can('orders.view.return_requested')) $allowedStatuses[] = 'return_requested';
                if ($user->can('orders.view.returned')) $allowedStatuses[] = 'returned';
                if ($user->can('orders.view.cancelled')) $allowedStatuses[] = 'cancelled';
            }

            if (empty($allowedStatuses)) {
                $orderQuery->whereRaw('1 = 0');
            } else {
                $orderQuery->whereIn('status', $allowedStatuses);
                if (! ($user && $user->can('view_all_order'))) {
                    $orderQuery->where('created_by', $user->id);
                }
            }
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
            ->whereNotIn('status', ['future_order'])
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
        $twelveMonthsAgo = Carbon::now()->subMonths(11)->startOfMonth();

        $monthlyRevenueRaw = (clone $orderQuery)->whereNotIn('status', ['cancelled', 'returned', 'future_order'])
            ->where('order_date', '>=', $twelveMonthsAgo)
            ->select(
                DB::raw("DATE_FORMAT(order_date, '%Y-%m') as month_year"),
                DB::raw('SUM(net_amount) as total')
            )
            ->groupBy('month_year')
            ->pluck('total', 'month_year')
            ->toArray();

        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $key = $month->format('Y-m');
            $revenue = (float) ($monthlyRevenueRaw[$key] ?? 0);
            $profit = $revenue * 0.2; // Simulated profit margin for UI

            $revenueData[] = [
                'month' => $month->format('M Y'),
                'revenue' => round($revenue, 2),
                'profit' => round($profit, 2),
            ];
        }

        // Daily Revenue (Last 30 Days)
        $dailyRevenueData = [];
        $thirtyDaysAgo = Carbon::now()->subDays(29)->startOfDay();

        $dailyRevenueRaw = (clone $orderQuery)->whereNotIn('status', ['cancelled', 'returned', 'future_order'])
            ->where('order_date', '>=', $thirtyDaysAgo)
            ->select(
                DB::raw('DATE(order_date) as date'),
                DB::raw('SUM(net_amount) as total')
            )
            ->groupBy('date')
            ->pluck('total', 'date')
            ->toArray();

        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $key = $date->toDateString();
            $revenue = (float) ($dailyRevenueRaw[$key] ?? 0);
            $profit = $revenue * 0.2;

            $dailyRevenueData[] = [
                'month' => $date->format('M d'),
                'revenue' => round($revenue, 2),
                'profit' => round($profit, 2),
            ];
        }

        // Customer Growth (Last 30 Days)
        $customerGrowth = [];
        $dailyCustomersRaw = (clone $customerQuery)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('date')
            ->pluck('total', 'date')
            ->toArray();

        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $key = $date->toDateString();
            $newUsers = (int) ($dailyCustomersRaw[$key] ?? 0);

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
            ->whereNotIn('status', ['future_order'])
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
            ->whereNotIn('status', ['future_order'])
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

            $itemsList = $order->items->map(function ($item) {
                $name = $item->product ? $item->product->name : 'Unknown Product';

                return $item->quantity.'x '.$name;
            })->implode(', ');

            return [
                'id'       => $order->order_no,
                'customer' => $order->party ? $order->party->name : 'Unknown',
                'phone'    => $order->party ? $order->party->phone : null,
                'items'    => $itemsList,
                'amount'   => 'Rs '.number_format($order->net_amount, 2),
                'status'   => [
                    'text'  => $order->statusLabel(),
                    'class' => $statusClass,
                ],
                'date'     => $order->order_date ? $order->order_date->format('M d, Y h:i A') : 'N/A',
            ];
        })->toArray();

        // Future Orders
        $futureOrdersRaw = (clone $orderQuery)->with(['party', 'items.product'])
            ->where('status', 'future_order')
            ->orderBy('future_order_date', 'asc')
            ->take(5)
            ->get();

        $futureOrders = $futureOrdersRaw->map(function ($order) {
            $statusClass = 'bg-primary';

            $itemsList = $order->items->map(function ($item) {
                $name = $item->product ? $item->product->name : 'Unknown Product';

                return $item->quantity.'x '.$name;
            })->implode(', ');

            return [
                'id'            => $order->order_no,
                'customer'      => $order->party ? $order->party->name : 'Unknown',
                'phone'         => $order->party ? $order->party->phone : null,
                'items'         => $itemsList,
                'amount'        => 'Rs '.number_format($order->net_amount, 2),
                'status'        => [
                    'text'  => 'Future Order',
                    'class' => $statusClass,
                ],
                'placed_date'   => $order->order_date ? $order->order_date->format('M d, Y') : 'N/A',
                'scheduled_for' => $order->future_order_date ? Carbon::parse($order->future_order_date)->format('M d, Y') : 'N/A',
            ];
        })->toArray();

        $dashboardData = [
            'revenue_monthly' => $revenueData,
            'revenue_daily' => $dailyRevenueData,
            'users' => $customerGrowth,
            'orders' => $orderStatusDistribution,
            'salesByLocation' => $salesByLocation,
            'recentOrders' => $recentOrders,
            'futureOrders' => $futureOrders,
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

    /**
     * Analytics Data API — powers the /analytics dashboard via AJAX.
     * All queries are read-only. No core business logic is modified.
     */
    public function analyticsData(Request $request)
    {
        $period = $request->input('period', 'today');
        $fromRaw = $request->input('from');
        $toRaw = $request->input('to');

        $limit = (int) $request->input('limit', 10);
        if (! in_array($limit, [5, 10, 15, 20])) {
            $limit = 10;
        }

        // ── Build date range ─────────────────────────────────────────────────
        [$start, $end] = $this->analyticsDateRange($period, $fromRaw, $toRaw);

        $startStr = $start->toDateTimeString();
        $endStr = $end->toDateTimeString();

        try {
            // ── KPI: Sales (sale orders, non-cancelled) ───────────────────────
            $salesData = DB::table('orders')
                ->where('type', 'sale')
                ->whereNotIn('status', ['cancelled'])
                ->whereBetween('order_date', [$startStr, $endStr])
                ->whereNull('deleted_at')
                ->selectRaw('SUM(net_amount) as total_amount, COUNT(id) as count')
                ->first();
            $totalSales = $salesData->total_amount ?? 0;
            $totalSalesCount = $salesData->count ?? 0;

            // ── KPI: Purchase (purchase_orders) ───────────────────────────────
            $purchaseData = DB::table('purchase_orders')
                ->whereNotIn('status', ['rejected'])
                ->whereBetween('created_at', [$startStr, $endStr])
                ->whereNull('deleted_at')
                ->selectRaw('SUM(net_amount) as total_amount, COUNT(id) as count')
                ->first();
            $totalPurchase = $purchaseData->total_amount ?? 0;
            $totalPurchaseCount = $purchaseData->count ?? 0;

            // ── Inward / Outward Payment totals summary ───────────────────────
            $inwardTotal = DB::table('payments')
                ->join('orders', 'payments.order_id', '=', 'orders.id')
                ->where('orders.type', 'sale')
                ->whereBetween('payments.payment_date', [$startStr, $endStr])
                ->whereNull('payments.deleted_at')
                ->selectRaw('SUM(CASE WHEN payments.status = "completed" THEN payments.amount ELSE 0 END) as completed,
                             SUM(CASE WHEN payments.status = "pending" THEN payments.amount ELSE 0 END) as pending,
                             COUNT(*) as count')
                ->first();

            $outwardTotal = DB::table('payments')
                ->join('orders', 'payments.order_id', '=', 'orders.id')
                ->where('orders.type', 'purchase')
                ->whereBetween('payments.payment_date', [$startStr, $endStr])
                ->whereNull('payments.deleted_at')
                ->selectRaw('SUM(CASE WHEN payments.status = "completed" THEN payments.amount ELSE 0 END) as completed,
                             SUM(CASE WHEN payments.status = "pending" THEN payments.amount ELSE 0 END) as pending,
                             COUNT(*) as count')
                ->first();

            // ── KPI: Inward Payments & Outward Payments ───────────────────────
            $inwardPayments = $inwardTotal->completed ?? 0;
            $outwardPayments = $outwardTotal->completed ?? 0;

            // ── KPI: Sales Outstanding (unpaid/partial invoices on sale orders) ─
            $salesOutstandingData = DB::table('invoices')
                ->join('orders', 'invoices.order_id', '=', 'orders.id')
                ->where('orders.type', 'sale')
                ->whereIn('invoices.status', ['unpaid', 'partially_paid'])
                ->whereNull('invoices.deleted_at')
                ->selectRaw('SUM(invoices.net_amount) as total_amount, COUNT(invoices.id) as count')
                ->first();
            $salesOutstanding = $salesOutstandingData->total_amount ?? 0;
            $salesOutstandingCount = $salesOutstandingData->count ?? 0;

            // ── KPI: Purchase Outstanding ─────────────────────────────────────
            $purchaseOutstanding = DB::table('parties')
                ->where('type', 'supplier')
                ->where('is_active', true)
                ->sum('outstanding_balance');

            // ── KPI: New Customers (created in period) ────────────────────────
            $newCustomers = DB::table('parties')
                ->where('type', 'customer')
                ->whereBetween('created_at', [$startStr, $endStr])
                ->whereNull('deleted_at')
                ->count();

            // ── KPI: Existing / Repeat Customers (orders_count > 1) ───────────
            $existingCustomers = DB::table('parties')
                ->where('type', 'customer')
                ->where('orders_count', '>', 1)
                ->whereNull('deleted_at')
                ->count();

            // ── Chart: Sales vs Purchase Trend (daily) ────────────────────────
            $salesTrend = DB::table('orders')
                ->select(DB::raw('DATE(order_date) as day'), DB::raw('SUM(net_amount) as total'))
                ->where('type', 'sale')
                ->whereNotIn('status', ['cancelled'])
                ->whereBetween('order_date', [$startStr, $endStr])
                ->whereNull('deleted_at')
                ->groupBy('day')
                ->orderBy('day')
                ->get();

            $purchaseTrend = DB::table('purchase_orders')
                ->select(DB::raw('DATE(created_at) as day'), DB::raw('SUM(net_amount) as total'))
                ->whereNotIn('status', ['rejected'])
                ->whereBetween('created_at', [$startStr, $endStr])
                ->whereNull('deleted_at')
                ->groupBy('day')
                ->orderBy('day')
                ->get();

            // ── Customer: New vs Existing donut ───────────────────────────────
            // new = created in period, existing = had order before period
            $totalCustomersInPeriod = DB::table('parties')
                ->where('type', 'customer')
                ->whereNull('deleted_at')
                ->count();

            // ── Sales State-Wise ──────────────────────────────────────────────
            $stateWiseSales = DB::table('orders')
                ->select('shipping_state', DB::raw('SUM(net_amount) as total'), DB::raw('COUNT(id) as order_count'))
                ->where('type', 'sale')
                ->whereNotIn('status', ['cancelled'])
                ->whereBetween('order_date', [$startStr, $endStr])
                ->whereNotNull('shipping_state')
                ->whereNull('deleted_at')
                ->groupBy('shipping_state')
                ->orderByDesc('total')
                ->limit(10)
                ->get();

            // ── Top Customers ─────────────────────────────────────────────────
            $topCustomers = DB::table('parties')
                ->join('orders', function ($j) use ($startStr, $endStr) {
                    $j->on('orders.party_id', '=', 'parties.id')
                        ->where('orders.type', 'sale')
                        ->whereNotIn('orders.status', ['cancelled'])
                        ->whereBetween('orders.order_date', [$startStr, $endStr])
                        ->whereNull('orders.deleted_at');
                })
                ->where('parties.type', 'customer')
                ->whereNull('parties.deleted_at')
                ->select(
                    'parties.id',
                    DB::raw("CONCAT(COALESCE(parties.firstname,''), ' ', COALESCE(parties.lastname,'')) as name"),
                    'parties.phone',
                    'parties.outstanding_balance',
                    DB::raw('COUNT(orders.id) as order_count'),
                    DB::raw('SUM(orders.net_amount) as lifetime_value')
                )
                ->groupBy('parties.id', 'parties.firstname', 'parties.lastname', 'parties.phone', 'parties.outstanding_balance')
                ->orderByDesc('lifetime_value')
                ->limit($limit)
                ->get();

            // ── Inventory: In / Low / Zero Stock ─────────────────────────────
            $stockStats = DB::table('stocks')
                ->join('products', 'stocks.product_id', '=', 'products.id')
                ->whereNull('stocks.deleted_at')
                ->selectRaw('
                    SUM(CASE WHEN stocks.quantity > products.min_stock_level AND stocks.quantity > 0 THEN 1 ELSE 0 END) as in_stock,
                    SUM(CASE WHEN stocks.quantity <= products.min_stock_level AND stocks.quantity > 0 THEN 1 ELSE 0 END) as low_stock,
                    SUM(CASE WHEN stocks.quantity <= 0 THEN 1 ELSE 0 END) as zero_stock
                ')
                ->first();

            $inStock = $stockStats->in_stock ?? 0;
            $lowStock = $stockStats->low_stock ?? 0;
            $zeroStock = $stockStats->zero_stock ?? 0;

            // ── Best Selling Products ─────────────────────────────────────────
            $bestSelling = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('orders.type', 'sale')
                ->whereNotIn('orders.status', ['cancelled'])
                ->whereBetween('orders.order_date', [$startStr, $endStr])
                ->whereNull('orders.deleted_at')
                ->select(
                    'products.id',
                    'products.name',
                    'products.sku',
                    DB::raw('SUM(order_items.quantity) as total_qty'),
                    DB::raw('SUM(order_items.total_amount) as total_revenue')
                )
                ->groupBy('products.id', 'products.name', 'products.sku')
                ->orderByDesc('total_qty')
                ->limit($limit)
                ->get();

            // ── Least Selling Products ────────────────────────────────────────
            $leastSelling = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('orders.type', 'sale')
                ->whereNotIn('orders.status', ['cancelled'])
                ->whereBetween('orders.order_date', [$startStr, $endStr])
                ->whereNull('orders.deleted_at')
                ->select(
                    'products.id',
                    'products.name',
                    'products.sku',
                    DB::raw('SUM(order_items.quantity) as total_qty'),
                    DB::raw('SUM(order_items.total_amount) as total_revenue')
                )
                ->groupBy('products.id', 'products.name', 'products.sku')
                ->orderBy('total_qty')
                ->limit($limit)
                ->get();

            // ── Low Stock Products ────────────────────────────────────────────
            $lowStockProducts = DB::table('stocks')
                ->join('products', 'stocks.product_id', '=', 'products.id')
                ->whereNull('stocks.deleted_at')
                ->where('stocks.quantity', '>', 0)
                ->whereColumn('stocks.quantity', '<=', 'products.min_stock_level')
                ->select(
                    'products.id',
                    'products.name',
                    'products.sku',
                    'products.min_stock_level',
                    'stocks.quantity'
                )
                ->orderBy('stocks.quantity')
                ->limit($limit)
                ->get();

            // ── Top Vendors / Suppliers ───────────────────────────────────────
            $topVendors = DB::table('purchase_orders')
                ->join('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.id')
                ->whereNotIn('purchase_orders.status', ['rejected'])
                ->whereBetween('purchase_orders.created_at', [$startStr, $endStr])
                ->whereNull('purchase_orders.deleted_at')
                ->select(
                    'suppliers.id',
                    DB::raw("COALESCE(suppliers.company_name, CONCAT(COALESCE(suppliers.firstname,''), ' ', COALESCE(suppliers.lastname,''))) as name"),
                    DB::raw('COUNT(purchase_orders.id) as po_count'),
                    DB::raw('SUM(purchase_orders.net_amount) as total_value')
                )
                ->groupBy('suppliers.id', 'suppliers.company_name', 'suppliers.firstname', 'suppliers.lastname')
                ->orderByDesc('total_value')
                ->limit($limit)
                ->get();

            // ── Purchase Invoice Due (POs pending/approved not yet received) ───
            $purchaseDue = DB::table('purchase_orders')
                ->join('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.id')
                ->whereIn('purchase_orders.status', ['pending', 'approved'])
                ->whereNull('purchase_orders.deleted_at')
                ->select(
                    'purchase_orders.id',
                    'purchase_orders.po_number',
                    DB::raw("COALESCE(suppliers.company_name, CONCAT(COALESCE(suppliers.firstname,''), ' ', COALESCE(suppliers.lastname,''))) as supplier_name"),
                    'purchase_orders.net_amount',
                    'purchase_orders.status',
                    'purchase_orders.expected_delivery_date',
                    'purchase_orders.created_at'
                )
                ->orderBy('purchase_orders.expected_delivery_date')
                ->limit($limit)
                ->get();

            // ── Sales Invoice Due (unpaid/partially paid invoices) ─────────────
            $salesInvoiceDue = DB::table('invoices')
                ->join('orders', 'invoices.order_id', '=', 'orders.id')
                ->leftJoin('parties', 'orders.party_id', '=', 'parties.id')
                ->where('orders.type', 'sale')
                ->whereIn('invoices.status', ['unpaid', 'partially_paid'])
                ->whereNull('invoices.deleted_at')
                ->select(
                    'invoices.id',
                    'invoices.invoice_no',
                    'invoices.net_amount',
                    'invoices.status',
                    'invoices.due_date',
                    'orders.order_no',
                    DB::raw("CONCAT(COALESCE(parties.firstname,''), ' ', COALESCE(parties.lastname,'')) as customer_name"),
                    'parties.phone'
                )
                ->orderBy('invoices.due_date')
                ->limit($limit)
                ->get();

            // ── Login Activity ────────────────────────────────────────────────
            $loginsToday = DB::table('login_histories')
                ->whereDate('attempted_at', Carbon::today())
                ->count();

            $loginsInPeriod = DB::table('login_histories')
                ->whereBetween('attempted_at', [$startStr, $endStr])
                ->count();

            $activeUsers = DB::table('users')
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->count();

            return response()->json([
                'period' => $period,
                'dateFrom' => $start->toDateString(),
                'dateTo' => $end->toDateString(),
                'kpis' => [
                    'totalSales' => round((float) $totalSales, 2),
                    'totalSalesCount' => (int) $totalSalesCount,
                    'totalPurchase' => round((float) $totalPurchase, 2),
                    'totalPurchaseCount' => (int) $totalPurchaseCount,
                    'inwardPayments' => round((float) $inwardPayments, 2),
                    'outwardPayments' => round((float) $outwardPayments, 2),
                    'salesOutstanding' => round((float) $salesOutstanding, 2),
                    'salesOutstandingCount' => (int) $salesOutstandingCount,
                    'purchaseOutstanding' => round((float) $purchaseOutstanding, 2),
                    'newCustomers' => (int) $newCustomers,
                    'existingCustomers' => (int) $existingCustomers,
                    'totalCustomers' => (int) $totalCustomersInPeriod,
                    'inStock' => (int) $inStock,
                    'lowStock' => (int) $lowStock,
                    'zeroStock' => (int) $zeroStock,
                    'loginsToday' => (int) $loginsToday,
                    'loginsInPeriod' => (int) $loginsInPeriod,
                    'activeUsers' => (int) $activeUsers,
                ],
                'charts' => [
                    'salesTrend' => $salesTrend,
                    'purchaseTrend' => $purchaseTrend,
                    'stateWiseSales' => $stateWiseSales,
                ],
                'tables' => [
                    'topCustomers' => $topCustomers,
                    'bestSelling' => $bestSelling,
                    'leastSelling' => $leastSelling,
                    'lowStockProducts' => $lowStockProducts,
                    'topVendors' => $topVendors,
                    'purchaseDue' => $purchaseDue,
                    'salesInvoiceDue' => $salesInvoiceDue,
                ],
                'payments' => [
                    'inward' => $inwardTotal,
                    'outward' => $outwardTotal,
                ],
            ]);

        } catch (\Throwable $e) {
            // Gracefully return empty structure if tables are missing/empty
            return response()->json([
                'period' => $period,
                'dateFrom' => $start->toDateString(),
                'dateTo' => $end->toDateString(),
                'error' => 'Data not available: '.$e->getMessage(),
                'kpis' => (object) [
                    'totalSales' => 0,
                    'totalSalesCount' => 0,
                    'totalPurchase' => 0,
                    'totalPurchaseCount' => 0,
                    'inwardPayments' => 0,
                    'outwardPayments' => 0,
                    'salesOutstanding' => 0,
                    'salesOutstandingCount' => 0,
                    'purchaseOutstanding' => 0,
                    'newCustomers' => 0,
                    'existingCustomers' => 0,
                    'totalCustomers' => 0,
                    'inStock' => 0,
                    'lowStock' => 0,
                    'zeroStock' => 0,
                    'loginsToday' => 0,
                    'loginsInPeriod' => 0,
                    'activeUsers' => 0,
                ],
                'charts' => (object) [
                    'salesTrend' => [],
                    'purchaseTrend' => [],
                    'stateWiseSales' => [],
                ],
                'tables' => (object) [
                    'topCustomers' => [],
                    'bestSelling' => [],
                    'leastSelling' => [],
                    'lowStockProducts' => [],
                    'topVendors' => [],
                    'purchaseDue' => [],
                    'salesInvoiceDue' => [],
                ],
                'payments' => (object) [
                    'inward' => null,
                    'outward' => null,
                ],
            ]);
        }
    }

    /**
     * Resolve start/end Carbon instances from period or custom from/to.
     */
    private function analyticsDateRange(string $period, ?string $from, ?string $to): array
    {
        if ($period === 'custom' && $from && $to) {
            return [
                Carbon::parse($from)->startOfDay(),
                Carbon::parse($to)->endOfDay(),
            ];
        }

        $now = Carbon::now();

        return match ($period) {
            'today' => [$now->copy()->startOfDay(),            $now->copy()->endOfDay()],
            'yesterday' => [$now->copy()->subDay()->startOfDay(),  $now->copy()->subDay()->endOfDay()],
            'last7' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            'last30' => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
            'last_month' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()],
            'last3m' => [$now->copy()->subMonths(3)->startOfDay(), $now->copy()->endOfDay()],
            'last6m' => [$now->copy()->subMonths(6)->startOfDay(), $now->copy()->endOfDay()],
            'current_month' => [$now->copy()->startOfMonth(),           $now->copy()->endOfMonth()],
            'current_year' => [$now->copy()->startOfYear(),            $now->copy()->endOfYear()],
            default => [$now->copy()->startOfDay(),             $now->copy()->endOfDay()],
        };
    }

    public function users()
    {
        $teams = \App\Modules\Users\Models\Team::where('is_active', true)->get();
        return view('users.index', compact('teams'));
    }

    public function teams()
    {
        return view('teams.index');
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

    public function departments()
    {
        return view('users.departments');
    }

    public function attendances()
    {
        return view('users.attendances');
    }

    public function leaves()
    {
        return view('users.leaves');
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
        return view('reports');
    }

    public function exportReports(Request $request)
    {
        $type = $request->get('type', 'sales_overview');
        $dateFrom = $request->get('from');
        $dateTo = $request->get('to');

        return match ($type) {
            'sales_overview' => $this->exportSalesOverview($dateFrom, $dateTo),
            'product_sales' => $this->exportProductSales($dateFrom, $dateTo),
            'sales_region' => $this->exportSalesRegion($dateFrom, $dateTo),
            'payment_reconciliation' => $this->exportPaymentReconciliation($dateFrom, $dateTo),
            'stock_valuation' => $this->exportStockValuation(),
            'stock_ledger' => $this->exportStockLedger($dateFrom, $dateTo),
            'low_stock' => $this->exportLowStock(),
            'po_fulfillment' => $this->exportPOFulfillment($dateFrom, $dateTo),
            'grn_discrepancy' => $this->exportGRNDiscrepancy($dateFrom, $dateTo),
            'call_performance' => $this->exportCallPerformance($dateFrom, $dateTo),
            'call_tagging' => $this->exportCallTagging($dateFrom, $dateTo),
            'customer_retention' => $this->exportCustomerRetention($dateFrom, $dateTo),
            'return_analysis' => $this->exportReturnAnalysis($dateFrom, $dateTo),
            'audit_trail' => $this->exportAuditTrail($dateFrom, $dateTo),
            'suppliers_report' => $this->exportSuppliersReport($dateFrom, $dateTo),
            'attendance_report' => $this->exportAttendanceReport($dateFrom, $dateTo),
            default => abort(404, 'Report type not yet implemented.'),
        };
    }

    private function exportStockValuation()
    {
        $fileName = 'stock_valuation_'.now()->format('Y-m-d_H-i-s').'.csv';
        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = ['Warehouse', 'SKU', 'Product Name', 'Current Qty', 'Reserved Qty', 'Available Qty'];

        $callback = function () use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            Stock::with(['warehouse', 'product'])->chunk(100, function ($stocks) use ($file) {
                foreach ($stocks as $stock) {
                    $row = [
                        $stock->warehouse ? $stock->warehouse->name : '',
                        $stock->product ? $stock->product->sku : '',
                        $stock->product ? $stock->product->name : '',
                        $stock->quantity,
                        $stock->reserved_qty,
                        ($stock->quantity - $stock->reserved_qty),
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
            $query->where('order_date', '>=', Carbon::parse($dateFrom)->startOfDay());
        }
        if ($dateTo) {
            $query->where('order_date', '<=', Carbon::parse($dateTo)->endOfDay());
        }

        $query->orderBy('id');

        $fileName = 'sales_report_'.now()->format('Y-m-d_H-i-s').'.csv';

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = [
            'Sale Order Id', 'Farmer Id', 'Farmer Name', 'Mobile',
            'Created On Date', 'Created On Time', 'Created By', 'Modified On Date',
            'Modified On Time', 'Modified By', 'Advance Order Date', 'Village',
            'PostOffice', 'Taluka', 'District', 'PinCode', 'State', 'Grand Total',
            'Status', 'Item Sku', 'Item Name', 'Item Quantity', 'Item Unit Price',
            'Item Total Price', 'Item Retail Store Discount',
            'Order Type', 'FC Name',
        ];

        $callback = function () use ($query, $columns) {
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
                            $order->future_order_date ? Carbon::parse($order->future_order_date)->format('Y-m-d') : '', // Advance Order Date
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
                            $order->future_order_date ? Carbon::parse($order->future_order_date)->format('Y-m-d') : '', // Advance Order Date
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
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];
    }

    private function exportProductSales($dateFrom, $dateTo)
    {
        $headers = $this->getCsvHeaders('product_sales_'.now()->format('Ymd_His').'.csv');
        $columns = ['Product Name', 'SKU', 'Category', 'Brand', 'Total Units Sold', 'Avg Unit Price', 'Total Revenue', 'Current Total Stock'];
        $callback = function () use ($dateFrom, $dateTo, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            $query = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
                ->select(
                    'products.id', 'products.name', 'products.sku', 
                    'categories.name as category_name', 'brands.name as brand_name',
                    DB::raw('SUM(order_items.quantity) as total_qty'), 
                    DB::raw('AVG(order_items.unit_price) as avg_price'), 
                    DB::raw('SUM(order_items.total_amount) as total_revenue')
                )
                ->where('orders.type', 'sale')
                ->groupBy('products.id', 'products.name', 'products.sku', 'categories.name', 'brands.name')
                ->orderByDesc('total_qty');

            if ($dateFrom) {
                $query->where('orders.order_date', '>=', \Carbon\Carbon::parse($dateFrom)->startOfDay());
            }
            if ($dateTo) {
                $query->where('orders.order_date', '<=', \Carbon\Carbon::parse($dateTo)->endOfDay());
            }

            $cursor = $query->cursor();
            foreach ($cursor as $row) {
                $stock = DB::table('stocks')->where('product_id', $row->id)->sum('quantity');
                fputcsv($file, [$row->name, $row->sku, $row->category_name, $row->brand_name, $row->total_qty, round((float) $row->avg_price, 2), $row->total_revenue, $stock]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportSalesRegion($dateFrom, $dateTo)
    {
        $headers = $this->getCsvHeaders('sales_region_'.now()->format('Ymd_His').'.csv');
        $columns = ['State', 'District', 'Taluka', 'Village', 'Order Count', 'Total Revenue', 'AOV'];
        $callback = function () use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            $query = DB::table('orders')
                ->select('shipping_state', 'shipping_district', 'shipping_taluka', 'shipping_village_name', DB::raw('COUNT(id) as order_count'), DB::raw('SUM(net_amount) as total_revenue'), DB::raw('AVG(net_amount) as aov'))
                ->groupBy('shipping_state', 'shipping_district', 'shipping_taluka', 'shipping_village_name');

            if ($dateFrom) {
                $query->where('orders.order_date', '>=', Carbon::parse($dateFrom)->startOfDay());
            }
            if ($dateTo) {
                $query->where('orders.order_date', '<=', Carbon::parse($dateTo)->endOfDay());
            }

            $cursor = $query->cursor();

            foreach ($cursor as $row) {
                fputcsv($file, [$row->shipping_state, $row->shipping_district, $row->shipping_taluka, $row->shipping_village_name, $row->order_count, $row->total_revenue, round((float) $row->aov, 2)]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportPaymentReconciliation($dateFrom, $dateTo)
    {
        $headers = $this->getCsvHeaders('payment_reconciliation_'.now()->format('Ymd_His').'.csv');
        $columns = ['Payment ID', 'Order No', 'Customer Name', 'Customer Phone', 'Payment Mode', 'Amount', 'Status', 'Transaction ID', 'Reference', 'Payment Date'];
        $callback = function () use ($dateFrom, $dateTo, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            $query = DB::table('payments')
                ->leftJoin('orders', 'payments.order_id', '=', 'orders.id')
                ->leftJoin('parties', 'orders.party_id', '=', 'parties.id')
                ->select(
                    'payments.id', 'orders.order_no', 'parties.firstname', 'parties.lastname', 'parties.phone',
                    'payments.payment_method as payment_mode', 'payments.amount', 'payments.status', 'payments.transaction_id', 
                    'payments.payment_no as reference_number', 'payments.created_at'
                )
                ->orderBy('payments.id');

            if ($dateFrom) {
                $query->where('payments.created_at', '>=', \Carbon\Carbon::parse($dateFrom)->startOfDay());
            }
            if ($dateTo) {
                $query->where('payments.created_at', '<=', \Carbon\Carbon::parse($dateTo)->endOfDay());
            }

            $query->chunk(100, function ($rows) use ($file) {
                foreach ($rows as $row) {
                    $name = trim($row->firstname . ' ' . $row->lastname);
                    fputcsv($file, [$row->id, $row->order_no, $name, $row->phone, $row->payment_mode, $row->amount, $row->status, $row->transaction_id, $row->reference_number, $row->created_at]);
                }
            });
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportStockLedger($dateFrom, $dateTo)
    {
        $headers = $this->getCsvHeaders('stock_ledger_'.now()->format('Ymd_His').'.csv');
        $columns = ['Date', 'Type', 'SKU', 'Product Name', 'Warehouse', 'Qty Before', 'Qty Moved', 'Qty After'];
        $callback = function () use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            $query = DB::table('stock_movements')
                ->leftJoin('products', 'stock_movements.product_id', '=', 'products.id')
                ->leftJoin('warehouses', 'stock_movements.warehouse_id', '=', 'warehouses.id')
                ->select('stock_movements.created_at', 'stock_movements.type', 'products.sku', 'products.name', 'warehouses.name as warehouse_name', 'stock_movements.quantity_before', 'stock_movements.quantity', 'stock_movements.quantity_after')
                ->orderBy('stock_movements.id');

            if ($dateFrom) {
                $query->where('stock_movements.created_at', '>=', Carbon::parse($dateFrom)->startOfDay());
            }
            if ($dateTo) {
                $query->where('stock_movements.created_at', '<=', Carbon::parse($dateTo)->endOfDay());
            }

            $query->chunk(100, function ($rows) use ($file) {
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
        $headers = $this->getCsvHeaders('low_stock_'.now()->format('Ymd_His').'.csv');
        $columns = ['SKU', 'Product Name', 'Warehouse', 'Current Qty', 'Reserved Qty', 'Available Qty'];
        $callback = function () use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            DB::table('stocks')
                ->leftJoin('products', 'stocks.product_id', '=', 'products.id')
                ->leftJoin('warehouses', 'stocks.warehouse_id', '=', 'warehouses.id')
                ->select(
                    'products.sku', 'products.name', 'warehouses.name as warehouse_name', 
                    'stocks.quantity', 'stocks.reserved_qty', 
                    DB::raw('(stocks.quantity - stocks.reserved_qty) as available_quantity')
                )
                ->whereRaw('(stocks.quantity - stocks.reserved_qty) < 10')
                ->orderBy('stocks.id')
                ->chunk(100, function ($rows) use ($file) {
                    foreach ($rows as $row) {
                        fputcsv($file, [$row->sku, $row->name, $row->warehouse_name, $row->quantity, $row->reserved_qty, $row->available_quantity]);
                    }
                });
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportPOFulfillment($dateFrom, $dateTo)
    {
        $headers = $this->getCsvHeaders('po_fulfillment_'.now()->format('Ymd_His').'.csv');
        $columns = ['PO Number', 'Supplier', 'Created Date', 'Expected Delivery', 'Total Amount', 'Status'];
        $callback = function () use ($dateFrom, $dateTo, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            $query = DB::table('purchase_orders')
                ->leftJoin('parties', 'purchase_orders.supplier_id', '=', 'parties.id')
                ->select(
                    'purchase_orders.po_number', 
                    DB::raw("CONCAT(COALESCE(parties.firstname,''), ' ', COALESCE(parties.lastname,'')) as supplier_name"),
                    'purchase_orders.created_at', 
                    'purchase_orders.expected_delivery_date', 
                    'purchase_orders.net_amount', 
                    'purchase_orders.status'
                )
                ->orderBy('purchase_orders.id');

            if ($dateFrom) {
                $query->where('purchase_orders.created_at', '>=', \Carbon\Carbon::parse($dateFrom)->startOfDay());
            }
            if ($dateTo) {
                $query->where('purchase_orders.created_at', '<=', \Carbon\Carbon::parse($dateTo)->endOfDay());
            }

            $query->chunk(100, function ($rows) use ($file) {
                foreach ($rows as $row) {
                    fputcsv($file, [$row->po_number, $row->supplier_name, $row->created_at, $row->expected_delivery_date, $row->net_amount, $row->status]);
                }
            });
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportGRNDiscrepancy($dateFrom, $dateTo)
    {
        $headers = $this->getCsvHeaders('grn_discrepancy_'.now()->format('Ymd_His').'.csv');
        $columns = ['GRN Number', 'SKU', 'Received Qty', 'Accepted Qty', 'Rejected Qty'];
        $callback = function () use ($dateFrom, $dateTo, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            $query = DB::table('goods_receipt_items')
                ->leftJoin('goods_receipts', 'goods_receipt_items.goods_receipt_id', '=', 'goods_receipts.id')
                ->leftJoin('products', 'goods_receipt_items.product_id', '=', 'products.id')
                ->select(
                    'goods_receipts.grn_number', 
                    'products.sku', 
                    'goods_receipt_items.received_qty', 
                    'goods_receipt_items.accepted_qty', 
                    'goods_receipt_items.rejected_qty'
                )
                ->orderBy('goods_receipt_items.id');

            if ($dateFrom) {
                $query->where('goods_receipt_items.created_at', '>=', \Carbon\Carbon::parse($dateFrom)->startOfDay());
            }
            if ($dateTo) {
                $query->where('goods_receipt_items.created_at', '<=', \Carbon\Carbon::parse($dateTo)->endOfDay());
            }

            $query->chunk(100, function ($rows) use ($file) {
                foreach ($rows as $row) {
                    fputcsv($file, [$row->grn_number, $row->sku, $row->received_qty, $row->accepted_qty, $row->rejected_qty]);
                }
            });
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportCallPerformance($dateFrom, $dateTo)
    {
        $headers = $this->getCsvHeaders('call_performance_'.now()->format('Ymd_His').'.csv');
        $columns = ['Call ID', 'Agent Name', 'Customer ID', 'Level 1 Tag', 'Level 2 Tag', 'Level 3 Tag', 'Notes', 'Logged Date'];
        $callback = function () use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            $query = CallLog::with(['agent', 'tagL1', 'tagL2', 'tagL3'])->chunk(100, function ($logs) use ($file) {
                foreach ($logs as $log) {
                    fputcsv($file, [
                        $log->id,
                        $log->agent ? $log->agent->name : '',
                        $log->customer_id,
                        $log->tagL1 ? $log->tagL1->name : '',
                        $log->tagL2 ? $log->tagL2->name : '',
                        $log->tagL3 ? $log->tagL3->name : '',
                        $log->notes,
                        $log->created_at,
                    ]);
                }
            });
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportCallTagging($dateFrom, $dateTo)
    {
        $headers = $this->getCsvHeaders('call_tagging_'.now()->format('Ymd_His').'.csv');
        $columns = ['Call ID', 'Agent Name', 'Customer ID', 'Level 1 Tag', 'Level 2 Tag', 'Level 3 Tag', 'Dynamic Form Data', 'Notes', 'Logged Date'];
        $callback = function () use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            $query = CallLog::with(['agent', 'tagL1', 'tagL2', 'tagL3', 'metas'])->chunk(100, function ($logs) use ($file) {
                foreach ($logs as $log) {
                    $metaString = $log->metas->map(fn ($m) => $m->key.': '.$m->value)->implode(' | ');

                    fputcsv($file, [
                        $log->id,
                        $log->agent ? $log->agent->name : '',
                        $log->customer_id,
                        $log->tagL1 ? $log->tagL1->name : '',
                        $log->tagL2 ? $log->tagL2->name : '',
                        $log->tagL3 ? $log->tagL3->name : '',
                        $metaString,
                        $log->notes,
                        $log->created_at,
                    ]);
                }
            });
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportCustomerRetention($dateFrom, $dateTo)
    {
        $headers = $this->getCsvHeaders('customer_retention_'.now()->format('Ymd_His').'.csv');
        $columns = ['Customer ID', 'Name', 'Email', 'Mobile', 'Joined Date', 'Wallet Balance', 'Credit Limit', 'Credit Days', 'Total Orders', 'LTV'];
        $callback = function () use ($dateFrom, $dateTo, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            $query = DB::table('parties')
                ->leftJoin('orders', 'parties.id', '=', 'orders.party_id')
                ->select(
                    'parties.id', 'parties.firstname', 'parties.lastname', 'parties.email', 'parties.phone', 
                    'parties.wallet_balance', 'parties.credit_limit', 'parties.credit_days', 'parties.created_at', 
                    DB::raw('COUNT(orders.id) as total_orders'), DB::raw('SUM(orders.net_amount) as ltv')
                )
                ->where('parties.type', 'customer')
                ->groupBy('parties.id', 'parties.firstname', 'parties.lastname', 'parties.email', 'parties.phone', 'parties.wallet_balance', 'parties.credit_limit', 'parties.credit_days', 'parties.created_at')
                ->orderByDesc('ltv');

            if ($dateFrom) {
                $query->where('parties.created_at', '>=', \Carbon\Carbon::parse($dateFrom)->startOfDay());
            }
            if ($dateTo) {
                $query->where('parties.created_at', '<=', \Carbon\Carbon::parse($dateTo)->endOfDay());
            }

            $cursor = $query->cursor();

            foreach ($cursor as $row) {
                $name = trim($row->firstname . ' ' . $row->lastname);
                fputcsv($file, [$row->id, $name, $row->email, $row->phone, $row->created_at, $row->wallet_balance, $row->credit_limit, $row->credit_days, $row->total_orders, $row->ltv]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportReturnAnalysis($dateFrom, $dateTo)
    {
        $headers = $this->getCsvHeaders('return_analysis_'.now()->format('Ymd_His').'.csv');
        $columns = ['Return ID', 'Return No', 'Order No', 'Reason', 'Status', 'Refund Amount'];
        $callback = function () use ($dateFrom, $dateTo, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            $query = DB::table('order_returns')
                ->leftJoin('orders', 'order_returns.order_id', '=', 'orders.id')
                ->select('order_returns.id', 'order_returns.return_no', 'orders.order_no', 'order_returns.reason', 'order_returns.status', 'order_returns.refund_amount')
                ->orderBy('order_returns.id');

            if ($dateFrom) {
                $query->where('order_returns.created_at', '>=', \Carbon\Carbon::parse($dateFrom)->startOfDay());
            }
            if ($dateTo) {
                $query->where('order_returns.created_at', '<=', \Carbon\Carbon::parse($dateTo)->endOfDay());
            }

            $query->chunk(100, function ($rows) use ($file) {
                foreach ($rows as $row) {
                    fputcsv($file, [$row->id, $row->return_no, $row->order_no, $row->reason, $row->status, $row->refund_amount]);
                }
            });
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportAuditTrail($dateFrom, $dateTo)
    {
        $headers = $this->getCsvHeaders('audit_trail_'.now()->format('Ymd_His').'.csv');
        $columns = ['Timestamp', 'User', 'Action', 'Subject Type', 'Subject ID'];
        $callback = function () use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            $query = DB::table('activity_log')
                ->leftJoin('users', 'activity_log.causer_id', '=', 'users.id')
                ->select('activity_log.created_at', 'users.name as user_name', 'activity_log.description', 'activity_log.subject_type', 'activity_log.subject_id')
                ->orderByDesc('activity_log.id');

            if ($dateFrom) {
                $query->where('activity_log.created_at', '>=', Carbon::parse($dateFrom)->startOfDay());
            }
            if ($dateTo) {
                $query->where('activity_log.created_at', '<=', Carbon::parse($dateTo)->endOfDay());
            }

            $query->chunk(100, function ($rows) use ($file) {
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
            
        $returnReasons = \App\Modules\Orders\Models\ReturnReason::where('is_active', true)->orderBy('id')->get();

        return view('shipping.shipments', compact('carriersList', 'returnReasons'));
    }

    public function shippingServices()
    {
        return view('shipping.services');
    }

    private function exportSuppliersReport($dateFrom, $dateTo)
    {
        $headers = $this->getCsvHeaders('suppliers_report_'.now()->format('Ymd_His').'.csv');
        $columns = ['Supplier ID', 'Name', 'Company', 'Email', 'Phone', 'GST No', 'PAN No', 'State', 'City', 'Wallet Balance'];
        $callback = function () use ($dateFrom, $dateTo, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            $query = DB::table('parties')
                ->leftJoin('party_addresses', 'parties.id', '=', 'party_addresses.party_id')
                ->select(
                    'parties.id', 'parties.firstname', 'parties.lastname', 'parties.company_name', 
                    'parties.email', 'parties.phone', 'parties.gst_no', 'parties.pan_no', 'parties.wallet_balance',
                    'party_addresses.state', 'party_addresses.city'
                )
                ->where('parties.type', 'supplier')
                ->where(function($q) {
                    $q->where('party_addresses.is_default', true)
                      ->orWhereNull('party_addresses.id');
                });

            if ($dateFrom) {
                $query->where('parties.created_at', '>=', \Carbon\Carbon::parse($dateFrom)->startOfDay());
            }
            if ($dateTo) {
                $query->where('parties.created_at', '<=', \Carbon\Carbon::parse($dateTo)->endOfDay());
            }

            $cursor = $query->cursor();
            foreach ($cursor as $row) {
                $name = trim($row->firstname . ' ' . $row->lastname);
                fputcsv($file, [$row->id, $name, $row->company_name, $row->email, $row->phone, $row->gst_no, $row->pan_no, $row->state, $row->city, $row->wallet_balance]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportAttendanceReport($dateFrom, $dateTo)
    {
        $headers = $this->getCsvHeaders('attendance_report_'.now()->format('Ymd_His').'.csv');
        $columns = ['Date', 'User Name', 'Role', 'Status', 'Check In', 'Check Out', 'Total Hours', 'Notes'];
        $callback = function () use ($dateFrom, $dateTo, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            
            $query = DB::table('attendances')
                ->leftJoin('users', 'attendances.user_id', '=', 'users.id')
                ->select('attendances.*', 'users.name as user_name')
                ->orderBy('attendances.date', 'desc');

            if ($dateFrom) {
                $query->where('attendances.date', '>=', \Carbon\Carbon::parse($dateFrom)->startOfDay());
            }
            if ($dateTo) {
                $query->where('attendances.date', '<=', \Carbon\Carbon::parse($dateTo)->endOfDay());
            }

            $query->chunk(100, function ($rows) use ($file) {
                foreach ($rows as $row) {
                    fputcsv($file, [
                        $row->date, $row->user_name, 'Staff', $row->status, 
                        $row->check_in, $row->check_out, 'N/A', $row->notes
                    ]);
                }
            });
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
