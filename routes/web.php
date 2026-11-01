<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\PreventBackHistory;
use App\Services\TeamContext;
use Illuminate\Support\Facades\Route;

require __DIR__.'/storefront.php';

Route::middleware([TeamContext::class, PreventBackHistory::class, EnsureUserIsActive::class])->group(function () {
    Route::prefix('admin')->group(__DIR__.'/admin.php');
    require __DIR__.'/browser-api.php';
});
