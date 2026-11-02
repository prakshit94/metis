<?php

// Includes legacy image, pricing, CSV, permission and stock rollback regressions.
require __DIR__.'/catalog_storefront.php';

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Services\ProductMedia;
use App\Modules\Inventory\Models\Stock;
use App\Storefront\Http\Controllers\StorefrontController;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;

$galleryPayload = array_replace($base, ['sku' => 'GALLERY-TEST', 'name' => 'Gallery sample']);
$result = $controller->store(catalogRequest($galleryPayload, ['images' => [imageUpload(), imageUpload(), imageUpload()]]))->getData(true)['data'];
$gallery = Product::findOrFail($result['id']);
check(count($result['images']) === 3 && count($gallery->gallery_image_paths) === 2, 'multiple uploads persist one legacy primary image and additional gallery images');
$paths = $gallery->imagePaths();
foreach ($paths as $path) check(Storage::disk('public')->exists($path), 'gallery image file exists');
$galleryMigration = require dirname(__DIR__, 2).'/database/migrations/2026_11_02_160000_add_gallery_image_paths_to_products_table.php';
try {
    $galleryMigration->down();
    throw new LogicException('Migration discarded live gallery metadata');
} catch (RuntimeException $error) {
    check(str_contains($error->getMessage(), 'Preserve product gallery associations') && count($gallery->fresh()->imagePaths()) === 3, 'schema rollback refuses to discard populated product galleries');
}
$beforeStock = $gallery->stocks()->get()->toJson();
$beforePrice = $gallery->selling_price;
$controller->update(catalogRequest(array_replace($galleryPayload, ['primary_image_id' => $result['images'][2]['id']])), $gallery);
$gallery->refresh();
check($gallery->image_path === $paths[2] && $gallery->imagePaths() === [$paths[2], $paths[0], $paths[1]], 'choosing primary updates the existing thumbnail and preserves the other images');
check($gallery->selling_price === $beforePrice && $gallery->stocks()->get()->toJson() === $beforeStock, 'gallery changes do not change prices, stock quantities or reservations');
$controller->update(catalogRequest($galleryPayload), $gallery);
check(count($gallery->fresh()->imagePaths()) === 3, 'older clients without gallery fields preserve the whole gallery');
$copyData = $controller->duplicate(catalogRequest(), $gallery)->getData(true)['data'];
$galleryCopy = Product::findOrFail($copyData['id']);
$controller->update(catalogRequest(array_replace($galleryPayload, ['remove_image_ids' => [$result['images'][0]['id']]])), $gallery);
$gallery->refresh();
check(count($gallery->imagePaths()) === 2 && Storage::disk('public')->exists($paths[0]), 'removal preserves a gallery file still used by a duplicated product');
invalid(fn () => $controller->update(catalogRequest(array_replace($galleryPayload, ['remove_image_ids' => [str_repeat('a', 64)]])), $gallery), 'unowned media identifiers are rejected');
invalid(fn () => $controller->update(catalogRequest(array_replace($galleryPayload, ['primary_image_id' => 'upload:9'])), $gallery), 'primary image must exist in the submitted gallery');
invalid(fn () => $controller->update(catalogRequest(array_replace($galleryPayload, ['remove_image_ids' => [$result['images'][2]['id']], 'primary_image_id' => $result['images'][2]['id']])), $gallery), 'an image cannot be removed and chosen as primary');
invalid(fn () => $controller->update(catalogRequest($galleryPayload, ['images' => array_map(fn () => imageUpload(), range(1, 9))]), $gallery), 'total image limit includes saved and new images');
invalid(fn () => $controller->update(catalogRequest($galleryPayload, ['image' => imageUpload(), 'images' => [imageUpload()]]), $gallery), 'ambiguous legacy and gallery uploads are rejected');
invalid(fn () => $controller->update(catalogRequest(array_replace($galleryPayload, ['name' => '   '])), $gallery), 'whitespace-only product names fail validation');
invalid(fn () => $controller->update(catalogRequest(array_replace($galleryPayload, ['sku' => ' AUDIT-1 '])), $gallery), 'trimmed SKU duplicates fail validation before hitting a unique index');
foreach (['purchase_price' => '1e100', 'selling_price' => '1e100', 'length_cm' => 1000000, 'default_discount' => 1000] as $field => $value) {
    invalid(fn () => $controller->update(catalogRequest(array_replace($galleryPayload, [$field => $value])), $gallery), 'numeric overflow is rejected before a database error');
}
$bad = UploadedFile::fake()->createWithContent('not-an-image.png', '<script>bad</script>');
invalid(fn () => $controller->update(catalogRequest($galleryPayload, ['images' => [$bad]]), $gallery), 'fake image extensions are rejected');
$large = UploadedFile::fake()->image('too-big.png')->size(5121);
invalid(fn () => $controller->update(catalogRequest($galleryPayload, ['images' => [$large]]), $gallery), 'per-image upload limit is enforced by the server');
$aggregate = array_map(fn () => UploadedFile::fake()->image('large.png')->size(5000), range(1, 6));
invalid(fn () => $controller->update(catalogRequest($galleryPayload, ['images' => $aggregate]), $gallery), 'aggregate upload limit is enforced by the server');

