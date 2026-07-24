import re

files = [
    '/home/user/metis/resources/js/components/roles-permissions.js',
    '/home/user/metis/resources/js/components/users.js'
]

for file in files:
    with open(file, 'r') as f:
        content = f.read()

    pattern = r"(dashboard: \['dashboard'\],)"
    replacement = r"\1\n    view_all_data: ['view-all-data'],"
    
    # Check if 'dashboard' is in the content before trying to match
    if 'dashboard:' in content:
        content = re.sub(pattern, replacement, content)
        
        pattern2 = r"(dashboard: 'Dashboard',)"
        replacement2 = r"\1 view_all_data: 'Global Data Visibility',"
        content = re.sub(pattern2, replacement2, content)

    with open(file, 'w') as f:
        f.write(content)
