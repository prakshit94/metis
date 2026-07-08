import Alpine from 'alpinejs';
import { Modal } from 'bootstrap';
import Swal from 'sweetalert2';

function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;
    const id = 'toast-' + Date.now();
    const iconMap = {
        success: 'bi-check-circle-fill',
        danger: 'bi-x-circle-fill',
        warning: 'bi-exclamation-triangle-fill',
        error: 'bi-x-circle-fill'
    };
    const el = document.createElement('div');
    el.id = id;
    el.className = `toast align-items-center text-bg-${type === 'error' || type === 'danger' ? 'danger' : type} border-0 show mb-2`;
    el.setAttribute('role', 'alert');
    el.innerHTML = `<div class="d-flex"><div class="toast-body"><i class="bi ${iconMap[type] ?? 'bi-info-circle-fill'} me-2"></i><span></span></div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
    el.querySelector('.toast-body span').textContent = message;
    container.appendChild(el);
    setTimeout(() => el.remove(), 4000);
}

async function confirmAction({ title, text, confirmText = 'Confirm', icon = 'warning' }) {
    const result = await Swal.fire({
        title,
        text,
        icon,
        showCancelButton: true,
        confirmButtonText: confirmText,
        cancelButtonText: 'Cancel',
        confirmButtonColor: icon === 'warning' || icon === 'danger' ? '#dc3545' : '#0d6efd',
        reverseButtons: true,
        focusCancel: true
    });
    return result.isConfirmed;
}

export default () => ({
    items: [],
    stats: { total: 0, pending: 0, approved: 0, rejected: 0 },
    warehouses: [],
    products: [],
    isLoading: false,
    saving: false,
    isEditing: false,
    searchQuery: '',
    statusFilter: '',
    currentPage: 1,
    itemsPerPage: 25,
    totalItems: 0,
    totalPages: 1,
    modalInstance: null,
    form: { id: null, warehouse_id: '', reason: '', items: [{ product_id: '', current_qty: 0, new_qty: 0, adjustment_type: 'Set', adjustment_value: 0 }] },
    warehouseStocks: {},
    
    // Selection state
    selectedItems: [],

    async init() {
        await this.loadOptions();
        await this.loadData();
        const modalEl = document.getElementById('adjustmentModal');
        if (modalEl) {
            this.modalInstance = Modal.getOrCreateInstance(modalEl);
            modalEl.addEventListener('hidden.bs.modal', () => this.resetForm());
        }
    },

    async apiRequest(url, options = {}) {
        const { headers, ...rest } = options;
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const reqHeaders = { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', ...(headers || {}) };
        if (options.body && !(options.body instanceof FormData)) reqHeaders['Content-Type'] = 'application/json';
        const response = await fetch(url, { headers: reqHeaders, ...rest });
        const text = await response.text();
        const payload = text ? JSON.parse(text) : {};
        if (!response.ok) {
            const msg = payload?.errors ? Object.values(payload.errors).flat().join(' ') : payload?.message || 'Request failed.';
            throw new Error(msg);
        }
        return payload;
    },

    async loadOptions() {
        try {
            const data = await this.apiRequest('/api/inventory/adjustments/options');
            this.warehouses = data.warehouses || [];
            this.products = data.products || [];
        } catch (e) { console.error(e); }
    },

    async loadData() {
        this.isLoading = true;
        try {
            const params = new URLSearchParams({ per_page: this.itemsPerPage, page: this.currentPage });
            if (this.searchQuery) params.set('search', this.searchQuery);
            if (this.statusFilter) params.set('status', this.statusFilter);
            const data = await this.apiRequest(`/api/inventory/adjustments?${params}`);
            this.items = data.data || [];
            this.stats = data.stats || this.stats;
            this.totalItems = data.meta?.total || 0;
            this.totalPages = data.meta?.last_page || 1;
            
            // Clear selection on page load
            this.selectedItems = [];
        } catch (e) {
            showToast(e.message, 'error');
        } finally {
            this.isLoading = false;
        }
    },

    // Selection helper methods
    toggleAll(checked) {
        this.selectedItems = checked ? this.items.map(i => i.id) : [];
    },

    toggleItem(itemId) {
        if (this.selectedItems.includes(itemId)) {
            this.selectedItems = this.selectedItems.filter(id => id !== itemId);
        } else {
            this.selectedItems = [...this.selectedItems, itemId];
        }
    },

    get paginatedItems() { return this.items; },
    get pageFrom() { return this.totalItems === 0 ? 0 : (this.currentPage - 1) * this.itemsPerPage + 1; },
    get pageTo() { return Math.min(this.currentPage * this.itemsPerPage, this.totalItems); },
    
    // Standardized visiblePages getter
    get visiblePages() {
        const delta = 2;
        const range = [];
        for (let i = Math.max(2, this.currentPage - delta);
             i <= Math.min(this.totalPages - 1, this.currentPage + delta); i++) {
            range.push(i);
        }
        const result = [];
        if (this.currentPage - delta > 2) result.push(1, '...');
        else result.push(1);
        result.push(...range);
        if (this.currentPage + delta < this.totalPages - 1) result.push('...', this.totalPages);
        else if (this.totalPages > 1) result.push(this.totalPages);
        return result.filter((v, i, a) => a.indexOf(v) === i && (typeof v === 'string' || v <= this.totalPages));
    },

    goToPage(page) {
        if (page >= 1 && page <= this.totalPages) {
            this.currentPage = page;
            this.loadData();
        }
    },

    async fetchWarehouseStocks() {
        const warehouseId = this.form.warehouse_id;
        if (!warehouseId) {
            this.warehouseStocks = {};
            return;
        }
        try {
            const data = await this.apiRequest(`/api/inventory/stocks?warehouse_id=${warehouseId}&per_page=1000`);
            const stocks = {};
            if (data.data) {
                data.data.forEach(stock => {
                    stocks[stock.product_id] = parseFloat(stock.quantity);
                });
            }
            this.warehouseStocks = stocks;
            // Also update current quantities for already selected products in the form
            this.form.items.forEach(item => {
                if (item.product_id) {
                    item.current_qty = this.getProductStock(item.product_id);
                    this.updateNewQty(item);
                }
            });
        } catch (e) {
            console.error('Failed to fetch warehouse stocks:', e);
            this.warehouseStocks = {};
        }
    },

    getProductStock(productId) {
        if (!productId) return 0;
        return this.warehouseStocks[productId] || 0;
    },

    updateProductStock(item) {
        item.current_qty = this.getProductStock(item.product_id);
        this.updateNewQty(item);
    },

    updateNewQty(item) {
        const val = parseFloat(item.adjustment_value) || 0;
        const current = parseFloat(item.current_qty) || 0;
        if (item.adjustment_type === 'Add') {
            item.new_qty = current + val;
        } else if (item.adjustment_type === 'Deduct') {
            item.new_qty = Math.max(0, current - val);
        } else {
            item.new_qty = val;
        }
    },

    formatDifference(item) {
        const diff = (parseFloat(item.new_qty) || 0) - (parseFloat(item.current_qty) || 0);
        if (diff > 0) return '+' + this.formatQty(diff);
        return this.formatQty(diff);
    },

    formatQty(value) {
        const num = parseFloat(value);
        if (isNaN(num)) return '0';
        return num % 1 === 0 ? num.toString() : num.toFixed(2);
    },

    resetForm() {
        this.isEditing = false;
        this.form = { id: null, warehouse_id: '', reason: '', items: [{ product_id: '', current_qty: 0, new_qty: 0, adjustment_type: 'Set', adjustment_value: 0 }] };
        this.warehouseStocks = {};
    },

    openCreateModal() { this.resetForm(); this.modalInstance?.show(); },

    async editItem(item) {
        this.isEditing = true;
        this.form = {
            id: item.id,
            warehouse_id: item.warehouse_id,
            reason: item.reason || '',
            items: (item.items || []).map(i => {
                const diff = parseFloat(i.new_qty) - parseFloat(i.current_qty);
                let type = 'Set';
                let val = parseFloat(i.new_qty);
                if (diff > 0) {
                    type = 'Add';
                    val = diff;
                } else if (diff < 0) {
                    type = 'Deduct';
                    val = Math.abs(diff);
                }
                return {
                    product_id: i.product_id,
                    current_qty: i.current_qty,
                    new_qty: i.new_qty,
                    adjustment_type: type,
                    adjustment_value: val
                };
            }),
        };
        if (this.form.items.length === 0) this.form.items = [{ product_id: '', current_qty: 0, new_qty: 0, adjustment_type: 'Set', adjustment_value: 0 }];
        await this.fetchWarehouseStocks();
        this.modalInstance?.show();
    },

    addItem() { this.form.items.push({ product_id: '', current_qty: 0, new_qty: 0, adjustment_type: 'Set', adjustment_value: 0 }); },
    removeItem(index) { if (this.form.items.length > 1) this.form.items.splice(index, 1); },

    async saveItem() {
        if (!this.form.warehouse_id) {
            showToast('Please select a warehouse location.', 'error');
            return;
        }
        if (!this.form.reason) {
            showToast('Please provide a reason or reference.', 'error');
            return;
        }
        const invalid = this.form.items.some(i => !i.product_id || i.new_qty === undefined || i.new_qty === null || parseFloat(i.new_qty) < 0);
        if (invalid) {
            showToast('Please select a product and valid non-negative new quantity for all items.', 'error');
            return;
        }

        this.saving = true;
        try {
            const payload = { warehouse_id: this.form.warehouse_id, reason: this.form.reason, items: this.form.items };
            if (this.form.id) {
                await this.apiRequest(`/api/inventory/adjustments/${this.form.id}`, { method: 'PUT', body: JSON.stringify(payload) });
                showToast('Adjustment updated successfully.', 'success');
            } else {
                await this.apiRequest('/api/inventory/adjustments', { method: 'POST', body: JSON.stringify(payload) });
                showToast('Adjustment submitted successfully.', 'success');
            }
            this.modalInstance?.hide();
            await this.loadData();
        } catch (e) {
            showToast(e.message, 'error');
        } finally {
            this.saving = false;
        }
    },

    async approveItem(item) {
        const ok = await confirmAction({ title: 'Approve Adjustment?', text: `This will update actual stock levels for ${item.items_count} product(s).`, confirmText: 'Yes, approve', icon: 'info' });
        if (!ok) return;
        try {
            await this.apiRequest(`/api/inventory/adjustments/${item.id}/approve`, { method: 'POST' });
            showToast('Adjustment approved and stock updated.', 'success');
            await this.loadData();
        } catch (e) { showToast(e.message, 'error'); }
    },

    async rejectItem(item) {
        const ok = await confirmAction({ title: 'Reject Adjustment?', text: `Reject adjustment ${item.reference_no}? This cannot be undone.`, confirmText: 'Yes, reject', icon: 'warning' });
        if (!ok) return;
        try {
            await this.apiRequest(`/api/inventory/adjustments/${item.id}/reject`, { method: 'POST' });
            showToast('Adjustment rejected.', 'warning');
            await this.loadData();
        } catch (e) { showToast(e.message, 'error'); }
    },

    // Bulk actions
    async bulkAction(action) {
        if (this.selectedItems.length === 0) {
            showToast('No adjustments selected.', 'warning');
            return;
        }

        const actionTitles = {
            approve: 'Approve Selected?',
            reject: 'Reject Selected?',
            delete: 'Delete Selected?'
        };

        const actionTexts = {
            approve: 'This will approve all selected pending adjustments and update actual stock levels.',
            reject: 'This will reject all selected pending adjustments.',
            delete: 'This will permanently delete all selected pending adjustments.'
        };

        const confirmButtonTexts = {
            approve: 'Yes, approve',
            reject: 'Yes, reject',
            delete: 'Yes, delete them'
        };

        const ok = await confirmAction({
            title: actionTitles[action] || 'Are you sure?',
            text: actionTexts[action] || 'Perform bulk action on selected items?',
            confirmText: confirmButtonTexts[action] || 'Confirm',
            icon: action === 'delete' || action === 'reject' ? 'warning' : 'info'
        });

        if (!ok) return;

        this.isLoading = true;
        try {
            const response = await this.apiRequest('/api/inventory/adjustments/bulk-action', {
                method: 'POST',
                body: JSON.stringify({ action, ids: this.selectedItems })
            });
            showToast(response.message || 'Bulk action completed successfully.', 'success');
            this.selectedItems = [];
            await this.loadData();
        } catch (e) {
            showToast(e.message, 'error');
            await this.loadData();
        } finally {
            this.isLoading = false;
        }
    }
});
