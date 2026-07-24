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
    Alpine.data('orderReasonsTable', () => ({
        activeTab: 'reschedule',
        reasons: [],
        searchQuery: '',
        statusFilter: '',
        
        // Pagination & Sorting
        itemsPerPage: 10,
        currentPage: 1,
        sortField: 'id',
        sortDirection: 'asc',
        
        // Bulk Actions
        selectedReasons: [],
        
        isLoading: false,
        isSubmitting: false,
        
        editingId: null,
        form: {
            reason: '',
            is_active: true
        },

        init() {
            this.fetchReasons();
        },

        get tabTitle() {
            switch(this.activeTab) {
                case 'reschedule': return 'Reschedule Reasons Directory';
                case 'return': return 'Return Reasons Directory';
                case 'failure': return 'Delivery Failure Reasons Directory';
                default: return 'Directory';
            }
        },

        get stats() {
            return {
                total: this.reasons.length,
                active: this.reasons.filter(r => r.is_active).length,
                inactive: this.reasons.filter(r => !r.is_active).length
            };
        },

        get filteredReasons() {
            let result = this.reasons;
            
            if (this.searchQuery) {
                const q = this.searchQuery.toLowerCase();
                result = result.filter(r => r.reason.toLowerCase().includes(q) || String(r.id).includes(q));
            }
            
            if (this.statusFilter === 'active') {
                result = result.filter(r => r.is_active);
            } else if (this.statusFilter === 'inactive') {
                result = result.filter(r => !r.is_active);
            }
            
            return result.sort((a, b) => {
                let aVal = a[this.sortField];
                let bVal = b[this.sortField];
                
                if (this.sortField === 'created_by') {
                    aVal = a.creator ? a.creator.first_name + ' ' + (a.creator.last_name || '') : 'System';
                    bVal = b.creator ? b.creator.first_name + ' ' + (b.creator.last_name || '') : 'System';
                }
                
                if (typeof aVal === 'string') aVal = aVal.toLowerCase();
                if (typeof bVal === 'string') bVal = bVal.toLowerCase();
                
                if (aVal < bVal) return this.sortDirection === 'asc' ? -1 : 1;
                if (aVal > bVal) return this.sortDirection === 'asc' ? 1 : -1;
                return 0;
            });
        },
        
        get paginatedReasons() {
            const start = (this.currentPage - 1) * this.itemsPerPage;
            return this.filteredReasons.slice(start, start + this.itemsPerPage);
        },

        get totalItems() {
            return this.filteredReasons.length;
        },

        get totalPages() {
            return Math.ceil(this.totalItems / this.itemsPerPage) || 1;
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

        goToPage(page) {
            if (page >= 1 && page <= this.totalPages) {
                this.currentPage = page;
                this.selectedReasons = []; // Clear selection when changing page
            }
        },

        sortBy(field) {
            if (this.sortField === field) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortField = field;
                this.sortDirection = 'asc';
            }
            this.currentPage = 1;
        },

        switchTab(tab) {
            this.activeTab = tab;
            this.searchQuery = '';
            this.statusFilter = '';
            this.selectedReasons = [];
            this.currentPage = 1;
            this.fetchReasons();
        },
        
        filterReasons() {
            this.currentPage = 1;
            this.selectedReasons = [];
        },
        
        toggleAll(checked) {
            if (checked) {
                this.selectedReasons = this.paginatedReasons.map(r => String(r.id));
            } else {
                this.selectedReasons = [];
            }
        },

        async fetchReasons() {
            this.isLoading = true;
            try {
                const data = await apiFetch(`/api/order-reasons/${this.activeTab}`);
                if (data.reasons) {
                    this.reasons = data.reasons;
                }
            } catch (error) {
                showToast(error.message || 'Failed to load reasons.', 'danger');
            } finally {
                this.isLoading = false;
            }
        },

        openCreateModal() {
            this.editingId = null;
            this.form = {
                reason: '',
                is_active: true,
                updated_at: null
            };
            this.$nextTick(() => {
                getModal('reasonModal')?.show();
            });
        },

        openEditModal(reason) {
            this.editingId = reason.id;
            this.form = {
                reason: reason.reason,
                is_active: reason.is_active,
                updated_at: reason.updated_at
            };
            this.$nextTick(() => {
                getModal('reasonModal')?.show();
            });
        },

        async saveReason() {
            this.isSubmitting = true;
            const method = this.editingId ? 'PUT' : 'POST';
            const url = this.editingId 
                ? `/api/order-reasons/${this.activeTab}/${this.editingId}` 
                : `/api/order-reasons/${this.activeTab}`;

            try {
                const data = await apiFetch(url, {
                    method: method,
                    body: JSON.stringify(this.form)
                });
                showToast(data.message || 'Reason saved successfully.');
                getModal('reasonModal')?.hide();
                this.fetchReasons();
            } catch (error) {
                showToast(error.message || 'Failed to save reason.', 'danger');
            } finally {
                this.isSubmitting = false;
            }
        },

        async toggleActive(reason) {
            try {
                const data = await apiFetch(`/api/order-reasons/${this.activeTab}/${reason.id}/toggle`, {
                    method: 'PATCH'
                });
                reason.is_active = data.is_active;
                showToast(data.message || 'Status toggled successfully.');
            } catch (error) {
                showToast(error.message || 'Failed to toggle status.', 'danger');
                reason.is_active = !reason.is_active; // revert
            }
        },

        async deleteReason(reason) {
            if (!confirm(`Are you sure you want to delete "${reason.reason}"?`)) {
                return;
            }

            try {
                const data = await apiFetch(`/api/order-reasons/${this.activeTab}/${reason.id}`, {
                    method: 'DELETE'
                });
                showToast(data.message || 'Reason deleted successfully.');
                this.fetchReasons();
            } catch (error) {
                showToast(error.message || 'Failed to delete reason.', 'danger');
            }
        },
        
        async bulkAction(action) {
            if (this.selectedReasons.length === 0) return;
            
            const actionText = action === 'delete' ? 'delete' : (action === 'activate' ? 'activate' : 'deactivate');
            if (!confirm(`Are you sure you want to ${actionText} ${this.selectedReasons.length} selected reasons?`)) {
                return;
            }
            
            this.isLoading = true;
            let successCount = 0;
            let errorCount = 0;
            
            try {
                for (const id of this.selectedReasons) {
                    try {
                        let url = '';
                        let method = '';
                        if (action === 'delete') {
                            url = `/api/order-reasons/${this.activeTab}/${id}`;
                            method = 'DELETE';
                        } else {
                            const reason = this.reasons.find(r => String(r.id) === id);
                            if (reason && ((action === 'activate' && !reason.is_active) || (action === 'deactivate' && reason.is_active))) {
                                url = `/api/order-reasons/${this.activeTab}/${id}/toggle`;
                                method = 'PATCH';
                            } else {
                                continue;
                            }
                        }
                        
                        await apiFetch(url, { method: method });
                        successCount++;
                    } catch (e) {
                        errorCount++;
                    }
                }
                
                showToast(`Bulk action completed: ${successCount} successful, ${errorCount} failed.`, errorCount > 0 ? 'warning' : 'success');
                this.selectedReasons = [];
                this.fetchReasons();
            } finally {
                this.isLoading = false;
            }
        }
    }));
});
