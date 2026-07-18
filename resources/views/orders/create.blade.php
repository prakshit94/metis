@extends('layouts.app')
@section('title', 'Create Order')
@section('page', 'orders.create')

@section('content')
    <script>
        window.__INITIAL_ORDER_CUSTOMER__ = @json($initialCustomer ? $initialCustomer->toArray() : null);
        window.__INITIAL_ORDER_TO_EDIT__ = @json($initialOrder ? $initialOrder->toArray() : null);
    </script>

    <div x-data="createOrderApp(window.__INITIAL_ORDER_CUSTOMER__, window.__INITIAL_ORDER_TO_EDIT__)">
    
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4 mb-lg-5 mb-xl-6">
        <div>
            <h1 class="h3 mb-1"><i class="bi bi-cart-check me-2"></i> Create New Order</h1>
            <p class="text-muted mb-0">
                <span x-show="!editingOrderId" x-cloak>Select customer, add products, and checkout.</span>
                <span x-show="editingOrderId" x-cloak>Edit an existing order.</span>
                <span class="badge text-bg-warning ms-2" x-show="editingOrderId" x-cloak>Edit Mode</span>
            </p>
        </div>
        <div class="d-flex gap-2">
            <template x-if="editingOrderId">
                <a href="{{ route('orders') }}" class="btn btn-outline-danger shadow-sm">
                    <i class="bi bi-x-circle me-1"></i> Cancel Edit Mode
                </a>
            </template>
            <a href="{{ route('orders') }}" class="btn btn-outline-secondary shadow-sm">
                <i class="bi bi-arrow-left me-2"></i> Back to Orders
            </a>
        </div>
    </div>

    {{-- Alert --}}
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-4">
        <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div @customer-updated.window="loadAddresses()" class="row g-4">
        <div class="col-xl-8">
            <div id="customer-workspace" class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-transparent border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 fw-bold"><i class="bi bi-person-badge me-2 text-primary"></i>Customer Workspace</h5>
                        <p class="mb-0 small text-muted">Profile, addresses, and order build steps in one place.</p>
                    </div>
                    <div class="d-flex align-items-center gap-2" x-show="customerDetails" x-cloak>
                        <button type="button" class="btn btn-sm btn-outline-secondary" @click="$dispatch('open-add-customer-modal', {customer: customerDetails})">
                            <i class="bi bi-pencil-square me-1"></i>Edit Profile
                        </button>
                    </div>
                </div>
                <div class="card-body p-4 p-lg-4">
                    <div class="card border shadow-sm mb-4" x-show="customerDetails" x-cloak>
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle bg-primary text-white fw-bold d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px;" x-text="customerDetails.firstname ? customerDetails.firstname.charAt(0) : '?'"></div>
                                    <div>
                                        <h5 class="mb-1 fw-bold" x-text="customerDisplayName"></h5>
                                        <div class="small text-muted d-flex align-items-center gap-2">
                                            <span x-text="customerDetails.party_code"></span>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success" x-text="customerDetails.status || 'Active'"></span>
                                            <span class="badge bg-info bg-opacity-10 text-info border border-info" x-show="customerDetails.kyc_completed">KYC Verified</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex flex-column align-items-end gap-2">
                                    <div x-show="customerDetails.created_at" class="badge bg-primary bg-opacity-10 text-primary border border-primary fw-medium" style="font-size: 10px; letter-spacing: 0.5px;">
                                        <i class="bi bi-clock-history me-1"></i> Since: <span x-text="new Date(customerDetails.created_at).toLocaleDateString()"></span> 
                                        (<span x-text="customerDetails.created_at ? Math.max(0, Math.floor((new Date() - new Date(customerDetails.created_at)) / 86400000)) : 0"></span> days)
                                    </div>
                                    <div x-show="customerDetails.updated_at" class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary fw-medium" style="font-size: 10px; letter-spacing: 0.5px;">
                                        <i class="bi bi-activity me-1"></i> Active: <span x-text="customerDetails.updated_at ? Math.max(0, Math.floor((new Date() - new Date(customerDetails.updated_at)) / 86400000)) : 0"></span> days ago
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row g-3 small">
                                <!-- Contact Profile -->
                                <div class="col-md-6 col-lg-3">
                                    <div class="fw-bold text-muted mb-2 pb-1 border-bottom" style="font-size: 11px; text-transform: uppercase;">Contact</div>
                                    <div class="mb-1" x-show="customerDetails.phone"><span class="text-muted me-1"><i class="bi bi-telephone"></i> Primary:</span><span class="fw-medium" x-text="customerDetails.phone"></span></div>
                                    <div class="mb-1" x-show="customerDetails.alternatemobile"><span class="text-muted me-1">Alt Mo:</span><span class="fw-medium" x-text="customerDetails.alternatemobile"></span></div>
                                    <div class="mb-1" x-show="customerDetails.phone_number_2"><span class="text-muted me-1">Landline:</span><span class="fw-medium" x-text="customerDetails.phone_number_2"></span></div>
                                    <div class="mb-1" x-show="customerDetails.relative_mobile"><span class="text-muted me-1">Relative Name:</span><span class="fw-medium" x-text="customerDetails.relative_mobile"></span></div>
                                    <div class="mb-1" x-show="customerDetails.relative_phone"><span class="text-muted me-1">Relative Mo:</span><span class="fw-medium" x-text="customerDetails.relative_phone"></span></div>
                                    <div class="mb-1" x-show="customerDetails.email"><span class="text-muted me-1"><i class="bi bi-envelope"></i> Email:</span><span class="fw-medium text-truncate d-inline-block align-bottom" style="max-width: 150px;" :title="customerDetails.email" x-text="customerDetails.email"></span></div>
                                    <div class="mb-1" x-show="!customerDetails.phone && !customerDetails.alternatemobile && !customerDetails.email && !customerDetails.phone_number_2 && !customerDetails.relative_mobile && !customerDetails.relative_phone"><span class="text-muted">No contact details</span></div>
                                </div>

                                <!-- Business Details -->
                                <div class="col-md-6 col-lg-3">
                                    <div class="fw-bold text-muted mb-2 pb-1 border-bottom" style="font-size: 11px; text-transform: uppercase;">Business & Identity</div>
                                    <div class="mb-1" x-show="customerDetails.category"><span class="text-muted me-1">Category:</span><span class="fw-medium text-capitalize" x-text="customerDetails.category"></span></div>
                                    <div class="mb-1" x-show="customerDetails.company_name"><span class="text-muted me-1">Company:</span><span class="fw-medium" x-text="customerDetails.company_name"></span></div>
                                    <div class="mb-1" x-show="customerDetails.gst_no"><span class="text-muted me-1">GST:</span><span class="fw-medium text-uppercase" x-text="customerDetails.gst_no"></span></div>
                                    <div class="mb-1" x-show="customerDetails.pan_no"><span class="text-muted me-1">PAN:</span><span class="fw-medium text-uppercase" x-text="customerDetails.pan_no"></span></div>
                                    <div class="mb-1" x-show="customerDetails.tax_no"><span class="text-muted me-1">Tax No:</span><span class="fw-medium text-uppercase" x-text="customerDetails.tax_no"></span></div>
                                    <div class="mb-1" x-show="customerDetails.aadhaar_last4"><span class="text-muted me-1">Aadhaar:</span><span class="fw-medium" x-text="'xxxx-xxxx-' + customerDetails.aadhaar_last4"></span></div>
                                    <div class="mb-1" x-show="customerDetails.kyc_verified_at"><span class="text-muted me-1">KYC Date:</span><span class="fw-medium" x-text="new Date(customerDetails.kyc_verified_at).toLocaleDateString()"></span></div>
                                    <div class="mb-1" x-show="!customerDetails.company_name && !customerDetails.gst_no && !customerDetails.pan_no && !customerDetails.category && !customerDetails.aadhaar_last4"><span class="text-muted">No business/identity details</span></div>
                                </div>

                                <!-- Agriculture Snapshot -->
                                <div class="col-md-6 col-lg-3">
                                    <div class="fw-bold text-muted mb-2 pb-1 border-bottom" style="font-size: 11px; text-transform: uppercase;">Agriculture & Tags</div>
                                    <div class="mb-1"><span class="text-muted me-1">Land:</span><span class="fw-medium"><span x-text="customerDetails.land_area || '0'"></span> <span x-text="customerDetails.land_unit || ''"></span></span></div>
                                    <div class="mb-1 d-flex gap-1 flex-wrap" x-show="customerDetails.crops && customerDetails.crops.length > 0">
                                        <span class="text-muted me-1">Crops:</span>
                                        <template x-for="crop in customerDetails.crops"><span class="badge bg-success bg-opacity-10 text-success" style="font-size:10px" x-text="crop"></span></template>
                                    </div>
                                    <div class="mb-1 d-flex gap-1 flex-wrap" x-show="customerDetails.irrigation_type && customerDetails.irrigation_type.length > 0">
                                        <span class="text-muted me-1">Irrig:</span>
                                        <template x-for="type in customerDetails.irrigation_type"><span class="badge bg-info bg-opacity-10 text-info" style="font-size:10px" x-text="type"></span></template>
                                    </div>
                                    <div class="mb-1 d-flex gap-1 flex-wrap" x-show="customerDetails.tags && customerDetails.tags.length > 0">
                                        <span class="text-muted me-1">Tags:</span>
                                        <template x-for="tag in customerDetails.tags"><span class="badge bg-secondary bg-opacity-10 text-secondary" style="font-size:10px" x-text="tag"></span></template>
                                    </div>
                                    <div class="mb-1 d-flex gap-1 flex-wrap" x-show="customerDetails.source && customerDetails.source.length > 0">
                                        <span class="text-muted me-1">Source:</span>
                                        <template x-for="src in customerDetails.source"><span class="badge bg-primary bg-opacity-10 text-primary" style="font-size:10px" x-text="src"></span></template>
                                    </div>
                                </div>

                                <!-- Financial Snapshot -->
                                <div class="col-md-6 col-lg-3">
                                    <div class="fw-bold text-muted mb-2 pb-1 border-bottom" style="font-size: 11px; text-transform: uppercase;">Financial & Stats</div>
                                    <div class="mb-1"><span class="text-muted me-1">Limit:</span><span class="fw-medium">Rs <span x-text="Number(customerDetails.credit_limit || 0).toFixed(2)"></span></span></div>
                                    <div class="mb-1"><span class="text-muted me-1">Balance:</span><span class="fw-medium" :class="Number(customerDetails.outstanding_balance) > 0 ? 'text-danger' : 'text-success'">Rs <span x-text="Number(customerDetails.outstanding_balance || 0).toFixed(2)"></span></span></div>
                                    <div class="mb-1"><span class="text-muted me-1">Cr. Days:</span><span class="fw-medium"><span x-text="customerDetails.credit_days || '0'"></span> Days</span></div>
                                    <div class="mb-1" x-show="customerDetails.credit_valid_till"><span class="text-muted me-1">Valid Till:</span><span class="fw-medium" x-text="new Date(customerDetails.credit_valid_till).toLocaleDateString()"></span></div>
                                    <div class="mb-1"><span class="text-muted me-1">Orders:</span><span class="fw-medium" x-text="customerDetails.orders_count || 0"></span></div>
                                    <div class="mb-1" x-show="customerDetails.first_purchase_at"><span class="text-muted me-1">First Order:</span><span class="fw-medium" x-text="new Date(customerDetails.first_purchase_at).toLocaleDateString()"></span></div>
                                    <div class="mb-1" x-show="customerDetails.last_purchase_at"><span class="text-muted me-1">Last Order:</span><span class="fw-medium" x-text="new Date(customerDetails.last_purchase_at).toLocaleDateString()"></span></div>
                                </div>

                                <!-- Notes & Status -->
                                <div class="col-12 mt-2 pt-2 border-top" x-show="customerDetails.internal_notes || customerDetails.is_blacklisted">
                                    <div class="d-flex flex-wrap gap-3 align-items-center">
                                        <div x-show="customerDetails.is_blacklisted"><span class="badge bg-danger"><i class="bi bi-slash-circle me-1"></i>Blacklisted</span></div>
                                        <div x-show="customerDetails.internal_notes">
                                            <span class="text-muted me-1 fw-bold" style="font-size: 11px; text-transform: uppercase;">Notes:</span>
                                            <span class="fw-medium text-danger" x-text="customerDetails.internal_notes"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- Addresses Section --}}
                    <div id="addresses-section" x-show="partyId" x-cloak class="mt-4 pt-4 border-top transition-all">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h6 class="fw-bold mb-0 text-body fs-5"><i class="bi bi-geo-alt-fill me-2 text-primary"></i>Shipping Addresses</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-4 shadow-sm hover-shadow transition-all" @click="$dispatch('open-address-modal', {customerId: partyId})">
                                <i class="bi bi-plus-lg me-2"></i>Add Address
                            </button>
                        </div>
                        
                        <div class="row g-4">
                            <template x-for="addr in addresses" :key="addr.id">
                                    <div class="col-md-6 col-lg-4">
                                        <div class="w-100 h-100 cursor-pointer" style="display:block;" @click="shippingAddressId = addr.id">
                                            <div class="card h-100 border shadow-sm transition-all" :class="shippingAddressId == addr.id ? 'border-primary bg-primary bg-opacity-10' : 'bg-body'">
                                                <div class="card-body p-3 position-relative">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <div>
                                                            <span class="badge bg-secondary me-1" x-text="addr.label || 'Address'"></span>
                                                            <span x-show="addr.is_default" class="badge bg-success"><i class="bi bi-star-fill me-1"></i>Default</span>
                                                        </div>
                                                        <button type="button" class="btn btn-sm btn-light border rounded-circle shadow-sm position-absolute d-flex align-items-center justify-content-center" style="top: 12px; right: 12px; width: 28px; height: 28px; z-index: 20;" @click.stop.prevent="$dispatch('open-address-modal', {customerId: partyId, address: addr})">
                                                            <i class="bi bi-pencil text-primary" style="font-size: 12px;"></i>
                                                        </button>
                                                    </div>
                                                    <p class="mb-1 small fw-bold" x-text="addr.address_line_1"></p>
                                                    <p class="mb-1 small text-muted" x-show="addr.address_line_2" x-text="addr.address_line_2"></p>
                                                    <p class="mb-1 small text-muted" x-show="addr.village" x-text="[addr.village?.village_name ? 'Vill: '+addr.village?.village_name : null, addr.village?.post_so_name ? 'PO: '+addr.village?.post_so_name : null, addr.village?.taluka_name ? 'Ta: '+addr.village?.taluka_name : null, addr.village?.district_name ? 'Dist: '+addr.village?.district_name : null].filter(Boolean).join(', ')"></p>
                                                    <p class="mb-0 small text-muted fw-medium">
                                                        <span x-show="addr.city" x-text="addr.city + ', '"></span>
                                                        <span x-show="addr.state" x-text="addr.state"></span>
                                                        <span x-show="addr.pincode" x-text="'- ' + addr.pincode"></span>
                                                    </p>
                                                    <div class="mt-3 pt-2 border-top">
                                                        <div class="small text-muted fw-semibold text-uppercase mb-1" style="font-size: 10px; letter-spacing: .5px;">Available services</div>
                                                        <div class="d-flex flex-wrap gap-1" x-show="availableServices(addr).length">
                                                            <template x-for="service in availableServices(addr)" :key="service.id">
                                                                <span class="badge text-bg-success" x-text="service.name"></span>
                                                            </template>
                                                        </div>
                                                        <span class="small text-muted" x-show="!availableServices(addr).length">No service available for this address</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                            </template>
                            <template x-if="addresses.length === 0">
                                <div class="col-12">
                                    <div class="alert alert-light border border-secondary border-opacity-25 rounded-4 d-flex align-items-center mb-0 p-4 shadow-sm bg-body-tertiary">
                                        <div class="bg-secondary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-4" style="width: 50px; height: 50px;">
                                            <i class="bi bi-info-circle text-muted fs-4"></i>
                                        </div>
                                        <div>
                                            <p class="mb-1 fw-bold fs-6 text-body">No addresses found.</p>
                                            <p class="mb-0 small text-muted">Please add a shipping address to continue with your order.</p>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Same as Shipping Toggle --}}
                        <div class="mt-4 form-check cursor-pointer d-flex align-items-center gap-2">
                            <input class="form-check-input mt-0" type="checkbox" id="sameAsShippingToggle" x-model="sameAsShipping" style="cursor: pointer;">
                            <label class="form-check-label small fw-bold text-muted text-uppercase mt-1" for="sameAsShippingToggle" style="cursor: pointer; font-size: 11px; letter-spacing: 1px;">Billing address same as Shipping address</label>
                        </div>

                        {{-- Billing Address Section --}}
                        <div x-show="!sameAsShipping" x-cloak class="mt-4 pt-4 border-top transition-all">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h6 class="fw-bold mb-0 text-body fs-5"><i class="bi bi-receipt me-2 text-primary"></i>Billing Addresses</h6>
                            </div>
                            
                            <div class="row g-4">
                                <template x-for="addr in addresses" :key="addr.id">
                                    <div class="col-md-6 col-lg-4">
                                        <div class="w-100 h-100 cursor-pointer" style="display:block;" @click="billingAddressId = addr.id">
                                            <div class="card h-100 border shadow-sm transition-all" :class="billingAddressId == addr.id ? 'border-primary bg-primary bg-opacity-10' : 'bg-body'">
                                                <div class="card-body p-3 position-relative">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <div>
                                                            <span class="badge bg-secondary me-1" x-text="addr.label || 'Address'"></span>
                                                            <span x-show="addr.is_default" class="badge bg-success"><i class="bi bi-star-fill me-1"></i>Default</span>
                                                        </div>
                                                        <button type="button" class="btn btn-sm btn-light border rounded-circle shadow-sm position-absolute d-flex align-items-center justify-content-center" style="top: 12px; right: 12px; width: 28px; height: 28px; z-index: 20;" @click.stop.prevent="$dispatch('open-address-modal', {customerId: partyId, address: addr})">
                                                            <i class="bi bi-pencil text-primary" style="font-size: 12px;"></i>
                                                        </button>
                                                    </div>
                                                    <p class="mb-1 small fw-bold" x-text="addr.address_line_1"></p>
                                                    <p class="mb-1 small text-muted" x-show="addr.address_line_2" x-text="addr.address_line_2"></p>
                                                    <p class="mb-1 small text-muted" x-show="addr.village" x-text="[addr.village?.village_name ? 'Vill: '+addr.village?.village_name : null, addr.village?.post_so_name ? 'PO: '+addr.village?.post_so_name : null, addr.village?.taluka_name ? 'Ta: '+addr.village?.taluka_name : null, addr.village?.district_name ? 'Dist: '+addr.village?.district_name : null].filter(Boolean).join(', ')"></p>
                                                    <p class="mb-0 small text-muted fw-medium">
                                                        <span x-show="addr.city" x-text="addr.city + ', '"></span>
                                                        <span x-show="addr.state" x-text="addr.state"></span>
                                                        <span x-show="addr.pincode" x-text="'- ' + addr.pincode"></span>
                                                    </p>
                                                    <div class="mt-3 pt-2 border-top">
                                                        <div class="small text-muted fw-semibold text-uppercase mb-1" style="font-size: 10px; letter-spacing: .5px;">Available services</div>
                                                        <div class="d-flex flex-wrap gap-1" x-show="availableServices(addr).length">
                                                            <template x-for="service in availableServices(addr)" :key="service.id">
                                                                <span class="badge text-bg-success" x-text="service.name"></span>
                                                            </template>
                                                        </div>
                                                        <span class="small text-muted" x-show="!availableServices(addr).length">No service available for this address</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="addresses.length === 0">
                                    <div class="col-12">
                                        <div class="alert alert-light border border-secondary border-opacity-25 rounded-4 d-flex align-items-center mb-0 p-4 shadow-sm bg-body-tertiary">
                                            <div class="bg-secondary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-4" style="width: 50px; height: 50px;">
                                                <i class="bi bi-info-circle text-muted fs-4"></i>
                                            </div>
                                            <div>
                                                <p class="mb-1 fw-bold fs-6 text-body">No addresses found.</p>
                                                <p class="mb-0 small text-muted">Please add a billing address to continue with your order.</p>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm border-0 mt-4">
                        <div class="card-body p-3 p-lg-4">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                                <div>
                                    <div class="small fw-bold text-muted text-uppercase mb-1" style="font-size: 11px; letter-spacing: 1px;">Warehouse</div>
                                    <h6 class="mb-0 fw-bold">Select fulfillment warehouse</h6>
                                </div>
                                <select class="form-select fw-bold" style="max-width: 260px;" x-model="warehouseId">
                                    <option value="">Select Warehouse</option>
                                    @foreach($warehouses as $w)
                                    <option value="{{ $w->id }}">{{ $w->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- Product Search Card --}}
            <div id="catalog-section" class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-transparent border-bottom py-3">
                    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
                        <span class="fw-bold fs-5"><i class="bi bi-search me-2 text-info"></i>Product Catalog</span>
                        <div class="d-flex flex-wrap gap-2 flex-grow-1 justify-content-md-end">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm" :class="viewMode === 'grid' ? 'btn-primary' : 'btn-outline-primary'" @click="viewMode = 'grid'" title="Grid View"><i class="bi bi-grid"></i></button>
                                <button type="button" class="btn btn-sm" :class="viewMode === 'table' ? 'btn-primary' : 'btn-outline-primary'" @click="viewMode = 'table'" title="Table View"><i class="bi bi-list-ul"></i></button>
                            </div>
                            <div class="input-group" style="max-width:240px">
                                <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control border-start-0" placeholder="Search SKU, name..." x-model="productQuery" @input.debounce.350ms="searchProducts(true)">
                            </div>
                            <select class="form-select" style="max-width:140px" x-model="stockFilter" @change="searchProducts(true)">
                                <option value="available">In Stock</option>
                                <option value="">All Stock</option>
                                <option value="out_of_stock">Out of Stock</option>
                            </select>
                            <select class="form-select" style="max-width:160px" x-model="categoryFilter" @change="searchProducts(true)">
                                <option value="">All Categories</option>
                                @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @foreach($cat->children as $child)
                                <option value="{{ $child->id }}">— {{ $child->name }}</option>
                                @endforeach
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    {{-- Loading --}}
                    <template x-if="searching">
                        <div class="text-center py-5"><div class="spinner-border text-primary"></div></div>
                    </template>
                    {{-- Empty --}}
                    <template x-if="!searching && products.length === 0">
                        <div class="text-center py-5 text-muted"><i class="bi bi-box-seam fs-1 d-block mb-2"></i>No products found</div>
                    </template>
                    {{-- Product Grid / Table --}}
                    <div class="p-3" x-show="!searching && products.length > 0">
                        {{-- Grid View --}}
                        <div class="row g-3" x-show="viewMode === 'grid'">
                            <template x-for="p in products" :key="p.id">
                                <div class="col-sm-6 col-md-4">
                                    <div class="card h-100 border shadow-sm transition-all" :class="{'border-primary bg-primary bg-opacity-10': isInCart(p.id), 'bg-body': !isInCart(p.id), 'opacity-50': !p.is_sku_enabled || p.available_stock <= 0}">
                                        <div class="card-body p-3">
                                            <div class="d-flex gap-3 mb-3">
                                                <div class="position-relative cursor-pointer" @click="openProductModal(p)">
                                                    <img :src="p.image_url || '/assets/images/product-placeholder.svg'" class="rounded border bg-body" style="width:60px;height:60px;object-fit:cover;flex-shrink:0" x-on:error="$el.src='/assets/images/product-placeholder.svg'">
                                                    <div x-show="isInCart(p.id)" class="position-absolute top-0 start-100 translate-middle p-1 bg-success border border-light rounded-circle text-white d-flex align-items-center justify-content-center" style="width: 20px; height: 20px; font-size: 10px;">
                                                        <i class="bi bi-check"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1" style="min-width: 0;">
                                                    <div class="fw-bold text-truncate text-body cursor-pointer text-primary-hover" :title="p.name" x-text="p.name" @click="openProductModal(p)"></div>
                                                    <div class="text-muted text-truncate font-monospace mt-1" style="font-size:11px;" x-text="p.sku"></div>
                                                </div>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mb-3 px-2 py-1 bg-body-tertiary rounded">
                                                <span class="fw-bold text-primary fs-5" x-text="'Rs ' + parseFloat(p.selling_price).toFixed(2)"></span>
                                                <span class="badge" :class="p.available_stock > 10 ? 'bg-success' : (p.available_stock > 0 ? 'bg-warning text-body' : 'bg-danger')" x-text="'Stock: ' + p.available_stock"></span>
                                            </div>
                                            <div class="row g-2">
                                                <div class="col-8">
                                                    <div class="form-floating">
                                                        <input type="number" class="form-control form-control-sm text-center fw-bold" style="height: 42px; min-height: 42px;" x-model.number="p._qty" min="1" :max="p.available_stock || 9999" placeholder="Qty" :disabled="!p.is_sku_enabled || p.available_stock <= 0">
                                                        <label class="text-muted" style="padding-top: 0.5rem; padding-bottom: 0.5rem; font-size: 0.75rem;">Qty</label>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <button class="btn btn-sm w-100 h-100 d-flex align-items-center justify-content-center gap-1" :class="isInCart(p.id) ? 'btn-primary' : 'btn-outline-primary'" @click="addToCart(p)" :title="isInCart(p.id) ? 'Add more' : 'Add to cart'" :disabled="!p.is_sku_enabled || p.available_stock <= 0">
                                                        <i class="bi fs-5" :class="isInCart(p.id) ? 'bi-cart-plus-fill' : 'bi-cart-plus'"></i>
                                                        <span x-text="!p.is_sku_enabled ? 'Disabled' : (p.available_stock <= 0 ? 'Out of Stock' : (isInCart(p.id) ? 'Add More' : 'Add'))" style="font-size: 13px;" class="fw-bold"></span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                        
                        {{-- Table View --}}
                        <div class="table-responsive border rounded bg-body shadow-sm" x-show="viewMode === 'table'">
                            <table class="table table-hover table-striped align-middle mb-0" style="font-size: 13px;">
                                <thead class="border-bottom">
                                    <tr class="text-muted">
                                        <th style="min-width: 250px;">Product Details</th>
                                        <th style="min-width: 150px;">Pricing & Offers</th>
                                        <th style="min-width: 150px;">Inventory</th>
                                        <th class="text-center" style="width: 140px;">Order Qty</th>
                                        <th class="text-center" style="width: 80px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="p in products" :key="'tbl-'+p.id">
                                        <tr :class="{'bg-primary bg-opacity-10': isInCart(p.id), 'opacity-50': !p.is_sku_enabled || p.available_stock <= 0}">
                                            <td>
                                                <div class="d-flex gap-3">
                                                    <img :src="p.image_url || '/assets/images/product-placeholder.svg'" class="rounded border bg-body shadow-sm cursor-pointer" style="width:50px;height:50px;object-fit:cover;flex-shrink:0" x-on:error="$el.src='/assets/images/product-placeholder.svg'" @click="openProductModal(p)">
                                                    <div style="min-width: 0;">
                                                        <div class="fw-bold text-body text-truncate mb-1 cursor-pointer text-primary-hover" :title="p.name" x-text="p.name" @click="openProductModal(p)"></div>
                                                        <div class="d-flex flex-wrap gap-1 align-items-center mb-1">
                                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary" x-text="p.sku"></span>
                                                            <span x-show="p.barcode" class="badge bg-body-secondary text-muted border"><i class="bi bi-upc-scan me-1"></i><span x-text="p.barcode"></span></span>
                                                            <span x-show="p.grade" class="badge bg-info bg-opacity-10 text-info border border-info" x-text="p.grade"></span>
                                                        </div>
                                                        <div class="small text-muted" style="font-size: 11px;">
                                                            <span x-show="p.category_id">Cat ID: <span class="fw-medium text-body" x-text="p.category_id"></span></span>
                                                            <span x-show="p.brand_id" class="ms-2">Brand ID: <span class="fw-medium text-body" x-text="p.brand_id"></span></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-primary fs-6 mb-1" x-text="'Rs ' + parseFloat(p.selling_price).toFixed(2)"></div>
                                                <div class="small text-muted text-decoration-line-through mb-1" x-show="p.mrp > p.selling_price" x-text="'MRP Rs ' + parseFloat(p.mrp).toFixed(2)"></div>
                                                <div class="badge bg-success" x-show="p.default_discount > 0"><span x-text="p.default_discount"></span><span x-text="p.default_discount_type === 'percent' ? '%' : ' Rs'"></span> OFF</div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <span class="badge" :class="p.available_stock > (p.min_stock_level || 10) ? 'bg-success' : (p.available_stock > 0 ? 'bg-warning text-dark' : 'bg-danger')" x-text="'Stock: ' + p.available_stock + ' ' + (p.uom_id || 'Units')"></span>
                                                </div>
                                                <div class="small text-muted mb-1" style="font-size: 11px;" x-show="p.min_stock_level > 0">Min Lvl: <span class="fw-medium" x-text="p.min_stock_level"></span></div>
                                                <div class="d-flex flex-wrap gap-1 mt-1">
                                                    <span x-show="p.batch_tracking" class="badge bg-secondary bg-opacity-10 text-secondary" style="font-size: 9px;"><i class="bi bi-box me-1"></i>Batch</span>
                                                    <span x-show="p.expiry_tracking" class="badge bg-secondary bg-opacity-10 text-secondary" style="font-size: 9px;"><i class="bi bi-calendar-x me-1"></i>Expiry</span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="input-group input-group-sm shadow-sm flex-nowrap mx-auto" style="width: 110px;">
                                                    <button class="btn btn-outline-secondary px-2" type="button" @click="if(p._qty > 1) p._qty--" :disabled="!p.is_sku_enabled || p.available_stock <= 0"><i class="bi bi-dash"></i></button>
                                                    <input type="number" class="form-control text-center fw-bold px-1 no-spinners" x-model.number="p._qty" min="1" :max="p.available_stock || 9999" placeholder="Qty" :disabled="!p.is_sku_enabled || p.available_stock <= 0">
                                                    <button class="btn btn-outline-secondary px-2" type="button" @click="if(p._qty < (p.available_stock || 9999)) p._qty++" :disabled="!p.is_sku_enabled || p.available_stock <= 0"><i class="bi bi-plus"></i></button>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-sm shadow-sm w-100 transition-all fw-bold text-nowrap" :class="isInCart(p.id) ? 'btn-primary' : 'btn-outline-primary'" @click="addToCart(p)" :title="isInCart(p.id) ? 'Add more' : 'Add to cart'" :disabled="!p.is_sku_enabled || p.available_stock <= 0">
                                                    <i class="bi" :class="isInCart(p.id) ? 'bi-cart-plus-fill' : 'bi-cart-plus'"></i> 
                                                    <span x-text="!p.is_sku_enabled ? 'Disabled' : (p.available_stock <= 0 ? 'Out of Stock' : (isInCart(p.id) ? 'Add More' : 'Add'))"></span>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    {{-- Pagination --}}
                    <div class="d-flex justify-content-between align-items-center px-3 pb-3 border-top pt-3" x-show="productTotal > 0">
                        <small class="text-muted"><span x-text="productFrom"></span>–<span x-text="productTo"></span> of <span x-text="productTotal"></span></small>
                        <div class="d-flex gap-1">
                            <button type="button" class="btn btn-sm btn-outline-secondary" @click="productPage--; searchProducts()" :disabled="productPage <= 1"><i class="bi bi-chevron-left"></i></button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" @click="productPage++; searchProducts()" :disabled="productPage >= productLastPage"><i class="bi bi-chevron-right"></i></button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Shopping Cart header --}}
            <div class="row align-items-center gy-3 mb-4 mt-2" x-show="false">
                <div class="col-sm">
                    <h4 class="mb-0 fw-black text-body d-flex align-items-center gap-2"><div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;"><i class="bi bi-cart3"></i></div> Shopping Cart (<span x-text="cart.length" class="text-primary"></span>)</h4>
                </div>
                <div class="col-sm-auto" x-show="cart.length > 0">
                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 shadow-sm hover-shadow" @click="cart = []">
                        <i class="bi bi-trash3 me-1"></i> Clear Cart
                    </button>
                </div>
            </div>

            {{-- Cart Items List (Glossy Style) --}}
            <div class="mb-4 space-y-3" x-show="false">
                {{-- Empty Cart --}}
                <template x-if="cart.length === 0">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body d-flex flex-column align-items-center justify-content-center text-center p-5 opacity-50">
                            <div class="bg-body-secondary rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                <i class="bi bi-bag fs-1 text-muted"></i>
                            </div>
                            <h5 class="fw-bold text-uppercase tracking-widest mb-2">Cart is empty</h5>
                            <p class="small text-muted mb-3">Browse products and click <strong>Add</strong> to begin.</p>
                        </div>
                    </div>
                </template>

                {{-- Cart Item Cards --}}
                <template x-for="(item, idx) in cart" :key="item.id">
                    <div class="card border shadow-sm mb-3">
                        <div class="d-flex align-items-start gap-3 p-3">
                            <div class="rounded-3 bg-body-tertiary border flex-shrink-0 d-flex align-items-center justify-content-center overflow-hidden" style="width: 70px; height: 70px;">
                                <img :src="item.image_url || '/assets/images/product-placeholder.svg'" class="w-100 h-100 object-fit-cover" x-on:error="$el.src='/assets/images/product-placeholder.svg'">
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex align-items-start justify-content-between gap-2">
                                    <div class="min-w-0">
                                        <h6 class="fw-bold text-truncate mb-1" x-text="item.name"></h6>
                                        <div class="font-monospace text-muted" style="font-size: 11px;" x-text="item.sku"></div>
                                    </div>
                                    <button type="button" @click.prevent="cart.splice(idx,1)" class="btn btn-sm btn-light text-muted hover-danger rounded-3 p-1 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;" title="Remove">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </div>
                                <div class="d-flex align-items-center justify-content-between mt-2">
                                    <span class="text-muted fw-medium" style="font-size: 12px;" x-text="'Rs ' + Number(item.price).toFixed(2) + ' × ' + item.quantity"></span>
                                    <span class="fw-bold text-success fs-6" x-text="'Rs ' + Number(lineTotal(item)).toFixed(2)"></span>
                                </div>
                                <div class="d-flex align-items-center justify-content-between mt-1" x-show="item.taxRate > 0">
                                    <span class="text-muted" style="font-size: 11px;" x-text="'+ GST ' + item.taxRate + '%'"></span>
                                    <span class="text-muted" style="font-size: 11px;" x-text="'Rs ' + Number(lineTotal(item) * (item.taxRate / 100)).toFixed(2)"></span>
                                </div>
                            </div>
                        </div>
                        <div class="px-3 pb-3 d-flex flex-wrap align-items-center gap-3">
                            <div class="d-flex align-items-center bg-body-secondary border rounded-3 p-1 flex-shrink-0">
                                <button type="button" @click.prevent="updateQty(idx,-1)" class="btn btn-sm btn-link text-body text-decoration-none fw-bold p-0 d-flex align-items-center justify-content-center hover-bg-body rounded" style="width: 28px; height: 28px;">
                                    <i class="bi bi-dash"></i>
                                </button>
                                <span class="fw-bold text-center" style="width: 32px; font-size: 13px;" x-text="item.quantity"></span>
                                <button type="button" @click.prevent="updateQty(idx,1)" class="btn btn-sm btn-link text-body text-decoration-none fw-bold p-0 d-flex align-items-center justify-content-center hover-bg-body rounded" style="width: 28px; height: 28px;">
                                    <i class="bi bi-plus"></i>
                                </button>
                            </div>
                            <div class="flex-grow-1 min-w-0 d-flex justify-content-end align-items-center gap-2">
                                <template x-if="item.discountValue > 0">
                                    <div class="badge bg-success bg-opacity-10 border border-success border-opacity-25 text-success d-flex align-items-center gap-1 px-2 py-1 rounded-3">
                                        <i class="bi bi-tag-fill"></i>
                                        <span class="fw-bold" style="font-size: 11px;" x-text="(item.discountType === 'flat' ? 'Rs ' : '') + Number(item.discountValue).toFixed(item.discountValue % 1 === 0 ? 0 : 2) + (item.discountType === 'flat' ? ' off' : '% off')"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- RIGHT: Cart Summary + Calculations + Offers + Place Order (Glossy Style) --}}
        <div class="col-xl-4">
            <div class="sticky-side-div" style="position: sticky; top: 24px;">
                <div class="card shadow-sm border-0 mb-4" x-show="partyId" x-cloak>
                    <div class="card-header bg-transparent border-bottom py-3 px-4">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-calendar-event me-2 text-primary"></i>Schedule Order</h5>
                        <p class="mb-0 small text-muted">Switch this on to save a future order draft.</p>
                    </div>
                    <div class="card-body p-4">
                        <div class="form-check form-switch d-flex align-items-center justify-content-between gap-3 p-3 rounded border bg-body">
                            <div>
                                <label class="form-check-label fw-bold mb-1" for="futureOrderSwitch">Place as Future Order</label>
                                <div class="small text-muted">Saves the order as pending for later processing.</div>
                            </div>
                            <input class="form-check-input fs-4 m-0" type="checkbox" id="futureOrderSwitch" x-model="isDraft">
                        </div>
                        <div x-show="isDraft" x-cloak class="mt-3 p-3 rounded border bg-body-tertiary">
                            <label class="form-label fw-semibold">Future Order Date</label>
                            <input type="date" class="form-control" :min="new Date().toISOString().split('T')[0]" x-model="futureOrderDate" required>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 mb-4" x-show="cart.length > 0" x-cloak>
                    <div class="card-header bg-transparent border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0 fw-bold"><i class="bi bi-cart3 me-2 text-primary"></i>Shopping Cart (<span x-text="cart.length" class="text-primary"></span>)</h5>
                            <p class="mb-0 small text-muted">Pinned summary for the order you’re building.</p>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger" @click="cart = []">
                            <i class="bi bi-trash3 me-1"></i> Clear
                        </button>
                    </div>
                    <div class="card-body p-3 p-lg-4">
                        <template x-if="cart.length === 0">
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-bag fs-1 d-block mb-2"></i>
                                Cart is empty
                            </div>
                        </template>
                        <template x-for="(item, idx) in cart" :key="item.id">
                            <div class="card border shadow-sm mb-3">
                                <div class="d-flex align-items-start gap-3 p-3">
                                    <div class="rounded-3 bg-body-tertiary border flex-shrink-0 d-flex align-items-center justify-content-center overflow-hidden" style="width: 70px; height: 70px;">
                                        <img :src="item.image_url || '/assets/images/product-placeholder.svg'" class="w-100 h-100 object-fit-cover" x-on:error="$el.src='/assets/images/product-placeholder.svg'">
                                    </div>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex align-items-start justify-content-between gap-2">
                                            <div class="min-w-0">
                                                <h6 class="fw-bold text-truncate mb-1" x-text="item.name"></h6>
                                                <div class="font-monospace text-muted" style="font-size: 11px;" x-text="item.sku"></div>
                                            </div>
                                            <button type="button" @click.prevent="cart.splice(idx,1)" class="btn btn-sm btn-light text-muted hover-danger rounded-3 p-1 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;" title="Remove">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between mt-2">
                                            <span class="text-muted fw-medium" style="font-size: 12px;" x-text="'Rs ' + Number(item.price).toFixed(2) + ' × ' + item.quantity"></span>
                                            <span class="fw-bold text-success fs-6" x-text="'Rs ' + Number(lineTotal(item)).toFixed(2)"></span>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between mt-1" x-show="item.taxRate > 0">
                                            <span class="text-muted" style="font-size: 11px;" x-text="'+ GST ' + item.taxRate + '%'"></span>
                                            <span class="text-muted" style="font-size: 11px;" x-text="'Rs ' + Number(lineTotal(item) * (item.taxRate / 100)).toFixed(2)"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="px-3 pb-3 d-flex flex-wrap align-items-center gap-3">
                                    <div class="d-flex align-items-center bg-body-secondary border rounded-3 p-1 flex-shrink-0">
                                        <button type="button" @click.prevent="updateQty(idx,-1)" class="btn btn-sm btn-link text-body text-decoration-none fw-bold p-0 d-flex align-items-center justify-content-center hover-bg-body rounded" style="width: 28px; height: 28px;">
                                            <i class="bi bi-dash"></i>
                                        </button>
                                        <span class="fw-bold text-center" style="width: 32px; font-size: 13px;" x-text="item.quantity"></span>
                                        <button type="button" @click.prevent="updateQty(idx,1)" class="btn btn-sm btn-link text-body text-decoration-none fw-bold p-0 d-flex align-items-center justify-content-center hover-bg-body rounded" style="width: 28px; height: 28px;">
                                            <i class="bi bi-plus"></i>
                                        </button>
                                    </div>
                                    <div class="flex-grow-1 min-w-0 d-flex justify-content-end align-items-center gap-2">
                                        <template x-if="item.discountValue > 0">
                                            <div class="badge bg-success bg-opacity-10 border border-success border-opacity-25 text-success d-flex align-items-center gap-1 px-2 py-1 rounded-3">
                                                <i class="bi bi-tag-fill"></i>
                                                <span class="fw-bold" style="font-size: 11px;" x-text="(item.discountType === 'flat' ? 'Rs ' : '') + Number(item.discountValue).toFixed(item.discountValue % 1 === 0 ? 0 : 2) + (item.discountType === 'flat' ? ' off' : '% off')"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
                <div class="card shadow-sm border-0 mb-4" x-show="cart.length > 0" x-cloak>
                    <div class="card-body p-4 space-y-4">
                        
                        {{-- ── Promotions & Offers ── --}}
                        <div class="mb-4">
                            <button type="button" class="btn btn-outline-primary w-100 border p-3 d-flex align-items-center justify-content-between shadow-sm bg-body-tertiary" data-bs-toggle="modal" data-bs-target="#promotionsModal">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 shadow-sm" style="width: 40px; height: 40px;">
                                        <i class="bi bi-tag-fill fs-5"></i>
                                    </div>
                                    <div class="text-start">
                                        <p class="mb-0 fw-bold fs-6">View Promos & Offers</p>
                                        <p class="mb-0 text-muted small" x-text="(activeOffers.length + activeCoupons.length) + ' available'"></p>
                                    </div>
                                </div>
                                <i class="bi bi-chevron-right text-muted"></i>
                            </button>
                        </div>

                        {{-- Applied Promotions Tags --}}
                        <div class="space-y-3 mb-4" x-show="bestOrderOffer || couponApplied || bogoDiscount > 0" x-cloak>
                            
                            {{-- Offer applied --}}
                            <template x-if="bestOrderOffer">
                                <div class="d-flex align-items-center justify-content-between gap-3 px-3 py-2 rounded-4 bg-success bg-opacity-10 border border-success border-opacity-25 shadow-sm transition-all hover-shadow">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="bg-success bg-opacity-25 text-success-emphasis rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;">
                                            <i class="bi bi-check-lg fs-5"></i>
                                        </div>
                                        <div>
                                            <p class="mb-0 fw-bold text-success-emphasis fs-6" x-text="bestOrderOffer.name"></p>
                                            <p class="mb-0 fw-semibold text-success opacity-75 small" x-text="'Saving Rs ' + Number(orderOfferDiscountAmount).toFixed(2)"></p>
                                        </div>
                                    </div>
                                    <button type="button" @click.prevent="appliedOfferId = 'none'" class="btn btn-sm btn-light text-muted hover-danger rounded-circle p-0 d-flex align-items-center justify-content-center shadow-sm" style="width: 28px; height: 28px;">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                            </template>

                            {{-- Coupon applied --}}
                            <template x-if="couponApplied">
                                <div class="d-flex align-items-center justify-content-between gap-3 px-3 py-2 rounded-4 bg-success bg-opacity-10 border border-success border-opacity-25 shadow-sm transition-all hover-shadow">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="bg-success bg-opacity-25 text-success-emphasis rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;">
                                            <i class="bi bi-check-lg fs-5"></i>
                                        </div>
                                        <div>
                                            <p class="mb-0 fw-bold text-success-emphasis fs-6" x-text="'Coupon: ' + couponCode"></p>
                                            <p class="mb-0 fw-semibold text-success opacity-75 small" x-text="'Saving Rs ' + Number(couponDiscount).toFixed(2)"></p>
                                        </div>
                                    </div>
                                    <button type="button" @click.prevent="removeCoupon()" class="btn btn-sm btn-light text-muted hover-danger rounded-circle p-0 d-flex align-items-center justify-content-center shadow-sm" style="width: 28px; height: 28px;">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                            </template>

                            {{-- BOGO (auto-applied) --}}
                            <template x-if="bogoDiscount > 0">
                                <div class="d-flex align-items-center justify-content-between gap-3 px-3 py-2 rounded-4 bg-info bg-opacity-10 border border-info border-opacity-25 shadow-sm transition-all hover-shadow">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="bg-info bg-opacity-25 text-info-emphasis rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;">
                                            <i class="bi bi-lightning-charge-fill fs-5"></i>
                                        </div>
                                        <div>
                                            <p class="mb-0 fw-bold text-info-emphasis fs-6">BOGO Savings</p>
                                            <p class="mb-0 text-info opacity-75 small">Auto-applied</p>
                                        </div>
                                    </div>
                                    <span class="fw-bold text-info-emphasis fs-6" x-text="'- Rs ' + Number(bogoDiscount).toFixed(2)"></span>
                                </div>
                            </template>

                        </div>

                        <hr class="border-secondary opacity-10">

                        {{-- Order Summary Calculations --}}
                        <div class="space-y-2 mb-4">
                            <div class="d-flex justify-content-between fw-medium text-muted" style="font-size: 13px;">
                                <span>Subtotal</span>
                                <span class="text-body fw-bold" x-text="'Rs ' + Number(subtotal).toFixed(2)"></span>
                            </div>
                            
                            <div class="d-flex justify-content-between fw-medium text-success" style="font-size: 13px;" x-show="bogoDiscount > 0" x-cloak>
                                <div>
                                    <span>BOGO Savings</span>
                                    <span class="text-muted d-block" style="font-size: 10px;">Auto-applied backend offer</span>
                                </div>
                                <span class="fw-bold align-top" x-text="'- Rs ' + Number(bogoDiscount).toFixed(2)"></span>
                            </div>

                            <div class="d-flex justify-content-between fw-medium text-success" style="font-size: 13px;" x-show="orderOfferDiscountAmount > 0" x-cloak>
                                <div>
                                    <span>Order Discount</span>
                                    <span class="text-muted d-block" style="font-size: 10px;" x-text="bestOrderOffer ? bestOrderOffer.name : ''"></span>
                                </div>
                                <span class="fw-bold align-top" x-text="'- Rs ' + Number(orderOfferDiscountAmount).toFixed(2)"></span>
                            </div>

                            <div class="d-flex justify-content-between fw-medium text-success" style="font-size: 13px;" x-show="couponDiscount > 0" x-cloak>
                                <div>
                                    <span>Coupon Savings</span>
                                    <span class="text-muted d-block" style="font-size: 10px;" x-text="'(Code: ' + couponCode + ')'"></span>
                                </div>
                                <span class="fw-bold align-top" x-text="'- Rs ' + Number(couponDiscount).toFixed(2)"></span>
                            </div>

                            <div class="d-flex justify-content-between fw-medium text-muted" style="font-size: 13px;">
                                <span>GST</span>
                                <span class="text-body" x-text="'Rs ' + Number(taxAmount).toFixed(2)"></span>
                            </div>

                            <hr class="border-secondary opacity-10 my-3">

                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-uppercase tracking-widest text-body" style="font-size: 14px;">Grand Total</span>
                                <span class="fw-black text-primary fs-3" x-text="'Rs ' + Number(grandTotal).toFixed(2)"></span>
                            </div>
                        </div>

                        {{-- Action Panel --}}
                        <button type="button" @click.prevent="placeOrder()" :disabled="placing || cart.length === 0 || !partyId || !warehouseId"
                            class="btn btn-primary w-100 py-3 fw-bold text-uppercase shadow-sm position-relative overflow-hidden" style="letter-spacing: 1px;">
                            <span x-show="placing" class="spinner-border spinner-border-sm me-2"></span>
                            <i x-show="!placing" class="bi bi-check-circle-fill me-2 fs-5 align-middle"></i>
                            <span x-text="editingOrderId ? 'Update Order' : (isDraft ? 'Save Future Order' : 'Complete Order')" class="align-middle"></span>
                        </button>
                        
                        <template x-if="formErrors.length">
                            <div class="alert alert-danger mt-3 mb-0 p-3 shadow-sm small border-0 bg-danger bg-opacity-10 text-danger-emphasis">
                                <div class="d-flex align-items-center gap-2 mb-2 fw-bold"><i class="bi bi-exclamation-triangle-fill"></i> Please fix the following errors:</div>
                                <ul class="mb-0 ps-3">
                                    <template x-for="e in formErrors" :key="e"><li x-text="e"></li></template>
                                </ul>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-5 overflow-hidden glass-panel mt-2 mb-4" x-show="partyId" x-cloak>
        <div class="card-header bg-body-tertiary border-bottom-0 p-3 p-lg-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div>
                    <h5 class="mb-1 fw-bold text-body-emphasis"><i class="bi bi-layers me-2 text-primary"></i>Order Center</h5>
                    <p class="mb-0 small text-muted">Tap an order to expand its details.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm rounded-pill px-4 fw-bold border" :class="bottomTab === 'history' ? 'btn-primary border-primary' : 'btn-light text-body-secondary'" @click="bottomTab = 'history'; expandedOrderId = null">History</button>
                    <button type="button" class="btn btn-sm rounded-pill px-4 fw-bold border" :class="bottomTab === 'future' ? 'btn-primary border-primary' : 'btn-light text-body-secondary'" @click="bottomTab = 'future'; expandedOrderId = null">Future Orders</button>
                    <button type="button" class="btn btn-sm rounded-pill px-4 fw-bold border" :class="bottomTab === 'tags' ? 'btn-primary border-primary' : 'btn-light text-body-secondary'" @click="bottomTab = 'tags'; expandedOrderId = null">Customer Tags</button>
                </div>
            </div>
        </div>
        <div class="card-body p-4 p-lg-4">
            <template x-if="bottomTab === 'history'">
                <div class="d-flex flex-column gap-3">
                    <template x-for="order in recentOrders" :key="'history-' + order.id">
                        <div class="card border-0 shadow-sm rounded-4 bg-body-tertiary overflow-hidden">
                            <button type="button" class="w-100 text-start border-0 bg-transparent p-0" @click="toggleOrderDetails(order.id)">
                                <div class="card-body p-4 d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center">
                                    <div class="min-w-0">
                                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                            <h6 class="fw-bold mb-0 text-body-emphasis" x-text="order.order_no || order.order_number || ('Order #' + order.id)"></h6>
                                            <span class="badge rounded-pill text-bg-primary-subtle text-primary-emphasis border" x-text="order.status_label || order.lifecycle_status || order.status || 'Pending'"></span>
                                            <span class="badge rounded-pill text-bg-warning-subtle text-warning-emphasis border" x-show="order.is_draft">Future Order</span>
                                        </div>
                                        <div class="small text-muted text-truncate">
                                            <span class="me-3" x-text="order.order_date ? new Date(order.order_date).toLocaleDateString() : 'No date'"></span>
                                            <span class="me-3" x-text="order.warehouse?.name ? 'Warehouse: ' + order.warehouse.name : 'Warehouse: N/A'"></span>
                                            <span x-text="'Items: ' + (order.items ? order.items.length : 0)"></span>
                                        </div>
                                    </div>
                                    <div class="text-lg-end">
                                        <div class="fw-bold text-body-emphasis" x-text="'Rs ' + Number(order.net_amount || 0).toFixed(2)"></div>
                                        <div class="small text-muted" x-text="expandedOrderId === order.id ? 'Hide details' : 'Show details'"></div>
                                    </div>
                                </div>
                            </button>

                            <div x-show="expandedOrderId === order.id" x-cloak class="border-top bg-body">
                                <div class="p-4">
                                    <div class="row g-3 mb-4">
                                        <div class="col-lg-4">
                                            <div class="p-3 rounded-4 bg-body-tertiary border h-100">
                                                <div class="fw-bold text-body-emphasis mb-1">Shipping</div>
                                                <div class="small text-muted" x-text="order.shipping_address_line_1 ? [order.shipping_address_line_1, order.shipping_city, order.shipping_state].filter(Boolean).join(', ') : 'Not available'"></div>
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="p-3 rounded-4 bg-body-tertiary border h-100">
                                                <div class="fw-bold text-body-emphasis mb-1">Billing</div>
                                                <div class="small text-muted" x-text="order.billing_address_line_1 ? [order.billing_address_line_1, order.billing_city, order.billing_state].filter(Boolean).join(', ') : 'Same as shipping / not available'"></div>
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="p-3 rounded-4 bg-body-tertiary border h-100">
                                                <div class="fw-bold text-body-emphasis mb-1">Totals</div>
                                                <div class="small text-muted" x-text="'Subtotal Rs ' + Number(order.total_amount || 0).toFixed(2) + ' | GST Rs ' + Number(order.tax_amount || 0).toFixed(2)"></div>
                                                <div class="small text-muted" x-text="order.applied_offer?.name ? 'Offer: ' + order.applied_offer.name : 'No offer applied'"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table align-middle mb-0">
                                            <thead>
                                                <tr class="small text-muted">
                                                    <th>Item</th>
                                                    <th>SKU</th>
                                                    <th class="text-center">Qty</th>
                                                    <th class="text-end">Price</th>
                                                    <th class="text-end">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <template x-for="item in (order.items || [])" :key="'history-item-' + order.id + '-' + item.id">
                                                    <tr>
                                                        <td class="fw-semibold text-body-emphasis" x-text="item.product?.name || item.product_name || 'Product'"></td>
                                                        <td class="text-muted" x-text="item.product?.sku || item.sku || 'N/A'"></td>
                                                        <td class="text-center fw-semibold" x-text="item.quantity"></td>
                                                        <td class="text-end text-muted" x-text="'Rs ' + Number(item.unit_price || 0).toFixed(2)"></td>
                                                        <td class="text-end fw-bold text-body-emphasis" x-text="'Rs ' + Number(item.total_amount || 0).toFixed(2)"></td>
                                                    </tr>
                                                </template>
                                                <template x-if="!order.items || order.items.length === 0">
                                                    <tr>
                                                        <td colspan="5" class="text-center text-muted py-4">No order items found.</td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template x-if="!recentOrders.length">
                        <div class="alert alert-light border rounded-4 mb-0 p-4 text-center text-muted">No order history available.</div>
                    </template>
                </div>
            </template>

            <template x-if="bottomTab === 'future'">
                <div class="d-flex flex-column gap-3">
                    <template x-for="order in futureOrders" :key="'future-' + order.id">
                        <div class="card border-0 shadow-sm rounded-4 bg-body-tertiary overflow-hidden">
                            <button type="button" class="w-100 text-start border-0 bg-transparent p-0" @click="toggleOrderDetails(order.id)">
                                <div class="card-body p-4 d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center">
                                    <div class="min-w-0">
                                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                            <h6 class="fw-bold mb-0 text-body-emphasis" x-text="order.order_no || order.order_number || ('Order #' + order.id)"></h6>
                                            <span class="badge rounded-pill text-bg-warning-subtle text-warning-emphasis border">Future</span>
                                            <span class="badge rounded-pill text-bg-primary-subtle text-primary-emphasis border" x-text="order.status_label || order.lifecycle_status || order.status || 'Pending'"></span>
                                        </div>
                                        <div class="small text-muted text-truncate">
                                            <span class="me-3" x-text="order.future_order_date ? new Date(order.future_order_date).toLocaleDateString() : 'No future date'"></span>
                                            <span class="me-3" x-text="order.warehouse?.name ? 'Warehouse: ' + order.warehouse.name : 'Warehouse: N/A'"></span>
                                            <span x-text="'Items: ' + (order.items ? order.items.length : 0)"></span>
                                        </div>
                                    </div>
                                    <div class="text-lg-end">
                                        <div class="fw-bold text-body-emphasis" x-text="'Rs ' + Number(order.net_amount || 0).toFixed(2)"></div>
                                        <div class="small text-muted" x-text="expandedOrderId === order.id ? 'Hide details' : 'Show details'"></div>
                                    </div>
                                </div>
                            </button>

                            <div x-show="expandedOrderId === order.id" x-cloak class="border-top bg-body">
                                <div class="p-4">
                                    <div class="row g-3 mb-4">
                                        <div class="col-lg-6">
                                            <div class="p-3 rounded-4 bg-body-tertiary border h-100">
                                                <div class="fw-bold text-body-emphasis mb-1">Shipping</div>
                                                <div class="small text-muted" x-text="order.shipping_address_line_1 ? [order.shipping_address_line_1, order.shipping_city, order.shipping_state].filter(Boolean).join(', ') : 'Not available'"></div>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="p-3 rounded-4 bg-body-tertiary border h-100">
                                                <div class="fw-bold text-body-emphasis mb-1">Billing</div>
                                                <div class="small text-muted" x-text="order.billing_address_line_1 ? [order.billing_address_line_1, order.billing_city, order.billing_state].filter(Boolean).join(', ') : 'Same as shipping / not available'"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table align-middle mb-0">
                                            <thead>
                                                <tr class="small text-muted">
                                                    <th>Item</th>
                                                    <th>SKU</th>
                                                    <th class="text-center">Qty</th>
                                                    <th class="text-end">Price</th>
                                                    <th class="text-end">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <template x-for="item in (order.items || [])" :key="'future-item-' + order.id + '-' + item.id">
                                                    <tr>
                                                        <td class="fw-semibold text-body-emphasis" x-text="item.product?.name || item.product_name || 'Product'"></td>
                                                        <td class="text-muted" x-text="item.product?.sku || item.sku || 'N/A'"></td>
                                                        <td class="text-center fw-semibold" x-text="item.quantity"></td>
                                                        <td class="text-end text-muted" x-text="'Rs ' + Number(item.unit_price || 0).toFixed(2)"></td>
                                                        <td class="text-end fw-bold text-body-emphasis" x-text="'Rs ' + Number(item.total_amount || 0).toFixed(2)"></td>
                                                    </tr>
                                                </template>
                                                <template x-if="!order.items || order.items.length === 0">
                                                    <tr>
                                                        <td colspan="5" class="text-center text-muted py-4">No order items found.</td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template x-if="!futureOrders.length">
                        <div class="alert alert-light border rounded-4 mb-0 p-4 text-center text-muted">No future orders scheduled.</div>
                    </template>
                </div>
            </template>

            <template x-if="bottomTab === 'tags'">
                <div class="d-flex flex-wrap gap-2">
                    <template x-for="tag in customerTags" :key="tag">
                        <span class="badge rounded-pill text-bg-primary-subtle text-primary-emphasis border px-3 py-2" x-text="tag"></span>
                    </template>
                    <span class="badge rounded-pill text-bg-success-subtle text-success-emphasis border px-3 py-2" x-show="customerDetails?.kyc_completed">KYC Completed</span>
                    <span class="badge rounded-pill text-bg-danger-subtle text-danger-emphasis border px-3 py-2" x-show="customerDetails?.is_blacklisted">Blacklisted</span>
                    <span class="badge rounded-pill text-bg-dark-subtle text-body-secondary border px-3 py-2" x-show="!customerTags.length && !customerDetails?.kyc_completed && !customerDetails?.is_blacklisted">No tags available</span>
                </div>
            </template>
        </div>
    </div>

    {{-- Promotions Modal --}}
    <div class="modal fade" id="promotionsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-body-tertiary border-bottom-0 p-4">
                    <h5 class="modal-title fw-bold text-body-emphasis d-flex align-items-center gap-2">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                            <i class="bi bi-tag-fill fs-6"></i>
                        </div>
                        Promotions & Offers
                    </h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <ul class="nav nav-tabs nav-fill border-bottom-0 bg-body-tertiary px-3" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-semibold py-3 border-0 border-bottom border-3 border-transparent" data-bs-toggle="tab" data-bs-target="#tab-offers" type="button" role="tab" onclick="this.classList.add('border-primary'); this.parentElement.nextElementSibling.firstElementChild.classList.remove('border-primary')">
                                Offers <span class="badge bg-secondary ms-1 rounded-pill" x-text="activeOffers.length"></span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-semibold py-3 border-0 border-bottom border-3 border-transparent" data-bs-toggle="tab" data-bs-target="#tab-coupons" type="button" role="tab" onclick="this.classList.add('border-primary'); this.parentElement.previousElementSibling.firstElementChild.classList.remove('border-primary')">
                                Coupons <span class="badge bg-secondary ms-1 rounded-pill" x-text="activeCoupons.length"></span>
                            </button>
                        </li>
                    </ul>
                    <div class="tab-content p-4 bg-body">
                        
                        {{-- Offers Tab --}}
                        <div class="tab-pane fade show active" id="tab-offers" role="tabpanel">
                            <template x-if="activeOffers.length === 0">
                                <div class="text-center py-4 text-muted">
                                    <i class="bi bi-gift fs-1 mb-2 d-block opacity-50"></i>
                                    <p class="mb-0 fw-medium">No offers available for your current cart.</p>
                                </div>
                            </template>
                            <div class="space-y-3">
                                <template x-for="offer in sortedActiveOffers" :key="offer.id">
                                    <div class="card border-2 rounded-4 transition-all hover-shadow" 
                                         :class="offer.type === 'bogo' ? 'border-info border-opacity-25 bg-info bg-opacity-10' : (appliedOfferId === offer.id ? 'border-success bg-success bg-opacity-10' : (orderOfferDiscount(offer) > 0 ? 'border-secondary border-opacity-10 bg-body-tertiary cursor-pointer' : 'border-secondary border-opacity-10 bg-body-secondary opacity-75'))" 
                                         @click="if(offer.type === 'order_discount' && orderOfferDiscount(offer) > 0) appliedOfferId = (appliedOfferId === offer.id) ? 'none' : offer.id">
                                        <div class="card-body p-3 d-flex align-items-center justify-content-between gap-3">
                                            <div>
                                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                                    <h6 class="fw-bold mb-0" :class="(appliedOfferId === offer.id || offer.type === 'bogo') ? 'text-body-emphasis' : 'text-body'" x-text="offer.name"></h6>
                                                    <span class="badge bg-secondary bg-opacity-10 text-secondary-emphasis rounded-pill px-2 py-0.5 small" style="font-size: 0.7rem;" x-text="'Priority: ' + offer.priority"></span>
                                                </div>
                                                
                                                {{-- Common Rules --}}
                                                <div class="mt-2 mb-2 pe-3 border-start border-2 border-secondary border-opacity-25 ps-2">
                                                    {{-- BOGO Offer Details --}}
                                                    <div x-show="offer.type === 'bogo'">
                                                        <p class="mb-1 small text-muted" x-text="'Rule: Buy ' + offer.buy_qty + ' Get ' + offer.get_qty + ' Free on ' + offer.product_name"></p>
                                                    </div>

                                                    {{-- Order Discount Details --}}
                                                    <div x-show="offer.type === 'order_discount'">
                                                        <p class="mb-1 small text-muted" x-text="'Discount: ' + (offer.discount_type === 'percentage' ? offer.value + '%' : 'Rs ' + Number(offer.value).toFixed(2))"></p>
                                                        <p class="mb-1 small text-muted" x-show="offer.max_discount > 0" x-text="'Max Discount: Rs ' + Number(offer.max_discount).toFixed(2)"></p>
                                                    </div>
                                                    
                                                    <p class="mb-1 small text-muted" x-show="offer.min_spend > 0" x-text="'Min. Spend: Rs ' + Number(offer.min_spend).toFixed(2)"></p>
                                                    <p class="mb-0 small text-muted" x-show="offer.ends_at" x-text="'Valid till ' + new Date(offer.ends_at).toLocaleDateString()"></p>
                                                </div>

                                                {{-- Savings/Unlock Status --}}
                                                <div x-show="offer.type === 'bogo'">
                                                    <p class="mb-0 small text-info"><i class="bi bi-lightning-charge-fill me-1"></i>Auto-applied to eligible items</p>
                                                </div>
                                                <div x-show="offer.type === 'order_discount'">
                                                    <div x-show="orderOfferDiscount(offer) > 0">
                                                        <p class="mb-0 small fw-medium">You save: <span class="text-success" x-text="'Rs ' + Number(orderOfferDiscount(offer)).toFixed(2)"></span></p>
                                                    </div>
                                                    <div x-show="orderOfferDiscount(offer) === 0">
                                                        <p class="mb-0 small text-danger"><i class="bi bi-info-circle me-1"></i>Add <span x-text="'Rs ' + Number(offer.min_spend).toFixed(2)"></span> to cart to unlock</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex-shrink-0">
                                                {{-- BOGO Badge --}}
                                                <template x-if="offer.type === 'bogo'">
                                                    <span class="badge bg-info bg-opacity-25 text-info-emphasis rounded-pill px-3 py-2 fw-medium">Active</span>
                                                </template>
                                                
                                                {{-- Order Discount Actions --}}
                                                <template x-if="offer.type === 'order_discount'">
                                                    <div>
                                                        <template x-if="appliedOfferId === offer.id">
                                                            <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm mx-auto" style="width: 28px; height: 28px;">
                                                                <i class="bi bi-check fs-5"></i>
                                                            </div>
                                                        </template>
                                                        <template x-if="appliedOfferId !== offer.id">
                                                            <button class="btn btn-sm rounded-pill px-3 fw-medium" 
                                                                    :class="orderOfferDiscount(offer) === 0 ? 'btn-light text-muted border' : 'btn-outline-secondary'" 
                                                                    :disabled="orderOfferDiscount(offer) === 0">
                                                                Apply
                                                            </button>
                                                        </template>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                        
                        {{-- Coupons Tab --}}
                        <div class="tab-pane fade" id="tab-coupons" role="tabpanel">
                            
                            {{-- Manual Entry --}}
                            <div class="d-flex align-items-center gap-2 mb-4 p-3 bg-body-secondary rounded-4 border">
                                <i class="bi bi-ticket-perforated text-muted fs-5 ms-1"></i>
                                <input type="text" x-model="couponInputTemp" @keydown.enter.prevent="applyCoupon(couponInputTemp)" placeholder="Enter promo code..." class="form-control border-0 bg-transparent shadow-none font-monospace text-uppercase fw-bold">
                                <button type="button" @click.prevent="applyCoupon(couponInputTemp)" class="btn btn-primary rounded-pill fw-bold text-uppercase tracking-widest px-4 flex-shrink-0 shadow-sm">
                                    Apply
                                </button>
                            </div>

                            <hr class="border-secondary opacity-10 mb-4">

                            <h6 class="fw-bold text-muted text-uppercase tracking-widest small mb-3">Available Coupons</h6>
                            
                            <template x-if="activeCoupons.length === 0">
                                <div class="text-center py-4 text-muted">
                                    <p class="mb-0 fw-medium">No coupons currently available.</p>
                                </div>
                            </template>

                            <div class="space-y-3">
                                <template x-for="c in activeCoupons" :key="c.id">
                                    <div class="card border-2 rounded-4 transition-all hover-shadow cursor-pointer" :class="(couponApplied && couponCode === c.code) ? 'border-success bg-success bg-opacity-10' : 'border-secondary border-opacity-10 bg-body-tertiary'" @click="applyCoupon(c.code)">
                                        <div class="card-body p-3 d-flex align-items-center justify-content-between gap-3">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="border border-dashed border-2 rounded-3 p-2 bg-body text-center">
                                                    <code class="fw-black text-body-emphasis fs-6 d-block mb-1" x-text="c.code"></code>
                                                    <span class="badge bg-primary bg-opacity-10 text-primary w-100" x-text="c.type === 'percentage' ? c.value + '% OFF' : 'Rs ' + Number(c.value).toFixed(2) + ' OFF'"></span>
                                                </div>
                                                <div class="ps-2 border-start border-2 border-secondary border-opacity-25 my-1">
                                                    <p class="mb-1 small text-muted" x-show="c.min_spend > 0" x-text="'Min. Spend: Rs ' + Number(c.min_spend).toFixed(2)"></p>
                                                    <p class="mb-1 small text-muted" x-show="c.max_discount > 0" x-text="'Max Discount: Rs ' + Number(c.max_discount).toFixed(2)"></p>
                                                    <p class="mb-1 small text-muted" x-show="c.usage_limit > 0" x-text="'Remaining Uses: ' + Math.max(0, c.usage_limit - c.used_count)"></p>
                                                    <p class="mb-0 small text-muted" x-show="c.expiry_date" x-text="'Valid till ' + new Date(c.expiry_date).toLocaleDateString()"></p>
                                                </div>
                                            </div>
                                            <div class="flex-shrink-0">
                                                <template x-if="couponApplied && couponCode === c.code">
                                                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 28px; height: 28px;">
                                                        <i class="bi bi-check fs-5"></i>
                                                    </div>
                                                </template>
                                                <template x-if="!(couponApplied && couponCode === c.code)">
                                                    <button class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-medium">Apply</button>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Product Details Modal -->
    <div class="modal fade" id="productDetailsModal" tabindex="-1" aria-labelledby="productDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content bg-body border-0 shadow-lg" x-show="selectedProductForModal">
                <!-- Header -->
                <div class="modal-header bg-body-tertiary border-bottom py-3 px-4">
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <h5 class="modal-title fw-bold mb-0" id="productDetailsModalLabel" x-text="selectedProductForModal ? selectedProductForModal.name : ''"></h5>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary" x-text="selectedProductForModal ? selectedProductForModal.sku : ''"></span>
                        <span class="badge" :class="selectedProductForModal && selectedProductForModal.status === 'published' ? 'bg-success' : 'bg-warning text-dark'" x-text="selectedProductForModal ? selectedProductForModal.status : ''"></span>
                        <span class="spinner-border spinner-border-sm text-primary ms-2" role="status" x-show="productModalLoading"></span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Body: scrollable single column -->
                <div class="modal-body p-0" style="overflow-y: auto;">
                    <div class="row g-0" style="min-height: 100%;">
                        <!-- Left: Image & Meta (fixed panel) -->
                        <div class="col-md-4 bg-body-tertiary border-end p-3" style="position: sticky; top: 0; height: fit-content; align-self: flex-start;">
                            <div class="card border border-secondary border-opacity-25 mb-3 rounded-4 overflow-hidden position-relative" style="aspect-ratio:1;width:100%;">
                                <img :src="selectedProductForModal ? (selectedProductForModal.image_url || '/assets/images/product-placeholder.svg') : ''" class="w-100 h-100 object-fit-cover" x-on:error="$el.src='/assets/images/product-placeholder.svg'">
                                <span class="position-absolute top-0 end-0 m-2 badge bg-success shadow-sm" x-show="selectedProductForModal && selectedProductForModal.default_discount > 0" x-text="selectedProductForModal ? selectedProductForModal.default_discount + (selectedProductForModal.default_discount_type === 'percent' ? '%' : '') + ' OFF' : ''"></span>
                            </div>
                            <div x-show="selectedProductForModal">
                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    <span x-show="selectedProductForModal && selectedProductForModal.category" class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 py-2 px-3"><i class="bi bi-tag-fill me-1"></i><span x-text="selectedProductForModal ? selectedProductForModal.category : ''"></span></span>
                                    <span x-show="selectedProductForModal && selectedProductForModal.brand" class="badge bg-dark bg-opacity-10 text-dark border py-2 px-3"><i class="bi bi-award-fill me-1"></i><span x-text="selectedProductForModal ? selectedProductForModal.brand : ''"></span></span>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-borderless small mb-0 text-muted">
                                        <tbody>
                                            <tr x-show="selectedProductForModal && selectedProductForModal.barcode"><th class="ps-0" style="width:100px;">Barcode</th><td x-text="selectedProductForModal ? selectedProductForModal.barcode : ''"></td></tr>
                                            <tr x-show="selectedProductForModal && selectedProductForModal.weight"><th class="ps-0">Weight</th><td x-text="selectedProductForModal ? selectedProductForModal.weight : ''"></td></tr>
                                            <tr x-show="selectedProductForModal && selectedProductForModal.uom"><th class="ps-0">UOM</th><td x-text="selectedProductForModal ? selectedProductForModal.uom : ''"></td></tr>
                                            <tr x-show="selectedProductForModal && selectedProductForModal.grade"><th class="ps-0">Grade</th><td x-text="selectedProductForModal ? selectedProductForModal.grade : ''"></td></tr>
                                            <tr x-show="selectedProductForModal && selectedProductForModal.warehouse"><th class="ps-0">Warehouse</th><td x-text="selectedProductForModal ? selectedProductForModal.warehouse : ''"></td></tr>
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
                                            <div class="fw-black text-primary" style="font-size:18px;" x-text="selectedProductForModal ? 'Rs ' + parseFloat(selectedProductForModal.selling_price||0).toFixed(2) : ''"></div>
                                            <div class="text-muted text-decoration-line-through" style="font-size:10px;" x-show="selectedProductForModal && selectedProductForModal.mrp > selectedProductForModal.selling_price" x-text="selectedProductForModal ? 'MRP Rs ' + parseFloat(selectedProductForModal.mrp||0).toFixed(2) : ''"></div>
                                        </div>
                                        <div class="col-4 border-end border-secondary border-opacity-25 ps-3">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase d-block" style="font-size:9px;">Purchase Price</label>
                                            <div class="fw-bold text-body-emphasis" style="font-size:14px;" x-text="selectedProductForModal ? 'Rs ' + parseFloat(selectedProductForModal.purchase_price||0).toFixed(2) : ''"></div>
                                        </div>
                                        <div class="col-4 ps-3">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase d-block" style="font-size:9px;">Profit Margin</label>
                                            <div class="fw-bold text-success" style="font-size:14px;" x-text="selectedProductForModal && selectedProductForModal.selling_price > 0 && selectedProductForModal.purchase_price > 0 ? (((selectedProductForModal.selling_price - selectedProductForModal.purchase_price) / selectedProductForModal.purchase_price) * 100).toFixed(1) + '%' : 'N/A'"></div>
                                        </div>
                                    </div>
                                    <div class="row g-2 pt-2 border-top border-secondary border-opacity-25">
                                        <div class="col-6 border-end border-secondary border-opacity-25">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase d-block" style="font-size:9px;">Taxes</label>
                                            <div class="fw-bold text-body-emphasis" style="font-size:13px;" x-text="selectedProductForModal && selectedProductForModal.tax_rate > 0 ? (selectedProductForModal.tax_rate + '%') : 'No Tax'"></div>
                                        </div>
                                        <div class="col-6 ps-3">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase d-block" style="font-size:9px;">HSN / SAC Code</label>
                                            <div class="fw-bold text-body-emphasis" style="font-size:13px;" x-text="selectedProductForModal ? (selectedProductForModal.hsn_code || 'Not Set') : ''"></div>
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
                                                    <div class="fw-bold text-body-emphasis" style="font-size:16px;" x-text="selectedProductForModal ? (selectedProductForModal.available_stock + ' ' + (selectedProductForModal.uom || 'Units')) : ''"></div>
                                                    <div class="text-muted" style="font-size:10px;">Available to Order</div>
                                                </div>
                                                <span class="badge" style="font-size:10px;" :class="selectedProductForModal && selectedProductForModal.available_stock > (selectedProductForModal.min_stock_level || 10) ? 'bg-success' : (selectedProductForModal && selectedProductForModal.available_stock > 0 ? 'bg-warning text-dark' : 'bg-danger')" x-text="selectedProductForModal && selectedProductForModal.available_stock > 0 ? 'In Stock' : 'Out of Stock'"></span>
                                            </div>
                                            <div class="row text-center g-1 mb-2">
                                                <div class="col-4"><div class="fw-semibold" style="font-size:13px;" x-text="selectedProductForModal ? (selectedProductForModal.physical_available || selectedProductForModal.stock) : 0"></div><div class="text-muted" style="font-size:9px;">Physical</div></div>
                                                <div class="col-4 border-start border-end border-secondary border-opacity-25"><div class="fw-semibold text-warning" style="font-size:13px;" x-text="selectedProductForModal ? ((selectedProductForModal.reserved_qty || 0) + (selectedProductForModal.pending_qty || 0)) : 0"></div><div class="text-muted" style="font-size:9px;">Reserved</div></div>
                                                <div class="col-4"><div class="fw-semibold text-danger" style="font-size:13px;" x-text="selectedProductForModal ? selectedProductForModal.min_stock_level : 0"></div><div class="text-muted" style="font-size:9px;">Min Level</div></div>
                                            </div>
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase d-block" style="font-size:9px;">Tracking</label>
                                            <div class="list-group list-group-flush border border-secondary border-opacity-25 rounded-3">
                                                <div class="list-group-item d-flex justify-content-between align-items-center px-2 py-1 bg-transparent"><span class="text-muted" style="font-size:10px;"><i class="bi bi-box me-1"></i>Batch</span><span class="badge" style="font-size:9px;" :class="selectedProductForModal && selectedProductForModal.batch_tracking ? 'bg-success' : 'bg-secondary'" x-text="selectedProductForModal && selectedProductForModal.batch_tracking ? 'ON' : 'OFF'"></span></div>
                                                <div class="list-group-item d-flex justify-content-between align-items-center px-2 py-1 bg-transparent"><span class="text-muted" style="font-size:10px;"><i class="bi bi-calendar-x me-1"></i>Expiry</span><span class="badge" style="font-size:9px;" :class="selectedProductForModal && selectedProductForModal.expiry_tracking ? 'bg-success' : 'bg-secondary'" x-text="selectedProductForModal && selectedProductForModal.expiry_tracking ? 'ON' : 'OFF'"></span></div>
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
                                            <div x-show="productModalLoading" class="text-center p-3 text-muted"><div class="spinner-border spinner-border-sm mb-1" role="status"></div><div style="font-size:10px;">Loading...</div></div>
                                            <div x-show="!productModalLoading">
                                                <template x-if="selectedProductForModal && selectedProductForModal.attributes && selectedProductForModal.attributes.length > 0">
                                                    <table class="table table-sm table-hover mb-0" style="font-size:11px;">
                                                        <tbody>
                                                            <template x-for="attr in selectedProductForModal.attributes" :key="attr.id">
                                                                <tr><th class="ps-2 text-muted w-50 border-0" x-text="attr.attribute"></th><td class="pe-2 fw-semibold text-end border-0"><div class="d-flex align-items-center justify-content-end gap-1"><span x-show="attr.color_code" class="rounded-circle border" :style="'width:8px;height:8px;background-color:'+attr.color_code"></span><span x-text="attr.value"></span></div></td></tr>
                                                            </template>
                                                        </tbody>
                                                    </table>
                                                </template>
                                                <template x-if="!selectedProductForModal || !selectedProductForModal.attributes || selectedProductForModal.attributes.length === 0">
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
                                    <div x-show="productModalLoading" class="text-center p-3 text-muted"><div class="spinner-border spinner-border-sm" role="status"></div></div>
                                    <div x-show="!productModalLoading" class="row g-3">
                                        <div class="col-md-6 border-end border-secondary border-opacity-25">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase d-block" style="font-size:9px;">Product Description</label>
                                            <div style="font-size:11px;" x-show="selectedProductForModal && selectedProductForModal.description" x-html="selectedProductForModal ? selectedProductForModal.description : ''"></div>
                                            <div style="font-size:11px;" x-show="!selectedProductForModal || !selectedProductForModal.description" class="text-muted fst-italic">No description available.</div>
                                        </div>
                                        <div class="col-md-6 ps-3">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase d-block" style="font-size:9px;">Application / Dosage</label>
                                            <div style="font-size:11px;" x-show="selectedProductForModal && selectedProductForModal.application_instructions" x-html="selectedProductForModal ? selectedProductForModal.application_instructions : ''"></div>
                                            <div style="font-size:11px;" x-show="!selectedProductForModal || !selectedProductForModal.application_instructions" class="text-muted fst-italic">No instructions available.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Promotions -->
                            <div x-show="productModalOffers && productModalOffers.length > 0" class="card mb-3 border border-primary border-opacity-25 shadow-sm rounded-4" style="background-color:var(--bs-primary-bg-subtle);">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center gap-2 pb-2 mb-2 border-bottom border-primary border-opacity-25">
                                        <div class="bg-primary text-white rounded-2 d-flex align-items-center justify-content-center" style="width:24px;height:24px;"><i class="bi bi-stars" style="font-size:12px;"></i></div>
                                        <h6 class="mb-0 fw-bold text-uppercase text-primary" style="font-size:11px;letter-spacing:1px;">Available Promotions</h6>
                                    </div>
                                    <div class="row g-2">
                                        <template x-for="offer in productModalOffers" :key="'pmo-'+offer.id">
                                            <div class="col-md-6">
                                                <div class="d-flex align-items-center gap-2 p-2 rounded-3 border border-primary border-opacity-10 bg-body">
                                                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:28px;height:28px;"><i class="bi bi-tag-fill" style="font-size:12px;"></i></div>
                                                    <div class="min-w-0">
                                                        <div class="fw-bold text-body-emphasis text-truncate" style="font-size:11px;" x-text="offer.name"></div>
                                                        <div class="text-muted text-truncate" style="font-size:9px;" x-text="offer.type === 'percentage' ? offer.value + '% off' : 'Flat Rs ' + offer.value + ' off'"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
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
                <div class="modal-footer bg-body-tertiary border-top p-3 d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        <span x-show="selectedProductForModal && isInCart(selectedProductForModal.id)" class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i>Currently in cart</span>
                    </div>
                    <div>
                        <button type="button" class="btn btn-light me-2 fw-medium" data-bs-dismiss="modal">Close</button>
                        <button x-show="selectedProductForModal" type="button" class="btn btn-primary fw-bold px-4" @click="selectedProductForModal && addToCart(selectedProductForModal); bootstrap.Modal.getInstance(document.getElementById('productDetailsModal')).hide();">
                            <i class="bi" :class="selectedProductForModal && isInCart(selectedProductForModal.id) ? 'bi-cart-plus-fill' : 'bi-cart-plus'"></i>
                            <span x-text="selectedProductForModal && isInCart(selectedProductForModal.id) ? 'Add More' : 'Add to Cart'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

