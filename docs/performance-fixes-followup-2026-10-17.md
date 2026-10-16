# Performance fixes follow-up — 17 October 2026

This pass addresses additional confirmed repeated query work. Existing business workflows, permission checks, financial calculations, model events, route contracts, response fields, and CSV formats are retained. It does not implement the previously documented missing write handlers or change lifecycle transitions.

## Changes

| Read path | Previous repeated work | New behavior |
| --- | --- | --- |
| Full and selected order CSV | Cursor ignored declared eager loads; relationship queries repeated for each order/item | Keep the original cursor and its sorting; eager-load its declared relationships in batches of 500 |
| Full and selected complaint CSV | Per-complaint order/customer/assignee reads | Same cursor, scopes, formatter and selected-ID checks; relation loading in batches of 500 |
| Return-list statistics | Ten separate aggregates | One conditional aggregate over the same query, preserving filters, pagination behavior and financial response types |
| Complaint statistics | Five global counts | One global conditional aggregate; list/assignment filters still do not change these global statistics |
| Supplier page statistics | Three counts | One aggregate, preserving soft deletes and `status != active` semantics |
| Offer statistics | Six counts | One aggregate, preserving independent/overlapping categories and strict date comparisons |
| Call-tag statistics | Four counts | One aggregate, preserving active/inactive values, tag levels and soft deletes |
| Purchase-order page statistics | Three counts | One aggregate over the same state/warehouse and soft-delete scope |
| Goods-receipt page totals/month | Two receipt counts | One aggregate with the same month boundaries; discrepancy counting and pending placeholder remain unchanged |
| API documentation | Unwarmed requests repeatedly regenerate the specification | Laravel `optimize` now invokes Scramble's supported `scramble:cache`; `optimize:clear` invalidates it with `scramble:clear` |

The export change intentionally keeps the original primary cursor rather than using offset pagination: this preserves primary row order, including existing sorts, and avoids introducing batch-boundary omissions with tied sort values. Only related models are loaded in batches. PDO primary-result buffering is unchanged; this is not a claim of constant memory for arbitrary exports.

The documentation change registers deployment commands only. It does not bypass documentation authorization or automatically cache per-user business responses. Deployments must run `php artisan optimize` to warm the current schema, and repeat it after source changes. No production deployment or persistent production cache warming was performed by this review.

## Verified behavior and query counts

The disposable MySQL regression suite compares complete CSV bytes against the unchanged original formatter fed by the old cursor implementation. It also checks selected IDs, complaint assignment/status visibility, requested order sorting, missing related customers, soft deletes, empty data, exact date boundaries, independent offer categories, state restrictions, and exact financial value types.

Measured examples from those fixtures:

- Order CSV: old cursor issued 163 queries for 30 orders; the optimized full export issued 6 queries for 90 orders.
- Complaint CSV: 81 queries reduced to 4 for the same 30 records, with identical CSV bytes.
- Complaint batch boundary: 535 records exported with 7 queries, without missing or duplicate records.
- Sorted order batch boundary: 505 records exported with 9 queries, with the exact requested order and no missing or duplicate records.

Return statistics are compared directly with the original ten aggregate expressions for default, status-filtered, empty-search, missing-status and later-page cases. The existing pagination/statistics contract is preserved, including its offset behavior; this pass does not reinterpret financial or list semantics.

Documentation verification uses a uniquely named in-memory cache, real generation, and a counting generator. It checks that cache warming generates once, two cached reads return the same public JSON schema without regeneration, and clearing invalidates the result. It does not write to the application's persistent documentation cache.

## Reproduce

```sh
php tests/regression/query_performance.php
php tests/regression/route_contracts.php
METIS_K6_ISOLATED=1 METIS_K6_RUN_LABEL=followup-final K6_SWEEP_ONLY=true \
  php tests/regression/query_performance.php
```

The expanded synthetic HTTP fixture now includes 535 complaints, 505 additional orders, suppliers, purchase orders, offers, call tags and receipts. Its latency is not directly comparable with the smaller earlier stress fixture. The follow-up HTTP run is a route sweep, not a new concurrency-capacity measurement. The prior 40-VU page p95 limit remains unproven after these changes; production CPU improvement requires equivalent before/after production-like measurements.

An initial sweep exposed missing timestamps in synthetic purchase-order/receipt inserts, which caused views to fail. Those test inserts now include the timestamps that normal Eloquent writes supply. This was corrected in fixtures, not used to justify changing business display or reporting rules.

## Final verification results

The complete isolated query regression suite passed, including the new follow-up checks. Documentation generation took 6,298 ms; two cached schema reads together took 246 ms, with exactly one generation. These are local observations with an in-memory test cache, not production HTTP timings.

| k6 route sweep | Registered read paths sampled | HTTP requests including setup | Check pass rate | p95 | p99 | Result |
| --- | ---: | ---: | ---: | ---: | ---: | --- |
| site | 63 | 66 | 100% | 336.6 ms | 440.5 ms | Passed |
| apis | 118 | 121 | 100% | 210.8 ms | 4209.2 ms | Passed |

Both k6 processes exited zero. There were no 5xx responses. Intended target-page redirects and allowed missing-fixture/validation responses remain explicitly recorded. See `tests/performance/results/2026-10-17-isolated-followup-final/report.md` for per-route status and latency evidence.

All application PHP syntax checks, route-contract regression checks, k6 script inspection and `git diff --check` passed. No new production-capacity or incident-resolution claim is inferred from this sweep.
