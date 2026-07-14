<?php $__env->startSection('title', 'Create Order'); ?>
<?php $__env->startSection('page', 'orders.create'); ?>

<?php $__env->startPush('head'); ?>
<style>
    .input-step {
        display: inline-flex;
        border: 1px solid var(--bs-border-color);
        border-radius: 0.375rem;
        overflow: hidden;
        background: var(--bs-body-bg);
    }
    .input-step button {
        width: 32px;
        height: 32px;
        border: none;
        background: var(--bs-tertiary-bg);
        color: var(--bs-body-color);
        font-size: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }
    .input-step button:hover {
        background: var(--bs-secondary-bg);
    }
    .input-step input {
        width: 50px;
        height: 32px;
        border: none;
        border-left: 1px solid var(--bs-border-color);
        border-right: 1px solid var(--bs-border-color);
        text-align: center;
        font-weight: 600;
        background: transparent;
    }
    .cart-item-card {
        transition: all 0.2s;
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid p-4 bg-light bg-gradient" style="min-height: 100vh;" x-data="createOrderApp()">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 fw-bold text-dark"><i class="bi bi-cart-check-fill me-2 text-primary" style="filter: drop-shadow(0 2px 4px rgba(var(--bs-primary-rgb), 0.4));"></i>Create New Order</h1>
            <p class="text-muted mb-0 small mt-1">Search products, build cart, select customer and place order</p>
        </div>
        <a href="<?php echo e(route('orders')); ?>" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm hover-shadow transition-all">
            <i class="bi bi-arrow-left me-2"></i> Back to Orders
        </a>
    </div>

    
    <?php if(session('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm border-0 bg-danger bg-opacity-10 text-danger-emphasis">
        <i class="bi bi-exclamation-circle-fill me-2"></i><?php echo e(session('error')); ?>

        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div @customer-updated.window="loadAddresses()" class="row g-4">
        
        <div class="col-xl-8">

            
            <div class="card mb-4 border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-primary bg-opacity-10 border-bottom-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-primary-emphasis fs-5"><i class="bi bi-person-badge me-2 text-primary"></i>Customer & Order Details</h5>
                    <div x-show="customerDetails" class="badge bg-primary bg-opacity-25 text-primary-emphasis rounded-pill px-3 py-2 fw-medium shadow-sm transition-all" x-cloak>
                        <i class="bi bi-check-circle-fill me-1"></i> Customer Selected
                    </div>
                </div>
                <div class="card-body p-4">
                    
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                           <label class="form-label fw-bold small text-uppercase text-muted"><i class="bi bi-person me-1"></i>Customer <span class="text-danger">*</span></label>
                           <div class="form-control bg-body-secondary fw-bold rounded-3 shadow-sm border-0 d-flex align-items-center" style="height: 42px;">
                               <template x-if="customerDetails">
                                   <span class="text-body" x-text="`${customerDetails.firstname || ''} ${customerDetails.middlename ? customerDetails.middlename + ' ' : ''}${customerDetails.lastname || ''}`.replace(/\s+/g, ' ').trim()"></span>
                               </template>
                               <template x-if="!customerDetails">
                                   <span class="text-muted fw-normal">Loading customer details...</span>
                               </template>
                           </div>
                        </div>
                        <div class="col-md-6">
                           <label class="form-label fw-bold small text-uppercase text-muted"><i class="bi bi-shop me-1"></i>Warehouse <span class="text-danger">*</span></label>
                           <select class="form-select fw-bold rounded-3 shadow-sm border-0 bg-body-secondary" style="height: 42px;" x-model="warehouseId">
                               <option value="">Select Warehouse</option>
                               <?php $__currentLoopData = $warehouses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $w): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                               <option value="<?php echo e($w->id); ?>"><?php echo e($w->name); ?></option>
                               <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                           </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-uppercase text-muted">Future Order?</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="isDraft" x-model="isDraft" style="cursor: pointer;">
                                <label class="form-check-label small fw-medium" for="isDraft" style="cursor: pointer;">Schedule for future</label>
                            </div>
                            <div x-show="isDraft" x-cloak class="mt-2 transition-all">
                                <input type="date" class="form-control form-control-sm rounded-3 shadow-sm border-0 bg-body-secondary fw-bold" style="height: 38px;" x-model="futureOrderDate" placeholder="Future date">
                            </div>
                        </div>
                    </div>

                    
                    <div x-show="customerDetails" x-cloak class="pt-3 border-top transition-all">
                        <div class="d-flex justify-content-between align-items-center mb-4 mt-2">
                            <h6 class="fw-bold mb-0 text-body fs-5"><i class="bi bi-person-lines-fill me-2 text-secondary"></i>Customer Profile</h6>
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-4 shadow-sm hover-shadow transition-all" @click="$dispatch('open-add-customer-modal', {customer: customerDetails})">
                                <i class="bi bi-pencil-square me-2"></i>Edit Profile
                            </button>
                        </div>

                        <div class="row g-4">
                            
                            <div class="col-lg-4">
                                <div class="bg-body-secondary rounded-4 p-4 h-100 border border-secondary border-opacity-10 transition-all hover-shadow" style="transform: translateY(0); transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                                    <div class="d-flex align-items-center gap-3 mb-4">
                                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center fw-bold fs-3 shadow-sm" style="width: 56px; height: 56px;" x-text="customerDetails.firstname ? customerDetails.firstname.charAt(0) : '?'"></div>
                                        <div>
                                            <h6 class="mb-0 fw-bold fs-5 text-body" x-text="`${customerDetails.firstname || ''} ${customerDetails.middlename ? customerDetails.middlename + ' ' : ''}${customerDetails.lastname || ''}`.replace(/\s+/g, ' ').trim()"></h6>
                                            <span class="badge bg-success bg-opacity-25 text-success-emphasis rounded-pill mt-2 px-3 py-1 shadow-sm fw-medium" x-text="customerDetails.status || 'Active'"></span>
                                        </div>
                                    </div>
                                    <ul class="list-unstyled mb-0 small">
                                        <li class="mb-3 d-flex align-items-center gap-2"><i class="bi bi-telephone text-muted fs-6"></i><span class="fw-medium text-body" x-text="customerDetails.phone || 'N/A'"></span></li>
                                        <li class="mb-3 d-flex align-items-center gap-2"><i class="bi bi-envelope text-muted fs-6"></i><span class="fw-medium text-body text-break" x-text="customerDetails.email || 'N/A'"></span></li>
                                        <li class="mb-3 d-flex align-items-center gap-2" x-show="customerDetails.company_name"><i class="bi bi-building text-muted fs-6"></i><span class="fw-medium text-body" x-text="customerDetails.company_name"></span></li>
                                        <li class="d-flex align-items-center gap-2" x-show="customerDetails.gst_no"><i class="bi bi-receipt text-muted fs-6"></i>GST: <span class="fw-medium text-body text-uppercase" x-text="customerDetails.gst_no"></span></li>
                                    </ul>
                                </div>
                            </div>
                            
                            
                            <div class="col-lg-4">
                                <div class="bg-warning bg-opacity-10 rounded-4 p-4 h-100 border border-warning border-opacity-25 transition-all hover-shadow" style="transform: translateY(0); transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                                    <h6 class="fw-bold mb-4 text-warning-emphasis fs-6"><i class="bi bi-sun me-2"></i>Agriculture Profile</h6>
                                    <div class="row g-3 small">
                                        <div class="col-6">
                                            <div class="text-muted mb-1" style="font-size:10px;text-transform:uppercase;letter-spacing:1.5px;font-weight:700;">Land Area</div>
                                            <div class="fw-black fs-5 text-body"><span x-text="customerDetails.land_area || '0'"></span> <span class="fs-6 fw-medium text-muted" x-text="customerDetails.land_unit || ''"></span></div>
                                        </div>
                                        <div class="col-12 mt-3" x-show="customerDetails.crops && customerDetails.crops.length > 0">
                                            <div class="text-muted mb-2" style="font-size:10px;text-transform:uppercase;letter-spacing:1.5px;font-weight:700;">Major Crops</div>
                                            <div class="d-flex flex-wrap gap-2">
                                                <template x-for="crop in customerDetails.crops">
                                                    <span class="badge bg-success bg-opacity-25 text-success-emphasis rounded-pill px-2 py-1 shadow-sm fw-medium" x-text="crop"></span>
                                                </template>
                                            </div>
                                        </div>
                                        <div class="col-12 mt-3" x-show="customerDetails.irrigation_type && customerDetails.irrigation_type.length > 0">
                                            <div class="text-muted mb-2" style="font-size:10px;text-transform:uppercase;letter-spacing:1.5px;font-weight:700;">Irrigation</div>
                                            <div class="d-flex flex-wrap gap-2">
                                                <template x-for="type in customerDetails.irrigation_type">
                                                    <span class="badge bg-info bg-opacity-25 text-info-emphasis rounded-pill px-2 py-1 shadow-sm fw-medium" x-text="type"></span>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            
                            <div class="col-lg-4">
                                <div class="bg-danger bg-opacity-10 rounded-4 p-4 h-100 border border-danger border-opacity-25 transition-all hover-shadow" style="transform: translateY(0); transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                                    <h6 class="fw-bold mb-4 text-danger-emphasis fs-6"><i class="bi bi-wallet2 me-2"></i>Financial Terms</h6>
                                    <div class="row g-3 small">
                                        <div class="col-6">
                                            <div class="text-muted mb-1" style="font-size:10px;text-transform:uppercase;letter-spacing:1.5px;font-weight:700;">Credit Limit</div>
                                            <div class="fw-black fs-5 text-body">₹<span x-text="Number(customerDetails.credit_limit || 0).toFixed(2)"></span></div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-muted mb-1" style="font-size:10px;text-transform:uppercase;letter-spacing:1.5px;font-weight:700;">Current Balance</div>
                                            <div class="fw-black fs-5" :class="Number(customerDetails.outstanding_balance) > 0 ? 'text-danger' : 'text-success'">₹<span x-text="Number(customerDetails.outstanding_balance || 0).toFixed(2)"></span></div>
                                        </div>
                                        <div class="col-6 mt-3">
                                            <div class="text-muted mb-1" style="font-size:10px;text-transform:uppercase;letter-spacing:1.5px;font-weight:700;">Credit Days</div>
                                            <div class="fw-bold text-body fs-6"><span x-text="customerDetails.credit_days || '0'"></span> Days</div>
                                        </div>
                                        <div class="col-6 mt-3" x-show="customerDetails.credit_valid_till">
                                            <div class="text-muted mb-1" style="font-size:10px;text-transform:uppercase;letter-spacing:1.5px;font-weight:700;">Valid Till</div>
                                            <div class="fw-bold text-body fs-6" x-text="new Date(customerDetails.credit_valid_till).toLocaleDateString()"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    
                    <div x-show="partyId" x-cloak class="mt-4 pt-4 border-top transition-all">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h6 class="fw-bold mb-0 text-body fs-5"><i class="bi bi-geo-alt-fill me-2 text-primary"></i>Shipping Addresses</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-4 shadow-sm hover-shadow transition-all" @click="$dispatch('open-address-modal', {customerId: partyId})">
                                <i class="bi bi-plus-lg me-2"></i>New Address
                            </button>
                        </div>
                        
                        <div class="row g-4">
                            <template x-for="addr in addresses" :key="addr.id">
                                    <div class="col-md-6 col-lg-4">
                                        <div class="w-100 h-100 cursor-pointer" style="display:block;" @click="shippingAddressId = addr.id">
                                            <div class="card h-100 border-2 rounded-4 transition-all" :class="shippingAddressId == addr.id ? 'border-primary bg-primary bg-opacity-10 shadow-md' : 'border-secondary border-opacity-10 bg-body-tertiary hover-shadow'" style="transform: translateY(0); transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                                                <div class="card-body p-4 position-relative">
                                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                                        <div>
                                                            <span class="badge bg-secondary bg-opacity-25 text-secondary-emphasis rounded-pill me-2 px-3 py-1 shadow-sm fw-medium" x-text="addr.label || 'Address'"></span>
                                                            <span x-show="addr.is_default" class="badge bg-success bg-opacity-25 text-success-emphasis rounded-pill px-3 py-1 shadow-sm fw-medium"><i class="bi bi-star-fill me-1"></i>Default</span>
                                                        </div>
                                                        <button type="button" class="btn btn-light btn-sm rounded-circle shadow-sm position-absolute d-flex align-items-center justify-content-center" style="top: 16px; right: 16px; width: 32px; height: 32px; z-index: 20; border: 1px solid rgba(0,0,0,0.05);" @click.stop.prevent="$dispatch('open-address-modal', {customerId: partyId, address: addr})">
                                                            <i class="bi bi-pencil text-primary"></i>
                                                        </button>
                                                    </div>
                                                    <p class="mb-2 small fw-bold text-body fs-6" x-text="addr.address_line_1"></p>
                                                    <p class="mb-2 small text-muted" x-show="addr.address_line_2" x-text="addr.address_line_2"></p>
                                                    <p class="mb-2 small text-muted" x-show="addr.village" x-text="[addr.village?.village_name ? 'Vill: '+addr.village?.village_name : null, addr.village?.post_so_name ? 'PO: '+addr.village?.post_so_name : null, addr.village?.taluka_name ? 'Ta: '+addr.village?.taluka_name : null, addr.village?.district_name ? 'Dist: '+addr.village?.district_name : null].filter(Boolean).join(', ')"></p>
                                                    <p class="mb-0 small text-muted fw-medium">
                                                        <span x-show="addr.city" x-text="addr.city + ', '"></span>
                                                        <span x-show="addr.state" x-text="addr.state"></span>
                                                        <span class="text-body" x-show="addr.pincode" x-text="'- ' + addr.pincode"></span>
                                                    </p>
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

                        
                        <div class="mt-4 form-check form-switch cursor-pointer d-flex align-items-center gap-2">
                            <input class="form-check-input mt-0" type="checkbox" id="sameAsShippingToggle" x-model="sameAsShipping" style="cursor: pointer; width: 40px; height: 20px;">
                            <label class="form-check-label small fw-bold text-muted text-uppercase mt-1" for="sameAsShippingToggle" style="cursor: pointer; font-size: 11px; letter-spacing: 1px;">Billing address same as Shipping address</label>
                        </div>

                        
                        <div x-show="!sameAsShipping" x-cloak class="mt-4 pt-4 border-top transition-all">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h6 class="fw-bold mb-0 text-body fs-5"><i class="bi bi-receipt me-2 text-primary"></i>Billing Addresses</h6>
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-4 shadow-sm hover-shadow transition-all" @click="$dispatch('open-address-modal', {customerId: partyId})">
                                    <i class="bi bi-plus-lg me-2"></i>New Address
                                </button>
                            </div>
                            
                            <div class="row g-4">
                                <template x-for="addr in addresses" :key="addr.id">
                                    <div class="col-md-6 col-lg-4">
                                        <div class="w-100 h-100 cursor-pointer" style="display:block;" @click="billingAddressId = addr.id">
                                            <div class="card h-100 border-2 rounded-4 transition-all" :class="billingAddressId == addr.id ? 'border-primary bg-primary bg-opacity-10 shadow-md' : 'border-secondary border-opacity-10 bg-body-tertiary hover-shadow'" style="transform: translateY(0); transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                                                <div class="card-body p-4 position-relative">
                                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                                        <div>
                                                            <span class="badge bg-secondary bg-opacity-25 text-secondary-emphasis rounded-pill me-2 px-3 py-1 shadow-sm fw-medium" x-text="addr.label || 'Address'"></span>
                                                            <span x-show="addr.is_default" class="badge bg-success bg-opacity-25 text-success-emphasis rounded-pill px-3 py-1 shadow-sm fw-medium"><i class="bi bi-star-fill me-1"></i>Default</span>
                                                        </div>
                                                        <button type="button" class="btn btn-light btn-sm rounded-circle shadow-sm position-absolute d-flex align-items-center justify-content-center" style="top: 16px; right: 16px; width: 32px; height: 32px; z-index: 20; border: 1px solid rgba(0,0,0,0.05);" @click.stop.prevent="$dispatch('open-address-modal', {customerId: partyId, address: addr})">
                                                            <i class="bi bi-pencil text-primary"></i>
                                                        </button>
                                                    </div>
                                                    <p class="mb-2 small fw-bold text-body fs-6" x-text="addr.address_line_1"></p>
                                                    <p class="mb-2 small text-muted" x-show="addr.address_line_2" x-text="addr.address_line_2"></p>
                                                    <p class="mb-2 small text-muted" x-show="addr.village" x-text="[addr.village?.village_name ? 'Vill: '+addr.village?.village_name : null, addr.village?.post_so_name ? 'PO: '+addr.village?.post_so_name : null, addr.village?.taluka_name ? 'Ta: '+addr.village?.taluka_name : null, addr.village?.district_name ? 'Dist: '+addr.village?.district_name : null].filter(Boolean).join(', ')"></p>
                                                    <p class="mb-0 small text-muted fw-medium">
                                                        <span x-show="addr.city" x-text="addr.city + ', '"></span>
                                                        <span x-show="addr.state" x-text="addr.state"></span>
                                                        <span class="text-body" x-show="addr.pincode" x-text="'- ' + addr.pincode"></span>
                                                    </p>
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
                </div>
            </div>

            
            <div class="card mb-4 border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-info bg-opacity-10 border-bottom-0 py-3">
                    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
                        <span class="fw-bold text-info-emphasis fs-5"><i class="bi bi-search me-2 text-info"></i>Product Catalog</span>
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
                                <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($cat->id); ?>"><?php echo e($cat->name); ?></option>
                                <?php $__currentLoopData = $cat->children; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $child): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($child->id); ?>">— <?php echo e($child->name); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    
                    <template x-if="searching">
                        <div class="text-center py-5"><div class="spinner-border text-primary"></div></div>
                    </template>
                    
                    <template x-if="!searching && products.length === 0">
                        <div class="text-center py-5 text-muted"><i class="bi bi-box-seam fs-1 d-block mb-2"></i>No products found</div>
                    </template>
                    
                    <div class="p-3" x-show="!searching && products.length > 0">
                        
                        <div class="row g-3" x-show="viewMode === 'grid'">
                            <template x-for="p in products" :key="p.id">
                                <div class="col-sm-6 col-md-4">
                                    <div class="card border-0 shadow-sm h-100 rounded-4 transition-all hover-shadow" :class="{'border-primary bg-primary bg-opacity-10 border-2': isInCart(p.id), 'bg-body': !isInCart(p.id)}" style="transform: translateY(0); transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
                                        <div class="card-body p-3">
                                            <div class="d-flex gap-3 mb-3">
                                                <div class="position-relative">
                                                    <img :src="p.image_url || '/assets/images/product-placeholder.svg'" class="rounded-3 border bg-white shadow-sm" style="width:60px;height:60px;object-fit:cover;flex-shrink:0" x-on:error="$el.src='/assets/images/product-placeholder.svg'">
                                                    <div x-show="isInCart(p.id)" class="position-absolute top-0 start-100 translate-middle p-1 bg-success border border-light rounded-circle text-white d-flex align-items-center justify-content-center shadow" style="width: 20px; height: 20px; font-size: 10px;">
                                                        <i class="bi bi-check"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1" style="min-width: 0;">
                                                    <div class="fw-bold text-truncate text-body" :title="p.name" x-text="p.name"></div>
                                                    <div class="text-muted text-truncate font-monospace mt-1" style="font-size:11px; letter-spacing: 0.5px;" x-text="p.sku"></div>
                                                </div>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mb-3 px-2 py-1 bg-body-secondary rounded-3">
                                                <span class="fw-black text-primary fs-5" x-text="'₹' + parseFloat(p.selling_price).toFixed(2)"></span>
                                                <span class="badge rounded-pill fw-medium" :class="p.available_stock > 10 ? 'bg-success bg-opacity-25 text-success-emphasis' : (p.available_stock > 0 ? 'bg-warning bg-opacity-25 text-warning-emphasis' : 'bg-danger bg-opacity-25 text-danger-emphasis')" x-text="'Stock: ' + p.available_stock"></span>
                                            </div>
                                            <div class="row g-2">
                                                <div class="col-5">
                                                    <div class="form-floating">
                                                        <input type="number" class="form-control form-control-sm text-center fw-bold" style="height: 42px; min-height: 42px;" x-model.number="p._qty" min="1" :max="p.available_stock || 9999" placeholder="Qty">
                                                        <label class="text-muted" style="padding-top: 0.5rem; padding-bottom: 0.5rem; font-size: 0.75rem;">Qty</label>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="form-floating">
                                                        <input type="number" class="form-control form-control-sm text-center fw-bold text-success" style="height: 42px; min-height: 42px;" x-model.number="p._disc" min="0" placeholder="Disc%">
                                                        <label class="text-muted" style="padding-top: 0.5rem; padding-bottom: 0.5rem; font-size: 0.75rem;">Disc%</label>
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <button class="btn btn-sm w-100 h-100 rounded-3 shadow-sm d-flex align-items-center justify-content-center transition-all" :class="isInCart(p.id) ? 'btn-primary' : 'btn-outline-primary'" @click="addToCart(p)" title="Add to cart">
                                                        <i class="bi fs-5" :class="isInCart(p.id) ? 'bi-plus-circle-fill' : 'bi-cart-plus'"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                        
                        
                        <div class="table-responsive" x-show="viewMode === 'table'" style="display: none;">
                            <table class="table table-hover align-middle mb-0 border">
                                <thead class="table-secondary">
                                    <tr>
                                        <th>Product</th>
                                        <th class="text-center">Stock</th>
                                        <th class="text-end">Price</th>
                                        <th class="text-center" style="width: 100px;">Qty</th>
                                        <th class="text-center" style="width: 90px;">Disc%</th>
                                        <th class="text-center" style="width: 60px;">Add</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="p in products" :key="'tbl-'+p.id">
                                        <tr :class="{'table-primary': isInCart(p.id)}">
                                            <td>
                                                <div class="d-flex align-items-center gap-2" style="min-width: 0;">
                                                    <img :src="p.image_url || '/assets/images/product-placeholder.svg'" class="rounded border bg-body-tertiary" style="width:36px;height:36px;object-fit:cover;flex-shrink:0" x-on:error="$el.src='/assets/images/product-placeholder.svg'">
                                                    <div style="min-width: 0; flex-grow: 1;">
                                                        <div class="fw-semibold small text-truncate" :title="p.name" x-text="p.name"></div>
                                                        <div class="text-muted text-truncate" style="font-size:11px" x-text="p.sku"></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge" :class="p.available_stock > 10 ? 'bg-success' : (p.available_stock > 0 ? 'bg-warning text-body' : 'bg-danger')" x-text="p.available_stock"></span>
                                            </td>
                                            <td class="text-end fw-bold text-primary" x-text="'₹' + parseFloat(p.selling_price).toFixed(2)"></td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm text-center" x-model.number="p._qty" min="1" :max="p.available_stock || 9999" placeholder="Qty">
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm text-center" x-model.number="p._disc" min="0" placeholder="Disc%">
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-sm w-100" :class="isInCart(p.id) ? 'btn-primary' : 'btn-outline-primary'" @click="addToCart(p)" title="Add to cart">
                                                    <i class="bi" :class="isInCart(p.id) ? 'bi-plus-circle-fill' : 'bi-cart-plus'"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center px-3 pb-3 border-top pt-3" x-show="productTotal > 0">
                        <small class="text-muted"><span x-text="productFrom"></span>–<span x-text="productTo"></span> of <span x-text="productTotal"></span></small>
                        <div class="d-flex gap-1">
                            <button type="button" class="btn btn-sm btn-outline-secondary" @click="productPage--; searchProducts()" :disabled="productPage <= 1"><i class="bi bi-chevron-left"></i></button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" @click="productPage++; searchProducts()" :disabled="productPage >= productLastPage"><i class="bi bi-chevron-right"></i></button>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="row align-items-center gy-3 mb-4 mt-2">
                <div class="col-sm">
                    <h4 class="mb-0 fw-black text-body d-flex align-items-center gap-2"><div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;"><i class="bi bi-cart3"></i></div> Shopping Cart (<span x-text="cart.length" class="text-primary"></span>)</h4>
                </div>
                <div class="col-sm-auto" x-show="cart.length > 0">
                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 shadow-sm hover-shadow" @click="cart = []">
                        <i class="bi bi-trash3 me-1"></i> Clear Cart
                    </button>
                </div>
            </div>

            
            <div class="mb-4 space-y-3">
                
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

                
                <template x-for="(item, idx) in cart" :key="item.id">
                    <div class="card border-0 shadow-sm rounded-4 mb-3 overflow-hidden group">
                        <div class="d-flex align-items-start gap-3 p-3">
                            <div class="rounded-3 bg-body-tertiary border flex-shrink-0 d-flex align-items-center justify-content-center overflow-hidden" style="width: 70px; height: 70px;">
                                <template x-if="item.image_url">
                                    <img :src="item.image_url" class="w-100 h-100 object-fit-cover" x-on:error="$el.src='/assets/images/product-placeholder.svg'">
                                </template>
                                <template x-if="!item.image_url">
                                    <i class="bi bi-box fs-3 text-muted opacity-50"></i>
                                </template>
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
                                    <span class="text-muted fw-medium" style="font-size: 12px;" x-text="'₹' + Number(item.price).toFixed(2) + ' × ' + item.quantity"></span>
                                    <span class="fw-bold text-success fs-6" x-text="'₹' + Number(lineTotal(item)).toFixed(2)"></span>
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
                                        <span class="fw-bold" style="font-size: 11px;" x-text="(item.discountType === 'flat' ? '₹' : '') + Number(item.discountValue).toFixed(item.discountValue % 1 === 0 ? 0 : 2) + (item.discountType === 'flat' ? ' off' : '% off')"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        
        <div class="col-xl-4">
            <div class="sticky-side-div" style="position: sticky; top: 24px;">
                <div class="card mb-4 border-0 shadow-sm rounded-4 overflow-hidden" x-show="cart.length > 0" x-cloak>
                    <div class="card-body p-4 space-y-4">
                        
                        
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <p class="mb-0 fw-bold text-muted text-uppercase tracking-widest d-flex align-items-center gap-2" style="font-size: 10px;">
                                <i class="bi bi-tag-fill text-primary"></i> Backend Offers
                            </p>
                            <template x-if="availableOrderOffers.length > 0">
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill" style="font-size: 9px;" x-text="availableOrderOffers.length + ' available'"></span>
                            </template>
                        </div>

                        
                        <template x-if="!bestOrderOffer">
                            <div class="border border-dashed rounded-4 bg-body-tertiary p-3 mb-3">
                                <template x-if="availableOrderOffers.length > 0">
                                    <div class="dropdown w-100">
                                        <button type="button" class="btn btn-link text-decoration-none w-100 p-0 d-flex align-items-center justify-content-between text-start" data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="bg-warning bg-opacity-10 text-warning rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;">
                                                    <i class="bi bi-gift-fill"></i>
                                                </div>
                                                <div>
                                                    <p class="mb-0 fw-bold text-body" style="font-size: 13px;">Apply an Offer</p>
                                                    <p class="mb-0 text-muted" style="font-size: 10px;" x-text="availableOrderOffers.length + ' offer(s) available for this cart'"></p>
                                                </div>
                                            </div>
                                            <i class="bi bi-chevron-right text-muted"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end w-100 p-2 shadow-lg border-0 rounded-4 mt-2">
                                            <h6 class="dropdown-header fw-bold text-body">Available Offers</h6>
                                            <template x-for="offer in availableOrderOffers" :key="offer.id">
                                                <button class="dropdown-item rounded-3 py-2 px-3 d-flex justify-content-between align-items-center mb-1" @click="appliedOfferId = offer.id">
                                                    <div>
                                                        <div class="fw-bold text-body" style="font-size: 13px;" x-text="offer.name"></div>
                                                        <div class="text-success fw-semibold" style="font-size: 11px;" x-text="'Save ₹' + Number(offer.computed_discount).toFixed(2)"></div>
                                                    </div>
                                                    <i class="bi bi-check-circle-fill text-success" x-show="appliedOfferId === offer.id"></i>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="availableOrderOffers.length === 0">
                                    <p class="mb-0 fw-semibold text-muted text-center" style="font-size: 12px;">No offers available for this cart.</p>
                                </template>
                            </div>
                        </template>

                        
                        <template x-if="bestOrderOffer">
                            <div class="d-flex align-items-center justify-content-between gap-3 px-3 py-2 rounded-4 bg-success bg-opacity-10 border border-success border-opacity-25 mb-3">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="bg-success bg-opacity-25 text-success rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 28px; height: 28px;">
                                        <i class="bi bi-check-lg"></i>
                                    </div>
                                    <div>
                                        <p class="mb-0 fw-bold text-success" style="font-size: 13px;" x-text="bestOrderOffer.name"></p>
                                        <p class="mb-0 fw-semibold text-success opacity-75" style="font-size: 11px;" x-text="'Saving ₹' + Number(orderOfferDiscountAmount).toFixed(2)"></p>
                                    </div>
                                </div>
                                <button type="button" @click.prevent="appliedOfferId = null" class="btn btn-sm btn-link text-success p-0 text-decoration-none">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </template>

                        
                        <template x-if="bogoDiscount > 0">
                            <div class="d-flex align-items-center justify-content-between gap-3 px-3 py-2 rounded-4 bg-body-secondary border mb-3">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="bg-primary bg-opacity-10 text-primary rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 28px; height: 28px;">
                                        <i class="bi bi-lightning-charge-fill"></i>
                                    </div>
                                    <div>
                                        <p class="mb-0 fw-bold text-body" style="font-size: 13px;">BOGO Savings</p>
                                        <p class="mb-0 text-muted" style="font-size: 11px;">Auto-applied</p>
                                    </div>
                                </div>
                                <span class="fw-bold text-success" style="font-size: 13px;" x-text="'- ₹' + Number(bogoDiscount).toFixed(2)"></span>
                            </div>
                        </template>

                        
                        <div class="d-flex align-items-center gap-2 mb-4" x-show="!couponApplied">
                            <input type="text" x-model="couponCode" @keydown.enter.prevent="applyCoupon()" placeholder="Promo code" class="form-control form-control-sm rounded-3 font-monospace text-uppercase flex-grow-1" style="height: 38px;">
                            <button type="button" @click.prevent="applyCoupon()" class="btn btn-primary btn-sm rounded-3 fw-bold text-uppercase tracking-widest px-3 flex-shrink-0" style="height: 38px; font-size: 11px;">
                                Apply
                            </button>
                        </div>
                        <div class="d-flex align-items-center justify-content-between px-3 py-2 rounded-4 bg-success bg-opacity-10 border border-success border-opacity-25 mb-4" x-show="couponApplied" x-cloak>
                            <div class="d-flex align-items-center gap-2 text-success">
                                <i class="bi bi-check-circle-fill"></i>
                                <span class="fw-bold text-uppercase tracking-widest" style="font-size: 11px;" x-text="'Coupon: ' + couponCode + ' (- ₹' + Number(couponDiscount).toFixed(2) + ')'"></span>
                            </div>
                            <button type="button" @click.prevent="removeCoupon()" class="btn btn-sm btn-link text-success p-0">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>

                        <hr class="border-secondary opacity-10">

                        
                        <div class="space-y-2 mb-4">
                            <div class="d-flex justify-content-between fw-medium text-muted" style="font-size: 13px;">
                                <span>Subtotal</span>
                                <span class="text-body fw-bold" x-text="'₹' + Number(subtotal).toFixed(2)"></span>
                            </div>
                            
                            <div class="d-flex justify-content-between fw-medium text-success" style="font-size: 13px;" x-show="bogoDiscount > 0" x-cloak>
                                <div>
                                    <span>BOGO Savings</span>
                                    <span class="text-muted d-block" style="font-size: 10px;">Auto-applied backend offer</span>
                                </div>
                                <span class="fw-bold align-top" x-text="'- ₹' + Number(bogoDiscount).toFixed(2)"></span>
                            </div>

                            <div class="d-flex justify-content-between fw-medium text-success" style="font-size: 13px;" x-show="orderOfferDiscountAmount > 0" x-cloak>
                                <div>
                                    <span>Order Discount</span>
                                    <span class="text-muted d-block" style="font-size: 10px;" x-text="bestOrderOffer ? bestOrderOffer.name : ''"></span>
                                </div>
                                <span class="fw-bold align-top" x-text="'- ₹' + Number(orderOfferDiscountAmount).toFixed(2)"></span>
                            </div>

                            <div class="d-flex justify-content-between fw-medium text-success" style="font-size: 13px;" x-show="couponDiscount > 0" x-cloak>
                                <div>
                                    <span>Coupon Savings</span>
                                    <span class="text-muted d-block" style="font-size: 10px;" x-text="'(Code: ' + couponCode + ')'"></span>
                                </div>
                                <span class="fw-bold align-top" x-text="'- ₹' + Number(couponDiscount).toFixed(2)"></span>
                            </div>

                            <div class="d-flex justify-content-between fw-medium text-muted" style="font-size: 13px;">
                                <span>GST</span>
                                <span class="text-body" x-text="'₹' + Number(taxAmount).toFixed(2)"></span>
                            </div>

                            <hr class="border-secondary opacity-10 my-3">

                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-uppercase tracking-widest text-body" style="font-size: 14px;">Grand Total</span>
                                <span class="fw-black text-primary fs-3" x-text="'₹' + Number(grandTotal).toFixed(2)"></span>
                            </div>
                        </div>

                        
                        <button type="button" @click.prevent="placeOrder()" :disabled="placing || cart.length === 0 || !partyId || !warehouseId"
                            class="btn btn-primary w-100 rounded-pill py-3 fw-black text-uppercase tracking-widest shadow position-relative overflow-hidden transition-all hover-shadow" style="letter-spacing: 2px;">
                            <span x-show="placing" class="spinner-border spinner-border-sm me-2"></span>
                            <i x-show="!placing" class="bi bi-check-circle-fill me-2 fs-5 align-middle"></i>
                            <span x-text="isDraft ? 'Save Future Order' : 'Complete Order'" class="align-middle"></span>
                            <div class="position-absolute top-0 start-0 w-100 h-100 bg-white opacity-25" style="transform: translateX(-100%); transition: transform 0.5s;" onmouseover="this.style.transform='translateX(100%)'" onmouseout="this.style.transform='translateX(-100%)'"></div>
                        </button>
                        
                        <template x-if="formErrors.length">
                            <div class="alert alert-danger mt-3 mb-0 p-3 rounded-4 shadow-sm small border-0 bg-danger bg-opacity-10 text-danger-emphasis">
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
</div>

<?php $__env->startPush('scripts'); ?>
<?php
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
        'get_qty' => (int)$o->get_qty
    ])->values()->all();
