@extends('layouts.app')

@section('title', 'Categories Management')
@section('page', 'catalog-categories')

@section('content')
<div class="categories-management" x-data="categoriesTable" x-cloak>
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 mb-lg-5">
        <div>
            <h1 class="h3 mb-0">Categories Management</h1>
            <p class="text-muted mb-0">Manage your catalog categories</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary" @click="exportData()">
                <i class="bi bi-download me-2"></i>Export
            </button>
            <button type="button" class="btn btn-primary" @click.prevent="openCreateModal()">
                <i class="bi bi-plus-lg me-2"></i>Add Category
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
                            <i class="bi bi-folder"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Total Categories</p>
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
                            <p class="h6 mb-0 text-muted">Active Categories</p>
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
                            <p class="h6 mb-0 text-muted">Inactive Categories</p>
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
                    <h2 class="h5 card-title mb-0">Categories Directory</h2>
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
                        <select x-select class="form-select form-select-sm" 
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
            <div class="bulk-actions-bar p-3 bg-body-secondary border-bottom" x-show="selectedItems.length > 0" x-transition>
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
                    <thead>
                        <tr>
                            <th style="width: 40px;" class="ps-4">
                                <input type="checkbox" 
                                       class="user-select-checkbox" 
                                       @change="$event.isTrusted && toggleAll($event.target.checked)"
                                       :checked="selectedItems.length === paginatedItems.length && paginatedItems.length > 0">
                            </th>
                            <th @click="sortBy('id')" class="sortable" style="width: 80px;">ID</th>
                            <th @click="sortBy('name')" class="sortable">Category Name</th>
                            <th>Parent Category</th>
                            <th class="text-center">Products</th>
                            <th @click="sortBy('status')" class="sortable">Status</th>
                            <th style="width: 120px;" class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="paginatedItems.length === 0">
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <div x-show="isLoading" class="spinner-border text-primary" role="status"></div>
                                    <div x-show="!isLoading">
                                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                        No categories found.
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
                                    <div class="d-flex align-items-center gap-3">
                                        <template x-if="item.image">
                                            <img :src="`/storage/${item.image}`" class="rounded object-cover border" style="width: 32px; height: 32px;" x-on:error="$el.src='/assets/images/product-placeholder.svg'">
                                        </template>
                                        <template x-if="!item.image">
                                            <img src="/assets/images/product-placeholder.svg" class="rounded object-cover border" style="width: 32px; height: 32px;" alt="No image">
                                        </template>
                                        <div class="fw-semibold text-body-emphasis" x-text="item.name"></div>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-muted small" x-text="item.parent ? item.parent.name : '-'"></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle" x-text="item.products_count || 0"></span>
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
        </div>
    </div>

    <!-- Form Modal -->
    <div class="modal fade" id="categoriesModal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold" x-text="isEditing ? 'Edit Category' : 'Add Category'"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <form @submit.prevent="saveItem" enctype="multipart/form-data">
                        <!-- Card 1: Category Info -->
                        <div class="card border-0 shadow-sm mb-4 bg-body-tertiary">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="bi bi-folder-fill"></i>
                                    </div>
                                    <h6 class="card-title mb-0 fw-bold">General Information</h6>
                                </div>
                                
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label fw-medium text-muted small">Category Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" x-model="form.name" required placeholder="e.g. Smartphones, T-Shirts">
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fw-medium text-muted small">Parent Category</label>
                                        <select x-select class="form-select" x-model="form.parent_id">
                                            <option value="">None (Root Category)</option>
                                            <template x-for="cat in parentCategories" :key="cat.id">
                                                <option :value="String(cat.id)" x-text="cat.name" :selected="cat.id == form.parent_id"></option>
                                            </template>
                                        </select>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fw-medium text-muted small">Status</label>
                                        <select x-select class="form-select" x-model="form.status">
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: Media & Settings -->
                        <div class="card border-0 shadow-sm mb-4 bg-body-tertiary">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-warning bg-opacity-10 text-warning rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="bi bi-image-fill"></i>
                                    </div>
                                    <h6 class="card-title mb-0 fw-bold">Media & Settings</h6>
                                </div>

                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label fw-medium text-muted small">Category Image</label>
                                        <div class="border border-dashed rounded p-3 text-center bg-body-secondary d-flex flex-column align-items-center justify-content-center" style="min-height: 120px; border-style: dashed !important;">
                                            <div class="mb-2">
                                                <template x-if="imagePreview || form.image">
                                                    <div class="position-relative d-inline-block">
                                                        <img :src="imagePreview || `/storage/${form.image}`" alt="Preview" class="rounded border shadow-sm" style="width: 80px; height: 80px; object-fit: cover;">
                                                        <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-0 translate-middle rounded-circle p-0 d-flex align-items-center justify-content-center" style="width: 20px; height: 20px;" @click="clearImage()">
                                                            <i class="bi bi-x" style="font-size: 14px; line-height: 1;"></i>
                                                        </button>
                                                    </div>
                                                </template>
                                                <template x-if="!imagePreview && !form.image">
                                                    <div class="position-relative d-inline-block">
                                                        <img src="/assets/images/product-placeholder.svg" alt="Preview Placeholder" class="rounded border shadow-sm opacity-50" style="width: 80px; height: 80px; object-fit: cover;">
                                                        <i class="bi bi-cloud-arrow-up fs-4 text-muted position-absolute top-50 start-50 translate-middle"></i>
                                                    </div>
                                                </template>
                                            </div>
                                            <input type="file" class="form-control form-control-sm" id="categoryImageInput" accept="image/*" @change="onFileChange($event)">
                                            <small class="text-muted mt-1" style="font-size: 0.7rem;">JPEG/PNG/WebP, max 2MB</small>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="p-2 border rounded bg-body-secondary">
                                            <div class="form-check form-switch m-0 d-flex align-items-center justify-content-between">
                                                <label class="form-check-label fw-medium small" for="isActiveSwitch">Active Flag</label>
                                                <input class="form-check-input m-0" type="checkbox" role="switch" x-model="form.is_active" id="isActiveSwitch">
                                            </div>
                                        </div>
                                        <div class="form-text text-muted" style="font-size: 0.75rem;">Determine if the category is visible in active list.</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer border-top-0 pt-0 px-0">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary px-4" :disabled="saving">
                                <span x-show="saving" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                <span x-text="isEditing ? 'Update Category' : 'Save Category'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
