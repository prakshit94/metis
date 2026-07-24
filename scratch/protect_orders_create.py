import re

with open('/home/user/metis/resources/views/orders/create.blade.php', 'r') as f:
    content = f.read()

# 1. Edit Customer
content = re.sub(
    r'(<button type="button" class="btn btn-sm btn-outline-secondary" @click="\$dispatch\(\'open-add-customer-modal\', \{customer: customerDetails\}\)">\s*<i class="bi bi-pencil me-1"></i> Edit\s*</button>)',
    r"@can('customer-edit')\n                        \1\n                        @endcan",
    content
)

# 2. Add Address
content = re.sub(
    r'(<button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-4 shadow-sm hover-shadow transition-all" @click="\$dispatch\(\'open-address-modal\', \{customerId: partyId\}\)">\s*<i class="bi bi-plus-circle me-1"></i> Add Address\s*</button>)',
    r"@can('customeraddress-create')\n                            \1\n                            @endcan",
    content
)

# 3. Edit Address (there are multiple instances for billing and shipping)
content = re.sub(
    r'(<button type="button" class="btn btn-sm btn-light border rounded-circle shadow-sm position-absolute d-flex align-items-center justify-content-center" style="top: 12px; right: 12px; width: 28px; height: 28px; z-index: 20;" @click\.stop\.prevent="\$dispatch\(\'open-address-modal\', \{customerId: partyId, address: addr\}\)">\s*<i class="bi bi-pencil" style="font-size: 11px;"></i>\s*</button>)',
    r"@can('customeraddress-edit')\n                                                        \1\n                                                        @endcan",
    content
)


with open('/home/user/metis/resources/views/orders/create.blade.php', 'w') as f:
    f.write(content)
