import re

with open('resources/views/components/sidebar.blade.php', 'r') as f:
    content = f.read()

# Add a "Toggle Menus" button right before the main <nav>
toggle_btn_html = """
        <div class="px-3 mb-2 d-flex justify-content-between align-items-center">
            <span class="text-muted small fw-bold text-uppercase">Navigation</span>
            <button class="btn btn-sm btn-link text-muted p-0 text-decoration-none" id="toggle-all-menus" title="Toggle all menus">
                <i class="bi bi-arrows-expand"></i>
            </button>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var btn = document.getElementById('toggle-all-menus');
                if(btn) {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        var collapses = document.querySelectorAll('.admin-sidebar .collapse');
                        var anyOpen = Array.from(collapses).some(c => c.classList.contains('show'));
                        
                        collapses.forEach(c => {
                            var bsCollapse = window.bootstrap.Collapse.getInstance(c);
                            if (!bsCollapse) {
                                bsCollapse = new window.bootstrap.Collapse(c, {toggle: false});
                            }
                            if (anyOpen) {
                                bsCollapse.hide();
                            } else {
                                bsCollapse.show();
                            }
                        });
                    });
                }
            });
        </script>
        <nav class="sidebar-nav">
"""
if 'id="toggle-all-menus"' not in content:
    content = content.replace('<nav class="sidebar-nav">', toggle_btn_html)

# We need to extract the condition from the `active` ternary inside the nav-link.
# Pattern: <a class="nav-link collapsed {{ (CONDITION) ? 'active' : '' }}" ... data-bs-target="#(TARGET)" ... aria-expanded="false" ...>
# Then we find the `<div class="collapse" id="(TARGET)">` and replace it.

def repl_link(m):
    # m.group(1) is the condition e.g. `in_array($current, [...])` or `Str::startsWith(...)`
    # m.group(2) is the target ID e.g. `orderManagementSubmenu`
    condition = m.group(1).strip()
    target_id = m.group(2).strip()
    
    # We rebuild the <a> tag
    # class="nav-link {{ CONDITION ? 'active' : 'collapsed' }}"
    # aria-expanded="{{ CONDITION ? 'true' : 'false' }}"
    
    original = m.group(0)
    
    # Replace class
    new_str = re.sub(r'class="nav-link collapsed \{\{.*?\}\}"', f'class="nav-link {{{{ {condition} ? \'active\' : \'collapsed\' }}}}"', original)
    
    # Replace aria-expanded
    new_str = re.sub(r'aria-expanded="false"', f'aria-expanded="{{{{ {condition} ? \'true\' : \'false\' }}}}"', new_str)
    
    return new_str

content = re.sub(r'<a class="nav-link collapsed \{\{\s*(.*?)\s*\?\s*\'active\'\s*:\s*\'\'\s*\}\}".*?data-bs-target="#(.*?)".*?>', repl_link, content, flags=re.DOTALL)

def repl_collapse(m):
    condition = m.group(1).strip()
    target_id = m.group(2).strip()
    return f'<div class="collapse {{{{ {condition} ? \'show\' : \'\' }}}}" id="{target_id}">'

# Now we need to inject the same condition into the collapse div.
# But we don't have the condition readily available when matching the div.
# So let's extract all target -> conditions first.
condition_map = {}
for m in re.finditer(r'<a class="nav-link \{\{\s*(.*?)\s*\?\s*\'active\'\s*:\s*\'collapsed\'\s*\}\}".*?data-bs-target="#(.*?)".*?>', content, flags=re.DOTALL):
    condition_map[m.group(2)] = m.group(1)

def repl_div(m):
    target = m.group(1)
    if target in condition_map:
        cond = condition_map[target]
        return f'<div class="collapse {{{{ {cond} ? \'show\' : \'\' }}}}" id="{target}">'
    return m.group(0)

content = re.sub(r'<div class="collapse" id="(.*?)">', repl_div, content)

with open('resources/views/components/sidebar.blade.php', 'w') as f:
    f.write(content)
