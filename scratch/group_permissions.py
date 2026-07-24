import re

with open('/home/user/metis/resources/js/components/roles-permissions.js', 'r') as f:
    content = f.read()

# I will replace the `permissionGroupMeta` and `permissionGroupFor` functions.
replacement = """const permissionGroupMeta = {
  catalog_products: { label: 'Catalog & Products', icon: 'collection', order: 10 },
  inventory_warehousing: { label: 'Inventory & Warehousing', icon: 'boxes', order: 20 },
  sales_orders: { label: 'Sales & Orders', icon: 'cart', order: 30 },
  customers: { label: 'Customers', icon: 'people', order: 40 },
  marketing: { label: 'Marketing', icon: 'megaphone', order: 50 },
  core_system: { label: 'Core & System', icon: 'gear', order: 60 },
  other: { label: 'Other', icon: 'grid', order: 99 },
};

function permissionGroupFor(name) {
  let prefix = String(name ?? '').split(/[-.]/)[0] || 'other';
  
  if (name === 'view_all_order') prefix = 'orders';
  if (prefix === 'bulkuser') prefix = 'user';
  if (prefix === 'audit-log') prefix = 'audit';

  const groups = {
    catalog_products: ['brand', 'catalog', 'category', 'productattribute', 'hsncode', 'taxrate', 'unitofmeasure', 'product'],
    inventory_warehousing: ['warehouse', 'inventoryadjustment', 'stockmanagement', 'stocktransfer'],
    sales_orders: ['orders', 'invoices', 'payments', 'refunds', 'returns'],
    customers: ['customer', 'customeraddress'],
    marketing: ['coupon', 'promotions'],
    core_system: ['village', 'shipping', 'role', 'permission', 'user', 'dashboard', 'analytics', 'reports', 'settings', 'audit']
  };

  let groupKey = 'other';
  for (const [key, prefixes] of Object.entries(groups)) {
    if (prefixes.includes(prefix)) {
      groupKey = key;
      break;
    }
  }

  const defaultLabel = prefix.charAt(0).toUpperCase() + prefix.slice(1);
  const meta = permissionGroupMeta[groupKey] ?? { label: defaultLabel, icon: 'grid', order: 999 };

  return { key: groupKey, ...meta };
}"""

pattern = r'const permissionGroupMeta = \{.*?\n\};\n\nfunction permissionGroupFor\(name\) \{.*?\n\}'

content = re.sub(pattern, replacement, content, flags=re.DOTALL)

with open('/home/user/metis/resources/js/components/roles-permissions.js', 'w') as f:
    f.write(content)
