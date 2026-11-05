# Dashboard sales and order-value audit

## Scope and integration

`/admin` and `/api/dashboard` keep the existing dashboard permission middleware,
warehouse-only redirect, customer search, order actions and existing payload
fields. New `orderValueReport` and `reportPeriod` fields are additive to
`dashboardData`. The new report receives a clone of the same authorized,
date-filtered order query used by the dashboard. It preserves owner/direct-report,
status permission and state/LOB restrictions; no separate global visibility
rules or permission-sensitive shared cache are introduced.

The report includes sales orders only, excludes cancelled and soft-deleted orders from every metric, and groups
by shipping-state snapshot, saved order office ID, and current order status.
It never infers office ownership from an employee's current office. Unknown
states/offices are Unassigned. All groups remain available through local search
and pagination, rather than silently limiting the report to a top-ten subset.

## Amount definitions

- Sales value: stored `net_amount + wallet_amount_used`. OrderService already
  subtracts wallet usage from net_amount; wallet is added once for sales value.
- Booked / headline Net order value: stored net payable excluding cancelled/future/returned statuses and any order
  with a completed return, matching the dashboard's existing whole-order return
  exclusion approach. Partial return revenue allocation is not changed.
- Delivered: eligible booked value currently delivered (or legacy completed).
- Dispatched: eligible booked value currently dispatched.
- Collected: completed, non-deleted payments linked to the selected orders.
- Refunded: completed cash-refund records linked to the selected orders.
- Outstanding: sum of per-order max(net payable - completed payments, 0), before
  return/refund adjustments; cancelled orders are excluded entirely. Wallet is not subtracted a second time.
- Breakdown shows actual stored subtotal, discount, tax, net payable and wallet.

The date selector filters order_date, not status-transition/payment/refund dates.
Today/yesterday use Asia/Kolkata and half-open date bounds. Collections/refunds
can include transactions on other dates for orders in that cohort. The UI
explains this; delivered value is not presented as cash collected or profit.
No pricing, inventory, payment, refund, return, wallet or status writes occur.

## Existing dashboard findings addressed

- Invalid date-filter values formerly allowed an unbounded order query. Periods
  are now validated; custom dates are inclusive and limited to one calendar month. Date inputs
  are mounted only for Custom dates, with matching frontend and backend bounds.
- Added Last 7 Days, Last 30 Days and custom range without removing existing
  Today, Yesterday, week, month, previous month and year choices.
- Removed fabricated growth percentages from the top cards.
- Labelled the existing simulated 20% profit series as an estimate.
- Corrected the 90D chart button: it now uses 90 daily points instead of four
  calendar-quarter aggregates. Existing 30-point API series remains intact;
  the 90-point series is additive. Month iteration avoids month-end overflow.
- Renamed the legacy booked/net revenue card to Net order value to distinguish
  it from delivered sales and collections. This headline now reuses the report
  booked total to ensure identical sales scope, return exclusions and wallet treatment.
  Other top-line metrics and transactional services remain intact.

## Performance and validation

The new report executes **one aggregate SQL query**. Per-order payment/refund
subqueries use existing order_id indexes; return eligibility uses EXISTS, so
multiple completed returns cannot multiply orders. State, office and status
rollups share the same grouped result. Money is accumulated as integer cents in
application rollups. No order/item collections are hydrated by this report.

- Integration suite: 21 passing checks covering amounts, multiple payments and
  returns, dates, office attribution, empty scopes, purchases/deleted orders,
  dashboard JSON and real owner/direct-report/status permissions.
- Scale suite: 10,000 orders, 20,000 payments, 2,000 completed-return records;
  one SELECT, approximately 100 ms locally, no additional allocated heap pages
  recorded while rolling up grouped results. All totals reconciled.
- Local live EXPLAIN: existing order-date/type and order_id indexes available;
  payment/refund/return lookups used order_id indexes. The local business dataset
  was empty, so the synthetic benchmark is the meaningful load measurement.
- Frontend: grouping/search/pagination/INR formatting/empty states and actual
  7/30/90-day chart windows passed. Blade rendered from real disposable fixtures.
- Production Vite build, PHP syntax, Blade compilation and diff checks passed.

These local checks do not establish production latency at larger cardinalities
or concurrent load. No schema/index migration is required. Deploy matching
Blade/source and rebuilt assets through the existing release process. Production
has not been accessed or changed. Reverting this report involves source/assets
only; it creates no business records or reporting tables to undo.

Run: `php tests/regression/dashboard_order_values.php`,
`php tests/regression/dashboard_order_values_performance.php`, and
`node tests/regression/dashboard_order_values.mjs`. PHP checks use the existing
disposable MySQL regression bootstrap and its test-credential variables.
