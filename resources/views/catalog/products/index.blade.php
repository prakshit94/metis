@extends('layouts.app')

@section('title', 'Product Management')
@section('page', 'catalog-products')

@section('content')
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
                            <div class="col-xl-3 col-lg-6">
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
                            <div class="col-xl-3 col-lg-6">
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
                            <div class="col-xl-3 col-lg-6">
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
                                        <div class="d-flex gap-2">
                                            <!-- Search -->
                                            <div class="position-relative">
                                                <input type="search" 
                                                       class="form-control form-control-sm" 
                                                       placeholder="Search products..."
                                                       x-model="searchQuery"
                                                       @input="filterProducts()"
                                                       style="width: 200px;">
                                                <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted"></i>
                                            </div>
                                            
                                            <!-- Category Filter -->
                                            <select class="form-select form-select-sm" 
                                                    x-model="categoryFilter" 
                                                    @change="filterProducts()"
                                                    style="width: 150px;">
                                                <option value="">All Categories</option>
                                                <option value="electronics">Electronics</option>
                                                <option value="clothing">Clothing</option>
                                                <option value="books">Books</option>
                                                <option value="home">Home & Garden</option>
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
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <!-- Bulk Actions Bar -->
                                <div class="bulk-actions-bar p-3 bg-light border-bottom" x-show="selectedProducts.length > 0" x-transition>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">
                                            <span x-text="selectedProducts.length"></span> product(s) selected
                                        </span>
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-sm btn-outline-secondary" @click="bulkAction('publish')">
                                                <i class="bi bi-eye me-1"></i>Publish
                                            </button>
                                            <button class="btn btn-sm btn-outline-secondary" @click="bulkAction('unpublish')">
                                                <i class="bi bi-eye-slash me-1"></i>Unpublish
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
                                                <th @click="sortBy('created')" class="sortable">Created</th>
                                                <th style="width: 120px;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-if="paginatedProducts.length === 0">
                                                <tr>
                                                    <td colspan="8" class="text-center py-5 text-muted">
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
                                                    <td x-text="`$${product.price}`"></td>
                                                    <td>
                                                        <span class="badge stock-badge" 
                                                              :class="{
                                                                  'in-stock': product.stock > 20,
                                                                  'low-stock': product.stock > 0 && product.stock <= 20,
                                                                  'out-of-stock': product.stock === 0
                                                              }"
                                                              x-text="product.stock + ' units'"></span>
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
        <div class="modal-dialog modal-lg">
            <div class="modal-content" x-data="productForm">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form @submit.prevent="saveProduct()">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Product Name</label>
                                <input type="text" class="form-control" x-model="form.name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">SKU</label>
                                <input type="text" class="form-control" x-model="form.sku" required>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" x-model="form.is_sku_enabled" id="skuEnabledToggle">
                                    <label class="form-check-label" for="skuEnabledToggle">SKU enabled</label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Category</label>
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
                                <label class="form-label">Brand</label>
                                <select class="form-select" x-model="form.brand_id">
                                    <option value="">No Brand</option>
                                    <template x-for="brand in options.brands" :key="brand.id">
                                        <option :value="String(brand.id)" x-text="brand.name"></option>
                                    </template>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Unit (UOM)</label>
                                <select class="form-select" x-model="form.uom_id">
                                    <option value="">Select Unit</option>
                                    <template x-for="uom in options.uoms" :key="uom.id">
                                        <option :value="String(uom.id)" x-text="uom.name + (uom.short_name ? ' (' + uom.short_name + ')' : '')"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tax Rate</label>
                                <select class="form-select" x-model="form.tax_rate_id">
                                    <option value="">No Tax</option>
                                    <template x-for="rate in options.taxRates" :key="rate.id">
                                        <option :value="String(rate.id)" x-text="rate.name + ' (' + rate.rate + '%)'"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">HSN Code</label>
                                <select class="form-select" x-model="form.hsn_code_id">
                                    <option value="">No HSN</option>
                                    <template x-for="hsn in options.hsnCodes" :key="hsn.id">
                                        <option :value="String(hsn.id)" x-text="hsn.code + (hsn.description ? ' - ' + hsn.description : '')"></option>
                                    </template>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Barcode / UPC</label>
                                <input type="text" class="form-control" x-model="form.barcode" placeholder="Scan barcode">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Weight / Volume</label>
                                <input type="text" class="form-control" x-model="form.weight" placeholder="e.g. 1kg, 500ml">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Default Warehouse</label>
                                <select class="form-select" x-model="form.default_warehouse_id">
                                    <option value="">No Default</option>
                                    <template x-for="warehouse in options.warehouses" :key="warehouse.id">
                                        <option :value="String(warehouse.id)" x-text="warehouse.name"></option>
                                    </template>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Purchase Price</label>
                                <input type="number" class="form-control" x-model="form.purchase_price" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">MRP</label>
                                <input type="number" class="form-control" x-model="form.mrp" step="0.01" min="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Selling Price</label>
                                <input type="number" class="form-control" x-model="form.selling_price" step="0.01" min="0" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Default Discount</label>
                                <input type="number" class="form-control" x-model="form.default_discount" step="0.01" min="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Discount Type</label>
                                <select class="form-select" x-model="form.default_discount_type">
                                    <option value="percent">Percent</option>
                                    <option value="flat">Flat</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Grade</label>
                                <select class="form-select" x-model="form.grade">
                                    <option value="">No Grade</option>
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                    <option value="D">D</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Stock Quantity</label>
                                <input type="number" class="form-control" x-model="form.stock" min="0" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Min Stock Level</label>
                                <input type="number" class="form-control" x-model="form.min_stock_level" min="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Overselling Limit</label>
                                <input type="number" class="form-control" x-model="form.overselling_qty" min="0" :disabled="!form.allow_overselling">
                            </div>

                            <div class="col-md-3">
                                <div class="form-check form-switch mt-4 pt-2">
                                    <input class="form-check-input" type="checkbox" role="switch" x-model="form.manage_stock" id="manageStockToggle">
                                    <label class="form-check-label" for="manageStockToggle">Manage stock</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check form-switch mt-4 pt-2">
                                    <input class="form-check-input" type="checkbox" role="switch" x-model="form.batch_tracking" id="batchTrackingToggle">
                                    <label class="form-check-label" for="batchTrackingToggle">Batch tracking</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check form-switch mt-4 pt-2">
                                    <input class="form-check-input" type="checkbox" role="switch" x-model="form.expiry_tracking" id="expiryTrackingToggle">
                                    <label class="form-check-label" for="expiryTrackingToggle">Expiry tracking</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check form-switch mt-4 pt-2">
                                    <input class="form-check-input" type="checkbox" role="switch" x-model="form.allow_overselling" id="allowOversellToggle">
                                    <label class="form-check-label" for="allowOversellToggle">Allow overselling</label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select class="form-select" x-model="form.status" required>
                                    <option value="">Select Status</option>
                                    <template x-for="status in options.statusList" :key="status.value">
                                        <option :value="status.value" x-text="status.label"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Product Attributes</label>
                                <div class="border rounded p-3">
                                    <template x-for="attribute in options.attributes" :key="attribute.id">
                                        <div class="mb-3">
                                            <div class="fw-semibold mb-2" x-text="attribute.name"></div>
                                            <div class="d-flex flex-wrap gap-2">
                                                <template x-for="value in attribute.values" :key="value.id">
                                                    <label class="form-check form-check-inline m-0">
                                                        <input class="form-check-input me-1" type="checkbox" :value="String(value.id)" x-model="form.attributes">
                                                        <span class="form-check-label" x-text="value.value"></span>
                                                    </label>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Application Instructions</label>
                                <textarea class="form-control" x-model="form.application_instructions" rows="3"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" x-model="form.description" rows="3"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Product Image</label>
                                <input type="file" class="form-control" accept="image/*" @change="handleImageUpload($event)">
                                <div class="mt-2 d-flex align-items-center gap-3" x-show="form.image">
                                    <img :src="form.image" alt="Product preview" class="rounded border" style="width: 72px; height: 72px; object-fit: cover;">
                                    <small class="text-muted">Preview updates when a file is selected.</small>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Product</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<div class="modal fade" id="productViewModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" x-data="{ get product() { return Alpine.store('productTable')?.previewProduct } }">
                <div class="modal-header">
                    <h5 class="modal-title">Product Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <template x-if="product">
                        <div class="row g-4">
                            <div class="col-md-4">
                                <img class="img-fluid rounded border w-100" :src="product.image || '/assets/images/product-placeholder.svg'" :alt="product.name">
                            </div>
                            <div class="col-md-8">
                                <h4 class="mb-1" x-text="product.name"></h4>
                                <p class="text-muted mb-3" x-text="'SKU: ' + product.sku"></p>
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <div class="border rounded p-3">
                                            <div class="text-muted small">Category</div>
                                            <div class="fw-semibold text-capitalize" x-text="product.category_label || product.category"></div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="border rounded p-3">
                                            <div class="text-muted small">Price</div>
                                            <div class="fw-semibold" x-text="`$${Number(product.price).toFixed(2)}`"></div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="border rounded p-3">
                                            <div class="text-muted small">Stock</div>
                                            <div class="fw-semibold" x-text="`${product.stock} units`"></div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="border rounded p-3">
                                            <div class="text-muted small">Status</div>
                                            <div class="fw-semibold text-capitalize" x-text="product.status"></div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="border rounded p-3">
                                            <div class="text-muted small">Brand</div>
                                            <div class="fw-semibold" x-text="product.brand || 'No brand'"></div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="border rounded p-3">
                                            <div class="text-muted small">UOM</div>
                                            <div class="fw-semibold" x-text="product.uom || 'N/A'"></div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="border rounded p-3">
                                            <div class="text-muted small">Tax / HSN</div>
                                            <div class="fw-semibold" x-text="`${product.tax_label || 'No tax'} / ${product.hsn_code || 'No HSN'}`"></div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="border rounded p-3">
                                            <div class="text-muted small">Warehouse</div>
                                            <div class="fw-semibold" x-text="product.warehouse || 'No warehouse'"></div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="border rounded p-3">
                                            <div class="text-muted small">Purchase / MRP</div>
                                            <div class="fw-semibold" x-text="`$${Number(product.purchase_price || 0).toFixed(2)} / $${Number(product.mrp || 0).toFixed(2)}`"></div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="border rounded p-3">
                                            <div class="text-muted small">Discount / Grade</div>
                                            <div class="fw-semibold" x-text="`${Number(product.default_discount || 0).toFixed(2)} ${product.default_discount_type || 'percent'} / ${product.grade || 'N/A'}`"></div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="border rounded p-3">
                                            <div class="text-muted small">Tracking</div>
                                            <div class="fw-semibold" x-text="[
                                                product.manage_stock ? 'Stock' : 'No stock',
                                                product.batch_tracking ? 'Batch' : null,
                                                product.expiry_tracking ? 'Expiry' : null,
                                                product.allow_overselling ? `Oversell ${product.overselling_qty || 0}` : null
                                            ].filter(Boolean).join(' • ')"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <div class="text-muted small mb-1">Description</div>
                                    <p class="mb-0" x-text="product.description || 'No description provided.'"></p>
                                </div>
                                <div class="mt-3" x-show="product.application_instructions">
                                    <div class="text-muted small mb-1">Application Instructions</div>
                                    <p class="mb-0" x-text="product.application_instructions"></p>
                                </div>
                                <div class="mt-3" x-show="product.attributes && product.attributes.length">
                                    <div class="text-muted small mb-2">Attributes</div>
                                    <div class="d-flex flex-wrap gap-2">
                                        <template x-for="attr in product.attributes" :key="attr.id">
                                            <span class="badge text-bg-light border text-dark" x-text="`${attr.attribute}: ${attr.value}`"></span>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
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
</div>
@endsection

@push('scripts')
@endpush
