<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\Roles\PermissionController;
use App\Http\Controllers\Roles\RoleController;
use App\Http\Controllers\Products\ProductController as CatalogProductController;
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
        Route::get('/products', [\App\Http\Controllers\Catalog\CatalogController::class, 'products'])->name('products');
        Route::get('/brands', [\App\Http\Controllers\Catalog\CatalogController::class, 'brands'])->name('brands');
        Route::get('/categories', [\App\Http\Controllers\Catalog\CatalogController::class, 'categories'])->name('categories');
        Route::get('/uom', [\App\Http\Controllers\Catalog\CatalogController::class, 'uom'])->name('uom');
        Route::get('/tax-rates', [\App\Http\Controllers\Catalog\CatalogController::class, 'taxRates'])->name('tax-rates');
        Route::get('/hsn-codes', [\App\Http\Controllers\Catalog\CatalogController::class, 'hsnCodes'])->name('hsn-codes');
        Route::get('/warehouses', [\App\Http\Controllers\Catalog\CatalogController::class, 'warehouses'])->name('warehouses');
        Route::get('/attributes', [\App\Http\Controllers\Catalog\CatalogController::class, 'attributes'])->name('attributes');
    });
    Route::prefix('inventory')->name('inventory.')->group(function (): void {
        Route::get('/stock-management', [PageController::class, 'stockManagement'])->name('stock-management');
        Route::get('/stock-transfers', [PageController::class, 'stockTransfers'])->name('stock-transfers');
        Route::get('/adjustments', [PageController::class, 'inventoryAdjustments'])->name('adjustments');
    });
    Route::post('orders/bulk-status', [\App\Http\Controllers\Orders\OrderController::class, 'bulkStatus'])->name('orders.bulk-status');
    Route::post('orders/bulk-verification', [\App\Http\Controllers\Orders\OrderController::class, 'bulkStoreVerification'])->name('orders.bulk-verification');
    Route::get('orders/bulk-print', [\App\Http\Controllers\Orders\OrderController::class, 'bulkPrint'])->name('orders.bulk-print');
    Route::get('orders/export', [\App\Http\Controllers\Orders\OrderController::class, 'bulkExport'])->name('orders.export');
    Route::post('orders/import', [\App\Http\Controllers\Orders\OrderController::class, 'bulkImport'])->name('orders.import');
    Route::get('orders/import-template', [\App\Http\Controllers\Orders\OrderController::class, 'bulkImportTemplate'])->name('orders.import-template');
    Route::get('orders/{order}/invoice-pdf', [\App\Http\Controllers\Orders\OrderController::class, 'downloadInvoice'])->name('orders.invoice-pdf');
    Route::post('orders/{order}/generate-invoice', [\App\Http\Controllers\Orders\OrderController::class, 'generateInvoice'])->name('orders.generate-invoice');
    Route::get('orders/{order}/cod-pdf', [\App\Http\Controllers\Orders\OrderController::class, 'downloadReceipt'])->name('orders.cod-pdf');
    Route::post('orders/{order}/confirm', [\App\Http\Controllers\Orders\OrderController::class, 'confirm'])->name('orders.confirm');
    Route::post('orders/{order}/ship', [\App\Http\Controllers\Orders\OrderController::class, 'ship'])->name('orders.ship');
    Route::post('orders/{order}/dispatch', [\App\Http\Controllers\Orders\OrderController::class, 'dispatch'])->name('orders.dispatch');
    Route::post('orders/{order}/processing', [\App\Http\Controllers\Orders\OrderController::class, 'markProcessing'])->name('orders.processing');
    Route::post('orders/{order}/deliver', [\App\Http\Controllers\Orders\OrderController::class, 'markDelivered'])->name('orders.deliver');
    Route::post('orders/{order}/cancel', [\App\Http\Controllers\Orders\OrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('orders/{order}/return', [\App\Http\Controllers\Orders\OrderController::class, 'markReturned'])->name('orders.return');
    Route::post('orders/{order}/revert-status', [\App\Http\Controllers\Orders\OrderController::class, 'revertStatus'])->name('orders.revert-status');
    Route::get('orders/{order}/receipt', [\App\Http\Controllers\Orders\OrderController::class, 'receipt'])->name('orders.receipt');
    Route::post('orders/{order}/verification', [\App\Http\Controllers\Orders\OrderController::class, 'storeVerification'])->name('orders.verification.store');
    Route::get('/orders', [\App\Http\Controllers\Orders\OrderController::class, 'index'])->name('orders');
    Route::resource('orders', \App\Http\Controllers\Orders\OrderController::class)->except(['index']);
    Route::get('/customers', [PageController::class, 'customers'])->name('customers');
    Route::get('/customers/search-by-phone', [\App\Http\Controllers\Customers\CustomerController::class, 'searchByPhone'])->name('customers.search-by-phone');
    Route::get('/customers/{customer}', [\App\Http\Controllers\Customers\CustomerController::class, 'show'])->name('customers.show');
    Route::post('/customers/{customer}/orders/place', [\App\Http\Controllers\Customers\CustomerController::class, 'placeOrder'])->name('customers.orders.place');
    Route::get('/villages', [PageController::class, 'villages'])->name('villages');
    Route::get('/shipping/shipments', [PageController::class, 'shipments'])->name('shipping.shipments');
    Route::get('/shipping/services', [PageController::class, 'shippingServices'])->name('shipping.services');
    Route::get('/reports', [PageController::class, 'reports'])->name('reports');
    Route::get('/messages', [PageController::class, 'messages'])->name('messages');
    Route::get('/calendar', [PageController::class, 'calendar'])->name('calendar');
    Route::get('/files', [PageController::class, 'files'])->name('files');
    Route::get('/forms', [PageController::class, 'forms'])->name('forms');
    Route::get('/settings', [PageController::class, 'settings'])->name('settings');
    Route::get('/security', [PageController::class, 'security'])->name('security');
    Route::get('/help', [PageController::class, 'help'])->name('help');

    // ─── Promotions ──────────────────────────────────────────────────────────
    Route::get('/promotions/coupons', [\App\Http\Controllers\Orders\PromotionsController::class, 'coupons'])->name('promotions.coupons');
    Route::get('/promotions/offers', [\App\Http\Controllers\Orders\PromotionsController::class, 'offers'])->name('promotions.offers');

    // Promotions JSON API
    Route::prefix('api/promotions')->name('api.promotions.')->group(function (): void {
        // Coupons
        Route::get('/coupons', [\App\Http\Controllers\Orders\PromotionsController::class, 'couponsIndex'])->name('coupons.index');
        Route::post('/coupons', [\App\Http\Controllers\Orders\PromotionsController::class, 'couponsStore'])->name('coupons.store');
        Route::patch('/coupons/{coupon}', [\App\Http\Controllers\Orders\PromotionsController::class, 'couponsUpdate'])->name('coupons.update');
        Route::delete('/coupons/{coupon}', [\App\Http\Controllers\Orders\PromotionsController::class, 'couponsDestroy'])->name('coupons.destroy');
        Route::patch('/coupons/{coupon}/toggle', [\App\Http\Controllers\Orders\PromotionsController::class, 'couponsToggle'])->name('coupons.toggle');
        Route::post('/coupons/bulk-action', [\App\Http\Controllers\Orders\PromotionsController::class, 'couponsBulk'])->name('coupons.bulk');
        // Offers
        Route::get('/offers', [\App\Http\Controllers\Orders\PromotionsController::class, 'offersIndex'])->name('offers.index');
        Route::post('/offers', [\App\Http\Controllers\Orders\PromotionsController::class, 'offersStore'])->name('offers.store');
        Route::patch('/offers/{offer}', [\App\Http\Controllers\Orders\PromotionsController::class, 'offersUpdate'])->name('offers.update');
        Route::delete('/offers/{offer}', [\App\Http\Controllers\Orders\PromotionsController::class, 'offersDestroy'])->name('offers.destroy');
        Route::patch('/offers/{offer}/toggle', [\App\Http\Controllers\Orders\PromotionsController::class, 'offersToggle'])->name('offers.toggle');
        Route::post('/offers/bulk-action', [\App\Http\Controllers\Orders\PromotionsController::class, 'offersBulk'])->name('offers.bulk');
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
    Route::get('/products-search-api', [\App\Http\Controllers\Products\ProductController::class, 'searchApi'])
        ->name('products.search.api')
        ->middleware('permission:orders.create');
    Route::post('/coupons/validate', [\App\Http\Controllers\Orders\CouponController::class, 'validateApi'])
        ->name('coupons.validate')
        ->middleware('permission:orders.create');

    // ─── User Management JSON API ─────────────────────────────────────────────
    Route::prefix('api')->group(function (): void {
        
        // Catalog API Routes
        Route::apiResource('/brands', \App\Http\Controllers\Catalog\BrandController::class);
        Route::apiResource('/categories', \App\Http\Controllers\Catalog\CategoryController::class);
        Route::apiResource('/uom', \App\Http\Controllers\Catalog\UnitOfMeasureController::class);
        Route::apiResource('/tax-rates', \App\Http\Controllers\Catalog\TaxRateController::class);
        Route::apiResource('/hsn-codes', \App\Http\Controllers\Catalog\HsnCodeController::class);
        Route::apiResource('/warehouses', \App\Http\Controllers\Catalog\WarehouseController::class);
        Route::apiResource('/attributes', \App\Http\Controllers\Catalog\ProductAttributeController::class);
        Route::post('/attributes/{attribute}/values', [\App\Http\Controllers\Catalog\ProductAttributeController::class, 'storeValue'])->name('api.attributes.values.store');
        Route::patch('/attributes/values/{value}', [\App\Http\Controllers\Catalog\ProductAttributeController::class, 'updateValue'])->name('api.attributes.values.update');
        Route::delete('/attributes/values/{value}', [\App\Http\Controllers\Catalog\ProductAttributeController::class, 'destroyValue'])->name('api.attributes.values.destroy');

        // Inventory API Routes
        Route::prefix('inventory')->name('api.inventory.')->group(function (): void {
            Route::get('/stocks', [\App\Http\Controllers\Inventory\StockManagementController::class, 'index'])->name('stocks.index');
            Route::post('/stocks/set', [\App\Http\Controllers\Inventory\StockManagementController::class, 'setStock'])->name('stocks.set');
            Route::get('/stocks/show', [\App\Http\Controllers\Inventory\StockManagementController::class, 'show'])->name('stocks.show');
            Route::get('/stocks/warehouse-options', [\App\Http\Controllers\Inventory\StockManagementController::class, 'warehouseOptions'])->name('stocks.warehouse-options');

            Route::get('/transfers/options', [\App\Http\Controllers\Inventory\StockTransferController::class, 'options'])->name('transfers.options');
            Route::post('/transfers/bulk-action', [\App\Http\Controllers\Inventory\StockTransferController::class, 'bulkAction'])->name('transfers.bulk-action');
            Route::post('/transfers/{stockTransfer}/send', [\App\Http\Controllers\Inventory\StockTransferController::class, 'send'])->name('transfers.send');
            Route::post('/transfers/{stockTransfer}/receive', [\App\Http\Controllers\Inventory\StockTransferController::class, 'receive'])->name('transfers.receive');
            Route::post('/transfers/{stockTransfer}/cancel', [\App\Http\Controllers\Inventory\StockTransferController::class, 'cancel'])->name('transfers.cancel');
            Route::apiResource('/transfers', \App\Http\Controllers\Inventory\StockTransferController::class);

            Route::get('/adjustments/options', [\App\Http\Controllers\Inventory\InventoryAdjustmentController::class, 'options'])->name('adjustments.options');
            Route::post('/adjustments/bulk-action', [\App\Http\Controllers\Inventory\InventoryAdjustmentController::class, 'bulkAction'])->name('adjustments.bulk-action');
            Route::post('/adjustments/{inventoryAdjustment}/approve', [\App\Http\Controllers\Inventory\InventoryAdjustmentController::class, 'approve'])->name('adjustments.approve');
            Route::post('/adjustments/{inventoryAdjustment}/reject', [\App\Http\Controllers\Inventory\InventoryAdjustmentController::class, 'reject'])->name('adjustments.reject');
            Route::apiResource('/adjustments', \App\Http\Controllers\Inventory\InventoryAdjustmentController::class);
        });

        Route::prefix('products')->name('api.products.')->group(function (): void {
            Route::get('/', [CatalogProductController::class, 'index'])->name('index');
            Route::get('/export', [CatalogProductController::class, 'export'])->name('export');
            Route::post('/import', [CatalogProductController::class, 'import'])->name('import');
            Route::post('/bulk-status', [CatalogProductController::class, 'bulkStatus'])->name('bulk-status');
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

            return response()->json(\App\Models\Role::query()
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

            return response()->json(\App\Models\Permission::orderBy('name')->get(['id', 'name']));
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
        Route::post('/users/bulk-action', \App\Http\Controllers\Users\BulkUserController::class)->name('api.users.bulk');
        Route::patch('/users/{user}/restore', [\App\Http\Controllers\Users\UserController::class, 'restore'])->name('api.users.restore');
        Route::delete('/users/{user}/force', [\App\Http\Controllers\Users\UserController::class, 'forceDelete'])->name('api.users.force-delete');
        Route::apiResource('/users', \App\Http\Controllers\Users\UserController::class)->names([
            'index'   => 'api.users.index',
            'store'   => 'api.users.store',
            'show'    => 'api.users.show',
            'update'  => 'api.users.update',
            'destroy' => 'api.users.destroy',
        ]);
        Route::patch('/users/{user}/toggle-active', [\App\Http\Controllers\Users\UserController::class, 'toggleActive'])->name('api.users.toggle-active');
        Route::post('/users/{user}/sync-roles', [\App\Http\Controllers\Users\UserController::class, 'syncRoles'])->name('api.users.sync-roles');
        Route::post('/users/{user}/sync-permissions', [\App\Http\Controllers\Users\UserController::class, 'syncPermissions'])->name('api.users.sync-permissions');
        Route::get('/users/{user}/login-history', [\App\Http\Controllers\Users\UserController::class, 'loginHistory'])->name('api.users.login-history');

        // Customers API Routes
        Route::post('/customers/bulk-action', [\App\Http\Controllers\Customers\CustomerController::class, 'bulkAction'])->name('api.customers.bulk');
        Route::patch('/customers/{customer}/restore', [\App\Http\Controllers\Customers\CustomerController::class, 'restore'])->name('api.customers.restore');
        Route::delete('/customers/{customer}/force', [\App\Http\Controllers\Customers\CustomerController::class, 'forceDelete'])->name('api.customers.force-delete');
        Route::patch('/customers/{customer}/toggle-active', [\App\Http\Controllers\Customers\CustomerController::class, 'toggleActive'])->name('api.customers.toggle-active');
        Route::apiResource('/customers', \App\Http\Controllers\Customers\CustomerController::class)->names([
            'index'   => 'api.customers.index',
            'store'   => 'api.customers.store',
            'show'    => 'api.customers.show',
            'update'  => 'api.customers.update',
            'destroy' => 'api.customers.destroy',
        ]);
        Route::apiResource('/customers.addresses', \App\Http\Controllers\Customers\CustomerAddressController::class)->names([
            'store'   => 'api.customers.addresses.store',
            'update'  => 'api.customers.addresses.update',
            'destroy' => 'api.customers.addresses.destroy',
        ])->only(['store', 'update', 'destroy']);

        // Villages API Routes
        Route::post('/villages/bulk-action', [\App\Http\Controllers\Villages\VillageController::class, 'bulkAction'])->name('api.villages.bulk');
        Route::get('/villages/services-options', [\App\Http\Controllers\Villages\VillageController::class, 'servicesOptions'])->name('api.villages.services-options');
        Route::get('/villages/search', [\App\Http\Controllers\Villages\VillageController::class, 'search'])->name('api.villages.search');
        Route::post('/villages/import', [\App\Http\Controllers\Villages\VillageController::class, 'import'])->name('api.villages.import');
        Route::apiResource('/villages', \App\Http\Controllers\Villages\VillageController::class)->names([
            'index'   => 'api.villages.index',
            'store'   => 'api.villages.store',
            'show'    => 'api.villages.show',
            'update'  => 'api.villages.update',
            'destroy' => 'api.villages.destroy',
        ]);

        // Shipping & Tracking API
        Route::prefix('shipping')->name('api.shipping.')->group(function (): void {
            Route::get('/shipments', [\App\Http\Controllers\Shipping\ShippingController::class, 'shipmentsIndex'])->name('shipments.index');
            Route::post('/shipments/{shipment}/status', [\App\Http\Controllers\Shipping\ShippingController::class, 'updateShipmentStatus'])->name('shipments.status');
            Route::get('/shipments/{shipment}/tracking', [\App\Http\Controllers\Shipping\ShippingController::class, 'trackingEvents'])->name('shipments.tracking');
            Route::post('/shipments/{shipment}/tracking-event', [\App\Http\Controllers\Shipping\ShippingController::class, 'addTrackingEvent'])->name('shipments.add-tracking-event');

            Route::get('/services', [\App\Http\Controllers\Shipping\ShippingController::class, 'servicesIndex'])->name('services.index');
            Route::post('/services', [\App\Http\Controllers\Shipping\ShippingController::class, 'storeService'])->name('services.store');
            Route::patch('/services/{service}', [\App\Http\Controllers\Shipping\ShippingController::class, 'updateService'])->name('services.update');
            Route::post('/services/{service}/toggle', [\App\Http\Controllers\Shipping\ShippingController::class, 'toggleService'])->name('services.toggle');
            Route::delete('/services/{service}', [\App\Http\Controllers\Shipping\ShippingController::class, 'destroyService'])->name('services.delete');
        });
    });
});
