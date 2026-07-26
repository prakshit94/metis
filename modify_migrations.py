import os

migrations_dir = 'database/migrations'
migrations = [
    '2026_07_26_133645_create_crops_table.php',
    '2026_07_26_133645_create_lead_sources_table.php',
    '2026_07_26_133646_create_irrigation_types_table.php',
    '2026_07_26_133646_create_land_units_table.php'
]

replacement = """$table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();"""

for m in migrations:
    filepath = os.path.join(migrations_dir, m)
    with open(filepath, 'r') as f:
        content = f.read()
    
    # Replace the standard blueprint
    content = content.replace("$table->id();\n            $table->timestamps();", replacement)
    
    with open(filepath, 'w') as f:
        f.write(content)
