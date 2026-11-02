<section class="border rounded-3 p-3 mb-4" aria-labelledby="product-images-label">
    <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
        <div><h6 id="product-images-label" class="mb-1">Product images</h6><p class="small text-muted mb-0">Choose a primary image for product lists and orders. Additional images appear in the product gallery.</p></div>
        <span class="badge bg-secondary text-nowrap" x-text="images.length + ' / 10'"></span>
    </div>
    <label for="product-images" class="form-label small mt-3">Add images</label>
    <input id="product-images" type="file" class="form-control" multiple accept="image/jpeg,image/png,image/webp,image/gif" @change="handleImageUpload($event)" :disabled="saving || images.length >= 10" aria-describedby="product-images-help">
    <p id="product-images-help" class="form-text">JPG, PNG, WebP or GIF · Up to 10 images · 5 MB per image · 25 MB per save. Recommended: 800 × 800 px. You can select more images again before saving.</p>
    <div class="alert alert-danger small" x-show="mediaError" x-text="mediaError" x-cloak role="alert"></div>
    <div class="text-center border rounded-3 bg-body-tertiary p-4 text-muted" x-show="!images.length"><i class="bi bi-images fs-3 d-block mb-2"></i>No product images yet. Add images using the field above.</div>
    <div class="row g-3 mt-1" aria-live="polite">
        <template x-for="(image, index) in images" :key="image.id">
            <div class="col-6 col-md-4 col-xl-3">
                <div class="border rounded-3 p-2 h-100" :class="primaryImageId === image.id ? 'border-primary' : ''">
                    <img :src="image.url" :alt="'Product image ' + (index + 1)" class="w-100 rounded bg-body-tertiary" style="aspect-ratio:1;object-fit:contain" x-on:error="if ($el.getAttribute('src') !== '/assets/images/product-placeholder.svg') $el.src='/assets/images/product-placeholder.svg'">
                    <p class="small text-muted text-truncate my-2" x-text="image.file?.name || 'Saved image ' + (index + 1)" :title="image.file?.name || 'Saved image'"></p>
                    <div class="d-flex flex-column gap-2">
                        <button type="button" class="btn btn-sm" :class="primaryImageId === image.id ? 'btn-primary' : 'btn-outline-primary'" :aria-pressed="primaryImageId === image.id" :disabled="saving" @click="primaryImageId = image.id" x-text="primaryImageId === image.id ? 'Primary image' : 'Make primary'"></button>
                        <button type="button" class="btn btn-sm btn-outline-danger" :disabled="saving" @click="removeImage(image.id)" :aria-label="'Remove product image ' + (index + 1)">Remove</button>
                    </div>
                </div>
            </div>
        </template>
    </div>
    <p class="small text-muted mt-3 mb-0" x-show="removedImageIds.length">Images marked for removal are removed only when you save. Canceling keeps the saved images.</p>
</section>
