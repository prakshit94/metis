import re

with open('/home/user/metis/database/seeders/RolesAndPermissionsSeeder.php', 'r') as f:
    content = f.read()

pattern_ov1 = r"('Order Varification 1' => \[\n\s*'dashboard-view',\n)(.*?)(        \],)"
replacement_ov1 = r"\1            'customer-view',\n            'customeraddress-view',\n            'customeraddress-create',\n            'customeraddress-edit',\n            'customeraddress-delete',\n\2\3"
content = re.sub(pattern_ov1, replacement_ov1, content, flags=re.DOTALL)

pattern_ov2 = r"('Order Varification 2' => \[\n\s*'dashboard-view',\n)(.*?)(        \],)"
replacement_ov2 = r"\1            'customer-view',\n            'customeraddress-view',\n            'customeraddress-create',\n            'customeraddress-edit',\n            'customeraddress-delete',\n\2\3"
content = re.sub(pattern_ov2, replacement_ov2, content, flags=re.DOTALL)

pattern_agent = r"('Agent' => \[\n\s*'dashboard-view',\n)(.*?)(        \])"
replacement_agent = r"\1            'customer-view',\n            'customeraddress-view',\n            'customeraddress-create',\n            'customeraddress-edit',\n            'customeraddress-delete',\n\2\3"
content = re.sub(pattern_agent, replacement_agent, content, flags=re.DOTALL)

with open('/home/user/metis/database/seeders/RolesAndPermissionsSeeder.php', 'w') as f:
    f.write(content)
