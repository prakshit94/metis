# Attendance and leave review — 17 October 2026

Reviewed the two Blade pages, attendance JavaScript, inline leave components, shared leave table, API routes, attendance/leave/balance/holiday controllers and models, and attendance rollover behavior. Changes are limited to these HR pages and their supporting endpoints. Existing dashboard and Users changes in this workspace belong to earlier requests.

## Fixes

| Area | Problem | Result |
| --- | --- | --- |
| Leave requests/history | Client filtered statuses after backend pagination, so pages and totals included the other tab's records. | Backend filters Pending/history before pagination; frontend uses the matching totals. |
| Leave pagination | Only page 1 was exposed in the page-number controls. | Current/adjacent/last pages and ellipses are generated. |
| Leave duration display | A one-day request displayed two days. | Inclusive day count now displays one day for equal start/end dates. The backend calculation remains inclusive. |
| Leave approvals | A stale bound model could be approved again; status and balance were separate writes. | Single updates/deletes/status changes lock and reload the leave within a transaction. Bulk processing locks pending rows in ID order. Balance increments/decrements are atomic and commit with status changes. |
| Leave errors | Several failed loads/mutations were silently ignored or only logged. | Request errors and expired sessions produce visible feedback. Form validation details are preserved. |
| Leave forms | Opening create for the same employee could retain stale balances; overlapping balance requests could win out of order. | Balance requests refresh on open and ignore obsolete responses. |
| Duplicate submissions | Form methods could be invoked more than once while saving. | Attendance, leave, balance and holiday save methods guard pending submissions. |
| Attendance form | Template referenced an undefined error field. | Error state is defined, reset, and populated on failure. |
| Calendar create | Clicking a date without a record was treated as editing an undefined attendance. | Date-only events initialize create mode with the selected day and employee. |
| Calendar time zones | Leading calendar days and default dates were derived with UTC conversion. | Date keys and form defaults use local calendar dates. |
| Overnight editing | Check-out was put on the same date even when the shift ended the following morning. | Edit form reconstructs the next-day check-out. Stored TIME columns and duration rules are unchanged. |
| Team calendar | Multiple employees' sessions on the same date were combined. | Attendance events and loaded-day statistics group by employee and date. Events show the employee name. |
| Attendance exports | Export URLs omitted the selected employee. | Summary and detailed exports retain the employee filter. |
| Attendance filtering | Non-global employee selection was ignored within LOB results. | Explicit employee filtering narrows the existing LOB scope, including calendar leaves and exports. |
| Employee directories | Dropdowns loaded only the first 100 employees and could show the first option while the model pointed to someone else. | All authorized directory pages load; selections are explicitly synchronized. A current-user fallback is available when directory access is denied. |
| Supplementary lists | Balance and holiday tabs silently stopped at their first page. | Frontend retrieves all pages, using bounded 100-row API requests. |
| Table request races | Older responses could overwrite newer filters/tab refreshes. | Each table has its own declared request counter and ignores obsolete responses. Selections are reconciled when results change. |
| Empty last page | Deletion could leave the table beyond its new last page. | Attendance and request tables return to the last valid page when necessary. |
| Calendar permissions | Read-only/processed/other-owner events opened edit forms that the API rejected. | Calendar actions respect existing ownership, edit permissions, and processed-leave restrictions. |
| Leave controls | Non-global users were offered approval controls and selection of other employees' requests. | UI matches existing global-only approval and own-record mutation restrictions. Backend authority is preserved. |
| Deletes | Balance/holiday deletion happened without confirmation. | Single and bulk supplementary deletions ask for confirmation. |
| Attendance bulk deletion | Permanent-delete bulk query excluded soft-deleted rows. | Explicit permanent-delete queries include trashed rows under the same permission/ownership checks. |
| Input validation | Malformed dates/months could reach parsing/calendar functions; pagination sizes were unbounded on several lists. | Date/month inputs are validated; leave/balance/holiday page sizes are bounded. Sorting ties use IDs for stable pagination. |
| Holiday dates | Date-only values were serialized as UTC datetimes; display/edit could move a holiday to the previous day. | Holiday serialization is Y-m-d; date-only displays use local midnight. Verified Christmas remains 25 December and Gandhi Jayanti remains 2 October. |
| Accessibility | Unnamed view buttons, missing modal heading IDs, unassociated floating labels, and mouse-only calendar actions. | Named view buttons, correct modal associations, linked labels, keyboard calendar events and sortable headings. |
| Layout | Attendance leave modal was outside the content section. | Both attendance-page modals render within the layout content. |

## Business rules preserved

- Global roles and view-all-data behavior; existing LOB scope and own-record mutation restrictions.
- Global-only leave approval, Pending-only processing/edit/delete restrictions, and existing permission names.
- Existing leave types, allocations, inclusive day calculations and handling of missing balances. No new entitlement, overlap, holiday-exclusion, or annual-reset policy was introduced.
- Multiple attendance sessions per day, current status choices, overnight duration handling, rollover/login attendance behavior and TIME-column storage.
- Dashboard, order, customer, manager assignment and unrelated user-management rules.

## Validation

Passed:

- `php tests/regression/hr_backend.php` in a disposable MySQL database, removed afterward. Covers backend status filtering before pagination, page-size bounds, inclusive balances, stale-model reapproval, bulk approval, rollback when balance writes fail, existing global/LOB restrictions, attendance employee filtering, overnight duration, malformed inputs, trashed bulk deletion and selected-employee CSV export. Also checks holiday date serialization.
- `TZ=Asia/Kolkata node tests/regression/hr_ui.cjs`. Covers calendar date keys, local dates, employee exports, response races, employee/day grouping, date-only create, overnight form dates, duplicate-save guard, balance races and history pagination.
- `npm run build` — successful. Existing large-chunk warnings remain; bundle splitting was not changed.
- Blade compilation, modified PHP/JavaScript syntax checks and `git diff --check`.

Browser checks on local authenticated admin session:

- Attendance renders with Master Admin selected and the complete authorized employee dropdown (153 employee options in the current data plus placeholder).
- Calendar and list switching, employee filtering, list pagination, create form defaults, and cancellation without saving.
- Pending/history tabs load and show empty states for current data; fixture tests cover multi-page leave records.
- Leave request opens once, defaults to the authenticated user, and loads active balances.
- Balances and all 21 current holidays render; holiday dates were checked against intended date-only values.
- Holiday form accessible labels and modal heading association verified.
- No new JavaScript errors during final checks. Earlier captured undefined-error messages predate the corrected attendance build.

No live approval, deletion or form-save action was performed for browser testing. Mutation checks use synthetic database fixtures. Browser checks used an admin session; restricted behavior was exercised through controller fixtures, not impersonating live employees. Tests exercise stale requests and atomic rollback; this was not a multi-process load test or a claim of exhaustive coverage of every browser/data combination.

Screenshots: `screenshots/attendances-audit-final.png`, `screenshots/leaves-audit-final.png`.

## Release

Deploy the changed HR PHP/Blade sources and rebuilt Vite assets/manifest together, and refresh the compiled view cache through the normal deployment process. No database migration is required. These changes are local; production deployment was not performed.
