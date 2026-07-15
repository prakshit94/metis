import os
import shutil
import re
from pathlib import Path

base_dir = '/home/ubuntu/metis'
app_dir = os.path.join(base_dir, 'app')
modules_dir = os.path.join(app_dir, 'Modules')

modules = {
    'Orders': {
        'models': ['Order', 'OrderItem', 'Invoice', 'Payment', 'Shipment', 'ShipmentTrackingEvent', 'Coupon', 'Offer', 'OrderVerificationLog'],
        'controllers_dirs': ['Orders']
    },
    'Catalog': {
        'models': ['Product', 'Category', 'Brand', 'HsnCode', 'ProductAttribute', 'ProductAttributeValue', 'TaxRate', 'UnitOfMeasure', 'Warehouse', 'Service'],
        'controllers_dirs': ['Catalog']
    },
    'Customers': {
        'models': ['Customer', 'Party', 'PartyAddress'],
        'controllers_dirs': ['Customers']
    },
    'Inventory': {
        'models': ['Stock', 'StockMovement', 'StockReservation', 'StockTransfer', 'StockTransferItem', 'InventoryAdjustment', 'InventoryAdjustmentItem'],
        'controllers_dirs': ['Inventory']
    },
    'Users': {
        'models': ['User', 'Role', 'Permission', 'LoginHistory'],
        'controllers_dirs': ['Users', 'Roles', 'Auth']
    },
    'Core': {
        'models': ['Village', 'VillageServiceMapping'],
        'controllers_dirs': ['Villages', 'Shipping']
    }
}

replacements = {}

os.makedirs(modules_dir, exist_ok=True)

# 1. Move Models
for mod_name, mod_data in modules.items():
    mod_model_dir = os.path.join(modules_dir, mod_name, 'Models')
    os.makedirs(mod_model_dir, exist_ok=True)
    
    for model_name in mod_data['models']:
        src = os.path.join(app_dir, 'Models', f"{model_name}.php")
        if os.path.exists(src):
            dst = os.path.join(mod_model_dir, f"{model_name}.php")
            shutil.move(src, dst)
            
            content = Path(dst).read_text(encoding='utf-8')
            content = content.replace("namespace App\\Models;", f"namespace App\\Modules\\{mod_name}\\Models;")
            Path(dst).write_text(content, encoding='utf-8')
            
            replacements[f"App\\Models\\{model_name}"] = f"App\\Modules\\{mod_name}\\Models\\{model_name}"

# 2. Move Controllers
for mod_name, mod_data in modules.items():
    mod_ctrl_dir = os.path.join(modules_dir, mod_name, 'Controllers')
    os.makedirs(mod_ctrl_dir, exist_ok=True)
    
    for c_dir in mod_data['controllers_dirs']:
        src_dir = os.path.join(app_dir, 'Http', 'Controllers', c_dir)
        if os.path.exists(src_dir):
            for file in os.listdir(src_dir):
                if file.endswith('.php'):
                    src_file = os.path.join(src_dir, file)
                    dst_file = os.path.join(mod_ctrl_dir, file)
                    shutil.move(src_file, dst_file)
                    
                    content = Path(dst_file).read_text(encoding='utf-8')
                    # Replace both namespace with folder and without folder just in case
                    content = content.replace(f"namespace App\\Http\\Controllers\\{c_dir};", f"namespace App\\Modules\\{mod_name}\\Controllers;")
                    Path(dst_file).write_text(content, encoding='utf-8')
                    
                    replacements[f"App\\Http\\Controllers\\{c_dir}\\{file[:-4]}"] = f"App\\Modules\\{mod_name}\\Controllers\\{file[:-4]}"

core_ctrl = os.path.join(modules_dir, 'Core', 'Controllers')
os.makedirs(core_ctrl, exist_ok=True)
for f in ['Controller.php', 'PageController.php']:
    src = os.path.join(app_dir, 'Http', 'Controllers', f)
    if os.path.exists(src):
        dst = os.path.join(core_ctrl, f)
        shutil.move(src, dst)
        content = Path(dst).read_text(encoding='utf-8')
        content = content.replace("namespace App\\Http\\Controllers;", "namespace App\\Modules\\Core\\Controllers;")
        Path(dst).write_text(content, encoding='utf-8')
        replacements[f"App\\Http\\Controllers\\{f[:-4]}"] = f"App\\Modules\\Core\\Controllers\\{f[:-4]}"

# Add Controller mapping manually so base class references get updated properly
replacements["App\\Http\\Controllers\\Controller"] = "App\\Modules\\Core\\Controllers\\Controller"

# Fix potential issue with nested controllers that still use App\Http\Controllers\Controller
# In Laravel, `use App\Http\Controllers\Controller;` is common in subdirectories.
# We will do a generic replacement of this line in all PHP files.
replacements["use App\\Http\\Controllers\\Controller;"] = "use App\\Modules\\Core\\Controllers\\Controller;"

# Sort replacements by length descending to prevent partial replacements (e.g. replacing App\Models\User before App\Models\UserProfile)
sorted_repls = sorted(replacements.items(), key=lambda x: len(x[0]), reverse=True)

# 3. Global Replace
def process_file(filepath):
    try:
        content = Path(filepath).read_text(encoding='utf-8')
        orig_content = content
        for old_ns, new_ns in sorted_repls:
            content = content.replace(old_ns, new_ns)
            
            # If there's an instance where it's used as a string like 'App\Models\User' (used in morphs or factories)
            content = content.replace(old_ns.replace('\\', '\\\\'), new_ns.replace('\\', '\\\\'))
            
        if content != orig_content:
            Path(filepath).write_text(content, encoding='utf-8')
            print(f"Updated: {filepath}")
    except Exception as e:
        pass

for root, dirs, files in os.walk(base_dir):
    if any(ignore in root for ignore in ['vendor', 'node_modules', '.git', 'storage', 'bootstrap/cache']):
        continue
    for file in files:
        if file.endswith('.php'):
            process_file(os.path.join(root, file))

print("Refactoring complete.")
