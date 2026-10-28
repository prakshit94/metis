# Mobile support — 27 October 2026

The later [detailed recheck](DETAILED_RECHECK.md) includes corrections to chat authentication, import access checks and response data.

The previously identified business API gaps now have bearer-token routes and native response branches. Existing controllers, permission checks, pricing, target allocation, inventory transitions, financial services and transaction boundaries are reused. This is backend support plus shared responsive browser improvements; this repository contains no Android, iOS, Flutter or React Native client. Native screens and physical-device compatibility are not certified.

## Added native workflow coverage

All paths below start with `/api`. Existing core APIs remain available.

| Workflow | Endpoints and contract |
| --- | --- |
| Restored login context | `GET auth/me`: current user, effective roles/permissions, active permission team. Existing token replacement/expiry/logout behavior remains. |
| Order form data | `GET orders/create-options`, `GET orders/{order}/edit-options`: existing form data as JSON. Existing status restrictions and edit permissions apply. |
| Order helpers/documents | `GET orders/nearby-selling-insights`, `POST orders/{order}/generate-invoice`, `GET orders/{order}/invoice-pdf`, `GET orders/{order}/shipping-label`, `GET orders/{order}/cod-pdf`, `GET orders/{order}/receipt`. PDFs remain downloads, receipt is JSON, shipping label returns `{url}` or JSON error. |
| Order operational imports | `POST orders/import-deliver`, `POST orders/import-return`; matching `GET orders/import-deliver-template` and `GET orders/import-return-template`. Same existing upload validation and lifecycle services. |
| Customer workflows | `GET customers/search-by-phone`, `POST customers/{customer}/orders/place`, `GET credit-notes/customers/search`. Customer order responses/errors now have native JSON branches. |
| Payment import | `GET payments/import/sample`, `POST payments/import/preview`, `POST payments/import/process`. Existing invoice visibility, validation and settlement services remain. |
| Targets | `GET/POST targets`, `PUT/DELETE targets/{target}`, `POST targets/recalculate`, `POST targets/import`, `GET targets/import-template`, `GET targets/export`. Scoped listing, pace metrics, assignees, fiscal hierarchy and recalculation reuse browser implementation. Import returns warnings for skipped rows. |
| Reports | `GET reports` returns all 17 existing report types and their CSV download URLs. `GET reports/export?type=...&from=YYYY-MM-DD&to=YYYY-MM-DD` runs the existing streaming exporter. It uses the same report access and data rules as the web route. These endpoints do not invent a new report dataset or change calculations/filter behavior. |
| Analytics | `GET analytics/data` uses existing period/from/to/limit parameters. |
| Warehouse dashboard | `GET inventory/dashboard`, `GET inventory/dashboard/activities` return data rather than HTML. Existing warehouse-dashboard permission required. |
| Procurement | `GET procurement/purchase-orders/bulk-pdf?ids=1,2`, `POST procurement/purchase-orders/{order}/invoice`; same PDF generator, upload handler and warehouse authorization. Empty PDF selections return JSON 422. |
| File manager | `GET/DELETE files`, `POST files/upload`, `POST files/rename`, `GET files/download`, `POST files/download-zip`, `POST files/login-background`, `POST files/set-default-image`. Legacy URI paths are preserved, now under Sanctum with the existing settings permission. Stateful browser cookies remain supported through Sanctum. File preview retains its original public design. |
| Customer settings | `GET/POST customer-settings/{type}`, `PUT/DELETE customer-settings/{type}/{id}`, `PATCH customer-settings/{type}/{id}/toggle`; types are `crop`, `irrigation`, `land_unit`, `lead_source`. Existing view/edit permissions remain. |
| Referral administration | Existing create/update/toggle/delete API routes now return JSON on success; single-active-program and milestone rules unchanged. |
| Call-tag administration | Existing list route now returns hierarchy, statistics and row metadata as JSON. |
| Security | Session listing, terminate-other and lockdown no longer require a native request to contain a browser session. Existing token/session revocation and lockdown scope retained. |

## Client contract

- Authenticate through `POST /api/auth/login`, then send `Authorization: Bearer <token>`. JSON negotiation is applied to API routes even without `Accept`. No browser session or CSRF token is required for bearer-only requests.
- Restore identity/navigation grants through `GET /api/auth/me`. Enforce actions on the server even when the client hides controls.
- Handle JSON `401`, `403`, `404`, `422` and other error statuses. Do not parse redirects or downloads as JSON. Existing endpoints retain their response keys/status conventions to avoid breaking current consumers.
- Upload files as multipart form data using each existing handler's field names (`file` for import workflows). Use the original invoice upload handler's `invoice` field. Consult controller validation for optional fields.
- Save CSV/XLSX/PDF responses using their content headers. Shipping-label URLs are returned as JSON; the native client opens/downloads the provider resource.
- Chat uses `page`, `per_page`, `meta.has_more`; default 50, maximum 100. Other endpoint pagination keeps its existing parameters/caps.
- Existing single-account login replacement, attendance side effects, role/team visibility, pricing, promotions, wallet, invoice and return rules remain authoritative on the server.

## Responsive browser behavior

Shared small-screen styling adds narrow container spacing, constrained sidebars/modals, scrollable modal contents and data tables, wrapping pagination, responsive media, safe-area sidebar padding and 16px form controls to avoid iOS input zoom. Existing responsive navigation, grids and chat behavior remain. Theme colors use Bootstrap variables and the repaired light/dark manager. Invalid screenshot references were removed from the install manifest; existing icons and install behavior remain.

## Scope and evidence

See `mobile-route-coverage.json` for the complete current route inventory and `MOBILE_IMPLEMENTATION.md` for validation results. Route availability is not a substitute for exercising every real business scenario.

Browser page shells, UI demonstrations and browser-session impersonation remain browser workflows. Creating native impersonation tokens would change the security model, so that is not introduced. Native theme rendering/preferences, push notifications, offline synchronization, secure device storage, camera permissions and background execution require actual client code. This work does not claim to implement those app-side capabilities or change existing website behavior into an offline app.
