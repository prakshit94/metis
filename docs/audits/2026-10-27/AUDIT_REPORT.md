# Metis audit — 27 October 2026

Implementation update: see [fix status](FIX_STATUS.md) and [mobile compatibility](MOBILE_COMPATIBILITY.md). The findings below preserve the original pre-fix audit evidence.

Status: findings/proposed fixes only. No application source or migration fixes have been applied. Confirmation is required before implementation, as requested.

## Scope and assurance

Inventoried 605 first-party PHP/Blade/JavaScript/SCSS source files across application code, routes, migrations, seeders, configuration, bootstrap and resources. Enumerated all 557 registered routes and checked their controller methods; syntax-checked all 379 PHP files under app/routes/database/config/bootstrap and all 43 resource JavaScript files. Reviewed critical order, inventory, finance, authorization, shipping, HR and chat flows and theme integration in depth. All 112 migrations executed on an isolated MySQL 8.0.46 instance under /tmp. Synthetic data reproductions ran in a transaction that was rolled back. No configured application database records were changed.

This is a broad source audit with targeted runtime reproductions, **not a claim that every possible bug has been found**. Every controller was included in route/syntax inventory; that is not equivalent to exercising every action with every role. There is no first-party tests directory in this checkout. The database regression command documented in database/README.md references a missing tests/regression/schema_repairs.php file. The configured application database could not be connected to. Authenticated browser flows, production query plans/data distributions, external India Post credentials/callback contracts and historical consolidated-schema upgrades have not been validated. Performance items explicitly distinguish measured evidence from source-based scaling risks. Vendored/node_modules dependencies were not manually audited or checked against an online vulnerability database.

The worktree already contained a modified vendor/mockery/mockery submodule; it was left untouched. Build and Blade-cache checks generated ordinary ignored runtime/build artifacts. Only audit documentation/evidence has been added to tracked-source directories.

## Verification results

| Check | Result |
| --- | --- |
| PHP syntax (379 files, PHP 8.3.6) | Passed |
| Resource JavaScript syntax (43 files) | Passed |
| Laravel route enumeration | 557 routes; 3 nonexistent method targets; binding defects below |
| Production Vite build | Passed; large-chunk warning; CSS ~508 kB raw/~73 kB gzip |
| Blade compilation | Passed (137 source views inventoried) |
| Fresh MySQL migrations | All 112 passed |
| MySQL rollback | Failed at index; continuing after test-only workarounds exposed two further FK failures |
| Targeted synthetic data checks | Five data-integrity defects reproduced |
| Theme mock-environment checks | System-follow and blocked-storage failures reproduced |

Passing syntax/build/fresh migration checks does not prove business correctness. No tests were run against real customer data.

## Reproduced examples

| Scenario | Observed result |
| --- | --- |
| Bulk payment: valid pending A then invalid refunded B, request completed | HTTP 422, but A persisted as completed inside the surrounding test transaction |
| Return request for order containing 1 unit | requested_qty=100 accepted |
| QC submits the same item twice, each restocked_qty=100 | HTTP 200; stock increased by 200 |
| Transfer 5 from physical stock 10, reserved stock 9 | Transfer sent; physical=5, reserved=9 |
| Edit payment for invoice net=100 to amount=1000 | HTTP 200; paid_amount=1000 |
| No saved theme, OS changes light->dark | Effective theme remains light |
| localStorage throws | ThemeManager construction fails; application initialization stops |

The synthetic return fixture intentionally used explicit null notes to keep quantity tests independent from the optional-key bug. The runtime scenarios were separate observations within an overall rollback-only fixture transaction.

## Findings and proposed fixes

Severity: Critical = unauthenticated business-state mutation; High = authorization or material stock/financial integrity; Medium = functional/deployment/reliability defect; Low = bounded robustness or proven redundant work. “Source confirmed” identifies a demonstrable path in code; concurrency findings still need concurrent-request regression tests during implementation.

### F01 · Critical · Public India Post webhook can change order delivery state

Evidence: Source confirmed. Source: [routes/api.php:77](/home/user/metis/routes/api.php:77).

The route is outside auth:sanctum and the handler verifies no shared secret or signature. Supplying a known tracking number and DELIVERED event can change shipment and order state. Rate limiting does not authenticate a carrier.

Proposed fix: Authenticate the carrier using its supported signature/secret contract before logging or mutating anything; reject unauthenticated payloads and replayed events.

