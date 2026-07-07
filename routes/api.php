<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Roles\PermissionController;
use App\Http\Controllers\Roles\RoleController;
use App\Http\Controllers\Users\UserController;
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

    // ── Auth & Token Management ──────────────────────────────────────────────
    Route::prefix('auth')->name('api.auth.')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout'])
            ->name('logout');

        Route::post('/revoke-other-tokens', [AuthController::class, 'revokeOtherTokens'])
            ->name('revoke-other-tokens');

        // Impersonation — requires manage-users permission
        Route::middleware('permission:manage-users')->group(function (): void {
            Route::post('/impersonate/{user}', [AuthController::class, 'impersonate'])
                ->name('impersonate');
        });

        Route::delete('/impersonate', [AuthController::class, 'stopImpersonating'])
            ->name('stop-impersonate');
    });

    // ── Roles Management ─────────────────────────────────────────────────────
    Route::middleware('permission:manage-roles')->group(function (): void {
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
    Route::middleware('permission:manage-users')->group(function (): void {
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

        Route::middleware('permission:view-audit-logs')->group(function (): void {
            Route::get('users/{user}/login-history', [UserController::class, 'loginHistory'])
                ->name('api.users.login-history');
        });
    });
});
