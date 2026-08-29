<?php

declare(strict_types=1);

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\ReferralProgramController;
use App\Modules\Catalog\Controllers\BrandController;
use App\Modules\Catalog\Controllers\CategoryController;
use App\Modules\Catalog\Controllers\HsnCodeController;
use App\Modules\Catalog\Controllers\ProductAttributeController;
use App\Modules\Catalog\Controllers\ProductController;
use App\Modules\Catalog\Controllers\ProductController as CatalogProductController;
use App\Modules\Catalog\Controllers\TaxRateController;
use App\Modules\Catalog\Controllers\UnitOfMeasureController;
use App\Modules\Catalog\Controllers\WarehouseController;
use App\Modules\Core\Controllers\PageController;
use App\Modules\Core\Controllers\ShippingController;
use App\Modules\Core\Controllers\ShippingSettingsController;
use App\Modules\Core\Controllers\VillageController;
use App\Modules\Customers\Controllers\CustomerAddressController;
use App\Modules\Customers\Controllers\CustomerController;
use App\Modules\Customers\Models\Party;
use App\Modules\Inventory\Controllers\GoodsReceiptController;
use App\Modules\Inventory\Controllers\InventoryAdjustmentController;
use App\Modules\Inventory\Controllers\PurchaseOrderController;
use App\Modules\Inventory\Controllers\StockManagementController;
use App\Modules\Inventory\Controllers\StockTransferController;
use App\Modules\Inventory\Controllers\SupplierController;
use App\Modules\Orders\Controllers\CallTagAdminController;
use App\Modules\Orders\Controllers\CallTaggingController;
use App\Modules\Orders\Controllers\CouponController;
use App\Modules\Orders\Controllers\CreditNoteController;
use App\Modules\Orders\Controllers\InvoiceController;
use App\Modules\Orders\Controllers\OrderComplaintController;
use App\Modules\Orders\Controllers\OrderController;
use App\Modules\Orders\Controllers\OrderReasonController;
use App\Modules\Orders\Controllers\OrderReturnController;
use App\Modules\Orders\Controllers\PaymentController;
use App\Modules\Orders\Controllers\PromotionsController;
use App\Modules\Orders\Controllers\RefundController;
use App\Modules\Users\Controllers\AttendanceController;
use App\Modules\Users\Controllers\AuthController;
use App\Modules\Users\Controllers\BulkUserController;
use App\Modules\Users\Controllers\DepartmentController;
use App\Modules\Users\Controllers\HolidayController;
use App\Modules\Users\Controllers\HrSettingController;
use App\Modules\Users\Controllers\LeaveBalanceController;
use App\Modules\Users\Controllers\LeaveController;
use App\Modules\Users\Controllers\OrgChartController;
use App\Modules\Users\Controllers\PermissionController;
use App\Modules\Users\Controllers\RoleController;
use App\Modules\Users\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| All routes here are stateless and protected by Sanctum token auth
| (except the login endpoint which is public).
|
| Device type is determined per-request by:
|   1. X-App-Source: mobile header
|   2. /api/ URL prefix (applied automatically here)
|
*/

// ─── Public ───────────────────────────────────────────────────────────────────

Route::post('/auth/login', [AuthController::class, 'login'])
    ->name('api.auth.login');

