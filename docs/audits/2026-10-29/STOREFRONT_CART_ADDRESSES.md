# Storefront cart, profile and address review

Scope: compared admin order creation, StoreOrderRequest, shared OrderService pricing/order snapshots, storefront cart mutation and checkout, profile update, customer address endpoints, and employee Address Details fields. No live customer/order was modified during verification.

## Changes

- Add/edit address dialog shared between cart and profile; profile now contains the same account-owned address book used for shipping and billing.
- Address Details fields mirror the employee vocabulary: lines 1/2, village/city, post office, taluka, district, state and PIN. Village lookup remains available; manual addresses remain supported for existing storefront compatibility. Shop was added to the existing label choices.
- Updates are scoped through the authenticated customer's active address relation. IDs, customer assignment and status cannot be supplied as editable fields. Account eligibility and customer state are rechecked under transaction locks.
- Village directory selections are resolved server-side rather than trusting client metadata. Manual edits to geographic fields unlink the directory ID to prevent stale references.
- Repeated saves of an existing default exclude that address from the bulk reset, preventing a model dirty-state issue that could otherwise clear its default flag.
- Editing an address preserves shipping/billing radio selections. Newly added addresses retain the existing select-for-delivery behavior. Dialog errors, busy state, cancellation and focus return work for both page contexts.
- Address save responses fetch the address collection once and reuse it for shipping, billing and profile fragments.
- Checkout now forwards calculated applied/selected offer IDs and cashback into the existing order service. Previously totals could include an offer while order metadata and usage accounting were omitted.

## Preserved boundaries

Pricing, GST, discounts, rounding, stock checks, pending confirmation lifecycle, warehouse selection, state/office numbering, wallet and payment policy remain in the existing services. Profile still edits personal contact fields only; email, account linkage and access remain server-owned. Saved address edits do not rewrite historical order snapshots. No migration is required.

The storefront continues to allow complete manual addresses, unlike the admin form's village-only order validation; changing that policy would invalidate existing storefront addresses and was intentionally outside this compatibility update. Customer-facing address controls do not expose employee-only status or access settings.

## Verification

Disposable MySQL integration suite with auditing/activity enabled and fake mail, including rendering cart/profile/modal views, cross-account address denial, default uniqueness, repeated default saves, canonical village fields, immutable order snapshots, profile field protection, checkout offer metadata/usage, server totals, stale quotes, unavailable products, stock failures, idempotency, OTP security and order lifecycle. PHP and JavaScript syntax checks and route registration also checked. No real email delivery or live customer login/order was used. Automated rendering is not a substitute for device/browser interaction testing.

Final result: **55 integration checks passed**. Browser inspection using an unsaved synthetic customer verified prefilled edit mode, cancellation/focus return, and clean add-mode reset. Preview screenshot: `address-editor-preview.png`. Device-specific mobile interaction testing remains for user acceptance.
