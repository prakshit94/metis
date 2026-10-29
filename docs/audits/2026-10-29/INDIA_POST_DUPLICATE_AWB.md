# India Post duplicate barcode — 29 October 2026

## Incident and read-only findings

India Post rejected `CX079641581IN` as “Duplicate article: Already booked today or yesterday” for all three attempted orders:

| Order ID | Order number | Status inspected |
| --- | --- | --- |
| 35679 | GJ2910202676 | processing |
| 35680 | GJ2910202677 | processing |
| 35681 | GJ2910202678 | processing |

These are distinct orders. The duplicate identifier in the supplied error is the carrier barcode, not the order ID. Active range 3, office 1791393120, prefix CX, was positioned at sequence 7964158, which generates that exact barcode. Its assigned range is 7963620–7968619. The barcode was absent from both local shipment and allocation records at inspection. India Post knows an existing booking that the local records do not identify; the remote booking's original owner cannot be established from this database alone. No live carrier lookup or booking was performed.

The database also contains 35 historical duplicate India Post-format tracking numbers across 71 shipment rows. `india-post-duplicate-tracking.csv` lists every matching barcode, shipment ID, order ID, order number and status, including normalized case/whitespace matches. Non-barcode legacy text such as carrier/person/place labels is excluded. These historical duplicates are separate from the CX barcode in this incident. No records were merged, deleted, renumbered or changed.

## Narrow fix

Changes are confined to the shared India Post provider used by single and bulk automatic ready-to-ship booking:

- Before allocating, skip barcodes already present in the allocation ledger or historical shipments. This protects stale/restored and overlapping counters.
- Preserve the existing independent committed-allocation mechanism. Regression checks verify that order rollback does not roll back a consumed barcode, and concurrent workers get different barcodes.
- When the carrier returns a successful HTTP response containing no valid articles and exactly one matching article whose errors are exclusively explicit duplicate/already-booked rejections, retain that allocation with status `rejected_duplicate` and try the next available barcode. Stop after three distinct attempts.
- Never automatically retry timeouts, server errors, mixed validation errors, or a rejection naming a different barcode. Those are not an authoritative duplicate rejection of the submitted article.
- An exhausted configured office range cannot silently fall back to an unrelated legacy range. Existing queued-range activation remains unchanged.

Order eligibility, state numbering, stock handling, amounts, COD calculation, addresses, parcel dimensions, carrier/provider selection, booking success mapping, tariffs, permissions and bulk status reporting are unchanged. No migration or production data repair was required. The three affected orders remain available for the user's manual test.

## Validation

`METIS_TEST_MYSQL_SOCKET=/tmp/metis-village-audit-mysql/mysql.sock php tests/regression/india_post_duplicate_awb.php`

19 checks passed using a disposable schema and a completely mocked carrier: committed allocations after rollback, barcode check digits, local history skipping, exact duplicate retry, unchanged COD/payload/tariff data, persisted rejection status, unsafe retry exclusions, bounded attempts, queued/exhausted ranges, real ready-to-ship failure rollback and successful transition, unchanged inventory movement/reservation state, and parallel allocation.

PHP syntax, formatting, response contracts and `git diff --check` passed. No requests were sent to India Post and no production orders, shipments, range counters or tracking records were modified during this task. External carrier availability and whether subsequent assigned barcodes are available must be confirmed by the user's live test; repeated explicit duplicates stop safely at the retry limit rather than scanning the whole range.
