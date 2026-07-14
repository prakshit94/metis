@extends('layouts.app')

@section('title', 'Warehouses Management')
@section('page', 'catalog-warehouses')

@section('content')
<div class="warehouses-management" x-data="warehousesTable" x-cloak>

    <!-- ═══════════════════════ Page Header ════════════════════════════════ -->
    <div class="d-flex justify-content-between align-items-center mb-4 mb-lg-5 mb-xl-6">
        <div>
            <h1 class="h3 mb-0">Warehouses</h1>
            <p class="text-muted mb-0">Manage fulfillment centres, addresses and operational details</p>
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

    <!-- ═══════════════════════ Stats Widgets ══════════════════════════════ -->
    <div class="row g-4 g-lg-5 g-xl-6 mb-5 mb-lg-5 mb-xl-6">
        <div class="col-xl-4 col-lg-4 col-md-4">
            <div class="card stats-card">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bi bi-buildings"></i>
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
            <div class="card stats-card">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-success bg-opacity-10 text-success me-3">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Active</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.active"></span></div>
                            <small class="text-success-emphasis">
                                <i class="bi bi-arrow-up"></i> Operational
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-lg-4 col-md-4">
            <div class="card stats-card">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-secondary bg-opacity-10 text-secondary me-3">
                            <i class="bi bi-dash-circle"></i>
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
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center g-2">
                <div class="col">
                    <h2 class="h5 card-title mb-0">Warehouses Directory</h2>
                </div>
                <div class="col-auto">
                    <div class="d-flex gap-2 flex-wrap">
                        <div class="position-relative">
                            <input type="search"
                                   class="form-control form-control-sm ps-4"
                                   placeholder="Search name, code, city, GSTIN…"
                                   x-model.debounce.300ms="searchQuery"
                                   @input="filterData()"
                                   style="width:260px">
                            <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-2 text-muted" style="font-size:.75rem;pointer-events:none"></i>
                        </div>
                        <select class="form-select form-select-sm" x-model="statusFilter" @change="filterData()" style="width:140px">
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
            <div class="bulk-actions-bar p-3 bg-light border-bottom" x-show="selectedItems.length > 0" x-transition>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted">
                        <span x-text="selectedItems.length"></span> item(s) selected
                    </span>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-success" @click="bulkAction('active')">
                            <i class="bi bi-check-circle me-1"></i>Mark Active
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" @click="bulkAction('inactive')">
                            <i class="bi bi-x-circle me-1"></i>Mark Inactive
                        </button>
                        <button class="btn btn-sm btn-outline-danger" @click="bulkAction('delete')">
                            <i class="bi bi-trash me-1"></i>Delete
                        </button>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:44px" class="ps-4">
                                <input type="checkbox" class="user-select-checkbox"
                                       @change="$event.isTrusted && toggleAll($event.target.checked)"
                                       :checked="selectedItems.length === paginatedItems.length && paginatedItems.length > 0">
                            </th>
                            <th @click="sortBy('id')" class="sortable" style="width:70px">ID</th>
                            <th @click="sortBy('name')" class="sortable">Warehouse</th>
                            <th>Contact Info</th>
                            <th>Address</th>
                            <th @click="sortBy('status')" class="sortable" style="width:110px">Status</th>
                            <th style="width:110px" class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Loading State -->
                        <template x-if="isLoading">
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="spinner-border text-primary" role="status"></div>
                                    <p class="text-muted small mt-2 mb-0">Loading warehouses…</p>
                                </td>
                            </tr>
                        </template>

                        <!-- Empty State -->
                        <template x-if="!isLoading && paginatedItems.length === 0">
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-buildings fs-1 d-block mb-3 opacity-25"></i>
                                    <p class="fw-semibold mb-1">No warehouses found</p>
                                    <p class="small mb-3">Get started by adding your first fulfillment centre.</p>
                                    <button class="btn btn-primary btn-sm" @click="openCreateModal()">
                                        <i class="bi bi-plus-lg me-1"></i>Add Warehouse
                                    </button>
                                </td>
                            </tr>
                        </template>

                        <!-- Data Rows -->
                        <template x-for="item in paginatedItems" :key="item.id">
                            <tr>
                                <td class="ps-4">
                                    <input type="checkbox" class="user-select-checkbox"
                                           :value="item.id"
                                           :checked="selectedItems.includes(item.id)"
                                           @change="toggleItem(item.id)">
                                </td>
                                <td class="text-muted small" x-text="item.id"></td>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="stats-icon bg-primary bg-opacity-10 text-primary flex-shrink-0" style="width:40px;height:40px;border-radius:0.5rem">
                                            <i class="bi bi-building"></i>
                                        </div>
                                        <div>
                                            <div class="fw-medium text-dark d-flex align-items-center gap-2">
                                                <span x-text="item.name"></span>
                                                <template x-if="item.is_default">
                                                    <span class="badge bg-warning text-dark" style="font-size:.65rem">Default</span>
                                                </template>
                                            </div>
                                            <small class="text-muted font-monospace" x-text="item.code || '—'"></small>
                                            <template x-if="item.company_name">
                                                <div class="small text-muted" x-text="item.company_name"></div>
                                            </template>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small">
                                        <template x-if="item.phone">
                                            <div class="d-flex align-items-center gap-1 mb-1">
                                                <i class="bi bi-telephone text-muted"></i>
                                                <span x-text="item.phone"></span>
                                            </div>
                                        </template>
                                        <template x-if="item.gstin">
                                            <div class="d-flex align-items-center gap-1">
                                                <i class="bi bi-card-text text-muted"></i>
                                                <span class="font-monospace" x-text="item.gstin"></span>
                                            </div>
                                        </template>
                                        <template x-if="!item.phone && !item.gstin">
                                            <span class="text-muted">—</span>
                                        </template>
                                    </div>
                                </td>
                                <td>
                                    <div class="small text-muted" x-text="[item.address_line_1, item.city, item.state, item.pincode].filter(Boolean).join(', ') || '—'"></div>
                                </td>
                                <td>
                                    <span class="badge rounded-pill"
                                          :class="{
                                              'bg-success-subtle text-success': item.status === 'active',
                                              'bg-secondary-subtle text-secondary': item.status === 'inactive'
                                          }"
                                          x-text="item.status"></span>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center gap-2" href="#" @click.prevent="editItem(item)">
                                                    <i class="bi bi-pencil text-primary"></i> Edit
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item text-danger d-flex align-items-center gap-2" href="#" @click.prevent="deleteItem(item)">
                                                    <i class="bi bi-trash"></i> Delete
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
            <div class="d-flex justify-content-between align-items-center p-3" x-show="filteredItems.length > 0">
                <div class="text-muted small">
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
    <div class="modal fade" id="warehousesModal" tabindex="-1" aria-labelledby="warehousesModalLabel" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">

                <!-- Header -->
                <div class="modal-header border-bottom-0 pb-0">
                    <div class="d-flex align-items-center gap-2">
                        <h5 class="modal-title fw-bold" id="warehousesModalLabel"
                            x-text="isEditing ? 'Edit Warehouse' : 'Add Warehouse'"></h5>
                        <template x-if="isEditing && form.is_default">
                            <span class="badge bg-warning text-dark">Default</span>
                        </template>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Body -->
                <div class="modal-body pt-3">
                    <form @submit.prevent="saveItem" id="warehouseForm" novalidate>
                        <div class="row g-4">

                            <!-- ── Column 1: Basic Info ─────────────────── -->
                            <div class="col-lg-6">

                                <!-- Identity Card -->
                                <div class="card border-0 shadow-sm mb-4 bg-body-tertiary">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="stats-icon bg-primary bg-opacity-10 text-primary me-3" style="width:36px;height:36px;border-radius:0.5rem;font-size:0.9rem">
                                                <i class="bi bi-building"></i>
                                            </div>
                                            <h6 class="card-title mb-0 fw-bold">Warehouse Identity</h6>
                                        </div>

                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label fw-medium text-muted small">
                                                    Warehouse Name <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" class="form-control"
                                                       x-model="form.name"
                                                       placeholder="e.g. Main Warehouse, Delhi Hub"
                                                       required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium text-muted small">Code</label>
                                                <input type="text" class="form-control font-monospace text-uppercase"
                                                       x-model="form.code"
                                                       placeholder="e.g. MAIN, DEL01"
                                                       maxlength="10">
                                                <div class="form-text">Auto-generated if blank.</div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium text-muted small">Status</label>
                                                <select class="form-select" x-model="form.status">
                                                    <option value="active">Active</option>
                                                    <option value="inactive">Inactive</option>
                                                </select>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label fw-medium text-muted small">Company / Legal Name</label>
                                                <input type="text" class="form-control"
                                                       x-model="form.company_name"
                                                       placeholder="e.g. ABC Logistics Pvt Ltd">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Contact Card -->
                                <div class="card border-0 shadow-sm bg-body-tertiary">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="stats-icon bg-info bg-opacity-10 text-info me-3" style="width:36px;height:36px;border-radius:0.5rem;font-size:0.9rem">
                                                <i class="bi bi-telephone"></i>
                                            </div>
                                            <h6 class="card-title mb-0 fw-bold">Contact & Compliance</h6>
                                        </div>

                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium text-muted small">Phone Number</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                                    <input type="tel" class="form-control"
                                                           x-model="form.phone"
                                                           placeholder="+91 98765 43210">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium text-muted small">GSTIN</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="bi bi-card-text"></i></span>
                                                    <input type="text" class="form-control font-monospace text-uppercase"
                                                           x-model="form.gstin"
                                                           placeholder="22AAAAA0000A1Z5"
                                                           maxlength="15">
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox"
                                                           role="switch" id="wh_default"
                                                           x-model="form.is_default">
                                                    <label class="form-check-label fw-medium small" for="wh_default">
                                                        Set as <strong>Default</strong> warehouse
                                                        <span class="text-muted">(used for all new orders)</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <!-- ── Column 2: Address ────────────────────── -->
                            <div class="col-lg-6">
                                <div class="card border-0 shadow-sm bg-body-tertiary h-100">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="stats-icon bg-success bg-opacity-10 text-success me-3" style="width:36px;height:36px;border-radius:0.5rem;font-size:0.9rem">
                                                <i class="bi bi-geo-alt"></i>
                                            </div>
                                            <h6 class="card-title mb-0 fw-bold">Address Details</h6>
                                        </div>

                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label fw-medium text-muted small">Address Line 1</label>
                                                <input type="text" class="form-control"
                                                       x-model="form.address_line_1"
                                                       placeholder="Building, Street, Plot No.">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label fw-medium text-muted small">Address Line 2</label>
                                                <input type="text" class="form-control"
                                                       x-model="form.address_line_2"
                                                       placeholder="Area, Landmark (optional)">
                                            </div>

                                            <!-- Village Autofill -->
                                            <div class="col-12">
                                                <label class="form-label fw-medium text-muted small">
                                                    <i class="bi bi-magic me-1 text-primary"></i>
                                                    Village / Locality
                                                    <span class="text-muted">(autofills city, state, pincode)</span>
                                                </label>
                                                <div class="position-relative">
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                                                        <input type="text" class="form-control"
                                                               x-model="villageSearchQuery"
                                                               @input="onVillageInput()"
                                                               @blur="setTimeout(() => { villageResults = [] }, 200)"
                                                               placeholder="Type village / town (min. 3 chars)…">
                                                        <button type="button" class="btn btn-outline-secondary"
                                                                x-show="form.village_id"
                                                                @click="clearVillage()"
                                                                title="Clear">
                                                            <i class="bi bi-x-lg"></i>
                                                        </button>
                                                    </div>

                                                    <!-- Loading spinner -->
                                                    <div class="position-absolute end-0 top-50 translate-middle-y me-5 pe-2"
                                                         x-show="villageSearchLoading" style="z-index:10">
                                                        <div class="spinner-border spinner-border-sm text-primary"></div>
                                                    </div>

                                                    <!-- Dropdown results -->
                                                    <div class="dropdown-menu w-100 shadow border-0 p-2 show mt-1"
                                                         x-show="villageResults.length > 0"
                                                         style="max-height:220px;overflow-y:auto;z-index:1055;border-radius:0.75rem">
                                                        <template x-for="v in villageResults" :key="v.id">
                                                            <button type="button"
                                                                    class="dropdown-item rounded-2 py-2 px-3 small"
                                                                    @mousedown.prevent="selectVillage(v)">
                                                                <div class="fw-semibold" x-text="v.village_name"></div>
                                                                <div class="text-muted" style="font-size:.75rem"
                                                                     x-text="[v.taluka_name, v.district_name, v.state_name, v.pincode].filter(Boolean).join(' › ')">
                                                                </div>
                                                            </button>
                                                        </template>
                                                    </div>
                                                </div>

                                                <!-- Selected village badge -->
                                                <div class="mt-2" x-show="form.village_id">
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2">
                                                        <i class="bi bi-geo-fill me-1"></i>
                                                        <span x-text="form.village_name"></span>
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Auto-filled fields -->
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium text-muted small">City / Taluka</label>
                                                <input type="text" class="form-control"
                                                       x-model="form.city"
                                                       placeholder="Auto-filled from village">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium text-muted small">Pincode</label>
                                                <input type="text" class="form-control font-monospace"
                                                       x-model="form.pincode"
                                                       placeholder="Auto-filled"
                                                       maxlength="6">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium text-muted small">State</label>
                                                <input type="text" class="form-control"
                                                       x-model="form.state"
                                                       placeholder="Auto-filled from village">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium text-muted small">Post Office</label>
                                                <input type="text" class="form-control"
                                                       x-model="form.post_office"
                                                       placeholder="Auto-filled">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>{{-- end col --}}

                        </div>{{-- end row --}}
                    </form>
                </div>{{-- end modal-body --}}

                <!-- Footer -->
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="warehouseForm" class="btn btn-primary px-4" :disabled="saving">
                        <span x-show="saving" class="spinner-border spinner-border-sm me-1" role="status"></span>
                        <span x-text="isEditing ? 'Update Warehouse' : 'Save Warehouse'"></span>
                    </button>
                </div>

            </div>{{-- end modal-content --}}
        </div>
    </div>

</div>{{-- end x-data --}}
@endsection
