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
        paginator: {
            current_page: 1,
            last_page: 1,
            total: 0,
            from: 0,
            to: 0,
            per_page: 10
        },
        stats: { total: 0, active: 0, inactive: 0 },

        searchQuery: '',
        statusFilter: '',
        sortField: 'id',
        sortDirection: 'desc',
        currentPage: 1,

        isLoading: false,
        saving: false,
        isEditing: false,

        apiBase: '/api/shipping/services',
        modalInstance: null,

        form: {
            id: null,
            code: '',
            name: '',
            description: '',
            is_active: true
        },

        init() {
            this.loadData();

            const modalEl = document.getElementById('servicesModal');
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
                // Fetch stats from all items
                const allPayload = await this.apiRequest(`${this.apiBase}?per_page=100`);
                const allItems = allPayload.data || [];
                this.stats.total = allItems.length;
                this.stats.active = allItems.filter(i => i.is_active).length;
                this.stats.inactive = allItems.filter(i => !i.is_active).length;

                // Fetch paginated active list
                const query = `page=${this.currentPage}&search=${encodeURIComponent(this.searchQuery)}&sort_by=${this.sortField}&sort_dir=${this.sortDirection}`;
                const payload = await this.apiRequest(`${this.apiBase}?${query}`);
                this.items = payload.data || [];
                this.paginator = {
                    current_page: payload.current_page,
                    last_page: payload.last_page,
                    total: payload.total,
                    from: payload.from || 0,
                    to: payload.to || 0,
                    per_page: payload.per_page
                };
            } catch (error) {
                console.error('Failed to load services:', error);
                showToast(error.message, 'error');
            } finally {
                this.isLoading = false;
            }
        },

        filterData() {
            this.currentPage = 1;
            this.loadData();
        },

        sortBy(field) {
            if (this.sortField === field) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortField = field;
                this.sortDirection = 'asc';
            }
            this.loadData();
        },

        goToPage(page) {
            if (page >= 1 && page <= this.paginator.last_page) {
                this.currentPage = page;
                this.loadData();
            }
        },

        get visiblePages() {
            const total = this.paginator.last_page;
            if (total <= 1) return [1];
            const pages = [];
            pages.push(1);

            if (total <= 7) {
                for (let i = 2; i <= total; i++) pages.push(i);
            } else {
                if (this.currentPage <= 4) {
                    for (let i = 2; i <= 5; i++) pages.push(i);
                    pages.push('...');
                    pages.push(total);
                } else if (this.currentPage >= total - 3) {
                    pages.push('...');
                    for (let i = total - 4; i <= total; i++) pages.push(i);
                } else {
                    pages.push('...');
                    for (let i = this.currentPage - 1; i <= this.currentPage + 1; i++) pages.push(i);
                    pages.push('...');
                    pages.push(total);
                }
            }
            return pages;
        },

        resetForm() {
            this.isEditing = false;
            this.form = {
                id: null,
                code: '',
                name: '',
                description: '',
                is_active: true
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
                code: item.code,
                name: item.name,
                description: item.description || '',
                is_active: !!item.is_active
            };
            this.modalInstance?.show();
        },

        async saveItem() {
            this.saving = true;
            try {
                const url = this.isEditing ? `${this.apiBase}/${this.form.id}` : this.apiBase;
                const method = this.isEditing ? 'PATCH' : 'POST';

                await this.apiRequest(url, {
                    method,
                    body: JSON.stringify(this.form)
                });

                showToast(`Successfully ${this.isEditing ? 'updated' : 'created'} shipping service.`, 'success');
                this.modalInstance?.hide();
                await this.loadData();
            } catch (error) {
                showToast(error.message, 'error');
            } finally {
                this.saving = false;
            }
        },

        async toggleActive(item) {
            try {
                await this.apiRequest(`${this.apiBase}/${item.id}/toggle`, { method: 'POST' });
                showToast(`Status updated for "${item.name}".`, 'success');
                await this.loadData();
            } catch (error) {
                showToast(error.message, 'error');
            }
        },

        async deleteItem(item) {
            const confirmed = await confirmDelete({
                title: 'Delete Shipping Service?',
                text: `Are you sure you want to delete "${item.name}"? This action cannot be undone.`
            });
            if (!confirmed) return;

            try {
                await this.apiRequest(`${this.apiBase}/${item.id}`, { method: 'DELETE' });
                showToast('Shipping service deleted successfully.', 'success');
                await this.loadData();
            } catch (error) {
                showToast(error.message, 'error');
            }
        }
    };

    window.Alpine.store('shippingServices', instance);
    return instance;
};
