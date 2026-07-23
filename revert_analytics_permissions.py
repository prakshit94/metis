import re

file_path = "/home/user/metis/database/seeders/RolesAndPermissionsSeeder.php"

with open(file_path, "r") as f:
    content = f.read()

def remove_perms(match):
    role_def = match.group(0)
    # Remove analytics-view and reports-view
    role_def = re.sub(r"\s*'analytics-view',", "", role_def)
    role_def = re.sub(r"\s*'reports-view',", "", role_def)
    return role_def

# Apply to User
content = re.sub(r"'User'\s*=>\s*\[.*?\]", remove_perms, content, flags=re.DOTALL)
# Apply to Order Varification 1
content = re.sub(r"'Order Varification 1'\s*=>\s*\[.*?\]", remove_perms, content, flags=re.DOTALL)
# Apply to Order Varification 2
content = re.sub(r"'Order Varification 2'\s*=>\s*\[.*?\]", remove_perms, content, flags=re.DOTALL)
# Apply to Agent
content = re.sub(r"'Agent'\s*=>\s*\[.*?\]", remove_perms, content, flags=re.DOTALL)

with open(file_path, "w") as f:
    f.write(content)

print("Permissions reverted for specific roles.")
