import re

with open('/home/user/metis/app/Modules/Core/Controllers/PageController.php', 'r') as f:
    content = f.read()

metrics_code = """
        $totalProducts = Product::count();

        // 1.5 Order Performance Metrics
        $totalDelivered = (clone $orderQuery)->whereIn('status', ['delivered', 'completed'])->count();
        $totalReturned = (clone $orderQuery)->whereIn('status', ['returned'])->count();
        $revDelivered = (clone $orderQuery)->whereIn('status', ['delivered', 'completed'])->sum('net_amount');
        $revReturned = (clone $orderQuery)->whereIn('status', ['returned'])->sum('net_amount');

        $deliveredPercent = $totalOrders > 0 ? round(($totalDelivered / $totalOrders) * 100) : 0;
        $returnedPercent = $totalOrders > 0 ? round(($totalReturned / $totalOrders) * 100) : 0;
"""

content = content.replace("        $totalProducts = Product::count();", metrics_code)

compact_find = """        return view('dashboard', compact(
            'totalCustomers',
            'totalRevenue',
            'totalOrders',
            'totalProducts',
            'dashboardData'
        ));"""

compact_replace = """        return view('dashboard', compact(
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
            'dashboardData'
        ));"""

content = content.replace(compact_find, compact_replace)

with open('/home/user/metis/app/Modules/Core/Controllers/PageController.php', 'w') as f:
    f.write(content)
