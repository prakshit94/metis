<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds the default roles and permissions required by the application.
 *
 * Roles:
 *   - Super Admin  → all permissions (bypassed globally via Gate::before)
 *   - Admin        → manage-users, view-audit-logs
 *   - Manager      → view-audit-logs
 *   - User         → (no direct permissions — limited to own profile)
 *
 * Run via:
 *   php artisan db:seed --class=RolesAndPermissionsSeeder
 */
class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Permissions that are essential to the system's operation.
     *
     * @var list<string>
     */
    private const array PERMISSIONS = [
        'manage-users',
        'manage-roles',
        'view-audit-logs',
    ];

    /**
     * Role-to-permission mapping.
     *
     * @var array<string, list<string>>
     */
    private const array ROLE_PERMISSIONS = [
        'Super Admin' => [], // Gate::before bypass — no explicit grants needed
        'Admin'       => ['manage-users', 'view-audit-logs'],
        'Manager'     => ['view-audit-logs'],
        'User'        => [],
    ];

    public function run(): void
    {
        // Reset the cached roles and permissions
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // ── Create permissions ───────────────────────────────────────────────
        foreach (self::PERMISSIONS as $permissionName) {
            Permission::firstOrCreate([
                'name'       => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        $this->command->info('✔ Permissions created: ' . implode(', ', self::PERMISSIONS));

        // ── Create roles and sync permissions ────────────────────────────────
        foreach (self::ROLE_PERMISSIONS as $roleName => $permissions) {
            $role = Role::firstOrCreate([
                'name'       => $roleName,
                'guard_name' => 'web',
            ]);

            if (! empty($permissions)) {
                $role->syncPermissions($permissions);
            }

            $this->command->info("✔ Role [{$roleName}] seeded" . (! empty($permissions) ? ' with: ' . implode(', ', $permissions) : '.'));
        }

        // ── Create Master Admin User ─────────────────────────────────────────
        $masterAdmin = \App\Models\User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Master Admin',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        if (! $masterAdmin->hasRole('Super Admin')) {
            $masterAdmin->assignRole('Super Admin');
        }

        $this->command->info('✔ Master Admin user seeded (admin@example.com / password).');

        // Reset cache after all changes
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command->info('✔ RolesAndPermissionsSeeder complete.');
    }
}
