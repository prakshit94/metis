import re

with open('/home/user/metis/database/seeders/RolesAndPermissionsSeeder.php', 'r') as f:
    content = f.read()

# I want to remove 'view-all-data', from the User, Order Varification 1, Order Varification 2, and Agent arrays.
# It looks like:
#        'User' => [
#            'dashboard-view',
#        'view-all-data',
#        ],

content = re.sub(
    r"(\s*'dashboard-view',\s*)'view-all-data',",
    r"\1",
    content
)

# Wait, I need to keep 'view-all-data' in the main $permissions array!
# The main $permissions array has it under 'Dashboards & Reports'
# Wait, let's see how I inserted it initially.