<style>
    /* Hide number input spin buttons */
    .no-spinners::-webkit-inner-spin-button,
    .no-spinners::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    .no-spinners {
        -moz-appearance: textfield;
    }

    /* Product Details Modal — two-column layout */
    #productDetailsModal .modal-body > .row {
        display: flex;
        flex-wrap: nowrap;
        align-items: flex-start;
    }
    #productDetailsModal .modal-body > .row > .col-md-4 {
        min-width: 300px;
        max-width: 300px;
    }
    #productDetailsModal .modal-body > .row > .col-md-8 {
        flex: 1;
        min-width: 0;
    }
</style>

@push('scripts')
@php
    $offersArray = $activeOffers->map(fn($o) => [
        'id' => $o->id,
        'name' => $o->name,
        'type' => $o->type,
        'discount_type' => $o->discount_type,
        'value' => (float)$o->value,
        'min_spend' => (float)$o->min_spend,
        'max_discount' => $o->max_discount ? (float)$o->max_discount : null,
        'product_id' => $o->product_id,
        'buy_qty' => (int)$o->buy_qty,
        'get_qty' => (int)$o->get_qty,
        'ends_at' => $o->ends_at,
        'priority' => (int)$o->priority,
        'product_name' => $o->product ? $o->product->name : 'Any Product'
    ])->values()->all();
