import Alpine from 'alpinejs';
import { Modal } from 'bootstrap';

// ─── CSRF helper ─────────────────────────────────────────────────────────────
function getCsrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

// ─── API fetch helper ────────────────────────────────────────────────────────
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

// ─── Toast helper ─────────────────────────────────────────────────────────────
function showToast(message, type = 'success') {
  const container = document.getElementById('toast-container');
  if (!container) return;

  const id = 'toast-' + Date.now();
  const iconMap = {
    success: 'bi-check-circle-fill',
    danger:  'bi-x-circle-fill',
    warning: 'bi-exclamation-triangle-fill',
    info:    'bi-info-circle-fill',
  };

  const el = document.createElement('div');
  el.id = id;
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
  setTimeout(() => el.remove(), 4000);
}

document.addEventListener('alpine:init', () => {
  Alpine.data('returnsTable', () => ({
    returns: [],
    selectedReturns: [],
    currentPage: 1,
    totalPages: 1,
    totalReturns: 0,
    itemsPerPage: 15,
    
    // Filters state
    searchQuery: '',
    statusFilter: '',
    financialFilter: '',
    sortField: 'id',
    sortDirection: 'desc',
    isLoading: false,
    
    // Statistics
    stats: {
      total: 0,
      pending_qc: 0,
      completed: 0,
      rejected: 0,
      total_refunded: 0,
      total_credited: 0
    },

    // Dropdown lists
    statusesList: [],
    financialStatusesList: [],

    // Modal data state
    selectedReturn: null,
    selectedReturns: [],
    totalPaidForSelected: 0,
    financeAction: 'refund',
    financeAmount: 0,
    financeMethod: 'upi',

    init() {
      this.loadReturns();
    },

    loadReturns() {
      this.isLoading = true;
      const params = new URLSearchParams();
      
      if (this.searchQuery) params.append('search', this.searchQuery);
      if (this.statusFilter) params.append('status', this.statusFilter);
      if (this.financialFilter) params.append('financial_status', this.financialFilter);
      
      params.append('limit', this.itemsPerPage);
      params.append('page', this.currentPage);
      params.append('sort_field', this.sortField);
      params.append('sort_direction', this.sortDirection);

      apiFetch(`/returns?${params.toString()}`)
        .then(data => {
          this.returns = (data.returns.data || []).map(r => this.mapReturn(r));
          this.currentPage = data.returns.current_page || 1;
          this.totalPages = data.returns.last_page || 1;
          this.totalReturns = data.returns.total || 0;
          
          if (data.stats) {
            this.stats = {
              total: data.stats.total,
              pending_qc: data.stats.pending_qc,
              completed: data.stats.completed,
              rejected: data.stats.rejected,
              total_refunded: data.stats.total_refunded,
              total_credited: data.stats.total_credited,
            };
          }

          if (data.statuses) this.statusesList = data.statuses;
          if (data.financial_statuses) this.financialStatusesList = data.financial_statuses;
        })
        .catch(err => {
          showToast(err.message, 'danger');
        })
        .finally(() => {
          this.isLoading = false;
        });
    },

    mapReturn(r) {
      // Map items and ensure fields are properly typed
      const items = (r.items || []).map(item => ({
        id: item.id,
        product: item.product || { name: 'Unknown', sku: 'N/A' },
        requested_qty: parseFloat(item.requested_qty || 0),
        received_qty: parseFloat(item.received_qty || item.requested_qty || 0), // Default to requested if 0
        restocked_qty: parseFloat(item.restocked_qty || 0),
        damaged_qty: parseFloat(item.damaged_qty || 0),
        qc_notes: item.qc_notes || '',
        qc_status: item.qc_status || 'pending'
      }));

      // Calculate total paid for the associated order
      const orderPayments = r.order?.payments || [];
      const totalPaid = orderPayments
        .filter(p => p.status === 'completed')
        .reduce((sum, p) => sum + parseFloat(p.amount || 0), 0);

      return {
        id: r.id,
        return_no: r.return_no,
        order_id: r.order_id,
        order_no: r.order?.order_no || 'N/A',
        status: r.status,
        financial_status: r.financial_status,
        reason: r.reason || 'N/A',
        notes: r.notes || '',
        refund_amount: parseFloat(r.refund_amount || 0),
        credit_note_amount: parseFloat(r.credit_note_amount || 0),
        created_at: r.created_at,
        customer: {
          name: r.order?.party ? `${r.order.party.firstname} ${r.order.party.lastname}` : 'N/A',
        },
        order: r.order,
        items: items,
        refunds: r.refunds || [],
        totalPaid: totalPaid,
        original: r
      };
    },

    formatCurrency(value) {
      const amount = Number.parseFloat(value ?? 0);
      return Number.isFinite(amount) ? '₹' + amount.toFixed(2) : '₹0.00';
    },

    formatDateTime(value) {
      if (!value) return 'N/A';
      const date = new Date(value);
      return Number.isNaN(date.getTime()) ? 'N/A' : date.toLocaleString();
    },

    formatDate(value) {
      if (!value) return 'N/A';
      const date = new Date(value);
      return Number.isNaN(date.getTime()) ? 'N/A' : date.toLocaleDateString();
    },

    getStatusColor(status) {
      const colors = {
        pending: '#f97316',
        received: '#0ea5e9',
        qc_in_progress: '#3b82f6',
        completed: '#10b981',
        rejected: '#ef4444'
      };
      return colors[status] || '#6c757d';
    },

    getFinancialStatusColor(status) {
      const colors = {
        pending: '#f97316',
        partial_refund: '#0ea5e9',
        fully_refunded: '#10b981',
        credited: '#6366f1'
      };
      return colors[status] || '#6c757d';
    },

    filterReturns() {
      this.currentPage = 1;
      this.loadReturns();
    },

    clearFilters() {
      this.searchQuery = '';
      this.statusFilter = '';
      this.financialFilter = '';
      this.sortField = 'id';
      this.sortDirection = 'desc';
      this.currentPage = 1;
      this.loadReturns();
    },

    hasActiveAdvancedFilters() {
      return Boolean(
        this.statusFilter ||
        this.financialFilter
      );
    },

    sortBy(field) {
      if (this.sortField === field) {
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
      } else {
        this.sortField = field;
        this.sortDirection = 'desc';
      }
      this.currentPage = 1;
      this.loadReturns();
    },
    
    viewReturnDetails(r) {
      // Fetch full details if needed, but we have enough from mapReturn for now,
      // though typically we might want to fetch `show` if we didn't eager load everything.
      // Assuming index eagerly loads everything we need:
      this.selectedReturn = r;
      this.financeAmount = 0;
      this.financeAction = 'refund';
      this.financeMethod = 'upi';
    },

    closeReturnDetails() {
      this.selectedReturn = null;
    },

    toggleAll(checked) {
      this.selectedReturns = checked ? this.returns.map(r => String(r.id)) : [];
    },

    async processQc() {
      try {
        const payload = {
          items: this.selectedReturn.items.map(i => ({
            id: i.id,
            received_qty: parseFloat(i.received_qty || 0),
            restocked_qty: parseFloat(i.restocked_qty || 0),
            damaged_qty: parseFloat(i.damaged_qty || 0),
            qc_notes: i.qc_notes
          }))
        };

        const res = await apiFetch(`/returns/${this.selectedReturn.id}/qc`, { 
          method: 'POST',
          body: JSON.stringify(payload)
        });
        
        showToast(res.message || 'Quality Check processed successfully.');
        
        // Refresh data
        this.loadReturns();
        
        // Temporarily clear selectedReturn to force re-render, then set it back with updated data
        const returnId = this.selectedReturn.id;
        this.selectedReturn = null;
        
        // After loading, the updated return will be in `this.returns`, but we need to wait for loadReturns to finish
        setTimeout(() => {
          this.selectedReturn = this.returns.find(r => r.id === returnId) || null;
        }, 500);

      } catch (err) {
        showToast(err.message, 'danger');
      }
    },

    async processFinance() {
      if (this.financeAmount <= 0) {
        showToast('Amount must be greater than zero', 'warning');
        return;
      }

      try {
        const payload = {
          action: this.financeAction,
          amount: parseFloat(this.financeAmount),
          payment_method: this.financeMethod
        };

        const res = await apiFetch(`/returns/${this.selectedReturn.id}/finance`, { 
          method: 'POST',
          body: JSON.stringify(payload)
        });
        
        showToast(res.message || 'Financial action processed successfully.');
        
        // Refresh data
        this.loadReturns();
        
        const returnId = this.selectedReturn.id;
        this.selectedReturn = null;
        
        setTimeout(() => {
          this.selectedReturn = this.returns.find(r => r.id === returnId) || null;
        }, 500);

      } catch (err) {
        showToast(err.message, 'danger');
      }
    }
  }));
});
