import re

with open('/home/user/metis/app/Modules/Core/Controllers/PageController.php', 'r') as f:
    content = f.read()

# Add auth logic at the top of dashboard method
auth_logic = """
        $user = auth()->user();
        $isGlobalView = $user && ($user->hasRole(['Super Admin', 'Admin']) || $user->can('view-all-data'));
        
        $customerQuery = Party::where('type', 'customer');
        $orderQuery = Order::query();
        
        if (!$isGlobalView) {
            $customerQuery->where('created_by', $user->id);
            $orderQuery->where('created_by', $user->id);
        }
"""

dashboard_pattern = r"(    public function dashboard\(\)\n    \{)\n        // 1. Top Metrics"
replacement = r"\1" + auth_logic + r"\n        // 1. Top Metrics"

content = re.sub(dashboard_pattern, replacement, content)

# Now replace all static Party:: and Order:: calls with the scoped clones
# Party::where('type', 'customer') -> (clone $customerQuery)
content = content.replace("Party::where('type', 'customer')->count()", "(clone $customerQuery)->count()")
content = content.replace("Order::whereNotIn('status', ['cancelled', 'returned'])->sum('net_amount')", "(clone $orderQuery)->whereNotIn('status', ['cancelled', 'returned'])->sum('net_amount')")
content = content.replace("Order::count()", "(clone $orderQuery)->count()")

content = content.replace("Order::whereNotIn('status', ['cancelled', 'returned'])\n                ->whereYear", "(clone $orderQuery)->whereNotIn('status', ['cancelled', 'returned'])\n                ->whereYear")
content = content.replace("Order::whereNotIn('status', ['cancelled', 'returned'])\n                ->whereDate", "(clone $orderQuery)->whereNotIn('status', ['cancelled', 'returned'])\n                ->whereDate")
content = content.replace("Party::where('type', 'customer')\n                ->whereDate", "(clone $customerQuery)\n                ->whereDate")

content = content.replace("Order::select('status', DB::raw('count(*) as total'))", "(clone $orderQuery)->select('status', DB::raw('count(*) as total'))")
content = content.replace("Order::whereNotNull('shipping_state')", "(clone $orderQuery)->whereNotNull('shipping_state')")
content = content.replace("Order::with('party')", "(clone $orderQuery)->with('party')")

with open('/home/user/metis/app/Modules/Core/Controllers/PageController.php', 'w') as f:
    f.write(content)
