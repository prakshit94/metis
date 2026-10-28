# Authenticator enrollment QR update

Authenticator setup on `/security` now shows a scannable QR first. The existing
manual key remains inside a closed “Can’t scan?” disclosure, and the same-device
app link remains available. The QR encodes the backend's existing `otpauth` URI;
TOTP secrets, confirmation, expiry, recovery and business rules are unchanged.

QR generation runs locally with the bundled [node-qrcode encoder](https://github.com/soldair/node-qrcode).
No external QR service receives the secret. Images have a black symbol and opaque
white quiet zone in both themes. QR state is cleared with other enrollment data;
a late asynchronous result cannot restore an image after cancellation. Encoding
failures leave the manual fallback available without revealing internal errors.

Removed the security page's duplicate `themeSwitch` and `searchComponent`
registrations. The shared theme manager and navbar search configuration now own
those components; the page no longer overrides them.

Verification: independent jsQR decoding recovered the exact URI from three
encoded account fixtures and from the actual mobile dark-mode browser screenshot.
Mobile layout has no horizontal overflow; the manual disclosure starts closed.
Frontend regression checks cover successful rendering, failure fallback, cleanup,
late-result rejection, both 2FA methods and mail settings. Shared theme checks,
Blade compilation, JavaScript syntax and production asset build passed. Browser
verification used only the public RFC test key, never a real account's key.

```sh
node tests/regression/authenticator_qr.mjs
node tests/regression/two_factor_ui.mjs
node tests/regression/theme.mjs
npm run build
```

Refresh `/security` and start authenticator setup again to display a fresh QR.
No database migration or existing account setting change is needed.
