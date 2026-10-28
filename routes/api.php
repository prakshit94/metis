<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Chat\ConversationController;
use App\Http\Controllers\Api\Chat\GroupController;
use App\Http\Controllers\Api\Chat\MessageController;
use App\Http\Controllers\Api\Chat\PresenceController;
use App\Http\Controllers\Api\Chat\SearchController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\ReferralProgramController;
use App\Http\Controllers\TargetController;
use App\Modules\Catalog\Controllers\BrandController;
use App\Modules\Catalog\Controllers\CategoryController;
use App\Modules\Catalog\Controllers\HsnCodeController;
use App\Modules\Catalog\Controllers\ProductAttributeController;
use App\Modules\Catalog\Controllers\ProductController;
use App\Modules\Catalog\Controllers\ProductController as CatalogProductController;
use App\Modules\Catalog\Controllers\TaxRateController;
use App\Modules\Catalog\Controllers\UnitOfMeasureController;
use App\Modules\Catalog\Controllers\WarehouseController;
use App\Modules\Core\Controllers\FileManagerController;
use App\Modules\Core\Controllers\PageController;
use App\Modules\Core\Controllers\ShippingController;
use App\Modules\Core\Controllers\ShippingSettingsController;
use App\Modules\Core\Controllers\VillageController;
use App\Modules\Customers\Controllers\CustomerAddressController;
use App\Modules\Customers\Controllers\CustomerController;
use App\Modules\Customers\Controllers\CustomerSettingsController;
use App\Modules\Customers\Models\Party;
use App\Modules\Inventory\Controllers\GoodsReceiptController;
use App\Modules\Inventory\Controllers\InventoryAdjustmentController;
use App\Modules\Inventory\Controllers\PurchaseOrderController;
use App\Modules\Inventory\Controllers\StockManagementController;
use App\Modules\Inventory\Controllers\StockTransferController;
use App\Modules\Inventory\Controllers\SupplierController;
use App\Modules\Inventory\Controllers\WarehouseDashboardController;
use App\Modules\Orders\Controllers\CallTagAdminController;
use App\Modules\Orders\Controllers\CallTaggingController;
use App\Modules\Orders\Controllers\CouponController;
use App\Modules\Orders\Controllers\CreditNoteController;
use App\Modules\Orders\Controllers\IndiaPostWebhookController;
use App\Modules\Orders\Controllers\InvoiceController;
use App\Modules\Orders\Controllers\OrderComplaintController;
use App\Modules\Orders\Controllers\OrderController;
use App\Modules\Orders\Controllers\OrderReasonController;
use App\Modules\Orders\Controllers\OrderReturnController;
use App\Modules\Orders\Controllers\PaymentController;
use App\Modules\Orders\Controllers\PaymentImportController;
use App\Modules\Orders\Controllers\PromotionsController;
use App\Modules\Orders\Controllers\RefundController;
use App\Modules\Users\Controllers\AttendanceController;
use App\Modules\Users\Controllers\AttendanceCorrectionController;
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
use App\Modules\Users\Controllers\RosterController;
use App\Modules\Users\Controllers\SecurityController;
use App\Modules\Users\Controllers\SecurityMailController;
use App\Modules\Users\Controllers\TeamController;
use App\Modules\Users\Controllers\TwoFactorController;
use App\Modules\Users\Controllers\UserController;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Authenticated routes accept Sanctum bearer tokens and first-party sessions.
| Login is public; provider webhooks verify their configured integration credential.
|
| Device type is determined per-request by:
|   1. X-App-Source: mobile header
|   2. /api/ URL prefix (applied automatically here)
|
*/

// ─── Public ───────────────────────────────────────────────────────────────────

Route::post('/auth/login', [AuthController::class, 'login'])
    ->name('api.auth.login');

Route::post('/auth/two-factor/enroll', [AuthController::class, 'twoFactorEnroll'])->middleware('throttle:10,1,otp-mobile-enroll:');
Route::post('/auth/two-factor/email', [AuthController::class, 'twoFactorEmail'])->middleware('throttle:10,1,otp-mobile-email:');
Route::post('/auth/two-factor/challenge', [AuthController::class, 'twoFactorChallenge'])->middleware('throttle:20,1,otp-mobile-verify:');

