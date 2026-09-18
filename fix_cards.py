import os
import re

directories_to_scan = [
    'resources/views',
]

colors = ['border-primary', 'border-success', 'border-warning', 'border-info', 'border-danger', 'border-secondary']

def process_file(filepath):
    with open(filepath, 'r') as f:
        content = f.read()

    # Find all instances of class="card ..." or class='card ...'
    # and if it doesn't have border-start, add border-start border-4 border-{color}
    
    lines = content.split('\n')
    modified = False
    
    color_index = 0
    
    for i, line in enumerate(lines):
        if 'class="card' in line or 'class=\'card' in line:
            # Skip if it already has border-start
            if 'border-start' in line:
                continue
                
            # Let's target stats-card specifically first, they definitely need it
            if 'stats-card' in line:
                color = colors[color_index % len(colors)]
                color_index += 1
                
                # Replace class="card stats-card" with class="card stats-card border-start border-4 {color}"
                # Handle varying spaces and other classes
                line = re.sub(r'class="card (.*?stats-card.*?)"', r'class="card \1 border-start border-4 ' + color + '"', line)
                if line != lines[i]:
                    lines[i] = line
                    modified = True

            # Also target the chart/detail cards like "card border-0 shadow-sm rounded-4"
            elif 'border-0' in line and ('shadow-sm' in line or 'rounded-4' in line):
                color = colors[color_index % len(colors)]
                color_index += 1
                # Replace border-0 with border-start border-4 {color}
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

