# Database setup

Run from `/home/user/metis`:

```bash
php artisan migrate:fresh --seed
```

This command deletes the configured database's existing tables, executes all PHP migration files in `database/migrations`, then runs the existing `DatabaseSeeder`. There is no SQL schema baseline. Both applications share these PHP migrations. For an existing database that must keep its data, use `php artisan migrate` instead.

The SQL under `database/transitions/metis-20261029` is retained solely for the reviewed backup import. Artisan does not execute it automatically. See that folder's README before importing; do not import the original schema-bearing dump directly into the project database.

The disposable database verification is `tests/regression/migration_fresh_seed.php`.

Promotion schema ownership: the original coupon/offer migrations already create
targeting, cashback and gift configuration; the original parties migration owns
`referral_code`. `2026_11_01_000001_harden_promotion_rewards.php` adds only the new
recipient, milestone-retirement, entitlement/recovery and activation-lock schema,
then repairs legacy values in existing columns. It is a required forward upgrade
for existing installations; editing an already-recorded create-table migration
does not upgrade them. Keep its migration ledger entry and source file.

The dated SQL transition snapshot describes the schema as audited on 29 October
2026. It is historical evidence, not the current schema or an alternate migration
source. New migrations require a fresh audit before regenerating that import.
