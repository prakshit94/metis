<?php
require __DIR__.'/bootstrap.php';

use App\Storefront\Models\Product;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

Artisan::call('migrate', ['--force' => true]);
$mediaDir = '/tmp/metis-media-'.bin2hex(random_bytes(6));
mkdir($mediaDir.'/app/public/products', 0700, true);
$app->useStoragePath($mediaDir);
register_shutdown_function(function () use ($mediaDir) {
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($mediaDir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($mediaDir);
});
copy(public_path('assets/images/logo-small.webp'), $mediaDir.'/app/public/products/sample.webp');
$product = Product::create(['name'=>'Media fixture', 'sku'=>'MEDIA-FIXTURE', 'slug'=>'media-fixture', 'selling_price'=>100, 'image_path'=>'products/sample.webp', 'storefront_visible'=>true, 'is_active'=>true, 'status'=>'published', 'is_sku_enabled'=>true]);
function mediaRequest(string $url, array $headers = []) {
    global $app;
    $request = Request::create($url, 'GET', [], [], [], $headers);
    $kernel = $app->make(Kernel::class);
    $response = $kernel->handle($request);
    $kernel->terminate($request, $response);
    return $response;
}
$firstUrl = $product->images->first()->url;
check(str_starts_with($firstUrl, '/images/'.$product->id.'/1?v='), 'product image URLs use the current origin and a revision');
$response = mediaRequest($firstUrl);
check($response->getStatusCode() === 200 && str_starts_with($response->headers->get('Content-Type'), 'image/'), 'public product image loads through the real middleware stack');
check(!$response->headers->getCookies(), 'public image does not start a customer or staff session');
check($response->headers->hasCacheControlDirective('public') && $response->headers->getCacheControlDirective('max-age') == 3600, 'product image is browser cacheable');
$etag = $response->getEtag();
check($etag && mediaRequest($firstUrl, ['HTTP_IF_NONE_MATCH'=>$etag])->getStatusCode() === 304, 'unchanged product image revalidates without sending the file again');
$logo = mediaRequest('/brand-logo');
check($logo->getStatusCode() === 200 && $logo->getFile()->getSize() < 20000, 'default logo response uses the small bundled image');
check(!$logo->headers->getCookies() && $logo->headers->hasCacheControlDirective('public'), 'logo loads without a shopping session and supports caching');
check(mediaRequest('/brand-logo', ['HTTP_IF_NONE_MATCH'=>$logo->getEtag()])->getStatusCode() === 304, 'logo supports conditional cache revalidation');
Illuminate\Support\Facades\DB::table('system_settings')->insert(['key'=>'default_image_logo_png', 'value'=>'/storage/products/sample.webp']);
$uploadedLogo = mediaRequest('/brand-logo');
check($uploadedLogo->getStatusCode() === 200 && $uploadedLogo->getFile()->getPathname() === $mediaDir.'/app/public/products/sample.webp', 'uploaded logo resolves safely through the public storage symlink');
Illuminate\Support\Facades\DB::table('system_settings')->where('key', 'default_image_logo_png')->update(['value'=>'/storage/../../private.txt']);
check(mediaRequest('/brand-logo')->getStatusCode() === 200, 'invalid configured logo path falls back to bundled branding');
$product->image_path = 'products/replacement.webp';
check($product->images->first()->url !== $firstUrl, 'replacing an image changes its URL instead of reusing a stale cached image');
$product->image_path = 'products/sample.webp';
$product->update(['storefront_visible'=>false]);
check(mediaRequest($firstUrl)->getStatusCode() === 404, 'media endpoint still hides products unavailable in the storefront');
$product->update(['storefront_visible'=>true, 'image_path'=>'../private.txt']);
check(mediaRequest($product->images->first()->url)->getStatusCode() === 404, 'media endpoint still rejects paths outside public product storage');
$router = app('router');
foreach (['/', '/cart'] as $url) {
    $route = $router->getRoutes()->match(Request::create($url));
    $middleware = $router->gatherRouteMiddleware($route);
    check(in_array(Illuminate\Session\Middleware\StartSession::class, $middleware, true) && in_array(Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class, $middleware, true), 'shopping session and CSRF middleware retained for '.$url);
}
echo "Storefront media regression checks passed.\n";
