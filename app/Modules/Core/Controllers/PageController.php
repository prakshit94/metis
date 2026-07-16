<?php

namespace App\Modules\Core\Controllers;

use Illuminate\Http\Request;
use App\Modules\Orders\Models\Order;
use App\Modules\Customers\Models\Party;
use App\Modules\Catalog\Models\Product;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PageController extends Controller
{
    public function dashboard()
    {
        // 1. Top Metrics
        $totalCustomers = Party::where('type', 'customer')->count();
        $totalRevenue = Order::whereNotIn('status', ['cancelled', 'returned'])->sum('net_amount');
        $totalOrders = Order::count();
        $totalProducts = Product::count();

        // 2. Chart Data
        
        // Revenue (Last 12 Months for simplicity)
        $revenueData = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $revenue = Order::whereNotIn('status', ['cancelled', 'returned'])
                ->whereYear('order_date', $month->year)
                ->whereMonth('order_date', $month->month)
                ->sum('net_amount');
            $profit = $revenue * 0.2; // Simulated profit margin for UI
            
            $revenueData[] = [
                'month' => $month->format('M Y'),
                'revenue' => round($revenue, 2),
                'profit' => round($profit, 2)
            ];
        }

        // Daily Revenue (Last 30 Days)
        $dailyRevenueData = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $revenue = Order::whereNotIn('status', ['cancelled', 'returned'])
                ->whereDate('order_date', $date->toDateString())
                ->sum('net_amount');
            $profit = $revenue * 0.2;
            
            $dailyRevenueData[] = [
                'month' => $date->format('M d'),
                'revenue' => round($revenue, 2),
                'profit' => round($profit, 2)
            ];
        }

        // Customer Growth (Last 30 Days)
        $customerGrowth = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $newUsers = Party::where('type', 'customer')
                ->whereDate('created_at', $date->toDateString())
                ->count();
            
            $customerGrowth[] = [
                'day' => 30 - $i,
                'newUsers' => $newUsers,
                'activeUsers' => rand(100, 500) // Keep some simulation for active users if not tracked
            ];
        }

        // Order Status Distribution
        $orderStatusRaw = Order::select('status', DB::raw('count(*) as total'))
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
        $salesByLocationRaw = Order::whereNotNull('shipping_state')
            ->select('shipping_state', DB::raw('SUM(net_amount) as total_sales'))
            ->whereNotIn('status', ['cancelled', 'returned'])
            ->groupBy('shipping_state')
            ->orderByDesc('total_sales')
            ->limit(10)
            ->get();
            
        $salesByLocation = $salesByLocationRaw->map(function ($item) {
            return [
                'name' => $item->shipping_state,
                'value' => round($item->total_sales, 2)
            ];
        })->toArray();

        // Recent Orders
        $recentOrdersRaw = Order::with('party')
            ->latest('order_date')
            ->take(5)
            ->get();
            
        $recentOrders = $recentOrdersRaw->map(function ($order) {
            $statusClass = match($order->lifecycleStatus()) {
                'delivered', 'completed' => 'bg-success',
                'pending', 'confirmed' => 'bg-warning',
                'dispatched', 'shipped', 'ready_to_ship', 'processing' => 'bg-info',
                'cancelled', 'returned' => 'bg-danger',
                default => 'bg-secondary',
            };
            
            return [
                'id' => $order->order_no,
                'customer' => $order->party ? $order->party->name : 'Unknown',
                'amount' => 'Rs ' . number_format($order->net_amount, 2),
                'status' => [
                    'text' => $order->statusLabel(),
                    'class' => $statusClass
                ],
                'date' => $order->order_date ? $order->order_date->format('M d, Y') : 'N/A'
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

        return view('dashboard', compact(
            'totalCustomers',
            'totalRevenue',
            'totalOrders',
            'totalProducts',
            'dashboardData'
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

    public function reports()
    {
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
        return view('shipping.shipments');
    }

    public function shippingServices()
    {
        return view('shipping.services');
    }
}
