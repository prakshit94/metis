import Alpine from 'alpinejs';
import { createAuthenticatorQr } from '../utils/authenticator-qr.js';

document.addEventListener('alpine:init', () => {
  Alpine.data('loginEnrollment', () => ({
    qr: '', error: '', destroyed: false,
    async init() {
      const uri = this.$el.dataset.enrollmentUri;
      if (!uri) return;
      try {
        const qr = await createAuthenticatorQr(uri);
        if (!this.destroyed) this.qr = qr;
      } catch {
        if (!this.destroyed) this.error = 'QR preview is unavailable. Use the manual setup key.';
      }
    },
    destroy() { this.destroyed = true; this.qr = ''; },
  }));
});
