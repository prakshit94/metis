# Metis Market — Laravel 12 storefront

Separate Laravel application in `/home/user/metis/storefront`, matching the reference project's `/shop` templates and CSS. The entry point, routes, controllers, views, customer guard, sessions and storage are separate from the admin application.

## Run

From the repository root, install the existing application's dependencies with `composer install` if needed. This storefront intentionally loads `../vendor/autoload.php` and the existing domain classes, configuration and database. It is a separate web application within the same repository, **not an independently deployable copy of the business backend**. Keep it alongside the admin application.

```bash
# From /home/user/metis, on a new deployment:
php storefront/artisan package:discover
php artisan migrate --force

# Start the separate application:
cd storefront
php artisan serve --host=127.0.0.1 --port=8001
```

Open <http://127.0.0.1:8001/shop>. The additive migration is already applied to the current local database.

Package discovery explicitly reads the parent dependency directory while retaining a separate storefront package cache. After updating the shared Composer dependencies, run `php storefront/artisan package:discover`.

The parent `.env` supplies the existing database, application encryption key and mail settings. Secrets are not copied into this folder. Optional `STOREFRONT_URL` and `STOREFRONT_STAFF_URL` values can be supplied as deployment environment variables or in the parent `.env`. Their local defaults are ports 8001 and 8000 respectively. Configure `STOREFRONT_URL` to the real public shop URL before sending verification links. Use a separate web-server document root pointing to `storefront/public` in production; keep the same shared domain/database deployment.

## Publish products

In the existing admin **Products** create/edit form, use **Customer storefront**:

- Visible in customer shop
- Featured pick
- Minimum/maximum quantity per order (1–1000)

The existing product-create/product-edit permissions protect these settings. Existing products remain private by default. Draft, inactive, deleted or SKU-disabled products are unavailable in the shop. Prices, taxes, product discounts, inventory settings and admin status handling are retained.

The server also offers trusted operator commands, run inside this folder:

```bash
php artisan shop:publish 123 --featured --min=1 --max=10
php artisan shop:publish 123 --hide
php artisan shop:account 1 --disable-login
php artisan shop:account 1 --enable-login --disable-ordering
```

The numeric account ID refers to `customer_accounts`, not staff users or customer/party IDs. Enabling login does not bypass email verification or CRM suspension. These are server operator commands, not public endpoints.

## Customer features

Catalog search, categories, sorting and pagination; product detail/gallery presentation; responsive cart controls; registration; signed email verification/resend; password or six-digit email-code login; saved shipping/billing addresses with village lookup; profile updates; COD checkout; private customer order history and updates; cancellation before dispatch and return requests after delivery. Return requests create the existing pending return and return-item records for staff approval/QC.

The current catalog has one main product image per product. That image is adapted to the reference gallery presentation; this change does not introduce a second image-management system. Product descriptions remain escaped text.

Email uses the encrypted shared delivery settings already managed by the master administrator on the admin Security page. Log/array transports are rejected for customer verification and OTP delivery. Real SMTP delivery was not exercised during automated checks; outbound transport was faked.

Customer identities use the existing `parties` and `party_addresses` tables. Public signup creates a new customer identity and never claims an existing CRM customer merely because its email matches. Shopping credentials and access flags are in `customer_accounts`; staff credentials, staff 2FA and staff sessions are separate. CRM deactivation, blacklisting and soft deletion immediately revoke shopping eligibility. Permanent deletion of customers linked to shopping accounts returns a validation error; soft deletion/restoration remain available.

## Existing business workflow

Checkout calls the existing `OrderService::recalculateAndValidate()` and `createOrder()` methods. It preserves tax/discount calculations, whole-rupee rounding and order numbering. Authenticated customers are never impersonated as staff creators. The existing service's guest prefix is `ORD`; staff state/office numbering is unchanged.

One existing active warehouse must be able to fulfil the complete bag, matching the current single-warehouse order model. Available stock and warehouse SKU settings are checked again during checkout. Stock remains reserved by the existing staff confirmation workflow, so pending storefront orders are subject to confirmation. There is no new stock reservation, payment, wallet or fulfilment engine. This intentionally adapts the reference's mixed-warehouse implementation to the existing backend.

Checkout uses server-owned prices, ownership-checked addresses, customer/account/product locks and unique request keys. Repeated placement never duplicates orders, stale retries do not clear a newer cart, and changed quotes require review. Address snapshots populate the existing admin order address columns. The customer's submitted contact name/phone are preserved with the checkout record. Admin orders and customer history use the same order records.

## Schema and verification

Only the new parent migration `2026_10_28_180000_add_storefront_customer_access.php` is added. Historical migrations and customer columns are not rewritten. It adds `customer_accounts`, `storefront_checkouts`, four product storefront settings and a catalog index. Foreign keys retain shopping history; rollback refuses while customer accounts or checkouts exist.

```bash
# Disposable MySQL server required; never uses the configured application's DB:
php storefront/tests/integration.php
node storefront/tests/product_settings.mjs
php tests/regression/permission_contracts.php
php tests/regression/route_contracts.php
```

The integration runner creates and drops a uniquely named disposable schema. It tests real domain order creation/cancellation with synthetic identities, fake email transport, OTP attempts/replay/expiry, quote changes, stock rejection, address ownership, totals, private actions, cart changes and protected migration rollback.
