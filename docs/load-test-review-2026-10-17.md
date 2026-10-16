# Load and CPU investigation — 17 October 2026

## Scope and evidence

This review combines a registered-route inventory, source inspection of query/export/authentication paths, isolated MySQL regression tests, and authenticated k6 HTTP read profiles. The workspace already contained substantial query optimizations and other uncommitted work. Those changes were preserved; earlier measured query reductions are documented in `query-performance-review.md` and are not new speedup claims from this run.

- 543 registered route entries were inspected for public controller action availability.
- 60 application controller classes are referenced by those entries. The per-controller inventory is in `controller-load-review-2026-10-17.md`; the machine-readable inventory includes route methods, actions, and middleware.
- The existing k6 manifest contains 118 read routes: 115 API-prefixed routes, two documentation routes, and one product-search helper. The registered API GET surface has 116 entries. The excluded route is live carrier tracking, which can call an external provider.
- The page sweep contains 63 authenticated static pages.
- These profiles cover route defaults and the selected fixtures. They do not cover every filter combination, browser interaction, role, lifecycle state, or write workflow.

## Final bounded stress results

| Profile | Peak VUs | Requests | Checks | p95 | p99 | Result |
| --- | ---: | ---: | --- | ---: | ---: | --- |
| Pages | 40 | 2,045 | 4,084/4,084 passed | 2.725 s | 2.963 s | p95 target of 2.5 s exceeded |
| API reads | 20 | 3,692 | All passed | 0.862 s | 1.137 s | Passed thresholds |

All 63 configured pages and all 118 configured read routes were sampled. There were no 5xx responses or authentication failures. The page traffic includes 51 intended target-alias redirects; the API traffic includes 495 HTTP 404 and 41 HTTP 422 responses, mainly absent synthetic records or deliberately omitted validation inputs. Expected client errors count as accepted handling, not successful detail/workflow coverage. Both corrected exports returned 200; bulk print returned the required validation 422 without generating invoices.

PHP process-group CPU averaged 122% of one core for pages (223.18 CPU seconds over 182.91 wall seconds) and 120% for APIs (219.36 over 182.77 seconds). Peak summed RSS was 318.0 MiB and 461.9 MiB respectively; shared pages may be counted repeatedly. These are whole-profile averages, not peak CPU measurements, and exclude database CPU. There is no equivalent pre-change baseline from which to claim a production CPU reduction.

The API sweep still included documentation generation once: HTML took 8.1 seconds and JSON took 11.6 seconds. The earlier diagnostic run repeated those routes, had p99 of 9.93 seconds, and failed its latency threshold. The final ordinary API load excludes repeated docs generation while retaining its one-time check; the traffic mix changed, so the two profiles are not an application before/after speed comparison.

The earlier normal page run returned the correct target redirects but failed its then-stricter checks; the corrected stress run validates those exact redirects. The overall final harness exits nonzero because the page p95 target is still exceeded. This is preserved as a measured capacity limit, not hidden by increasing its threshold.

Detailed samples and route-level results: `tests/performance/results/2026-10-17-isolated-stress/report.md`. The earlier normal diagnostics are retained separately in `2026-10-17-isolated/report.md`.

## New application changes

### Product-sales CSV export

The export performed one grouped sales query followed by a stock SUM query for every product row. It now joins stock totals grouped by product. Using MAX of that single grouped stock value preserves sales quantities and revenue when a product has multiple order items or warehouse stock rows. Products with no stock still produce zero. The existing CSV headers, order, date predicates, and reporting scope are retained.

Regression coverage creates multiple warehouse rows for each product, checks unchanged quantity/revenue/stock values, includes a sold product without stock, checks that every sold product is present, and requires exactly one query for the export. The old implementation used one additional query per exported product.

### Regional-sales and audit-trail CSV date filters

Both streaming closures referenced date variables absent from their capture lists. The closures now capture the supplied dates. This fixes undefined-variable handling and enables the existing date predicates. No replacement date interpretation was introduced. Regression checks convert warnings to exceptions and exercise an out-of-range reporting period.

### Audit-log statistics

Four separate COUNT queries are combined into one conditional aggregate. Statistics still describe the complete audit dataset, while event/search filters apply to the list. Counts remain integers, including zero on an empty dataset, and other audit events still contribute to the total. Regression checks cover empty results, created/updated/deleted/restored events, and an event-filtered list.

## Test reliability changes

The old page suite followed redirects and accepted any HTTP 200 HTML, so an expired or unsuccessful login could produce misleading passes by testing the login page repeatedly. Page requests and the setup dashboard probe now disable redirects. Both suites reject a login POST that redirects back to login. The API request checks reject status zero, authentication failures, redirects, and server errors. Expected 400/404/422 responses remain visible in the per-route report and do not prove successful feature operation.

The synthetic regression suite intentionally deactivates a user while testing activity ordering. The HTTP harness explicitly reactivates its browser user afterward. It uses a separate synthetic bearer-token user because the application's login process revokes existing tokens. Required fixture complaint permissions are present; their initial absence caused test-fixture failures, not a proven production defect.

The runner creates a random temporary database, copies table definitions only, uses synthetic business records, launches a loopback HTTP server, records k6 samples and PHP resource counters, terminates the server group, and drops the temporary database. Existing application records and carrier settings are not copied or mutated by the fixture setup. Login, attendance, session, token, permission, and test business writes are confined to that database.

## Verification

