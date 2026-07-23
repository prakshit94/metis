import re

file_path = "/home/user/metis/resources/views/components/sidebar.blade.php"
with open(file_path, "r") as f:
    content = f.read()

permissions = {
    "route('dashboard')": "dashboard-view",
    "route('analytics')": "analytics-view",
    "route('reports')": "analytics-view",
    "route('orders')": "orders.view",
    "route('promotions.coupons')": "coupon-view",
    "route('promotions.offers')": "promotions-view",
    "route('shipping.shipments')": "shipping-view",
    "route('shipping.services')": "shipping-view",
    "route('catalog.warehouses')": "warehouse-view",
    "route('inventory.stock-management')": "stockmanagement-view",
    "route('inventory.stock-transfers')": "stocktransfer-view",
    "route('inventory.adjustments')": "inventoryadjustment-view",
    "route('catalog.products')": "product-view",
    "route('catalog.categories')": "category-view",
    "route('catalog.brands')": "brand-view",
    "route('catalog.attributes')": "productattribute-view",
    "route('catalog.uom')": "unitofmeasure-view",
    "route('catalog.tax-rates')": "taxrate-view",
    "route('catalog.hsn-codes')": "hsncode-view",
    "route('users')": "user-view",
    "route('roles-permissions')": "role-view",
    "route('customers')": "customer-view",
    "route('villages')": "village-view",
    "route('settings')": "settings-view",
}

for route_str, perm in permissions.items():
    # Only wrap if it's not already wrapped
    # We look for <li class="nav-item">\s*<a class="nav-link[^>]*href="{{ route_str }}"
    # We will use regex to find it, taking care of spaces.
    
    # We need to escape the route_str
    escaped_route = re.escape(route_str)
    
    # regex: look for <li class="nav-item"> ... href="{{ route_str }}" ... </li>
    # we use non-greedy dotall
    pattern = r'(<li class="nav-item">\s*<a class="nav-link[^>]*href="\{\{\s*' + escaped_route + r'\s*\}\}".*?</li>)'
    
    def replacer(match):
        # check if it is already wrapped (by checking the text just before it in the full string, but it's hard with just sub)
        # Instead, we just replace it. If we run this once, it's fine.
        return f"@can('{perm}')\n                            {match.group(1)}\n                            @endcan"

    content = re.sub(pattern, replacer, content, flags=re.DOTALL)

# But wait, dashboard, analytics and reports were already wrapped because they are top level, and the previous script might have skipped their inner or it actually handled them?
# Let's clean up any double @can('dashboard-view') just in case.
content = re.sub(r"@can\('[^']+'\)\s*@can\('([^']+)'\)", r"@can('\1')", content)
content = re.sub(r"@endcan\s*@endcan", r"@endcan", content)

with open(file_path, "w") as f:
    f.write(content)

print("Child items wrapped.")
