<?php

use Illuminate\Support\Facades\Route;
use App\Storefront\Http\Controllers\StorefrontController as Shop;

// Public media never reads or writes a shopping session.
$mediaMiddleware = [
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    // GET-only media has no session token cookie to issue. Shopping POSTs retain CSRF.
    \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    \App\Http\Middleware\SelectWebSession::class,
];

Route::name('shop.')->group(function () use ($mediaMiddleware) {
    Route::get('/brand-logo', \App\Storefront\Http\Controllers\BrandingController::class)->withoutMiddleware($mediaMiddleware)->name('brand-logo');
    Route::get('/', [Shop::class, 'index'])->block(10, 10)->name('index');
    Route::get('/products/{slug}', [Shop::class, 'product'])->block(10, 10)->name('product');
    Route::get('/images/{id}/{image}', [Shop::class, 'image'])->whereNumber(['id', 'image'])->withoutMiddleware($mediaMiddleware)->name('image');
    Route::get('/register', [Shop::class, 'registerForm'])->block(10, 10)->name('register');
    Route::post('/register', [Shop::class, 'register'])->middleware('throttle:5,1')->block(10, 10)->name('register.submit');
    Route::post('/verification/resend', [Shop::class, 'resendVerification'])->middleware('throttle:3,1')->block(10, 10)->name('verification.resend');
    Route::get('/verify/{id}/{hash}', [Shop::class, 'verifyEmail'])->middleware(['signed', 'throttle:10,1'])->block(10, 10)->name('verify');
    Route::get('/login', [Shop::class, 'loginForm'])->block(10, 10)->name('login');
    Route::post('/login', [Shop::class, 'login'])->middleware('throttle:10,1')->block(10, 10)->name('login.submit');
    Route::get('/login/code', [Shop::class, 'codeForm'])->block(10, 10)->name('login.code');
    Route::post('/login/code', [Shop::class, 'verifyLoginCode'])->middleware('throttle:10,1')->block(10, 10)->name('login.code.verify');
    Route::post('/login/code/resend', [Shop::class, 'resendLoginCode'])->middleware('throttle:3,1')->block(10, 10)->name('login.code.resend');
    Route::post('/login/code/cancel', [Shop::class, 'cancelLoginCode'])->block(10, 10)->name('login.code.cancel');
    Route::post('/logout', [Shop::class, 'logout'])->block(10, 10)->name('logout');
    Route::get('/cart', [Shop::class, 'cart'])->block(10, 10)->name('cart');
    Route::post('/cart', [Shop::class, 'updateCart'])->middleware('throttle:60,1')->block(10, 10)->name('cart.update');
    Route::post('/addresses', [Shop::class, 'storeAddress'])->middleware('throttle:20,1')->block(10, 10)->name('addresses.store');
    Route::post('/addresses/{addressId}', [Shop::class, 'storeAddress'])->whereNumber('addressId')->middleware('throttle:20,1')->block(10, 10)->name('addresses.update');
    Route::get('/address-villages', [Shop::class, 'addressVillages'])->middleware('throttle:60,1')->block(10, 10)->name('address.villages');
    Route::post('/checkout', [Shop::class, 'checkout'])->middleware('throttle:10,1')->block(10, 10)->name('checkout');
    Route::patch('/profile', [Shop::class, 'updateProfile'])->middleware('throttle:10,1')->block(10, 10)->name('profile.update');
    Route::get('/profile', [Shop::class, 'profile'])->block(10, 10)->name('profile');
    Route::post('/orders/{id}/status', [Shop::class, 'orderAction'])->whereNumber('id')->middleware('throttle:10,1')->block(10, 10)->name('orders.status');
    Route::get('/orders', [Shop::class, 'orders'])->block(10, 10)->name('orders');
});

