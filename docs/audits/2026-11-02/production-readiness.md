# Production readiness review — 2 November 2026

## Release scope

This release contains three independently committed application change groups:

1. Catalog multi-image uploads, primary-image compatibility, file cleanup and migration.
2. Storefront image-preview modal and compact main-image/thumbnail layout.
3. Attendance/leave validation and UI changes from the earlier request.

There is also a pre-existing dirty `vendor/mockery/mockery` entry. It is unrelated
and is excluded from this application release. New services, Blade partials and
public JS are included with their corresponding feature commits.

## Isolation checks

- No diff in `OrderService`, `InventoryService`, storefront `Checkout`, or
  `public/storefront/shop-cart.js`.
- Compact gallery CSS is scoped to `.premium-detail .compact-product-gallery`.
- Modal styling uses its own class names; the only document-wide scroll class is
  added on preview open and removed on close.
- Modal JS is loaded only on product details, handles only gallery links/dialog
  controls and performs no cart/order writes. Unsupported native dialogs retain
  normal image-link navigation. Modified new-tab gestures remain available.
- Product image URLs/primary `image_path` remain compatible with existing cards,
  carts and clients. Additional media is stored in an additive nullable column.
- Missing-image products retain a full-width placeholder in the compact gallery.
- Migration rollback refuses to drop a populated gallery column.

## Deployment requirements

Before activating the gallery backend, apply only the approved gallery migration
(or the normal reviewed release migration set):

```sh
php artisan migrate --path=database/migrations/2026_11_02_160000_add_gallery_image_paths_to_products_table.php --force
```

Deploy matching source, Blade partials, `public/storefront/product-gallery.js`,
`public/storefront/shop.css` and freshly built Vite assets as one release.
`public/build` is gitignored: run the production asset build in CI/deployment.
Refresh the compiled views with `php artisan view:cache` after the files exist.
Use the project's existing configuration/route-cache deployment steps.

Preserve the existing public-storage directory and `public/storage` link across
releases; do not replace uploaded images with an empty release directory. Check
write permission for the serving PHP account. Local storage/link checks pass,
but production filesystem permissions have not been inspected.

Application limits are 10 images, 5 MB/file and 25 MB of new files/save. Serving
PHP/proxy limits must accommodate those sizes plus multipart overhead. Local CLI
PHP reports only 2 MB/file and 8 MB/request; production settings are unverified.
The UI explains request-limit failures instead of reporting a successful save.

Use staging to smoke-test a legacy single-image product, a no-image product and
a new gallery, then product create/edit, cart and checkout, and HR pages if that
change group is included. This review does not deploy or access production.

## Rollback

Application commits on `fetures/fixbugs1-11-2026`:

- `a39aecf04`: attendance/leave validation and workspaces.
- `1f7fcde55`: gallery backend, admin, migration and compatibility checks.
- `ee114f743`: storefront compact gallery and preview UI.
- Previous release base: `146faaa50`.

To undo only the storefront UI, create a normal revert commit and push it:

```sh
git revert ee114f743
git push origin fetures/fixbugs1-11-2026
```

The HR changes can independently be reverted with `git revert a39aecf04`.
Rebuild frontend assets and refresh compiled views when deploying a revert.
Do not reset or force-push the shared branch.

Keep database/storage backups and the previous application release. Roll back
only the image-layout/modal UI if necessary while retaining the compatible media
backend and nullable column. Do not automatically drop gallery metadata or delete
uploaded files. A full rollback to a backend that only understands primary images
requires a reviewed media-data/backup plan before allowing image edits or permanent
product deletion; that older cleanup code does not know about gallery references.

## Validation results

| Check | Result |
| --- | --- |
| Catalog/gallery + existing catalog compatibility | PASS — 66 checks |
| Attendance/leave compatibility | PASS — 17 checks |
| Public image middleware, caching and visibility | PASS — 15 checks |
| Storefront/cart/checkout integration | PASS — 64 checks |
| Gallery upload frontend regression | PASS |
| Image preview frontend regression | PASS |
| HR frontend regression | PASS |
| Production Vite asset build | PASS |
| PHP syntax for changed application classes and migration | PASS |
| Blade compilation and whitespace/error check | PASS |

All four database processes exited successfully, with 162 passing checks total.
They used separate disposable MySQL schemas; no production database was accessed.
Media tests used temporary filesystem roots and the real application middleware.
These checks cannot certify production configuration or eliminate every possible
regression. Production staging/backup/storage/upload checks remain release gates.
