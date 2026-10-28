# Per-user two-step login and owner enrollment

## Controls

Users → Create/Edit → Account Settings → **Require two-step login**. The switch requires both normal user-management access and `settings-edit`. Changing it requires the acting administrator's password and, if enrolled, their own fresh verification/recovery code. No administrator creates or receives another user's factor secret.

Security → Two-Factor Auth provides the owner On/Off control, authenticator/email setup, method switching and recovery-code replacement. `/security#twofactor` opens this section. Required accounts cannot turn protection off; verified method switching retains the existing factor until the new method is confirmed. Removing the administrator requirement does not disable the user's existing factor.

Requiring protection revokes the target's existing sessions/tokens/remember credential. An unenrolled user next enters a restricted password-verified enrollment flow. Application credentials, successful-login activity and attendance are issued only after verification. Browser enrollment shows recovery codes once before continuing. Mobile enrollment returns them once with the final authenticated response.

## Mobile API

- `POST /api/auth/login`: existing password fields. Required unenrolled accounts return HTTP 202 with `enrollment_required: true` and `challenge_token`, without access token.
- `POST /api/auth/two-factor/enroll`: `challenge_token`, `method` (`authenticator` or `email`). Returns owner setup URI/key or masked email. The password challenge expires after five minutes.
- `POST /api/auth/two-factor/email`: resend email code using the challenge.
- `POST /api/auth/two-factor/challenge`: `challenge_token`, `code`. Only successful verification issues the existing mobile access-token response, plus one-time `recovery_codes` on enrollment.
- Authenticated owner settings retain `/api/security/two-factor` endpoints. Method replacement uses `/setup` with `replace: true`, current password and current method verification/recovery code; then `action: confirm` proves the new method.

The mobile app must consume these enrollment/challenge responses; no native mobile app source is present here.

## Compatibility and validation

Migration `2026_10_28_130000_add_user_two_factor_requirement.php` adds an opt-in flag defaulting false and a login challenge purpose. Existing users remain optional. Only this additive migration is applied for this change; unrelated pending business migrations are not included.

Regression coverage includes current authentication lifecycle, email delivery, per-user policy permissions and reauthentication, old-credential revocation, required browser/mobile enrollment, required-disable rejection, atomic method replacement, current-token preservation, no secret restoration after leaving a tab, Users form payload permissions, menu positioning, local QR decoding, Blade/PHP compilation and production build. Tests use disposable database schemas; application user policies are not changed for testing.

Final verification: the full two-factor regression suite passed, including mandatory browser/mobile authenticator and email enrollment and migration rollback; 51 Users directory/policy checks and 37 SMTP checks passed. Security/Users/menu frontend tests, independent QR decoding, 616 route contracts, 1,299 response-method checks, Blade/PHP syntax and production build passed. The local application migration is recorded as batch 12. View and route caches were cleared. Browser visual testing is left to the user's requested manual test.