?>
<script>
function createOrderApp() {
    return {
        viewMode: 'grid',
        partyId: new URLSearchParams(window.location.search).get('customer_id') || '', warehouseId: '<?php echo e($warehouses->first()->id ?? ''); ?>', shippingAddressId: '', billingAddressId: '', sameAsShipping: true, orderType: 'sale',
        orderDate: new Date().toISOString().substring(0,10),
        isDraft: false, futureOrderDate: '',
        addresses: [],
        products: [], productQuery: '', stockFilter: 'available', categoryFilter: '',
        searching: false, productPage: 1, productLastPage: 1, productTotal: 0, productFrom: 0, productTo: 0,
        cart: [], couponCode: '', couponApplied: false, couponDiscount: 0, appliedOfferId: null,
        placing: false, formErrors: [],
        activeOffers: <?php echo json_encode($offersArray, 15, 512) ?>,

        customerDetails: null,

        init() {
            this.searchProducts();
            if (this.partyId) {
                this.loadAddresses();
            }
            const saved = localStorage.getItem('metis_create_order_cart');
            if (saved) { try { this.cart = JSON.parse(saved); } catch(e){} }
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
                    if (this.appliedOfferId && !this.availableOrderOffers.some(o => o.id === this.appliedOfferId)) {
                        this.appliedOfferId = null;
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
            if (!this.partyId) { this.addresses = []; this.customerDetails = null; return; }
            try {
                const res = await fetch(`/customers/${this.partyId}`, { headers: {'Accept':'application/json','X-Requested-With':'XMLHttpRequest'} });
                const json = await res.json();
                this.customerDetails = json.data;
                this.addresses = json.data?.addresses || [];
                if (this.addresses.length) {
                    this.shippingAddressId = this.addresses.find(a=>a.is_default)?.id || this.addresses[0].id;
                    this.billingAddressId = this.shippingAddressId;
                }
            } catch(e) { console.error(e); }
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
            const qty = parseInt(p._qty)||1;
            const disc = parseFloat(p._disc)||0;
            if (qty <= 0) return;
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
            const bogos = this.activeOffers.filter(o=>o.type==='bogo');
            return this.cart.reduce((t,item)=>{
                const match = bogos.find(o=>Number(o.product_id)===Number(item.id));
                if(!match) return t;
                const cycle = (match.buy_qty||1)+(match.get_qty||1);
                const qty = parseInt(item.quantity)||0;
                if(qty<cycle) return t;
                const free = Math.floor(qty/cycle)*(match.get_qty||1);
                const eff = qty>0 ? this.lineTotal(item)/qty : 0;
                return t + Math.min(eff*free, this.lineTotal(item));
            },0);
        },
        get activeOrderOffers() {
            return (this.activeOffers || []).filter(o => o.type === 'order_discount');
        },
        orderOfferDiscount(offer) {
            if (!offer || this.subtotal <= 0) return 0;
            if ((parseFloat(offer.min_spend) || 0) > this.subtotal) return 0;
            let discount = String(offer.discount_type) === 'percentage'
                ? this.subtotal * ((parseFloat(offer.value) || 0) / 100)
                : (parseFloat(offer.value) || 0);
            if ((parseFloat(offer.max_discount) || 0) > 0) {
                discount = Math.min(discount, parseFloat(offer.max_discount) || 0);
            }
            return Math.min(discount, this.subtotal);
        },
        get availableOrderOffers() {
            return this.activeOrderOffers
                .map(offer => ({ ...offer, computed_discount: this.orderOfferDiscount(offer) }))
                .filter(offer => offer.computed_discount > 0)
                .sort((a, b) => (b.computed_discount - a.computed_discount) || (a.id - b.id));
        },
        get bestOrderOffer() {
            if (!this.appliedOfferId) return null;
            return this.availableOrderOffers.find(o => o.id === this.appliedOfferId) || null;
        },
        get orderOfferDiscountAmount() {
            const best = this.bestOrderOffer;
            return best ? best.computed_discount : 0;
        },
        get totalDiscount() { return Math.min(this.subtotal, this.bogoDiscount + this.couponDiscount + this.orderOfferDiscountAmount); },
        get grandTotal() { return Math.max(0, this.subtotal - this.totalDiscount + this.taxAmount); },

        async applyCoupon() {
            const code = this.couponCode.toUpperCase().trim();
            if (!code) return;
            const res = await fetch('/coupons/validate', {method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content,'Accept':'application/json'}, body:JSON.stringify({code,subtotal:this.subtotal})});
            const json = await res.json();
            if (json.valid) { this.couponDiscount=json.discount; this.couponApplied=true; }
            else { this.couponDiscount=0; this.couponApplied=false; }
            window.dispatchEvent(new CustomEvent('notify',{detail:{type:json.valid?'success':'error',message:json.message}}));
        },

        removeCoupon() { this.couponCode=''; this.couponDiscount=0; this.couponApplied=false; },

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
                    applied_offer_id: this.appliedOfferId || null,
                    total_amount: parseFloat(this.subtotal.toFixed(2)),
                    tax_amount: parseFloat(this.taxAmount.toFixed(2)),
                    discount_amount: parseFloat(this.totalDiscount.toFixed(2)),
                    net_amount: parseFloat(this.grandTotal.toFixed(2)),
                };
                const res = await fetch('/orders', { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content,'Accept':'application/json'}, body:JSON.stringify(payload) });
                const json = await res.json();
                if (!res.ok) {
                    this.formErrors = Object.values(json.errors||{}).flat();
                    if (!this.formErrors.length && json.message) this.formErrors.push(json.message);
                    return;
                }
                localStorage.removeItem('metis_create_order_cart');
                this.cart = [];
                window.dispatchEvent(new CustomEvent('notify',{detail:{type:'success',message:'Order placed successfully!'}}));
                setTimeout(() => { window.location.href = '/orders?success=' + encodeURIComponent('Order placed successfully!'); }, 800);
            } catch(e) { this.formErrors.push('An unexpected error occurred.'); }
            finally { this.placing = false; }
        },
    };
}
</script>
<?php $__env->stopPush(); ?>
<?php if (isset($component)) { $__componentOriginal6a3051decf2176a9f137b7e9181451ca = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6a3051decf2176a9f137b7e9181451ca = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.customer-address-modal','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('customer-address-modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6a3051decf2176a9f137b7e9181451ca)): ?>
<?php $attributes = $__attributesOriginal6a3051decf2176a9f137b7e9181451ca; ?>
<?php unset($__attributesOriginal6a3051decf2176a9f137b7e9181451ca); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6a3051decf2176a9f137b7e9181451ca)): ?>
<?php $component = $__componentOriginal6a3051decf2176a9f137b7e9181451ca; ?>
<?php unset($__componentOriginal6a3051decf2176a9f137b7e9181451ca); ?>
<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/ubuntu/metis/resources/views/orders/create.blade.php ENDPATH**/ ?>