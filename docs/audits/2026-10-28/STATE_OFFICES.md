# State and office hierarchy

Implemented on 2026-10-28 following explicit confirmation that all offices use their state code for order numbers and that state-wide visibility stays unchanged.

## Structure and compatibility

- Existing `teams` remain the state/LOB identities used by role pivots and business visibility. Existing IDs, assignments, state spellings and order numbers are retained.
- New `offices` belong to one state team. Office codes and names are unique within that state. Offices cannot be moved between states: create a new office and explicitly move agents instead.
- `users.office_id` is optional. Existing agents remain unassigned until an authorized user assigns an office in Users create/edit. Assignment requires `user-sync-roles`, the same access used for state assignment.
- New orders snapshot `office_id` and `state_team_id` from the authenticated creator, never request payload fields. The existing state-code/date/sequence algorithm is retained across offices. Existing orders are deliberately not inferred from current memberships or backfilled.
- Browser, mobile and CSV import creation share OrderService. Existing explicitly supplied import order numbers retain their existing behavior.
- Office assignment does not narrow any business visibility; all existing role/status/creator/state restrictions continue to apply.

## Safety and bug fixes

- Invalid assigned-state mappings fail with 403 instead of removing the state filter.
- State/code conflicts are rejected; known legacy spellings such as Maharastra are preserved.
- State identity changes and deletion are blocked while dependent members, scoped roles/permissions, offices, targets or orders exist. Legacy order prefixes are checked as well as new state snapshots.
- Offices with current/soft-deleted users or historical/soft-deleted orders cannot be deleted. Foreign keys provide additional protection.
- Inactive states/offices reject new assignments. Existing assignments, logins, history and visibility are retained.
- State/office CRUD has action permissions and state boundaries. Scoped operators cannot create other states or edit other states/offices.
- `office-view/create/edit/delete` are added to the catalog/seeder. The migration copies only these new grants from corresponding team permissions, preserving unrelated manual revocations and scoped pivot values.
- All state/office mutations record actor, before/after values and timestamps in `organization_events`; UI validation and API errors do not expose SQL.
- Team membership resolves real role pivots. Directory member counts are distinct across multiple roles.
- Search filters are grouped; page sizes validated/bounded; range metadata and empty final-page behavior corrected.
- State and office search responses cannot overwrite newer responses. Search is debounced; duplicate save/delete requests guarded; Bootstrap modal reused.
- Tree action buttons avoid clipped dropdowns; modal content scrolls; inputs have labels, bounds and accessible control names.

## API

Existing `/api/teams` CRUD retains its paths. Paginated index includes `from`, `to`, `resolved_state_name`, `members_count`, `offices_count`, state names and known code mappings. The 1000-size option is bounded and paginated, rather than returning an unlimited result.

New `/api/offices` CRUD accepts `team_id`, `name`, `code`, optional address/city/PIN/description and `is_active`. Index supports team, search, status and bounded pagination. GET options is also available to authorized role-assignment operators.

User create/edit accepts nullable `office_id`. `/api/users?office_id=ID` retains existing directory access checks; API user records include office metadata. Teams tree links to that filtered directory. Orders include office identity; the order list displays the creating office without altering existing filters or totals.

## Deployment

Additive migrations `2026_10_28_160000_add_state_offices` and `2026_10_28_160100_add_office_permissions` applied to the local app. They do not create example offices, move agents, rewrite business records, or change default credentials. Rollback refuses to discard populated organization attribution silently.

## Verification

- 38 isolated state/office checks: migrations, API validation and authorization, cross-state boundaries, actual pivot membership, filtering, safe deletion, active-parent validation, history preservation, order prefix/shared sequence and payload-spoof rejection.
- 52 existing Users/two-step regression checks passed.
- Frontend regression checks cover stale state/office requests, duplicate submissions, API failures, CSRF and office form assignment/clearing.
- Permission/route/response contract checks, PHP/Blade syntax checks and production build passed.
- Browser preview uses fake data and a GET-only server; it is not a live login or real office creation test.
