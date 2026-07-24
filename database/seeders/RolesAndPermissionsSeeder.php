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
        // ── Catalog & Products ──

        // Brands
        'brand-view',
        'brand-create',
        'brand-edit',
        'brand-delete',
        'brand-restore',
        'brand-permanent-delete',

        // Catalogs
        'catalog-view',
        'catalog-create',
        'catalog-edit',
        'catalog-delete',
        'catalog-restore',
        'catalog-permanent-delete',

        // Categories
        'category-view',
        'category-create',
        'category-edit',
        'category-delete',
        'category-restore',
        'category-permanent-delete',

        // Product Attributes
        'productattribute-view',
        'productattribute-create',
        'productattribute-edit',
        'productattribute-delete',
        'productattribute-restore',
        'productattribute-permanent-delete',

        // HSN Codes
        'hsncode-view',
        'hsncode-create',
        'hsncode-edit',
        'hsncode-delete',
        'hsncode-restore',
        'hsncode-permanent-delete',

        // Tax Rates
        'taxrate-view',
        'taxrate-create',
        'taxrate-edit',
        'taxrate-delete',
        'taxrate-restore',
        'taxrate-permanent-delete',

        // Units of Measure
        'unitofmeasure-view',
        'unitofmeasure-create',
        'unitofmeasure-edit',
        'unitofmeasure-delete',
        'unitofmeasure-restore',
        'unitofmeasure-permanent-delete',

        // Products
        'product-view',
        'product-create',
        'product-edit',
        'product-delete',
        'product-restore',
        'product-permanent-delete',
        'product-import',
        'product-export',

        // ── Inventory & Warehousing ──

        // Warehouses
        'warehouse-view',
        'warehouse-create',
        'warehouse-edit',
        'warehouse-delete',
        'warehouse-restore',
        'warehouse-permanent-delete',

        // Inventory Adjustments
        'inventoryadjustment-view',
        'inventoryadjustment-create',
        'inventoryadjustment-edit',
        'inventoryadjustment-delete',
        'inventoryadjustment-restore',
        'inventoryadjustment-permanent-delete',

        // Stock Management
        'stockmanagement-view',
        'stockmanagement-create',
        'stockmanagement-edit',
        'stockmanagement-delete',
        'stockmanagement-restore',
        'stockmanagement-permanent-delete',

        // Stock Transfers
        'stocktransfer-view',
        'stocktransfer-create',
        'stocktransfer-edit',
        'stocktransfer-delete',
        'stocktransfer-restore',
        'stocktransfer-permanent-delete',

        // ── Marketing ──

        // Coupons
        'coupon-view',
        'coupon-create',
        'coupon-edit',
        'coupon-delete',
        'coupon-restore',
        'coupon-permanent-delete',

        // Promotions
        'promotions-view',
        'promotions-create',
        'promotions-edit',
        'promotions-delete',
        'promotions-restore',
        'promotions-permanent-delete',

        // ── Customers & Addresses ──

        // Customers
        'view_all_customer',
        'customer-view',
        'customer-create',
        'customer-edit',
        'customer-delete',
        'customer-restore',
        'customer-permanent-delete',
        'customer-import',
        'customer-export',
        'customer-activate',

        // Customer Addresses
        'customeraddress-view',
        'customeraddress-create',
        'customeraddress-edit',
        'customeraddress-delete',
        'customeraddress-restore',
        'customeraddress-permanent-delete',

        // ── Core & System ──

        // Villages
        'village-view',
        'village-create',
        'village-edit',
        'village-delete',
        'village-restore',
        'village-permanent-delete',
        'village-import',
        'village-export',

        // Shipping
        'shipping-view',
        'shipping-create',
        'shipping-edit',
        'shipping-delete',
        'shipping-restore',
        'shipping-permanent-delete',
        'shipping-manage',

        // Roles
        'role-view',
        'role-create',
        'role-edit',
        'role-delete',
        'role-restore',
        'role-permanent-delete',
        'role-import',
        'role-export',

        // Permissions
        'permission-view',
        'permission-create',
        'permission-edit',
        'permission-delete',
        'permission-restore',
        'permission-permanent-delete',
        'permission-import',
        'permission-export',

        // Users
        'user-view',
        'user-create',
        'user-edit',
        'user-delete',
        'user-restore',
        'user-permanent-delete',
        'user-import',
        'user-export',
        'user-activate',
        'user-sync-roles',
        'user-sync-permissions',
        'user-invite',
        'user-report',

        // Bulk Users
        'bulkuser-view',
        'bulkuser-create',
        'bulkuser-edit',
        'bulkuser-delete',
        'bulkuser-restore',
        'bulkuser-permanent-delete',

        // Audit Logs
        'audit-log-view',
        'audit-log-create',
        'audit-log-edit',
        'audit-log-delete',
        'audit-log-restore',
        'audit-log-permanent-delete',

        // ── Sales & Orders (Dot Notation) ──

        // Orders
        'orders.view',
        'orders.create',
        'orders.edit',
        'orders.delete',
        'orders.restore',
        'orders.permanent-delete',

        // Invoices
        'invoices.view',
        'invoices.create',
        'invoices.edit',
        'invoices.delete',
        'invoices.restore',
        'invoices.permanent-delete',

        // Payments
        'payments.view',
        'payments.create',
        'payments.edit',
        'payments.delete',
        'payments.restore',
        'payments.permanent-delete',

        // Refunds
        'refunds.view',
        'refunds.create',
        'refunds.edit',
        'refunds.delete',
        'refunds.restore',
        'refunds.permanent-delete',

        // Returns
        'returns.view',
        'returns.create',
        'returns.edit',
        'returns.delete',
        'returns.restore',
        'returns.permanent-delete',

        // ── Additional Order Specifics ──

        // Order Views
        'orders.view.future_order',
        'orders.view.pending',
        'orders.view.confirmed',
        'orders.view.processing',
        'orders.view.ready_to_ship',
        'orders.view.dispatched',
        'orders.view.delivered',
        'orders.view.returned',
        'orders.view.cancelled',

        // Order Actions
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

        // ── General System Views ──


        // ── Utilities & Tools ──

        // Chat
        'chat-view',
        'chat-create',
        'chat-edit',
        'chat-delete',
        'chat-restore',
        'chat-permanent-delete',

        // Messages
        'messages-view',
        'messages-create',
        'messages-edit',
        'messages-delete',
        'messages-restore',
        'messages-permanent-delete',

        // Calendar
        'calendar-view',
        'calendar-create',
        'calendar-edit',
        'calendar-delete',
        'calendar-restore',
        'calendar-permanent-delete',

        // Files
        'files-view',
        'files-create',
        'files-edit',
        'files-delete',
        'files-restore',
        'files-permanent-delete',

        // Forms
        'forms-view',
        'forms-create',
        'forms-edit',
        'forms-delete',
        'forms-restore',
        'forms-permanent-delete',

        // Security
        'security-view',
        'security-create',
        'security-edit',
        'security-delete',
        'security-restore',
        'security-permanent-delete',

        // Help
        'help-view',
        'help-create',
        'help-edit',
        'help-delete',
        'help-restore',
        'help-permanent-delete',

        // Dashboards & Reports
        'dashboard-view',
        'view-all-data',
        'analytics-view',
        'reports-view',

        // System Settings
        'settings-view',
        'settings-edit',
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
            'reports-view',
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
            'invoices.view',
            'payments.view',
            'refunds.view',
            'returns.view',
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
            'permission-export',
            'role-view',
            'role-export',
            'user-view',
            'user-export',
            'user-report',
            'customer-export',
            'customer-activate',
            'audit-log-view',
        ],
        'User'        => [
            'dashboard-view',
        ],
        'Order Varification 1' => [
            'dashboard-view',
            'customer-view',
            'customeraddress-view',
            'customeraddress-create',
            'customeraddress-edit',
            'customeraddress-delete',
            'orders.view',
            'orders.create',
            'orders.edit',
            'orders.cancel',
            'orders.confirm',
        ],
        'Order Varification 2' => [
            'dashboard-view',
            'customer-view',
            'customeraddress-view',
            'customeraddress-create',
            'customeraddress-edit',
            'customeraddress-delete',
            'orders.view',
            'orders.create',
            'orders.edit',
            'orders.cancel',
            'orders.confirm',
        ],
        'Agent' => [
            'dashboard-view',
            'view_all_customer',
            'customer-view',
            'customeraddress-view',
            'customeraddress-create',
            'customeraddress-edit',
            'customeraddress-delete',
            'customer-create',
            'customer-edit',
            'orders.view',
            'orders.create',
            'orders.edit',
            'orders.cancel',
        ],
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
            [
                'email' => 'order.verifier1@example.com',
                'first_name' => 'Order',
                'last_name' => 'Verifier1',
                'name' => 'Order Verifier 1',
                'department' => 'Operations',
                'phone' => '+91 6543210987',
                'role' => 'Order Varification 1'
            ],
            [
                'email' => 'order.verifier2@example.com',
                'first_name' => 'Order',
                'last_name' => 'Verifier2',
                'name' => 'Order Verifier 2',
                'department' => 'Operations',
                'phone' => '+91 5432109876',
                'role' => 'Order Varification 2'
            ],
            [
                'email' => 'agent.one@example.com',
                'first_name' => 'Agent',
                'last_name' => 'One',
                'name' => 'Agent One',
                'department' => 'Sales',
                'phone' => '+91 4321098765',
                'role' => 'Agent'
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
