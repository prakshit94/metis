# Production deployment

Serve this single Laravel application through `public/index.php` with PHP-FPM. The domain root serves the customer storefront; `/admin` and `/admin/login` serve staff administration. Existing `/api` endpoints remain unchanged. No separate storefront virtual host, Composer installation or Laravel bootstrap is needed.

## Build a clean release

From the source checkout, using the committed lockfiles:

```sh
npm ci
npm run build
python3 scripts/package-production.py
```

The default output is `releases/metis-production.tar.gz`. An existing archive is never overwritten; use `--output /path/to/new-release.tar.gz` for subsequent releases. The build clears only the generated `public/build` directory, removing obsolete asset hashes.

The package includes application code, views, migrations, static/built assets, dependency manifests and empty writable-directory placeholders. It excludes tests, development tools, `vendor`, `node_modules`, `.env`, generated caches, audit reports, manual SQL imports, seed/demo data and customer uploads. Tests and historical evidence may remain in the development checkout without being deployed. Keep `composer.lock` in the release; do not copy a development `vendor` directory to production.

## Configure the target release

Extract into a new release directory. Attach the deployment's persistent `.env` and `storage` before installing dependencies. Preserve existing uploads, invoices, sessions and the existing `APP_KEY`; do not regenerate an established application's encryption key.

Use production environment values, including:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.com
LOG_LEVEL=warning
SESSION_SECURE_COOKIE=true
```

Configure database, cache, queue and mail connections for the target environment. `.env.example` deliberately has no application key or live credentials. Generate a new application key only for a brand-new installation, before storing encrypted data.

Install the locked runtime dependencies:

```sh
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
```

The PHP-FPM and queue identities need write access to `storage/` and `bootstrap/cache/`. Set ownership for those directories; do not make the source tree world-writable.

## Migrate and cache

Back up the target database and use the deployment's normal maintenance/traffic-draining procedure when applying changes. Run migrations once from the root application:

```sh
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

The promotion fixes require `2026_11_01_000001_harden_promotion_rewards.php`. Apply it before serving the updated application. It repairs legacy targeting/reward metadata without rewriting saved order totals. The migration can resume existing schema objects after an interrupted run, preserves already-assigned customer referral codes and reward thresholds, and backfills missing codes in 200-row transactions. Malformed promotion targeting fails preflight rather than silently broadening eligibility. Conflicting existing reward ownership also stops preflight. Rollback refuses before dropping schema if personal ownership, retired milestones or reward/recovery history would be lost; use a reviewed forward fix after the feature has been used. Do not run `migrate:fresh`, general demo seeders, or backup-import SQL during an ordinary production deployment.

After switching to the new release, reload PHP-FPM/OPcache as appropriate and restart long-running queue workers with `php artisan queue:restart`. Keep the scheduler configured to run `php artisan schedule:run` every minute. Verify `/up`, authentication and the affected order/promotion workflows against the deployed environment.

## Web-server routing

Set the application document root to the release's `public/` directory. A typical Nginx Laravel routing fragment is:

```nginx
index index.php;

location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ ^/index\.php(/|$) {
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    fastcgi_param DOCUMENT_ROOT $realpath_root;
    fastcgi_pass unix:/run/php/php-fpm.sock;
    internal;
}

location ~ \.(?:php|phar)$ {
    return 404;
}

location ~ /\.(?!well-known).* {
    deny all;
}
```

Use the PHP-FPM socket and TLS/server-name configuration appropriate to the host. Neither the repository root nor private storage should be a document root.

## Verification before deployment

Run the structural checks, production build and appropriate disposable-database regression suite in CI. The focused promotion/order regression is `tests/regression/promotions_orders.php`; the broader business regression is `tests/regression/audit_fixes.php`. Production packages intentionally contain neither test scripts nor their synthetic fixtures.

Creating a release archive does not deploy it, apply live migrations, or verify the target's credentials and external carrier/mail integrations.

The routing consolidation needs no new database migration. Clear old application caches before rebuilding them. Stop the former standalone storefront process and update any reverse proxy to the common `public/` directory. Staff bookmarks now use `/admin/...`; customers use root shop URLs. Previous standalone customer sessions and verification links may require a new login or verification email. Keep the existing `APP_KEY` and database intact.
