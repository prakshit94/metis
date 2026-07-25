<?php $__env->startSection('title', 'Warehouses Management'); ?>
<?php $__env->startSection('page', 'catalog-warehouses'); ?>

<?php $__env->startSection('content'); ?>
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
            <div>
                <table class="table table-hover align-middle mb-0 text-nowrap">
                    <thead class="table-light">
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
                            <th>Inventory</th>
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
                                        <i class="bi bi-geo-alt text-muted mt-1"></i>
                                        <span class="text-wrap" style="max-width: 250px;" x-text="[item.address_line_1, item.city, item.state, item.pincode].filter(Boolean).join(', ') || '—'"></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="small d-flex flex-column gap-1">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-box-seam text-muted"></i>
                                            <span class="fw-medium"><span x-text="item.total_skus || 0"></span> SKUs</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-stack text-muted"></i>
                                            <span class="text-muted"><span x-text="item.total_physical_stock || 0"></span> Units</span>
                                        </div>
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

        </div>
    </div>


    <!-- ═══════════════════════ Add / Edit Modal ════════════════════════════ -->
    <div class="modal fade" id="warehousesModal" tabindex="-1" aria-labelledby="warehousesModalLabel" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content shadow-lg border-0 rounded-4">

                <!-- Header -->
                <div class="modal-header border-bottom-0 pb-0">
                    <div class="d-flex align-items-center gap-2">
                        <h5 class="modal-title fw-bold" id="warehousesModalLabel"
                            x-text="isEditing ? 'Edit Warehouse' : 'Add Warehouse'"></h5>
                        <template x-if="isEditing && form.is_default">
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Default</span>
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
                                            <div class="col-md-6 d-flex align-items-end mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox"
                                                           id="wh_status"
                                                           :checked="form.status === 'active'"
                                                           @change="form.status = $event.target.checked ? 'active' : 'inactive'">
                                                    <label class="form-check-label fw-medium small" for="wh_status">
                                                        Is Active
                                                    </label>
                                                </div>
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
                                                <label class="form-label fw-medium text-muted small">Email Address</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                                    <input type="email" class="form-control"
                                                           x-model="form.email"
                                                           placeholder="warehouse@example.com">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium text-muted small">Reference No.</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="bi bi-hash"></i></span>
                                                    <input type="text" class="form-control"
                                                           x-model="form.reference_no"
                                                           placeholder="e.g. WH-REF-001">
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
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium text-muted small">Seed Lic No.</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="bi bi-card-checklist"></i></span>
                                                    <input type="text" class="form-control"
                                                           x-model="form.seed_lic_no"
                                                           placeholder="GAN/FSR220001380/2022-2023">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium text-muted small">Pesti Lic No.</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="bi bi-card-checklist"></i></span>
                                                    <input type="text" class="form-control"
                                                           x-model="form.pesti_lic_no"
                                                           placeholder="GAN/FP1220002020/2022-2023">
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox"
                                                           id="wh_default"
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

                                            <!-- Auto-filled / Editable fields -->
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium text-muted small">Village / Locality</label>
                                                <input type="text" class="form-control"
                                                       x-model="form.village_name"
                                                       placeholder="Village name">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium text-muted small">Post Office (BO)</label>
                                                <input type="text" class="form-control"
                                                       x-model="form.post_office"
                                                       placeholder="Post Office">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium text-muted small">Taluka</label>
                                                <input type="text" class="form-control"
                                                       x-model="form.taluka"
                                                       placeholder="Taluka">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium text-muted small">City / District</label>
                                                <input type="text" class="form-control"
                                                       x-model="form.city"
                                                       placeholder="City / District">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium text-muted small">State</label>
                                                <input type="text" class="form-control"
                                                       x-model="form.state"
                                                       placeholder="State">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium text-muted small">Pincode</label>
                                                <input type="text" class="form-control font-monospace"
                                                       x-model="form.pincode"
                                                       placeholder="Pincode"
                                                       maxlength="6">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </form>
                </div>

                <!-- Footer -->
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="warehouseForm" class="btn btn-primary px-4" :disabled="saving">
                        <span x-show="saving" class="spinner-border spinner-border-sm me-1" role="status"></span>
                        <span x-text="isEditing ? 'Update Warehouse' : 'Save Warehouse'"></span>
                    </button>
                </div>

            </div>
        </div>
    </div>

    <!-- ═══════════════════════ View Details Modal ════════════════════════════ -->
    <div class="modal fade" id="viewWarehouseModal" tabindex="-1" aria-hidden="true">
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
                                            <div class="h5 mb-0 fw-bold text-body-emphasis" x-text="viewData.total_physical_stock || 0"></div>
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="p-2 border rounded bg-body shadow-sm border-danger border-opacity-25">
                                            <div class="small text-muted mb-1">Reserved</div>
                                            <div class="h5 mb-0 fw-bold text-danger" x-text="viewData.total_reserved_stock || 0"></div>
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="p-2 border rounded bg-body shadow-sm border-success border-opacity-25">
                                            <div class="small text-muted mb-1">Available</div>
                                            <div class="h5 mb-0 fw-bold text-success" x-text="Math.max(0, (viewData.total_physical_stock || 0) - (viewData.total_reserved_stock || 0))"></div>
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

</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/user/metis/resources/views/catalog/warehouses/index.blade.php ENDPATH**/ ?>