$beforeFiles = Storage::disk('public')->allFiles(); sort($beforeFiles);
$beforePaths = $gallery->imagePaths();
$failGalleryStock = true;
Stock::saving(function ($stock) use (&$failGalleryStock, $gallery) {
    if ($failGalleryStock && (int) $stock->product_id === $gallery->id) throw new RuntimeException('Gallery rollback probe');
});
try {
    $controller->update(catalogRequest(array_replace($galleryPayload, ['warehouse_is_sku_enabled' => true]), ['images' => [imageUpload(), imageUpload()]]), $gallery);
    throw new LogicException('Related failure was accepted');
} catch (RuntimeException $error) {
    check($error->getMessage() === 'Gallery rollback probe', 'related stock failure aborts gallery save');
}
$failGalleryStock = false;
$afterFiles = Storage::disk('public')->allFiles(); sort($afterFiles);
check($beforeFiles === $afterFiles && $gallery->fresh()->imagePaths() === $beforePaths, 'failed saves roll back metadata and remove all newly uploaded files');
$gallery->refresh();
$realDisk = Storage::disk('public');
$failingDisk = new class($realDisk) {
    private int $writes = 0;
    public function __construct(private Illuminate\Filesystem\FilesystemAdapter $disk) {}
    public function putFileAs(...$args) { return ++$this->writes === 1 ? $this->disk->putFileAs(...$args) : false; }
    public function exists($path) { return $this->disk->exists($path); }
    public function delete($path) { return $this->disk->delete($path); }
};
Storage::set('public', $failingDisk);
try {
    invalid(fn () => $controller->update(catalogRequest($galleryPayload, ['images' => [imageUpload(), imageUpload()]]), $gallery), 'partial storage failure returns validation instead of a false successful save');
} finally { Storage::set('public', $realDisk); }
$afterFailure = Storage::disk('public')->allFiles(); sort($afterFailure);
check($afterFailure === $beforeFiles && $gallery->fresh()->imagePaths() === $beforePaths, 'partial storage failure cleans up the first upload and preserves saved media');
$gallery->refresh();
if ($renderDirectory = getenv('METIS_PRODUCT_RENDER_DIR')) {
    if (!is_dir($renderDirectory)) mkdir($renderDirectory, 0755, true);
    app()->instance('request', catalogRequest());
    app('session')->driver()->start();
    view()->share('errors', new Illuminate\Support\ViewErrorBag);
    file_put_contents($renderDirectory.'/products.html', app(App\Modules\Catalog\Controllers\CatalogController::class)->products(catalogRequest())->render());
    file_put_contents($renderDirectory.'/products.json', $controller->index(catalogRequest())->getContent());
    file_put_contents($renderDirectory.'/detail.json', $controller->show(catalogRequest(), $gallery)->getContent());
    file_put_contents($renderDirectory.'/gallery.json', json_encode($gallery->mediaImages()));
    file_put_contents($renderDirectory.'/shop.html', view('shop.product', ['product' => App\Storefront\Models\Product::findOrFail($gallery->id)])->render());
    foreach (Storage::disk('public')->allFiles('products') as $path) {
        $target = $renderDirectory.'/storage/'.$path;
        if (!is_dir(dirname($target))) mkdir(dirname($target), 0755, true);
        copy(Storage::disk('public')->path($path), $target);
    }
}
$shopProduct = App\Storefront\Models\Product::findOrFail($gallery->id);
check($shopProduct->images->count() === 2 && $shopProduct->images->first()->id === 1, 'shop gallery remains primary-first and keeps legacy image ID 1');
$shop = app(StorefrontController::class);
check($shop->image(catalogRequest(), $gallery->id, 2)->getStatusCode() === 200, 'shop endpoint serves additional images from the configured public disk');
foreach ([0, 3] as $imageId) {
    try { $shop->image(catalogRequest(), $gallery->id, $imageId); throw new LogicException('Invalid image accepted'); }
    catch (HttpException $error) { check($error->getStatusCode() === 404, 'out-of-range shop image IDs return 404'); }
}
$gallery->update(['storefront_visible' => false]);
try { $shop->image(catalogRequest(), $gallery->id, 2); throw new LogicException('Hidden gallery served'); }
catch (Illuminate\Database\Eloquent\ModelNotFoundException $error) { check(true, 'hidden products retain storefront image visibility checks'); }
$galleryCopy->delete();
$controller->forceDelete(catalogRequest(), (string) $galleryCopy->id);
check(!Storage::disk('public')->exists($paths[0]), 'last reference cleanup includes gallery images on permanent deletion');
$allIds = array_column($gallery->mediaImages(), 'id');
$controller->update(catalogRequest(array_replace($galleryPayload, ['remove_image_ids' => $allIds])), $gallery);
check($gallery->fresh()->image_path === null && $gallery->fresh()->gallery_image_paths === [], 'removing all images safely returns a product to its placeholder');
check(count($gallery->fresh()->imagePaths()) === 0, 'removed gallery paths are no longer exposed');
echo "Product gallery checks complete.\n";
