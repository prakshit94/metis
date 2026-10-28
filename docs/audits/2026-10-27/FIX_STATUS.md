# Fix status — 27 October 2026

A later detailed recheck found and corrected additional issues. See [current recheck results](DETAILED_RECHECK.md).

Mobile support was expanded after this audit phase. See [mobile implementation and current validation](MOBILE_IMPLEMENTATION.md).

The 39 audit findings have corresponding code corrections. This document records implementation scope, not a guarantee that every application behavior is bug-free. No production deployment, live-data repair or real India Post request was performed.

## Finding-by-finding changes

| ID | Correction and verification |
| --- | --- |
| F01 | Configured bearer authentication, payload validation and stable event deduplication. Verified unauthorized and replayed callbacks; real sender configuration is still required. |
| F02 | Receipt permission and record visibility now protect reversal. Real unauthorized bearer request checked. |
| F03 | Transfer send/receive/cancel and bulk-specific delete/edit grants are enforced. Unauthorized bearer request checked. |
| F04 | Shared order visibility service, detail/bulk middleware and scoped financial queries preserve existing global-view exceptions. Own/other order and financial-list HTTP checks added. |
| F05 | Create/edit cannot jump into fulfillment/terminal states; dedicated existing lifecycle actions own those transitions. Rejected lifecycle bypass and ordinary pricing checks added. |
| F06 | Deletion rejects financial/fulfilled history; an unfulfilled confirmed order uses existing cancellation before deletion. Reservation release checked. |
| F07 | Transfers check physical minus reserved stock under lock. Invalid and valid stock-conserving transfer cases checked. |
| F08 | Locked return validation enforces eligible state and cumulative remaining quantities. Oversized and valid returns checked. |
| F09 | QC item IDs must be distinct and quantities bounded. Duplicate QC test added. |
| F10 | QC locks/reloads order, return and items before state changes. Two concurrent stale requests restock once. |
| F11 | QC IDs are scoped to the return; a complete, non-empty item set is required. Empty/duplicate/excess inputs rejected; zero received quantity remains valid. |
| F12 | Omitted notes/QC notes use null defaults. Valid return/QC requests omit these fields in regression checks. |
| F13 | Bulk payment changes throw validation inside a transaction. Mixed valid/invalid selection rolls back preceding updates. |
| F14 | Shared payment amendment locks invoice/payment and checks remaining balance/refunded state. Overpayment rejected; normal payment still marks paid. |
| F15 | Payment synchronization reloads the locked invoice and preserves cancellation; new payments on cancelled invoices are rejected. Regression checked. |
| F16 | Cash refunds and credit notes share locked order/return entitlement; pending obligations are consumed rather than duplicated. Refund-plus-credit cap checked. |
| F17 | Refund transitions recompute return summaries from ledger rows. Existing pending settlement and combined summaries checked. |
| F18 | Manual notes settle an exact matching pending refund under order/customer/refund locks, with a unique settlement link. Cross-customer/arbitrary credit rejected. Used settlements cannot be reopened/deleted through ordinary CRUD. |
| F19 | Wallet mutations read balances under row locks. Two concurrent orders redeem only the available funds; manual credit and return/cancellation paths also use the wallet lock. |
| F20 | AWBs use a committed, serialized allocation transaction shared with range administration, and persistence failures propagate. Concurrent AWB test uses no remote booking call; used ranges cannot be reassigned and overlaps are rejected. |
| F21 | Gift allowance is consumed per product/source across cart lines. Ordinary price, valid free gift and excessive gift tests added. |
| F22 | Carrier webhook/poller call the existing dispatch/delivery services. Verified inventory/lifecycle processing and once-only cashback on replay. System stock movements now use nullable actor IDs rather than invalid user 0. |
| F23 | Implemented payment creation, live order import, and a return process adapter. All route handlers reflect successfully; live-import server pricing is checked. Historical fulfillment import remains deliberately unsupported through CRUD. |
| F24 | Corrected resource parameter maps and return order_id adapter. Brand/transfer/return HTTP binding and all model-name route contracts checked. |
| F25 | Rollback supplies a supporting creator FK index before dropping the composite index. Full rollback/re-migrate/rollback checked. |
| F26 | Migration down methods remove the external users/departments FKs before dropping referenced tables. Full rollback checked with FK enforcement. |
| F27 | Removed PHP 8.3 typed constant syntax while preserving constants/values. Syntax validated on installed PHP 8.3; an actual PHP 8.2 runtime was not available. |
| F28 | Adding an already-active member preserves their role. Owner-demotion regression checked. |
| F29 | Header toggle uses the same theme manager/event as charts, keeping manager/Alpine state aligned. Unit/build/Blade checks; no authenticated browser visual certification. |
| F30 | Applying an OS theme no longer stores an explicit preference. OS-following and manual preference tests added. |
| F31 | Theme startup, header initialization and sidebar storage access tolerate blocked storage. Theme unit tests cover storage exceptions. |
| F32 | Financial list limits and department pagination are clamped. Native invoice list limit=0 checked. |
| F33 | Database sequence warms from existing visible suffixes once per prefix and then increments under an exclusive lock. Number format preserved; concurrent uniqueness and one-time initialization checked. No production throughput benchmark claimed. |
| F34 | Removed duplicate product synchronization and replaced stock hydration with equivalent SQL aggregation. Valid transfer totals checked; production latency not measured. |
| F35 | Conversation API pages graph data (50 default/100 max), retains array data and adds metadata/global unread count. Browser has load-more handling. Native clients must consume pagination. |
| F36 | Transfer edits/deletes reload and validate draft state under the same row lock as sending. Stale draft-object edit/delete regressions added. |
| F37 | Shipping mutations require settings-edit, with structured office validation. Shipping-view alone cannot mutate configuration. Sanitized JSON settings GET added. |
| F38 | Shared hierarchy validation rejects invalid parents/self/descendant cycles under a serialization lock; category validation precedes file replacement. Cycle regression checked. |
| F39 | Catalog/transfer sort direction is normalized before orderBy. Invalid sort input API checked. |

