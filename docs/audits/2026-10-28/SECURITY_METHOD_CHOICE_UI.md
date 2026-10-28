# Security method choice and master-only shared mail

The Security page opens on personal two-step verification. Two labeled radio cards let the user choose Authenticator app or Email OTP. Each explains setup, sign-in behavior, availability and appropriate use cases. The selected method drives the setup button and request; choosing a radio alone never enables protection.

Authenticator setup generates a private QR code after current-password verification, then confirms a six-digit app code. Email setup sends a single-use code to the user's own account email after current-password verification; the user confirms it before enrollment becomes active. Email codes expire after five minutes. The shared server mailer supplies delivery for every user; users never enter SMTP credentials.

Existing-factor switching verifies the current factor before setting up the replacement, and retains the original method until the replacement is confirmed. Required accounts cannot disable protection. Recovery codes remain available for loss of phone/email access. Misleading instructions to disable the old method before switching were removed. Mobile navigation uses two columns to keep the chooser discoverable; method cards stack on small screens and use Bootstrap theme colors.

Mail Configuration is restricted to the existing protected master account: user ID 1 with the Super Admin role. This restriction is checked in the shared controller before read, save and test actions and cannot be bypassed by another Super Admin or a delegated mail permission. The navigation entry and entire configuration section are omitted from non-master HTML. Current-password, current-factor and impersonation protections are retained. Default non-Super-Admin role seeding excludes the mail-management permissions. Existing grants are not destructively removed; the master-only controller gate is authoritative.

Validation: 40 isolated SMTP/security checks passed, including rejection of other Super Admins for read, save and test. Frontend 2FA regressions include explicit email selection and unavailable-delivery guards. All changed Blade views compile, 616 route contracts and 1,303 response-method checks pass, and the production build succeeds. Read-only desktop/light and 390px mobile/dark previews confirm selectable methods, no horizontal overflow, correct email setup text and absence of Mail Configuration for non-master users. Screenshots use fabricated identities and fixture data. No live security settings or SMTP credentials were changed and no real test emails were sent.

## Tab navigation and Email OTP selection follow-up

All five Security sections now use in-place navigation buttons. Duplicate Security Log and Manage two-step login shortcuts, the outbound Gmail-help link and authenticator deep link were removed from this page; QR and manual enrollment remain. Required redirects after a security logout and recovery-code file downloads are retained as operation behavior.

Email OTP can be selected even if delivery is unavailable. The page explains that activation must wait for master-configured delivery; the server delivery safeguards are unchanged. Changing method choices during an unfinished setup clears the pending UI action, password, codes and QR state so another option can be chosen without disabling the currently enrolled factor. Controls remain locked only while loading or submitting.

Read-only browser fixtures verified Account Security, Active Sessions, Security Activity, Mail Configuration and Two-Factor Auth each display their own panel without navigation. Email selection with delivery unavailable showed the selected radio and clear activation prerequisite. Frontend regressions and production build passed; no live account settings changed.

## Saved SMTP configuration reported unavailable

A read-only check found an existing encrypted SMTP configuration with username, password and sender present, but `enabled: false`; the fallback application mailer was `log`. The availability result was therefore correct, but the UI did not clearly distinguish saved credentials from active delivery. At the user's request, the existing configuration was validated and enabled. A comparison confirmed all saved credentials and other configuration values were preserved. The exact Security status service now returns `email_available: true`.

Mail Configuration now shows a persisted enabled/disabled status independently of unsaved checkbox changes. Save messages explicitly state whether shared delivery was enabled. Email OTP warnings describe disabled or unavailable delivery and offer a status refresh. No user factor enrollment or business mail default was changed, and no real test email was sent. Frontend, saved-default/redaction, Blade/PHP, route/permission/response checks and the production build passed.
