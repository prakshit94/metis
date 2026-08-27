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
                        @can('complaints.view')
                        <button class="btn btn-sm btn-outline-primary" @click="bulkExport()" title="Export filtered to CSV" :disabled="isLoading">
                            <i class="bi bi-file-earmark-excel"></i> Export
                        </button>
                        @endcan
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
                        <select class="form-select form-select-sm w-auto" x-model="bulkAssignTo" @change="if(bulkAssignTo) { bulkAction('assign', {assigned_to: bulkAssignTo}); bulkAssignTo = ''; }" :disabled="isSubmitting">
                            <option value="">Assign To...</option>
                            <template x-for="user in assignableUsers" :key="user.id">
                                <option :value="user.id" x-text="user.name"></option>
                            </template>
                        </select>
                        <select class="form-select form-select-sm w-auto" x-model="bulkPriority" @change="if(bulkPriority) { bulkAction('change_priority', {priority: bulkPriority}); bulkPriority = ''; }" :disabled="isSubmitting">
                            <option value="">Set Priority...</option>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                        <button class="btn btn-sm btn-success" @click="bulkAction('resolve')" :disabled="isSubmitting">
                            <i class="bi bi-check2-all me-1"></i>Resolve
                        </button>
                        <button class="btn btn-sm btn-secondary" @click="bulkAction('close')" :disabled="isSubmitting">
                            <i class="bi bi-x-circle me-1"></i>Close
                        </button>
                        @endcan
                        @can('complaints.delete')
                        <button class="btn btn-sm btn-outline-danger" @click="bulkAction('delete')" :disabled="isSubmitting">
                            <i class="bi bi-trash me-1"></i>Delete
                        </button>
                        @endcan
                        @can('complaints.view')
                        <button class="btn btn-sm btn-outline-primary" @click="exportSelected()" :disabled="isSubmitting">
                            <i class="bi bi-file-earmark-excel me-1"></i>Export
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
            <div class="table-responsive" style="overflow: visible;" x-show="!isLoading" x-cloak>
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
                                    <span class="badge small px-2 py-1"
                                          :class="`text-bg-${getStatusColor(cmp.status)}`"
                                          x-text="getStatusLabel(cmp.status)"></span>
                                </td>
                                <td><span class="small text-muted" x-text="formatDate(cmp.created_at)"></span></td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li>
                                                <a class="dropdown-item" href="#" @click.prevent="viewComplaint(cmp)">
                                                    <i class="bi bi-eye me-2 text-primary"></i>View Details
                                                </a>
                                            </li>
                                            @can('complaints.edit')
                                            <li>
                                                <a class="dropdown-item" href="#" @click.prevent="viewComplaint(cmp)">
                                                    <i class="bi bi-pencil me-2 text-warning"></i>Edit
                                                </a>
                                            </li>
                                            @endcan
                                            @can('complaints.delete')
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item text-danger" href="#" @click.prevent="deleteComplaint(cmp)">
                                                    <i class="bi bi-trash me-2"></i>Delete
                                                </a>
                                            </li>
                                            @endcan
                                        </ul>
                                    </div>
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
            <div class="modal-content border-0 shadow-lg">
                <form @submit.prevent="saveComplaint" class="d-flex flex-column h-100 bg-body rounded-4 overflow-hidden w-100">
                    <div class="modal-header bg-body border-bottom py-3 px-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 shadow-sm" style="width:45px;height:45px;">
                            <i class="bi bi-headset fs-5"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold mb-0 text-body-emphasis" x-text="isEditing ? 'Edit Complaint' : 'New Complaint'"></h5>
                            <div class="d-flex align-items-center gap-2 mt-1">
                                <span class="badge rounded-pill bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25" x-show="!isEditing">Search an order below</span>
                                <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25" x-show="isEditing" x-text="'#' + (form.complaint_number || form.id)"></span>
                                <template x-if="isEditing && form.status">
                                    <span class="badge rounded-pill" :class="`bg-${getStatusColor(form.status)} bg-opacity-10 text-${getStatusColor(form.status)} border border-${getStatusColor(form.status)} border-opacity-25`" x-text="getStatusLabel(form.status)"></span>
                                </template>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body p-4 bg-body-tertiary d-flex flex-column" style="overflow: hidden;">
                    <!-- 1. Order Search (Create Mode Only) -->
                    <div x-show="!isEditing" class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-body p-4">
                            <label class="form-label fw-bold text-body-emphasis mb-3"><i class="bi bi-search me-2 text-primary"></i>Lookup Order</label>
                            <div class="input-group input-group-lg shadow-sm rounded-4 overflow-hidden">
                                <input type="text" class="form-control border-0 bg-body-secondary px-4" x-model="searchQueryOrder" placeholder="Search by Order ID (e.g. ORD-0001) or Mobile..." @keydown.enter.prevent="searchOrders">
                                <button class="btn btn-primary px-4 fw-semibold" type="button" @click="searchOrders" :disabled="isSearchingOrders">
                                    <span x-show="!isSearchingOrders">Search</span>
                                    <span x-show="isSearchingOrders"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span></span>
                                </button>
                            </div>
                            
                            <div class="mt-2 text-danger small fw-medium px-2" x-show="searchOrderError" x-text="searchOrderError"></div>
                            
                            <!-- Search Results List -->
                            <template x-if="fetchedOrders && fetchedOrders.length > 0">
                                <div class="mt-4 border rounded-3 overflow-hidden shadow-sm bg-body">
                                    <ul class="list-group list-group-flush">
                                        <template x-for="ord in fetchedOrders" :key="ord.id">
                                            <li class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 cursor-pointer" @click="selectOrderForComplaint(ord.id)">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px;">
                                                        <i class="bi bi-box-seam"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-body-emphasis mb-1" x-text="ord.order_no"></div>
                                                        <div class="small text-muted d-flex align-items-center gap-2">
                                                            <span><i class="bi bi-calendar3 me-1"></i><span x-text="formatDate(ord.order_date)"></span></span>
                                                            <span x-show="ord.customer"><i class="bi bi-person me-1 ms-1"></i><span x-text="ord.customer.firstname + ' ' + (ord.customer.lastname || '')"></span></span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="text-end">
                                                    <span class="badge rounded-pill mb-1" :class="getStatusTheme(ord.lifecycle_status) ? 'text-bg-' + getStatusTheme(ord.lifecycle_status) + '-subtle text-' + getStatusTheme(ord.lifecycle_status) + '-emphasis' : 'text-bg-secondary-subtle text-secondary-emphasis'" x-text="ord.status_label"></span>
                                                    <div class="fw-bold text-body-emphasis small" x-text="'₹ ' + parseFloat(ord.net_amount).toFixed(2)"></div>
                                                </div>
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Tabs Navigation -->
                    <ul class="nav nav-pills nav-fill bg-body-secondary p-1 rounded-pill shadow-sm mb-4" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active rounded-pill fw-bold" id="complaint-details-tab" data-bs-toggle="pill" data-bs-target="#complaint-details-pane" type="button" role="tab" aria-controls="complaint-details-pane" aria-selected="true">
                                <i class="bi bi-pencil-square me-2"></i>Complaint Management
                            </button>
                        </li>
                        <li class="nav-item" role="presentation" x-show="selectedOrderDetails || isEditing" x-cloak>
                            <button class="nav-link rounded-pill fw-bold" id="order-details-tab" data-bs-toggle="pill" data-bs-target="#order-details-pane" type="button" role="tab" aria-controls="order-details-pane" aria-selected="false">
                                <i class="bi bi-receipt me-2"></i>Order Preview <span class="badge bg-primary rounded-pill ms-2" x-show="selectedOrderDetails" x-text="selectedOrderDetails?.orderNumber"></span>
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content flex-grow-1 pe-2" id="complaintModalTabContent" style="overflow-y: auto; min-height: 0;">
                        <!-- Tab 2: Order Preview (Placed here because it's first in DOM) -->
                        <div class="tab-pane fade" id="order-details-pane" role="tabpanel" aria-labelledby="order-details-tab" tabindex="0">
                            <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                                <div class="card-body p-0 border border-primary border-opacity-10 rounded-4">
                                    <template x-if="selectedOrderDetails">
                                    <div class="row g-0">
                                        <!-- Left Column: Details & Items -->
                                        <div class="col-lg-8 p-4 bg-body-tertiary">
                                            <!-- Quick Stats Row -->
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
                                                                <p class="small text-muted mb-0 fw-semibold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">Order Type</p>
                                                                <p class="fw-bold mb-0 text-body-emphasis text-capitalize" x-text="selectedOrderDetails.type || 'Sale'"></p>
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
                                                                <p class="fw-bold mb-0 text-body-emphasis" style="font-size:0.85rem;" x-text="selectedOrderDetails.orderDate ? formatDate(selectedOrderDetails.orderDate) : 'N/A'"></p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Customer Info -->
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
                                                                <img :src="selectedOrderDetails.customer.avatar || '/assets/images/avatar-placeholder.png'" class="rounded-circle shadow-sm" width="48" height="48" alt="Customer" x-on:error="$el.src='/assets/images/avatar-placeholder.png'">
                                                                <div>
                                                                    <h6 class="fw-bold mb-1" x-text="selectedOrderDetails.customer.name"></h6>
                                                                    <p class="text-muted small mb-1 d-flex align-items-center gap-1"><i class="bi bi-envelope"></i> <span x-text="selectedOrderDetails.customer.email"></span></p>
                                                                    <p class="text-muted small mb-1 d-flex align-items-center gap-1"><i class="bi bi-telephone"></i> <span x-text="selectedOrderDetails.customer.phone || 'N/A'"></span></p>
                                                                    <template x-if="selectedOrderDetails.customer.company">
                                                                        <p class="text-muted small mb-1 d-flex align-items-center gap-1"><i class="bi bi-building"></i> <span x-text="selectedOrderDetails.customer.company"></span></p>
                                                                    </template>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <p class="fw-bold small text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Shipping Address</p>
                                                                <p class="small mb-0 text-body-emphasis" x-text="selectedOrderDetails.shippingAddress ? selectedOrderDetails.shippingAddress.formatted : 'N/A'"></p>
                                                            </div>
                                                            <div>
                                                                <p class="fw-bold small text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Billing Address</p>
                                                                <p class="small mb-0 text-body-emphasis" x-text="selectedOrderDetails.billingAddress ? selectedOrderDetails.billingAddress.formatted : 'N/A'"></p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Order Items Table -->
                                            <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                                                <div class="card-header bg-body border-bottom pt-4 pb-3 px-4 d-flex justify-content-between align-items-center">
                                                    <h6 class="fw-bold mb-0 text-body-emphasis d-flex align-items-center gap-2">
                                                        <i class="bi bi-box-seam text-primary fs-5"></i> Order Items
                                                    </h6>
                                                    <span class="badge text-bg-primary-subtle text-primary-emphasis rounded-pill px-3" x-text="`${selectedOrderDetails.itemCount} Items`"></span>
                                                </div>
                                                <div class="table-responsive">
                                                    <table class="table table-borderless table-hover align-middle mb-0 text-nowrap">
                                                        <thead class="bg-body-tertiary">
                                                            <tr>
                                                                <th class="fw-semibold text-muted small py-3 ps-4">Product Details</th>
                                                                <th class="fw-semibold text-muted small py-3 text-end">Price</th>
                                                                <th class="fw-semibold text-muted small py-3 text-center">Qty</th>
                                                                <th class="fw-semibold text-muted small py-3 text-end">Discount</th>
                                                                <th class="fw-semibold text-muted small py-3 text-end">Tax</th>
                                                                <th class="fw-semibold text-muted small py-3 text-end pe-4">Total</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <template x-for="(item, idx) in selectedOrderDetails.items" :key="idx">
                                                                <tr class="border-bottom">
                                                                    <td class="ps-4 py-3">
                                                                        <div class="d-flex align-items-center gap-3">
                                                                            <img :src="item.image || '/assets/images/product-placeholder.svg'"
                                                                                 class="rounded-3 shadow-sm object-fit-cover"
                                                                                 width="48"
                                                                                 height="48"
                                                                                 :alt="item.name"
                                                                                 x-on:error="$el.src='/assets/images/product-placeholder.svg'">
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
                                                                    <td class="text-end py-3 small">
                                                                        <div class="text-success fw-medium mb-1" x-show="parseFloat(item.discount || 0) > 0" x-text="`-₹ ${parseFloat(item.discount || 0).toFixed(2)}`"></div>
                                                                        <template x-if="parseFloat(item.discount || 0) > 0 && item.discountBadgeLabel">
                                                                            <div class="badge bg-success bg-opacity-10 border border-success border-opacity-25 text-success d-inline-flex align-items-center gap-1 px-2 py-1 rounded-3 mt-1">
                                                                                <i class="bi bi-tag-fill"></i>
                                                                                <span class="fw-bold" style="font-size: 11px;" x-text="item.discountBadgeLabel"></span>
                                                                            </div>
                                                                        </template>
                                                                        <div class="text-muted" x-show="!item.discount || parseFloat(item.discount) == 0">—</div>
                                                                    </td>
                                                                    <td class="text-end py-3 small">
                                                                        <div class="text-muted fw-medium" x-text="`+₹ ${parseFloat(item.tax || 0).toFixed(2)}`"></div>
                                                                        <div class="text-muted opacity-75" style="font-size: 0.7rem;" x-show="parseFloat(item.taxRate || 0) > 0" x-text="`(${parseFloat(item.taxRate).toFixed(0)}%)`"></div>
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

                                            <!-- Financial Summary -->
                                            <div class="card border-0 shadow-sm rounded-4">
                                                <div class="card-body p-4 bg-body rounded-4">
                                                    <div class="row justify-content-end">
                                                        <div class="col-md-6 col-lg-5">
                                                            <div class="d-flex justify-content-between mb-2">
                                                                <span class="text-muted fw-medium">Subtotal</span>
                                                                <span class="text-body-emphasis fw-bold" x-text="`₹ ${formatCurrency(selectedOrderDetails.subtotal)}`"></span>
                                                            </div>
                                                            <div class="d-flex justify-content-between mb-2">
                                                                <div>
                                                                    <span class="text-muted fw-medium">Discount</span>
                                                                    <span x-show="selectedOrderDetails.couponCode" class="badge bg-success ms-2 rounded-pill" x-text="selectedOrderDetails.couponCode"></span>
                                                                </div>
                                                                <span class="text-success fw-bold" x-text="`-₹ ${formatCurrency(selectedOrderDetails.discountTotal)}`"></span>
                                                            </div>
                                                            <div class="d-flex justify-content-between mb-3 border-bottom pb-3">
                                                                <span class="text-muted fw-medium">Tax</span>
                                                                <span class="text-body-emphasis fw-bold" x-text="`₹ ${formatCurrency(selectedOrderDetails.taxTotal)}`"></span>
                                                            </div>
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <span class="text-body-emphasis fw-bolder fs-5">Grand Total</span>
                                                                <span class="text-primary fw-bolder fs-4" x-text="`₹ ${formatCurrency(selectedOrderDetails.total)}`"></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Right Column: Warehouse / Logistics / Timeline -->
                                        <div class="col-lg-4 p-4 p-lg-5 border-start bg-body" style="height: fit-content; align-self: flex-start;">
                                            <!-- Logistics / Warehouse -->
                                            <div class="card border-0 shadow-sm rounded-4 mb-4">
                                                <div class="card-header bg-body border-bottom-0 pt-4 pb-2 px-4">
                                                    <h6 class="fw-bold mb-0 text-body-emphasis d-flex align-items-center gap-2">
                                                        <i class="bi bi-building text-secondary fs-5"></i> Fulfillment Center
                                                    </h6>
                                                </div>
                                                <div class="card-body p-4 pt-2">
                                                    <div class="p-3 bg-body-tertiary rounded-4 border">
                                                        <p class="mb-1 fw-bold text-body-emphasis" x-text="selectedOrderDetails.warehouse ? selectedOrderDetails.warehouse.name : 'Unassigned'"></p>
                                                        <p class="small text-muted mb-0 lh-sm" x-text="selectedOrderDetails.warehouse ? selectedOrderDetails.warehouse.address : 'N/A'"></p>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Invoice Details -->
                                            <template x-if="selectedOrderDetails.invoice">
                                                <div class="card border-0 shadow-sm rounded-4 mb-4 bg-secondary bg-opacity-10 border border-secondary border-opacity-25">
                                                    <div class="card-body p-4">
                                                        <h6 class="fw-bold mb-3 text-secondary d-flex align-items-center gap-2">
                                                            <i class="bi bi-receipt fs-5"></i> Invoice Details
                                                        </h6>
                                                        <div class="bg-body p-3 rounded-4 shadow-sm">
                                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                                <span class="small text-muted">Invoice No:</span>
                                                                <span class="fw-bold text-body-emphasis" x-text="selectedOrderDetails.invoice.number"></span>
                                                            </div>
                                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                                <span class="small text-muted">Date:</span>
                                                                <span class="fw-medium small" x-text="formatDate(selectedOrderDetails.invoice.date)"></span>
                                                            </div>
                                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                                <span class="small text-muted">Status:</span>
                                                                <span class="badge bg-secondary" x-text="selectedOrderDetails.invoice.status"></span>
                                                            </div>
                                                            <hr class="my-2">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <span class="small text-muted">Paid:</span>
                                                                <span class="text-success fw-bold" x-text="`₹ ${selectedOrderDetails.invoice.paid.toFixed(2)}`"></span>
                                                            </div>
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <span class="small text-muted">Due:</span>
                                                                <span class="text-danger fw-bold" x-text="`₹ ${selectedOrderDetails.invoice.due.toFixed(2)}`"></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>

                                            <!-- Payments Tracking -->
                                            <template x-if="selectedOrderDetails.payments && selectedOrderDetails.payments.length > 0">
                                                <div class="card border-0 shadow-sm rounded-4 mb-4 bg-success bg-opacity-10 border border-success border-opacity-25">
                                                    <div class="card-body p-4">
                                                        <h6 class="fw-bold mb-3 text-success d-flex align-items-center gap-2">
                                                            <i class="bi bi-cash-stack fs-5"></i> Payments
                                                        </h6>
                                                        <div class="position-relative ms-2 ps-3 border-start border-success border-opacity-25 border-2">
                                                            <template x-for="(payment, i) in selectedOrderDetails.payments" :key="payment.id">
                                                                <div class="position-relative mb-3">
                                                                    <div class="position-absolute bg-success rounded-circle" style="width: 10px; height: 10px; left: -22px; top: 5px;"></div>
                                                                    <div class="d-flex justify-content-between align-items-start">
                                                                        <div>
                                                                            <p class="fw-bold text-body-emphasis mb-0 small" x-text="`₹ ${payment.amount}`"></p>
                                                                            <p class="text-muted mb-0" style="font-size: 0.75rem;" x-text="formatDateTime(payment.date)"></p>
                                                                            <p class="text-secondary small mt-1 lh-sm mb-0">
                                                                                <span x-text="payment.method"></span>
                                                                                <span x-show="payment.transactionId && payment.transactionId !== 'N/A'" x-text="` | Txn: ${payment.transactionId}`"></span>
                                                                            </p>
                                                                        </div>
                                                                        <span class="badge" 
                                                                              :class="{
                                                                                'bg-success': payment.status === 'completed',
                                                                                'bg-warning': payment.status === 'pending' || payment.status === 'authorized',
                                                                                'bg-danger': payment.status === 'failed' || payment.status === 'refunded'
                                                                              }"
                                                                              x-text="payment.statusLabel || payment.status"></span>
                                                                    </div>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>

                                            <!-- Returns Tracking -->
                                            <template x-if="selectedOrderDetails.original && (selectedOrderDetails.original.order_returns && selectedOrderDetails.original.order_returns.length > 0 || selectedOrderDetails.original.orderReturns && selectedOrderDetails.original.orderReturns.length > 0)">
                                                <div class="card border-0 shadow-sm rounded-4 mb-4 bg-danger bg-opacity-10 border border-danger border-opacity-25">
                                                    <div class="card-body p-4">
                                                        <h6 class="fw-bold mb-3 text-danger d-flex align-items-center gap-2">
                                                            <i class="bi bi-arrow-return-left fs-5"></i> Returns & Refunds
                                                        </h6>
                                                        <div class="position-relative ms-2 ps-3 border-start border-danger border-opacity-25 border-2">
                                                            <template x-for="(ret, i) in (selectedOrderDetails.original.order_returns || selectedOrderDetails.original.orderReturns)" :key="ret.id || i">
                                                                <div class="position-relative mb-3">
                                                                    <div class="position-absolute bg-danger rounded-circle" style="width: 10px; height: 10px; left: -22px; top: 5px;"></div>
                                                                    <div class="d-flex justify-content-between align-items-start">
                                                                        <div>
                                                                            <p class="fw-bold text-body-emphasis mb-0 small" x-text="ret.return_no || 'Return'"></p>
                                                                            <p class="text-muted mb-0" style="font-size: 0.75rem;" x-text="formatDateTime(ret.created_at)"></p>
                                                                            <p class="text-secondary small mt-1 lh-sm mb-0">Reason: <span x-text="ret.reason || 'N/A'"></span></p>
                                                                            <p class="text-danger small fw-medium mt-1 mb-0" x-show="ret.refund_amount > 0">Refund: ₹ <span x-text="ret.refund_amount"></span></p>
                                                                        </div>
                                                                        <span class="badge bg-danger" x-text="ret.status"></span>
                                                                    </div>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>

                                            <!-- Order Status Timeline -->
                                            <template x-if="selectedOrderDetails.original && selectedOrderDetails.original.status_logs && selectedOrderDetails.original.status_logs.length > 0">
                                                <div class="card border-0 shadow-sm rounded-4 mb-4 bg-secondary bg-opacity-10 border border-secondary border-opacity-25">
                                                    <div class="card-body p-4">
                                                        <h6 class="fw-bold mb-3 text-secondary d-flex align-items-center gap-2">
                                                            <i class="bi bi-clock-history fs-5"></i> Order Status Timeline
                                                        </h6>
                                                        <div class="position-relative ms-2 ps-3 border-start border-secondary border-opacity-25 border-2">
                                                            <template x-for="log in selectedOrderDetails.original.status_logs" :key="log.id">
                                                                <div class="position-relative mb-3">
                                                                    <div class="position-absolute bg-secondary rounded-circle" style="width: 10px; height: 10px; left: -22px; top: 5px;"></div>
                                                                    <div class="d-flex justify-content-between align-items-start">
                                                                        <div>
                                                                            <p class="fw-bold text-body-emphasis mb-0 small text-capitalize" x-text="log.status.replace(/_/g, ' ')"></p>
                                                                            <p class="text-muted mb-0" style="font-size: 0.75rem;" x-text="formatDateTime(log.created_at)"></p>
                                                                            <p class="text-secondary small mt-1 lh-sm mb-0" x-show="log.notes" x-text="log.notes"></p>
                                                                            <p class="text-secondary opacity-75" style="font-size: 0.7rem; margin-top: 2px;" x-show="log.user">
                                                                                <i class="bi bi-person me-1"></i><span x-text="log.user.name || (log.user.first_name + ' ' + (log.user.last_name || ''))"></span>
                                                                            </p>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>

                                        </div>
                                    </div>
                                </template>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tab 1: Complaint Details (Active by default) -->
                                <div class="tab-pane fade show active" id="complaint-details-pane" role="tabpanel" aria-labelledby="complaint-details-tab" tabindex="0">
                                    
                                    <!-- Empty State for Order Details (Create mode) -->
                                    <div x-show="!selectedOrderDetails && !isEditing" class="text-center p-5 border border-dashed rounded-4 bg-body-tertiary mb-4 opacity-75">
                                        <i class="bi bi-search fs-1 text-muted mb-3 d-block"></i>
                                        <h6 class="fw-bold text-muted">No Order Selected</h6>
                                        <p class="small text-muted mb-0">Search and select an order above to attach it to this complaint.</p>
                                    </div>

                                    <!-- 3. Complaint Details Form -->
                                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                                        <div class="card-body p-4">
                                            <h6 class="fw-bold mb-4 d-flex align-items-center gap-2 text-body-emphasis border-bottom pb-3">
                                                <i class="bi bi-pencil-square text-warning fs-5"></i> Complaint Details
                                            </h6>
                            <div class="row g-4">
                                <div class="col-12">
                                    <label class="form-label fw-semibold small text-uppercase text-muted" style="letter-spacing: 0.5px;">Subject <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-lg bg-body-secondary border-0 shadow-sm" x-model="form.subject" required placeholder="Brief summary of the issue">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold small text-uppercase text-muted" style="letter-spacing: 0.5px;">Description <span class="text-danger">*</span></label>
                                    <textarea class="form-control bg-body-secondary border-0 shadow-sm" rows="4" x-model="form.description" required placeholder="Provide detailed information about the complaint..."></textarea>
                                </div>
                                
                                <div class="col-md-6" x-show="!selectedOrderDetails && !isEditing">
                                    <label class="form-label fw-semibold small text-uppercase text-muted" style="letter-spacing: 0.5px;">Order No <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control bg-body-secondary border-0 shadow-sm" x-model="form.order_no" :required="!isEditing && !selectedOrderDetails" placeholder="e.g. ORD-0001">
                                </div>
                                <div class="col-md-6" x-show="!selectedOrderDetails && !isEditing">
                                    <label class="form-label fw-semibold small text-uppercase text-muted" style="letter-spacing: 0.5px;">Customer ID <span class="text-muted fw-normal text-lowercase">(Optional)</span></label>
                                    <input type="number" class="form-control bg-body-secondary border-0 shadow-sm" x-model="form.customer_id">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fw-semibold small text-uppercase text-muted" style="letter-spacing: 0.5px;">Category <span class="text-danger">*</span></label>
                                    <select class="form-select bg-body-secondary border-0 shadow-sm" x-model="form.category" required>
                                        <option value="other">Other</option>
                                        <option value="delivery_delay">Delivery Delay</option>
                                        <option value="damaged_item">Damaged Item</option>
                                        <option value="missing_item">Missing Item</option>
                                        <option value="wrong_item">Wrong Item</option>
                                        <option value="payment_issue">Payment Issue</option>
                                        <option value="poor_service">Poor Service</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold small text-uppercase text-muted" style="letter-spacing: 0.5px;">Priority <span class="text-danger">*</span></label>
                                    <select class="form-select bg-body-secondary border-0 shadow-sm" x-model="form.priority" required>
                                        <option value="low">Low</option>
                                        <option value="medium">Medium</option>
                                        <option value="high">High</option>
                                        <option value="urgent">Urgent</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold small text-uppercase text-muted" style="letter-spacing: 0.5px;">Assigned To</label>
                                    <select class="form-select bg-body-secondary border-0 shadow-sm" x-model="form.assigned_to">
                                        <option value="">Unassigned</option>
                                        <template x-for="user in assignableUsers" :key="user.id">
                                            <option :value="user.id" x-text="user.name + (user.email ? ' (' + user.email + ')' : '')"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Resolution Tracking & Activity Feed -->
                    <!-- 4. Update Complaint Status & Resolution -->
                    <template x-if="isEditing">
                        <div class="card border-0 shadow-sm rounded-4 mb-4 border border-success border-opacity-25 bg-success bg-opacity-10">
                            <div class="card-body p-4">
                                <h6 class="fw-bold mb-3 text-success d-flex align-items-center gap-2">
                                    <i class="bi bi-shield-check fs-5"></i> Update Status & Resolution
                                </h6>
                                <div class="row g-4 align-items-start bg-body rounded-4 p-3 shadow-sm border border-success border-opacity-10">
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold small text-uppercase text-muted" style="letter-spacing: 0.5px;">Status <span class="text-danger">*</span></label>
                                        <select class="form-select form-select-lg bg-body-tertiary border-0 shadow-sm text-body-emphasis fw-bold" x-model="form.status" required>
                                            <option value="open">Open</option>
                                            <option value="in_progress">In Progress</option>
                                            <option value="resolved">Resolved</option>
                                            <option value="closed">Closed</option>
                                        </select>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label fw-semibold small text-uppercase text-muted" style="letter-spacing: 0.5px;">Add Resolution Note</label>
                                        <textarea class="form-control border-0 bg-body-tertiary shadow-sm rounded-3" rows="2" x-model="form.resolution_notes" placeholder="Type a note to append to the resolution..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- 5. Activity & Communication Feed -->
                    <template x-if="isEditing">
                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                            <div class="card-header bg-primary bg-opacity-10 border-bottom-0 py-3 px-4">
                                <h6 class="fw-bold mb-0 text-primary d-flex align-items-center gap-2">
                                    <i class="bi bi-chat-text fs-5"></i> Activity & Communication Feed
                                </h6>
                            </div>
                            <div class="card-body p-0 bg-body d-flex flex-column" style="max-height: 500px;">
                                
                                <!-- Timeline Feed -->
                                <div class="p-4 bg-body-tertiary overflow-auto flex-grow-1 border-bottom">
                                    <template x-if="timelineFeed && timelineFeed.length > 0">
                                        <div class="position-relative ms-3 ps-4 border-start border-primary border-opacity-25 border-2">
                                            
                                            <template x-for="(item, idx) in timelineFeed" :key="idx">
                                                <div class="position-relative mb-4">
                                                    
                                                    <template x-if="item._type === 'reply'">
                                                        <div>
                                                            <div class="position-absolute bg-primary rounded-circle shadow-sm" style="width: 14px; height: 14px; left: -31px; top: 12px; border: 3px solid var(--bs-body-bg);"></div>
                                                            <div class="card border-0 shadow-sm rounded-4 bg-primary bg-opacity-10 border border-primary border-opacity-25 ms-1">
                                                                <div class="card-body p-3">
                                                                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom border-primary border-opacity-10">
                                                                        <div class="d-flex align-items-center gap-2">
                                                                            <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center text-primary shadow-sm" style="width:28px;height:28px;">
                                                                                <i class="bi bi-chat-text-fill"></i>
                                                                            </div>
                                                                            <span class="fw-bold text-primary small" x-text="item.user ? (item.user.name || (item.user.first_name + ' ' + (item.user.last_name || ''))) : 'Agent'"></span>
                                                                        </div>
                                                                        <span class="text-primary opacity-75" style="font-size: 0.7rem;" x-text="formatDateTime(item.created_at)"></span>
                                                                    </div>
                                                                    <p class="text-body-emphasis small mb-0 lh-base" x-show="item.message" x-html="(item.message || '').replace(/\n/g, '<br>')"></p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </template>

                                                    <template x-if="item._type === 'log'">
                                                        <div class="ms-1 d-flex flex-column mb-2 mt-2 opacity-75">
                                                            <div class="position-absolute bg-secondary rounded-circle" style="width: 10px; height: 10px; left: -29px; top: 6px; border: 2px solid var(--bs-body-bg);"></div>
                                                            <div class="small text-muted w-100 d-flex gap-2 align-items-center">
                                                                <span class="badge bg-secondary rounded-pill fw-medium" x-text="item.status ? item.status.replace(/_/g, ' ').toUpperCase() : 'UPDATE'"></span>
                                                                <span x-text="item.user ? (item.user.name || item.user.first_name) : 'System'"></span>
                                                                <span style="font-size: 0.7rem;" x-text="formatDateTime(item.created_at)"></span>
                                                            </div>
                                                            
                                                            <!-- Inject resolution notes inline if applicable -->
                                                            <template x-if="item.id === selectedComplaint.status_logs[selectedComplaint.status_logs.length - 1]?.id && ['resolved', 'closed'].includes(selectedComplaint.status) && selectedComplaint.resolution_notes">
                                                                <div class="d-block w-100 mt-2 p-2 bg-success bg-opacity-10 border border-success border-opacity-25 rounded-3 text-success small">
                                                                    <i class="bi bi-check-circle-fill me-1"></i> <span class="fw-bold">Resolution:</span> <span x-html="(selectedComplaint.resolution_notes || '').replace(/\n/g, '<br>')"></span>
                                                                </div>
                                                            </template>
                                                            <template x-if="item.notes && (!selectedComplaint.resolution_notes || item.id !== selectedComplaint.status_logs[selectedComplaint.status_logs.length - 1]?.id)">
                                                                <div class="d-block w-100 mt-1 small text-muted fst-italic" x-text="`Note: ${item.notes}`"></div>
                                                            </template>
                                                        </div>
                                                    </template>

                                                </div>
                                            </template>
                                            
                                        </div>
                                    </template>
                                    <template x-if="!timelineFeed || timelineFeed.length === 0">
                                        <div class="text-center p-4 text-muted small">No activity history recorded yet.</div>
                                    </template>
                                </div>

                                <!-- Reply Input Area -->
                                <div class="p-3 bg-body rounded-bottom-4">
                                    <div class="d-flex gap-3 align-items-center">
                                        <div class="flex-grow-1">
                                            <input type="text" class="form-control border-0 bg-body-tertiary rounded-pill px-4 py-3 shadow-sm" x-model="replyMessage" placeholder="Type a message to send..." @keydown.enter.prevent="postReply">
                                        </div>
                                        <div>
                                            <button type="button" class="btn btn-primary rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;" @click="postReply" :disabled="!replyMessage.trim() || isReplying">
                                                <span x-show="isReplying" class="spinner-border spinner-border-sm"></span>
                                                <i class="bi bi-send fs-5" x-show="!isReplying" style="margin-left: -2px;"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                            </div>
                        </div>
                    </template>
                                </div> <!-- End Tab 1 -->
                            </div> <!-- End Tab Content -->
                </div>
                
                <div class="modal-footer bg-body-tertiary border-top py-3 px-4">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    @can('complaints.create')
                    <button type="submit" class="btn btn-primary px-4 fw-semibold" x-show="!isEditing" :disabled="isSubmitting">
                        <span x-show="isSubmitting" class="spinner-border spinner-border-sm me-1"></span>
                        Save Complaint
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
            assignableUsers: [],
            stats: { total: 0, open: 0, in_progress: 0, resolved: 0, closed: 0 },
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
            selectedComplaint: null,
            form: {
                id: null, order_no: '', customer_id: '', category: 'other',
                priority: 'medium', subject: '', description: '',
                status: 'open', resolution_notes: ''
            },
            replyMessage: '',
            isReplying: false,
            bulkAssignTo: '',
            bulkPriority: '',

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
            get timelineFeed() {
                if (!this.selectedComplaint) return [];
                const logs = (this.selectedComplaint.status_logs || []).map(l => ({ ...l, _type: 'log', _date: new Date(l.created_at) }));
                const replies = (this.selectedComplaint.replies || []).map(r => ({ ...r, _type: 'reply', _date: new Date(r.created_at) }));
                return [...logs, ...replies].sort((a, b) => a._date - b._date);
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
                    this.complaints      = data.complaints.data;
                    this.currentPage     = data.complaints.current_page;
                    this.totalPages      = data.complaints.last_page;
                    this.totalComplaints = data.complaints.total;
                    this.stats           = data.stats;
                    this.assignableUsers = data.assignable_users || [];
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
                return { open: 'warning', in_progress: 'info', resolved: 'success', closed: 'secondary' }[status] || 'secondary';
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
                this.form = { id: null, order_no: '', customer_id: '', assigned_to: '', category: 'other', priority: 'medium', subject: '', description: '', status: 'open', resolution_notes: '' };
                this.searchQueryOrder = ''; this.fetchedOrders = []; this.selectedOrderDetails = null; this.searchOrderError = '';
                this.modalInstance.show();
            },
            async viewComplaint(cmp) {
                this.isEditing = true;
                this.selectedComplaint = cmp;
                this.form = {
                    id:               cmp.id,
                    order_no:         cmp.order?.order_no || '',
                    customer_id:      cmp.customer_id || '',
                    assigned_to:      cmp.assigned_to || '',
                    category:         cmp.category || 'other',
                    priority:         cmp.priority || 'medium',
                    subject:          cmp.subject || '',
                    description:      cmp.description || '',
                    status:           cmp.status || 'open',
                    resolution_notes: cmp.resolution_notes || '',
                    complaint_number: cmp.complaint_number || '',
                };
                this.searchQueryOrder = ''; this.fetchedOrders = []; this.selectedOrderDetails = null; this.searchOrderError = '';
                this.modalInstance.show();

                if (cmp.order_id || (cmp.order && cmp.order.id)) {
                    const orderId = cmp.order_id || cmp.order.id;
                    try {
                        const data = await this.api(`/api/orders/${orderId}`);
                        this.selectedOrderDetails = this.mapRawOrder(data.order || data.data || data);
                    } catch (e) {
                        console.error('Failed to fetch order details:', e);
                    }
                }
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
                    const msg = e.data?.message
                        || (e.data?.errors ? Object.values(e.data.errors).flat().join(' ') : null)
                        || 'Failed to save complaint.';
                    window.Swal.fire('Error', msg, 'error');
                }
                this.isSubmitting = false;
            },

            async postReply() {
                if (!this.replyMessage.trim() || !this.selectedComplaint) return;
                this.isReplying = true;
                try {
                    const res = await this.api(`/api/complaints/${this.selectedComplaint.id}/reply`, {
                        method: 'POST',
                        body: JSON.stringify({ message: this.replyMessage })
                    });
                    if (!this.selectedComplaint.replies) this.selectedComplaint.replies = [];
                    this.selectedComplaint.replies.push(res.data);
                    this.replyMessage = '';
                } catch (e) {
                    window.Swal.fire('Error', e.data?.message || 'Failed to post reply.', 'error');
                }
                this.isReplying = false;
            },

            async bulkAction(action, payload = {}) {
                if (!this.selectedComplaints.length) return;
                const confirmed = await window.Swal.fire({
                    title: 'Are you sure?',
                    text: `You are about to perform this action on ${this.selectedComplaints.length} complaint(s).`,
                    icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, proceed!'
                });
                if (!confirmed.isConfirmed) return;
                this.isSubmitting = true;
                try {
                    await this.api('/api/complaints/bulk-action', {
                        method: 'POST',
                        body: JSON.stringify({ action, ids: this.selectedComplaints, ...payload })
                    });
                    window.Swal.fire('Success', 'Bulk action completed.', 'success');
                    this.selectedComplaints = [];
                    this.fetchComplaints();
                    this.fetchStats();
                } catch (e) {
                    window.Swal.fire('Error', e.data?.message || 'Bulk action failed.', 'error');
                }
                this.isSubmitting = false;
            },

            async downloadCsv(url, filename, options = {}) {
                const method = (options.method || 'GET').toUpperCase();
                const headers = {
                    'Accept': 'text/csv',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.csrf(),
                    ...(options.body ? { 'Content-Type': 'application/json' } : {}),
                };
                try {
                    const res = await fetch(url, { ...options, method, headers });
                    if (!res.ok) throw new Error('Download failed');
                    const blob = await res.blob();
                    const urlBlob = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = urlBlob;
                    a.download = filename;
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    window.URL.revokeObjectURL(urlBlob);
                } catch (e) {
                    console.error(e);
                    window.Swal.fire('Error', 'Failed to download export.', 'error');
                }
            },

            async bulkExport() {
                const params = new URLSearchParams();
                if (this.searchQuery)   params.append('search',   this.searchQuery);
                if (this.statusFilter)  params.append('status',   this.statusFilter);
                if (this.priorityFilter) params.append('priority', this.priorityFilter);
                
                await this.downloadCsv(`/api/complaints/export?${params.toString()}`, `complaints_export_${new Date().getTime()}.csv`);
            },

            async exportSelected() {
                if (!this.selectedComplaints.length) return;
                await this.downloadCsv('/api/complaints/export-selected', `selected_complaints_${new Date().getTime()}.csv`, {
                    method: 'POST',
                    body: JSON.stringify({ ids: this.selectedComplaints })
                });
            }
        }));
    });
