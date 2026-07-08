@extends('layouts.app')

@section('title', 'Product Attributes Management')
@section('page', 'catalog-attributes')

@section('content')
<div class="attributes-management" x-data="attributesTable" x-cloak>
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 mb-lg-5">
        <div>
            <h1 class="h3 mb-0">Product Attributes Management</h1>
            <p class="text-muted mb-0">Manage your catalog product attributes</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary" @click="exportData()">
                <i class="bi bi-download me-2"></i>Export
            </button>
            <button type="button" class="btn btn-primary" @click.prevent="openCreateModal()">
                <i class="bi bi-plus-lg me-2"></i>Add Attribute
            </button>
        </div>
    </div>

    <!-- Stats Widgets -->
    <div class="row g-4 g-lg-5 mb-5">
        <div class="col-xl-4 col-md-4">
            <div class="card stats-card h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bi bi-collection"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Total Product Attributes</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.total"></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-4">
            <div class="card stats-card h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-success bg-opacity-10 text-success me-3">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Active Product Attributes</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.active"></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-4">
            <div class="card stats-card h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-secondary bg-opacity-10 text-secondary me-3">
                            <i class="bi bi-dash-circle"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Inactive Product Attributes</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.inactive"></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table Container -->
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="h5 card-title mb-0">Product Attributes Directory</h2>
                </div>
                <div class="col-auto">
                    <div class="d-flex gap-2">
                        <!-- Search -->
                        <div class="position-relative">
                            <input type="search" 
                                   class="form-control form-control-sm" 
                                   placeholder="Search..."
                                   x-model.debounce.300ms="searchQuery"
                                   @input="filterData()"
                                   style="width: 250px;">
                            <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted"></i>
                        </div>
                        
                        <!-- Status Filter -->
                        <select class="form-select form-select-sm" 
                                x-model="statusFilter" 
                                @change="filterData()"
                                style="width: 150px;">
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
                            <th style="width: 40px;" class="ps-4">
                                <input type="checkbox" 
                                       class="user-select-checkbox" 
                                       @change="$event.isTrusted && toggleAll($event.target.checked)"
                                       :checked="selectedItems.length === paginatedItems.length && paginatedItems.length > 0">
                            </th>
                            <th @click="sortBy('id')" class="sortable" style="width: 80px;">ID</th>
                            <th @click="sortBy('name')" class="sortable">Name</th>
                            <th @click="sortBy('type')" class="sortable">Type</th>
                            <th>Values</th>
                            <th @click="sortBy('is_filterable')" class="sortable">Filterable</th>
                            <th @click="sortBy('status')" class="sortable">Status</th>
                            <th style="width: 120px;" class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="paginatedItems.length === 0">
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <div x-show="isLoading" class="spinner-border text-primary" role="status"></div>
                                    <div x-show="!isLoading">
                                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                        No product attributes found.
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <template x-for="item in paginatedItems" :key="item.id">
                            <tr>
                                <td class="ps-4">
                                    <input type="checkbox" 
                                           class="user-select-checkbox" 
                                           :value="item.id"
                                           :checked="selectedItems.includes(item.id)"
                                           @change="toggleItem(item.id)">
                                </td>
                                <td class="text-muted" x-text="item.id"></td>
                                <td>
                                    <div class="fw-semibold text-dark" x-text="item.name"></div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-secondary border text-uppercase" x-text="item.type" style="font-size: 0.72rem;"></span>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1" style="max-width: 250px;">
                                        <template x-for="val in (item.values || []).slice(0, 3)" :key="val.id">
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle" x-text="val.value" style="font-size: 0.7rem;"></span>
                                        </template>
                                        <template x-if="item.values && item.values.length > 3">
                                            <span class="text-muted" style="font-size: 0.7rem;" x-text="`+${item.values.length - 3} more`"></span>
                                        </template>
                                        <template x-if="!item.values || item.values.length === 0">
                                            <span class="text-muted italic" style="font-size: 0.72rem;">No values</span>
                                        </template>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge rounded-pill" 
                                          :class="{
                                              'bg-success-subtle text-success': item.is_filterable,
                                              'bg-secondary-subtle text-secondary': !item.is_filterable
                                          }"
                                          x-text="item.is_filterable ? 'Yes' : 'No'"></span>
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
                                                <a class="dropdown-item d-flex align-items-center gap-2" href="#" @click.prevent="openValueModal(item)">
                                                    <i class="bi bi-tag text-info"></i> Manage Values
                                                </a>
                                            </li>
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
        </div>
    </div>

    <!-- Form Modal -->
    <!-- Form Modal -->
    <div class="modal fade" id="attributesModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold" x-text="isEditing ? 'Edit Attribute' : 'Add Attribute'"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <form @submit.prevent="saveItem">
                        <!-- Card: Attribute Info -->
                        <div class="card border-0 shadow-sm mb-4 bg-body-tertiary">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="bi bi-tags-fill"></i>
                                    </div>
                                    <h6 class="card-title mb-0 fw-bold">Attribute Settings</h6>
                                </div>
                                
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label fw-medium text-muted small">Attribute Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" x-model="form.name" required placeholder="e.g. Color, Size, Material">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium text-muted small">Input Type <span class="text-danger">*</span></label>
                                        <select class="form-select" x-model="form.type" required>
                                            <option value="text">Text</option>
                                            <option value="color">Color</option>
                                            <option value="select">Select</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-medium text-muted small">Status</label>
                                        <select class="form-select" x-model="form.status">
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                    </div>

                                    <div class="col-12 mt-4">
                                        <div class="p-2 border rounded bg-body-secondary">
                                            <div class="form-check form-switch m-0 d-flex align-items-center justify-content-between">
                                                <label class="form-check-label fw-medium small" for="isFilterableSwitch">Filterable</label>
                                                <input class="form-check-input m-0" type="checkbox" role="switch" x-model="form.is_filterable" id="isFilterableSwitch">
                                            </div>
                                        </div>
                                        <div class="form-text text-muted" style="font-size: 0.75rem;">Show this attribute in frontend product filters.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-top-0 pt-0 px-0">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary px-4" :disabled="saving">
                                <span x-show="saving" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                <span x-text="isEditing ? 'Update Attribute' : 'Save Attribute'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Values Modal -->
    <div class="modal fade" id="valuesModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold" x-text="managingValues ? `Manage Values for ${managingValues.name}` : 'Manage Values'"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <!-- Card: Add Value -->
                    <div class="card border-0 shadow-sm mb-4 bg-body-tertiary">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-success bg-opacity-10 text-success rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="bi bi-plus-circle-fill"></i>
                                </div>
                                <h6 class="card-title mb-0 fw-bold">Add Attribute Value</h6>
                            </div>

                            <form @submit.prevent="addValue">
                                <div class="row g-2 align-items-end">
                                    <div class="col">
                                        <label class="form-label fw-medium text-muted small mb-1">New Value <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control form-control-sm" x-model="newValueForm.value" required placeholder="e.g. Red, XL, Cotton">
                                    </div>
                                    <template x-if="managingValues && managingValues.type === 'color'">
                                        <div class="col-auto" style="width: 80px;">
                                            <label class="form-label fw-medium text-muted small mb-1">Color</label>
                                            <input type="color" class="form-control form-control-sm form-control-color w-100" x-model="newValueForm.color_code" style="padding: 0.2rem; height: 31px;">
                                        </div>
                                    </template>
                                    <div class="col-auto">
                                        <button type="submit" class="btn btn-sm btn-primary px-3" :disabled="valueSaving" style="height: 31px;">
                                            <span x-show="valueSaving" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                            <span x-text="valueSaving ? 'Adding' : 'Add'"></span>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Card: Values List -->
                    <div class="card border-0 shadow-sm mb-4 bg-body-tertiary">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="bi bi-list-task"></i>
                                </div>
                                <h6 class="card-title mb-0 fw-bold">Existing Values</h6>
                            </div>

                            <div class="values-list" style="max-height: 250px; overflow-y: auto;">
                                <template x-if="!managingValues || !managingValues.values || managingValues.values.length === 0">
                                    <div class="text-center py-4 text-muted small">
                                        <i class="bi bi-tag-fill d-block mb-1 fs-4 text-muted" style="opacity: 0.5;"></i>
                                        No values defined yet.
                                    </div>
                                </template>
                                <div class="list-group">
                                    <template x-for="val in (managingValues ? managingValues.values : [])" :key="val.id">
                                        <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-3 bg-body-secondary border-0 mb-2 rounded">
                                            <div class="d-flex align-items-center gap-2">
                                                <template x-if="managingValues && managingValues.type === 'color'">
                                                    <div class="rounded-circle border" :style="`background-color: ${val.color_code}; width: 16px; height: 16px;`"></div>
                                                </template>
                                                <span class="fw-medium text-dark small" x-text="val.value"></span>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-link text-danger p-0" @click="deleteValue(val)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
