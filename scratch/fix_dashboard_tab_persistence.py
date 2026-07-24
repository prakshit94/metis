import re

with open('/home/user/metis/resources/views/dashboard.blade.php', 'r') as f:
    content = f.read()

# Replace the hardcoded activeTab
content = content.replace(
    """<div x-data="{ activeTab: 'search' }">""",
    """<div x-data="{ activeTab: new URLSearchParams(window.location.search).has('filter') ? 'dashboard' : 'search' }">"""
)

with open('/home/user/metis/resources/views/dashboard.blade.php', 'w') as f:
    f.write(content)