- `php tests/regression/query_performance.php`: financial totals and response types, soft deletes, overpayments, customer ownership/state visibility, user team intersections, lifecycle labels, query budgets, date boundaries, mixed morph aliases, chat aggregate cache coverage, original ledger CSV format, and the new export/audit checks.
- `php tests/regression/schema_repairs.php`: fresh migrations, additive/repeat schema repair, shipment updates under strict attributes, independently rolled-back failed bulk orders, ledger filters, and payments with incomplete optional accounting tables.
- `php tests/regression/warehouse_overview.php`: warehouse totals, returns across statuses, duplicate related records, stock and delivery-attempt splits, unassigned groups, and filters.
- PHP syntax checks, k6 script inspection, and `git diff --check`.

HTTP results, route-level status counts, latency, and PHP resource observations are in the dated `tests/performance/results/*-isolated*/report.md` reports. Initial failed harness runs and the redirect-following page run are superseded; their numbers must not be treated as successful authenticated coverage.

## Unresolved route contracts

These registered write routes reference methods that do not exist:

| Route | Missing action |
| --- | --- |
| `POST /api/orders/import` | `OrderController::bulkImport` |
| `POST /api/payments` | `PaymentController::store` |
| `POST /api/returns/{orderReturn}/process` | `OrderReturnController::process` |

No replacement business workflow was invented. Import, payment creation, and return processing have financial/inventory consequences; their payload contracts and intended service transitions must be established before implementing these missing handlers. The inventory is a static confirmation, not an HTTP write test.

## CPU and coverage limits

The new changes remove repeated database work, but this run has no equivalent production before/after CPU baseline. PHP CPU observations are local process-group CPU seconds and peak summed RSS. Shared memory pages can be counted more than once. Database, browser, queue-worker, carrier-provider, and production PHP-FPM CPU are not measured. Local PHP development-server results do not establish deployment capacity.

The HTTP fixture contains tens of users/customers and around ninety orders; results do not predict behavior with the production database size. Large unpaginated responses, exports, leading-wildcard searches, and browser polling remain workload risks. The users page polls recent activity every three seconds; k6 HTML requests do not execute that JavaScript.

The HTTP profiles exercise reads. They do not establish load safety or correctness for order placement, inventory reservation, dispatch, refunds, payment reversal, return financials, concurrent edits, imports, webhooks, or external-provider failures. Selected service invariants pass the separate regression suites, but comprehensive write HTTP load tests require additional lifecycle-specific synthetic fixtures and provider stubs. No claim is made that every feature is load-tested or that the production high-CPU incident is resolved.

## Route precedence fixes found during HTTP review

The initial read sweep returned 404 for orders export, order bulk print, and complaints export. Router matching confirmed that the earlier `/{order}` / `/{complaint}` routes consumed those literal path segments as resource IDs. The three static GET registrations now precede their parameterized detail routes. Their controllers, names, permissions, and handler logic are unchanged. `tests/regression/route_contracts.php` verifies all three matches plus unchanged numeric order/complaint detail matching. A static match audit of every literal registered path reports no remaining shadowed handlers.

The API k6 checks now specifically require HTTP 200 for the two exports. Bulk print is intentionally sent without IDs/type and must return validation HTTP 422: its handler can generate missing invoices when valid inputs are supplied, so this read-focused suite does not execute that side effect.

## Module review map

| Area | Evidence checked | Remaining limit |
| --- | --- | --- |
| Orders and invoices | List query budgets, lifecycle labels, complete dashboard order list, soft deletes, totals, failed bulk rollback, export/print route matching | No concurrent placement, dispatch, or invoice-generation HTTP write load |
| Customers | Batched balances, per-invoice overpayment clamping, completed transactions, owner/state visibility, address filtering | Large-data search and customer mutation traffic are not characterized |
| Payments and refunds | Completed/pending/deleted handling, exact response types, optional accounting schema behavior | No payment-provider integration or concurrent reversal/refund traffic |
| Warehouses and inventory | Stock exceptions, multi-row join multiplication, return/delivery-attempt grouping, unassigned warehouses, CSV stock totals | No simultaneous stock reservations/transfers under HTTP write load |
| Procurement | Read route sweep, default list paths, missing-record handling | Sparse fixtures; purchase-order/receipt detail/PDF workflows can return 404 |
| Catalog | List/helper route sweep and synthetic product/stock export fixture | Not every attribute/variant combination is seeded |
| HR and users | Team intersections, activity ordering, date ranges, read route sweep | Missing leave/holiday/department resource fixtures limit detail coverage |
| Chat | Daily cache aggregate completeness, read route sweep, authentication | No message-write/attachment/group load; conversations can be absent |
| Shipping | Shipment serialization/date predicates, strict shipment update regression | Live carrier lookups and webhooks excluded |
| Reports and audit | Product-sales query budget, date callback fixes, CSV structure, audit global/filter statistics | No production-sized export or background-worker measurements |
| Targets and promotions | Registered read paths, intended target redirects, coupon aggregate regression | Target chain creation, offer/referral mutations not HTTP load-tested |
| Frontend | Page responses and source polling inspection | HTML requests do not exercise JavaScript, rendering, browser CPU, or asset downloads |

## Subsequent performance fixes

See `performance-fixes-followup-2026-10-17.md` for additional export batching, statistics consolidation, deployment documentation caching, and passing fresh page/API route sweeps. The earlier stress results above are retained as historical measurements; they were not rerun with an equivalent fixture for a before/after capacity comparison.
