# Email two-factor authentication

Email verification is an optional alternative to the authenticator app. Existing authenticator enrollments retain their method. Enable either method under `/security` → Two-Factor Auth; switching requires verifying and disabling the existing method first. Enrollment remains inactive until a code proves access to the current account email. Recovery codes work with either method, including delivery outages or account-email changes.

## Delivery configuration

Configure SMTP directly under `/security` → **Mail Configuration**, using an
administrator account with `settings-edit`. Enter delivery credentials, sender,
TLS and connection settings, save with account password/2FA verification, then
send a test to your own account. SMTP no longer requires `.env` edits. See
[Mail configuration UI](MAIL_CONFIGURATION_UI.md) for provider support and controls.

The database override applies only to 2FA/test emails and stores credentials
encrypted. When disabled, the previous Laravel mailer remains the fallback;
`TWO_FACTOR_MAILER` and the existing [Laravel mail configuration](https://laravel.com/docs/12.x/mail)
remain optional deployment alternatives. Log and array mailers, including fallback
chains containing them, are rejected for real verification codes. Messages are
sent synchronously so plaintext OTPs do not enter queue payloads.

## Verification behavior

- Six-digit cryptographically random codes; five-minute expiry.
- Stored as application-keyed, context-specific HMAC hashes, never plaintext.
- Separate contexts for enrollment, each browser/native sign-in challenge, disabling and recovery-code replacement. A code for one purpose cannot authorize another.
- Row locks and deletion on successful consumption prevent reuse. Resending changes the previous code without resetting the attempt budget.
- Five verification attempts per code; existing account-wide second-factor throttle also applies. Email requests have a sixty-second account cooldown and ten-per-hour limit.
- Current-password verification is required for enrollment and settings-code requests. Settings cannot be changed during impersonation. Verification/recovery is still required before disabling or regenerating codes.
- Mail failures expose a sanitized message. First-factor login still returns a pending challenge so an unused recovery code can complete sign-in during an outage.
- Enrollment binds to the verified address. A later profile-email change never silently moves protection to the new mailbox: recovery codes remain usable, and email must be enrolled again.
- Email delivery events join the existing sanitized security audit trail. Existing token expiry, one-active-login policy, attendance, permissions and business rules are retained.

## API additions

`POST /api/security/two-factor/setup` now accepts optional `method: "email"` (the default remains `"authenticator"`). An email setup response contains `method`, masked `email_hint`, and `expires_in`, with no code. Confirm with the existing `action: "confirm"`, current password and emailed code. Confirmation returns the normal one-time recovery-code disclosure.

`POST /api/security/two-factor/email` accepts current password and `action: "confirm" | "disable" | "recovery"` to request the corresponding email code. Complete the action using the existing settings update endpoint. Recovery codes remain accepted for disable/regeneration without requesting email.

Password sign-in for an enrolled email account returns the existing HTTP 202 challenge with `method: "email"`, masked `email_hint` and nullable `email_delivery_error`. The initial code is sent automatically. `POST /api/auth/two-factor/email` accepts `challenge_token` to resend; it cannot extend the pending sign-in's expiry. Verification continues to use `POST /api/auth/two-factor/challenge` with challenge token and code. Browser flows expose the same resend control through a CSRF-protected POST.

## Verification and rollout

Integration tests use disposable schemas and fake mail. They cover both methods, browser/native sessions, enrollment, expiry, resend invalidation and budgets, purpose isolation, email changes, recovery, delivery failures and schema rollback. The original business regression suite is also run against all forward/backward migrations. Frontend tests cover both methods and sensitive-state cleanup.

Only the new email-specific migration is applied locally; no real account is enrolled and no unrelated pending business migration is run. Actual inbox delivery requires the delivery configuration above and has not been claimed as tested.

```sh
php tests/regression/two_factor.php
php tests/regression/two_factor_email_delivery.php
php tests/regression/two_factor_concurrency.php
node tests/regression/two_factor_ui.mjs
```

Final results: 102 combined authenticator/email integration checks, eight delivery
checks, eight concurrency/schema checks, 214 existing business/native checks,
both frontend factor-state checks, route/response/theme checks, Blade compilation
and the production build passed. All 117 migrations ran forward and rolled back
in disposable schemas. The email migration was applied successfully to the local
application; the legacy mailer remains `log` until an administrator saves a delivery configuration.

Automatic review declined cleanup of email-only rate-limit counters created before
test cache isolation was corrected, because those account-ID keys might also belong
to a real account. No cleanup was performed; their normal expiry is retained. New
test runs isolate their rate limiter in memory and do not share these counters.
