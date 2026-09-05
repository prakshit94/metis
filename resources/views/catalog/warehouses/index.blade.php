@extends('layouts.app')

@section('title', 'Warehouses Management')
@section('page', 'catalog-warehouses')

@section('content')
<div class="warehouses-management" x-data="warehousesTable" x-cloak>

    <!-- ═══════════════════════ Page Header ════════════════════════════════ -->
    <div class="d-flex justify-content-between align-items-center mb-4 mb-lg-5">
        <div>
            <h1 class="h3 mb-0">Warehouses Management</h1>
            <p class="text-muted mb-0">Manage fulfillment centres, addresses, and operational details</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary" @click="exportData()">
                <i class="bi bi-download me-2"></i>Export
            </button>
            <button type="button" class="btn btn-primary" @click.prevent="openCreateModal()">
                <i class="bi bi-plus-lg me-2"></i>Add Warehouse
            </button>
        </div>
    </div>

    <!-- Analytics Dashboard Container -->
    <div class="row g-4 mb-4" x-show="items.length > 0" style="display: none;">
        <!-- Chart 1: Physical Stock Distribution -->
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header border-bottom-0 pt-4 pb-0">
                    <h5 class="fw-bold text-primary mb-0"><i class="bi bi-bar-chart-fill me-2"></i>Stock Distribution</h5>
                </div>
                <div class="card-body">
                    <div id="stockDistributionChart" style="min-height: 300px;"></div>
                </div>
            </div>
        </div>
        <!-- Chart 2: SKU Spread -->
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header border-bottom-0 pt-4 pb-0">
                    <h5 class="fw-bold text-success mb-0"><i class="bi bi-pie-chart-fill me-2"></i>SKU Spread</h5>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <div id="skuSpreadChart" class="w-100" style="min-height: 300px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════ Stats Widgets ══════════════════════════════ -->
    <div class="row g-4 g-lg-5 mb-5">
        <div class="col-xl-4 col-lg-4 col-md-4">
            <div class="card stats-card" style="cursor: default;">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bi bi-buildings-fill"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Total Warehouses</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.total"></span></div>
                            <small class="text-muted">All registered centres</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-lg-4 col-md-4">
            <div class="card stats-card" style="cursor: default;">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-success bg-opacity-10 text-success me-3">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Active</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.active"></span></div>
                            <small class="text-success">
                                <i class="bi bi-arrow-up-right me-1"></i>Operational
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-lg-4 col-md-4">
            <div class="card stats-card" style="cursor: default;">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-secondary bg-opacity-10 text-secondary me-3">
                            <i class="bi bi-dash-circle-fill"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Inactive</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.inactive"></span></div>
                            <small class="text-muted">Temporarily offline</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════ Main Table Card ═════════════════════════════ -->
    <div class="card mb-5">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="h5 card-title mb-0">Warehouses Directory</h2>
                </div>
                <div class="col-auto mt-3 mt-md-0">
                    <div class="d-flex gap-2 flex-wrap justify-content-end">
                        <div class="position-relative">
                            <input type="search"
                                   class="form-control form-control-sm"
                                   placeholder="Search name, code, city, GSTIN…"
                                   x-model.debounce.300ms="searchQuery"
                                   @input="filterData()"
                                   style="width:250px;">
                            <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted"></i>
                        </div>
                        <select class="form-select form-select-sm" x-model="statusFilter" @change="filterData()" style="width:150px;">
                            <option value="">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <!-- Bulk Actions Bar -->
            <div class="bulk-actions-bar p-3 bg-primary bg-opacity-10 border-bottom border-primary border-opacity-25" x-show="selectedItems.length > 0" x-transition>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-primary fw-medium">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <strong><span x-text="selectedItems.length"></span></strong> item(s) selected
                    </span>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-success" @click="bulkAction('active')">
                            <i class="bi bi-check-circle me-1"></i>Mark Active
                        </button>
                        <button class="btn btn-sm btn-secondary" @click="bulkAction('inactive')">
                            <i class="bi bi-x-circle me-1"></i>Mark Inactive
                        </button>
                        <button class="btn btn-sm btn-danger" @click="bulkAction('delete')">
                            <i class="bi bi-trash me-1"></i>Delete
                        </button>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-nowrap">
                    <thead>
                        <tr>
                            <th style="width:44px" class="ps-3">
                                <input type="checkbox" class="user-select-checkbox"
                                       @change="$event.isTrusted && toggleAll($event.target.checked)"
                                       :checked="selectedItems.length === paginatedItems.length && paginatedItems.length > 0">
                            </th>
                            <th @click="sortBy('id')" class="sortable" style="width:90px; cursor: pointer;">
                                ID
                                <i class="bi ms-1" :class="sortField === 'id' ? (sortDirection === 'asc' ? 'bi-chevron-up text-primary' : 'bi-chevron-down text-primary') : 'bi-arrow-down-up opacity-25'"></i>
                            </th>
                            <th @click="sortBy('name')" class="sortable" style="cursor: pointer;">
                                Warehouse
                                <i class="bi ms-1" :class="sortField === 'name' ? (sortDirection === 'asc' ? 'bi-chevron-up text-primary' : 'bi-chevron-down text-primary') : 'bi-arrow-down-up opacity-25'"></i>
                            </th>
                            <th>Contact Info</th>
                            <th>Address</th>
                            <th>Inventory & Orders</th>
                            <th @click="sortBy('status')" class="sortable" style="width:110px; cursor: pointer;">
                                Status
                                <i class="bi ms-1" :class="sortField === 'status' ? (sortDirection === 'asc' ? 'bi-chevron-up text-primary' : 'bi-chevron-down text-primary') : 'bi-arrow-down-up opacity-25'"></i>
                            </th>
                            <th style="width:110px" class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Loading State -->
                        <template x-if="isLoading">
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="spinner-border text-primary" role="status"></div>
                                    <p class="text-muted small mt-2 mb-0 fw-medium">Loading warehouses…</p>
                                </td>
                            </tr>
                        </template>

                        <!-- Empty State -->
                        <template x-if="!isLoading && paginatedItems.length === 0">
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-buildings fs-2 d-block mb-2"></i>
                                    No warehouses found matching your criteria.
                                </td>
                            </tr>
                        </template>

                        <!-- Data Rows -->
                        <template x-for="item in paginatedItems" :key="item.id">
                            <tr :class="{ 'selected': selectedItems.includes(item.id) }">
                                <td class="ps-3">
                                    <input type="checkbox" class="user-select-checkbox"
                                           :value="item.id"
                                           :checked="selectedItems.includes(item.id)"
                                           @change="toggleItem(item.id)">
                                </td>
                                <td class="text-muted" x-text="item.id"></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary bg-opacity-10 text-primary rounded d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width:38px;height:38px;">
                                            <i class="bi bi-building"></i>
                                        </div>
                                        <div>
                                            <div class="fw-medium d-flex align-items-center gap-2">
                                                <span x-text="item.name"></span>
                                                <template x-if="item.is_default">
                                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">DEFAULT</span>
                                                </template>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <small class="text-muted font-monospace" x-text="item.code || 'NO-CODE'"></small>
                                                <template x-if="item.company_name">
                                                    <span class="small text-muted border-start ps-2" x-text="item.company_name"></span>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small d-flex flex-column gap-1">
                                        <template x-if="item.phone">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="bi bi-telephone text-muted"></i>
                                                <span x-text="item.phone"></span>
                                            </div>
                                        </template>
                                        <template x-if="item.email">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="bi bi-envelope text-muted"></i>
                                                <span x-text="item.email"></span>
                                            </div>
                                        </template>
                                        <template x-if="item.gstin">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="bi bi-card-text text-muted"></i>
                                                <span class="font-monospace text-muted" x-text="item.gstin"></span>
                                            </div>
                                        </template>
                                        <template x-if="item.seed_lic_no || item.pesti_lic_no">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="bi bi-card-checklist text-muted"></i>
                                                <span class="text-muted" x-text="item.seed_lic_no || item.pesti_lic_no"></span>
                                            </div>
                                        </template>
                                        <template x-if="!item.phone && !item.email && !item.gstin && !item.seed_lic_no && !item.pesti_lic_no">
                                            <span class="text-muted fst-italic">—</span>
                                        </template>
                                    </div>
                                </td>
                                <td>
                                    <div class="small d-flex align-items-start gap-2">
                                        <i class="bi bi-geo-alt text-muted mt-1 flex-shrink-0"></i>
                                        <div class="text-wrap" style="max-width: 280px; line-height: 1.4;">
                                            <div class="fw-medium text-body" x-text="[item.address_line_1, item.address_line_2].filter(Boolean).join(', ') || '—'"></div>
                                            <div class="text-muted mt-1" style="font-size: 0.75rem;">
                                                <template x-if="item.village_name || item.post_office || item.taluka">
                                                    <div class="mb-1" x-text="[
                                                        item.village_name, 
                                                        item.post_office ? 'PO: ' + item.post_office : '', 
                                                        item.taluka ? 'Taluka: ' + item.taluka : ''
                                                    ].filter(Boolean).join(', ')"></div>
                                                </template>
                                                <div x-text="[
                                                    item.city ? 'Dist: ' + item.city : '', 
                                                    item.state, 
                                                    item.pincode
                                                ].filter(Boolean).join(', ')"></div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small d-flex flex-column gap-1 mb-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-box-seam text-muted"></i>
                                            <span class="fw-medium"><span x-text="item.total_skus || 0"></span> SKUs</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-stack text-muted"></i>
                                            <span class="text-muted"><span x-text="parseFloat(item.total_physical_stock || 0)"></span> Units</span>
                                        </div>
                                    </div>
                                    <div class="small d-flex gap-2">
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle" title="Total Orders">
                                            <i class="bi bi-cart me-1"></i><span x-text="item.total_orders || 0"></span>
                                        </span>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle" title="Fulfillable Orders">
                                            <i class="bi bi-check-circle me-1"></i><span x-text="item.fulfillable_orders || 0"></span>
                                        </span>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle" title="Unfulfillable Orders">
                                            <i class="bi bi-exclamation-circle me-1"></i><span x-text="item.unfulfillable_orders || 0"></span>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge"
                                          :class="item.status === 'active' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle'">
                                          <span x-text="item.status.toUpperCase()"></span>
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                            <li>
                                                <a class="dropdown-item" href="#" @click.prevent="viewItem(item)">
                                                    <i class="bi bi-eye me-2"></i> View Details
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="#" @click.prevent="editItem(item)">
                                                    <i class="bi bi-pencil me-2"></i> Edit Details
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item text-danger" href="#" @click.prevent="deleteItem(item)">
                                                    <i class="bi bi-trash me-2"></i> Delete
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
                <div class="text-muted">
                    Showing <span x-text="pageFrom"></span> to
                    <span x-text="pageTo"></span> of
                    <span x-text="filteredItems.length"></span> results
                </div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item" :class="{ 'disabled': currentPage === 1 }">
                            <a class="page-link" href="#" @click.prevent="goToPage(currentPage - 1)">Previous</a>
                        </li>
                        <template x-for="(page, index) in visiblePages" :key="`page-${index}`">
                            <li class="page-item" :class="{ 'active': page === currentPage }">
                                <a class="page-link" href="#" @click.prevent="page !== '...' && goToPage(page)" x-text="page"></a>
                            </li>
                        </template>
                        <li class="page-item" :class="{ 'disabled': currentPage === totalPages }">
                            <a class="page-link" href="#" @click.prevent="goToPage(currentPage + 1)">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>

        </div>{{-- end card-body --}}
    </div>{{-- end card --}}


    <!-- ═══════════════════════ Add / Edit Modal ════════════════════════════ -->
    <div class="modal fade" id="warehousesModal" aria-labelledby="warehousesModalLabel" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content shadow-lg border-0 rounded-4">

                <!-- Header -->
                <div class="modal-header bg-body-tertiary border-bottom d-flex align-items-center justify-content-between p-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px;">
                            <i class="bi bi-buildings fs-4"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h4 class="mb-0 fw-bold text-body"><span x-text="isEditing ? 'Edit Warehouse' : 'Add New Warehouse'"></span></h4>
                                <template x-if="isEditing && form.is_default">
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill">Default</span>
                                </template>
                            </div>
                            <p class="mb-0 small text-muted">Manage fulfillment centre identity and contact details</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Body -->
                <div class="modal-body p-4 bg-body-tertiary">
                    <form @submit.prevent="saveItem" id="warehouseForm" novalidate>
                        <div class="row g-4">

                            <!-- ── Column 1: Basic Info ─────────────────── -->
                            <div class="col-lg-6">

                                <!-- Identity Card -->
                                <div class="card border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary mb-3">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom">
                                            <div class="bg-primary bg-opacity-10 text-primary rounded-2 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                                <i class="bi bi-building fs-6"></i>
                                            </div>
                                            <h6 class="mb-0 fw-bold text-uppercase text-body" style="font-size: 11px; letter-spacing: 1px;">Warehouse Identity</h6>
                                        </div>

                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Warehouse Name *</label>
                                                <input type="text" class="form-control form-control-sm fw-semibold" style="font-size: 12px;" x-model="form.name" placeholder="e.g. Main Warehouse, Delhi Hub" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Code</label>
                                                <input type="text" class="form-control form-control-sm font-monospace text-uppercase fw-semibold" style="font-size: 12px;" x-model="form.code" placeholder="e.g. MAIN, DEL01" maxlength="10">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Status</label>
                                                <select class="form-select form-select-sm fw-semibold" style="font-size: 12px;" x-model="form.status">
                                                    <option value="active">Active</option>
                                                    <option value="inactive">Inactive</option>
                                                </select>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Company / Legal Name</label>
                                                <input type="text" class="form-control form-control-sm fw-semibold" style="font-size: 12px;" x-model="form.company_name" placeholder="e.g. ABC Logistics Pvt Ltd">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Contact Card -->
                                <div class="card border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary mb-3 mb-lg-0">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom">
                                            <div class="bg-info bg-opacity-10 text-info rounded-2 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                                <i class="bi bi-telephone fs-6"></i>
                                            </div>
                                            <h6 class="mb-0 fw-bold text-uppercase text-body" style="font-size: 11px; letter-spacing: 1px;">Contact & Compliance</h6>
                                        </div>

                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Phone Number</label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-body text-muted border-end-0"><i class="bi bi-telephone"></i></span>
                                                    <input type="tel" class="form-control border-start-0 ps-0 fw-semibold" style="font-size: 12px;" x-model="form.phone" placeholder="9876543210" maxlength="10" pattern="[0-9]{10}" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Email Address</label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-body text-muted border-end-0"><i class="bi bi-envelope"></i></span>
                                                    <input type="email" class="form-control border-start-0 ps-0 fw-semibold" style="font-size: 12px;" x-model="form.email" placeholder="warehouse@example.com">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Reference No.</label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-body text-muted border-end-0"><i class="bi bi-hash"></i></span>
                                                    <input type="text" class="form-control border-start-0 ps-0 fw-semibold" style="font-size: 12px;" x-model="form.reference_no" placeholder="e.g. WH-REF-001">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">GSTIN</label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-body text-muted border-end-0"><i class="bi bi-card-text"></i></span>
                                                    <input type="text" class="form-control border-start-0 ps-0 font-monospace text-uppercase fw-semibold" style="font-size: 12px;" x-model="form.gstin" placeholder="22AAAAA0000A1Z5" maxlength="15">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Seed Lic No.</label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-body text-muted border-end-0"><i class="bi bi-card-checklist"></i></span>
                                                    <input type="text" class="form-control border-start-0 ps-0 fw-semibold" style="font-size: 12px;" x-model="form.seed_lic_no" placeholder="GAN/FSR220001380/2022">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Pesti Lic No.</label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-body text-muted border-end-0"><i class="bi bi-card-checklist"></i></span>
                                                    <input type="text" class="form-control border-start-0 ps-0 fw-semibold" style="font-size: 12px;" x-model="form.pesti_lic_no" placeholder="GAN/FP1220002020/2022">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">E-Biller ID / Reg. No</label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-body text-muted border-end-0"><i class="bi bi-upc-scan"></i></span>
                                                    <input type="text" class="form-control border-start-0 ps-0 fw-semibold" style="font-size: 12px;" x-model="form.ebiller_id" placeholder="1211658094">
                                                </div>
                                            </div>
                                            <div class="col-12 mt-3">
                                                <div class="form-check form-switch cursor-pointer">
                                                    <input class="form-check-input" type="checkbox" id="wh_default" x-model="form.is_default" style="cursor: pointer;">
                                                    <label class="form-check-label fw-bold text-primary text-uppercase" for="wh_default" style="font-size: 9px; letter-spacing: 0.1em; cursor: pointer;">Set as default warehouse</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <!-- ── Column 2: Address ────────────────────── -->
                            <div class="col-lg-6">
                                <div class="card border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary h-100">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom">
                                            <div class="bg-primary bg-opacity-10 text-primary rounded-2 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                                <i class="bi bi-geo fs-6"></i>
                                            </div>
                                            <h6 class="mb-0 fw-bold text-uppercase text-body" style="font-size: 11px; letter-spacing: 1px;">Address Details</h6>
                                        </div>

                                        <div class="row g-3">
                                            <div class="col-sm-6">
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Address Line 1</label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-body text-muted border-end-0"><i class="bi bi-house"></i></span>
                                                    <input type="text" class="form-control border-start-0 ps-0 fw-semibold" style="font-size: 12px;" x-model="form.address_line_1" placeholder="Building, Street, Plot No.">
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Address Line 2</label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-body text-muted border-end-0"><i class="bi bi-signpost"></i></span>
                                                    <input type="text" class="form-control border-start-0 ps-0 fw-semibold" style="font-size: 12px;" x-model="form.address_line_2" placeholder="Area, Landmark (optional)">
                                                </div>
                                            </div>

                                            <div class="col-12">
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Village Search</label>
                                                <div class="position-relative">
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text bg-body text-muted border-end-0"><i class="bi bi-search"></i></span>
                                                        <input type="text" class="form-control border-start-0 ps-0 fw-semibold" style="font-size: 12px;" placeholder="Type 3 letters to search village..." 
                                                               x-model="villageSearchQuery" @input="onVillageInput()" @blur="setTimeout(() => { villageResults = [] }, 200)">
                                                        <button type="button" class="btn btn-outline-secondary" x-show="form.village_id" @click="clearVillage()" title="Clear">
                                                            <i class="bi bi-x-lg"></i>
                                                        </button>
                                                    </div>
                                                    
                                                    <!-- Loading spinner -->
                                                    <div class="position-absolute end-0 top-50 translate-middle-y me-5 pe-2" x-show="villageSearchLoading" style="z-index:10">
                                                        <div class="spinner-border spinner-border-sm text-primary"></div>
                                                    </div>

                                                    <!-- Dropdown results -->
                                                    <div class="position-absolute w-100 dropdown-menu show shadow overflow-auto" style="max-height: 200px; z-index: 1060;" x-show="villageResults.length > 0">
                                                        <template x-for="v in villageResults" :key="v.id">
                                                            <button type="button" class="dropdown-item w-100 text-start py-2 px-3 border-bottom border-light-subtle"
                                                                    @mousedown.prevent="selectVillage(v)">
                                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                                    <span class="fw-bold text-primary" style="font-size: 12px;" x-text="v.village_name"></span>
                                                                    <span class="badge bg-secondary-subtle text-secondary-emphasis" x-text="v.pincode"></span>
                                                                </div>
                                                                <div class="text-muted small" style="font-size: 0.75rem; line-height: 1.4;">
                                                                    <span x-show="v.post_so_name" x-text="'PO: ' + v.post_so_name + ' · '"></span>
                                                                    <span x-show="v.taluka_name" x-text="'Taluka: ' + v.taluka_name + ' · '"></span>
                                                                    <span x-show="v.district_name" x-text="'District: ' + v.district_name + ' · '"></span>
                                                                    <span x-show="v.state_name" x-text="'State: ' + v.state_name"></span>
                                                                </div>
                                                            </button>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Selected Village Details -->
                                            <template x-if="form.village_id">
                                                <div class="col-12 mt-2">
                                                    <div class="card bg-body border-0 border-start border-4 border-primary shadow-sm mt-2">
                                                        <div class="card-body p-3">
                                                            <div class="row g-2">
                                                                <div class="col-md-4">
                                                                    <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Village</label>
                                                                    <div class="fw-semibold text-truncate text-body" style="font-size: 12px;" x-text="form.village_name || '—'"></div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Post Office</label>
                                                                    <div class="text-truncate text-body" style="font-size: 12px;" x-text="form.post_office || '—'"></div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Taluka</label>
                                                                    <div class="text-truncate text-body" style="font-size: 12px;" x-text="form.taluka || '—'"></div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">District</label>
                                                                    <div class="text-truncate text-body" style="font-size: 12px;" x-text="form.city || '—'"></div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">State</label>
                                                                    <div class="text-truncate text-body" style="font-size: 12px;" x-text="form.state || '—'"></div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Pincode</label>
                                                                    <div class="fw-bold text-body" style="font-size: 12px;" x-text="form.pincode || '—'"></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>


                                        </div>
                                    </div>
                                </div>
                            </div>{{-- end col --}}

                        </div>{{-- end row --}}
                    </form>
                </div>{{-- end modal-body --}}

                <!-- Footer -->
                <div class="modal-footer border-top-0 p-4 bg-body-tertiary">
                    <button type="button" class="btn text-muted fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 1px;" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="warehouseForm" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" :disabled="saving">
                        <span x-show="saving" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        <span x-text="isEditing ? 'Save Changes' : 'Add Warehouse'"></span>
                    </button>
                </div>

            </div>{{-- end modal-content --}}
        </div>
    </div>

    <!-- ═══════════════════════ View Details Modal ════════════════════════════ -->
    <div class="modal fade" id="viewWarehouseModal" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content shadow-lg border-0 rounded-4">
                <div class="modal-header border-bottom-0 pb-0">
                    <div class="d-flex align-items-center gap-2">
                        <h5 class="modal-title fw-bold">Warehouse Details</h5>
                        <template x-if="viewData.is_default">
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Default</span>
                        </template>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3 pb-4">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="p-3 bg-body-tertiary rounded-3 h-100">
                                <h6 class="fw-bold mb-3 text-primary"><i class="bi bi-building me-2"></i>Basic Info</h6>
                                <table class="table table-sm table-borderless mb-0">
                                    <tbody>
                                        <tr><td class="text-muted w-50">Name</td><td class="fw-medium" x-text="viewData.name || '—'"></td></tr>
                                        <tr><td class="text-muted">Code</td><td class="font-monospace" x-text="viewData.code || '—'"></td></tr>
                                        <tr><td class="text-muted">Company</td><td x-text="viewData.company_name || '—'"></td></tr>
                                        <tr>
                                            <td class="text-muted">Status</td>
                                            <td>
                                                <span class="badge"
                                                      :class="viewData.status === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary'"
                                                      x-text="(viewData.status || '').toUpperCase()">
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-body-tertiary rounded-3 h-100">
                                <h6 class="fw-bold mb-3 text-info"><i class="bi bi-telephone me-2"></i>Contact Info</h6>
                                <table class="table table-sm table-borderless mb-0">
                                    <tbody>
                                        <tr><td class="text-muted w-50">Phone</td><td x-text="viewData.phone || '—'"></td></tr>
                                        <tr><td class="text-muted">Email</td><td x-text="viewData.email || '—'"></td></tr>
                                        <tr><td class="text-muted">Reference No.</td><td class="font-monospace" x-text="viewData.reference_no || '—'"></td></tr>
                                        <tr><td class="text-muted">GSTIN</td><td class="font-monospace" x-text="viewData.gstin || '—'"></td></tr>
                                        <tr><td class="text-muted">Seed Lic No.</td><td class="font-monospace" x-text="viewData.seed_lic_no || '—'"></td></tr>
                                        <tr><td class="text-muted">Pesti Lic No.</td><td class="font-monospace" x-text="viewData.pesti_lic_no || '—'"></td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="p-3 bg-body-tertiary rounded-3">
                                <h6 class="fw-bold mb-3 text-success"><i class="bi bi-geo-alt me-2"></i>Location</h6>
                                <table class="table table-sm table-borderless mb-0">
                                    <tbody>
                                        <tr><td class="text-muted" style="width: 25%;">Address Line 1</td><td x-text="viewData.address_line_1 || '—'"></td></tr>
                                        <tr><td class="text-muted">Address Line 2</td><td x-text="viewData.address_line_2 || '—'"></td></tr>
                                        <tr><td class="text-muted">Village / Locality</td><td x-text="viewData.village_name || '—'"></td></tr>
                                        <tr><td class="text-muted">Post Office</td><td x-text="viewData.post_office || '—'"></td></tr>
                                        <tr><td class="text-muted">Taluka</td><td x-text="viewData.taluka || '—'"></td></tr>
                                        <tr><td class="text-muted">City / District</td><td x-text="viewData.city || '—'"></td></tr>
                                        <tr><td class="text-muted">State</td><td x-text="viewData.state || '—'"></td></tr>
                                        <tr><td class="text-muted">Pincode</td><td class="font-monospace" x-text="viewData.pincode || '—'"></td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="p-3 bg-body-tertiary rounded-3 h-100">
                                <h6 class="fw-bold mb-3 text-warning"><i class="bi bi-box-seam me-2"></i>Inventory Overview</h6>
                                <div class="row text-center g-3">
                                    <div class="col-3">
                                        <div class="p-2 border rounded bg-body shadow-sm">
                                            <div class="small text-muted mb-1">Total SKUs</div>
                                            <div class="h5 mb-0 fw-bold" x-text="viewData.total_skus || 0"></div>
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="p-2 border rounded bg-body shadow-sm">
                                            <div class="small text-muted mb-1">Physical Stock</div>
                                            <div class="h5 mb-0 fw-bold text-body-emphasis" x-text="parseFloat(viewData.total_physical_stock || 0)"></div>
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="p-2 border rounded bg-body shadow-sm border-danger border-opacity-25">
                                            <div class="small text-muted mb-1">Reserved</div>
                                            <div class="h5 mb-0 fw-bold text-danger" x-text="parseFloat(viewData.total_reserved_stock || 0)"></div>
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="p-2 border rounded bg-body shadow-sm border-success border-opacity-25">
                                            <div class="small text-muted mb-1">Available</div>
                                            <div class="h5 mb-0 fw-bold text-success" x-text="Math.max(0, parseFloat(viewData.total_physical_stock || 0) - parseFloat(viewData.total_reserved_stock || 0))"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 mt-4">
                            <div class="p-3 bg-body-tertiary rounded-3 h-100">
                                <h6 class="fw-bold mb-3 text-primary"><i class="bi bi-cart-check me-2"></i>Order Fulfillment Overview</h6>
                                <div class="row text-center g-3">
                                    <div class="col-4">
                                        <div class="p-2 border rounded bg-body shadow-sm">
                                            <div class="small text-muted mb-1">Total Orders</div>
                                            <div class="h5 mb-0 fw-bold" x-text="viewData.total_orders || 0"></div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="p-2 border rounded bg-body shadow-sm border-success border-opacity-25">
                                            <div class="small text-muted mb-1">Fulfillable</div>
                                            <div class="h5 mb-0 fw-bold text-success" x-text="viewData.fulfillable_orders || 0"></div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="p-2 border rounded bg-body shadow-sm border-danger border-opacity-25">
                                            <div class="small text-muted mb-1">Unfulfillable</div>
                                            <div class="h5 mb-0 fw-bold text-danger" x-text="viewData.unfulfillable_orders || 0"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" @click="editItem(viewData); viewModalInstance.hide();">
                        <i class="bi bi-pencil me-1"></i> Edit
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>{{-- end x-data --}}
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    // Prevent dropdowns from being clipped by table-responsive or causing scrollbars
    document.addEventListener('show.bs.dropdown', function (e) {
        if (e.target.closest('.table-responsive')) {
            const instance = bootstrap.Dropdown.getOrCreateInstance(e.target);
            if (instance && typeof instance._getPopperConfig === 'function' && !instance._originalGetPopperConfig) {
                instance._originalGetPopperConfig = instance._getPopperConfig;
                instance._getPopperConfig = function () {
                    const config = this._originalGetPopperConfig();
                    config.strategy = 'fixed';
                    return config;
                };
            }
        }
    });
</script>
@endpush