### F02 · High · Payment reversal lacks an action permission

Evidence: Source confirmed. Source: [app/Modules/Orders/Controllers/PaymentController.php:235](/home/user/metis/app/Modules/Orders/Controllers/PaymentController.php:235).

DELETE /payments/{payment} has session authentication but no permission middleware. The controller and revertPayment service perform no permission check. Any authenticated account reaching this route can soft-delete a payment and change invoice totals.

Proposed fix: Require the established payment reversal/receipt permission and apply record visibility checks before calling FinancialService.

### F03 · High · Stock transfer actions bypass action permissions

Evidence: Source confirmed. Source: [app/Modules/Inventory/Controllers/StockTransferController.php:21](/home/user/metis/app/Modules/Inventory/Controllers/StockTransferController.php:21).

send, receive and cancel are absent from the middleware map and have no internal authorization. Their API routes require authentication alone. bulkAction requires edit permission but also accepts delete, bypassing the separate delete permission.

Proposed fix: Protect each action with the existing appropriate inventory permission and check the specific bulk action permission.

### F04 · High · Record detail and mutation endpoints bypass list visibility rules

Evidence: Source confirmed. Source: [app/Modules/Orders/Controllers/OrderController.php:1944](/home/user/metis/app/Modules/Orders/Controllers/OrderController.php:1944).

Orders are filtered by creator/LOB in their listing, but destroy and multiple direct order actions accept globally bound records without equivalent checks. Returns show/QC/finance, stock transfer show/update/actions, and adjustment show/approve/reject also lack the list warehouse/LOB checks. Payment and refund lists have no corresponding order ownership/LOB filter. Generic action permissions therefore expose records hidden in the directory.

Proposed fix: Centralize the existing visibility predicates and apply them to direct lookups, financial lists, exports and every bulk-selected ID. Preserve current global-view exceptions.

### F05 · High · Order create/edit accepts lifecycle states without lifecycle processing

Evidence: Source confirmed. Source: [app/Modules/Orders/Requests/StoreOrderRequest.php:88](/home/user/metis/app/Modules/Orders/Requests/StoreOrderRequest.php:88).

The create request accepts confirmed through returned; createOrder stores that state without reserving/deducting stock or performing delivery side effects. UpdateOrderRequest also accepts terminal states; updateCustomerOrder stores the requested state and only handles reservation for confirmed. An orders.create/orders.edit grant can bypass dedicated confirm/dispatch/deliver permissions and inventory transitions.

Proposed fix: Constrain ordinary create/edit to their intended states and route authorized transitions through existing lifecycle services. Confirm which initial states are intentionally supported before implementation.

### F06 · High · Deleting a confirmed order leaves inventory reserved

Evidence: Source confirmed. Source: [app/Modules/Orders/Controllers/OrderController.php:1944](/home/user/metis/app/Modules/Orders/Controllers/OrderController.php:1944).

destroy calls only $order->delete(). Soft deletion does not cascade or release stock_reservations or stocks.reserved_qty. Deleting a confirmed sale leaves stock unavailable; deleting later lifecycle states can hide records still represented by movements and financial documents.

Proposed fix: Define allowed deletion states, then reject incompatible deletions or use the existing cancellation/reversal workflow before deletion. Do not invent stock compensation for historical orders.

### F07 · High · Sending a transfer consumes stock reserved for customer orders

Evidence: Runtime reproduced. Source: [app/Services/InventoryService.php:733](/home/user/metis/app/Services/InventoryService.php:733).

The transfer compares physical quantity with transfer quantity, ignoring reserved_qty. Reproduction: physical=10, reserved=9, transfer=5 succeeds and leaves physical=5 while reserved=9.

Proposed fix: Validate against physical minus reserved quantity while holding the stock lock. Preserve the existing transfer movement accounting.

### F08 · High · Return requests can exceed quantities sold

Evidence: Runtime reproduced. Source: [app/Modules/Orders/Controllers/OrderReturnController.php:157](/home/user/metis/app/Modules/Orders/Controllers/OrderReturnController.php:157).

Product membership is checked, but requested_qty has no upper bound, no distinct-product rule, no cumulative-return check and no eligible-order-state guard. Reproduction: an order containing one unit accepts a request for 100 units.

Proposed fix: Lock the order; validate returnable state and remaining quantity per product/order line, aggregate duplicate input or reject it, and account for existing non-rejected returns.

