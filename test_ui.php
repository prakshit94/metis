<?php
$user = \App\Modules\Users\Models\User::where('email', 'admin@example.com')->first();
auth()->login($user);

$routes = [
    '/users',
    '/departments',
    '/attendances',
    '/leaves',
    '/api/departments',
    '/api/attendances',
    '/api/leaves'
];

foreach ($routes as $route) {
    $request = Illuminate\Http\Request::create($route, 'GET');
    $response = app()->make(Illuminate\Contracts\Http\Kernel::class)->handle($request);
    
    echo "Route: $route - Status: " . $response->getStatusCode() . "\n";
    if ($response->getStatusCode() >= 400) {
        echo substr($response->getContent(), 0, 500) . "\n\n";
    }
}
