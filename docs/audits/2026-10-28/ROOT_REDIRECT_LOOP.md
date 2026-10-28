# Root redirect-loop fix

## Confirmed cause

Read-only inspection found an active user session whose cached permission team differed from the user's actual role assignment. The registered TeamContext service trusted the session value, hiding the existing dashboard permission. The browser authorization exception handler redirected every authenticated denial back to the dashboard, including denial of the dashboard itself, producing an unlimited `/` → `/` loop.

An in-memory check against the affected account confirmed that resolving the actual assigned team restores its existing dashboard permission. No credentials, role grants, real session payloads or business records were changed by that check.

## Changes

- Registered TeamContext now resolves permission scope from role pivots for the authenticated identity on each request. Global Admin/Super Admin role assignments retain global context; scoped users use their actual role scope.
- Browser team context is repaired or cleared automatically. Guest requests clear leftover context. Bearer requests neither trust nor modify browser context.
- New web logins explicitly initialize the identity's permission context and remove any previous cached team selection.
- Authenticated permission denials use a different, owner-accessible Security destination when dashboard access is unavailable. Denial on the fallback page terminates with HTTP 403. Existing dashboard fallback remains for users who have dashboard access.
- API permission failures remain JSON 403, never browser redirects.
- No permissions are added, no password/OTP rules relaxed, and no mass session invalidation or cookie deletion is required.

## Verification

11 isolated checks passed for stale state recovery, bearer/session isolation, global admin handling, non-dashboard users, bounded redirects, terminal 403, API errors, guests and role reassignment. Existing Users/two-step policy suite passed all 52 checks. The full authenticator/email OTP, browser/mobile login, required-enrollment and recovery-code suite also passed, including rollback of the explicitly selected 2FA migrations. Its old fixed four-step rollback assumption was updated so later office migrations do not affect that test. Route, permission and response contracts plus OTP throttle tests passed. A fresh browser request to `/` reached the normal login page without a redirect loop.

The obsolete `App\Http\Middleware\TeamContextMiddleware` class is not registered by the application; bootstrap uses the corrected `App\Services\TeamContext` alias. No changes were made to that unused class.
