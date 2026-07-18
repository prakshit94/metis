<?php $__env->startSection('title', 'Stock Transfers'); ?>
<?php $__env->startSection('page', 'inventory-stock-transfers'); ?>

<?php $__env->startSection('content'); ?>
<div class="stock-transfers" x-data="stockTransfers" x-cloak>

    
    <div class="d-flex justify-content-between align-items-center mb-4 mb-lg-5">
        <div>
            <h1 class="h3 mb-0">Stock Transfers</h1>
            <p class="text-muted mb-0">Transfer and track stock between warehouse locations</p>
        </div>
        <button type="button" class="btn btn-primary" @click.prevent="openCreateModal()">
            <i class="bi bi-plus-lg me-2"></i>New Transfer
        </button>
    </div>

    
    <div class="row g-4 g-lg-5 mb-5">
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bi bi-arrow-left-right"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Total Transfers</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.total ?? 0"></span></div>
                            <small class="text-success-emphasis">
                                <i class="bi bi-info-circle"></i> Overall transactions
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-secondary bg-opacity-10 text-secondary me-3">
                            <i class="bi bi-pencil-fill"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Draft</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.draft ?? 0"></span></div>
                            <small class="text-secondary">
                                <i class="bi bi-clock-history"></i> Not dispatched yet
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-warning bg-opacity-10 text-warning me-3">
                            <i class="bi bi-truck"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">In Transit</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.pending ?? 0"></span></div>
                            <small class="text-warning">
                                <i class="bi bi-arrow-right-short"></i> Dispatched/on road
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-success bg-opacity-10 text-success me-3">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Received</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.received ?? 0"></span></div>
                            <small class="text-success">
                                <i class="bi bi-house-door-fill"></i> Completed transfers
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <div>
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h2 class="h5 card-title mb-0">Transfer Records</h2>
                    </div>
                    <div class="col-auto">
                        <div class="d-flex gap-2">
                            
                            <div class="position-relative">
                                <input type="search"
                                       class="form-control form-control-sm"
                                       placeholder="Search transfer no..."
                                       x-model.debounce.400ms="searchQuery"
                                       @input="loadData()"
                                       style="width: 220px;">
                                <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted"></i>
                            </div>
                            
                            <select class="form-select form-select-sm"
                                    x-model="statusFilter"
                                    @change="loadData()"
                                    style="width: 150px;">
                                <option value="">All Statuses</option>
                                <option value="draft">Draft</option>
                                <option value="sent">In Transit</option>
                                <option value="received">Received</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                </div>
            <div class="card-body p-0">
                <!-- Bulk Actions Bar -->
                <div class="bulk-actions-bar p-3 bg-primary bg-opacity-10 border-bottom border-primary border-opacity-25"
                     x-show="selectedItems.length > 0"
                     style="display: none;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-check-circle-fill text-primary me-2"></i>
                            <span class="fw-medium text-primary">
                                <span x-text="selectedItems.length"></span> transfer<span x-show="selectedItems.length !== 1">s</span> selected
                            </span>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-warning" @click="bulkAction('send')">
                                <i class="bi bi-truck me-1"></i>Dispatch Selected
                            </button>
                            <button class="btn btn-sm btn-success" @click="bulkAction('receive')">
                                <i class="bi bi-check-circle me-1"></i>Receive Selected
                            </button>
                            <button class="btn btn-sm btn-outline-danger" @click="bulkAction('cancel')">
                                <i class="bi bi-x-circle me-1"></i>Cancel Selected
                            </button>
                            <button class="btn btn-sm btn-danger" @click="bulkAction('delete')">
                                <i class="bi bi-trash me-1"></i>Delete Selected
                            </button>
                        </div>
                    </div>
                </div>

                
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;" class="ps-3">
                                    <input type="checkbox"
                                           class="user-select-checkbox"
                                           @change="$event.isTrusted && toggleAll($event.target.checked)"
                                           :checked="selectedItems.length === paginatedItems.length && paginatedItems.length > 0">
                                </th>
                                <th class="ps-2" style="width: 70px;"><i class="bi bi-hash me-1 text-secondary"></i>ID</th>
                                <th><i class="bi bi-file-earmark-text me-1 text-secondary"></i>Transfer No.</th>
                                <th><i class="bi bi-box-arrow-right me-1 text-secondary"></i>From Warehouse</th>
                                <th><i class="bi bi-box-arrow-in-right me-1 text-secondary"></i>To Warehouse</th>
                                <th class="text-center"><i class="bi bi-box-seam me-1 text-secondary"></i>Items Count</th>
                                <th><i class="bi bi-info-circle me-1 text-secondary"></i>Status</th>
                                <th><i class="bi bi-calendar-event me-1 text-secondary"></i>Date</th>
                                <th style="width: 120px;" class="text-end pe-4"><i class="bi bi-lightning-charge me-1 text-secondary"></i>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="paginatedItems.length === 0">
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted">
                                        <div x-show="isLoading" class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <div x-show="!isLoading">
                                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                            No transfers found matching filters.
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <template x-for="item in paginatedItems" :key="item.id">
                                <tr :class="{ 'selected': selectedItems.includes(item.id) }">
                                    <td class="ps-3">
                                        <input type="checkbox"
                                               class="user-select-checkbox"
                                               :value="item.id"
                                               :checked="selectedItems.includes(item.id)"
                                               @change="toggleItem(item.id)">
                                    </td>
                                    <td class="text-muted ps-2" x-text="item.id"></td>
                                    <td>
                                        <span class="fw-semibold font-monospace text-primary" x-text="item.transfer_no"></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border" x-text="item.from_warehouse?.name || '-'"></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border" x-text="item.to_warehouse?.name || '-'"></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle" x-text="item.items_count || 0"></span>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill"
                                              :class="{
                                                  'bg-secondary-subtle text-secondary': item.status === 'draft',
                                                  'bg-warning-subtle text-warning': item.status === 'sent',
                                                  'bg-success-subtle text-success': item.status === 'received',
                                                  'bg-danger-subtle text-danger': item.status === 'cancelled'
                                              }"
                                              x-text="item.status"></span>
                                    </td>
                                    <td>
                                        <small class="text-muted" x-text="item.created_at ? new Date(item.created_at).toLocaleDateString() : '-'"></small>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                                    type="button"
                                                    data-bs-toggle="dropdown">
                                                <i class="bi bi-three-dots"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                                <template x-if="item.status === 'draft'">
                                                    <li>
                                                        <a class="dropdown-item d-flex align-items-center gap-2" href="#" @click.prevent="editItem(item)">
                                                            <i class="bi bi-pencil text-primary"></i> Edit Transfer
                                                        </a>
                                                    </li>
                                                </template>
                                                <template x-if="item.status === 'draft'">
                                                    <li>
                                                        <a class="dropdown-item d-flex align-items-center gap-2" href="#" @click.prevent="sendTransfer(item)">
                                                            <i class="bi bi-truck text-warning"></i> Mark Dispatch
                                                        </a>
                                                    </li>
                                                </template>
                                                <template x-if="item.status === 'sent'">
                                                    <li>
                                                        <a class="dropdown-item d-flex align-items-center gap-2" href="#" @click.prevent="receiveTransfer(item)">
                                                            <i class="bi bi-check-circle text-success"></i> Mark Received
                                                        </a>
                                                    </li>
                                                </template>
                                                <template x-if="['draft','sent'].includes(item.status)">
                                                    <li><hr class="dropdown-divider"></li>
                                                </template>
                                                <template x-if="['draft','sent'].includes(item.status)">
                                                    <li>
                                                        <a class="dropdown-item text-danger d-flex align-items-center gap-2" href="#" @click.prevent="cancelTransfer(item)">
                                                            <i class="bi bi-x-circle"></i> Cancel Transfer
                                                        </a>
                                                    </li>
                                                </template>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                
                <div class="d-flex justify-content-between align-items-center p-3">
                    <div class="text-muted">
                        Showing <span x-text="pageFrom"></span> to
                        <span x-text="pageTo"></span> of
                        <span x-text="totalItems"></span> results
                    </div>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item" :class="{ 'disabled': currentPage === 1 }">
                                <a class="page-link" href="#" @click.prevent="goToPage(currentPage - 1)">Previous</a>
                            </li>
                            <template x-for="(page, index) in visiblePages" :key="`${page}-${index}`">
                                <li class="page-item" :class="{ 'active': page === currentPage }">
                                    <a class="page-link" href="#" @click.prevent="goToPage(page)" x-text="page"></a>
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
    </div>

    
    <div class="modal fade" id="transferModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold" x-text="isEditing ? 'Edit Stock Transfer' : 'New Stock Transfer'"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <form @submit.prevent="saveItem">
                        <div class="row g-4">
                            <div class="col-12">
                                
                                <div class="card border-0 shadow-sm mb-4 bg-body-tertiary">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <i class="bi bi-buildings-fill"></i>
                                            </div>
                                            <h6 class="card-title mb-0 fw-bold">Warehouse Route</h6>
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium text-muted small">Source Warehouse <span class="text-danger">*</span></label>
                                                <select class="form-select" x-model="form.from_warehouse_id" @change="fetchWarehouseStocks()" required :disabled="isEditing">
                                                    <option value="">Select source location...</option>
                                                    <template x-for="wh in warehouses" :key="wh.id">
                                                        <option :value="wh.id" x-text="wh.name"></option>
                                                    </template>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium text-muted small">Destination Warehouse <span class="text-danger">*</span></label>
                                                <select class="form-select" x-model="form.to_warehouse_id" required :disabled="isEditing">
                                                    <option value="">Select destination location...</option>
                                                    <template x-for="wh in warehouses.filter(w => w.id != form.from_warehouse_id)" :key="wh.id">
                                                        <option :value="wh.id" x-text="wh.name"></option>
                                                    </template>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                
                                <div class="card border-0 shadow-sm mb-4 bg-body-tertiary">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center justify-content-between mb-3">
                                            <div class="d-flex align-items-center">
                                                <div class="bg-success bg-opacity-10 text-success rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                    <i class="bi bi-list-ul"></i>
                                                </div>
                                                <h6 class="card-title mb-0 fw-bold">Items List</h6>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-primary" @click="addItem()">
                                                <i class="bi bi-plus-lg me-1"></i>Add Item
                                            </button>
                                        </div>
                                        
                                        <div class="row g-2 mb-2 d-none d-md-flex">
                                            <div class="col-md-7"><small class="text-muted fw-semibold">Product Name</small></div>
                                            <div class="col-md-3"><small class="text-muted fw-semibold">Quantity to Transfer</small></div>
                                            <div class="col-md-2"></div>
                                        </div>

                                        <template x-for="(item, index) in form.items" :key="index">
                                            <div class="row g-2 mb-2 align-items-center">
                                                <div class="col-md-7 col-12">
                                                    <div class="position-relative" x-data="{ open: false, search: '' }" @click.outside="open = false">
                                                        <div class="input-group input-group-sm" @click="open = !open">
                                                            <input type="text" 
                                                                   class="form-control form-control-sm cursor-pointer bg-white" 
                                                                   placeholder="Search & choose product..." 
                                                                   :value="item.product_id ? (products.find(p => p.id == item.product_id)?.name + ' (' + products.find(p => p.id == item.product_id)?.sku + ')') : ''"
                                                                   readonly>
                                                            <span class="input-group-text bg-white"><i class="bi bi-chevron-down small text-muted"></i></span>
                                                        </div>
                                                        
                                                        <div x-show="open" 
                                                             class="position-absolute w-100 bg-white border rounded shadow-lg mt-1 p-2" 
                                                             style="z-index: 1050; max-height: 200px; overflow-y: auto;"
                                                             x-transition>
                                                            <div class="mb-2">
                                                                <input type="text" 
                                                                       class="form-control form-control-sm" 
                                                                       placeholder="Type to search..." 
                                                                       x-model="search"
                                                                       @click.stop>
                                                            </div>
                                                            <div class="list-group list-group-flush small">
                                                                <template x-for="p in products.filter(p => !search || p.name.toLowerCase().includes(search.toLowerCase()) || p.sku.toLowerCase().includes(search.toLowerCase()))" :key="p.id">
                                                                    <button type="button" 
                                                                            class="list-group-item list-group-item-action text-start border-0 py-2 px-3 rounded"
                                                                            :class="item.product_id == p.id ? 'active' : ''"
                                                                            @click="item.product_id = p.id; open = false; search = ''">
                                                                        <div class="fw-bold" x-text="p.name"></div>
                                                                        <div class="small" :class="item.product_id == p.id ? 'text-white-50' : 'text-muted'" x-text="'SKU: ' + p.sku"></div>
                                                                    </button>
                                                                </template>
                                                                <template x-if="products.filter(p => !search || p.name.toLowerCase().includes(search.toLowerCase()) || p.sku.toLowerCase().includes(search.toLowerCase())).length === 0">
                                                                    <div class="text-muted text-center py-2">No products found</div>
                                                                </template>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-text small mt-1 d-flex gap-3" x-show="item.product_id && form.from_warehouse_id">
                                                        <span class="text-secondary">Current Stock: <strong class="text-dark" x-text="formatQty(getProductStock(item.product_id))"></strong></span>
                                                        <span class="text-secondary" x-show="item.quantity > 0">Remaining: <strong :class="getProductRemainingStock(item) < 0 ? 'text-danger' : 'text-success'" x-text="formatQty(getProductRemainingStock(item))"></strong></span>
                                                    </div>
                                                </div>
                                                <div class="col-md-3 col-8">
                                                    <input type="number" class="form-control form-control-sm" x-model.number="item.quantity" min="0.01" step="0.01" placeholder="Quantity" required>
                                                </div>
                                                <div class="col-md-2 col-4">
                                                    <button type="button" class="btn btn-sm btn-outline-danger w-100" @click="removeItem(index)" :disabled="form.items.length === 1">
                                                        <i class="bi bi-trash-fill"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer border-top-0 pt-0 px-0">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary px-4" :disabled="saving">
                                <span x-show="saving" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                <span x-text="isEditing ? 'Update Transfer' : 'Create Transfer'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/user/metis/resources/views/inventory/stock-transfers.blade.php ENDPATH**/ ?>