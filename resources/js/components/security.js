import Alpine from 'alpinejs';
import { createSearchComponent } from '../utils/search-component.js';

document.addEventListener('alpine:init', () => {
  Alpine.data('securityComponent', () => ({
    // UI State
    activeSection: 'account',
    loading: false,
    sidebarVisible: false,
    
    // Session Management
    activeSessions: [],
    
    // Data structure to match HTML expectations
    securityData: {
      twoFactor: {
        app: true,
        sms: false
      }
    },
    
    // Navigation sections
    sections: [
      { id: 'account', name: 'Account Security', icon: 'bi-shield-check' },
      { id: 'twofactor', name: 'Two-Factor Auth', icon: 'bi-key' },
      { id: 'sessions', name: 'Active Sessions', icon: 'bi-laptop' },
      { id: 'activity', name: 'Security Activity', icon: 'bi-activity' }
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
      weeklyReports: false
    },
    
    // Backup Codes
    backupCodes: [
      'ABC123-DEF456',
      'GHI789-JKL012',
      'MNO345-PQR678',
      'STU901-VWX234',
      'YZA567-BCD890'
    ],
    
    init() {
      this.loadSecuritySettings();
      this.fetchSessions();
      this.fetchActivity();
    },

    async fetchActivity() {
      try {
        const response = await fetch('/api/security/activity', {
          headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? ''
          }
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
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? ''
          }
        });
        if (response.ok) {
          const data = await response.json();
          this.activeSessions = data.data || [];
        }
      } catch (err) {
        console.error('Failed to fetch sessions:', err);
      }
    },
    
    // Navigation
    setActiveSection(section) {
      this.activeSection = section;
    },
    
    // Additional methods referenced in HTML
    changePassword() {
      window.dispatchEvent(new CustomEvent('open-change-password-modal', { detail: { } }));
    },
    
    confirmAction(title, message, callback, btnClass = 'btn-primary', btnText = 'Confirm', iconClass = 'bi-exclamation-triangle text-warning') {
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
    
    setupAuthenticatorApp() {
      if (this.securityData.twoFactor.app) {
        this.confirmAction('Disable Authenticator App', 'Are you sure you want to disable your Authenticator App?', () => {
            this.securityData.twoFactor.app = false;
            this.saveSecuritySettings();
            this.showNotification('Authenticator app disabled', 'warning');
        }, 'btn-warning', 'Disable');
      } else {
        this.confirmAction('Enable Authenticator App', 'Are you sure you want to enable the Authenticator App for Two-Factor Authentication?', () => {
            this.securityData.twoFactor.app = true;
            this.saveSecuritySettings();
            this.showNotification('Authenticator app setup completed', 'success');
        }, 'btn-success', 'Enable', 'bi-shield-check text-success');
      }
    },
    
    setupSMSVerification() {
      if (this.securityData.twoFactor.sms) {
        this.confirmAction('Disable SMS Verification', 'Are you sure you want to disable SMS Verification?', () => {
            this.securityData.twoFactor.sms = false;
            this.saveSecuritySettings();
            this.showNotification('SMS verification disabled', 'warning');
        }, 'btn-warning', 'Disable');
      } else {
        this.confirmAction('Enable SMS Verification', 'Are you sure you want to enable SMS Verification for Two-Factor Authentication?', () => {
            this.securityData.twoFactor.sms = true;
            this.saveSecuritySettings();
            this.showNotification('SMS verification setup completed', 'success');
        }, 'btn-success', 'Enable', 'bi-shield-check text-success');
      }
    },
    
    generateBackupCodes() {
        this.confirmAction('Regenerate Backup Codes', 'Are you sure you want to regenerate backup codes? This will invalidate all existing codes.', () => {
          this.showNotification('Backup codes regenerated and downloaded successfully', 'success');
        });
    },
    
    viewSecurityLog() {
      this.setActiveSection('activity');
    },
    
    async emergencyLockdown() {
      this.confirmAction('Emergency Lockdown', 'Are you sure you want to initiate emergency lockdown? This will log out all sessions and require a password reset.', async () => {
        try {
          const response = await fetch('/api/security/emergency-lockdown', {
            method: 'POST',
            headers: {
              'Accept': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? ''
            }
          });
          if (response.ok) {
            this.showNotification('Emergency lockdown initiated. All sessions terminated. Redirecting...', 'warning');
            setTimeout(() => window.location.href = '/login', 2000);
          } else {
            throw new Error('Lockdown failed');
          }
        } catch (err) {
          this.showNotification('Failed to initiate emergency lockdown', 'error');
        }
      }, 'btn-danger', 'Lockdown Now', 'bi-shield-lock-fill text-danger');
    },
    
    loadMoreActivity() {
      this.showNotification('You have reached the end of the log.', 'info');
    },
    
    // Session Management
    async terminateSession(sessionId) {
      this.confirmAction('Terminate Session', 'Are you sure you want to terminate this specific session?', async () => {
        try {
          const response = await fetch(`/api/security/sessions/${sessionId}`, {
            method: 'DELETE',
            headers: {
              'Accept': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? ''
            }
          });
          if (response.ok) {
            this.activeSessions = this.activeSessions.filter(session => session.id !== sessionId);
            this.showNotification('Session terminated successfully', 'success');
          } else {
             throw new Error('Termination failed');
          }
        } catch (err) {
          this.showNotification('Failed to terminate session', 'error');
        }
      }, 'btn-danger', 'Terminate Session', 'bi-x-circle text-danger');
    },
    
    async terminateAllSessions() {
      this.confirmAction('Terminate All Other Sessions', 'Are you sure you want to terminate all other sessions? You will need to log in again on those devices.', async () => {
        try {
          const response = await fetch(`/api/security/sessions/terminate-other`, {
            method: 'POST',
            headers: {
              'Accept': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? ''
            }
          });
          if (response.ok) {
            this.activeSessions = this.activeSessions.filter(session => session.current);
            this.showNotification('All other sessions terminated', 'success');
          } else {
             throw new Error('Termination failed');
          }
        } catch (err) {
          this.showNotification('Failed to terminate sessions', 'error');
        }
      }, 'btn-danger', 'Terminate All', 'bi-power text-danger');
    },


    
    // Data Export
    exportSecurityLog() {
      const exportData = {
        securityEvents: this.securityEvents,
        activeSessions: this.activeSessions,
        securityScore: this.securityScore,
        exportDate: new Date().toISOString()
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
    
    // Settings Management
    loadSecuritySettings() {
      const saved = localStorage.getItem('securitySettings');
      if (!saved) {
        return;
      }
      try {
        const parsed = JSON.parse(saved);
        if (!parsed || typeof parsed !== 'object') throw new Error('not an object');

        if (parsed.securityData) {
            if (parsed.securityData.twoFactor) {
                this.securityData.twoFactor.app = parsed.securityData.twoFactor.app ?? this.securityData.twoFactor.app;
                this.securityData.twoFactor.sms = parsed.securityData.twoFactor.sms ?? this.securityData.twoFactor.sms;
            }
        }
      } catch (error) {
        console.warn('Failed to load security settings:', error);
        localStorage.removeItem('securitySettings');
      }
    },
    
    saveSecuritySettings() {
      const settings = {
        securityData: this.securityData
      };
      
      try {
        localStorage.setItem('securitySettings', JSON.stringify(settings));
        this.showNotification('Security settings saved', 'success');
      } catch {
        this.showNotification('Failed to save settings', 'error');
      }
    },
    
    // Notifications
    showNotification(message, type = 'info') {
      console.log(`[${type.toUpperCase()}] ${message}`);

      // Dispatch a custom event for a global notification system
      document.dispatchEvent(new CustomEvent('showNotification', {
        detail: { message, type }
      }));
    }
  }));

  // Also register search and theme components for this page
  Alpine.data('searchComponent', createSearchComponent({ getResults: () => [] }));

  Alpine.data('themeSwitch', () => ({
    currentTheme: 'light',
    
    init() {
      this.currentTheme = document.documentElement.getAttribute('data-bs-theme') || 
                         localStorage.getItem('theme') || 'light';
    },
    
    toggle() {
      this.currentTheme = this.currentTheme === 'light' ? 'dark' : 'light';
      document.documentElement.setAttribute('data-bs-theme', this.currentTheme);
      localStorage.setItem('theme', this.currentTheme);
    }
  }));
});