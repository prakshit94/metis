import os
import re
from pathlib import Path

def process_controller(filepath):
    content = Path(filepath).read_text(encoding='utf-8')
    
    if 'implements HasMiddleware' in content:
        return
    
    filename = os.path.basename(filepath)
    if filename in ['Controller.php', 'AuthController.php', 'PageController.php']:
        return

    base_name = filename.replace('Controller.php', '').lower()
    
    if 'use Illuminate\\Routing\\Controllers\\HasMiddleware;' not in content:
        content = re.sub(
            r'(use [^;]+;)(?!.*\nuse )', 
            r'\1\nuse Illuminate\\Routing\\Controllers\\HasMiddleware;\nuse Illuminate\\Routing\\Controllers\\Middleware;', 
            content, 
            count=1
        )
        
    class_def_pattern = r'class\s+([A-Za-z0-9_]+)\s+extends\s+([A-Za-z0-9_]+)\s*\{'
    content = re.sub(class_def_pattern, r'class \1 extends \2 implements HasMiddleware\n{', content, count=1)
    
    methods = re.findall(r'public\s+function\s+([a-zA-Z0-9_]+)\s*\(', content)
    
    mappings = []
    view_methods = [m for m in methods if m in ['index', 'show', 'search', 'options', 'servicesOptions', 'loginHistory', 'trackingEvents', 'shipmentsIndex', 'servicesIndex']]
    if view_methods:
        mappings.append(f"            new Middleware('permission:{base_name}-view', only: {view_methods}),")
        
    create_methods = [m for m in methods if m in ['create', 'store', 'import', 'storeService', 'addTrackingEvent']]
    if create_methods:
        mappings.append(f"            new Middleware('permission:{base_name}-create', only: {create_methods}),")
        
    edit_methods = [m for m in methods if m in ['edit', 'update', 'toggleActive', 'syncRoles', 'syncPermissions', 'bulkAction', 'updateShipmentStatus', 'updateService', 'toggleService']]
    if edit_methods:
        mappings.append(f"            new Middleware('permission:{base_name}-edit', only: {edit_methods}),")
        
    delete_methods = [m for m in methods if m in ['destroy', 'forceDelete', 'destroyService']]
    if delete_methods:
        mappings.append(f"            new Middleware('permission:{base_name}-delete', only: {delete_methods}),")
        
    restore_methods = [m for m in methods if m in ['restore']]
    if restore_methods:
        mappings.append(f"            new Middleware('permission:{base_name}-restore', only: {restore_methods}),")

    if mappings:
        middleware_str = "    public static function middleware(): array\n    {\n        return [\n" + "\n".join(mappings) + "\n        ];\n    }\n"
        content = re.sub(r'(implements HasMiddleware\n\{)', r'\1\n' + middleware_str, content)

    Path(filepath).write_text(content, encoding='utf-8')
    print(f"Processed: {filename}")

for root, _, files in os.walk('app/Modules'):
    for file in files:
        if file.endswith('Controller.php'):
            process_controller(os.path.join(root, file))