### F09 · High · Duplicate QC item IDs restock the same item repeatedly

Evidence: Runtime reproduced. Source: [app/Modules/Orders/Controllers/OrderReturnController.php:624](/home/user/metis/app/Modules/Orders/Controllers/OrderReturnController.php:624).

items.*.id has no distinct rule. Each submitted occurrence calls processReturnItem with the full quantity. Reproduction: submitting the same return item twice with restocked_qty=100 adds 200 stock units; the saved return item records only 100.

Proposed fix: Require distinct item IDs, validate each quantity against the remaining authorized return quantity, and apply stock changes exactly once.

### F10 · High · Concurrent QC requests can both restock a return

Evidence: Source confirmed. Source: [app/Modules/Orders/Controllers/OrderReturnController.php:626](/home/user/metis/app/Modules/Orders/Controllers/OrderReturnController.php:626).

The eligible-status check occurs before the transaction. The transaction never reloads/locks the return or its items, so two requests seeing approved can both apply inventory and refund side effects, then mark the same return completed.

Proposed fix: Reload and lock the return and items inside the transaction, recheck state there and make completion idempotent. Add a concurrent-request regression test.

### F11 · High · QC can complete a return without processing its own items

Evidence: Source confirmed. Source: [app/Modules/Orders/Controllers/OrderReturnController.php:633](/home/user/metis/app/Modules/Orders/Controllers/OrderReturnController.php:633).

The exists rule checks the entire order_return_items table. Foreign IDs are silently skipped; items=[] is also accepted. The return and order are still marked completed/returned even when no matching item was processed. A subset can also complete the whole return while omitting other items.

Proposed fix: Scope IDs to this return, require a non-empty valid set and verify the required item coverage before completion. Confirm partial-QC semantics instead of silently completing omitted items.

### F12 · Medium · Optional return notes are accessed as required array keys

Evidence: Source confirmed. Source: [app/Modules/Orders/Controllers/OrderReturnController.php:194](/home/user/metis/app/Modules/Orders/Controllers/OrderReturnController.php:194).

notes is nullable rather than required, but store and bulkStore read $validated[notes] directly. processQc similarly reads $itemData[qc_notes] after optional validation. Omitted keys trigger PHP warnings that Laravel converts to exceptions, rejecting otherwise valid requests.

Proposed fix: Use null defaults for omitted nullable fields and test omitted as well as explicit null input.

### F13 · High · Bulk payment rejection commits preceding updates

Evidence: Runtime reproduced. Source: [app/Modules/Orders/Controllers/PaymentController.php:133](/home/user/metis/app/Modules/Orders/Controllers/PaymentController.php:133).

Returning false from DB::transaction commits rather than rolls back. Reproduction: select pending payment A followed by refunded payment B, request completed. HTTP 422 is returned for B, but A has already become completed.

Proposed fix: Validate all locked rows before mutation or throw a validation exception from the transaction so all updates roll back.

### F14 · High · Editing a payment bypasses overpayment and refund-state guards

Evidence: Runtime reproduced. Source: [app/Modules/Orders/Controllers/PaymentController.php:159](/home/user/metis/app/Modules/Orders/Controllers/PaymentController.php:159).

update writes the amount/status directly and does not lock or verify invoice due. Reproduction: a payment on a 100 invoice is edited to 1000 and succeeds. The single-record edit also lacks the refunded-state guard present in bulkStatus.

Proposed fix: Use a shared transactional payment amendment service: lock the invoice/payments, subtract the old payment when calculating the allowable replacement and enforce existing state restrictions.

### F15 · High · Payment hooks can revive a cancelled invoice

Evidence: Source confirmed. Source: [app/Modules/Orders/Models/Payment.php:57](/home/user/metis/app/Modules/Orders/Models/Payment.php:57).

The saved/deleted callback sets paid, partially_paid or unpaid with no cancelled-status exception. Editing or deleting a payment on a cancelled invoice overwrites cancellation. FinancialService also accepts payment on a cancelled invoice if due_amount is positive.

Proposed fix: Preserve cancelled state in payment synchronization and reject payment creation/amendment on cancelled invoices unless an explicit existing reopening workflow authorizes it.

### F16 · High · Refund and credit limits do not enforce one shared remaining entitlement

