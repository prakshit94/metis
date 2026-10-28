# Verification — 27 October 2026

A later detailed recheck found and corrected additional issues. See [current recheck results](DETAILED_RECHECK.md).

Mobile support was expanded after this audit phase. See [mobile implementation and current validation](MOBILE_IMPLEMENTATION.md).

- 65 isolated MySQL regression checks passed, including concurrent workers and two complete migration/rollback cycles.
- All 115 migrations passed on a fresh MySQL 8 schema. Test schemas were dropped automatically; the configured application database was not used.
- 568 registered routes passed existing-handler and model-parameter checks.
- Theme regression checks passed.
- 394 first-party PHP files passed syntax checking on PHP 8.3.6; 43 JavaScript source files passed Node syntax checking.
- Changed PHP sources/tests were formatted with Pint; diff whitespace checks passed.
- Blade view compilation passed; production Vite build passed (large-chunk warning remains).

The actual native app/device, authenticated browser visuals, real carrier integration, production load and historical consolidated-schema upgrade are not certified by these tests. No deployment or historical-data reconciliation was performed.

## Isolated regression output

```text
PASS: all fresh migrations execute
PASS: invalid bulk payment throws validation
PASS: failed bulk payment rolls back preceding changes
PASS: payment edit cannot overpay
PASS: valid payment still marks invoice paid
PASS: payment reversal preserves invoice cancellation
PASS: cancelled invoice rejects new payments
PASS: return cannot exceed sold quantity
PASS: valid return accepts omitted optional notes
PASS: duplicate QC IDs rejected
PASS: empty QC rejected
PASS: QC cannot exceed requested quantity
PASS: valid QC restocks exactly once
PASS: stale return object cannot run QC twice
PASS: transfer protects reserved stock
PASS: valid transfer conserves total stock
PASS: stale draft model cannot edit a received transfer
PASS: stale draft model cannot delete a received transfer
PASS: sequence initializes once and increments
PASS: CRUD cannot bypass lifecycle transitions
PASS: hierarchy cycle rejected
PASS: ordinary server-side pricing remains unchanged
PASS: earned free gift remains free
PASS: gift quantity cannot exceed earned allowance
PASS: normal confirmation still reserves stock
PASS: deleting an unfulfilled confirmed order releases its reservation
PASS: live order import uses server pricing and cannot fabricate wallet usage
PASS: settling a pending refund reuses its obligation
PASS: refund and credit share one remaining entitlement
PASS: return financial summaries match both compensation channels
PASS: adding an active member preserves owner role
PASS: API unauthenticated response is JSON without Accept
PASS: bearer account without transfer permission is rejected
PASS: bearer account without receipt permission cannot reverse payments
PASS: webhook rejects missing integration credential
PASS: scoped user can still open their own permitted order
PASS: direct order lookup enforces directory ownership scope
PASS: financial list hides orders outside the status scope
PASS: shipping view grant does not allow settings mutations
PASS: brand API binds the requested model
PASS: invalid catalog sort direction is normalized safely
PASS: transfer resource API binds the requested model
PASS: return API binds the requested model
PASS: invoice list accepts bounded pagination and JSON mobile context
PASS: mobile JSON smoke: /api/orders (200)
PASS: mobile JSON smoke: /api/payments (200)
PASS: mobile JSON smoke: /api/refunds (200)
PASS: mobile JSON smoke: /api/credit-notes (200)
PASS: mobile JSON smoke: /api/inventory/transfers (200)
PASS: mobile JSON smoke: /api/inventory/adjustments (200)
PASS: mobile JSON smoke: /api/shipping/settings (200)
PASS: mobile JSON smoke: /api/chat/conversations (200)
PASS: mobile JSON smoke: /api/dashboard (200)
PASS: mobile JSON smoke: /api/users (200)
PASS: authenticated carrier callback executes the dispatch/delivery workflow
PASS: replayed carrier callback does not duplicate cashback or tracking event
PASS: manual credit cannot settle another customer invoice or invent an amount
PASS: invalid credit leaves the wallet untouched
PASS: native API login issues a token without browser headers
PASS: concurrent sequence allocation stays unique
PASS: concurrent AWBs are uniquely committed without a remote booking call
PASS: concurrent stale QC requests restock exactly once
PASS: concurrent orders cannot redeem the same wallet funds twice
PASS: full rollback succeeds with foreign keys enabled
PASS: migrate/rollback remains repeatable
All audit regression checks passed.
```
