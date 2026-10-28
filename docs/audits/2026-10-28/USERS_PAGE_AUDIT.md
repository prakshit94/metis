# Users page audit — 28 October 2026

Scope: `/users`, its Blade layout and Alpine components, user request validation, user directory/detail/options and CRUD APIs, bulk actions, role/direct-permission assignment, activation and suspension controls, photos, password editing, login-history access, CSV import/export/report, and entry points to existing two-factor settings. The related authentication/2FA implementation is checked with its existing regression suite.

## Confirmed fixes

- The list's existing LOB boundary now also applies to individual detail/edit/photo/status/suspension/role/permission/history requests, deleted-user actions and bulk actions. Mixed-scope requests fail before mutation. Scoped operators cannot move a user or select a manager outside their permitted LOB. The page itself requires `user-view` and only supplies permitted team choices.
- Ordinary profile saves omit unchanged role, team and activation fields, and never replace direct permissions. The edit model now retains permissions and additional roles. Explicit primary-role edits preserve additional roles; explicit LOB moves preserve direct grants. Request team context is restored after assignment, including failure paths.
- Creation and editing are transactional. Other account mutations share a consistent administrator lock order. Last-active-Super-Admin protection now covers role removal, soft/permanent deletion and bulk deletion even when inactive Super Admins remain. Super Admin photo, restore and role changes receive the same protection from lower-privilege operators. Permanent deletion uses its dedicated permission and bulk permanent deletion executes relationship-cleanup hooks.
- Password resets revoke old tokens, sessions and remember cookies, and record `password_changed_at`. Audit/activity configuration excludes password hashes and remember tokens from new records. Historical records are not altered. Changing an email clears its old verification timestamp.
- Partial-name PATCH requests preserve omitted name parts. Reporting-manager self-links and indirect cycles are rejected. Login-history pagination is bounded; detail responses include history only for the account owner or an audit-log viewer. Malformed directory filters produce validation errors, and unknown role filters return an empty result rather than a server error.
- Export/report pagination retains the LOB and security filters and freezes query filters throughout the download. Invitations match string selection IDs correctly. Table refresh removes hidden stale selections; failed loads clear obsolete rows; deletion of the last row on a page returns to a valid page. Repeated bulk submissions are blocked while pending.
- CSV import handles BOM, quoted multiline fields and escaped quotes, and rejects missing individual passwords instead of silently assigning a shared default. CSV exports neutralize formula prefixes. Form reset clears stale photo/permission data.
- Duplicate page-specific theme/search registrations were removed so the existing shared components own those controls.

## Security controls available

The header link **My security & 2-step verification** opens `/security` for the signed-in account. Existing authenticator QR enrollment, email verification, recovery codes, session management and security activity remain in that page. Mail transport configuration continues to require its existing settings permission.

The Users directory now displays confirmed two-step enrollment and method (Authenticator / Email / Off), and offers corresponding filters. The edit form provides the same status and explains self-service management. API responses expose booleans and method labels only; they never serialize enrollment secrets, pending keys, recovery hashes, verification codes or mail credentials. No administrator-enforced MFA policy, delegated enrollment, or administrator 2FA reset workflow is introduced.

## Performance

- New read-only `GET /api/users/options` supplies minimal manager choices in one request, with the same permission and LOB scope. The form previously downloaded every page of full user records, relationships, presence and login histories.
- Directory security flags are loaded in one batch, rather than one settings query per user. Eager-loaded roles/permissions and the existing team map avoid repeated LOB role/state lookups. Stable ID tie-breaking avoids unstable pagination for equal names/dates.
- Expired tokens no longer mark users online. The existing presence interval is preserved.
- Background activity polling pauses when the tab is hidden and runs every 15 seconds instead of every 3 seconds. Hidden analytics charts are not instantiated, and chart recreation clears obsolete initialization markers.

## Verification

- `node tests/regression/users_ui.mjs`: form payloads, assignment preservation, permissions, security status/filter propagation, LOB export filtering, selection/invite IDs, CSV handling and shared component ownership.
- `php tests/regression/users_directory.php`: real bearer-authenticated requests in a uniquely named disposable MySQL schema; cross-LOB reads/mutations, protected administrators, ordinary CRUD, assignment preservation, manager cycles, password reset revocation, safe security flags/filters, manager choices and complete Blade rendering.
- Existing `audit_fixes.php`, `two_factor.php`, route/response contracts, frontend two-factor checks and production build are also run for compatibility.
- Browser verification uses the actual rendered Blade page and built assets against read-only fixture APIs. No live user account, permission, password or mail configuration is edited. Fixture verification supplements the automated API checks; it is not a claim of testing every physical mobile device.

Final results: 45 Users API/rendering checks, 102 existing two-factor checks and
214 existing business/mobile/concurrency/migration checks passed. The full
business rerun exited successfully, including repeated migration/rollback. Users
frontend checks, existing two-factor UI checks, theme checks, 614 route contracts,
1,294 method response contracts, production build and `git diff --check` passed.
The build retains the existing large-chunk size warning.

Read-only browser checks confirmed the status filter, edit-form guidance, minimal
manager choices, light/dark themes, and no settled document overflow at 375, 768
and 1280 CSS pixels. A fresh mobile dark-mode load also passed. Screenshots use
fixture users only: [desktop light](/tmp/metis-users-security-light.jpg),
[desktop dark](/tmp/metis-users-security-dark.jpg),
[mobile dark](/tmp/metis-users-mobile-dark.jpg).
