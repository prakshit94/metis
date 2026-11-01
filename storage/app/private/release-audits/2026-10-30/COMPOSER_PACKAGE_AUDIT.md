# Composer package audit — 30 October 2026

> Historical audit snapshot before dependency fixes. See [implemented fixes](/home/user/metis/docs/deployment/2026-10-30-composer-dependencies.md) and the adjacent after-fixes inventory/security JSON for current results.

## Outcome

Most business packages are wired correctly and basic integration checks pass. Dependencies are **not fully production-cleared**: locked versions have three reported security advisories, the effective PHP minimum is higher than the root manifest states, API-document generation has unresolved errors, and generated/vendor files are tracked in Git.

This audit did not upgrade/remove packages, alter composer.json/composer.lock, run migrations/seeders, send mail or change business data. Checks describe the local CLI environment; production PHP/FPM and every external integration were not tested.

## Inventory

- 15 direct Composer packages: 10 runtime and 5 development. PHP is a platform requirement, not another package in that count.
- 106 locked packages in total: 99 runtime and 7 development, including transitive dependencies.
- Registered installed package names/versions exactly match the lock file; no missing, extra or version-mismatched registered packages were found.
- No abandoned packages were reported by the security audit.
- Full inventory: `composer-package-inventory.json` beside this report. JavaScript packages such as Alpine, Bootstrap and QR generation are managed by npm and are outside this Composer inventory.

| Direct package | Locked/installed version | Purpose | Integration result |
| --- | --- | --- | --- |
| `barryvdh/laravel-dompdf` | v3.1.2 | Order/invoice/COD and procurement PDF controllers | Wrapper binding and actual PDF generation passed. |
| `dedoc/scramble` | 0.13.43 | AppServiceProvider route selection/custom extension; config/scramble.php | Registered and generates OpenAPI 3.1, but export has 4 errors and 10 warnings. Not fully clean. |
| `laravel/framework` | v12.69.2 | Application framework, routes, Eloquent, validation, queues and mail | Application boot and existing route/regression checks pass. |
| `laravel/sanctum` | v4.3.3 | User HasApiTokens, stateful API middleware, personal_access_tokens | Token model and middleware wiring verified; no production login or token issuance performed. |
| `laravel/tinker` | v2.11.1 | Artisan interactive REPL | Command is registered. Operational tooling in runtime dependencies; moving it to dev is optional and would change production CLI availability. |
| `maatwebsite/excel` | 4.0.3 | OrdersImport, TargetsImport, TargetsExport and controllers | Actual XLSX export and PhpSpreadsheet readback passed. Requires PHP 8.3. |
| `owen-it/laravel-auditing` | v14.0.6 | Auditable model traits and audits table | Auditor binding and default configuration resolve; published config/audit.php is not required for package defaults. |
| `picqer/php-barcode-generator` | v2.4.2 | Single/bulk COD Blade templates | Actual Code 128 PNG generation passed. This package is used, not redundant. |
| `spatie/laravel-activitylog` | 4.12.3 | App\Concerns\LogsActivity, activity feed and activity_log | Logger binding resolves; application wrapper controls activity-only hooks through activitylog.enabled. |
| `spatie/laravel-permission` | 8.3.0 | User HasRoles, custom role/permission models, middleware, team context | Registrar resolves; existing permissions integration and tests cover team/direct grants. Requires PHP 8.3. |
| `fakerphp/faker` | v1.24.1 | Optional sample-data seeders | Used in test/sample seeders. Correctly require-dev; do not invoke those seeders on a no-dev production install. |
| `laravel/pail` | v1.2.7 | composer dev log-tail process | Command registered; CLI pcntl/posix extensions present locally. Development-only. |
| `laravel/pint` | v1.32.1 | Code formatting | Used successfully during recent checks. Installed version requires PHP 8.3. Development-only. |
| `laravel/sail` | v1.67.0 | Optional Docker development tooling | Commands registered, but no project Docker Compose definition was found outside vendor. Sail workflow not verified; removal needs a tooling decision. |
| `nunomaduro/collision` | v8.9.5 | Development console error rendering | Registered dev provider; do not treat it as the application test framework. |

## Confirmed findings

### 1. Security patch updates are needed

Composer audit against current Packagist advisories reported:

| Package | Installed | Reported issue | Patched version relevant to this lock |
| --- | --- | --- | --- |
| league/commonmark | 2.10.1 | Raw-HTML filtering bypass, medium | 2.10.2 |
| league/commonmark | 2.10.1 | GFM table-parser quadratic-time denial of service, high | 2.10.2 |
| symfony/mime | 7.4.19 | MIME parameter-name email-header injection, medium; CVE-2026-45070 | 7.4.22 |

