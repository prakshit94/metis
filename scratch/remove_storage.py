import re

with open('resources/js/main.js', 'r') as f:
    content = f.read()

# Remove the blocks that deal with `submenu-${targetId}`
content = re.sub(r'// Handle submenu toggle persistence.*?\}\);', '', content, flags=re.DOTALL)
content = re.sub(r'// Restore submenu states from localStorage.*?\}\);', '', content, flags=re.DOTALL)

with open('resources/js/main.js', 'w') as f:
    f.write(content)
