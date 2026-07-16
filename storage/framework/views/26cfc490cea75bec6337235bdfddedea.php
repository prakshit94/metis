<?php $__env->startSection('title', 'Invoices Management'); ?>
<?php $__env->startSection('page', 'invoices'); ?>

<?php $__env->startSection('content'); ?>
<div class="invoices-management" x-data="invoicesTable()" x-init="init()">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 mb-lg-5 mb-xl-6">
        <div>
            <h1 class="h3 mb-0 fw-bold">Invoices</h1>
            <p class="text-muted mb-0">Generate, track, and send customer invoices</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary" style="background: rgba(99,102,241,0.8); border: none;">
                <i class="bi bi-plus-lg me-2"></i>Generate Invoice
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4 g-lg-5 g-xl-6 mb-5 mb-lg-5 mb-xl-6">
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-primary bg-opacity-10 text-primary me-3 fs-3 rounded p-2">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Total Invoiced</p>
                            <div class="h3 mb-0 fw-bold text-white">$850K</div>
                            <small class="text-success"><i class="bi bi-arrow-up"></i> +4.5% vs last year</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-success bg-opacity-10 text-success me-3 fs-3 rounded p-2">
                            <i class="bi bi-check2-square"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Paid</p>
                            <div class="h3 mb-0 fw-bold text-white" x-text="stats.paid"></div>
                            <small class="text-success-emphasis">93% collection rate</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-warning bg-opacity-10 text-warning me-3 fs-3 rounded p-2">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Unpaid/Overdue</p>
                            <div class="h3 mb-0 fw-bold text-white" x-text="stats.unpaid"></div>
                            <small class="text-warning">Follow-up required</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-info bg-opacity-10 text-info me-3 fs-3 rounded p-2">
                            <i class="bi bi-graph-up"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Avg. Invoice Value</p>
                            <div class="h3 mb-0 fw-bold text-white">$325</div>
                            <small class="text-info">Based on 2.6k invoices</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="h5 card-title mb-0">Invoice Directory</h2>
                </div>
                <div class="col-auto">
                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                        <div class="position-relative">
                            <input type="search" class="form-control form-control-sm" placeholder="Search Invoice #..." x-model="searchQuery" @input="filterInvoices()" style="width: 200px;">
                            <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted"></i>
                        </div>
                        <select class="form-select form-select-sm" x-model="statusFilter" @change="filterInvoices()" style="width: 150px;">
                            <option value="">All Statuses</option>
                            <option value="paid">Paid</option>
                            <option value="unpaid">Unpaid</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <!-- Bulk Actions Bar -->
            <div class="bulk-actions-bar p-3 bg-primary bg-opacity-10 border-bottom border-primary border-opacity-25" x-show="selectedInvoices.length > 0" x-transition>
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-primary"></i>
                        <span class="fw-medium text-primary">
                            <strong x-text="selectedInvoices.length"></strong> invoice(s) selected
                        </span>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-sm btn-primary" @click="bulkUpdateStatus('paid')" title="Mark as Paid">
                            <i class="bi bi-check2-all me-1"></i>Mark Paid
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" @click="selectedInvoices = []" title="Clear selection">
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
                            <th style="width: 40px;">
                                <input type="checkbox" class="form-check-input border-secondary" style="cursor: pointer;" @change="toggleAll($event.target.checked)" :checked="selectedInvoices.length === invoices.length && invoices.length > 0">
                            </th>
                            <th scope="col" role="button" @click="sortBy('invoice_no')" class="sortable">
                                Invoice #
                                <i class="bi bi-arrow-up" x-show="sortField === 'invoice_no' && sortDirection === 'asc'"></i>
                                <i class="bi bi-arrow-down" x-show="sortField === 'invoice_no' && sortDirection === 'desc'"></i>
                            </th>
                            <th scope="col">Order #</th>
                            <th scope="col">Recipient</th>
                            <th scope="col" role="button" @click="sortBy('net_amount')" class="sortable">
                                Amount
                                <i class="bi bi-arrow-up" x-show="sortField === 'net_amount' && sortDirection === 'asc'"></i>
                                <i class="bi bi-arrow-down" x-show="sortField === 'net_amount' && sortDirection === 'desc'"></i>
                            </th>
                            <th scope="col">Status</th>
                            <th scope="col" role="button" @click="sortBy('due_date')" class="sortable">
                                Due Date
                                <i class="bi bi-arrow-up" x-show="sortField === 'due_date' && sortDirection === 'asc'"></i>
                                <i class="bi bi-arrow-down" x-show="sortField === 'due_date' && sortDirection === 'desc'"></i>
                            </th>
                            <th style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="invoice in invoices" :key="invoice.id">
                            <tr :class="{ 'selected': selectedInvoices.includes(String(invoice.id)) }">
                                <td>
                                    <input type="checkbox" class="form-check-input border-secondary" style="cursor: pointer;" :value="String(invoice.id)" x-model="selectedInvoices">
                                </td>
                                <td>
                                    <span class="fw-medium text-white" x-text="invoice.invoice_no"></span>
                                </td>
                                <td>
                                    <span class="text-white-50 font-monospace" x-text="invoice.order_id"></span>
                                </td>
                                <td>
                                    <div class="small fw-medium text-white" x-text="invoice.recipient"></div>
                                </td>
                                <td>
                                    <span class="fw-bold text-white" x-text="`$${invoice.net_amount.toFixed(2)}`"></span>
                                </td>
                                <td>
                                    <span class="badge" 
                                          :class="{
                                              'bg-success bg-opacity-25 text-success border border-success border-opacity-50': invoice.status === 'paid',
                                              'bg-warning bg-opacity-25 text-warning border border-warning border-opacity-50': invoice.status === 'unpaid',
                                              'bg-danger bg-opacity-25 text-danger border border-danger border-opacity-50': invoice.status === 'cancelled'
                                          }"
                                          x-text="invoice.status.toUpperCase()"></span>
                                </td>
                                <td>
                                    <div class="small text-white-50" x-text="invoice.due_date"></div>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="#" @click.prevent="viewDetails(invoice)">
                                                <i class="bi bi-eye me-2"></i>View Details
                                            </a></li>
                                            <li><a class="dropdown-item" href="#">
                                                <i class="bi bi-download me-2"></i>Download PDF
                                            </a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <template x-if="invoices.length === 0">
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    No invoices found matching current criteria.
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center p-3 border-top">
                <div class="text-muted small">
                    Showing <span x-text="(currentPage - 1) * itemsPerPage + 1"></span> to 
                    <span x-text="Math.min(currentPage * itemsPerPage, totalItems)"></span> of 
                    <span x-text="totalItems"></span> results
                </div>
                <nav>
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
    
    <!-- Details Modal -->
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content shadow-lg border-0 rounded-4" x-show="selectedInvoice">
                <template x-if="selectedInvoice">
                    <div>
                        <div class="modal-header border-bottom-0 pb-4 pt-4 px-4 px-lg-5 bg-body-tertiary">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-body-secondary text-primary p-3 rounded-circle shadow-sm">
                                    <i class="bi bi-file-earmark-text fs-3"></i>
                                </div>
                                <div>
                                    <h4 class="modal-title fw-bolder mb-1">Invoice <span class="text-primary" x-text="selectedInvoice.invoice_no"></span></h4>
                                    <p class="text-muted small mb-0">Due: <span x-text="selectedInvoice.due_date"></span></p>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4 p-lg-5">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <p class="fw-bold small text-muted text-uppercase mb-1">Billed To</p>
                                    <p class="fs-5 fw-medium text-body-emphasis" x-text="selectedInvoice.recipient"></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="fw-bold small text-muted text-uppercase mb-1">Order Ref</p>
                                    <p class="font-monospace fw-medium text-body-emphasis" x-text="selectedInvoice.order_id"></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="fw-bold small text-muted text-uppercase mb-1">Amount Due</p>
                                    <p class="fs-4 fw-bolder text-primary" x-text="`$${selectedInvoice.amount.toFixed(2)}`"></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="fw-bold small text-muted text-uppercase mb-1">Status</p>
                                    <p class="fw-medium text-body-emphasis" x-text="selectedInvoice.status"></p>
                                </div>
                                <div class="col-12 mt-4 text-center">
                                    <button class="btn btn-outline-primary shadow-sm" type="button">
                                        <i class="bi bi-download me-2"></i>Download PDF
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('invoicesTable', () => ({
            allInvoices: [
                { id: 1, invoice_no: 'INV-2026-001', order_id: 'ORD-1310', recipient: 'Acme Corp', net_amount: 850.00, status: 'paid', due_date: 'Oct 24, 2026' },
                { id: 2, invoice_no: 'INV-2026-002', order_id: 'ORD-1311', recipient: 'Globex Inc', net_amount: 120.00, status: 'unpaid', due_date: 'Nov 01, 2026' },
                { id: 3, invoice_no: 'INV-2026-003', order_id: 'ORD-1312', recipient: 'Soylent Corp', net_amount: 450.50, status: 'unpaid', due_date: 'Oct 15, 2026' },
                { id: 4, invoice_no: 'INV-2026-004', order_id: 'ORD-1313', recipient: 'Initech', net_amount: 2300.00, status: 'paid', due_date: 'Oct 22, 2026' },
                { id: 5, invoice_no: 'INV-2026-005', order_id: 'ORD-1314', recipient: 'Massive Dynamic', net_amount: 75.25, status: 'paid', due_date: 'Oct 21, 2026' }
            ],
            invoices: [],
            selectedInvoices: [],
            selectedInvoice: null,
            searchQuery: '',
            statusFilter: '',
            sortField: 'invoice_no',
            sortDirection: 'desc',
            currentPage: 1,
            itemsPerPage: 10,
            totalItems: 5,
            
            get stats() {
                return {
                    paid: this.allInvoices.filter(i => i.status === 'paid').length,
                    unpaid: this.allInvoices.filter(i => i.status === 'unpaid' || i.status === 'cancelled').length
                }
            },
            
            init() {
                this.filterInvoices();
            },
            
            filterInvoices() {
                let filtered = this.allInvoices.filter(i => {
                    const matchesSearch = i.invoice_no.toLowerCase().includes(this.searchQuery.toLowerCase());
                    const matchesStatus = this.statusFilter === '' || i.status === this.statusFilter;
                    return matchesSearch && matchesStatus;
                });
                
                filtered.sort((a, b) => {
                    let modifier = this.sortDirection === 'asc' ? 1 : -1;
                    if(a[this.sortField] < b[this.sortField]) return -1 * modifier;
                    if(a[this.sortField] > b[this.sortField]) return 1 * modifier;
                    return 0;
                });
                
                this.totalItems = filtered.length;
                this.invoices = filtered;
            },
            
            sortBy(field) {
                if (this.sortField === field) {
                    this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
                } else {
                    this.sortField = field;
                    this.sortDirection = 'asc';
                }
                this.filterInvoices();
            },
            
            toggleAll(checked) {
                if (checked) {
                    this.selectedInvoices = this.invoices.map(i => String(i.id));
                } else {
                    this.selectedInvoices = [];
                }
            },
            
            bulkUpdateStatus(status) {
                this.allInvoices.forEach(i => {
                    if(this.selectedInvoices.includes(String(i.id))) {
                        i.status = status;
                    }
                });
                this.selectedInvoices = [];
                this.filterInvoices();
            },
            
            viewDetails(invoice) {
                this.selectedInvoice = invoice;
                this.$nextTick(() => {
                    const modalEl = document.getElementById('detailModal');
                    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.show();
                });
            },
            
            get totalPages() {
                return Math.ceil(this.totalItems / this.itemsPerPage) || 1;
            },
            
            goToPage(page) {
                this.currentPage = page;
                this.filterInvoices();
            },
            
            get visiblePages() {
                return [1];
            }
        }));
    });
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/user/metis/resources/views/orders/invoices/index.blade.php ENDPATH**/ ?>