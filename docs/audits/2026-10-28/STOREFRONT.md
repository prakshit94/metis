# Reference storefront audit and integration

Reference: `/home/user/MYNEWPROJECT/Bootstrap-Admin-Template/laravel12-template`, `routes/shop.php`, `StorefrontController`, 17 shop Blade templates, shop CSS and three JavaScript assets. Destination selected by the user: `/home/user/metis/storefront`.

The separate Laravel 12 application preserves the reference layout, typography, colours, responsive breakpoints, navigation, hero, category strip, product cards, detail presentation, quantity controls, cart/address dialog, login method switch, registration, profile and order history. The reference stylesheet is copied unchanged, with its MIT license retained. Actual products/categories are from the current project; demo data is not copied into the live database.

## Required adaptations and fixes

- The current CRM stores customers in `parties` and addresses in `party_addresses`. Customer credentials belong in a separate table, with a distinct customer provider and cookie. Copying the reference controllers unchanged would query nonexistent tables and risk mixing staff/customer IDs.
- Public registration creates a fresh CRM customer. It does not attach a signup to another person's existing CRM record by matching email. The current `LeadSource` model does not support `withTrashed`; registration uses its actual supported API.
- Product publication is opt-in. New admin form controls use existing create/edit permissions; old API clients that omit the new fields preserve their current values. Min/max quantities are bounded and checked together. Existing product prices, discounts, taxes, inventory, status and warehouse settings are not replaced.
- The current product image is mapped to the reference image/gallery UI. Its public image route checks catalog visibility, numeric IDs, canonical file containment and image MIME type.
- The reference checkout inserts orders directly and has a different stock/branch model. This application instead calls the existing order calculation and creation service, retaining taxes, discounts, whole-rupee rounding, numbering, observer behavior and staff confirmation/reservation. Warehouse eligibility is fetched in batches before final locked checks. The current single-warehouse model remains in use.
- Shared domain observers and morph aliases are registered without importing staff login listeners. Customer sessions never populate the staff guard. Domain cache invalidation shares the admin cache; customer sessions and browser request locks remain separate.
- Checkout stores unique request keys, locks identities/products, checks current eligibility and address ownership, rejects changed quotes, and preserves a new cart when an old checkout is retried. Unpublished/invalid-quantity cart lines are removed with a visible notice rather than causing GET validation redirects.
- Returns call the existing return workflow with owned order items and server-selected remaining sold quantities. Staff receive real pending return records; cancellation calls the existing inventory cancellation service. Customer actions use optimistic status checks.
- Verification links are signed and expiring. Login OTPs are hashed, expire after five minutes, have five attempts, reject replay, preserve absolute expiry on resend, and are subject to send/route limits. Customer suspension/deletion is checked on authentication and sensitive writes.
- Customer email uses the existing master-admin encrypted shared mail settings and sender. Log/array delivery is rejected. Staff login link points to the current admin login rather than the reference's `/login.html`.
- Village lookup is bounded to 50 matches with at least three search characters and stale-response handling in the UI.

## Migration and existing application impact

Added and applied locally: `2026_10_28_180000_add_storefront_customer_access.php`.

New tables: `customer_accounts`, `storefront_checkouts`. New product columns: `storefront_visible` (false), `is_featured` (false), `min_order_qty` (1), `max_order_qty` (1000), plus a catalog lookup index. Historical migrations and existing customer columns remain unchanged. Existing rows are not repurposed or seeded with credentials.

Restrictive foreign keys preserve shopping records. Rollback refuses while shopping accounts/checkouts exist. Single and bulk permanent deletion of linked customers now returns a clear validation error; existing soft-delete/restoration remains available. Automatic approval review rejected a proposed cascade deletion alternative; restrictive keys were retained instead.

## Validation

- 40 disposable MySQL integration checks passed: full application migrations, account eligibility, registration, fake email construction, password provider, OTP attempt/replay/expiry, real order service creation/cancellation, duplicate checkout, address snapshots/ownership, discounts/GST/rounding, quote and stock changes, protected deletion/rollback, stale cart recovery, foreign-order actions, and existing return workflow.
- Admin product form JavaScript test: edit mapping and safe reset defaults.
- Existing contract checks: 425 permissions, 591 route gates, 621 admin routes, 1325 application method bodies.
- Production Vite build passed (existing bundle-size advisory remains).
- PHP syntax, standalone route registration (23 shop endpoints) and Blade compilation passed.
- Browser checked the actual local storefront home, password/email-code login switch and a 390px mobile viewport. Screenshots: `storefront-home.png`, `storefront-mobile.png`.
- Outbound email was faked. No real customer's login, OTP or transaction was used for tests. Disposable test schemas are dropped; the live database only receives the additive schema changes, with no sample orders/accounts created.

## Deliberate integration boundaries

This is a separate Laravel web application that shares the parent repository's installed dependencies, domain implementation, database and encryption key. It is not a fork of the business backend. See `storefront/README.md` for deployment/startup.

The current project has one main product image and one warehouse per order. Those constraints are preserved; the reference's separate image management and mixed-warehouse inventory architecture are not introduced. Customer orders have no staff creator, so the existing guest `ORD` numbering applies and staff state/office attribution is unchanged. Pending orders remain subject to staff confirmation. All products stay private until explicitly published in admin Products.
