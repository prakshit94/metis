import re

with open('/home/user/metis/database/seeders/RolesAndPermissionsSeeder.php', 'r') as f:
    lines = f.readlines()

new_lines = []
for i, line in enumerate(lines):
    if "'view-all-data'" in line and i > 400:
        continue # Skip this line
    new_lines.append(line)

with open('/home/user/metis/database/seeders/RolesAndPermissionsSeeder.php', 'w') as f:
    f.writelines(new_lines)
