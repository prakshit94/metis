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

        // Suppliers
        'supplier-view',
        'supplier-create',
        'supplier-edit',
        'supplier-delete',
        'supplier-restore',
        'supplier-permanent-delete',

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

        // Purchase Orders
        'purchaseorder-view',
        'purchaseorder-create',
        'purchaseorder-edit',
        'purchaseorder-delete',
        'purchaseorder-restore',
        'purchaseorder-permanent-delete',

        // Goods Receipts
        'goodsreceipt-view',
        'goodsreceipt-create',
        'goodsreceipt-edit',
        'goodsreceipt-delete',
        'goodsreceipt-restore',
        'goodsreceipt-permanent-delete',

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
        
        // ── HR Module ──
        
        // Departments
        'department-view',
        'department-create',
        'department-edit',
        'department-delete',
        
        // Attendances
        'attendance-view',
        'attendance-create',
        'attendance-edit',
        'attendance-delete',
        
        // Leaves
        'leave-view',
        'leave-create',
        'leave-edit',
        'leave-delete',
        'bulkuser-restore',
        'bulkuser-permanent-delete',

        // Audit Logs
        'audit-log-view',
        'audit-log-create',
        'audit-log-edit',
        'audit-log-delete',
        'audit-log-restore',
        'audit-log-permanent-delete',
        
        // Call Center
        'skip-call-log',

        // Order Reasons
        'orderreason-view',
        'orderreason-create',
        'orderreason-edit',
        'orderreason-delete',
        'orderreason-restore',
        'orderreason-permanent-delete',


        // ── Sales & Orders (Dot Notation) ──

        // Orders
        'orders.view',
        'orders.export',
        'orders.import',
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

        // Credit Notes
        'credit-notes.view',
        'credit-notes.create',
        'credit-notes.edit',
        'credit-notes.delete',
        'credit-notes.restore',
        'credit-notes.permanent-delete',

        // ── Additional Order Specifics ──

        // Order Views
        'orders.view.future_order',
        'orders.view.pending',
        'orders.view.pending_confirmation',
        'orders.view.confirmed',
        'orders.view.processing',
        'orders.view.ready_to_ship',
        'orders.view.dispatched',
        'orders.view.delivered',
        'orders.view.return_requested',
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
        'sidebar-view',
        'search-view',


        // ── Utilities & Tools ──

        // Chat
        'chat-view',
        'chat-export',
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
        'warehouse-dashboard-view',
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
            'sidebar-view',
            'brand-view',
            'catalog-view',
            'category-view',
            'hsncode-view',
            'productattribute-view',
            'taxrate-view',
            'unitofmeasure-view',
            'warehouse-view',
            'warehouse-dashboard-view',
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
            'credit-notes.view',
            'supplier-view',
            'supplier-create',
            'supplier-edit',
            'supplier-delete',
            'purchaseorder-view',
            'goodsreceipt-view',
            'orders.view.future_order',
            'orders.view.pending',
            'orders.view.pending_confirmation',
            'orders.view.confirmed',
            'orders.view.processing',
            'orders.view.ready_to_ship',
            'orders.view.dispatched',
            'orders.view.delivered',
            'orders.view.return_requested',
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
            'orders.export',
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
            'skip-call-log',
            'chat-view',
            'chat-export',
            'chat-create',
            'chat-edit',
            'chat-delete',
        ],
        'User'        => [
            'sidebar-view',
            'dashboard-view',
            'chat-view',
            'chat-create',
            'chat-edit',
            'chat-delete',
        ],
        'Verification' => [
            'sidebar-view',
            'dashboard-view',
            'customer-view',
            'view_all_customer',
            'customer-create',
            'customer-edit',
            'customeraddress-view',
            'customeraddress-create',
            'customeraddress-edit',
            'customeraddress-delete',
            'orders.view',
            'orders.view.pending',
            'orders.view.dispatched',
            'view_all_order',
            'orders.create',
            'orders.edit',
            'orders.cancel',
            'orders.confirm',
            'orders.deliver',
            'orders.bulk_status',
            'orders.bulk_print',
            'orders.export',
            'chat-view',
            'chat-create',
            'chat-edit',
            'chat-delete',
        ],
        'Operations' => [
            'sidebar-view',
            'warehouse-dashboard-view',
            'orders.view',
            'orders.view.processing',
            'orders.view.ready_to_ship',
            'view_all_order',
            'orders.create',
            'orders.confirm',
            'orders.ship',
            'orders.dispatch',
            'orders.processing',
            'customer-view',
            'customeraddress-view',
            'inventoryadjustment-view',
            'stockmanagement-view',
            'stocktransfer-view',
            'supplier-view',
            'supplier-create',
            'supplier-edit',
            'supplier-delete',
            'purchaseorder-view',
            'goodsreceipt-view',
            'shipping-view',
            'shipping-manage',
            'invoices.view',
            'invoices.create',
            'invoices.edit',
            'payments.view',
            'payments.create',
            'payments.edit',
            'returns.view',
            'returns.create',
            'returns.edit',
            'refunds.view',
            'refunds.create',
            'refunds.edit',
            'credit-notes.view',
            'credit-notes.create',
            'credit-notes.edit',
            'orders.generate_invoice',
            'orders.invoice_pdf',
            'orders.receipt',
            'orders.bulk_print',
            'orders.bulk_status',
            'orders.cod',
            'orders.view.confirmed',
            'search-view',
            'product-view',
            'chat-view',
            'chat-create',
            'chat-edit',
            'chat-delete',
        ],
        'Team Leader' => [
            'sidebar-view',
            'dashboard-view',
            'analytics-view',
            'reports-view',
            'orders.view',
            'view_all_order',
            'orders.filter_status',
            'orders.filter_date',
            'user-view',
            'user-report',
            'audit-log-view',
            'customer-view',
            'customer-export',
            'chat-view',
            'chat-create',
            'chat-edit',
            'chat-delete',
        ],
        'Agent' => [
            'sidebar-view',
            'dashboard-view',
            'view_all_customer',
            'customer-view',
            'catalog-view',
            'product-view',
            'stockmanagement-view',
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
            'chat-view',
            'chat-create',
            'chat-edit',
            'chat-delete',
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
                'middle_name' => 'R',
                'last_name' => 'Kumar',
                'name' => 'Rajesh R Kumar',
                'phone' => '9876543210',
                'employee_id' => 'EMP-001',
                'designation' => 'General Manager',
                'employment_type' => 'Full-time',
                'date_of_birth' => '1985-04-15',
                'gender' => 'Male',
                'blood_group' => 'O+',
                'joining_date' => '2020-01-10',
                'photo' => null,
                'address_line_1' => '101, Business Park',
                'address_line_2' => 'MG Road',
                'post_office' => 'Mumbai GPO',
                'taluka' => 'Mumbai',
                'district' => 'Mumbai City',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'pincode' => '400001',
                'emergency_contact_name' => 'Sunita Kumar',
                'emergency_contact_phone' => '9876500001',
                'role' => 'Manager'
            ],
            [
                'email' => 'priya.sharma@example.com',
                'first_name' => 'Priya',
                'middle_name' => 'M',
                'last_name' => 'Sharma',
                'name' => 'Priya M Sharma',
                'phone' => '8765432109',
                'employee_id' => 'EMP-002',
                'designation' => 'System Administrator',
                'employment_type' => 'Full-time',
                'date_of_birth' => '1990-08-22',
                'gender' => 'Female',
                'blood_group' => 'A+',
                'joining_date' => '2021-03-15',
                'photo' => null,
                'address_line_1' => 'Sector 4, Dwarka',
                'address_line_2' => 'Phase 1',
                'post_office' => 'Dwarka PO',
                'taluka' => 'South West',
                'district' => 'New Delhi',
                'city' => 'New Delhi',
                'state' => 'Delhi',
                'pincode' => '110075',
                'emergency_contact_name' => 'Rahul Sharma',
                'emergency_contact_phone' => '8765400002',
                'role' => 'Admin'
            ],
            [
                'email' => 'amit.patel@example.com',
                'first_name' => 'Amit',
                'middle_name' => 'J',
                'last_name' => 'Patel',
                'name' => 'Amit J Patel',
                'phone' => '7654321098',
                'employee_id' => 'EMP-003',
                'designation' => 'Support Associate',
                'employment_type' => 'Contract',
                'date_of_birth' => '1995-11-05',
                'gender' => 'Male',
                'blood_group' => 'B+',
                'joining_date' => '2023-06-01',
                'photo' => null,
                'village_name' => 'Vastrapur',
                'address_line_1' => 'Street 5',
                'address_line_2' => 'Near Lake',
                'post_office' => 'Vastrapur PO',
                'taluka' => 'Ahmedabad City',
                'district' => 'Ahmedabad',
                'city' => 'Ahmedabad',
                'state' => 'Gujarat',
                'pincode' => '380015',
                'emergency_contact_name' => 'Neha Patel',
                'emergency_contact_phone' => '7654300003',
                'role' => 'User'
            ],
            [
                'email' => 'order.verifier@example.com',
                'first_name' => 'Order',
                'middle_name' => '',
                'last_name' => 'Verifier',
                'name' => 'Order Verifier',
                'phone' => '6543210987',
                'employee_id' => 'EMP-004',
                'designation' => 'Quality Analyst',
                'employment_type' => 'Full-time',
                'date_of_birth' => '1988-02-28',
                'gender' => 'Other',
                'blood_group' => 'AB+',
                'joining_date' => '2022-09-10',
                'photo' => null,
                'address_line_1' => 'MG Road',
                'address_line_2' => 'Block C',
                'post_office' => 'MG Road PO',
                'taluka' => 'Bangalore North',
                'district' => 'Bangalore Urban',
                'city' => 'Bangalore',
                'state' => 'Karnataka',
                'pincode' => '560001',
                'role' => 'Verification'
            ],
            [
                'email' => 'agent.one@example.com',
                'first_name' => 'Agent',
                'middle_name' => '',
                'last_name' => 'One',
                'name' => 'Agent One',
                'phone' => '4321098765',
                'employee_id' => 'EMP-005',
                'designation' => 'Customer Agent',
                'employment_type' => 'Full-time',
                'date_of_birth' => '1992-07-14',
                'gender' => 'Male',
                'blood_group' => 'O-',
                'joining_date' => '2022-11-20',
                'photo' => null,
                'address_line_1' => 'FC Road',
                'address_line_2' => 'Near College',
                'post_office' => 'Deccan PO',
                'taluka' => 'Pune City',
                'district' => 'Pune',
                'city' => 'Pune',
                'state' => 'Maharashtra',
                'pincode' => '411001',
                'role' => 'Agent'
            ],
            [
                'email' => 'operations@example.com',
                'first_name' => 'Operations',
                'middle_name' => '',
                'last_name' => 'Lead',
                'name' => 'Operations Lead',
                'phone' => '3210987654',
                'employee_id' => 'EMP-006',
                'designation' => 'Operations Manager',
                'employment_type' => 'Full-time',
                'date_of_birth' => '1983-12-12',
                'gender' => 'Female',
                'blood_group' => 'A-',
                'joining_date' => '2019-05-05',
                'photo' => null,
                'address_line_1' => 'Mount Road',
                'address_line_2' => 'T Nagar',
                'post_office' => 'T Nagar PO',
                'taluka' => 'Guindy',
                'district' => 'Chennai',
                'city' => 'Chennai',
                'state' => 'Tamil Nadu',
                'pincode' => '600001',
                'role' => 'Operations'
            ],
            [
                'email' => 'team.leader@example.com',
                'first_name' => 'Team',
                'middle_name' => '',
                'last_name' => 'Leader',
                'name' => 'Team Leader',
                'phone' => '2109876543',
                'employee_id' => 'EMP-007',
                'designation' => 'Team Lead',
                'employment_type' => 'Full-time',
                'date_of_birth' => '1987-09-30',
                'gender' => 'Male',
                'blood_group' => 'B-',
                'joining_date' => '2020-08-15',
                'photo' => null,
                'address_line_1' => 'Hitech City',
                'address_line_2' => 'Phase 2',
                'post_office' => 'Madhapur PO',
                'taluka' => 'Serilingampally',
                'district' => 'Ranga Reddy',
                'city' => 'Hyderabad',
                'state' => 'Telangana',
                'pincode' => '500001',
                'role' => 'Team Leader'
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
