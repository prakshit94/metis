<?php

$request = Illuminate\Http\Request::create('/api/users', 'GET', []);
// bypass middleware or simulate user
$user = App\Modules\Users\Models\User::find(1);
$request->setUserResolver(function () use ($user) {
    return $user;
});

$controller = app()->make(App\Modules\Users\Controllers\UserController::class);
$response = $controller->index($request);
echo $response->getContent();
