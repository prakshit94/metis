# Catalog and storefront follow-up audit

Scope: `/catalog/products`, product CRUD/bulk/duplicate/import/export APIs, Alpine product table/form, shared catalog model, and the separate storefront's catalog/detail/cart/checkout presentation.

## Bugs fixed

1. **Broken public product URLs after edits.** Every edit previously regenerated the slug from the current name. Saved slugs now remain stable. New slug generation includes soft-deleted products to match the database's unique constraint, uses a safe fallback for names that do not transliterate, and leaves space for uniqueness suffixes. Copy SKU generation also checks deleted rows and bounds generated length.
2. **Shared image deletion.** Duplicates share the original image path. Replacing or permanently deleting one product previously removed that image even when another product still referenced it. Cleanup now includes active and trashed references and removes only unused product-upload paths.
3. **Partial saves and premature image removal.** Product, attribute and warehouse-setting writes now share a transaction. The previous image remains until commit. Failed saves clean up the new uploaded image rather than leaving a broken previous image or partially updated product. A regression injects a related stock save failure and verifies both database rollback and filesystem preservation.
4. **Unexpected publication through copying.** Server duplicates are explicitly private/unfeatured drafts. Form-based copies also reset storefront visibility/featured/status and clear the unsaved image preview, so the UI does not falsely imply an unuploaded image will be copied.
5. **Stale edit responses.** A slower edit/clone fetch could overwrite a later selection. Detail request versions now discard superseded responses; opening Create invalidates pending selections. Replaced blob image previews are released.
6. **Incomplete storefront management.** Added Listed / Hidden / Enabled but unavailable filtering, visible row badges, links to actually listed products, a saved-product link in Edit, and bulk Show/Hide in shop controls. Existing product-edit permissions protect the new endpoint; hiding/showing does not alter product status, pricing, stock or SKU settings.
7. **Duplicated publication rules.** The admin API, storefront catalog and checkout now use the shared product publication predicate. Draft/inactive/deleted/SKU-disabled/negative-price products do not become publicly listed simply because storefront visibility was enabled. Admin responses include slug, availability and the configured shop URL.
8. **Missing backend input checks.** Storefront quantity bounds are enforced on both clients and APIs. Backend percentage-discount validation now matches the existing client's 100% rule. Invalid structured search/filter input produces validation errors rather than server exceptions. HTML error responses are converted into readable UI messages.
9. **CSV gaps.** Both exports include storefront flags and quantity bounds. CSV import validates raw numeric/reference/boolean fields before coercion, rejects duplicate headers, supports a UTF-8 BOM, rejects explicit warehouses outside the operator's state, and commits each valid product/stock row atomically. Invalid rows return bounded, readable row errors and do not partially create products. Legacy CSVs that omit storefront columns retain those settings. Existing overwrite/increment stock rules are retained.
10. **False storefront discount display.** The reference storefront compared this project's pre-GST selling price against GST-inclusive MRP. Public product/detail/cart unit prices now display GST-inclusive prices using the existing tax rate. MRP discount comparisons therefore use the same tax basis. Tax rates are eager loaded to avoid per-card queries.
11. **Ambiguous cart totals.** The summary now shows the existing calculation engine's subtotal after product discounts, order discounts, GST and rounding separately. Values are also refreshed after AJAX quantity changes. The components reconcile exactly to the existing rounded order total; no pricing formula was changed. Updated shop scripts receive asset versions to avoid stale browser code.
12. **Empty badges visible in the UI.** Bootstrap `d-flex`/`d-inline-flex !important` defeated Alpine's hiding of empty grade/weight badges. These use `x-show.important`; an invalid theme text class and repeated image-error fallback assignment were also corrected.

## Existing business rules retained

The order, inventory, wallet, tax, discount, refund and payment service implementations were not changed. Stock reservation still occurs through the existing confirmation workflow; the storefront remains subject to the existing single-warehouse fulfilment rule and rounding. Customer/staff authentication and state-wide record visibility remain as implemented. New shop controls use existing product permissions; no permission grants or role assignments were changed.

No new migration was necessary. The earlier additive storefront migration already contains the required flags and quantity limits. The shop URL is now centralized in `config/storefront.php` and reused by the separate app.

The existing full-list admin API and client-side table contract are preserved. No forced pagination or altered statistics semantics were introduced.

## Verification

- **22 catalog regression checks passed** using a unique disposable MySQL schema and a temporary upload disk. Covered real create/edit/duplicate APIs, stable slugs, shared-image cleanup, related-write rollback, legacy-client compatibility, quantity/percentage validation, bulk shop permissions, list visibility predicates, deleted slug collisions, CSV import/export and malformed input.
- **42 storefront integration checks passed** in a different disposable schema. Existing OTP, registration, address ownership, checkout, duplicate/stale retries, stock rejection, cancellation and staff return integration remain covered. Added GST-inclusive display and exact cart-summary reconciliation checks.
- JavaScript tests passed for form defaults/edit mapping, private copies, stale edit responses, shop filters and bulk visibility request bodies.
- Existing permission, route and response contracts passed: **425 permissions / 591 route gates / 622 admin routes / 1329 application method bodies**.
- Production Vite build passed. Its pre-existing bundle-size advisory remains.
- PHP syntax, Blade compilation and JavaScript syntax checks passed.
- Read-only browser preview used an unsaved synthetic display identity and synthetic product API responses. Verified shop filtering and bulk controls, light/dark themes, and a 390px mobile viewport. No real admin sign-in or live product mutation was used for browser checks.
- Screenshots: `catalog-storefront-controls.png`, `catalog-storefront-dark.png`, `catalog-storefront-mobile.png`.

Disposable test schemas/files are removed after execution. Real SMTP delivery remains outside these regression checks.
