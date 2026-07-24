import re

with open('/home/user/metis/database/seeders/RolesAndPermissionsSeeder.php', 'r') as f:
    content = f.read()

# Define the structured permissions
permissions_groups = {
    'Catalog & Products': [
        'brand', 'catalog', 'category', 'productattribute', 'hsncode', 'taxrate', 'unitofmeasure', 'product'
    ],
    'Inventory & Warehousing': [
        'warehouse', 'inventoryadjustment', 'stockmanagement', 'stocktransfer'
    ],
    'Marketing': [
        'coupon', 'promotions'
    ],
    'Customers & Addresses': [
        'customer', 'customeraddress'
    ],
    'Core & System': [
        'village', 'shipping', 'role', 'permission', 'user', 'bulkuser', 'audit-log'
    ]
}

permissions_list = []

for group, entities in permissions_groups.items():
    permissions_list.append(f"        // ── {group} ──")
    for entity in entities:
        # standard CRUD
        for action in ['view', 'create', 'edit', 'delete', 'restore', 'permanent-delete']:
            permissions_list.append(f"        '{entity}-{action}',")
        # special
        if entity in ['product', 'customer', 'role', 'permission', 'user']:
            permissions_list.append(f"        '{entity}-import',")
            permissions_list.append(f"        '{entity}-export',")
        if entity == 'customer' or entity == 'user':
            permissions_list.append(f"        '{entity}-activate',")
        if entity == 'shipping':
            permissions_list.append(f"        '{entity}-manage',")
        if entity == 'user':
            permissions_list.append(f"        '{entity}-sync-roles',")
            permissions_list.append(f"        '{entity}-sync-permissions',")
            permissions_list.append(f"        '{entity}-invite',")
            permissions_list.append(f"        '{entity}-report',")
    permissions_list.append("")

permissions_list.append("        // ── Sales & Orders (Dot Notation) ──")
for entity in ['orders', 'invoices', 'payments', 'refunds', 'returns']:
    for action in ['view', 'create', 'edit', 'delete', 'restore', 'permanent-delete']:
        permissions_list.append(f"        '{entity}.{action}',")
permissions_list.append("")
permissions_list.append("        // ── Additional Order Specifics ──")
for action in ['future_order', 'pending', 'confirmed', 'processing', 'ready_to_ship', 'dispatched', 'delivered', 'returned', 'cancelled']:
    permissions_list.append(f"        'orders.view.{action}',")
for action in ['confirm', 'ship', 'dispatch', 'processing', 'deliver', 'cancel', 'return', 'invoice_pdf', 'generate_invoice', 'cod', 'receipt', 'bulk_status', 'bulk_print', 'revert_status', 'filter_status', 'filter_product', 'filter_fulfillment', 'filter_state', 'filter_district', 'filter_taluka', 'filter_village', 'filter_carrier', 'filter_date']:
    permissions_list.append(f"        'orders.{action}',")
permissions_list.append("        'view_all_order',")
permissions_list.append("")
permissions_list.append("        // ── General System Views ──")
for entity in ['dashboard', 'analytics', 'reports', 'settings']:
    permissions_list.append(f"        '{entity}-view',")
permissions_list.append("        'settings-edit',")

permissions_str = "\n".join(permissions_list)

# Replace PERMISSIONS array
pattern_permissions = r'(private const array PERMISSIONS = \[).*?(    \];)'
content = re.sub(pattern_permissions, r'\1\n' + permissions_str + r'\n\2', content, flags=re.DOTALL)

# Replace Super Admin / Admin arrays
pattern_super_admin = r"('Super Admin'\s*=>\s*\[)(.*?)(\],)"
content = re.sub(pattern_super_admin, r'\1\n        // Super Admin gets all permissions implicitly or explicitly\n        ...self::PERMISSIONS,\n    \3', content, flags=re.DOTALL)

pattern_admin = r"('Admin'\s*=>\s*\[)(.*?)(\],\n\s*'Manager')"
content = re.sub(pattern_admin, r'\1\n        // Admin gets all permissions\n        ...self::PERMISSIONS,\n    \3', content, flags=re.DOTALL)

with open('/home/user/metis/database/seeders/RolesAndPermissionsSeeder.php', 'w') as f:
    f.write(content)
