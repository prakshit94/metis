import re

files = [
    '/home/user/metis/resources/js/components/roles-permissions.js',
    '/home/user/metis/resources/js/components/users.js'
]

for file in files:
    with open(file, 'r') as f:
        content = f.read()

    pattern1 = r"(  core_system: \{ label: 'Core & System', icon: 'gear', order: 60 \},)"
    replacement1 = r"\1\n  utilities_tools: { label: 'Utilities & Tools', icon: 'tools', order: 70 },"
    content = re.sub(pattern1, replacement1, content)

    pattern2 = r"(    marketing: \['coupon', 'promotions'\],)"
    replacement2 = r"\1\n    utilities_tools: ['chat', 'messages', 'calendar', 'files', 'forms', 'security', 'help'],"
    content = re.sub(pattern2, replacement2, content)

    pattern3 = r"(    permission: 'Permissions', user: 'Users', audit: 'Audit Logs', dashboard: 'Dashboard',)"
    replacement3 = r"\1\n    chat: 'Team Chat', messages: 'Messages', calendar: 'Calendar', files: 'Files', forms: 'Forms', security: 'Security', help: 'Help & Support',"
    content = re.sub(pattern3, replacement3, content)

    with open(file, 'w') as f:
        f.write(content)
