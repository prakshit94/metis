import re

with open('/home/user/metis/app/Modules/Orders/Controllers/OrderController.php', 'r') as f:
    content = f.read()

content = content.replace("!$user->can('view_all_order')", "!($user->can('view_all_order') || $user->can('view-all-data'))")
content = content.replace("$user->can('view_all_order')", "($user->can('view_all_order') || $user->can('view-all-data'))")

with open('/home/user/metis/app/Modules/Orders/Controllers/OrderController.php', 'w') as f:
    f.write(content)
