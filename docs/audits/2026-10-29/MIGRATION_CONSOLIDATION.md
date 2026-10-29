# PHP migration installation

The generated MySQL baseline and 119 per-table SQL definitions were removed at the user's request. The 125 original PHP migration files remain unchanged. `php artisan migrate:fresh --seed` executes those PHP migrations and the existing DatabaseSeeder; no schema dump is loaded.

The baseline builder, baseline-only tests and SchemaLoaded cache listeners were removed. The existing installation repair command is retained. Storefront migrations still use the root database directory. Backup-import SQL remains in `database/transitions/metis-20261029` for the user's manual test and does not run automatically.

Fresh migration/seeding verification uses an isolated disposable database only. No live database was refreshed by this change.

Verification passed: `migrate:fresh --seed` executed all 125 PHP migrations on a randomly named disposable schema, recreated all 119 tables with identical columns/types/defaults/indexes/foreign keys, seeded 425 permissions and the existing roles/teams, and retained all original migration file hashes. All 10 regression assertions passed.
