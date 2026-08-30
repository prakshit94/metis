@extends('layouts.app')

@section('title', '📋 Order Reasons Management')
@section('page', 'order.reasons')

@section('content')
<div class="order-reasons-management" x-data="orderReasonsTable()" x-init="init()">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 mb-lg-5 mb-xl-6">
        <div>
            <h1 class="h3 mb-0 fw-bold"><i class="bi bi-list-task text-primary me-2"></i>Order Reasons Management</h1>
            <p class="text-muted mb-0">Manage dropdown options for reschedule, return, and delivery failure</p>
        </div>
        <div class="d-flex gap-2">
            @can('orderreason-create')
            <button type="button" class="btn btn-primary" @click="openCreateModal()">
                <i class="bi bi-plus-circle me-2"></i>Add Reason
            </button>
            @endcan
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4 mb-lg-5 border-bottom">
        <li class="nav-item">
            <a class="nav-link cursor-pointer" :class="{ 'active fw-bold': activeTab === 'reschedule', 'text-muted': activeTab !== 'reschedule' }" @click.prevent="switchTab('reschedule')">
                <i class="bi bi-calendar-event me-2"></i>Reschedule Reasons
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link cursor-pointer" :class="{ 'active fw-bold': activeTab === 'return', 'text-muted': activeTab !== 'return' }" @click.prevent="switchTab('return')">
                <i class="bi bi-arrow-return-left me-2"></i>Return Reasons
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link cursor-pointer" :class="{ 'active fw-bold': activeTab === 'failure', 'text-muted': activeTab !== 'failure' }" @click.prevent="switchTab('failure')">
                <i class="bi bi-exclamation-triangle me-2"></i>Delivery Failure Reasons
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link cursor-pointer" :class="{ 'active fw-bold': activeTab === 'cancel', 'text-muted': activeTab !== 'cancel' }" @click.prevent="switchTab('cancel')">
                <i class="bi bi-x-circle me-2"></i>Cancellation Reasons
            </a>
        </li>
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
                            <p class="h6 mb-0 text-muted">Total Reasons</p>
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
                            <input type="search" class="form-control form-control-sm" placeholder="Search reasons..." x-model="searchQuery" @input.debounce.300ms="filterReasons()" style="width: 200px;">
                            <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted"></i>
                        </div>
                        <select x-select class="form-select form-select-sm" x-model="statusFilter" @change="filterReasons()" style="width: 150px;">
                            <option value="">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Disabled</option>
                        </select>
                        <select x-select class="form-select form-select-sm" x-model.number="itemsPerPage" @change="filterReasons()" style="width: 120px;">
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
            <div class="bulk-actions-bar p-3 bg-primary bg-opacity-10 border-bottom border-primary border-opacity-25" x-show="selectedReasons.length > 0" x-cloak>
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-primary"></i>
                        <span class="fw-medium text-primary">
                            <strong x-text="selectedReasons.length"></strong> reason(s) selected
                        </span>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @can('orderreason-edit')
                        <button class="btn btn-sm btn-success" @click="bulkAction('activate')" :disabled="isLoading" title="Activate selected reasons">
                            <i class="bi bi-check-circle me-1"></i>Activate
                        </button>
                        <button class="btn btn-sm btn-warning" @click="bulkAction('deactivate')" :disabled="isLoading" title="Deactivate selected reasons">
                            <i class="bi bi-x-circle me-1"></i>Deactivate
                        </button>
                        @endcan
                        @can('orderreason-delete')
                        <button class="btn btn-sm btn-danger" @click="bulkAction('delete')" :disabled="isLoading" title="Delete selected reasons">
                            <i class="bi bi-trash me-1"></i>Delete
                        </button>
                        @endcan
                        <button class="btn btn-sm btn-outline-secondary" @click="selectedReasons = []" title="Clear selection">
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
                                <input type="checkbox" class="form-check-input border-secondary" style="cursor: pointer;" @change="toggleAll($event.target.checked)" :checked="paginatedReasons.length > 0 && paginatedReasons.every(r => selectedReasons.includes(String(r.id)))">
                            </th>
                            <th scope="col" role="button" @click="sortBy('id')" class="sortable" style="width: 80px;">
                                <i class="bi bi-hash me-1 text-secondary"></i>ID
                                <i class="bi bi-arrow-up" x-show="sortField === 'id' && sortDirection === 'asc'"></i>
                                <i class="bi bi-arrow-down" x-show="sortField === 'id' && sortDirection === 'desc'"></i>
                            </th>
                            <th scope="col" role="button" @click="sortBy('reason')" class="sortable">
                                <i class="bi bi-chat-text me-1 text-secondary"></i>Reason Text
                                <i class="bi bi-arrow-up" x-show="sortField === 'reason' && sortDirection === 'asc'"></i>
                                <i class="bi bi-arrow-down" x-show="sortField === 'reason' && sortDirection === 'desc'"></i>
                            </th>
                            <th scope="col" role="button" @click="sortBy('is_active')" class="sortable">
                                <i class="bi bi-info-circle me-1 text-secondary"></i>Status
                                <i class="bi bi-arrow-up" x-show="sortField === 'is_active' && sortDirection === 'asc'"></i>
                                <i class="bi bi-arrow-down" x-show="sortField === 'is_active' && sortDirection === 'desc'"></i>
                            </th>
                            <th scope="col" role="button" @click="sortBy('created_by')" class="sortable">
                                <i class="bi bi-person me-1 text-secondary"></i>Created By
                                <i class="bi bi-arrow-up" x-show="sortField === 'created_by' && sortDirection === 'asc'"></i>
                                <i class="bi bi-arrow-down" x-show="sortField === 'created_by' && sortDirection === 'desc'"></i>
                            </th>
                            <th scope="col" role="button" @click="sortBy('created_at')" class="sortable">
                                <i class="bi bi-calendar-event me-1 text-secondary"></i>Date
                                <i class="bi bi-arrow-up" x-show="sortField === 'created_at' && sortDirection === 'asc'"></i>
                                <i class="bi bi-arrow-down" x-show="sortField === 'created_at' && sortDirection === 'desc'"></i>
                            </th>
                            <th style="width: 120px;"><i class="bi bi-lightning-charge me-1 text-secondary"></i>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Loading State -->
                        <tr x-show="isLoading" style="display: none;">
                            <td colspan="7" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status"></div>
                                <p class="mt-2 text-muted mb-0">Loading data...</p>
                            </td>
                        </tr>

                        <!-- Empty State -->
                        <tr x-show="!isLoading && paginatedReasons.length === 0" style="display: none;">
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No reasons found matching current criteria.
                            </td>
                        </tr>

                        <!-- Data Rows -->
                        <template x-for="r in paginatedReasons" :key="r.id">
                            <tr :class="{ 'table-active': selectedReasons.includes(String(r.id)) }">
                                <td class="ps-3">
                                    <input type="checkbox" class="form-check-input border-secondary" style="cursor: pointer;" :value="String(r.id)" x-model="selectedReasons">
                                </td>
                                <td>
                                    <span class="fw-medium text-body-emphasis" x-text="r.id"></span>
                                </td>
                                <td>
                                    <span class="fw-semibold text-body" x-text="r.reason"></span>
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
                                    <div class="d-flex align-items-center">
                                        <div class="bg-secondary bg-opacity-25 rounded-circle me-2 d-flex align-items-center justify-content-center" style="width:32px;height:32px;">
                                            <i class="bi bi-person text-body-secondary"></i>
                                        </div>
                                        <span class="small fw-medium text-body-emphasis" x-text="r.creator ? r.creator.first_name + ' ' + (r.creator.last_name || '') : 'System'"></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="small">
                                        <div class="text-body-secondary" x-text="r.created_at ? new Date(r.created_at).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }) : '—'"></div>
                                        <div class="text-muted" x-show="r.updated_at && r.updated_at !== r.created_at" style="font-size: 0.75rem;">
                                            <i class="bi bi-pencil-square me-1"></i><span x-text="new Date(r.updated_at).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' })"></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @can('orderreason-edit')
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
                                            @endcan
                                            @can('orderreason-delete')
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item text-danger" href="#" @click.prevent="deleteReason(r)">
                                                    <i class="bi bi-trash me-2"></i>Delete
                                                </a>
                                            </li>
                                            @endcan
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
    <div class="modal fade" id="reasonModal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <form @submit.prevent="saveReason()">
                    <div class="modal-header border-bottom-0 pb-0 pt-4 px-4">
                        <h5 class="modal-title fw-bold" x-text="editingId ? 'Edit Reason' : 'Add New Reason'"></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-semibold text-muted">Reason Text <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" x-model="form.reason" placeholder="e.g. Customer Not Reachable" required>
                            </div>
                            <div class="col-12">
                                <div class="form-check mt-2">
                                    <input class="form-check-input border-secondary" type="checkbox" id="formIsActive" x-model="form.is_active" style="cursor: pointer;">
                                    <label class="form-check-label fw-semibold" for="formIsActive">Is Active</label>
                                    <div class="form-text small text-muted">If inactive, this option will be hidden from dropdown menus.</div>
                                </div>
                            </div>
                            <div x-show="editingId && form.updated_at" class="col-12 mt-4 pt-3 border-top small text-muted">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-clock-history me-2"></i>
                                    <span>Last updated: <strong x-text="form.updated_at ? new Date(form.updated_at).toLocaleString() : '—'"></strong></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 px-4 pb-4 pt-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4" :disabled="isSubmitting">
                            <span x-show="isSubmitting" class="spinner-border spinner-border-sm me-2" role="status"></span>
                            <span x-text="editingId ? 'Save Changes' : 'Create Reason'"></span>
                        </button>
                    </div>
                </form>
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
        Alpine.data("orderReasonsTable", () => ({
            activeTab: "reschedule",
            reasons: [],
            searchQuery: "",
            statusFilter: "",
            itemsPerPage: 10,
            currentPage: 1,
            sortField: "created_at",
            sortDirection: "desc",
            selectedReasons: [],
            isLoading: false,
            isSubmitting: false,
            editingId: null,
            form: { reason: "", is_active: true },
            modalInstance: null,

            init() {
                this.modalInstance = new bootstrap.Modal(document.getElementById('reasonModal'));
                this.fetchReasons();
            },

            get tabTitle() {
                const map = {
                    reschedule: "Reschedule Reasons",
                    return: "Return Reasons",
                    failure: "Delivery Failure Reasons",
                    cancel: "Cancellation Reasons"
                };
                return map[this.activeTab] || "Reasons";
            },

            get stats() {
                return {
                    total: this.reasons.length,
                    active: this.reasons.filter(r => r.is_active).length,
                    inactive: this.reasons.filter(r => !r.is_active).length
                };
            },

            get filteredReasons() {
                let r = this.reasons;
                if (this.searchQuery) {
                    const q = this.searchQuery.toLowerCase();
                    r = r.filter(i => i.reason.toLowerCase().includes(q) || String(i.id).includes(q));
                }
                if (this.statusFilter === "active") {
                    r = r.filter(i => i.is_active);
                } else if (this.statusFilter === "inactive") {
                    r = r.filter(i => !i.is_active);
                }
                r.sort((a, b) => {
                    let s = a[this.sortField], i = b[this.sortField];
                    if (typeof s === "string") s = s.toLowerCase();
                    if (typeof i === "string") i = i.toLowerCase();
                    if (s < i) return this.sortDirection === "asc" ? -1 : 1;
                    if (s > i) return this.sortDirection === "asc" ? 1 : -1;
                    return 0;
                });
                return r;
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
                const p = [1];
                if (this.totalPages <= 7) {
                    for (let i = 2; i <= this.totalPages; i++) p.push(i);
                } else {
                    if (this.currentPage > 3) p.push("...");
                    const start = Math.max(2, this.currentPage - 1);
                    const end = Math.min(this.totalPages - 1, this.currentPage + 1);
                    for (let i = start; i <= end; i++) p.push(i);
                    if (this.currentPage < this.totalPages - 2) p.push("...");
                    p.push(this.totalPages);
                }
                return p;
            },

            goToPage(p) {
                if (p >= 1 && p <= this.totalPages) this.currentPage = p;
            },

            sortBy(field) {
                if (this.sortField === field) {
                    this.sortDirection = this.sortDirection === "asc" ? "desc" : "asc";
                } else {
                    this.sortField = field;
                    this.sortDirection = "asc";
                }
                this.currentPage = 1;
            },

            switchTab(tab) {
                this.activeTab = tab;
                this.searchQuery = "";
                this.statusFilter = "";
                this.selectedReasons = [];
                this.currentPage = 1;
                this.fetchReasons();
            },

            filterReasons() {
                this.currentPage = 1;
                this.selectedReasons = [];
            },

            toggleAll(checked) {
                this.selectedReasons = checked ? this.paginatedReasons.map(r => String(r.id)) : [];
            },

            async fetchReasons() {
                this.isLoading = true;
                try {
                    const res = await apiFetch(`/api/order-reasons/${this.activeTab}`);
                    if (res.reasons) this.reasons = res.reasons;
                } catch (e) {
                    showToast(e.message || "Failed to load reasons.", "danger");
                } finally {
                    this.isLoading = false;
                }
            },

            openCreateModal() {
                this.editingId = null;
                this.form = { reason: "", is_active: true };
                this.modalInstance.show();
            },

            openEditModal(r) {
                this.editingId = r.id;
                this.form = { reason: r.reason, is_active: r.is_active, updated_at: r.updated_at };
                this.modalInstance.show();
            },

            async saveReason() {
                this.isSubmitting = true;
                const method = this.editingId ? "PUT" : "POST";
                const url = this.editingId 
                    ? `/api/order-reasons/${this.activeTab}/${this.editingId}` 
                    : `/api/order-reasons/${this.activeTab}`;
                
                try {
                    const res = await apiFetch(url, {
                        method: method,
                        body: JSON.stringify(this.form)
                    });
                    showToast(res.message || "Reason saved.");
                    this.modalInstance.hide();
                    this.fetchReasons();
                } catch (e) {
                    showToast(e.message || "Failed to save reason.", "danger");
                } finally {
                    this.isSubmitting = false;
                }
            },

            async toggleActive(r) {
                try {
                    const res = await apiFetch(`/api/order-reasons/${this.activeTab}/${r.id}/toggle`, { method: "PATCH" });
                    r.is_active = res.is_active;
                    showToast(res.message || "Status toggled.");
                } catch (e) {
                    showToast(e.message || "Failed to toggle status.", "danger");
                    r.is_active = !r.is_active;
                }
            },

            async deleteReason(r) {
                if (!confirm(`Delete reason "${r.reason}"?`)) return;
                try {
                    const res = await apiFetch(`/api/order-reasons/${this.activeTab}/${r.id}`, { method: "DELETE" });
                    showToast(res.message || "Reason deleted.");
                    this.fetchReasons();
                } catch (e) {
                    showToast(e.message || "Failed to delete.", "danger");
                }
            },

            async bulkAction(action) {
                if (this.selectedReasons.length === 0) return;
                if (!confirm(`Are you sure you want to ${action} ${this.selectedReasons.length} reasons?`)) return;
                
                let success = 0, fail = 0;
                for (const id of this.selectedReasons) {
                    try {
                        if (action === "delete") {
                            await apiFetch(`/api/order-reasons/${this.activeTab}/${id}`, { method: "DELETE" });
                        } else {
                            const r = this.reasons.find(e => String(e.id) === id);
                            if (!r || (action === "activate" && r.is_active) || (action === "deactivate" && !r.is_active)) continue;
                            await apiFetch(`/api/order-reasons/${this.activeTab}/${id}/toggle`, { method: "PATCH" });
                        }
                        success++;
                    } catch (e) {
                        fail++;
                    }
                }
                showToast(`Bulk action complete. Success: ${success}, Fail: ${fail}.`, fail > 0 ? "warning" : "success");
                this.selectedReasons = [];
                this.fetchReasons();
            }
        }));
    });
</script>
@endpush
@endsection
