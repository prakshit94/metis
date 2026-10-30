# Chat, branding and permission compatibility release

Compared with commit `b3a58efc` (tracking-ID fix). Production has not been deployed or live-tested by this review.

## Release scope

- Chat fixes: idempotent sends/notifications, transaction/audit rollback, active membership and ownership checks, scoped forwarding, read receipts, privacy filtering and stale UI response guards.
- Performance: one directory read per poll, latest-session query, batched missing receipts, hidden-tab pause, bounded catch-up and indexed history cursors.
- Compatibility: retain creation-time history ordering, with ID as a deterministic tie-breaker; support backdated and nullable legacy timestamps. Existing request/response envelopes and optional pagination remain supported.
- UI: existing People directory is reachable on mobile; controls follow existing permissions and group roles. External Axios CDN replaced by built same-origin transport.
- Branding: preview-backed uploaded logos resolve correctly; a successful App Logo save updates the existing header without a full page reload.
- Permissions: explicit LegacyPermissionGrantsSeeder is available for intended legacy-import repairs. No automatic role reset or compatibility repair is introduced by deployment.

## Deploy

1. Back up production. Deploy the reviewed source files and freshly built `public/build` assets together through the existing release process. Keep production `.env`, uploads, invoices and storage contents.
2. Apply the additive chat history index migration:

```sh
php artisan migrate --path=database/migrations/2026_10_30_020000_index_chat_history_cursor.php --force
php artisan view:clear
```

The nonunique index covers `(conversation_id, deleted_at, created_at, id)`. It changes no message values. Index creation can take time on a large table; use the normal deployment window. Its rollback removes only the named index.

3. Refresh browser assets. With authorized test accounts, verify existing send/reply/edit/upload/read behavior, group roles and member operations, older history, mobile People access and the uploaded header logo. Verify a read-only account cannot send or manage groups.
4. Production chat attachments keep the existing configured storage policy. Public-disk attachment URLs remain public to anyone with the URL; this release does not move or delete files.

## Permission seeder choice

Routine missing-definition refresh, preserving assignments:

```sh
php artisan db:seed --class=FeaturePermissionsSeeder --force
php artisan permission:cache-reset
```

Only when explicitly repairing legacy mappings:

```sh
php artisan db:seed --class=LegacyPermissionGrantsSeeder --force
php artisan permission:cache-reset
```

The latter can restore a revoked action if its legacy source permission remains assigned. Do not run it automatically on every deployment. Do not run RolesAndPermissionsSeeder, DatabaseSeeder, migrate:fresh or migrate:refresh to deploy this release.

## Validation and commit hygiene

Focused tests remain under `tests/regression/` and are not loaded by application routes. They cover the fixes and prevent concurrency/compatibility regressions. The chat worker is a test dependency, not scratch code. Existing suites also exercise broader business behavior and migration/rollback compatibility. Exact final results are recorded in the review response.

One-off audit reports and before/after permission snapshots are retained privately under ignored `storage/app/private/release-audits/` and `storage/app/private/permission-repairs/`. They are not release artifacts. Do not include unrelated upload executable-bit changes or the existing dirty Mockery vendor directory in the source commit. Use the explicit staged file set rather than `git add -A` or `git commit -a`.

Passing automated checks does not establish that every production integration works. Confirm the release with the production smoke checks above before promoting it broadly.

## Final local release checks

- 214 broader business/API/concurrency checks passed, including full forward migration, rollback and repeatability.
- 44 focused chat integrity checks passed, including chronological/null-date history and parallel creation/send retries.
- 21 frontend chat checks, 19 branding checks and 16 permission-catalog checks passed; theme checks passed.
- Route, permission and response contracts, PHP/JavaScript syntax, Blade compilation and production asset build passed.
- Removed the unreferenced empty typing placeholder; kept functional regression coverage. No production records or uploads were changed by these checks.

The final comparison also fixed two polling error races: a stale 404 cannot close a different selected conversation, and deletion of a read-receipt boundary message does not close a valid chat.

## Header chat notification follow-up

The dedicated Team Chat badge now refreshes on other pages every 15 seconds, pauses in hidden tabs and refreshes on tab return. The chat page keeps using its existing workspace poll. Server-rendered counts take priority over old local-storage values, while same-tab/cross-tab read events remain supported. Requests do not mark messages as read.

The unread API retains its original `unread` conversation count and adds `unread_messages` for the badge. Both are calculated in one scoped aggregate query. Deleted conversations/messages, inactive/departed memberships, the sender's own messages and existing read receipts are excluded. No new schema or permission assignments are needed for this header follow-up.

The bell still handles the same system/activity alerts. Its obsolete request to a nonexistent chat read-all endpoint was removed, and dismissed-state persistence now tolerates blocked browser storage. No messages are automatically marked read from the bell.

Additional validation: 17 header lifecycle/event/polling checks and 8 isolated counter/API/scope checks passed. Existing 21 chat frontend checks, branding checks, route/permission contracts, Blade compilation and the production build passed. After deployment, open an authorized recipient's dashboard, send a test message from a second authorized account, and verify the Team Chat badge appears within the polling interval and clears after reading in chat.
