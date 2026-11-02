# Product catalog audit and image gallery

Scope: `/admin/catalog/products`, its create/edit/detail modals, catalog API,
product persistence, image storage and deletion, and storefront image consumers.
The existing attendance/leave work in this checkout is separate from this change.

## Business flow reviewed

- `ProductController::index` supplies the directory, stock totals, options and
  storefront visibility flags. The browser filters, sorts and paginates this data.
- Create/edit use the same `validatePayload`, `fillProduct`, `persistProduct`,
  attribute mapping and warehouse stock-configuration workflow. Existing product
  permissions and LOB warehouse restrictions remain the authority.
- Tax-inclusive selling prices remain authoritative when provided. Base prices,
  discounts, quantities, reservations and inventory history are not recalculated
  by media handling. Saving a product continues to configure stock settings;
  it does not directly overwrite inventory quantities.
- Detail responses supply full product information; directory summary responses
  keep the existing single thumbnail instead of expanding every gallery.
- The shop filters published/active/listed/SKU-enabled products before serving
  details or image files. Cards and carts continue to use the first image.
- Soft deletion retains media for restoration. Permanent deletion still refuses
  products with transaction history or nonzero physical/reserved/dispatched stock.
- The duplicate API retains shared image files, while the existing unsaved Clone
  form starts without images. Both behaviors are preserved.

## Implemented

### Backward-compatible storage

`products.image_path` remains the primary image. A nullable JSON
`gallery_image_paths` column stores only the additional image paths. Old products
need no backfill. Old clients may continue sending `image` to replace the primary;
updates omitting all media fields retain the gallery.

`imagePaths()` returns deduplicated paths with primary first. Full catalog
responses expose `images: [{id, url, is_primary}]`. IDs are hashes of saved paths;
clients cannot provide arbitrary storage paths. The new fields are not mass
assignable. The primary still supplies existing `image` and `image_url` fields.

### Create/edit UI

Media & Status now supports repeated multi-file selections, previews, primary
selection and individual removal. A saved image is not reuploaded. Removal is
staged until Save; Cancel performs no server deletion. Pending object URLs are
released when removed, reset, replaced or the modal closes. Product details expose
all saved thumbnails, and the shop's existing gallery displays all images.

Limits: 10 images total, 5 MB each, 25 MB of new uploads per save. New gallery
uploads accept JPEG, PNG, WebP and GIF. Existing legacy image files remain readable.
Client checks improve feedback; server image inspection, MIME/extension validation,
count, size and ownership checks enforce the limits independently.

### Transaction and filesystem handling

The product row is locked before applying gallery changes. Media identifiers are
checked against that product's current paths while the lock is held. A primary
must refer to a retained image or an upload in the request. Removing a primary
without another explicit selection promotes the first remaining image.

Files use server-generated UUID names. New paths are tracked before filesystem
writes, so exceptions and partial write failures can be cleaned up. Product,
gallery metadata, attributes and warehouse configuration save in one transaction.
Old files are considered for cleanup only after commit. Cleanup checks primary
and gallery references on active AND soft-deleted products, preserving shared
images used by duplicates. Cleanup errors are logged without falsely reporting
that a committed product save failed.

The storefront image route accepts additional 1-based gallery positions, retains
image ID 1 for the primary, enforces product visibility, checks real filesystem
paths stay inside the configured public disk, checks MIME and keeps caching /
`nosniff` behavior. This continues to target the existing local public disk.

### Validation / feedback defects corrected

- Trim names and SKUs before required/unique validation, preventing empty names
  and whitespace-wrapped duplicate SKUs from reaching persistence.
- Bound prices, discounts and shipping dimensions to their existing database
  column sizes so impossible values return validation errors rather than SQL errors.
- Stop malformed warehouse IDs before custom database lookup closures run.
- Reject mixed legacy and gallery upload contracts in one request.
- Permission failures now throw an error instead of returning `undefined` after
  redirecting, which previously allowed calling code to display apparent success.
- Explain session expiry and web-server upload-limit responses, including HTML 413s.
- Keep save errors visible on the form and open Media for media-validation failures.
- Keep successful saves distinct from subsequent directory refresh failures.

## Verification

`node tests/regression/product_gallery.mjs` exercises successive selections,
primary selection/removal, multipart upload indexing after removal, saved-image
references, file/count/aggregate limits, object URL cleanup and API error behavior.

`php tests/regression/product_gallery.php` uses the disposable MySQL schema and
temporary public image root. It includes the existing catalog/storefront suite:
legacy uploads, duplicate visibility, image sharing and cleanup, stock rollback,
CSV validation/import/export, stable slugs, exact inclusive prices and permissions.
New checks cover multi-upload persistence, retained galleries for older clients,
primary changes, unchanged prices/stock, unauthorized media IDs, malformed files,
limits/overflow, rollback, a filesystem that fails after the first upload,
storefront gallery reads/visibility and deletion of the final file reference.

An outdated existing test expected `eligible_subtotal` from coupon validation.
That endpoint returns `discount`; its assertion now verifies the same exact
fractional-quantity/base-price arithmetic through the current discount response.
No coupon/order service logic was changed.

Optional `METIS_PRODUCT_RENDER_DIR` generates sample pages and responses using
only disposable fixture data for browser inspection.

Completed validation: catalog + gallery database suite PASS; gallery JavaScript
suite PASS; PHP/JavaScript syntax checks PASS; Blade compilation PASS; production
Vite build PASS. Browser checks with fixture data verified the edit gallery,
simultaneous two-file selection, primary switching, removal, cancellation restoring
the saved gallery, empty create state and a 375-pixel modal without horizontal
content overflow. No browser console errors were recorded during the final check.

## Deployment and rollback

- Apply `2026_11_02_160000_add_gallery_image_paths_to_products_table.php` before
  enabling the new application code. It adds a nullable column and preserves all
  existing product rows and primary images. This migration was applied locally.
- Build/deploy frontend assets and refresh compiled views as usual.
- Keep the existing public storage link and writable `products` directory. For a
  full 25 MB upload, PHP `post_max_size` and the proxy/web-server body limit must
  exceed 25 MB plus multipart overhead; `upload_max_filesize` must permit 5 MB
  and `max_file_uploads` must permit 10. Lower server limits produce an explicit
  UI error; this patch does not change server configuration automatically. Local
  CLI PHP currently reports 2 MB/file, 8 MB/request and 20 files/request; raise
  the serving PHP/proxy limits to use the full application upload allowance.
- For application rollback, leave the additive column/files in place so galleries
  can be recovered. The migration now refuses to drop a column containing gallery
  associations. Prefer a UI-only rollback while retaining compatible media cleanup;
  review the data plan before reverting to a primary-only backend. Do not delete
  the physical gallery files as a rollback.

## Existing behavior left for separate decisions

- Directory loading is unpaginated on the server. Very large catalogs still need
  a separately designed server-pagination/filter/export change; this gallery does
  not increase the summary response with full image arrays.
- CSV images, drag reordering, cropping, compression and remote/CDN storage are
  not introduced. They require additional contracts or operational choices.
- Existing MRP rules, attribute-empty behavior, inventory adjustment workflows,
  lifecycle transitions and public storage visibility were not redefined.
