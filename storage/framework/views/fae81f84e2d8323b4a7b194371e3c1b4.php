<?php $__env->startSection('title', 'Create Order'); ?>
<?php $__env->startSection('page', 'orders.create'); ?>

<?php $__env->startPush('head'); ?>
<style>
    .create-order-shell {
        position: relative;
        min-height: 100vh;
        background:
            radial-gradient(circle at top left, rgba(var(--bs-primary-rgb), 0.12), transparent 34%),
            radial-gradient(circle at top right, rgba(var(--bs-info-rgb), 0.10), transparent 26%),
            linear-gradient(180deg, var(--bs-body-bg) 0%, rgba(var(--bs-body-bg-rgb), 0.92) 100%);
    }
    .order-hero {
        background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.10), rgba(var(--bs-info-rgb), 0.08));
        border: 1px solid rgba(var(--bs-primary-rgb), 0.08);
        box-shadow: 0 18px 60px rgba(15, 23, 42, 0.08);
    }
    .glass-panel {
        background: rgba(var(--bs-body-bg-rgb), 0.92);
        backdrop-filter: blur(14px);
    }
    .section-label {
        font-size: 10px;
        letter-spacing: 0.16em;
        text-transform: uppercase;
    }
    .profile-chip {
        min-height: 100%;
    }
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
    .sticky-side-div {
        position: sticky;
        top: 24px;
    }
</style>
<?php $__env->stopPush(); ?>

<script>
    window.__INITIAL_ORDER_CUSTOMER__ = <?php echo json_encode($initialCustomer ? $initialCustomer->toArray() : null, 15, 512) ?>;
</script>

