with open('app/Modules/Orders/Controllers/OrderController.php', 'r') as f:
    content = f.read()

old = """                'orders' => function ($q) {
                    $q->latest()->limit(10)->with(["""
new = """                'orders' => function ($q) {
                    $q->latest()->limit(10)->withCount('complaints')->with(["""

content = content.replace(old, new)

with open('app/Modules/Orders/Controllers/OrderController.php', 'w') as f:
    f.write(content)
