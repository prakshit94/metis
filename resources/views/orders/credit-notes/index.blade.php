@extends('layouts.app')

@section('title', '🧾 Credit Notes Management')
@section('page', 'orders-credit-notes')

@section('content')
<div class="user-management" x-data="creditNotesTable" x-cloak>
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 mb-lg-5 mb-xl-6">
        <div>
            <h1 class="h3 mb-0"><i class="bi bi-receipt-cutoff text-primary me-2"></i>Credit Notes Management</h1>
            <p class="text-muted mb-0">Manage customer credit notes and balances</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary" @click.prevent="openCreateModal()">
                <i class="bi bi-plus-lg me-2"></i>Issue Credit Note
            </button>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row g-4 g-lg-5 g-xl-6 mb-5 mb-lg-5 mb-xl-6">
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bi bi-receipt"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Total Records</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.total"></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-success bg-opacity-10 text-success me-3">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Active Notes</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.active"></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-warning bg-opacity-10 text-warning me-3">
                            <i class="bi bi-check2-all"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Fully Used</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.used"></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-danger bg-opacity-10 text-danger me-3">
                            <i class="bi bi-x-circle-fill"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Cancelled</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.cancelled || 0"></span></div>
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
                    <h2 class="h5 card-title mb-0">Credit Notes Directory</h2>
                </div>
                <div class="col-auto">
                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                        <div class="position-relative">
                            <input type="search" class="form-control form-control-sm" placeholder="Search by ID or Customer..." x-model.debounce.400ms="searchQuery" style="width: 200px;">
                            <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted"></i>
                        </div>
                        <select x-select class="form-select form-select-sm" x-model="statusFilter" @change="fetchData()" style="width: 150px;">
                            <option value="">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="used">Used</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <!-- Bulk Actions Bar -->
            <div class="bulk-actions-bar p-3 bg-primary bg-opacity-10 border-bottom border-primary border-opacity-25" x-show="selected.length > 0" x-cloak>
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle-fill text-primary me-2"></i>
                        <span class="fw-medium text-primary">
                            <span x-text="selected.length"></span> selected
                        </span>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-success" @click="bulkAction('activate')"><i class="bi bi-check-circle me-1"></i>Mark Active</button>
                        <button class="btn btn-sm btn-warning" @click="bulkAction('cancel')"><i class="bi bi-pause-circle me-1"></i>Cancel</button>
                        <button class="btn btn-sm btn-danger" @click="bulkAction('delete')"><i class="bi bi-trash me-1"></i>Delete</button>
                        <button class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center px-2" @click="selected = []" title="Clear selection">
                            <i class="bi bi-x-lg" style="margin-left: 7px"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="text-uppercase small">
                        <tr>
                            <th style="width:40px"><input type="checkbox" class="user-select-checkbox" @change="$event.isTrusted && toggleAll($event)" :checked="allSelected"></th>
                            <th>Note ID</th>
                            <th>Customer</th>
                            <th>Related Doc</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Balance</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="isLoading">
                            <tr><td colspan="8" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>
                        </template>
                        <template x-if="!isLoading && items.length === 0">
                            <tr><td colspan="8" class="text-center py-5 text-muted"><i class="bi bi-receipt fs-1 d-block mb-2"></i>No credit notes found</td></tr>
                        </template>
                        <template x-for="item in items" :key="item.id">
                            <tr :class="{ 'selected': selected.includes(item.id) }">
                                <td><input type="checkbox" class="user-select-checkbox" :value="item.id" x-model="selected"></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="p-2 rounded-circle me-3 d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary" style="width: 38px; height: 38px;">
                                            <i class="fs-5 bi bi-receipt-cutoff"></i>
                                        </div>
                                        <div>
                                            <code class="badge bg-body-secondary text-body-emphasis fs-6 px-3 py-2 font-monospace">CN-<span x-text="item.id.toString().padStart(5, '0')"></span></code>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold text-body-emphasis fs-6" x-text="item.customer ? (item.customer.company_name || item.customer.firstname + ' ' + (item.customer.lastname || '')) : 'Unknown'"></div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        <template x-if="item.invoice">
                                            <span class="badge rounded-pill px-3 py-2 fw-medium border bg-info-subtle text-info-emphasis border-info-subtle align-self-start">
                                                <i class="bi bi-file-earmark-text me-1"></i> Inv: <span x-text="item.invoice.invoice_number"></span>
                                            </span>
                                        </template>
                                        <template x-if="item.order_return">
                                            <span class="badge rounded-pill px-3 py-2 fw-medium border bg-warning-subtle text-warning-emphasis border-warning-subtle align-self-start">
                                                <i class="bi bi-arrow-return-left me-1"></i> Ret: <span x-text="item.order_return.return_number"></span>
                                            </span>
                                        </template>
                                        <template x-if="!item.invoice && !item.order_return">
                                            <span class="text-muted small">—</span>
                                        </template>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <span class="fw-bold text-body-emphasis fs-6">₹<span x-text="item.amount"></span></span>
                                </td>
                                <td class="text-end">
                                    <span class="fw-bold fs-6" :class="item.balance_remaining > 0 ? 'text-success' : 'text-muted'">₹<span x-text="item.balance_remaining"></span></span>
                                </td>
                                <td>
                                    <span class="badge rounded-pill px-3 py-2 fw-medium border"
                                          :class="{
                                              'bg-success-subtle text-success-emphasis border-success-subtle': item.status === 'active',
                                              'bg-secondary-subtle text-secondary-emphasis border-secondary-subtle': item.status === 'used',
                                              'bg-danger-subtle text-danger-emphasis border-danger-subtle': item.status === 'cancelled'
                                          }">
                                        <span class="d-inline-block rounded-circle me-1" 
                                              :class="{
                                                'bg-success': item.status === 'active',
                                                'bg-secondary': item.status === 'used',
                                                'bg-danger': item.status === 'cancelled'
                                              }" 
                                              style="width: 6px; height: 6px; vertical-align: middle;"></span>
                                        <span x-text="item.status.toUpperCase()"></span>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="#" @click.prevent="editItem(item)">
                                                    <i class="bi bi-pencil me-2"></i>Edit Balance/Status
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item text-danger" href="#" @click.prevent="deleteItem(item)">
                                                    <i class="bi bi-trash me-2"></i>Delete
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
            <div class="d-flex justify-content-between align-items-center p-3">
                <div class="text-muted small">
                    Showing <span x-text="from"></span> to <span x-text="to"></span> of <span x-text="total"></span> results
                </div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item" :class="{ 'disabled': currentPage <= 1 }">
                            <a class="page-link" href="#" @click.prevent="currentPage--; fetchData()">Previous</a>
                        </li>
                        <li class="page-item" :class="{ 'disabled': currentPage >= totalPages }">
                            <a class="page-link" href="#" @click.prevent="currentPage++; fetchData()">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Form Modal (Glossy Style) -->
    <div class="modal fade" id="creditNoteModal" wire:ignore.self>
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                {{-- GLOSSY STYLE HEADER WITH BOOTSTRAP --}}
                <div class="modal-header bg-body-tertiary border-bottom d-flex align-items-center justify-content-between p-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px;">
                            <i class="bi bi-receipt fs-4 text-primary"></i>
                        </div>
                        <div>
                            <h4 class="mb-0 fw-bold text-body"><span x-text="isEditing ? 'Edit Credit Note' : 'Issue Credit Note'"></span></h4>
                            <p class="mb-0 small text-muted">Manage customer credit and balances</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body p-4 bg-body-tertiary">
                    <form @submit.prevent="saveItem">
                        <div class="row g-4">
                            <div class="col-12">
                                <template x-if="!isEditing">
                                    <div>
                                        <div class="card mb-3 border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary">
                                            <div class="card-body p-3">
                                                <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom">
                                                    <div class="bg-primary bg-opacity-10 text-primary rounded-2 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                                        <i class="bi bi-person fs-6 text-primary"></i>
                                                    </div>
                                                    <h6 class="mb-0 fw-bold text-uppercase text-body" style="font-size: 11px; letter-spacing: 1px;">Customer Information</h6>
                                                </div>
                                                <div class="mb-3 position-relative" @click.away="showCustomerDropdown = false">
                                                    <label class="form-label fw-bold text-muted text-uppercase" style="font-size: 10px;">Customer *</label>
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text bg-body text-muted border-end-0"><i class="bi bi-person"></i></span>
                                                        <div class="form-control border-start-0 ps-0 fw-semibold bg-body cursor-pointer d-flex align-items-center" @click="showCustomerDropdown = !showCustomerDropdown" style="font-size: 12px; min-height: 31px;">
                                                            <span class="flex-grow-1" x-text="selectedCustomerName || 'Select Customer'"></span>
                                                            <i class="bi bi-chevron-down text-muted"></i>
                                                        </div>
                                                    </div>
                                                    <div x-show="showCustomerDropdown" class="position-absolute w-100 bg-body border rounded shadow-lg mt-1" style="max-height: 200px; overflow-y: auto; z-index: 1050;">
                                                        <div class="p-2 border-bottom position-sticky top-0 bg-body">
                                                            <input type="text" class="form-control form-control-sm" x-model="customerSearch" placeholder="Search...">
                                                        </div>
                                                        <template x-for="cust in filteredCustomers" :key="cust.id">
                                                            <div class="px-3 py-2 cursor-pointer custom-hover-bg d-flex align-items-center" @click="form.customer_id = cust.id; showCustomerDropdown = false">
                                                                <span style="font-size: 12px;" x-text="cust.name"></span>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card mb-3 border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary">
                                            <div class="card-body p-3">
                                                <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom">
                                                    <div class="bg-success bg-opacity-10 text-success rounded-2 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                                        <i class="bi bi-currency-rupee fs-6 text-success"></i>
                                                    </div>
                                                    <h6 class="mb-0 fw-bold text-uppercase text-body" style="font-size: 11px; letter-spacing: 1px;">Value & Details</h6>
                                                </div>
                                                <div class="row g-3 mb-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-bold text-muted text-uppercase" style="font-size: 10px;">Amount *</label>
                                                        <div class="input-group input-group-sm">
                                                            <span class="input-group-text">₹</span>
                                                            <input type="number" step="0.01" min="0.01" class="form-control fw-semibold" x-model="form.amount" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-bold text-muted text-uppercase" style="font-size: 10px;">Status</label>
                                                        <select x-select class="form-select form-select-sm fw-semibold" x-model="form.status">
                                                            <option value="active">Active</option>
                                                            <option value="used">Used</option>
                                                            <option value="cancelled">Cancelled</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-bold text-muted text-uppercase" style="font-size: 10px;">Related Invoice (Opt)</label>
                                                        <select x-select class="form-select form-select-sm fw-semibold" x-model="form.invoice_id">
                                                            <option value="">None</option>
                                                            <template x-for="inv in invoices" :key="inv.id">
                                                                <option :value="inv.id" x-text="inv.invoice_number"></option>
                                                            </template>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-bold text-muted text-uppercase" style="font-size: 10px;">Related Return (Opt)</label>
                                                        <select x-select class="form-select form-select-sm fw-semibold" x-model="form.order_return_id">
                                                            <option value="">None</option>
                                                            <template x-for="ret in returns" :key="ret.id">
                                                                <option :value="ret.id" x-text="ret.return_number"></option>
                                                            </template>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                
                                <template x-if="isEditing">
                                    <div class="card mb-3 border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom">
                                                <div class="bg-warning bg-opacity-10 text-warning rounded-2 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                                    <i class="bi bi-pencil fs-6 text-warning"></i>
                                                </div>
                                                <h6 class="mb-0 fw-bold text-uppercase text-body" style="font-size: 11px; letter-spacing: 1px;">Edit Details</h6>
                                            </div>
                                            <div class="row g-3">
                                                <div class="col-12">
                                                    <label class="form-label fw-bold text-muted text-uppercase" style="font-size: 10px;">Original Amount</label>
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text">₹</span>
                                                        <input type="text" class="form-control fw-semibold" :value="form.amount" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label fw-bold text-muted text-uppercase" style="font-size: 10px;">Balance Remaining *</label>
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text">₹</span>
                                                        <input type="number" step="0.01" min="0" class="form-control fw-semibold" x-model="form.balance_remaining" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label fw-bold text-muted text-uppercase" style="font-size: 10px;">Status</label>
                                                    <select x-select class="form-select form-select-sm fw-semibold" x-model="form.status">
                                                        <option value="active">Active</option>
                                                        <option value="used">Used</option>
                                                        <option value="cancelled">Cancelled</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </form>
                </div>
                
                {{-- Form Actions --}}
                <div class="modal-footer bg-body-tertiary border-top d-flex justify-content-end gap-3 p-3">
                    <button type="button" data-bs-dismiss="modal" class="btn text-muted fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 1px;">Cancel</button>
                    <button class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" @click="saveItem()" :disabled="saving">
                        <span x-show="saving" class="spinner-border spinner-border-sm me-2"></span>
                        <span x-text="isEditing ? 'Save Changes' : 'Issue Note'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
