# Metis

Laravel admin application for customers, orders, inventory, promotions, payments and shipping. The separate customer storefront lives in `storefront/` and shares the root application's dependencies and database.

## Development

Install the locked dependencies with `composer install` and `npm ci`. Copy `.env.example` to `.env` for a new local installation and configure its database and application key. Start the admin with `php artisan serve` and the asset watcher with `npm run dev`.

For an existing database, apply changes with `php artisan migrate`. Never use `migrate:fresh` on data that must be retained. See [database setup](database/README.md) and the [storefront guide](storefront/README.md).

## Production

Follow [the deployment guide](docs/deployment.md). Build assets with `npm run build`, then create a runtime-only release:

```sh
python3 scripts/package-production.py
```

The archive excludes development dependencies, tests, audit evidence, SQL backups, local environment files, caches and customer uploads. Install PHP production dependencies from the included lockfile on the release host. Keep environment settings and persistent storage outside release directories.

## Regression checks

See [tests/regression/README.md](tests/regression/README.md). Database checks use disposable MySQL schemas, independently of the configured application database. Tests stay in the development repository and are excluded from production packages.

## License

The original Metis template attribution and MIT license are retained in [LICENSE.md](LICENSE.md). See `storefront/LICENSE-METIS.md` for the storefront template license.
