import os
import re

MIGRATIONS_DIR = "/home/user/metis/database/migrations"

def read_file(path):
    with open(path, 'r') as f:
        return f.read()

def write_file(path, content):
    with open(path, 'w') as f:
        f.write(content)

# Village Services
vs_file = os.path.join(MIGRATIONS_DIR, "2026_07_07_000005b_create_village_services_tables.php")
if os.path.exists(vs_file):
    print("Dividing village services")
    content = read_file(vs_file)
    
    villages_up = re.search(r"(Schema::create\('villages',.*?\n        \}\);)", content, re.DOTALL).group(1)
    services_up = re.search(r"(Schema::create\('services',.*?\n        \}\);)", content, re.DOTALL).group(1)
    mappings_up = re.search(r"(Schema::create\('village_service_mappings',.*?\n        \}\);)", content, re.DOTALL).group(1)
    
    template = """<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{{
    public function up(): void
    {{
        {up_content}
    }}

    public function down(): void
    {{
        Schema::dropIfExists('{table_name}');
    }}
}};
"""
    write_file(os.path.join(MIGRATIONS_DIR, "2026_07_07_000005a_create_villages_table.php"), template.format(up_content=villages_up, table_name="villages"))
    write_file(os.path.join(MIGRATIONS_DIR, "2026_07_07_000005b_create_services_table.php"), template.format(up_content=services_up, table_name="services"))
    write_file(os.path.join(MIGRATIONS_DIR, "2026_07_07_000005c_create_village_service_mappings_table.php"), template.format(up_content=mappings_up, table_name="village_service_mappings"))

# Order Returns
or_file = os.path.join(MIGRATIONS_DIR, "2026_07_16_142452_create_order_returns_and_financials_tables.php")
if os.path.exists(or_file):
    print("Dividing order returns")
    content = read_file(or_file)
    
    returns_up = re.search(r"(Schema::create\('order_returns',.*?\n        \}\);)", content, re.DOTALL).group(1)
    items_up = re.search(r"(Schema::create\('order_return_items',.*?\n        \}\);)", content, re.DOTALL).group(1)
    refunds_up = re.search(r"(Schema::create\('refunds',.*?\n        \}\);)", content, re.DOTALL).group(1)
    credit_notes_up = re.search(r"(Schema::create\('credit_notes',.*?\n        \}\);)", content, re.DOTALL).group(1)
    
    write_file(os.path.join(MIGRATIONS_DIR, "2026_07_16_142452_create_order_returns_table.php"), template.format(up_content=returns_up, table_name="order_returns"))
    write_file(os.path.join(MIGRATIONS_DIR, "2026_07_16_142453_create_order_return_items_table.php"), template.format(up_content=items_up, table_name="order_return_items"))
    write_file(os.path.join(MIGRATIONS_DIR, "2026_07_16_142454_create_refunds_table.php"), template.format(up_content=refunds_up, table_name="refunds"))
    write_file(os.path.join(MIGRATIONS_DIR, "2026_07_16_142455_create_credit_notes_table.php"), template.format(up_content=credit_notes_up, table_name="credit_notes"))

