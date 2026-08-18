@extends('layouts.app')

@section('title', 'Inventory Adjustments')
@section('page', 'inventory-adjustments')

@section('content')
<div class="inventory-adjustments" x-data="inventoryAdjustments" x-cloak>

    {{-- ── Page Header ────────────────────────────────────────── --}}
    <div class="d-flex justify-content-between align-items-center mb-4 mb-lg-5">
        <div>
            <h1 class="h3 mb-0">Inventory Adjustments</h1>
            <p class="text-muted mb-0">Manage stock discrepancy overrides and counts</p>
        </div>
        <button type="button" class="btn btn-primary" @click.prevent="openCreateModal()">
            <i class="bi bi-plus-lg me-2"></i>New Adjustment
        </button>
    </div>

    {{-- ── Stats Widgets ───────────────────────────────────────── --}}
    <div class="row g-4 g-lg-5 mb-5">
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bi bi-sliders"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Total Adjustments</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.total ?? 0"></span></div>
                            <small class="text-success-emphasis">
                                <i class="bi bi-info-circle"></i> Lifecycle total counts
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
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Pending</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.pending ?? 0"></span></div>
                            <small class="text-warning">
                                <i class="bi bi-clock-history"></i> Awaiting review
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
                            <p class="h6 mb-0 text-muted">Approved</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.approved ?? 0"></span></div>
                            <small class="text-success">
                                <i class="bi bi-patch-check-fill"></i> Applied to stock
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
                        <div class="stats-icon bg-danger bg-opacity-10 text-danger me-3">
                            <i class="bi bi-x-circle-fill"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Rejected</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.rejected ?? 0"></span></div>
                            <small class="text-danger">
                                <i class="bi bi-dash-circle"></i> Rejected counts
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Main Table Container ─────────────────────────────────── --}}
    <div>
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h2 class="h5 card-title mb-0">Adjustment Records</h2>
                    </div>
                    <div class="col-auto">
                        <div class="d-flex gap-2">
                            {{-- Search --}}
                            <div class="position-relative">
                                <input type="search"
                                       class="form-control form-control-sm"
                                       placeholder="Search reference or reason..."
                                       x-model.debounce.400ms="searchQuery"
                                       @input="loadData()"
                                       style="width: 250px;">
                                <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted"></i>
                            </div>
                            {{-- Status Filter --}}
                            <select x-select class="form-select form-select-sm"
                                    x-model="statusFilter"
                                    @change="loadData()"
                                    style="width: 150px;">
                                <option value="">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
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
                                <span x-text="selectedItems.length"></span> adjustment<span x-show="selectedItems.length !== 1">s</span> selected
                            </span>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-success" @click="bulkAction('approve')">
                                <i class="bi bi-check-circle me-1"></i>Approve Selected
                            </button>
                            <button class="btn btn-sm btn-outline-danger" @click="bulkAction('reject')">
                                <i class="bi bi-x-circle me-1"></i>Reject Selected
                            </button>
                            <button class="btn btn-sm btn-danger" @click="bulkAction('delete')">
                                <i class="bi bi-trash me-1"></i>Delete Selected
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Table --}}
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
                                <th><i class="bi bi-file-earmark-text me-1 text-secondary"></i>Reference No.</th>
                                <th><i class="bi bi-buildings-fill me-1 text-secondary"></i>Warehouse</th>
                                <th><i class="bi bi-chat-left-text me-1 text-secondary"></i>Reason</th>
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
                                            No adjustments found matching filters.
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
                                        <span class="fw-semibold font-monospace text-primary" x-text="item.reference_no"></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border" x-text="item.warehouse?.name || '-'"></span>
                                    </td>
                                    <td>
                                        <span class="text-muted small" x-text="item.reason || '-'"></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle" x-text="item.items_count || 0"></span>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill"
                                              :class="{
                                                  'bg-warning-subtle text-warning': item.status === 'pending',
                                                  'bg-success-subtle text-success': item.status === 'approved',
                                                  'bg-danger-subtle text-danger': item.status === 'rejected'
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
                                                <template x-if="item.status === 'pending'">
                                                    <li>
                                                        <a class="dropdown-item d-flex align-items-center gap-2" href="#" @click.prevent="editItem(item)">
                                                            <i class="bi bi-pencil text-primary"></i> Edit Count
                                                        </a>
                                                    </li>
                                                </template>
                                                <template x-if="item.status === 'pending'">
                                                    <li>
                                                        <a class="dropdown-item d-flex align-items-center gap-2 text-success" href="#" @click.prevent="approveItem(item)">
                                                            <i class="bi bi-check-circle"></i> Approve Adjustment
                                                        </a>
                                                    </li>
                                                </template>
                                                <template x-if="item.status === 'pending'">
                                                    <li><hr class="dropdown-divider"></li>
                                                </template>
                                                <template x-if="item.status === 'pending'">
                                                    <li>
                                                        <a class="dropdown-item text-danger d-flex align-items-center gap-2" href="#" @click.prevent="rejectItem(item)">
                                                            <i class="bi bi-x-circle"></i> Reject Count
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

                {{-- Pagination --}}
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

    {{-- ── Adjustment Form Modal ───────────────────────────────── --}}
    <div class="modal fade" id="adjustmentModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold" x-text="isEditing ? 'Edit Adjustment' : 'New Inventory Adjustment'"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <form @submit.prevent="saveItem">
                        <div class="row g-4">
                            <div class="col-12">
                                {{-- Card 1: Details --}}
                                <div class="card border-0 shadow-sm mb-4 bg-body-tertiary">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <i class="bi bi-sliders"></i>
                                            </div>
                                            <h6 class="card-title mb-0 fw-bold">Adjustment Details</h6>
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium text-muted small">Warehouse Location <span class="text-danger">*</span></label>
                                                <select x-select class="form-select" x-model="form.warehouse_id" @change="fetchWarehouseStocks()" required :disabled="isEditing">
                                                    <option value="">Select warehouse...</option>
                                                    <template x-for="wh in warehouses" :key="wh.id">
                                                        <option :value="wh.id" x-text="wh.name"></option>
                                                    </template>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium text-muted small">Reason / Reference <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" x-model="form.reason" placeholder="e.g. Discrepancy, Damaged product" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Card 2: Line Items --}}
                                <div class="card border-0 shadow-sm mb-4 bg-body-tertiary">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center justify-content-between mb-3">
                                            <div class="d-flex align-items-center">
                                                <div class="bg-warning bg-opacity-10 text-warning rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                    <i class="bi bi-list-check"></i>
                                                </div>
                                                <h6 class="card-title mb-0 fw-bold">Line Items</h6>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-primary" @click="addItem()">
                                                <i class="bi bi-plus-lg me-1"></i>Add Item
                                            </button>
                                        </div>

                                        <div class="row g-2 mb-2 d-none d-md-flex">
                                            <div class="col-md-4"><small class="text-muted fw-semibold">Product</small></div>
                                            <div class="col-md-2"><small class="text-muted fw-semibold">Current Qty</small></div>
                                            <div class="col-md-3 text-center"><small class="text-muted fw-semibold">Adjustment Type</small></div>
                                            <div class="col-md-2"><small class="text-muted fw-semibold">Value</small></div>
                                            <div class="col-md-1"></div>
                                        </div>

                                        <template x-for="(item, index) in form.items" :key="index">
                                            <div class="row g-2 mb-2 align-items-center">
                                                <div class="col-md-4 col-12">
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
                                                                            @click="item.product_id = p.id; open = false; search = ''; updateProductStock(item)">
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
                                                </div>
                                                <div class="col-md-2 col-6">
                                                    <input type="number" class="form-control form-control-sm bg-light" x-model.number="item.current_qty" min="0" step="0.01" placeholder="Current Qty" readonly required>
                                                </div>
                                                <div class="col-md-3 col-6 text-center">
                                                    <div class="btn-group btn-group-sm w-100" role="group">
                                                        <input type="radio" class="btn-check" :name="'type-' + index" :id="'plus-' + index" value="Add" x-model="item.adjustment_type" @change="updateNewQty(item)">
                                                        <label class="btn btn-outline-success px-2 py-1" :for="'plus-' + index">Add</label>

                                                        <input type="radio" class="btn-check" :name="'type-' + index" :id="'minus-' + index" value="Deduct" x-model="item.adjustment_type" @change="updateNewQty(item)">
                                                        <label class="btn btn-outline-danger px-2 py-1" :for="'minus-' + index">Deduct</label>

                                                        <input type="radio" class="btn-check" :name="'type-' + index" :id="'equal-' + index" value="Set" x-model="item.adjustment_type" @change="updateNewQty(item)">
                                                        <label class="btn btn-outline-primary px-2 py-1" :for="'equal-' + index">Set</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-2 col-6">
                                                    <input type="number" class="form-control form-control-sm" x-model.number="item.adjustment_value" min="0" step="0.01" placeholder="Val" @input="updateNewQty(item)" required>
                                                    <div class="form-text small mt-1" x-show="item.product_id" style="font-size: 0.75rem; white-space: nowrap;">
                                                        New: <strong class="text-dark" x-text="formatQty(item.new_qty)">0</strong>
                                                        (<span :class="(item.new_qty - item.current_qty) > 0 ? 'text-success' : ((item.new_qty - item.current_qty) < 0 ? 'text-danger' : 'text-secondary')" x-text="formatDifference(item)">0</span>)
                                                    </div>
                                                </div>
                                                <div class="col-md-1 col-12 text-end">
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
                                <span x-text="isEditing ? 'Update Adjustment' : 'Submit Adjustment'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
