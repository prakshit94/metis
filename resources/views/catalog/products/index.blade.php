@extends('layouts.app')

@section('title', 'Product Management')
@section('page', 'catalog-products')

@section('content')
@php
    $activeOffers = \App\Modules\Orders\Models\Offer::active()->get();
    $activeCoupons = \App\Modules\Orders\Models\Coupon::where('is_active', true)->get();
    
    // Check if ReferralProgram exists and has active scope, if not fallback to the original query without cache
    if (class_exists(\App\Modules\Promotion\Models\ReferralProgram::class) && method_exists(\App\Modules\Promotion\Models\ReferralProgram::class, 'scopeActive')) {
        $activeReferrals = \App\Modules\Promotion\Models\ReferralProgram::active()->get();
    } elseif (class_exists(\App\Models\ReferralProgram::class)) {
        $now = now();
        $activeReferrals = \App\Models\ReferralProgram::where('is_active', true)
            ->where(function($q) use ($now) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', $now);
            })
            ->where(function($q) use ($now) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $now);
            })->get();
    } else {
        $activeReferrals = collect([]);
    }

    $user = auth()->user();
    $isMasterAdmin = $user && ($user->hasRole(['Super Admin', 'Admin']) || $user->can('view-all-data'));
    $userWarehouseId = $user->warehouse_id ?? null;
@endphp
<script>
    window.globalPromotions = {
        offers: @js($activeOffers),
        coupons: @js($activeCoupons),
        referrals: @js($activeReferrals)
    };
    window.userContext = {
        isMasterAdmin: @json($isMasterAdmin),
        warehouseId: @json($userWarehouseId)
    };
    window.getApplicablePromotions = function(product) {
        if (!product) return { offers: [], coupons: [], referrals: window.globalPromotions.referrals };
        
        let pId = String(product.id);
        let cId = String(product.category_id);
        
        let filterFunc = (item) => {
            let excProd = typeof item.excluded_products === 'string' ? JSON.parse(item.excluded_products || '[]') : (item.excluded_products || []);
            let excCat = typeof item.excluded_categories === 'string' ? JSON.parse(item.excluded_categories || '[]') : (item.excluded_categories || []);
            let appProd = typeof item.applicable_products === 'string' ? JSON.parse(item.applicable_products || '[]') : (item.applicable_products || []);
            let appCat = typeof item.applicable_categories === 'string' ? JSON.parse(item.applicable_categories || '[]') : (item.applicable_categories || []);

            if (excProd.length > 0 && excProd.map(String).includes(pId)) return false;
            if (excCat.length > 0 && excCat.map(String).includes(cId)) return false;
            
            let hasProdInc = appProd.length > 0;
            let hasCatInc = appCat.length > 0;
            
            if (!hasProdInc && !hasCatInc) return true;
            if (hasProdInc && appProd.map(String).includes(pId)) return true;
            if (hasCatInc && appCat.map(String).includes(cId)) return true;
            
            return false;
        };

        return {
            offers: window.globalPromotions.offers.filter(filterFunc),
            coupons: window.globalPromotions.coupons.filter(filterFunc),
            referrals: window.globalPromotions.referrals
        };
    };
