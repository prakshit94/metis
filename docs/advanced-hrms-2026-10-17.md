# Advanced Attendance, Leave and Users integration — 17 October 2026

Implemented the reporting improvements and the two workflows explicitly selected by the user: direct-manager leave decisions and direct-manager attendance correction approval. The earlier HR review documented global-only leave approval; this request intentionally extends that authority to assigned direct managers, under the existing permissions and LOB restrictions.

## Features available

### Attendance

- Employee context from Users: name, employee ID, designation, department, assigned manager, LOB and joining date. Joining dates are interpreted in the application's timezone.
- Full filtered-result diagnostics: recorded employee-days, completed session time, open sessions, and check-outs missing a check-in. These totals include records beyond the current page.
- From/through date filters, displayed-month shortcut, reset, and export of the current filtered results. Existing displayed-month CSV exports remain available and are explicitly labeled.
- Informational holiday markers for viewers with leave access. They do not change absence, entitlement or payroll rules.
- Click a calendar day number to inspect every session, notes, status, open sessions, leave requests and holidays.
- Self-service correction requests for an existing session or a missing day.
- My Requests and Requests I Can Review inboxes, searchable status selection, pagination, original/proposed values, reasons, reviewer comments and decision timestamps.

### Leave

- Search by employee name/ID, type or reason.
- Searchable employee, leave-type, decision and page-size filters; overlapping date range; direct-reportee filter; reset.
- Counts and requested-day totals for all matching requests, independent of pagination. An overlapping request contributes its full duration, not just the days inside the selected window.
- Full request details, applicant/approver history and permission-aware review actions.
- Single and bulk direct-manager approval/rejection. Existing own-record editing/deletion rules are preserved.
- Filtered CSV export using the same query and visibility scope as the report. Employee-entered formula-like text is exported as text.

### Users

- User action links to that employee's attendance and leave report. Employee IDs in URLs preselect filters; backend scope still governs visibility.
- Existing Manager field defines the direct reporting relationship. Help text explains its workflow role.
- Existing department, designation, employee ID and LOB fields supply HR context. No new parallel employee directory was introduced.

## Configure manager access

1. In Users, edit the employee and assign their Manager.
2. Give the manager the existing page/view access (`leave-view`, `attendance-view`) and the relevant review/edit permission (`leave-edit`, `attendance-edit`). No new role or blanket permission grant is created automatically.
3. Existing LOB restrictions still apply. Assigning a manager does not grant access across an otherwise forbidden LOB.
4. Use Leave → My direct reportees, or Attendance → Correction inbox → Requests I can review.

Direct reports only: the implementation does not traverse an entire reporting tree or treat department heads as approvers. Reassignment changes review access on subsequent requests. HR/Admin/global access is retained.

## Correction lifecycle and protections

- Employees submit proposed values for their own attendance. The request stores the original snapshot and reason; attendance is unchanged while Pending.
- Duplicate Pending requests for the same attendance record or missing day are prevented. Employee-row locking serializes submissions.
- The assigned direct manager with attendance-edit, or an existing global reviewer with attendance-edit, can approve/reject within scope. Non-global employees cannot self-approve.
- Approval locks the request and attendance, compares the current record with its original snapshot, applies the proposal, and stores the reviewer, comment and timestamp in one transaction.
- A record changed or deleted after submission produces a conflict instead of being overwritten. It can be rejected and resubmitted against current values.
- Repeated decisions do not apply the correction again. A failed attendance write leaves both the record and request unchanged.
- Rejection changes only the request. Approved missing-day requests create one attendance record.
- Check-in must match the requested attendance date. Proposed sessions must be shorter than 24 hours because attendance uses TIME columns; overnight sessions are supported.
- The new model uses existing activity logging. Existing manual attendance creation/editing permissions continue to work; the correction workflow is additive.

## Rules retained

Existing leave entitlements, inclusive day deductions, balance processing, Pending-only leave mutation, attendance statuses, multiple sessions per day, overnight duration calculation, rollover/login behavior, own-record manual mutation and LOB visibility remain in force. No shifts, overtime rates, payroll calculations, leave accrual/carry-forward rules, geofencing, biometric integration or automatic policy changes were introduced.

Completed time sums recorded completed sessions. It is not a payroll total or an overlap-deduplicated working-hours calculation. Holiday markers are informational. Requests submitted without an eligible assigned manager remain available to HR/Admin reviewers. No email or external messages are sent by these features.

## Validation

- `php tests/regression/hr_backend.php`: passed using a disposable MySQL database and synthetic fixtures. Covers previous HR regressions plus report filters, full-result time summaries, leave exports, filtered attendance export identity, manager decisions, reassignment, correction ownership, LOB restrictions, self-review denial, unchanged Pending attendance, duplicate submissions, repeated decisions, original/proposed serialization, stale-record conflict, approved missing-day creation and failure rollback.
- `TZ=Asia/Kolkata node tests/regression/hr_ui.cjs`: passed, including correction defaults/overnight values, duplicate submission guard, Users shortcut filters, report query parameters and joining-date timezone conversion.
- `node tests/regression/users_access_ui.cjs`: passed.
- PHP/JavaScript syntax, Blade compilation, registered-route checks and `git diff --check`: passed.
- `npm run build`: passed. Existing large-chunk warnings remain.
- Local admin browser: employee context and full-result totals, holiday markers, multi-session day inspector, correction form prefill and dismissal, reviewer inbox controls, leave filters/reset/details, no-match employee links, Users attendance shortcut and no new console errors.

No live leave approval or attendance correction was submitted during browser checks. Workflow mutations were exercised in disposable fixtures. Restricted manager behavior was tested in controller fixtures, not by impersonating live staff. This is targeted regression coverage, not a payroll-policy certification or a multi-process load test.

## Migration and release

The additive `attendance_corrections` migration was applied to the local app database. Its employee relationship cascades on permanent employee deletion, matching existing HR-record deletion behavior; reviewer deletion clears the reviewer link. No pre-existing attendance or leave record was rewritten by the migration.

For another environment, deploy sources, routes, templates and the rebuilt Vite assets/manifest together, then run:

```sh
php artisan migrate --path=database/migrations/2026_10_17_180000_create_attendance_corrections_table.php --force
php artisan view:cache
```

Refresh route caches through the environment's normal release process if routes are cached. No production deployment was performed.

## Leaves workspace layout update

Leaves now matches the Attendance workspace: scrollable section navigation for Pending Requests, History, Leave Balances and Holidays; separate request totals; grouped searchable report filters; export/refresh actions; and an Attendance workspace shortcut. Pending Requests retains Requested Date. Tables scroll independently on smaller screens, selected-row actions wrap, and request/balance/holiday/detail modals support shorter viewports. Existing remembered tabs, employee URL selection, report filters, permissions, approval logic, balance calculations and API behavior are unchanged.

Validation: Blade compilation and HR/Users UI regressions passed. Local browser checked all four sections, leave and balance form opening/dismissal, Requested Date, desktop layout and 390px mobile navigation. No leave, balance or holiday was submitted during verification. No new backend changes or migration are needed for this layout update.
