# Production repository cleanup

This is source and packaging cleanup. No database migration, seeding, application
cache-store flush, session deletion or production deployment was performed. Composer
discovery regenerated the applications' compiled bootstrap manifests. Runtime uploads,
invoices, chat attachments, environment settings and recovery backups remain
on disk. Inactive backup PHP/Blade trees and scratch SQL transcripts were moved
to an ignored private local archive, outside application source paths. The
remaining active app/view files were verified byte-for-byte unchanged.

## Source tracking

- Composer vendor files, generated provider manifests, runtime cache entries,
  local SQLite files and the generated public storage link are no longer tracked.
- Uploaded/private storage files are no longer tracked. The fifteen original
  category images remain source fixtures because CategorySeeder/ProductSeeder
  depend on them. The ignore allowlist does not admit future uploaded images.
- Existing private import/recovery backups remain local and ignored. Reviewed
  `database/transitions` scripts remain available in Git as manual tooling.
- The orphan Mockery directory and its pre-existing modifications remain local;
  it is no longer tracked or included in production source exports.
- New ignore rules prevent these generated/private paths from being re-added.
  Existing Git history was not rewritten.

The regression suite is maintained verification code, not obsolete scratch code.
It remains in Git; `.gitattributes` excludes it, historical audit evidence,
screenshots and manual import artifacts from production source archives.
Dependency lock files, migrations, seeders, source assets, runtime views, public
static assets, licenses and current deployment notes remain available.

## First deployment after this cleanup

Git removes previously tracked paths when checking out their deletion. That
includes old vendor files and any previously committed uploads. **Do not pull
this cleanup directly into the live application's upload/vendor directories.**

Prepare a separate release directory. Preserve the existing environment/APP_KEY
and keep all real storage content in persistent storage outside that checkout.
Use `APP_ENV=production` and `APP_DEBUG=false` in that release.
Back up the current files/database through the existing release procedure before
changing release paths. An empty fresh storage volume also needs the fifteen
seed images; copy those only when absent, without overwriting existing files.

In the new release, install dependencies from the lock files and build assets:

```sh
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
composer check-platform-reqs --no-dev
npm ci
npm run build
```

Mount/link persistent storage using your existing deployment layout. Then create
the admin and storefront public storage links if absent:

```sh
php artisan storage:link
php storefront/artisan storage:link
```

The storefront has its own configured storage paths; preserve its storage too.
Apply only reviewed outstanding migrations and prepare caches/workers using the
other release notes. Do not run `migrate:fresh`, seeders, import scripts or
`key:generate` against an existing production environment for this cleanup.
Switch traffic only after both applications are ready. Rebuilding in a fresh
release also avoids shipping accumulated old local Vite outputs; the existing
live build directory has not been purged.

## Source archive

After committing the reviewed changes, standard `git archive` uses the included
export-ignore attributes to omit development/private/generated content. Such an
archive is source, not a ready-to-serve build: it requires Composer installation,
the frontend build, production configuration and persistent storage.

Locally retained cleanup manifests and archived files are under
`storage/app/private/repository-cleanup/2026-10-30/`; they are not tracked or
included in source archives.

## Verification

- All 372 active app/view files retained their pre-cleanup contents.
- All 147 protected upload, backup, environment and manual-import files retained
  their content hashes; the local public-storage link remains intact.
- Admin/storefront CLI boot, Composer validation, 622-route/1,344-method contracts,
  theme checks, 21 chat UI checks and 17 header notification checks pass.
- Frontend production build passes (existing large-chunk advisory remains).
- A source export excludes vendor, tests, private data and generated builds;
  all 126 PHP migrations and fifteen seed images remain included.
- Installing that export with no dev dependencies boots both applications and
  registers 621 admin and 32 storefront routes. The only omitted local route is
  Scramble's development-tools JavaScript endpoint; no business route is lost.

Changes are staged for review, not committed, pushed or deployed.
