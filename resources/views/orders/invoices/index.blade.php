@extends('layouts.app')
@section('title', '🧾 Invoices Management')
@section('page', 'invoices')

@section('content')
<div class="invoices-management" x-data="invoicesTable()" x-init="init()">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 mb-lg-5 mb-xl-6">
        <div>
            <h1 class="h3 mb-0 fw-bold"><i class="bi bi-receipt text-primary me-2"></i>Invoices</h1>
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
                            <div class="h3 mb-0 fw-bold text-white" x-text="formatCurrency(stats.total_invoiced)"></div>
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
                            <i class="bi bi-check2-square"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Paid</p>
                            <div class="h3 mb-0 fw-bold text-white" x-text="stats.paid"></div>
                            <small class="text-success-emphasis">Collection rate tracked</small>
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
                            <div class="h3 mb-0 fw-bold text-white" x-text="formatCurrency(stats.avg_value)"></div>
                            <small class="text-info">Based on total transactions</small>
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
                            <input type="search" class="form-control form-control-sm" placeholder="Search Invoice #..." x-model="searchQuery" @input.debounce.300ms="filterInvoices()" style="width: 200px;">
                            <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted"></i>
                        </div>
                        <select class="form-select form-select-sm" x-model="statusFilter" @change="filterInvoices()" style="width: 150px;">
                            <option value="">All Statuses</option>
                            <option value="paid">Paid</option>
                            <option value="partially_paid">Partially Paid</option>
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
                        <button class="btn btn-sm btn-primary" @click="bulkUpdateStatus('paid')" :disabled="isSubmitting" title="Mark as Paid">
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
                                <i class="bi bi-file-earmark-text me-1 text-secondary"></i>Invoice #
                                <i class="bi bi-arrow-up" x-show="sortField === 'invoice_no' && sortDirection === 'asc'"></i>
                                <i class="bi bi-arrow-down" x-show="sortField === 'invoice_no' && sortDirection === 'desc'"></i>
                            </th>
                            <th scope="col"><i class="bi bi-hash me-1 text-secondary"></i>Order #</th>
                            <th scope="col"><i class="bi bi-person me-1 text-secondary"></i>Recipient</th>
                            <th scope="col" role="button" @click="sortBy('net_amount')" class="sortable">
                                <i class="bi bi-currency-dollar me-1 text-secondary"></i>Amount
                                <i class="bi bi-arrow-up" x-show="sortField === 'net_amount' && sortDirection === 'asc'"></i>
                                <i class="bi bi-arrow-down" x-show="sortField === 'net_amount' && sortDirection === 'desc'"></i>
                            </th>
                            <th scope="col"><i class="bi bi-info-circle me-1 text-secondary"></i>Status</th>
                            <th scope="col" role="button" @click="sortBy('due_date')" class="sortable">
                                <i class="bi bi-calendar-event me-1 text-secondary"></i>Due Date
                                <i class="bi bi-arrow-up" x-show="sortField === 'due_date' && sortDirection === 'asc'"></i>
                                <i class="bi bi-arrow-down" x-show="sortField === 'due_date' && sortDirection === 'desc'"></i>
                            </th>
                            <th style="width: 120px;"><i class="bi bi-lightning-charge me-1 text-secondary"></i>Actions</th>
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
                                    <span class="text-white-50 font-monospace" x-text="invoice.order ? invoice.order.order_no : 'N/A'"></span>
                                </td>
                                <td>
                                    <div class="small fw-medium text-white" x-text="invoice.order && invoice.order.party ? (invoice.order.party.firstname + ' ' + invoice.order.party.lastname) : 'N/A'"></div>
                                </td>
                                <td>
                                    <div class="fw-bold text-white" x-text="formatCurrency(invoice.net_amount)"></div>
                                    <template x-if="invoice.status === 'partially_paid' || invoice.paid_amount > 0">
                                        <div class="small mt-1 lh-sm">
                                            <span class="text-success d-block" style="font-size: 0.75rem;">Paid: <span x-text="formatCurrency(invoice.paid_amount)"></span></span>
                                            <span class="text-warning d-block" style="font-size: 0.75rem;">Remaining: <span x-text="formatCurrency(invoice.due_amount)"></span></span>
                                        </div>
                                    </template>
                                </td>
                                <td>
                                    <span class="badge" 
                                          :class="{
                                              'bg-success bg-opacity-25 text-success border border-success border-opacity-50': invoice.status === 'paid',
                                              'bg-warning bg-opacity-25 text-warning border border-warning border-opacity-50': invoice.status === 'unpaid' || invoice.status === 'partially_paid',
                                              'bg-danger bg-opacity-25 text-danger border border-danger border-opacity-50': invoice.status === 'cancelled'
                                          }"
                                          x-text="invoice.status.toUpperCase().replace('_', ' ')"></span>
                                </td>
                                <td>
                                    <div class="small text-white-50" x-text="formatDate(invoice.due_date)"></div>
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
                                    <p class="text-muted small mb-0">Due: <span x-text="formatDate(selectedInvoice.due_date)"></span></p>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4 p-lg-5">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <p class="fw-bold small text-muted text-uppercase mb-1">Billed To</p>
                                    <p class="fs-5 fw-medium text-body-emphasis" x-text="selectedInvoice.order && selectedInvoice.order.party ? (selectedInvoice.order.party.firstname + ' ' + selectedInvoice.order.party.lastname) : 'N/A'"></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="fw-bold small text-muted text-uppercase mb-1">Order Ref</p>
                                    <p class="font-monospace fw-medium text-body-emphasis" x-text="selectedInvoice.order ? selectedInvoice.order.order_no : 'N/A'"></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="fw-bold small text-muted text-uppercase mb-1">Total Invoice Value</p>
                                    <p class="fs-5 fw-bold text-body-emphasis" x-text="formatCurrency(selectedInvoice.net_amount)"></p>
                                </div>
                                <div class="col-md-6" x-show="selectedInvoice.paid_amount > 0">
                                    <p class="fw-bold small text-muted text-uppercase mb-1">Amount Paid</p>
                                    <p class="fs-5 fw-bold text-success" x-text="formatCurrency(selectedInvoice.paid_amount)"></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="fw-bold small text-muted text-uppercase mb-1">Remaining Balance Due</p>
                                    <p class="fs-4 fw-bolder text-primary" x-text="formatCurrency(selectedInvoice.due_amount)"></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="fw-bold small text-muted text-uppercase mb-1">Status</p>
                                    <p class="fw-medium text-body-emphasis" x-text="selectedInvoice.status.toUpperCase().replace('_', ' ')"></p>
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
@endsection
