<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/test-bug', function (Request $request) {
    $user = \App\Modules\Users\Models\User::first();
    \Illuminate\Support\Facades\Auth::login($user);
    $controller = app()->make(\App\Modules\Orders\Controllers\OrderController::class);
    // Bind request
    app()->instance('request', $request);
    $request->merge(['customer_id' => '6']);
    try {
        $response = $controller->create();
        return $response;
    } catch (\Exception $e) {
        return $e->getMessage() . "\n" . $e->getTraceAsString();
    }
});
