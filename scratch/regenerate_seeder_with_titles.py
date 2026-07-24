import re

with open('/home/user/metis/database/seeders/RolesAndPermissionsSeeder.php', 'r') as f:
    content = f.read()

permissions_groups = {
    'Catalog & Products': {
        'Brands': ['brand'],
        'Catalogs': ['catalog'],
        'Categories': ['category'],
        'Product Attributes': ['productattribute'],
        'HSN Codes': ['hsncode'],
        'Tax Rates': ['taxrate'],
        'Units of Measure': ['unitofmeasure'],
        'Products': ['product']
    },
    'Inventory & Warehousing': {
        'Warehouses': ['warehouse'],
        'Inventory Adjustments': ['inventoryadjustment'],
        'Stock Management': ['stockmanagement'],
        'Stock Transfers': ['stocktransfer']
    },
    'Marketing': {
        'Coupons': ['coupon'],
        'Promotions': ['promotions']
    },
    'Customers & Addresses': {
        'Customers': ['customer'],
        'Customer Addresses': ['customeraddress']
    },
    'Core & System': {
        'Villages': ['village'],
        'Shipping': ['shipping'],
        'Roles': ['role'],
        'Permissions': ['permission'],
        'Users': ['user'],
        'Bulk Users': ['bulkuser'],
        'Audit Logs': ['audit-log']
    }
}

permissions_list = []

for group_name, entities in permissions_groups.items():
    permissions_list.append(f"        // ── {group_name} ──")
    permissions_list.append("")
    for entity_title, entity_keys in entities.items():
        for entity in entity_keys:
            permissions_list.append(f"        // {entity_title}")
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
permissions_list.append("")

for title, entity in [('Orders', 'orders'), ('Invoices', 'invoices'), ('Payments', 'payments'), ('Refunds', 'refunds'), ('Returns', 'returns')]:
    permissions_list.append(f"        // {title}")
    for action in ['view', 'create', 'edit', 'delete', 'restore', 'permanent-delete']:
        permissions_list.append(f"        '{entity}.{action}',")
    permissions_list.append("")

permissions_list.append("        // ── Additional Order Specifics ──")
permissions_list.append("")
permissions_list.append("        // Order Views")
for action in ['future_order', 'pending', 'confirmed', 'processing', 'ready_to_ship', 'dispatched', 'delivered', 'returned', 'cancelled']:
    permissions_list.append(f"        'orders.view.{action}',")
permissions_list.append("")
permissions_list.append("        // Order Actions")
for action in ['confirm', 'ship', 'dispatch', 'processing', 'deliver', 'cancel', 'return', 'invoice_pdf', 'generate_invoice', 'cod', 'receipt', 'bulk_status', 'bulk_print', 'revert_status', 'filter_status', 'filter_product', 'filter_fulfillment', 'filter_state', 'filter_district', 'filter_taluka', 'filter_village', 'filter_carrier', 'filter_date']:
    permissions_list.append(f"        'orders.{action}',")
permissions_list.append("        'view_all_order',")
permissions_list.append("")

permissions_list.append("        // ── General System Views ──")
permissions_list.append("")
permissions_list.append("        // Dashboards & Reports")
for entity in ['dashboard', 'analytics', 'reports']:
    permissions_list.append(f"        '{entity}-view',")
permissions_list.append("")
permissions_list.append("        // System Settings")
permissions_list.append("        'settings-view',")
permissions_list.append("        'settings-edit',")

permissions_str = "\n".join(permissions_list)

# Replace PERMISSIONS array
pattern_permissions = r'(private const array PERMISSIONS = \[).*?(    \];)'
content = re.sub(pattern_permissions, r'\1\n' + permissions_str + r'\n\2', content, flags=re.DOTALL)

with open('/home/user/metis/database/seeders/RolesAndPermissionsSeeder.php', 'w') as f:
    f.write(content)
