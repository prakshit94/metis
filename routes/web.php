<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageController;


Route::get('/', [PageController::class, 'dashboard'])->name('dashboard');
Route::get('/login', [PageController::class, 'login'])->name('login');
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

// Elements
Route::prefix('elements')->group(function () {
    Route::get('/', [PageController::class, 'elementsOverview'])->name('elements');
    Route::get('/alerts', [PageController::class, 'elementsAlerts'])->name('elements-alerts');
    Route::get('/badges', [PageController::class, 'elementsBadges'])->name('elements-badges');
    Route::get('/buttons', [PageController::class, 'elementsButtons'])->name('elements-buttons');
    Route::get('/cards', [PageController::class, 'elementsCards'])->name('elements-cards');
    Route::get('/forms', [PageController::class, 'elementsForms'])->name('elements-forms');
    Route::get('/modals', [PageController::class, 'elementsModals'])->name('elements-modals');
    Route::get('/tables', [PageController::class, 'elementsTables'])->name('elements-tables');
});