</script>
<div class="product-management" x-data="productTable">
<!-- Page Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4 mb-lg-5">
                        <div>
                            <h1 class="h3 mb-0">Product Management</h1>
                            <p class="text-muted mb-0">Manage your product catalog and inventory</p>
                        </div>
                        <div class="d-flex gap-2">
                            @can('product-export')
                            <button type="button" class="btn btn-outline-secondary" @click="exportProducts()">
                                <i class="bi bi-download me-2"></i>Export
                            </button>
                            @endcan
                            @can('product-import')
                            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#importModal">
                                <i class="bi bi-upload me-2"></i>Import
                            </button>
                            @endcan
                            @can('product-create')
                            <button type="button" class="btn btn-primary" @click.prevent="openCreateProduct()">
                                <i class="bi bi-plus-lg me-2"></i>Add Product
                            </button>
                            @endcan
                        </div>
                    </div>

                    <!-- Product Management Container -->
                    <div>
                        <!-- Product Stats Widgets -->
                        <div class="row g-4 g-lg-5 mb-5">
                            <div class="col-xl-3 col-lg-6" style="cursor: pointer;" @click="stockFilter = ''; filterProducts()">
                                <div class="card stats-card">
                                    <div class="card-body p-3 p-lg-4">
                                        <div class="d-flex align-items-center">
                                            <div class="stats-icon bg-primary bg-opacity-10 text-primary me-3">
                                                <i class="bi bi-box"></i>
                                            </div>
                                            <div>
                                                <p class="h6 mb-0 text-muted">Total Products</p>
                                                <div class="h3 mb-0" aria-live="polite"><span x-text="stats.total"></span></div>
                                                <small class="text-success-emphasis">
                                                    <i class="bi bi-arrow-up"></i> +5% from last month
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-lg-6" style="cursor: pointer;" @click="stockFilter = 'in-stock'; filterProducts()">
                                <div class="card stats-card">
                                    <div class="card-body p-3 p-lg-4">
                                        <div class="d-flex align-items-center">
                                            <div class="stats-icon bg-success bg-opacity-10 text-success me-3">
                                                <i class="bi bi-check-circle"></i>
                                            </div>
                                            <div>
                                                <p class="h6 mb-0 text-muted">In Stock</p>
                                                <div class="h3 mb-0" aria-live="polite"><span x-text="stats.inStock"></span></div>
                                                <small class="text-success-emphasis">
                                                    <i class="bi bi-arrow-up"></i> Well stocked
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-lg-6" style="cursor: pointer;" @click="stockFilter = 'low-stock'; filterProducts()">
                                <div class="card stats-card">
                                    <div class="card-body p-3 p-lg-4">
                                        <div class="d-flex align-items-center">
                                            <div class="stats-icon bg-warning bg-opacity-10 text-warning me-3">
                                                <i class="bi bi-exclamation-triangle"></i>
                                            </div>
                                            <div>
                                                <p class="h6 mb-0 text-muted">Low Stock</p>
                                                <div class="h3 mb-0" aria-live="polite"><span x-text="stats.lowStock"></span></div>
                                                <small class="text-warning">
                                                    <i class="bi bi-exclamation-circle"></i> Needs attention
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
                                            <div class="stats-icon bg-info bg-opacity-10 text-info me-3">
                                                <i class="bi bi-currency-rupee"></i>
                                            </div>
                                            <div>
                                                <p class="h6 mb-0 text-muted">Total Value</p>
                                                <div class="h3 mb-0" aria-live="polite"><span x-text="`₹ ${stats.totalValue.toLocaleString()}`"></span></div>
                                                <small class="text-info">
                                                    <i class="bi bi-info-circle"></i> Inventory value
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <!-- Products Table -->
                        <div class="card">
                            <div class="card-header">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <h2 class="h5 card-title mb-0">Product Catalog</h2>
                                    </div>
                                    <div class="col-auto">
                                        <div class="d-flex flex-wrap gap-2 justify-content-end">
                                            <!-- Search -->
                                            <div class="position-relative">
                                                <input type="search" 
                                                       class="form-control form-control-sm" 
                                                       placeholder="Search products..."
                                                       x-model="searchQuery"
                                                       @input="filterProducts()"
                                                       style="width: 200px; padding-right: 30px;">
                                                <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted"></i>
                                            </div>
                                            
                                            <!-- Category Filter -->
                                            <select x-select class="form-select form-select-sm" 
                                                    x-model="categoryFilter" 
                                                    @change="filterProducts()"
                                                    style="width: 150px;">
                                                <option value="">All Categories</option>
                                                <template x-for="cat in options.categories" :key="cat.id">
                                                    <optgroup :label="cat.name">
                                                        <option :value="cat.slug" x-text="cat.name + ' (Main)'"></option>
                                                        <template x-for="child in cat.children" :key="child.id">
                                                            <option :value="child.slug" x-text="'↳ ' + child.name"></option>
                                                        </template>
                                                    </optgroup>
                                                </template>
                                            </select>
                                            
                                            <!-- Warehouse Filter -->
                                            <select x-select class="form-select form-select-sm" 
                                                    x-model="warehouseFilter" 
                                                    @change="filterProducts()"
                                                    style="width: 150px;">
                                                @if($isMasterAdmin)
                                                <option value="">All Warehouses</option>
                                                @endif
                                                <template x-for="wh in options.warehouses" :key="wh.id">
                                                    <option :value="String(wh.id)" x-text="wh.name"></option>
                                                </template>
                                            </select>
                                            
                                            <!-- Stock Filter -->
                                            <select x-select class="form-select form-select-sm" 
                                                    x-model="stockFilter" 
                                                    @change="filterProducts()"
                                                    style="width: 150px;">
                                                <option value="">All Stock</option>
                                                <option value="in-stock">In Stock</option>
                                                <option value="low-stock">Low Stock</option>
                                                <option value="out-of-stock">Out of Stock</option>
                                            </select>

                                            <!-- Items Per Page -->
                                            <select x-select class="form-select form-select-sm"
                                                    x-model.number="itemsPerPage"
                                                    @change="filterProducts()"
                                                    style="width: 120px;">
                                                <option value="10">10 / page</option>
                                                <option value="15">15 / page</option>
                                                <option value="20">20 / page</option>
                                                <option value="25">25 / page</option>
                                                <option value="50">50 / page</option>
                                            </select>
                                            
                                            <button class="btn btn-sm btn-outline-secondary" type="button" @click="resetFilters()">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <!-- Bulk Actions Bar -->
                                <div class="bulk-actions-bar p-3 bg-primary bg-opacity-10 border-bottom border-primary border-opacity-25" x-show="selectedProducts.length > 0 && warehouseFilter !== ''" x-transition>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-check-circle-fill text-primary me-2"></i>
                                            <span class="fw-medium text-primary">
                                                <span x-text="selectedProducts.length"></span> record<span x-show="selectedProducts.length !== 1">s</span> selected
                                            </span>
                                        </div>
                                        <div class="d-flex gap-2">
                                            @can('product-edit')
                                            <button class="btn btn-sm btn-outline-secondary bg-body" 
                                                    x-show="filteredProducts.filter(p => selectedProducts.includes(p.id)).some(p => p.status !== 'published' && p.status !== 'active')" 
                                                    @click="bulkAction('publish')">
                                                <i class="bi bi-eye me-1"></i>Publish
                                            </button>
                                            <button class="btn btn-sm btn-outline-secondary bg-body" 
                                                    x-show="filteredProducts.filter(p => selectedProducts.includes(p.id)).some(p => p.status === 'published' || p.status === 'active')" 
                                                    @click="bulkAction('unpublish')">
                                                <i class="bi bi-eye-slash me-1"></i>Unpublish
                                            </button>
                                            <button class="btn btn-sm btn-outline-secondary bg-body" 
                                                    x-show="filteredProducts.filter(p => selectedProducts.includes(p.id)).some(p => isSkuEnabled(p))" 
                                                    @click="bulkAction('disable_sku')">
                                                <i class="bi bi-tags me-1"></i>Disable SKU
                                            </button>
                                            <button class="btn btn-sm btn-outline-secondary bg-body" 
                                                    x-show="filteredProducts.filter(p => selectedProducts.includes(p.id)).some(p => !isSkuEnabled(p))" 
                                                    @click="bulkAction('enable_sku')">
                                                <i class="bi bi-tags-fill me-1"></i>Enable SKU
                                            </button>
                                            @endcan
                                            @can('product-delete')
                                            <button class="btn btn-sm btn-outline-danger bg-body" @click="bulkAction('delete')">
                                                <i class="bi bi-trash me-1"></i>Delete
                                            </button>
                                            @endcan
                                        </div>
                                    </div>
                                </div>

                                <!-- Table -->
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th style="width: 40px;">
                                                    <input type="checkbox" 
                                                           class="form-check-input" 
                                                           @change="$event.isTrusted && toggleAll($event.target.checked)"
                                                           :checked="selectedProducts.length === filteredProducts.length && filteredProducts.length > 0">
                                                </th>
                                                <th>Product Details</th>
                                                <th @click="sortBy('price')" class="sortable">Pricing & Inventory</th>
                                                <th>Status & Tracking</th>
                                                <th style="width: 80px;" class="text-end pe-4" x-show="warehouseFilter !== ''">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-if="paginatedProducts.length === 0">
                                                <tr>
                                                    <td colspan="9" class="text-center py-5 text-muted">
                                                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                                        No products match the current filters.
                                                    </td>
                                                </tr>
                                            </template>
                                            <template x-for="product in paginatedProducts" :key="product.id">
                                                <tr :class="{'opacity-50': getEffectiveStock(product) <= 0}">
                                                    <td>
                                                        <input type="checkbox" 
                                                               class="form-check-input" 
                                                               :value="product.id"
                                                               :checked="selectedProducts.includes(product.id)"
                                                               @change="toggleProduct(product.id)">
                                                    </td>
                                                    <td class="align-middle">
                                                        <div class="d-flex align-items-start gap-3">
                                                            <div class="position-relative flex-shrink-0">
                                                                <img :src="product.image || '/assets/images/product-placeholder.svg'" 
                                                                     class="rounded border shadow-sm object-fit-cover" 
                                                                     style="width: 48px; height: 48px;" 
                                                                     :alt="product.name"
                                                                     x-on:error="$el.src='/assets/images/product-placeholder.svg'">
                                                                <div x-show="product.grade" 
                                                                     class="position-absolute top-100 start-50 translate-middle badge border shadow-sm rounded-pill px-2 d-flex align-items-center" 
                                                                     style="font-size: 9px; padding-top: 2px; padding-bottom: 2px;"
                                                                     :class="{'bg-success-subtle text-success-emphasis border-success': product.grade === 'A', 'bg-warning-subtle text-warning-emphasis border-warning': product.grade === 'B', 'bg-danger-subtle text-danger-emphasis border-danger': product.grade === 'C', 'bg-dark-subtle text-body-emphasis-emphasis border-dark': !['A','B','C'].includes(product.grade)}"
                                                                     :title="'Grade ' + product.grade"
                                                                     x-cloak>
                                                                    <i class="bi bi-star-fill text-warning me-1" style="font-size: 8px;"></i><span x-text="product.grade" style="font-weight: 800;"></span>
                                                                </div>
                                                            </div>
                                                            <div class="d-flex flex-column min-w-0 pt-1">
                                                                <a href="#" class="fw-bold text-decoration-none text-body-emphasis text-truncate mb-1" style="max-width: 220px;" @click.prevent="viewProduct(product)" x-text="product.name"></a>
                                                                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                                                    <small class="text-muted" style="font-size: 11px;" x-text="'SKU: ' + product.sku"></small>
                                                                    <span class="badge bg-secondary bg-opacity-10 text-secondary-emphasis border border-secondary border-opacity-25" style="font-size: 9px; padding: 0.25em 0.5em;" x-text="product.category"></span>
                                                                </div>
                                                                <div x-data="{ promos: window.getApplicablePromotions(product) }" x-show="promos.offers.length > 0 || promos.coupons.length > 0 || promos.referrals.length > 0">
                                                                    <button type="button" class="btn btn-sm btn-outline-success rounded-pill py-0 px-2 d-inline-flex align-items-center gap-1 bg-body" style="font-size: 10px;" data-bs-toggle="modal" data-bs-target="#offersModal" @click="$dispatch('set-promos', promos)">
                                                                        <i class="bi bi-gift-fill"></i> <span x-text="promos.offers.length + promos.coupons.length + promos.referrals.length + ' Offers'"></span>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="align-middle">
                                                        <div class="d-flex flex-column gap-2">
                                                            <div class="d-flex align-items-center gap-2">
                                                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 text-decoration-line-through" x-show="product.mrp && Number(product.mrp) > (Number(product.price) * (1 + (Number(product.tax_rate || 0) / 100)))" x-text="'₹' + Number(product.mrp).toFixed(2)"></span>
                                                                <span class="badge bg-success text-white fw-bold shadow-sm" style="font-size: 13px;" x-text="'₹' + (Number(product.price) * (1 + (Number(product.tax_rate || 0) / 100))).toFixed(2)"></span>
                                                            </div>
                                                            <div class="p-2 bg-body-tertiary rounded border shadow-sm w-100" style="min-width: 160px; max-width: 200px;">
                                                                <div class="d-flex justify-content-between align-items-center mb-1 border-bottom border-secondary border-opacity-10 pb-1">
                                                                    <span class="text-muted fw-medium" style="font-size: 9px; letter-spacing: 0.5px;">AVAILABLE FOR SELL</span>
                                                                    <span class="badge stock-badge" 
                                                                          :class="{
                                                                              'in-stock': getEffectiveStock(product) > (product.min_stock_level || 10),
                                                                              'low-stock': getEffectiveStock(product) > 0 && getEffectiveStock(product) <= (product.min_stock_level || 10),
                                                                              'out-of-stock': getEffectiveStock(product) <= 0
                                                                          }"
                                                                          x-text="getEffectiveStock(product)"></span>
                                                                </div>
                                                                <div class="d-flex justify-content-between align-items-center" style="font-size: 10px;">
                                                                    <span class="text-muted">Physical: <span class="fw-bold text-body-emphasis" x-text="getPhysicalStock(product)"></span></span>
                                                                    <span x-show="isOversellingAllowed(product) && getRemainingOversell(product) > 0" class="text-warning fw-bold" title="Overselling Allowed" x-text="'+' + getRemainingOversell(product) + ' (OS)'"></span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="align-middle">
                                                        <div class="d-flex flex-column gap-2">
                                                            <div class="d-flex align-items-center gap-2">
                                                                <span class="badge" 
                                                                      :class="{
                                                                          'bg-success': ['published', 'active'].includes(product.status),
                                                                          'bg-secondary': product.status === 'draft',
                                                                          'bg-warning': ['pending', 'out_of_stock'].includes(product.status)
                                                                      }"
                                                                      x-text="product.status"></span>
                                                                <small class="text-muted" style="font-size: 10px;"><i class="bi bi-clock me-1"></i><span x-text="product.created"></span></small>
                                                            </div>
                                                            <div class="d-flex flex-wrap gap-1" style="max-width: 180px;">
                                                                <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25" style="font-size:9px;" x-show="isSkuEnabled(product)" title="SKU Enabled"><i class="bi bi-upc-scan me-1"></i>SKU On</span>
                                                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25" style="font-size:9px;" x-show="!isSkuEnabled(product)" title="SKU Disabled"><i class="bi bi-upc-scan me-1"></i>SKU Off</span>
                                                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25" style="font-size:9px;" x-show="product.batch_tracking" title="Batch Tracking"><i class="bi bi-layers me-1"></i>Batch</span>
                                                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25" style="font-size:9px;" x-show="product.expiry_tracking" title="Expiry Tracking"><i class="bi bi-calendar-x me-1"></i>Expiry</span>
                                                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25" style="font-size:9px;" x-show="isOversellingAllowed(product)" title="Allow Overselling"><i class="bi bi-arrow-down-up me-1"></i>Oversell</span>
                                                                <span class="text-muted small" style="font-size:10px;" x-show="!isSkuEnabled(product) && !product.batch_tracking && !product.expiry_tracking && !isOversellingAllowed(product)">-</span>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="text-end pe-4" x-show="warehouseFilter !== ''">
                                                        <div class="dropdown">
                                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                                    type="button" 
                                                                    data-bs-toggle="dropdown" data-bs-boundary="window">
                                                                <i class="bi bi-three-dots"></i>
                                                            </button>
                                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" style="z-index: 1050;">
                                                                @can('product-edit')
                                                                <li><a class="dropdown-item" href="#" @click.prevent="editProduct(product)">
                                                                    <i class="bi bi-pencil me-2"></i>Edit
                                                                </a></li>
                                                                @endcan


                                                                @can('product-delete')
                                                                <li><hr class="dropdown-divider"></li>
                                                                <li><a class="dropdown-item text-danger" href="#" @click.prevent="deleteProduct(product)">
                                                                    <i class="bi bi-trash me-2"></i>Delete
                                                                </a></li>
                                                                @endcan
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
                                        <span x-text="filteredProducts.length"></span> results
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
                        
                    </div> <!-- End Product Management Container -->
    <!-- Modals -->
