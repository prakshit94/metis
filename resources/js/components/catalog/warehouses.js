import Alpine from 'alpinejs';
import { Modal } from 'bootstrap';
import Swal from 'sweetalert2';

function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const id = 'toast-' + Date.now();
    const iconMap = {
        success: 'bi-check-circle-fill',
        danger:  'bi-x-circle-fill',
        warning: 'bi-exclamation-triangle-fill',
        info:    'bi-info-circle-fill',
        error:   'bi-x-circle-fill',
    };

    const el = document.createElement('div');
    el.id = id;
    el.className = `toast align-items-center text-bg-${type === 'error' ? 'danger' : type} border-0 show mb-2`;
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

async function confirmDelete({ title, text, confirmButtonText = 'Yes, delete it' }) {
    const result = await Swal.fire({
        title,
        text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText,
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc3545',
        reverseButtons: true,
        focusCancel: true,
        customClass: {
            popup: 'rounded-4 shadow-lg',
            confirmButton: 'btn btn-danger me-2',
            cancelButton: 'btn btn-secondary',
        },
        buttonsStyling: false,
    });

    return result.isConfirmed;
}

function emptyForm() {
    return {
        id: null,
        name: '',
        code: '',
        company_name: '',
        gstin: '',
        phone: '',
        address_line_1: '',
        address_line_2: '',
        village_id: '',
        village_name: '',
        post_office: '',
        taluka: '',
        city: '',
        state: '',
        pincode: '',
        is_default: false,
        status: 'active',
    };
}

