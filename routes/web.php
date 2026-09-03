<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Chat\ConversationController;
use App\Http\Controllers\Api\Chat\GroupController;
use App\Http\Controllers\Api\Chat\MessageController;
use App\Http\Controllers\Api\Chat\PresenceController;
use App\Http\Controllers\Api\Chat\SearchController;
use App\Http\Controllers\Api\Chat\UserController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\ReferralProgramController;
use App\Http\Controllers\Web\Chat\ChatController;
use App\Modules\Catalog\Controllers\CatalogController;
use App\Modules\Catalog\Controllers\ProductController;
use App\Modules\Core\Controllers\FileManagerController;
use App\Modules\Core\Controllers\PageController;
use App\Modules\Core\Controllers\ShippingSettingsController;
use App\Modules\Customers\Controllers\CustomerController;
use App\Modules\Customers\Controllers\CustomerSettingsController;
use App\Modules\Inventory\Controllers\GoodsReceiptController;
use App\Modules\Inventory\Controllers\PurchaseOrderController;
use App\Modules\Inventory\Controllers\SupplierController;
use App\Modules\Inventory\Controllers\WarehouseDashboardController;
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
use App\Modules\Orders\Controllers\PaymentImportController;
use App\Modules\Orders\Controllers\PromotionsController;
use App\Modules\Orders\Controllers\RefundController;
use App\Modules\Users\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// ─── Public Auth Routes ───────────────────────────────────────────────────────

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [PageController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

Route::middleware('auth')->group(function (): void {
    Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])->name('logout');
});

