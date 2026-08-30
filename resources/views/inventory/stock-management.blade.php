@extends('layouts.app')

@section('title', 'Stock Management')
@section('page', 'inventory-stock-management')

@section('content')
<div class="stock-management" x-data="stockManagement" x-cloak>

    {{-- ── Page Header ────────────────────────────────────────── --}}
    <div class="d-flex justify-content-between align-items-center mb-4 mb-lg-5">
        <div>
            <h1 class="h3 mb-0">Stock Management</h1>
            <p class="text-muted mb-0">Monitor and adjust real-time stock levels per warehouse</p>
        </div>
        <div class="d-flex gap-2">
            @can('product-export')
            <button type="button" class="btn btn-outline-secondary" @click="exportStock()">
                <i class="bi bi-download me-2"></i>Export
            </button>
            @endcan
            @can('stockmanagement-edit')
            <button type="button" class="btn btn-primary" @click.prevent="openAdjustModal(null)">
                <i class="bi bi-plus-lg me-2"></i>Set Stock
            </button>
            @endcan
        </div>
    </div>

    {{-- ── Stats Widgets ───────────────────────────────────────── --}}
    <div class="row g-4 g-lg-5 mb-5">
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="card stats-card cursor-pointer" @click="stockLevelFilter = ''; loadData()">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bi bi-box-seam-fill"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Total SKUs</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.total_products ?? 0"></span></div>
                            <small class="text-success-emphasis">
                                <i class="bi bi-database"></i> Unique products tracked
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="card stats-card">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-info bg-opacity-10 text-info me-3">
                            <i class="bi bi-buildings-fill"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Warehouses</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.total_warehouses ?? 0"></span></div>
                            <small class="text-info">
                                <i class="bi bi-info-circle"></i> Active locations
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="card stats-card">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-secondary bg-opacity-10 text-secondary me-3">
                            <i class="bi bi-boxes"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Total Units</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="parseFloat(stats.total_units || 0).toFixed(0)"></span></div>
                            <small class="text-secondary">
                                <i class="bi bi-info-circle"></i> Sum of all quantity
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="card stats-card cursor-pointer" @click="stockLevelFilter = 'in_stock'; loadData()">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-success bg-opacity-10 text-success me-3">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">In Stock</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.in_stock ?? 0"></span></div>
                            <small class="text-success">
                                <i class="bi bi-check-circle"></i> Healthy stock levels
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="card stats-card cursor-pointer" @click="stockLevelFilter = 'low_stock'; loadData()">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-warning bg-opacity-10 text-warning me-3">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Low Stock</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.low_stock_count ?? 0"></span></div>
                            <small class="text-warning">
                                <i class="bi bi-exclamation-circle"></i> Needs attention
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="card stats-card cursor-pointer" @click="stockLevelFilter = 'out_of_stock'; loadData()">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-danger bg-opacity-10 text-danger me-3">
                            <i class="bi bi-x-circle-fill"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Out of Stock</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.out_of_stock ?? 0"></span></div>
                            <small class="text-danger">
                                <i class="bi bi-dash-circle"></i> Needs restocking
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
                        <h2 class="h5 card-title mb-0">Stock Levels</h2>
                    </div>
                    <div class="col-auto mt-3 mt-md-0">
                        <div class="d-flex flex-wrap gap-2 justify-content-end">
                            {{-- Search --}}
                            <div class="position-relative">
                                <input type="search"
                                       class="form-control form-control-sm"
                                       placeholder="Search product or SKU..."
                                       x-model="searchQuery"
                                       @input.debounce.500ms="onSearch()"
                                       style="width: 220px;">
                                <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted"></i>
                            </div>
                            {{-- Warehouse Filter --}}
                            <select x-select class="form-select form-select-sm"
                                    x-model="warehouseFilter"
                                    @change="loadData()"
                                    style="width: 170px;">
                                <option value="">All Warehouses</option>
                                <template x-for="wh in warehouses" :key="wh.id">
                                    <option :value="wh.id" x-text="wh.name + (wh.is_default ? ' (Default)' : '')"></option>
                                </template>
                            </select>
                            {{-- Stock Level Filter --}}
                            <select x-select class="form-select form-select-sm"
                                    x-model="stockLevelFilter"
                                    @change="loadData()"
                                    style="width: 150px;">
                                <option value="">All Levels</option>
                                <option value="in_stock">In Stock</option>
                                <option value="low_stock">Low Stock</option>
                                <option value="out_of_stock">Out of Stock</option>
                            </select>
                            {{-- Per Page Filter --}}
                            <select class="form-select form-select-sm"
                                    x-model="itemsPerPage"
                                    @change="currentPage = 1; loadData()"
                                    style="width: 110px;">
                                <option value="10">10 / page</option>
                                <option value="15">15 / page</option>
                                <option value="20">20 / page</option>
                                <option value="25">25 / page</option>
                                <option value="50">50 / page</option>
                            </select>
                        </div>
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
                                <span x-text="selectedItems.length"></span> record<span x-show="selectedItems.length !== 1">s</span> selected
                            </span>
                        </div>
                        <div class="d-flex gap-2">
                            @can('product-export')
                            <button class="btn btn-sm btn-secondary" @click="exportStock(true)">
                                <i class="bi bi-download me-1"></i>Export Selected
                            </button>
                            @endcan
                        </div>
                    </div>
                </div>

                {{-- Table --}}
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-nowrap">
                        <thead class="table-group-divider">
                            <tr>
                                <th style="width: 50px;" class="ps-3">
                                    <input type="checkbox"
                                           class="user-select-checkbox"
                                           @change="$event.isTrusted && toggleAll($event.target.checked)"
                                           :checked="selectedItems.length === paginatedItems.length && paginatedItems.length > 0">
                                </th>
                                <th @click="sortBy('product_id')" class="sortable"><i class="bi bi-box-seam me-1 text-secondary"></i>Product</th>
                                <th @click="sortBy('warehouse_id')" class="sortable"><i class="bi bi-buildings-fill me-1 text-secondary"></i>Warehouse</th>
                                <th @click="sortBy('quantity')" class="sortable text-center"><i class="bi bi-inboxes me-1 text-secondary"></i>In Stock</th>
                                <th @click="sortBy('available')" class="sortable text-center"><i class="bi bi-check-circle me-1 text-secondary"></i>Available For Sell</th>
                                <th @click="sortBy('pending_qty')" class="sortable text-center"><i class="bi bi-hourglass-split me-1 text-secondary"></i>Order Placed</th>
                                <th @click="sortBy('reserved_qty')" class="sortable text-center"><i class="bi bi-bookmark-dash me-1 text-secondary"></i>Reserved</th>
                                <th @click="sortBy('dispatched_qty')" class="sortable text-center"><i class="bi bi-send-check me-1 text-secondary"></i>Dispatched</th>
                                <th @click="sortBy('delivered_qty')" class="sortable text-center"><i class="bi bi-box2-heart me-1 text-secondary"></i>Delivered</th>
                                <th @click="sortBy('return_requested_qty')" class="sortable text-center"><i class="bi bi-arrow-return-left me-1 text-secondary"></i>Return Req</th>
                                <th @click="sortBy('in_transit_qty')" class="sortable text-center"><i class="bi bi-truck me-1 text-secondary"></i>In Transit</th>
                                <th @click="sortBy('damaged_qty')" class="sortable text-center"><i class="bi bi-exclamation-octagon me-1 text-secondary"></i>Bad Qty</th>
                                <th style="width: 120px;" class="text-end pe-4"><i class="bi bi-lightning-charge me-1 text-secondary"></i>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="paginatedItems.length === 0">
                                <tr>
                                    <td colspan="12" class="text-center py-5 text-muted">
                                        <div x-show="isLoading" class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <div x-show="!isLoading">
                                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                            No stock records match the current filters.
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
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div x-show="item.product?.grade" 
                                                 class="badge border shadow-sm rounded-2 d-flex flex-column align-items-center justify-content-center me-2 flex-shrink-0" 
                                                 style="width: 28px; height: 34px; font-size: 11px; padding: 2px;"
                                                 :class="{'bg-success-subtle text-success-emphasis border-success': item.product?.grade === 'A', 'bg-warning-subtle text-warning-emphasis border-warning': item.product?.grade === 'B', 'bg-danger-subtle text-danger-emphasis border-danger': item.product?.grade === 'C', 'bg-dark-subtle text-body-emphasis-emphasis border-dark': !['A','B','C'].includes(item.product?.grade)}"
                                                 :title="'Grade ' + (item.product?.grade || '')"
                                                 x-cloak>
                                                <i class="bi bi-star-fill text-warning" style="font-size: 10px; line-height: 1; margin-bottom: 2px;"></i>
                                                <span x-text="item.product?.grade" style="line-height: 1; font-weight: 800;"></span>
                                            </div>
                                            <template x-if="item.product?.image_url">
                                                <div class="rounded overflow-hidden d-flex align-items-center justify-content-center me-3 flex-shrink-0 border border-secondary-subtle bg-body" style="width:38px;height:38px;">
                                                    <img :src="item.product.image_url" alt="" class="w-100 h-100 object-fit-contain">
                                                </div>
                                            </template>
                                            <template x-if="!item.product?.image_url">
                                                <div class="bg-primary bg-opacity-10 text-primary rounded d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width:38px;height:38px;">
                                                    <i class="bi bi-box-seam"></i>
                                                </div>
                                            </template>
                                            <div>
                                                <div class="fw-medium" x-text="item.product?.name || '-'"></div>
                                                <div class="d-flex align-items-center gap-2 mt-1">
                                                    <small class="text-muted font-monospace" x-text="item.product?.sku || ''"></small>
                                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25" 
                                                          style="font-size:9px; padding: 2px 4px;" 
                                                          x-show="item.allow_overselling ?? item.product?.allow_overselling" 
                                                          title="Overselling Allowed" x-cloak>
                                                        <i class="bi bi-arrow-down-up me-1"></i>Oversell
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-body-secondary text-body-emphasis border" x-text="item.warehouse?.name || '-'"></span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex align-items-center justify-content-center gap-1">
                                            <span class="badge stock-badge"
                                                  :class="{
                                                      'in-stock':     parseFloat(item.quantity || 0) - parseFloat(item.reserved_qty || 0) > (item.product?.min_stock_level ?? 5),
                                                      'low-stock':    parseFloat(item.quantity || 0) - parseFloat(item.reserved_qty || 0) > 0 && parseFloat(item.quantity || 0) - parseFloat(item.reserved_qty || 0) <= (item.product?.min_stock_level ?? 5),
                                                      'out-of-stock': parseFloat(item.quantity || 0) - parseFloat(item.reserved_qty || 0) <= 0
                                                  }"
                                                  x-text="parseFloat(item.quantity || 0)">
                                            </span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex align-items-center justify-content-center gap-1">
                                            <span class="badge stock-badge"
                                                  :class="{
                                                      'in-stock':     (parseFloat(item.quantity||0) - parseFloat(item.reserved_qty||0) - parseFloat(item.pending_qty||0)) > 5,
                                                      'low-stock':    (parseFloat(item.quantity||0) - parseFloat(item.reserved_qty||0) - parseFloat(item.pending_qty||0)) > 0 && (parseFloat(item.quantity||0) - parseFloat(item.reserved_qty||0) - parseFloat(item.pending_qty||0)) <= 5,
                                                      'out-of-stock': (parseFloat(item.quantity||0) - parseFloat(item.reserved_qty||0) - parseFloat(item.pending_qty||0)) <= 0
                                                  }"
                                                  x-text="parseFloat((parseFloat(item.quantity||0) - parseFloat(item.reserved_qty||0) - parseFloat(item.pending_qty||0)).toFixed(4))">
                                            </span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle"
                                              x-text="parseFloat(item.pending_qty || 0).toFixed(2)"></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle"
                                              x-text="parseFloat(item.reserved_qty || 0).toFixed(2)"></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info-subtle text-info border border-info-subtle"
                                              x-text="parseFloat(item.dispatched_qty || 0).toFixed(2)"></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success-subtle text-success border border-success-subtle"
                                              x-text="parseFloat(item.delivered_qty || 0).toFixed(2)"></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"
                                              x-text="parseFloat(item.return_requested_qty || 0).toFixed(2)"></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"
                                              x-text="parseFloat(item.in_transit_qty || 0).toFixed(2)"></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle"
                                              x-text="parseFloat(item.damaged_qty || 0).toFixed(2)"></span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                                    type="button"
                                                    data-bs-toggle="dropdown">
                                                <i class="bi bi-three-dots"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                                @can('stockmanagement-edit')
                                                <li>
                                                    <a class="dropdown-item" href="#" @click.prevent="openAdjustModal(item)">
                                                        <i class="bi bi-pencil me-2"></i>Set Stock Level
                                                    </a>
                                                </li>
                                                @endcan
                                                @can('stocktransfer-create')
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('inventory.stock-transfers') }}">
                                                        <i class="bi bi-arrow-left-right me-2"></i>Create Transfer
                                                    </a>
                                                </li>
                                                @endcan
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
    </div> {{-- End stock management container --}}

    {{-- ── Set Stock Modal ─────────────────────────────────────── --}}
    <div class="modal fade" id="adjustStockModal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">Set Stock Level</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <form @submit.prevent="saveAdjustment()">
                        <div class="row g-4">
                            <div class="col-12">
                                {{-- Card: Stock Override --}}
                                <div class="card border-0 shadow-sm mb-4 bg-body-tertiary">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <i class="bi bi-bar-chart-steps"></i>
                                            </div>
                                            <h6 class="card-title mb-0 fw-bold">Stock Override</h6>
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-12" x-show="!isEditing">
                                                <label class="form-label fw-medium text-muted small">Product <span class="text-danger">*</span></label>
                                                <div class="position-relative" x-data="{ open: false, search: '' }" @click.outside="open = false">
                                                    <div class="input-group" @click="open = !open">
                                                        <input type="text" 
                                                               class="form-control cursor-pointer bg-body" 
                                                               placeholder="Search & choose product..." 
                                                               :value="adjustForm.productId ? (productOptions.find(p => p.id == adjustForm.productId)?.name + ' (' + productOptions.find(p => p.id == adjustForm.productId)?.sku + ')') : ''"
                                                               readonly>
                                                        <span class="input-group-text bg-body"><i class="bi bi-chevron-down small text-muted"></i></span>
                                                    </div>
                                                    
                                                    <div x-show="open" 
                                                         class="position-absolute w-100 bg-body border rounded shadow-lg mt-1 p-2" 
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
                                                            <template x-for="p in productOptions.filter(p => !search || p.name.toLowerCase().includes(search.toLowerCase()) || p.sku.toLowerCase().includes(search.toLowerCase()))" :key="p.id">
                                                                <button type="button" 
                                                                        class="list-group-item list-group-item-action text-start border-0 py-2 px-3 rounded"
                                                                        :class="adjustForm.productId == p.id ? 'active' : ''"
                                                                        @click="adjustForm.productId = p.id; open = false; search = ''; fetchCurrentStock()">
                                                                    <div class="fw-bold" x-text="p.name"></div>
                                                                    <div class="small" :class="adjustForm.productId == p.id ? 'text-white-50' : 'text-muted'" x-text="'SKU: ' + p.sku"></div>
                                                                </button>
                                                            </template>
                                                            <template x-if="productOptions.filter(p => !search || p.name.toLowerCase().includes(search.toLowerCase()) || p.sku.toLowerCase().includes(search.toLowerCase())).length === 0">
                                                                <div class="text-muted text-center py-2">No products found</div>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12" x-show="isEditing">
                                                <label class="form-label fw-medium text-muted small">Product</label>
                                                <input type="text" class="form-control bg-body-secondary" :value="adjustForm.productName" disabled>
                                            </div>
                                            <div class="col-12" x-show="!isEditing">
                                                <label class="form-label fw-medium text-muted small">Warehouse <span class="text-danger">*</span></label>
                                                <select x-select class="form-select" x-model="adjustForm.warehouseId" @change="fetchCurrentStock()" required>
                                                    <option value="">Select warehouse...</option>
                                                    <template x-for="wh in warehouses" :key="wh.id">
                                                        <option :value="wh.id" x-text="wh.name"></option>
                                                    </template>
                                                </select>
                                            </div>
                                            <div class="col-12" x-show="isEditing">
                                                <label class="form-label fw-medium text-muted small">Warehouse</label>
                                                <input type="text" class="form-control bg-body-secondary" :value="adjustForm.warehouseName" disabled>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label fw-medium text-muted small">Current Quantity</label>
                                                <input type="number" class="form-control bg-body-secondary" :value="adjustForm.currentQty" disabled>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label fw-medium text-muted small">New Quantity <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control" x-model.number="adjustForm.newQty" min="0" step="0.01" required>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label fw-medium text-muted small">Current Bad Qty</label>
                                                <input type="number" class="form-control bg-body-secondary" :value="adjustForm.currentDamagedQty" disabled>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label fw-medium text-muted small">New Bad Qty <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control" x-model.number="adjustForm.newDamagedQty" min="0" step="0.01" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-top-0 pt-0">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary px-4" :disabled="saving">
                                <span x-show="saving" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                Update Stock
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
