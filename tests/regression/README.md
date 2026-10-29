# Audit regression checks

Run syntax/route/theme checks without application database access:

```sh
php tests/regression/route_contracts.php
node tests/regression/theme.mjs
```

The database suite requires MySQL and permission to create/drop disposable databases:

```sh
METIS_TEST_MYSQL_SOCKET=/path/to/test/mysql.sock php tests/regression/audit_fixes.php
```

Optional credentials: `METIS_TEST_MYSQL_USER`, `METIS_TEST_MYSQL_PASSWORD`. Defaults target the isolated local audit socket under `/tmp`, not the application's configured database. Each run creates a random `metis_regression_*` schema and drops it at shutdown. `METIS_EXISTING_TEST_DB` is an internal worker setting; do not set it when launching the suite.

The suite checks valid pricing/gifts/payments/returns/transfers, rejection/rollback of invalid operations, real bearer-authenticated HTTP requests, webhook replay, concurrent sequences/AWBs/QC/wallet use and full forward/backward migrations. Concurrent workers call no external booking service. It does not modify customer data, deploy the application or test real carrier credentials/native clients.

Mobile workflow checks are included automatically by `audit_fixes.php` through
`mobile_support.php`. They exercise bearer authentication, JSON responses, target
mutations and authorization, all 17 report streams, CSV templates, upload validation,
referral responses, and native security-session handling in the disposable schema.

After `npm run build`, run `python3 tests/regression/responsive_browser.py` for
shared CSS checks in Chrome at 320, 375, 768 and 1280 CSS-pixel viewport widths in
both themes. The fixture loads the actual built stylesheet, checks document overflow,
sidebar geometry, table scrolling and input sizing, and uses no application database.
These are shared-layout checks, not complete device tests of every business screen.

The follow-up suite also includes `detailed_workflows.php` automatically. It sweeps
static API reads, exercises ordinary CRUD, real CSV uploads and visibility boundaries,
a native chat exchange, account blocking and native order pricing/lifecycle checks.
Every request restores the default auth guard to prevent cross-request false positives.
Run `php tests/regression/response_contracts.php` to check application method ASTs for
misplaced middleware objects in business data arrays.

Two-factor authentication checks:

```sh
php tests/regression/two_factor.php
node tests/regression/two_factor_ui.mjs
```

The PHP suite uses the same disposable MySQL setup. It covers RFC TOTP/HOTP
vectors, enrollment and encrypted storage, native and browser authentication,
replay/expiry/throttling, recovery replacement, password/account changes,
impersonation restrictions, sanitized audit records and migration rollback.
The JS suite verifies the server-backed settings flow and sensitive-state cleanup.

Email two-factor coverage is included by `two_factor.php` through
`two_factor_email.php`, using fake mail only. Run
`php tests/regression/two_factor_email_delivery.php` for focused resend budget,
transport-failure and independent email-migration rollback checks. The bootstrap
moves the eagerly resolved rate limiter to in-memory cache while preserving named
limit registrations, keeping these tests separate from application rate-limit state.

Run `php tests/regression/security_mail_settings.php` for focused database-managed
mail configuration checks. It creates only security dependencies in a disposable
schema and uses fake mail. SMTP credentials are never loaded from or written to
the live application by this suite. Permission cache state is also isolated in
memory, preserving live authorization caches.

`node tests/regression/authenticator_qr.mjs` independently decodes locally generated
QR images to verify the exact enrollment URI, TOTP parameters and white quiet zone.
It uses a public RFC key only. Frontend checks also reject late QR rendering after
cancellation and verify manual fallback on rendering failure.

Users directory checks:

```sh
node tests/regression/users_ui.mjs
php tests/regression/users_directory.php
```

The PHP suite uses a disposable schema and exercises real bearer-authenticated
user APIs, LOB boundaries, profile/role preservation, administrator safeguards,
password reset revocation, security status filters, minimal manager choices and
complete Blade rendering. It writes read-only fixture HTML/JSON under
`/tmp/metis-users-preview` for optional visual checks; no live users are changed.

`node tests/regression/user_action_menu.mjs` checks Users action-menu positioning
configuration, delayed visibility measurement, keyboard access and observer/listener
cleanup. Menus are portaled outside the scrolling table/card without changing the
underlying user actions.

Shared integer validation (no database required):

```sh
php tests/regression/typed_validation.php
php tests/regression/typed_validation.php --storefront
node tests/regression/integer_selection.mjs
```

Only fields declared `integer` are normalized after successful validation. Keep
money/fractional quantities as `numeric` and phone/PIN/OTP/SKU values as text or
`digits` rules. Use validated data for persisted application input.

PHP migration and seeder verification (disposable database only):

```sh
METIS_TEST_MYSQL_SOCKET=/path/to/disposable/mysql.sock php tests/regression/migration_fresh_seed.php
```

Fresh installs execute every PHP migration in `database/migrations`, followed by
`DatabaseSeeder`. No Laravel SQL schema dump is present. The storefront shares
the root migration directory. Backup import SQL under `database/transitions`
is manual tooling and is never executed by Artisan migrations.

MySQL Workbench import SQL checks (synthetic source data, disposable schemas):

```sh
METIS_TEST_MYSQL_SOCKET=/path/to/disposable/mysql.sock php tests/regression/import_sql_workbench.php
```

This validates the complete generated import file with Safe Updates enabled,
including partial-source/missing-migration guards, transaction rollback, restored
session settings, preserved schema/history and all verification queries. Real
backup rows are never loaded by this test.

Village manual-sync audit (synthetic records and mocked postal requests):

```sh
METIS_TEST_MYSQL_SOCKET=/path/to/disposable/mysql.sock php tests/regression/villages_manual_sync.php
node tests/regression/villages_frontend.mjs
```

Village saves must never call India Post. Sync is a separate confirmed action.
The backend suite also checks state-scoped writes, retry/stop/locking behavior,
CSV rollback after a full insert batch, and preserved service priorities.

India Post duplicate-barcode recovery checks:

```sh
METIS_TEST_MYSQL_SOCKET=/path/to/test/mysql.sock php tests/regression/india_post_duplicate_awb.php
```

Uses disposable data and a mocked carrier. Covers stale counters, committed allocations,
explicit duplicate-only retries, unsafe retry exclusions, range transitions, actual
ready-to-ship rollback/success and concurrent allocation. No live bookings are made.

Run `php tests/regression/awb_log_search.php` with the isolated MySQL socket above
for office-scoped AWB log search, order/partial-barcode matching, literal wildcard
handling, historical orders and bounded pagination. Fixtures are disposable and
no carrier calls or production updates are made.
