import re
with open('app/Modules/Customers/Controllers/CustomerController.php', 'r') as f:
    content = f.read()

# Add withCount('complaints') to orders relation
old = """                'orders' => function ($q) {
                    $q->latest()->limit(10)->with(["""
new = """                'orders' => function ($q) {
                    $q->latest()->limit(10)->withCount('complaints')->with(["""

content = content.replace(old, new)

with open('app/Modules/Customers/Controllers/CustomerController.php', 'w') as f:
    f.write(content)
