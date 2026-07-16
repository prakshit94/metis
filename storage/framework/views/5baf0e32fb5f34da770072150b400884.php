<?php $__env->startSection('title', 'Refunds Management'); ?>
<?php $__env->startSection('page', 'refunds'); ?>

<?php $__env->startSection('content'); ?>
<div class="refunds-management" x-data="refundsTable()" x-init="init()">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 mb-lg-5 mb-xl-6">
        <div>
            <h1 class="h3 mb-0 fw-bold">Refunds</h1>
            <p class="text-muted mb-0">Manage financial settlements and customer refunds</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary">
                <i class="bi bi-download me-2"></i>Export
            </button>
            <button class="btn btn-primary" style="background: rgba(99,102,241,0.8); border: none;">
                <i class="bi bi-arrow-return-left me-2"></i>Process Refund
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
                            <i class="bi bi-wallet2"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Total Refunded</p>
                            <div class="h3 mb-0 fw-bold text-white">$45,230</div>
                            <small class="text-success"><i class="bi bi-arrow-up"></i> +5.2% this month</small>
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
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Pending</p>
                            <div class="h3 mb-0 fw-bold text-white" x-text="stats.pending"></div>
                            <small class="text-warning">Needs attention</small>
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
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Processed Today</p>
                            <div class="h3 mb-0 fw-bold text-white">8</div>
                            <small class="text-success"><i class="bi bi-check2-all"></i> All cleared</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-danger bg-opacity-10 text-danger me-3 fs-3 rounded p-2">
                            <i class="bi bi-x-octagon"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Failed</p>
                            <div class="h3 mb-0 fw-bold text-white" x-text="stats.failed"></div>
                            <small class="text-danger"><i class="bi bi-exclamation-triangle"></i> Action required</small>
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
                    <h2 class="h5 card-title mb-0">Refund Directory</h2>
                </div>
                <div class="col-auto">
                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                        <div class="position-relative">
                            <input type="search" class="form-control form-control-sm" placeholder="Search refunds..." x-model="searchQuery" @input="filterRefunds()" style="width: 200px;">
                            <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted"></i>
                        </div>
                        <select class="form-select form-select-sm" x-model="statusFilter" @change="filterRefunds()" style="width: 150px;">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="processed">Processed</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <!-- Bulk Actions Bar -->
            <div class="bulk-actions-bar p-3 bg-primary bg-opacity-10 border-bottom border-primary border-opacity-25" x-show="selectedRefunds.length > 0" x-transition>
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-primary"></i>
                        <span class="fw-medium text-primary">
                            <strong x-text="selectedRefunds.length"></strong> refund(s) selected
                        </span>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-sm btn-primary" @click="bulkUpdateStatus('processed')" title="Process selected refunds">
                            <i class="bi bi-check2-all me-1"></i>Process Selected
                        </button>
                        <button class="btn btn-sm btn-outline-danger" @click="bulkUpdateStatus('failed')" title="Mark as failed">
                            <i class="bi bi-x-circle me-1"></i>Mark Failed
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" @click="selectedRefunds = []" title="Clear selection">
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
                                <input type="checkbox" class="form-check-input border-secondary" style="cursor: pointer;" @change="toggleAll($event.target.checked)" :checked="selectedRefunds.length === refunds.length && refunds.length > 0">
                            </th>
                            <th scope="col" role="button" @click="sortBy('refund_no')" class="sortable">
                                Refund ID
                                <i class="bi bi-arrow-up" x-show="sortField === 'refund_no' && sortDirection === 'asc'"></i>
                                <i class="bi bi-arrow-down" x-show="sortField === 'refund_no' && sortDirection === 'desc'"></i>
                            </th>
                            <th scope="col">Order #</th>
                            <th scope="col">Customer</th>
                            <th scope="col" role="button" @click="sortBy('amount')" class="sortable">
                                Amount
                                <i class="bi bi-arrow-up" x-show="sortField === 'amount' && sortDirection === 'asc'"></i>
                                <i class="bi bi-arrow-down" x-show="sortField === 'amount' && sortDirection === 'desc'"></i>
                            </th>
                            <th scope="col">Status</th>
                            <th scope="col" role="button" @click="sortBy('created_at')" class="sortable">
                                Date
                                <i class="bi bi-arrow-up" x-show="sortField === 'created_at' && sortDirection === 'asc'"></i>
                                <i class="bi bi-arrow-down" x-show="sortField === 'created_at' && sortDirection === 'desc'"></i>
                            </th>
                            <th style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="refund in refunds" :key="refund.id">
                            <tr :class="{ 'selected': selectedRefunds.includes(String(refund.id)) }">
                                <td>
                                    <input type="checkbox" class="form-check-input border-secondary" style="cursor: pointer;" :value="String(refund.id)" x-model="selectedRefunds">
                                </td>
                                <td>
                                    <span class="fw-medium text-white" x-text="refund.refund_no"></span>
                                </td>
                                <td>
                                    <span class="text-white-50 font-monospace" x-text="refund.order_id"></span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-secondary bg-opacity-25 rounded-circle me-2 d-flex align-items-center justify-content-center" style="width:32px;height:32px;">
                                            <i class="bi bi-person text-white-50"></i>
                                        </div>
                                        <div class="small fw-medium text-white" x-text="refund.customer"></div>
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-bold text-white" x-text="`$${refund.amount.toFixed(2)}`"></span>
                                    <br><small class="text-muted" x-text="refund.payment_method"></small>
                                </td>
                                <td>
                                    <span class="badge" 
                                          :class="{
                                              'bg-success bg-opacity-25 text-success border border-success border-opacity-50': refund.status === 'processed',
                                              'bg-warning bg-opacity-25 text-warning border border-warning border-opacity-50': refund.status === 'pending',
                                              'bg-danger bg-opacity-25 text-danger border border-danger border-opacity-50': refund.status === 'failed'
                                          }"
                                          x-text="refund.status.toUpperCase()"></span>
                                </td>
                                <td>
                                    <div class="small text-white-50" x-text="refund.created_at"></div>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="#" @click.prevent="viewDetails(refund)">
                                                <i class="bi bi-eye me-2"></i>View Details
                                            </a></li>
                                            <template x-if="refund.status === 'pending'">
                                                <li><a class="dropdown-item" href="#" @click.prevent="refund.status = 'processed'; filterRefunds()">
                                                    <i class="bi bi-check2 me-2"></i>Process Refund
                                                </a></li>
                                            </template>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <template x-if="refunds.length === 0">
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    No refunds found matching current criteria.
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
            <div class="modal-content shadow-lg border-0 rounded-4" x-show="selectedRefund">
                <template x-if="selectedRefund">
                    <div>
                        <div class="modal-header border-bottom-0 pb-4 pt-4 px-4 px-lg-5 bg-body-tertiary">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-body-secondary text-primary p-3 rounded-circle shadow-sm">
                                    <i class="bi bi-receipt fs-3"></i>
                                </div>
                                <div>
                                    <h4 class="modal-title fw-bolder mb-1">Refund <span class="text-primary" x-text="selectedRefund.refund_no"></span></h4>
                                    <p class="text-muted small mb-0" x-text="selectedRefund.created_at"></p>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4 p-lg-5">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <p class="fw-bold small text-muted text-uppercase mb-1">Customer</p>
                                    <p class="fs-5 fw-medium text-body-emphasis" x-text="selectedRefund.customer"></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="fw-bold small text-muted text-uppercase mb-1">Order Ref</p>
                                    <p class="font-monospace fw-medium text-body-emphasis" x-text="selectedRefund.order_id"></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="fw-bold small text-muted text-uppercase mb-1">Amount</p>
                                    <p class="fs-4 fw-bolder text-primary" x-text="`$${selectedRefund.amount.toFixed(2)}`"></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="fw-bold small text-muted text-uppercase mb-1">Gateway</p>
                                    <p class="fw-medium text-body-emphasis" x-text="selectedRefund.payment_method"></p>
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
        Alpine.data('refundsTable', () => ({
            allRefunds: [
                { id: 1, refund_no: 'REF-89234', order_id: 'ORD-1234', customer: 'Alice Smith', amount: 124.50, status: 'processed', created_at: 'Oct 24, 2026', payment_method: 'Stripe' },
                { id: 2, refund_no: 'REF-89235', order_id: 'ORD-1245', customer: 'Bob Johnson', amount: 45.00, status: 'pending', created_at: 'Oct 24, 2026', payment_method: 'PayPal' },
                { id: 3, refund_no: 'REF-89236', order_id: 'ORD-1288', customer: 'Charlie Davis', amount: 210.75, status: 'failed', created_at: 'Oct 23, 2026', payment_method: 'Stripe' },
                { id: 4, refund_no: 'REF-89237', order_id: 'ORD-1299', customer: 'Diana Evans', amount: 89.99, status: 'processed', created_at: 'Oct 22, 2026', payment_method: 'Store Credit' },
                { id: 5, refund_no: 'REF-89238', order_id: 'ORD-1302', customer: 'Evan Wright', amount: 15.20, status: 'processed', created_at: 'Oct 21, 2026', payment_method: 'Stripe' }
            ],
            refunds: [],
            selectedRefunds: [],
            selectedRefund: null,
            searchQuery: '',
            statusFilter: '',
            sortField: 'refund_no',
            sortDirection: 'desc',
            currentPage: 1,
            itemsPerPage: 10,
            totalItems: 5,
            
            get stats() {
                return {
                    pending: this.allRefunds.filter(r => r.status === 'pending').length,
                    failed: this.allRefunds.filter(r => r.status === 'failed').length
                }
            },
            
            init() {
                this.filterRefunds();
            },
            
            filterRefunds() {
                let filtered = this.allRefunds.filter(r => {
                    const matchesSearch = r.refund_no.toLowerCase().includes(this.searchQuery.toLowerCase()) || r.customer.toLowerCase().includes(this.searchQuery.toLowerCase());
                    const matchesStatus = this.statusFilter === '' || r.status === this.statusFilter;
                    return matchesSearch && matchesStatus;
                });
                
                filtered.sort((a, b) => {
                    let modifier = this.sortDirection === 'asc' ? 1 : -1;
                    if(a[this.sortField] < b[this.sortField]) return -1 * modifier;
                    if(a[this.sortField] > b[this.sortField]) return 1 * modifier;
                    return 0;
                });
                
                this.totalItems = filtered.length;
                this.refunds = filtered;
            },
            
            sortBy(field) {
                if (this.sortField === field) {
                    this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
                } else {
                    this.sortField = field;
                    this.sortDirection = 'asc';
                }
                this.filterRefunds();
            },
            
            toggleAll(checked) {
                if (checked) {
                    this.selectedRefunds = this.refunds.map(r => String(r.id));
                } else {
                    this.selectedRefunds = [];
                }
            },
            
            bulkUpdateStatus(status) {
                this.allRefunds.forEach(r => {
                    if(this.selectedRefunds.includes(String(r.id))) {
                        r.status = status;
                    }
                });
                this.selectedRefunds = [];
                this.filterRefunds();
            },
            
            viewDetails(refund) {
                this.selectedRefund = refund;
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
                this.filterRefunds();
            },
            
            get visiblePages() {
                return [1];
            }
        }));
    });
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/user/metis/resources/views/orders/refunds/index.blade.php ENDPATH**/ ?>