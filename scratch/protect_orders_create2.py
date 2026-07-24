import re

with open('/home/user/metis/resources/views/orders/create.blade.php', 'r') as f:
    content = f.read()

# 1. Edit Customer button
pattern1 = r"(<button type=\"button\" class=\"btn btn-sm btn-outline-secondary\" @click=\"\$dispatch\('open-add-customer-modal', \{customer: customerDetails\}\)\">\n\s*<i class=\"bi bi-pencil me-1\"></i> Edit\n\s*</button>)"
replacement1 = r"@can('customer-edit')\n                        \1\n                        @endcan"
content = re.sub(pattern1, replacement1, content)

# 2. Add Address button
pattern2 = r"(<button type=\"button\" class=\"btn btn-sm btn-outline-primary rounded-pill px-4 shadow-sm hover-shadow transition-all\" @click=\"\$dispatch\('open-address-modal', \{customerId: partyId\}\)\">\n\s*<i class=\"bi bi-plus-lg me-2\"></i>Add Address\n\s*</button>)"
replacement2 = r"@can('customeraddress-create')\n                            \1\n                            @endcan"
content = re.sub(pattern2, replacement2, content)

# 3. Edit Address button
pattern3 = r"(<button type=\"button\" class=\"btn btn-sm btn-light border rounded-circle shadow-sm position-absolute d-flex align-items-center justify-content-center\" style=\"top: 12px; right: 12px; width: 28px; height: 28px; z-index: 20;\" @click\.stop\.prevent=\"\$dispatch\('open-address-modal', \{customerId: partyId, address: addr\}\)\">\n\s*<i class=\"bi bi-pencil\" style=\"font-size: 11px;\"></i>\n\s*</button>)"
replacement3 = r"@can('customeraddress-edit')\n                                                        \1\n                                                        @endcan"
content = re.sub(pattern3, replacement3, content)

with open('/home/user/metis/resources/views/orders/create.blade.php', 'w') as f:
    f.write(content)