</div>

<style>
.custom-hover-bg:hover { background-color: rgba(var(--bs-primary-rgb), 0.1); }
input[type="text"]:focus, input[type="search"]:focus {
    box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.15) !important;
    border-color: var(--bs-primary) !important;
}
.cursor-pointer { cursor: pointer; }
</style>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('creditNotesTable', () => ({
        items: [],
        isLoading: false,
        searchQuery: '',
        statusFilter: '',
        currentPage: 1,
        totalPages: 1,
        total: 0, from: 0, to: 0,
        selected: [],
        stats: {!! json_encode($stats ?? ['total' => 0, 'active' => 0, 'used' => 0, 'cancelled' => 0]) !!},
        customers: {!! isset($customers) ? $customers->map(function($c) { return ['id' => $c->id, 'name' => $c->company_name ?: $c->firstname . ' ' . $c->lastname]; })->toJson() : '[]' !!},
        invoices: {!! json_encode($invoices ?? []) !!},
        returns: {!! json_encode($returns ?? []) !!},
        
        isEditing: false,
        saving: false,
        modal: null,
        
        form: {
            id: null,
            customer_id: '',
            invoice_id: '',
            order_return_id: '',
            amount: '',
            balance_remaining: '',
            status: 'active'
        },
        
        customerSearch: '',
        showCustomerDropdown: false,
        
        get filteredCustomers() {
            if (!this.customerSearch) return this.customers;
            return this.customers.filter(c => c.name.toLowerCase().includes(this.customerSearch.toLowerCase()));
        },
        get selectedCustomerName() {
            const c = this.customers.find(c => c.id === this.form.customer_id);
            return c ? c.name : '';
        },
        get allSelected() { 
            return this.items.length > 0 && this.selected.length === this.items.length; 
        },
        
        init() {
            this.modal = new bootstrap.Modal(document.getElementById('creditNoteModal'));
            this.fetchData();
            this.$watch('searchQuery', () => { this.currentPage = 1; this.fetchData(); });
        },
        
        fetchData() {
            this.isLoading = true;
            let url = new URL(window.location.href);
            url.searchParams.set('page', this.currentPage);
            if (this.searchQuery) url.searchParams.set('search', this.searchQuery);
            if (this.statusFilter) url.searchParams.set('status', this.statusFilter);
            
            fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(res => res.json())
                .then(data => {
                    this.items = data.data;
                    this.currentPage = data.current_page;
                    this.totalPages = data.last_page;
                    this.total = data.total || 0;
                    this.from = data.from || 0;
                    this.to = data.to || 0;
                })
                .catch(err => console.error(err))
                .finally(() => this.isLoading = false);
        },
        
        openCreateModal() {
            this.isEditing = false;
            this.form = { id: null, customer_id: '', invoice_id: '', order_return_id: '', amount: '', balance_remaining: '', status: 'active' };
            this.customerSearch = '';
            this.modal.show();
        },
        
        editItem(item) {
            this.isEditing = true;
            this.form = { ...item };
            this.modal.show();
        },
        
        saveItem() {
            this.saving = true;
            const url = this.isEditing ? `/api/credit-notes/${this.form.id}` : '/api/credit-notes';
            const method = this.isEditing ? 'PATCH' : 'POST';
            
            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(this.form)
            })
            .then(res => {
                if(!res.ok) throw new Error('Network response was not ok');
                return res.json();
            })
            .then(data => {
                this.modal.hide();
                this.fetchData();
                if(window.AdminApp && window.AdminApp.notificationManager) {
                    window.AdminApp.notificationManager.success(data.message);
                }
            })
            .catch(err => {
                console.error(err);
                if(window.AdminApp && window.AdminApp.notificationManager) {
                    window.AdminApp.notificationManager.error('Error saving credit note.');
                }
            })
            .finally(() => this.saving = false);
        },
        
        deleteItem(item) {
            if(!confirm('Are you sure you want to delete this credit note?')) return;
            fetch(`/api/credit-notes/${item.id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(res => {
                if(!res.ok) throw new Error('Error deleting note');
                this.fetchData();
            })
            .catch(err => console.error(err));
        },
        
        toggleAll(e) { this.selected = e.target.checked ? this.items.map(i => i.id) : []; },
        
        bulkAction(action) {
            if (!this.selected.length) return;
            alert(`Bulk action ${action} on ${this.selected.length} items (UI Scaffold)`);
            this.selected = [];
        }
    }));
});
</script>
@endpush
