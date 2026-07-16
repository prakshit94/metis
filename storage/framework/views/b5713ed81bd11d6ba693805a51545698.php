<?php $__env->startSection('title', 'Payments Management'); ?>
<?php $__env->startSection('page', 'payments'); ?>

<?php $__env->startSection('content'); ?>
<div class="payments-management" x-data="paymentsTable()" x-init="init()">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 mb-lg-5 mb-xl-6">
        <div>
            <h1 class="h3 mb-0 fw-bold">Payments</h1>
            <p class="text-muted mb-0">Track incoming transactions and capture statuses</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary">
                <i class="bi bi-download me-2"></i>Export
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
                            <i class="bi bi-cash-stack"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Total Volume</p>
                            <div class="h3 mb-0 fw-bold text-white">$1.2M</div>
                            <small class="text-success"><i class="bi bi-arrow-up"></i> +12% YoY</small>
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
                            <p class="h6 mb-0 text-muted">Captured</p>
                            <div class="h3 mb-0 fw-bold text-white" x-text="stats.captured"></div>
                            <small class="text-success-emphasis">95% success</small>
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
                            <i class="bi bi-shield-lock"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Authorized</p>
                            <div class="h3 mb-0 fw-bold text-white" x-text="stats.authorized"></div>
                            <small class="text-info">Awaiting capture</small>
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
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Failed</p>
                            <div class="h3 mb-0 fw-bold text-white" x-text="stats.failed"></div>
                            <small class="text-danger">0.4% failure rate</small>
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
                    <h2 class="h5 card-title mb-0">Transactions</h2>
                </div>
                <div class="col-auto">
                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                        <div class="position-relative">
                            <input type="search" class="form-control form-control-sm" placeholder="Search Txn ID..." x-model="searchQuery" @input="filterPayments()" style="width: 200px;">
                            <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted"></i>
                        </div>
                        <select class="form-select form-select-sm" x-model="statusFilter" @change="filterPayments()" style="width: 150px;">
                            <option value="">All Statuses</option>
                            <option value="captured">Captured</option>
                            <option value="authorized">Authorized</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <!-- Bulk Actions Bar -->
            <div class="bulk-actions-bar p-3 bg-primary bg-opacity-10 border-bottom border-primary border-opacity-25" x-show="selectedPayments.length > 0" x-transition>
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-primary"></i>
                        <span class="fw-medium text-primary">
                            <strong x-text="selectedPayments.length"></strong> payment(s) selected
                        </span>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-sm btn-primary" @click="bulkUpdateStatus('captured')" title="Capture authorized payments">
                            <i class="bi bi-cash me-1"></i>Capture Selected
                        </button>
                        <button class="btn btn-sm btn-outline-danger" @click="bulkUpdateStatus('failed')" title="Mark as failed">
                            <i class="bi bi-x-circle me-1"></i>Mark Failed
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" @click="selectedPayments = []" title="Clear selection">
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
                                <input type="checkbox" class="form-check-input border-secondary" style="cursor: pointer;" @change="toggleAll($event.target.checked)" :checked="selectedPayments.length === payments.length && payments.length > 0">
                            </th>
                            <th scope="col" role="button" @click="sortBy('transaction_id')" class="sortable">
                                Txn ID
                                <i class="bi bi-arrow-up" x-show="sortField === 'transaction_id' && sortDirection === 'asc'"></i>
                                <i class="bi bi-arrow-down" x-show="sortField === 'transaction_id' && sortDirection === 'desc'"></i>
                            </th>
                            <th scope="col">Order #</th>
                            <th scope="col" role="button" @click="sortBy('payment_method')" class="sortable">
                                Method
                                <i class="bi bi-arrow-up" x-show="sortField === 'payment_method' && sortDirection === 'asc'"></i>
                                <i class="bi bi-arrow-down" x-show="sortField === 'payment_method' && sortDirection === 'desc'"></i>
                            </th>
                            <th scope="col" role="button" @click="sortBy('amount')" class="sortable">
                                Amount
                                <i class="bi bi-arrow-up" x-show="sortField === 'amount' && sortDirection === 'asc'"></i>
                                <i class="bi bi-arrow-down" x-show="sortField === 'amount' && sortDirection === 'desc'"></i>
                            </th>
                            <th scope="col">Status</th>
                            <th scope="col" role="button" @click="sortBy('payment_date')" class="sortable">
                                Date
                                <i class="bi bi-arrow-up" x-show="sortField === 'payment_date' && sortDirection === 'asc'"></i>
                                <i class="bi bi-arrow-down" x-show="sortField === 'payment_date' && sortDirection === 'desc'"></i>
                            </th>
                            <th style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="payment in payments" :key="payment.id">
                            <tr :class="{ 'selected': selectedPayments.includes(String(payment.id)) }">
                                <td>
                                    <input type="checkbox" class="form-check-input border-secondary" style="cursor: pointer;" :value="String(payment.id)" x-model="selectedPayments">
                                </td>
                                <td>
                                    <span class="fw-medium text-white" x-text="payment.transaction_id"></span>
                                </td>
                                <td>
                                    <span class="text-white-50 font-monospace" x-text="payment.order_id"></span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-secondary bg-opacity-25 rounded-circle me-2 d-flex align-items-center justify-content-center" style="width:32px;height:32px;">
                                            <i class="bi text-white-50" :class="{
                                                'bi-credit-card': payment.payment_method === 'Credit Card',
                                                'bi-paypal': payment.payment_method === 'PayPal',
                                                'bi-cash': payment.payment_method === 'COD'
                                            }"></i>
                                        </div>
                                        <div class="small fw-medium text-white" x-text="payment.payment_method"></div>
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-bold text-white" x-text="`$${payment.amount.toFixed(2)}`"></span>
                                </td>
                                <td>
                                    <span class="badge" 
                                          :class="{
                                              'bg-success bg-opacity-25 text-success border border-success border-opacity-50': payment.status === 'captured',
                                              'bg-info bg-opacity-25 text-info border border-info border-opacity-50': payment.status === 'authorized',
                                              'bg-danger bg-opacity-25 text-danger border border-danger border-opacity-50': payment.status === 'failed'
                                          }"
                                          x-text="payment.status.toUpperCase()"></span>
                                </td>
                                <td>
                                    <div class="small text-white-50" x-text="payment.payment_date"></div>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="#" @click.prevent="viewDetails(payment)">
                                                <i class="bi bi-eye me-2"></i>View Details
                                            </a></li>
                                            <template x-if="payment.status === 'authorized'">
                                                <li><a class="dropdown-item" href="#" @click.prevent="payment.status = 'captured'; filterPayments()">
                                                    <i class="bi bi-cash me-2"></i>Capture Payment
                                                </a></li>
                                            </template>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <template x-if="payments.length === 0">
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    No payments found matching current criteria.
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
            <div class="modal-content shadow-lg border-0 rounded-4" x-show="selectedPayment">
                <template x-if="selectedPayment">
                    <div>
                        <div class="modal-header border-bottom-0 pb-4 pt-4 px-4 px-lg-5 bg-body-tertiary">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-body-secondary text-primary p-3 rounded-circle shadow-sm">
                                    <i class="bi bi-receipt fs-3"></i>
                                </div>
                                <div>
                                    <h4 class="modal-title fw-bolder mb-1">Txn <span class="text-primary" x-text="selectedPayment.transaction_id"></span></h4>
                                    <p class="text-muted small mb-0" x-text="selectedPayment.payment_date"></p>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4 p-lg-5">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <p class="fw-bold small text-muted text-uppercase mb-1">Method</p>
                                    <p class="fs-5 fw-medium text-body-emphasis" x-text="selectedPayment.payment_method"></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="fw-bold small text-muted text-uppercase mb-1">Order Ref</p>
                                    <p class="font-monospace fw-medium text-body-emphasis" x-text="selectedPayment.order_id"></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="fw-bold small text-muted text-uppercase mb-1">Amount</p>
                                    <p class="fs-4 fw-bolder text-primary" x-text="`$${selectedPayment.amount.toFixed(2)}`"></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="fw-bold small text-muted text-uppercase mb-1">Status</p>
                                    <p class="fw-medium text-body-emphasis" x-text="selectedPayment.status"></p>
                                </div>
                                <div class="col-12 mt-4">
                                    <p class="fw-bold small text-muted text-uppercase mb-2">Gateway Response Log</p>
                                    <div class="bg-body-tertiary p-3 rounded font-monospace small text-muted" style="white-space: pre-wrap;">
{
  "id": "<span x-text="selectedPayment.transaction_id"></span>",
  "object": "charge",
  "amount": <span x-text="selectedPayment.amount * 100"></span>,
  "status": "succeeded",
  "captured": <span x-text="selectedPayment.status === 'captured' ? 'true' : 'false'"></span>
}
                                    </div>
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
        Alpine.data('paymentsTable', () => ({
            allPayments: [
                { id: 1, transaction_id: 'TXN-994821', order_id: 'ORD-1310', payment_method: 'Credit Card', amount: 350.00, status: 'captured', payment_date: 'Oct 24, 2026' },
                { id: 2, transaction_id: 'TXN-994822', order_id: 'ORD-1311', payment_method: 'PayPal', amount: 89.99, status: 'authorized', payment_date: 'Oct 24, 2026' },
                { id: 3, transaction_id: 'TXN-994823', order_id: 'ORD-1312', payment_method: 'COD', amount: 45.50, status: 'failed', payment_date: 'Oct 23, 2026' },
                { id: 4, transaction_id: 'TXN-994824', order_id: 'ORD-1313', payment_method: 'Credit Card', amount: 1200.00, status: 'captured', payment_date: 'Oct 22, 2026' },
                { id: 5, transaction_id: 'TXN-994825', order_id: 'ORD-1314', payment_method: 'Credit Card', amount: 65.25, status: 'captured', payment_date: 'Oct 21, 2026' }
            ],
            payments: [],
            selectedPayments: [],
            selectedPayment: null,
            searchQuery: '',
            statusFilter: '',
            sortField: 'transaction_id',
            sortDirection: 'desc',
            currentPage: 1,
            itemsPerPage: 10,
            totalItems: 5,
            
            get stats() {
                return {
                    captured: this.allPayments.filter(i => i.status === 'captured').length,
                    authorized: this.allPayments.filter(i => i.status === 'authorized').length,
                    failed: this.allPayments.filter(i => i.status === 'failed').length
                }
            },
            
            init() {
                this.filterPayments();
            },
            
            filterPayments() {
                let filtered = this.allPayments.filter(p => {
                    const matchesSearch = p.transaction_id.toLowerCase().includes(this.searchQuery.toLowerCase());
                    const matchesStatus = this.statusFilter === '' || p.status === this.statusFilter;
                    return matchesSearch && matchesStatus;
                });
                
                filtered.sort((a, b) => {
                    let modifier = this.sortDirection === 'asc' ? 1 : -1;
                    if(a[this.sortField] < b[this.sortField]) return -1 * modifier;
                    if(a[this.sortField] > b[this.sortField]) return 1 * modifier;
                    return 0;
                });
                
                this.totalItems = filtered.length;
                this.payments = filtered;
            },
            
            sortBy(field) {
                if (this.sortField === field) {
                    this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
                } else {
                    this.sortField = field;
                    this.sortDirection = 'asc';
                }
                this.filterPayments();
            },
            
            toggleAll(checked) {
                if (checked) {
                    this.selectedPayments = this.payments.map(p => String(p.id));
                } else {
                    this.selectedPayments = [];
                }
            },
            
            bulkUpdateStatus(status) {
                this.allPayments.forEach(p => {
                    if(this.selectedPayments.includes(String(p.id))) {
                        p.status = status;
                    }
                });
                this.selectedPayments = [];
                this.filterPayments();
            },
            
            viewDetails(payment) {
                this.selectedPayment = payment;
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
                this.filterPayments();
            },
            
            get visiblePages() {
                return [1];
            }
        }));
    });
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/user/metis/resources/views/orders/payments/index.blade.php ENDPATH**/ ?>