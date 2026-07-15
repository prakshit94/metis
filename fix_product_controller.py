import os
import re
from pathlib import Path

base_dir = '/home/ubuntu/metis'
file_path = os.path.join(base_dir, 'app/Modules/Catalog/Controllers/ProductController.php')
if os.path.exists(file_path):
    content = Path(file_path).read_text(encoding='utf-8')
    content = content.replace("namespace App\\Http\\Controllers\\Products;", "namespace App\\Modules\\Catalog\\Controllers;")
    Path(file_path).write_text(content, encoding='utf-8')
    print("Updated ProductController.php")

# Global replace of App\Http\Controllers\Products\ProductController to App\Modules\Catalog\Controllers\ProductController
for root, dirs, files in os.walk(base_dir):
    if any(ignore in root for ignore in ['vendor', 'node_modules', '.git', 'storage', 'bootstrap/cache']):
        continue
    for file in files:
        if file.endswith('.php'):
            filepath = os.path.join(root, file)
            content = Path(filepath).read_text(encoding='utf-8')
            orig_content = content
            content = content.replace("App\\Http\\Controllers\\Products\\ProductController", "App\\Modules\\Catalog\\Controllers\\ProductController")
            if content != orig_content:
                Path(filepath).write_text(content, encoding='utf-8')
                print(f"Replaced in {filepath}")
