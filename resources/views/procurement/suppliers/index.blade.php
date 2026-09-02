@extends('layouts.app')

@section('title', 'Supplier Management')

@section('content')
<div class="container-fluid p-0" x-data="suppliersTable()">
    <!-- Header -->
    <div class="row mb-4 align-items-center">
        <div class="col-12 col-lg-6 mb-3 mb-lg-0">
            <h1 class="h3 mb-1 fw-bold text-body d-flex align-items-center">
                <i class="bi bi-building fs-4 me-2 text-primary"></i> Supplier Management
            </h1>
            <p class="text-muted mb-0">Manage suppliers, contacts, and tax details</p>
        </div>
        <div class="col-12 col-lg-6 text-lg-end">
            <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                @can('supplier-create')
                <button type="button" class="btn btn-primary d-inline-flex align-items-center shadow-sm" @click="openCreateModal()">
                    <i class="bi bi-plus-circle me-2"></i>Add Supplier
                </button>
                @endcan
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 g-lg-4 mb-4">
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden position-relative">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="text-muted text-uppercase fw-semibold" style="letter-spacing: 0.5px; font-size: 0.75rem;">Total Suppliers</div>
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="bi bi-building fs-5"></i>
                        </div>
                    </div>
                    <h3 class="mb-0 fw-bold display-6">{{ $stats['total'] }}</h3>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden position-relative">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="text-muted text-uppercase fw-semibold" style="letter-spacing: 0.5px; font-size: 0.75rem;">Active Suppliers</div>
                        <div class="bg-success bg-opacity-10 text-success rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="bi bi-check-circle fs-5"></i>
                        </div>
                    </div>
                    <h3 class="mb-0 fw-bold display-6">{{ $stats['active'] }}</h3>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden position-relative">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="text-muted text-uppercase fw-semibold" style="letter-spacing: 0.5px; font-size: 0.75rem;">Inactive Suppliers</div>
                        <div class="bg-warning bg-opacity-10 text-warning rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="bi bi-x-circle fs-5"></i>
                        </div>
                    </div>
                    <h3 class="mb-0 fw-bold display-6">{{ $stats['inactive'] }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-transparent border-bottom-0 py-3">
            <div class="row align-items-center g-3">
                <div class="col-12 col-md-5 col-lg-4">
                    <div class="input-group">
                        <span class="input-group-text bg-body-tertiary border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" class="form-control bg-body-tertiary border-start-0 ps-0" placeholder="Search suppliers..." x-model="filters.search" @input.debounce.500ms="fetchSuppliers()">
                    </div>
                </div>
                <div class="col-12 col-md-7 col-lg-8 text-md-end">
                    <div class="d-inline-flex gap-2 w-100 w-md-auto">
                        <select class="form-select bg-body-tertiary w-auto flex-grow-1 flex-md-grow-0" x-model.number="itemsPerPage" @change="fetchSuppliers()">
                            <option value="10">10 / page</option>
                            <option value="15">15 / page</option>
                            <option value="20">20 / page</option>
                            <option value="25">25 / page</option>
                            <option value="50">50 / page</option>
                        </select>
                        <select class="form-select bg-body-tertiary w-auto flex-grow-1 flex-md-grow-0" x-model="filters.status" @change="fetchSuppliers()">
                            <option value="">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <button class="btn btn-outline-secondary" @click="fetchSuppliers()">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk Actions Bar -->
        <div class="bulk-actions-bar p-3 bg-primary bg-opacity-10 border-bottom border-primary border-opacity-25" x-show="selectedSuppliers.length > 0" x-transition>
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <i class="bi bi-check-circle-fill text-primary me-2"></i>
                    <span class="fw-medium text-primary">
                        <span x-text="selectedSuppliers.length"></span> supplier<span x-show="selectedSuppliers.length !== 1">s</span> selected
                    </span>
                </div>
                <div class="d-flex gap-2">
                    @can('supplier-edit')
                    <button class="btn btn-sm btn-success" @click="bulkAction('active')">
                        <i class="bi bi-check-circle me-1"></i>Set Active
                    </button>
                    <button class="btn btn-sm btn-warning" @click="bulkAction('inactive')">
                        <i class="bi bi-x-circle me-1"></i>Set Inactive
                    </button>
                    @endcan
                    @can('supplier-delete')
                    <button class="btn btn-sm btn-danger" @click="bulkAction('delete')">
                        <i class="bi bi-trash me-1"></i>Delete
                    </button>
                    @endcan
                    <button class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center px-2" @click="selectedSuppliers = []" title="Clear selection">
                        <i class="bi bi-x-lg" style="margin-left: 7px;"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="table-responsive custom-scrollbar" x-data="{ dropdownOpen: false }" x-on:show.bs.dropdown="dropdownOpen = true" x-on:hide.bs.dropdown="dropdownOpen = false" :class="{ 'overflow-visible': dropdownOpen }" style="min-height: 250px;">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 50px;" class="ps-4">
                            <input type="checkbox" class="form-check-input" @change="toggleAll($event.target.checked)" :checked="selectedSuppliers.length === items.length && items.length > 0">
                        </th>
                        <th scope="col" role="button" @click="sortBy('company_name')" class="sortable text-uppercase text-muted" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                            Company / Name
                            <i class="bi bi-arrow-up ms-1" x-show="sortField === 'company_name' && sortDirection === 'asc'"></i>
                            <i class="bi bi-arrow-down ms-1" x-show="sortField === 'company_name' && sortDirection === 'desc'"></i>
                        </th>
                        <th scope="col" class="text-uppercase text-muted" style="font-size: 0.75rem; letter-spacing: 0.5px;">Contact Info</th>
                        <th scope="col" class="text-uppercase text-muted" style="font-size: 0.75rem; letter-spacing: 0.5px;">Tax Details</th>
                        <th scope="col" class="text-uppercase text-muted" style="font-size: 0.75rem; letter-spacing: 0.5px;">Status</th>
                        <th style="width: 80px;" class="text-end pe-4 text-uppercase text-muted" style="font-size: 0.75rem; letter-spacing: 0.5px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="item in items" :key="item.id">
                        <tr :class="{ 'table-active': selectedSuppliers.includes(item.id) }">
                            <td class="ps-4">
                                <input type="checkbox" class="form-check-input" :value="item.id" x-model="selectedSuppliers">
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width:40px;height:40px;">
                                        <i class="bi bi-building fs-5"></i>
                                    </div>
                                    <div>
                                        <a href="#" class="fw-bold text-decoration-none text-primary d-block mb-1" @click.prevent="openViewModal(item)" x-text="item.company_name || '—'"></a>
                                        <small class="text-muted"><i class="bi bi-person me-1"></i><span x-text="`${item.firstname || ''} ${item.lastname || ''}`.trim() || 'No contact name'"></span></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <div class="text-body d-flex align-items-center small"><i class="bi bi-envelope text-muted me-2"></i> <span x-text="item.email || '—'"></span></div>
                                    <div class="text-muted small d-flex align-items-center"><i class="bi bi-telephone text-muted me-2"></i> <span x-text="item.phone || '—'"></span></div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column gap-2 align-items-start">
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis shadow-sm fw-medium border border-secondary-subtle">GST: <span x-text="item.gst_no || 'N/A'"></span></span>
                                    <small class="text-muted d-flex align-items-center fw-medium" style="font-size: 11px;">PAN: <span class="ms-1" x-text="item.pan_no || 'N/A'"></span></small>
                                </div>
                            </td>
                            <td>
                                <span class="badge rounded-pill px-3 py-2 fw-medium" :class="item.status === 'active' ? 'bg-success-subtle text-success-emphasis border border-success-subtle' : 'bg-danger-subtle text-danger-emphasis border border-danger-subtle'" x-text="item.status ? item.status.toUpperCase() : 'UNKNOWN'"></span>
                            </td>
                            <td class="text-end pe-4">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle border" type="button" data-bs-toggle="dropdown" data-bs-boundary="window" aria-expanded="false" title="Supplier actions">
                                        <i class="bi bi-three-dots"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        <li>
                                            <a class="dropdown-item" href="#" @click.prevent="openViewModal(item)">
                                                <i class="bi bi-eye me-2 text-muted"></i>View
                                            </a>
                                        </li>
                                        @can('supplier-edit')
                                        <li>
                                            <a class="dropdown-item" href="#" @click.prevent="openEditModal(item)">
                                                <i class="bi bi-pencil me-2 text-muted"></i>Edit
                                            </a>
                                        </li>
                                        @endcan
                                        <li><hr class="dropdown-divider"></li>
                                        @can('supplier-delete')
                                        <li>
                                            <a class="dropdown-item text-danger" href="#" @click.prevent="deleteSupplier(item)">
                                                <i class="bi bi-trash me-2"></i>Delete
                                            </a>
                                        </li>
                                        @endcan
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    </template>
                    
                    <!-- Loading State -->
                    <tr x-show="loading" style="display: none;">
                        <td colspan="6" class="text-center py-5">
                            <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                            <span class="text-muted">Loading suppliers...</span>
                        </td>
                    </tr>
                    
                    <!-- Empty State -->
                    <tr x-show="!loading && items.length === 0" style="display: none;">
                        <td colspan="6" class="text-center py-5 text-muted">
                            <div class="bg-body-tertiary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                <i class="bi bi-building fs-1 text-secondary"></i>
                            </div>
                            <h5 class="mb-1 text-body">No Suppliers Found</h5>
                            <p class="mb-0">No suppliers match your current criteria.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="card-footer bg-transparent border-top-0 py-3" x-show="pagination.last_page > 1">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <span class="text-muted small">
                    Showing <span class="fw-semibold text-body" x-text="pagination.from || 0"></span> to <span class="fw-semibold text-body" x-text="pagination.to || 0"></span> of <span class="fw-semibold text-body" x-text="pagination.total || 0"></span> suppliers
                </span>
                <nav aria-label="Suppliers pagination">
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item" :class="{ 'disabled': pagination.current_page === 1 }">
                            <button class="page-link" @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page === 1">Previous</button>
                        </li>
                        <template x-for="page in visiblePages" :key="page">
                            <li class="page-item" :class="{ 'active': page === pagination.current_page, 'disabled': page === '...' }">
                                <button class="page-link" @click="page !== '...' && changePage(page)" x-text="page" :disabled="page === '...'"></button>
                            </li>
                        </template>
                        <li class="page-item" :class="{ 'disabled': pagination.current_page === pagination.last_page }">
                            <button class="page-link" @click="changePage(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page">Next</button>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Modals -->
    {{-- ═══════════════════════ Add / Edit Supplier Modal ═══════════════════════════ --}}
    <div class="modal fade" id="supplierModal" tabindex="-1" aria-labelledby="supplierModalLabel" aria-hidden="true" :class="{ 'view-mode-active': isViewMode }">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-bottom-0 pb-0" x-show="!isViewMode">
                    <h5 class="modal-title fw-bold" id="supplierModalLabel">
                        <span x-text="isEditing ? 'Edit Supplier' : 'Add New Supplier'"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body pt-3 pb-4">
                    <!-- VIEW PROFILE -->
                    <div x-show="isViewMode" style="display: none;" class="view-profile-container">
                        <div class="d-flex align-items-start justify-content-between mb-4 pb-4 border-bottom">
                            <div class="d-flex align-items-center gap-4">
                                <div class="position-relative">
                                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle border border-3 shadow-sm d-flex align-items-center justify-content-center" style="width: 100px; height: 100px; border-color: var(--bs-body-bg) !important;">
                                        <i class="bi bi-building" style="font-size: 2.5rem;"></i>
                                    </div>
                                    <span class="position-absolute bottom-0 end-0 p-2 border border-2 rounded-circle shadow-sm" :class="form.status === 'active' ? 'bg-success' : 'bg-danger'" style="width: 22px; height: 22px; right: 4px !important; bottom: 4px !important; border-color: var(--bs-body-bg) !important;"></span>
                                </div>
                                <div>
                                    <h3 class="mb-1 fw-bold text-body" x-text="form.company_name || '—'"></h3>
                                    <div class="text-muted mb-2 d-flex align-items-center gap-2" style="font-size: 0.95rem;">
                                        <span class="fw-medium text-body d-flex align-items-center gap-1"><i class="bi bi-person text-muted"></i> <span x-text="`${form.firstname || ''} ${form.lastname || ''}`.trim() || 'No Contact'"></span></span>
                                    </div>
                                    <span class="badge bg-primary-subtle text-primary-emphasis rounded-pill px-3 py-1 fw-medium border border-primary-subtle">SUPPLIER</span>
                                    <span class="badge ms-2 rounded-pill px-3 py-1 fw-medium" :class="form.status === 'active' ? 'bg-success-subtle text-success-emphasis border border-success-subtle' : 'bg-danger-subtle text-danger-emphasis border border-danger-subtle'" x-text="form.status ? form.status.toUpperCase() : 'UNKNOWN'"></span>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        
                        <div class="row g-4 mt-2">
                            <div class="col-lg-4">
                                <div class="card border-0 shadow-sm mb-4 bg-body-tertiary">
                                    <div class="card-header bg-transparent border-0 pt-4 pb-0">
                                        <h6 class="fw-bold text-uppercase text-muted mb-0" style="letter-spacing: 0.5px; font-size: 0.8rem;"><i class="bi bi-person-badge me-2"></i>Contact Details</h6>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-unstyled mb-0">
                                            <li class="d-flex align-items-center mb-4">
                                                <div class="bg-info-subtle text-info-emphasis rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                    <i class="bi bi-envelope-fill fs-5"></i>
                                                </div>
                                                <div>
                                                    <small class="text-muted d-block fw-medium" style="font-size: 0.75rem;">Email Address</small>
                                                    <a :href="`mailto:${form.email}`" class="fw-semibold text-body text-decoration-none" x-text="form.email || '—'"></a>
                                                </div>
                                            </li>
                                            <li class="d-flex align-items-center mb-0">
                                                <div class="bg-success-subtle text-success-emphasis rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                    <i class="bi bi-telephone-fill fs-5"></i>
                                                </div>
                                                <div>
                                                    <small class="text-muted d-block fw-medium" style="font-size: 0.75rem;">Phone Number</small>
                                                    <a :href="`tel:${form.phone}`" class="fw-semibold text-body text-decoration-none" x-text="form.phone || '—'"></a>
                                                </div>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-8">
                                <div class="card border-0 shadow-sm mb-4">
                                    <div class="card-header bg-transparent border-bottom pt-4 pb-3 px-4">
                                        <h6 class="fw-bold text-uppercase text-primary-emphasis mb-0" style="letter-spacing: 0.5px; font-size: 0.8rem;"><i class="bi bi-briefcase me-2"></i>Company Information</h6>
                                    </div>
                                    <div class="card-body p-4">
                                        <div class="row g-4 mb-4">
                                            <div class="col-sm-6">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="bg-body-secondary rounded p-2 text-body-secondary"><i class="bi bi-card-text"></i></div>
                                                    <div>
                                                        <small class="text-muted text-uppercase d-block fw-semibold mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">GST Number</small>
                                                        <span class="fw-semibold text-body" x-text="form.gst_no || '—'"></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="bg-body-secondary rounded p-2 text-body-secondary"><i class="bi bi-credit-card"></i></div>
                                                    <div>
                                                        <small class="text-muted text-uppercase d-block fw-semibold mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">PAN Number</small>
                                                        <span class="fw-semibold text-body" x-text="form.pan_no || '—'"></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="bg-body-secondary rounded p-2 text-body-secondary"><i class="bi bi-cash"></i></div>
                                                    <div>
                                                        <small class="text-muted text-uppercase d-block fw-semibold mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">Credit Limit</small>
                                                        <span class="fw-semibold text-body" x-text="form.credit_limit || '—'"></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="bg-body-secondary rounded p-2 text-body-secondary"><i class="bi bi-calendar"></i></div>
                                                    <div>
                                                        <small class="text-muted text-uppercase d-block fw-semibold mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">Credit Days</small>
                                                        <span class="fw-semibold text-body" x-text="form.credit_days || '—'"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                <!-- Address Details -->
                                <div class="card border-0 shadow-sm mb-4 bg-body-tertiary">
                                    <div class="card-header bg-transparent border-bottom pt-4 pb-3 px-4">
                                        <h6 class="fw-bold text-uppercase text-danger-emphasis mb-0" style="letter-spacing: 0.5px; font-size: 0.8rem;"><i class="bi bi-geo-alt me-2"></i>Address Details</h6>
                                    </div>
                                    <div class="card-body p-4">
                                        <div class="row g-4">
                                            <div class="col-sm-6">
                                                <small class="text-muted text-uppercase d-block fw-semibold mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">Address Line 1</small>
                                                <span class="fw-semibold text-body" x-text="form.address_line_1 || '—'"></span>
                                            </div>
                                            <div class="col-sm-6">
                                                <small class="text-muted text-uppercase d-block fw-semibold mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">Address Line 2</small>
                                                <span class="fw-semibold text-body" x-text="form.address_line_2 || '—'"></span>
                                            </div>
                                            <div class="col-sm-4">
                                                <small class="text-muted text-uppercase d-block fw-semibold mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">Village / City</small>
                                                <span class="fw-semibold text-body" x-text="form.village_name || form.city || '—'"></span>
                                            </div>
                                            <div class="col-sm-4">
                                                <small class="text-muted text-uppercase d-block fw-semibold mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">Post Office</small>
                                                <span class="fw-semibold text-body" x-text="form.post_office || '—'"></span>
                                            </div>
                                            <div class="col-sm-4">
                                                <small class="text-muted text-uppercase d-block fw-semibold mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">Taluka</small>
                                                <span class="fw-semibold text-body" x-text="form.taluka || '—'"></span>
                                            </div>
                                            <div class="col-sm-4">
                                                <small class="text-muted text-uppercase d-block fw-semibold mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">District</small>
                                                <span class="fw-semibold text-body" x-text="form.district || '—'"></span>
                                            </div>
                                            <div class="col-sm-4">
                                                <small class="text-muted text-uppercase d-block fw-semibold mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">State</small>
                                                <span class="fw-semibold text-body" x-text="form.state || '—'"></span>
                                            </div>
                                            <div class="col-sm-4">
                                                <small class="text-muted text-uppercase d-block fw-semibold mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">Pincode</small>
                                                <span class="fw-semibold text-body" x-text="form.pincode || '—'"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
    
                                <!-- Supplied Products -->
                                <div class="card border-0 shadow-sm mb-4 bg-body-tertiary">
                                    <div class="card-header bg-transparent border-bottom pt-4 pb-3 px-4">
                                        <h6 class="fw-bold text-uppercase text-success-emphasis mb-0" style="letter-spacing: 0.5px; font-size: 0.8rem;"><i class="bi bi-box-seam me-2"></i>Supplied Products</h6>
                                    </div>
                                    <div class="card-body p-4">
                                        <template x-if="!form.products || form.products.length === 0">
                                            <div class="text-center text-muted py-3">
                                                <i class="bi bi-inbox fs-3 d-block mb-2 text-secondary text-opacity-50"></i>
                                                <small>No products linked to this supplier.</small>
                                            </div>
                                        </template>
                                        <template x-if="form.products && form.products.length > 0">
                                            <div class="d-flex flex-wrap gap-2">
                                                <template x-for="product in form.products" :key="product.id">
                                                    <span class="badge bg-body text-body border shadow-sm px-3 py-2 d-inline-flex align-items-center">
                                                        <i class="bi bi-box me-2 text-primary"></i>
                                                        <span x-text="product.name"></span>
                                                        <small class="text-muted ms-2" x-text="`(${product.sku})`"></small>
                                                    </span>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                                        <div class="pt-3 border-top">
                                            <small class="text-muted text-uppercase d-block fw-semibold mb-2" style="font-size: 0.7rem; letter-spacing: 0.5px;">Internal Notes</small>
                                            <p class="mb-0 text-body" style="white-space: pre-wrap;" x-text="form.internal_notes || 'No internal notes recorded.'"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- EDIT/ADD FORM -->
                    <form id="supplierForm" @submit.prevent="submitForm" x-show="!isViewMode">
                        <fieldset :disabled="submitting">
                        <div class="row g-3">
                            <!-- Left Column -->
                            <div class="col-lg-8">
                                <!-- Card 1: Company Details -->
                                <div class="card border-0 shadow-sm mb-3 bg-body-tertiary">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
                                            <i class="bi bi-building text-primary fs-5 me-2"></i>
                                            <h6 class="card-title mb-0 fw-bold">Company Details</h6>
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-md-12">
                                                <label class="form-label fw-medium text-muted small">Company Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control form-control-sm" x-model="form.company_name" placeholder="e.g. Acme Corp" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium text-muted small">Contact First Name</label>
                                                <input type="text" class="form-control form-control-sm" x-model="form.firstname" placeholder="e.g. John">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium text-muted small">Contact Last Name</label>
                                                <input type="text" class="form-control form-control-sm" x-model="form.lastname" placeholder="e.g. Doe">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Card 2: Contact & Address -->
                                <div class="card border-0 shadow-sm mb-3 bg-body-tertiary" style="z-index: 10;">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
                                            <i class="bi bi-geo-alt-fill text-info fs-5 me-2"></i>
                                            <h6 class="card-title mb-0 fw-bold">Contact & Address</h6>
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium text-muted small">Email Address</label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-body-secondary"><i class="bi bi-envelope"></i></span>
                                                    <input type="email" class="form-control form-control-sm" x-model="form.email" placeholder="contact@example.com">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium text-muted small">Phone Number <span class="text-danger">*</span></label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-body-secondary"><i class="bi bi-telephone"></i></span>
                                                    <input type="tel" class="form-control form-control-sm" x-model="form.phone" required placeholder="10-digit number">
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium text-muted small">Address Line 1</label>
                                                <input type="text" class="form-control form-control-sm" placeholder="House/Flat No., Street" x-model="form.address_line_1">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium text-muted small">Address Line 2</label>
                                                <input type="text" class="form-control form-control-sm" placeholder="Landmark, Area" x-model="form.address_line_2">
                                            </div>
                                            
                                            <!-- Village Search -->
                                            <div class="col-12">
                                                <label class="form-label fw-medium text-muted small">Village / Area Search</label>
                                                <div class="position-relative">
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text bg-body-secondary"><i class="bi bi-search"></i></span>
                                                        <input type="text" class="form-control form-control-sm" placeholder="Type 3 letters to search village or area..." x-model="villageSearchQuery" @input.debounce.300ms="searchVillages()">
                                                    </div>
                                                    <div class="position-absolute w-100 dropdown-menu show shadow overflow-auto mt-1" style="max-height: 200px; z-index: 1060;" x-show="villageResults.length > 0">
                                                        <template x-for="v in villageResults" :key="v.id">
                                                            <button type="button" class="dropdown-item py-2 px-3 border-bottom text-wrap" @click="selectVillage(v)">
                                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                                    <span class="fw-bold text-primary" x-text="v.village_name"></span>
                                                                    <span class="badge bg-secondary" x-text="v.pincode"></span>
                                                                </div>
                                                                <div class="text-muted small">
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
                                            <template x-if="form.village_name">
                                                <div class="col-12">
                                                    <div class="card border border-info border-opacity-25 bg-info bg-opacity-10 shadow-sm mt-2">
                                                        <div class="card-body p-3">
                                                            <div class="row g-2 small">
                                                                <div class="col-md-4">
                                                                    <div class="fw-bold text-muted text-uppercase" style="font-size: 10px;">Village</div>
                                                                    <div class="fw-medium text-body" x-text="form.village_name || '—'"></div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <div class="fw-bold text-muted text-uppercase" style="font-size: 10px;">Post Office</div>
                                                                    <div class="fw-medium text-body" x-text="form.post_office || '—'"></div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <div class="fw-bold text-muted text-uppercase" style="font-size: 10px;">Taluka</div>
                                                                    <div class="fw-medium text-body" x-text="form.taluka || '—'"></div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <div class="fw-bold text-muted text-uppercase" style="font-size: 10px;">District</div>
                                                                    <div class="fw-medium text-body" x-text="form.district || '—'"></div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <div class="fw-bold text-muted text-uppercase" style="font-size: 10px;">State</div>
                                                                    <div class="fw-medium text-body" x-text="form.state || '—'"></div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <div class="fw-bold text-muted text-uppercase" style="font-size: 10px;">Pincode</div>
                                                                    <div class="fw-bold text-primary" x-text="form.pincode || '—'"></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>

                                            <template x-if="!form.village_name">
                                                <div class="col-12">
                                                    <div class="row g-3">
                                                        <div class="col-md-4">
                                                            <label class="form-label fw-medium text-muted small">City</label>
                                                            <input type="text" class="form-control form-control-sm" x-model="form.city">
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label fw-medium text-muted small">State</label>
                                                            <input type="text" class="form-control form-control-sm" x-model="form.state">
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label fw-medium text-muted small">Pincode</label>
                                                            <input type="text" class="form-control form-control-sm" x-model="form.pincode">
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Card 3: Financial & Tax -->
                                <div class="card border-0 shadow-sm mb-3 bg-body-tertiary">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
                                            <i class="bi bi-cash-stack text-success fs-5 me-2"></i>
                                            <h6 class="card-title mb-0 fw-bold">Financial & Tax</h6>
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium text-muted small">GST No</label>
                                                <input type="text" class="form-control form-control-sm" x-model="form.gst_no" placeholder="e.g. 22AAAAA0000A1Z5">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium text-muted small">PAN No</label>
                                                <input type="text" class="form-control form-control-sm" x-model="form.pan_no" placeholder="e.g. ABCDE1234F">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium text-muted small">Credit Limit</label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-body-secondary">₹</span>
                                                    <input type="number" step="0.01" min="0" class="form-control form-control-sm" x-model="form.credit_limit" placeholder="e.g. 50000">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium text-muted small">Credit Days</label>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" min="0" class="form-control form-control-sm" x-model="form.credit_days" placeholder="e.g. 30">
                                                    <span class="input-group-text bg-body-secondary">Days</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Right Column -->
                            <div class="col-lg-4">
                                <!-- Card 4: Status & Settings -->
                                <div class="card border-0 shadow-sm mb-3 bg-body-tertiary">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
                                            <i class="bi bi-gear-fill text-warning fs-5 me-2"></i>
                                            <h6 class="card-title mb-0 fw-bold">Status & Settings</h6>
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label fw-medium text-muted small">Account Status <span class="text-danger">*</span></label>
                                                <select class="form-select form-select-sm" x-model="form.status" required>
                                                    <option value="active">Active</option>
                                                    <option value="inactive">Inactive</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Card 5: Additional Details -->
                                <div class="card border-0 shadow-sm mb-3 bg-body-tertiary">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
                                            <i class="bi bi-journal-text text-secondary fs-5 me-2"></i>
                                            <h6 class="card-title mb-0 fw-bold">Additional Details</h6>
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label fw-medium text-muted small">Internal Notes</label>
                                                <textarea class="form-control form-control-sm" rows="5" x-model="form.internal_notes" placeholder="Any internal operational notes about this supplier..."></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </fieldset>
                    </form>
                </div>
                <div class="modal-footer border-top-0 bg-body-tertiary" x-show="!isViewMode">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="supplierForm" class="btn btn-primary px-4 d-inline-flex align-items-center" :disabled="submitting">
                        <span x-show="submitting" class="spinner-border spinner-border-sm me-2"></span>
                        <span x-text="isEditing ? 'Save Changes' : 'Create Supplier'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('suppliersTable', () => ({
            items: [],
            pagination: {},
            loading: false,
            submitting: false,
            isEditing: false,
            isViewMode: false,
            modalInstance: null,
            itemsPerPage: 15,
            selectedSuppliers: [],
            sortField: 'company_name',
            sortDirection: 'asc',
            filters: {
                search: '',
                status: ''
            },
                            form: {
                    id: null,
                    company_name: '',
                    firstname: '',
                    lastname: '',
                    email: '',
                    phone: '',
                    gst_no: '',
                    pan_no: '',
                    credit_limit: '',
                    credit_days: '',
                    internal_notes: '',
                    status: 'active',
                    address_line_1: '',
                    address_line_2: '',
                    village_id: null,
                    village_name: '',
                    post_office: '',
                    taluka: '',
                    district: '',
                    city: '',
                    state: '',
                    pincode: ''
                },
                villageSearchQuery: '',
                villageResults: [],

            
            async searchVillages() {
                if (!this.villageSearchQuery || this.villageSearchQuery.length < 3) {
                    this.villageResults = [];
                    return;
                }
                try {
                    const res = await fetch(`/api/villages/search?q=${encodeURIComponent(this.villageSearchQuery)}`, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await res.json();
                    this.villageResults = data.data || [];
                } catch (e) {
                    console.error('Village search failed:', e);
                }
            },

            selectVillage(v) {
                this.form.village_id = v.id;
                this.form.village_name = v.village_name || v.name || '';
                this.form.post_office = v.post_so_name || v.post_office || '';
                this.form.taluka = v.taluka_name || v.taluka || '';
                this.form.district = v.district_name || v.district || '';
                this.form.city = v.district_name || v.district || v.city || '';
                this.form.state = v.state_name || v.state || '';
                this.form.pincode = v.pincode || '';

                this.villageSearchQuery = '';
                this.villageResults = [];
            },

            init() {
                this.fetchSuppliers();
                this.modalInstance = new bootstrap.Modal(document.getElementById('supplierModal'));
            },

            async fetchSuppliers(page = 1) {
                this.loading = true;
                try {
                    const params = new URLSearchParams({
                        page: page,
                        per_page: this.itemsPerPage,
                        search: this.filters.search,
                        status: this.filters.status,
                        sort_by: this.sortField,
                        sort_dir: this.sortDirection
                    });
                    
                    const response = await fetch(`/procurement/suppliers?${params}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    
                    if (!response.ok) throw new Error('Network response was not ok');
                    
                    const data = await response.json();
                    this.items = data.data;
                    this.pagination = data;
                    this.selectedSuppliers = [];
                } catch (error) {
                    console.error('Error fetching suppliers:', error);
                } finally {
                    this.loading = false;
                }
            },

            sortBy(field) {
                if (this.sortField === field) {
                    this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
                } else {
                    this.sortField = field;
                    this.sortDirection = 'asc';
                }
                this.fetchSuppliers();
            },

            toggleAll(checked) {
                this.selectedSuppliers = checked ? this.items.map(item => item.id) : [];
            },

            async bulkAction(action) {
                if (!this.selectedSuppliers.length) return;
                const msgs = {
                    delete: 'delete',
                    active: 'activate',
                    inactive: 'deactivate'
                };
                if (!confirm(`Are you sure you want to ${msgs[action]} ${this.selectedSuppliers.length} supplier(s)?`)) return;

                try {
                    const response = await fetch(`/procurement/suppliers/bulk`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ action: action, ids: this.selectedSuppliers })
                    });
                    if (response.ok) {
                        this.fetchSuppliers(this.pagination.current_page);
                    } else {
                        const data = await response.json();
                        alert(data.message || 'Failed to process bulk action');
                    }
                } catch (error) {
                    console.error('Error in bulk action:', error);
                }
            },

            openCreateModal() {
                this.isEditing = false;
                this.isViewMode = false;
                this.resetForm();
                this.modalInstance.show();
            },

            openEditModal(item) {
                this.isEditing = true;
                this.isViewMode = false;
                this.form = { ...item };
                this.modalInstance.show();
            },

            openViewModal(item) {
                this.isEditing = false;
                this.isViewMode = true;
                this.form = { ...item };
                this.modalInstance.show();
            },

            resetForm() {
                this.form = { id: null, company_name: '', firstname: '', lastname: '', email: '', phone: '', gst_no: '', pan_no: '', credit_limit: '', credit_days: '', internal_notes: '', status: 'active', address_line_1: '', address_line_2: '', village_id: null, village_name: '', post_office: '', taluka: '', district: '', city: '', state: '', pincode: '' };
            },

            async submitForm() {
                this.submitting = true;
                const isUpdate = this.isEditing && this.form.id;
                const url = isUpdate ? `/procurement/suppliers/${this.form.id}` : '/procurement/suppliers';
                
                try {
                    const payload = { ...this.form };
                    if (isUpdate) payload._method = 'PUT';

                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(payload)
                    });

                    if (response.ok) {
                        this.modalInstance.hide();
                        this.fetchSuppliers(this.pagination.current_page);
                        this.resetForm();
                    } else {
                        const data = await response.json();
                        if (data.errors) {
                            const errs = Object.values(data.errors).flat().join('\n');
                            alert('Validation Error:\n' + errs);
                        } else {
                            alert(data.message || 'An error occurred while saving.');
                        }
                    }
                } catch (error) {
                    console.error('Submission error:', error);
                    alert('An error occurred. Please try again.');
                } finally {
                    this.submitting = false;
                }
            },

            async deleteSupplier(item) {
                if (!confirm(`Are you sure you want to delete ${item.company_name}?`)) return;
                
                try {
                    const response = await fetch(`/procurement/suppliers/${item.id}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });

                    if (response.ok) {
                        this.fetchSuppliers(this.pagination.current_page);
                    } else {
                        alert('Failed to delete supplier.');
                    }
                } catch (error) {
                    console.error('Delete error:', error);
                }
            },

            changePage(page) {
                if (page > 0 && page <= this.pagination.last_page) {
                    this.fetchSuppliers(page);
                }
            },

            get visiblePages() {
                if (!this.pagination.last_page) return [];
                const delta = 2;
                const range = [];
                for (let i = Math.max(2, this.pagination.current_page - delta); i <= Math.min(this.pagination.last_page - 1, this.pagination.current_page + delta); i++) {
                    range.push(i);
                }
                const result = [];
                if (this.pagination.current_page - delta > 2) result.push(1, '...');
                else result.push(1);
                result.push(...range);
                if (this.pagination.current_page + delta < this.pagination.last_page - 1) result.push('...', this.pagination.last_page);
                else if (this.pagination.last_page > 1) result.push(this.pagination.last_page);
                return result.filter((v, i, a) => a.indexOf(v) === i && (typeof v === 'string' || v <= this.pagination.last_page));
            }
        }));
    });

    // Fix for Bootstrap dropdowns getting cut off inside table-responsive
    document.addEventListener('show.bs.dropdown', function (e) {
        if (e.target.closest('.table-responsive')) {
            e.target.closest('.table-responsive').style.overflow = 'visible';
        }
    });
    document.addEventListener('hide.bs.dropdown', function (e) {
        if (e.target.closest('.table-responsive')) {
            e.target.closest('.table-responsive').style.overflow = '';
        }
    });
</script>
@endsection
