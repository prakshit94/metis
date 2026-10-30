# Database setup

For an existing database, apply reviewed pending migrations from `/home/user/metis`:

```bash
php artisan migrate --force
```

Only for a new/disposable database that can lose all existing data:

```bash
php artisan migrate:fresh --seed
```

This command deletes the configured database's existing tables, executes the PHP migration files in `database/migrations`, then runs the existing `DatabaseSeeder`. There is no SQL schema baseline. Both applications share these PHP migrations. Never use `migrate:fresh` for an existing database that must keep its data.

The SQL under `database/transitions/metis-20261029` is retained solely for the reviewed backup import. Artisan does not execute it automatically. See that folder's README before importing; do not import the original schema-bearing dump directly into the project database.

The disposable database verification is `tests/regression/migration_fresh_seed.php`.
