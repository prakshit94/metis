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
    });

    return result.isConfirmed;
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

        apiBase: '/api/attributes',
        modalInstance: null,
        valuesModalInstance: null,

        form: {
            id: null,
            name: '',
            type: 'select',
            status: 'active',
            is_filterable: true
        },

        managingValues: null,
        newValueForm: {
            value: '',
            color_code: '#000000',
            status: 'active'
        },
        valueSaving: false,

        init() {
            this.loadData();

            const modalEl = document.getElementById('attributesModal');
            if (modalEl) {
                this.modalInstance = Modal.getOrCreateInstance(modalEl);
                modalEl.addEventListener('hidden.bs.modal', () => {
                    this.resetForm();
                });
            }

            const valuesModalEl = document.getElementById('valuesModal');
            if (valuesModalEl) {
                this.valuesModalInstance = Modal.getOrCreateInstance(valuesModalEl);
                valuesModalEl.addEventListener('hidden.bs.modal', () => {
                    this.resetValueForm();
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
            this.stats.total = this.items.length;
            this.stats.active = this.items.filter(i => i.status === 'active').length;
            this.stats.inactive = this.items.filter(i => i.status === 'inactive').length;
        },

        filterData() {
            this.filteredItems = this.items.filter(item => {
                const searchTerms = this.searchQuery.toLowerCase();
                const matchesSearch = !this.searchQuery ||
                    (item.name || '').toLowerCase().includes(searchTerms) ||
                    (item.type || '').toLowerCase().includes(searchTerms);

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
                    aVal = parseFloat(aVal) || 0;
                    bVal = parseFloat(bVal) || 0;
                } else if (this.sortField === 'is_filterable') {
                    aVal = aVal ? 1 : 0;
                    bVal = bVal ? 1 : 0;
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
            const pages = [];
            pages.push(1);

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
      if (checked) {
        this.paginatedItems.forEach(item => {
          if (!this.selectedItems.includes(String(item.id))) {
            this.selectedItems.push(String(item.id));
          }
        });
      } else {
        const currentIds = this.paginatedItems.map(item => String(item.id));
        this.selectedItems = this.selectedItems.filter(id => !currentIds.includes(id));
      }
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
            this.form = {
                id: null,
                name: '',
                type: 'select',
                status: 'active',
                is_filterable: true
            };
        },

        openCreateModal() {
            this.resetForm();
            this.modalInstance?.show();
        },

        editItem(item) {
            this.isEditing = true;
            this.form = {
                id: item.id,
                name: item.name || '',
                type: item.type || 'select',
                status: item.status || 'active',
                is_filterable: item.is_filterable !== undefined ? !!item.is_filterable : true
            };
            this.modalInstance?.show();
        },

        async saveItem() {
            this.saving = true;
            try {
                const url = this.isEditing ? `${this.apiBase}/${this.form.id}` : this.apiBase;
                const method = this.isEditing ? 'PUT' : 'POST';

                await this.apiRequest(url, {
                    method,
                    body: JSON.stringify(this.form)
                });

                showToast(`Successfully ${this.isEditing ? 'updated' : 'created'} attribute.`, 'success');
                this.modalInstance?.hide();
                await this.loadData();
            } catch (error) {
                showToast(error.message, 'error');
            } finally {
                this.saving = false;
            }
        },

        async deleteItem(item) {
            const name = item.name;
            const confirmed = await confirmDelete({
                title: 'Delete Attribute?',
                text: `Are you sure you want to delete "${name}"?`
            });
            if (!confirmed) return;

            try {
                await this.apiRequest(`${this.apiBase}/${item.id}`, { method: 'DELETE' });
                showToast('Attribute deleted successfully.', 'success');
                await this.loadData();
            } catch (error) {
                showToast(error.message, 'error');
            }
        },

        async bulkAction(action) {
            if (this.selectedItems.length === 0) return;

            try {
                if (action === 'delete') {
                    const confirmed = await confirmDelete({
                        title: 'Delete Selected?',
                        text: `Are you sure you want to delete ${this.selectedItems.length} attributes?`
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
                            body: JSON.stringify({ status })
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

        exportData() {
            if (this.filteredItems.length === 0) {
                showToast('No data to export.', 'warning');
                return;
            }

            const headers = ['ID', 'Name', 'Type', 'Filterable', 'Status', 'Created At'];
            const csvRows = [headers.join(',')];

            this.filteredItems.forEach(item => {
                const values = [
                    item.id,
                    `"${(item.name || '').replace(/"/g, '""')}"`,
                    `"${(item.type || '').replace(/"/g, '""')}"`,
                    item.is_filterable ? 'Yes' : 'No',
                    item.status,
                    item.created_at || ''
                ];
                csvRows.push(values.join(','));
            });

            const blob = new Blob([csvRows.join('\n')], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.setAttribute('href', url);
            a.setAttribute('download', 'attributes_export.csv');
            a.click();
            URL.revokeObjectURL(url);
        },

        resetValueForm() {
            this.newValueForm = {
                value: '',
                color_code: '#000000',
                status: 'active'
            };
            this.valueSaving = false;
        },

        openValueModal(item) {
            this.managingValues = item;
            this.resetValueForm();
            this.valuesModalInstance?.show();
        },

        async addValue() {
            if (!this.newValueForm.value.trim()) {
                showToast('Please enter a value.', 'warning');
                return;
            }
            this.valueSaving = true;
            try {
                const response = await this.apiRequest(`${this.apiBase}/${this.managingValues.id}/values`, {
                    method: 'POST',
                    body: JSON.stringify(this.newValueForm)
                });
                
                if (!this.managingValues.values) {
                    this.managingValues.values = [];
                }
                this.managingValues.values.push(response.data);
                showToast('Value added successfully.', 'success');
                this.resetValueForm();
                await this.loadData();
                
                // Find the updated item in our items and refresh managingValues
                const updatedItem = this.items.find(i => i.id === this.managingValues.id);
                if (updatedItem) {
                    this.managingValues = updatedItem;
                }
            } catch (error) {
                showToast(error.message, 'error');
            } finally {
                this.valueSaving = false;
            }
        },

        async deleteValue(val) {
            const confirmed = await confirmDelete({
                title: 'Remove Value?',
                text: `Are you sure you want to remove value "${val.value}"?`
            });
            if (!confirmed) return;

            try {
                await this.apiRequest(`${this.apiBase}/values/${val.id}`, { method: 'DELETE' });
                showToast('Value deleted successfully.', 'success');
                await this.loadData();
                
                // Refresh managingValues from loaded items
                const updatedItem = this.items.find(i => i.id === this.managingValues.id);
                if (updatedItem) {
                    this.managingValues = updatedItem;
                }
            } catch (error) {
                showToast(error.message, 'error');
            }
        }
    };

    window.Alpine.store('attributesTable', instance);
    return instance;
};
