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
    stats: { total: 0, draft: 0, pending: 0, received: 0 },
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
    form: { id: null, from_warehouse_id: '', to_warehouse_id: '', items: [{ product_id: '', quantity: 1 }] },
    warehouseStocks: {},
    
    // Selection state
    selectedItems: [],

    async init() {
        await this.loadOptions();
        await this.loadData();
        const modalEl = document.getElementById('transferModal');
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
            const data = await this.apiRequest('/api/inventory/transfers/options');
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
            const data = await this.apiRequest(`/api/inventory/transfers?${params}`);
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
      if (checked) {
        this.items.forEach(item => {
          if (!this.selectedItems.includes(String(item.id))) {
            this.selectedItems.push(String(item.id));
          }
        });
      } else {
        const currentIds = this.items.map(item => String(item.id));
        this.selectedItems = this.selectedItems.filter(id => !currentIds.includes(id));
      }
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
        const fromWarehouseId = this.form.from_warehouse_id;
        if (!fromWarehouseId) {
            this.warehouseStocks = {};
            return;
        }
        try {
            const data = await this.apiRequest(`/api/inventory/stocks?warehouse_id=${fromWarehouseId}&per_page=1000`);
            const stocks = {};
            if (data.data) {
                data.data.forEach(stock => {
                    stocks[stock.product_id] = Math.max(0, parseFloat(stock.quantity) - parseFloat(stock.reserved_qty));
                });
            }
            this.warehouseStocks = stocks;
        } catch (e) {
            console.error('Failed to fetch warehouse stocks:', e);
            this.warehouseStocks = {};
        }
    },

    getProductStock(productId) {
        if (!productId) return 0;
        return this.warehouseStocks[productId] || 0;
    },

    getProductRemainingStock(item) {
        if (!item.product_id) return 0;
        const currentStock = this.getProductStock(item.product_id);
        const qtyToTransfer = parseFloat(item.quantity) || 0;
        return currentStock - qtyToTransfer;
    },

    formatQty(value) {
        const num = parseFloat(value);
        if (isNaN(num)) return '0';
        return num % 1 === 0 ? num.toString() : num.toFixed(2);
    },

    resetForm() {
        this.isEditing = false;
        this.form = { id: null, from_warehouse_id: '', to_warehouse_id: '', items: [{ product_id: '', quantity: 1 }] };
        this.warehouseStocks = {};
    },

    openCreateModal() { this.resetForm(); this.modalInstance?.show(); },

    async editItem(item) {
        this.isEditing = true;
        this.form = {
            id: item.id,
            from_warehouse_id: item.from_warehouse_id,
            to_warehouse_id: item.to_warehouse_id,
            items: (item.items || []).map(i => ({ product_id: i.product_id, quantity: i.quantity })),
        };
        if (this.form.items.length === 0) this.form.items = [{ product_id: '', quantity: 1 }];
        await this.fetchWarehouseStocks();
        this.modalInstance?.show();
    },

    addItem() { this.form.items.push({ product_id: '', quantity: 1 }); },
    removeItem(index) { if (this.form.items.length > 1) this.form.items.splice(index, 1); },

    async saveItem() {
        if (!this.form.from_warehouse_id || !this.form.to_warehouse_id) {
            showToast('Please select source and destination warehouses.', 'error');
            return;
        }
        if (this.form.from_warehouse_id == this.form.to_warehouse_id) {
            showToast('Source and Destination warehouses must be different.', 'error');
            return;
        }
        const invalid = this.form.items.some(i => !i.product_id || !i.quantity || parseFloat(i.quantity) <= 0);
        if (invalid) {
            showToast('Please select a product and valid quantity for all items.', 'error');
            return;
        }

        this.saving = true;
        try {
            const payload = { from_warehouse_id: this.form.from_warehouse_id, to_warehouse_id: this.form.to_warehouse_id, items: this.form.items };
            if (this.form.id) {
                await this.apiRequest(`/api/inventory/transfers/${this.form.id}`, { method: 'PUT', body: JSON.stringify(payload) });
                showToast('Transfer updated successfully.', 'success');
            } else {
                await this.apiRequest('/api/inventory/transfers', { method: 'POST', body: JSON.stringify(payload) });
                showToast('Transfer created successfully.', 'success');
            }
            this.modalInstance?.hide();
            await this.loadData();
        } catch (e) {
            showToast(e.message, 'error');
        } finally {
            this.saving = false;
        }
    },

    async sendTransfer(item) {
        const ok = await confirmAction({ title: 'Mark as Sent?', text: `Mark transfer ${item.transfer_no} as dispatched?`, confirmText: 'Yes, mark sent', icon: 'info' });
        if (!ok) return;
        try {
            await this.apiRequest(`/api/inventory/transfers/${item.id}/send`, { method: 'POST' });
            showToast('Transfer marked as sent.', 'success');
            await this.loadData();
        } catch (e) { showToast(e.message, 'error'); }
    },

    async receiveTransfer(item) {
        const ok = await confirmAction({ title: 'Receive Transfer?', text: `This will move stock from ${item.from_warehouse?.name} to ${item.to_warehouse?.name}.`, confirmText: 'Receive & Update Stock', icon: 'info' });
        if (!ok) return;
        try {
            await this.apiRequest(`/api/inventory/transfers/${item.id}/receive`, { method: 'POST' });
            showToast('Transfer received and stock updated.', 'success');
            await this.loadData();
        } catch (e) { showToast(e.message, 'error'); }
    },

    async cancelTransfer(item) {
        const ok = await confirmAction({ title: 'Cancel Transfer?', text: `Cancel transfer ${item.transfer_no}?`, confirmText: 'Yes, cancel it', icon: 'warning' });
        if (!ok) return;
        try {
            await this.apiRequest(`/api/inventory/transfers/${item.id}/cancel`, { method: 'POST' });
            showToast('Transfer cancelled.', 'warning');
            await this.loadData();
        } catch (e) { showToast(e.message, 'error'); }
    },

    // Bulk actions
    async bulkAction(action) {
        if (this.selectedItems.length === 0) {
            showToast('No transfers selected.', 'warning');
            return;
        }

        const actionTitles = {
            send: 'Dispatch Selected?',
            receive: 'Receive Selected?',
            cancel: 'Cancel Selected?',
            delete: 'Delete Selected?'
        };

        const actionTexts = {
            send: 'This will dispatch all selected draft transfers.',
            receive: 'This will mark all selected sent transfers as received and update stock.',
            cancel: 'This will cancel all selected draft or sent transfers.',
            delete: 'This will permanently delete all selected draft transfers.'
        };

        const confirmButtonTexts = {
            send: 'Yes, dispatch',
            receive: 'Yes, receive & update',
            cancel: 'Yes, cancel them',
            delete: 'Yes, delete them'
        };

        const ok = await confirmAction({
            title: actionTitles[action] || 'Are you sure?',
            text: actionTexts[action] || 'Perform bulk action on selected items?',
            confirmText: confirmButtonTexts[action] || 'Confirm',
            icon: action === 'delete' || action === 'cancel' ? 'warning' : 'info'
        });

        if (!ok) return;

        this.isLoading = true;
        try {
            const response = await this.apiRequest('/api/inventory/transfers/bulk-action', {
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
