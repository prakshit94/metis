import Alpine from 'alpinejs';
import { Modal } from 'bootstrap';

function getCsrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

async function apiFetch(url, options = {}) {
  const { headers, ...otherOptions } = options;
  const res = await fetch(url, {
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': getCsrfToken(),
      ...(headers || {}),
    },
    ...otherOptions,
  });

  const text = await res.text();
  const data = text ? JSON.parse(text) : {};

  if (!res.ok) {
    const validation = data?.errors ? Object.values(data.errors).flat().join(' ') : '';
    const message = validation || data?.message || data?.error || 'Request failed';
    if (res.status === 403 || message.toLowerCase().includes("authoriz") || message.toLowerCase().includes("forbidden")) { window.location.href = "/"; return; }
    throw new Error(message);
  }

  return data;
}

function showToast(message, type = 'success') {
  const container = document.getElementById('toast-container');
  if (!container) return;

  const iconMap = {
    success: 'bi-check-circle-fill',
    danger:  'bi-x-circle-fill',
    warning: 'bi-exclamation-triangle-fill',
    info:    'bi-info-circle-fill',
  };

  const el = document.createElement('div');
  el.className = `toast align-items-center text-bg-${type} border-0 show mb-2`;
  el.setAttribute('role', 'alert');
  el.innerHTML = `
    <div class="d-flex">
      <div class="toast-body">
        <i class="bi ${iconMap[type] ?? 'bi-info-circle-fill'} me-2"></i><span></span>
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>`;
  el.querySelector('.toast-body span').textContent = message;

  container.appendChild(el);
  setTimeout(() => el.remove(), 5000);
}

function getModal(id) {
  const el = document.getElementById(id);
  return el ? Modal.getOrCreateInstance(el) : null;
}

