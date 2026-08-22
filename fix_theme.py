import re

with open('/home/user/metis/resources/views/procurement/goods-receipts/index.blade.php', 'r') as f:
    content = f.read()

# Replace bg-light with bg-body-tertiary
content = content.replace('bg-light', 'bg-body-tertiary')

# Replace bg-white with bg-body
content = content.replace('bg-white', 'bg-body')

# Replace table-light with bg-body-secondary
content = content.replace('table-light', 'bg-body-secondary')

# Replace text-dark with text-body
content = content.replace('text-dark', 'text-body')

with open('/home/user/metis/resources/views/procurement/goods-receipts/index.blade.php', 'w') as f:
    f.write(content)
