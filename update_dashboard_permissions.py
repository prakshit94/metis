import re

file_path = "/home/user/metis/database/seeders/RolesAndPermissionsSeeder.php"

with open(file_path, "r") as f:
    content = f.read()

# 1. Add 'reports-view' to PERMISSIONS array right after 'analytics-view'
if "'reports-view'" not in content:
    content = re.sub(
        r"('analytics-view',)",
        r"\1\n        'reports-view',",
        content,
        count=1
    )

# 2. Add 'reports-view' to Manager role right after 'analytics-view'
content = re.sub(
    r"(\s*'dashboard-view',\s*'analytics-view',)",
    r"\1\n            'reports-view',",
    content
)

# 3. Add to User role
content = re.sub(
    r"('User'\s*=>\s*\[)(.*?)(\],)",
    r"\1\n            'dashboard-view',\n            'analytics-view',\n            'reports-view',\n        \3",
    content,
    flags=re.DOTALL
)

# 4. Add to Order Varification 1, 2 and Agent
def add_dashboard_perms(match):
    role = match.group(1)
    perms = match.group(2)
    
    # only add if not already there
    if "'dashboard-view'" not in perms:
        return f"{role}\n            'dashboard-view',\n            'analytics-view',\n            'reports-view',{perms}"
    return match.group(0)

content = re.sub(
    r"('Order Varification 1'\s*=>\s*\[)(.*?)(?=\],)",
    add_dashboard_perms,
    content,
    flags=re.DOTALL
)

content = re.sub(
    r"('Order Varification 2'\s*=>\s*\[)(.*?)(?=\],)",
    add_dashboard_perms,
    content,
    flags=re.DOTALL
)

content = re.sub(
    r"('Agent'\s*=>\s*\[)(.*?)(?=\],)",
    add_dashboard_perms,
    content,
    flags=re.DOTALL
)

with open(file_path, "w") as f:
    f.write(content)

print("RolesAndPermissionsSeeder updated with dashboard, analytics, and reports permissions for all roles.")
