@extends('layouts.app')

@section('title', 'Customer Settings')
@section('page', 'customer-settings')

@section('content')
<div class="customer-settings-management" x-data="customerSettingsTable()" x-init="init()">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 mb-lg-5 mb-xl-6">
        <div>
            <h1 class="h3 mb-0 fw-bold"><i class="bi bi-gear-wide-connected text-primary me-2"></i>Customer Settings Management</h1>
            <p class="text-muted mb-0">Manage dropdown options for lead sources, crops, irrigation, and land units</p>
        </div>
        <div class="d-flex gap-2">
            @role('Super Admin')
            <button type="button" class="btn btn-primary" @click="openCreateModal()">
                <i class="bi bi-plus-circle me-2"></i>Add <span x-text="getTabName(activeTab)"></span>
            </button>
            @endrole
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4 mb-lg-5 border-bottom flex-nowrap overflow-auto hide-scrollbar">
        <template x-for="tab in tabs" :key="tab.id">
            <li class="nav-item text-nowrap">
                <a class="nav-link cursor-pointer" 
                   :class="{ 'active fw-bold': activeTab === tab.id, 'text-muted': activeTab !== tab.id }" 
                   @click.prevent="switchTab(tab.id)">
                    <i class="bi me-2" :class="tab.icon"></i><span x-text="tab.name"></span>
                </a>
            </li>
        </template>
    </ul>

    <!-- Stats Widgets -->
    <div class="row g-4 g-lg-5 g-xl-6 mb-5 mb-lg-5 mb-xl-6">
        <div class="col-xl-4 col-lg-4">
            <div class="card stats-card">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-primary bg-opacity-10 text-primary me-3 fs-3 rounded p-2">
                            <i class="bi bi-list-check"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Total Items</p>
                            <div class="h3 mb-0 fw-bold text-body-emphasis" x-text="stats.total"></div>
                            <small class="text-primary-emphasis" x-text="tabTitle"></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-lg-4">
            <div class="card stats-card">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-success bg-opacity-10 text-success me-3 fs-3 rounded p-2">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Active</p>
                            <div class="h3 mb-0 fw-bold text-body-emphasis" x-text="stats.active"></div>
                            <small class="text-success-emphasis">Currently available</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-lg-4">
            <div class="card stats-card">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-danger bg-opacity-10 text-danger me-3 fs-3 rounded p-2">
                            <i class="bi bi-x-circle-fill"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Disabled</p>
                            <div class="h3 mb-0 fw-bold text-body-emphasis" x-text="stats.inactive"></div>
                            <small class="text-danger-emphasis">Hidden from dropdowns</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table Container -->
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="h5 card-title mb-0" x-text="tabTitle"></h2>
                </div>
                <div class="col-auto">
                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                        <div class="position-relative">
                            <input type="search" class="form-control form-control-sm" placeholder="Search items..." x-model="searchQuery" @input.debounce.300ms="filterItems()" style="width: 200px;">
                            <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted"></i>
                        </div>
                        <select x-select class="form-select form-select-sm" x-model="statusFilter" @change="filterItems()" style="width: 150px;">
                            <option value="">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Disabled</option>
                        </select>
                        <select x-select class="form-select form-select-sm" x-model.number="itemsPerPage" @change="filterItems()" style="width: 120px;">
                            <option value="10">10 / page</option>
                            <option value="15">15 / page</option>
                            <option value="20">20 / page</option>
                            <option value="25">25 / page</option>
                            <option value="50">50 / page</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <!-- Bulk Actions Bar -->
            <div class="bulk-actions-bar p-3 bg-primary bg-opacity-10 border-bottom border-primary border-opacity-25" x-show="selectedItems.length > 0" x-cloak>
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-primary"></i>
                        <span class="fw-medium text-primary">
                            <strong x-text="selectedItems.length"></strong> item(s) selected
                        </span>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @role('Super Admin')
                        <button class="btn btn-sm btn-success" @click="bulkAction('activate')" :disabled="isLoading" title="Activate selected items">
                            <i class="bi bi-check-circle me-1"></i>Activate
                        </button>
                        <button class="btn btn-sm btn-warning" @click="bulkAction('deactivate')" :disabled="isLoading" title="Deactivate selected items">
                            <i class="bi bi-x-circle me-1"></i>Deactivate
                        </button>
                        <button class="btn btn-sm btn-danger" @click="bulkAction('delete')" :disabled="isLoading" title="Delete selected items">
                            <i class="bi bi-trash me-1"></i>Delete
                        </button>
                        @endrole
                        <button class="btn btn-sm btn-outline-secondary" @click="selectedItems = []" title="Clear selection">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;" class="ps-3">
                                <input type="checkbox" class="form-check-input border-secondary" style="cursor: pointer;" @change="toggleAll($event.target.checked)" :checked="paginatedItems.length > 0 && paginatedItems.every(r => selectedItems.includes(String(r.id)))">
                            </th>
                            <th scope="col" role="button" @click="sortBy('id')" class="sortable" style="width: 80px;">
                                <i class="bi bi-hash me-1 text-secondary"></i>ID
                                <i class="bi bi-arrow-up" x-show="sortField === 'id' && sortDirection === 'asc'"></i>
                                <i class="bi bi-arrow-down" x-show="sortField === 'id' && sortDirection === 'desc'"></i>
                            </th>
                            <th scope="col" role="button" @click="sortBy('name')" class="sortable">
                                <i class="bi bi-chat-text me-1 text-secondary"></i>Name
                                <i class="bi bi-arrow-up" x-show="sortField === 'name' && sortDirection === 'asc'"></i>
                                <i class="bi bi-arrow-down" x-show="sortField === 'name' && sortDirection === 'desc'"></i>
                            </th>
                            <th scope="col" role="button" @click="sortBy('is_active')" class="sortable">
                                <i class="bi bi-info-circle me-1 text-secondary"></i>Status
                                <i class="bi bi-arrow-up" x-show="sortField === 'is_active' && sortDirection === 'asc'"></i>
                                <i class="bi bi-arrow-down" x-show="sortField === 'is_active' && sortDirection === 'desc'"></i>
                            </th>
                            <th style="width: 120px;"><i class="bi bi-lightning-charge me-1 text-secondary"></i>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Loading State -->
                        <tr x-show="isLoading" style="display: none;">
                            <td colspan="5" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status"></div>
                                <p class="mt-2 text-muted mb-0">Loading data...</p>
                            </td>
                        </tr>

                        <!-- Empty State -->
                        <tr x-show="!isLoading && paginatedItems.length === 0" style="display: none;">
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No items found matching current criteria.
                            </td>
                        </tr>

                        <!-- Data Rows -->
                        <template x-for="r in paginatedItems" :key="r.id">
                            <tr :class="{ 'table-active': selectedItems.includes(String(r.id)) }">
                                <td class="ps-3">
                                    <input type="checkbox" class="form-check-input border-secondary" style="cursor: pointer;" :value="String(r.id)" x-model="selectedItems">
                                </td>
                                <td>
                                    <span class="fw-medium text-body-emphasis" x-text="r.id"></span>
                                </td>
                                <td>
                                    <span class="fw-semibold text-body" x-text="r.name"></span>
                                </td>
                                <td>
                                    <span class="badge" 
                                          :class="{
                                              'bg-success bg-opacity-25 text-success border border-success border-opacity-50': r.is_active,
                                              'bg-danger bg-opacity-25 text-danger border border-danger border-opacity-50': !r.is_active
                                          }"
                                          x-text="r.is_active ? 'ACTIVE' : 'DISABLED'"></span>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @role('Super Admin')
                                            <li>
                                                <a class="dropdown-item" href="#" @click.prevent="openEditModal(r)">
                                                    <i class="bi bi-pencil-square me-2"></i>Edit
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="#" @click.prevent="toggleActive(r)">
                                                    <i class="bi me-2" :class="r.is_active ? 'bi-x-circle text-warning' : 'bi-check-circle text-success'"></i>
                                                    <span x-text="r.is_active ? 'Deactivate' : 'Activate'"></span>
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item text-danger" href="#" @click.prevent="deleteItem(r)">
                                                    <i class="bi bi-trash me-2"></i>Delete
                                                </a>
                                            </li>
                                            @endrole
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center p-3 border-top">
                <div class="text-muted small">
                    Showing <span x-text="totalItems === 0 ? 0 : (currentPage - 1) * itemsPerPage + 1"></span> to 
                    <span x-text="Math.min(currentPage * itemsPerPage, totalItems)"></span> of 
                    <span x-text="totalItems"></span> results
                </div>
                <nav x-show="totalPages > 1">
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item" :class="{ 'disabled': currentPage === 1 }">
                            <a class="page-link" href="#" @click.prevent="goToPage(currentPage - 1)">Previous</a>
                        </li>
                        <template x-for="(page, index) in visiblePages" :key="`page-${index}`">
                            <li class="page-item" :class="{ 'active': page === currentPage, 'disabled': page === '...' }">
                                <a class="page-link" href="#" @click.prevent="page !== '...' && goToPage(page)" x-text="page"></a>
                            </li>
                        </template>
                        <li class="page-item" :class="{ 'disabled': currentPage === totalPages }">
                            <a class="page-link" href="#" @click.prevent="goToPage(currentPage + 1)">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Add / Edit Modal -->
    <div class="modal fade" id="itemModal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <form @submit.prevent="saveItem()">
                    <div class="modal-header border-bottom-0 pb-0 pt-4 px-4">
                        <h5 class="modal-title fw-bold" x-text="editingId ? 'Edit ' + getTabName(activeTab) : 'Add New ' + getTabName(activeTab)"></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-semibold text-muted">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" x-model="form.name" placeholder="e.g. Tomato" required>
                            </div>
                            <div class="col-12">
                                <div class="form-check mt-2">
                                    <input class="form-check-input border-secondary" type="checkbox" id="formIsActive" x-model="form.is_active" style="cursor: pointer;">
                                    <label class="form-check-label fw-semibold" for="formIsActive">Is Active</label>
                                    <div class="form-text small text-muted">If inactive, this option will be hidden from dropdown menus.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 px-4 pb-4 pt-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4" :disabled="isSubmitting">
                            <span x-show="isSubmitting" class="spinner-border spinner-border-sm me-2" role="status"></span>
                            <span x-text="editingId ? 'Save Changes' : 'Create Item'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmModal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-body p-4 text-center">
                    <div class="mb-3">
                        <div class="avatar rounded-circle mx-auto d-flex align-items-center justify-content-center" 
                             :class="confirmActionType.includes('delete') ? 'bg-danger bg-opacity-10 text-danger' : 'bg-warning bg-opacity-10 text-warning'"
                             style="width: 60px; height: 60px;">
                            <i class="bi fs-2" :class="confirmActionType.includes('delete') ? 'bi-trash3-fill' : 'bi-exclamation-triangle-fill'"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold mb-2">Confirm Action</h5>
                    <p class="text-muted mb-4" x-text="confirmMessage"></p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-outline-secondary w-50" data-bs-dismiss="modal" :disabled="isConfirming">Cancel</button>
                        <button type="button" class="btn w-50 fw-semibold" 
                                :class="confirmActionType.includes('delete') ? 'btn-danger' : 'btn-primary'" 
                                @click="executeConfirmAction()" 
                                :disabled="isConfirming">
                            <span x-show="isConfirming" class="spinner-border spinner-border-sm me-2" role="status"></span>
                            <span x-show="!isConfirming">Confirm</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    async function apiFetch(url, options = {}) {
        const { headers, ...rest } = options;
        const response = await fetch(url, {
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content ?? "",
                ...(headers || {})
            },
            ...rest
        });
        const text = await response.text();
        const data = text ? JSON.parse(text) : {};
        if (!response.ok) {
            const error = data?.errors ? Object.values(data.errors).flat().join(" ") : "";
            throw new Error(error || data?.message || data?.error || "Request failed");
        }
        return data;
    }

    function showToast(message, type = "success") {
        const container = document.getElementById("toast-container");
        if (!container) return;
        const toast = document.createElement("div");
        toast.className = `toast align-items-center text-bg-${type} border-0 show mb-2`;
        toast.setAttribute("role", "alert");
        toast.innerHTML = `
        <div class="d-flex">
          <div class="toast-body">
            <i class="bi ${
                type === 'success' ? 'bi-check-circle-fill' : 
                type === 'danger' ? 'bi-x-circle-fill' : 
                type === 'warning' ? 'bi-exclamation-triangle-fill' : 
                'bi-info-circle-fill'
            } me-2"></i><span></span>
          </div>
          <button type="button" class="btn-close btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>`;
        toast.querySelector(".toast-body span").textContent = message;
        container.appendChild(toast);
        setTimeout(() => toast.remove(), 5000);
    }

    document.addEventListener("alpine:init", () => {
        Alpine.data("customerSettingsTable", () => ({
            activeTab: "crop",
            tabs: [
                { id: 'crop', name: 'Crops', icon: 'bi-flower1' },
                { id: 'irrigation', name: 'Irrigation Types', icon: 'bi-droplet-fill' },
                { id: 'lead_source', name: 'Lead Sources', icon: 'bi-megaphone-fill' },
                { id: 'land_unit', name: 'Land Units', icon: 'bi-bounding-box' }
            ],
            items: [],
            searchQuery: "",
            statusFilter: "",
            itemsPerPage: 10,
            currentPage: 1,
            sortField: "name",
            sortDirection: "asc",
            selectedItems: [],
            isLoading: false,
            isSubmitting: false,
            isConfirming: false,
            editingId: null,
            form: { name: "", is_active: true },
            modalInstance: null,
            confirmModalInstance: null,
            confirmMessage: '',
            confirmActionType: '',
            confirmPayload: null,

            init() {
                this.modalInstance = new bootstrap.Modal(document.getElementById('itemModal'));
                this.confirmModalInstance = new bootstrap.Modal(document.getElementById('confirmModal'));
                
                if (window.location.hash) {
                    const hash = window.location.hash.substring(1);
                    if (this.tabs.some(t => t.id === hash)) {
                        this.activeTab = hash;
                    }
                }
                
                this.$watch('activeTab', value => {
                    window.history.replaceState(null, null, `#${value}`);
                });

                this.fetchItems();
            },

            getTabName(id) {
                const tab = this.tabs.find(t => t.id === id);
                let name = tab ? tab.name : '';
                if (name.endsWith('ies')) return name.slice(0, -3) + 'y';
                if (name.endsWith('s')) return name.slice(0, -1);
                return name;
            },

            get tabTitle() {
                const tab = this.tabs.find(t => t.id === this.activeTab);
                return tab ? tab.name + " Directory" : "Directory";
            },

            get stats() {
                return {
                    total: this.items.length,
                    active: this.items.filter(e => e.is_active).length,
                    inactive: this.items.filter(e => !e.is_active).length
                };
            },

            get filteredItems() {
                let e = this.items;
                if (this.searchQuery) {
                    const t = this.searchQuery.toLowerCase();
                    e = e.filter(i => i.name.toLowerCase().includes(t) || String(i.id).includes(t));
                }
                if (this.statusFilter === "active") {
                    e = e.filter(i => i.is_active);
                } else if (this.statusFilter === "inactive") {
                    e = e.filter(i => !i.is_active);
                }
                e.sort((a, b) => {
                    let s = a[this.sortField], i = b[this.sortField];
                    if (typeof s === "string") s = s.toLowerCase();
                    if (typeof i === "string") i = i.toLowerCase();
                    if (s < i) return this.sortDirection === "asc" ? -1 : 1;
                    if (s > i) return this.sortDirection === "asc" ? 1 : -1;
                    return 0;
                });
                return e;
            },

            get paginatedItems() {
                const e = (this.currentPage - 1) * this.itemsPerPage;
                return this.filteredItems.slice(e, e + this.itemsPerPage);
            },

            get totalItems() {
                return this.filteredItems.length;
            },

            get totalPages() {
                return Math.ceil(this.totalItems / this.itemsPerPage) || 1;
            },

            get visiblePages() {
                if (this.totalPages <= 1) return [1];
                const e = [1];
                if (this.totalPages <= 7) {
                    for (let t = 2; t <= this.totalPages; t++) e.push(t);
                } else {
                    if (this.currentPage > 3) e.push("...");
                    const t = Math.max(2, this.currentPage - 1);
                    const s = Math.min(this.totalPages - 1, this.currentPage + 1);
                    for (let i = t; i <= s; i++) e.push(i);
                    if (this.currentPage < this.totalPages - 2) e.push("...");
                    e.push(this.totalPages);
                }
                return e;
            },

            goToPage(e) {
                if (e >= 1 && e <= this.totalPages) this.currentPage = e;
            },

            sortBy(e) {
                if (this.sortField === e) {
                    this.sortDirection = this.sortDirection === "asc" ? "desc" : "asc";
                } else {
                    this.sortField = e;
                    this.sortDirection = "asc";
                }
                this.currentPage = 1;
            },

            switchTab(e) {
                this.activeTab = e;
                this.searchQuery = "";
                this.statusFilter = "";
                this.selectedItems = [];
                this.currentPage = 1;
                this.fetchItems();
            },

            filterItems() {
                this.currentPage = 1;
                this.selectedItems = [];
            },

            toggleAll(e) {
                this.selectedItems = e ? this.paginatedItems.map(i => String(i.id)) : [];
            },

            async fetchItems() {
                this.isLoading = true;
                try {
                    const response = await apiFetch(`/api/customer-settings/${this.activeTab}`);
                    if (response.items) {
                        this.items = response.items;
                    }
                } catch (error) {
                    showToast(error.message || "Failed to load items.", "danger");
                } finally {
                    this.isLoading = false;
                }
            },

            openCreateModal() {
                this.editingId = null;
                this.form = { name: "", is_active: true };
                this.modalInstance.show();
            },

            openEditModal(e) {
                this.editingId = e.id;
                this.form = { name: e.name, is_active: e.is_active };
                this.modalInstance.show();
            },

            async saveItem() {
                this.isSubmitting = true;
                const method = this.editingId ? "PUT" : "POST";
                const url = this.editingId 
                    ? `/api/customer-settings/${this.activeTab}/${this.editingId}` 
                    : `/api/customer-settings/${this.activeTab}`;
                
                try {
                    const response = await apiFetch(url, {
                        method: method,
                        body: JSON.stringify(this.form)
                    });
                    showToast(response.message || "Item saved successfully.");
                    this.modalInstance.hide();
                    this.fetchItems();
                } catch (error) {
                    showToast(error.message || "Failed to save item.", "danger");
                } finally {
                    this.isSubmitting = false;
                }
            },

            async toggleActive(item) {
                try {
                    const response = await apiFetch(`/api/customer-settings/${this.activeTab}/${item.id}/toggle`, { method: "PATCH" });
                    item.is_active = response.is_active;
                    showToast(response.message || "Status toggled successfully.");
                } catch (error) {
                    showToast(error.message || "Failed to toggle status.", "danger");
                    item.is_active = !item.is_active;
                }
            },

            async deleteItem(item) {
                const typeName = this.getTabName(this.activeTab).toLowerCase();
                this.confirmMessage = `Are you sure you want to delete the ${typeName} "${item.name}"?`;
                this.confirmActionType = 'delete';
                this.confirmPayload = item;
                this.confirmModalInstance.show();
            },

            bulkAction(action) {
                if (this.selectedItems.length === 0) return;
                
                const actionWord = action === "delete" ? "delete" : (action === "activate" ? "activate" : "deactivate");
                const tabName = this.tabs.find(t => t.id === this.activeTab)?.name.toLowerCase() || 'items';
                
                this.confirmMessage = `Are you sure you want to ${actionWord} ${this.selectedItems.length} selected ${tabName}?`;
                this.confirmActionType = `bulk_${action}`;
                this.confirmPayload = action;
                this.confirmModalInstance.show();
            },

            async executeConfirmAction() {
                if (this.confirmActionType === 'delete') {
                    const item = this.confirmPayload;
                    try {
                        this.isConfirming = true;
                        const response = await apiFetch(`/api/customer-settings/${this.activeTab}/${item.id}`, { method: "DELETE" });
                        showToast(response.message || "Item deleted successfully.");
                        this.confirmModalInstance.hide();
                        this.fetchItems();
                    } catch (error) {
                        showToast(error.message || "Failed to delete item.", "danger");
                    } finally {
                        this.isConfirming = false;
                    }
                } else if (this.confirmActionType.startsWith('bulk_')) {
                    const action = this.confirmPayload;
                    this.isConfirming = true;
                    let successCount = 0;
                    let failCount = 0;
                    
                    try {
                        for (const id of this.selectedItems) {
                            try {
                                let url = "";
                                let method = "";
                                
                                if (action === "delete") {
                                    url = `/api/customer-settings/${this.activeTab}/${id}`;
                                    method = "DELETE";
                                } else {
                                    const item = this.items.find(e => String(e.id) === id);
                                    if (!item || (action === "activate" && item.is_active) || (action === "deactivate" && !item.is_active)) {
                                        continue;
                                    }
                                    url = `/api/customer-settings/${this.activeTab}/${id}/toggle`;
                                    method = "PATCH";
                                }
                                
                                await apiFetch(url, { method: method });
                                successCount++;
                            } catch (err) {
                                failCount++;
                            }
                        }
                        showToast(`Bulk action completed: ${successCount} successful, ${failCount} failed.`, failCount > 0 ? "warning" : "success");
                        this.selectedItems = [];
                        this.confirmModalInstance.hide();
                        this.fetchItems();
                    } finally {
                        this.isConfirming = false;
                    }
                }
            }
        }));
    });
</script>
<style>
    .hide-scrollbar::-webkit-scrollbar { display: none; }
    .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
@endpush
@endsection
