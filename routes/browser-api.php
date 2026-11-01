<?php

use App\Modules\Core\Controllers\FileManagerController;
use App\Modules\Users\Controllers\SecurityController;
use Illuminate\Support\Facades\Route;

// Browser sessions and CSRF remain mandatory on these existing API URLs.
Route::post('/api/security/stop-impersonate', [SecurityController::class, 'stopImpersonating'])
    ->middleware('throttle:api')->block(10, 10);
Route::post('/api/security/impersonate/{user}', [SecurityController::class, 'impersonate'])
    ->middleware(['auth', 'throttle:api'])->block(10, 10);
Route::get('/api/files/preview', [FileManagerController::class, 'preview'])->name('files.preview');
