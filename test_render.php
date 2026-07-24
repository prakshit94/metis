<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::create('/orders', 'GET');
$user = App\Modules\Users\Models\User::first();
if ($user) {
    auth()->login($user);
}
// Boot the app to ensure db connection works
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$response = $kernel->handle($request);
$content = $response->getContent();
preg_match('/<select class="form-select" x-model="scheduleReason".*?<\/select>/s', $content, $matches);
print_r($matches);
