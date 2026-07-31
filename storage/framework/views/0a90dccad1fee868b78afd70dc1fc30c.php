<?php $__env->startSection('title', 'Tax Rates Management'); ?>
<?php $__env->startSection('page', 'catalog-tax-rates'); ?>

<?php $__env->startSection('content'); ?>
<div class="tax-rates-management" x-data="taxRatesTable" x-cloak>
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 mb-lg-5">
        <div>
            <h1 class="h3 mb-0">Tax Rates Management</h1>
            <p class="text-muted mb-0">Manage your catalog tax rates</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary" @click="exportData()">
                <i class="bi bi-download me-2"></i>Export
            </button>
            <button type="button" class="btn btn-primary" @click.prevent="openCreateModal()">
                <i class="bi bi-plus-lg me-2"></i>Add Tax Rate
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
                            <p class="h6 mb-0 text-muted">Total Tax Rates</p>
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
                            <p class="h6 mb-0 text-muted">Active Tax Rates</p>
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
                            <p class="h6 mb-0 text-muted">Inactive Tax Rates</p>
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
                    <h2 class="h5 card-title mb-0">Tax Rates Directory</h2>
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
            <div class="bulk-actions-bar p-3 bg-primary bg-opacity-10 border-bottom border-primary border-opacity-25" x-show="selectedItems.length > 0" x-transition>
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
                            <th @click="sortBy('name')" class="sortable">Name / Code</th>
                            <th @click="sortBy('rate')" class="sortable">Total GST (%)</th>
                            <th>CGST / SGST</th>
                            <th>IGST</th>
                            <th @click="sortBy('status')" class="sortable">Status</th>
                            <th style="width: 120px;" class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="paginatedItems.length === 0">
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <div x-show="isLoading" class="spinner-border text-primary" role="status"></div>
                                    <div x-show="!isLoading">
                                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                        No tax rates found.
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
                                    <div class="fw-medium text-body-emphasis" x-text="item.name || item.code"></div>
                                    
                                </td>
                                <td x-text="`${item.rate}%`"></td>
                                <td class="text-muted">
                                    <span x-text="`${(item.rate / 2).toFixed(1)}%`"></span> / 
                                    <span x-text="`${(item.rate / 2).toFixed(1)}%`"></span>
                                </td>
                                <td class="text-muted" x-text="`${item.rate}%`"></td>
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
    <div class="modal fade" id="taxRatesModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold" x-text="isEditing ? 'Edit Tax Rate' : 'Add Tax Rate'"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <form @submit.prevent="saveItem">
                        <!-- Card: Tax Rate Info -->
                        <div class="card border-0 shadow-sm mb-4 bg-body-tertiary">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="bi bi-percent"></i>
                                    </div>
                                    <h6 class="card-title mb-0 fw-bold">Tax Rate Details</h6>
                                </div>
                                
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label fw-medium text-muted small">Name / Code <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" x-model="form.name" required placeholder="e.g. GST 18%, VAT 5%">
                                        <div class="form-text text-muted" style="font-size: 0.75rem;">Primary identifier for this tax rate.</div>
                                    </div>
                                    
                                    <div class="col-12">
                                        <label class="form-label fw-medium text-muted small">Rate (%) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" x-model="form.rate" step="0.01" required placeholder="e.g. 18.00">
                                    </div>
                    
                                    <div class="col-12 mt-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   id="tax_status"
                                                   :checked="form.status === 'active'"
                                                   @change="form.status = $event.target.checked ? 'active' : 'inactive'">
                                            <label class="form-check-label fw-semibold" for="tax_status">
                                                Is Active
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-top-0 pt-0 px-0">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary px-4" :disabled="saving">
                                <span x-show="saving" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                <span x-text="isEditing ? 'Update Tax Rate' : 'Save Tax Rate'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/user/metis/resources/views/catalog/tax-rates/index.blade.php ENDPATH**/ ?>