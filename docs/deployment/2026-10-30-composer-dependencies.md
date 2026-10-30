# Composer dependency fixes — 30 October 2026

The shared admin/storefront runtime now requires PHP 8.3. The effective minimum
was already 8.3 because of the installed Excel and Permission versions. The root
manifest also explicitly requires cURL and PDO MySQL, used by shipping and the
database. The storefront continues to use the parent vendor/autoload.php.

## Dependency changes

| Package | Change |
| --- | --- |
| league/commonmark | Security patch 2.10.1 → 2.10.2 |
| symfony/mime | Security patch 7.4.19 → 7.4.22 |
| laravel/sail | Removed unused Docker development tooling; no Sail workflow/configuration was found |
| symfony/yaml | Removed automatically as Sail's now-unused dependency |
| laravel/tinker, psy/psysh | Development-only; Tinker is unavailable after a production no-dev install |

There are now 14 direct packages and 104 total locked packages: 97 runtime and
7 development. No other package versions changed and no new packages were added.
Composer audit reported no advisories after the updates.

## Why both logging packages remain

Owen Auditing stores security/change history in `audits`, with actor and old/new
values. Spatie Activitylog powers `activity_log`, the activity feed, manual order
events, notifications and per-user read state. Existing models, controllers and
relations use both formats. Removing either would change working features.
Neither history table, its data nor its event hooks were removed or migrated.
The existing ACTIVITY_LOGGER_ENABLED switch still works.

Excel/PhpSpreadsheet, PDF/barcode generation, authentication/permissions and
developer log-tail/error-rendering packages similarly serve different roles;
their dependencies are not interchangeable duplicate implementations.

## API documentation

An application-side Scramble parameter extractor reads structural validation
rules directly from the controller AST for the twelve actions requiring bound
models, current users or dynamic table names. It avoids evaluating those PHP
expressions during documentation generation. It retains required fields, types,
enum values, bounds, nested items and conditional target-period fields. Database
existence, uniqueness and contextual membership checks still execute in the
original controllers; they are not OpenAPI property constraints.

Other actions continue to use normal Scramble extraction and diagnostics. No
validation rules, endpoint authorization, request handling or business services
were changed. Full export retains 399 paths and completes without the previous
4 errors/10 warnings. Generated JSON remains a deployment artifact, not source.

## Deploy

Use PHP 8.3+ satisfying the lock-file requirements in both CLI and PHP-FPM.
From the repository root, install the committed lock file rather than running
an unrestricted update:

```sh
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
composer check-platform-reqs --no-dev
composer audit --locked
php artisan optimize
php artisan queue:restart
```

Allow the Composer post-autoload scripts: they clear obsolete compiled provider
manifests and regenerate discovery for **both** applications. The storefront's
separate manifests otherwise continue referencing removed packages even after
root discovery runs. The shared-install hook now clears them before its Artisan
bootstrap and runs package discovery for the storefront too. Do not copy a
developer machine's discovery caches into a production build.
`artisan optimize` includes this application's existing API-docs cache step and
therefore requires the usual database schema access. Use your normal release
process for PHP-FPM/worker reloads and writable cache/storage directories.

No new database migration or seeding is required for these dependency changes.
No application records were modified during verification. Live SMTP/carrier
delivery and production PHP-FPM have not been exercised by this change.

## Verification

- Composer manifest validation, locked platform checks and production no-dev
  installation dry-run pass.
- A real no-dev install in an isolated release copy succeeds; admin and
  storefront boot with development providers absent and their caches rebuilt.
- 214 existing business/mobile regression checks pass in disposable schemas.
- 40 shared mail-configuration and 8 email OTP checks pass with fake delivery.
- 19 package integration checks cover persistent records from both loggers,
  actors, unchanged saves, idempotent activity reads, deletions, PDF/barcode/XLSX
  generation, MIME attachment serialization and generated API request schemas.
- Route and response-contract checks pass for 622 routes and 1,344 application
  methods.

Run the focused integration suite using the disposable MySQL configuration
described in tests/regression/README.md. To include generated-schema assertions:

```sh
php artisan scramble:export --path=/tmp/metis-openapi.json
METIS_OPENAPI_FILE=/tmp/metis-openapi.json php tests/regression/composer_integration.php
```

## Git tracking cleanup

The subsequent explicit repository cleanup removed vendor and discovery caches
from Git tracking while retaining installed files locally. Composer installation
from the lock file is authoritative. The orphan Mockery directory's existing
local modifications remain preserved. See
[first-deployment instructions](2026-10-30-repository-cleanup.md) before checking
out that cleanup on an existing server; Git can delete previously tracked paths.
