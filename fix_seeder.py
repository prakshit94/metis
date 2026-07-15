from pathlib import Path
import re

file_path = '/home/ubuntu/metis/database/seeders/RolesAndPermissionsSeeder.php'
content = Path(file_path).read_text(encoding='utf-8')

all_perms = [
    # Catalog
    'brand-view', 'brand-create', 'brand-edit', 'brand-delete',
    'catalog-view',
    'category-view', 'category-create', 'category-edit', 'category-delete',
    'hsncode-view', 'hsncode-create', 'hsncode-edit', 'hsncode-delete',
    'productattribute-view', 'productattribute-create', 'productattribute-edit', 'productattribute-delete',
    'taxrate-view', 'taxrate-create', 'taxrate-edit', 'taxrate-delete',
    'unitofmeasure-view', 'unitofmeasure-create', 'unitofmeasure-edit', 'unitofmeasure-delete',
    'warehouse-view', 'warehouse-create', 'warehouse-edit', 'warehouse-delete',
    'product-view', 'product-create', 'product-edit', 'product-delete', 'product-restore', 'product-permanent-delete', 'product-import', 'product-export',
    
    # Core
    'dashboard-view',
    'analytics-view',
    'settings-view', 'settings-edit',
    'shipping-view', 'shipping-manage',
    'village-view', 'village-create', 'village-edit', 'village-delete',
    
    # Customers
    'customer-view', 'customer-create', 'customer-edit', 'customer-delete',
    'customeraddress-view', 'customeraddress-create', 'customeraddress-edit', 'customeraddress-delete',
    
    # Inventory
    'inventoryadjustment-view', 'inventoryadjustment-create', 'inventoryadjustment-edit', 'inventoryadjustment-delete',
    'stockmanagement-view', 'stockmanagement-create', 'stockmanagement-edit', 'stockmanagement-delete',
    'stocktransfer-view', 'stocktransfer-create', 'stocktransfer-edit', 'stocktransfer-delete',
    
    # Orders
    'coupon-view', 'coupon-create', 'coupon-edit', 'coupon-delete',
    'promotions-view', 'promotions-create', 'promotions-edit', 'promotions-delete',
    'orders.view', 'orders.create', 'orders.edit', 'orders.delete', 
    'orders.confirm', 'orders.ship', 'orders.dispatch', 'orders.processing', 
    'orders.deliver', 'orders.cancel', 'orders.return', 'orders.invoice_pdf', 
    'orders.generate_invoice', 'orders.cod', 'orders.receipt', 'orders.bulk_status', 
    'orders.bulk_print', 'orders.revert_status', 'orders.filter_status', 
    'orders.filter_product', 'orders.filter_fulfillment', 'orders.filter_state', 
    'orders.filter_district', 'orders.filter_taluka', 'orders.filter_village', 
    'orders.filter_carrier', 'orders.filter_date', 'view_all_order',
    
    # Users
    'bulkuser-view', 'bulkuser-manage',
    'permission-view', 'permission-create', 'permission-edit', 'permission-delete', 'permission-restore', 'permission-permanent-delete',
    'role-view', 'role-create', 'role-edit', 'role-delete', 'role-restore', 'role-permanent-delete',
    'user-view', 'user-create', 'user-edit', 'user-delete', 'user-restore', 'user-permanent-delete', 'user-activate', 'user-sync-roles', 'user-sync-permissions', 'user-impersonate',
    'audit-log-view'
]

manager_perms = [p for p in all_perms if 'view' in p or p in ['product-export', 'orders.create', 'orders.edit', 'orders.confirm', 'orders.ship', 'orders.dispatch', 'orders.processing', 'orders.deliver', 'orders.cancel', 'orders.return', 'orders.invoice_pdf', 'orders.generate_invoice', 'orders.cod', 'orders.receipt', 'orders.bulk_status', 'orders.bulk_print', 'orders.revert_status', 'orders.filter_status', 'orders.filter_product', 'orders.filter_fulfillment', 'orders.filter_state', 'orders.filter_district', 'orders.filter_taluka', 'orders.filter_village', 'orders.filter_carrier', 'orders.filter_date', 'view_all_order', 'shipping-manage']]

perms_str = ",\n        ".join([f"'{p}'" for p in all_perms])
mgr_perms_str = ",\n            ".join([f"'{p}'" for p in manager_perms])

old_perms_pattern = r'private const array PERMISSIONS = \[.*?\];'
new_perms = f"private const array PERMISSIONS = [\n        {perms_str},\n    ];"

content = re.sub(old_perms_pattern, new_perms, content, flags=re.DOTALL)

old_role_pattern = r"'Manager'\s*=>\s*\[.*?\]"
new_role = f"'Manager'     => [\n            {mgr_perms_str},\n        ]"
content = re.sub(old_role_pattern, new_role, content, flags=re.DOTALL)

Path(file_path).write_text(content, encoding='utf-8')
print("RolesAndPermissionsSeeder.php updated.")