Evidence: Source confirmed. Source: [app/Services/FinancialService.php:88](/home/user/metis/app/Services/FinancialService.php:88).

processRefund subtracts only this return.refund_amount from order.net_amount; issueCreditNote independently subtracts this return.credit_note_amount. Other returns, QC-created pending/completed refunds, actual completed cash received, wallet refunds and the other compensation method are not included in a shared cap. A refund plus credit note can each consume the full cap.

Proposed fix: Lock the order/return financial scope and calculate a shared remaining entitlement from authoritative ledger rows, including pending obligations. Agree the existing cash/wallet/credit treatment before implementing any changed compensation policy.

### F17 · Medium · Refund status changes do not reconcile return financial summaries

Evidence: Source confirmed. Source: [app/Modules/Orders/Controllers/RefundController.php:102](/home/user/metis/app/Modules/Orders/Controllers/RefundController.php:102).

QC creates pending Refund rows without updating return.refund_amount. bulkStatus later changes them to completed/failed/pending without reconciling return.refund_amount or financial_status. FinancialService alone maintains those columns, so the UI totals and later caps diverge from the refund ledger; its cash path also never chooses fully_refunded.

Proposed fix: Use one refund transition service and derive/reconcile return summaries from relevant ledger statuses atomically. Preserve the distinction between pending obligations and completed payouts.

### F18 · High · Manual credit notes permit arbitrary amounts and unrelated references

Evidence: Source confirmed. Source: [app/Modules/Orders/Controllers/CreditNoteController.php:135](/home/user/metis/app/Modules/Orders/Controllers/CreditNoteController.php:135).

Validation independently checks customer_id, invoice_id and order_return_id existence, but not that they belong together or that amount is owed. The handler credits the requested wallet amount and marks the first matching refund completed regardless of its amount/status. Repeating the request can credit money again.

Proposed fix: Validate customer/invoice/return ownership together, lock the exact refundable obligation, cap the amount and make settlement idempotent. Keep cash and wallet settlement records consistent.

### F19 · High · Wallet redemption/credit paths lack a shared wallet lock

Evidence: Source confirmed. Source: [app/Services/OrderService.php:180](/home/user/metis/app/Services/OrderService.php:180).

New-order redemption reads the balance without lockForUpdate and later decrements it. Two orders can both observe the same funds and overdraw. CreditNoteController reads then writes an absolute balance without a lock, allowing concurrent credits/debits to overwrite each other. The order edit path already demonstrates the correct locked approach.

Proposed fix: Lock the customer wallet row before reading or changing it in all wallet mutations, keep ledger creation in the same transaction and use a consistent lock order.

### F20 · High · India Post AWB allocation races and suppresses duplicate errors

Evidence: Source confirmed. Source: [app/Services/Shipping/Providers/IndiaPostProvider.php:549](/home/user/metis/app/Services/Shipping/Providers/IndiaPostProvider.php:549).

The active range is read and incremented without a row lock. Concurrent orders can select the same current_sequence. The unique tracking-row insert exception is swallowed, allowing the duplicate barcode to be returned despite the schema uniqueness constraint. Range activation/editing can also race.

Proposed fix: Reserve sequences in a short transaction under an office/range lock; enforce non-overlapping ranges; never continue after allocation persistence fails. Avoid holding that allocation lock during remote HTTP calls.

### F21 · High · Free-gift quantities are not limited to earned quantities

Evidence: Source confirmed. Source: [app/Services/OrderService.php:644](/home/user/metis/app/Services/OrderService.php:644).

expectedGifts contains qty, but gift validation checks only product ID and gift_source. A matching gift line receives a 100% discount for any submitted quantity; repeated matching lines also receive the discount.

Proposed fix: Track remaining earned quantity per product/source across all lines and discount only that allowance. Reject or charge excess units using the established product price.

### F22 · High · Carrier tracking updates bypass stock, cashback and referral delivery logic

Evidence: Source confirmed. Source: [app/Modules/Orders/Controllers/IndiaPostWebhookController.php:75](/home/user/metis/app/Modules/Orders/Controllers/IndiaPostWebhookController.php:75).

The webhook and PollIndiaPostTracking directly update order status. InventoryService::deliverOrder performs dispatched-quantity reduction, cashback and referral rewards, but these paths never call it. Later deliverOrder calls immediately return because status is already delivered. Polling can also set dispatched directly without dispatchOrder stock deduction.

