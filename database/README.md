# Database setup

Run from `/home/user/metis`:

```bash
php artisan migrate:fresh --seed
```

This command deletes the configured database's existing tables, executes all 125 PHP migration files in `database/migrations`, then runs the existing `DatabaseSeeder`. There is no SQL schema baseline. Both applications share these PHP migrations. For an existing database that must keep its data, use `php artisan migrate` instead.

The SQL under `database/transitions/metis-20261029` is retained solely for the reviewed backup import. Artisan does not execute it automatically. See that folder's README before importing; do not import the original schema-bearing dump directly into the project database.

The disposable database verification is `tests/regression/migration_fresh_seed.php`.
