import re

path = "/home/user/metis/database/seeders/RolesAndPermissionsSeeder.php"

with open(path, 'r') as f:
    content = f.read()

missing_permissions = [
    "'brand-restore'",
    "'brand-permanent-delete'",
    "'category-restore'",
    "'category-permanent-delete'",
    "'hsncode-restore'",
    "'hsncode-permanent-delete'",
    "'productattribute-restore'",
    "'productattribute-permanent-delete'",
    "'taxrate-restore'",
    "'taxrate-permanent-delete'",
    "'unitofmeasure-restore'",
    "'unitofmeasure-permanent-delete'",
    "'warehouse-restore'",
    "'warehouse-permanent-delete'",
    "'village-restore'",
    "'village-permanent-delete'",
    "'customer-permanent-delete'",
    "'customeraddress-restore'",
    "'customeraddress-permanent-delete'",
    "'inventoryadjustment-restore'",
    "'inventoryadjustment-permanent-delete'",
    "'stockmanagement-restore'",
    "'stockmanagement-permanent-delete'",
    "'stocktransfer-restore'",
    "'stocktransfer-permanent-delete'",
    "'coupon-restore'",
    "'coupon-permanent-delete'",
    "'promotions-restore'",
    "'promotions-permanent-delete'",
]

insert_string = "        " + ",\n        ".join(missing_permissions) + ",\n"

# Insert before 'audit-log-view',
content = content.replace("        'audit-log-view',\n    ];", insert_string + "        'audit-log-view',\n    ];")

with open(path, 'w') as f:
    f.write(content)