Proposed fix: Send carrier events through the existing authorized, idempotent lifecycle workflow and update shipment/event state atomically. Define safe handling for legacy out-of-order events.

### F23 · High · Three exposed APIs target nonexistent controller methods

Evidence: Route reflection verified. Source: [routes/api.php:285](/home/user/metis/routes/api.php:285).

Route reflection found POST /api/orders/import -> OrderController::bulkImport, POST /api/payments -> PaymentController::store, and POST /api/returns/{orderReturn}/process -> OrderReturnController::process. None of those methods exists. These calls cannot execute their advertised operation.

Proposed fix: Connect each endpoint to its supported existing workflow, with matching validation, permissions and response contract; remove/deprecate an endpoint only after checking mobile clients.

### F24 · High · API route parameter names prevent model binding

Evidence: Route/framework-source verified. Source: [routes/api.php:399](/home/user/metis/routes/api.php:399).

Transfer resource routes use {transfer}, but methods request StockTransfer $stockTransfer. Brand, UOM and warehouse show routes request $model instead of the route names. GET /api/returns/{orderReturn} requests OrderReturn $return, and POST /api/returns has no {order} parameter although store requires Order $order. Laravel skips unmatched implicit model binding and controller dependency injection supplies a new model, producing empty responses or failed mutations. Snake-case/camel-case pairs such as tax_rate/taxRate were explicitly excluded because Laravel supports them.

Proposed fix: Align parameter names/resource parameter maps and supply the intended order on return creation. Add route-level existing-ID/nonexistent-ID checks.

### F25 · Medium · Rolling back the order target index fails on MySQL

Evidence: Runtime reproduced. Source: [database/migrations/2026_10_24_120000_add_order_target_lookup_index.php:19](/home/user/metis/database/migrations/2026_10_24_120000_add_order_target_lookup_index.php:19).

After all 112 migrations succeeded on MySQL 8, rollback failed with error 1553: orders_creator_date_deleted_idx is needed in a foreign key constraint. MySQL used the new left-prefixed composite index to support created_by and removed/replaced its redundant implicit index.

Proposed fix: Ensure another suitable created_by index exists before dropping the composite index. Implement an existing-installation-compatible correction and test forward/backward execution.

### F26 · Medium · Two creation migrations leave external foreign keys behind on rollback

Evidence: Runtime reproduced. Source: [database/migrations/2026_07_07_000005a_create_villages_table.php:39](/home/user/metis/database/migrations/2026_07_07_000005a_create_villages_table.php:39).

Continuing isolated rollback after temporarily supplying the supporting order index failed dropping villages: users_village_id_foreign still references it. After temporarily removing that FK only in the test DB, rollback then failed dropping users: departments_manager_id_foreign remains. Both migrations add a foreign key to an older table in up() without removing it in down().

Proposed fix: Drop the foreign keys added to users/departments before dropping the referenced tables. Test a full rollback and full re-migration with MySQL foreign-key enforcement enabled.

### F27 · Medium · Declared PHP 8.2 support conflicts with typed class constants

Evidence: Source confirmed. Source: [app/Modules/Users/Controllers/AuthController.php:35](/home/user/metis/app/Modules/Users/Controllers/AuthController.php:35).

composer.json allows PHP ^8.2, but AuthController uses typed class constants, introduced in PHP 8.3. An allowed PHP 8.2 installation cannot parse this authentication controller. Current syntax checks passed under PHP 8.3.6 and therefore do not validate the advertised minimum.

Proposed fix: Remove constant type declarations to preserve the stated minimum, or explicitly raise the platform requirement if deployment policy already requires 8.3. Prefer the compatibility-preserving option.

### F28 · High · Adding an existing chat member can demote the owner

Evidence: Source confirmed. Source: [app/Services/Chat/ChatService.php:224](/home/user/metis/app/Services/Chat/ChatService.php:224).

GroupController::addMember permits moderators to add role=member. attachMember uses updateOrCreate and overwrites an existing member role. Adding the current owner as a member therefore demotes the owner without the dedicated ownership-transfer checks; admins can similarly rewrite roles through the add path.

Proposed fix: Treat already-active members as unchanged or reject duplicates; reserve role changes for updateRole and prevent the add path from modifying owner/admin roles.

### F29 · Medium · Header theme toggle bypasses chart updates and manager state

