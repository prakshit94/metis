# Admin order list: customer attribution

Reviewed the supplied status/date/pagination URL, the order index query and visibility scopes, list/detail serialization, stock flags and the frontend order mapper/Placed By cell.

## Changes

- Orders created through the shop have a storefront checkout link but no staff creator. The list previously used only creator.name, resulting in N/A. A read-only attribution adapter now resolves the linked customer account's customer name and labels its source as Shop · Customer.
- Staff-created orders retain their recorded creator and office display. Orders with neither a checkout link nor a creator display Not recorded; they are not assumed to be customer-placed.
- Delivery contact names are not used as the actor identity. Passwords, login codes and recipient phone numbers are not included in attribution.
- Attribution is attached only after the order query's existing visibility/filter/pagination rules select records, using a single indexed batch lookup for those IDs. It does not join into the paginated query, change order counts, impersonate staff or update any order fields.
- Detail responses include the same attribution so opening or refreshing an order keeps the same identity.
- Non-ID sorting now adds order ID as a deterministic tie-breaker, avoiding unstable page membership when totals/status/dates tie. The supplied descending-ID sort retains its behavior.

No order lifecycle, pricing, stock reservation, status-filter definitions, ownership permissions or date-window rules were changed. No live order/customer was modified during verification.

## Verification

Disposable database regression covers linked shop customer vs different delivery contact, untouched created_by/order fields, staff/unknown fallback, restricted attribution fields, the supplied status/date filters, list/detail parity and tied-total pagination. Frontend mapping checks cover customer, staff, unknown and avatar fallback. Route/response contracts, PHP/JS syntax, Blade compilation and production build are also checked.

Result: all 10 attribution/filter/pagination checks passed. Frontend attribution mapping, 622 route contracts, 1,334 response-method contracts, syntax and Blade compilation passed. Production build succeeded with the existing bundle-size warning. Test database stopped and compiled views cleared.
