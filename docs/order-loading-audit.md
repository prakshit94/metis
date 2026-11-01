# Order creation loading audit

Scope: `/admin/orders/create?customer_id=84342`, the shared admin page shell,
application startup, product search, customer/address initialization, and the
backend order pricing/validation path. This is a focused audit of the reported
order-page failure, not a certification of every screen in the application.

## Finding and fix

The full-page loading overlay was dismissed only by `AdminApp.init()` in the
Vite application module. A failed or delayed module/import therefore kept the
logo covering server-rendered content indefinitely. The earlier product-search
timeout guarded a different spinner and did not fix this dependency.

The shared layout now includes a small inline script immediately after the loader.
It releases the overlay at document readiness `interactive`, before deferred module
startup. It also handles already-parsed/restored documents and removes the overlay
without relying on a CSS transition event. Event listeners are removed after use.
No additional asset request or frontend build is required for this Blade change.
The existing bundle's dismissal remains safe when the overlay is already removed.

## Backend review

Reviewed the order controller's customer/order loading, visibility and permission
checks; product-search routing and stock calculations; server quote construction,
quote validation, and transaction/locking paths in `OrderService`; and session
selection and cache-control middleware. No backend change is needed to release
this DOM overlay. Pricing, tax, inventory, offers, coupons, gifts, wallet handling,
record visibility and order lifecycle rules are unchanged by this fix.

## Validation

- Shared-loader regression: parsed document with no application bundle, late
  execution, restored document, missing overlay, cleanup without transition events.
- Product-loading regression: success, stalled request timeout, network failure,
  cancellation without replacing newer results.
- Actual browser failure fixture: missing application module; overlay not visible
  and page content visible. Temporary public fixture removed after verification.
- Authenticated browser: order page reload released the overlay and displayed
  customer information and the catalog, without captured console errors.
- `promotions_orders.php`: passed using a uniquely named disposable MySQL schema;
  covered canonical pricing/taxes, promotion targeting, gifts, personal rewards,
  quote changes, wallet rollback, concurrent activation, rendering, and migrations.
  Test schema is removed by the suite's shutdown handler; no live order was placed.
- Route and response contracts passed (648 routes, 1,424 application methods).
- Blade compilation and syntax checks passed for the reviewed order/catalog
  controllers and order service.

The indefinite failure did not occur during ordinary authenticated reloads.
The missing-module fixture verifies the dependency failure directly. The fix
removes the covering loader; it cannot repair an unavailable application bundle
or make interactive controls work when that bundle itself fails to load.
