# Order and invoice status filter review — 18 October 2026

Scope: invoice order-status badges/filter, order payment-status badges/filter, and placement in the existing Filters panel.

## Compatibility

- All controller actions following index are byte-for-byte unchanged from HEAD in both controllers. No payment processing, order transition, export, print, model, observer, route, or schema modifications.
- With payment_status absent, the Orders query adds no payment predicate. The badges use the invoice relationship already eager-loaded by Orders; no extra queries per row.
- Read-only checks verified the latest invoice status on up to 25 results for each payment filter, including combined paid/no-invoice and invalid input. Default result counts were unchanged.
- The existing invoice order-status query checks completed successfully for all, pending, unfulfillable, dispatched, delivery attempted, return requested, returned, delivered, and combined confirmed/processing/ready-to-ship filters.
- Existing frontend regression checks and route contracts pass. Added coverage for payment filter URL initialization, allowed values, labels, pagination reset, clearing, and the active Filters indicator.
- PHP and JavaScript syntax checks, Blade template compilation, and production asset build pass.

## Performance observations

Local read-only database checks, count plus a page of 25 orders with its invoice eager-loaded; single measurements, not full endpoint or load benchmarks:

| Scope | Unfiltered | Selected payment filters |
| --- | --- | --- |
| Requested order statuses and date window (363 orders) | 12 ms | 3–102 ms |
| All orders (34,352 orders) | 31 ms | 84–435 ms |

EXPLAIN showed existing invoice order_id, deleted_at, and primary indexes used by the latest-invoice relation. The optional filter incurs database work; zero performance overhead is not claimed. Existing latestOfMany relationship behavior is retained so older invoices do not incorrectly match the filter.

The final placement change only moves markup. An audit fix adds payment status to the existing active Filters indicator.

## Limits

The browser session requires sign-in, so rendered-page verification was unavailable. Query timings exclude the rest of the Orders endpoint, statistics cache behavior, network latency, and browser rendering. No production load test was performed. No business records were modified during verification.