// ─── Webhooks ─────────────────────────────────────────────────────────────────
Route::post('/webhooks/indiapost', [IndiaPostWebhookController::class, 'handle'])
    ->name('api.webhooks.indiapost')
    ->withoutMiddleware([EnsureFrontendRequestsAreStateful::class]);

// ─── Authenticated (Sanctum token required) ───────────────────────────────────
Route::middleware('auth:sanctum')->group(function (): void {
    // Shared chat endpoints support native tokens and browser sessions.
    Route::prefix('chat')->middleware('throttle:chat')->group(function () {
        Route::get('/unread-count', [ConversationController::class, 'unreadCount']);
        Route::get('/conversations', [ConversationController::class, 'index']);
        Route::post('/conversations', [ConversationController::class, 'store']);
        Route::get('/conversations/{conversation}', [ConversationController::class, 'show']);
        Route::post('/conversations/{conversation}/archive', [ConversationController::class, 'archive']);
        Route::post('/conversations/{conversation}/pin', [ConversationController::class, 'pin']);
        Route::get('/conversations/{conversation}/messages', [MessageController::class, 'index']);
        Route::post('/conversations/{conversation}/messages', [MessageController::class, 'store']);
        Route::post('/conversations/{conversation}/read', [MessageController::class, 'markRead']);
        Route::put('/messages/{message}', [MessageController::class, 'update']);
        Route::delete('/messages/{message}', [MessageController::class, 'destroy']);
        Route::post('/messages/{message}/edit', [MessageController::class, 'update']);
        Route::post('/messages/{message}/delete', [MessageController::class, 'destroy']);
        Route::post('/messages/{message}/forward', [MessageController::class, 'forward']);
        Route::put('/groups/{conversation}', [GroupController::class, 'update']);
        Route::delete('/groups/{conversation}', [GroupController::class, 'destroy']);
        Route::post('/groups/{conversation}/members', [GroupController::class, 'addMember']);
        Route::delete('/groups/{conversation}/members', [GroupController::class, 'removeMember']);
        Route::post('/groups/{conversation}/members/remove', [GroupController::class, 'removeMember']);
        Route::put('/groups/{conversation}/members/role', [GroupController::class, 'updateRole']);
        Route::post('/groups/{conversation}/members/role', [GroupController::class, 'updateRole']);
        Route::post('/groups/{conversation}/transfer-owner', [GroupController::class, 'transferOwner']);
        Route::post('/groups/{conversation}/leave', [GroupController::class, 'leave']);
        Route::get('/active-status', [PresenceController::class, 'index']);
        Route::post('/active-status', [PresenceController::class, 'update']);
        Route::get('/users/export', [App\Http\Controllers\Api\Chat\UserController::class, 'export']);
        Route::get('/users', [App\Http\Controllers\Api\Chat\UserController::class, 'index']);
        Route::get('/search', SearchController::class);
    });

    Route::get('/auth/me', [AuthController::class, 'me'])->name('api.auth.me');
    Route::get('/orders/create-options', [OrderController::class, 'create'])->middleware('permission:orders.create');
    Route::get('/orders/{order}/edit-options', [OrderController::class, 'edit']);
    // Browser workflow parity for bearer-token clients. Static paths precede resources.
    Route::get('analytics/data', [PageController::class, 'analyticsData']);
    Route::get('reports/export', [PageController::class, 'exportReports']);
    Route::get('inventory/dashboard', [WarehouseDashboardController::class, 'index'])->middleware('permission:warehouse-dashboard-view');
    Route::get('inventory/dashboard/activities', [WarehouseDashboardController::class, 'activities'])->middleware('permission:warehouse-dashboard-view');
    Route::get('orders/nearby-selling-insights', [OrderController::class, 'nearbySellingInsights'])->middleware('permission:orders.create|orders.edit');
    Route::post('orders/import-deliver', [OrderController::class, 'importBulkDeliver']);
    Route::get('orders/import-deliver-template', [OrderController::class, 'importBulkDeliverTemplate']);
    Route::post('orders/import-return', [OrderController::class, 'importBulkReturn']);
    Route::get('orders/import-return-template', [OrderController::class, 'importBulkReturnTemplate']);
    Route::post('orders/{order}/generate-invoice', [OrderController::class, 'generateInvoice']);
    Route::get('orders/{order}/invoice-pdf', [OrderController::class, 'downloadInvoice']);
    Route::get('orders/{order}/shipping-label', [OrderController::class, 'downloadShippingLabel']);
    Route::get('orders/{order}/cod-pdf', [OrderController::class, 'downloadReceipt']);
    Route::get('orders/{order}/receipt', [OrderController::class, 'receipt']);
    Route::get('payments/import/sample', [PaymentImportController::class, 'downloadSample']);
    Route::post('payments/import/preview', [PaymentImportController::class, 'preview']);
    Route::post('payments/import/process', [PaymentImportController::class, 'process']);
    Route::get('credit-notes/customers/search', [CreditNoteController::class, 'searchCustomers']);
    Route::get('customers/search-by-phone', [CustomerController::class, 'searchByPhone']);
    Route::post('customers/{customer}/orders/place', [CustomerController::class, 'placeOrder']);
    Route::get('procurement/purchase-orders/bulk-pdf', [PurchaseOrderController::class, 'bulkDownloadPdf']);
    Route::post('procurement/purchase-orders/{order}/invoice', [PurchaseOrderController::class, 'uploadInvoice']);
    Route::get('targets', [TargetController::class, 'index']);
    Route::post('targets', [TargetController::class, 'store']);
    Route::get('targets/export', [TargetController::class, 'export']);
    Route::get('targets/import-template', [TargetController::class, 'importTemplate']);
    Route::post('targets/import', [TargetController::class, 'import']);
    Route::post('targets/recalculate', [TargetController::class, 'recalculate']);
    Route::put('targets/{target}', [TargetController::class, 'update']);
    Route::delete('targets/{target}', [TargetController::class, 'destroy']);
    Route::get('customer-settings/{type}', [CustomerSettingsController::class, 'list']);
    Route::post('customer-settings/{type}', [CustomerSettingsController::class, 'store']);
    Route::put('customer-settings/{type}/{id}', [CustomerSettingsController::class, 'update']);
    Route::patch('customer-settings/{type}/{id}/toggle', [CustomerSettingsController::class, 'toggle']);
    Route::delete('customer-settings/{type}/{id}', [CustomerSettingsController::class, 'destroy']);
    Route::get('files', [FileManagerController::class, 'index'])->middleware('permission:files-view');
    Route::post('files/upload', [FileManagerController::class, 'upload'])->middleware('permission:files-create');
    Route::delete('files', [FileManagerController::class, 'delete'])->middleware('permission:files-delete');
    Route::post('files/rename', [FileManagerController::class, 'rename'])->middleware('permission:files-edit');
    Route::get('files/download', [FileManagerController::class, 'download'])->middleware('permission:files-download');
    Route::post('files/download-zip', [FileManagerController::class, 'downloadZip'])->middleware('permission:files-download');
    Route::post('files/login-background', [FileManagerController::class, 'setLoginBackground'])->middleware('permission:settings-edit');
    Route::post('files/set-default-image', [FileManagerController::class, 'setDefaultImage'])->middleware('permission:settings-edit');

    Route::get('/shipping/settings', [ShippingSettingsController::class, 'index'])->name('api.shipping.settings.index')->middleware('permission:shipping-view');
    Route::post('/shipping/settings', [ShippingSettingsController::class, 'store'])->name('api.shipping.settings.store')->middleware('permission:settings-edit');
    Route::get('/shipping/awb-logs/{officeId}', [ShippingSettingsController::class, 'awbLogs'])->name('api.shipping.awb-logs')->middleware('permission:shipping-view');
    Route::get('/shipping/offices/{officeId}/ranges', [ShippingSettingsController::class, 'getRanges'])->name('api.shipping.ranges.index')->middleware('permission:shipping-view');
    Route::post('/shipping/offices/{officeId}/ranges', [ShippingSettingsController::class, 'storeRange'])->name('api.shipping.ranges.store')->middleware('permission:settings-edit');
    Route::post('/shipping/ranges/{rangeId}/activate', [ShippingSettingsController::class, 'activateRange'])->name('api.shipping.ranges.activate')->middleware('permission:settings-edit');
    Route::put('/shipping/ranges/{rangeId}', [ShippingSettingsController::class, 'updateRange'])->name('api.shipping.ranges.update')->middleware('permission:settings-edit');
    Route::delete('/shipping/ranges/{rangeId}', [ShippingSettingsController::class, 'deleteRange'])->name('api.shipping.ranges.destroy')->middleware('permission:settings-edit');

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

        Route::apiResource('offices', \App\Modules\Users\Controllers\OfficeController::class)->names([
            'index' => 'api.offices.index', 'store' => 'api.offices.store', 'show' => 'api.offices.show',
            'update' => 'api.offices.update', 'destroy' => 'api.offices.destroy',
        ]);

        Route::apiResource('teams', TeamController::class)
            ->names([
                'index' => 'api.teams.index',
                'store' => 'api.teams.store',
                'show' => 'api.teams.show',
                'update' => 'api.teams.update',
                'destroy' => 'api.teams.destroy',
            ]);
    });

    // ── User Management ──────────────────────────────────────────────────────
    Route::group([], function (): void {
        Route::patch('users/{user}/restore', [UserController::class, 'restore'])
            ->name('api.users.restore');

        Route::delete('users/{user}/force', [UserController::class, 'forceDelete'])
            ->name('api.users.force-delete');

        Route::get('users/options', [UserController::class, 'options'])->name('api.users.options');

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

        Route::patch('users/{user}/unsuspend', [UserController::class, 'unsuspend'])
            ->name('api.users.unsuspend');

        Route::post('users/{user}/update-photo', [UserController::class, 'updatePhoto'])
            ->name('api.users.update-photo');

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

        // Security / Impersonation Routes
        Route::get('/security/mail', [SecurityMailController::class, 'show'])->middleware('permission:security-mail-view');
        Route::post('/security/mail', [SecurityMailController::class, 'update'])->middleware('permission:security-mail-edit');
        Route::post('/security/mail/test', [SecurityMailController::class, 'test'])->middleware('permission:security-mail-test');
        Route::get('/security/two-factor', [TwoFactorController::class, 'status']);
        Route::post('/security/two-factor/email', [TwoFactorController::class, 'email']);
        Route::post('/security/two-factor/setup', [TwoFactorController::class, 'setup']);
        Route::post('/security/two-factor', [TwoFactorController::class, 'update']);
        Route::get('/security/sessions', [SecurityController::class, 'sessions']);
        Route::get('/security/activity', [SecurityController::class, 'activity']);
        Route::delete('/security/sessions/{id}', [SecurityController::class, 'terminateSession']);
        Route::post('/security/sessions/terminate-other', [SecurityController::class, 'terminateOtherSessions']);
        Route::post('/security/emergency-lockdown', [SecurityController::class, 'emergencyLockdown']);
        Route::post('/roles/{role}/clone', [SecurityController::class, 'cloneRole']);

        // Org Chart
        Route::middleware('permission:department-view')->get('/org-chart', [OrgChartController::class, 'index']);

        Route::middleware('permission:hrsetting-view')->get('/hr-settings/{type}', [HrSettingController::class, 'list']);
        Route::middleware('permission:hrsetting-create')->post('/hr-settings/{type}', [HrSettingController::class, 'store']);
        Route::middleware('permission:hrsetting-edit')->put('/hr-settings/{type}/{id}', [HrSettingController::class, 'update']);
        Route::middleware('permission:hrsetting-edit')->patch('/hr-settings/{type}/{id}/toggle', [HrSettingController::class, 'toggleActive']);
        Route::middleware('permission:hrsetting-delete')->delete('/hr-settings/{type}/{id}', [HrSettingController::class, 'destroy']);

        // Attendance corrections use the existing view/edit permissions and reporting hierarchy.
        Route::get('/attendance-corrections', [AttendanceCorrectionController::class, 'index']);
        Route::post('/attendance-corrections', [AttendanceCorrectionController::class, 'store']);
        Route::patch('/attendance-corrections/{correction}/review', [AttendanceCorrectionController::class, 'review']);

        // Attendance
        Route::get('/attendances/export/filtered', [AttendanceController::class, 'exportFiltered']);
        Route::get('/attendances/export/summary', [AttendanceController::class, 'exportSummary']);
        Route::get('/attendances/export/detailed', [AttendanceController::class, 'exportDetailed']);
        Route::post('/attendances/bulk-action', [AttendanceController::class, 'bulkAction']);
        Route::patch('/attendances/{attendance}/restore', [AttendanceController::class, 'restore']);
        Route::delete('/attendances/{attendance}/force', [AttendanceController::class, 'forceDelete']);
        Route::apiResource('attendances', AttendanceController::class);

        Route::get('/rosters', [RosterController::class, 'index']);
        Route::post('/rosters/import', [RosterController::class, 'import']);
        Route::get('/rosters/co', [RosterController::class, 'claims']);
        Route::post('/rosters/co', [RosterController::class, 'claim']);
        Route::patch('/rosters/co/{id}/review', [RosterController::class, 'review']);

        // Leaves
        Route::middleware('permission:leave-export')->get('/leaves/export', [LeaveController::class, 'export']);
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
        Route::post('/preview', [OrderController::class, 'preview'])->name('preview');
        Route::post('/', [OrderController::class, 'store'])->name('store');
        Route::get('/bulk-print', [OrderController::class, 'bulkPrint'])->name('bulk-print');
        Route::get('/export', [OrderController::class, 'bulkExport'])->name('export');
        Route::get('/{order}', [OrderController::class, 'show'])->name('show');
        Route::patch('/{order}', [OrderController::class, 'update'])->name('update');
        Route::delete('/{order}', [OrderController::class, 'destroy'])->name('destroy');

        // Order Actions
        Route::post('/{order}/confirm', [OrderController::class, 'confirm'])->name('confirm');
        Route::post('/{order}/ship', [OrderController::class, 'ship'])->name('ship');
        Route::post('/{order}/dispatch', [OrderController::class, 'dispatch'])->name('dispatch');
        Route::post('/{order}/processing', [OrderController::class, 'markProcessing'])->name('processing');
        Route::post('/{order}/deliver', [OrderController::class, 'markDelivered'])->name('deliver');
        Route::post('/{order}/cancel', [OrderController::class, 'cancel'])->name('cancel');
        Route::post('/{order}/return', [OrderController::class, 'markReturned'])->name('return');
        Route::post('/{order}/revert-status', [OrderController::class, 'revertStatus'])->name('revert-status');

        // Bulk Actions & Helpers
        Route::post('/bulk-status', [OrderController::class, 'bulkStatus'])->name('bulk-status');
        Route::post('/bulk-return', [OrderReturnController::class, 'bulkStore'])->name('bulk-return');
        Route::post('/bulk-generate-invoices', [OrderController::class, 'generateBulkInvoices'])->name('bulk-generate-invoices');
        Route::post('/export-selected', [OrderController::class, 'exportSelected'])->name('export-selected');
        Route::post('/import', [OrderController::class, 'bulkImport'])->name('import');
    });

    Route::prefix('complaints')->name('api.complaints.')->group(function (): void {
        Route::get('/stats', [OrderComplaintController::class, 'stats'])->name('stats');
        Route::get('/', [OrderComplaintController::class, 'index'])->name('index');
        Route::get('/export', [OrderComplaintController::class, 'bulkExport'])->name('export');
        Route::get('/{complaint}', [OrderComplaintController::class, 'show'])->name('show');
        Route::post('/', [OrderComplaintController::class, 'store'])->name('store');
        Route::put('/{complaint}', [OrderComplaintController::class, 'update'])->name('update');
        Route::post('/{complaint}/reply', [OrderComplaintController::class, 'reply'])->name('reply');
        Route::delete('/{complaint}', [OrderComplaintController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/restore', [OrderComplaintController::class, 'restore'])->name('restore');
        Route::delete('/{id}/force-delete', [OrderComplaintController::class, 'forceDelete'])->name('force-delete');
        Route::post('/bulk-action', [OrderComplaintController::class, 'bulkAction'])->name('bulk-action');
        Route::post('/export-selected', [OrderComplaintController::class, 'exportSelected'])->name('export-selected');
    });

    Route::get('/products-search-api', [ProductController::class, 'searchApi'])->name('api.products.search.api')->middleware('permission:orders.create|orders.edit');
    Route::post('/coupons/validate', [CouponController::class, 'validateApi'])->name('api.coupons.validate')->middleware('permission:orders.create|orders.edit');

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
        Route::post('/{invoice}/payments', [InvoiceController::class, 'recordPayment'])->name('payments.store');
        Route::post('/export-selected', [InvoiceController::class, 'exportSelected'])->name('export-selected');
        Route::get('/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('pdf');
    });

    Route::prefix('payments')->name('api.payments.')->group(function (): void {
        Route::get('/', [PaymentController::class, 'index'])->name('index');
        Route::post('/bulk-status', [PaymentController::class, 'bulkStatus'])->name('bulk-status');
        Route::post('/', [PaymentController::class, 'store'])->name('store');
        Route::get('/{payment}', [PaymentController::class, 'show'])->name('show')->withTrashed();
        Route::patch('/{payment}', [PaymentController::class, 'update'])->name('update');
        Route::delete('/{payment}', [PaymentController::class, 'destroy'])->name('destroy');
        Route::post('/export-selected', [PaymentController::class, 'exportSelected'])->name('export-selected');
    });

    Route::prefix('refunds')->name('api.refunds.')->group(function (): void {
        Route::get('/', [RefundController::class, 'index'])->name('index');
        Route::post('/bulk-status', [RefundController::class, 'bulkStatus'])->name('bulk-status');
        Route::get('/{refund}', [RefundController::class, 'show'])->name('show');
    });

    Route::prefix('returns')->name('api.returns.')->group(function (): void {
        Route::get('/', [OrderReturnController::class, 'index'])->name('index');
        Route::post('/', [OrderReturnController::class, 'storeApi'])->name('store');
        Route::get('/{return}', [OrderReturnController::class, 'show'])->name('show');
        Route::post('/{return}/process', [OrderReturnController::class, 'process'])->name('process');
        Route::post('/{return}/approve', [OrderReturnController::class, 'approve'])->name('approve');
        Route::post('/{return}/cancel', [OrderReturnController::class, 'cancel'])->name('cancel');
        Route::post('/{return}/qc', [OrderReturnController::class, 'processQc'])->name('qc');
        Route::post('/{return}/finance', [OrderReturnController::class, 'processFinancials'])->name('finance');
        Route::post('/bulk-qc-import', [OrderReturnController::class, 'importBulkQc'])->name('bulk-qc-import');
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
        Route::apiResource('/transfers', StockTransferController::class)->parameters(['transfers' => 'stockTransfer']);

        Route::get('/adjustments/options', [InventoryAdjustmentController::class, 'options'])->name('adjustments.options');
        Route::post('/adjustments/bulk-action', [InventoryAdjustmentController::class, 'bulkAction'])->name('adjustments.bulk-action');
        Route::post('/adjustments/{inventoryAdjustment}/approve', [InventoryAdjustmentController::class, 'approve'])->name('adjustments.approve');
        Route::post('/adjustments/{inventoryAdjustment}/reject', [InventoryAdjustmentController::class, 'reject'])->name('adjustments.reject');
        Route::apiResource('/adjustments', InventoryAdjustmentController::class)->parameters([
            'adjustments' => 'inventoryAdjustment',
        ]);
    });

    Route::prefix('products')->name('api.products.')->group(function (): void {
        Route::get('/', [CatalogProductController::class, 'index'])->name('index');
        Route::get('/export', [CatalogProductController::class, 'export'])->name('export');
        Route::post('/import', [CatalogProductController::class, 'import'])->name('import');
        Route::post('/bulk-status', [CatalogProductController::class, 'bulkStatus'])->name('bulk-status');
        Route::post('/bulk-disable-sku', [CatalogProductController::class, 'bulkDisableSku'])->name('bulk-disable-sku');
        Route::post('/bulk-enable-sku', [CatalogProductController::class, 'bulkEnableSku'])->name('bulk-enable-sku');
        Route::post('/bulk-delete', [CatalogProductController::class, 'bulkDelete'])->name('bulk-delete');
        Route::post('/bulk-restore', [CatalogProductController::class, 'bulkRestore'])->name('bulk-restore');
        Route::post('/bulk-force-delete', [CatalogProductController::class, 'bulkForceDelete'])->name('bulk-force-delete');
        Route::post('/{product}/duplicate', [CatalogProductController::class, 'duplicate'])->name('duplicate');
        Route::post('/{product}/restore', [CatalogProductController::class, 'restore'])->name('restore');
        Route::delete('/{product}/force-delete', [CatalogProductController::class, 'forceDelete'])->name('force-delete');
        Route::get('/{product}', [CatalogProductController::class, 'show'])->name('show');
        Route::patch('/{product}', [CatalogProductController::class, 'update'])->name('update');
        Route::delete('/{product}', [CatalogProductController::class, 'destroy'])->name('destroy');
        Route::post('/', [CatalogProductController::class, 'store'])->name('store');
    });

    // Bulk action must be defined before the resource to avoid route conflict
    Route::post('/users/bulk-action', BulkUserController::class)->middleware(\App\Services\UserDirectoryAccess::class)->name('api.users.bulk');

    // Customers API Routes
    Route::get('/customers/check-referral/{code}', function ($code) {
        $party = Party::where('referral_code', $code)->first(['id', 'firstname', 'lastname']);

        return response()->json([
            'valid' => (bool) $party,
            'name' => $party ? trim("{$party->firstname} {$party->lastname}") : null,
        ]);
    })->middleware('permission:customer-create|customer-edit')->name('api.customers.check-referral');

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
    Route::post('/villages/sync-indiapost', [VillageController::class, 'syncIndiaPostPincodes'])
        ->withoutMiddleware([ThrottleRequests::class.':api', 'throttle:api'])
        ->name('api.villages.sync-indiapost');
    Route::post('/villages/bulk-action', [VillageController::class, 'bulkAction'])->name('api.villages.bulk');
    Route::post('/villages/service-selection', [VillageController::class, 'serviceSelection'])->name('api.villages.service-selection');
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
        Route::patch('/shipments/{shipment}', [ShippingController::class, 'updateShipment'])->name('shipments.update');
        Route::post('/shipments/{shipment}/return', [ShippingController::class, 'returnShipment'])->name('shipments.return');
        Route::post('/shipments/{shipment}/status', [ShippingController::class, 'updateShipmentStatus'])->name('shipments.status');
        Route::get('/shipments/{shipment}/tracking', [ShippingController::class, 'trackingEvents'])->name('shipments.tracking');
        Route::post('/shipments/{shipment}/tracking-event', [ShippingController::class, 'addTrackingEvent'])->name('shipments.add-tracking-event');
        Route::get('/shipments/{shipment}/live-tracking', [ShippingController::class, 'fetchLiveTracking'])->name('shipments.live-tracking');

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
        Route::get('/purchase-orders/{order}/payments', [PurchaseOrderController::class, 'getPayments'])->name('purchase-orders.payments.index');
        Route::post('/purchase-orders/{order}/payments', [PurchaseOrderController::class, 'addPayment'])->name('purchase-orders.payments.store');

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
    Route::post('/call-tags-admin/bulk-action', [CallTagAdminController::class, 'bulkAction']); // must be before {callTag} wildcard
    Route::put('/call-tags-admin/{callTag}', [CallTagAdminController::class, 'update']);
    Route::delete('/call-tags-admin/{callTag}', [CallTagAdminController::class, 'destroy']);

    // ── System Audit Logs ───────────────────────────────────────────────────
    Route::get('/admin/audit-logs', [AuditLogController::class, 'index'])->name('api.audit-logs.index');
    Route::delete('/admin/audit-logs/clear', [AuditLogController::class, 'clearAll'])->name('api.audit-logs.clear');
    Route::delete('/admin/audit-logs/destroy', [AuditLogController::class, 'destroy'])->name('api.audit-logs.destroy');

});
