<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\PageController;
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
    Route::get('/products', [PageController::class, 'products'])->name('products');
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

        // Roles list (for dropdowns)
        Route::get('/roles', function () {
            return response()->json(\Spatie\Permission\Models\Role::orderBy('name')->get(['id', 'name']));
        })->name('api.roles.index');
    });
});
