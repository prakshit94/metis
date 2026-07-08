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

export default () => ({
    items: [],
    stats: { total_products: 0, total_warehouses: 0, low_stock_count: 0, out_of_stock: 0 },
    warehouses: [],
    productOptions: [],
    isLoading: false,
    saving: false,
    isEditing: false,
    searchQuery: '',
    warehouseFilter: '',
    stockLevelFilter: '',
    sortField: 'id',
    sortDirection: 'desc',
    currentPage: 1,
    itemsPerPage: 25,
    totalItems: 0,
    totalPages: 1,
    modalInstance: null,
    adjustForm: { productId: null, warehouseId: null, productName: '', warehouseName: '', currentQty: 0, newQty: 0 },
    
    // Selection state
    selectedItems: [],

    async init() {
        await this.loadOptions();
        await this.loadData();
        const modalEl = document.getElementById('adjustStockModal');
        if (modalEl) this.modalInstance = Modal.getOrCreateInstance(modalEl);
    },

    async apiRequest(url, options = {}) {
        const { headers, ...otherOptions } = options;
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const reqHeaders = { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', ...(headers || {}) };
        if (options.body && !(options.body instanceof FormData)) reqHeaders['Content-Type'] = 'application/json';
        const response = await fetch(url, { headers: reqHeaders, ...otherOptions });
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
            this.productOptions = data.products || [];
        } catch (e) { console.error(e); }
    },

    async loadData() {
        this.isLoading = true;
        try {
            const params = new URLSearchParams({ per_page: this.itemsPerPage, page: this.currentPage, sort_by: this.sortField, sort_dir: this.sortDirection });
            if (this.searchQuery) params.set('search', this.searchQuery);
            if (this.warehouseFilter) params.set('warehouse_id', this.warehouseFilter);
            if (this.stockLevelFilter) params.set('stock_level', this.stockLevelFilter);
            const data = await this.apiRequest(`/api/inventory/stocks?${params}`);
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

    // CSV Export
    async fetchAllFiltered() {
        const params = new URLSearchParams({ per_page: 1000, sort_by: this.sortField, sort_dir: this.sortDirection });
        if (this.searchQuery) params.set('search', this.searchQuery);
        if (this.warehouseFilter) params.set('warehouse_id', this.warehouseFilter);
        if (this.stockLevelFilter) params.set('stock_level', this.stockLevelFilter);
        const data = await this.apiRequest(`/api/inventory/stocks?${params}`);
        return data.data || [];
    },

    async exportStock(selectedOnly = false) {
        try {
            let exportItems = [];
            if (selectedOnly) {
                exportItems = this.items.filter(item => this.selectedItems.includes(item.id));
                if (exportItems.length === 0) {
                    showToast('No items selected for export.', 'warning');
                    return;
                }
            } else {
                exportItems = await this.fetchAllFiltered();
            }

            const headers = ['Product Name', 'SKU', 'Warehouse', 'Quantity', 'Alert Level', 'Status'];
            const rows = exportItems.map(item => {
                const qty = parseFloat(item.quantity || 0);
                const alert = parseFloat(item.product?.alert_quantity || 0);
                let status = 'In Stock';
                if (qty <= 0) status = 'Out of Stock';
                else if (qty <= alert) status = 'Low Stock';

                return [
                    item.product?.name || '',
                    item.product?.sku || '',
                    item.warehouse?.name || '',
                    qty,
                    alert,
                    status
                ];
            });

            const csvEscape = val => `"${String(val ?? '').replace(/"/g, '""')}"`;
            const csvContent = [headers, ...rows].map(r => r.map(csvEscape).join(',')).join('\n');

            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `stock-levels-export.csv`;
            document.body.appendChild(link);
            link.click();
            URL.revokeObjectURL(link.href);
            document.body.removeChild(link);

            showToast(`Exported ${exportItems.length} record(s) successfully.`);
        } catch (e) {
            showToast('Export failed: ' + e.message, 'danger');
        }
    },

    openAdjustModal(item) {
        if (!item) {
            this.isEditing = false;
            this.adjustForm = {
                productId: '',
                warehouseId: '',
                productName: '',
                warehouseName: '',
                currentQty: 0,
                newQty: 0,
            };
        } else {
            this.isEditing = true;
            this.adjustForm = {
                productId: item.product_id,
                warehouseId: item.warehouse_id,
                productName: (item.product?.name || '') + (item.product?.sku ? ` (${item.product.sku})` : ''),
                warehouseName: item.warehouse?.name || '',
                currentQty: parseFloat(item.quantity || 0),
                newQty: parseFloat(item.quantity || 0),
            };
        }
        this.modalInstance?.show();
    },

    async fetchCurrentStock() {
        const prodId = this.adjustForm.productId;
        const whId = this.adjustForm.warehouseId;
        if (!prodId || !whId) {
            this.adjustForm.currentQty = 0;
            return;
        }
        try {
            const data = await this.apiRequest(`/api/inventory/stocks/show?product_id=${prodId}&warehouse_id=${whId}`);
            this.adjustForm.currentQty = parseFloat(data.data?.quantity || 0);
        } catch (e) {
            this.adjustForm.currentQty = 0;
        }
    },

    async saveAdjustment() {
        if (!this.adjustForm.productId || !this.adjustForm.warehouseId) {
            showToast('Please select both product and warehouse.', 'error');
            return;
        }
        if (this.adjustForm.newQty === undefined || this.adjustForm.newQty === null || parseFloat(this.adjustForm.newQty) < 0) {
            showToast('Please enter a valid non-negative new quantity.', 'error');
            return;
        }
        this.saving = true;
        try {
            await this.apiRequest('/api/inventory/stocks/set', {
                method: 'POST',
                body: JSON.stringify({
                    product_id: this.adjustForm.productId,
                    warehouse_id: this.adjustForm.warehouseId,
                    quantity: this.adjustForm.newQty,
                }),
            });
            showToast('Stock level updated successfully.', 'success');
            this.modalInstance?.hide();
            await this.loadData();
        } catch (e) {
            showToast(e.message, 'error');
        } finally {
            this.saving = false;
        }
    },
});
