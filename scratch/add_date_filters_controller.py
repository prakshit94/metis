import re

with open('/home/user/metis/app/Modules/Core/Controllers/PageController.php', 'r') as f:
    content = f.read()

# Add Request injection if it's missing from dashboard method signature
if "public function dashboard()" in content:
    content = content.replace("public function dashboard()", "public function dashboard(Request $request)")

filter_logic = """
        if (!$isGlobalView) {
            $customerQuery->where('created_by', $user->id);
            $orderQuery->where('created_by', $user->id);
        }
        
        $filter = $request->input('filter', 'all');
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
"""

content = re.sub(
    r"        if \(!\$isGlobalView\) \{\n            \$customerQuery->where\('created_by', \$user->id\);\n            \$orderQuery->where\('created_by', \$user->id\);\n        \}",
    filter_logic,
    content
)

# make sure compact includes 'filter'
compact_find = """        return view('dashboard', compact(
            'totalCustomers',"""

compact_replace = """        return view('dashboard', compact(
            'filter',
            'totalCustomers',"""
            
content = content.replace(compact_find, compact_replace)

with open('/home/user/metis/app/Modules/Core/Controllers/PageController.php', 'w') as f:
    f.write(content)