<?php $__env->startSection('content'); ?>
    <div class="container-fluid p-4 create-order-shell" x-data="createOrderApp(window.__INITIAL_ORDER_CUSTOMER__)">
    
    <div class="order-hero rounded-5 p-4 p-lg-5 mb-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge rounded-pill text-bg-primary-subtle text-primary-emphasis border border-primary border-opacity-10 px-3 py-2">Order Studio</span>
                    <span class="badge rounded-pill text-bg-dark-subtle text-body-secondary border px-3 py-2">Customer <?php echo e(request()->query('customer_id') ? '#' . request()->query('customer_id') : 'Selection'); ?></span>
                </div>
                <h1 class="h3 mb-1 fw-black text-body-emphasis">
                    <i class="bi bi-cart-check-fill me-2 text-primary"></i> Create New Order
                </h1>
                <p class="text-muted mb-0">A cleaner layout for customer profile, addresses, cart, and checkout.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="<?php echo e(route('orders')); ?>" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
                    <i class="bi bi-arrow-left me-2"></i> Back to Orders
                </a>
            </div>
        </div>
    </div>

    
    <?php if(session('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm border-0 bg-danger bg-opacity-10 text-danger-emphasis">
        <i class="bi bi-exclamation-circle-fill me-2"></i><?php echo e(session('error')); ?>

        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div id="checkout-step-2" class="card mb-4 border-0 shadow-sm rounded-5 overflow-hidden glass-panel" x-show="partyId && showCheckoutReview" x-cloak>
        <div class="card-header bg-body-tertiary border-bottom-0 py-3 px-4 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold text-body-emphasis"><i class="bi bi-bag-check me-2 text-primary"></i><span x-text="isDraft ? 'Future Order Review' : 'Order Review'"></span></h5>
                <p class="mb-0 small text-muted">Step 2 of 2. Review everything before placing the order.</p>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" @click="closeCheckoutReview()">
                <i class="bi bi-x-lg me-1"></i>Close
            </button>
        </div>
        <div class="card-body p-4 p-lg-4">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100 bg-body-tertiary">
                        <div class="card-body p-4">
                            <div class="section-label text-muted fw-bold mb-2">Customer</div>
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="rounded-circle bg-primary text-white fw-black d-flex align-items-center justify-content-center shadow-sm" style="width: 54px; height: 54px;" x-text="customerDetails && customerDetails.firstname ? customerDetails.firstname.charAt(0) : '?'"></div>
                                <div>
                                    <h5 class="fw-bold text-body-emphasis mb-1" x-text="customerDisplayName"></h5>
                                    <div class="small text-muted" x-text="customerDetails?.party_code || 'No customer code'"></div>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge rounded-pill text-bg-primary-subtle text-primary-emphasis border" x-text="customerDetails?.phone || 'No phone'"></span>
                                <span class="badge rounded-pill text-bg-secondary-subtle text-body-secondary border" x-text="customerDetails?.email || 'No email'"></span>
                            </div>
                            <div class="mt-4">
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" @click="$dispatch('open-add-customer-modal', {customer: customerDetails})">
                                    <i class="bi bi-pencil-square me-1"></i>Edit Profile
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100 bg-body-tertiary">
                        <div class="card-body p-3">
                            <div class="section-label text-muted fw-bold mb-2">Addresses</div>

                            <div id="review-addresses-section" class="transition-all">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="fw-bold mb-0 text-body fs-6"><i class="bi bi-geo-alt-fill me-2 text-primary"></i>Shipping Addresses</h6>
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-sm hover-shadow transition-all" @click="$dispatch('open-address-modal', {customerId: partyId})">
                                        <i class="bi bi-plus-lg me-2"></i>Add Address
                                    </button>
                                </div>

                                <div class="row g-3">
                                    <template x-for="addr in addresses" :key="'review-shipping-' + addr.id">
                                        <div class="col-md-6">
                                            <div class="w-100 h-100 cursor-pointer" style="display:block;" @click="shippingAddressId = addr.id">
                                                <div class="card h-100 border-2 rounded-4 transition-all" :class="shippingAddressId == addr.id ? 'border-primary bg-primary bg-opacity-10 shadow-md' : 'border-secondary border-opacity-10 bg-body-tertiary hover-shadow'" style="transform: translateY(0); transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                                                    <div class="card-body p-3 position-relative">
                                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                                            <div>
                                                                <span class="badge bg-secondary bg-opacity-25 text-secondary-emphasis rounded-pill me-2 px-2 py-1 shadow-sm fw-medium" x-text="addr.label || 'Address'"></span>
                                                                <span x-show="addr.is_default" class="badge bg-success bg-opacity-25 text-success-emphasis rounded-pill px-2 py-1 shadow-sm fw-medium"><i class="bi bi-star-fill me-1"></i>Default</span>
                                                            </div>
                                                            <button type="button" class="btn btn-light btn-sm rounded-circle shadow-sm position-absolute d-flex align-items-center justify-content-center" style="top: 12px; right: 12px; width: 28px; height: 28px; z-index: 20; border: 1px solid rgba(0,0,0,0.05);" @click.stop.prevent="$dispatch('open-address-modal', {customerId: partyId, address: addr})">
                                                                <i class="bi bi-pencil text-primary"></i>
                                                            </button>
                                                        </div>
                                                        <p class="mb-1 small fw-bold text-body" x-text="addr.address_line_1"></p>
                                                        <p class="mb-1 small text-muted" x-show="addr.address_line_2" x-text="addr.address_line_2"></p>
                                                        <p class="mb-1 small text-muted" x-show="addr.village" x-text="[addr.village?.village_name ? 'Vill: '+addr.village?.village_name : null, addr.village?.post_so_name ? 'PO: '+addr.village?.post_so_name : null, addr.village?.taluka_name ? 'Ta: '+addr.village?.taluka_name : null, addr.village?.district_name ? 'Dist: '+addr.village?.district_name : null].filter(Boolean).join(', ')"></p>
                                                        <p class="mb-0 small text-muted fw-medium" style="font-size: 12px;">
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

                                <div class="mt-3 form-check form-switch cursor-pointer d-flex align-items-center gap-2">
                                    <input class="form-check-input mt-0" type="checkbox" id="reviewSameAsShippingToggle" x-model="sameAsShipping" style="cursor: pointer; width: 40px; height: 20px;">
                                    <label class="form-check-label small fw-bold text-muted text-uppercase mt-1" for="reviewSameAsShippingToggle" style="cursor: pointer; font-size: 11px; letter-spacing: 1px;">Billing address same as Shipping address</label>
                                </div>

                                <div x-show="!sameAsShipping" x-cloak class="mt-3 pt-3 border-top transition-all">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="fw-bold mb-0 text-body fs-6"><i class="bi bi-receipt me-2 text-primary"></i>Billing Addresses</h6>
                                    </div>

                                    <div class="row g-3">
                                        <template x-for="addr in addresses" :key="'review-billing-' + addr.id">
                                            <div class="col-md-6">
                                                <div class="w-100 h-100 cursor-pointer" style="display:block;" @click="billingAddressId = addr.id">
                                                    <div class="card h-100 border-2 rounded-4 transition-all" :class="billingAddressId == addr.id ? 'border-primary bg-primary bg-opacity-10 shadow-md' : 'border-secondary border-opacity-10 bg-body-tertiary hover-shadow'" style="transform: translateY(0); transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                                                        <div class="card-body p-3 position-relative">
                                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                                <div>
                                                                    <span class="badge bg-secondary bg-opacity-25 text-secondary-emphasis rounded-pill me-2 px-2 py-1 shadow-sm fw-medium" x-text="addr.label || 'Address'"></span>
                                                                    <span x-show="addr.is_default" class="badge bg-success bg-opacity-25 text-success-emphasis rounded-pill px-2 py-1 shadow-sm fw-medium"><i class="bi bi-star-fill me-1"></i>Default</span>
                                                                </div>
                                                                <button type="button" class="btn btn-light btn-sm rounded-circle shadow-sm position-absolute d-flex align-items-center justify-content-center" style="top: 12px; right: 12px; width: 28px; height: 28px; z-index: 20; border: 1px solid rgba(0,0,0,0.05);" @click.stop.prevent="$dispatch('open-address-modal', {customerId: partyId, address: addr})">
                                                                    <i class="bi bi-pencil text-primary"></i>
                                                                </button>
                                                            </div>
                                                            <p class="mb-1 small fw-bold text-body" x-text="addr.address_line_1"></p>
                                                            <p class="mb-1 small text-muted" x-show="addr.address_line_2" x-text="addr.address_line_2"></p>
                                                            <p class="mb-1 small text-muted" x-show="addr.village" x-text="[addr.village?.village_name ? 'Vill: '+addr.village?.village_name : null, addr.village?.post_so_name ? 'PO: '+addr.village?.post_so_name : null, addr.village?.taluka_name ? 'Ta: '+addr.village?.taluka_name : null, addr.village?.district_name ? 'Dist: '+addr.village?.district_name : null].filter(Boolean).join(', ')"></p>
                                                            <p class="mb-0 small text-muted fw-medium" style="font-size: 12px;">
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
                </div>

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100 bg-body-tertiary">
                        <div class="card-body p-4">
                            <div class="section-label text-muted fw-bold mb-2">Order Summary</div>
                            <div class="d-flex justify-content-between small mb-2">
                                <span class="text-muted">Warehouse</span>
                                <span class="fw-semibold text-body-emphasis" x-text="selectedWarehouseName"></span>
                            </div>
                            <div class="d-flex justify-content-between small mb-2">
                                <span class="text-muted">Items</span>
                                <span class="fw-semibold text-body-emphasis" x-text="cart.length"></span>
                            </div>
                            <div class="d-flex justify-content-between small mb-2">
                                <span class="text-muted">Subtotal</span>
                                <span class="fw-semibold text-body-emphasis" x-text="'₹' + Number(subtotal).toFixed(2)"></span>
                            </div>
                            <div class="d-flex justify-content-between small mb-2" x-show="totalDiscount > 0">
                                <span class="text-muted">Discount</span>
                                <span class="fw-semibold text-success" x-text="'- ₹' + Number(totalDiscount).toFixed(2)"></span>
                            </div>
                            <div class="d-flex justify-content-between small mb-2">
                                <span class="text-muted">GST</span>
                                <span class="fw-semibold text-body-emphasis" x-text="'₹' + Number(taxAmount).toFixed(2)"></span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-uppercase small text-body">Grand Total</span>
                                <span class="fw-black text-primary fs-4" x-text="'₹' + Number(grandTotal).toFixed(2)"></span>
                            </div>
                            <div class="mt-4">
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" @click="closeCheckoutReview(); scrollToSection('catalog-section')">
                                    <i class="bi bi-pencil me-1"></i>Edit Orders
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4 bg-body-tertiary">
                        <div class="card-body p-4">
                            <div class="section-label text-muted fw-bold mb-3">Order Items</div>
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead>
                                        <tr class="small text-muted">
                                            <th>Item</th>
                                            <th class="text-center">Qty</th>
                                            <th class="text-end">Price</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="item in cart" :key="'checkout-' + item.id">
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold text-body-emphasis" x-text="item.name"></div>
                                                    <div class="small text-muted" x-text="item.sku"></div>
                                                </td>
                                                <td class="text-center fw-semibold" x-text="item.quantity"></td>
                                                <td class="text-end text-muted" x-text="'₹' + Number(item.price).toFixed(2)"></td>
                                                <td class="text-end fw-bold text-body-emphasis" x-text="'₹' + Number(lineTotal(item)).toFixed(2)"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <template x-if="isDraft">
                    <div class="col-12">
                        <div class="alert alert-warning rounded-4 border-0 mb-0">
                            Future order date: <span class="fw-bold" x-text="futureOrderDate || 'Not selected'"></span>
                        </div>
                    </div>
                </template>
            </div>
        </div>
        <div class="card-footer bg-body-tertiary border-top-0 p-4 pt-0 d-flex flex-wrap gap-2 justify-content-end">
            <button type="button" class="btn btn-light rounded-pill px-4" @click="closeCheckoutReview()">Back</button>
            <button type="button" class="btn btn-primary rounded-pill px-4" :disabled="placing || (isDraft && !futureOrderDate)" @click="confirmCheckout()">
                <span x-show="placing" class="spinner-border spinner-border-sm me-2"></span>
                <span x-text="isDraft ? 'Confirm Future Order' : 'Confirm & Place Order'"></span>
            </button>
        </div>
    </div>

    <div @customer-updated.window="loadAddresses()" class="row g-4" x-show="!showCheckoutReview" x-cloak>
        <div class="col-xl-8">
            <div id="customer-workspace" class="card border-0 shadow-sm rounded-5 overflow-hidden mb-4 glass-panel">
                <div class="card-header bg-body-tertiary border-bottom-0 py-3 px-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 fw-bold text-body-emphasis"><i class="bi bi-person-badge me-2 text-primary"></i>Customer Workspace</h5>
                        <p class="mb-0 small text-muted">Profile, addresses, and order build steps in one place.</p>
                    </div>
                    <div class="d-flex align-items-center gap-2" x-show="customerDetails" x-cloak>
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" @click="$dispatch('open-add-customer-modal', {customer: customerDetails})">
                            <i class="bi bi-pencil-square me-1"></i>Edit Profile
                        </button>
                    </div>
                </div>
                <div class="card-body p-4 p-lg-4">
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <div class="rounded-4 border border-primary border-opacity-10 bg-primary bg-opacity-10 p-3 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle bg-primary text-white fw-black d-flex align-items-center justify-content-center shadow-sm" style="width: 62px; height: 62px;" x-text="customerDetails && customerDetails.firstname ? customerDetails.firstname.charAt(0) : '?'"></div>
                                    <div>
                                        <div class="section-label text-primary-emphasis fw-bold mb-1">Selected Customer</div>
                                        <h4 class="mb-1 fw-black text-body-emphasis" x-text="customerDisplayName"></h4>
                                        <div class="d-flex flex-wrap gap-2 small text-muted">
                                            <span x-text="customerDetails?.party_code ? customerDetails.party_code : 'No party code'"></span>
                                            <span x-show="customerDetails?.phone" x-text="'• ' + customerDetails.phone"></span>
                                            <span x-show="customerDetails?.email" x-text="'• ' + customerDetails.email"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <span class="badge rounded-pill text-bg-success-subtle text-success-emphasis border">Profile ready</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div x-show="customerDetails" x-cloak class="transition-all">
                        <div class="row g-4">
                            <div class="col-lg-4">
                                <div class="card h-100 border-0 rounded-4 shadow-sm bg-body-secondary profile-chip">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center gap-3 mb-4">
                                            <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center fw-bold fs-3 shadow-sm" style="width: 56px; height: 56px;" x-text="customerDetails.firstname ? customerDetails.firstname.charAt(0) : '?'"></div>
                                            <div>
                                                <div class="section-label text-muted fw-bold mb-1">Contact Profile</div>
                                                <div class="fw-bold fs-5 text-body-emphasis" x-text="customerDetails.party_code || 'Customer profile'"></div>
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
                            </div>
                            <div class="col-lg-4">
                                <div class="card h-100 border-0 rounded-4 shadow-sm bg-warning bg-opacity-10">
                                    <div class="card-body p-4">
                                        <div class="section-label text-warning-emphasis fw-bold mb-3"><i class="bi bi-sun me-1"></i>Agriculture Snapshot</div>
                                        <div class="row g-3 small">
                                            <div class="col-6">
                                                <div class="text-muted mb-1 section-label">Land Area</div>
                                                <div class="fw-black fs-5 text-body"><span x-text="customerDetails.land_area || '0'"></span> <span class="fs-6 fw-medium text-muted" x-text="customerDetails.land_unit || ''"></span></div>
                                            </div>
                                            <div class="col-12 mt-2" x-show="customerDetails.crops && customerDetails.crops.length > 0">
                                                <div class="text-muted mb-2 section-label">Major Crops</div>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <template x-for="crop in customerDetails.crops">
                                                        <span class="badge bg-success bg-opacity-25 text-success-emphasis rounded-pill px-2 py-1 shadow-sm fw-medium" x-text="crop"></span>
                                                    </template>
                                                </div>
                                            </div>
                                            <div class="col-12 mt-2" x-show="customerDetails.irrigation_type && customerDetails.irrigation_type.length > 0">
                                                <div class="text-muted mb-2 section-label">Irrigation</div>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <template x-for="type in customerDetails.irrigation_type">
                                                        <span class="badge bg-info bg-opacity-25 text-info-emphasis rounded-pill px-2 py-1 shadow-sm fw-medium" x-text="type"></span>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="card h-100 border-0 rounded-4 shadow-sm bg-danger bg-opacity-10">
                                    <div class="card-body p-4">
                                        <div class="section-label text-danger-emphasis fw-bold mb-3"><i class="bi bi-wallet2 me-1"></i>Financial Snapshot</div>
                                        <div class="row g-3 small">
                                            <div class="col-6">
                                                <div class="text-muted mb-1 section-label">Credit Limit</div>
                                                <div class="fw-black fs-5 text-body">₹<span x-text="Number(customerDetails.credit_limit || 0).toFixed(2)"></span></div>
                                            </div>
                                            <div class="col-6">
                                                <div class="text-muted mb-1 section-label">Balance</div>
                                                <div class="fw-black fs-5" :class="Number(customerDetails.outstanding_balance) > 0 ? 'text-danger' : 'text-success'">₹<span x-text="Number(customerDetails.outstanding_balance || 0).toFixed(2)"></span></div>
                                            </div>
                                            <div class="col-6 mt-3">
                                                <div class="text-muted mb-1 section-label">Credit Days</div>
                                                <div class="fw-bold text-body fs-6"><span x-text="customerDetails.credit_days || '0'"></span> Days</div>
                                            </div>
                                            <div class="col-6 mt-3" x-show="customerDetails.credit_valid_till">
                                                <div class="text-muted mb-1 section-label">Valid Till</div>
                                                <div class="fw-bold text-body fs-6" x-text="new Date(customerDetails.credit_valid_till).toLocaleDateString()"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    
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

                    <div class="card border-0 shadow-sm rounded-4 bg-body-tertiary mt-4">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                                <div>
                                    <div class="section-label text-primary fw-bold mb-1">Warehouse</div>
                                    <h6 class="mb-0 fw-bold text-body-emphasis">Select fulfillment warehouse</h6>
                                </div>
                                <select class="form-select fw-bold rounded-pill shadow-sm border-0 bg-body-secondary" style="max-width: 260px; height: 42px;" x-model="warehouseId">
                                    <option value="">Select Warehouse</option>
                                    <?php $__currentLoopData = $warehouses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $w): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($w->id); ?>"><?php echo e($w->name); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            
            <div id="catalog-section" class="card mb-4 border-0 shadow-sm rounded-4 overflow-hidden">
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
                                                    <img :src="p.image_url || '/assets/images/product-placeholder.svg'" class="rounded-3 border bg-body shadow-sm" style="width:60px;height:60px;object-fit:cover;flex-shrink:0" x-on:error="$el.src='/assets/images/product-placeholder.svg'">
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
                                                <div class="col-8">
                                                    <div class="form-floating">
                                                        <input type="number" class="form-control form-control-sm text-center fw-bold" style="height: 42px; min-height: 42px;" x-model.number="p._qty" min="1" :max="p.available_stock || 9999" placeholder="Qty">
                                                        <label class="text-muted" style="padding-top: 0.5rem; padding-bottom: 0.5rem; font-size: 0.75rem;">Qty</label>
                                                    </div>
                                                </div>
                                                <div class="col-4">
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

            
            <div class="mb-4 space-y-3" x-show="false">
                
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
                <div class="card mb-4 border-0 shadow-sm rounded-5 overflow-hidden glass-panel" x-show="partyId" x-cloak>
                    <div class="card-header bg-body-tertiary border-bottom-0 py-3 px-4">
                        <h5 class="mb-0 fw-bold text-body-emphasis"><i class="bi bi-calendar-event me-2 text-primary"></i>Schedule Order</h5>
                        <p class="mb-0 small text-muted">Switch this on to save a future order draft.</p>
                    </div>
                    <div class="card-body p-4">
                        <div class="form-check form-switch d-flex align-items-center justify-content-between gap-3 p-3 rounded-4 border bg-white">
                            <div>
                                <label class="form-check-label fw-bold text-body-emphasis mb-1" for="futureOrderSwitch">Place as Future Order</label>
                                <div class="small text-muted">Saves the order as pending for later processing.</div>
                            </div>
                            <input class="form-check-input fs-4 m-0" type="checkbox" id="futureOrderSwitch" x-model="isDraft">
                        </div>
                        <div x-show="isDraft" x-cloak class="mt-3 p-3 rounded-4 border bg-body-tertiary">
                            <label class="form-label fw-semibold text-body-emphasis">Future Order Date</label>
                            <input type="date" class="form-control rounded-pill" :min="new Date().toISOString().split('T')[0]" x-model="futureOrderDate" required>
                        </div>
                    </div>
                </div>

                <div class="card mb-4 border-0 shadow-sm rounded-5 overflow-hidden glass-panel" x-show="cart.length > 0" x-cloak>
                    <div class="card-header bg-body-tertiary border-bottom-0 py-3 px-4 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0 fw-bold text-body-emphasis"><i class="bi bi-cart3 me-2 text-primary"></i>Shopping Cart (<span x-text="cart.length" class="text-primary"></span>)</h5>
                            <p class="mb-0 small text-muted">Pinned summary for the order you’re building.</p>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3" @click="cart = []">
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
                            <div class="card border-0 shadow-sm rounded-4 mb-3 overflow-hidden group bg-body">
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
                <div class="card mb-4 border-0 shadow-sm rounded-4 overflow-hidden" x-show="cart.length > 0" x-cloak>
                    <div class="card-body p-4 space-y-4">
                        
                        
                        <div class="mb-4">
                            <button type="button" class="btn btn-outline-primary w-100 rounded-4 border-dashed p-3 d-flex align-items-center justify-content-between shadow-sm hover-shadow transition-all bg-body-tertiary" data-bs-toggle="modal" data-bs-target="#promotionsModal">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 shadow-sm" style="width: 40px; height: 40px;">
                                        <i class="bi bi-tag-fill fs-5"></i>
                                    </div>
                                    <div class="text-start">
                                        <p class="mb-0 fw-bold text-body fs-6">View Promos & Offers</p>
                                        <p class="mb-0 text-muted small" x-text="(activeOffers.length + activeCoupons.length) + ' available'"></p>
                                    </div>
                                </div>
                                <i class="bi bi-chevron-right text-muted"></i>
                            </button>
                        </div>

                        
                        <div class="space-y-3 mb-4" x-show="bestOrderOffer || couponApplied || bogoDiscount > 0" x-cloak>
                            
                            
                            <template x-if="bestOrderOffer">
                                <div class="d-flex align-items-center justify-content-between gap-3 px-3 py-2 rounded-4 bg-success bg-opacity-10 border border-success border-opacity-25 shadow-sm transition-all hover-shadow">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="bg-success bg-opacity-25 text-success-emphasis rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;">
                                            <i class="bi bi-check-lg fs-5"></i>
                                        </div>
                                        <div>
                                            <p class="mb-0 fw-bold text-success-emphasis fs-6" x-text="bestOrderOffer.name"></p>
                                            <p class="mb-0 fw-semibold text-success opacity-75 small" x-text="'Saving ₹' + Number(orderOfferDiscountAmount).toFixed(2)"></p>
                                        </div>
                                    </div>
                                    <button type="button" @click.prevent="appliedOfferId = 'none'" class="btn btn-sm btn-light text-muted hover-danger rounded-circle p-0 d-flex align-items-center justify-content-center shadow-sm" style="width: 28px; height: 28px;">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                            </template>

                            
                            <template x-if="couponApplied">
                                <div class="d-flex align-items-center justify-content-between gap-3 px-3 py-2 rounded-4 bg-success bg-opacity-10 border border-success border-opacity-25 shadow-sm transition-all hover-shadow">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="bg-success bg-opacity-25 text-success-emphasis rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;">
                                            <i class="bi bi-check-lg fs-5"></i>
                                        </div>
                                        <div>
                                            <p class="mb-0 fw-bold text-success-emphasis fs-6" x-text="'Coupon: ' + couponCode"></p>
                                            <p class="mb-0 fw-semibold text-success opacity-75 small" x-text="'Saving ₹' + Number(couponDiscount).toFixed(2)"></p>
                                        </div>
                                    </div>
                                    <button type="button" @click.prevent="removeCoupon()" class="btn btn-sm btn-light text-muted hover-danger rounded-circle p-0 d-flex align-items-center justify-content-center shadow-sm" style="width: 28px; height: 28px;">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                            </template>

                            
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
                                    <span class="fw-bold text-info-emphasis fs-6" x-text="'- ₹' + Number(bogoDiscount).toFixed(2)"></span>
                                </div>
                            </template>

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

                        
                        <button type="button" @click.prevent="openCheckoutReview()" :disabled="placing || cart.length === 0 || !partyId || !warehouseId"
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
                                        <div class="fw-bold text-body-emphasis" x-text="'₹' + Number(order.net_amount || 0).toFixed(2)"></div>
                                        <div class="small text-muted" x-text="expandedOrderId === order.id ? 'Hide details' : 'Show details'"></div>
                                    </div>
                                </div>
                            </button>

                            <div x-show="expandedOrderId === order.id" x-cloak class="border-top bg-white">
                                <div class="p-4">
                                    <div class="row g-3 mb-4">
                                        <div class="col-lg-4">
                                            <div class="p-3 rounded-4 bg-body-tertiary border h-100">
                                                <div class="fw-bold text-body-emphasis mb-1">Shipping</div>
                                                <div class="small text-muted" x-text="order.shipping_address ? [order.shipping_address.label, order.shipping_address.address_line_1, order.shipping_address.city, order.shipping_address.state].filter(Boolean).join(', ') : 'Not available'"></div>
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="p-3 rounded-4 bg-body-tertiary border h-100">
                                                <div class="fw-bold text-body-emphasis mb-1">Billing</div>
                                                <div class="small text-muted" x-text="order.billing_address ? [order.billing_address.label, order.billing_address.address_line_1, order.billing_address.city, order.billing_address.state].filter(Boolean).join(', ') : 'Same as shipping / not available'"></div>
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="p-3 rounded-4 bg-body-tertiary border h-100">
                                                <div class="fw-bold text-body-emphasis mb-1">Totals</div>
                                                <div class="small text-muted" x-text="'Subtotal ₹' + Number(order.total_amount || 0).toFixed(2) + ' | GST ₹' + Number(order.tax_amount || 0).toFixed(2)"></div>
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
                                                        <td class="text-end text-muted" x-text="'₹' + Number(item.unit_price || 0).toFixed(2)"></td>
                                                        <td class="text-end fw-bold text-body-emphasis" x-text="'₹' + Number(item.total_amount || 0).toFixed(2)"></td>
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
                                        <div class="fw-bold text-body-emphasis" x-text="'₹' + Number(order.net_amount || 0).toFixed(2)"></div>
                                        <div class="small text-muted" x-text="expandedOrderId === order.id ? 'Hide details' : 'Show details'"></div>
                                    </div>
                                </div>
                            </button>

                            <div x-show="expandedOrderId === order.id" x-cloak class="border-top bg-white">
                                <div class="p-4">
                                    <div class="row g-3 mb-4">
                                        <div class="col-lg-6">
                                            <div class="p-3 rounded-4 bg-body-tertiary border h-100">
                                                <div class="fw-bold text-body-emphasis mb-1">Shipping</div>
                                                <div class="small text-muted" x-text="order.shipping_address ? [order.shipping_address.label, order.shipping_address.address_line_1, order.shipping_address.city, order.shipping_address.state].filter(Boolean).join(', ') : 'Not available'"></div>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="p-3 rounded-4 bg-body-tertiary border h-100">
                                                <div class="fw-bold text-body-emphasis mb-1">Billing</div>
                                                <div class="small text-muted" x-text="order.billing_address ? [order.billing_address.label, order.billing_address.address_line_1, order.billing_address.city, order.billing_address.state].filter(Boolean).join(', ') : 'Same as shipping / not available'"></div>
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
                                                        <td class="text-end text-muted" x-text="'₹' + Number(item.unit_price || 0).toFixed(2)"></td>
                                                        <td class="text-end fw-bold text-body-emphasis" x-text="'₹' + Number(item.total_amount || 0).toFixed(2)"></td>
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
                                                
                                                
                                                <div class="mt-2 mb-2 pe-3 border-start border-2 border-secondary border-opacity-25 ps-2">
                                                    
                                                    <div x-show="offer.type === 'bogo'">
                                                        <p class="mb-1 small text-muted" x-text="'Rule: Buy ' + offer.buy_qty + ' Get ' + offer.get_qty + ' Free on ' + offer.product_name"></p>
                                                    </div>

                                                    
                                                    <div x-show="offer.type === 'order_discount'">
                                                        <p class="mb-1 small text-muted" x-text="'Discount: ' + (offer.discount_type === 'percentage' ? offer.value + '%' : '₹' + Number(offer.value).toFixed(2))"></p>
                                                        <p class="mb-1 small text-muted" x-show="offer.max_discount > 0" x-text="'Max Discount: ₹' + Number(offer.max_discount).toFixed(2)"></p>
                                                    </div>
                                                    
                                                    <p class="mb-1 small text-muted" x-show="offer.min_spend > 0" x-text="'Min. Spend: ₹' + Number(offer.min_spend).toFixed(2)"></p>
                                                    <p class="mb-0 small text-muted" x-show="offer.ends_at" x-text="'Valid till ' + new Date(offer.ends_at).toLocaleDateString()"></p>
                                                </div>

                                                
                                                <div x-show="offer.type === 'bogo'">
                                                    <p class="mb-0 small text-info"><i class="bi bi-lightning-charge-fill me-1"></i>Auto-applied to eligible items</p>
                                                </div>
                                                <div x-show="offer.type === 'order_discount'">
                                                    <div x-show="orderOfferDiscount(offer) > 0">
                                                        <p class="mb-0 small fw-medium">You save: <span class="text-success" x-text="'₹' + Number(orderOfferDiscount(offer)).toFixed(2)"></span></p>
                                                    </div>
                                                    <div x-show="orderOfferDiscount(offer) === 0">
                                                        <p class="mb-0 small text-danger"><i class="bi bi-info-circle me-1"></i>Add <span x-text="'₹' + Number(offer.min_spend).toFixed(2)"></span> to cart to unlock</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex-shrink-0">
                                                
                                                <template x-if="offer.type === 'bogo'">
                                                    <span class="badge bg-info bg-opacity-25 text-info-emphasis rounded-pill px-3 py-2 fw-medium">Active</span>
                                                </template>
                                                
                                                
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
                        
                        
                        <div class="tab-pane fade" id="tab-coupons" role="tabpanel">
                            
                            
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
                                                    <span class="badge bg-primary bg-opacity-10 text-primary w-100" x-text="c.type === 'percentage' ? c.value + '% OFF' : '₹' + Number(c.value).toFixed(2) + ' OFF'"></span>
                                                </div>
                                                <div class="ps-2 border-start border-2 border-secondary border-opacity-25 my-1">
                                                    <p class="mb-1 small text-muted" x-show="c.min_spend > 0" x-text="'Min. Spend: ₹' + Number(c.min_spend).toFixed(2)"></p>
                                                    <p class="mb-1 small text-muted" x-show="c.max_discount > 0" x-text="'Max Discount: ₹' + Number(c.max_discount).toFixed(2)"></p>
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
        'get_qty' => (int)$o->get_qty,
        'ends_at' => $o->ends_at,
        'priority' => (int)$o->priority,
        'product_name' => $o->product ? $o->product->name : 'Any Product'
    ])->values()->all();
