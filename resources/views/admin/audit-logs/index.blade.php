@extends('layouts.app')

@section('title', 'System Audit Logs')
@section('page', 'admin-audit-logs')

@section('content')
<div class="audit-logs-management" x-data="auditLogsTable" x-init="init()" x-cloak>
    <!-- Page Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4 pb-3 border-bottom">
        <div>
            <h1 class="h3 fw-bold mb-0">System Audit Logs</h1>
            <p class="text-muted mb-0">Monitor all data changes across the application</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-danger rounded-pill px-3" @click="confirmClearAll()">
                <i class="bi bi-trash me-2"></i>Clear All Logs
            </button>
        </div>
    </div>

    <!-- Stats Widgets -->
    <div class="row g-4 g-lg-5 g-xl-6 mb-5 mb-lg-5 mb-xl-6">
        <div class="col-xl-3 col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 stats-card h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-primary bg-opacity-10 text-primary me-3" style="width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                            <i class="bi bi-journal-medical"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Total Logs</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.total">0</span></div>
                            <small class="text-muted">Recorded globally</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 stats-card h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-success bg-opacity-10 text-success me-3" style="width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                            <i class="bi bi-plus-circle-fill"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Total Created</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.created">0</span></div>
                            <small class="text-success-emphasis">New records</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 stats-card h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-warning bg-opacity-10 text-warning me-3" style="width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                            <i class="bi bi-pencil-fill"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Total Updates</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.updated">0</span></div>
                            <small class="text-warning-emphasis">Modifications</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 stats-card h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-danger bg-opacity-10 text-danger me-3" style="width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                            <i class="bi bi-trash-fill"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Total Deletions</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.deleted">0</span></div>
                            <small class="text-danger-emphasis">Removed records</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table Container -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="h5 card-title mb-0">Transaction Log Directory</h2>
                </div>
                <div class="col-auto">
                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                        <!-- Search -->
                        <div class="position-relative">
                            <input type="search" 
                                   class="form-control form-control-sm" 
                                   placeholder="Search user, model, event..."
                                   x-model="searchQuery"
                                   @input.debounce.300ms="fetchData()"
                                   style="width: 200px; padding-right: 30px;">
                            <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted"></i>
                        </div>
                        
                        <!-- Event Filter -->
                        <select x-select class="form-select form-select-sm" 
                                x-model="eventFilter" 
                                @change="fetchData()"
                                style="width: 150px;">
                            <option value="">All Events</option>
                            <option value="created">Created</option>
                            <option value="updated">Updated</option>
                            <option value="deleted">Deleted</option>
                            <option value="restored">Restored</option>
                        </select>

                        <!-- Items Per Page -->
                        <select x-select class="form-select form-select-sm"
                                x-model.number="itemsPerPage"
                                @change="fetchData()"
                                style="width: 120px;">
                            <option value="10">10 / page</option>
                            <option value="15">15 / page</option>
                            <option value="20">20 / page</option>
                            <option value="25">25 / page</option>
                            <option value="50">50 / page</option>
                        </select>
                        
                        <button class="btn btn-sm btn-outline-secondary" type="button" @click="resetFilters()">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">

            <!-- Bulk Actions Banner -->
            <div class="bulk-actions-bar p-3 bg-primary bg-opacity-10 border-bottom border-primary border-opacity-25" 
                 x-show="selectedItems.length > 0" style="display: none;">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle-fill text-primary me-2"></i>
                        <span class="fw-medium text-primary">
                            <span x-text="selectedItems.length"></span> log<span x-show="selectedItems.length !== 1">s</span> selected
                        </span>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-danger" @click="bulkDelete()">
                            <i class="bi bi-trash me-1"></i>Delete Selected
                        </button>
                        <button class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center px-2" @click="selectedItems = []" title="Clear selection">
                            <i class="bi bi-x-lg" style="margin-left: 7px"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="table-responsive mt-2">
                <table class="table table-hover align-middle mb-0 text-nowrap">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;" class="border-0 ps-4 rounded-start">
                                <input type="checkbox" 
                                       class="user-select-checkbox form-check-input" 
                                       :checked="items.length > 0 && selectedItems.length === items.length"
                                       @change="toggleAll($event.target.checked)">
                            </th>
                            <th class="border-0">Date & Time</th>
                            <th class="border-0">Event</th>
                            <th class="border-0">Details</th>
                            <th class="border-0">User</th>
                            <th class="border-0">IP Address</th>
                            <th style="width: 80px;" class="border-0 pe-4 rounded-end"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Loading State -->
                        <tr x-show="isLoading" style="display: none;">
                            <td colspan="7" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status"></div>
                                <p class="mt-2 text-muted mb-0">Loading logs...</p>
                            </td>
                        </tr>

                        <!-- Empty State -->
                        <tr x-show="!isLoading && items.length === 0" style="display: none;">
                            <td colspan="7" class="text-center py-5">
                                <i class="bi bi-journal-x text-muted display-4"></i>
                                <p class="mt-2 fw-semibold mb-1">No logs found</p>
                                <p class="text-muted small mb-0">Try adjusting your filters or search query.</p>
                            </td>
                        </tr>

                        <!-- Data Rows -->
                        <template x-for="item in items" :key="item.id">
                            <tr class="user-row" :class="{'table-active': selectedItems.includes(item.id)}">
                                <td class="ps-4">
                                    <input type="checkbox" 
                                           class="user-select-checkbox form-check-input" 
                                           :value="item.id" 
                                           :checked="selectedItems.includes(item.id)"
                                           @change="toggleItem(item.id)">
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar bg-secondary bg-opacity-10 text-secondary rounded-circle me-3 d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">
                                            <i class="bi bi-clock"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold text-body" x-text="formatDate(item.created_at)"></div>
                                            <div class="small text-muted font-monospace" x-text="formatTime(item.created_at)"></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge"
                                          :class="{
                                            'bg-success bg-opacity-10 text-success': item.event === 'created',
                                            'bg-warning bg-opacity-10 text-warning': item.event === 'updated',
                                            'bg-danger bg-opacity-10 text-danger': item.event === 'deleted',
                                            'bg-info bg-opacity-10 text-info': item.event === 'restored'
                                          }"
                                          x-text="item.event.toUpperCase()">
                                    </span>
                                </td>
                                <td>
                                    <div class="text-wrap" style="max-width: 400px; line-height: 1.5;" x-html="item.formatted_description"></div>
                                    <div class="text-muted small font-monospace mt-1" x-text="'DB ID: ' + item.auditable_id"></div>
                                </td>
                                <td>
                                    <div class="fw-medium" x-text="item.user ? item.user.name : 'System / Guest'"></div>
                                    <div class="text-muted small" x-text="item.user ? item.user.email : ''"></div>
                                </td>
                                <td>
                                    <span class="font-monospace text-muted small" x-text="item.ip_address || 'N/A'"></span>
                                </td>
                                <td class="pe-4">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                                type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="#" @click.prevent="viewItem(item)">
                                                    <i class="bi bi-eye me-2"></i>View Details
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center p-3 border-top" x-show="items.length > 0" style="display: none;">
                <div class="text-muted small">
                    Showing <span x-text="pageFrom"></span> to <span x-text="pageTo"></span> of <span x-text="totalItems"></span> results
                </div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item" :class="{ 'disabled': currentPage === 1 }">
                            <a class="page-link" href="#" @click.prevent="goToPage(currentPage - 1)">Previous</a>
                        </li>
                        <template x-for="(page, index) in visiblePages" :key="`${page}-${index}`">
                            <li class="page-item" :class="{ 'active': page === currentPage }">
                                <a class="page-link" href="#" @click.prevent="goToPage(page)" x-text="page"></a>
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


    <!-- View Details Modal -->
    <div class="modal fade" id="viewAuditModal" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content shadow-lg border-0 rounded-4">
                <div class="modal-header border-bottom-0 pb-0 pt-4 px-4 px-lg-5">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-journal-code me-2 text-primary"></i>Audit Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 pt-3 px-lg-5">
                    <template x-if="viewData">
                        <div class="row g-4">
                            <!-- Context info -->
                            <div class="col-12">
                                <div class="card border-0 shadow-sm bg-body-tertiary rounded-4">
                                    <div class="card-body p-4 d-flex flex-wrap gap-4">
                                        <div>
                                            <div class="text-muted small fw-bold text-uppercase mb-1" style="font-size: 0.7rem; letter-spacing: 0.05em;">Event</div>
                                            <span class="badge"
                                                :class="{
                                                    'bg-success': viewData.event === 'created',
                                                    'bg-warning text-body-emphasis': viewData.event === 'updated',
                                                    'bg-danger': viewData.event === 'deleted',
                                                    'bg-info': viewData.event === 'restored'
                                                }"
                                                x-text="viewData.event.toUpperCase()">
                                            </span>
                                        </div>
                                        <div>
                                            <div class="text-muted small fw-bold text-uppercase mb-1" style="font-size: 0.7rem; letter-spacing: 0.05em;">Model</div>
                                            <div class="fw-medium text-primary"><span x-text="viewData.model_name"></span> <span class="text-muted">#<span x-text="viewData.auditable_id"></span></span></div>
                                        </div>
                                        <div>
                                            <div class="text-muted small fw-bold text-uppercase mb-1" style="font-size: 0.7rem; letter-spacing: 0.05em;">Date</div>
                                            <div class="fw-medium" x-text="formatDate(viewData.created_at) + ' ' + formatTime(viewData.created_at)"></div>
                                        </div>
                                        <div>
                                            <div class="text-muted small fw-bold text-uppercase mb-1" style="font-size: 0.7rem; letter-spacing: 0.05em;">User</div>
                                            <div class="fw-medium" x-text="viewData.user ? viewData.user.name : 'System / Guest'"></div>
                                        </div>
                                        <div>
                                            <div class="text-muted small fw-bold text-uppercase mb-1" style="font-size: 0.7rem; letter-spacing: 0.05em;">IP Address</div>
                                            <div class="font-monospace" x-text="viewData.ip_address || 'N/A'"></div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <div class="text-muted small fw-bold text-uppercase mb-1" style="font-size: 0.7rem; letter-spacing: 0.05em;">URL / Source</div>
                                            <div class="font-monospace text-break" x-text="viewData.url || 'Console / API'"></div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <div class="text-muted small fw-bold text-uppercase mb-1" style="font-size: 0.7rem; letter-spacing: 0.05em;">User Agent</div>
                                            <div class="font-monospace text-break" style="font-size: 0.85rem;" x-text="viewData.user_agent || 'N/A'"></div>
                                        </div>
                                        <div class="col-12" x-show="viewData.tags">
                                            <div class="text-muted small fw-bold text-uppercase mb-1" style="font-size: 0.7rem; letter-spacing: 0.05em;">Tags</div>
                                            <div class="font-monospace text-break" x-text="viewData.tags"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Changes comparison -->
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm rounded-4 h-100">
                                    <div class="card-header bg-danger bg-opacity-10 border-bottom-0 py-3">
                                        <h6 class="fw-bold text-danger mb-0"><i class="bi bi-file-earmark-minus me-2"></i>Old Values</h6>
                                    </div>
                                    <div class="card-body p-0 bg-dark text-white rounded-bottom-4" style="overflow-x: auto;">
                                        <pre class="m-0 p-3 font-monospace small"><code x-text="formatJSON(viewData.old_values)"></code></pre>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm rounded-4 h-100">
                                    <div class="card-header bg-success bg-opacity-10 border-bottom-0 py-3">
                                        <h6 class="fw-bold text-success mb-0"><i class="bi bi-file-earmark-plus me-2"></i>New Values</h6>
                                    </div>
                                    <div class="card-body p-0 bg-dark text-white rounded-bottom-4" style="overflow-x: auto;">
                                        <pre class="m-0 p-3 font-monospace small"><code x-text="formatJSON(viewData.new_values)"></code></pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Clear All Confirmation Modal -->
    <div class="modal fade" id="clearAllModal" aria-labelledby="clearAllModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold" id="clearAllModalLabel">
                        <i class="fas fa-exclamation-triangle text-danger me-2"></i>Clear All Logs
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-4">
                    <p class="mb-0 fs-5">Are you absolutely sure you want to delete <strong>ALL</strong> audit logs?</p>
                    <p class="text-muted small mt-2 mb-0">This action cannot be undone and will permanently remove the entire system audit history.</p>
                </div>
                <div class="modal-footer border-top-0 pt-0 pb-3 pe-3">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger rounded-pill px-4 shadow-sm" @click="executeClearAll">Yes, Clear All Logs</button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('auditLogsTable', () => ({
        items: [],
        selectedItems: [],
        isLoading: true, // Start loading
        
        searchQuery: '',
        eventFilter: '',
        
        currentPage: 1,
        totalPages: 1,
        totalItems: 0,
        itemsPerPage: 15,
        
        stats: {
            total: 0,
            created: 0,
            updated: 0,
            deleted: 0
        },
        
        viewData: null,
        viewModalInstance: null,
        clearModalInstance: null,

        init() {
            this.fetchData();
            
            const viewEl = document.getElementById('viewAuditModal');
            if (viewEl) {
                this.viewModalInstance = new bootstrap.Modal(viewEl);
            }
            
            const clearEl = document.getElementById('clearAllModal');
            if (clearEl) {
                this.clearModalInstance = new bootstrap.Modal(clearEl);
            }
        },

        resetFilters() {
            this.searchQuery = '';
            this.eventFilter = '';
            this.itemsPerPage = 15;
            this.currentPage = 1;
            this.fetchData();
        },

        async fetchData() {
            this.isLoading = true;
            try {
                const url = new URL(window.location.href);
                url.searchParams.set('page', this.currentPage);
                url.searchParams.set('per_page', this.itemsPerPage);
                if (this.searchQuery) url.searchParams.set('search', this.searchQuery);
                if (this.eventFilter) url.searchParams.set('event', this.eventFilter);

                const response = await fetch(url, {
                    headers: { 
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (response.ok) {
                    const res = await response.json();
                    this.items = res.data;
                    this.totalPages = res.last_page;
                    this.totalItems = res.total;
                    if (res.stats) {
                        this.stats = res.stats;
                    }
                }
            } catch (error) {
                console.error("Failed to fetch logs:", error);
                // Optional: show a toast notification here
            } finally {
                this.isLoading = false;
            }
        },

        goToPage(page) {
            if (page >= 1 && page <= this.totalPages) {
                this.currentPage = page;
                this.fetchData();
            }
        },

        toggleAll(checked) {
            this.selectedItems = checked ? this.items.map(i => i.id) : [];
        },

        toggleItem(id) {
            const index = this.selectedItems.indexOf(id);
            if (index === -1) {
                this.selectedItems.push(id);
            } else {
                this.selectedItems.splice(index, 1);
            }
        },

        viewItem(item) {
            this.viewData = item;
            if (this.viewModalInstance) this.viewModalInstance.show();
        },

        confirmClearAll() {
            if (this.clearModalInstance) {
                this.clearModalInstance.show();
            } else {
                if (confirm('Are you absolutely sure you want to delete ALL audit logs? This action cannot be undone.')) {
                    this.executeClearAll();
                }
            }
        },

        async executeClearAll() {
            try {
                const response = await fetch('/admin/audit-logs/clear', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });
                
                if (response.ok) {
                    if (this.clearModalInstance) this.clearModalInstance.hide();
                    this.currentPage = 1;
                    this.fetchData();
                } else {
                    const data = await response.json();
                    throw new Error(data.message || 'Failed to clear logs.');
                }
            } catch (error) {
                alert(error.message);
            }
        },

        async bulkDelete() {
            if (this.selectedItems.length === 0) return;
            if (!confirm(`Are you sure you want to delete the ${this.selectedItems.length} selected log(s)?`)) return;
            
            try {
                const response = await fetch('/admin/audit-logs/destroy', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ ids: this.selectedItems })
                });
                
                if (response.ok) {
                    this.selectedItems = [];
                    this.fetchData();
                } else {
                    const data = await response.json();
                    throw new Error(data.message || 'Failed to delete logs.');
                }
            } catch (error) {
                alert(error.message);
            }
        },

        // Pagination Helpers
        get pageFrom() {
            if (this.totalItems === 0) return 0;
            return ((this.currentPage - 1) * this.itemsPerPage) + 1;
        },

        get pageTo() {
            if (this.totalItems === 0) return 0;
            return Math.min(this.currentPage * this.itemsPerPage, this.totalItems);
        },

        get visiblePages() {
            const current = this.currentPage;
            const last = this.totalPages;
            const delta = 2;
            const left = current - delta;
            const right = current + delta + 1;
            const range = [];

            for (let i = 1; i <= last; i++) {
                if (i === 1 || i === last || (i >= left && i < right)) {
                    range.push(i);
                }
            }
            
            return range;
        },

        formatDate(dateString) {
            if (!dateString) return '';
            const d = new Date(dateString);
            return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
        },
        
        formatTime(dateString) {
            if (!dateString) return '';
            const d = new Date(dateString);
            return d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
        },
        
        formatJSON(data) {
            if (!data || (typeof data === 'object' && Object.keys(data).length === 0)) return 'No data recorded for this state.';
            return JSON.stringify(data, null, 2);
        }
    }));
});
</script>
@endpush
