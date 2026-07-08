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
    Route::get('/orders', [PageController::class, 'orders'])->name('orders');
    Route::get('/reports', [PageController::class, 'reports'])->name('reports');
    Route::get('/messages', [PageController::class, 'messages'])->name('messages');
    Route::get('/calendar', [PageController::class, 'calendar'])->name('calendar');
    Route::get('/files', [PageController::class, 'files'])->name('files');
    Route::get('/forms', [PageController::class, 'forms'])->name('forms');
    Route::get('/settings', [PageController::class, 'settings'])->name('settings');
    Route::get('/security', [PageController::class, 'security'])->name('security');
    Route::get('/help', [PageController::class, 'help'])->name('help');

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
    });
});
