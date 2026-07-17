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
      paid: 0,
      unpaid: 0,
      avg_value: 0,
    },

    selectedInvoice: null,

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
      this.selectedInvoices = [];
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
      this.selectedInvoices = checked ? this.invoices.map(i => String(i.id)) : [];
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

    viewDetails(invoice) {
      this.selectedInvoice = invoice;
      this.$nextTick(() => {
        getModal('detailModal')?.show();
      });
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
