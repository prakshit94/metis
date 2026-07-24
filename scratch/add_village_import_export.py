import re

with open('/home/user/metis/database/seeders/RolesAndPermissionsSeeder.php', 'r') as f:
    content = f.read()

pattern = r"(        'village-permanent-delete',)"
replacement = r"\1\n        'village-import',\n        'village-export',"

content = re.sub(pattern, replacement, content)

with open('/home/user/metis/database/seeders/RolesAndPermissionsSeeder.php', 'w') as f:
    f.write(content)