</script>

<style>
    /* ── Complaint Modal — Two-Column Scrollable Layout ── */
    #complaintModal .modal-dialog {
        max-height: calc(100vh - 3.5rem);
    }
    #complaintModal .modal-content {
        max-height: calc(100vh - 3.5rem);
        display: flex;
        flex-direction: column;
    }
    #complaintModal .modal-body {
        flex: 1 1 auto;
        overflow: hidden !important; /* modal body does not scroll, columns do */
    }
    #complaintModal .pvm-layout {
        height: 100%;
        min-height: 0;
    }
    #complaintModal .pvm-left,
    #complaintModal .pvm-right {
        overflow-y: auto;
        /* vh minus header minus footer */
        max-height: calc(100vh - 3.5rem - 70px - 70px); 
    }
    #complaintModal .pvm-left::-webkit-scrollbar,
    #complaintModal .pvm-right::-webkit-scrollbar {
        width: 5px;
    }
    #complaintModal .pvm-left::-webkit-scrollbar-thumb,
    #complaintModal .pvm-right::-webkit-scrollbar-thumb {
        background-color: rgba(var(--bs-secondary-rgb), 0.3);
        border-radius: 4px;
    }
    @media (max-width: 991.98px) {
        #complaintModal .pvm-left,
        #complaintModal .pvm-right {
            max-height: none;
            overflow-y: visible;
        }
        #complaintModal .modal-body {
            overflow-y: auto !important;
        }
    }
    #complaintModal .min-width-0 { min-width: 0; }
</style>
@endpush
