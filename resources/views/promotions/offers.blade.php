@extends('layouts.app')
@section('title', '⭐ Offers & Deals')
@section('page', 'promotions.offers')

@section('content')
<div class="user-management" x-data="offersModule()" x-init="init()">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 mb-lg-5 mb-xl-6">
        <div>
            <h1 class="h3 mb-0"><i class="bi bi-star-fill text-primary me-2"></i>Offers &amp; Deals</h1>
            <p class="text-muted mb-0">Create BOGO deals and order-level discount offers</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary" @click="openModal()">
                <i class="bi bi-plus-lg me-2"></i>New Offer
            </button>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row g-4 g-lg-5 g-xl-6 mb-5 mb-lg-5 mb-xl-6">
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bi bi-star-fill"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Total Offers</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.total"></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-success bg-opacity-10 text-success me-3">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Active</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.active"></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-info bg-opacity-10 text-info me-3">
                            <i class="bi bi-arrow-repeat"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">BOGO Offers</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.bogo"></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-warning bg-opacity-10 text-warning me-3">
                            <i class="bi bi-tag-fill"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Order Discounts</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.order_discount"></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="h5 card-title mb-0">Offers Directory</h2>
                </div>
                <div class="col-auto">
                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                        <div class="position-relative">
                            <input type="search" class="form-control form-control-sm" placeholder="Search offers..." x-model="search" @input.debounce.400ms="fetchOffers()" style="width: 200px;">
                            <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted"></i>
                        </div>
                        <select x-select class="form-select form-select-sm" x-model="filterType" @change="fetchOffers()" style="width: 150px;">
                            <option value="">All Types</option>
                            <option value="order_discount">Order Discount</option>
                            <option value="bogo">BOGO</option>
                        </select>
                        <select x-select class="form-select form-select-sm" x-model="filterStatus" @change="fetchOffers()" style="width: 150px;">
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
            <div class="bulk-actions-bar p-3 bg-primary bg-opacity-10 border-bottom border-primary border-opacity-25" x-show="selected.length > 0" x-cloak>
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle-fill text-primary me-2"></i>
                        <span class="fw-medium text-primary">
                            <span x-text="selected.length"></span> selected
                        </span>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-success" @click="bulkAction('activate')"><i class="bi bi-check-circle me-1"></i>Activate</button>
                        <button class="btn btn-sm btn-warning" @click="bulkAction('deactivate')"><i class="bi bi-pause-circle me-1"></i>Deactivate</button>
                        <button class="btn btn-sm btn-danger" @click="bulkAction('delete')"><i class="bi bi-trash me-1"></i>Delete</button>
                        <button class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center px-2" @click="selected = []" title="Clear selection">
                            <i class="bi bi-x-lg" style="margin-left: 7px"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class=" text-uppercase small">
                        <tr>
                            <th style="width:40px"><input type="checkbox" class="user-select-checkbox" @change="$event.isTrusted && toggleAll($event)" :checked="allSelected"></th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Discount</th>
                            <th>Min Spend</th>
                            <th>Product Scope</th>
                            <th>Validity Period</th>
                            <th>Priority</th>
                            <th>Usage</th>
                            <th>Created</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="loading">
                            <tr><td colspan="11" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>
                        </template>
                        <template x-if="!loading && offers.length === 0">
                            <tr><td colspan="11" class="text-center py-5 text-muted"><i class="bi bi-star fs-1 d-block mb-2"></i>No offers found</td></tr>
                        </template>
                        <template x-for="o in offers" :key="o.id">
                            <tr :class="{ 'selected': selected.includes(o.id) }">
                                <td><input type="checkbox" class="user-select-checkbox" :value="o.id" x-model="selected"></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="p-2 rounded-circle me-3 d-flex align-items-center justify-content-center" 
                                             :class="o.type === 'bogo' ? 'bg-info bg-opacity-10 text-info' : 'bg-primary bg-opacity-10 text-primary'" 
                                             style="width: 38px; height: 38px;">
                                            <i class="fs-5" :class="o.type === 'bogo' ? 'bi bi-tags-fill' : 'bi bi-percent'"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-primary text-decoration-underline cursor-pointer" style="cursor: pointer;" @click="viewDetails(o)" x-text="o.name"></div>
                                            <div class="text-muted small" style="font-size: 10px;" x-text="'ID: #' + o.id"></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge rounded-pill px-3 py-2 fw-medium border"
                                          :class="o.type === 'bogo' ? 'bg-info-subtle text-info-emphasis border-info-subtle' : (o.type === 'free_product' ? 'bg-success-subtle text-success-emphasis border-success-subtle' : (o.type === 'category_discount' ? 'bg-warning-subtle text-warning-emphasis border-warning-subtle' : 'bg-primary-subtle text-primary-emphasis border-primary-subtle'))">
                                        <i class="bi me-1" :class="o.type === 'bogo' ? 'bi-gift-fill' : (o.type === 'free_product' ? 'bi-box2-heart-fill' : (o.type === 'category_discount' ? 'bi-tags-fill' : 'bi-tag-fill'))"></i>
                                        <span x-text="o.type === 'bogo' ? 'BOGO' : (o.type === 'free_product' ? 'Free Product' : (o.type === 'category_discount' ? 'Category Discount' : 'Order Discount'))"></span>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-body-emphasis">
                                        <template x-if="o.type === 'bogo'">
                                            <span>Buy <span class="text-info" x-text="o.buy_qty"></span> Get <span class="text-info" x-text="o.get_qty"></span> Free</span>
                                        </template>
                                        <template x-if="o.type === 'free_product'">
                                            <span>Buy <span class="text-success" x-text="o.buy_qty"></span> Get <span class="text-success" x-text="o.get_qty"></span> Free Item</span>
                                        </template>
                                        <template x-if="o.type === 'order_discount' || o.type === 'category_discount'">
                                            <span x-text="o.discount_type === 'percentage' ? parseFloat(o.value).toFixed(2) + '%' : '₹ ' + parseFloat(o.value).toFixed(2)"></span>
                                        </template>
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-semibold text-secondary" x-text="o.min_spend > 0 ? '₹ ' + parseFloat(o.min_spend).toFixed(2) : 'No Min Spend'"></span>
                                </td>
                                <td>
                                    <template x-if="o.applicable_products && (typeof o.applicable_products === 'string' ? JSON.parse(o.applicable_products).length > 0 : o.applicable_products.length > 0)">
                                        <span class="badge bg-body-tertiary text-body-emphasis border px-2 py-1 d-inline-flex align-items-center gap-1">
                                            <i class="bi bi-box-seam text-secondary"></i>
                                            <span x-text="(typeof o.applicable_products === 'string' ? JSON.parse(o.applicable_products).length : o.applicable_products.length) + ' Specific Product(s)'"></span>
                                        </span>
                                    </template>
                                    <template x-if="!o.applicable_products || (typeof o.applicable_products === 'string' ? JSON.parse(o.applicable_products).length === 0 : o.applicable_products.length === 0)">
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1 d-inline-flex align-items-center gap-1">
                                            <i class="bi bi-globe2 text-secondary"></i>
                                            <span>Global (Any Product)</span>
                                        </span>
                                    </template>
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        <template x-if="!o.starts_at && !o.ends_at">
                                            <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle px-2 py-1 align-self-start">
                                                <i class="bi bi-infinity me-1"></i>Always Active
                                            </span>
                                        </template>
                                        <template x-if="o.starts_at || o.ends_at">
                                            <div class="small" style="font-size: 11px;">
                                                <div class="text-nowrap text-muted"><span class="fw-semibold text-body-emphasis">From:</span> <span x-text="o.starts_at ? formatDateTime(o.starts_at) : '∞'"></span></div>
                                                <div class="text-nowrap text-muted"><span class="fw-semibold text-body-emphasis">To:</span> <span x-text="o.ends_at ? formatDateTime(o.ends_at) : '∞'"></span></div>
                                            </div>
                                        </template>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-body-tertiary text-secondary border rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 26px; height: 26px;" x-text="o.priority || 0"></span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <div class="bg-body-tertiary text-primary border rounded px-2 py-1 fw-bold small">
                                            <i class="bi bi-receipt me-1"></i><span x-text="o.used_count || 0"></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1 text-muted small">
                                        <span><i class="bi bi-person me-1"></i><span x-text="o.creator ? o.creator.name : 'System'"></span></span>
                                        <span style="font-size: 11px;"><i class="bi bi-clock me-1"></i><span x-text="formatDateTime(o.created_at)"></span></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge rounded-pill px-3 py-2 fw-medium border cursor-pointer"
                                          :class="o.is_active ? 'bg-success-subtle text-success-emphasis border-success-subtle' : 'bg-secondary-subtle text-secondary-emphasis border-secondary-subtle'"
                                          @click="toggleStatus(o)">
                                        <span class="d-inline-block rounded-circle me-1" 
                                              :class="o.is_active ? 'bg-success' : 'bg-secondary'" 
                                              style="width: 6px; height: 6px; vertical-align: middle;"></span>
                                        <span x-text="o.is_active ? 'Active' : 'Inactive'"></span>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="#" @click.prevent="openModal(o)">
                                                    <i class="bi bi-pencil me-2"></i>Edit
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item text-danger" href="#" @click.prevent="deleteOffer(o)">
                                                    <i class="bi bi-trash me-2"></i>Delete
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
            <div class="d-flex justify-content-between align-items-center p-3">
                <div class="text-muted small">
                    Showing <span x-text="from"></span> to <span x-text="to"></span> of <span x-text="total"></span> results
                </div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item" :class="{ 'disabled': page <= 1 }">
                            <a class="page-link" href="#" @click.prevent="page--; fetchOffers()">Previous</a>
                        </li>
                        <li class="page-item" :class="{ 'disabled': page >= lastPage }">
                            <a class="page-link" href="#" @click.prevent="page++; fetchOffers()">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    {{-- Modal --}}
    {{-- Modal --}}
    <div class="modal fade" id="offerModal">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden bg-body">
                
                {{-- GLOSSY STYLE HEADER WITH BOOTSTRAP --}}
                <div class="modal-header bg-body-tertiary border-bottom d-flex align-items-center justify-content-between p-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px;">
                            <i class="bi bi-magic fs-4 text-primary"></i>
                        </div>
                        <div>
                            <h4 class="mb-0 fw-bold text-body"><span x-text="form.id ? 'Edit Offer Details' : 'Create New Offer'"></span></h4>
                            <p class="mb-0 small text-muted">Configure promotion rules, rewards, and product scopes</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4 p-md-5 pt-4">
                    <template x-if="formError">
                        <div class="alert alert-danger small py-3 mb-4 fw-bold shadow-sm rounded-4 border-0 d-flex align-items-center gap-2" style="background-color: rgba(var(--bs-danger-rgb), 0.1); color: var(--bs-danger);">
                            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                            <span x-text="formError"></span>
                        </div>
                    </template>
                    
                    <div class="row g-4">
                        <div class="col-12">
                            
                            {{-- Card 1: Basic Information --}}
                            <div class="card mb-4 border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center gap-2 pb-3 mb-4 border-bottom border-secondary border-opacity-25">
                                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                            <i class="bi bi-tag-fill fs-6"></i>
                                        </div>
                                        <h6 class="mb-0 fw-bolder text-uppercase text-body" style="font-size: 11px; letter-spacing: 1.5px;">Basic Information</h6>
                                    </div>
                                    <div class="row g-4">
                                        <div class="col-md-8">
                                            <label class="form-label mb-2 fw-bold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.1em;">Offer Name *</label>
                                            <input type="text" class="form-control form-control-lg fw-semibold rounded-3 bg-body border-secondary border-opacity-25 shadow-none px-3" x-model="form.name" placeholder="e.g. Summer Seeds 20%" style="font-size: 14px;">
                                            <small class="text-muted d-block mt-2" style="font-size: 11px;">A friendly title for the promotion.</small>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label mb-2 fw-bold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.1em;">Offer Type *</label>
                                            <select x-select class="form-select form-select-lg fw-semibold rounded-3 bg-body border-secondary border-opacity-25 shadow-none px-3" x-model="form.type" style="font-size: 14px;">
                                                <option value="order_discount">Order Discount</option>
                                                <option value="bogo">Buy X Get Y (BOGO)</option>
                                                <option value="free_product">Free Product</option>
                                                <option value="category_discount">Category Discount</option>
                                            </select>
                                            <div class="mt-3 p-3 bg-primary bg-opacity-10 rounded-3 border-0 text-primary" style="font-size: 11px;">
                                                <i class="bi bi-info-circle-fill me-1"></i>
                                                <span x-show="form.type === 'order_discount'">Applies to cart subtotal.</span>
                                                <span x-show="form.type === 'bogo'">Applies to same-product deals.</span>
                                                <span x-show="form.type === 'free_product'">Awards a specific gift item.</span>
                                                <span x-show="form.type === 'category_discount'">Applies to category-wide sales.</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Card 2: Discount Rules --}}
                            <div class="card mb-4 border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center gap-2 pb-3 mb-4 border-bottom border-secondary border-opacity-25">
                                        <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                            <i class="bi bi-percent fs-6"></i>
                                        </div>
                                        <h6 class="mb-0 fw-bolder text-uppercase text-body" style="font-size: 11px; letter-spacing: 1.5px;">Discount Rules</h6>
                                    </div>
                                    
                                    {{-- Order Discount Config --}}
                                    <div class="row g-4" x-show="form.type === 'order_discount' || form.type === 'category_discount'" style="display: none;">
                                        <div class="col-md-6" x-show="form.type === 'order_discount' || form.type === 'category_discount'">
                                            <label class="form-label mb-2 fw-bold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.1em;">Discount Type *</label>
                                            <select x-select class="form-select form-select-lg fw-semibold rounded-3 bg-body border-secondary border-opacity-25 shadow-none px-3" x-model="form.discount_type" style="font-size: 14px;">
                                                <option value="percentage">Percentage (%)</option>
                                                <option value="fixed">Flat Amount (₹ )</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6" x-show="form.type === 'order_discount' || form.type === 'category_discount'">
                                            <label class="form-label mb-2 fw-bold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.1em;">Value *</label>
                                            <div class="input-group input-group-lg bg-body border border-secondary border-opacity-25 rounded-3 overflow-hidden">
                                                <span class="input-group-text border-0 bg-transparent text-muted fw-bold" x-text="form.discount_type === 'percentage' ? '%' : '₹ '"></span>
                                                <input type="number" class="form-control fw-semibold border-0 bg-transparent shadow-none px-2" x-model="form.value" min="0" step="0.01" style="font-size: 14px;">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label mb-2 fw-bold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.1em;">Min Spend</label>
                                            <div class="input-group input-group-lg bg-body border border-secondary border-opacity-25 rounded-3 overflow-hidden">
                                                <span class="input-group-text border-0 bg-transparent text-muted fw-bold">Rs</span>
                                                <input type="number" class="form-control fw-semibold border-0 bg-transparent shadow-none px-2" x-model="form.min_spend" min="0" step="0.01" placeholder="0" style="font-size: 14px;">
                                            </div>
                                            <small class="text-muted d-block mt-2" style="font-size: 11px;">Minimum purchase requirement to unlock offer.</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label mb-2 fw-bold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.1em;">Max Discount</label>
                                            <div class="input-group input-group-lg bg-body border border-secondary border-opacity-25 rounded-3 overflow-hidden">
                                                <span class="input-group-text border-0 bg-transparent text-muted fw-bold">Rs</span>
                                                <input type="number" class="form-control fw-semibold border-0 bg-transparent shadow-none px-2" x-model="form.max_discount" min="0" step="0.01" placeholder="Unlimited" style="font-size: 14px;">
                                            </div>
                                            <small class="text-muted d-block mt-2" style="font-size: 11px;">Maximum cap. Leave empty/0 for unlimited.</small>
                                        </div>
                                    </div>

                                    {{-- BOGO Config --}}
                                    <div class="row g-4" x-show="form.type === 'bogo' || form.type === 'free_product'" style="display: none;">
                                        <div class="col-md-6">
                                            <label class="form-label mb-2 fw-bold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.1em;">Buy Qty *</label>
                                            <input type="number" class="form-control form-control-lg fw-semibold rounded-3 bg-body border-secondary border-opacity-25 shadow-none px-3" x-model="form.buy_qty" min="1" style="font-size: 14px;">
                                            <small class="text-muted d-block mt-2" style="font-size: 11px;">Quantity customer must buy (e.g., 2 for Buy 2 Get 1).</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label mb-2 fw-bold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.1em;">Get Qty Free *</label>
                                            <input type="number" class="form-control form-control-lg fw-semibold rounded-3 bg-body border-secondary border-opacity-25 shadow-none px-3" x-model="form.get_qty" min="1" style="font-size: 14px;">
                                            <small class="text-muted d-block mt-2" style="font-size: 11px;">Quantity rewarded for free.</small>
                                        </div>
                                    </div>
                                    <div class="row g-4 mt-1 border-top border-secondary border-opacity-25 pt-3">
                                        <div class="col-md-6">
                                            <label class="form-label mb-2 fw-bold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.1em;">Cashback Percentage</label>
                                            <div class="input-group input-group-lg bg-body border border-secondary border-opacity-25 rounded-3 overflow-hidden">
                                                <input type="number" class="form-control fw-semibold border-0 bg-transparent shadow-none px-3" x-model="form.cashback_percent" min="0" max="100" step="0.01" placeholder="e.g. 5" style="font-size: 14px;">
                                                <span class="input-group-text border-0 bg-transparent text-muted fw-bold">%</span>
                                            </div>
                                            <small class="text-muted d-block mt-2" style="font-size: 11px;">Percent of net amount to credit to wallet on delivery.</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label mb-2 fw-bold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.1em;">Cashback Fixed Amount</label>
                                            <div class="input-group input-group-lg bg-body border border-secondary border-opacity-25 rounded-3 overflow-hidden">
                                                <span class="input-group-text border-0 bg-transparent text-muted fw-bold">Rs</span>
                                                <input type="number" class="form-control fw-semibold border-0 bg-transparent shadow-none px-2" x-model="form.cashback_fixed" min="0" step="0.01" placeholder="e.g. 100" style="font-size: 14px;">
                                            </div>
                                            <small class="text-muted d-block mt-2" style="font-size: 11px;">Fixed amount to credit to wallet on delivery.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Card 3: Targeting & Scope --}}
                            <div class="card mb-4 border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary position-relative" style="z-index: 20;">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center gap-2 pb-3 mb-4 border-bottom border-secondary border-opacity-25">
                                        <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                            <i class="bi bi-box-seam fs-6"></i>
                                        </div>
                                        <h6 class="mb-0 fw-bolder text-uppercase text-body" style="font-size: 11px; letter-spacing: 1.5px;">Targeting & Scope</h6>
                                    </div>
                                    <div class="row g-4">
                                        <div class="col-12" x-show="form.type !== 'order_discount'" style="display: none;">
                                            <label class="form-label mb-2 fw-bold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.1em;">Applicable Category IDs (Comma Separated)</label>
                                            <input type="text" class="form-control form-control-lg fw-semibold rounded-3 bg-body border-secondary border-opacity-25 shadow-none px-3" x-model="form.applicable_categories" placeholder="e.g. 1,2,3" style="font-size: 14px;">
                                            <small class="text-muted d-block mt-2" style="font-size: 11px;">Enter Category IDs that trigger the offer.</small>
                                        </div>
                                        <div class="col-12" @click.away="showProductsDropdown = false" x-show="form.type !== 'order_discount'">
                                            <label class="form-label mb-2 fw-bold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.1em;" x-text="form.type === 'free_product' ? 'Required Products (Trigger)' : 'Applicable Products'"></label>
                                            
                                            <div class="position-relative">
                                                <div class="form-control form-control-lg d-flex flex-wrap align-items-center gap-2 bg-body border border-secondary border-opacity-25 shadow-none cursor-pointer rounded-3 px-3 py-2" style="min-height: 48px; cursor: text;" @click="showProductsDropdown = true; $refs.productSearch.focus()">
                                                    
                                                    <!-- Selected Chips -->
                                                    <template x-for="pId in form.product_ids" :key="pId">
                                                        <div class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 d-flex align-items-center gap-2 rounded-pill px-3 py-2 shadow-sm transition-all">
                                                            <span x-text="(allProducts.find(p => p.id == pId) || {}).name || 'Unknown Product'" style="font-size: 12px; font-weight: 600;"></span>
                                                            <i class="bi bi-x-circle-fill cursor-pointer opacity-75 custom-hover-opacity" @click.stop="form.product_ids = form.product_ids.filter(id => id != pId)" style="font-size: 14px;"></i>
                                                        </div>
                                                    </template>

                                                    <!-- Any Product Chip -->
                                                    <template x-if="form.product_ids.length === 0">
                                                        <div class="badge bg-secondary bg-opacity-10 text-secondary d-flex align-items-center gap-2 rounded-pill px-3 py-2 border border-secondary border-opacity-25">
                                                            <i class="bi bi-globe2"></i>
                                                            <span style="font-size: 12px; font-weight: 600;">Any Product (Global)</span>
                                                        </div>
                                                    </template>

                                                    <div class="flex-grow-1" style="min-width: 150px;">
                                                        <input x-ref="productSearch" type="text" x-model="productSearch" @focus="showProductsDropdown = true" placeholder="Search to add..." class="border-0 w-100 outline-none bg-transparent fw-semibold text-body" style="font-size: 14px; outline: none !important; box-shadow: none;">
                                                    </div>
                                                </div>
                                                
                                                <!-- Dropdown List -->
                                                <div x-show="showProductsDropdown" x-transition.opacity.duration.200ms class="position-absolute w-100 bg-body border border-secondary border-opacity-25 rounded-4 shadow-sm mt-2 overflow-auto" style="max-height: 350px; z-index: 1050; top: 100%; left: 0; display: none;">
                                                    <div class="px-4 py-3 cursor-pointer custom-hover-bg d-flex align-items-center transition-all" @click.stop="form.product_ids = []">
                                                        <div class="form-check m-0 d-flex align-items-center w-100">
                                                            <input type="checkbox" :checked="form.product_ids.length === 0" class="form-check-input border-secondary border-opacity-50 bg-body-tertiary me-3" style="cursor: pointer; transform: scale(1.2);">
                                                            <span class="text-muted fw-bold" style="font-size: 13px;">Any Product (Global)</span>
                                                        </div>
                                                    </div>
                                                    <hr class="dropdown-divider my-0 border-secondary border-opacity-25">
                                                    <template x-for="p in allProducts.filter(p => p.name.toLowerCase().includes(productSearch.toLowerCase()) || (p.sku && p.sku.toLowerCase().includes(productSearch.toLowerCase())))" :key="p.id">
                                                        <div class="px-4 py-3 cursor-pointer custom-hover-bg d-flex align-items-center transition-all" @click.stop="form.product_ids.includes(p.id) ? form.product_ids = form.product_ids.filter(id => id != p.id) : form.product_ids.push(p.id)">
                                                            <div class="form-check m-0 d-flex align-items-center w-100">
                                                                <input type="checkbox" :checked="form.product_ids.includes(p.id)" class="form-check-input border-secondary border-opacity-50 bg-body-tertiary me-3" style="cursor: pointer; transform: scale(1.2);">
                                                                <div>
                                                                    <div style="font-size: 13px; font-weight: 600;" class="text-body" x-text="p.name"></div>
                                                                    <small class="text-muted" style="font-size: 11px;" x-text="'SKU: ' + p.sku"></small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                            <small class="text-muted d-block mt-2" style="font-size: 11px;">Select specific products, or leave empty for a global offer.</small>
                                            
                                            <div class="col-12 mt-4" x-show="form.type === 'free_product'" style="display: none;">
                                                <label class="form-label mb-2 fw-bold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.1em;">Gift Product (Free Item) *</label>
                                                <select x-select class="form-select form-select-lg fw-semibold rounded-3 bg-body border-secondary border-opacity-25 shadow-none px-3" x-model="form.product_id" style="font-size: 14px;">
                                                    <option value="">Select Free Product...</option>
                                                    <template x-for="p in allProducts" :key="p.id">
                                                        <option :value="p.id" x-text="p.name + ' (' + p.sku + ')'"></option>
                                                    </template>
                                                </select>
                                                <small class="text-muted d-block mt-2" style="font-size: 11px;">The specific product given away for free.</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Card 4: Validity & Settings --}}
                            <div class="card mb-4 border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary position-relative" style="z-index: 10;">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center gap-2 pb-3 mb-4 border-bottom border-secondary border-opacity-25">
                                        <div class="bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                            <i class="bi bi-calendar-event fs-6"></i>
                                        </div>
                                        <h6 class="mb-0 fw-bolder text-uppercase text-body" style="font-size: 11px; letter-spacing: 1.5px;">Validity & Priority</h6>
                                    </div>
                                    <div class="row g-4">
                                        <div class="col-md-4">
                                            <label class="form-label mb-2 fw-bold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.1em;">Starts At</label>
                                            <input type="datetime-local" class="form-control form-control-lg fw-semibold rounded-3 bg-body border-secondary border-opacity-25 shadow-none px-3" x-model="form.starts_at" style="font-size: 14px;">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label mb-2 fw-bold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.1em;">Ends At</label>
                                            <input type="datetime-local" class="form-control form-control-lg fw-semibold rounded-3 bg-body border-secondary border-opacity-25 shadow-none px-3" x-model="form.ends_at" style="font-size: 14px;">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label mb-2 fw-bold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.1em;">Priority</label>
                                            <input type="number" class="form-control form-control-lg fw-semibold rounded-3 bg-body border-secondary border-opacity-25 shadow-none px-3" x-model="form.priority" min="0" placeholder="0" style="font-size: 14px;">
                                        </div>
                                        <div class="col-12 mt-4 pt-3 border-top border-secondary border-opacity-25">
                                            <div class="d-flex align-items-center justify-content-between p-3 rounded-3 border border-secondary border-opacity-10 bg-body">
                                                <div>
                                                    <h6 class="mb-1 fw-bold text-body" style="font-size: 14px;">Offer Status</h6>
                                                    <p class="mb-0 text-muted" style="font-size: 12px;">Toggle to activate or deactivate this offer.</p>
                                                </div>
                                                <div class="form-check form-switch cursor-pointer m-0">
                                                    <input class="form-check-input border-secondary border-opacity-50" type="checkbox" id="offerActive" x-model="form.is_active" style="width: 2.5em; height: 1.25em; cursor: pointer;">
                                                    <label class="form-check-label fw-bold ms-2" for="offerActive" style="cursor: pointer;" :class="form.is_active ? 'text-success' : 'text-muted'" x-text="form.is_active ? 'Active' : 'Inactive'"></label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="modal-footer bg-body-tertiary border-top p-4">
                    <div class="d-flex w-100 justify-content-between align-items-center">
                        <button type="button" data-bs-dismiss="modal" class="btn btn-outline-secondary rounded-pill px-4 fw-bold text-uppercase" style="font-size: 12px; letter-spacing: 1px;">Cancel</button>
                        <button class="btn btn-primary rounded-pill px-5 py-2 fw-bold shadow-sm d-flex align-items-center" @click="saveOffer()" :disabled="saving">
                            <span x-show="saving" class="spinner-border spinner-border-sm me-2" style="width: 1rem; height: 1rem; display: none;"></span>
                            <span x-text="form.id ? 'Save Changes' : 'Create Offer'"></span>
                            <i class="bi bi-arrow-right ms-2" x-show="!saving"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.custom-hover-bg:hover { background-color: rgba(var(--bs-primary-rgb), 0.1); }
