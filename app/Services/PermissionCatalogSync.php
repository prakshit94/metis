<?php

declare(strict_types=1);

namespace App\Services;

use App\Modules\Users\Models\Permission;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/** Additive updates only: no users, passwords, role resets or team-context changes. */
final class PermissionCatalogSync
{
    public function run(bool $copyLegacyGrants = true, ?array $onlyActions = null): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();
        try {
            DB::transaction(function () use ($copyLegacyGrants, $onlyActions) {
                foreach (RolesAndPermissionsSeeder::permissions() as $name) {
                    // Do not resurrect an intentionally soft-deleted permission.
                    if (!Permission::withTrashed()->where('name', $name)->where('guard_name', 'web')->exists()) {
                        Permission::findOrCreate($name, 'web');
                    }
                }
                if (!$copyLegacyGrants) return;
                $tables = config('permission.table_names');
                $key = config('permission.column_names.permission_pivot_key') ?: 'permission_id';
                $ids = Permission::where('guard_name', 'web')->pluck('id', 'name');
                foreach (FeaturePermissions::legacy() as $action => $sources) {
                    if (($onlyActions !== null && !in_array($action, $onlyActions, true)) || !isset($ids[$action])) continue;
                    foreach ($sources as $source) {
                        if (!isset($ids[$source])) continue;
                        foreach (['role_has_permissions', 'model_has_permissions'] as $pivot) {
                            // Preserve complete pivot rows, including model type and team identifiers.
                            $rows = DB::table($tables[$pivot])->where($key, $ids[$source])->get();
                            foreach ($rows->chunk(250) as $chunk) {
                                $grants = $chunk->map(function ($row) use ($key, $ids, $action) {
                                    $grant = (array)$row;
                                    $grant[$key] = $ids[$action];
                                    return $grant;
                                })->all();
                                foreach ($grants as $grant) {
                                    if (!DB::table($tables[$pivot])->where($grant)->exists()) {
                                        DB::table($tables[$pivot])->insertOrIgnore($grant);
                                    }
                                }
                            }
                        }
                    }
                }
            });
        } finally {
            $registrar->forgetCachedPermissions();
        }
    }
}
