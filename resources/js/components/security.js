import Alpine from 'alpinejs';
import { createAuthenticatorQr } from '../utils/authenticator-qr.js';

document.addEventListener('alpine:init', () => {
  Alpine.data('securityComponent', () => ({
    canManageMail: false,
    mailLoaded: false,
    mailConfigurationSaved: false,
    mailSavedEnabled: false,
    mailBusy: false,
    mailError: '',
    mailMessage: '',
    mailProviders: [],
    mailExistingMailers: [],
    mailPassword: '',
    mailCode: '',
    mailSettings: { enabled: false, provider: 'smtp', host: '', port: 587, encryption: 'starttls', username: '',
      password: '', api_key: '', aws_key: '', aws_secret: '', region: 'us-east-1', message_stream: '',
      existing_mailer: 'smtp', timeout: 15, from_address: '', from_name: '', reply_to: '', clear_credentials: false },

    // UI State
    activeSection: 'twofactor',
    loading: false,
    sidebarVisible: false,

    // Session Management
    activeSessions: [],

    // Data structure to match HTML expectations
    securityData: {
      twoFactor: {
        app: false,
        sms: false,
      },
    },

    // Navigation sections
    sections: [
      { id: 'account', name: 'Account Security', icon: 'bi-shield-check' },
      { id: 'twofactor', name: 'Two-Factor Auth', icon: 'bi-key' },
      { id: 'sessions', name: 'Active Sessions', icon: 'bi-laptop' },
      { id: 'activity', name: 'Security Activity', icon: 'bi-activity' },
    ],

    // Security activity for the log
    securityActivity: [],

    // Security Events
    securityEvents: [],

    // Notification Settings
    notifications: {
      loginAlerts: true,
      securityUpdates: true,
      suspiciousActivity: true,
      weeklyReports: false,
    },

    backupCodes: [],
    twoFactorLoaded: false,
    twoFactorRequired: false,
    twoFactorRevision: 0,
    statusRevision: 0,
    twoFactorMethod: null,
    enrollmentMethod: 'authenticator',
    selectedTwoFactorMethod: 'authenticator',
    emailAvailable: false,
    emailHint: '',
    twoFactorBusy: false,
    twoFactorError: '',
    twoFactorMessage: '',
    twoFactorAction: '',
    twoFactorPassword: '',
    twoFactorCode: '',
    enrollmentSecret: '',
    enrollmentUri: '',
    enrollmentQr: '',
    enrollmentQrLoading: false,
    enrollmentQrError: '',
    recoveryCount: 0,

    init() {
      this.canManageMail = document.querySelector('.security-page')?.dataset?.canManageMail === 'true';
      if (this.canManageMail) {
        this.sections.push({ id: 'mail', name: 'Mail Configuration', icon: 'bi-envelope' });
        this.loadMailSettings();
      }
      this.hashHandler = () => {
        const section = window.location?.hash?.slice(1);
        if (this.sections.some(item => item.id === section)) this.setActiveSection(section);
      };
      this.hashHandler();
      window.addEventListener?.('hashchange', this.hashHandler);
      this.loadSecuritySettings();
      this.fetchSessions();
      this.fetchActivity();
    },

    async fetchActivity() {
      try {
        const response = await fetch('/api/security/activity', {
          headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
          },
        });
        if (response.ok) {
          const data = await response.json();
          this.securityActivity = data.data || [];
          this.securityEvents = data.data || [];
        }
      } catch (err) {
        console.error('Failed to fetch activity logs:', err);
      }
    },

    async fetchSessions() {
      try {
        const response = await fetch('/api/security/sessions', {
          headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
          },
        });
        if (response.ok) {
          const data = await response.json();
          this.activeSessions = data.data || [];
        }
      } catch (err) {
        console.error('Failed to fetch sessions:', err);
      }
    },

    destroy() {
      window.removeEventListener?.('hashchange', this.hashHandler);
      this.clearTwoFactorSensitiveData();
      this.clearMailSecrets();
    },

    // Navigation
    setActiveSection(section) {
      if (!this.sections.some(item => item.id === section)) return;
      if (section !== 'mail') this.clearMailSecrets();
      if (section !== 'twofactor') {
        this.clearTwoFactorSensitiveData();
        this.twoFactorAction = '';
      }
      this.activeSection = section;
    },

    // Additional methods referenced in HTML
    changePassword() {
      window.dispatchEvent(new CustomEvent('open-change-password-modal', { detail: {} }));
    },

    confirmAction(
      title,
      message,
      callback,
      btnClass = 'btn-primary',
      btnText = 'Confirm',
      iconClass = 'bi-exclamation-triangle text-warning'
    ) {
      const modalEl = document.getElementById('securityConfirmModal');
      if (!modalEl) {
        // Fallback if modal not present
        if (confirm(message)) callback();
        return;
      }

      document.getElementById('confirmModalTitleText').textContent = title;
      document.getElementById('confirmModalMessage').textContent = message;
      document.getElementById('confirmModalIcon').className = `me-2 ${iconClass}`;

      const btnEl = document.getElementById('confirmModalBtn');
      btnEl.className = `btn ${btnClass} fw-bold shadow-sm`;
      btnEl.textContent = btnText;

      const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

      const newBtn = btnEl.cloneNode(true);
      btnEl.parentNode.replaceChild(newBtn, btnEl);

      newBtn.addEventListener('click', () => {
        modal.hide();
        callback();
      });

      modal.show();
    },

    toggleTwoFactor() {
      if (!this.twoFactorLoaded || this.twoFactorBusy || (this.twoFactorRequired && this.securityData.twoFactor.app)) return;
      if (this.securityData.twoFactor.app) this.openTwoFactorAction('disable');
      else this.enableSelectedMethod();
    },

    selectTwoFactorMethod(method) {
      if (!['authenticator', 'email'].includes(method) || !this.twoFactorLoaded || this.twoFactorBusy) return;
      this.selectedTwoFactorMethod = method;
      if (this.twoFactorAction) {
        this.clearTwoFactorSensitiveData();
        this.twoFactorAction = '';
        this.twoFactorError = '';
        this.twoFactorMessage = '';
      }
    },

    enableSelectedMethod() {
      this.setupMethod(this.selectedTwoFactorMethod);
    },

    setupAuthenticatorApp() {
      this.setupMethod('authenticator');
    },

    setupEmailVerification() {
      this.setupMethod('email');
    },

    setupMethod(method) {
      if (!['authenticator', 'email'].includes(method) || !this.twoFactorLoaded || this.twoFactorBusy || (method === 'email' && !this.emailAvailable) || (this.securityData.twoFactor.app && this.twoFactorMethod === method)) return;
      this.selectedTwoFactorMethod = method;
      this.enrollmentMethod = method;
      this.openTwoFactorAction(this.securityData.twoFactor.app ? 'switch_setup' : 'setup');
    },

    async sendSettingsEmail() {
      if (this.twoFactorBusy) return;
      this.twoFactorBusy = true;
      this.twoFactorError = '';
      const revision = this.twoFactorRevision;
      try {
        const data = await this.twoFactorRequest('/email', { action: this.twoFactorAction === 'switch_setup' ? 'switch' : this.twoFactorAction, current_password: this.twoFactorPassword });
        if (revision !== this.twoFactorRevision) return;
        this.twoFactorMessage = data.message;
      } catch (error) {
        this.twoFactorError = error.message;
      } finally {
        this.twoFactorPassword = '';
        this.twoFactorCode = '';
        this.twoFactorBusy = false;
      }
    },

    generateBackupCodes() {
      this.openTwoFactorAction('recovery');
    },

    openTwoFactorAction(action) {
      this.clearTwoFactorSensitiveData();
      this.twoFactorError = '';
      this.twoFactorMessage = '';
      this.twoFactorAction = action;
      this.$nextTick(() => document.getElementById('twoFactorPassword')?.focus());
    },

    clearTwoFactorSensitiveData() {
      this.twoFactorRevision++;
      this.twoFactorPassword = '';
      this.twoFactorCode = '';
      this.enrollmentSecret = '';
      this.enrollmentUri = '';
      this.enrollmentQr = '';
      this.enrollmentQrLoading = false;
      this.enrollmentQrError = '';
      this.backupCodes = [];
    },

    async renderEnrollmentQr() {
      const uri = this.enrollmentUri;
      if (!uri || this.enrollmentMethod !== 'authenticator') return;
      this.enrollmentQrLoading = true;
      this.enrollmentQrError = '';
      try {
        const qr = await createAuthenticatorQr(uri);
        if (this.enrollmentUri === uri && this.twoFactorAction === 'confirm') this.enrollmentQr = qr;
      } catch {
        if (this.enrollmentUri === uri) this.enrollmentQrError = 'QR preview could not be generated. Use the manual setup key below.';
      } finally {
        if (this.enrollmentUri === uri) this.enrollmentQrLoading = false;
      }
    },

    async twoFactorRequest(path, payload) {
      const response = await fetch('/api/security/two-factor' + path, {
        method: payload ? 'POST' : 'GET',
        cache: 'no-store',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        },
        ...(payload ? { body: JSON.stringify(payload) } : {}),
      });
      const data = await response.json();
      if (!response.ok) throw new Error(Object.values(data.errors ?? {}).flat()[0] || data.message || 'Unable to update two-factor settings.');
      return data;
    },

    async submitTwoFactor() {
      if (this.twoFactorBusy) return;
      this.twoFactorBusy = true;
      this.twoFactorError = '';
      const revision = this.twoFactorRevision;
      try {
        const action = this.twoFactorAction;
        const setup = action === 'setup' || action === 'switch_setup';
        const data = await this.twoFactorRequest(setup ? '/setup' : '', {
          replace: action === 'switch_setup',
          action,
          method: this.enrollmentMethod,
          current_password: this.twoFactorPassword,
          code: this.twoFactorCode,
        });
        ++this.statusRevision;
        if (revision !== this.twoFactorRevision) { this.loadSecuritySettings(); return; }
        this.twoFactorPassword = '';
        this.twoFactorCode = '';
        if (setup) {
          this.enrollmentSecret = data.secret || '';
          this.emailHint = data.email_hint || '';
          this.enrollmentUri = data.otpauth_uri || '';
          this.twoFactorAction = 'confirm';
          this.twoFactorMessage = this.enrollmentMethod === 'email' ? `Enter the verification code sent to ${this.emailHint}. It expires in five minutes.` : 'Scan the QR code with your authenticator app. Re-enter your account password and the six-digit code from the new account to finish. Setup expires in 10 minutes.';
          if (this.enrollmentMethod === 'authenticator') await this.renderEnrollmentQr();
        } else {
          this.clearTwoFactorSensitiveData();
          this.backupCodes = data.recovery_codes || [];
          this.securityData.twoFactor.app = data.enabled;
          this.twoFactorRequired = data.required ?? this.twoFactorRequired;
          this.twoFactorMethod = data.method || (data.enabled ? 'authenticator' : null);
          this.selectedTwoFactorMethod = this.twoFactorMethod || this.selectedTwoFactorMethod;
          this.recoveryCount = data.recovery_codes_remaining;
          this.emailAvailable = data.email_available ?? this.emailAvailable;
          this.twoFactorAction = '';
          this.twoFactorMessage = data.enabled ? 'Two-factor authentication is enabled. Save your recovery codes securely; they are shown only once.' : 'Two-factor authentication is disabled.';
          this.fetchActivity();
          this.fetchSessions();
        }
      } catch (error) {
        if (revision === this.twoFactorRevision) this.twoFactorError = error.message;
        this.twoFactorPassword = '';
        this.twoFactorCode = '';
      } finally {
        this.twoFactorBusy = false;
      }
    },

    downloadBackupCodes() {
      if (!this.backupCodes.length) return;
      const url = URL.createObjectURL(new Blob(['Metis recovery codes — each code can be used once.\n\n' + this.backupCodes.join('\n')], { type: 'text/plain' }));
      const link = document.createElement('a');
      link.href = url;
      link.download = 'metis-recovery-codes.txt';
      link.click();
      URL.revokeObjectURL(url);
    },

    async emergencyLockdown() {
      this.confirmAction(
        'Emergency Lockdown',
        'Are you sure you want to initiate emergency lockdown? This will log out all sessions and require a password reset.',
        async () => {
          try {
            const response = await fetch('/api/security/emergency-lockdown', {
              method: 'POST',
              headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
              },
            });
            if (response.ok) {
              this.showNotification(
                'Emergency lockdown initiated. All sessions terminated. Redirecting...',
                'warning'
              );
              setTimeout(() => (window.location.href = '/login'), 2000);
            } else {
              throw new Error('Lockdown failed');
            }
          } catch (err) {
            this.showNotification('Failed to initiate emergency lockdown', 'error');
          }
        },
        'btn-danger',
        'Lockdown Now',
        'bi-shield-lock-fill text-danger'
      );
    },

    loadMoreActivity() {
      this.showNotification('You have reached the end of the log.', 'info');
    },

    // Session Management
    async terminateSession(sessionId) {
      this.confirmAction(
        'Terminate Session',
        'Are you sure you want to terminate this specific session?',
        async () => {
          try {
            const response = await fetch(`/api/security/sessions/${sessionId}`, {
              method: 'DELETE',
              headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
              },
            });
            if (response.ok) {
              this.activeSessions = this.activeSessions.filter(
                (session) => session.id !== sessionId
              );
              this.showNotification('Session terminated successfully', 'success');
            } else {
              throw new Error('Termination failed');
            }
          } catch (err) {
            this.showNotification('Failed to terminate session', 'error');
          }
        },
        'btn-danger',
        'Terminate Session',
        'bi-x-circle text-danger'
      );
    },

    async terminateAllSessions() {
      this.confirmAction(
        'Terminate All Other Sessions',
        'Are you sure you want to terminate all other sessions? You will need to log in again on those devices.',
        async () => {
          try {
            const response = await fetch(`/api/security/sessions/terminate-other`, {
              method: 'POST',
              headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
              },
            });
            if (response.ok) {
              this.activeSessions = this.activeSessions.filter((session) => session.current);
              this.showNotification('All other sessions terminated', 'success');
            } else {
              throw new Error('Termination failed');
            }
          } catch (err) {
            this.showNotification('Failed to terminate sessions', 'error');
          }
        },
        'btn-danger',
        'Terminate All',
        'bi-power text-danger'
      );
    },

    // Data Export
    exportSecurityLog() {
      const exportData = {
        securityEvents: this.securityEvents,
        activeSessions: this.activeSessions,
        securityScore: this.securityScore,
        exportDate: new Date().toISOString(),
      };

      const content = JSON.stringify(exportData, null, 2);
      const blob = new Blob([content], { type: 'application/json' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'security-log.json';
      a.click();
      URL.revokeObjectURL(url);
      this.showNotification('Security log exported', 'success');
    },

    clearMailSecrets() {
      this.mailPassword = '';
      this.mailCode = '';
      for (const key of ['password', 'api_key', 'aws_key', 'aws_secret']) this.mailSettings[key] = '';
    },

    async mailRequest(path = '', payload) {
      const response = await fetch('/api/security/mail' + path, {
        method: payload ? 'POST' : 'GET', cache: 'no-store',
        headers: { Accept: 'application/json', 'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' },
        ...(payload ? { body: JSON.stringify(payload) } : {}),
      });
      const data = await response.json();
      if (!response.ok) throw new Error(Object.values(data.errors ?? {}).flat()[0] || data.message || 'Unable to update mail configuration.');
      return data;
    },

    applyMailSettings(data) {
      this.clearMailSecrets();
      this.mailConfigurationSaved = Boolean(data.configuration_saved);
      this.mailSavedEnabled = Boolean(data.settings?.enabled);
      this.mailSettings = { ...this.mailSettings, ...data.settings, clear_credentials: false };
      this.mailProviders = data.providers || [];
      this.mailExistingMailers = data.existing_mailers || [];
      this.mailLoaded = true;
    },

    async loadMailSettings() {
      this.mailError = '';
      try { this.applyMailSettings(await this.mailRequest()); }
      catch (error) { this.mailError = error.message; }
    },

    async saveMailSettings() {
      if (this.mailBusy) return;
      this.mailBusy = true;
      this.mailError = ''; this.mailMessage = '';
      try {
        const data = await this.mailRequest('', { ...this.mailSettings, current_password: this.mailPassword, code: this.mailCode });
        this.applyMailSettings(data);
        this.mailMessage = data.message;
        window.AdminApp?.notificationManager?.show(data.message || 'Mail configuration saved.', 'success');
        await this.loadSecuritySettings();
        this.fetchActivity();
      } catch (error) {
        this.mailError = error.message || 'Unable to save mail configuration. Please try again.';
        window.AdminApp?.notificationManager?.show(this.mailError, 'error');
      }
      finally { this.clearMailSecrets(); this.mailBusy = false; }
    },

    async testMailSettings() {
      if (this.mailBusy) return;
      this.mailBusy = true;
      this.mailError = ''; this.mailMessage = '';
      try {
        const data = await this.mailRequest('/test', { current_password: this.mailPassword, code: this.mailCode });
        this.mailMessage = data.message;
        this.fetchActivity();
      } catch (error) { this.mailError = error.message; }
      finally { this.clearMailSecrets(); this.mailBusy = false; }
    },

    async sendMailVerification() {
      if (this.mailBusy) return;
      this.mailBusy = true; this.mailError = ''; this.mailMessage = '';
      try {
        const data = await this.twoFactorRequest('/email', { action: 'mail_settings', current_password: this.mailPassword });
        this.mailMessage = data.message;
      } catch (error) { this.mailError = error.message; }
      finally { this.mailPassword = ''; this.mailCode = ''; this.mailBusy = false; }
    },

    async loadSecuritySettings() {
      const revision = ++this.statusRevision;
      this.twoFactorError = '';
      try {
        const data = await this.twoFactorRequest('');
        if (revision !== this.statusRevision) return;
        this.twoFactorRequired = data.required ?? false;
        this.securityData.twoFactor.app = data.enabled;
        this.twoFactorMethod = data.method || (data.enabled ? 'authenticator' : null);
        this.selectedTwoFactorMethod = this.twoFactorMethod || this.selectedTwoFactorMethod;
        this.emailAvailable = data.email_available ?? false;
        this.emailHint = data.email_hint || '';
        this.recoveryCount = data.recovery_codes_remaining;
        this.twoFactorLoaded = true;
      } catch (error) {
        if (revision !== this.statusRevision) return;
        this.twoFactorLoaded = false;
        this.twoFactorError = error.message;
      }
    },

    // Notifications
    showNotification(message, type = 'info') {
      window.AdminApp?.notificationManager?.show(message, type);
    },
  }));

});
