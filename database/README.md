# Consolidated migrations

Each application table has one creation migration. Fresh databases use the normal `php artisan migrate` command.

## Existing databases

Before running migrations on a database created with the former combined migrations:

```sh
php artisan schema:repair-consolidated
php artisan schema:repair-consolidated --apply
php artisan migrate
```

The first command previews the changes. `--apply` adds missing nullable India Post tracking fields and their indexes, then registers the split migrations for tables already created by a recorded combined migration. It preserves the original batch and historical migration records. It does not change orders, stock, payments, or other application records. Repeating the repair does not add duplicate columns, indexes, or migration records.

Do not use `migrate:fresh` on a database containing application data. Editing an already-executed creation migration does not upgrade an existing table.

## Regression checks

```sh
php tests/regression/audit_fixes.php
```

This requires MySQL credentials with permission to create a temporary database. It creates a uniquely named test database, runs the migrations and regression checks there, then drops only that test database.

The stock-ledger CSV exports recorded movement quantities. Historical before/after stock balances are not stored and are not fabricated. Accounting remains optional: payment hooks run only when all three accounting tables exist; this project does not define an accounting installation.