// ─── Authenticated (Sanctum token required) ───────────────────────────────────

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/shipping/settings', [ShippingSettingsController::class, 'store'])->name('api.shipping.settings.store')->middleware('permission:settings-edit|shipping-view');

    // ── Auth & Token Management ──────────────────────────────────────────────
    Route::prefix('auth')->name('api.auth.')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout'])
            ->name('logout');

        Route::post('/revoke-other-tokens', [AuthController::class, 'revokeOtherTokens'])
            ->name('revoke-other-tokens');
    });

    // ── Dashboard & Analytics ────────────────────────────────────────────────
    Route::get('/dashboard', [PageController::class, 'dashboard'])->name('api.dashboard');
    Route::get('/reports', [PageController::class, 'reports'])->name('api.reports');

    // ── Notifications / Activities ───────────────────────────────────────────
    Route::get('/activities/recent', [AuditLogController::class, 'recentActivities'])
        ->name('api.activities.recent');

    Route::post('/activities/{id}/read', [AuditLogController::class, 'markAsRead'])
        ->name('api.activities.read');

    // ── Roles Management ─────────────────────────────────────────────────────
    Route::group([], function (): void {
        Route::patch('roles/{role}/restore', [RoleController::class, 'restore'])
            ->name('api.roles.restore');

        Route::delete('roles/{role}/force', [RoleController::class, 'forceDelete'])
            ->name('api.roles.force-delete');

        Route::patch('permissions/{permission}/restore', [PermissionController::class, 'restore'])
            ->name('api.permissions.restore');

        Route::delete('permissions/{permission}/force', [PermissionController::class, 'forceDelete'])
            ->name('api.permissions.force-delete');

        Route::get('/roles/options', [RoleController::class, 'options'])
            ->name('api.roles.options');

        Route::get('/permissions/options', [PermissionController::class, 'options'])
            ->name('api.permissions.options');

        Route::apiResource('roles', RoleController::class)
            ->names([
                'index' => 'api.roles.index',
                'store' => 'api.roles.store',
                'show' => 'api.roles.show',
                'update' => 'api.roles.update',
                'destroy' => 'api.roles.destroy',
            ]);

        Route::apiResource('permissions', PermissionController::class)
            ->names([
                'index' => 'api.permissions.index',
                'store' => 'api.permissions.store',
                'show' => 'api.permissions.show',
                'update' => 'api.permissions.update',
                'destroy' => 'api.permissions.destroy',
            ]);
    });

    // ── User Management ──────────────────────────────────────────────────────
    Route::group([], function (): void {
        Route::patch('users/{user}/restore', [UserController::class, 'restore'])
            ->name('api.users.restore');

        Route::delete('users/{user}/force', [UserController::class, 'forceDelete'])
            ->name('api.users.force-delete');

        Route::apiResource('users', UserController::class)
            ->names([
                'index' => 'api.users.index',
                'store' => 'api.users.store',
                'show' => 'api.users.show',
                'update' => 'api.users.update',
                'destroy' => 'api.users.destroy',
            ]);

        Route::patch('users/{user}/toggle-active', [UserController::class, 'toggleActive'])
            ->name('api.users.toggle-active');

        Route::post('users/{user}/sync-roles', [UserController::class, 'syncRoles'])
            ->name('api.users.sync-roles');

        Route::post('users/{user}/sync-permissions', [UserController::class, 'syncPermissions'])
            ->name('api.users.sync-permissions');

        Route::get('users/{user}/login-history', [UserController::class, 'loginHistory'])
            ->name('api.users.login-history');

        // HR & Departments
        Route::middleware('permission:department-view')->get('/departments', [DepartmentController::class, 'index']);
        Route::middleware('permission:department-view')->get('/departments/{department}', [DepartmentController::class, 'show']);
        Route::middleware('permission:department-create')->post('/departments', [DepartmentController::class, 'store']);
        Route::middleware('permission:department-edit')->put('/departments/{department}', [DepartmentController::class, 'update']);
        Route::middleware('permission:department-delete')->delete('/departments/{department}', [DepartmentController::class, 'destroy']);

        // Org Chart
        Route::middleware('permission:department-view')->get('/org-chart', [OrgChartController::class, 'index']);

        Route::middleware('permission:department-view')->get('/hr-settings/{type}', [HrSettingController::class, 'list']);
        Route::middleware('permission:department-create')->post('/hr-settings/{type}', [HrSettingController::class, 'store']);
        Route::middleware('permission:department-edit')->put('/hr-settings/{type}/{id}', [HrSettingController::class, 'update']);
        Route::middleware('permission:department-edit')->patch('/hr-settings/{type}/{id}/toggle', [HrSettingController::class, 'toggleActive']);
        Route::middleware('permission:department-delete')->delete('/hr-settings/{type}/{id}', [HrSettingController::class, 'destroy']);

        // Attendance
        Route::get('/attendances/export/summary', [AttendanceController::class, 'exportSummary']);
        Route::get('/attendances/export/detailed', [AttendanceController::class, 'exportDetailed']);
        Route::post('/attendances/bulk-action', [AttendanceController::class, 'bulkAction']);
        Route::patch('/attendances/{attendance}/restore', [AttendanceController::class, 'restore']);
        Route::delete('/attendances/{attendance}/force', [AttendanceController::class, 'forceDelete']);
        Route::apiResource('attendances', AttendanceController::class);

        // Leaves
        Route::post('/leaves/bulk-action', [LeaveController::class, 'bulkAction']);
        Route::middleware('permission:leave-view')->get('/leaves', [LeaveController::class, 'index']);
        Route::middleware('permission:leave-view')->get('/leaves/{leave}', [LeaveController::class, 'show']);
        Route::middleware('permission:leave-create')->post('/leaves', [LeaveController::class, 'store']);
        Route::middleware('permission:leave-edit')->put('/leaves/{leave}', [LeaveController::class, 'update']);
        Route::middleware('permission:leave-delete')->delete('/leaves/{leave}', [LeaveController::class, 'destroy']);
        Route::patch('leaves/{leave}/status', [LeaveController::class, 'updateStatus'])
            ->name('api.leaves.status');

        // Leave Balances
        Route::post('/leave-balances/bulk-action', [LeaveBalanceController::class, 'bulkAction']);
        Route::apiResource('leave-balances', LeaveBalanceController::class);

        // Holidays
        Route::post('/holidays/bulk-action', [HolidayController::class, 'bulkAction']);
        Route::apiResource('holidays', HolidayController::class);
    });

    // ── Financial & Sales APIs ─────────────────────────────────────────────
    Route::prefix('orders')->name('api.orders.')->group(function (): void {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::post('/', [OrderController::class, 'store'])->name('store');
        Route::get('/{order}', [OrderController::class, 'show'])->name('show');
        Route::patch('/{order}', [OrderController::class, 'update'])->name('update');
        Route::delete('/{order}', [OrderController::class, 'destroy'])->name('destroy');
        // Bulk Actions & Helpers
        Route::post('/bulk-status', [OrderController::class, 'bulkStatus'])->name('bulk-status');
        Route::post('/bulk-generate-invoices', [OrderController::class, 'generateBulkInvoices'])->name('bulk-generate-invoices');
        Route::get('/bulk-print', [OrderController::class, 'bulkPrint'])->name('bulk-print');
        Route::get('/export', [OrderController::class, 'bulkExport'])->name('export');
        Route::post('/export-selected', [OrderController::class, 'exportSelected'])->name('export-selected');
        Route::post('/import', [OrderController::class, 'bulkImport'])->name('import');
    });

    Route::prefix('complaints')->name('api.complaints.')->group(function (): void {
        Route::get('/stats', [OrderComplaintController::class, 'stats'])->name('stats');
        Route::get('/', [OrderComplaintController::class, 'index'])->name('index');
        Route::post('/', [OrderComplaintController::class, 'store'])->name('store');
        Route::put('/{complaint}', [OrderComplaintController::class, 'update'])->name('update');
        Route::post('/{complaint}/reply', [OrderComplaintController::class, 'reply'])->name('reply');
        Route::delete('/{complaint}', [OrderComplaintController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/restore', [OrderComplaintController::class, 'restore'])->name('restore');
        Route::delete('/{id}/force-delete', [OrderComplaintController::class, 'forceDelete'])->name('force-delete');
        Route::post('/bulk-action', [OrderComplaintController::class, 'bulkAction'])->name('bulk-action');
        Route::get('/export', [OrderComplaintController::class, 'bulkExport'])->name('export');
        Route::post('/export-selected', [OrderComplaintController::class, 'exportSelected'])->name('export-selected');
    });

    Route::get('/products-search-api', [ProductController::class, 'searchApi'])->name('api.products.search.api');
    Route::post('/coupons/validate', [CouponController::class, 'validateApi'])->name('api.coupons.validate');

    Route::prefix('credit-notes')->name('api.credit-notes.')->group(function (): void {
        Route::get('/', [CreditNoteController::class, 'index'])->name('index');
        Route::post('/', [CreditNoteController::class, 'store'])->name('store');
        Route::patch('/{creditNote}', [CreditNoteController::class, 'update'])->name('update');
        Route::delete('/{creditNote}', [CreditNoteController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('invoices')->name('api.invoices.')->group(function (): void {
        Route::get('/', [InvoiceController::class, 'index'])->name('index');
        Route::post('/bulk-status', [InvoiceController::class, 'bulkStatus'])->name('bulk-status');
        Route::get('/{invoice}', [InvoiceController::class, 'show'])->name('show');
        Route::get('/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('pdf');
    });

    Route::prefix('payments')->name('api.payments.')->group(function (): void {
        Route::get('/', [PaymentController::class, 'index'])->name('index');
        Route::post('/bulk-status', [PaymentController::class, 'bulkStatus'])->name('bulk-status');
        Route::post('/', [PaymentController::class, 'store'])->name('store');
        Route::get('/{payment}', [PaymentController::class, 'show'])->name('show');
    });

    Route::prefix('refunds')->name('api.refunds.')->group(function (): void {
        Route::get('/', [RefundController::class, 'index'])->name('index');
        Route::post('/bulk-status', [RefundController::class, 'bulkStatus'])->name('bulk-status');
        Route::get('/{refund}', [RefundController::class, 'show'])->name('show');
    });

    Route::prefix('returns')->name('api.returns.')->group(function (): void {
        Route::get('/', [OrderReturnController::class, 'index'])->name('index');
        Route::post('/', [OrderReturnController::class, 'store'])->name('store');
        Route::get('/{orderReturn}', [OrderReturnController::class, 'show'])->name('show');
        Route::post('/{orderReturn}/process', [OrderReturnController::class, 'process'])->name('process');
    });

    // ── Reason Codes API (Returns, Cancellations, Reschedules) ───────────────
    Route::prefix('order-reasons/{type}')->name('api.order-reasons.')->group(function (): void {
        Route::get('/', [OrderReasonController::class, 'list'])->name('list');
        Route::post('/', [OrderReasonController::class, 'store'])->name('store');
        Route::put('/{id}', [OrderReasonController::class, 'update'])->name('update');
        Route::delete('/{id}', [OrderReasonController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/toggle', [OrderReasonController::class, 'toggleActive'])->name('toggle');
    });

    // Promotions JSON API
    Route::prefix('promotions')->name('api.promotions.')->group(function (): void {
        // Coupons
        Route::get('/coupons', [PromotionsController::class, 'couponsIndex'])->name('coupons.index');
        Route::post('/coupons', [PromotionsController::class, 'couponsStore'])->name('coupons.store');
        Route::patch('/coupons/{coupon}', [PromotionsController::class, 'couponsUpdate'])->name('coupons.update');
        Route::delete('/coupons/{coupon}', [PromotionsController::class, 'couponsDestroy'])->name('coupons.destroy');
        Route::patch('/coupons/{coupon}/toggle', [PromotionsController::class, 'couponsToggle'])->name('coupons.toggle');
        Route::post('/coupons/bulk-action', [PromotionsController::class, 'couponsBulk'])->name('coupons.bulk');
        // Offers
        Route::get('/offers', [PromotionsController::class, 'offersIndex'])->name('offers.index');
        Route::post('/offers', [PromotionsController::class, 'offersStore'])->name('offers.store');
        Route::patch('/offers/{offer}', [PromotionsController::class, 'offersUpdate'])->name('offers.update');
        Route::delete('/offers/{offer}', [PromotionsController::class, 'offersDestroy'])->name('offers.destroy');
        Route::patch('/offers/{offer}/toggle', [PromotionsController::class, 'offersToggle'])->name('offers.toggle');
        Route::post('/offers/bulk-action', [PromotionsController::class, 'offersBulk'])->name('offers.bulk');

        // Referral Programs
        Route::get('/referral-programs', [ReferralProgramController::class, 'index'])->name('referrals.programs.index');
        Route::post('/referral-programs', [ReferralProgramController::class, 'store'])->name('referrals.programs.store');
        Route::post('/referral-programs/bulk-action', [ReferralProgramController::class, 'bulk'])->name('referrals.programs.bulk');
        Route::put('/referral-programs/{id}', [ReferralProgramController::class, 'update'])->name('referrals.programs.update');
        Route::patch('/referral-programs/{id}/toggle', [ReferralProgramController::class, 'toggle'])->name('referrals.programs.toggle');
        Route::delete('/referral-programs/{id}', [ReferralProgramController::class, 'destroy'])->name('referrals.programs.destroy');
    });

    // Catalog API Routes
    Route::apiResource('/brands', BrandController::class);
    Route::apiResource('/categories', CategoryController::class);
    Route::apiResource('/uom', UnitOfMeasureController::class);
    Route::apiResource('/tax-rates', TaxRateController::class);
    Route::apiResource('/hsn-codes', HsnCodeController::class);
    Route::apiResource('/warehouses', WarehouseController::class);
    Route::apiResource('/attributes', ProductAttributeController::class);
    Route::post('/attributes/{attribute}/values', [ProductAttributeController::class, 'storeValue'])->name('api.attributes.values.store');
    Route::patch('/attributes/values/{value}', [ProductAttributeController::class, 'updateValue'])->name('api.attributes.values.update');
    Route::delete('/attributes/values/{value}', [ProductAttributeController::class, 'destroyValue'])->name('api.attributes.values.destroy');

    // Inventory API Routes
    Route::prefix('inventory')->name('api.inventory.')->group(function (): void {
        Route::get('/stocks', [StockManagementController::class, 'index'])->name('stocks.index');
        Route::post('/stocks/set', [StockManagementController::class, 'setStock'])->name('stocks.set');
        Route::get('/stocks/show', [StockManagementController::class, 'show'])->name('stocks.show');
        Route::get('/stocks/warehouse-options', [StockManagementController::class, 'warehouseOptions'])->name('stocks.warehouse-options');

        Route::get('/transfers/options', [StockTransferController::class, 'options'])->name('transfers.options');
        Route::post('/transfers/bulk-action', [StockTransferController::class, 'bulkAction'])->name('transfers.bulk-action');
        Route::post('/transfers/{stockTransfer}/send', [StockTransferController::class, 'send'])->name('transfers.send');
        Route::post('/transfers/{stockTransfer}/receive', [StockTransferController::class, 'receive'])->name('transfers.receive');
        Route::post('/transfers/{stockTransfer}/cancel', [StockTransferController::class, 'cancel'])->name('transfers.cancel');
        Route::apiResource('/transfers', StockTransferController::class);

        Route::get('/adjustments/options', [InventoryAdjustmentController::class, 'options'])->name('adjustments.options');
        Route::post('/adjustments/bulk-action', [InventoryAdjustmentController::class, 'bulkAction'])->name('adjustments.bulk-action');
        Route::post('/adjustments/{inventoryAdjustment}/approve', [InventoryAdjustmentController::class, 'approve'])->name('adjustments.approve');
        Route::post('/adjustments/{inventoryAdjustment}/reject', [InventoryAdjustmentController::class, 'reject'])->name('adjustments.reject');
        Route::apiResource('/adjustments', InventoryAdjustmentController::class);
    });

    Route::prefix('products')->name('api.products.')->group(function (): void {
        Route::get('/', [CatalogProductController::class, 'index'])->name('index');
        Route::get('/export', [CatalogProductController::class, 'export'])->name('export');
        Route::post('/import', [CatalogProductController::class, 'import'])->name('import');
        Route::post('/bulk-status', [CatalogProductController::class, 'bulkStatus'])->name('bulk-status');
        Route::post('/bulk-disable-sku', [CatalogProductController::class, 'bulkDisableSku'])->name('bulk-disable-sku');
        Route::post('/bulk-enable-sku', [CatalogProductController::class, 'bulkEnableSku'])->name('bulk-enable-sku');
        Route::post('/bulk-delete', [CatalogProductController::class, 'bulkDelete'])->name('bulk-delete');
        Route::post('/{product}/duplicate', [CatalogProductController::class, 'duplicate'])->name('duplicate');
        Route::post('/{product}/restore', [CatalogProductController::class, 'restore'])->name('restore');
        Route::delete('/{product}/force-delete', [CatalogProductController::class, 'forceDelete'])->name('force-delete');
        Route::get('/{product}', [CatalogProductController::class, 'show'])->name('show');
        Route::patch('/{product}', [CatalogProductController::class, 'update'])->name('update');
        Route::delete('/{product}', [CatalogProductController::class, 'destroy'])->name('destroy');
        Route::post('/', [CatalogProductController::class, 'store'])->name('store');
    });

    // Bulk action must be defined before the resource to avoid route conflict
    Route::post('/users/bulk-action', BulkUserController::class)->name('api.users.bulk');

    // Customers API Routes
    Route::get('/customers/check-referral/{code}', function ($code) {
        $party = Party::where('referral_code', $code)->first(['id', 'firstname', 'lastname']);

        return response()->json([
            'valid' => (bool) $party,
            'name' => $party ? trim("{$party->firstname} {$party->lastname}") : null,
        ]);
    })->name('api.customers.check-referral');

    Route::post('/customers/bulk-action', [CustomerController::class, 'bulkAction'])->name('api.customers.bulk');
    Route::patch('/customers/{customer}/restore', [CustomerController::class, 'restore'])->name('api.customers.restore');
    Route::delete('/customers/{customer}/force', [CustomerController::class, 'forceDelete'])->name('api.customers.force-delete');
    Route::patch('/customers/{customer}/toggle-active', [CustomerController::class, 'toggleActive'])->name('api.customers.toggle-active');
    Route::apiResource('/customers', CustomerController::class)->names([
        'index' => 'api.customers.index',
        'store' => 'api.customers.store',
        'show' => 'api.customers.show',
        'update' => 'api.customers.update',
        'destroy' => 'api.customers.destroy',
    ]);
    Route::apiResource('/customers.addresses', CustomerAddressController::class)->names([
        'store' => 'api.customers.addresses.store',
        'update' => 'api.customers.addresses.update',
        'destroy' => 'api.customers.addresses.destroy',
    ])->only(['store', 'update', 'destroy']);

    // Villages API Routes
    Route::post('/villages/bulk-action', [VillageController::class, 'bulkAction'])->name('api.villages.bulk');
    Route::get('/villages/services-options', [VillageController::class, 'servicesOptions'])->name('api.villages.services-options');
    Route::get('/villages/search', [VillageController::class, 'search'])->name('api.villages.search');
    Route::post('/villages/import', [VillageController::class, 'import'])->name('api.villages.import');
    Route::get('/villages/import-template', [VillageController::class, 'importTemplate'])->name('api.villages.import-template');
    Route::get('/villages/export', [VillageController::class, 'export'])->name('api.villages.export');
    Route::post('/villages/export-selected', [VillageController::class, 'exportSelected'])->name('api.villages.export-selected');
    Route::apiResource('/villages', VillageController::class)->names([
        'index' => 'api.villages.index',
        'store' => 'api.villages.store',
        'show' => 'api.villages.show',
        'update' => 'api.villages.update',
        'destroy' => 'api.villages.destroy',
    ]);

    // Shipping & Tracking API
    Route::prefix('shipping')->name('api.shipping.')->group(function (): void {
        Route::get('/shipments', [ShippingController::class, 'shipmentsIndex'])->name('shipments.index');
        Route::post('/shipments/bulk-action', [ShippingController::class, 'shipmentsBulk'])->name('shipments.bulk');
        Route::post('/shipments/{shipment}/status', [ShippingController::class, 'updateShipmentStatus'])->name('shipments.status');
        Route::get('/shipments/{shipment}/tracking', [ShippingController::class, 'trackingEvents'])->name('shipments.tracking');
        Route::post('/shipments/{shipment}/tracking-event', [ShippingController::class, 'addTrackingEvent'])->name('shipments.add-tracking-event');

        Route::get('/services', [ShippingController::class, 'servicesIndex'])->name('services.index');
        Route::get('/services/provider-options', [ShippingController::class, 'providerOptions'])->name('services.provider-options');
        Route::post('/services/bulk-action', [ShippingController::class, 'servicesBulk'])->name('services.bulk');
        Route::post('/services', [ShippingController::class, 'storeService'])->name('services.store');
        Route::patch('/services/{service}', [ShippingController::class, 'updateService'])->name('services.update');
        Route::post('/services/{service}/toggle', [ShippingController::class, 'toggleService'])->name('services.toggle');
        Route::delete('/services/{service}', [ShippingController::class, 'destroyService'])->name('services.delete');
    });

    // ── Procurement API Routes ─────────────────────────────────────────────
    Route::prefix('procurement')->name('api.procurement.')->group(function (): void {
        Route::get('/purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
        Route::post('/purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
        Route::post('/purchase-orders/bulk', [PurchaseOrderController::class, 'bulkAction'])->name('purchase-orders.bulk');
        Route::get('/purchase-orders/{order}/pdf', [PurchaseOrderController::class, 'downloadPdf'])->name('purchase-orders.pdf');
        Route::delete('/purchase-orders/{order}', [PurchaseOrderController::class, 'destroy'])->name('purchase-orders.destroy');
        Route::post('/purchase-orders/{id}/restore', [PurchaseOrderController::class, 'restore'])->name('purchase-orders.restore');
        Route::post('/purchase-orders/{order}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
        Route::post('/purchase-orders/{order}/reject', [PurchaseOrderController::class, 'reject'])->name('purchase-orders.reject');
        Route::post('/purchase-orders/{order}/receive', [GoodsReceiptController::class, 'store'])->name('purchase-orders.receive');

        Route::get('/goods-receipts', [GoodsReceiptController::class, 'index'])->name('goods-receipts.index');
        Route::get('/goods-receipts/{receipt}/pdf', [GoodsReceiptController::class, 'downloadPdf'])->name('goods-receipts.pdf');
        Route::post('/goods-receipts/bulk', [GoodsReceiptController::class, 'bulkAction'])->name('goods-receipts.bulk');
        Route::delete('/goods-receipts/{receipt}', [GoodsReceiptController::class, 'destroy'])->name('goods-receipts.destroy');
        Route::post('/goods-receipts/{id}/restore', [GoodsReceiptController::class, 'restore'])->name('goods-receipts.restore');

        Route::post('/suppliers/bulk', [SupplierController::class, 'bulkAction'])->name('suppliers.bulk');
        Route::apiResource('/suppliers', SupplierController::class);
    });

    // ── Call Tagging ────────────────────────────────────────────────────────
    Route::get('/call-tags', [CallTaggingController::class, 'getTags']);
    Route::get('/call-tags/{tag}/form', [CallTaggingController::class, 'getFormFields']);
    Route::post('/call-logs', [CallTaggingController::class, 'storeCallLog']);

    Route::get('/call-tags-admin', [CallTagAdminController::class, 'index'])->name('api.call-tags.index');
    Route::post('/call-tags-admin', [CallTagAdminController::class, 'store']);
    Route::put('/call-tags-admin/{callTag}', [CallTagAdminController::class, 'update']);
    Route::delete('/call-tags-admin/{callTag}', [CallTagAdminController::class, 'destroy']);
    Route::post('/call-tags-admin/bulk-action', [CallTagAdminController::class, 'bulkAction']);

    // ── System Audit Logs ───────────────────────────────────────────────────
    Route::get('/admin/audit-logs', [AuditLogController::class, 'index'])->name('api.audit-logs.index');
    Route::delete('/admin/audit-logs/clear', [AuditLogController::class, 'clearAll'])->name('api.audit-logs.clear');
    Route::delete('/admin/audit-logs/destroy', [AuditLogController::class, 'destroy'])->name('api.audit-logs.destroy');

});