## Business behavior boundaries

Product prices, discount/tax formulas, whole-rupee order rounding, cashback rates, referral milestones, target eligibility and HR entitlements were not redesigned. Regression cases check ordinary pricing, valid earned gifts, payment state, return/QC and stock conservation. Invalid/unauthorized operations now fail instead of mutating data: lifecycle bypass, oversized/duplicate returns, overpayments, excessive compensation, unsafe deletion, unauthorized configuration/record access and stale transfer edits.

Existing pending refunds are settled or converted into one linked credit obligation rather than creating another payout. Cash/credit settlement uses ledger evidence and locks. No historical wallet, reservation, refund or target records were rewritten; existing inconsistencies still need a separate read-only reconciliation and approved repair.

## Deployment requirements

1. Apply the three new migrations before enabling this code: refund/credit settlement link, business sequences, and provider-event deduplication. Existing customer records are not backfilled or rewritten.
2. Configure `INDIA_POST_WEBHOOK_TOKEN` and configure the actual sender/integration gateway to send `Authorization: Bearer <value>`. An unset credential produces 503; unauthenticated callbacks produce 401. This is a local integration-token contract, **not a claimed India Post vendor signature standard**. Coordinate/verify the real sender before rollout.
3. Clients must use dedicated lifecycle endpoints and action permissions; QC submits every return item once. Confirmed deletion cannot erase fulfillment/financial history. Native chat must follow pagination metadata; browser chat includes a load-more control.
4. Native reports are explicitly unsupported with JSON 501, instead of returning HTML. Review [mobile coverage](MOBILE_COMPATIBILITY.md) for all remaining browser-only features and required client validation.

A real PHP 8.2 runtime, authenticated browser visual tests, live carrier credentials, existing consolidated production-schema upgrade and native application/device tests remain unverified. Fresh MySQL forward/backward migrations and synthetic HTTP/concurrency workflows are tested separately. Existing tests and local build evidence are linked below.

Read-only originals of two source directories were preserved in ignored local backup directories because their original directory ownership prevented direct edits; these backups are excluded from delivery by .gitignore. Automatic review rejected an attempted ownership change, so no ownership/permission mutation was performed. The existing modified vendor/mockery/mockery submodule was left untouched.

## Checks

- `php tests/regression/audit_fixes.php`: isolated, disposable MySQL schema; valid/invalid business flows, HTTP bearer requests, concurrent workers and repeatable migrations.
- `php tests/regression/route_contracts.php`: every registered application handler and implicit model parameter contract.
- `node tests/regression/theme.mjs`: theme state/event/storage behavior.
- PHP/JavaScript syntax, Blade compilation, production Vite build and diff-whitespace checks.
- Pint formats the changed PHP sources/tests. The build retains a large-chunk warning (not a build failure); no unsupported production speedup claim is made.

See [regression suite instructions](/home/user/metis/tests/regression/README.md) and [route coverage](mobile-route-coverage.json). Final check output is recorded in VERIFICATION.md.
