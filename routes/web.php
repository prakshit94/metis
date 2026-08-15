<?php

declare(strict_types=1);

use App\Modules\Users\Controllers\AuthController;
use App\Modules\Core\Controllers\PageController;
use App\Modules\Users\Controllers\PermissionController;
use App\Modules\Users\Controllers\RoleController;
use App\Modules\Catalog\Controllers\ProductController as CatalogProductController;
use Illuminate\Support\Facades\Route;

// ─── Public Auth Routes ───────────────────────────────────────────────────────

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [PageController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

// ─── Authenticated Dashboard Routes ──────────────────────────────────────────

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/', [PageController::class, 'dashboard'])->name('dashboard');
    Route::get('/analytics', [PageController::class, 'analytics'])->name('analytics');
    Route::get('/users', [PageController::class, 'users'])->name('users');
    Route::get('/roles-permissions', [PageController::class, 'rolesPermissions'])->name('roles-permissions');
    
    // HR Module
    Route::get('/departments', [PageController::class, 'departments'])->name('departments')->middleware('permission:department-view');
    Route::get('/attendances', [PageController::class, 'attendances'])->name('attendances')->middleware('permission:attendance-view');
    Route::get('/leaves', [PageController::class, 'leaves'])->name('leaves')->middleware('permission:leave-view');
    
    // Call Tags Admin CRUD
    Route::get('/call-tags-admin', [\App\Modules\Orders\Controllers\CallTagAdminController::class, 'index'])->name('call-tags.index')->middleware('permission:settings-view');
    Route::post('/call-tags-admin', [\App\Modules\Orders\Controllers\CallTagAdminController::class, 'store'])->middleware('permission:settings-view');
    Route::put('/call-tags-admin/{callTag}', [\App\Modules\Orders\Controllers\CallTagAdminController::class, 'update'])->middleware('permission:settings-view');
    Route::delete('/call-tags-admin/{callTag}', [\App\Modules\Orders\Controllers\CallTagAdminController::class, 'destroy'])->middleware('permission:settings-view');
    Route::post('/call-tags-admin/bulk-action', [\App\Modules\Orders\Controllers\CallTagAdminController::class, 'bulkAction'])->middleware('permission:settings-view');
    
    Route::prefix('catalog')->name('catalog.')->group(function (): void {
        Route::get('/products', [\App\Modules\Catalog\Controllers\CatalogController::class, 'products'])->name('products');
        Route::get('/brands', [\App\Modules\Catalog\Controllers\CatalogController::class, 'brands'])->name('brands');
        Route::get('/categories', [\App\Modules\Catalog\Controllers\CatalogController::class, 'categories'])->name('categories');
        Route::get('/uom', [\App\Modules\Catalog\Controllers\CatalogController::class, 'uom'])->name('uom');
        Route::get('/tax-rates', [\App\Modules\Catalog\Controllers\CatalogController::class, 'taxRates'])->name('tax-rates');
        Route::get('/hsn-codes', [\App\Modules\Catalog\Controllers\CatalogController::class, 'hsnCodes'])->name('hsn-codes');
        Route::get('/warehouses', [\App\Modules\Catalog\Controllers\CatalogController::class, 'warehouses'])->name('warehouses');
        Route::get('/attributes', [\App\Modules\Catalog\Controllers\CatalogController::class, 'attributes'])->name('attributes');
    });
    Route::prefix('inventory')->name('inventory.')->group(function (): void {
        Route::get('/dashboard', [\App\Modules\Inventory\Controllers\WarehouseDashboardController::class, 'index'])->name('dashboard')->middleware('permission:warehouse-dashboard-view');
        Route::get('/dashboard/activities', [\App\Modules\Inventory\Controllers\WarehouseDashboardController::class, 'activities'])->name('dashboard.activities')->middleware('permission:warehouse-dashboard-view');
        Route::get('/stock-management', [PageController::class, 'stockManagement'])->name('stock-management')->middleware('permission:stockmanagement-view');
        Route::get('/stock-transfers', [PageController::class, 'stockTransfers'])->name('stock-transfers')->middleware('permission:stocktransfer-view');
        Route::get('/adjustments', [PageController::class, 'inventoryAdjustments'])->name('adjustments')->middleware('permission:inventoryadjustment-view');
    });
    Route::prefix('procurement')->name('procurement.')->group(function (): void {
        Route::get('/purchase-orders', [\App\Modules\Inventory\Controllers\PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
        Route::post('/purchase-orders', [\App\Modules\Inventory\Controllers\PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
        Route::post('/purchase-orders/{order}/receive', [\App\Modules\Inventory\Controllers\GoodsReceiptController::class, 'store'])->name('purchase-orders.receive');
        Route::get('/goods-receipts', [\App\Modules\Inventory\Controllers\GoodsReceiptController::class, 'index'])->name('goods-receipts.index');
    });
    Route::post('orders/bulk-status', [\App\Modules\Orders\Controllers\OrderController::class, 'bulkStatus'])->name('orders.bulk-status');
    Route::post('orders/bulk-generate-invoices', [\App\Modules\Orders\Controllers\OrderController::class, 'generateBulkInvoices'])->name('orders.bulk-generate-invoices');
    Route::get('orders/bulk-print', [\App\Modules\Orders\Controllers\OrderController::class, 'bulkPrint'])->name('orders.bulk-print');
    Route::get('orders/export', [\App\Modules\Orders\Controllers\OrderController::class, 'bulkExport'])->name('orders.export');
    Route::post('orders/export-selected', [\App\Modules\Orders\Controllers\OrderController::class, 'exportSelected'])->name('orders.export-selected');
    Route::post('orders/import', [\App\Modules\Orders\Controllers\OrderController::class, 'bulkImport'])->name('orders.import');
    Route::get('orders/import-template', [\App\Modules\Orders\Controllers\OrderController::class, 'bulkImportTemplate'])->name('orders.import-template');
    Route::get('orders/{order}/invoice-pdf', [\App\Modules\Orders\Controllers\OrderController::class, 'downloadInvoice'])->name('orders.invoice-pdf');
    Route::post('orders/{order}/generate-invoice', [\App\Modules\Orders\Controllers\OrderController::class, 'generateInvoice'])->name('orders.generate-invoice');
    Route::get('orders/{order}/cod-pdf', [\App\Modules\Orders\Controllers\OrderController::class, 'downloadReceipt'])->name('orders.cod-pdf');
    Route::post('orders/{order}/confirm', [\App\Modules\Orders\Controllers\OrderController::class, 'confirm'])->name('orders.confirm');
    Route::post('orders/{order}/ship', [\App\Modules\Orders\Controllers\OrderController::class, 'ship'])->name('orders.ship');
    Route::post('orders/{order}/dispatch', [\App\Modules\Orders\Controllers\OrderController::class, 'dispatch'])->name('orders.dispatch');
    Route::post('orders/{order}/processing', [\App\Modules\Orders\Controllers\OrderController::class, 'markProcessing'])->name('orders.processing');
    Route::post('orders/{order}/deliver', [\App\Modules\Orders\Controllers\OrderController::class, 'markDelivered'])->name('orders.deliver');
    Route::post('orders/{order}/cancel', [\App\Modules\Orders\Controllers\OrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('orders/{order}/return', [\App\Modules\Orders\Controllers\OrderController::class, 'markReturned'])->name('orders.return');
    Route::post('orders/{order}/revert-status', [\App\Modules\Orders\Controllers\OrderController::class, 'revertStatus'])->name('orders.revert-status');
    Route::get('orders/{order}/receipt', [\App\Modules\Orders\Controllers\OrderController::class, 'receipt'])->name('orders.receipt');
    Route::get('/order-reasons', [\App\Modules\Orders\Controllers\OrderReasonController::class, 'index'])->name('order.reasons');
    Route::get('/orders', [\App\Modules\Orders\Controllers\OrderController::class, 'index'])->name('orders');
    Route::resource('orders', \App\Modules\Orders\Controllers\OrderController::class)->except(['index']);

    // Order Returns
    Route::get('returns', [\App\Modules\Orders\Controllers\OrderReturnController::class, 'index'])->name('returns.index');
    Route::post('orders/{order}/returns', [\App\Modules\Orders\Controllers\OrderReturnController::class, 'store'])->name('orders.returns.store');
    Route::get('returns/{return}', [\App\Modules\Orders\Controllers\OrderReturnController::class, 'show'])->name('returns.show');
    Route::post('returns/{return}/qc', [\App\Modules\Orders\Controllers\OrderReturnController::class, 'processQc'])->name('returns.qc');
    Route::post('returns/{return}/finance', [\App\Modules\Orders\Controllers\OrderReturnController::class, 'processFinancials'])->name('returns.finance');

    // Billing & Financials
    Route::get('credit-notes', [\App\Modules\Orders\Controllers\CreditNoteController::class, 'index'])->name('credit-notes.index');
    Route::get('refunds', [\App\Modules\Orders\Controllers\RefundController::class, 'index'])->name('refunds.index');
    Route::post('refunds/bulk-status', [\App\Modules\Orders\Controllers\RefundController::class, 'bulkStatus'])->name('refunds.bulk-status');
    Route::get('payments', [\App\Modules\Orders\Controllers\PaymentController::class, 'index'])->name('payments.index');
    Route::get('payments/{payment}', [\App\Modules\Orders\Controllers\PaymentController::class, 'show'])->name('payments.show')->withTrashed();
    Route::post('payments/bulk-status', [\App\Modules\Orders\Controllers\PaymentController::class, 'bulkStatus'])->name('payments.bulk-status');
    Route::post('payments/export', [\App\Modules\Orders\Controllers\PaymentController::class, 'exportSelected'])->name('payments.export.selected');
    Route::put('payments/{payment}', [\App\Modules\Orders\Controllers\PaymentController::class, 'update'])->name('payments.update');
    Route::delete('payments/{payment}', [\App\Modules\Orders\Controllers\PaymentController::class, 'destroy'])->name('payments.destroy');
    
    // Import Routes
    Route::get('payments/import/sample', [\App\Modules\Orders\Controllers\PaymentImportController::class, 'downloadSample'])->name('payments.import.sample');
    Route::post('payments/import/preview', [\App\Modules\Orders\Controllers\PaymentImportController::class, 'preview'])->name('payments.import.preview');
    Route::post('payments/import/process', [\App\Modules\Orders\Controllers\PaymentImportController::class, 'process'])->name('payments.import.process');

    Route::get('invoices', [\App\Modules\Orders\Controllers\InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('invoices/{invoice}', [\App\Modules\Orders\Controllers\InvoiceController::class, 'show'])->name('invoices.show');
    Route::post('invoices/bulk-status', [\App\Modules\Orders\Controllers\InvoiceController::class, 'bulkStatus'])->name('invoices.bulk-status');
    Route::post('invoices/export', [\App\Modules\Orders\Controllers\InvoiceController::class, 'exportSelected'])->name('invoices.export.selected');
    Route::post('invoices/{invoice}/payments', [\App\Modules\Orders\Controllers\InvoiceController::class, 'recordPayment'])->name('invoices.payments.store');
    Route::get('/customers', [PageController::class, 'customers'])->name('customers');
    
    // Customer Settings Routes
    Route::get('/customer-settings', [\App\Modules\Customers\Controllers\CustomerSettingsController::class, 'index'])->name('customer-settings.index');
    Route::get('/api/customer-settings/{type}', [\App\Modules\Customers\Controllers\CustomerSettingsController::class, 'list']);
    Route::post('/api/customer-settings/{type}', [\App\Modules\Customers\Controllers\CustomerSettingsController::class, 'store']);
    Route::put('/api/customer-settings/{type}/{id}', [\App\Modules\Customers\Controllers\CustomerSettingsController::class, 'update']);
    Route::patch('/api/customer-settings/{type}/{id}/toggle', [\App\Modules\Customers\Controllers\CustomerSettingsController::class, 'toggle']);
    Route::delete('/api/customer-settings/{type}/{id}', [\App\Modules\Customers\Controllers\CustomerSettingsController::class, 'destroy']);

    Route::get('/customers/search-by-phone', [\App\Modules\Customers\Controllers\CustomerController::class, 'searchByPhone'])->name('customers.search-by-phone');
    Route::get('/customers/{customer}', [\App\Modules\Customers\Controllers\CustomerController::class, 'show'])->name('customers.show');
    Route::post('/customers/{customer}/orders/place', [\App\Modules\Customers\Controllers\CustomerController::class, 'placeOrder'])->name('customers.orders.place');
    Route::get('/villages', [PageController::class, 'villages'])->name('villages');
    Route::get('/shipping/shipments', [PageController::class, 'shipments'])->name('shipping.shipments')->middleware('permission:shipping-view');
    Route::get('/shipping/services', [PageController::class, 'shippingServices'])->name('shipping.services')->middleware('permission:shipping-view');
    Route::get('/reports', [PageController::class, 'reports'])->name('reports');
    Route::get('/reports/export', [PageController::class, 'exportReports'])->name('reports.export');
    Route::get('/messages', [PageController::class, 'messages'])->name('messages');
    Route::get('/calendar', [PageController::class, 'calendar'])->name('calendar');
    Route::get('/files', [PageController::class, 'files'])->name('files');
    Route::get('/forms', [PageController::class, 'forms'])->name('forms');
    Route::get('/security', [PageController::class, 'security'])->name('security');
    Route::get('/help', [PageController::class, 'help'])->name('help');

    // ─── Chat ────────────────────────────────────────────────────────────────
    Route::get('/chat', \App\Http\Controllers\Web\Chat\ChatController::class)->name('chat.index');
    
    Route::prefix('api/chat')->middleware('throttle:chat')->group(function () {
        Route::get('/conversations', [\App\Http\Controllers\Api\Chat\ConversationController::class, 'index']);
        Route::post('/conversations', [\App\Http\Controllers\Api\Chat\ConversationController::class, 'store']);
        Route::get('/conversations/{conversation}', [\App\Http\Controllers\Api\Chat\ConversationController::class, 'show']);
        Route::post('/conversations/{conversation}/archive', [\App\Http\Controllers\Api\Chat\ConversationController::class, 'archive']);
        Route::post('/conversations/{conversation}/pin', [\App\Http\Controllers\Api\Chat\ConversationController::class, 'pin']);
        Route::get('/conversations/{conversation}/messages', [\App\Http\Controllers\Api\Chat\MessageController::class, 'index']);
        Route::post('/conversations/{conversation}/messages', [\App\Http\Controllers\Api\Chat\MessageController::class, 'store']);
        Route::post('/conversations/{conversation}/read', [\App\Http\Controllers\Api\Chat\MessageController::class, 'markRead']);
        Route::put('/messages/{message}', [\App\Http\Controllers\Api\Chat\MessageController::class, 'update']);
        Route::delete('/messages/{message}', [\App\Http\Controllers\Api\Chat\MessageController::class, 'destroy']);
        Route::post('/messages/{message}/edit', [\App\Http\Controllers\Api\Chat\MessageController::class, 'update']);
        Route::post('/messages/{message}/delete', [\App\Http\Controllers\Api\Chat\MessageController::class, 'destroy']);
        Route::post('/messages/{message}/forward', [\App\Http\Controllers\Api\Chat\MessageController::class, 'forward']);
        Route::put('/groups/{conversation}', [\App\Http\Controllers\Api\Chat\GroupController::class, 'update']);
        Route::delete('/groups/{conversation}', [\App\Http\Controllers\Api\Chat\GroupController::class, 'destroy']);
        Route::post('/groups/{conversation}/members', [\App\Http\Controllers\Api\Chat\GroupController::class, 'addMember']);
        Route::delete('/groups/{conversation}/members', [\App\Http\Controllers\Api\Chat\GroupController::class, 'removeMember']);
        Route::post('/groups/{conversation}/members/remove', [\App\Http\Controllers\Api\Chat\GroupController::class, 'removeMember']);
        Route::put('/groups/{conversation}/members/role', [\App\Http\Controllers\Api\Chat\GroupController::class, 'updateRole']);
        Route::post('/groups/{conversation}/members/role', [\App\Http\Controllers\Api\Chat\GroupController::class, 'updateRole']);
        Route::post('/groups/{conversation}/transfer-owner', [\App\Http\Controllers\Api\Chat\GroupController::class, 'transferOwner']);
        Route::post('/groups/{conversation}/leave', [\App\Http\Controllers\Api\Chat\GroupController::class, 'leave']);
        Route::get('/active-status', [\App\Http\Controllers\Api\Chat\PresenceController::class, 'index']);
        Route::post('/active-status', [\App\Http\Controllers\Api\Chat\PresenceController::class, 'update']);
        Route::get('/users', [\App\Http\Controllers\Api\Chat\UserController::class, 'index']);
        Route::get('/search', \App\Http\Controllers\Api\Chat\SearchController::class);
    });

    // ─── Promotions ──────────────────────────────────────────────────────────
    Route::get('/promotions/coupons', [\App\Modules\Orders\Controllers\PromotionsController::class, 'coupons'])->name('promotions.coupons');
    Route::get('/promotions/offers', [\App\Modules\Orders\Controllers\PromotionsController::class, 'offers'])->name('promotions.offers');
    
    // Referral Programs
    Route::get('/promotions/referral-programs', [\App\Http\Controllers\ReferralProgramController::class, 'index'])->name('referrals.programs.index');
    Route::post('/promotions/referral-programs', [\App\Http\Controllers\ReferralProgramController::class, 'store'])->name('referrals.programs.store');
    Route::post('/promotions/referral-programs/bulk-action', [\App\Http\Controllers\ReferralProgramController::class, 'bulk'])->name('referrals.programs.bulk');
    Route::put('/promotions/referral-programs/{id}', [\App\Http\Controllers\ReferralProgramController::class, 'update'])->name('referrals.programs.update');
    Route::patch('/promotions/referral-programs/{id}/toggle', [\App\Http\Controllers\ReferralProgramController::class, 'toggle'])->name('referrals.programs.toggle');
    Route::delete('/promotions/referral-programs/{id}', [\App\Http\Controllers\ReferralProgramController::class, 'destroy'])->name('referrals.programs.destroy');


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
    Route::get('/products-search-api', [\App\Modules\Catalog\Controllers\ProductController::class, 'searchApi'])
        ->name('products.search.api')
        ->middleware('permission:orders.create');
    Route::post('/coupons/validate', [\App\Modules\Orders\Controllers\CouponController::class, 'validateApi'])
        ->name('coupons.validate')
        ->middleware('permission:orders.create');

    // Call Tagging Ajax Routes
    Route::get('/call-tags', [\App\Modules\Orders\Controllers\CallTaggingController::class, 'getTags']);
    Route::get('/call-tags/{tag}/form', [\App\Modules\Orders\Controllers\CallTaggingController::class, 'getFormFields']);
    Route::post('/call-logs', [\App\Modules\Orders\Controllers\CallTaggingController::class, 'storeCallLog']);

    // System Audit Logs
    Route::get('/admin/audit-logs', [\App\Http\Controllers\AuditLogController::class, 'index'])->name('admin.audit-logs.index')->middleware('role:Super Admin');
    Route::delete('/admin/audit-logs/clear', [\App\Http\Controllers\AuditLogController::class, 'clearAll'])->name('admin.audit-logs.clear')->middleware('role:Super Admin');
    Route::delete('/admin/audit-logs/destroy', [\App\Http\Controllers\AuditLogController::class, 'destroy'])->name('admin.audit-logs.destroy')->middleware('role:Super Admin');

});
