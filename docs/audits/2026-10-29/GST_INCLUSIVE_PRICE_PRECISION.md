# Exact GST-inclusive product prices

## Cause

The product form accepted an inclusive amount, converted it to a base price and rounded that base to two decimals before storage. At 18%, 2000 / 1.18 became 1694.92; multiplying that stored base by 1.18 displayed 2000.01.

## Implementation

- Added nullable `products.selling_price_including_tax` DECIMAL(15,2). Values explicitly entered in the product form are authoritative inclusive prices.
- The backend validates inclusive inputs at paisa precision and derives the legacy stored base itself; separately supplied base prices cannot override an inclusive input.
- The shared Product accessor derives the precise taxable price from the inclusive amount and current linked GST rate. Existing products with a null inclusive amount retain their existing two-decimal base price and calculations.
- Product create/edit/clone and detail/list DTOs carry the inclusive amount. The edit form uses the stored amount directly; the storefront displays the same value.
- CSV import/export supports an optional inclusive column. Legacy CSV/API clients retain their base-price semantics. A base-price write clears obsolete inclusive pricing instead of retaining conflicting values.
- Widened order_items.unit_price from DECIMAL(15,2) to DECIMAL(21,8) to preserve the derived taxable unit-price snapshot. Currency totals, tax/discount policies, order rounding and purchase prices retain existing behavior.
- No existing product prices are inferred or rewritten, and historical order totals are not recalculated. A product previously showing 2000.01 must be edited once with the intended 2000.00; its original input cannot be recovered reliably from the rounded base.
- Rollback refuses to discard populated inclusive prices. The wider historical unit-price precision is retained on rollback to avoid losing saved data.

## Verification

Catalog regression checks cover inclusive persistence, repeated edits, server derivation despite a conflicting submitted base, duplication, CSV, legacy-price invalidation and invalid sub-paisa input. Storefront tests cover exact 2000.00 display, two-unit quote/checkout, precise order snapshots and unchanged historical totals. Pure model arithmetic checks cover GST 0/5/12/18/28%, fractional prices and multiple quantities. Existing catalog/storefront suites, JS mapping checks and production asset build are also run.

Result: 30 catalog checks, 59 storefront checks, 120 arithmetic combinations and 3 JavaScript check groups passed. Production build passed (existing bundle-size warning). The migration was applied locally successfully and both view caches were cleared. Checkout preloads tax rates to avoid per-product lookups for canonical prices; nearby-product recommendations also derive their current base price from the inclusive value.
