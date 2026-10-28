import assert from 'node:assert/strict';
import jsQR from 'jsqr';
import { PNG } from 'pngjs';
import { createAuthenticatorQr } from '../../resources/js/utils/authenticator-qr.js';

// Public RFC test key only; never copy a real enrollment key into tests or screenshots.
const secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';
for (const account of ['Metis:qr-fixture@example.invalid', 'Metis:account+mobile@example.invalid', 'Workspace:'.repeat(8) + 'fixture@example.invalid']) {
  const uri = `otpauth://totp/${encodeURIComponent(account)}?secret=${secret}&issuer=Metis&algorithm=SHA1&digits=6&period=30`;
  const dataUrl = await createAuthenticatorQr(uri);
  assert.ok(dataUrl.startsWith('data:image/png;base64,'));
  const bitmap = PNG.sync.read(Buffer.from(dataUrl.split(',')[1], 'base64'));
  assert.equal(bitmap.width, 320);
  assert.equal(bitmap.height, 320);
  const decoded = jsQR(new Uint8ClampedArray(bitmap.data), bitmap.width, bitmap.height);
  assert.equal(decoded?.data, uri, 'Independent decoder must recover the exact account, secret and TOTP parameters');
  assert.deepEqual([...bitmap.data.subarray(0, 4)], [255, 255, 255, 255], 'Opaque white quiet zone stays scannable in dark mode');
}
await assert.rejects(createAuthenticatorQr('https://example.invalid/not-an-enrollment'));
await assert.rejects(createAuthenticatorQr(null));
console.log('PASS: independently decoded authenticator QR images preserve the exact enrollment URI, TOTP settings and white quiet zone');
