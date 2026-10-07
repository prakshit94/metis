# K6 performance results

Runs were against the local Laravel instance with four PHP development-server workers on the 4-core workspace host. These results are local capacity observations, not production capacity guarantees.

## Coverage and safety

- All 63 configured static authenticated pages completed the route sweep and returned HTML.
- The route manifest contains 115 of 116 registered GET API routes. The live carrier tracking lookup is excluded because it can make external provider calls.
- API load tests use GET only. Export/PDF/download endpoints are each checked once in the route sweep and excluded from repeated stress traffic.
- POST, PUT, PATCH, and DELETE APIs were not invoked; no disposable database was configured, and these endpoints can mutate business data.
- Parameterized API routes use ID `1` unless configured otherwise. Missing records can return 404, so full record-level checks require representative fixture IDs.
- A static route-action audit found three registered write routes that point to missing controller methods: `POST /api/orders/import`, `POST /api/payments`, and `POST /api/returns/{orderReturn}/process`. They were not remapped because their intended write workflows are ambiguous and changing them could alter business behavior.

## Results

| Profile | Coverage/load | Requests | HTTP failures | p95 | p99 | Result |
|---|---:|---:|---:|---:|---:|---|
| Pages, normal | 10 VUs | 982 | 0% | 0.77 s | 1.28 s | Passed thresholds |
| Pages, stress | 40 VUs peak | 1,639 | 0% | 4.68 s | 5.40 s | Latency thresholds exceeded |
| API route sweep | 115 GET routes, one pass | 118 total including setup | 0% | 0.43 s | 0.78 s | All routes returned below 500 |
| APIs, stress | 40 VUs peak | 3,116 | 0% | 2.43 s | 3.13 s | Passed thresholds |

At 20 page VUs during the page stress ramp, p95 was 2.41 s and p99 was 2.68 s. At 40 VUs, latency rose broadly across pages; the slowest median pages included orders, payments, order reasons, order creation, messages, and procurement. No route showed a uniquely large slowdown, suggesting host/app capacity saturation rather than one isolated page handler.

API throughput was about 17.0 requests/sec at 20 VUs and 17.2 requests/sec at 40 VUs, while p95 rose from 1.47 s to 2.43 s. This local run did not gain material throughput from doubling concurrency.

## Fixes made

- Village CSV exports used `cursor()` while accessing eager-loaded relationships, producing per-village relation queries. Both full and selected exports now use bounded `lazyById(500)` chunks so mappings and services are eager-loaded per chunk. The initial single-worker sweep timed out at 60 s on the village export; after this fix and restarting the dev server with four workers, the route sweep max was 4.14 s and the later full sweep p99 was 0.78 s. The before/after also reflects the server worker change.
- Fixed Category implicit route-model binding and added the missing read handlers for holidays, leave balances, suppliers, refunds, and invoice PDFs. Added JSON output for the referral-program API route. These routes returned 500 during the first sweep and no longer return server errors.
- Repeated stress traffic excludes large export/PDF downloads; each is still requested once by the route sweep.

## Reports

- [Page 10-VU summary](pages-summary-fixed.json)
- [Page 40-VU summary](pages-summary-40vus.json)
- [Page 40-VU per-request samples](pages-40vus.ndjson)
- [API route sweep summary](apis-sweep-fixed.json)
- [API 20-VU summary](apis-summary-fixed.json)
- [API 40-VU summary](apis-summary-40vus.json)
- [API route list](k6/api-routes.json)

## Fresh local rerun (2026-10-07)

The local MySQL service was started, all migrations were confirmed present, and Laravel was run with four PHP development-server workers. Tests used the provided local admin account. K6 tested authenticated reads; it did not submit order, payment, return, inventory, user, or other business-changing forms/API requests. The provided account was logged in several times; this app invalidates that account’s existing sessions and API tokens on login, so other sessions for it may need to sign in again. Login history was updated.

| Profile | Coverage/load | Requests | HTTP failures | p95 | p99 | Result |
|---|---:|---:|---:|---:|---:|---|
| Pages, stress | 63 page routes + 40 peak VUs | 1,856 | 0% | 3.37 s | 3.68 s | All checks passed; p95 target (2.5 s) exceeded |
| API route sweep + stress | 115 GET routes + 40 peak VUs | 3,093 | 0% | 2.66 s | 3.93 s | Passed thresholds |
| Order history workflow | Customer 80729 and order 33115; 10 page VUs | 1,128 | 0% | 0.95 s | 1.65 s | All 2,119 checks passed |

The page stress profile sustained about 10.2 HTTP requests/sec at 40 VUs. API stress sustained about 17.1 requests/sec at 40 VUs. The measured page latency begins to miss the chosen 2.5 s p95 target under the 40-VU profile; the API profile stayed within its 5 s p95 target. Therefore, the observed load is 40 concurrent VUs per profile with zero HTTP errors, while the page profile is latency-limited at that level. This is a local development-server/database measurement, not a production capacity limit or a concurrency number for simultaneous writes.

The API route sweep may return 404 or 422 for placeholder resource IDs; this run reported no 5xx responses. The order-history path used valid existing test records and remained read-only.

Fresh K6 summaries: [pages-summary-current.json](pages-summary-current.json), [apis-summary-current.json](apis-summary-current.json), and [pages-order-history-current.json](pages-order-history-current.json).
