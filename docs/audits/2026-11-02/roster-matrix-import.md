# Weekly employee roster import

The reference image has employee ID, advisor name, LOB, then a date per column,
with an optional second weekday row. CSV and single-worksheet XLSX versions of
this layout are accepted. Upload the spreadsheet, not its photograph.

Employee IDs must already identify exactly one employee within the operator's
existing access. Names and LOB are reference labels and never update user data.
The legacy `employee_id,date,day_type` and `user_id,date,day_type` CSV formats
remain accepted. The weekly template is available from Attendance > Roster import.

- `08:00-17:00`: planned Working shift, retaining both times.
- `22:00-06:00`: planned overnight Working shift.
- `WO`: Week Off, retaining the existing compensatory work/approval rules.
- `C/OFF`: Comp Off planning label, never a CO credit or approved leave.
- `L`: Planned Leave label, never an approved leave request.
- Blank: do not change an existing employee/date record.

As explicitly requested, leave requests, approvals, deductions and attendance
records are unchanged. L/C/OFF display in the attendance calendar and do not
invent absent/missing-record markers on those planned leave dates. The Leave
workspace explains that a separate leave request is still required.

Dates accept YYYY-MM-DD or English 02-Nov-26 / 02-Nov-2026. XLSX numeric Excel
dates are supported. Weekday labels, if present, must match. Invalid dates,
invalid shift times, equal start/end times, duplicate dates or employee/date
entries, unknown/ambiguous/inaccessible employees, and protected CO changes block
the entire file. Existing Pending/Approved CO day types remain protected.

Limits: 2 MB uploaded file; 2,000 employee rows; 366 date columns; 10,000 nonblank
employee/date entries. Errors report source rows/columns (first 50 errors).
Preview never writes data. Confirmation uses a server-signed hash bound to the
operator, file, parsed result and saved row versions. Changed saved data requires
a new preview; commit rechecks versions under locks and writes atomically.
Unchanged reimports do not duplicate audit events. Working day-only imports
retain existing shift times.

## Deployment

Apply `2026_11_02_220000_add_shift_times_to_employee_rosters.php` before activating
this code. It adds nullable time columns; existing rows remain compatible.
The migration has been applied locally. Build Vite assets and refresh compiled
views with the normal release process. Production has not been modified.
Keep a database backup. Do not drop the new columns when rolling back UI, since
that discards imported shift information. This change does not migrate roster
labels into the leave table or modify payroll, shift calculations or balances.

## Verification

`php tests/regression/roster_matrix.php` uses the shared disposable MySQL test
bootstrap. It checks matrix expansion, atomic preview/commit, blank cells,
overnight times, invalid data, duplicates, protected CO, stale/bypassed previews,
legacy imports, real XLSX date headers, permission checks and unchanged balances.
The existing HR regression suites cover attendance validation and leave approval.
The new matrix suite passed all 19 checks, the existing HR suite passed all 17
checks, and frontend regressions passed. The final production asset build passed. PHP
syntax and Blade compilation passed. Authenticated browser verification requires
signing in; the available browser session stopped at the admin login page.

## Preview and template controls

The preview groups validated entries into an employee-by-date grid with employee
ID, canonical employee name, reference LOB, date and weekday headers. Off/leave
codes are highlighted; hovering a cell shows Create/Update/Unchanged. Empty date
columns from a matrix file remain visible. Preview pagination shows 25 employees
per page, and confirmation still imports every validated entry.

The grid template supports an entire selected month (including leap years) or
an inclusive custom range of up to 366 days. It includes five clearly named sample employees with rotating working shifts,
overnight shifts, WO, C/OFF and L. Replace every sample employee and schedule
before uploading. Even a one-day custom template demonstrates every code across
the sample employees. Upload dates always come from the file, independently of
the template controls. Legacy employee-ID/system-ID template download buttons
have been removed; existing legacy files remain import-compatible. Only a
successful confirmed import emits a success toast. These controls do not alter
leave approvals, balances, roster protection rules or persistence behavior.

### Spreadsheet-exported date headers

Headers also accept day-first numeric dates (`02/11/2026`, `02-11-2026`,
`2-11-26`), English dates with spaces (`02 Nov 26`), and Excel serial dates
in CSV exports. Slash/dash numeric dates are day first; use ISO YYYY-MM-DD to
avoid ambiguity. Nonbreaking whitespace and text-cell apostrophes are normalized.
Completely empty trailing export columns are ignored, but a blank date header
above nonempty schedule data is rejected. Duplicate dates remain blocked after
normalization, even when written using different formats. Header errors now name
the specific column and value/date. Run parser-only verification with
`php tests/regression/roster_header_dates.php` (no database writes).

## Export, edit and re-upload

Use **Export > Editable roster (XLSX)** in Attendance for the selected employee
and list date/range, or the displayed calendar month. In Roster import, choose
a month/custom range and **Export editable roster (XLSX)** for all employees
within your existing roster access who have assigned employee IDs. The sample
CSV download remains available separately. Existing attendance reports remain
unchanged and are not roster-import files.

The editable workbook has one worksheet, EMP. ID / Advisor Name / LOB, complete
ISO date headers, a weekday row, actual employee IDs/names and saved planned
shifts/off/leave codes. Unscheduled dates and the reference-only LOB field stay
blank. Actual attendance sessions and approved leave records are not converted
into roster entries. Editing this file and uploading it uses the existing roster
preview/confirmation process; no export writes any attendance or leave data.

Employee IDs and names use explicit Excel text cells, preserving leading zeros
and preventing spreadsheet formulas. Freeze panes and column widths make the
grid editable. Export requires existing attendance-view and attendance-export
permissions and uses the same employee scope as roster import. Duplicate IDs
and exports beyond import size/row/date/entry limits are rejected before download.
