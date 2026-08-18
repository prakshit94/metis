import Alpine from 'alpinejs';
import { createSearchComponent } from '../utils/search-component.js';

document.addEventListener('alpine:init', () => {
  const getStartOfDay = () => {
    const now = new Date();
    now.setHours(0, 0, 0, 0);
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    return now.toISOString().slice(0, 16);
  };

  const getEndOfDay = () => {
    const now = new Date();
    now.setHours(23, 59, 59, 999);
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    return now.toISOString().slice(0, 16);
  };

  Alpine.data('reportsComponent', () => ({
    // Filter settings
    reportType: 'sales_overview',
    dateFrom: getStartOfDay(),
    dateTo: getEndOfDay(),

    init() {
      // Initialize tooltip if any
      const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
      const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    },

    scheduleReport() {
      this.showToast('Info', 'Report scheduling would open here', 'info');
    },

    exportData() {
      this.showToast('Info', 'Exporting data...', 'info');
    },

    generateReport() {
      this.showToast('Info', 'New report generation would start here', 'info');
    },

    downloadAdvancedReport() {
      if (!this.dateFrom || !this.dateTo) {
         this.showToast('Error', 'Please select both Date From and Date To', 'danger');
         return;
      }

      console.log('Downloading CSV Report:', this.reportType, 'From:', this.dateFrom, 'To:', this.dateTo);
      
      const url = new URL(window.location.origin + '/reports/export');
      url.searchParams.append('type', this.reportType);
      url.searchParams.append('from', this.dateFrom);
      url.searchParams.append('to', this.dateTo);
      
      window.location.href = url.toString();
      
      this.showToast('Success', `CSV Report generation started. Your download will begin shortly.`, 'success');
    },

    // Toast Notification Helper
    showToast(title, message, type = 'info') {
      const toastContainer = document.getElementById('toast-container') || this.createToastContainer();
      
      const toastEl = document.createElement('div');
      toastEl.className = `toast align-items-center text-bg-${type} border-0`;
      toastEl.setAttribute('role', 'alert');
      toastEl.setAttribute('aria-live', 'assertive');
      toastEl.setAttribute('aria-atomic', 'true');
      
      toastEl.innerHTML = `
        <div class="d-flex">
          <div class="toast-body">
            <strong>${title}</strong><br>${message}
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
      `;
      
      toastContainer.appendChild(toastEl);
      const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
      toast.show();
      
      toastEl.addEventListener('hidden.bs.toast', () => {
        toastEl.remove();
      });
    },
    
    createToastContainer() {
      const container = document.createElement('div');
      container.id = 'toast-container';
      container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
      container.style.zIndex = '1055';
      document.body.appendChild(container);
      return container;
    }
  }));
});