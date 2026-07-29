import re

with open('resources/views/components/sidebar.blade.php', 'r') as f:
    content = f.read()

# Replace aria-expanded="{{ ... ? 'true' : 'false' }}" with aria-expanded="false"
content = re.sub(r'aria-expanded="\{\{.*?\}\}"', 'aria-expanded="false"', content)

# Replace collapse {{ ... ? 'show' : '' }} with collapse
content = re.sub(r'class="collapse \{\{.*?\}\}"', 'class="collapse"', content)

with open('resources/views/components/sidebar.blade.php', 'w') as f:
    f.write(content)
