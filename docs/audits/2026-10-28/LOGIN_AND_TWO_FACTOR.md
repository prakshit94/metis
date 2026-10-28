# Login presentation and two-factor audit — 28 October 2026

## Findings addressed

1. Authenticator and SMS switches were browser-only flags. The initial authenticator flag was hardcoded `true`; password sign-in never checked a second factor. Replaced authenticator flags with authenticated server status and a real challenge before either session/token issuance.
2. Recovery codes were fixed example strings and regeneration did nothing. Replaced them with ten random 80-bit, single-use codes, stored as SHA-256 hashes. Codes are returned once after enrollment/replacement, with a local text download.
3. Enrollment had no password reauthentication, expiring setup, or proof of possession. Setup now requires the current password, expires after ten minutes, and enables protection only after a valid authenticator code plus password confirmation.
4. Settings changes had no second-factor verification or impersonation restriction. Disabling and replacing recovery codes require current password plus an unused authenticator/recovery code; impersonated sessions cannot manage credentials.
5. There was no second-factor replay/guessing protection or audit trail. Added account verification throttling (five checks per five minutes), five-minute opaque login challenges with an additional five-attempt budget, atomic consumption with row locks, and sanitized account-owned events.
6. In-flight sign-in needed invalidation across credential/security changes. Challenges bind to a password fingerprint and factor version; changing the password or factor configuration invalidates them. Inactive/suspended/deleted accounts cannot complete verification. Emergency lockdown cancels pending challenges.
7. Activating protection must not leave older credentials usable. Enrollment/configuration updates revoke other tokens/database sessions and rotate remember tokens, preserving the credential making the change. Authentication holds the factor lock through credential issuance to prevent enrollment races.
8. The security page claimed SMS support with no provider. It now reports SMS as unavailable, with no fake enable action.
9. The login form lacked the requested collapse control and centered logo. Added accessible hide/restore controls, focus restoration, and centered configured branding. Background selection, form action, field names, validation, remember preference and theme behavior remain in place.

## Authentication and business boundaries

Two-factor enrollment is optional per account; existing accounts remain password-only until enrollment is confirmed. Both successful routes reuse the existing web-session/mobile-token issuance helpers, including the single-active-login policy, attendance check-in, permission/team context, token expiry, and intended browser destination. First-factor success alone never revokes the previous login, records a successful login, or checks in attendance for an enrolled account.

No sales, payment, inventory, customer, pricing or HR business rules were changed by this task. Existing privileged impersonation remains available; its ability to change another person's second-factor credentials is blocked.

Secrets are encrypted with Laravel's application encryption key in dedicated tables, outside the audited/serialized User model. Security events contain event name, account ID, IP and timestamp, never passwords, authenticator keys, submitted codes, challenge bearers or recovery codes. Setup/status/change responses use `Cache-Control: no-store`. The UI keeps sensitive setup/recovery data in memory only and clears it when leaving the section/page. The activity endpoint merges at most fifty login and fifty factor events, returning the latest fifty.

TOTP uses SHA-1, six digits, thirty-second steps and a ±1-step tolerance, with monotonic accepted-step tracking. Algorithm outputs are checked against the primary [RFC 6238](https://www.rfc-editor.org/rfc/rfc6238) and [RFC 4226](https://www.rfc-editor.org/rfc/rfc4226) test vectors. Authenticator enrollment supports a manual setup key and an `otpauth://` link; no external QR service receives secrets.

## Mobile API contract

Existing `POST /api/auth/login` remains unchanged for unenrolled accounts. For enrolled accounts, correct email/password returns HTTP 202:

```json
{"two_factor_required":true,"challenge_token":"<opaque bearer>","expires_in":300,"message":"Enter an authenticator or recovery code."}
```

The native client submits `POST /api/auth/two-factor/challenge` with `challenge_token` and `code`. Success returns the existing token/user/expiry response; invalid/expired/consumed codes return 422; throttling returns 429. The opaque challenge is not an access token. A new first-factor challenge replaces the previous challenge for the same channel. Browser and mobile challenges cannot cross channels.

Authenticated settings work with bearer tokens and browser sessions:

- `GET /api/security/two-factor`: enabled, confirmation time, remaining recovery count, SMS availability; no secrets.
- `POST /api/security/two-factor/setup`: `current_password`; returns expiring enrollment key/URI.
- `POST /api/security/two-factor`: `action` (`confirm`, `disable`, `recovery`), `current_password`, `code`; returns updated status and, when generated, one-time recovery-code disclosure.

Native app source is not present in this repository. These endpoints support native clients; a native client must handle HTTP 202 and present its own code-entry screen before storing the returned access token.

## Verification

- 70 dedicated integration checks passed: sixteen RFC vectors, enrollment, encryption/serialization, native/browser login, remember preference, rejected replay/expired challenges, password/account changes, throttling, recovery replacement, impersonation restriction, authenticated security-page rendering, audit secrecy and migration rollback.
- Eight focused concurrency/schema checks passed: simultaneous browser/native consumption permits exactly one successful use of a recovery code or TOTP step; independent security rollback succeeds.
- All 214 existing business/mobile regression checks passed with all 116 forward migrations and full rollback in disposable MySQL schemas. This broad run preceded the final transaction wrapping; the subsequent dedicated integration suite verified the final login transaction flow.
- Server-backed UI state, enrollment, confirmation, CSRF headers, error handling, and sensitive-state cleanup passed the JS regression test.
- 607 route handlers/model parameters, 1,256 application methods' response contracts, theme checks, PHP syntax, Blade compilation and the production frontend build passed.
- Live login inspected at desktop and 375-pixel mobile widths, including collapse/restore and dark mode; configured background and centered logo remain visible, with no horizontal overflow.

Only `2026_10_28_100000_create_two_factor_security_tables.php` was applied to the local application's database. Unrelated pending migrations were not run. No real account was enrolled, disabled, or issued recovery credentials during verification. Database integration tests used disposable schemas and no carrier/payment calls.

Run the dedicated checks using the isolated MySQL socket described in `tests/regression/README.md`:

```sh
php tests/regression/two_factor.php
php tests/regression/two_factor_concurrency.php
node tests/regression/two_factor_ui.mjs
```
