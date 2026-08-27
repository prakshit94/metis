@extends('layouts.app')
@section('title', '🚨 Complaints Management')
@section('page', 'complaints')

@section('content')
<div class="complaints-management" x-data="complaintsTable()" x-init="init()">

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 fw-bold"><i class="bi bi-headset text-danger me-2"></i>Order Complaints</h1>
            <p class="text-muted mb-0 small">Manage customer issues, delivery delays, and service tickets.</p>
        </div>
        @can('complaints.create')
        <div>
            <button class="btn btn-primary" @click="openCreateModal()">
                <i class="bi bi-plus-lg me-1"></i>New Complaint
            </button>
        </div>
        @endcan
    </div>

    {{-- Stats Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stats-icon bg-primary bg-opacity-10 text-primary fs-3 rounded-3 p-2 flex-shrink-0"><i class="bi bi-headset"></i></div>
                        <div>
                            <p class="mb-1 small text-muted">Total Complaints</p>
                            <div class="h4 mb-0 fw-bold" x-text="stats.total || '—'"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stats-icon bg-warning bg-opacity-10 text-warning fs-3 rounded-3 p-2 flex-shrink-0"><i class="bi bi-exclamation-circle"></i></div>
                        <div>
                            <p class="mb-1 small text-muted">Open</p>
                            <div class="h4 mb-0 fw-bold text-warning" x-text="stats.open || '—'"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stats-icon bg-info bg-opacity-10 text-info fs-3 rounded-3 p-2 flex-shrink-0"><i class="bi bi-arrow-repeat"></i></div>
                        <div>
                            <p class="mb-1 small text-muted">In Progress</p>
                            <div class="h4 mb-0 fw-bold text-info" x-text="stats.in_progress || '—'"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stats-icon bg-success bg-opacity-10 text-success fs-3 rounded-3 p-2 flex-shrink-0"><i class="bi bi-check-circle"></i></div>
                        <div>
                            <p class="mb-1 small text-muted">Resolved</p>
                            <div class="h4 mb-0 fw-bold text-success" x-text="stats.resolved || '—'"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Data Table Card --}}
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center g-2">
                <div class="col"><h2 class="h5 card-title mb-0">Complaints Overview</h2></div>
                <div class="col-auto">
                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                        <div class="position-relative">
                            <input type="search" class="form-control form-control-sm pe-4" placeholder="Search CMP / Subject…" x-model="searchQuery" @input.debounce.400ms="filterComplaints()" style="width:220px;">
                            <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted small"></i>
                        </div>
                        <select class="form-select form-select-sm" x-model="statusFilter" @change="filterComplaints()" style="width:140px;">
                            <option value="">All Statuses</option>
                            <option value="open">Open</option>
                            <option value="in_progress">In Progress</option>
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                        </select>
                        <select class="form-select form-select-sm" x-model="priorityFilter" @change="filterComplaints()" style="width:140px;">
                            <option value="">All Priorities</option>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                        <button class="btn btn-sm btn-outline-secondary" @click="clearFilters()" title="Clear filters"><i class="bi bi-x-circle"></i></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-0">

            {{-- Bulk Actions Bar --}}
            <div class="px-3 py-2 border-bottom bg-primary bg-opacity-10" x-show="selectedComplaints.length > 0" x-transition x-cloak>
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span class="fw-medium text-primary small">
                        <i class="bi bi-check-circle-fill me-1"></i>
                        <strong x-text="selectedComplaints.length"></strong> complaint(s) selected
                    </span>
                    <div class="d-flex gap-2 flex-wrap">
                        @can('complaints.edit')
                        <button class="btn btn-sm btn-success" @click="bulkAction('resolve')" :disabled="isSubmitting">
                            <i class="bi bi-check2-all me-1"></i>Resolve Selected
                        </button>
                        <button class="btn btn-sm btn-secondary" @click="bulkAction('close')" :disabled="isSubmitting">
                            <i class="bi bi-x-circle me-1"></i>Close Selected
                        </button>
                        @endcan
                        @can('complaints.delete')
                        <button class="btn btn-sm btn-outline-danger" @click="bulkAction('delete')" :disabled="isSubmitting">
                            <i class="bi bi-trash me-1"></i>Delete
                        </button>
                        @endcan
                        <button class="btn btn-sm btn-outline-secondary" @click="selectedComplaints = []"><i class="bi bi-x-lg"></i></button>
                    </div>
                </div>
            </div>

            {{-- Loading --}}
            <div class="text-center py-5" x-show="isLoading">
                <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading…</span></div>
            </div>

            {{-- Table --}}
            <div class="table-responsive" x-show="!isLoading" x-cloak>
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            @canany(['complaints.edit', 'complaints.delete'])
                            <th style="width:40px;">
                                <input type="checkbox" class="form-check-input border-secondary" :checked="allSelected" @change="toggleAll($event.target.checked)" style="cursor:pointer;">
                            </th>
                            @endcanany
                            <th role="button" @click="sortBy('complaint_number')" class="user-select-none">
                                <i class="bi bi-hash me-1 text-secondary"></i>CMP # <i class="bi ms-1" :class="sortField==='complaint_number'?(sortDirection==='asc'?'bi-sort-up':'bi-sort-down'):'bi-sort'"></i>
                            </th>
                            <th><i class="bi bi-cart me-1 text-secondary"></i>Order</th>
                            <th><i class="bi bi-person me-1 text-secondary"></i>Customer</th>
                            <th><i class="bi bi-tag me-1 text-secondary"></i>Category</th>
                            <th role="button" @click="sortBy('priority')" class="user-select-none">
                                <i class="bi bi-exclamation-triangle me-1 text-secondary"></i>Priority <i class="bi ms-1" :class="sortField==='priority'?(sortDirection==='asc'?'bi-sort-up':'bi-sort-down'):'bi-sort'"></i>
                            </th>
                            <th role="button" @click="sortBy('status')" class="user-select-none">
                                <i class="bi bi-record-circle me-1 text-secondary"></i>Status <i class="bi ms-1" :class="sortField==='status'?(sortDirection==='asc'?'bi-sort-up':'bi-sort-down'):'bi-sort'"></i>
                            </th>
                            <th role="button" @click="sortBy('created_at')" class="user-select-none">
                                <i class="bi bi-calendar-event me-1 text-secondary"></i>Created <i class="bi ms-1" :class="sortField==='created_at'?(sortDirection==='asc'?'bi-sort-up':'bi-sort-down'):'bi-sort'"></i>
                            </th>
                            <th style="width:100px;"><i class="bi bi-lightning-charge me-1 text-secondary"></i>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="cmp in complaints" :key="cmp.id">
                            <tr :class="{ 'table-active': selectedComplaints.includes(String(cmp.id)) }">
                                @canany(['complaints.edit', 'complaints.delete'])
                                <td>
                                    <input type="checkbox" class="form-check-input border-secondary" :value="String(cmp.id)" x-model="selectedComplaints" style="cursor:pointer;">
                                </td>
                                @endcanany
                                <td><span class="fw-semibold font-monospace small" x-text="cmp.complaint_number"></span></td>
                                <td><span class="text-muted font-monospace small" x-text="cmp.order?.order_no || 'N/A'"></span></td>
                                <td><span class="small" x-text="cmp.customer?.name || 'N/A'"></span></td>
                                <td><span class="badge bg-secondary bg-opacity-25 text-body small text-capitalize" x-text="(cmp.category||'').replace(/_/g,' ')"></span></td>
                                <td>
                                    <span class="badge small" :class="getPriorityClass(cmp.priority)" x-text="(cmp.priority || 'low').toUpperCase()"></span>
                                </td>
                                <td>
                                    <span class="badge small text-white px-2 py-1"
                                          :style="`background-color: ${getStatusColor(cmp.status)};`"
                                          x-text="getStatusLabel(cmp.status)"></span>
                                </td>
                                <td><span class="small text-muted" x-text="formatDate(cmp.created_at)"></span></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" @click="viewComplaint(cmp)">
                                        <i class="bi bi-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                        </template>
                        <template x-if="!isLoading && complaints.length === 0">
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                                    <p class="mb-0">No complaints found.</p>
                                    <p class="small text-muted mt-1">Use filters to refine or raise a new complaint.</p>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top" x-show="totalPages > 1" x-cloak>
                <div class="text-muted small">
                    Page <span x-text="currentPage"></span> of <span x-text="totalPages"></span>
                    &nbsp;·&nbsp; <span x-text="totalComplaints"></span> total
                </div>
                <nav>
                    <ul class="pagination pagination-sm mb-0 gap-1">
                        <li class="page-item" :class="{ disabled: currentPage === 1 }">
                            <a class="page-link rounded" href="#" @click.prevent="goToPage(currentPage - 1)">‹</a>
                        </li>
                        <template x-for="(page, idx) in visiblePages" :key="idx">
                            <li class="page-item" :class="{ active: page === currentPage, disabled: page === '...' }">
                                <a class="page-link rounded" href="#" @click.prevent="page !== '...' && goToPage(page)" x-text="page"></a>
                            </li>
                        </template>
                        <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                            <a class="page-link rounded" href="#" @click.prevent="goToPage(currentPage + 1)">›</a>
                        </li>
                    </ul>
                </nav>
            </div>

        </div>
    </div>

    {{-- Create/Edit Complaint Modal --}}
    <div class="modal fade" id="complaintModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content">
                <form @submit.prevent="saveComplaint">
                    <div class="modal-header">
                        <h5 class="modal-title" x-text="isEditing ? 'Edit Complaint' : 'New Complaint'"></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <!-- Order Lookup -->
                            <div class="col-md-12" x-show="!isEditing">
                                <label class="form-label fw-semibold text-primary">Lookup Order (by ID or Mobile)</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" x-model="searchQueryOrder" placeholder="e.g. ORD-0001 or 9876543210" @keydown.enter.prevent="searchOrders">
                                    <button class="btn btn-primary" type="button" @click="searchOrders" :disabled="isSearchingOrders">
                                        <span x-show="!isSearchingOrders"><i class="bi bi-search me-1"></i>Search</span>
                                        <span x-show="isSearchingOrders"><span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span></span>
                                    </button>
                                </div>
                                <div class="mt-2" x-show="fetchedOrders.length > 1" x-transition x-cloak>
                                    <label class="form-label small text-muted">Multiple orders found. Select one:</label>
                                    <select class="form-select" @change="selectOrder($event.target.value)">
                                        <option value="" disabled selected>Select an order...</option>
                                        <template x-for="order in fetchedOrders" :key="order.id">
                                        <option :value="order.id" x-text="`${order.order_no} — ₹${parseFloat(order.net_amount || order.total_amount || 0).toFixed(2)} (${new Date(order.created_at).toLocaleDateString('en-IN')})`"></option>
                                        </template>
                                    </select>
                                </div>
                                <div class="mt-1 text-danger small" x-show="searchOrderError" x-text="searchOrderError"></div>
                            </div>
                            
                            <!-- Injected Order Details Card -->
                            <div class="col-12" x-show="selectedOrderDetails" x-transition x-cloak>
                                <div class="card border-primary shadow-sm mb-3">
                                    <div class="card-header bg-primary bg-opacity-10 py-2 d-flex justify-content-between align-items-center">
                                        <span class="fw-bold text-primary"><i class="bi bi-receipt me-1"></i>Selected Order Preview</span>
                                        <button type="button" class="btn-close btn-sm" @click="selectedOrderDetails = null; form.order_no = ''; form.customer_id = '';" title="Clear Selection"></button>
                                    </div>
                                    <div class="card-body p-0">
                                        <!-- DETAILS FROM ORDERS INDEX -->
                                        <template x-if="selectedOrderDetails">
                                        <div class="p-4 bg-body-tertiary">
                                            <div class="row g-3 mb-4">
                                                <div class="col-sm-4">
                                                    <div class="card h-100 border-0 shadow-sm rounded-4">
                                                        <div class="card-body p-3 d-flex align-items-center gap-3">
                                                            <div class="text-bg-primary-subtle text-primary-emphasis p-2 rounded-3"><i class="bi bi-credit-card fs-5"></i></div>
                                                            <div>
                                                                <p class="small text-muted mb-0 fw-semibold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">Payment</p>
                                                                <p class="fw-bold mb-0 text-body-emphasis" x-text="selectedOrderDetails.paymentMethod || 'N/A'"></p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-sm-4">
                                                    <div class="card h-100 border-0 shadow-sm rounded-4">
                                                        <div class="card-body p-3 d-flex align-items-center gap-3">
                                                            <div class="text-bg-success-subtle text-success-emphasis p-2 rounded-3"><i class="bi bi-tag fs-5"></i></div>
                                                            <div>
                                                                <p class="small text-muted mb-0 fw-semibold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">Status</p>
                                                                <p class="fw-bold mb-0 text-body-emphasis" x-text="selectedOrderDetails.statusLabel || selectedOrderDetails.status"></p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-sm-4">
                                                    <div class="card h-100 border-0 shadow-sm rounded-4">
                                                        <div class="card-body p-3 d-flex align-items-center gap-3">
                                                            <div class="text-bg-info-subtle text-info-emphasis p-2 rounded-3"><i class="bi bi-calendar3 fs-5"></i></div>
                                                            <div>
                                                                <p class="small text-muted mb-0 fw-semibold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">Order Date</p>
                                                                <p class="fw-bold mb-0 text-body-emphasis" x-text="selectedOrderDetails.orderDate ? formatDate(selectedOrderDetails.orderDate) : 'N/A'"></p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="card border-0 shadow-sm rounded-4 mb-4">
                                                <div class="card-header bg-body border-bottom-0 pt-4 pb-0 px-4">
                                                    <h6 class="fw-bold mb-0 text-body-emphasis d-flex align-items-center gap-2">
                                                        <i class="bi bi-person-hearts text-danger fs-5"></i> Customer & Fulfillment
                                                    </h6>
                                                </div>
                                                <div class="card-body p-4">
                                                    <div class="row g-4">
                                                        <div class="col-md-6 border-end-md">
                                                            <div class="d-flex align-items-start gap-3 mb-3">
                                                                <template x-if="selectedOrderDetails.customer?.avatar">
                                                                    <img x-bind:src="selectedOrderDetails.customer.avatar" class="rounded-circle shadow-sm" width="48" height="48" alt="Customer">
                                                                </template>
                                                                <div>
                                                                    <h6 class="fw-bold mb-1" x-text="selectedOrderDetails.customer?.name || 'N/A'"></h6>
                                                                    <p class="text-muted small mb-1 d-flex align-items-center gap-1"><i class="bi bi-telephone"></i> <span x-text="selectedOrderDetails.customer?.phone || 'N/A'"></span></p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <p class="fw-bold small text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Shipping Address</p>
                                                                <p class="small mb-0 text-body-emphasis" x-text="selectedOrderDetails.shippingAddress ? selectedOrderDetails.shippingAddress.formatted : 'N/A'"></p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                                                <div class="card-header bg-body border-bottom pt-4 pb-3 px-4 d-flex justify-content-between align-items-center">
                                                    <h6 class="fw-bold mb-0 text-body-emphasis d-flex align-items-center gap-2">
                                                        <i class="bi bi-box-seam text-primary fs-5"></i> Order Items
                                                    </h6>
                                                    <span class="badge text-bg-primary-subtle text-primary-emphasis rounded-pill px-3" x-text="`${selectedOrderDetails.itemCount || (selectedOrderDetails.items ? selectedOrderDetails.items.length : 0)} Items`"></span>
                                                </div>
                                                <div class="table-responsive">
                                                    <table class="table table-borderless table-hover align-middle mb-0 text-nowrap">
                                                        <thead class="bg-body-tertiary">
                                                            <tr>
                                                                <th class="fw-semibold text-muted small py-3 ps-4">Product Details</th>
                                                                <th class="fw-semibold text-muted small py-3 text-end">Price</th>
                                                                <th class="fw-semibold text-muted small py-3 text-center">Qty</th>
                                                                <th class="fw-semibold text-muted small py-3 text-end pe-4">Total</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <template x-for="(item, idx) in selectedOrderDetails.items" :key="idx">
                                                                <tr class="border-bottom">
                                                                    <td class="ps-4 py-3">
                                                                        <div class="d-flex align-items-center gap-3">
                                                                            <div>
                                                                                <p class="fw-bold text-body-emphasis mb-0" x-text="item.name"></p>
                                                                                <p class="text-muted small mb-0 font-monospace" style="font-size: 0.75rem;" x-text="item.sku || 'No SKU'"></p>
                                                                            </div>
                                                                        </div>
                                                                    </td>
                                                                    <td class="text-end py-3">
                                                                        <span class="text-body-emphasis fw-medium" x-text="`₹ ${parseFloat(item.price || 0).toFixed(2)}`"></span>
                                                                    </td>
                                                                    <td class="text-center py-3">
                                                                        <span class="badge bg-secondary bg-opacity-10 text-body-emphasis px-2 py-1 rounded-3" x-text="item.quantity || 0"></span>
                                                                    </td>
                                                                    <td class="text-end pe-4 py-3">
                                                                        <span class="fw-bold text-primary" x-text="`₹ ${((parseFloat(item.price || 0) * parseFloat(item.quantity || 0)) - parseFloat(item.discount || 0) + parseFloat(item.tax || 0)).toFixed(2)}`"></span>
                                                                    </td>
                                                                </tr>
                                                            </template>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                        </template>
                                        <!-- END DETAILS -->
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Hidden inputs for linking order to complaint -->
                            <div class="col-md-6" x-show="!selectedOrderDetails && !isEditing">
                                <label class="form-label">Order No (Manual Entry) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" x-model="form.order_no" :required="!isEditing && !selectedOrderDetails" placeholder="e.g. ORD-0001">
                            </div>
                            <div class="col-md-6" x-show="!selectedOrderDetails && !isEditing">
                                <label class="form-label">Customer ID (Optional)</label>
                                <input type="number" class="form-control" x-model="form.customer_id">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Category <span class="text-danger">*</span></label>
                                <select class="form-select" x-model="form.category" required>
                                    <option value="delivery_delay">Delivery Delay</option>
                                    <option value="damaged_item">Damaged Item</option>
                                    <option value="missing_item">Missing Item</option>
                                    <option value="wrong_item">Wrong Item</option>
                                    <option value="payment_issue">Payment Issue</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Priority <span class="text-danger">*</span></label>
                                <select class="form-select" x-model="form.priority" required>
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Subject <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" x-model="form.subject" required :readonly="isEditing">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Description <span class="text-danger">*</span></label>
                                <textarea class="form-control" rows="3" x-model="form.description" required :readonly="isEditing"></textarea>
                            </div>
                            
                            <template x-if="isEditing">
                                <div class="col-md-12 border-top pt-3 mt-3">
                                    <label class="form-label">Status <span class="text-danger">*</span></label>
                                    <select class="form-select mb-3" x-model="form.status" required>
                                        <option value="open">Open</option>
                                        <option value="in_progress">In Progress</option>
                                        <option value="resolved">Resolved</option>
                                        <option value="closed">Closed</option>
                                    </select>
                                    <label class="form-label">Resolution Notes</label>
                                    <textarea class="form-control" rows="3" x-model="form.resolution_notes"></textarea>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        @can('complaints.create')
                        <button type="submit" class="btn btn-primary" x-show="!isEditing" :disabled="isSubmitting">
                            <span x-show="isSubmitting" class="spinner-border spinner-border-sm me-1"></span>
                            Save
                        </button>
                        @endcan
                        @can('complaints.edit')
                        <button type="submit" class="btn btn-primary" x-show="isEditing" :disabled="isSubmitting">
                            <span x-show="isSubmitting" class="spinner-border spinner-border-sm me-1"></span>
                            Update
                        </button>
                        @endcan
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('complaintsTable', () => ({
            complaints: [],
            stats: { total: 0, open: 0, in_progress: 0, resolved: 0 },
            isLoading: false,
            isSubmitting: false,
            searchQuery: '',
            statusFilter: '',
            priorityFilter: '',
            sortField: 'created_at',
            sortDirection: 'desc',
            currentPage: 1,
            totalPages: 1,
            totalComplaints: 0,
            selectedComplaints: [],
            isEditing: false,
            modalInstance: null,
            form: {
                id: null, order_no: '', customer_id: '', category: 'other',
                priority: 'medium', subject: '', description: '',
                status: 'open', resolution_notes: ''
            },

            // --- Order Lookup Variables ---
            searchQueryOrder: '',
            fetchedOrders: [],
            isSearchingOrders: false,
            searchOrderError: '',
            selectedOrderDetails: null,

            // ── Helpers ────────────────────────────────────────────────────────
            csrf() {
                return document.querySelector('meta[name="csrf-token"]')?.content || '';
            },
            async api(url, options = {}) {
                const method = (options.method || 'GET').toUpperCase();
                const headers = {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.csrf(),
                    ...(options.body ? { 'Content-Type': 'application/json' } : {}),
                    ...(options.headers || {}),
                };
                const res = await fetch(url, { ...options, method, headers });
                if (!res.ok) {
                    const err = await res.json().catch(() => ({ message: `HTTP ${res.status}` }));
                    throw Object.assign(new Error(err.message || `HTTP ${res.status}`), { status: res.status, data: err });
                }
                return res.json();
            },

            // ── Computed ───────────────────────────────────────────────────────
            get allSelected() {
                return this.complaints.length > 0 && this.selectedComplaints.length === this.complaints.length;
            },
            get visiblePages() {
                const delta = 2, range = [];
                for (let i = Math.max(2, this.currentPage - delta); i <= Math.min(this.totalPages - 1, this.currentPage + delta); i++) range.push(i);
                if (this.currentPage - delta > 2) range.unshift('...');
                if (this.currentPage + delta < this.totalPages - 1) range.push('...');
                range.unshift(1);
                if (this.totalPages > 1) range.push(this.totalPages);
                return range;
            },

            // ── Lifecycle ──────────────────────────────────────────────────────
            init() {
                this.modalInstance = new bootstrap.Modal(document.getElementById('complaintModal'));
                this.fetchComplaints();
                this.fetchStats();

                // Auto-open modal from URL params (e.g. from "Raise Complaint" button)
                const urlParams = new URLSearchParams(window.location.search);
                const orderNo = urlParams.get('order_no');
                const customerId = urlParams.get('customer_id');
                if (orderNo) {
                    this.$nextTick(() => {
                        this.isEditing = false;
                        this.form = {
                            id: null,
                            order_no: orderNo,
                            customer_id: customerId || '',
                            category: urlParams.get('category') || 'other',
                            priority: urlParams.get('priority') || 'medium',
                            subject: urlParams.get('subject') || '',
                            description: '',
                            status: 'open',
                            resolution_notes: ''
                        };
                        this.searchQueryOrder = orderNo;
                        this.modalInstance.show();
                        window.history.replaceState({}, '', window.location.pathname);
                    });
                }
            },

            // ── Complaints API ─────────────────────────────────────────────────
            async fetchComplaints() {
                this.isLoading = true;
                try {
                    const params = new URLSearchParams({
                        page: this.currentPage,
                        sort_by: this.sortField,
                        sort_direction: this.sortDirection
                    });
                    if (this.searchQuery)   params.append('search',   this.searchQuery);
                    if (this.statusFilter)  params.append('status',   this.statusFilter);
                    if (this.priorityFilter) params.append('priority', this.priorityFilter);

                    const data = await this.api(`/api/complaints?${params.toString()}`);
                    this.complaints      = data.data;
                    this.currentPage     = data.meta.current_page;
                    this.totalPages      = data.meta.last_page;
                    this.totalComplaints = data.meta.total;
                    this.stats.total     = this.totalComplaints;
                } catch (e) {
                    console.error('fetchComplaints error:', e);
                    window.Swal.fire('Error', 'Failed to load complaints', 'error');
                }
                this.isLoading = false;
            },

            async fetchStats() {
                try {
                    const data = await this.api('/api/complaints/stats');
                    this.stats.total       = data.total;
                    this.stats.open        = data.open;
                    this.stats.in_progress = data.in_progress;
                    this.stats.resolved    = data.resolved;
                } catch (e) {
                    console.warn('fetchStats error:', e);
                }
            },

            filterComplaints() { this.currentPage = 1; this.fetchComplaints(); },
            clearFilters() {
                this.searchQuery = ''; this.statusFilter = ''; this.priorityFilter = '';
                this.filterComplaints();
            },
            sortBy(field) {
                if (this.sortField === field) {
                    this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
                } else {
                    this.sortField = field; this.sortDirection = 'desc';
                }
                this.fetchComplaints();
            },
            goToPage(page) {
                if (page >= 1 && page <= this.totalPages) { this.currentPage = page; this.fetchComplaints(); }
            },
            toggleAll(checked) {
                this.selectedComplaints = checked ? this.complaints.map(c => String(c.id)) : [];
            },

            // ── Order Lookup ───────────────────────────────────────────────────
            async searchOrders() {
                if (!this.searchQueryOrder.trim()) return;
                this.isSearchingOrders = true;
                this.searchOrderError  = '';
                this.fetchedOrders     = [];
                this.selectedOrderDetails = null;

                try {
                    // OrderController@index returns { orders: { data: [...] }, stats: {...} }
                    const data   = await this.api(`/api/orders?search=${encodeURIComponent(this.searchQueryOrder.trim())}&limit=50`);
                    const orders = data.orders?.data ?? data.data ?? [];

                    if (!orders.length) {
                        this.searchOrderError = 'No orders found matching this query.';
                    } else if (orders.length === 1) {
                        await this.selectOrder(orders[0].id);
                    } else {
                        this.fetchedOrders = orders;
                    }
                } catch (e) {
                    this.searchOrderError = e.status === 403
                        ? 'Permission denied — you cannot search orders.'
                        : 'Failed to search orders. Please try again.';
                    console.error('searchOrders error:', e);
                }
                this.isSearchingOrders = false;
            },

            async selectOrder(orderId) {
                if (!orderId) return;
                this.isSearchingOrders = true;
                try {
                    // OrderController@show returns { order: rawData }
                    const data = await this.api(`/api/orders/${orderId}`);
                    const raw  = data.order || data;
                    this.selectedOrderDetails = this.mapRawOrder(raw);
                    this.form.order_no    = this.selectedOrderDetails.orderNumber;
                    this.form.customer_id = raw.party_id || '';
                    this.fetchedOrders    = [];
                } catch (e) {
                    this.searchOrderError = 'Failed to fetch order details.';
                    console.error('selectOrder error:', e);
                }
                this.isSearchingOrders = false;
            },

            mapRawOrder(o) {
                if (!o) return null;
                const fmt = v => parseFloat(v ?? 0) || 0;
                const fmtAddr = (o, type) => {
                    const addr = type === 'shipping' ? o.shipping_address : o.billing_address;
                    if (!addr) return null;
                    const parts = [addr.address_line_1, addr.address_line_2, addr.village_name, addr.taluka, addr.district, addr.city, addr.state, addr.pincode].filter(Boolean);
                    return { formatted: parts.join(', ') || 'N/A' };
                };
                const invoice  = o.invoice || null;
                const invPmts  = invoice?.payments ?? [];
                const paid     = invPmts.filter(p => p.status === 'completed').reduce((s, p) => s + fmt(p.amount), 0);
                const netInv   = fmt(invoice?.total_amount);
                const allPmts  = o.payments || invPmts;
                const lastPmt  = allPmts.slice().reverse().find(p => p.payment_method);
                const pmtLabel = lastPmt ? lastPmt.payment_method.toUpperCase().replace(/_/g, ' ') : (invoice ? 'PENDING PAYMENT' : 'NOT RECORDED');

                return {
                    id: o.id,
                    orderNumber: o.order_no,
                    type: o.type || 'sale',
                    orderDate: o.order_date,
                    status: o.lifecycle_status || o.status,
                    statusLabel: o.status_label || (o.lifecycle_status || o.status || '').charAt(0).toUpperCase() + (o.lifecycle_status || o.status || '').slice(1).replace(/_/g, ' '),
                    customer: {
                        id: o.party_id || null,
                        name: o.party ? `${o.party.firstname || ''} ${o.party.lastname || ''}`.trim() : 'N/A',
                        email: o.party?.email || 'N/A',
                        avatar: o.party?.avatar || '/assets/images/default_avatar.jpeg',
                        phone: o.party?.phone || 'N/A',
                        company: o.party?.company_name || '',
                    },
                    warehouse: o.warehouse ? {
                        name: o.warehouse.name || 'N/A',
                        address: [o.warehouse.address_line_1, o.warehouse.city, o.warehouse.state].filter(Boolean).join(', ') || 'N/A'
                    } : null,
                    shippingAddress: fmtAddr(o, 'shipping'),
                    billingAddress:  fmtAddr(o, 'billing'),
                    invoice: invoice ? { number: invoice.invoice_no || 'N/A', date: invoice.invoice_date || null, status: invoice.status || 'N/A', total: netInv, paid, due: Math.max(0, netInv - paid) } : null,
                    items: (o.items || []).map(item => ({
                        name:  item.product?.name || 'Unknown Product',
                        sku:   item.product?.sku  || '',
                        image: item.product?.image_path ? `/storage/${item.product.image_path}` : null,
                        quantity: item.quantity,
                        price:    fmt(item.unit_price),
                        discount: fmt(item.discount_amount),
                        discountBadgeLabel: fmt(item.discount_amount) > 0 ? `-₹${fmt(item.discount_amount).toFixed(2)}` : '',
                        tax: fmt(item.tax_amount), taxRate: item.tax_rate || 0, net: fmt(item.total_amount)
                    })),
                    itemCount: o.items_count || o.items?.length || 0,
                    total:         fmt(o.net_amount),
                    subtotal:      (o.items || []).reduce((s, i) => s + fmt(i.unit_price) * fmt(i.quantity), 0),
                    taxTotal:      fmt(o.tax_amount),
                    discountTotal: fmt(o.discount_amount),
                    paymentMethod: pmtLabel,
                    couponCode:    o.coupon_code || '',
                    payments: (o.payments || []).map(p => ({
                        id: p.id, amount: fmt(p.amount), method: p.payment_method || 'N/A',
                        status: p.status || 'N/A', date: p.payment_date || null, transactionId: p.transaction_id || 'N/A'
                    })),
                    original: o
                };
            },

            // ── Formatters ─────────────────────────────────────────────────────
            formatDate(str) {
                if (!str) return '—';
                return new Date(str).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
            },
            formatCurrency(val) {
                return parseFloat(val || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },
            formatDateTime(dateStr) {
                if (!dateStr) return 'N/A';
                return new Date(dateStr).toLocaleString('en-IN', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true });
            },
            getStatusColor(status) {
                return { open: '#f59e0b', in_progress: '#0ea5e9', resolved: '#10b981', closed: '#6b7280' }[status] || '#6b7280';
            },
            getStatusLabel(status) { return (status || '').replace(/_/g, ' ').toUpperCase(); },
            getPriorityClass(priority) {
                return {
                    low:    'bg-secondary bg-opacity-25 text-body',
                    medium: 'bg-info bg-opacity-25 text-info',
                    high:   'bg-warning bg-opacity-25 text-warning',
                    urgent: 'bg-danger bg-opacity-25 text-danger'
                }[priority] || 'bg-secondary bg-opacity-25 text-body';
            },
            getStatusTheme(status) {
                return { pending:'warning', pending_confirmation:'info', confirmed:'primary', processing:'secondary', ready_to_ship:'dark', dispatched:'info', shipped:'info', delivered:'success', cancelled:'danger', returned:'danger' }[status] || 'secondary';
            },

            // ── Modal ──────────────────────────────────────────────────────────
            openCreateModal() {
                this.isEditing = false;
                this.form = { id: null, order_no: '', customer_id: '', category: 'other', priority: 'medium', subject: '', description: '', status: 'open', resolution_notes: '' };
                this.searchQueryOrder = ''; this.fetchedOrders = []; this.selectedOrderDetails = null; this.searchOrderError = '';
                this.modalInstance.show();
            },
            viewComplaint(cmp) {
                this.isEditing = true;
                this.form = { ...cmp };
                this.form.order_no = cmp.order?.order_no || '';
                this.searchQueryOrder = ''; this.fetchedOrders = []; this.selectedOrderDetails = null; this.searchOrderError = '';
                this.modalInstance.show();
            },

            async saveComplaint() {
                this.isSubmitting = true;
                try {
                    if (this.isEditing) {
                        await this.api(`/api/complaints/${this.form.id}`, { method: 'PUT', body: JSON.stringify(this.form) });
                        window.Swal.fire('Updated', 'Complaint updated successfully.', 'success');
                    } else {
                        await this.api('/api/complaints', { method: 'POST', body: JSON.stringify(this.form) });
                        window.Swal.fire('Created', 'Complaint logged successfully.', 'success');
                    }
                    this.modalInstance.hide();
                    this.fetchComplaints();
                    this.fetchStats();
                } catch (e) {
                    const msg = e.data?.message || e.data?.errors ? Object.values(e.data.errors || {}).flat().join(' ') : 'Failed to save complaint.';
                    window.Swal.fire('Error', msg, 'error');
                }
                this.isSubmitting = false;
            },

            async bulkAction(action) {
                if (!this.selectedComplaints.length) return;
                const confirmed = await window.Swal.fire({
                    title: 'Are you sure?',
                    text: `You are about to ${action} ${this.selectedComplaints.length} complaint(s).`,
                    icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, proceed!'
                });
                if (!confirmed.isConfirmed) return;
                this.isSubmitting = true;
                try {
                    await this.api('/api/complaints/bulk-action', {
                        method: 'POST',
                        body: JSON.stringify({ action, ids: this.selectedComplaints })
                    });
                    window.Swal.fire('Success', 'Bulk action completed.', 'success');
                    this.selectedComplaints = [];
                    this.fetchComplaints();
                    this.fetchStats();
                } catch (e) {
                    window.Swal.fire('Error', e.data?.message || 'Bulk action failed.', 'error');
                }
                this.isSubmitting = false;
            }
        }));
    });
</script>
@endpush

