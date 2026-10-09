# API coverage audit — 2026-10-09

## Inventory

Compared Laravel's registered route table with `tests/performance/k6/api-routes.json`.

- Laravel registered 368 route entries under the `api` route-list filter.
- The route table contains 119 GET/HEAD endpoints. One external live-tracking endpoint was excluded because it can call a shipping provider.
- The k6 inventory now contains all remaining 118 GET endpoints, including `docs/api`, `docs/api.json`, and the authenticated `products-search-api` helper. URI and controller-action comparison has no missing routes, extra routes, or action mismatches.
- The route table also contains POST, PUT, PATCH, and DELETE operations. They were inventoried but not invoked because many mutate orders, stock, users, financials, or configuration.

## Read-only sweep

k6 completed all 118 GET route checks with zero failed checks and no server errors. The route sweep took 22.2 seconds; average response time was 187 ms, p95 was 383 ms, and p99 was 4.42 seconds. Some resource routes returned client errors for placeholder IDs or missing query parameters (for example 404/422); those responses do not verify resource-specific behavior.

The external live-tracking route was not called. GET export/download endpoints were hit once by the sweep but excluded from repeated load traffic. No business write operation was submitted.

## Files

- `../k6/api-routes.json` is the updated GET route inventory.
- `2026-10-09-api-full-sweep.json` contains the sanitized k6 sweep summary.
- `2026-10-09-load-test.md` contains the prior 68-VU page/API performance results and limits.
