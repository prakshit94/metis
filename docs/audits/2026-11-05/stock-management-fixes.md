# Stock-management audit fixes

## Definitions and transaction isolation

The page keeps its existing pending-demand forecast: max(0, quantity - reserved
- pending quantity + permitted overselling limit). Warehouse overselling settings
still override product settings; explicit false and zero now remain false/zero.
Physical availability is reported separately as max(0, quantity - reserved).
Filters, sorting, badges, alert thresholds and CSV now use the same forecast.
Zero product alert thresholds are preserved instead of falling back to five.

InventoryService and OrderService were not modified. Reservations and stock
allocation continue using their existing physical availability rules; pending
forecast and overselling reporting never change reserved, committed, dispatched,
in-transit or damaged quantities. Existing stock overrides still use
InventoryService, retain reserved-quantity protection, and keep transaction retry
behavior. Accepted override notes are saved in the existing activity log in the
same transaction.

## Reporting, API and UI corrections

- Delivered values now use the existing Stock model definition in SQL, including
  its parent-order status rules for completed returns. The accessor honors that
  preloaded result rather than running two more queries per stock row.
- Return summaries ignore deleted parent orders; delivery-attempt counts ignore
  deleted shipments. The existing pipeline's order-type scope is retained.
- Warehouse filters are pushed into aggregate subqueries. Sorting has a stable
  stock-ID tie breaker. Existing SKU coverage/history eligibility is retained.
- Summary cards intentionally cover all stock levels within the selected
  warehouse/search; the page explains this scope.
- Filter/ID types, allowed sort/level values, pagination limits and stock quantity
  precision/range are validated before querying/writing.
- Details GET only reads an existing non-deleted row; missing/deleted stock
  returns data:null. It cannot create, restore, lock or merge stock rows.
- Full export uses a permission-checked server endpoint and 500-row chunks,
  removing the former 1,000-record truncation. It uses the same filters, scopes,
  values and ordering as the table. Selected export respects warehouse/state
  visibility. Formula-like product/SKU/warehouse text is neutralized in CSV.
- Warehouse/stock-level changes reset pagination. Removed rows or narrower
  results redirect the display to the last valid page. Old responses cannot
  replace newer results; navigation aborts pending table requests.
- Failed warehouse loads and expired/non-JSON responses get readable errors.
  Empty action menus are removed without adding stock-edit permissions/actions.
  Column group and empty-state spans now match the actual table columns.

## Verification

Disposable database suite covers 31 checks: matching forecast/filter/sort values,
zero/false settings, real thresholds, delivered/return/deletion handling,
coverage-only rows, zero accessor queries, bounded list queries, malformed
inputs, read-only GET, existing override/reservation behavior and audit notes,
full CSV beyond 1,000 rows, CSV safety, export permission, state scoping and
exact selected exports. The six-row sample list stayed within eight SQL queries
including validation/eager loads; serialization of a preloaded delivered value
issued zero queries instead of two per row.

Frontend checks cover matching availability, zero settings, threshold handling,
last-page recovery, stale requests, error feedback, selected export parameters
and the absence of a page-size cap on full exports. PHP syntax, Blade compilation,
production Vite build and diff checks passed.

No business data was changed by testing. Test schemas are disposable and removed
at completion. No new schema/index migration is required. Deploy the new export
route/controller together with matching rebuilt JS and Blade, then refresh the
normal route/view caches. The pre-existing vendor modification is unrelated.
Production and GitHub have not been updated by this task.
