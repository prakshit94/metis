# Metis admin and customer storefront

Laravel 12 admin/backend with a separate customer storefront in `storefront/`.
Both applications share the parent Composer installation, domain services,
database and PHP migrations. Their web entry points and sessions remain separate.

## Requirements

- PHP 8.3+ and the extensions required by `composer.lock`, including cURL/PDO MySQL.
- Composer, MySQL, and Node.js/npm satisfying the locked build dependencies.
- Writable Laravel storage/cache directories and the configured queue workers.

## Development

For a new checkout, create `.env` from `.env.example` and configure its database
and application settings. Preserve an existing installation's `.env` and APP_KEY.

```sh
composer install
npm ci
npm run build
php artisan serve --port=8000
php storefront/artisan serve --port=8001
```

For a **new installation only**, generate its APP_KEY with `php artisan key:generate`
and initialize its schema using the reviewed migrations. Existing environments
must keep their current key so encrypted security/mail settings remain readable.
Use `php artisan migrate` for existing databases; `migrate:fresh` destroys data.
Seeders and historical backup-import scripts are separate operator actions.

The storefront is available at `/shop` on its configured host. See
[storefront setup](storefront/README.md) for its shared-runtime configuration.

## Production deployment

Build a new release directory and install the committed lock files:

```sh
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
composer check-platform-reqs --no-dev
npm ci
npm run build
```

Set `APP_ENV=production` and `APP_DEBUG=false`, preserve/mount existing persistent storage,
and follow the reviewed migration/cache/worker steps in the deployment notes.
Set the admin web root to `public/` and the storefront web root to
`storefront/public/`. Recreate each application's public storage link with its
`artisan storage:link` command when preparing a release. Run `php artisan optimize`
and refresh the storefront's caches according to its own configuration.

**This cleanup stops tracking vendor, uploaded files and private recovery data.**
Do not apply that checkout directly over live storage/vendor files: Git can remove
previously tracked paths. Prepare a new release with persistent storage and
fresh dependency installation before switching traffic. Existing local uploads,
invoices, attachments, backups, environment settings and database records are
preserved by this cleanup.

- [Repository cleanup and first deployment](docs/deployment/2026-10-30-repository-cleanup.md)
- [Composer changes and shared package discovery](docs/deployment/2026-10-30-composer-dependencies.md)
- [Chat/branding/permission release](docs/deployment/2026-10-30-chat-branding-permissions.md)
- [Database migration/import notes](database/README.md)

## Verification and source archives

Meaningful regression tests remain in Git; see
[regression instructions](tests/regression/README.md). They use disposable schemas
and fake delivery rather than production records.

`.gitattributes` excludes tests, historical audit/screenshots and manual import
artifacts from `git archive` production source exports. Build dependencies and
compiled assets in the new release; do not ship local caches, development
sessions or private backups. Archive a reviewed commit containing the cleanup.

## License

Retain [LICENSE.md](LICENSE.md) and the storefront's attribution/license files.
The admin frontend is based on the Metis Bootstrap template.
