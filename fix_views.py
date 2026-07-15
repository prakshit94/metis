import os
import shutil
import re
from pathlib import Path

base_dir = '/home/ubuntu/metis'
views_dir = os.path.join(base_dir, 'resources/views')

# Map of old view file to new view path
view_moves = {
    'orders.blade.php': 'orders/index.blade.php',
    'customers.blade.php': 'customers/index.blade.php',
    'users.blade.php': 'users/index.blade.php',
    'roles-permissions.blade.php': 'users/roles-permissions.blade.php',
    'villages.blade.php': 'villages/index.blade.php'
}

# Map of old view string to new view string for controllers/routes
view_strings = {
    "view('orders')": "view('orders.index')",
    'view("orders")': 'view("orders.index")',
    "view('customers')": "view('customers.index')",
    'view("customers")': 'view("customers.index")',
    "view('users')": "view('users.index')",
    'view("users")': 'view("users.index")',
    "view('roles-permissions')": "view('users.roles-permissions')",
    'view("roles-permissions")': 'view("users.roles-permissions")',
    "view('villages')": "view('villages.index')",
    'view("villages")': 'view("villages.index")'
}

# 1. Create missing directories
os.makedirs(os.path.join(views_dir, 'users'), exist_ok=True)
os.makedirs(os.path.join(views_dir, 'villages'), exist_ok=True)

# 2. Move view files
for old_file, new_path in view_moves.items():
    src = os.path.join(views_dir, old_file)
    dst = os.path.join(views_dir, new_path)
    if os.path.exists(src):
        # ensure parent dir exists
        os.makedirs(os.path.dirname(dst), exist_ok=True)
        shutil.move(src, dst)
        print(f"Moved {old_file} to {new_path}")

# 3. Update controllers and routes
search_dirs = [
    os.path.join(base_dir, 'app', 'Modules'),
    os.path.join(base_dir, 'routes')
]

for s_dir in search_dirs:
    for root, dirs, files in os.walk(s_dir):
        for file in files:
            if file.endswith('.php'):
                filepath = os.path.join(root, file)
                try:
                    content = Path(filepath).read_text(encoding='utf-8')
                    orig_content = content
                    for old_str, new_str in view_strings.items():
                        content = content.replace(old_str, new_str)
                    
                    # Also replace Route::view string literal arguments
                    content = content.replace("Route::view('/orders', 'orders')", "Route::view('/orders', 'orders.index')")
                    content = content.replace("Route::view('/customers', 'customers')", "Route::view('/customers', 'customers.index')")
                    content = content.replace("Route::view('/users', 'users')", "Route::view('/users', 'users.index')")
                    content = content.replace("Route::view('/roles-permissions', 'roles-permissions')", "Route::view('/roles-permissions', 'users.roles-permissions')")
                    content = content.replace("Route::view('/villages', 'villages')", "Route::view('/villages', 'villages.index')")

                    if content != orig_content:
                        Path(filepath).write_text(content, encoding='utf-8')
                        print(f"Updated view references in: {filepath}")
                except Exception as e:
                    pass

print("View reorganization complete.")
