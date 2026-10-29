# Validated integer input audit

## Scope and boundary

Reviewed strict numeric type checks, request/session writes, integer and numeric validation rules, strict ID comparisons and numeric membership checks across admin and storefront application PHP sources. The application has 209 Request::validate call sites plus FormRequests and direct validators; changing each controller independently would leave future endpoints exposed to the same mismatch.

Both application providers now register the shared App\Validation\TypedValidator. It retains Laravel's original validation and only converts fields explicitly declared with the integer rule after validation succeeds. This covers Request::validate, FormRequest::validated, Validator::validate/validated and safe validated access. Wildcard/nested arrays and escaped literal-dot keys are handled without restructuring payloads. Nullable, missing, excluded and optional empty fields preserve existing behavior. Raw request input is retained for compatibility.

Integer IDs, counts, pagination and whole-number quantity fields now have consistent validated types regardless of form or JSON transport. Numeric rules continue to permit decimals where the existing business rules permit them. Monetary amounts, stock/order quantities declared numeric, phone/PIN/OTP codes, SKU/external identifiers and boolean representations are not globally cast. No database values, historical orders or migration schemas are rewritten by this change.

## Other confirmed fixes

- Existing storefront carts still recover whole-number strings; new writes explicitly save integers. Invalid/fractional quantities remain rejected rather than truncated.
- Warehouse address updates compare the selected village ID numerically even when the legacy exists-only rule returns a text ID, avoiding a false change when reselecting the same village.
- Coupon preview now loads the authoritative inclusive-price and tax relation fields used by the shared Product model. Its restricted select previously fell back to the rounded legacy base price, potentially disagreeing with the actual order calculator after the exact-price update.

## Verification

Pure validation checks execute in both application containers without a database: typed integer fields, nested ID lists, fractional quantity/money preservation, leading-zero phone/PIN/OTP/SKU preservation, nullable/empty/missing/excluded fields, raw-input preservation, validation callbacks, invalid-value rejection, Request validation, FormRequests and safe validated access.

Full admin audit suite and storefront integration suite run only in disposable MySQL schemas with synthetic records. No live user session, customer account, order or outbound email is used. PHP syntax, route/response/permission contracts are also checked. This is a source and regression audit, not a claim of device testing every UI control or every external integration.

Additional frontend finding: role/permission Select All stored string IDs while row checkboxes, selected-row flags and manual toggles used numeric API IDs. Select All and deselection now retain the same numeric IDs as individual selection. A JavaScript regression verifies bulk/manual selection, deleted/active flags, switching lists and duplicate prevention. Other selection components were reviewed for this mismatch; string identities such as file asset IDs are intentionally preserved.

The older broad workflow test actors were updated with the existing payments/QC/chat feature permissions needed to reach their intended ownership checks. These grants affect synthetic test records only; no application authorization rule or real-user permission was changed.

Final regression results: 214 admin workflow checks, 64 storefront integration checks, 31 catalog/coupon checks, and 38 shared validation checks passed. The 120-case inclusive-price arithmetic sweep and JavaScript product/selection checks also passed. Route (622), response (1,333 methods) and permission (425 permissions/591 gates) contracts passed. Both compiled-view caches were cleared. Test databases were shut down after use.
