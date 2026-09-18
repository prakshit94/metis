import os
import re

directories_to_scan = [
    'resources/views',
]

colors = ['border-primary', 'border-success', 'border-warning', 'border-info', 'border-danger', 'border-secondary']

def process_file(filepath):
    with open(filepath, 'r') as f:
        content = f.read()

    lines = content.split('\n')
    modified = False
    
    color_index = 0
    
    for i, line in enumerate(lines):
        if 'class="card' in line or "class='card" in line:
            # If the line has border-start but ALSO border-0, the border-0 will kill the border!
            if 'border-start' in line and 'border-0' in line:
                line = line.replace('border-0 ', '').replace(' border-0', '')
                lines[i] = line
                modified = True
                
            # If the card is a stats-card but somehow still missed border-start
            if 'stats-card' in line and 'border-start' not in line:
                color = colors[color_index % len(colors)]
                color_index += 1
                line = re.sub(r'class="card (.*?stats-card.*?)"', r'class="card \1 border-start border-4 ' + color + '"', line)
                if line != lines[i]:
                    lines[i] = line
                    modified = True
                    
            # If it's a generic widget card (has shadow, rounded, border-0) but NOT a table card
            # Table cards usually don't have bg-opacity, etc.
            if 'border-0' in line and 'border-start' not in line:
                if 'shadow-sm' in line and 'rounded-' in line:
                    # Let's add border-start border-4
                    color = colors[color_index % len(colors)]
                    color_index += 1
                    line = re.sub(r'border-0', r'border-start border-4 ' + color, line)
                    if line != lines[i]:
                        lines[i] = line
                        modified = True
                        
    if modified:
        with open(filepath, 'w') as f:
            f.write('\n'.join(lines))
        print(f"Updated {filepath}")

for root, _, files in os.walk('resources/views'):
    for file in files:
        if file.endswith('.blade.php'):
            process_file(os.path.join(root, file))

