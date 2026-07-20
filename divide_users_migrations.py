import os
import re

MIGRATIONS_DIR = "/home/user/metis/database/migrations"
users_file = os.path.join(MIGRATIONS_DIR, "0001_01_01_000000_create_users_table.php")

if os.path.exists(users_file):
    print("Dividing users tables")
    with open(users_file, 'r') as f:
        content = f.read()
        
    users_up = re.search(r"(Schema::create\('users',.*?\);)", content, re.DOTALL).group(1)
    tokens_up = re.search(r"(Schema::create\('password_reset_tokens',.*?\);)", content, re.DOTALL).group(1)
    sessions_up = re.search(r"(Schema::create\('sessions',.*?\);)", content, re.DOTALL).group(1)
    
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
    
    def write(name, up_c, drop):
        with open(os.path.join(MIGRATIONS_DIR, name), 'w') as f:
            f.write(template.format(up_content=up_c, table_name=drop))

    write("0001_01_01_000000a_create_users_table.php", users_up, "users")
    write("0001_01_01_000000b_create_password_reset_tokens_table.php", tokens_up, "password_reset_tokens")
    write("0001_01_01_000000c_create_sessions_table.php", sessions_up, "sessions")
    
    os.remove(users_file)

