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
        imagePreview: null,
        clearImageFlag: false,

        apiBase: '/api/categories',
        modalInstance: null,

        form: {
            id: null,
            name: '',
            parent_id: '',
            status: 'active',
            is_active: true,
            image: null
        },

        init() {
            this.loadData();

            // Setup modal
            const modalEl = document.getElementById('categoriesModal');
            if (modalEl) {
                this.modalInstance = Modal.getOrCreateInstance(modalEl);
                modalEl.addEventListener('hidden.bs.modal', () => {
                    this.resetForm();
                });
            }
        },

        async apiRequest(url, options = {}) {
            const { headers, ...otherOptions } = options;
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            
            const reqHeaders = {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                ...(headers || {})
            };

            if (options.body && !(options.body instanceof FormData)) {
                reqHeaders['Content-Type'] = 'application/json';
            }

            const response = await fetch(url, {
                headers: reqHeaders,
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
                    (item.parent && (item.parent.name || '')).toLowerCase().includes(searchTerms);

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
            this.imagePreview = null;
            this.clearImageFlag = false;
            this.form = {
                id: null,
                name: '',
                parent_id: '',
                status: 'active',
                is_active: true,
                image: null
            };
            const fileInput = document.getElementById('categoryImageInput');
            if (fileInput) fileInput.value = '';
        },

        openCreateModal() {
            this.resetForm();
            this.modalInstance?.show();
        },

        editItem(item) {
            this.isEditing = true;
            this.imagePreview = null;
            this.clearImageFlag = false;
            this.form = {
                id: item.id,
                name: item.name || '',
                parent_id: item.parent_id || '',
                status: item.status || 'active',
                is_active: item.is_active !== undefined ? !!item.is_active : true,
                image: item.image || null
            };
            const fileInput = document.getElementById('categoryImageInput');
            if (fileInput) fileInput.value = '';
            this.modalInstance?.show();
        },

        onFileChange(e) {
            const file = e.target.files[0];
            if (file) {
                this.imagePreview = URL.createObjectURL(file);
                this.clearImageFlag = false;
            }
        },

        clearImage() {
            this.imagePreview = null;
            this.form.image = null;
            this.clearImageFlag = true;
            const fileInput = document.getElementById('categoryImageInput');
            if (fileInput) fileInput.value = '';
        },

        get parentCategories() {
            return this.items.filter(item => !this.form.id || item.id !== this.form.id);
        },

        async saveItem() {
            this.saving = true;
            try {
                const formData = new FormData();
                if (this.form.id) {
                    formData.append('_method', 'PUT');
                }
                formData.append('name', this.form.name);
                formData.append('parent_id', this.form.parent_id ? String(this.form.parent_id) : '');
                formData.append('status', this.form.status);
                formData.append('is_active', this.form.is_active ? '1' : '0');

                if (this.clearImageFlag) {
                    formData.append('clear_image', '1');
                }

                const fileInput = document.getElementById('categoryImageInput');
                if (fileInput && fileInput.files[0]) {
                    formData.append('image', fileInput.files[0]);
                }

                const url = this.form.id ? `${this.apiBase}/${this.form.id}` : this.apiBase;

                await this.apiRequest(url, {
                    method: 'POST',
                    body: formData
                });

                showToast(`Successfully ${this.isEditing ? 'updated' : 'created'} category.`, 'success');
                this.modalInstance?.hide();
                await this.loadData();
            } catch (error) {
                showToast(error.message, 'error');
            } finally {
                this.saving = false;
            }
        },

        async deleteItem(item) {
            const confirmed = await confirmDelete({
                title: 'Delete Category?',
                text: `Are you sure you want to delete "${item.name}"?`
            });
            if (!confirmed) return;

            try {
                await this.apiRequest(`${this.apiBase}/${item.id}`, { method: 'DELETE' });
                showToast('Category deleted successfully.', 'success');
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
                        text: `Are you sure you want to delete ${this.selectedItems.length} categories?`
                    });
                    if (!confirmed) return;

                    for (const id of this.selectedItems) {
                        await this.apiRequest(`${this.apiBase}/${id}`, { method: 'DELETE' });
                    }
                } else {
                    const status = action;
                    for (const id of this.selectedItems) {
                        const formData = new FormData();
                        formData.append('_method', 'PUT');
                        formData.append('status', status);

                        await this.apiRequest(`${this.apiBase}/${id}`, {
                            method: 'POST',
                            body: formData
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

            const headers = ['ID', 'Name', 'Slug', 'Parent ID', 'Status', 'Created At'];
            const csvRows = [headers.join(',')];

            this.filteredItems.forEach(item => {
                const values = [
                    item.id,
                    `"${(item.name || '').replace(/"/g, '""')}"`,
                    `"${(item.slug || '').replace(/"/g, '""')}"`,
                    item.parent_id || '',
                    item.status,
                    item.created_at || ''
                ];
                csvRows.push(values.join(','));
            });

            const blob = new Blob([csvRows.join('\n')], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.setAttribute('href', url);
            a.setAttribute('download', 'categories_export.csv');
            a.click();
            URL.revokeObjectURL(url);
        }
    };

    window.Alpine.store('categoriesTable', instance);
    return instance;
};
