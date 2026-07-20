<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Users\Models\Permission;
use App\Modules\Users\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds the default roles and permissions required by the application.
 *
 * Roles:
 *   - Super Admin  → all operation permissions
 *   - Admin        → all operation permissions
 *   - Manager      → view-focused user and audit permissions
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
        'brand-view',
        'brand-create',
        'brand-edit',
        'brand-delete',
        'catalog-view',
        'category-view',
        'category-create',
        'category-edit',
        'category-delete',
        'hsncode-view',
        'hsncode-create',
        'hsncode-edit',
        'hsncode-delete',
        'productattribute-view',
        'productattribute-create',
        'productattribute-edit',
        'productattribute-delete',
        'taxrate-view',
        'taxrate-create',
        'taxrate-edit',
        'taxrate-delete',
        'unitofmeasure-view',
        'unitofmeasure-create',
        'unitofmeasure-edit',
        'unitofmeasure-delete',
        'warehouse-view',
        'warehouse-create',
        'warehouse-edit',
        'warehouse-delete',
        'product-view',
        'product-create',
        'product-edit',
        'product-delete',
        'product-restore',
        'product-permanent-delete',
        'product-import',
        'product-export',
        'dashboard-view',
        'analytics-view',
        'settings-view',
        'settings-edit',
        'shipping-view',
        'shipping-manage',
        'village-view',
        'village-create',
        'village-edit',
        'village-delete',
        'customer-view',
        'customer-create',
        'customer-edit',
        'customer-delete',
        'customer-restore',
        'customeraddress-view',
        'customeraddress-create',
        'customeraddress-edit',
        'customeraddress-delete',
        'inventoryadjustment-view',
        'inventoryadjustment-create',
        'inventoryadjustment-edit',
        'inventoryadjustment-delete',
        'stockmanagement-view',
        'stockmanagement-create',
        'stockmanagement-edit',
        'stockmanagement-delete',
        'stocktransfer-view',
        'stocktransfer-create',
        'stocktransfer-edit',
        'stocktransfer-delete',
        'coupon-view',
        'coupon-create',
        'coupon-edit',
        'coupon-delete',
        'promotions-view',
        'promotions-create',
        'promotions-edit',
        'promotions-delete',
        'orders.view',
        'orders.view.future_order',
        'orders.view.pending',
        'orders.view.confirmed',
        'orders.view.processing',
        'orders.view.ready_to_ship',
        'orders.view.dispatched',
        'orders.view.delivered',
        'orders.view.returned',
        'orders.view.cancelled',
        'orders.create',
        'orders.edit',
        'orders.delete',
        'orders.confirm',
        'orders.ship',
        'orders.dispatch',
        'orders.processing',
        'orders.deliver',
        'orders.cancel',
        'orders.return',
        'orders.invoice_pdf',
        'orders.generate_invoice',
        'orders.cod',
        'orders.receipt',
        'orders.bulk_status',
        'orders.bulk_print',
        'orders.revert_status',
        'orders.filter_status',
        'orders.filter_product',
        'orders.filter_fulfillment',
        'orders.filter_state',
        'orders.filter_district',
        'orders.filter_taluka',
        'orders.filter_village',
        'orders.filter_carrier',
        'orders.filter_date',
        'view_all_order',
        'bulkuser-view',
        'bulkuser-manage',
        'permission-view',
        'permission-create',
        'permission-edit',
        'permission-delete',
        'permission-restore',
        'permission-permanent-delete',
        'role-view',
        'role-create',
        'role-edit',
        'role-delete',
        'role-restore',
        'role-permanent-delete',
        'user-view',
        'user-create',
        'user-edit',
        'user-delete',
        'user-restore',
        'user-permanent-delete',
        'user-activate',
        'user-sync-roles',
        'user-sync-permissions',
        'brand-restore',
        'brand-permanent-delete',
        'category-restore',
        'category-permanent-delete',
        'hsncode-restore',
        'hsncode-permanent-delete',
        'productattribute-restore',
        'productattribute-permanent-delete',
        'taxrate-restore',
        'taxrate-permanent-delete',
        'unitofmeasure-restore',
        'unitofmeasure-permanent-delete',
        'warehouse-restore',
        'warehouse-permanent-delete',
        'village-restore',
        'village-permanent-delete',
        'customer-permanent-delete',
        'customeraddress-restore',
        'customeraddress-permanent-delete',
        'inventoryadjustment-restore',
        'inventoryadjustment-permanent-delete',
        'stockmanagement-restore',
        'stockmanagement-permanent-delete',
        'stocktransfer-restore',
        'stocktransfer-permanent-delete',
        'coupon-restore',
        'coupon-permanent-delete',
        'promotions-restore',
        'promotions-permanent-delete',
        'audit-log-view',
    ];

    /**
     * Role-to-permission mapping.
     *
     * @var array<string, list<string>>
     */
    private const array ROLE_PERMISSIONS = [
        'Super Admin' => self::PERMISSIONS,
        'Admin'       => self::PERMISSIONS,
        'Manager'     => [
            'brand-view',
            'catalog-view',
            'category-view',
            'hsncode-view',
            'productattribute-view',
            'taxrate-view',
            'unitofmeasure-view',
            'warehouse-view',
            'product-view',
            'product-export',
            'dashboard-view',
            'analytics-view',
            'settings-view',
            'shipping-view',
            'shipping-manage',
            'village-view',
            'customer-view',
            'customeraddress-view',
            'inventoryadjustment-view',
            'stockmanagement-view',
            'stocktransfer-view',
            'coupon-view',
            'promotions-view',
            'orders.view',
            'orders.view.future_order',
            'orders.view.pending',
            'orders.view.confirmed',
            'orders.view.processing',
            'orders.view.ready_to_ship',
            'orders.view.dispatched',
            'orders.view.delivered',
            'orders.view.returned',
            'orders.view.cancelled',
            'orders.create',
            'orders.edit',
            'orders.confirm',
            'orders.ship',
            'orders.dispatch',
            'orders.processing',
            'orders.deliver',
            'orders.cancel',
            'orders.return',
            'orders.invoice_pdf',
            'orders.generate_invoice',
            'orders.cod',
            'orders.receipt',
            'orders.bulk_status',
            'orders.bulk_print',
            'orders.revert_status',
            'orders.filter_status',
            'orders.filter_product',
            'orders.filter_fulfillment',
            'orders.filter_state',
            'orders.filter_district',
            'orders.filter_taluka',
            'orders.filter_village',
            'orders.filter_carrier',
            'orders.filter_date',
            'view_all_order',
            'bulkuser-view',
            'permission-view',
            'role-view',
            'user-view',
            'audit-log-view',
        ],
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

            $role->syncPermissions($permissions);

            $this->command->info("✔ Role [{$roleName}] seeded" . (! empty($permissions) ? ' with: ' . implode(', ', $permissions) : '.'));
        }

        // ── Create Master Admin User ─────────────────────────────────────────
        $masterAdmin = \App\Modules\Users\Models\User::firstOrCreate(
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

        // ── Create Additional Users ──────────────────────────────────────────
        $users = [
            [
                'email' => 'rajesh.kumar@example.com',
                'first_name' => 'Rajesh',
                'last_name' => 'Kumar',
                'name' => 'Rajesh Kumar',
                'department' => 'Operations',
                'phone' => '+91 9876543210',
                'role' => 'Manager'
            ],
            [
                'email' => 'priya.sharma@example.com',
                'first_name' => 'Priya',
                'last_name' => 'Sharma',
                'name' => 'Priya Sharma',
                'department' => 'Administration',
                'phone' => '+91 8765432109',
                'role' => 'Admin'
            ],
            [
                'email' => 'amit.patel@example.com',
                'first_name' => 'Amit',
                'last_name' => 'Patel',
                'name' => 'Amit Patel',
                'department' => 'Sales',
                'phone' => '+91 7654321098',
                'role' => 'User'
            ],
        ];

        foreach ($users as $userData) {
            $role = $userData['role'];
            unset($userData['role']);
            
            $user = \App\Modules\Users\Models\User::firstOrCreate(
                ['email' => $userData['email']],
                array_merge($userData, [
                    'password' => \Illuminate\Support\Facades\Hash::make('password'),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ])
            );

            if (! $user->hasRole($role)) {
                $user->assignRole($role);
            }
        }

        $this->command->info('✔ Additional users seeded successfully.');

        // Reset cache after all changes
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command->info('✔ RolesAndPermissionsSeeder complete.');
    }
}
