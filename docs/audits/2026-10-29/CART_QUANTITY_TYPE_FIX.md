# Checkout cart quantity type fix

The checkout error came from a type mismatch: HTML form inputs submit strings, and Laravel's integer validation validates those values without casting them. updateCart stored the validated value unchanged, while Checkout required is_int(quantity). Valid values such as "2" therefore failed at checkout.

Changes:
- Cast validated quantities to integers before writing the session cart.
- Normalize existing numeric-string quantities when loading the cart without reporting a false availability change or invalidating a valid checkout key.
- Accept bounded digit strings from existing sessions at checkout, then use normalized integers for inventory/pricing input.
- Reject fractional or malformed values rather than truncating them while rendering cart lines.
- Retain product min/max limits, the 1,000-unit cap, current-price checks, stock checks and existing quote/idempotency rules.

Verification uses the disposable MySQL storefront suite with fake email, covering actual string-valued form requests, existing-session checkout and display recovery, fractional/over-limit rejection, and existing pricing/security/order regressions. No live order or customer account was used.

Result: all 64 storefront integration checks passed, including string-valued form requests, existing-cart recovery and quantity-limit regressions. PHP syntax checks passed; storefront view cache cleared.
