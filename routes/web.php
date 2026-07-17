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
        Route::get('/stock-management', [PageController::class, 'stockManagement'])->name('stock-management');
        Route::get('/stock-transfers', [PageController::class, 'stockTransfers'])->name('stock-transfers');
        Route::get('/adjustments', [PageController::class, 'inventoryAdjustments'])->name('adjustments');
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
    Route::get('/orders', [\App\Modules\Orders\Controllers\OrderController::class, 'index'])->name('orders');
    Route::resource('orders', \App\Modules\Orders\Controllers\OrderController::class)->except(['index']);

    // Order Returns
    Route::get('returns', [\App\Modules\Orders\Controllers\OrderReturnController::class, 'index'])->name('returns.index');
    Route::post('orders/{order}/returns', [\App\Modules\Orders\Controllers\OrderReturnController::class, 'store'])->name('orders.returns.store');
    Route::get('returns/{return}', [\App\Modules\Orders\Controllers\OrderReturnController::class, 'show'])->name('returns.show');
    Route::post('returns/{return}/qc', [\App\Modules\Orders\Controllers\OrderReturnController::class, 'processQc'])->name('returns.qc');
    Route::post('returns/{return}/finance', [\App\Modules\Orders\Controllers\OrderReturnController::class, 'processFinancials'])->name('returns.finance');

    // Billing & Financials
    Route::get('refunds', [\App\Modules\Orders\Controllers\RefundController::class, 'index'])->name('refunds.index');
    Route::post('refunds/bulk-status', [\App\Modules\Orders\Controllers\RefundController::class, 'bulkStatus'])->name('refunds.bulk-status');
    Route::get('payments', [\App\Modules\Orders\Controllers\PaymentController::class, 'index'])->name('payments.index');
    Route::post('payments/bulk-status', [\App\Modules\Orders\Controllers\PaymentController::class, 'bulkStatus'])->name('payments.bulk-status');
    Route::post('payments/export', [\App\Modules\Orders\Controllers\PaymentController::class, 'exportSelected'])->name('payments.export.selected');
    Route::put('payments/{payment}', [\App\Modules\Orders\Controllers\PaymentController::class, 'update'])->name('payments.update');
    
    // Import Routes
    Route::get('payments/import/sample', [\App\Modules\Orders\Controllers\PaymentImportController::class, 'downloadSample'])->name('payments.import.sample');
    Route::post('payments/import/preview', [\App\Modules\Orders\Controllers\PaymentImportController::class, 'preview'])->name('payments.import.preview');
    Route::post('payments/import/process', [\App\Modules\Orders\Controllers\PaymentImportController::class, 'process'])->name('payments.import.process');

    Route::get('invoices', [\App\Modules\Orders\Controllers\InvoiceController::class, 'index'])->name('invoices.index');
    Route::post('invoices/bulk-status', [\App\Modules\Orders\Controllers\InvoiceController::class, 'bulkStatus'])->name('invoices.bulk-status');
    Route::post('invoices/export', [\App\Modules\Orders\Controllers\InvoiceController::class, 'exportSelected'])->name('invoices.export.selected');
    Route::post('invoices/{invoice}/payments', [\App\Modules\Orders\Controllers\InvoiceController::class, 'recordPayment'])->name('invoices.payments.store');
    Route::get('/customers', [PageController::class, 'customers'])->name('customers');
    Route::get('/customers/search-by-phone', [\App\Modules\Customers\Controllers\CustomerController::class, 'searchByPhone'])->name('customers.search-by-phone');
    Route::get('/customers/{customer}', [\App\Modules\Customers\Controllers\CustomerController::class, 'show'])->name('customers.show');
    Route::post('/customers/{customer}/orders/place', [\App\Modules\Customers\Controllers\CustomerController::class, 'placeOrder'])->name('customers.orders.place');
    Route::get('/villages', [PageController::class, 'villages'])->name('villages');
    Route::get('/shipping/shipments', [PageController::class, 'shipments'])->name('shipping.shipments')->middleware('permission:shipping-view');
    Route::get('/shipping/services', [PageController::class, 'shippingServices'])->name('shipping.services')->middleware('permission:shipping-view');
    Route::get('/reports', [PageController::class, 'reports'])->name('reports');
    Route::get('/messages', [PageController::class, 'messages'])->name('messages');
    Route::get('/calendar', [PageController::class, 'calendar'])->name('calendar');
    Route::get('/files', [PageController::class, 'files'])->name('files');
    Route::get('/forms', [PageController::class, 'forms'])->name('forms');
    Route::get('/settings', [PageController::class, 'settings'])->name('settings');
    Route::get('/security', [PageController::class, 'security'])->name('security');
    Route::get('/help', [PageController::class, 'help'])->name('help');

    // ─── Promotions ──────────────────────────────────────────────────────────
    Route::get('/promotions/coupons', [\App\Modules\Orders\Controllers\PromotionsController::class, 'coupons'])->name('promotions.coupons');
    Route::get('/promotions/offers', [\App\Modules\Orders\Controllers\PromotionsController::class, 'offers'])->name('promotions.offers');

    // Promotions JSON API
    Route::prefix('api/promotions')->name('api.promotions.')->group(function (): void {
        // Coupons
        Route::get('/coupons', [\App\Modules\Orders\Controllers\PromotionsController::class, 'couponsIndex'])->name('coupons.index');
        Route::post('/coupons', [\App\Modules\Orders\Controllers\PromotionsController::class, 'couponsStore'])->name('coupons.store');
        Route::patch('/coupons/{coupon}', [\App\Modules\Orders\Controllers\PromotionsController::class, 'couponsUpdate'])->name('coupons.update');
        Route::delete('/coupons/{coupon}', [\App\Modules\Orders\Controllers\PromotionsController::class, 'couponsDestroy'])->name('coupons.destroy');
        Route::patch('/coupons/{coupon}/toggle', [\App\Modules\Orders\Controllers\PromotionsController::class, 'couponsToggle'])->name('coupons.toggle');
        Route::post('/coupons/bulk-action', [\App\Modules\Orders\Controllers\PromotionsController::class, 'couponsBulk'])->name('coupons.bulk');
        // Offers
        Route::get('/offers', [\App\Modules\Orders\Controllers\PromotionsController::class, 'offersIndex'])->name('offers.index');
        Route::post('/offers', [\App\Modules\Orders\Controllers\PromotionsController::class, 'offersStore'])->name('offers.store');
        Route::patch('/offers/{offer}', [\App\Modules\Orders\Controllers\PromotionsController::class, 'offersUpdate'])->name('offers.update');
        Route::delete('/offers/{offer}', [\App\Modules\Orders\Controllers\PromotionsController::class, 'offersDestroy'])->name('offers.destroy');
        Route::patch('/offers/{offer}/toggle', [\App\Modules\Orders\Controllers\PromotionsController::class, 'offersToggle'])->name('offers.toggle');
        Route::post('/offers/bulk-action', [\App\Modules\Orders\Controllers\PromotionsController::class, 'offersBulk'])->name('offers.bulk');
    });

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

    // ─── User Management JSON API ─────────────────────────────────────────────
    Route::prefix('api')->group(function (): void {
        
        // Catalog API Routes
        Route::apiResource('/brands', \App\Modules\Catalog\Controllers\BrandController::class);
        Route::apiResource('/categories', \App\Modules\Catalog\Controllers\CategoryController::class);
        Route::apiResource('/uom', \App\Modules\Catalog\Controllers\UnitOfMeasureController::class);
        Route::apiResource('/tax-rates', \App\Modules\Catalog\Controllers\TaxRateController::class);
        Route::apiResource('/hsn-codes', \App\Modules\Catalog\Controllers\HsnCodeController::class);
        Route::apiResource('/warehouses', \App\Modules\Catalog\Controllers\WarehouseController::class);
        Route::apiResource('/attributes', \App\Modules\Catalog\Controllers\ProductAttributeController::class);
        Route::post('/attributes/{attribute}/values', [\App\Modules\Catalog\Controllers\ProductAttributeController::class, 'storeValue'])->name('api.attributes.values.store');
        Route::patch('/attributes/values/{value}', [\App\Modules\Catalog\Controllers\ProductAttributeController::class, 'updateValue'])->name('api.attributes.values.update');
        Route::delete('/attributes/values/{value}', [\App\Modules\Catalog\Controllers\ProductAttributeController::class, 'destroyValue'])->name('api.attributes.values.destroy');

        // Inventory API Routes
        Route::prefix('inventory')->name('api.inventory.')->group(function (): void {
            Route::get('/stocks', [\App\Modules\Inventory\Controllers\StockManagementController::class, 'index'])->name('stocks.index');
            Route::post('/stocks/set', [\App\Modules\Inventory\Controllers\StockManagementController::class, 'setStock'])->name('stocks.set');
            Route::get('/stocks/show', [\App\Modules\Inventory\Controllers\StockManagementController::class, 'show'])->name('stocks.show');
            Route::get('/stocks/warehouse-options', [\App\Modules\Inventory\Controllers\StockManagementController::class, 'warehouseOptions'])->name('stocks.warehouse-options');

            Route::get('/transfers/options', [\App\Modules\Inventory\Controllers\StockTransferController::class, 'options'])->name('transfers.options');
            Route::post('/transfers/bulk-action', [\App\Modules\Inventory\Controllers\StockTransferController::class, 'bulkAction'])->name('transfers.bulk-action');
            Route::post('/transfers/{stockTransfer}/send', [\App\Modules\Inventory\Controllers\StockTransferController::class, 'send'])->name('transfers.send');
            Route::post('/transfers/{stockTransfer}/receive', [\App\Modules\Inventory\Controllers\StockTransferController::class, 'receive'])->name('transfers.receive');
            Route::post('/transfers/{stockTransfer}/cancel', [\App\Modules\Inventory\Controllers\StockTransferController::class, 'cancel'])->name('transfers.cancel');
            Route::apiResource('/transfers', \App\Modules\Inventory\Controllers\StockTransferController::class);

            Route::get('/adjustments/options', [\App\Modules\Inventory\Controllers\InventoryAdjustmentController::class, 'options'])->name('adjustments.options');
            Route::post('/adjustments/bulk-action', [\App\Modules\Inventory\Controllers\InventoryAdjustmentController::class, 'bulkAction'])->name('adjustments.bulk-action');
            Route::post('/adjustments/{inventoryAdjustment}/approve', [\App\Modules\Inventory\Controllers\InventoryAdjustmentController::class, 'approve'])->name('adjustments.approve');
            Route::post('/adjustments/{inventoryAdjustment}/reject', [\App\Modules\Inventory\Controllers\InventoryAdjustmentController::class, 'reject'])->name('adjustments.reject');
            Route::apiResource('/adjustments', \App\Modules\Inventory\Controllers\InventoryAdjustmentController::class);
        });

        Route::prefix('products')->name('api.products.')->group(function (): void {
            Route::get('/', [CatalogProductController::class, 'index'])->name('index');
            Route::get('/export', [CatalogProductController::class, 'export'])->name('export');
            Route::post('/import', [CatalogProductController::class, 'import'])->name('import');
            Route::post('/bulk-status', [CatalogProductController::class, 'bulkStatus'])->name('bulk-status');
            Route::post('/bulk-disable-sku', [CatalogProductController::class, 'bulkDisableSku'])->name('bulk-disable-sku');
            Route::post('/bulk-delete', [CatalogProductController::class, 'bulkDelete'])->name('bulk-delete');
            Route::post('/{product}/duplicate', [CatalogProductController::class, 'duplicate'])->name('duplicate');
            Route::post('/{product}/restore', [CatalogProductController::class, 'restore'])->name('restore');
            Route::delete('/{product}/force-delete', [CatalogProductController::class, 'forceDelete'])->name('force-delete');
            Route::get('/{product}', [CatalogProductController::class, 'show'])->name('show');
            Route::patch('/{product}', [CatalogProductController::class, 'update'])->name('update');
            Route::delete('/{product}', [CatalogProductController::class, 'destroy'])->name('destroy');
            Route::post('/', [CatalogProductController::class, 'store'])->name('store');
        });

        Route::get('/roles/options', function () {
            abort_unless(
                request()->user()?->can('role-view')
                || request()->user()?->can('user-create')
                || request()->user()?->can('user-edit')
                || request()->user()?->can('role-create')
                || request()->user()?->can('role-edit'),
                403,
            );

            return response()->json(\App\Modules\Users\Models\Role::query()
                ->orderBy('name')
                ->get(['id', 'name', 'guard_name']));
        })->name('api.roles.options');
        Route::get('/permissions/options', function () {
            abort_unless(
                request()->user()?->can('permission-view')
                || request()->user()?->can('user-create')
                || request()->user()?->can('user-edit')
                || request()->user()?->can('role-create')
                || request()->user()?->can('role-edit'),
                403,
            );

            return response()->json(\App\Modules\Users\Models\Permission::orderBy('name')->get(['id', 'name']));
        })->name('api.permissions.options');
        Route::patch('/roles/{role}/restore', [RoleController::class, 'restore'])->name('api.roles.restore');
        Route::delete('/roles/{role}/force', [RoleController::class, 'forceDelete'])->name('api.roles.force-delete');
        Route::patch('/permissions/{permission}/restore', [PermissionController::class, 'restore'])->name('api.permissions.restore');
        Route::delete('/permissions/{permission}/force', [PermissionController::class, 'forceDelete'])->name('api.permissions.force-delete');
        Route::apiResource('/roles', RoleController::class)->names([
            'index'   => 'api.roles.index',
            'store'   => 'api.roles.store',
            'show'    => 'api.roles.show',
            'update'  => 'api.roles.update',
            'destroy' => 'api.roles.destroy',
        ]);
        Route::apiResource('/permissions', PermissionController::class)->names([
            'index'   => 'api.permissions.index',
            'store'   => 'api.permissions.store',
            'show'    => 'api.permissions.show',
            'update'  => 'api.permissions.update',
            'destroy' => 'api.permissions.destroy',
        ]);

        // Bulk action must be defined before the resource to avoid route conflict
        Route::post('/users/bulk-action', \App\Modules\Users\Controllers\BulkUserController::class)->name('api.users.bulk');
        Route::patch('/users/{user}/restore', [\App\Modules\Users\Controllers\UserController::class, 'restore'])->name('api.users.restore');
        Route::delete('/users/{user}/force', [\App\Modules\Users\Controllers\UserController::class, 'forceDelete'])->name('api.users.force-delete');
        Route::apiResource('/users', \App\Modules\Users\Controllers\UserController::class)->names([
            'index'   => 'api.users.index',
            'store'   => 'api.users.store',
            'show'    => 'api.users.show',
            'update'  => 'api.users.update',
            'destroy' => 'api.users.destroy',
        ]);
        Route::patch('/users/{user}/toggle-active', [\App\Modules\Users\Controllers\UserController::class, 'toggleActive'])->name('api.users.toggle-active');
        Route::post('/users/{user}/sync-roles', [\App\Modules\Users\Controllers\UserController::class, 'syncRoles'])->name('api.users.sync-roles');
        Route::post('/users/{user}/sync-permissions', [\App\Modules\Users\Controllers\UserController::class, 'syncPermissions'])->name('api.users.sync-permissions');
        Route::get('/users/{user}/login-history', [\App\Modules\Users\Controllers\UserController::class, 'loginHistory'])->name('api.users.login-history');

        // Customers API Routes
        Route::post('/customers/bulk-action', [\App\Modules\Customers\Controllers\CustomerController::class, 'bulkAction'])->name('api.customers.bulk');
        Route::patch('/customers/{customer}/restore', [\App\Modules\Customers\Controllers\CustomerController::class, 'restore'])->name('api.customers.restore');
        Route::delete('/customers/{customer}/force', [\App\Modules\Customers\Controllers\CustomerController::class, 'forceDelete'])->name('api.customers.force-delete');
        Route::patch('/customers/{customer}/toggle-active', [\App\Modules\Customers\Controllers\CustomerController::class, 'toggleActive'])->name('api.customers.toggle-active');
        Route::apiResource('/customers', \App\Modules\Customers\Controllers\CustomerController::class)->names([
            'index'   => 'api.customers.index',
            'store'   => 'api.customers.store',
            'show'    => 'api.customers.show',
            'update'  => 'api.customers.update',
            'destroy' => 'api.customers.destroy',
        ]);
        Route::apiResource('/customers.addresses', \App\Modules\Customers\Controllers\CustomerAddressController::class)->names([
            'store'   => 'api.customers.addresses.store',
            'update'  => 'api.customers.addresses.update',
            'destroy' => 'api.customers.addresses.destroy',
        ])->only(['store', 'update', 'destroy']);

        // Villages API Routes
        Route::post('/villages/bulk-action', [\App\Modules\Core\Controllers\VillageController::class, 'bulkAction'])->name('api.villages.bulk');
        Route::get('/villages/services-options', [\App\Modules\Core\Controllers\VillageController::class, 'servicesOptions'])->name('api.villages.services-options');
        Route::get('/villages/search', [\App\Modules\Core\Controllers\VillageController::class, 'search'])->name('api.villages.search');
        Route::post('/villages/import', [\App\Modules\Core\Controllers\VillageController::class, 'import'])->name('api.villages.import');
        Route::apiResource('/villages', \App\Modules\Core\Controllers\VillageController::class)->names([
            'index'   => 'api.villages.index',
            'store'   => 'api.villages.store',
            'show'    => 'api.villages.show',
            'update'  => 'api.villages.update',
            'destroy' => 'api.villages.destroy',
        ]);

        // Shipping & Tracking API
        Route::prefix('shipping')->name('api.shipping.')->group(function (): void {
            Route::get('/shipments', [\App\Modules\Core\Controllers\ShippingController::class, 'shipmentsIndex'])->name('shipments.index');
            Route::post('/shipments/bulk-action', [\App\Modules\Core\Controllers\ShippingController::class, 'shipmentsBulk'])->name('shipments.bulk');
            Route::post('/shipments/{shipment}/status', [\App\Modules\Core\Controllers\ShippingController::class, 'updateShipmentStatus'])->name('shipments.status');
            Route::get('/shipments/{shipment}/tracking', [\App\Modules\Core\Controllers\ShippingController::class, 'trackingEvents'])->name('shipments.tracking');
            Route::post('/shipments/{shipment}/tracking-event', [\App\Modules\Core\Controllers\ShippingController::class, 'addTrackingEvent'])->name('shipments.add-tracking-event');

            Route::get('/services', [\App\Modules\Core\Controllers\ShippingController::class, 'servicesIndex'])->name('services.index');
            Route::post('/services/bulk-action', [\App\Modules\Core\Controllers\ShippingController::class, 'servicesBulk'])->name('services.bulk');
            Route::post('/services', [\App\Modules\Core\Controllers\ShippingController::class, 'storeService'])->name('services.store');
            Route::patch('/services/{service}', [\App\Modules\Core\Controllers\ShippingController::class, 'updateService'])->name('services.update');
            Route::post('/services/{service}/toggle', [\App\Modules\Core\Controllers\ShippingController::class, 'toggleService'])->name('services.toggle');
            Route::delete('/services/{service}', [\App\Modules\Core\Controllers\ShippingController::class, 'destroyService'])->name('services.delete');
        });
    });
});
