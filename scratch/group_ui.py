import re

with open('/home/user/metis/resources/js/components/roles-permissions.js', 'r') as f:
    content = f.read()

# I will add `subGroups` mapping.

pattern = r"""function groupPermissions\(permissions\) \{
  const groups = new Map\(\);

  \[\.\.\.permissions\]
    \.sort\(\(a, b\) => String\(a\.name\)\.localeCompare\(String\(b\.name\)\)\)
    \.forEach\(permission => \{
      const group = permissionGroupFor\(permission\.name\);
      if \(\!groups\.has\(group\.key\)\) \{
        groups\.set\(group\.key, \{
          \.\.\.group,
          items: \[\],
        \}\);
      \}
      groups\.get\(group\.key\)\.items\.push\(\{
        \.\.\.permission,
        actionLabel: permissionActionLabel\(permission\.name\),
      \}\);
    \}\);"""

replacement = """function getEntityLabel(name) {
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
    });"""

content = re.sub(pattern, replacement, content, flags=re.DOTALL)

with open('/home/user/metis/resources/js/components/roles-permissions.js', 'w') as f:
    f.write(content)
