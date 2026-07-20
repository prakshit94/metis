import os
import re

MIGRATIONS_DIR = "/home/user/metis/database/migrations"
perm_file = os.path.join(MIGRATIONS_DIR, "2025_01_01_000002_create_permission_tables.php")

if os.path.exists(perm_file):
    print("Dividing permission tables")
    with open(perm_file, 'r') as f:
        content = f.read()
    
    # Extract blocks
    config_setup = """        $teams = config('permission.teams');
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';

        throw_if(empty($tableNames), 'Error: config/permission.php not loaded. Run [php artisan config:clear] and try again.');
        throw_if($teams && empty($columnNames['team_foreign_key'] ?? null), 'Error: team_foreign_key on config/permission.php not loaded. Run [php artisan config:clear] and try again.');
"""
    
    permissions_up = re.search(r"(Schema::create\(\$tableNames\['permissions'\].*?\);)", content, re.DOTALL).group(1)
    roles_up = re.search(r"(Schema::create\(\$tableNames\['roles'\].*?\);)", content, re.DOTALL).group(1)
    model_perm_up = re.search(r"(Schema::create\(\$tableNames\['model_has_permissions'\].*?\);)", content, re.DOTALL).group(1)
    model_role_up = re.search(r"(Schema::create\(\$tableNames\['model_has_roles'\].*?\);)", content, re.DOTALL).group(1)
    role_perm_up = re.search(r"(Schema::create\(\$tableNames\['role_has_permissions'\].*?\);)", content, re.DOTALL).group(1)
    cache_clear = """        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));"""
            
    template = """<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{{
    public function up(): void
    {{
{config_setup}
        {up_content}
    }}

    public function down(): void
    {{
        $tableNames = config('permission.table_names');
        throw_if(empty($tableNames), 'Error: config/permission.php not found and defaults could not be merged. Please publish the package configuration before proceeding, or drop the tables manually.');
        
        Schema::dropIfExists({drop_table});
    }}
}};
"""
    
    template_with_cache = """<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{{
    public function up(): void
    {{
{config_setup}
        {up_content}
        
{cache_clear}
    }}

    public function down(): void
    {{
        $tableNames = config('permission.table_names');
        throw_if(empty($tableNames), 'Error: config/permission.php not found and defaults could not be merged. Please publish the package configuration before proceeding, or drop the tables manually.');
        
        Schema::dropIfExists({drop_table});
    }}
}};
"""

    def write(name, up_c, drop):
        with open(os.path.join(MIGRATIONS_DIR, name), 'w') as f:
            f.write(template.format(config_setup=config_setup, up_content=up_c, drop_table=drop))

    write("2025_01_01_000002_create_permissions_table.php", permissions_up, "$tableNames['permissions']")
    write("2025_01_01_000002a_create_roles_table.php", roles_up, "$tableNames['roles']")
    write("2025_01_01_000002b_create_model_has_permissions_table.php", model_perm_up, "$tableNames['model_has_permissions']")
    write("2025_01_01_000002c_create_model_has_roles_table.php", model_role_up, "$tableNames['model_has_roles']")
    
    with open(os.path.join(MIGRATIONS_DIR, "2025_01_01_000002d_create_role_has_permissions_table.php"), 'w') as f:
        f.write(template_with_cache.format(config_setup=config_setup, up_content=role_perm_up, cache_clear=cache_clear, drop_table="$tableNames['role_has_permissions']"))
        
    os.remove(perm_file)

