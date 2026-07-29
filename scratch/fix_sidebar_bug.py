import re

with open('resources/views/components/sidebar.blade.php', 'r') as f:
    content = f.read()

# Replace <a class="nav-link {{ ... ? 'active' : '' }}"
# with <a class="nav-link collapsed {{ ... ? 'active' : '' }}"
# but only for those that have data-bs-toggle="collapse"

def repl(match):
    # check if the next lines have data-bs-toggle="collapse"
    if 'data-bs-toggle="collapse"' in match.group(0):
        # insert 'collapsed ' before '{{'
        a_tag = match.group(0)
        a_tag = re.sub(r'class="nav-link\s+\{\{', 'class="nav-link collapsed {{', a_tag)
        return a_tag
    return match.group(0)

# We match the entire <a> opening tag
content = re.sub(r'<a class="nav-link\s+\{\{.*?\}\}".*?>', repl, content, flags=re.DOTALL)

with open('resources/views/components/sidebar.blade.php', 'w') as f:
    f.write(content)
