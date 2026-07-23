import os

file_path = "/home/user/metis/resources/views/components/sidebar.blade.php"

with open(file_path, "r") as f:
    lines = f.readlines()

new_lines = []
skip = False

# Mapping of route to permission
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

# Mapping of submenu id to permission list for canany
parent_permissions = {
    'data-bs-target="#salesSubmenu"': "['orders.view', 'coupon-view', 'promotions-view']",
    'data-bs-target="#billingSubmenu"': "['orders.view']",
    'data-bs-target="#shippingSubmenu"': "['shipping-view', 'warehouse-view']",
    'data-bs-target="#inventorySubmenu"': "['stockmanagement-view', 'stocktransfer-view', 'inventoryadjustment-view']",
    'data-bs-target="#catalogSubmenu"': "['product-view', 'category-view', 'brand-view', 'productattribute-view', 'unitofmeasure-view', 'taxrate-view', 'hsncode-view']",
    'data-bs-target="#peopleSubmenu"': "['user-view', 'role-view', 'customer-view', 'village-view']",
}

i = 0
while i < len(lines):
    line = lines[i]
    
    # Check if this is a nav-item starting line
    if '<li class="nav-item">' in line:
        # Check ahead to see what it is
        # Is it a parent with submenu?
        is_parent = False
        parent_perm = None
        for j in range(i+1, min(i+10, len(lines))):
            for k, v in parent_permissions.items():
                if k in lines[j]:
                    is_parent = True
                    parent_perm = v
                    break
            if is_parent:
                break
        
        # Is it a child with a specific route?
        is_child = False
        child_perm = None
        for j in range(i+1, min(i+10, len(lines))):
            if "route(" in lines[j]:
                for route_str, perm in permissions.items():
                    if route_str in lines[j]:
                        is_child = True
                        child_perm = perm
                        break
            if is_child:
                break
        
        if is_parent:
            indent = line[:len(line) - len(line.lstrip())]
            if parent_perm == "['orders.view']":
                new_lines.append(f"{indent}@can('orders.view')\n")
            else:
                new_lines.append(f"{indent}@canany({parent_perm})\n")
            
            # Now we add the parent li
            new_lines.append(line)
            
            # Find the matching closing </li> based on indentation
            # Parent ends when we find a </li> at the EXACT SAME indentation
            parent_indent = len(line) - len(line.lstrip())
            i += 1
            while i < len(lines):
                curr_line = lines[i]
                new_lines.append(curr_line)
                curr_indent = len(curr_line) - len(curr_line.lstrip())
                if '</li>' in curr_line and curr_indent == parent_indent:
                    # found the end
                    new_lines.append(f"{indent}@endcanany\n" if parent_perm != "['orders.view']" else f"{indent}@endcan\n")
                    break
                i += 1
            i += 1
            continue
            
        elif is_child:
            indent = line[:len(line) - len(line.lstrip())]
            new_lines.append(f"{indent}@can('{child_perm}')\n")
            new_lines.append(line)
            
            child_indent = len(line) - len(line.lstrip())
            i += 1
            while i < len(lines):
                curr_line = lines[i]
                new_lines.append(curr_line)
                curr_indent = len(curr_line) - len(curr_line.lstrip())
                if '</li>' in curr_line and curr_indent == child_indent:
                    new_lines.append(f"{indent}@endcan\n")
                    break
                i += 1
            i += 1
            continue
            
    # Default append
    new_lines.append(line)
    i += 1

with open(file_path, "w") as f:
    f.writelines(new_lines)

print("Sidebar updated correctly.")
