# Product catalog multi-warehouse review

Scope: `/catalog/products`, its Alpine form/table/preview, product list/detail/save APIs, warehouse SKU bulk actions, stock relationships, and the existing import/delete protections. This is a product-catalog review, not an audit of every application module.

## Existing behavior

- A product is shared across warehouses. Its prices, tax, category, default warehouse and global tracking settings belong to the product.
- A stock row is unique per product and warehouse. Quantity, reserved quantity, dispatched quantity, and nullable SKU/overselling overrides belong to that row.
- A null override inherits the product setting. An explicit false or zero is a local setting.
- Unreserved demand includes future, pending, and pending-confirmation orders in that warehouse. Confirmed orders are already represented by reserved quantity and must not be subtracted again.
- Sellable quantity is computed for each warehouse as `max(0, quantity - reserved - pending + applicable overselling allowance)`. Without overselling, the allowance is zero. The existing global zero/unset allowance fallback of 999 is retained; an explicit warehouse allowance of zero remains zero.
- The all-warehouse sellable quantity sums warehouse results. A selected warehouse or user state scopes stock and pending-order data. Product-level fields remain shared.

## Corrections

1. Added an independent **Warehouse for these rules** selector. Opening an edit from a warehouse filter selects that warehouse's rules without rewriting the product's default warehouse. The API validates the rule target separately and preserves the existing default, including an unchanged default outside a state-scoped user's warehouse options.
2. Editing loads all accessible warehouse rows, so switching the rule selector restores that warehouse's actual settings instead of replacing them with inherited values.
3. The full list and detail APIs now apply a supplied warehouse ID to stock rows as well as aggregate totals; callers do not need an extra scoping flag.
4. Frontend availability and overselling indicators preserve explicit zero local allowances and include local overrides in the all-warehouse summary.
5. Warehouse SKU bulk actions reuse the unique stock row, including a soft-deleted row, rather than attempting a duplicate insert. Quantities and other overrides remain intact.
6. Preview reserved-demand totals include pending quantities consistently for selected and aggregated warehouse views.

The prior fixes for decimal pricing, global SKU persistence, clearing settings, duplicate submissions and hidden-tab validation remain in place.

## Verification

- `node tests/regression/products.cjs`: real Alpine component methods with isolated fetch/form fixtures; availability/SKU rules, zero allowances, warehouse switching, precision and save behavior.
- `php tests/regression/products_backend.php`: disposable database copied from the application schema, with two warehouses, different stock/rules, pending and confirmed orders, and state-restricted users. Covers independent rule saves, unchanged default/quantities, list/detail scope, cross-state rejection, nullable inheritance, bulk SKU actions, imports, restore, and permanent-delete stock protection.
- Browser: selected the second warehouse, opened an existing product for editing, confirmed default warehouse 1 and rule warehouse 2, switched the rule selector, and confirmed no runtime errors. Cancelled without saving application records.
- Production asset build, PHP syntax and Blade compilation passed. ESLint reports zero errors and existing style warnings.

Application business records were not changed by these regression fixtures or browser checks. No changes were made to tax calculations, order reservation/dispatch services, warehouse transfers, or CSV stock modes.