Evidence: Source confirmed. Source: [resources/js/main.js:748](/home/user/metis/resources/js/main.js:748).

The visible header uses Alpine themeSwitch.toggle(), which changes the HTML attribute/localStorage directly. ThemeManager.setTheme emits themeChanged, but the header never calls it. Existing dashboard/analytics charts keep their old theme and ThemeManager.currentTheme becomes stale.

Proposed fix: Use one theme manager for the header and every toggle, synchronize Alpine state from its event and update charts through the existing themeChanged handlers.

### F30 · Medium · Automatic system-theme following stops immediately

Evidence: Runtime reproduced. Source: [resources/js/utils/theme-manager.js:14](/home/user/metis/resources/js/utils/theme-manager.js:14).

Initialization stores the OS-derived theme. The system change listener then sees a stored theme and refuses to follow OS changes even though the user never made a choice. Reproduction: start with no saved value/light OS, change OS to dark; actual theme remains light.

Proposed fix: Separate applying an effective theme from saving an explicit user preference; only persist deliberate choices and validate stored values.

### F31 · Medium · Blocked browser storage aborts application initialization

Evidence: Runtime reproduced. Source: [resources/js/utils/theme-manager.js:25](/home/user/metis/resources/js/utils/theme-manager.js:25).

getStoredTheme and setTheme do not catch localStorage failures. Reproduction with storage throwing SecurityError causes ThemeManager construction to fail. AdminApp initializes this before Bootstrap/page components/Alpine inside one try block, so the rest of initialization is skipped.

Proposed fix: Wrap storage reads/writes, keep a validated in-memory fallback and let the rest of the application initialize. Apply the same safe access in Alpine theme state.

### F32 · Medium · Financial list pagination is unbounded and accepts zero/negative limits

Evidence: Source-confirmed performance/input issue. Source: [app/Modules/Orders/Controllers/PaymentController.php:68](/home/user/metis/app/Modules/Orders/Controllers/PaymentController.php:68).

PaymentController, InvoiceController and RefundController pass the requested limit directly into paginate. Very large values load all matching records and nested payment/refund relations; zero/negative values can generate invalid pagination behavior. DepartmentController similarly uses raw per_page.

Proposed fix: Validate and clamp list page sizes to an agreed existing application maximum; keep full exports on their separate streaming paths.

### F33 · Medium · Order numbering work grows with daily order volume and serializes LOBs

Evidence: Source-confirmed scaling bottleneck. Source: [app/Services/OrderService.php:287](/home/user/metis/app/Services/OrderService.php:287).

Each auto-number allocation acquires one database/date advisory lock shared by all LOB prefixes, loads every matching daily order number with lockForUpdate, then scans suffixes in PHP. This adds O(daily orders) work per order and accumulates row locks until the outer transaction commits. Total daily suffix scanning grows approximately quadratically. No production throughput measurement was performed.

Proposed fix: Use a transaction-safe sequence row scoped to database/date/prefix, seeded to the existing maximum suffix, while preserving the exact visible number format and uniqueness. Benchmark before/after.

### F34 · Low · Inventory lifecycle repeats aggregate queries while holding write locks

Evidence: Source-confirmed redundant work. Source: [app/Services/InventoryService.php:810](/home/user/metis/app/Services/InventoryService.php:810).

receiveTransfer calls syncProductStatus twice consecutively for the same product. That helper already aggregates every warehouse, so the second call repeats the same reads/save. confirmOrder also repeats sync after reserveStock already synchronized it. syncProductStatus hydrates all stocks merely to sum quantity-reserved_qty.

Proposed fix: Remove proven duplicate synchronization and perform equivalent SQL aggregation. Keep the same status formula, drafts and overselling behavior. Measure query counts with multi-item transfers.

### F35 · Medium · Chat polling reloads the entire conversation graph

Evidence: Source-confirmed scaling risk. Source: [app/Services/Chat/ChatService.php:25](/home/user/metis/app/Services/Chat/ChatService.php:25).

listConversations performs get() with all active members/users, latest senders and unread correlated counts for every conversation. The client refreshes conversation data periodically. Payload/query work grows with total conversation membership rather than the visible page. Production latency was not measured.

Proposed fix: Use compatible pagination or a delta/since cursor, cap initial list size, and avoid repeatedly fetching unchanged member profiles. Measure client payload and database work before changing the polling contract.

### F36 · High · Transfer editing can race with sending and change in-transit contents

