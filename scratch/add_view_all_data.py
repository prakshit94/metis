import re

with open('/home/user/metis/database/seeders/RolesAndPermissionsSeeder.php', 'r') as f:
    content = f.read()

pattern = r"(        'dashboard-view',)"
replacement = r"\1\n        'view-all-data',"

content = re.sub(pattern, replacement, content)

with open('/home/user/metis/database/seeders/RolesAndPermissionsSeeder.php', 'w') as f:
    f.write(content)
