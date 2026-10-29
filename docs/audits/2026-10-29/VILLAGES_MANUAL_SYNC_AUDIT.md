# Villages audit and explicit India Post sync

## Result

Create and edit now save only the entered village. They neither instantiate the postal client nor fetch/import India Post offices. A village name is required for manual creation. Sync remains an explicit, confirmed action governed by the existing village-edit permission:

- Toolbar: Sync India Post processes pending pincodes in the permitted state scope. A six-digit search targets that pincode; confirmation explains that other filters are not used.
- Row Actions: Sync India Post targets the row's six-digit pincode.
- Saved edit form: Sync this pincode is enabled only when there are no unsaved changes. Fields are protected while syncing. Reopening a different editor cannot be overwritten by an older asynchronous response.

The UI explains that saving and syncing are separate. Existing completed offices are skipped; manually targeting a FAILED pincode can retry it. Service assignments and priorities are preserved when an unsynced village stub adopts its postal office details. No live sync or business-data mutations were performed during testing.

## Confirmed fixes

1. Removed automatic pincode import on create, which could ignore entered fields and overwrite multiple unsynced records.
2. Enforced assigned-state boundaries on create, edits, CSV imports, sync candidates, adopted records and returned postal offices. Scoped failure markers and stop/resume flags prevent other-state writes or interference.
3. Serialized postal batches with an expiring lock to prevent concurrent sync jobs racing writes.
4. Validate six-digit sync inputs and skip malformed legacy pincodes without external calls. Reject incomplete/mismatched postal offices rather than overwriting valid data with them.
5. Auth/network failures now stop with a manual-retry explanation rather than repeatedly reauthenticating automatically. Running flags are cleared on terminal failures. Provider construction failures return an actionable 503; stopping does not depend on working postal credentials.
6. Menu placement uses the existing shared keyboard-accessible body-level action-menu utility, escaping table/card clipping. The action toolbar wraps on narrow screens.
7. Removed redundant Alpine x-init: Alpine already invokes init(), so the old page sent duplicate initial requests and could show duplicate resume dialogs.
8. List response versioning prevents stale filter/sort requests overwriting newer results; sorting has an ID tie-breaker. Invalid query shapes return validation errors. Non-JSON/expired-session responses show readable errors, and permission errors no longer silently redirect away from the page.
9. The header selection checkbox now reflects visible rows even when other pages are selected, including an indeterminate state. CSV supports canonical/reordered headers and common exported header aliases, UTF-8 BOMs, blank rows, standard boolean flags and bounded previews. Required fields and each row's state are validated. Malformed/empty files return validation errors. Imports remain transactional and chunked; a failure after a 1,000-row insertion rolls the entire import back.
10. Bulk mutations are transactional; permanent-delete foreign-key failures return a useful error instead of partially deleting the selection. Selected-export IDs and duplicate service-key aliases are validated.

Existing address lookup APIs, state-wide visibility rules, service selection semantics, shipping decision logic, permissions, seeders and schema are retained. Changes are limited to the village controller, village component/template, regression tests and audit evidence.

## Validation

- 30 backend/API regression checks: tests/regression/villages_manual_sync.php. Uses a newly initialized, network-disabled disposable MySQL instance; synthetic records; all postal requests mocked. Includes authenticated API routing/provider resolution, manual create/edit, state isolation, explicit retry, terminal failures, locking, scoped stop flags, CSV rollback and service assignment preservation.
- 6 frontend behavior checks: node tests/regression/villages_frontend.mjs. Tests stale list responses, save-without-sync, unsaved-edit protection, stale editor responses cancelled sync confirmation, and cross-page header selection.
- Production Vite build, JavaScript/PHP syntax checks and Blade compilation passed.
- 622 route contracts, 425 permissions / 591 route gates, and 1,339 response/middleware contracts passed.
- Read-only synthetic browser preview verified desktop and 375px mobile layout, light/dark mode, body-portalled menus within the viewport, enabled saved-record sync and disabled sync on unsaved edits. No browser console errors observed.

Screenshots contain synthetic data only: villages-manual-sync-form.jpg and villages-mobile-menu.jpg. The preview rejected all POST requests and used a stub layout; authenticated real-data screens were not modified or used to trigger postal synchronization.
