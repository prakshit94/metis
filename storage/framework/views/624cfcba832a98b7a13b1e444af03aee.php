<?php $__env->startSection('title', 'Shipping Services'); ?>
<?php $__env->startSection('page', 'shipping-services'); ?>

<?php $__env->startSection('content'); ?>
<div class="shipping-services-management" x-data="shippingServices" x-cloak>
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 mb-lg-5">
        <div>
            <h1 class="h3 mb-0">Shipping Services</h1>
            <p class="text-muted mb-0">Manage logistics services, carriers, and delivery systems mapped to villages</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary" @click="openCreateModal()">
                <i class="bi bi-plus-lg me-2"></i>Add Service
            </button>
        </div>
    </div>

    <!-- Stats widgets -->
    <div class="row g-4 g-lg-5 mb-5">
        <div class="col-xl-4 col-md-4">
            <div class="card stats-card h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bi bi-gear-wide-connected"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Total Services</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.total">0</span></div>
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
                            <p class="h6 mb-0 text-muted">Active Services</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.active">0</span></div>
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
                            <p class="h6 mb-0 text-muted">Inactive Services</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.inactive">0</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 g-lg-5 mb-5 mb-lg-5 mb-xl-6">
        <!-- Trends Chart -->
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="h5 card-title mb-0">Service Activity</h2>
                </div>
                <div class="card-body p-3 p-lg-4">
                    <div id="serviceTrendsChart" style="height: 300px;"></div>
                </div>
            </div>
        </div>

        <!-- Status Distribution -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <h2 class="h5 card-title mb-0">Service Status</h2>
                </div>
                <div class="card-body p-3 p-lg-4">
                    <div id="statusChart" style="height: 200px;"></div>
                    <div class="mt-3">
                        <template x-for="status in statusStats" :key="status.name">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small" x-text="status.name"></span>
                                <div class="d-flex align-items-center">
                                    <span class="small text-muted me-2" x-text="`${status.percentage}%`"></span>
                                    <span class="small fw-medium" x-text="status.count"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Directory Card -->
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="h5 card-title mb-0">Services Directory</h2>
                </div>
                <div class="col-auto">
                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                        <div class="position-relative">
                            <input type="search" 
                                   class="form-control form-control-sm" 
                                   placeholder="Search..."
                                   x-model.debounce.300ms="searchQuery"
                                   @input="filterData()"
                                   style="width: 200px;">
                            <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted"></i>
                        </div>
                        <select class="form-select form-select-sm"
                                x-model.number="itemsPerPage"
                                @change="filterData()"
                                style="width: 120px;">
                            <option value="10">10 / page</option>
                            <option value="25">25 / page</option>
                            <option value="50">50 / page</option>
                            <option value="100">100 / page</option>
                        </select>
                        <!-- Advanced Filters Trigger -->
                        <button class="btn btn-sm"
                                :class="hasActiveAdvancedFilters() ? 'btn-primary' : 'btn-outline-secondary'"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#advancedFilters"
                                aria-expanded="false">
                            <i class="bi bi-funnel me-1"></i>Filters
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Collapsible Advanced Filters Drawer -->
        <div class="collapse" id="advancedFilters">
            <div class="p-3 bg-body-tertiary border-top border-bottom border-secondary-subtle">
                <div class="row g-3">
                    <!-- Status Filter -->
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-body-secondary">Status</label>
                        <select class="form-select form-select-sm" x-model="statusFilter" @change="filterData()">
                            <option value="">All Statuses</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>

                    <!-- Reset Filters -->
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="button" class="btn btn-sm btn-outline-secondary w-100 d-inline-flex align-items-center justify-content-center" @click="clearFilters()">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <!-- Bulk Actions Bar -->
            <div class="bulk-actions-bar p-3 bg-primary bg-opacity-10 border-bottom border-primary border-opacity-25"
                 x-show="selectedItems.length > 0" x-cloak>
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle-fill text-primary me-2"></i>
                        <span class="fw-medium text-primary">
                            <span x-text="selectedItems.length"></span> service<span x-show="selectedItems.length !== 1">s</span> selected
                        </span>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-primary" @click="openBulkAssignModal()">
                            <i class="bi bi-person-plus-fill me-1"></i>Assign Provider
                        </button>
                        <button class="btn btn-sm btn-success" @click="bulkAction('activate')">
                            <i class="bi bi-check-circle me-1"></i>Activate
                        </button>
                        <button class="btn btn-sm btn-warning" @click="bulkAction('deactivate')">
                            <i class="bi bi-x-circle me-1"></i>Deactivate
                        </button>
                        <button class="btn btn-sm btn-danger" @click="bulkAction('delete')">
                            <i class="bi bi-trash me-1"></i>Delete
                        </button>
                        <button class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center px-2" @click="selectedItems = []" title="Clear selection">
                            <i class="bi bi-x-lg" style="margin-left: 7px"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;" class="ps-3">
                                <input type="checkbox" 
                                       class="user-select-checkbox"
                                       @change="$event.isTrusted && toggleAll($event.target.checked)"
                                       :checked="selectedItems.length === items.length && items.length > 0">
                            </th>
                            <th @click="sortBy('id')" class="sortable">ID</th>
                            <th @click="sortBy('code')" class="sortable">Code</th>
                            <th @click="sortBy('name')" class="sortable">Name</th>
                            <th>Description</th>
                            <th>Service providers</th>
                            <th @click="sortBy('is_active')" class="sortable">Status</th>
                            <th style="width: 120px;" class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="items.length === 0">
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <div x-show="isLoading" class="spinner-border text-primary spinner-border-sm mb-2" role="status"></div>
                                    <div x-show="!isLoading">
                                        <i class="bi bi-gear-wide-connected fs-2 d-block mb-2 text-muted"></i>
                                        No shipping services found.
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <template x-for="item in items" :key="item.id">
                            <tr :class="{ 'table-primary': selectedItems.includes(String(item.id)) }">
                                <td class="ps-3">
                                    <input type="checkbox"
                                           class="user-select-checkbox"
                                           :value="String(item.id)"
                                           x-model="selectedItems">
                                </td>
                                <td class="text-muted" x-text="item.id"></td>
                                <td class="font-monospace fw-semibold text-secondary" x-text="item.code"></td>
                                <td class="fw-medium text-body" x-text="item.name"></td>
                                <td x-text="item.description || '-'"></td>
                                <td>
                                    <template x-if="item.providers?.length">
                                        <div class="d-flex flex-column gap-2">
                                            <template x-for="provider in item.providers" :key="provider.id">
                                                <div class="small">
                                                    <div class="fw-semibold" x-text="provider.name"></div>
                                                    <div class="text-muted" x-text="[provider.email, provider.phone, provider.department].filter(Boolean).join(' · ')"></div>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                    <span class="small text-muted" x-show="!item.providers?.length">Not assigned</span>
                                </td>
                                <td>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch"
                                               :checked="item.is_active" 
                                               @change="toggleActive(item)">
                                        <span class="small" :class="item.is_active ? 'text-success' : 'text-secondary'" x-text="item.is_active ? 'Active' : 'Inactive'"></span>
                                    </div>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
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
            <div class="d-flex justify-content-between align-items-center p-3 border-top" x-show="paginator.total > 0">
                <div class="text-muted small">
                    Showing <span x-text="paginator.from"></span> to 
                    <span x-text="paginator.to"></span> of 
                    <span x-text="paginator.total"></span> results
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
                        <li class="page-item" :class="{ 'disabled': currentPage === paginator.last_page }">
                            <a class="page-link" href="#" @click.prevent="goToPage(currentPage + 1)">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Form Modal -->
    <div class="modal fade" id="servicesModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold" x-text="isEditing ? 'Edit Shipping Service' : 'Add Shipping Service'"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <form @submit.prevent="saveItem">
                        <!-- Card: Service Info -->
                        <div class="card border-0 shadow-sm mb-4 bg-body-tertiary">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="bi bi-gear-wide-connected"></i>
                                    </div>
                                    <h6 class="card-title mb-0 fw-bold">Service Information</h6>
                                </div>
                                
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label fw-medium text-muted small">Service Code <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control font-monospace" x-model="form.code" required placeholder="e.g. BLUEDART">
                                        <div class="form-text text-muted" style="font-size: 0.75rem;">Unique code representing the carrier service.</div>
                                    </div>
                                    
                                    <div class="col-12">
                                        <label class="form-label fw-medium text-muted small">Service Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" x-model="form.name" required placeholder="e.g. Blue Dart Logistics">
                                    </div>
                                    
                                    <div class="col-12">
                                        <label class="form-label fw-medium text-muted small">Description</label>
                                        <textarea class="form-control" rows="3" x-model="form.description" placeholder="Service priority, tier levels or notes..."></textarea>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fw-medium text-muted small">Service Providers</label>
                                        <div class="border rounded-2 p-2 bg-body border-secondary-subtle">
                                            <div class="position-relative mb-2">
                                                <input type="text" class="form-control form-control-sm border-secondary-subtle bg-body-tertiary pe-4" placeholder="Search providers..." x-model="providerSearch">
                                                <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted" style="font-size: 0.8rem;"></i>
                                            </div>
                                            <div style="max-height: 180px; overflow-y: auto;" class="custom-scrollbar px-1">
                                                <template x-for="user in filteredProviderUsers" :key="user.id">
                                                    <div class="d-flex align-items-center py-1 mb-1 border-bottom border-light cursor-pointer">
                                                        <input type="checkbox" class="me-2" style="cursor: pointer;" :value="String(user.id)" :id="'provider_' + user.id" x-model="form.provider_user_ids">
                                                        <label class="d-block mb-0 cursor-pointer w-100" style="cursor: pointer;" :for="'provider_' + user.id">
                                                            <div class="fw-medium text-body" x-text="user.name"></div>
                                                            <div class="text-muted small" style="font-size: 0.75rem;" x-text="[user.email, user.department].filter(Boolean).join(' · ')"></div>
                                                        </label>
                                                    </div>
                                                </template>
                                                <div x-show="filteredProviderUsers.length === 0" class="text-muted small p-3 text-center">
                                                    No providers found matching your search.
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-text text-muted" style="font-size: 0.75rem;">Check the boxes to assign providers to this service.</div>
                                    </div>
                                    
                                    <div class="col-12 form-check ms-2 mt-3">
                                        <input class="form-check-input" type="checkbox" x-model="form.is_active" id="isActiveSwitch">
                                        <label class="form-check-label fw-semibold" for="isActiveSwitch">Is Active</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-top-0 pt-0 px-0">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary px-4" :disabled="saving">
                                <span x-show="saving" class="spinner-border spinner-border-sm me-1" role="status"></span>
                                <span x-text="isEditing ? 'Update Service' : 'Save Service'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Bulk Assign Provider Modal -->
    <div class="modal fade" id="bulkAssignModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">Assign Service Provider</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <form @submit.prevent="bulkAssignProvider">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Select Providers <span class="text-danger">*</span></label>
                            <div class="border rounded-2 p-2 bg-body border-secondary-subtle">
                                <div class="position-relative mb-2">
                                    <input type="text" class="form-control form-control-sm border-secondary-subtle bg-body-tertiary pe-4" placeholder="Search providers..." x-model="providerSearch">
                                    <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted" style="font-size: 0.8rem;"></i>
                                </div>
                                <div style="max-height: 180px; overflow-y: auto;" class="custom-scrollbar px-1">
                                    <template x-for="user in filteredProviderUsers" :key="user.id">
                                        <div class="d-flex align-items-center py-1 mb-1 border-bottom border-light cursor-pointer">
                                            <input type="checkbox" class="me-2" style="cursor: pointer;" :value="String(user.id)" :id="'bulk_provider_' + user.id" x-model="bulkAssignForm.provider_ids">
                                            <label class="d-block mb-0 cursor-pointer w-100" style="cursor: pointer;" :for="'bulk_provider_' + user.id">
                                                <div class="fw-medium text-body" x-text="user.name"></div>
                                                <div class="text-muted small" style="font-size: 0.75rem;" x-text="[user.email, user.department].filter(Boolean).join(' · ')"></div>
                                            </label>
                                        </div>
                                    </template>
                                    <div x-show="filteredProviderUsers.length === 0" class="text-muted small p-3 text-center">
                                        No providers found matching your search.
                                    </div>
                                </div>
                            </div>
                            <div class="form-text text-muted mt-2" style="font-size: 0.75rem;">These providers will be assigned to all selected services.</div>
                        </div>
                        <div class="modal-footer border-top-0 pt-0 px-0">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="saving">
                                <span x-show="saving" class="spinner-border spinner-border-sm me-1" role="status"></span>
                                Assign
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/user/metis/resources/views/shipping/services.blade.php ENDPATH**/ ?>