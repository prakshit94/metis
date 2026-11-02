<dialog id="product-image-preview" class="product-image-preview" aria-labelledby="image-preview-title">
    <div class="image-preview-header">
        <h2 id="image-preview-title">{{ $product->name }}</h2>
        <button type="button" class="image-preview-button image-preview-close" data-preview-close aria-label="Close image preview" autofocus><span aria-hidden="true">×</span></button>
    </div>
    <div class="image-preview-stage"><img data-preview-image alt="" decoding="async"></div>
    <div class="image-preview-footer">
        <button type="button" class="image-preview-button" data-preview-previous aria-label="Previous image"><span aria-hidden="true">‹</span> Previous</button>
        <p data-preview-counter role="status" aria-live="polite" aria-atomic="true"></p>
        <button type="button" class="image-preview-button" data-preview-next aria-label="Next image">Next <span aria-hidden="true">›</span></button>
    </div>
    <p class="image-preview-error" data-preview-error role="status" hidden>This image could not be loaded. Try another image or close the preview.</p>
</dialog>