?>
<script>
function createOrderApp(initialCustomer = null) {
    return {
        activeTab: 'customer',
        viewMode: 'grid',
        partyId: new URLSearchParams(window.location.search).get('customer_id') || '', warehouseId: '<?php echo e($warehouses->first()->id ?? ''); ?>', shippingAddressId: '', billingAddressId: '', sameAsShipping: true, orderType: 'sale',
        orderDate: new Date().toISOString().substring(0,10),
        isDraft: false, futureOrderDate: '',
        addresses: [],
        recentOrders: [],
        products: [], productQuery: '', stockFilter: 'available', categoryFilter: '',
        searching: false, productPage: 1, productLastPage: 1, productTotal: 0, productFrom: 0, productTo: 0,
        cart: [], couponCode: '', couponApplied: false, appliedCouponObj: null, appliedOfferId: null,
        placing: false, formErrors: [],
        warehouses: <?php echo json_encode($warehouses->map(fn($w) => ['id' => $w->id, 'name' => $w->name]), 512) ?>,
        activeOffers: <?php echo json_encode($offersArray, 15, 512) ?>,
        activeCoupons: <?php echo json_encode($activeCoupons, 15, 512) ?>,
        couponInputTemp: '',

        customerDetails: initialCustomer || window.__INITIAL_ORDER_CUSTOMER__ || null,
        bottomTab: 'history',
        showCheckoutReview: false,
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
            const step = new URLSearchParams(window.location.search).get('step');
            if (step === 'review' && this.partyId) {
                this.showCheckoutReview = true;
                this.$nextTick(() => {
                    const el = document.getElementById('checkout-step-2');
                    if (el) {
                        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });
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

        get shippingAddressSummary() {
            return this.addressSummary(this.addresses.find(a => String(a.id) === String(this.shippingAddressId)));
        },

        get billingAddressSummary() {
            if (this.sameAsShipping) return 'Same as shipping';
            return this.addressSummary(this.addresses.find(a => String(a.id) === String(this.billingAddressId)));
        },

        openCheckoutReview() {
            this.showCheckoutReview = true;
            this.bottomTab = 'history';
            this.$nextTick(() => {
                const el = document.getElementById('checkout-step-2');
                if (el) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        },

        closeCheckoutReview() {
            this.showCheckoutReview = false;
            this.$nextTick(() => {
                const el = document.getElementById('customer-workspace');
                if (el) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        },

        async confirmCheckout() {
            this.closeCheckoutReview();
            await this.placeOrder();
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