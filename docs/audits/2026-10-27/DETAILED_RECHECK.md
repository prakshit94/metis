# Detailed follow-up verification — 28 October 2026

This pass expanded the earlier mobile and business checks. It found additional defects, including regressions in the earlier audit changes. All database work uses randomly named disposable MySQL schemas; configured application data and real carrier services are not used.

## Corrections

| Area | Confirmed defect and correction |
| --- | --- |
| Order/return payloads | Six misplaced `Middleware` objects had been inserted into business arrays during the earlier fixes. Removed those entries from order statistics, warehouse statistics, carrier/provider data, order-form stock data and QC-import defaults. The actual controller middleware remains registered. Added an AST check across application methods to detect this mistake. |
| Operational CSV visibility | Delivery, return and QC CSV lookups bypassed the record visibility enforced by direct endpoints. Delivery/return lookups now reuse `RecordVisibility`; QC imports call the same visibility assertion as route middleware before previewing or processing a return. Authorized processing still uses the existing inventory services. |
| Mobile CSV errors | Empty delivery/return CSV files and processing errors could return browser redirects. API/JSON requests now receive JSON errors; browser navigation retains its existing responses. |
| QC upload validation | A plain text `file` parameter passed validation and then caused a null-file method call. The existing size rule now also requires an uploaded file. |
| Payment CSV parsing | Line-based parsing broke quoted multiline fields, and a UTF-8 BOM prevented matching the first header. Parsing now uses `fgetcsv` and strips the BOM from the first header. Payment calculations and settlement rules are unchanged. |
| Native chat authentication | Chat API URLs were registered under browser authentication. Moved the existing URI/handler group into the Sanctum API group, retaining the chat throttle and supporting both native tokens and stateful browser sessions. |
| Complaint summaries/pagination | Complaint list totals included complaints hidden by the assignee scope. Aggregate queries now apply the same visibility rule as the list and dedicated stats endpoint. Page size is bounded to 1–100, preventing invalid zero/negative pagination and unbounded requests. |
| Chat attachment authorization | Uploads were written before the service checked conversation membership. The same membership check now runs before storage writes; non-members cannot leave unauthorized attachments behind. |
| Order editing | Optional complaint-count loading dereferenced a missing customer relation, crashing edit options for orders without a current linked customer. The lookup is now null-safe; pricing and required customer/address validation on order writes remain unchanged. |
| Maintenance access | `/clear-views` was public. It now requires authentication, the existing `settings-edit` permission and a request throttle. |

## Test isolation correction

The earlier HTTP helper forgot guard instances but did not restore the default guard between requests. In the reused test kernel, a prior Sanctum request could therefore mask a later web-only route's authentication requirement. Both HTTP helpers now reset the default guard before every request. The native chat exchange is tested with this stricter isolation.

The first expanded read sweep also identified missing complaint permissions in the disposable fixture. The fixture now creates/grants its existing seeded permission before exercising complaint administration; this did not require changing the application's permission policy.

## Verification coverage

- Existing pricing, promotions/gifts, wallet, payments/refunds/credits, returns/QC, stock transfer, deletion/reservation and concurrency checks remain included.
- Static application API GET routes are exercised through the real HTTP kernel with bearer tokens. Required-query validation responses are distinguished from server errors; this is a reachability/response check, not every combination of business filters.
- Actual create/read/update/delete flows cover brands, categories, UOM, HSN codes, tax rates, departments and holidays.
- Multipart CSV tests cover empty files, malformed uploads, UTF-8 BOM/multiline payment fields, unauthorized preview/commit attempts and authorized QC previews.
- A native chat exchange covers creation, sending, reading, sender-only editing, read receipts, outsider rejection, attachment authorization before storage, and soft deletion.
- Order preview/create/edit-option checks verify authoritative pricing and rejection/rollback of attempted lifecycle bypass.
- Disabled/suspended bearer users are checked for denial and token revocation.
- All 17 report streams, CSV templates, target mutations and permission checks remain in the suite.
- Route contracts, AST response checks, PHP syntax, theme behavior, responsive shared-layout checks, Blade compilation and the production build are checked separately.

Final results: **214 regression assertions passed**, including the original business checks and the expanded workflows above.

- **73 API read endpoints** exercised; individual statuses are saved in [endpoint evidence](recheck-endpoints.json). Some endpoints require query parameters and correctly returned validation responses in this sweep.
- **601 route contracts** passed; the current inventory contains **417 application Sanctum routes**, including the migrated chat endpoints.
- **115 migrations** passed fresh installation, complete rollback with foreign keys enabled, and repeat migration/rollback.
- **1,233 application method bodies** passed the misplaced-middleware AST check. This structural check does not prove every method's business behavior.
- **397 PHP files** passed syntax checks; the final order edit null-safety change and test updates also passed explicit syntax checks.
- **8 responsive browser checks** passed at 320, 375, 768 and 1280 CSS-pixel widths in both themes.
- Theme tests, production Vite build, Blade compilation and whitespace checks passed. The build retains its existing large-chunk warning.
- Native invoice generation returned an actual PDF document; this checks generation and download rather than exhaustive print-layout review.

Successful run logs: `/tmp/metis-completion-checks.log`, `/tmp/metis-detailed-responsive.log`, `/tmp/metis-detailed-build.log`, `/tmp/metis-pending-blade.log`.

No new database migration was needed for these follow-up fixes. No production records were changed. Pricing, financial settlement, inventory transitions and target calculations continue to use the existing business services.

 This remains code and isolated integration verification; real carrier credentials, production-scale datasets, native app screens, physical devices and every possible user/data combination are not certified.
