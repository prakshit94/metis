@extends('layouts.app')
@section('title', 'Payments Management')
@section('page', 'payments')

@section('content')
<div class="payments-management" x-data="paymentsTable()" x-init="init()">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 mb-lg-5 mb-xl-6">
        <div>
            <h1 class="h3 mb-0 fw-bold"><i class="bi bi-credit-card text-primary me-2"></i>Payments</h1>
            <p class="text-muted mb-0">Track incoming transactions and capture statuses</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary" @click.prevent="openImportModal">
                <i class="bi bi-upload me-2"></i>Import
            </button>
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
                            <div class="h3 mb-0 fw-bold text-body-emphasis" x-text="formatCurrency(stats.total_volume)"></div>
                            <small class="text-success"><i class="bi bi-arrow-up"></i> Live data</small>
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
                            <p class="h6 mb-0 text-muted">Completed</p>
                            <div class="h3 mb-0 fw-bold text-body-emphasis" x-text="formatCurrency(stats.completed_amount)"></div>
                            <small class="text-success-emphasis">Successful payments</small>
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
                            <p class="h6 mb-0 text-muted">Authorized/Pending</p>
                            <div class="h3 mb-0 fw-bold text-body-emphasis" x-text="formatCurrency(stats.authorized_amount)"></div>
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
                            <p class="h6 mb-0 text-muted">Failed/Refunded</p>
                            <div class="h3 mb-0 fw-bold text-body-emphasis" x-text="formatCurrency(stats.failed_amount)"></div>
                            <small class="text-danger">Failed transactions</small>
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
                            <input type="search" class="form-control form-control-sm" placeholder="Search Txn, Order ID, Name..." x-model="searchQuery" @input.debounce.300ms="filterPayments()" style="width: 200px;">
                            <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted"></i>
                        </div>
                        <select class="form-select form-select-sm" x-model="statusFilter" @change="filterPayments()" style="width: 150px;">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="authorized">Authorized</option>
                            <option value="completed">Completed</option>
                            <option value="failed">Failed</option>
                            <option value="refunded">Refunded</option>
                            <option value="reverted">Reverted</option>
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
                        <button class="btn btn-sm btn-outline-info" @click="exportSelectedPayments()" :disabled="isSubmitting" title="Export Selected to CSV">
                            <i class="bi bi-download me-1"></i>Export CSV
                        </button>
                        <button class="btn btn-sm btn-primary" @click="bulkUpdateStatus('completed')" :disabled="isSubmitting" title="Complete authorized payments">
                            <i class="bi bi-cash me-1"></i>Complete Selected
                        </button>
                        <button class="btn btn-sm btn-outline-danger" @click="bulkUpdateStatus('failed')" :disabled="isSubmitting" title="Mark as failed">
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
                            <th scope="col" role="button" @click="sortBy('payment_no')" class="sortable">
                                <i class="bi bi-credit-card me-1 text-secondary"></i>Payment #
                                <i class="bi bi-arrow-up" x-show="sortField === 'payment_no' && sortDirection === 'asc'"></i>
                                <i class="bi bi-arrow-down" x-show="sortField === 'payment_no' && sortDirection === 'desc'"></i>
                            </th>
                            <th scope="col"><i class="bi bi-hash me-1 text-secondary"></i>Order #</th>
                            <th scope="col"><i class="bi bi-file-earmark-text me-1 text-secondary"></i>Invoice #</th>
                            <th scope="col" role="button" @click="sortBy('payment_method')" class="sortable">
                                <i class="bi bi-wallet2 me-1 text-secondary"></i>Method
                                <i class="bi bi-arrow-up" x-show="sortField === 'payment_method' && sortDirection === 'asc'"></i>
                                <i class="bi bi-arrow-down" x-show="sortField === 'payment_method' && sortDirection === 'desc'"></i>
                            </th>
                            <th scope="col" role="button" @click="sortBy('amount')" class="sortable">
                                <i class="bi bi-currency-rupee me-1 text-secondary"></i>Amount
                                <i class="bi bi-arrow-up" x-show="sortField === 'amount' && sortDirection === 'asc'"></i>
                                <i class="bi bi-arrow-down" x-show="sortField === 'amount' && sortDirection === 'desc'"></i>
                            </th>
                            <th scope="col"><i class="bi bi-info-circle me-1 text-secondary"></i>Status</th>
                            <th scope="col" role="button" @click="sortBy('payment_date')" class="sortable">
                                <i class="bi bi-calendar-event me-1 text-secondary"></i>Date
                                <i class="bi bi-arrow-up" x-show="sortField === 'payment_date' && sortDirection === 'asc'"></i>
                                <i class="bi bi-arrow-down" x-show="sortField === 'payment_date' && sortDirection === 'desc'"></i>
                            </th>
                            <th style="width: 120px;"><i class="bi bi-lightning-charge me-1 text-secondary"></i>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="payment in payments" :key="payment.id">
                            <tr :class="{ 'selected': selectedPayments.includes(String(payment.id)) }">
                                <td>
                                    <input type="checkbox" class="form-check-input border-secondary" style="cursor: pointer;" :value="String(payment.id)" x-model="selectedPayments">
                                </td>
                                <td>
                                    <span class="fw-medium text-body-emphasis" x-text="payment.payment_no"></span>
                                    <br><small class="text-muted" x-text="payment.transaction_id || 'N/A'"></small>
                                </td>
                                <td>
                                    <template x-if="payment.order">
                                        <a :href="`/orders/${payment.order.id}`" class="text-decoration-none font-monospace text-primary fw-medium" target="_blank" x-text="payment.order.order_no"></a>
                                    </template>
                                    <template x-if="!payment.order">
                                        <span class="text-body-secondary font-monospace">N/A</span>
                                    </template>
                                </td>
                                <td>
                                    <template x-if="payment.invoice">
                                        <a href="{{ route('invoices.index') }}" class="text-decoration-none font-monospace text-primary fw-medium" target="_blank" x-text="payment.invoice.invoice_no" title="View in Invoices"></a>
                                    </template>
                                    <template x-if="!payment.invoice">
                                        <span class="text-body-secondary font-monospace">N/A</span>
                                    </template>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-secondary bg-opacity-25 rounded-circle me-2 d-flex align-items-center justify-content-center" style="width:32px;height:32px;">
                                            <i class="bi text-body-secondary" :class="{
                                                'bi-credit-card': payment.payment_method === 'credit_card' || payment.payment_method === 'Credit Card',
                                                'bi-paypal': payment.payment_method === 'paypal' || payment.payment_method === 'PayPal',
                                                'bi-cash': payment.payment_method === 'cod' || payment.payment_method === 'COD',
                                                'bi-bank': payment.payment_method === 'bank_transfer'
                                            }"></i>
                                        </div>
                                        <div class="small fw-medium text-body-emphasis" x-text="payment.payment_method.toUpperCase().replace('_', ' ')"></div>
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-bold text-body-emphasis" x-text="formatCurrency(payment.amount)"></span>
                                </td>
                                <td>
                                    <span class="badge" 
                                          :class="{
                                              'bg-dark bg-opacity-25 text-body-emphasis border border-dark border-opacity-50': payment.deleted_at,
                                              'bg-success bg-opacity-25 text-success border border-success border-opacity-50': !payment.deleted_at && payment.status === 'completed',
                                              'bg-info bg-opacity-25 text-info border border-info border-opacity-50': !payment.deleted_at && payment.status === 'authorized',
                                              'bg-warning bg-opacity-25 text-warning border border-warning border-opacity-50': !payment.deleted_at && payment.status === 'pending',
                                              'bg-danger bg-opacity-25 text-danger border border-danger border-opacity-50': !payment.deleted_at && payment.status === 'failed',
                                              'bg-secondary bg-opacity-25 text-secondary border border-secondary border-opacity-50': !payment.deleted_at && payment.status === 'refunded'
                                          }"
                                          x-text="payment.deleted_at ? 'REVERTED' : payment.status.toUpperCase()"></span>
                                </td>
                                <td>
                                    <div class="small text-body-secondary" x-text="formatDate(payment.payment_date)"></div>
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
                                            <li><a class="dropdown-item" href="#" @click.prevent="editPayment(payment)">
                                                <i class="bi bi-pencil-square me-2"></i>Edit
                                            </a></li>
                                            <template x-if="payment.status === 'authorized'">
                                                <li><a class="dropdown-item" href="#" @click.prevent="updatePaymentStatus(payment.id, 'completed')">
                                                    <i class="bi bi-cash me-2"></i>Complete Payment
                                                </a></li>
                                            </template>
                                            <template x-if="payment.status === 'completed'">
                                                <li><a class="dropdown-item text-danger" href="#" @click.prevent="revertPayment(payment.id)">
                                                    <i class="bi bi-arrow-counterclockwise me-2"></i>Revert Payment
                                                </a></li>
                                            </template>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <template x-if="payments.length === 0">
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
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
                    Showing <span x-text="totalItems === 0 ? 0 : (currentPage - 1) * itemsPerPage + 1"></span> to 
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
    <div class="modal fade" id="detailModal">
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
                                    <h4 class="modal-title fw-bolder mb-1">Txn <span class="text-primary" x-text="selectedPayment.payment_no"></span></h4>
                                    <p class="text-muted small mb-0"><i class="bi bi-clock me-1"></i><span x-text="formatDateTime(selectedPayment.payment_date)"></span></p>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4 p-lg-5">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <p class="fw-bold small text-muted text-uppercase mb-1">Method</p>
                                    <p class="fs-5 fw-medium text-body-emphasis" x-text="selectedPayment.payment_method.toUpperCase().replace('_', ' ')"></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="fw-bold small text-muted text-uppercase mb-1">Order Ref</p>
                                    <p class="font-monospace fw-medium text-body-emphasis" x-text="selectedPayment.order ? selectedPayment.order.order_no : 'N/A'"></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="fw-bold small text-muted text-uppercase mb-1">Amount</p>
                                    <p class="fs-4 fw-bolder text-primary" x-text="formatCurrency(selectedPayment.amount)"></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="fw-bold small text-muted text-uppercase mb-1">Status</p>
                                    <p class="fw-medium text-body-emphasis" x-text="selectedPayment.status.toUpperCase()"></p>
                                </div>
                                <div class="col-12">
                                    <p class="fw-bold small text-muted text-uppercase mb-2">Payment History</p>
                                    <div class="border rounded-3 overflow-hidden">
                                        <template x-if="selectedPayment.paymentHistory.length">
                                            <div class="table-responsive">
                                                <table class="table table-sm align-middle mb-0">
                                                    <thead class="table-light"><tr><th>Payment #</th><th>Method</th><th>Date</th><th class="text-end">Amount</th><th>Status</th><th>Transaction ID</th></tr></thead>
                                                    <tbody>
                                                        <template x-for="historyPayment in selectedPayment.paymentHistory" :key="historyPayment.id">
                                                            <tr :class="{ 'table-primary': historyPayment.id === selectedPayment.id }">
                                                                <td class="font-monospace" x-text="historyPayment.payment_no"></td>
                                                                <td x-text="(historyPayment.payment_method || 'N/A').toUpperCase().replace(/_/g, ' ')"></td>
                                                                <td x-text="formatDateTime(historyPayment.payment_date)"></td>
                                                                <td class="text-end fw-semibold" x-text="formatCurrency(historyPayment.amount)"></td>
                                                                <td><span class="badge" :class="historyPayment.status === 'completed' ? 'bg-success' : 'bg-secondary'" x-text="historyPayment.status.toUpperCase()"></span></td>
                                                                <td class="font-monospace small" x-text="historyPayment.transaction_id || '—'"></td>
                                                            </tr>
                                                        </template>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </template>
                                        <template x-if="!selectedPayment.paymentHistory.length"><p class="text-muted small mb-0 p-3">No related payment history found.</p></template>
                                    </div>
                                </div>
                                <div class="col-12 mt-4">
                                    <p class="fw-bold small text-muted text-uppercase mb-2">Gateway Response Log</p>
                                    <pre class="bg-body-tertiary p-3 rounded font-monospace small text-muted" style="white-space: pre-wrap;">
{
  "id": "<span x-text="selectedPayment.payment_no"></span>",
  "object": "charge",
  "amount": <span x-text="selectedPayment.amount * 100"></span>,
  "status": "<span x-text="selectedPayment.status"></span>",
  "completed": <span x-text="selectedPayment.status === 'completed' ? 'true' : 'false'"></span>
}
                                    </pre>
                                </div>
                                <div class="col-12 mt-2">
                                    <div class="p-4 bg-body border border-light-subtle rounded-4 shadow-sm">
                                        <div class="row align-items-center">
                                            <div class="col-6 border-end">
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <i class="bi bi-person-check-fill text-success fs-5"></i>
                                                    <p class="fw-bold small text-muted text-uppercase mb-0">Recorded By</p>
                                                </div>
                                                <p class="fw-bold text-body-emphasis mb-0 fs-6 ps-4 ms-1" x-text="selectedPayment.recorder ? selectedPayment.recorder.name : 'System'"></p>
                                                <small class="text-muted ps-4 ms-1" x-text="selectedPayment.recorded_at ? formatDateTime(selectedPayment.recorded_at) : ''"></small>
                                            </div>
                                            <div class="col-6 ps-4" x-show="selectedPayment.reverter">
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <i class="bi bi-arrow-counterclockwise text-danger fs-5"></i>
                                                    <p class="fw-bold small text-danger text-uppercase mb-0">Reverted By</p>
                                                </div>
                                                <p class="fw-bold text-danger mb-0 fs-6 ps-4 ms-1" x-text="selectedPayment.reverter ? selectedPayment.reverter.name : 'System'"></p>
                                                <small class="text-danger ps-4 ms-1" x-text="selectedPayment.reverted_at ? formatDateTime(selectedPayment.reverted_at) : ''"></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <form @submit.prevent="updatePayment">
                    <div class="modal-header border-bottom-0 pb-0 pt-4 px-4">
                        <h5 class="modal-title fw-bold">Edit Payment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-muted">Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text border-end-0 bg-transparent text-muted">₹</span>
                                    <input type="number" step="0.01" class="form-control" x-model="editForm.amount" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-muted">Payment Date</label>
                                <input type="datetime-local" class="form-control" x-model="editForm.payment_date" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-muted">Method</label>
                                <select class="form-select" x-model="editForm.payment_method" required>
                                    <option value="credit_card">Credit Card</option>
                                    <option value="paypal">PayPal</option>
                                    <option value="cod">COD</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-muted">Status</label>
                                <select class="form-select" x-model="editForm.status" required>
                                    <option value="pending">Pending</option>
                                    <option value="authorized">Authorized</option>
                                    <option value="completed">Completed</option>
                                    <option value="failed">Failed</option>
                                    <option value="refunded">Refunded</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold text-muted">Transaction ID</label>
                                <input type="text" class="form-control" x-model="editForm.transaction_id">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 px-4 pb-4 pt-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4" :disabled="isSubmitting">
                            <span x-show="isSubmitting" class="spinner-border spinner-border-sm me-2" role="status"></span>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    @include('components.import-payments-modal')
</div>
@endsection
