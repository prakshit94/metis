<?php $__env->startSection('title', 'Product Management'); ?>
<?php $__env->startSection('page', 'catalog-products'); ?>

<?php $__env->startSection('content'); ?>
<div class="product-management" x-data="productTable">
<!-- Page Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4 mb-lg-5">
                        <div>
                            <h1 class="h3 mb-0">Product Management</h1>
                            <p class="text-muted mb-0">Manage your product catalog and inventory</p>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary" @click="exportProducts()">
                                <i class="bi bi-download me-2"></i>Export
                            </button>
                            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#importModal">
                                <i class="bi bi-upload me-2"></i>Import
                            </button>
                            <button type="button" class="btn btn-primary" @click.prevent="openCreateProduct()">
                                <i class="bi bi-plus-lg me-2"></i>Add Product
                            </button>
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
                                                <i class="bi bi-currency-dollar"></i>
                                            </div>
                                            <div>
                                                <p class="h6 mb-0 text-muted">Total Value</p>
                                                <div class="h3 mb-0" aria-live="polite"><span x-text="`$${stats.totalValue.toLocaleString()}`"></span></div>
                                                <small class="text-info">
                                                    <i class="bi bi-info-circle"></i> Inventory value
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Charts Row -->
                        <div class="row g-4 g-lg-5 mb-5">
                            <!-- Sales Performance Chart -->
                            <div class="col-lg-8">
                                <div class="card h-100">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h2 class="h5 card-title mb-0">Sales Performance</h2>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <input type="radio" class="btn-check" name="salesPeriod" id="sales7d" autocomplete="off" checked>
                                            <label class="btn btn-outline-secondary" for="sales7d">7D</label>
                                            <input type="radio" class="btn-check" name="salesPeriod" id="sales30d" autocomplete="off">
                                            <label class="btn btn-outline-secondary" for="sales30d">30D</label>
                                            <input type="radio" class="btn-check" name="salesPeriod" id="sales90d" autocomplete="off">
                                            <label class="btn btn-outline-secondary" for="sales90d">90D</label>
                                        </div>
                                    </div>
                                    <div class="card-body p-3 p-lg-4">
                                        <div id="salesChart" style="height: 300px;"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Category Distribution -->
                            <div class="col-lg-4">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h2 class="h5 card-title mb-0">Category Distribution</h2>
                                    </div>
                                    <div class="card-body p-3 p-lg-4">
                                        <div id="categoryChart" style="height: 200px;"></div>
                                        <div class="mt-3">
                                            <template x-for="category in categoryStats" :key="category.name">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span class="small" x-text="category.name"></span>
                                                    <div class="d-flex align-items-center">
                                                        <span class="small text-muted me-2" x-text="`${category.percentage}%`"></span>
                                                        <span class="small fw-medium" x-text="category.count"></span>
                                                    </div>
                                                </div>
                                            </template>
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
                                            <select class="form-select form-select-sm" 
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
                                            
                                            <!-- Stock Filter -->
                                            <select class="form-select form-select-sm" 
                                                    x-model="stockFilter" 
                                                    @change="filterProducts()"
                                                    style="width: 150px;">
                                                <option value="">All Stock</option>
                                                <option value="in-stock">In Stock</option>
                                                <option value="low-stock">Low Stock</option>
                                                <option value="out-of-stock">Out of Stock</option>
                                            </select>

                                            <!-- Items Per Page -->
                                            <select class="form-select form-select-sm"
                                                    x-model.number="itemsPerPage"
                                                    @change="filterProducts()"
                                                    style="width: 120px;">
                                                <option value="10">10 / page</option>
                                                <option value="25">25 / page</option>
                                                <option value="50">50 / page</option>
                                                <option value="100">100 / page</option>
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
                                <div class="bulk-actions-bar p-3 bg-primary bg-opacity-10 border-bottom border-primary border-opacity-25" x-show="selectedProducts.length > 0" x-transition>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-check-circle-fill text-primary me-2"></i>
                                            <span class="fw-medium text-primary">
                                                <span x-text="selectedProducts.length"></span> record<span x-show="selectedProducts.length !== 1">s</span> selected
                                            </span>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-sm btn-outline-secondary bg-body" @click="bulkAction('publish')">
                                                <i class="bi bi-eye me-1"></i>Publish
                                            </button>
                                            <button class="btn btn-sm btn-outline-secondary bg-body" @click="bulkAction('unpublish')">
                                                <i class="bi bi-eye-slash me-1"></i>Unpublish
                                            </button>
                                            <button class="btn btn-sm btn-outline-secondary bg-body" @click="bulkAction('disable_sku')">
                                                <i class="bi bi-tags me-1"></i>Disable SKU
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger bg-body" @click="bulkAction('delete')">
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
                                                <th style="width: 40px;">
                                                    <input type="checkbox" 
                                                           class="form-check-input" 
                                                           @change="$event.isTrusted && toggleAll($event.target.checked)"
                                                           :checked="selectedProducts.length === filteredProducts.length && filteredProducts.length > 0">
                                                </th>
                                                <th>Product</th>
                                                <th @click="sortBy('category')" class="sortable">Category</th>
                                                <th @click="sortBy('price')" class="sortable">Price</th>
                                                <th @click="sortBy('stock')" class="sortable">Stock</th>
                                                <th>Status</th>
                                                <th>Tracking</th>
                                                <th @click="sortBy('created')" class="sortable">Created</th>
                                                <th style="width: 120px;">Actions</th>
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
                                                <tr>
                                                    <td>
                                                        <input type="checkbox" 
                                                               class="form-check-input" 
                                                               :value="product.id"
                                                               :checked="selectedProducts.includes(product.id)"
                                                               @change="toggleProduct(product.id)">
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <img :src="product.image" 
                                                                 class="product-image me-3" 
                                                                 :alt="product.name">
                                                            <div>
                                                                <div class="fw-medium" x-text="product.name"></div>
                                                                <small class="text-muted" x-text="'SKU: ' + product.sku"></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-light text-dark" x-text="product.category"></span>
                                                    </td>
                                                    <td x-text="'$' + Number(product.price).toFixed(2)"></td>
                                                    <td>
                                                        <span class="badge stock-badge" 
                                                              :class="{
                                                                  'in-stock': product.stock > 20,
                                                                  'low-stock': product.stock > 0 && product.stock <= 20,
                                                                  'out-of-stock': product.stock === 0
                                                              }"
                                                              x-text="parseFloat(product.stock) + ' units'"></span>
                                                    </td>
                                                    <td>
                                                        <span class="badge" 
                                                              :class="{
                                                                  'bg-success': ['published', 'active'].includes(product.status),
                                                                  'bg-secondary': product.status === 'draft',
                                                                  'bg-warning': ['pending', 'out_of_stock'].includes(product.status)
                                                              }"
                                                              x-text="product.status"></span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex flex-wrap gap-1">
                                                            <span class="badge bg-purple bg-opacity-10 text-purple border border-purple border-opacity-25" x-show="product.batch_tracking" title="Batch Tracking">Batch</span>
                                                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25" x-show="product.expiry_tracking" title="Expiry Tracking">Expiry</span>
                                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25" x-show="product.allow_overselling" title="Allow Overselling">Oversell</span>
                                                            <span class="text-muted small" x-show="!product.batch_tracking && !product.expiry_tracking && !product.allow_overselling">-</span>
                                                        </div>
                                                    </td>
                                                    <td x-text="product.created"></td>
                                                    <td>
                                                        <div class="dropdown">
                                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                                    type="button" 
                                                                    data-bs-toggle="dropdown">
                                                                <i class="bi bi-three-dots"></i>
                                                            </button>
                                                            <ul class="dropdown-menu">
                                                                <li><a class="dropdown-item" href="#" @click.prevent="editProduct(product)">
                                                                    <i class="bi bi-pencil me-2"></i>Edit
                                                                </a></li>
                                                                <li><a class="dropdown-item" href="#" @click.prevent="viewProduct(product)">
                                                                    <i class="bi bi-eye me-2"></i>View Details
                                                                </a></li>
                                                                <li><a class="dropdown-item" href="#" @click.prevent="duplicateProduct(product)">
                                                                    <i class="bi bi-copy me-2"></i>Duplicate
                                                                </a></li>
                                                                <li><hr class="dropdown-divider"></li>
                                                                <li><a class="dropdown-item text-danger" href="#" @click.prevent="deleteProduct(product)">
                                                                    <i class="bi bi-trash me-2"></i>Delete
                                                                </a></li>
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
<div class="modal fade" id="productModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content" x-data="productForm">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <form @submit.prevent="saveProduct()">
                        <div class="row g-4">
                            <!-- Left Column (General Info, Pricing, Descriptions) -->
                            <div class="col-lg-8">
                                <!-- Card 1: General Info -->
                                <div class="card border-0 shadow-sm mb-4 bg-body-tertiary">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <i class="bi bi-info-circle-fill"></i>
                                            </div>
                                            <h6 class="card-title mb-0 fw-bold">General Information</h6>
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label fw-medium text-muted small">Product Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" x-model="form.name" required placeholder="e.g. Wireless Noise Cancelling Headphones">
                                            </div>
                                            <div class="col-md-8">
                                                <label class="form-label fw-medium text-muted small">SKU <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" x-model="form.sku" :disabled="!form.is_sku_enabled" :required="form.is_sku_enabled" placeholder="SKU Code">
                                                    <div class="input-group-text bg-body-secondary border-start-0">
                                                        <div class="form-check form-switch m-0">
                                                            <input class="form-check-input" type="checkbox" role="switch" x-model="form.is_sku_enabled" id="skuEnabledToggle">
                                                            <label class="form-check-label small fw-medium ms-1" for="skuEnabledToggle">Enabled</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fw-medium text-muted small">Barcode / UPC</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-body-secondary"><i class="bi bi-upc-scan"></i></span>
                                                    <input type="text" class="form-control" x-model="form.barcode" placeholder="Scan or enter barcode">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium text-muted small">Category <span class="text-danger">*</span></label>
                                                <select class="form-select" x-model="form.category_id" required>
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
                                                <select class="form-select" x-model="form.brand_id">
                                                    <option value="">No Brand</option>
                                                    <template x-for="brand in options.brands" :key="brand.id">
                                                        <option :value="String(brand.id)" x-text="brand.name"></option>
                                                    </template>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card 2: Pricing & Discount -->
                                <div class="card border-0 shadow-sm mb-4 bg-body-tertiary">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="bg-success bg-opacity-10 text-success rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <i class="bi bi-currency-dollar"></i>
                                            </div>
                                            <h6 class="card-title mb-0 fw-bold">Pricing & Taxation</h6>
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label fw-medium text-muted small">Purchase Price <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-body-secondary">$</span>
                                                    <input type="number" class="form-control" x-model="form.purchase_price" step="0.01" min="0" required placeholder="0.00">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fw-medium text-muted small">MRP</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-body-secondary">$</span>
                                                    <input type="number" class="form-control" x-model="form.mrp" step="0.01" min="0" placeholder="0.00">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fw-medium text-muted small">Selling Price <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-body-secondary">$</span>
                                                    <input type="number" class="form-control" x-model="form.selling_price" step="0.01" min="0" required placeholder="0.00">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium text-muted small">Default Discount</label>
                                                <input type="number" class="form-control" x-model="form.default_discount" step="0.01" min="0" placeholder="0.00">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium text-muted small">Discount Type</label>
                                                <select class="form-select" x-model="form.default_discount_type">
                                                    <option value="percent">Percent (%)</option>
                                                    <option value="flat">Flat ($)</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium text-muted small">Tax Rate</label>
                                                <select class="form-select" x-model="form.tax_rate_id">
                                                    <option value="">No Tax</option>
                                                    <template x-for="rate in options.taxRates" :key="rate.id">
                                                        <option :value="String(rate.id)" x-text="rate.name + ' (' + rate.rate + '%)'"></option>
                                                    </template>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium text-muted small">HSN Code</label>
                                                <select class="form-select" x-model="form.hsn_code_id">
                                                    <option value="">No HSN</option>
                                                    <template x-for="hsn in options.hsnCodes" :key="hsn.id">
                                                        <option :value="String(hsn.id)" x-text="hsn.code + (hsn.description ? ' - ' + hsn.description : '')"></option>
                                                    </template>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card 3: Descriptions -->
                                <div class="card border-0 shadow-sm mb-4 bg-body-tertiary">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="bg-info bg-opacity-10 text-info rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <i class="bi bi-text-left"></i>
                                            </div>
                                            <h6 class="card-title mb-0 fw-bold">Detailed Descriptions</h6>
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label fw-medium text-muted small">Product Description</label>
                                                <textarea class="form-control" x-model="form.description" rows="3" placeholder="Enter detailed product description..."></textarea>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label fw-medium text-muted small">Application Instructions</label>
                                                <textarea class="form-control" x-model="form.application_instructions" rows="3" placeholder="Enter instructions for use/application..."></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column (Media, Inventory, Toggles, Attributes) -->
                            <div class="col-lg-4">
                                <!-- Card 4: Status & Media -->
                                <div class="card border-0 shadow-sm mb-4 bg-body-tertiary">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="bg-warning bg-opacity-10 text-warning rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <i class="bi bi-image"></i>
                                            </div>
                                            <h6 class="card-title mb-0 fw-bold">Status & Media</h6>
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label fw-medium text-muted small">Product Status <span class="text-danger">*</span></label>
                                                <select class="form-select" x-model="form.status" required>
                                                    <option value="">Select Status</option>
                                                    <template x-for="status in options.statusList" :key="status.value">
                                                        <option :value="status.value" x-text="status.label"></option>
                                                    </template>
                                                </select>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label fw-medium text-muted small">Product Image</label>
                                                <div class="border border-dashed rounded p-3 text-center bg-body-secondary d-flex flex-column align-items-center justify-content-center" style="min-height: 150px; border-style: dashed !important;">
                                                    <div class="mb-2">
                                                        <template x-if="form.image">
                                                            <img :src="form.image" alt="Preview" class="rounded border shadow-sm" style="width: 80px; height: 80px; object-fit: cover;">
                                                        </template>
                                                        <template x-if="!form.image">
                                                            <i class="bi bi-cloud-arrow-up fs-2 text-muted"></i>
                                                        </template>
                                                    </div>
                                                    <input type="file" class="form-control form-control-sm" accept="image/*" @change="handleImageUpload($event)">
                                                    <small class="text-muted mt-1" style="font-size: 0.7rem;">Click to upload image</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card 5: Inventory & Logistics -->
                                <div class="card border-0 shadow-sm mb-4 bg-body-tertiary">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="bg-danger bg-opacity-10 text-danger rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <i class="bi bi-box-seam"></i>
                                            </div>
                                            <h6 class="card-title mb-0 fw-bold">Inventory & Logistics</h6>
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium text-muted small">Stock Quantity <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control" x-model="form.stock" min="0" required placeholder="0">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium text-muted small">Min Stock Level</label>
                                                <input type="number" class="form-control" x-model="form.min_stock_level" min="0" placeholder="0">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium text-muted small">Unit (UOM)</label>
                                                <select class="form-select" x-model="form.uom_id">
                                                    <option value="">Select Unit</option>
                                                    <template x-for="uom in options.uoms" :key="uom.id">
                                                        <option :value="String(uom.id)" x-text="uom.name + (uom.short_name ? ' (' + uom.short_name + ')' : '')"></option>
                                                    </template>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium text-muted small">Warehouse</label>
                                                <select class="form-select" x-model="form.default_warehouse_id">
                                                    <option value="">No Default</option>
                                                    <template x-for="warehouse in options.warehouses" :key="warehouse.id">
                                                        <option :value="String(warehouse.id)" x-text="warehouse.name"></option>
                                                    </template>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium text-muted small">Weight / Volume</label>
                                                <input type="text" class="form-control" x-model="form.weight" placeholder="e.g. 1kg, 500ml">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium text-muted small">Grade</label>
                                                <select class="form-select" x-model="form.grade">
                                                    <option value="">No Grade</option>
                                                    <option value="A">A</option>
                                                    <option value="B">B</option>
                                                    <option value="C">C</option>
                                                    <option value="D">D</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card 6: Tracking Parameters -->
                                <div class="card border-0 shadow-sm mb-4 bg-body-tertiary">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="bg-purple bg-opacity-10 text-purple rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <i class="bi bi-sliders"></i>
                                            </div>
                                            <h6 class="card-title mb-0 fw-bold">Tracking Settings</h6>
                                        </div>
                                        <div class="d-flex flex-column gap-2 mb-3">
                                            <div class="p-2 border rounded bg-body-secondary">
                                                <div class="form-check form-switch m-0 d-flex align-items-center justify-content-between">
                                                    <label class="form-check-label fw-medium small" for="manageStockToggle">Manage stock</label>
                                                    <input class="form-check-input m-0" type="checkbox" role="switch" x-model="form.manage_stock" id="manageStockToggle">
                                                </div>
                                            </div>
                                            <div class="p-2 border rounded bg-body-secondary">
                                                <div class="form-check form-switch m-0 d-flex align-items-center justify-content-between">
                                                    <label class="form-check-label fw-medium small" for="batchTrackingToggle">Batch tracking</label>
                                                    <input class="form-check-input m-0" type="checkbox" role="switch" x-model="form.batch_tracking" id="batchTrackingToggle">
                                                </div>
                                            </div>
                                            <div class="p-2 border rounded bg-body-secondary">
                                                <div class="form-check form-switch m-0 d-flex align-items-center justify-content-between">
                                                    <label class="form-check-label fw-medium small" for="expiryTrackingToggle">Expiry tracking</label>
                                                    <input class="form-check-input m-0" type="checkbox" role="switch" x-model="form.expiry_tracking" id="expiryTrackingToggle">
                                                </div>
                                            </div>
                                            <div class="p-2 border rounded bg-body-secondary">
                                                <div class="form-check form-switch m-0 d-flex align-items-center justify-content-between">
                                                    <label class="form-check-label fw-medium small" for="allowOversellToggle">Allow overselling</label>
                                                    <input class="form-check-input m-0" type="checkbox" role="switch" x-model="form.allow_overselling" id="allowOversellToggle">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12" x-show="form.allow_overselling" x-transition>
                                            <label class="form-label fw-medium text-muted small">Overselling Limit Quantity</label>
                                            <input type="number" class="form-control" x-model="form.overselling_qty" min="0" placeholder="0">
                                        </div>
                                    </div>
                                </div>

                                <!-- Card 7: Attributes -->
                                <div class="card border-0 shadow-sm mb-4 bg-body-tertiary">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="bg-teal bg-opacity-10 text-teal rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <i class="bi bi-tags"></i>
                                            </div>
                                            <h6 class="card-title mb-0 fw-bold">Product Attributes</h6>
                                        </div>
                                        <div class="border rounded p-3 bg-body-secondary">
                                            <template x-for="attribute in options.attributes" :key="attribute.id">
                                                <div class="mb-3">
                                                    <div class="fw-bold small mb-2 text-primary text-uppercase" x-text="attribute.name" style="font-size: 0.75rem; letter-spacing: 0.5px;"></div>
                                                    <div class="d-flex flex-wrap gap-1">
                                                        <template x-for="value in attribute.values" :key="value.id">
                                                            <label class="btn btn-xs py-1 px-2 border rounded-pill text-nowrap d-flex align-items-center gap-1"
                                                                   :class="form.attributes.includes(String(value.id)) ? 'btn-primary border-primary' : 'btn-outline-secondary bg-body'"
                                                                   style="font-size: 0.75rem; cursor: pointer;">
                                                                <input type="checkbox" class="d-none" :value="String(value.id)" x-model="form.attributes">
                                                                <span x-text="value.value"></span>
                                                            </label>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-top-0 pt-0">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary px-4">Save Product</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<div class="modal fade" id="productViewModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content bg-body border-0 shadow-lg" x-data="{ get product() { return Alpine.store('productTable')?.previewProduct } }">
            <!-- Header -->
            <div class="modal-header bg-body-tertiary border-bottom py-3 px-4">
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <h5 class="modal-title fw-bold mb-0" x-text="product ? product.name : ''"></h5>
                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary" x-text="product ? product.sku : ''"></span>
                    <span class="badge" :class="product && ['published', 'active'].includes(product.status) ? 'bg-success' : 'bg-warning text-dark'" x-text="product ? product.status : ''"></span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Body: scrollable single column -->
            <div class="modal-body p-0" style="overflow-y: auto;">
                <div class="row g-0" style="min-height: 100%;">
                    <!-- Left: Image & Meta (fixed panel) -->
                    <div class="col-md-4 bg-body-tertiary border-end p-3" style="position: sticky; top: 0; height: fit-content; align-self: flex-start;">
                        <div class="card border border-secondary border-opacity-25 mb-3 rounded-4 overflow-hidden position-relative" style="aspect-ratio:1;width:100%;">
                            <img :src="product ? (product.image || '/assets/images/product-placeholder.svg') : ''" class="w-100 h-100 object-fit-cover" x-on:error="$el.src='/assets/images/product-placeholder.svg'">
                            <span class="position-absolute top-0 end-0 m-2 badge bg-success shadow-sm" x-show="product && product.default_discount > 0" x-text="product ? product.default_discount + (product.default_discount_type === 'percent' ? '%' : '') + ' OFF' : ''"></span>
                        </div>
                        <div x-show="product">
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <span x-show="product && (product.category_label || product.category)" class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 py-2 px-3"><i class="bi bi-tag-fill me-1"></i><span x-text="product ? (product.category_label || product.category) : ''"></span></span>
                                <span x-show="product && product.brand" class="badge bg-dark bg-opacity-10 text-dark border py-2 px-3"><i class="bi bi-award-fill me-1"></i><span x-text="product ? product.brand : ''"></span></span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-borderless small mb-0 text-muted">
                                    <tbody>
                                        <tr x-show="product && product.barcode"><th class="ps-0" style="width:100px;">Barcode</th><td x-text="product ? product.barcode : ''"></td></tr>
                                        <tr x-show="product && product.weight"><th class="ps-0">Weight</th><td x-text="product ? product.weight : ''"></td></tr>
                                        <tr x-show="product && product.uom"><th class="ps-0">UOM</th><td x-text="product ? product.uom : ''"></td></tr>
                                        <tr x-show="product && product.grade"><th class="ps-0">Grade</th><td x-text="product ? product.grade : ''"></td></tr>
                                        <tr x-show="product && product.warehouse"><th class="ps-0">Warehouse</th><td x-text="product ? product.warehouse : ''"></td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Right: All Data Cards (scrolls with modal-body) -->
                    <div class="col-md-8 p-3">

                        <!-- Pricing Card -->
                        <div class="card mb-3 border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom border-secondary border-opacity-25">
                                    <div class="bg-primary bg-opacity-10 text-primary rounded-2 d-flex align-items-center justify-content-center" style="width:24px;height:24px;"><i class="bi bi-tag-fill" style="font-size:12px;"></i></div>
                                    <h6 class="mb-0 fw-bold text-uppercase text-body" style="font-size:11px;letter-spacing:1px;">Pricing Breakdown</h6>
                                </div>
                                <div class="row g-2 mb-2">
                                    <div class="col-4 border-end border-secondary border-opacity-25">
                                        <label class="form-label mb-1 fw-bold text-muted text-uppercase d-block" style="font-size:9px;">Selling Price</label>
                                        <div class="fw-black text-primary" style="font-size:18px;" x-text="product ? 'Rs ' + parseFloat(product.selling_price || product.price || 0).toFixed(2) : ''"></div>
                                        <div class="text-muted text-decoration-line-through" style="font-size:10px;" x-show="product && product.mrp > (product.selling_price || product.price)" x-text="product ? 'MRP Rs ' + parseFloat(product.mrp||0).toFixed(2) : ''"></div>
                                    </div>
                                    <div class="col-4 border-end border-secondary border-opacity-25 ps-3">
                                        <label class="form-label mb-1 fw-bold text-muted text-uppercase d-block" style="font-size:9px;">Purchase Price</label>
                                        <div class="fw-bold text-body-emphasis" style="font-size:14px;" x-text="product ? 'Rs ' + parseFloat(product.purchase_price||0).toFixed(2) : ''"></div>
                                    </div>
                                    <div class="col-4 ps-3">
                                        <label class="form-label mb-1 fw-bold text-muted text-uppercase d-block" style="font-size:9px;">Profit Margin</label>
                                        <div class="fw-bold text-success" style="font-size:14px;" x-text="product && (product.selling_price || product.price) > 0 && product.purchase_price > 0 ? ((((product.selling_price || product.price) - product.purchase_price) / product.purchase_price) * 100).toFixed(1) + '%' : 'N/A'"></div>
                                    </div>
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

                        <!-- Inventory & Specs Row -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <div class="card h-100 border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center gap-2 pb-2 mb-2 border-bottom border-secondary border-opacity-25">
                                            <div class="bg-warning bg-opacity-10 rounded-2 d-flex align-items-center justify-content-center" style="width:24px;height:24px;color:#ffc107;"><i class="bi bi-box-seam-fill" style="font-size:12px;"></i></div>
                                            <h6 class="mb-0 fw-bold text-uppercase text-body" style="font-size:11px;letter-spacing:1px;">Inventory</h6>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom border-secondary border-opacity-25">
                                            <div>
                                                <div class="fw-bold text-body-emphasis" style="font-size:16px;" x-text="product ? (parseFloat(product.available_stock !== undefined ? product.available_stock : product.stock) + ' ' + (product.uom || 'Units')) : ''"></div>
                                                <div class="text-muted" style="font-size:10px;">Available to Order</div>
                                            </div>
                                            <span class="badge" style="font-size:10px;" :class="product && (product.available_stock !== undefined ? product.available_stock : product.stock) > (product.min_stock_level || 10) ? 'bg-success' : (product && (product.available_stock !== undefined ? product.available_stock : product.stock) > 0 ? 'bg-warning text-dark' : 'bg-danger')" x-text="product && (product.available_stock !== undefined ? product.available_stock : product.stock) > 0 ? 'In Stock' : 'Out of Stock'"></span>
                                        </div>
                                        <div class="row text-center g-1 mb-2">
                                            <div class="col-4"><div class="fw-semibold" style="font-size:13px;" x-text="product ? parseFloat(product.physical_available !== undefined ? product.physical_available : product.stock) : 0"></div><div class="text-muted" style="font-size:9px;">Physical</div></div>
                                            <div class="col-4 border-start border-end border-secondary border-opacity-25"><div class="fw-semibold text-warning" style="font-size:13px;" x-text="product ? ((product.reserved_qty || 0) + (product.pending_qty || 0)) : 0"></div><div class="text-muted" style="font-size:9px;">Reserved</div></div>
                                            <div class="col-4"><div class="fw-semibold text-danger" style="font-size:13px;" x-text="product ? (product.min_stock_level || 0) : 0"></div><div class="text-muted" style="font-size:9px;">Min Level</div></div>
                                        </div>
                                        <label class="form-label mb-1 fw-bold text-muted text-uppercase d-block" style="font-size:9px;">Tracking</label>
                                        <div class="list-group list-group-flush border border-secondary border-opacity-25 rounded-3">
                                            <div class="list-group-item d-flex justify-content-between align-items-center px-2 py-1 bg-transparent"><span class="text-muted" style="font-size:10px;"><i class="bi bi-box me-1"></i>Batch</span><span class="badge" style="font-size:9px;" :class="product && product.batch_tracking ? 'bg-success' : 'bg-secondary'" x-text="product && product.batch_tracking ? 'ON' : 'OFF'"></span></div>
                                            <div class="list-group-item d-flex justify-content-between align-items-center px-2 py-1 bg-transparent"><span class="text-muted" style="font-size:10px;"><i class="bi bi-calendar-x me-1"></i>Expiry</span><span class="badge" style="font-size:9px;" :class="product && product.expiry_tracking ? 'bg-success' : 'bg-secondary'" x-text="product && product.expiry_tracking ? 'ON' : 'OFF'"></span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card h-100 border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary">
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
                        </div>

                        <!-- Details & Usage -->
                        <div class="card mb-3 border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom border-secondary border-opacity-25">
                                    <div class="bg-secondary bg-opacity-10 text-secondary rounded-2 d-flex align-items-center justify-content-center" style="width:24px;height:24px;"><i class="bi bi-file-text-fill" style="font-size:12px;"></i></div>
                                    <h6 class="mb-0 fw-bold text-uppercase text-body" style="font-size:11px;letter-spacing:1px;">Details & Usage</h6>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6 border-end border-secondary border-opacity-25">
                                        <label class="form-label mb-1 fw-bold text-muted text-uppercase d-block" style="font-size:9px;">Product Description</label>
                                        <div style="font-size:11px;" x-show="product && product.description" x-html="product ? product.description : ''"></div>
                                        <div style="font-size:11px;" x-show="!product || !product.description" class="text-muted fst-italic">No description available.</div>
                                    </div>
                                    <div class="col-md-6 ps-3">
                                        <label class="form-label mb-1 fw-bold text-muted text-uppercase d-block" style="font-size:9px;">Application / Dosage</label>
                                        <div style="font-size:11px;" x-show="product && product.application_instructions" x-html="product ? product.application_instructions : ''"></div>
                                        <div style="font-size:11px;" x-show="!product || !product.application_instructions" class="text-muted fst-italic">No instructions available.</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    <!-- end col-md-8 -->
                </div>
                <!-- end row -->
            </div>
            <!-- end modal-body -->

            <!-- Footer -->
            <div class="modal-footer bg-body-tertiary border-top p-3 d-flex justify-content-end align-items-center">
                <button type="button" class="btn btn-light fw-medium" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="importModal" tabindex="-1" x-data="{ get table() { return Alpine.store('productTable') } }">
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
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>CSV Format:</strong> name, sku, category_id/category, purchase_price, mrp, selling_price, stock, status<br>
                        <small>Example: iPhone 14, IPHONE14-128, 1, 650, 799.99, 50, published</small>
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
        /* Product Details Modal — two-column layout */
        #productViewModal .modal-body > .row {
            display: flex;
            flex-wrap: nowrap;
            align-items: flex-start;
        }
        #productViewModal .modal-body > .row > .col-md-4 {
            min-width: 300px;
            max-width: 300px;
        }
        #productViewModal .modal-body > .row > .col-md-8 {
            flex: 1;
            min-width: 0;
        }
    </style>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/user/metis/resources/views/catalog/products/index.blade.php ENDPATH**/ ?>