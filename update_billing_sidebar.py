import re

file_path = "/home/user/metis/resources/views/components/sidebar.blade.php"
with open(file_path, "r") as f:
    content = f.read()

# Replace the parent dropdown @can('orders.view') with @canany for billing
content = re.sub(
    r"@can\('orders.view'\)\n(\s*<li class=\"nav-item\">\s*<a class=\"nav-link[^>]*data-bs-target=\"#billingSubmenu\".*?</li>\s*)@endcan",
    r"@canany(['invoices.view', 'payments.view', 'refunds.view', 'returns.view'])\n\1@endcanany",
    content,
    flags=re.DOTALL
)

# Now wrap each individual item inside the billingSubmenu
# Invoices
content = re.sub(
    r'(<li class="nav-item">\s*<a class="nav-link[^>]*href="\{\{\s*route\(\'invoices\.index\'\)\s*\}\}".*?</li>)',
    r"@can('invoices.view')\n                            \1\n                            @endcan",
    content,
    flags=re.DOTALL
)

# Payments
content = re.sub(
    r'(<li class="nav-item">\s*<a class="nav-link[^>]*href="\{\{\s*route\(\'payments\.index\'\)\s*\}\}".*?</li>)',
    r"@can('payments.view')\n                            \1\n                            @endcan",
    content,
    flags=re.DOTALL
)

# Refunds
content = re.sub(
    r'(<li class="nav-item">\s*<a class="nav-link[^>]*href="\{\{\s*route\(\'refunds\.index\'\)\s*\}\}".*?</li>)',
    r"@can('refunds.view')\n                            \1\n                            @endcan",
    content,
    flags=re.DOTALL
)

# Returns
content = re.sub(
    r'(<li class="nav-item">\s*<a class="nav-link[^>]*href="\{\{\s*route\(\'returns\.index\'\)\s*\}\}".*?</li>)',
    r"@can('returns.view')\n                            \1\n                            @endcan",
    content,
    flags=re.DOTALL
)

with open(file_path, "w") as f:
    f.write(content)

print("Billing sidebar updated.")
