import re

with open('/home/user/metis/resources/js/components/users.js', 'r') as f:
    content = f.read()

helpers = """// ─── Permission Helpers ─────────────────────────────────────────────────────────────
function permissionActionLabel(name) {
  if (name === 'view_all_order') return 'Bulk View';

  const parts = String(name ?? '').split(/[-.]/);
  if (parts.length <= 1) return String(name ?? '');

  return parts.slice(1)
    .map(part => part.charAt(0).toUpperCase() + part.slice(1).replace(/_/g, ' '))
    .join(' ');
}

const permissionGroupMeta = {
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
}

function getEntityLabel(name) {
  let prefix = String(name ?? '').split(/[-.]/)[0] || 'other';
  if (name === 'view_all_order') prefix = 'orders';
  if (prefix === 'bulkuser') prefix = 'user';
  if (prefix === 'audit-log') prefix = 'audit';
  
  const labels = {
    brand: 'Brands', catalog: 'Catalogs', category: 'Categories', productattribute: 'Product Attributes',
    hsncode: 'HSN Codes', taxrate: 'Tax Rates', unitofmeasure: 'Units of Measure', product: 'Products',
    warehouse: 'Warehouses', inventoryadjustment: 'Inventory Adjustments', stockmanagement: 'Stock Management',
    stocktransfer: 'Stock Transfers', orders: 'Orders', invoices: 'Invoices', payments: 'Payments',
    refunds: 'Refunds', returns: 'Returns', customer: 'Customers', customeraddress: 'Customer Addresses',
    coupon: 'Coupons', promotions: 'Promotions', village: 'Villages', shipping: 'Shipping', role: 'Roles',
    permission: 'Permissions', user: 'Users', audit: 'Audit Logs', dashboard: 'Dashboard',
    analytics: 'Analytics', reports: 'Reports', settings: 'Settings'
  };
  return labels[prefix] || (prefix.charAt(0).toUpperCase() + prefix.slice(1));
}

function groupPermissions(permissions) {
  const groups = new Map();

  [...permissions]
    .sort((a, b) => String(a.name).localeCompare(String(b.name)))
    .forEach(permission => {
      const group = permissionGroupFor(permission.name);
      if (!groups.has(group.key)) {
        groups.set(group.key, {
          ...group,
          items: [],
          subGroups: [],
        });
      }
      
      const permObj = {
        ...permission,
        actionLabel: permissionActionLabel(permission.name),
      };
      
      const groupData = groups.get(group.key);
      groupData.items.push(permObj);
      
      const subLabel = getEntityLabel(permission.name);
      let subGroup = groupData.subGroups.find(s => s.label === subLabel);
      if (!subGroup) {
        subGroup = { label: subLabel, items: [] };
        groupData.subGroups.push(subGroup);
      }
      subGroup.items.push(permObj);
    });

  return [...groups.values()].sort((a, b) => a.order - b.order || a.label.localeCompare(b.label));
}
"""

content = content.replace("document.addEventListener('alpine:init', () => {", helpers + "\ndocument.addEventListener('alpine:init', () => {")

# Add `get groupedAvailablePermissions` to `userModal` component
pattern_modal = r"""    availablePermissions: \[\],
    villageSearchQuery: '',"""

replacement_modal = """    availablePermissions: [],
    
    get groupedAvailablePermissions() {
      return groupPermissions(this.availablePermissions);
    },
    
    villageSearchQuery: '',"""

content = re.sub(pattern_modal, replacement_modal, content)

with open('/home/user/metis/resources/js/components/users.js', 'w') as f:
    f.write(content)
