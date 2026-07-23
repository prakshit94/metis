import re

file_path = "/home/user/metis/resources/views/components/sidebar.blade.php"

with open(file_path, "r") as f:
    content = f.read()

def wrap_li(pattern, permission, content):
    # This finds <li class="nav-item">\n <a ... href="{{ route('pattern') }}" ... </a>\n </li>
    # and wraps the entire <li> with @can('permission') ... @endcan
    # Regex approach:
    # We look for `<li class="nav-item"[^>]*>.*?href="\{\{ route\('` + pattern + `'\) \}\}".*?</li>`
    
    # We will use a more robust regex that looks for the href line, then goes backwards to <li and forward to </li>.
    # Actually, it's easier to split by '<li class="nav-item' and if the block contains the route, wrap it.
    
    parts = re.split(r'(<li class="nav-item[^>]*>)', content)
    new_content = parts[0]
    
    i = 1
    while i < len(parts):
        tag = parts[i]
        body = parts[i+1]
        
        # does body contain the route?
        if re.search(r'href="\{\{\s*route\(\'' + pattern + r'\'\)\s*\}\}"', body):
            # Wrap tag + body
            new_content += f"@can('{permission}')\n" + tag + body + "@endcan\n"
        else:
            new_content += tag + body
            
        i += 2
        
    return new_content

# Mapping of route name to permission
mappings = [
    ('dashboard', 'dashboard-view'),
    ('analytics', 'analytics-view'),
    ('reports', 'analytics-view'), # or maybe skip
    ('orders', 'orders.view'),
    ('promotions.coupons', 'coupon-view'),
    ('promotions.offers', 'promotions-view'),
    ('shipping.shipments', 'shipping-view'),
    ('shipping.services', 'shipping-view'),
    ('catalog.warehouses', 'warehouse-view'),
    ('inventory.stock-management', 'stockmanagement-view'),
    ('inventory.stock-transfers', 'stocktransfer-view'),
    ('inventory.adjustments', 'inventoryadjustment-view'),
    ('catalog.products', 'product-view'),
    ('catalog.categories', 'category-view'),
    ('catalog.brands', 'brand-view'),
    ('catalog.attributes', 'productattribute-view'),
    ('catalog.uom', 'unitofmeasure-view'),
    ('catalog.tax-rates', 'taxrate-view'),
    ('catalog.hsn-codes', 'hsncode-view'),
    ('users', 'user-view'),
    ('roles-permissions', 'role-view'),
    ('customers', 'customer-view'),
    ('villages', 'village-view'),
    ('settings', 'settings-view'),
]

for route_name, perm in mappings:
    content = wrap_li(route_name, perm, content)

# Now, wrap the main dropdown parent items using @canany or @hasanyrole if needed.
# Actually, the user said "add all the missing permission in it". 
# The simplest approach is to wrap the sub-items, and then wrap the parent dropdowns in a @canany of all their sub-item permissions.

# For Enterprise Sales & Marketing Dropdown (contains orders, coupons, offers)
content = re.sub(
    r'(<li class="nav-item">\s*<a class="nav-link[^>]*data-bs-target="#salesSubmenu".*?</li>)',
    r"@canany(['orders.view', 'coupon-view', 'promotions-view'])\n\1\n@endcanany",
    content,
    flags=re.DOTALL
)

# For Billing & Payments Dropdown (invoices, payments, refunds, returns)
# We might just wrap the whole thing in @can('orders.view') since no specific billing views exist in seeder
content = re.sub(
    r'(<li class="nav-item">\s*<a class="nav-link[^>]*data-bs-target="#billingSubmenu".*?</li>)',
    r"@can('orders.view')\n\1\n@endcan",
    content,
    flags=re.DOTALL
)

# For Logistics & Warehouses Dropdown (shipping, warehouses)
content = re.sub(
    r'(<li class="nav-item">\s*<a class="nav-link[^>]*data-bs-target="#shippingSubmenu".*?</li>)',
    r"@canany(['shipping-view', 'warehouse-view'])\n\1\n@endcanany",
    content,
    flags=re.DOTALL
)

# For Inventory & Stock Dropdown (stock management, transfers, adjustments)
content = re.sub(
    r'(<li class="nav-item">\s*<a class="nav-link[^>]*data-bs-target="#inventorySubmenu".*?</li>)',
    r"@canany(['stockmanagement-view', 'stocktransfer-view', 'inventoryadjustment-view'])\n\1\n@endcanany",
    content,
    flags=re.DOTALL
)

# For Catalog Management Dropdown (products, categories, brands, attributes, uom, tax-rates, hsn-codes)
content = re.sub(
    r'(<li class="nav-item">\s*<a class="nav-link[^>]*data-bs-target="#catalogSubmenu".*?</li>)',
    r"@canany(['product-view', 'category-view', 'brand-view', 'productattribute-view', 'unitofmeasure-view', 'taxrate-view', 'hsncode-view'])\n\1\n@endcanany",
    content,
    flags=re.DOTALL
)

# For People & Admin Dropdown (users, roles-permissions, customers, villages)
content = re.sub(
    r'(<li class="nav-item">\s*<a class="nav-link[^>]*data-bs-target="#peopleSubmenu".*?</li>)',
    r"@canany(['user-view', 'role-view', 'customer-view', 'village-view'])\n\1\n@endcanany",
    content,
    flags=re.DOTALL
)


with open(file_path, "w") as f:
    f.write(content)

print("Sidebar updated successfully.")
