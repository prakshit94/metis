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
  Alpine.data('paymentsTable', () => ({
    payments: [],
    selectedPayments: [],
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
      total_volume: 0,
      captured_amount: 0,
      authorized_amount: 0,
      failed_amount: 0,
    },

    selectedPayment: null,
    editForm: {
      id: null,
      amount: '',
      payment_date: '',
      payment_method: '',
      status: '',
      transaction_id: ''
    },

    importStep: 1,
    importFile: null,
    importPreview: [],
    importErrors: [],
    isImporting: false,

    init() {
      this.loadPayments();
    },

    loadPayments() {
      this.isLoading = true;
      const params = new URLSearchParams();

      if (this.searchQuery) params.append('search', this.searchQuery);
      if (this.statusFilter) params.append('status', this.statusFilter);

      params.append('limit', this.itemsPerPage);
      params.append('page', this.currentPage);
      params.append('sort_field', this.sortField);
      params.append('sort_direction', this.sortDirection);

      apiFetch(`/payments?${params.toString()}`)
        .then(data => {
          this.payments = data.payments?.data || [];
          this.currentPage = data.payments?.current_page || 1;
          this.totalPages = data.payments?.last_page || 1;
          this.totalItems = data.payments?.total || 0;

          if (data.stats) {
            this.stats = { ...this.stats, ...data.stats };
          }
        })
        .catch(err => showToast(err.message, 'danger'))
        .finally(() => { this.isLoading = false; });
    },

    filterPayments() {
      this.currentPage = 1;
      this.selectedPayments = [];
      this.loadPayments();
    },

    sortBy(field) {
      this.sortDirection = (this.sortField === field && this.sortDirection === 'asc') ? 'desc' : 'asc';
      this.sortField = field;
      this.currentPage = 1;
      this.loadPayments();
    },

    goToPage(page) {
      if (page >= 1 && page <= this.totalPages) {
        this.currentPage = page;
        this.loadPayments();
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
      this.selectedPayments = checked ? this.payments.map(p => String(p.id)) : [];
    },

    async bulkUpdateStatus(status) {
      if (!this.selectedPayments.length) return;

      this.isSubmitting = true;
      try {
        const res = await apiFetch('/payments/bulk-status', {
          method: 'POST',
          body: JSON.stringify({
            ids: this.selectedPayments,
            status: status
          })
        });
        showToast(res.message || 'Status updated successfully.');
        this.selectedPayments = [];
        this.loadPayments();
      } catch (err) {
        showToast(err.message, 'danger');
      } finally {
        this.isSubmitting = false;
      }
    },

    async updatePaymentStatus(id, status) {
      this.isSubmitting = true;
      try {
        const res = await apiFetch('/payments/bulk-status', {
          method: 'POST',
          body: JSON.stringify({
            ids: [id],
            status: status
          })
        });
        showToast(res.message || 'Payment status updated.');
        this.loadPayments();
      } catch (err) {
        showToast(err.message, 'danger');
      } finally {
        this.isSubmitting = false;
      }
    },

    async exportSelectedPayments() {
      if (!this.selectedPayments.length) return;
      this.isSubmitting = true;
      try {
        const res = await fetch('/payments/export', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken()
          },
          body: JSON.stringify({ ids: this.selectedPayments })
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
        a.download = 'payments_export.csv';
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        
        showToast('Payments exported successfully.');
        this.selectedPayments = [];
      } catch (err) {
        showToast(err.message, 'danger');
      } finally {
        this.isSubmitting = false;
      }
    },

    viewDetails(payment) {
      this.selectedPayment = payment;
      this.$nextTick(() => {
        getModal('detailModal')?.show();
      });
    },

    editPayment(payment) {
      this.editForm = {
        id: payment.id,
        amount: payment.amount,
        payment_date: payment.payment_date ? payment.payment_date.slice(0, 16) : '',
        payment_method: payment.payment_method,
        status: payment.status,
        transaction_id: payment.transaction_id || ''
      };
      this.$nextTick(() => {
        getModal('editModal')?.show();
      });
    },

    async updatePayment() {
      if (!this.editForm.id) return;
      this.isSubmitting = true;
      try {
        const res = await apiFetch(`/payments/${this.editForm.id}`, {
          method: 'PUT',
          body: JSON.stringify(this.editForm)
        });
        showToast(res.message || 'Payment updated successfully.');
        getModal('editModal')?.hide();
        this.loadPayments();
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
        this.loadPayments();
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
