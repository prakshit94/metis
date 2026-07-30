<?php

declare(strict_types=1);

use App\Modules\Users\Controllers\AuthController;
use App\Modules\Users\Controllers\PermissionController;
use App\Modules\Users\Controllers\RoleController;
use App\Modules\Users\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Modules\Catalog\Controllers\ProductController as CatalogProductController;

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

    // ── Auth & Token Management ──────────────────────────────────────────────
    Route::prefix('auth')->name('api.auth.')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout'])
            ->name('logout');

        Route::post('/revoke-other-tokens', [AuthController::class, 'revokeOtherTokens'])
            ->name('revoke-other-tokens');
    });

    // ── Dashboard & Analytics ────────────────────────────────────────────────
    Route::get('/dashboard', [\App\Modules\Core\Controllers\PageController::class, 'dashboard'])->name('api.dashboard');
    Route::get('/reports', [\App\Modules\Core\Controllers\PageController::class, 'reports'])->name('api.reports');

    // ── Notifications / Activities ───────────────────────────────────────────
    Route::get('/activities/recent', function () {
        abort_unless(
            request()->user()?->can('audit-log-view')
            || request()->user()?->hasAnyRole(['Super Admin', 'Admin']),
            403,
            'You do not have permission to view activity logs.'
        );

        $user = auth()->user();
        $activities = \Spatie\Activitylog\Models\Activity::with('causer')->latest()->limit(15)->get();
        $readIds = $user ? $user->readActivities()->whereIn('activity_id', $activities->pluck('id'))->pluck('activity_id')->toArray() : [];

        $unreadCount = $activities->whereNotIn('id', $readIds)->count();

        return response()->json([
            'count' => $unreadCount,
            'activities' => $activities->map(function ($a) use ($readIds) {
                return [
                    'id'           => $a->id,
                    'description'  => $a->description,
                    'subject_type' => class_basename($a->subject_type),
                    'causer_name'  => $a->causer->name ?? 'System',
                    'causer_photo' => $a->causer->photo ?? null,
                    'time_ago'     => $a->created_at->diffForHumans(),
                    'is_read'      => in_array($a->id, $readIds),
                ];
            })
        ]);
    })->name('api.activities.recent');

    Route::post('/activities/{id}/read', function ($id) {
        $user = auth()->user();
        if (!$user) return response()->json(['success' => false], 401);
        
        if ($id === 'all') {
            $activityIds = \Spatie\Activitylog\Models\Activity::latest()->limit(50)->pluck('id');
            $user->readActivities()->syncWithoutDetaching($activityIds);
        } else {
            $user->readActivities()->syncWithoutDetaching([$id]);
        }
        
        return response()->json(['success' => true]);
    })->name('api.activities.read');

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

        Route::apiResource('roles', RoleController::class)
            ->names([
                'index'   => 'api.roles.index',
                'store'   => 'api.roles.store',
                'show'    => 'api.roles.show',
                'update'  => 'api.roles.update',
                'destroy' => 'api.roles.destroy',
            ]);

        Route::apiResource('permissions', PermissionController::class)
            ->names([
                'index'   => 'api.permissions.index',
                'store'   => 'api.permissions.store',
                'show'    => 'api.permissions.show',
                'update'  => 'api.permissions.update',
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
                'index'   => 'api.users.index',
                'store'   => 'api.users.store',
                'show'    => 'api.users.show',
                'update'  => 'api.users.update',
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
    });

    // ── Financial & Sales APIs ─────────────────────────────────────────────
    Route::prefix('orders')->name('api.orders.')->group(function (): void {
        Route::get('/', [\App\Modules\Orders\Controllers\OrderController::class, 'index'])->name('index');
        Route::post('/', [\App\Modules\Orders\Controllers\OrderController::class, 'store'])->name('store');
        Route::get('/{order}', [\App\Modules\Orders\Controllers\OrderController::class, 'show'])->name('show');
        Route::patch('/{order}', [\App\Modules\Orders\Controllers\OrderController::class, 'update'])->name('update');
        Route::delete('/{order}', [\App\Modules\Orders\Controllers\OrderController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('credit-notes')->name('api.credit-notes.')->group(function (): void {
        Route::get('/', [\App\Modules\Orders\Controllers\CreditNoteController::class, 'index'])->name('index');
        Route::post('/', [\App\Modules\Orders\Controllers\CreditNoteController::class, 'store'])->name('store');
        Route::patch('/{creditNote}', [\App\Modules\Orders\Controllers\CreditNoteController::class, 'update'])->name('update');
        Route::delete('/{creditNote}', [\App\Modules\Orders\Controllers\CreditNoteController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('invoices')->name('api.invoices.')->group(function (): void {
        Route::get('/', [\App\Modules\Orders\Controllers\InvoiceController::class, 'index'])->name('index');
        Route::post('/bulk-status', [\App\Modules\Orders\Controllers\InvoiceController::class, 'bulkStatus'])->name('bulk-status');
        Route::get('/{invoice}', [\App\Modules\Orders\Controllers\InvoiceController::class, 'show'])->name('show');
        Route::get('/{invoice}/pdf', [\App\Modules\Orders\Controllers\InvoiceController::class, 'downloadPdf'])->name('pdf');
    });

    Route::prefix('payments')->name('api.payments.')->group(function (): void {
        Route::get('/', [\App\Modules\Orders\Controllers\PaymentController::class, 'index'])->name('index');
        Route::post('/bulk-status', [\App\Modules\Orders\Controllers\PaymentController::class, 'bulkStatus'])->name('bulk-status');
        Route::post('/', [\App\Modules\Orders\Controllers\PaymentController::class, 'store'])->name('store');
        Route::get('/{payment}', [\App\Modules\Orders\Controllers\PaymentController::class, 'show'])->name('show');
    });

    Route::prefix('refunds')->name('api.refunds.')->group(function (): void {
        Route::get('/', [\App\Modules\Orders\Controllers\RefundController::class, 'index'])->name('index');
        Route::post('/bulk-status', [\App\Modules\Orders\Controllers\RefundController::class, 'bulkStatus'])->name('bulk-status');
        Route::get('/{refund}', [\App\Modules\Orders\Controllers\RefundController::class, 'show'])->name('show');
    });

    Route::prefix('returns')->name('api.returns.')->group(function (): void {
        Route::get('/', [\App\Modules\Orders\Controllers\OrderReturnController::class, 'index'])->name('index');
        Route::post('/', [\App\Modules\Orders\Controllers\OrderReturnController::class, 'store'])->name('store');
        Route::get('/{orderReturn}', [\App\Modules\Orders\Controllers\OrderReturnController::class, 'show'])->name('show');
        Route::post('/{orderReturn}/process', [\App\Modules\Orders\Controllers\OrderReturnController::class, 'process'])->name('process');
    });

    // ── Reason Codes API (Returns, Cancellations, Reschedules) ───────────────
    Route::prefix('order-reasons/{type}')->name('api.order-reasons.')->group(function (): void {
        Route::get('/', [\App\Modules\Orders\Controllers\OrderReasonController::class, 'list'])->name('list');
        Route::post('/', [\App\Modules\Orders\Controllers\OrderReasonController::class, 'store'])->name('store');
        Route::put('/{id}', [\App\Modules\Orders\Controllers\OrderReasonController::class, 'update'])->name('update');
        Route::delete('/{id}', [\App\Modules\Orders\Controllers\OrderReasonController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/toggle', [\App\Modules\Orders\Controllers\OrderReasonController::class, 'toggleActive'])->name('toggle');
    });

    // Promotions JSON API
    Route::prefix('promotions')->name('api.promotions.')->group(function (): void {
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

        

        // Bulk action must be defined before the resource to avoid route conflict
        Route::post('/users/bulk-action', \App\Modules\Users\Controllers\BulkUserController::class)->name('api.users.bulk');

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
        Route::get('/villages/import-template', [\App\Modules\Core\Controllers\VillageController::class, 'importTemplate'])->name('api.villages.import-template');
        Route::get('/villages/export', [\App\Modules\Core\Controllers\VillageController::class, 'export'])->name('api.villages.export');
        Route::post('/villages/export-selected', [\App\Modules\Core\Controllers\VillageController::class, 'exportSelected'])->name('api.villages.export-selected');
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
            Route::get('/services/provider-options', [\App\Modules\Core\Controllers\ShippingController::class, 'providerOptions'])->name('services.provider-options');
            Route::post('/services/bulk-action', [\App\Modules\Core\Controllers\ShippingController::class, 'servicesBulk'])->name('services.bulk');
            Route::post('/services', [\App\Modules\Core\Controllers\ShippingController::class, 'storeService'])->name('services.store');
            Route::patch('/services/{service}', [\App\Modules\Core\Controllers\ShippingController::class, 'updateService'])->name('services.update');
            Route::post('/services/{service}/toggle', [\App\Modules\Core\Controllers\ShippingController::class, 'toggleService'])->name('services.toggle');
            Route::delete('/services/{service}', [\App\Modules\Core\Controllers\ShippingController::class, 'destroyService'])->name('services.delete');
        });

});