export default () => {
    const instance = {
        items: [],
        filteredItems: [],
        selectedItems: [],
        stats: { total: 0, active: 0, inactive: 0 },

        searchQuery: '',
        statusFilter: '',
        sortField: 'id',
        sortDirection: 'desc',
        currentPage: 1,
        itemsPerPage: 10,

        isLoading: false,
        saving: false,
        isEditing: false,

        apiBase: '/api/warehouses',
        modalInstance: null,

        // Village autofill
        villageSearchQuery: '',
        villageResults: [],
        villageSearchLoading: false,
        villageSearchTimeout: null,

        form: emptyForm(),

        init() {
            this.loadData();

            const modalEl = document.getElementById('warehousesModal');
            if (modalEl) {
                this.modalInstance = Modal.getOrCreateInstance(modalEl);
                modalEl.addEventListener('hidden.bs.modal', () => {
                    this.resetForm();
                });
            }
        },

        async apiRequest(url, options = {}) {
            const { headers, ...otherOptions } = options;
            const response = await fetch(url, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    ...(headers || {})
                },
                ...otherOptions,
            });

            const text = await response.text();
            const payload = text ? JSON.parse(text) : {};

            if (!response.ok) {
                const validation = payload?.errors ? Object.values(payload.errors).flat().join(' ') : '';
                const message = validation || payload?.message || payload?.error || 'Request failed.';
                throw new Error(message);
            }
            return payload;
        },

        async loadData() {
            this.isLoading = true;
            try {
                const payload = await this.apiRequest(`${this.apiBase}?per_page=1000`);
                this.items = Array.isArray(payload.data) ? payload.data : [];
                this.filterData();
            } catch (error) {
                console.error('Failed to load data:', error);
                showToast(error.message, 'error');
            } finally {
                this.isLoading = false;
            }
        },

        calculateStats() {
            this.stats.total    = this.items.length;
            this.stats.active   = this.items.filter(i => i.status === 'active').length;
            this.stats.inactive = this.items.filter(i => i.status === 'inactive').length;
        },

        filterData() {
            this.filteredItems = this.items.filter(item => {
                const q = this.searchQuery.toLowerCase();
                const matchesSearch = !q ||
                    (item.name || '').toLowerCase().includes(q) ||
                    (item.code || '').toLowerCase().includes(q) ||
                    (item.city || '').toLowerCase().includes(q) ||
                    (item.state || '').toLowerCase().includes(q) ||
                    (item.gstin || '').toLowerCase().includes(q);

                const matchesStatus = !this.statusFilter || item.status === this.statusFilter;
                return matchesSearch && matchesStatus;
            });

            this.sortData();
            this.calculateStats();
            this.currentPage = 1;
            this.selectedItems = [];
        },

        sortData() {
            this.filteredItems.sort((a, b) => {
                let aVal = a[this.sortField] || '';
                let bVal = b[this.sortField] || '';

                if (this.sortField === 'id') {
                    aVal = parseInt(aVal) || 0;
                    bVal = parseInt(bVal) || 0;
                } else {
                    aVal = String(aVal).toLowerCase();
                    bVal = String(bVal).toLowerCase();
                }

                if (this.sortDirection === 'asc') return aVal > bVal ? 1 : -1;
                return aVal < bVal ? 1 : -1;
            });
        },

        sortBy(field) {
            if (this.sortField === field) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortField = field;
                this.sortDirection = 'asc';
            }
            this.sortData();
        },

        get paginatedItems() {
            const start = (this.currentPage - 1) * this.itemsPerPage;
            return this.filteredItems.slice(start, start + this.itemsPerPage);
        },

        get pageFrom() {
            if (this.filteredItems.length === 0) return 0;
            return (this.currentPage - 1) * this.itemsPerPage + 1;
        },

        get pageTo() {
            return Math.min(this.currentPage * this.itemsPerPage, this.filteredItems.length);
        },

        get totalPages() {
            return Math.ceil(this.filteredItems.length / this.itemsPerPage);
        },

        get visiblePages() {
            if (this.totalPages <= 1) return [1];
            const pages = [1];

            if (this.totalPages <= 7) {
                for (let i = 2; i <= this.totalPages; i++) pages.push(i);
            } else {
                if (this.currentPage <= 4) {
                    for (let i = 2; i <= 5; i++) pages.push(i);
                    pages.push('...');
                    pages.push(this.totalPages);
                } else if (this.currentPage >= this.totalPages - 3) {
                    pages.push('...');
                    for (let i = this.totalPages - 4; i <= this.totalPages; i++) pages.push(i);
                } else {
                    pages.push('...');
                    for (let i = this.currentPage - 1; i <= this.currentPage + 1; i++) pages.push(i);
                    pages.push('...');
                    pages.push(this.totalPages);
                }
            }
            return pages;
        },

        goToPage(page) {
            if (page >= 1 && page <= this.totalPages) {
                this.currentPage = page;
            }
        },

        toggleAll(checked) {
            this.selectedItems = checked ? this.paginatedItems.map(i => i.id) : [];
        },

        toggleItem(id) {
            if (this.selectedItems.includes(id)) {
                this.selectedItems = this.selectedItems.filter(i => i !== id);
            } else {
                this.selectedItems.push(id);
            }
        },

        resetForm() {
            this.isEditing = false;
            this.form = emptyForm();
            this.villageSearchQuery = '';
            this.villageResults = [];
        },

        openCreateModal() {
            this.resetForm();
            this.modalInstance?.show();
        },

        editItem(item) {
            this.isEditing = true;
            this.form = {
                id:             item.id,
                name:           item.name || '',
                code:           item.code || '',
                company_name:   item.company_name || '',
                gstin:          item.gstin || '',
                phone:          item.phone || '',
                address_line_1: item.address_line_1 || '',
                address_line_2: item.address_line_2 || '',
                village_id:     item.village_id || '',
                village_name:   item.village_name || '',
                post_office:    item.post_office || '',
                taluka:         item.taluka || '',
                city:           item.city || '',
                state:          item.state || '',
                pincode:        item.pincode || '',
                is_default:     !!item.is_default,
                status:         item.status || 'active',
            };
            this.villageSearchQuery = item.village_name || '';
            this.villageResults = [];
            this.modalInstance?.show();
        },

        async saveItem() {
            if (!this.form.name.trim()) {
                showToast('Warehouse name is required.', 'warning');
                return;
            }

            this.saving = true;
            try {
                const url    = this.isEditing ? `${this.apiBase}/${this.form.id}` : this.apiBase;
                const method = this.isEditing ? 'PUT' : 'POST';

                const payload = { ...this.form };

                await this.apiRequest(url, {
                    method,
                    body: JSON.stringify(payload),
                });

                showToast(`Warehouse ${this.isEditing ? 'updated' : 'created'} successfully.`, 'success');
                this.modalInstance?.hide();
                await this.loadData();
            } catch (error) {
                showToast(error.message, 'error');
            } finally {
                this.saving = false;
            }
        },

        async deleteItem(item) {
            const name = item.name || item.code;
            const confirmed = await confirmDelete({
                title: 'Delete Warehouse?',
                text: `Are you sure you want to delete "${name}"? This action cannot be undone.`,
            });
            if (!confirmed) return;

            try {
                await this.apiRequest(`${this.apiBase}/${item.id}`, { method: 'DELETE' });
                showToast('Warehouse deleted successfully.', 'success');
                await this.loadData();
            } catch (error) {
                showToast(error.message, 'error');
            }
        },

        async bulkAction(action) {
            if (this.selectedItems.length === 0) {
                showToast('Please select at least one warehouse.', 'warning');
                return;
            }

            try {
                if (action === 'delete') {
                    const confirmed = await confirmDelete({
                        title: 'Delete Selected?',
                        text: `Are you sure you want to delete ${this.selectedItems.length} warehouses?`,
                    });
                    if (!confirmed) return;

                    for (const id of this.selectedItems) {
                        await this.apiRequest(`${this.apiBase}/${id}`, { method: 'DELETE' });
                    }
                } else {
                    const status = action;
                    for (const id of this.selectedItems) {
                        await this.apiRequest(`${this.apiBase}/${id}`, {
                            method: 'PUT',
                            body: JSON.stringify({ status }),
                        });
                    }
                }

                this.selectedItems = [];
                showToast('Bulk action completed successfully.', 'success');
                await this.loadData();
            } catch (error) {
                showToast(error.message || 'Bulk action failed.', 'error');
            }
        },

        // ─── Village Autofill ──────────────────────────────────────────────────────

        onVillageInput() {
            clearTimeout(this.villageSearchTimeout);
            if (this.villageSearchQuery.length < 3) {
                this.villageResults = [];
                return;
            }
            this.villageSearchTimeout = setTimeout(() => this.searchVillages(), 300);
        },

        async searchVillages() {
            if (this.villageSearchQuery.trim().length < 3) {
                this.villageResults = [];
                return;
            }
            this.villageSearchLoading = true;
            try {
                const res = await this.apiRequest(`/api/villages/search?q=${encodeURIComponent(this.villageSearchQuery)}`);
                this.villageResults = res.data ?? [];
            } catch (e) {
                console.error('Village search failed:', e);
            } finally {
                this.villageSearchLoading = false;
            }
        },

        selectVillage(v) {
            this.form.village_id   = v.id;
            this.form.village_name = v.village_name;
            this.form.city         = v.taluka_name || v.district_name || '';
            this.form.state        = v.state_name || '';
            this.form.pincode      = v.pincode || '';
            this.form.post_office  = v.post_so_name || '';
            this.form.taluka       = v.taluka_name || '';
            this.villageSearchQuery = v.village_name;
            this.villageResults = [];
        },

        clearVillage() {
            this.form.village_id   = '';
            this.form.village_name = '';
            this.form.city         = '';
            this.form.state        = '';
            this.form.pincode      = '';
            this.form.post_office  = '';
            this.form.taluka       = '';
            this.villageSearchQuery = '';
            this.villageResults = [];
        },

        exportData() {
            if (this.filteredItems.length === 0) {
                showToast('No data to export.', 'warning');
                return;
            }

            const headers = ['ID', 'Name', 'Code', 'Company', 'GSTIN', 'Phone', 'Address', 'City', 'State', 'Pincode', 'Status', 'Default'];
            const csvRows = [headers.join(',')];

            this.filteredItems.forEach(item => {
                const addr = [item.address_line_1, item.address_line_2].filter(Boolean).join(', ');
                const values = [
                    item.id,
                    `"${(item.name || '').replace(/"/g, '""')}"`,
                    item.code || '',
                    `"${(item.company_name || '').replace(/"/g, '""')}"`,
                    item.gstin || '',
                    item.phone || '',
                    `"${addr.replace(/"/g, '""')}"`,
                    item.city || '',
                    item.state || '',
                    item.pincode || '',
                    item.status,
                    item.is_default ? 'Yes' : 'No',
                ];
                csvRows.push(values.join(','));
            });

            const blob = new Blob([csvRows.join('\n')], { type: 'text/csv' });
            const url  = URL.createObjectURL(blob);
            const a    = document.createElement('a');
            a.setAttribute('href', url);
            a.setAttribute('download', 'warehouses_export.csv');
            a.click();
            URL.revokeObjectURL(url);
        },
    };

    window.Alpine.store('warehousesTable', instance);
    return instance;
};