Sources: [CommonMark HTML advisory](https://github.com/advisories/GHSA-97jj-33gv-5xf9), [CommonMark parser advisory](https://github.com/advisories/GHSA-3q6v-r5mr-hxv8), [Symfony advisory](https://symfony.com/blog/cve-2026-45070-email-header-injection-via-non-token-characters-in-mime-parameter-names).

CommonMark comes through Laravel. Symfony Mime comes through Laravel and Symfony Mailer. No direct application-level Markdown renderer or attacker-controlled MIME parameter-name setter was found in app/routes/views. That does not establish non-exploitability; the locked library versions are affected and should be patched.

A read-only solver simulation succeeded with **0 installs, 2 updates, 0 removals**, targeting exactly CommonMark 2.10.2 and Symfony Mime 7.4.22. No lock/vendor changes were performed. The audit still reports the currently installed old versions. Apply targeted patches in a separate change, then rerun audit plus mail, OTP, exports/PDF and business regressions. Do not run an unrestricted composer update for this purpose.

### 2. Runtime requirements should be made explicit

The root manifest advertises PHP `^8.2`, while installed Excel 4.0.3 and Permission 8.3.0 require `^8.3`. Pint also requires 8.3 in development. The effective runtime minimum is therefore **PHP 8.3**, and deployment documentation/manifest should agree. This is not a failing local platform check: local PHP 8.3.6 passes all locked requirements.

The project uses MySQL and India Post's HTTP client references cURL constants. Neither ext-pdo_mysql nor ext-curl is a hard requirement in the root manifest or locked runtime dependency requirements. Both are loaded locally, but Composer could otherwise approve an environment missing an application-required driver/extension. Declare those requirements when aligning the manifest, with the associated lock metadata update. No driver change is suggested.

### 3. Scramble is integrated but documentation is incomplete

Export required access to database schema metadata. After that access was available, it produced OpenAPI 3.1 with 399 paths and an HTTP security scheme, but returned an error result: **4 generator errors, 10 warnings**.

Generator errors are `GEN001: Scope is not initialized for route` for:

- POST api/returns/{return}/qc (two diagnostics)
- POST api/shipping/shipments/{shipment}/status
- POST orders/{order}/returns

Warnings include request-user-dependent validation and dynamic model/table rules in presence, targets, HR settings and order reasons. These are documentation-generation/inference problems; they do not prove those working business endpoints fail at runtime. Address them with appropriate documentation overrides, schemas/annotations or a verified compatible package fix, rather than changing business logic simply to satisfy the generator.

Production docs remain guarded by RestrictedDocsAccess and the application's viewApiDocs gate. No documentation was published or uploaded by this audit.

### 4. Vendor/cache tracking makes deployment less reproducible

Git tracks vendor/composer files and bootstrap/cache/packages.php/services.php. Generated provider manifests from a development installation can reference development-only providers after a no-dev install unless discovery/cache generation runs correctly. Standard post-autoload package discovery is configured, but generated cache files should not be promoted from a developer machine.

A production `composer install --no-dev --dry-run` succeeds on the current platform and would remove 7 dev packages; no removal was executed. Prefer a clean release build from composer.lock and regenerated package caches. Stop tracking vendor/generated manifests only as a controlled repository/deployment change, after the build process supports it; do not delete live production vendor files.

There is also a pre-existing tracked vendor/mockery/mockery directory that is absent from composer.lock and registered installed packages. Mockery is not autoloadable in the current setup. It is leftover source, not an integrated dependency, and should not be relied upon. No PHPUnit dependency/factory scaffold was found; the repository's documented plain PHP/Node regression scripts are the existing test workflow.

### 5. Preserve the two audit systems unless retention/workload review justifies changes

Owen Auditing and Spatie Activitylog are both used on many models. They record different histories consumed by the application. The activity-only enable switch exists. Do not remove either as a supposedly unused duplicate. This audit confirmed bindings/configuration and code references; it did not benchmark persistent log-write overhead or exercise all audit events on production.

## Checks completed

- Composer validate: valid manifest/lock.
- Composer check-platform-reqs --lock: passed locally.
- Composer install dry-run with and without dev dependencies: passed; no installs/updates/removals executed.
- Official Composer advisory query: 3 advisories affecting 2 transitive packages; no abandoned packages reported.
- Registered installed metadata versus lock: exact package/version match.
- PDF wrapper, Excel service, permission registrar, activity logger and Owen auditor resolved.
- Actual in-memory PDF render, Code 128 PNG render, XLSX export and XLSX readback passed using synthetic content.
- Sanctum user trait/token model/stateful middleware and permission/team configuration references verified.
- API documentation export: completed with 4 errors and 10 warnings, requiring follow-up.
- Full upstream vendor-source integrity comparison could not be completed: Composer status encountered cache/network restrictions. Version matching is not a claim that every vendor source file was compared with its upstream archive.

## Recommended order

1. Target the two security patch updates and verify the audit clears for the installed result.
2. Align the PHP minimum and explicit application extension requirements; verify production CLI and FPM satisfy them.
3. Fix the Scramble documentation diagnostics without modifying business behavior.
4. Review clean vendor/package-discovery deployment and the orphan Mockery directory.
5. Retain business-used packages. Decide separately whether optional Sail/Tinker tooling belongs in your development/operations setup.

No package should be removed just because it is transitive or lacks a direct application import; framework providers, traits and CLI tooling are valid integration points.
