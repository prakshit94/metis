# Returns QC lookup by tracking ID

The Returns & QC template selector offers Order ID / number (existing) and
Tracking ID / AWB (new). Upload either `order_id` or `tracking_id` as a single
identifier column. `tracking_no`, `tracking_number` and `awb` aliases are also
accepted. Numeric tracking IDs remain strings, including leading zeroes.

Tracking identifiers resolve non-deleted shipments against accessible orders.
Multiple matching orders are rejected rather than choosing arbitrarily. A match
loads the latest QC-eligible return using the existing approved/received/
qc_in_progress status rules and the existing record visibility checks. Combined
order/tracking columns are rejected to avoid silently ignoring one identifier.
The existing detailed order_no/SKU/quantity CSV remains supported.

Identifier CSVs only preview and load the current QC review form. Operators still
review and submit through the existing bulk QC action. processQc, stock movement,
financial handling, return creation and quantity validation are unchanged.
Tracking can also be searched in the returns directory, under its existing scope.

Checks: `php tests/regression/returns_tracking_qc.php` uses disposable MySQL
fixtures to cover tracking aliases, leading zeroes, unchanged order lookup and
QC defaults, ambiguity, invalid status/missing/deleted shipment matches,
preview-only imports and no inventory/financial changes during lookup.
`node tests/regression/returns_tracking_qc.mjs` checks both template headers.

Pending approval is not a tracking lookup failure. When an accessible tracking
match has no QC-eligible return, the preview now names its pending return and
asks the operator to approve it through the existing Returns & QC action first.
Completed/rejected return statuses and orders without a return get distinct
instructions. Lookup never approves, creates, or changes a return automatically.

## Selective pending-return processing

Identifier imports explicitly request pending candidates in preview only. The
pending-return review also opens when table selections contain pending returns.
Pending bypass checkboxes start unchecked and reset for every new selection.
Unselected pending returns are excluded from aggregation and QC submissions.
Approved returns retain their normal QC flow. The UI passes bypass_approval only
on the selected return's own QC request, never as a batch-wide switch.

A pending bypass requires both existing returns.approve and returns.qc authority.
QC validates quantities, locks the order and return, and rechecks status before
changing anything. Bypass approval, its actor audit entry, item processing,
stock and existing financial updates share the same transaction. Any QC failure
rolls back the bypass too. Completed/rejected returns cannot use this flag.
Reject/cancel uses the existing returns.cancel permission and cancellation
endpoint, including its prior-order-status restoration behavior; it does not
introduce a separate rejected-order lifecycle.

Verification: returns_selective_qc.php includes the tracking suite and tests
unchecked pending returns, opt-in previews, selected-only completion, retained
quantity constraints, permission checks, failed-inventory rollback, audit
history, duplicate submission protection and existing cancellation behavior.
Frontend checks ensure excluded returns do not contribute to quantities or API
payloads, and bypass consent is not retained across selections.