.custom-hover-opacity { transition: all 0.2s; }
.custom-hover-opacity:hover { opacity: 1 !important; color: var(--bs-danger) !important; transform: scale(1.1); }
.cursor-pointer { cursor: pointer; }
</style>

@push('scripts')
<script>
const INITIAL_PRODUCTS = @json($products ?? []);

function offersModule() {
    return {
        allProducts: INITIAL_PRODUCTS || [],
        showProductsDropdown: false, productSearch: '',
        offers: [], loading: false, saving: false,
        search: '', filterType: '', filterStatus: '', page: 1, lastPage: 1,
        total: 0, from: 0, to: 0,
        selected: [], stats: { total: 0, active: 0, bogo: 0, order_discount: 0 },
        form: { id: null, name: '', type: 'order_discount', discount_type: 'percentage', value: '', min_spend: '', max_discount: '', cashback_percent: '', cashback_fixed: '', product_ids: [], product_id: '', applicable_categories: [], buy_qty: 1, get_qty: 1, starts_at: '', ends_at: '', priority: 0, is_active: true },
        formError: null,

        generateDescription(o) {
            let desc = '';
            if (o.type === 'order_discount') desc += 'Order Discount that grants a ';
            else if (o.type === 'category_discount') desc += 'Category Discount that grants a ';
            else if (o.type === 'bogo') return `Buy ${o.buy_qty} Get ${o.get_qty} Free`;
            else if (o.type === 'free_product') return `Free Product Offer (Buy ${o.buy_qty} Get ${o.get_qty})`;

            if (o.discount_type === 'percentage') desc += parseFloat(o.value) + '% Discount';
            else desc += '₹ ' + parseFloat(o.value) + ' Discount';

            if (o.cashback_percent > 0 && o.cashback_fixed > 0) {
                desc += ` AND additionally grants ${parseFloat(o.cashback_percent)}% + ₹ ${parseFloat(o.cashback_fixed)} Cashback simultaneously!`;
            } else if (o.cashback_percent > 0) {
                desc += ` AND additionally grants ${parseFloat(o.cashback_percent)}% Cashback simultaneously!`;
            } else if (o.cashback_fixed > 0) {
                desc += ` AND additionally grants a ₹ ${parseFloat(o.cashback_fixed)} flat Cashback simultaneously!`;
            }
            return desc;
        },

        viewDetails(o) {
            const desc = this.generateDescription(o);
            // Check if Swal is available (often loaded globally), otherwise fallback to standard alert
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Promotion Details',
                    text: desc,
                    icon: 'info',
                    confirmButtonText: 'Got it!'
                });
            } else {
                alert("Promotion Details:\n\n" + desc);
            }
        },

        formatDateTime(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr.replace(' ', 'T'));
            if (isNaN(d.getTime())) return dateStr;
            return d.toLocaleString('en-IN', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        },

        get allSelected() { return this.offers.length > 0 && this.selected.length === this.offers.length; },

        async init() { await this.fetchOffers(); },

        async fetchOffers() {
            this.loading = true;
            try {
                const params = new URLSearchParams({ search: this.search, type: this.filterType, status: this.filterStatus, per_page: 15, page: this.page });
                const res = await fetch(`/api/promotions/offers?${params}`, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                const json = await res.json();
                const d = json.data;
                this.offers = d.data || [];
                this.total = d.total || 0; this.from = d.from || 0; this.to = d.to || 0; this.lastPage = d.last_page || 1;
                this.stats.total = this.total;
                this.stats.active = this.offers.filter(o => o.is_active).length;
                this.stats.bogo = this.offers.filter(o => o.type === 'bogo').length;
                this.stats.order_discount = this.offers.filter(o => o.type === 'order_discount').length;
            } catch (e) { console.error(e); } finally { this.loading = false; }
        },

        openModal(o = null) {
            this.formError = null;
            if (o) {
                this.form = { id: o.id, name: o.name, type: o.type, discount_type: o.discount_type, value: o.value, min_spend: o.min_spend || '', max_discount: o.max_discount || '', cashback_percent: o.cashback_percent || '', cashback_fixed: o.cashback_fixed || '', product_ids: typeof o.applicable_products === 'string' ? JSON.parse(o.applicable_products) : (o.applicable_products || []), product_id: o.type === 'free_product' ? o.product_id : '', applicable_categories: typeof o.applicable_categories === 'string' ? JSON.parse(o.applicable_categories) : (o.applicable_categories || []), buy_qty: o.buy_qty || 1, get_qty: o.get_qty || 1, starts_at: o.starts_at ? o.starts_at.substring(0,16) : '', ends_at: o.ends_at ? o.ends_at.substring(0,16) : '', priority: o.priority || 0, is_active: o.is_active };
            } else {
                this.form = { id: null, name: '', type: 'order_discount', discount_type: 'percentage', value: '', min_spend: '', max_discount: '', cashback_percent: '', cashback_fixed: '', product_ids: [], product_id: '', applicable_categories: [], buy_qty: 1, get_qty: 1, starts_at: '', ends_at: '', priority: 0, is_active: true };
            }
            new bootstrap.Modal(document.getElementById('offerModal')).show();
        },

        async saveOffer() {
            this.saving = true; this.formError = null;
            try {
                let payload = JSON.parse(JSON.stringify(this.form));
                if (typeof payload.applicable_categories === "string") {
                    payload.applicable_categories = payload.applicable_categories.split(",").map(i => parseInt(i.trim())).filter(i => !isNaN(i));
                }
                const url = this.form.id ? `/api/promotions/offers/${this.form.id}` : '/api/promotions/offers';
                const method = this.form.id ? 'PATCH' : 'POST';
                const res = await fetch(url, { method, headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' }, body: JSON.stringify(payload) });
                const json = await res.json();
                if (!res.ok) { this.formError = Object.values(json.errors || {}).flat().join(' ') || json.message; return; }
                bootstrap.Modal.getInstance(document.getElementById('offerModal'))?.hide();
                this.fetchOffers();
                window.dispatchEvent(new CustomEvent('notify', { detail: { type: 'success', message: json.message } }));
            } catch (e) { this.formError = 'An error occurred.'; } finally { this.saving = false; }
        },

        async toggleStatus(o) {
            const res = await fetch(`/api/promotions/offers/${o.id}/toggle`, { method: 'PATCH', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' } });
            if (res.ok) this.fetchOffers();
        },

        async deleteOffer(o) {
            if (!confirm(`Delete offer "${o.name}"?`)) return;
            await fetch(`/api/promotions/offers/${o.id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' } });
            this.fetchOffers();
        },

        toggleAll(e) { this.selected = e.target.checked ? this.offers.map(o => o.id) : []; },

        async bulkAction(action) {
            if (!this.selected.length) return;
            if (action === 'delete' && !confirm(`Delete ${this.selected.length} offer(s)?`)) return;
            await fetch('/api/promotions/offers/bulk-action', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' }, body: JSON.stringify({ action, ids: this.selected }) });
            this.selected = []; this.fetchOffers();
        },
    };
}
</script>
@endpush
@endsection