<div class="modal fade" id="productModal" >
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content" x-data="productForm">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <form @submit.prevent="saveProduct()">
                                                <div class="d-flex flex-column flex-md-row border-top" x-data="{ activeTab: 'general' }">
                            <!-- Sidebar Tabs -->
                            <div class="bg-body-tertiary border-end p-3" style="min-width: 240px;">
                                <div class="nav flex-column nav-pills gap-1" role="tablist" aria-orientation="vertical">
                                    <button type="button" class="nav-link text-start d-flex align-items-center rounded-3" :class="{'active': activeTab === 'general', 'bg-body': activeTab !== 'general'}" @click="activeTab = 'general'">
                                        <i class="bi bi-info-circle me-3 fs-5" :class="{'text-primary': activeTab !== 'general'}"></i>
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold">General</span>
                                            <span class="small" :class="{'text-white-50': activeTab === 'general', 'text-muted': activeTab !== 'general'}" style="font-size: 0.7rem;">Basic info & identifiers</span>
                                        </div>
                                    </button>
                                    <button type="button" class="nav-link text-start d-flex align-items-center rounded-3" :class="{'active': activeTab === 'pricing', 'bg-body': activeTab !== 'pricing'}" @click="activeTab = 'pricing'">
                                        <i class="bi bi-currency-rupee me-3 fs-5" :class="{'text-success': activeTab !== 'pricing'}"></i>
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold">Pricing & Tax</span>
                                            <span class="small" :class="{'text-white-50': activeTab === 'pricing', 'text-muted': activeTab !== 'pricing'}" style="font-size: 0.7rem;">MRP, Selling price & GST</span>
                                        </div>
                                    </button>
                                    <button type="button" class="nav-link text-start d-flex align-items-center rounded-3" :class="{'active': activeTab === 'inventory', 'bg-body': activeTab !== 'inventory'}" @click="activeTab = 'inventory'">
                                        <i class="bi bi-box-seam me-3 fs-5" :class="{'text-danger': activeTab !== 'inventory'}"></i>
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold">Inventory</span>
                                            <span class="small" :class="{'text-white-50': activeTab === 'inventory', 'text-muted': activeTab !== 'inventory'}" style="font-size: 0.7rem;">Stock, Tracking & Suppliers</span>
                                        </div>
                                    </button>
                                    <button type="button" class="nav-link text-start d-flex align-items-center rounded-3" :class="{'active': activeTab === 'media', 'bg-body': activeTab !== 'media'}" @click="activeTab = 'media'">
                                        <i class="bi bi-image me-3 fs-5" :class="{'text-warning': activeTab !== 'media'}"></i>
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold">Media & Status</span>
                                            <span class="small" :class="{'text-white-50': activeTab === 'media', 'text-muted': activeTab !== 'media'}" style="font-size: 0.7rem;">Images, Status & Dimensions</span>
                                        </div>
                                    </button>
                                    <button type="button" class="nav-link text-start d-flex align-items-center rounded-3" :class="{'active': activeTab === 'attributes', 'bg-body': activeTab !== 'attributes'}" @click="activeTab = 'attributes'">
                                        <i class="bi bi-tags me-3 fs-5" :class="{'text-info': activeTab !== 'attributes'}"></i>
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold">Attributes</span>
                                            <span class="small" :class="{'text-white-50': activeTab === 'attributes', 'text-muted': activeTab !== 'attributes'}" style="font-size: 0.7rem;">Colors, Sizes & Variants</span>
                                        </div>
                                    </button>
                                </div>
                            </div>

                            <!-- Tab Content Area -->
                            <div class="flex-grow-1 p-4 bg-body" style="max-height: 70vh; overflow-y: auto;">
                                
                                <!-- GENERAL TAB -->
                                <div x-show="activeTab === 'general'" x-transition.opacity>
                                    <h5 class="fw-bold mb-4 text-primary d-flex align-items-center">
                                        <i class="bi bi-info-circle-fill me-2"></i> Basic Information
                                    </h5>
                                    
                                    <div class="row g-4 mb-5">
                                        <div class="col-12">
                                            <label class="form-label fw-medium text-muted small">Product Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" x-model="form.name" required placeholder="e.g. Wireless Noise Cancelling Headphones">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-medium text-muted small">Category <span class="text-danger">*</span></label>
                                            <select x-select class="form-select" x-model="form.category_id" required>
                                                <option value="">Select Category</option>
                                                <template x-for="category in options.categories" :key="category.id">
                                                    <optgroup :label="category.name">
                                                        <option :value="String(category.id)" x-text="category.name + ' (Main)'"></option>
                                                        <template x-for="child in category.children" :key="child.id">
                                                            <option :value="String(child.id)" x-text="'↳ ' + child.name"></option>
                                                        </template>
                                                    </optgroup>
                                                </template>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-medium text-muted small">Brand</label>
                                            <select x-select class="form-select" x-model="form.brand_id">
                                                <option value="">No Brand</option>
                                                <template x-for="brand in options.brands" :key="brand.id">
                                                    <option :value="String(brand.id)" x-text="brand.name"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-medium text-muted small">Product Description</label>
                                            <textarea class="form-control" x-model="form.description" rows="3" placeholder="Enter detailed product description..."></textarea>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-medium text-muted small">Application Instructions</label>
                                            <textarea class="form-control" x-model="form.application_instructions" rows="2" placeholder="Enter instructions for use/application..."></textarea>
                                        </div>
                                    </div>
                                    
                                    <h5 class="fw-bold mb-4 pt-3 border-top text-info d-flex align-items-center">
                                        <i class="bi bi-upc-scan me-2"></i> Identifiers
                                    </h5>
                                    
                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <label class="form-label fw-medium text-muted small">SKU <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" x-model="form.sku" :disabled="!form.is_sku_enabled" :required="form.is_sku_enabled" placeholder="SKU Code">
                                                <div class="input-group-text bg-body-secondary border-start-0">
                                                    <div class="form-check form-switch m-0">
                                                        <input class="form-check-input" type="checkbox" role="switch" x-model="form.is_sku_enabled" id="skuEnabledToggle">
                                                        <label class="form-check-label small fw-medium ms-1" for="skuEnabledToggle" style="margin-top: 2px;">Auto</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-medium text-muted small">Barcode / UPC</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-body-secondary"><i class="bi bi-upc-scan"></i></span>
                                                <input type="text" class="form-control" x-model="form.barcode" placeholder="Scan or enter barcode">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- PRICING TAB -->
                                <div x-show="activeTab === 'pricing'" x-transition.opacity x-cloak>
                                    <h5 class="fw-bold mb-4 text-success d-flex align-items-center">
                                        <i class="bi bi-currency-rupee me-2"></i> Pricing & Taxation
                                    </h5>
                                    
                                    <div class="row g-4">
                                        <div class="col-md-4">
                                            <label class="form-label fw-medium text-muted small">Purchase Price <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-body-secondary">₹</span>
                                                <input type="number" class="form-control" x-model="form.purchase_price" step="0.01" min="0" required placeholder="0.00">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-medium text-muted small">MRP</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-body-secondary">₹</span>
                                                <input type="number" class="form-control" x-model="form.mrp" step="0.01" min="0" placeholder="0.00">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-medium text-muted small">Selling Price (Inc. GST) <span class="text-danger">*</span></label>
                                            <div class="input-group mb-1">
                                                <span class="input-group-text bg-body-secondary">₹</span>
                                                <input type="number" class="form-control" x-model="form.selling_price_inc_gst" step="0.01" min="0" required placeholder="0.00">
                                            </div>
                                            <small class="text-muted fw-medium" x-show="form.selling_price_inc_gst && form.tax_rate_id" x-cloak>
                                                Base (Excl): ₹<span x-text="baseSellingPriceExcludingTax.toFixed(2)"></span>
                                            </small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-medium text-muted small">Tax Rate <span class="text-danger">*</span></label>
                                            <select x-select class="form-select" x-model="form.tax_rate_id" required>
                                                <option value="">No Tax</option>
                                                <template x-for="rate in options.taxRates" :key="rate.id">
                                                    <option :value="String(rate.id)" x-text="rate.name + ' (' + rate.rate + '%)'"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-medium text-muted small">HSN Code <span class="text-danger">*</span></label>
                                            <select x-select class="form-select" x-model="form.hsn_code_id" required>
                                                <option value="">No HSN</option>
                                                <template x-for="hsn in options.hsnCodes" :key="hsn.id">
                                                    <option :value="String(hsn.id)" x-text="hsn.code + (hsn.description ? ' - ' + hsn.description : '')"></option>
                                                </template>
                                            </select>
                                        </div>
                                        
                                        <div class="col-12 mt-4 pt-4 border-top">
                                            <h6 class="fw-bold mb-3">Default Discounts</h6>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label fw-medium text-muted small">Default Discount Amount</label>
                                            <input type="number" class="form-control" x-model="form.default_discount" step="0.01" min="0" placeholder="0.00">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-medium text-muted small">Discount Type</label>
                                            <select x-select class="form-select" x-model="form.default_discount_type">
                                                <option value="percent">Percent (%)</option>
                                                <option value="flat">Flat (₹)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- INVENTORY TAB -->
                                <div x-show="activeTab === 'inventory'" x-transition.opacity x-cloak>
                                    <h5 class="fw-bold mb-4 text-danger d-flex align-items-center">
                                        <i class="bi bi-box-seam me-2"></i> Inventory & Logistics
                                    </h5>
                                    
                                    <div class="row g-4 mb-5">
                                        <div class="col-md-6">
                                            <label class="form-label fw-medium text-muted small">Stock Quantity <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control form-control-lg" x-model="form.stock" min="0" required placeholder="0">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-medium text-muted small">Min Stock Level</label>
                                            <input type="number" class="form-control form-control-lg" x-model="form.min_stock_level" min="0" placeholder="0">
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label fw-medium text-muted small">Unit (UOM) <span class="text-danger">*</span></label>
                                            <select x-select class="form-select" x-model="form.uom_id" required>
                                                <option value="">Select Unit</option>
                                                <template x-for="uom in options.uoms" :key="uom.id">
                                                    <option :value="String(uom.id)" x-text="uom.name + (uom.short_name ? ' (' + uom.short_name + ')' : '')"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-medium text-muted small">Weight / Volume <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" x-model="form.weight" required placeholder="e.g. 1kg, 500ml">
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label fw-medium text-muted small">Default Warehouse</label>
                                            <select x-select class="form-select" x-model="form.default_warehouse_id">
                                                <option value="">No Default</option>
                                                <template x-for="warehouse in options.warehouses" :key="warehouse.id">
                                                    <option :value="String(warehouse.id)" x-text="warehouse.name"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-medium text-muted small">Supplier</label>
                                            <select x-select class="form-select" x-model="form.supplier_id">
                                                <option value="">Select Supplier</option>
                                                <template x-for="supplier in options.suppliers" :key="supplier.id">
                                                    <option :value="String(supplier.id)" x-text="supplier.company_name ? supplier.company_name : (supplier.firstname + ' ' + (supplier.lastname || ''))"></option>
                                                </template>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <h5 class="fw-bold mb-4 pt-3 border-top text-secondary d-flex align-items-center">
                                        <i class="bi bi-sliders me-2"></i> Tracking & Overselling Settings
                                    </h5>
                                    
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="p-3 border rounded bg-body-secondary h-100">
                                                <div class="form-check form-switch m-0 d-flex align-items-center justify-content-between mb-3">
                                                    <label class="form-check-label fw-medium" for="manageStockToggle">Manage stock</label>
                                                    <input class="form-check-input m-0" type="checkbox" role="switch" x-model="form.manage_stock" id="manageStockToggle">
                                                </div>
                                                <div class="form-check form-switch m-0 d-flex align-items-center justify-content-between mb-3">
                                                    <label class="form-check-label fw-medium" for="batchTrackingToggle">Batch tracking</label>
                                                    <input class="form-check-input m-0" type="checkbox" role="switch" x-model="form.batch_tracking" id="batchTrackingToggle">
                                                </div>
                                                <div class="form-check form-switch m-0 d-flex align-items-center justify-content-between">
                                                    <label class="form-check-label fw-medium" for="expiryTrackingToggle">Expiry tracking</label>
                                                    <input class="form-check-input m-0" type="checkbox" role="switch" x-model="form.expiry_tracking" id="expiryTrackingToggle">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="p-3 border rounded bg-body-secondary h-100">
                                                <div class="form-check form-switch m-0 d-flex align-items-center justify-content-between mb-3">
                                                    <label class="form-check-label fw-medium" for="allowOversellToggle">Allow global overselling</label>
                                                    <input class="form-check-input m-0" type="checkbox" role="switch" x-model="form.allow_overselling" id="allowOversellToggle">
                                                </div>
                                                
                                                <div x-show="form.allow_overselling" x-transition>
                                                    <label class="form-label fw-medium text-muted small">Global Overselling Limit</label>
                                                    <input type="number" class="form-control" x-model="form.overselling_qty" min="0" placeholder="0">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-12 mt-3" x-show="form.default_warehouse_id" x-transition>
                                            <div class="p-3 border rounded bg-warning bg-opacity-10 border-warning border-opacity-25">
                                                <div class="form-check form-switch m-0 d-flex align-items-center justify-content-between mb-2">
                                                    <div>
                                                        <label class="form-check-label fw-bold text-warning-emphasis" for="warehouseOversellToggle">
                                                            Override Warehouse Rule
                                                        </label>
                                                        <div class="fw-normal text-muted mt-1" style="font-size: 0.8rem;">Set specific rules for the selected warehouse.</div>
                                                    </div>
                                                    <input class="form-check-input m-0" type="checkbox" role="switch" id="warehouseOversellToggle"
                                                           :checked="form.warehouse_allow_overselling !== null || form.warehouse_is_sku_enabled !== null"
                                                           @change="form.warehouse_allow_overselling = $event.target.checked ? false : null; form.warehouse_overselling_qty = $event.target.checked ? 0 : null; form.warehouse_is_sku_enabled = $event.target.checked ? form.is_sku_enabled : null;">
                                                </div>
                                                <div x-show="form.warehouse_allow_overselling !== null || form.warehouse_is_sku_enabled !== null" x-transition class="pt-3 border-top border-warning border-opacity-25 mt-3 row g-3">
                                                    <div class="col-md-6">
                                                        <div class="form-check form-switch m-0 d-flex align-items-center justify-content-between h-100">
                                                            <label class="form-check-label fw-medium" for="whAllowOversellToggle">Allow Overselling Here</label>
                                                            <input class="form-check-input m-0" type="checkbox" role="switch" x-model="form.warehouse_allow_overselling" id="whAllowOversellToggle">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6" x-show="form.warehouse_allow_overselling" x-transition>
                                                        <label class="form-label fw-medium text-muted small">Warehouse Limit Qty</label>
                                                        <input type="number" class="form-control" x-model="form.warehouse_overselling_qty" min="0" placeholder="0">
                                                    </div>
                                                    <div class="col-12 mt-3 pt-3 border-top border-warning border-opacity-25" x-show="form.warehouse_allow_overselling !== null || form.warehouse_is_sku_enabled !== null" x-transition>
                                                        <div class="form-check form-switch m-0 d-flex align-items-center justify-content-between h-100">
                                                            <label class="form-check-label fw-medium" for="whSkuToggle">Enable SKU Here</label>
                                                            <input class="form-check-input m-0" type="checkbox" role="switch" x-model="form.warehouse_is_sku_enabled" id="whSkuToggle">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- MEDIA & STATUS TAB -->
                                <div x-show="activeTab === 'media'" x-transition.opacity x-cloak>
                                    <h5 class="fw-bold mb-4 text-warning d-flex align-items-center">
                                        <i class="bi bi-image me-2"></i> Media & Status
                                    </h5>
                                    
                                    <div class="row g-4 mb-5">
                                        <div class="col-md-6">
                                            <label class="form-label fw-medium text-muted small">Product Status <span class="text-danger">*</span></label>
                                            <select x-select class="form-select form-select-lg" x-model="form.status" required>
                                                <option value="">Select Status</option>
                                                <template x-for="status in options.statusList" :key="status.value">
                                                    <option :value="status.value" x-text="status.label"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-medium text-muted small">Grade</label>
                                            <select x-select class="form-select form-select-lg" x-model="form.grade">
                                                <option value="">No Grade</option>
                                                <option value="A">A</option>
                                                <option value="B">B</option>
                                                <option value="C">C</option>
                                                <option value="D">D</option>
                                            </select>
                                        </div>
                                        
                                        <div class="col-12">
                                            <label class="form-label fw-medium text-muted small">Product Image</label>
                                            <div class="border border-dashed rounded-3 p-4 text-center bg-body-secondary d-flex flex-column align-items-center justify-content-center" style="min-height: 200px; border-style: dashed !important; border-width: 2px !important; transition: all 0.2s;">
                                                <div class="mb-3 position-relative">
                                                    <template x-if="form.image">
                                                        <img :src="form.image" alt="Preview" class="rounded border shadow" style="width: 120px; height: 120px; object-fit: cover;">
                                                    </template>
                                                    <template x-if="!form.image">
                                                        <div class="bg-body d-flex align-items-center justify-content-center rounded-circle shadow-sm" style="width: 80px; height: 80px;">
                                                            <i class="bi bi-cloud-arrow-up fs-1 text-primary"></i>
                                                        </div>
                                                    </template>
                                                </div>
                                                <input type="file" class="form-control" style="max-width: 300px;" accept="image/*" @change="handleImageUpload($event)">
                                                <small class="text-muted mt-2 d-block">Recommended size: 800x800px (Max 5MB)</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <h5 class="fw-bold mb-4 pt-3 border-top text-body-secondary d-flex align-items-center">
                                        <i class="bi bi-arrows-fullscreen me-2"></i> Physical Dimensions (For Shipping)
                                    </h5>
                                    
                                    <div class="row g-4">
                                        <div class="col-md-3 col-sm-6">
                                            <label class="form-label fw-medium text-muted small">Weight (g)</label>
                                            <div class="input-group">
                                                <input type="number" step="0.01" class="form-control" x-model="form.weight_g" placeholder="0">
                                                <span class="input-group-text bg-body-secondary">g</span>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-sm-6">
                                            <label class="form-label fw-medium text-muted small">Length (cm)</label>
                                            <div class="input-group">
                                                <input type="number" step="0.01" class="form-control" x-model="form.length_cm" placeholder="0">
                                                <span class="input-group-text bg-body-secondary">cm</span>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-sm-6">
                                            <label class="form-label fw-medium text-muted small">Width (cm)</label>
                                            <div class="input-group">
                                                <input type="number" step="0.01" class="form-control" x-model="form.width_cm" placeholder="0">
                                                <span class="input-group-text bg-body-secondary">cm</span>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-sm-6">
                                            <label class="form-label fw-medium text-muted small">Height (cm)</label>
                                            <div class="input-group">
                                                <input type="number" step="0.01" class="form-control" x-model="form.height_cm" placeholder="0">
                                                <span class="input-group-text bg-body-secondary">cm</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- ATTRIBUTES TAB -->
                                <div x-show="activeTab === 'attributes'" x-transition.opacity x-cloak>
                                    <h5 class="fw-bold mb-4 text-info d-flex align-items-center">
                                        <i class="bi bi-tags me-2"></i> Product Attributes
                                    </h5>
                                    
                                    <div class="border rounded-3 p-4 bg-body-secondary">
                                        <template x-for="attribute in options.attributes" :key="attribute.id">
                                            <div class="mb-4 last-mb-0">
                                                <div class="fw-bold mb-3 text-body-emphasis text-uppercase d-flex align-items-center" style="font-size: 0.85rem; letter-spacing: 0.5px;">
                                                    <i class="bi bi-tag-fill me-2 text-primary opacity-50"></i>
                                                    <span x-text="attribute.name"></span>
                                                </div>
                                                <div class="d-flex flex-wrap gap-2 ps-4">
                                                    <template x-for="value in attribute.values" :key="value.id">
                                                        <label class="btn btn-sm py-2 px-3 border rounded-pill text-nowrap d-flex align-items-center gap-2 fw-medium transition-all"
                                                               :class="form.attributes.includes(String(value.id)) ? 'btn-primary border-primary shadow-sm' : 'btn-outline-secondary bg-body text-body'"
                                                               style="cursor: pointer;">
                                                            <input type="checkbox" class="d-none" :value="String(value.id)" x-model="form.attributes">
                                                            <i class="bi" :class="form.attributes.includes(String(value.id)) ? 'bi-check-circle-fill' : 'bi-circle'"></i>
                                                            <span x-text="value.value"></span>
                                                        </label>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                        <div x-show="!options.attributes || options.attributes.length === 0" class="text-center py-5 text-muted">
                                            <i class="bi bi-tags fs-1 d-block mb-2 opacity-50"></i>
                                            <p class="mb-0">No attributes available to configure.</p>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div><div class="modal-footer border-top-0 pt-0">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary px-4">Save Product</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<div class="modal fade" id="productViewModal" >
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content bg-body border-0 shadow-lg" x-data="{ 
            get product() { return Alpine.store('productTable')?.previewProduct },
            get applicablePromotions() {
                return window.getApplicablePromotions(this.product);
            }
        }">
            <!-- Header -->
            <div class="modal-header bg-body-tertiary border-bottom py-3 px-4">
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <h5 class="modal-title fw-bold mb-0" x-text="product ? product.name : ''"></h5>
                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary" x-text="product ? product.sku : ''"></span>
                    <span class="badge text-uppercase" :class="product && ['published', 'active'].includes(product.status) ? 'bg-success' : 'bg-warning-subtle text-warning-emphasis'" x-text="product ? product.status : ''"></span>
                    <span x-show="product && product.grade" class="badge border shadow-sm" :class="{'bg-success-subtle text-success-emphasis border-success': product?.grade === 'A', 'bg-warning-subtle text-warning-emphasis border-warning': product?.grade === 'B', 'bg-danger-subtle text-danger-emphasis border-danger': product?.grade === 'C', 'bg-dark-subtle text-body-emphasis-emphasis border-dark': !['A','B','C'].includes(product?.grade)}"><i class="bi bi-star-fill me-1 text-warning"></i>Grade <span x-text="product ? product.grade : ''"></span></span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Body: two-column scrollable layout -->
            <div class="modal-body p-0">
                <div class="pvm-layout">
                    <!-- Left: Image & Meta (sticky panel) -->
                    <div class="pvm-left bg-body-tertiary border-end p-3">
                        <div class="card border-0 shadow-sm mb-3 rounded-4 overflow-hidden position-relative" style="aspect-ratio:1;width:100%;">
                            <img :src="product ? (product.image || '/assets/images/product-placeholder.svg') : ''" class="w-100 h-100 object-fit-cover" x-on:error="$el.src='/assets/images/product-placeholder.svg'">
                            <span class="position-absolute top-0 end-0 m-2 badge bg-success shadow-sm" x-show="product && product.default_discount > 0" x-text="product ? product.default_discount + (product.default_discount_type === 'percent' ? '%' : '') + ' OFF' : ''"></span>
                        </div>
                        <div x-show="product">
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <span x-show="product && (product.category_label || product.category)" class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 py-2 px-3"><i class="bi bi-tag-fill me-1"></i><span x-text="product ? (product.category_label || product.category) : ''"></span></span>
                                <span x-show="product && product.brand" class="badge bg-secondary-subtle text-secondary-emphasis border py-2 px-3"><i class="bi bi-award-fill me-1"></i><span x-text="product ? product.brand : ''"></span></span>
                                @if(auth()->user()?->hasRole('Super Admin'))
                                <span x-show="product && product.supplier" class="badge bg-info-subtle text-info-emphasis border border-info border-opacity-25 py-2 px-3"><i class="bi bi-truck me-1"></i><span x-text="product ? product.supplier : ''"></span></span>
                                @endif
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-borderless small mb-0 text-muted">
                                    <tbody>
                                        <tr x-show="product && product.barcode"><th class="ps-0" style="width:100px;">Barcode</th><td x-text="product ? product.barcode : ''"></td></tr>
                                        <tr x-show="product && product.weight"><th class="ps-0">Weight</th><td x-text="product ? product.weight : ''"></td></tr>
                                        <tr x-show="product && (product.weight_g || product.length_cm || product.width_cm || product.height_cm)">
                                            <th class="ps-0">Dimensions</th>
                                            <td style="font-size: 11px;">
                                                <span x-show="product.weight_g" x-text="product.weight_g + 'g '"></span>
                                                <span x-show="product.length_cm || product.width_cm || product.height_cm" x-text="(product.length_cm || 0) + 'x' + (product.width_cm || 0) + 'x' + (product.height_cm || 0) + 'cm'"></span>
                                            </td>
                                        </tr>
                                        <tr x-show="product && product.uom"><th class="ps-0">UOM</th><td x-text="product ? product.uom : ''"></td></tr>
                                        <tr x-show="product && product.warehouse"><th class="ps-0">Warehouse</th><td x-text="product ? product.warehouse : ''"></td></tr>
                                        <tr x-show="product && product.slug"><th class="ps-0">URL Slug</th><td x-text="product ? product.slug : ''"></td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Right: All Data Cards (scrollable panel) -->
                    <div class="pvm-right p-3">

                        <!-- Pricing Card -->
                        <div class="card mb-3 border-0 shadow-sm bg-body-tertiary">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom border-secondary border-opacity-25">
                                    <div class="bg-primary bg-opacity-10 text-primary rounded-2 d-flex align-items-center justify-content-center" style="width:24px;height:24px;"><i class="bi bi-tag-fill" style="font-size:12px;"></i></div>
                                    <h6 class="mb-0 fw-bold text-uppercase text-body" style="font-size:11px;letter-spacing:1px;">Pricing Breakdown</h6>
                                </div>
                                <div class="row g-2 mb-2">
                                    <div class="@if(auth()->user()?->hasRole('Super Admin')) col-4 border-end @else col-12 @endif border-secondary border-opacity-25">
                                        <label class="form-label mb-1 fw-bold text-muted text-uppercase d-block" style="font-size:9px;">Selling Price (Inc. GST)</label>
                                        <div class="d-flex align-items-center flex-wrap gap-2">
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 text-decoration-line-through" x-show="product && product.mrp > ((product.selling_price || product.price) * (1 + (product.tax_rate || 0) / 100))" x-text="product ? 'MRP: ₹' + parseFloat(product.mrp||0).toFixed(2) : ''"></span>
                                            <span class="badge bg-success text-white fw-bold shadow-sm" style="font-size: 16px;" x-text="product ? '₹ ' + (parseFloat(product.selling_price || product.price || 0) * (1 + (parseFloat(product.tax_rate || 0) / 100))).toFixed(2) : ''"></span>
                                        </div>
                                    </div>
                                    @if(auth()->user()?->hasRole('Super Admin'))
                                    <div class="col-4 border-end border-secondary border-opacity-25 ps-3">
                                        <label class="form-label mb-1 fw-bold text-muted text-uppercase d-block" style="font-size:9px;">Purchase Price</label>
                                        <div class="fw-bold text-body-emphasis" style="font-size:14px;" x-text="product ? '₹ ' + parseFloat(product.purchase_price||0).toFixed(2) : ''"></div>
                                    </div>
                                    <div class="col-4 ps-3">
                                        <label class="form-label mb-1 fw-bold text-muted text-uppercase d-block" style="font-size:9px;">Profit Margin</label>
                                        <div class="fw-bold text-success" style="font-size:14px;" x-text="product && (product.selling_price || product.price) > 0 && product.purchase_price > 0 ? ((((product.selling_price || product.price) - product.purchase_price) / product.purchase_price) * 100).toFixed(1) + '%' : 'N/A'"></div>
                                    </div>
                                    @endif
                                </div>
                                <div class="row g-2 pt-2 border-top border-secondary border-opacity-25">
                                    <div class="col-6 border-end border-secondary border-opacity-25">
                                        <label class="form-label mb-1 fw-bold text-muted text-uppercase d-block" style="font-size:9px;">Taxes</label>
                                        <div class="fw-bold text-body-emphasis" style="font-size:13px;" x-text="product && product.tax_rate > 0 ? (product.tax_rate + '%') : (product && product.tax_label ? product.tax_label : 'No Tax')"></div>
                                    </div>
                                    <div class="col-6 ps-3">
                                        <label class="form-label mb-1 fw-bold text-muted text-uppercase d-block" style="font-size:9px;">HSN / SAC Code</label>
                                        <div class="fw-bold text-body-emphasis" style="font-size:13px;" x-text="product ? (product.hsn_code || 'Not Set') : ''"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Specs & Details Row -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <!-- Technical Specs -->
                                <div class="card h-100 border-0 shadow-sm bg-body-tertiary">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center gap-2 pb-2 mb-2 border-bottom border-secondary border-opacity-25">
                                            <div class="bg-info bg-opacity-10 text-info rounded-2 d-flex align-items-center justify-content-center" style="width:24px;height:24px;"><i class="bi bi-list-stars" style="font-size:12px;"></i></div>
                                            <h6 class="mb-0 fw-bold text-uppercase text-body" style="font-size:11px;letter-spacing:1px;">Technical Specs</h6>
                                        </div>
                                        <div>
                                            <template x-if="product && product.attributes && product.attributes.length > 0">
                                                <table class="table table-sm table-hover mb-0" style="font-size:11px;">
                                                    <tbody>
                                                        <template x-for="attr in product.attributes" :key="attr.id || attr.attribute">
                                                            <tr><th class="ps-2 text-muted w-50 border-0" x-text="attr.attribute || attr"></th><td class="pe-2 fw-semibold text-end border-0"><div class="d-flex align-items-center justify-content-end gap-1"><span x-show="attr.color_code" class="rounded-circle border" :style="'width:8px;height:8px;background-color:'+attr.color_code"></span><span x-text="attr.value"></span></div></td></tr>
                                                        </template>
                                                    </tbody>
                                                </table>
                                            </template>
                                            <template x-if="!product || !product.attributes || product.attributes.length === 0">
                                                <div class="text-center text-muted py-3" style="font-size:10px;">No specifications recorded.</div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <!-- Details & Usage -->
                                <div class="card h-100 border-0 shadow-sm bg-body-tertiary">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom border-secondary border-opacity-25">
                                            <div class="bg-secondary bg-opacity-10 text-secondary rounded-2 d-flex align-items-center justify-content-center" style="width:24px;height:24px;"><i class="bi bi-file-text-fill" style="font-size:12px;"></i></div>
                                            <h6 class="mb-0 fw-bold text-uppercase text-body" style="font-size:11px;letter-spacing:1px;">Details & Usage</h6>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase d-block" style="font-size:9px;">Product Description</label>
                                            <div style="font-size:11px;" x-show="product && product.description" x-html="product ? product.description : ''"></div>
                                            <div style="font-size:11px;" x-show="!product || !product.description" class="text-muted fst-italic">No description available.</div>
                                        </div>
                                        <div>
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase d-block" style="font-size:9px;">Application / Dosage</label>
                                            <div style="font-size:11px;" x-show="product && product.application_instructions" x-html="product ? product.application_instructions : ''"></div>
                                            <div style="font-size:11px;" x-show="!product || !product.application_instructions" class="text-muted fst-italic">No instructions available.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Active Promotions Card -->
                        <div class="card mb-3 border-0 shadow-sm bg-body-tertiary" x-show="applicablePromotions.offers.length > 0 || applicablePromotions.coupons.length > 0 || applicablePromotions.referrals.length > 0" x-cloak>
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom border-secondary border-opacity-25">
                                    <div class="bg-success bg-opacity-10 text-success rounded-2 d-flex align-items-center justify-content-center" style="width:24px;height:24px;"><i class="bi bi-stars" style="font-size:12px;"></i></div>
                                    <h6 class="mb-0 fw-bold text-uppercase text-body" style="font-size:11px;letter-spacing:1px;">Active Promotions & Discounts</h6>
                                </div>
                                
                                <div class="d-flex flex-column gap-2">
                                    <!-- Offers -->
                                    <template x-for="offer in applicablePromotions.offers" :key="'offer-'+offer.id">
                                        <div class="p-2 bg-success bg-opacity-10 border border-success border-opacity-25 rounded d-flex align-items-start gap-2">
                                            <i class="bi bi-tag-fill text-success mt-1"></i>
                                            <div>
                                                <div class="fw-bold text-success" style="font-size:12px;" x-text="offer.name"></div>
                                                <div class="text-muted" style="font-size:10px;">
                                                    <span x-show="offer.type === 'bogo'" x-text="'Buy ' + offer.buy_qty + ' Get ' + offer.get_qty + ' Free'"></span>
                                                    <span x-show="offer.type !== 'bogo' && offer.discount_type === 'percent'" x-text="offer.value + '% OFF'"></span>
                                                    <span x-show="offer.type !== 'bogo' && offer.discount_type === 'fixed'" x-text="'₹' + offer.value + ' OFF'"></span>
                                                    <span x-show="offer.min_spend > 0" x-text="' (Min spend: ₹' + offer.min_spend + ')'"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                    
                                    <!-- Coupons -->
                                    <template x-for="coupon in applicablePromotions.coupons" :key="'coupon-'+coupon.id">
                                        <div class="p-2 bg-primary bg-opacity-10 border border-primary border-opacity-25 rounded d-flex align-items-start gap-2">
                                            <i class="bi bi-ticket-perforated-fill text-primary mt-1"></i>
                                            <div>
                                                <div class="fw-bold text-primary" style="font-size:12px;">
                                                    <span x-text="coupon.code"></span>
                                                    <span class="badge bg-primary ms-1" style="font-size:9px;" x-show="coupon.type === 'percent'" x-text="coupon.value + '%'"></span>
                                                    <span class="badge bg-primary ms-1" style="font-size:9px;" x-show="coupon.type === 'fixed'" x-text="'₹' + coupon.value"></span>
                                                </div>
                                                <div class="text-muted" style="font-size:10px;">
                                                    Use code at checkout to claim discount.
                                                    <span x-show="coupon.min_spend > 0" x-text="'Min spend: ₹' + coupon.min_spend + '.'"></span>
                                                    <span x-show="coupon.expiry_date" x-text="'Valid till ' + new Date(coupon.expiry_date).toLocaleDateString() + '.'"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                    
                                    <!-- Referrals -->
                                    <template x-for="ref in applicablePromotions.referrals" :key="'ref-'+ref.id">
                                        <div class="p-2 bg-info bg-opacity-10 border border-info border-opacity-25 rounded d-flex align-items-start gap-2">
                                            <i class="bi bi-people-fill text-info mt-1"></i>
                                            <div>
                                                <div class="fw-bold text-info" style="font-size:12px;" x-text="ref.name"></div>
                                                <div class="text-muted" style="font-size:10px;" x-text="ref.description || 'Earn rewards by referring friends.'"></div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Inventory Row -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-12">
                                <!-- Inventory & Config -->
                                <div class="card border-0 shadow-sm bg-body-tertiary">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center gap-2 pb-2 mb-2 border-bottom border-secondary border-opacity-25">
                                            <div class="bg-warning bg-opacity-10 text-warning rounded-2 d-flex align-items-center justify-content-center" style="width:24px;height:24px;"><i class="bi bi-box-seam-fill" style="font-size:12px;"></i></div>
                                            <h6 class="mb-0 fw-bold text-uppercase text-body" style="font-size:11px;letter-spacing:1px;">Inventory & Tracking Config</h6>
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-md-6 border-end border-secondary border-opacity-25">
                                                <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom border-secondary border-opacity-25">
                                                    <div>
                                                        <div class="fw-bold text-body-emphasis" style="font-size:16px;" x-text="product ? (parseFloat(Alpine.store('productTable').getEffectiveStock(product)) + ' ' + (product.uom || 'Units')) : ''"></div>
                                                        <div class="text-muted" style="font-size:10px;">Available to Order</div>
                                                    </div>
                                                    <span class="badge" style="font-size:10px;" 
                                                          :class="product && Alpine.store('productTable').getEffectiveStock(product) > (product.min_stock_level || 10) ? 'bg-success' : (product && Alpine.store('productTable').getEffectiveStock(product) > 0 ? 'bg-warning-subtle text-warning-emphasis' : 'bg-danger')" 
                                                          x-text="product && Alpine.store('productTable').getEffectiveStock(product) > 0 ? 'In Stock' : 'Out of Stock'"></span>
                                                </div>
                                                <div class="row text-center g-1 mb-2">
                                                    <div class="col-4"><div class="fw-semibold" style="font-size:13px;" x-text="product ? parseFloat(Alpine.store('productTable').getPhysicalStock(product)) : 0"></div><div class="text-muted" style="font-size:9px;">Physical</div></div>
                                                    <div class="col-4 border-start border-end border-secondary border-opacity-25"><div class="fw-semibold text-warning" style="font-size:13px;" x-text="product ? (Alpine.store('productTable').warehouseFilter ? (product.warehouse_stocks?.find(s => String(s.warehouse_id) === String(Alpine.store('productTable').warehouseFilter))?.reserved_qty || 0) : ((product.reserved_qty || 0) + (product.pending_qty || 0))) : 0"></div><div class="text-muted" style="font-size:9px;">Reserved</div></div>
                                                    <div class="col-4"><div class="fw-semibold text-danger" style="font-size:13px;" x-text="product ? (product.min_stock_level || 0) : 0"></div><div class="text-muted" style="font-size:9px;">Min Level</div></div>
                                                </div>
                                                
                                                <!-- Warehouse Wise Breakdown -->
                                                <div class="mt-3 border-top border-secondary border-opacity-25 pt-2" x-show="product && product.warehouse_stocks && product.warehouse_stocks.length > 0">
                                                    <label class="form-label mb-2 fw-bold text-muted text-uppercase d-block" style="font-size:9px;">Warehouse Breakdown</label>
                                                    <div class="d-flex flex-column gap-1" style="max-height: 120px; overflow-y: auto;">
                                                        <template x-for="ws in product.warehouse_stocks" :key="'ws-'+ws.warehouse_id">
                                                            <div class="d-flex justify-content-between align-items-center bg-body-secondary p-1 px-2 rounded" style="font-size: 10px;">
                                                                <span class="text-truncate me-2 fw-medium" x-text="ws.warehouse_name" style="max-width: 100px;"></span>
                                                                <div class="d-flex align-items-center gap-2">
                                                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25" x-text="'Qty: ' + ws.quantity"></span>
                                                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25" x-show="ws.reserved_qty > 0" x-text="'Res: ' + ws.reserved_qty"></span>
                                                                </div>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase d-block" style="font-size:9px;">Tracking & Config</label>
                                                <div class="list-group list-group-flush border border-secondary border-opacity-25 rounded-3">
                                                    <div class="list-group-item d-flex justify-content-between align-items-center px-2 py-1 bg-transparent"><span class="text-muted" style="font-size:10px;"><i class="bi bi-box-seam me-1"></i>Manage Stock</span><span class="badge" style="font-size:9px;" :class="product && product.manage_stock ? 'bg-success' : 'bg-secondary'" x-text="product && product.manage_stock ? 'YES' : 'NO'"></span></div>
                                                    <div class="list-group-item d-flex justify-content-between align-items-center px-2 py-1 bg-transparent"><span class="text-muted" style="font-size:10px;"><i class="bi bi-layers me-1"></i>Batch Tracking</span><span class="badge" style="font-size:9px;" :class="product && product.batch_tracking ? 'bg-success' : 'bg-secondary'" x-text="product && product.batch_tracking ? 'ON' : 'OFF'"></span></div>
                                                    <div class="list-group-item d-flex justify-content-between align-items-center px-2 py-1 bg-transparent"><span class="text-muted" style="font-size:10px;"><i class="bi bi-calendar-x me-1"></i>Expiry Tracking</span><span class="badge" style="font-size:9px;" :class="product && product.expiry_tracking ? 'bg-success' : 'bg-secondary'" x-text="product && product.expiry_tracking ? 'ON' : 'OFF'"></span></div>
                                                    <div class="list-group-item d-flex justify-content-between align-items-center px-2 py-1 bg-transparent"><span class="text-muted" style="font-size:10px;"><i class="bi bi-arrow-down-up me-1"></i>Overselling</span><span class="badge" style="font-size:9px;" :class="product && Alpine.store('productTable').isOversellingAllowed(product) ? 'bg-success' : 'bg-secondary'" x-text="product && Alpine.store('productTable').isOversellingAllowed(product) ? 'ON' + (product && Alpine.store('productTable').getOversellingLimit(product) > 0 ? ' (Limit ' + Alpine.store('productTable').getOversellingLimit(product) + ')' : '') : 'OFF'"></span></div>
                                                    <div class="list-group-item d-flex justify-content-between align-items-center px-2 py-1 bg-transparent"><span class="text-muted" style="font-size:10px;"><i class="bi bi-upc-scan me-1"></i>SKU Enabled</span><span class="badge" style="font-size:9px;" :class="product && Alpine.store('productTable').isSkuEnabled(product) ? 'bg-success' : 'bg-secondary'" x-text="product && Alpine.store('productTable').isSkuEnabled(product) ? 'YES' : 'NO'"></span></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Extended Related Details -->
                        <div class="row g-3">
                            <!-- Supplier & Brand Details -->
                            <div class="col-md-6">
                                <div class="card h-100 border-0 shadow-sm bg-body-tertiary">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center gap-2 pb-2 mb-2 border-bottom border-secondary border-opacity-25">
                                            <div class="bg-primary bg-opacity-10 text-primary rounded-2 d-flex align-items-center justify-content-center" style="width:24px;height:24px;"><i class="bi bi-building" style="font-size:12px;"></i></div>
                                            <h6 class="mb-0 fw-bold text-uppercase text-body" style="font-size:11px;letter-spacing:1px;">Supplier & Brand Details</h6>
                                        </div>
                                        @if(auth()->user()?->hasRole('Super Admin'))
                                        <div class="mb-3" x-show="product && product.supplier_data">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase d-block" style="font-size:9px;">Supplier Info</label>
                                            <table class="table table-sm table-borderless small mb-0 text-muted" style="font-size: 11px;">
                                                <tbody>
                                                    <tr x-show="product?.supplier_data?.company_name"><th class="ps-0" style="width:90px;">Company</th><td x-text="product.supplier_data.company_name"></td></tr>
                                                    <tr x-show="product?.supplier_data?.email"><th class="ps-0">Email</th><td x-text="product.supplier_data.email"></td></tr>
                                                    <tr x-show="product?.supplier_data?.phone"><th class="ps-0">Phone</th><td x-text="product.supplier_data.phone"></td></tr>
                                                    <tr x-show="product?.supplier_data?.gst_no"><th class="ps-0">GST No</th><td x-text="product.supplier_data.gst_no"></td></tr>
                                                    <tr x-show="product?.supplier_data?.city"><th class="ps-0">Location</th><td x-text="(product.supplier_data.city || '') + (product.supplier_data.state ? ', ' + product.supplier_data.state : '')"></td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        @endif
                                        <div x-show="product && product.brand_data">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase d-block @if(auth()->user()?->hasRole('Super Admin')) border-top pt-2 @endif" style="font-size:9px;">Brand Info</label>
                                            <div class="d-flex align-items-center gap-2">
                                                <img x-show="product?.brand_data?.logo" :src="product.brand_data.logo" style="width:32px;height:32px;object-fit:cover;" class="rounded-circle border">
                                                <div>
                                                    <div class="fw-semibold text-body" style="font-size:12px;" x-text="product.brand_data.name"></div>
                                                    <div class="text-muted" style="font-size:10px;">Status: <span class="text-uppercase" x-text="product.brand_data.status"></span></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Category & Warehouse Details -->
                            <div class="col-md-6">
                                <div class="card h-100 border-0 shadow-sm bg-body-tertiary">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center gap-2 pb-2 mb-2 border-bottom border-secondary border-opacity-25">
                                            <div class="bg-success bg-opacity-10 text-success rounded-2 d-flex align-items-center justify-content-center" style="width:24px;height:24px;"><i class="bi bi-geo-alt" style="font-size:12px;"></i></div>
                                            <h6 class="mb-0 fw-bold text-uppercase text-body" style="font-size:11px;letter-spacing:1px;">Hierarchy & Logistics</h6>
                                        </div>
                                        <div class="mb-3" x-show="product && product.category_data">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase d-block" style="font-size:9px;">Category Hierarchy</label>
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="bi bi-folder2-open text-muted"></i>
                                                <div style="font-size:12px;">
                                                    <span x-show="product.category_data.parent" x-text="product.category_data.parent?.name + ' > '"></span>
                                                    <span class="fw-semibold text-body" x-text="product.category_data.name"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div x-show="product && product.warehouse_data">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase d-block border-top pt-2" style="font-size:9px;">Default Warehouse Info</label>
                                            <table class="table table-sm table-borderless small mb-0 text-muted" style="font-size: 11px;">
                                                <tbody>
                                                    <tr x-show="product?.warehouse_data?.name"><th class="ps-0" style="width:90px;">Name</th><td class="fw-semibold text-body" x-text="product.warehouse_data.name"></td></tr>
                                                    <tr x-show="product?.warehouse_data?.code"><th class="ps-0">Code</th><td x-text="product.warehouse_data.code"></td></tr>
                                                    <tr x-show="product?.warehouse_data?.phone"><th class="ps-0">Phone</th><td x-text="product.warehouse_data.phone"></td></tr>
                                                    <tr x-show="product?.warehouse_data?.city"><th class="ps-0">Location</th><td x-text="(product.warehouse_data.city || '') + (product.warehouse_data.state ? ', ' + product.warehouse_data.state : '')"></td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    <!-- end pvm-right -->
                </div>
                <!-- end pvm-layout -->
            </div>
            <!-- end modal-body -->

            <!-- Footer -->
            <div class="modal-footer bg-body-tertiary border-top p-3 d-flex justify-content-end align-items-center">
                <button type="button" class="btn btn-outline-secondary fw-medium" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="offersModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" x-data="{ promos: {offers: [], coupons: [], referrals: []} }" @set-promos.window="promos = $event.detail">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-gift-fill text-primary me-2"></i>Available Promotions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-3 pb-4">
                <div class="d-flex flex-column gap-3">
                    <!-- Offers -->
                    <div x-show="promos.offers.length > 0">
                        <h6 class="text-success fw-bold mb-2 border-bottom border-success border-opacity-25 pb-1"><i class="bi bi-tags-fill me-1"></i> Offers</h6>
                        <div class="d-flex flex-column gap-2">
                            <template x-for="offer in promos.offers" :key="'modal-offer-'+offer.id">
                                <div class="bg-success bg-opacity-10 border border-success border-opacity-25 rounded p-2">
                                    <div class="d-flex align-items-center gap-1 text-success fw-bold" style="font-size: 13px;">
                                        <i class="bi bi-tag-fill"></i> <span x-text="offer.name"></span>
                                    </div>
                                    <div class="text-muted mt-1" style="font-size: 12px;">
                                        <span x-show="offer.type === 'bogo'" x-text="'Buy ' + offer.buy_qty + ' Get ' + offer.get_qty + ' Free'"></span>
                                        <span x-show="offer.type !== 'bogo' && offer.discount_type === 'percent'" x-text="offer.value + '% OFF'"></span>
                                        <span x-show="offer.type !== 'bogo' && offer.discount_type === 'fixed'" x-text="'₹' + offer.value + ' OFF'"></span>
                                        <span x-show="offer.min_spend > 0" x-text="' (Min Order: ₹' + offer.min_spend + ')'"></span>
                                    </div>
                                    <div class="text-muted mt-1 fst-italic" style="font-size: 11px;" x-show="offer.description" x-text="offer.description"></div>
                                </div>
                            </template>
                        </div>
                    </div>
                    <!-- Coupons -->
                    <div x-show="promos.coupons.length > 0">
                        <h6 class="text-primary fw-bold mb-2 border-bottom border-primary border-opacity-25 pb-1"><i class="bi bi-ticket-perforated-fill me-1"></i> Coupons</h6>
                        <div class="d-flex flex-column gap-2">
                            <template x-for="coupon in promos.coupons" :key="'modal-coupon-'+coupon.id">
                                <div class="bg-primary bg-opacity-10 border border-primary border-opacity-25 rounded p-2">
                                    <div class="d-flex align-items-center gap-1 text-primary fw-bold" style="font-size: 13px;">
                                        <i class="bi bi-ticket-perforated-fill"></i> Code: <span x-text="coupon.code"></span>
                                    </div>
                                    <div class="text-muted mt-1" style="font-size: 12px;">
                                        <span x-text="coupon.discount_type === 'percent' ? coupon.value + '% OFF' : '₹' + coupon.value + ' OFF'"></span>
                                        <span x-show="coupon.min_spend > 0" x-text="' | Min Spend: ₹' + coupon.min_spend"></span>
                                        <span x-show="coupon.expiry_date" x-text="' | Valid till: ' + new Date(coupon.expiry_date).toLocaleDateString()"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                    <!-- Referrals -->
                    <div x-show="promos.referrals.length > 0">
                        <h6 class="text-info fw-bold mb-2 border-bottom border-info border-opacity-25 pb-1"><i class="bi bi-people-fill me-1"></i> Referral Programs</h6>
                        <div class="d-flex flex-column gap-2">
                            <template x-for="ref in promos.referrals" :key="'modal-ref-'+ref.id">
                                <div class="bg-info bg-opacity-10 border border-info border-opacity-25 rounded p-2">
                                    <div class="d-flex align-items-center gap-1 text-info fw-bold" style="font-size: 13px;">
                                        <i class="bi bi-people-fill"></i> <span x-text="ref.name"></span>
                                    </div>
                                    <div class="text-muted mt-1" style="font-size: 12px;" x-text="ref.description || 'Invite friends and earn rewards.'"></div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="importModal"  x-data="{ get table() { return Alpine.store('productTable') } }">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Import Products</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Upload CSV File</label>
                        <input id="productImportFile" type="file" class="form-control" accept=".csv">
                        <div class="form-text">Upload a CSV file with columns: name, sku, category_id or category, brand_id, uom_id, tax_rate_id, hsn_code_id, purchase_price, mrp, selling_price, stock, status</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label d-block">Import Mode</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="importMode" id="modeOverwrite" value="overwrite" x-model="table.importMode">
                            <label class="form-check-label" for="modeOverwrite">Overwrite Stock</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="importMode" id="modeIncrement" value="increment" x-model="table.importMode">
                            <label class="form-check-label" for="modeIncrement">Increment Stock</label>
                        </div>
                        <div class="form-text">Choose whether to replace existing stock quantities or add to them.</div>
                    </div>
                    <div class="alert alert-info mb-3">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>CSV Format:</strong> name, sku, category_id/category, purchase_price, mrp, selling_price, stock, status<br>
                        <small>Example: iPhone 14, IPHONE14-128, 1, 650, 799.99, 50, published</small>
                    </div>
                    <div class="alert alert-danger" x-show="table.importErrors.length > 0">
                        <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle me-1"></i>Import Errors</div>
                        <ul class="mb-0 small ps-3">
                            <template x-for="(error, index) in table.importErrors" :key="index">
                                <li x-text="error"></li>
                            </template>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" @click="table.importProducts()" :disabled="table.importing">
                        <span class="spinner-border spinner-border-sm me-1" x-show="table.importing"></span>
                        Import Products
                    </button>
                </div>
            </div>
        </div>
    </div>
    <style>
        /* ── Product View Modal — sticky left / scrollable right ── */
        #productViewModal .modal-dialog {
            max-height: calc(100vh - 3.5rem);
        }
        #productViewModal .modal-content {
            max-height: calc(100vh - 3.5rem);
            display: flex;
            flex-direction: column;
        }
        #productViewModal .modal-body {
            flex: 1 1 auto;
            overflow: hidden !important; /* outer body never scrolls */
            padding: 0 !important;
        }
        #productViewModal .pvm-layout {
            display: flex;
            flex-direction: row;
            height: 100%;
            min-height: 0;
        }
        #productViewModal .pvm-left {
            width: 300px;
            min-width: 280px;
            max-width: 300px;
            flex-shrink: 0;
            overflow-y: auto;
            /* max-height fills remaining modal height */
            max-height: calc(100vh - 3.5rem - 57px - 60px); /* vh minus header minus footer */
        }
        #productViewModal .pvm-right {
            flex: 1 1 0;
            min-width: 0;
            overflow-y: auto;
            max-height: calc(100vh - 3.5rem - 57px - 60px);
        }
        @media (max-width: 767.98px) {
            #productViewModal .pvm-layout {
                flex-direction: column;
            }
            #productViewModal .pvm-left,
            #productViewModal .pvm-right {
                width: 100%;
                max-width: 100%;
                max-height: none;
                overflow-y: visible;
            }
            #productViewModal .modal-body {
                overflow-y: auto !important;
            }
        }
    </style>
</div>
@endsection

@push('scripts')
@endpush