Evidence: Source confirmed. Source: [app/Modules/Inventory/Controllers/StockTransferController.php:158](/home/user/metis/app/Modules/Inventory/Controllers/StockTransferController.php:158).

The draft check occurs outside the transaction; update never reloads/locks the transfer. If sendTransfer locks and sends the draft after that check, update can later overwrite warehouses and delete/recreate items on the sent transfer. Receive then credits the edited contents instead of those deducted from the source. destroy and bulk delete also check stale status without a transfer lock.

Proposed fix: Reload/lock the transfer and verify draft state inside each edit/delete transaction before changing items. Share the same lock convention as send/receive.

### F37 · High · Shipping view permission also grants configuration mutations

Evidence: Source confirmed. Source: [routes/api.php:83](/home/user/metis/routes/api.php:83).

Shipping settings and range mutations use permission:settings-edit|shipping-view, an OR rule. A user granted only shipping-view can replace office configuration/credentials and alter, activate or delete barcode ranges. store also validates only the outer array, allowing malformed office entries to break provider configuration.

Proposed fix: Require the established edit permission for every mutation, keep view-only access read-only and validate office entry structure/IDs/provider URL and credential field types before storage.

### F38 · Medium · Category and department parent updates can create cycles

Evidence: Source confirmed. Source: [app/Modules/Catalog/Controllers/CategoryController.php:92](/home/user/metis/app/Modules/Catalog/Controllers/CategoryController.php:92).

Category parent_id is only nullable; it accepts self-parenting and descendant cycles, and invalid IDs reach database errors. Department validation blocks only direct self-parenting, permitting A->B->A. The organization chart starts at parent_id=NULL, so cyclic departments disappear from the tree.

Proposed fix: Validate existing parent IDs, reject self/descendant references using a bounded ancestor traversal under appropriate locks, and preserve the intended hierarchy depth.

### F39 · Low · Catalog/transfer sort direction errors become server failures

Evidence: Source confirmed. Source: [app/Modules/Inventory/Controllers/StockTransferController.php:61](/home/user/metis/app/Modules/Inventory/Controllers/StockTransferController.php:61).

Several catalog list controllers and StockTransferController whitelist sort columns but pass arbitrary sort_dir to orderBy. Laravel throws InvalidArgumentException for a value other than asc/desc; a malformed query causes a 500 instead of a controlled validation response. InventoryAdjustmentController already normalizes this correctly.

Proposed fix: Validate sort_dir as asc/desc or normalize it consistently before passing it into the query builder.

## Implementation boundaries for approval

No pricing, discount, tax, rounding, cashback percentage, target eligibility, attendance entitlement or existing role policy should be silently redesigned. The proposed work applies the existing workflows consistently and prevents duplicate/unauthorized mutations. Some defects expose conflicting current behaviors, so zero behavioral change for every input is impossible: invalid or unauthorized inputs must start failing.

Before coding financial/deletion/state fixes, resolve these narrow policy points from the current product behavior and your approval: which initial order states are supported; whether confirmed orders may be deleted; whether QC can be partial; how cash/wallet/credit jointly cap a return; and which existing permission authorizes payment reversal and transfer actions. Carrier authentication must match the actual provider contract. Do not fabricate a webhook signature scheme or a historical-money reconciliation.

After approval, fix authorization and ledger/stock integrity first, then API wiring, rollback/compatibility, themes and performance. Add meaningful regression coverage for the reproduced cases, unauthorized requests, cross-LOB IDs, valid lifecycle flows, parallel QC/wallet/AWB operations and migrations on both fresh and representative upgraded schemas. Preserve current valid business outputs. Benchmark the proposed query changes rather than claiming production speedups from source alone.

Existing data may already contain inconsistent reservations, refunds or wallet balances. Identify discrepancies read-only and report a separate reconciliation proposal; do not rewrite historical data as part of ordinary code fixes.

## Evidence files

- [Route inventory](routes.json)
- [Source inventory](source-inventory.txt)
- [Fresh migration output](migrations.log)
- [Rollback index failure](rollback-index.log)
- [Rollback villages FK failure](rollback-villages.log)
- [Rollback users FK failure](rollback-users.log)
- [Machine-readable findings](findings.json)

The runtime reproduction scripts were kept under /tmp and were not installed as application code. They use only the disposable local database and synthetic identifiers. External-service operations were not invoked.
