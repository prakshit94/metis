# Query performance review — 2026-10-16

## Scope

Inventoried 543 registered routes, including 365 API route entries, across 63 application controllers. Reviewed database query patterns in controllers and their supporting services: repeated aggregates, queries inside loops, eager loading, date predicates, large ID lists, relationship scopes, and query-only calculations.

Changes preserve existing response structures, sorting, limits, soft-delete rules, permission scopes, lifecycle status rules, and financial calculations. There are no database schema changes or business-record updates in this review.

## Measured query counts

Measured against an isolated synthetic MySQL database with 30 users/customers, 90 orders/invoices/payments, item variants, refunds, returns, sessions, and village-service mappings. Counts include controller response serialization, where appended model attributes previously caused additional queries.

| Read path | Before | After |
| --- | ---: | ---: |
| Dashboard | 199 | 18 |
| Customer list | 38 | 5 |
| Invoice list | 41 | 9 |
| Payment list | 34 | 10 |
| Refund list | 21 | 10 |
| Shipment list | 21 | 9 |
| User list | 39 | 28 |
| Coupon list | 7 | 3 |
| Village list | 20 | 16 |
| Stock transfer list | 8 | 5 |
| Warehouse fulfillment metrics | 4 | 1 |

These are database round-trip counts for these fixtures, not production latency or capacity promises. Timing measurements were variable, so no speedup percentage or concurrency claim is inferred from them.

## Changes

- Batch customer outstanding balances in SQL, preserving per-invoice zero clamping, completed payments, and soft deletes. Combine customer statistics into one aggregate.
- Compute dashboard payment sums in SQL and join variant labels to the item query. Combine quantity and variant counts. Keep the complete existing dashboard order lists.
- Eager-load lifecycle relationships needed during invoice, payment, refund, and shipment serialization, hiding internal relationships to preserve JSON shape. Precompute invoice refund sums while preserving the original decimal-string/zero response types.
- Combine repeated fulfillment, payment, refund, coupon, and transfer aggregates. Aggregate requested returns instead of hydrating all return records.
- Replace user ID materialization with SQL subqueries/EXISTS; batch role loading. Preserve team/global scopes and online-first sorting.
- Batch village-service distributions in one grouped query with the same filtered village scope.
- Replace DATE/MONTH/YEAR wrappers with equivalent date ranges for shipping, attendance exports, goods receipts, and chat reads where the date boundaries are known. Existing indexes can serve those ranges.

## Reproduced query defects

- State-scoped customer lists queried a nonexistent polymorphic `addresses` table. The query now uses the actual `party_addresses` relationship.
- Stock-transfer activity searches queried `reference_no` instead of `transfer_no`. Searches now also handle both stored morph aliases and historical full class names, while retaining model query scopes.
- Chat cached order totals for only the first search's user subset under a shared key. The shared daily query now aggregates all creators for that date; each response still returns only its original visible user list.
- Group warehouse transfer directions so subsequent date/status predicates apply to both directions.

## Verification

Twenty-eight complete before/after responses matched exactly, including financial types and empty results, with deterministic seeds for the dashboard's existing random chart values. Three previously failing/incorrect scenarios were checked against expected results. Additional regression checks cover latest return state, delivery attempts, deleted invoices/payments, overpayments, default-address state filtering, team/global user filters, mixed morph references, month/date boundaries, and MySQL EXPLAIN index eligibility.

Run the saved regression suite with:

```sh
php tests/regression/query_performance.php
```

The suite creates a uniquely named temporary MySQL database, copies table definitions only, inserts synthetic records, runs its checks, and drops that database. It requires database creation privileges.

All application PHP files and the regression script pass PHP syntax checks; `git diff --check` passes.

## Limits

This was a source/query review and targeted database verification, not a full authenticated HTTP load test of every route. Write endpoints and external shipping-provider calls were not invoked. Leading-wildcard search semantics and existing all-record responses/exports remain unchanged; changing those to prefix search, new search infrastructure, pagination, or background exports would change the current interface and needs separate scope. Those paths can still consume substantial resources on large datasets.

## Compatibility follow-up

Re-reviewed the final diff and added checks for restricted customer ownership/state visibility, user team intersections, and exact equivalence between optimized financial getters and their original fallback queries, including pending/deleted transactions. The checks pass. Routes, permission middleware, and the inventory, order, invoice, and financial service implementations remain unchanged by query optimization.

The earlier stock-ledger repair now preserves the original eight CSV headers and column positions. Before/after balances remain blank because this schema never recorded them; the query supplies NULL aliases rather than inventing historical balances or querying nonexistent columns. Its date filters and CSV compatibility are covered by regression checks.

Intentional behavior corrections remain the documented query bug fixes and, from the earlier repair, rollback of a failed bulk item and safe handling of incomplete optional accounting schemas. These are deliberate fixes, not claims that every old failing behavior is retained. Full production end-to-end testing has not been performed.
