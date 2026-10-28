import QRCode from 'qrcode';

// Runs entirely in the browser; the enrollment URI never goes to a QR service.
export async function createAuthenticatorQr(uri) {
  if (typeof uri !== 'string' || !uri.startsWith('otpauth://totp/') || uri.length > 8192) {
    throw new Error('Invalid authenticator enrollment URI.');
  }
  return QRCode.toDataURL(uri, {
    errorCorrectionLevel: 'M',
    width: 320,
    margin: 4,
    color: { dark: '#000000ff', light: '#ffffffff' },
  });
}
