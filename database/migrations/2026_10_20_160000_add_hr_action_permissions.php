<?php

use App\Support\HrPermissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permissionClass = config('permission.models.permission');
        $tables = config('permission.table_names');
        $permissionKey = config('permission.column_names.permission_pivot_key') ?: 'permission_id';

        DB::transaction(function () use ($permissionClass, $tables, $permissionKey) {
            foreach (HrPermissions::LEGACY as $action => $legacy) {
                $permission = $permissionClass::findOrCreate($action, 'web');
                $legacyId = $permissionClass::where('name', $legacy)->where('guard_name', 'web')->value('id');
                if (! $legacyId) {
                    continue;
                }

                // Copy full pivot rows to preserve direct grants and team identifiers.
                foreach (['role_has_permissions', 'model_has_permissions'] as $pivot) {
                    DB::table($tables[$pivot])->where($permissionKey, $legacyId)
                        ->orderBy($permissionKey)->chunk(250, function ($rows) use ($tables, $pivot, $permissionKey, $permission) {
                            $grants = $rows->map(function ($row) use ($permissionKey, $permission) {
                                $grant = (array) $row;
                                $grant[$permissionKey] = $permission->getKey();

                                return $grant;
                            })->all();
                            DB::table($tables[$pivot])->insertOrIgnore($grants);
                        });
                }
            }
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Preserve administrators' assignments when rolling back application code.
    }
};
