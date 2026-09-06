@extends('layouts.app')

@section('title', 'Shipping Settings')
@section('page', 'shipping-settings')

@section('content')
<div class="shipping-settings" x-data="shippingSettings" x-cloak>

    <!-- ═══════════════════════ Page Header ════════════════════════════════ -->
    <div class="d-flex justify-content-between align-items-center mb-4 mb-lg-5">
        <div>
            <h1 class="h3 mb-0">India Post Configuration</h1>
            <p class="text-muted mb-0">Manage independent post office branches and API credentials</p>
        </div>
        
    </div>

    <!-- ═══════════════════════ Stats Widgets ══════════════════════════════ -->
    <div class="row g-4 g-lg-5 mb-5">
        <div class="col-xl-4 col-lg-4 col-md-4">
            <div class="card stats-card" style="cursor: default;">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bi bi-building"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Total Offices</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="form.india_post_offices.length"></span></div>
                            <small class="text-muted">All configured branches</small>
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
                            <p class="h6 mb-0 text-muted">Active Offices</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="form.india_post_offices.filter(o => o.status === 'active').length"></span></div>
                            <small class="text-success"><i class="bi bi-arrow-right me-1"></i>Currently operational</small>
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
                            <i class="bi bi-dash-circle"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Inactive Offices</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="form.india_post_offices.filter(o => o.status === 'inactive').length"></span></div>
                            <small class="text-secondary">Disabled routing</small>
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
                    <h2 class="h5 card-title mb-0">Offices Directory</h2>
                </div>
                <div class="col-auto mt-3 mt-md-0">
                    <div class="d-flex gap-2 flex-wrap justify-content-end">
                        <div class="position-relative">
                            <input type="search" 
                                   class="form-control form-control-sm" 
                                   placeholder="Search offices..."
                                   x-model="searchQuery"
                                   style="width:250px;">
                            <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted"></i>
                        </div>
                        <select class="form-select form-select-sm" x-model="statusFilter" style="width:160px;">
                            <option value="">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <button type="button" class="btn btn-sm btn-primary ms-2" @click.prevent="openOfficeModal()">
                            <i class="bi bi-plus-lg me-1"></i> Add Office
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <!-- Bulk Actions Bar matching PO -->
            <div class="bulk-actions-bar p-3 bg-primary bg-opacity-10 border-bottom border-primary border-opacity-25" x-show="selectedOffices.length > 0" x-transition style="display: none;">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle-fill text-primary me-2"></i>
                        <span class="fw-medium text-primary">
                            <span x-text="selectedOffices.length"></span> item(s) selected
                        </span>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-success fw-medium shadow-sm" @click="bulkAction('active')">
                            <i class="bi bi-check-circle me-1"></i>Mark Active
                        </button>
                        <button type="button" class="btn btn-sm btn-warning fw-medium shadow-sm" @click="bulkAction('inactive')">
                            <i class="bi bi-x-circle me-1"></i>Mark Inactive
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger fw-medium shadow-sm bg-body" @click="bulkAction('delete')">
                            <i class="bi bi-trash me-1"></i>Delete Selected
                        </button>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="table-responsive overflow-visible">
                <table class="table table-hover align-middle mb-0 text-nowrap">
                    <thead class="table-group-divider">
                        <tr>
                            <th class="ps-3" style="width:44px;">
                                <input type="checkbox" class="user-select-checkbox" @change="$event.isTrusted && toggleAll($event.target.checked)" :checked="selectedOffices.length === paginatedOffices.length && paginatedOffices.length > 0">
                            </th>
                            <th>Booking Office</th>
                            <th>IDs & Pincodes</th>
                            <th>Status</th>
                            <th style="width:90px;" class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="paginatedOffices.length === 0">
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                    No offices found matching your criteria.
                                </td>
                            </tr>
                        </template>
                        <template x-for="office in paginatedOffices" :key="office.id">
                            <tr :class="{ 'selected': selectedOffices.includes(office.id) }">
                                <td class="ps-3">
                                    <input type="checkbox" class="user-select-checkbox" :value="office.id" :checked="selectedOffices.includes(office.id)" @change="toggleOffice(office.id)">
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="fw-bold text-primary px-2 py-1" style="margin-left:-0.5rem;" x-text="office.booking_office_name"></div>
                                        <span x-show="office.is_default" class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle"><i class="bi bi-star-fill"></i> Default</span>
                                    </div>
                                    <div class="small text-muted mt-1">PIN: <span x-text="office.booking_office_pin"></span></div>
                                </td>
                                <td>
                                    <div class="small d-flex align-items-center gap-2">
                                        <i class="bi bi-hash text-muted"></i>
                                        <span>ID: <span class="fw-medium" x-text="office.pickup_dropoff_office_id"></span></span>
                                    </div>
                                    <div class="small d-flex align-items-center gap-2 mt-1">
                                        <i class="bi bi-geo-alt text-muted"></i>
                                        <span>Dropoff PIN: <span class="fw-medium" x-text="office.drop_off_pincode"></span></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge" 
                                          :class="office.status === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary'"
                                          x-text="(office.status || 'unknown').toUpperCase()"></span>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown" data-bs-boundary="window">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li>
                                                <a class="dropdown-item fw-medium" href="#" @click.prevent="viewOffice(office)">
                                                    <i class="bi bi-eye me-2 text-info"></i> View Details
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item fw-medium" href="#" @click.prevent="editOffice(office)">
                                                    <i class="bi bi-pencil me-2 text-primary"></i> Edit Details
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item text-danger" href="#" @click.prevent="deleteOffice(office.id)">
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
            <div class="d-flex justify-content-between align-items-center p-3 border-top" x-show="filteredOffices.length > 0">
                <div class="text-muted small">
                    Showing <span x-text="(currentPage - 1) * itemsPerPage + 1"></span> to 
                    <span x-text="Math.min(currentPage * itemsPerPage, filteredOffices.length)"></span> of 
                    <span x-text="filteredOffices.length"></span> results
                </div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item" :class="{ 'disabled': currentPage === 1 }">
                            <a class="page-link" href="#" @click.prevent="currentPage--">Previous</a>
                        </li>
                        <li class="page-item disabled">
                            <span class="page-link">Page <span x-text="currentPage"></span> of <span x-text="totalPages"></span></span>
                        </li>
                        <li class="page-item" :class="{ 'disabled': currentPage === totalPages }">
                            <a class="page-link" href="#" @click.prevent="currentPage++">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>


    <!-- View Details Modal -->
    <div class="modal fade" id="viewOfficeModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0 bg-body-tertiary">
                    <h5 class="modal-title fw-bold">Office Configuration Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3 bg-body-tertiary">
                    <template x-if="officeViewData">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="card mb-0 border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary">
                                    <div class="card-body p-3">
                                        <h6 class="mb-3 fw-bold text-uppercase text-body" style="font-size: 11px; letter-spacing: 1px;"><i class="bi bi-info-circle text-primary me-2"></i>Basic Information</h6>
                                        <table class="table table-sm table-borderless mb-0">
                                            <tbody>
                                                <tr><td class="text-muted fw-bold" style="width: 200px;">Booking Office Name</td><td class="fw-medium" x-text="officeViewData.booking_office_name || '—'"></td></tr>
                                                <tr><td class="text-muted fw-bold">Booking Pincode</td><td class="fw-medium" x-text="officeViewData.booking_office_pin || '—'"></td></tr>
                                                <tr><td class="text-muted fw-bold">Dropoff Pincode</td><td class="fw-medium" x-text="officeViewData.drop_off_pincode || '—'"></td></tr>
                                                <tr><td class="text-muted fw-bold">Pickup/Dropoff Office ID</td><td class="fw-medium" x-text="officeViewData.pickup_dropoff_office_id || '—'"></td></tr>
                                                <tr><td class="text-muted fw-bold">Status</td><td>
                                                    <span class="badge" :class="officeViewData.status === 'active' ? 'bg-success' : 'bg-secondary'" x-text="(officeViewData.status || '').toUpperCase()"></span>
                                                </td></tr>
                                                <tr><td class="text-muted fw-bold">Default Office</td><td>
                                                    <span class="badge bg-primary" x-show="officeViewData.is_default">Yes</span>
                                                    <span class="text-muted" x-show="!officeViewData.is_default">No</span>
                                                </td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="card mb-0 border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary">
                                    <div class="card-body p-3">
                                        <h6 class="mb-3 fw-bold text-uppercase text-body" style="font-size: 11px; letter-spacing: 1px;"><i class="bi bi-key text-success me-2"></i>API Credentials</h6>
                                        <table class="table table-sm table-borderless mb-0">
                                            <tbody>
                                                <tr><td class="text-muted fw-bold" style="width: 200px;">Base URL</td><td class="fw-medium text-primary text-break" x-text="officeViewData.api_base_url || '—'"></td></tr>
                                                <tr><td class="text-muted fw-bold">Username</td><td class="fw-medium" x-text="officeViewData.api_username || '—'"></td></tr>
                                                <tr><td class="text-muted fw-bold">Password</td><td class="fw-medium text-muted fst-italic">[Encrypted in Database]</td></tr>
                                                <tr><td class="text-muted fw-bold">Bulk Customer ID</td><td class="fw-medium" x-text="officeViewData.bulk_customer_id || '—'"></td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="card mb-0 border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary">
                                    <div class="card-body p-3">
                                        <h6 class="mb-3 fw-bold text-uppercase text-body" style="font-size: 11px; letter-spacing: 1px;"><i class="bi bi-file-earmark-text text-warning me-2"></i>Contract Details</h6>
                                        <table class="table table-sm table-borderless mb-0">
                                            <tbody>
                                                <tr><td class="text-muted fw-bold" style="width: 200px;">Speed Post Doc</td><td class="fw-medium" x-text="officeViewData.contract_sp_doc || '—'"></td></tr>
                                                <tr><td class="text-muted fw-bold">Speed Post Parcel</td><td class="fw-medium" x-text="officeViewData.contract_sp_parcel || '—'"></td></tr>
                                                <tr><td class="text-muted fw-bold">Business Parcel</td><td class="fw-medium" x-text="officeViewData.contract_bp || '—'"></td></tr>
                                                <tr><td class="text-muted fw-bold">24 SpeedPost Doc</td><td class="fw-medium" x-text="officeViewData.contract_24_sp_doc || '—'"></td></tr>
                                                <tr><td class="text-muted fw-bold">24 SPP Parcel</td><td class="fw-medium" x-text="officeViewData.contract_24_spp_parspl || '—'"></td></tr>
                                                <tr><td class="text-muted fw-bold">48 SpeedPost Doc</td><td class="fw-medium" x-text="officeViewData.contract_48_sp_doc || '—'"></td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
                <div class="modal-footer border-top-0 pt-3 bg-body-tertiary">
                    <button type="button" class="btn btn-secondary px-4 shadow-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Office Modal Matching PO Create Form UI -->
    <div class="modal fade" id="officeModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0 bg-body-tertiary">
                    <h5 class="modal-title fw-bold" x-text="isEditing ? 'Edit Office Configuration' : 'Add Office Configuration'"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3 bg-body-tertiary">
                    <form @submit.prevent="saveOffice" autocomplete="off">
                        <p class="small text-muted mb-3">Configure branch details and API credentials</p>

                        <div class="row g-3">
                            
                            {{-- Basic Details Card --}}
                            <div class="col-12 position-relative">
                                <div class="card mb-0 border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center gap-2 pb-2 mb-2 border-bottom">
                                            <div class="bg-primary bg-opacity-10 text-primary rounded-2 d-flex align-items-center justify-content-center" style="width: 20px; height: 20px;">
                                                <i class="bi bi-info-circle" style="font-size: 10px;"></i>
                                            </div>
                                            <h6 class="mb-0 fw-bold text-uppercase text-body" style="font-size: 11px; letter-spacing: 1px;">Basic Information</h6>
                                        </div>
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Booking Office Name *</label>
                                                <input type="text" class="form-control form-control-sm fw-semibold" x-model="officeForm.booking_office_name" placeholder="e.g. Bengaluru Foreign Post" style="font-size: 12px;" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Booking Pincode *</label>
                                                <input type="text" class="form-control form-control-sm fw-semibold" x-model="officeForm.booking_office_pin" placeholder="e.g. 560001" style="font-size: 12px;" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Dropoff Pincode *</label>
                                                <input type="text" class="form-control form-control-sm fw-semibold" x-model="officeForm.drop_off_pincode" placeholder="e.g. 560001" style="font-size: 12px;" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Pickup/Dropoff Office ID *</label>
                                                <input type="text" class="form-control form-control-sm fw-semibold" x-model="officeForm.pickup_dropoff_office_id" placeholder="8 digits ID" style="font-size: 12px;" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Status</label>
                                                <select class="form-select form-select-sm fw-semibold" x-model="officeForm.status" style="font-size: 12px;">
                                                    <option value="active">Active</option>
                                                    <option value="inactive">Inactive</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3 d-flex align-items-end">
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="isDefaultCheck" x-model="officeForm.is_default">
                                                    <label class="form-check-label fw-bold text-muted text-uppercase" for="isDefaultCheck" style="font-size: 9px; letter-spacing: 0.1em;">Set as Default Office</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- API Credentials Card --}}
                            <div class="col-12 position-relative">
                                <div class="card mb-0 border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center gap-2 pb-2 mb-2 border-bottom">
                                            <div class="bg-success bg-opacity-10 text-success rounded-2 d-flex align-items-center justify-content-center" style="width: 20px; height: 20px;">
                                                <i class="bi bi-key" style="font-size: 10px;"></i>
                                            </div>
                                            <h6 class="mb-0 fw-bold text-uppercase text-body" style="font-size: 11px; letter-spacing: 1px;">API Credentials</h6>
                                        </div>
                                        <div class="row g-2">
                                            <div class="col-md-12">
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Base URL *</label>
                                                <input type="url" class="form-control form-control-sm fw-semibold text-primary" x-model="officeForm.api_base_url" placeholder="https://test.cept.gov.in/beextcustomer" style="font-size: 12px;" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Username *</label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-body text-muted border-end-0"><i class="bi bi-person"></i></span>
                                                    <input type="text" class="form-control border-start-0 ps-0 fw-semibold bg-body" x-model="officeForm.api_username" placeholder="API Username" style="font-size: 12px;" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Password</label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-body text-muted border-end-0"><i class="bi bi-asterisk"></i></span>
                                                    <input :type="showPassword ? 'text' : 'password'" class="form-control border-start-0 border-end-0 ps-0 fw-semibold bg-body" x-model="officeForm.api_password" placeholder="Leave blank to keep unchanged" style="font-size: 12px;">
                                                    <button class="btn btn-outline-secondary border-start-0 bg-body" type="button" @click="showPassword = !showPassword">
                                                        <i class="bi" :class="showPassword ? 'bi-eye-slash' : 'bi-eye'"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="col-md-12 mt-3">
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Bulk Customer ID *</label>
                                                <input type="text" class="form-control form-control-sm fw-semibold" x-model="officeForm.bulk_customer_id" placeholder="e.g. 3000064781" style="font-size: 12px;" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Contracts Card --}}
                            <div class="col-12 position-relative">
                                <div class="card mb-0 border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center gap-2 pb-2 mb-2 border-bottom">
                                            <div class="bg-warning bg-opacity-10 text-warning rounded-2 d-flex align-items-center justify-content-center" style="width: 20px; height: 20px;">
                                                <i class="bi bi-file-earmark-text" style="font-size: 10px;"></i>
                                            </div>
                                            <h6 class="mb-0 fw-bold text-uppercase text-body" style="font-size: 11px; letter-spacing: 1px;">Contract Details</h6>
                                        </div>
                                        
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Speed Post Doc</label>
                                                <input type="text" class="form-control form-control-sm fw-semibold" x-model="officeForm.contract_sp_doc" style="font-size: 12px;">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Speed Post Parcel</label>
                                                <input type="text" class="form-control form-control-sm fw-semibold" x-model="officeForm.contract_sp_parcel" style="font-size: 12px;">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Business Parcel</label>
                                                <input type="text" class="form-control form-control-sm fw-semibold" x-model="officeForm.contract_bp" style="font-size: 12px;">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">24 SpeedPost Doc</label>
                                                <input type="text" class="form-control form-control-sm fw-semibold" x-model="officeForm.contract_24_sp_doc" style="font-size: 12px;">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">24 SPP Parcel</label>
                                                <input type="text" class="form-control form-control-sm fw-semibold" x-model="officeForm.contract_24_spp_parspl" style="font-size: 12px;">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">48 SpeedPost Doc</label>
                                                <input type="text" class="form-control form-control-sm fw-semibold" x-model="officeForm.contract_48_sp_doc" style="font-size: 12px;">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </form>
                </div>
                <div class="modal-footer border-top-0 pt-3 bg-body-tertiary">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary px-4 shadow-sm" @click="saveOffice()" :disabled="saving">
                        <span x-show="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
                        <i x-show="!saving" class="bi bi-floppy me-2"></i> <span x-text="saving ? 'Saving...' : (isEditing ? 'Update Office' : 'Save Office')"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alpine JS Logic -->
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('shippingSettings', () => ({
        form: {
            india_post_offices: {!! isset($settings['india_post_offices']) && is_string($settings['india_post_offices']) ? $settings['india_post_offices'] : '[]' !!},
        },
        showPassword: false,
        saving: false,
        
        searchQuery: '',
        statusFilter: '',
        selectedOffices: [],
        itemsPerPage: 10,
        currentPage: 1,
        
        isEditing: false,
        officeViewData: null,
        viewModalInstance: null,
        officeForm: {
            id: '',
            pickup_dropoff_office_id: '',
            drop_off_pincode: '',
            booking_office_name: '',
            booking_office_pin: '',
            status: 'active',
            is_default: false,
            api_base_url: '',
            api_username: '',
            api_password: '',
            bulk_customer_id: '',
            contract_sp_doc: '',
            contract_sp_parcel: '',
            contract_bp: '',
            contract_24_sp_doc: '',
            contract_24_spp_parspl: '',
            contract_48_sp_doc: ''
        },
        modalInstance: null,

        init() {
            if (typeof this.form.india_post_offices === 'string') {
                try {
                    this.form.india_post_offices = JSON.parse(this.form.india_post_offices);
                } catch(e) {
                    this.form.india_post_offices = [];
                }
            }
            if (!Array.isArray(this.form.india_post_offices)) {
                this.form.india_post_offices = [];
            }
        },

        get filteredOffices() {
            let result = this.form.india_post_offices;
            
            if (this.searchQuery) {
                const q = this.searchQuery.toLowerCase();
                result = result.filter(o => 
                    (o.booking_office_name || '').toLowerCase().includes(q) ||
                    (o.pickup_dropoff_office_id || '').toLowerCase().includes(q) ||
                    (o.api_username || '').toLowerCase().includes(q)
                );
            }
            if (this.statusFilter) {
                result = result.filter(o => o.status === this.statusFilter);
            }
            
            return result;
        },

        get paginatedOffices() {
            const start = (this.currentPage - 1) * this.itemsPerPage;
            const end = start + this.itemsPerPage;
            return this.filteredOffices.slice(start, end);
        },

        get totalPages() {
            return Math.ceil(this.filteredOffices.length / this.itemsPerPage) || 1;
        },

        toggleAll(checked) {
            if (checked) {
                this.selectedOffices = this.filteredOffices.map(o => o.id);
            } else {
                this.selectedOffices = [];
            }
        },

        toggleOffice(id) {
            if (this.selectedOffices.includes(id)) {
                this.selectedOffices = this.selectedOffices.filter(i => i !== id);
            } else {
                this.selectedOffices.push(id);
            }
        },

        async bulkAction(action) {
            if (action === 'delete') {
                if(confirm('Are you sure you want to delete selected offices?')) {
                    this.form.india_post_offices = this.form.india_post_offices.filter(o => !this.selectedOffices.includes(o.id));
                    await this.saveSettings('Selected offices deleted.');
                }
            } else if (action === 'active' || action === 'inactive') {
                this.form.india_post_offices = this.form.india_post_offices.map(o => {
                    if (this.selectedOffices.includes(o.id)) o.status = action;
                    return o;
                });
                await this.saveSettings('Offices status updated.');
            }
            this.selectedOffices = [];
        },
        
        getViewModal() {
            if (!this.viewModalInstance) {
                this.viewModalInstance = new bootstrap.Modal(document.getElementById('viewOfficeModal'));
            }
            return this.viewModalInstance;
        },
        viewOffice(office) {
            this.officeViewData = office;
            this.getViewModal().show();
        },
        getModal() {
            if (!this.modalInstance) {
                this.modalInstance = new bootstrap.Modal(document.getElementById('officeModal'));
            }
            return this.modalInstance;
        },

        openOfficeModal() {
            this.isEditing = false;
            this.showPassword = false;
            this.officeForm = {
                id: Date.now().toString(),
                pickup_dropoff_office_id: '',
                drop_off_pincode: '',
                booking_office_name: '',
                booking_office_pin: '',
                status: 'active',
                is_default: false,
                api_base_url: 'https://test.cept.gov.in/beextcustomer',
                api_username: '',
                api_password: '',
                bulk_customer_id: '',
                contract_sp_doc: '',
                contract_sp_parcel: '',
                contract_bp: '',
                contract_24_sp_doc: '',
                contract_24_spp_parspl: '',
                contract_48_sp_doc: ''
            };
            this.getModal().show();
        },

        editOffice(office) {
            this.isEditing = true;
            this.showPassword = false;
            this.officeForm = { ...office };
            this.getModal().show();
        },

        async deleteOffice(id) {
            if(confirm('Are you sure you want to delete this office?')) {
                this.form.india_post_offices = this.form.india_post_offices.filter(o => o.id !== id);
                await this.saveSettings('Office deleted successfully.');
            }
        },

        async saveOffice() {
            if (!this.officeForm.booking_office_name || !this.officeForm.pickup_dropoff_office_id || !this.officeForm.api_username || !this.officeForm.api_base_url) {
                alert('Please fill in all required fields.');
                return;
            }
            
            // Handle Default uniqueness
            if (this.officeForm.is_default) {
                this.form.india_post_offices.forEach(o => o.is_default = false);
            }
            
            if (this.isEditing) {
                const index = this.form.india_post_offices.findIndex(o => o.id === this.officeForm.id);
                if (index !== -1) {
                    this.form.india_post_offices[index] = { ...this.officeForm };
                }
            } else {
                this.form.india_post_offices.unshift({ ...this.officeForm });
            }
            
            this.getModal().hide();
            await this.saveSettings('Office saved successfully.');
        },

        async saveSettings(customMessage = null) {
            this.saving = true;
            try {
                const response = await fetch('/api/shipping/settings', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(this.form)
                });
                
                const data = await response.json();
                
                if (!response.ok) {
                    throw new Error(data.message || data.error || 'Failed to save settings.');
                }
                
                this.showToast(customMessage || data.message || 'Settings updated successfully.', 'success');
                
                // Clear all passwords from frontend state so they don't resend it next time
                this.form.india_post_offices.forEach(o => o.api_password = '');
                
            } catch (error) {
                this.showToast(error.message, 'error');
            } finally {
                this.saving = false;
            }
        },
        
        showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            if (!container) return;
            
            const iconMap = {
                success: 'bi-check-circle-fill',
                error: 'bi-x-circle-fill',
                warning: 'bi-exclamation-triangle-fill'
            };
            
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-bg-${type === 'error' ? 'danger' : type} border-0 show mb-2`;
            toast.setAttribute('role', 'alert');
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi ${iconMap[type] || 'bi-info-circle-fill'} me-2"></i>
                        <span>${message}</span>
                    </div>
                    <button type="button" class="btn-close btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>`;
                
            container.appendChild(toast);
            setTimeout(() => toast.remove(), 4000);
        }
    }));
});
</script>
@endsection