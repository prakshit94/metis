import re

with open('/home/user/metis/resources/views/orders/create.blade.php', 'r') as f:
    content = f.read()

# 1. Edit Customer button
pattern1 = r"(<button type=\"button\" class=\"btn btn-sm btn-outline-secondary\" @click=\"\$dispatch\('open-add-customer-modal', \{customer: customerDetails\}\)\">\n\s*<i class=\"bi bi-pencil-square me-1\"></i>Edit Profile\n\s*</button>)"
replacement1 = r"@can('customer-edit')\n                        \1\n                        @endcan"
content = re.sub(pattern1, replacement1, content)

# 3. Edit Address button
pattern3 = r"(<button type=\"button\" class=\"btn btn-sm btn-light border rounded-circle shadow-sm position-absolute d-flex align-items-center justify-content-center\" style=\"top: 12px; right: 12px; width: 28px; height: 28px; z-index: 20;\" @click\.stop\.prevent=\"\$dispatch\('open-address-modal', \{customerId: partyId, address: addr\}\)\">\n\s*<i class=\"bi bi-pencil text-primary\" style=\"font-size: 12px;\"></i>\n\s*</button>)"
replacement3 = r"@can('customeraddress-edit')\n                                                        \1\n                                                        @endcan"
content = re.sub(pattern3, replacement3, content)

with open('/home/user/metis/resources/views/orders/create.blade.php', 'w') as f:
    f.write(content)
