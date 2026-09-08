<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$provider = new App\Services\Shipping\Providers\IndiaPostProvider();
try {
    $token = $provider->authenticate();
    echo "Token: " . substr($token, 0, 10) . "...\n";
    
    $settings = \App\Models\SystemSetting::where('key', 'like', 'india_post_%')->pluck('value', 'key');
    $baseUrl = $settings['india_post_base_url'] ?? config('shipping.providers.india_post.base_url');
    echo "Base URL: " . $baseUrl . "\n";
    
    $response = \Illuminate\Support\Facades\Http::withToken($token)
        ->withOptions(['curl' => [CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2]])
        ->get("{$baseUrl}/v1/pincode-search", [
            'pincode' => '570001',
            'office-type' => 'post',
        ]);
        
    echo "Status: " . $response->status() . "\n";
    echo "Body: " . $response->body() . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
