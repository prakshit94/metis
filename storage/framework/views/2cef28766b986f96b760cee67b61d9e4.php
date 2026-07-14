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

    <!-- Main Directory Card -->
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="h5 card-title mb-0">Services Directory</h2>
                </div>
                <div class="col-auto">
                    <div class="position-relative">
                        <input type="search" 
                               class="form-control form-control-sm" 
                               placeholder="Search..."
                               x-model.debounce.300ms="searchQuery"
                               @input="filterData()"
                               style="width: 250px;">
                        <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th @click="sortBy('id')" class="sortable ps-4">ID</th>
                            <th @click="sortBy('code')" class="sortable">Code</th>
                            <th @click="sortBy('name')" class="sortable">Name</th>
                            <th>Description</th>
                            <th @click="sortBy('is_active')" class="sortable">Status</th>
                            <th style="width: 120px;" class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="items.length === 0">
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <div x-show="isLoading" class="spinner-border text-primary spinner-border-sm mb-2" role="status"></div>
                                    <div x-show="!isLoading">
                                        <i class="bi bi-gear-wide-connected fs-2 d-block mb-2 text-muted"></i>
                                        No shipping services found.
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <template x-for="item in items" :key="item.id">
                            <tr>
                                <td class="ps-4 text-muted" x-text="item.id"></td>
                                <td class="font-monospace fw-semibold text-secondary" x-text="item.code"></td>
                                <td class="fw-medium text-dark" x-text="item.name"></td>
                                <td x-text="item.description || '-'"></td>
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
                                    
                                    <div class="col-12 form-check form-switch ms-2">
                                        <input class="form-check-input" type="checkbox" role="switch" x-model="form.is_active" id="isActiveSwitch">
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
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/ubuntu/metis/resources/views/shipping/services.blade.php ENDPATH**/ ?>