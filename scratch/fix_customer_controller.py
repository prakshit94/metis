import re

with open('/home/user/metis/app/Modules/Customers/Controllers/CustomerController.php', 'r') as f:
    content = f.read()

pattern = r"new Middleware\('permission:customer-view', only: \['index', 'show', 'searchByPhone'\]\),"
replacement = """new Middleware('permission:customer-view', only: ['index', 'show']),
            new Middleware('permission:customer-view|orders.create', only: ['searchByPhone']),"""

content = re.sub(pattern, replacement, content)

with open('/home/user/metis/app/Modules/Customers/Controllers/CustomerController.php', 'w') as f:
    f.write(content)
