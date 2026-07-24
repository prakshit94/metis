import re

files = [
    '/home/user/metis/resources/js/components/roles-permissions.js',
    '/home/user/metis/resources/js/components/users.js'
]

for file in files:
    with open(file, 'r') as f:
        content = f.read()

    pattern = r"(core_system: \['village', 'settings', 'role', 'permission', 'user', 'audit', 'dashboard'\])"
    replacement = r"core_system: ['village', 'settings', 'role', 'permission', 'user', 'audit', 'dashboard', 'view-all-data']"
    
    content = re.sub(pattern, replacement, content)

    with open(file, 'w') as f:
        f.write(content)
