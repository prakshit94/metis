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

// ─── Modal helper ─────────────────────────────────────────────────────────────
function getModal(id) {
  const el = document.getElementById(id);
  return el ? Modal.getOrCreateInstance(el) : null;
}

// ─── Main Alpine Component ────────────────────────────────────────────────────
document.addEventListener('alpine:init', () => {
  Alpine.data('returnsTable', () => ({
    // --- Table state ---
    returns: [],
    selectedReturns: [],
    currentPage: 1,
    totalPages: 1,
    totalReturns: 0,
    itemsPerPage: 15,
    isLoading: false,
    isSubmitting: false,

    // --- Filters ---
    searchQuery: '',
    statusFilter: '',
    financialFilter: '',
    sortField: 'id',
    sortDirection: 'desc',

    // --- Stats ---
    stats: {
      total: 0,
      pending_qc: 0,
      completed: 0,
      rejected: 0,
      total_refunded: 0,
      total_credited: 0,
    },

    // --- QC Modal state ---
    selectedReturn: null,
    // Deep-editable copy of items for QC form
    qcItems: [],

    // --- Bulk QC Modal state ---
    bulkQcItems: [],
    selectedReturnsForBulk: [],

    // --- Finance Modal state ---
    financeAction: 'refund',
    financeAmount: 0,
    financeMethod: 'upi',

    // ─── Lifecycle ───────────────────────────────────────────────────────────

    init() {
      this.loadReturns();
    },

    // ─── Data Loading ─────────────────────────────────────────────────────────

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
          this.returns = (data.returns?.data || []).map(r => this.mapReturn(r));
          this.currentPage = data.returns?.current_page || 1;
          this.totalPages  = data.returns?.last_page  || 1;
          this.totalReturns = data.returns?.total     || 0;

          if (data.stats) {
            this.stats = { ...this.stats, ...data.stats };
          }
        })
        .catch(err => showToast(err.message, 'danger'))
        .finally(() => { this.isLoading = false; });
    },

    // ─── Mapping ──────────────────────────────────────────────────────────────

    mapReturn(r) {
      const items = (r.items || []).map(item => ({
        id: item.id,
        product_id: item.product_id,
        product:        item.product  || { name: 'Unknown', sku: 'N/A', image_url: null },
        image_url:      item.product?.image_url || item.product?.image_path || null,
        requested_qty:  parseFloat(item.requested_qty  || 0),
        received_qty:   parseFloat(item.received_qty   || 0),
        restocked_qty:  parseFloat(item.restocked_qty  || 0),
        damaged_qty:    parseFloat(item.damaged_qty    || 0),
        qc_notes:  item.qc_notes  || '',
        qc_status: item.qc_status || 'pending',
      }));

      const totalPaid = (r.order?.payments || [])
        .filter(p => p.status === 'completed')
        .reduce((sum, p) => sum + parseFloat(p.amount || 0), 0);

      return {
        id:              r.id,
        return_no:       r.return_no,
        order_id:        r.order_id,
        order_no:        r.order?.order_no || 'N/A',
        status:          r.status,
        financial_status:r.financial_status,
        reason:          r.reason || 'N/A',
        notes:           r.notes  || '',
        refund_amount:   parseFloat(r.refund_amount        || 0),
        credit_note_amount: parseFloat(r.credit_note_amount || 0),
        created_at:      r.created_at,
        customer: {
          name: r.order?.party
            ? `${r.order.party.firstname} ${r.order.party.lastname || ''}`.trim()
            : 'N/A',
        },
        order:    r.order,
        items:    items,
        refunds:  r.refunds || [],
        totalPaid,
        original: r,
      };
    },

    // ─── Filters / Sort / Pagination ──────────────────────────────────────────

    filterReturns() {
      this.currentPage = 1;
      this.selectedReturns = [];
      this.loadReturns();
    },

    clearFilters() {
      this.searchQuery    = '';
      this.statusFilter   = '';
      this.financialFilter = '';
      this.sortField      = 'id';
      this.sortDirection  = 'desc';
      this.currentPage    = 1;
      this.loadReturns();
    },

    sortBy(field) {
      this.sortDirection = (this.sortField === field && this.sortDirection === 'asc') ? 'desc' : 'asc';
      this.sortField = field;
      this.currentPage = 1;
      this.loadReturns();
    },

    goToPage(page) {
      if (page >= 1 && page <= this.totalPages) {
        this.currentPage = page;
        this.loadReturns();
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
        const end   = Math.min(this.totalPages - 1, this.currentPage + 1);
        for (let i = start; i <= end; i++) pages.push(i);
        if (this.currentPage < this.totalPages - 2) pages.push('...');
        pages.push(this.totalPages);
      }
      return pages;
    },

    // ─── Selection / Bulk ─────────────────────────────────────────────────────

    toggleAll(checked) {
      this.selectedReturns = checked ? this.returns.map(r => String(r.id)) : [];
    },

    get allSelected() {
      return this.returns.length > 0 && this.selectedReturns.length === this.returns.length;
    },

    /**
     * Bulk approve: loop each selected pending return and call QC with
     * restocked = requested (100 % good), damaged = 0.
     */
    async bulkUpdateStatus(action) {
      if (!this.selectedReturns.length) return;

      const pendingReturns = this.returns.filter(r =>
        this.selectedReturns.includes(String(r.id)) && r.status === 'pending'
      );

      if (!pendingReturns.length) {
        showToast('No pending returns selected.', 'warning');
        return;
      }

      this.isSubmitting = true;
      let successCount = 0;
      let failCount = 0;

      for (const ret of pendingReturns) {
        try {
          const payload = {
            items: ret.items.map(i => ({
              id:            i.id,
              received_qty:  i.requested_qty,
              restocked_qty: action === 'completed' ? i.requested_qty : 0,
              damaged_qty:   action === 'rejected'  ? i.requested_qty : 0,
              qc_notes:      action === 'completed'
                ? 'Bulk approved — all items restocked'
                : 'Bulk rejected — all items marked damaged',
            })),
          };
          await apiFetch(`/returns/${ret.id}/qc`, { method: 'POST', body: JSON.stringify(payload) });
          successCount++;
        } catch {
          failCount++;
        }
      }

      this.isSubmitting = false;
      this.selectedReturns = [];

      if (successCount) showToast(`${successCount} return(s) processed successfully.`);
      if (failCount)    showToast(`${failCount} return(s) failed.`, 'warning');

      this.loadReturns();
    },

    // ─── QC Inspect Modal ─────────────────────────────────────────────────────

    /** Open Inspect QC modal for a single return */
    viewReturnDetails(ret) {
      this.selectedReturn = ret;
      // Deep-copy items so we don't mutate the table data until confirmed
      this.qcItems = ret.items.map(i => ({
        ...i,
        image_url:     i.image_url || i.product?.image_url || null,
        received_qty:  i.requested_qty,
        restocked_qty: i.requested_qty,
        damaged_qty:   0,
        qc_notes:      i.qc_notes || '',
      }));
      this.financeAmount = 0;
      this.financeAction = 'refund';
      this.financeMethod = 'upi';
      this.$nextTick(() => getModal('qcInspectModal')?.show());
    },

    closeQcModal() {
      getModal('qcInspectModal')?.hide();
      setTimeout(() => {
        this.selectedReturn = null;
        this.qcItems = [];
      }, 300);
    },

    /** Per-item validation: damaged + restocked should not exceed received, received ≤ requested */
    qcItemValid(item) {
      const req  = parseFloat(item.requested_qty || 0);
      const recv = parseFloat(item.received_qty  || 0);
      const rest = parseFloat(item.restocked_qty || 0);
      const dmg  = parseFloat(item.damaged_qty   || 0);
      return recv >= 0 && recv <= req && rest >= 0 && dmg >= 0 && (rest + dmg) <= recv;
    },

    get qcFormValid() {
      return this.qcItems.length > 0 && this.qcItems.every(i => this.qcItemValid(i));
    },

    /** Clamp received to [0, requested], then auto-set restocked to remainder */
    onReceivedChange(item) {
      const req  = parseFloat(item.requested_qty || 0);
      item.received_qty  = Math.min(Math.max(0, parseFloat(item.received_qty || 0)), req);
      const recv = item.received_qty;
      const dmg  = Math.min(parseFloat(item.damaged_qty || 0), recv);
      item.damaged_qty   = dmg;
      item.restocked_qty = Math.max(0, recv - dmg);
    },

    /** Clamp damaged to [0, received], then auto-set restocked to remainder */
    onDamagedChange(item) {
      const recv = parseFloat(item.received_qty || 0);
      item.damaged_qty   = Math.min(Math.max(0, parseFloat(item.damaged_qty || 0)), recv);
      item.restocked_qty = Math.max(0, recv - item.damaged_qty);
    },

    /** Clamp restocked to [0, received - damaged] */
    onRestockedChange(item) {
      const recv = parseFloat(item.received_qty || 0);
      const dmg  = parseFloat(item.damaged_qty  || 0);
      item.restocked_qty = Math.min(Math.max(0, parseFloat(item.restocked_qty || 0)), Math.max(0, recv - dmg));
    },

    /** Quick-fill: mark all as fully restocked (all good) */
    markAllGood() {
      this.qcItems.forEach(i => {
        i.received_qty  = i.requested_qty;
        i.restocked_qty = i.requested_qty;
        i.damaged_qty   = 0;
      });
    },

    /** Quick-fill: mark all as damaged */
    markAllDamaged() {
      this.qcItems.forEach(i => {
        i.received_qty  = i.requested_qty;
        i.restocked_qty = 0;
        i.damaged_qty   = i.requested_qty;
      });
    },

    /** Submit QC with full per-item quantities */
    async processQc() {
      if (!this.qcFormValid) {
        showToast('Please fix validation errors before submitting.', 'warning');
        return;
      }

      this.isSubmitting = true;
      try {
        const payload = {
          items: this.qcItems.map(i => ({
            id:            i.id,
            received_qty:  parseFloat(i.received_qty  || 0),
            restocked_qty: parseFloat(i.restocked_qty || 0),
            damaged_qty:   parseFloat(i.damaged_qty   || 0),
            qc_notes:      i.qc_notes || null,
          })),
        };

        const res = await apiFetch(`/returns/${this.selectedReturn.id}/qc`, {
          method: 'POST',
          body: JSON.stringify(payload),
        });

        showToast(res.message || 'QC submitted successfully.');
        this.closeQcModal();
        this.loadReturns();
      } catch (err) {
        showToast(err.message, 'danger');
      } finally {
        this.isSubmitting = false;
      }
    },

    // ─── Bulk QC Inspect Modal ────────────────────────────────────────────────
    openBulkQcModal() {
      // Find selected pending returns
      this.selectedReturnsForBulk = this.returns.filter(r =>
        this.selectedReturns.includes(String(r.id)) && r.status === 'pending'
      );

      if (!this.selectedReturnsForBulk.length) {
        showToast('No pending returns selected.', 'warning');
        return;
      }

      // Aggregate all items across selected returns by product_id
      const aggregationMap = {};
      for (const ret of this.selectedReturnsForBulk) {
        for (const item of ret.items) {
          const pid = item.product_id;
          if (!aggregationMap[pid]) {
            aggregationMap[pid] = {
              product_id: pid,
              product: item.product || { name: 'Unknown', sku: 'N/A', image_url: null },
              image_url: item.image_url || item.product?.image_url || null,
              requested_qty: 0,
              received_qty: 0,
              restocked_qty: 0,
              damaged_qty: 0,
              qc_notes: '',
              items: [],
            };
          }
          aggregationMap[pid].requested_qty += parseFloat(item.requested_qty || 0);
          aggregationMap[pid].items.push({
            id: item.id,
            return_id: ret.id,
            requested_qty: parseFloat(item.requested_qty || 0),
          });
        }
      }

      // Map map to array and set default quantities
      this.bulkQcItems = Object.values(aggregationMap).map(p => {
        p.received_qty = p.requested_qty;
        p.restocked_qty = p.requested_qty;
        p.damaged_qty = 0;
        return p;
      });

      this.$nextTick(() => getModal('bulkQcModal')?.show());
    },

    closeBulkQcModal() {
      getModal('bulkQcModal')?.hide();
      setTimeout(() => {
        this.selectedReturnsForBulk = [];
        this.bulkQcItems = [];
      }, 300);
    },

    bulkQcItemValid(item) {
      const req  = parseFloat(item.requested_qty || 0);
      const recv = parseFloat(item.received_qty  || 0);
      const rest = parseFloat(item.restocked_qty || 0);
      const dmg  = parseFloat(item.damaged_qty   || 0);
      return recv >= 0 && recv <= req && rest >= 0 && dmg >= 0 && (rest + dmg) <= recv;
    },

    get bulkQcFormValid() {
      return this.bulkQcItems.length > 0 && this.bulkQcItems.every(i => this.bulkQcItemValid(i));
    },

    onBulkReceivedChange(item) {
      const req  = parseFloat(item.requested_qty || 0);
      item.received_qty  = Math.min(Math.max(0, parseFloat(item.received_qty || 0)), req);
      const recv = item.received_qty;
      const dmg  = Math.min(parseFloat(item.damaged_qty || 0), recv);
      item.damaged_qty   = dmg;
      item.restocked_qty = Math.max(0, recv - dmg);
    },

    onBulkDamagedChange(item) {
      const recv = parseFloat(item.received_qty || 0);
      item.damaged_qty   = Math.min(Math.max(0, parseFloat(item.damaged_qty || 0)), recv);
      item.restocked_qty = Math.max(0, recv - item.damaged_qty);
    },

    onBulkRestockedChange(item) {
      const recv = parseFloat(item.received_qty || 0);
      const dmg  = parseFloat(item.damaged_qty  || 0);
      item.restocked_qty = Math.min(Math.max(0, parseFloat(item.restocked_qty || 0)), Math.max(0, recv - dmg));
    },

    bulkMarkAllGood() {
      this.bulkQcItems.forEach(i => {
        i.received_qty  = i.requested_qty;
        i.restocked_qty = i.requested_qty;
        i.damaged_qty   = 0;
      });
    },

    bulkMarkAllDamaged() {
      this.bulkQcItems.forEach(i => {
        i.received_qty  = i.requested_qty;
        i.restocked_qty = 0;
        i.damaged_qty   = i.requested_qty;
      });
    },

    async submitBulkQc() {
      if (!this.bulkQcFormValid) {
        showToast('Please fix validation errors before submitting.', 'warning');
        return;
      }

      this.isSubmitting = true;

      // Object to hold the final distributed QC payload for each return ID
      const returnPayloads = {};

      for (const aggProd of this.bulkQcItems) {
        let R = parseFloat(aggProd.received_qty || 0);
        let S = parseFloat(aggProd.restocked_qty || 0);
        let D = parseFloat(aggProd.damaged_qty || 0);
        const notes = aggProd.qc_notes || 'Bulk Processed QC';

        // Sort items by ID for deterministic distribution
        const sortedItems = [...aggProd.items].sort((a, b) => a.id - b.id);

        for (const item of sortedItems) {
          const req = parseFloat(item.requested_qty || 0);
          const itemReceived = Math.min(req, R);
          R -= itemReceived;

          const itemRestocked = Math.min(itemReceived, S);
          S -= itemRestocked;

          const itemDamaged = Math.min(itemReceived - itemRestocked, D);
          D -= itemDamaged;

          if (!returnPayloads[item.return_id]) {
            returnPayloads[item.return_id] = { items: [] };
          }

          returnPayloads[item.return_id].items.push({
            id:            item.id,
            received_qty:  itemReceived,
            restocked_qty: itemRestocked,
            damaged_qty:   itemDamaged,
            qc_notes:      notes,
          });
        }
      }

      let successCount = 0;
      let failCount = 0;

      // Submit API calls sequentially
      for (const returnId of Object.keys(returnPayloads)) {
        try {
          const payload = returnPayloads[returnId];
          await apiFetch(`/returns/${returnId}/qc`, {
            method: 'POST',
            body: JSON.stringify(payload),
          });
          successCount++;
        } catch (err) {
          console.error(`Bulk QC failed for return ID ${returnId}:`, err);
          failCount++;
        }
      }

      this.isSubmitting = false;
      this.selectedReturns = [];

      if (successCount) showToast(`${successCount} return(s) processed successfully.`);
      if (failCount)    showToast(`${failCount} return(s) failed.`, 'warning');

      this.closeBulkQcModal();
      this.loadReturns();
    },

    // ─── Finance ──────────────────────────────────────────────────────────────

    async processFinance() {
      if (this.financeAmount <= 0) {
        showToast('Amount must be greater than zero.', 'warning');
        return;
      }

      this.isSubmitting = true;
      try {
        const payload = {
          action:         this.financeAction,
          amount:         parseFloat(this.financeAmount),
          payment_method: this.financeMethod,
        };

        const res = await apiFetch(`/returns/${this.selectedReturn.id}/finance`, {
          method: 'POST',
          body: JSON.stringify(payload),
        });

        showToast(res.message || 'Financial action processed successfully.');
        this.closeQcModal();
        this.loadReturns();
      } catch (err) {
        showToast(err.message, 'danger');
      } finally {
        this.isSubmitting = false;
      }
    },

    // ─── Formatting ───────────────────────────────────────────────────────────

    formatCurrency(value) {
      const n = Number.parseFloat(value ?? 0);
      return Number.isFinite(n) ? '₹' + n.toFixed(2) : '₹0.00';
    },

    formatDate(value) {
      if (!value) return 'N/A';
      const d = new Date(value);
      return Number.isNaN(d.getTime()) ? 'N/A' : d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
    },

    getStatusColor(status) {
      // Returns hex so blades can use :style="`color: ${getStatusColor(s)}`"
      return {
        pending:        '#f97316',
        received:       '#0ea5e9',
        qc_in_progress: '#6366f1',
        completed:      '#10b981',
        rejected:       '#ef4444',
      }[status] || '#6c757d';
    },

    getStatusLabel(status) {
      return {
        pending:        'Pending',
        received:       'Received',
        qc_in_progress: 'QC In Progress',
        completed:      'Completed',
        rejected:       'Rejected',
      }[status] || status;
    },

    getFinancialStatusColor(status) {
      return {
        pending:        '#f97316',
        partial_refund: '#0ea5e9',
        fully_refunded: '#10b981',
        credited:       '#6366f1',
      }[status] || '#6c757d';
    },
  }));
});
