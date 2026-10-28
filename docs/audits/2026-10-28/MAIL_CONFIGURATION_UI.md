# Mail configuration on the Security page

Administrators with the existing `settings-edit` permission can now open
`/security` → **Mail Configuration** and configure email 2FA without editing
`.env`. Ordinary users and impersonated sessions cannot read or modify this
configuration.

## Using the form

The form loads saved values first. Otherwise it prefills Gmail SMTP:
`smtp.gmail.com`, port `587`, STARTTLS and a 15-second timeout. Enter your own full
Gmail address as username/sender and a Google app password; credentials remain
blank and delivery stays disabled until explicitly saved. Existing saved values
retain priority. See [Google’s SMTP settings](https://support.google.com/mail/answer/7104828)
and [app-password guidance](https://support.google.com/accounts/answer/185833).

1. Select SMTP and enable the saved configuration.
2. Enter host, port, STARTTLS or SMTPS, username/password, sender name/address,
   optional reply-to, and a connection timeout between one and sixty seconds.
3. Confirm the current account password. If the administrator is enrolled in
   2FA, also provide a fresh authenticator, purpose-bound email, or recovery code.
4. Save. Blank credential fields retain saved values; credentials are never
   returned to the browser. Changing API providers never reuses the old provider's
   API key.
5. Re-enter the account password and a fresh 2FA code to send a test email to
   the signed-in administrator's own account address. The test uses saved settings.

SMTP works with installed dependencies. Postmark, Resend and SES configuration
fields are available when their respective Laravel transport packages are
installed; unsupported choices are labeled and disabled. Existing configured
mailers can also be selected when supported and free of log/array fallbacks.

Disabling the override restores the prior application mailer. An explicit
"clear saved credentials" option is available only while disabling the override.
These settings apply to verification/test emails only; global business-mail
transport and sender defaults remain unchanged.

## Protection and scope

The singleton configuration is encrypted with Laravel Crypt in
`security_mail_settings`, outside audited general-purpose settings models.
Credentials, account passwords, submitted verification codes and credential
values never enter audit events or API responses. Secret presence flags allow
editing without retrieving secrets. Responses are marked no-store; the frontend
clears secrets on completion, errors, section changes and page departure.

Mutations use account password reauthentication and enrolled 2FA verification,
with existing throttles. Failed verification attempts and their audit records
persist even when the settings update is rejected. Test messages are limited to
three per five minutes per administrator and contain no authentication codes.
SMTP requires TLS with normal certificate verification. Raw transport exceptions
are replaced by sanitized errors.

The saved transport is resolved lazily for email 2FA, with a dedicated mailer
instance refreshed when settings change. No application configuration files or
`.env` credentials are overwritten. The new database migration adds only the
mail configuration table; no existing account was enrolled and no actual SMTP
credentials were saved during implementation.

## Verification

- 37 focused HTTP/storage checks passed: permission boundaries, password and
  authenticator/email verification, replay rejection, failed-attempt persistence,
  encrypted credential storage, redaction, preservation/clearing, provider-key
  separation, SMTP validation/TLS, saved transport routing, test sender/recipient,
  unchanged business-mail defaults and independent migration rollback.
- All 102 combined browser/native authenticator/email integration checks passed.
- All 214 existing business/mobile checks passed, including all 118 migrations,
  rollback and repeated forward/backward migration.
- Three frontend state suites passed, including mail credential cleanup and test
  submission. Route/response checks, PHP syntax, Blade compilation and production
  asset build passed.
- Actual Blade form markup and built assets were checked in an isolated read-only
  browser fixture on desktop and 375-pixel mobile, in both themes. No horizontal
  overflow; mobile text inputs use 16px type. Real account authentication and SMTP
  credentials were not entered in the browser.
- The new mail-settings migration was applied to the local application. Other
  pending business migrations were not applied. Real inbox delivery requires the
  administrator to enter their provider details and run the test action.

```sh
php tests/regression/security_mail_settings.php
php tests/regression/two_factor.php
php tests/regression/audit_fixes.php
node tests/regression/two_factor_ui.mjs
```
