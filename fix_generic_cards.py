import os
import re

colors = ['border-primary', 'border-success', 'border-warning', 'border-info', 'border-danger', 'border-secondary']

def process_file(filepath):
    with open(filepath, 'r') as f:
        content = f.read()

    lines = content.split('\n')
    modified = False
    color_index = 0
    
    for i, line in enumerate(lines):
        # Target plain <div class="card h-100"> or similar that lack border styling
        if 'class="card h-100"' in line or 'class="card h-100 "' in line:
            if 'border-start' not in line and 'border-top' not in line:
                color = colors[color_index % len(colors)]
                color_index += 1
                
                line = re.sub(r'class="card h-100"', r'class="card h-100 border-start border-4 ' + color + '"', line)
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