@endphp
<script>
function createOrderApp(initialCustomer = null, initialOrder = null) {
    return {
        activeTab: 'customer',
        viewMode: 'table',
        partyId: new URLSearchParams(window.location.search).get('customer_id') || '', warehouseId: '{{ $warehouses->first()->id ?? '' }}', shippingAddressId: '', billingAddressId: '', sameAsShipping: true, orderType: 'sale',
        orderDate: (() => { const d = new Date(); const o = d.getTimezoneOffset() * 60000; return new Date(d - o).toISOString().slice(0, 19).replace('T', ' '); })(),
        isDraft: false, futureOrderDate: '',
        editingOrderId: null,
        editingOrderNo: null,
        addresses: [],
        recentOrders: [],
        products: [], productQuery: '', stockFilter: 'available', categoryFilter: '',
        searching: false, productPage: 1, productLastPage: 1, productTotal: 0, productFrom: 0, productTo: 0,
        cart: [], couponCode: '', couponApplied: false, appliedCouponObj: null, appliedOfferId: null,
        placing: false, formErrors: [],
        warehouses: @json($warehouses->map(fn($w) => ['id' => $w->id, 'name' => $w->name])),
        activeOffers: @json($offersArray),
        activeCoupons: @json($activeCoupons),
        couponInputTemp: '',

        selectedProductForModal: null,
        productModalOffers: [],
        productModalLoading: false,
        productModalTab: 'overview',

        customerDetails: initialCustomer || window.__INITIAL_ORDER_CUSTOMER__ || null,
        bottomTab: 'history',
        
        expandedOrderId: null,

        async init() {
            this.searchProducts();
            if (this.customerDetails) {
                this.addresses = this.customerDetails.addresses || [];
                this.recentOrders = this.customerDetails.orders || [];
                if (this.addresses.length) {
                    this.shippingAddressId = this.addresses.find(a => a.is_default)?.id || this.addresses[0].id;
                    this.billingAddressId = this.shippingAddressId;
                }
            }
            if (this.partyId) {
                await this.loadAddresses();
            }

            if (initialOrder) {
                this.applyOrderForEdit(initialOrder);
                localStorage.removeItem('metis_create_order_cart');
            } else {
                const saved = localStorage.getItem('metis_create_order_cart');
                if (saved) {
                    try {
                        this.cart = JSON.parse(saved);
                    } catch (e) {}
                }
            }

            this.$watch('cart', v => {
                localStorage.setItem('metis_create_order_cart', JSON.stringify(v));
                window.dispatchEvent(new CustomEvent('cart-updated'));
                if (v.length === 0) {
                    this.removeCoupon();
                    this.appliedOfferId = null;
                } else {
                    if (this.couponApplied) {
                        this.applyCoupon();
                    }
                    if (this.appliedOfferId && this.appliedOfferId !== 'none' && !this.availableOrderOffers.some(o => o.id === this.appliedOfferId)) {
                        this.appliedOfferId = null;
                    }
                    if (!this.appliedOfferId && this.availableOrderOffers.length > 0) {
                        this.appliedOfferId = this.availableOrderOffers[0].id;
                    }
                }
            });

            // Listen for notify events (e.g. address saved, profile updated)
            window.addEventListener('notify', (e) => {
                const { type = 'success', message = '' } = e.detail || {};
                const container = document.getElementById('toast-container');
                if (!container || !message) return;
                const iconMap = {
                    success: 'bi-check-circle-fill',
                    error:   'bi-x-circle-fill',
                    danger:  'bi-x-circle-fill',
                    warning: 'bi-exclamation-triangle-fill',
                    info:    'bi-info-circle-fill',
                };
                const bsType = type === 'error' ? 'danger' : type;
                const id = 'toast-' + Date.now();
                const el = document.createElement('div');
                el.id = id;
                el.className = `toast align-items-center text-bg-${bsType} border-0 show mb-2`;
                el.setAttribute('role', 'alert');
                el.setAttribute('aria-live', 'assertive');
                el.setAttribute('aria-atomic', 'true');
                el.innerHTML = `<div class="d-flex">
                    <div class="toast-body fw-semibold d-flex align-items-center gap-2">
                        <i class="bi ${iconMap[type] || iconMap.info} flex-shrink-0"></i>
                        <span>${message}</span>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>`;
                container.appendChild(el);
                setTimeout(() => { el.classList.remove('show'); setTimeout(() => el.remove(), 400); }, 4000);
            });

            // Listen to cart changes from header dropdown or other components
            window.addEventListener('cart-updated', () => {
                const updated = localStorage.getItem('metis_create_order_cart');
                if (updated) {
                    try {
                        const parsed = JSON.parse(updated);
                        if (JSON.stringify(this.cart) !== JSON.stringify(parsed)) {
                            this.cart = parsed;
                        }
                    } catch (e) {}
                } else if (this.cart.length > 0) {
                    this.cart = [];
                }
            });
        },

        async loadAddresses() {
            if (!this.partyId) { this.addresses = []; this.recentOrders = []; this.customerDetails = null; return; }
            try {
                const res = await fetch(`/customers/${this.partyId}`, { headers: {'Accept':'application/json','X-Requested-With':'XMLHttpRequest'} });
                const json = await res.json();
                this.customerDetails = json.data;
                this.addresses = json.data?.addresses || [];
                this.recentOrders = json.data?.orders || [];
                if (this.addresses.length) {
                    this.shippingAddressId = this.addresses.find(a=>a.is_default)?.id || this.addresses[0].id;
                    this.billingAddressId = this.shippingAddressId;
                }
            } catch(e) { console.error(e); }
        },

        async openProductModal(p) {
            this.selectedProductForModal = p;
            this.productModalTab = 'overview';
            this.productModalOffers = this.activeOffers.filter(o => o.product_id === p.id || o.product_id === null);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('productDetailsModal')).show();
            
            this.productModalLoading = true;
            try {
                const res = await fetch(`/products/${p.id}`, { headers: {'Accept':'application/json'} });
                const json = await res.json();
                if (json && json.data) {
                    this.selectedProductForModal = { ...p, ...json.data };
                }
            } catch(e) { console.error(e); }
            this.productModalLoading = false;
        },

        applyOrderForEdit(order) {
            if (!order) return;

            this.editingOrderId = order.id || null;
            this.editingOrderNo = order.order_no || order.orderNumber || null;
            this.partyId = order.party_id || this.partyId;
            this.orderType = order.type || this.orderType;
            this.warehouseId = order.warehouse_id || this.warehouseId;
            this.shippingAddressId = order.shipping_address_id || '';
            this.billingAddressId = order.billing_address_id || order.shipping_address_id || '';
            this.sameAsShipping = !order.billing_address_id || String(order.billing_address_id) === String(order.shipping_address_id);
            this.orderDate = order.order_date ? String(order.order_date).replace('T', ' ').substring(0, 19) : this.orderDate;
            this.isDraft = Boolean(order.is_draft);
            this.futureOrderDate = order.future_order_date ? String(order.future_order_date).substring(0, 10) : '';
            this.couponCode = order.coupon_code || '';
            this.appliedOfferId = order.applied_offer_id || null;
            this.customerDetails = order.party
                ? {
                    ...(this.customerDetails || {}),
                    ...order.party,
                    addresses: order.party.addresses || this.customerDetails?.addresses || [],
                }
                : this.customerDetails;
            this.addresses = order.party?.addresses || this.addresses;
            this.recentOrders = order.party?.orders || this.recentOrders;

            this.cart = (order.items || []).map(item => ({
                id: item.product_id || item.id,
                name: item.product?.name || item.product_name || 'Product',
                sku: item.product?.sku || item.sku || '',
                price: item.unit_price ?? item.price ?? 0,
                image_url: item.product?.image_url || (item.product?.image_path ? `/storage/${item.product.image_path}` : null),
                quantity: Number(item.quantity || 1),
                available: item.product?.available_stock ?? item.available ?? null,
                taxRate: Number(item.tax_rate || item.taxRate || 0),
                discountValue: Number(item.quantity || 1) > 0
                    ? Number(item.discount_amount || item.discountValue || 0) / Number(item.quantity || 1)
                    : 0,
                discountType: 'flat',
            }));

            if (this.cart.length > 0) {
                localStorage.setItem('metis_create_order_cart', JSON.stringify(this.cart));
            }
        },

        scrollToSection(sectionId) {
            const el = document.getElementById(sectionId);
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        },

        get customerDisplayName() {
            if (!this.customerDetails) {
                return this.partyId ? `Customer #${this.partyId}` : 'Selected customer';
            }
            const parts = [
                this.customerDetails.firstname || '',
                this.customerDetails.middlename || '',
                this.customerDetails.lastname || '',
            ].filter(Boolean);
            return parts.join(' ').replace(/\s+/g, ' ').trim() || this.customerDetails.company_name || this.customerDetails.name || `Customer #${this.partyId || ''}`;
        },

        get selectedWarehouseName() {
            const match = (this.warehouses || []).find(w => String(w.id) === String(this.warehouseId));
            return match ? match.name : 'Select warehouse';
        },

        get futureOrders() {
            return (this.recentOrders || []).filter(order => Boolean(order.is_draft) || order.lifecycle_status === 'future_order' || order.status_label === 'Future Order');
        },

        get customerTags() {
            const tags = this.customerDetails?.tags;
            if (Array.isArray(tags)) return tags.filter(Boolean);
            if (typeof tags === 'string' && tags.trim()) {
                return tags.split(',').map(tag => tag.trim()).filter(Boolean);
            }
            return [];
        },

        toggleOrderDetails(orderId) {
            this.expandedOrderId = this.expandedOrderId === orderId ? null : orderId;
        },

        addressSummary(address) {
            if (!address) return 'Select address';
            const parts = [
                address.label,
                address.address_line_1,
                address.address_line_2,
                address.city,
                address.state,
                address.pincode,
            ].filter(Boolean);
            return parts.join(', ');
        },

        availableServices(address) {
            const today = new Date().toISOString().slice(0, 10);

            return (address?.village?.services || []).filter(service => {
                const pivot = service.pivot || {};
                const isActive = service.is_active !== false && service.is_active !== 0;
                const isAvailable = pivot.is_available === true || pivot.is_available === 1 || pivot.is_available === '1';
                const hasStarted = !pivot.serviceable_from_date || pivot.serviceable_from_date <= today;
                const hasNotEnded = !pivot.serviceable_to_date || pivot.serviceable_to_date >= today;

                return isActive && isAvailable && hasStarted && hasNotEnded;
            });
        },

        get shippingAddressSummary() {
            return this.addressSummary(this.addresses.find(a => String(a.id) === String(this.shippingAddressId)));
        },

        get billingAddressSummary() {
            if (this.sameAsShipping) return 'Same as shipping';
            return this.addressSummary(this.addresses.find(a => String(a.id) === String(this.billingAddressId)));
        },

        

        async searchProducts(reset = false) {
            if (reset) this.productPage = 1;
            this.searching = true;
            try {
                const p = new URLSearchParams({ q: this.productQuery, stock: this.stockFilter, category: this.categoryFilter, perPage: 12, page: this.productPage });
                const res = await fetch(`/products-search-api?${p}`, { headers: {'Accept':'application/json','X-Requested-With':'XMLHttpRequest'} });
                const json = await res.json();
                this.products = (json.data || []).map(p => ({...p, _qty: 1, _disc: parseFloat(p.default_discount)||0}));
                this.productTotal = json.total||0; this.productFrom = json.from||0; this.productTo = json.to||0; this.productLastPage = json.last_page||1;
            } catch(e) { window.dispatchEvent(new CustomEvent('notify',{detail:{type:'error',message:'Failed to load products'}})); }
            finally { this.searching = false; }
        },

        isInCart(id) { return this.cart.some(i => i.id === id); },

        addToCart(p) {
            let qty = parseInt(p._qty)||1;
            const disc = parseFloat(p._disc)||0;
            if (qty <= 0) return;

            // Auto-BOGO Quantity Injection
            const bogos = this.activeOffers.filter(o => o.type === 'bogo');
            const match = bogos.find(o => !o.product_id || Number(o.product_id) === Number(p.id));
            if (match) {
                const buyQty = parseInt(match.buy_qty) || 1;
                const getQty = parseInt(match.get_qty) || 1;
                
                // Only auto-add if they added a multiple of buyQty
                if (qty % buyQty === 0) {
                    const cycles = qty / buyQty;
                    const bonusQty = cycles * getQty;
                    const minSpend = parseFloat(match.min_spend) || 0;
                    
                    // The gross subtotal check: (Current Subtotal) + (Price * The paid items they are adding)
                    // If they are adding exactly 'buyQty', they will pay for 'buyQty'.
                    // Note: If the item is already in cart, we should ideally check total qty, but 
                    // this simple Add-To-Cart injection is safe enough for initial clicks.
                    const estimatedSubtotal = this.subtotal + (parseFloat(p.selling_price) * qty);
                    
                    if (estimatedSubtotal >= minSpend) {
                        qty += bonusQty;
                        window.dispatchEvent(new CustomEvent('notify', { detail: { type: 'success', message: `BOGO Triggered: Added ${bonusQty} free item(s) automatically!` }}));
                    }
                }
            }

            const existing = this.cart.findIndex(i => i.id === p.id);
            if (existing >= 0) {
                if (p.available_stock !== null && p.available_stock !== undefined && this.cart[existing].quantity + qty > p.available_stock) {
                    window.dispatchEvent(new CustomEvent('notify',{detail:{type:'warning',message:'Cannot exceed available stock ('+p.available_stock+')'}}));
                    return;
                }
                this.cart[existing].quantity += qty;
            } else {
                if (p.available_stock !== null && p.available_stock !== undefined && qty > p.available_stock) {
                    window.dispatchEvent(new CustomEvent('notify',{detail:{type:'warning',message:'Cannot exceed available stock ('+p.available_stock+')'}}));
                    return;
                }
                this.cart.push({ id:p.id, name:p.name, sku:p.sku, price:p.selling_price, image_url:p.image_url, quantity:qty, available:p.available_stock, taxRate:parseFloat(p.tax_rate)||0, discountValue:disc, discountType:p.default_discount_type||'percent' });
            }
            window.dispatchEvent(new CustomEvent('notify',{detail:{type:'success',message:'Added '+p.name+' to cart'}}));
        },

        updateQty(idx, delta) {
            const item = this.cart[idx];
            if (!item) return;
            const newQty = item.quantity + delta;
            if (newQty <= 0) {
                this.cart.splice(idx,1);
            } else {
                if (item.available !== null && item.available !== undefined && newQty > item.available) {
                    window.dispatchEvent(new CustomEvent('notify',{detail:{type:'warning',message:'Cannot exceed available stock ('+item.available+')'}}));
                    return;
                }
                item.quantity = newQty;
            }
        },

        lineTotal(item) {
            const base = (parseFloat(item.price)||0) * (parseInt(item.quantity)||0);
            const disc = parseFloat(item.discountValue)||0;
            if (disc <= 0) return base;
            const d = ['flat','amount','fixed'].includes((item.discountType||'').toLowerCase())
                ? Math.min(disc * item.quantity, base)
                : Math.min(base * (disc/100), base);
            return Math.max(0, base - d);
        },

        get subtotal() { return this.cart.reduce((t,i) => t + this.lineTotal(i), 0); },
        get taxAmount() { return this.cart.reduce((t,i) => t + this.lineTotal(i) * ((parseFloat(i.taxRate)||0)/100), 0); },
        get bogoDiscount() {
            const bogos = this.activeOffers
                .filter(o=>o.type==='bogo')
                .sort((a,b)=>(b.priority - a.priority) || (a.id - b.id));
            return this.cart.reduce((t,item)=>{
                // Find matching product BOGO, otherwise fallback to global BOGO
                const match = bogos.find(o=> Number(o.product_id)===Number(item.id)) || bogos.find(o=> !o.product_id);
                if(!match) return t;
                
                // Enforce minimum spend (check against total subtotal)
                if ((parseFloat(match.min_spend) || 0) > this.subtotal) return t;

                const buyQty = parseInt(match.buy_qty)||1;
                const getQty = parseInt(match.get_qty)||1;
                const cycle = buyQty + getQty;
                const qty = parseInt(item.quantity)||0;
                if(qty<cycle) return t;
                const free = Math.floor(qty/cycle)*getQty;
                const eff = qty>0 ? this.lineTotal(item)/qty : 0;
                return t + Math.min(eff*free, this.lineTotal(item));
            },0);
        },
        get appliedBogoIds() {
            const bogos = this.activeOffers
                .filter(o=>o.type==='bogo')
                .sort((a,b)=>(b.priority - a.priority) || (a.id - b.id));
            const ids = [];
            this.cart.forEach(item => {
                const match = bogos.find(o=> Number(o.product_id)===Number(item.id)) || bogos.find(o=> !o.product_id);
                if (!match) return;
                
                if ((parseFloat(match.min_spend) || 0) > this.subtotal) return;

                const buyQty = parseInt(match.buy_qty)||1;
                const getQty = parseInt(match.get_qty)||1;
                const cycle = buyQty + getQty;
                const qty = parseInt(item.quantity)||0;
                if (qty >= cycle) {
                    ids.push(match.id);
                }
            });
            return [...new Set(ids)];
        },
        get sortedActiveOffers() {
            return (this.activeOffers || [])
                .map(offer => ({ ...offer }))
                .sort((a, b) => (b.priority - a.priority) || (a.id - b.id));
        },
        get activeOrderOffers() {
            return (this.activeOffers || []).filter(o => o.type === 'order_discount');
        },
        orderOfferDiscount(offer) {
            if (!offer || this.subtotal <= 0) return 0;
            if ((parseFloat(offer.min_spend) || 0) > this.subtotal) return 0;
            let eligibleSubtotal = this.subtotal;
            if (offer.product_id) {
                eligibleSubtotal = this.cart.reduce((t, item) => {
                    if (item.id == offer.product_id) {
                        return t + this.lineTotal(item);
                    }
                    return t;
                }, 0);
            }
            if (eligibleSubtotal <= 0) return 0;
            let discount = String(offer.discount_type) === 'percentage'
                ? eligibleSubtotal * ((parseFloat(offer.value) || 0) / 100)
                : (parseFloat(offer.value) || 0);
            if ((parseFloat(offer.max_discount) || 0) > 0) {
                discount = Math.min(discount, parseFloat(offer.max_discount) || 0);
            }
            return Math.min(discount, eligibleSubtotal);
        },
        get availableOrderOffers() {
            return this.activeOrderOffers
                .map(offer => ({ ...offer, computed_discount: this.orderOfferDiscount(offer) }))
                .filter(offer => offer.computed_discount > 0)
                .sort((a, b) => (b.priority - a.priority) || (b.computed_discount - a.computed_discount) || (a.id - b.id));
        },
        get bestOrderOffer() {
            if (!this.appliedOfferId || this.appliedOfferId === 'none') return null;
            return this.availableOrderOffers.find(o => o.id === this.appliedOfferId) || null;
        },
        get orderOfferDiscountAmount() {
            const best = this.bestOrderOffer;
            return best ? best.computed_discount : 0;
        },
        get couponDiscount() {
            if (!this.couponApplied || !this.appliedCouponObj) return 0;
            const c = this.appliedCouponObj;
            if ((parseFloat(c.min_spend) || 0) > this.subtotal) return 0;
            let d = c.type === 'percentage' ? this.subtotal * (parseFloat(c.value) / 100) : parseFloat(c.value);
            if ((parseFloat(c.max_discount) || 0) > 0) d = Math.min(d, parseFloat(c.max_discount));
            return Math.min(d, this.subtotal);
        },
        get totalDiscount() { return Math.min(this.subtotal, this.bogoDiscount + this.couponDiscount + this.orderOfferDiscountAmount); },
        get grandTotal() { return Math.max(0, this.subtotal - this.totalDiscount + this.taxAmount); },

        async applyCoupon(codeToApply = null) {
            const code = (codeToApply || this.couponInputTemp || this.couponCode).toUpperCase().trim();
            if (!code) return;
            const res = await fetch('/coupons/validate', {method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content,'Accept':'application/json'}, body:JSON.stringify({code,subtotal:this.subtotal})});
            const json = await res.json();
            if (json.valid) { 
                this.couponCode = code;
                this.appliedCouponObj = json.coupon;
                this.couponApplied = true; 
                this.couponInputTemp = ''; // clear temp
                bootstrap.Modal.getInstance(document.getElementById('promotionsModal'))?.hide();
            } else { 
                this.appliedCouponObj = null;
                this.couponApplied = false; 
            }
            window.dispatchEvent(new CustomEvent('notify',{detail:{type:json.valid?'success':'error',message:json.message}}));
        },

        removeCoupon() { this.couponCode=''; this.appliedCouponObj=null; this.couponApplied=false; },

        buildCartPayload() {
            return this.cart.map(item => {
                const base = (parseFloat(item.price)||0) * (parseInt(item.quantity)||0);
                const disc = this.lineTotal(item) < base ? base - this.lineTotal(item) : 0;
                const tax = this.lineTotal(item) * ((parseFloat(item.taxRate)||0)/100);
                return { product_id: item.id, quantity: item.quantity, unit_price: item.price, discount_amount: parseFloat(disc.toFixed(2)), tax_amount: parseFloat(tax.toFixed(2)), total_amount: parseFloat(this.lineTotal(item).toFixed(2)) };
            });
        },

        async placeOrder() {
            this.formErrors = [];
            if (!this.partyId) { this.formErrors.push('Please select a customer.'); return; }
            if (!this.warehouseId) { this.formErrors.push('Please select a warehouse.'); return; }
            if (!this.shippingAddressId) { this.formErrors.push('Please select a shipping address.'); return; }
            if (!this.sameAsShipping && !this.billingAddressId) { this.formErrors.push('Please select a billing address.'); return; }
            if (this.cart.length === 0) { this.formErrors.push('Cart is empty.'); return; }
            if (this.isDraft && !this.futureOrderDate) { this.formErrors.push('Please set future order date.'); return; }

            this.placing = true;
            try {
                const payload = {
                    type: this.orderType,
                    party_id: this.partyId,
                    warehouse_id: this.warehouseId,
                    shipping_address_id: this.shippingAddressId || null,
                    billing_address_id: this.sameAsShipping ? (this.shippingAddressId || null) : (this.billingAddressId || null),
                    order_date: this.orderDate,
                    items: this.buildCartPayload(),
                    is_draft: this.isDraft ? 1 : 0,
                    future_order_date: this.isDraft ? this.futureOrderDate : null,
                    coupon_code: this.couponApplied ? this.couponCode : null,
                    applied_offer_id: (this.appliedOfferId && this.appliedOfferId !== 'none') ? this.appliedOfferId : null,
                    applied_bogo_ids: this.appliedBogoIds,
                    total_amount: parseFloat(this.subtotal.toFixed(2)),
                    tax_amount: parseFloat(this.taxAmount.toFixed(2)),
                    discount_amount: parseFloat(this.totalDiscount.toFixed(2)),
                    net_amount: parseFloat(this.grandTotal.toFixed(2)),
                };
                const url = this.editingOrderId ? `/orders/${this.editingOrderId}` : '/orders';
                const res = await fetch(url, { method: this.editingOrderId ? 'PUT' : 'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content,'Accept':'application/json'}, body:JSON.stringify(payload) });
                const json = await res.json();
                if (!res.ok) {
                    this.formErrors = Object.values(json.errors||{}).flat();
                    if (!this.formErrors.length && json.message) this.formErrors.push(json.message);
                    return;
                }
                localStorage.removeItem('metis_create_order_cart');
                this.cart = [];
                const successMessage = this.editingOrderId ? 'Order updated successfully!' : 'Order placed successfully!';
                window.dispatchEvent(new CustomEvent('notify',{detail:{type:'success',message:successMessage}}));
                setTimeout(() => { window.location.href = '/orders?success=' + encodeURIComponent(successMessage); }, 800);
            } catch(e) { this.formErrors.push('An unexpected error occurred.'); }
            finally { this.placing = false; }
        },
    };
}
</script>
@endpush
<x-customer-address-modal />

@endsection