// ─── Authenticated Dashboard Routes ──────────────────────────────────────────

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/', [PageController::class, 'dashboard'])->name('dashboard');
    Route::get('/analytics', [PageController::class, 'analytics'])->name('analytics');
    Route::get('/analytics/data', [PageController::class, 'analyticsData'])->name('analytics.data');
    Route::get('/users', [PageController::class, 'users'])->name('users');
    Route::get('/roles-permissions', [PageController::class, 'rolesPermissions'])->name('roles-permissions');
    Route::get('/teams', [PageController::class, 'teams'])->name('teams');

    // HR Module
    Route::get('/departments', [PageController::class, 'departments'])->name('departments')->middleware('permission:department-view');
    Route::get('/attendances', [PageController::class, 'attendances'])->name('attendances')->middleware('permission:attendance-view');
    Route::get('/leaves', [PageController::class, 'leaves'])->name('leaves')->middleware('permission:leave-view');

    // Call Tags Admin CRUD
    Route::get('/call-tags-admin', [CallTagAdminController::class, 'index'])->name('call-tags.index')->middleware('permission:settings-view');
    Route::post('/call-tags-admin', [CallTagAdminController::class, 'store'])->middleware('permission:settings-view');
    Route::put('/call-tags-admin/{callTag}', [CallTagAdminController::class, 'update'])->middleware('permission:settings-view');
    Route::delete('/call-tags-admin/{callTag}', [CallTagAdminController::class, 'destroy'])->middleware('permission:settings-view');
    Route::post('/call-tags-admin/bulk-action', [CallTagAdminController::class, 'bulkAction'])->middleware('permission:settings-view');

    Route::prefix('catalog')->name('catalog.')->group(function (): void {
        Route::get('/products', [CatalogController::class, 'products'])->name('products');
        Route::get('/brands', [CatalogController::class, 'brands'])->name('brands');
        Route::get('/categories', [CatalogController::class, 'categories'])->name('categories');
        Route::get('/uom', [CatalogController::class, 'uom'])->name('uom');
        Route::get('/tax-rates', [CatalogController::class, 'taxRates'])->name('tax-rates');
        Route::get('/hsn-codes', [CatalogController::class, 'hsnCodes'])->name('hsn-codes');
        Route::get('/warehouses', [CatalogController::class, 'warehouses'])->name('warehouses');
        Route::get('/attributes', [CatalogController::class, 'attributes'])->name('attributes');
    });
    Route::prefix('inventory')->name('inventory.')->group(function (): void {
        Route::get('/dashboard', [WarehouseDashboardController::class, 'index'])->name('dashboard')->middleware('permission:warehouse-dashboard-view');
        Route::get('/dashboard/activities', [WarehouseDashboardController::class, 'activities'])->name('dashboard.activities')->middleware('permission:warehouse-dashboard-view');
        Route::get('/stock-management', [PageController::class, 'stockManagement'])->name('stock-management')->middleware('permission:stockmanagement-view');
        Route::get('/stock-transfers', [PageController::class, 'stockTransfers'])->name('stock-transfers')->middleware('permission:stocktransfer-view');
        Route::get('/adjustments', [PageController::class, 'inventoryAdjustments'])->name('adjustments')->middleware('permission:inventoryadjustment-view');
    });
    Route::prefix('procurement')->name('procurement.')->group(function (): void {
        Route::get('/purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
        Route::get('/purchase-orders/bulk-pdf', [PurchaseOrderController::class, 'bulkDownloadPdf'])->name('purchase-orders.bulk-pdf');
        Route::get('/purchase-orders/{order}/pdf', [PurchaseOrderController::class, 'downloadPdf'])->name('purchase-orders.pdf');
        Route::post('/purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
        Route::post('/purchase-orders/bulk', [PurchaseOrderController::class, 'bulkAction'])->name('purchase-orders.bulk');
        Route::delete('/purchase-orders/{order}', [PurchaseOrderController::class, 'destroy'])->name('purchase-orders.destroy');
        Route::post('/purchase-orders/{id}/restore', [PurchaseOrderController::class, 'restore'])->name('purchase-orders.restore');
        Route::post('/purchase-orders/{order}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
        Route::post('/purchase-orders/{order}/reject', [PurchaseOrderController::class, 'reject'])->name('purchase-orders.reject');
        Route::post('/purchase-orders/{order}/receive', [GoodsReceiptController::class, 'store'])->name('purchase-orders.receive');
        Route::post('/purchase-orders/{order}/invoice', [PurchaseOrderController::class, 'uploadInvoice'])->name('purchase-orders.invoice');

        Route::get('/goods-receipts', [GoodsReceiptController::class, 'index'])->name('goods-receipts.index');
        Route::get('/goods-receipts/{receipt}/pdf', [GoodsReceiptController::class, 'downloadPdf'])->name('goods-receipts.pdf');
        Route::post('/goods-receipts/bulk', [GoodsReceiptController::class, 'bulkAction'])->name('goods-receipts.bulk');
        Route::delete('/goods-receipts/{receipt}', [GoodsReceiptController::class, 'destroy'])->name('goods-receipts.destroy');
        Route::post('/goods-receipts/{id}/restore', [GoodsReceiptController::class, 'restore'])->name('goods-receipts.restore');

        Route::post('/suppliers/bulk', [SupplierController::class, 'bulkAction'])->name('suppliers.bulk');
        Route::resource('/suppliers', SupplierController::class)->except(['create', 'show', 'edit']);
    });
    Route::post('orders/bulk-status', [OrderController::class, 'bulkStatus'])->name('orders.bulk-status');
    Route::post('orders/bulk-generate-invoices', [OrderController::class, 'generateBulkInvoices'])->name('orders.bulk-generate-invoices');
    Route::get('orders/bulk-print', [OrderController::class, 'bulkPrint'])->name('orders.bulk-print');
    Route::get('orders/export', [OrderController::class, 'bulkExport'])->name('orders.export');
    Route::post('orders/export-selected', [OrderController::class, 'exportSelected'])->name('orders.export-selected');
    Route::post('orders/import', [OrderController::class, 'bulkImport'])->name('orders.import');
    Route::get('orders/import-template', [OrderController::class, 'bulkImportTemplate'])->name('orders.import-template');
    Route::get('orders/{order}/invoice-pdf', [OrderController::class, 'downloadInvoice'])->name('orders.invoice-pdf');
    Route::post('orders/{order}/generate-invoice', [OrderController::class, 'generateInvoice'])->name('orders.generate-invoice');
    Route::get('orders/{order}/cod-pdf', [OrderController::class, 'downloadReceipt'])->name('orders.cod-pdf');
    Route::post('orders/{order}/confirm', [OrderController::class, 'confirm'])->name('orders.confirm');
    Route::post('orders/{order}/ship', [OrderController::class, 'ship'])->name('orders.ship');
    Route::post('orders/{order}/dispatch', [OrderController::class, 'dispatch'])->name('orders.dispatch');
    Route::post('orders/{order}/processing', [OrderController::class, 'markProcessing'])->name('orders.processing');
    Route::post('orders/{order}/deliver', [OrderController::class, 'markDelivered'])->name('orders.deliver');
    Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('orders/{order}/return', [OrderController::class, 'markReturned'])->name('orders.return');
    Route::post('orders/{order}/revert-status', [OrderController::class, 'revertStatus'])->name('orders.revert-status');
    Route::get('orders/{order}/receipt', [OrderController::class, 'receipt'])->name('orders.receipt');
    Route::get('/order-reasons', [OrderReasonController::class, 'index'])->name('order.reasons');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders');
    Route::resource('orders', OrderController::class)->except(['index']);

    // Order Returns
    Route::get('returns', [OrderReturnController::class, 'index'])->name('returns.index');
    Route::post('orders/{order}/returns', [OrderReturnController::class, 'store'])->name('orders.returns.store');

    // Order Complaints
    Route::get('complaints', [OrderComplaintController::class, 'index'])->name('complaints.index');

    Route::get('returns/{return}', [OrderReturnController::class, 'show'])->name('returns.show');
    Route::post('returns/{return}/qc', [OrderReturnController::class, 'processQc'])->name('returns.qc');
    Route::post('returns/{return}/finance', [OrderReturnController::class, 'processFinancials'])->name('returns.finance');

    // Billing & Financials
    Route::get('credit-notes', [CreditNoteController::class, 'index'])->name('credit-notes.index');
    Route::get('refunds', [RefundController::class, 'index'])->name('refunds.index');
    Route::post('refunds/bulk-status', [RefundController::class, 'bulkStatus'])->name('refunds.bulk-status');
    Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('payments/{payment}', [PaymentController::class, 'show'])->name('payments.show')->withTrashed();
    Route::post('payments/bulk-status', [PaymentController::class, 'bulkStatus'])->name('payments.bulk-status');
    Route::post('payments/export', [PaymentController::class, 'exportSelected'])->name('payments.export.selected');
    Route::put('payments/{payment}', [PaymentController::class, 'update'])->name('payments.update');
    Route::delete('payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');

    // Import Routes
    Route::get('payments/import/sample', [PaymentImportController::class, 'downloadSample'])->name('payments.import.sample');
    Route::post('payments/import/preview', [PaymentImportController::class, 'preview'])->name('payments.import.preview');
    Route::post('payments/import/process', [PaymentImportController::class, 'process'])->name('payments.import.process');

    Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::post('invoices/bulk-status', [InvoiceController::class, 'bulkStatus'])->name('invoices.bulk-status');
    Route::post('invoices/export', [InvoiceController::class, 'exportSelected'])->name('invoices.export.selected');
    Route::post('invoices/{invoice}/payments', [InvoiceController::class, 'recordPayment'])->name('invoices.payments.store');
    Route::get('/customers', [PageController::class, 'customers'])->name('customers');

    // Customer Settings Routes
    Route::get('/customer-settings', [CustomerSettingsController::class, 'index'])->name('customer-settings.index');
    Route::get('/api/customer-settings/{type}', [CustomerSettingsController::class, 'list']);
    Route::post('/api/customer-settings/{type}', [CustomerSettingsController::class, 'store']);
    Route::put('/api/customer-settings/{type}/{id}', [CustomerSettingsController::class, 'update']);
    Route::patch('/api/customer-settings/{type}/{id}/toggle', [CustomerSettingsController::class, 'toggle']);
    Route::delete('/api/customer-settings/{type}/{id}', [CustomerSettingsController::class, 'destroy']);

    Route::get('/customers/search-by-phone', [CustomerController::class, 'searchByPhone'])->name('customers.search-by-phone');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::post('/customers/{customer}/orders/place', [CustomerController::class, 'placeOrder'])->name('customers.orders.place');
    Route::get('/villages', [PageController::class, 'villages'])->name('villages');
    Route::get('/shipping/shipments', [PageController::class, 'shipments'])->name('shipping.shipments')->middleware('permission:shipping-view');
    Route::get('/shipping/services', [PageController::class, 'shippingServices'])->name('shipping.services')->middleware('permission:shipping-view');
    Route::get('/shipping/settings', [ShippingSettingsController::class, 'index'])->name('shipping.settings')->middleware('permission:shipping-view');

    Route::get('/reports', [PageController::class, 'reports'])->name('reports');
    Route::get('/reports/export', [PageController::class, 'exportReports'])->name('reports.export');
    Route::get('/messages', [PageController::class, 'messages'])->name('messages');
    Route::get('/calendar', [PageController::class, 'calendar'])->name('calendar');
    Route::get('/files', [PageController::class, 'files'])->name('files')->middleware('permission:settings-view');
    Route::prefix('api/files')->middleware('permission:settings-view')->group(function () {
        Route::get('/', [FileManagerController::class, 'index']);
        Route::post('/upload', [FileManagerController::class, 'upload']);
        Route::delete('/', [FileManagerController::class, 'delete']);
        Route::post('/rename', [FileManagerController::class, 'rename']);
        Route::post('/download-zip', [FileManagerController::class, 'downloadZip']);
        Route::post('/login-background', [FileManagerController::class, 'setLoginBackground']);
        Route::post('/set-default-image', [FileManagerController::class, 'setDefaultImage']);
    });
    Route::get('/forms', [PageController::class, 'forms'])->name('forms');
    Route::get('/security', [PageController::class, 'security'])->name('security');
    Route::get('/help', [PageController::class, 'help'])->name('help');

    // ─── Chat ────────────────────────────────────────────────────────────────
    Route::get('/chat', ChatController::class)->name('chat.index');

    Route::prefix('api/chat')->middleware('throttle:chat')->group(function () {
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
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/search', SearchController::class);
    });

    // ─── Promotions ──────────────────────────────────────────────────────────
    Route::get('/promotions/coupons', [PromotionsController::class, 'coupons'])->name('promotions.coupons');
    Route::get('/promotions/offers', [PromotionsController::class, 'offers'])->name('promotions.offers');

    // Referral Programs
    Route::get('/promotions/referral-programs', [ReferralProgramController::class, 'index'])->name('referrals.programs.index');
    Route::post('/promotions/referral-programs', [ReferralProgramController::class, 'store'])->name('referrals.programs.store');
    Route::post('/promotions/referral-programs/bulk-action', [ReferralProgramController::class, 'bulk'])->name('referrals.programs.bulk');
    Route::put('/promotions/referral-programs/{id}', [ReferralProgramController::class, 'update'])->name('referrals.programs.update');
    Route::patch('/promotions/referral-programs/{id}/toggle', [ReferralProgramController::class, 'toggle'])->name('referrals.programs.toggle');
    Route::delete('/promotions/referral-programs/{id}', [ReferralProgramController::class, 'destroy'])->name('referrals.programs.destroy');

    // Elements sub-section
    Route::prefix('elements')->group(function (): void {
        Route::get('/', [PageController::class, 'elementsOverview'])->name('elements');
        Route::get('/alerts', [PageController::class, 'elementsAlerts'])->name('elements-alerts');
        Route::get('/badges', [PageController::class, 'elementsBadges'])->name('elements-badges');
        Route::get('/buttons', [PageController::class, 'elementsButtons'])->name('elements-buttons');
        Route::get('/cards', [PageController::class, 'elementsCards'])->name('elements-cards');
        Route::get('/forms', [PageController::class, 'elementsForms'])->name('elements-forms');
        Route::get('/modals', [PageController::class, 'elementsModals'])->name('elements-modals');
        Route::get('/tables', [PageController::class, 'elementsTables'])->name('elements-tables');
    });

    // ─── Order Creation Helper Endpoints ─────────────────────────────────────
    Route::get('/products-search-api', [ProductController::class, 'searchApi'])
        ->name('products.search.api')
        ->middleware('permission:orders.create');
    Route::post('/coupons/validate', [CouponController::class, 'validateApi'])
        ->name('coupons.validate')
        ->middleware('permission:orders.create');

    // Call Tagging Ajax Routes
    Route::get('/call-tags', [CallTaggingController::class, 'getTags']);
    Route::get('/call-tags/{tag}/form', [CallTaggingController::class, 'getFormFields']);
    Route::post('/call-logs', [CallTaggingController::class, 'storeCallLog']);

    // System Audit Logs
    Route::get('/admin/audit-logs', [AuditLogController::class, 'index'])->name('admin.audit-logs.index')->middleware('role:Super Admin');
    Route::delete('/admin/audit-logs/clear', [AuditLogController::class, 'clearAll'])->name('admin.audit-logs.clear')->middleware('role:Super Admin');
    Route::delete('/admin/audit-logs/destroy', [AuditLogController::class, 'destroy'])->name('admin.audit-logs.destroy')->middleware('role:Super Admin');
});