document.addEventListener('alpine:init', () => {
  Alpine.data('invoicesTable', () => ({
    invoices: [],
    selectedInvoices: [],
    currentPage: 1,
    totalPages: 1,
    totalItems: 0,
    itemsPerPage: 10,
    isLoading: false,
    isSubmitting: false,

    searchQuery: '',
    statusFilter: '',
    sortField: 'id',
    sortDirection: 'desc',

    stats: {
      total_invoiced: 0,
      collected_amount: 0,
      pending_amount: 0,
      avg_value: 0,
    },

    selectedInvoice: null,
    paymentForm: {
      invoice_id: null,
      max_amount: 0,
      amount: '',
      payment_method: 'credit_card',
      transaction_id: '',
      payment_date: ''
    },

    importStep: 1,
    importFile: null,
    importPreview: [],
    importErrors: [],
    isImporting: false,

    init() {
      this.loadInvoices();
    },

    loadInvoices() {
      this.isLoading = true;
      const params = new URLSearchParams();

      if (this.searchQuery) params.append('search', this.searchQuery);
      if (this.statusFilter) params.append('status', this.statusFilter);

      params.append('limit', this.itemsPerPage);
      params.append('page', this.currentPage);
      params.append('sort_field', this.sortField);
      params.append('sort_direction', this.sortDirection);

      apiFetch(`/invoices?${params.toString()}`)
        .then(data => {
          this.invoices = data.invoices?.data || [];
          this.currentPage = data.invoices?.current_page || 1;
          this.totalPages = data.invoices?.last_page || 1;
          this.totalItems = data.invoices?.total || 0;

          if (data.stats) {
            this.stats = { ...this.stats, ...data.stats };
          }
        })
        .catch(err => showToast(err.message, 'danger'))
        .finally(() => { this.isLoading = false; });
    },

    filterInvoices() {
      this.currentPage = 1;
      
      this.loadInvoices();
    },

    sortBy(field) {
      this.sortDirection = (this.sortField === field && this.sortDirection === 'asc') ? 'desc' : 'asc';
      this.sortField = field;
      this.currentPage = 1;
      this.loadInvoices();
    },

    goToPage(page) {
      if (page >= 1 && page <= this.totalPages) {
        this.currentPage = page;
        this.loadInvoices();
      }
    },

    get visiblePages() {
      if (this.totalPages <= 1) return [1];
      const pages = [1];
      if (this.totalPages <= 7) {
        for (let i = 2; i <= this.totalPages; i++) pages.push(i);
      } else {
        if (this.currentPage > 3) pages.push('...');
        const start = Math.max(2, this.currentPage - 1);
        const end = Math.min(this.totalPages - 1, this.currentPage + 1);
        for (let i = start; i <= end; i++) pages.push(i);
        if (this.currentPage < this.totalPages - 2) pages.push('...');
        pages.push(this.totalPages);
      }
      return pages;
    },

    toggleAll(checked) {
      if (checked) {
        this.invoices.forEach(item => {
          if (!this.selectedInvoices.includes(String(item.id))) {
            this.selectedInvoices.push(String(item.id));
          }
        });
      } else {
        const currentIds = this.invoices.map(item => String(item.id));
        this.selectedInvoices = this.selectedInvoices.filter(id => !currentIds.includes(id));
      }
    },

    async bulkUpdateStatus(status) {
      if (!this.selectedInvoices.length) return;

      this.isSubmitting = true;
      try {
        const res = await apiFetch('/invoices/bulk-status', {
          method: 'POST',
          body: JSON.stringify({
            ids: this.selectedInvoices,
            status: status
          })
        });
        showToast(res.message || 'Status updated successfully.');
        this.selectedInvoices = [];
        this.loadInvoices();
      } catch (err) {
        showToast(err.message, 'danger');
      } finally {
        this.isSubmitting = false;
      }
    },

    async exportSelectedInvoices() {
      if (!this.selectedInvoices.length) return;
      this.isSubmitting = true;
      try {
        const res = await fetch('/invoices/export', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken()
          },
          body: JSON.stringify({ ids: this.selectedInvoices })
        });
        
        if (!res.ok) {
          const text = await res.text();
          const errData = text ? JSON.parse(text) : {};
          throw new Error(errData.message || 'Export failed');
        }

        const blob = await res.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.style.display = 'none';
        a.href = url;
        // filename is normally from Content-Disposition, but we can set a fallback
        a.download = 'invoices_export.csv';
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        
        showToast('Invoices exported successfully.');
        this.selectedInvoices = [];
      } catch (err) {
        showToast(err.message, 'danger');
      } finally {
        this.isSubmitting = false;
      }
    },

    async viewDetails(invoice) {
      try {
        const data = await apiFetch(`/invoices/${invoice.id}`);
        this.selectedInvoice = {
          ...data.invoice,
          paymentHistory: data.invoice.payments || [],
        };
        this.$nextTick(() => {
          getModal('detailModal')?.show();
        });
      } catch (err) {
        showToast(err.message, 'danger');
      }
    },

    recordPayment(invoice) {
      const now = new Date();
      const localISOTime = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 16);

      this.paymentForm = {
        invoice_id: invoice.id,
        max_amount: invoice.due_amount,
        amount: invoice.due_amount,
        payment_method: 'credit_card',
        transaction_id: '',
        payment_date: localISOTime
      };
      this.$nextTick(() => {
        getModal('paymentModal')?.show();
      });
    },

    async submitPayment() {
      if (!this.paymentForm.invoice_id) return;
      this.isSubmitting = true;
      try {
        const res = await apiFetch(`/invoices/${this.paymentForm.invoice_id}/payments`, {
          method: 'POST',
          body: JSON.stringify({
            amount: this.paymentForm.amount,
            payment_method: this.paymentForm.payment_method,
            transaction_id: this.paymentForm.transaction_id,
            payment_date: this.paymentForm.payment_date
          })
        });
        showToast(res.message || 'Payment recorded successfully.');
        getModal('paymentModal')?.hide();
        this.loadInvoices();
      } catch (err) {
        showToast(err.message, 'danger');
      } finally {
        this.isSubmitting = false;
      }
    },

    openImportModal() {
      this.importStep = 1;
      this.importFile = null;
      this.importPreview = [];
      this.importErrors = [];
      if (document.getElementById('importFile')) {
        document.getElementById('importFile').value = '';
      }
      this.$nextTick(() => {
        getModal('importPaymentsModal')?.show();
      });
    },

    async previewImport() {
      if (!this.importFile) return;
      this.isImporting = true;
      const formData = new FormData();
      formData.append('file', this.importFile);

      try {
        const res = await fetch('/payments/import/preview', {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': getCsrfToken(), 'Accept': 'application/json' },
          body: formData
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.message || 'Failed to parse file.');
        
        this.importPreview = data.preview || [];
        this.importErrors = data.errors || [];
        this.importStep = 2;
      } catch (err) {
        showToast(err.message, 'danger');
      } finally {
        this.isImporting = false;
      }
    },

    async processImport() {
      if (!this.importPreview.length) return;
      this.isImporting = true;
      try {
        const res = await apiFetch('/payments/import/process', {
          method: 'POST',
          body: JSON.stringify({ payments: this.importPreview })
        });
        showToast(res.message);
        if (res.errors && res.errors.length) {
          res.errors.forEach(e => showToast(e, 'warning'));
        }
        getModal('importPaymentsModal')?.hide();
        this.loadInvoices();
      } catch (err) {
        showToast(err.message, 'danger');
      } finally {
        this.isImporting = false;
      }
    },

    formatCurrency(value) {
      const n = Number.parseFloat(value ?? 0);
      return Number.isFinite(n) ? '₹' + n.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '₹0.00';
    },

    formatDate(value) {
      if (!value) return 'N/A';
      const d = new Date(value);
      return Number.isNaN(d.getTime()) ? 'N/A' : d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
    }
  }));
});
