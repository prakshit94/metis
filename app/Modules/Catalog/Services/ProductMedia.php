<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductMedia
{
    public const MAX_IMAGES = 10;
    public const MAX_UPLOAD_BYTES = 25 * 1024 * 1024;

    /** Called under the product row lock. Uploaded files are tracked for rollback. */
    public function apply(Product $product, array $data, Request $request, array &$uploaded): void
    {
        $paths = $product->imagePaths();
        $byId = array_combine(array_map(fn ($path) => hash('sha256', $path), $paths), $paths);
        foreach ($data['remove_image_ids'] ?? [] as $id) {
            if (!isset($byId[$id])) {
                throw ValidationException::withMessages(['remove_image_ids' => 'An image has changed or does not belong to this product. Reopen the product and retry.']);
            }
            unset($byId[$id]);
        }

        $files = $request->hasFile('image') ? [$request->file('image')] : array_values($request->file('images', []));
        // The original API's image field replaces only the primary image.
        if ($request->hasFile('image') && $product->image_path) {
            unset($byId[hash('sha256', $product->image_path)]);
        }
        if (count($byId) + count($files) > self::MAX_IMAGES) {
            throw ValidationException::withMessages(['images' => 'A product can have up to 10 images. Remove an image before adding more.']);
        }
        if (array_sum(array_map(fn ($file) => $file->getSize(), $files)) > self::MAX_UPLOAD_BYTES) {
            throw ValidationException::withMessages(['images' => 'Upload no more than 25 MB of images per save.']);
        }
        $primary = $data['primary_image_id'] ?? null;
        $validPrimary = $primary === null || isset($byId[$primary])
            || (preg_match('/^upload:(0|[1-9][0-9]*)$/', $primary) && isset($files[(int) substr($primary, 7)]));
        if (!$validPrimary) {
            throw ValidationException::withMessages(['primary_image_id' => 'Choose a primary image from the images kept on this product.']);
        }

        foreach ($files as $index => $file) {
            $path = 'products/'.Str::uuid().'.'.$file->extension();
            // Track before writing, including adapters that throw after a partial write.
            $uploaded[] = $path;
            try {
                $disk = Storage::disk('public');
                $stored = $disk->putFileAs('products', $file, basename($path));
                if (!$stored || !$disk->exists($path)) throw new \RuntimeException('Image write failed.');
            } catch (\Throwable $error) {
                Log::error('Product image upload failed.', ['exception' => $error->getMessage()]);
                throw ValidationException::withMessages(['images' => 'The image could not be saved to product storage. Please retry or contact support.']);
            }
            $byId['upload:'.$index] = $path;
        }

        $primaryPath = $primary !== null ? $byId[$primary] : ($request->hasFile('image') ? ($byId['upload:0'] ?? null) : reset($byId));
        $product->image_path = $primaryPath ?: null;
        $product->gallery_image_paths = array_values(array_filter($byId, fn ($path) => $path !== $product->image_path));
    }

    /** Shared paths from duplicated or soft-deleted products must remain readable. */
    public function deleteUnused(?string $path): void
    {
        if (!$path || !str_starts_with($path, 'products/') || str_contains($path, '..')) return;
        if (Product::withTrashed()->where(fn ($query) => $query->where('image_path', $path)
            ->orWhereJsonContains('gallery_image_paths', $path))->exists()) return;
        try {
            if (!Storage::disk('public')->delete($path)) Log::warning('Unused product image could not be deleted.', ['path' => $path]);
        } catch (\Throwable $error) {
            // A cleanup failure must not turn a committed product save into a false failure.
            Log::warning('Unused product image cleanup failed.', ['path' => $path, 'exception' => $error->getMessage()]);
        }
    }
}
