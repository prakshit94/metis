import re
from pathlib import Path

fixes = {
    'app/Modules/Orders/Models/OrderItem.php': ['App\\Modules\\Catalog\\Models\\Product'],
    'app/Modules/Orders/Models/Order.php': ['App\\Modules\\Customers\\Models\\Party', 'App\\Modules\\Catalog\\Models\\Warehouse', 'App\\Modules\\Customers\\Models\\PartyAddress', 'App\\Modules\\Users\\Models\\User'],
    'app/Modules/Orders/Models/OrderVerificationLog.php': ['App\\Modules\\Users\\Models\\User'],
    'app/Modules/Orders/Models/Offer.php': ['App\\Modules\\Catalog\\Models\\Product'],
    'app/Modules/Core/Models/Village.php': ['App\\Modules\\Catalog\\Models\\Service'],
    'app/Modules/Core/Models/VillageServiceMapping.php': ['App\\Modules\\Catalog\\Models\\Service'],
    'app/Modules/Core/Controllers/ShippingController.php': ['App\\Services\\InventoryService'],
    'app/Modules/Inventory/Models/InventoryAdjustment.php': ['App\\Modules\\Catalog\\Models\\Warehouse', 'App\\Modules\\Users\\Models\\User'],
    'app/Modules/Inventory/Models/StockMovement.php': ['App\\Modules\\Catalog\\Models\\Product', 'App\\Modules\\Catalog\\Models\\Warehouse', 'App\\Modules\\Users\\Models\\User'],
    'app/Modules/Inventory/Models/InventoryAdjustmentItem.php': ['App\\Modules\\Catalog\\Models\\Product'],
    'app/Modules/Inventory/Models/StockReservation.php': ['App\\Modules\\Catalog\\Models\\Product', 'App\\Modules\\Catalog\\Models\\Warehouse', 'App\\Modules\\Orders\\Models\\Order'],
    'app/Modules/Inventory/Models/StockTransferItem.php': ['App\\Modules\\Catalog\\Models\\Product'],
    'app/Modules/Inventory/Models/StockTransfer.php': ['App\\Modules\\Catalog\\Models\\Warehouse'],
    'app/Modules/Inventory/Models/Stock.php': ['App\\Modules\\Catalog\\Models\\Product', 'App\\Modules\\Catalog\\Models\\Warehouse'],
    'app/Modules/Catalog/Models/Service.php': ['App\\Modules\\Core\\Models\\Village', 'App\\Modules\\Core\\Models\\VillageServiceMapping'],
    'app/Modules/Catalog/Models/Product.php': ['App\\Modules\\Inventory\\Models\\Stock', 'App\\Modules\\Inventory\\Models\\StockReservation', 'App\\Modules\\Inventory\\Models\\StockMovement', 'App\\Modules\\Orders\\Models\\OrderItem'],
    'app/Modules/Catalog/Models/Warehouse.php': ['App\\Modules\\Inventory\\Models\\Stock', 'App\\Modules\\Core\\Models\\Village'],
    'app/Modules/Customers/Models/Party.php': ['App\\Modules\\Orders\\Models\\Order'],
    'app/Modules/Customers/Models/Customer.php': ['App\\Modules\\Orders\\Models\\Order'],
    'app/Modules/Customers/Models/PartyAddress.php': ['App\\Modules\\Core\\Models\\Village'],
}

for filepath, imports in fixes.items():
    path = Path(filepath)
    if not path.exists():
        continue
    content = path.read_text(encoding='utf-8')
    
    uses_to_add = []
    for imp in imports:
        if f'use {imp};' not in content:
            uses_to_add.append(f'use {imp};')
            
    if uses_to_add:
        uses_str = '\n'.join(uses_to_add) + '\n'
        
        # find namespace statement
        match = re.search(r'(namespace [^;]+;)', content)
        if match:
            ns_stmt = match.group(1)
            content = content.replace(ns_stmt, ns_stmt + '\n\n' + uses_str, 1)
            path.write_text(content, encoding='utf-8')
            print(f"Fixed {filepath}")

