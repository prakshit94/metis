

<div x-show="isCartOpen" x-cloak
     x-transition.opacity.duration.300ms
     @click="isCartOpen = false"
     class="offcanvas-backdrop show" style="z-index: 1040;">
</div>


<div x-show="isCartOpen" x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="transform translate-x-100"
     x-transition:enter-end="transform translate-x-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="transform translate-x-0"
     x-transition:leave-end="transform translate-x-100"
     class="offcanvas offcanvas-end show border-start shadow" style="visibility: visible; z-index: 1045; width: 100%; max-width: 500px;">

    <div class="offcanvas-header bg-light border-bottom position-relative overflow-hidden p-4">
        <div class="position-absolute top-0 start-0 w-100 h-100 bg-success bg-opacity-10 pointer-events-none"></div>
        <div class="d-flex align-items-center gap-3 position-relative z-1">
            <div class="bg-white text-success border border-success border-opacity-25 rounded-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px;">
                <i class="bi bi-cart3 fs-4"></i>
            </div>
            <div>
                <h2 class="offcanvas-title h5 fw-bold text-dark mb-0">Shopping Cart</h2>
                <p class="mb-0 text-muted fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 1px;" x-text="cart.length + ' item' + (cart.length === 1 ? '' : 's')"></p>
            </div>
        </div>
        <button type="button" class="btn-close shadow-none position-relative z-1" @click="isCartOpen = false" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body p-0 d-flex flex-column bg-light bg-opacity-50" style="overflow-y: auto; scrollbar-width: thin;">
        <div class="p-4 d-flex flex-column gap-3">
            <template x-if="cart.length === 0">
                <div class="d-flex flex-column align-items-center justify-content-center text-center py-5 opacity-75">
                    <div class="bg-secondary bg-opacity-10 text-secondary rounded-circle d-flex align-items-center justify-content-center mb-4" style="width: 80px; height: 80px;">
                        <i class="bi bi-bag-x fs-1"></i>
                    </div>
                    <p class="fw-bold text-uppercase text-dark mb-2" style="font-size: 12px; letter-spacing: 1px;">Cart is empty</p>
                    <p class="text-muted small mb-4">Browse products and click <strong>Add</strong> to begin.</p>
                    <button type="button" @click="isCartOpen = false; activeTab = 'order'"
                        class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 1px;">
                        Browse Products
                    </button>
                </div>
            </template>

            <template x-for="(item, index) in cart" :key="item.id">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-3">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-start gap-3 mb-3">
                            <div class="bg-light border rounded-3 d-flex align-items-center justify-content-center overflow-hidden flex-shrink-0" style="width: 56px; height: 56px;">
                                <template x-if="item.image_url">
                                    <img :src="item.image_url" class="w-100 h-100" style="object-fit: cover;">
                                </template>
                                <template x-if="!item.image_url">
                                    <i class="bi bi-box-seam text-muted opacity-50 fs-4"></i>
                                </template>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex align-items-start justify-content-between gap-2">
                                    <div class="min-w-0">
                                        <p class="mb-0 fw-bold text-dark text-truncate fs-6" x-text="item.name"></p>
                                        <p class="mb-0 font-monospace text-muted" style="font-size: 10px;" x-text="item.sku"></p>
                                    </div>
                                    <button type="button" @click.prevent="removeFromCart(index)"
                                        class="btn btn-sm btn-light text-danger border-0 p-1 rounded-2 flex-shrink-0">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                                <div class="d-flex align-items-center justify-content-between mt-2">
                                    <span class="text-muted fw-semibold" style="font-size: 11px;" x-text="'₹' + Number(item.price).toFixed(2) + ' × ' + item.quantity"></span>
                                    <span class="fw-black text-success fs-6" x-text="'₹' + Number(itemLineTotal(item)).toFixed(2)"></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 pt-3 border-top">
                            <div class="input-group input-group-sm bg-light rounded-3 p-1" style="width: 100px;">
                                <button type="button" @click.prevent="updateCartQty(index, -1)"
                                    class="btn btn-sm btn-white border-0 fw-bold text-dark w-25 p-0">
                                    <i class="bi bi-dash"></i>
                                </button>
                                <span class="form-control bg-transparent border-0 text-center fw-bold text-dark px-1" x-text="item.quantity"></span>
                                <button type="button" @click.prevent="updateCartQty(index, 1)"
                                    class="btn btn-sm btn-white border-0 fw-bold text-dark w-25 p-0">
                                    <i class="bi bi-plus"></i>
                                </button>
                            </div>
                            
                            <template x-if="item.discountValue > 0">
                                <div class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 d-flex align-items-center gap-1 py-2 px-3">
                                    <i class="bi bi-tag-fill"></i>
                                    <span style="font-size: 10px; letter-spacing: 0.5px;"
                                        x-text="(item.discountType === 'flat' ? '₹' : '') + Number(item.discountValue).toFixed(item.discountValue % 1 === 0 ? 0 : 2) + (item.discountType === 'flat' ? ' off' : '% off')">
                                    </span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <div class="mt-auto bg-white border-top shadow-lg" x-show="cart.length > 0" x-cloak style="z-index: 2;">
        <div class="p-4 border-bottom">
            
            <div class="d-flex align-items-center justify-content-between mb-3">
                <p class="mb-0 fw-bold text-uppercase text-muted d-flex align-items-center gap-2" style="font-size: 10px; letter-spacing: 1px;">
                    <i class="bi bi-tag-fill text-primary"></i> Backend Offers
                </p>
                <template x-if="availableOrderOffers.length > 0">
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill" style="font-size: 9px;"
                          x-text="availableOrderOffers.length + ' available'"></span>
                </template>
            </div>

            
            <template x-if="!bestOrderOffer">
                <div class="rounded-4 border border-dashed bg-light p-3 mb-3">
                    <template x-if="availableOrderOffers.length > 0">
                        <button type="button" @click.prevent="isOffersModalOpen = true"
                            class="btn btn-link text-decoration-none p-0 w-100 d-flex align-items-center justify-content-between text-dark">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-warning bg-opacity-25 text-warning rounded-3 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                    <i class="bi bi-gift-fill"></i>
                                </div>
                                <div class="text-start">
                                    <p class="mb-0 fw-bold fs-6 lh-1">Apply an Offer</p>
                                    <p class="mb-0 text-muted" style="font-size: 10px;" x-text="availableOrderOffers.length + ' offer(s) available for this cart'"></p>
                                </div>
                            </div>
                            <i class="bi bi-chevron-right text-muted"></i>
                        </button>
                    </template>
                    <template x-if="availableOrderOffers.length === 0">
                        <p class="mb-0 fw-semibold text-muted text-center" style="font-size: 11px;">No offers available for this cart.</p>
                    </template>
                </div>
            </template>

            
            <template x-if="bestOrderOffer">
                <div class="d-flex align-items-center justify-content-between gap-3 p-3 rounded-4 bg-success bg-opacity-10 border border-success border-opacity-25 mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-success bg-opacity-25 text-success rounded-3 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                            <i class="bi bi-check-lg"></i>
                        </div>
                        <div>
                            <p class="mb-0 fw-bold text-success fs-6 lh-1" x-text="bestOrderOffer.name"></p>
                            <p class="mb-0 fw-semibold text-success" style="font-size: 10px;" x-text="'Saving ₹' + Number(orderDiscountAmount).toFixed(2)"></p>
                        </div>
                    </div>
                    <button type="button" @click.prevent="removeOrderOffer()"
                        class="btn btn-sm btn-light text-danger border-0 p-1">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </template>

            
            <template x-if="bogoDiscountTotal > 0">
                <div class="d-flex align-items-center justify-content-between gap-3 p-3 rounded-4 bg-light border mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-3 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                            <i class="bi bi-lightning-charge-fill"></i>
                        </div>
                        <div>
                            <p class="mb-0 fw-bold text-dark fs-6 lh-1">BOGO Savings</p>
                            <p class="mb-0 text-muted" style="font-size: 10px;">Auto-applied</p>
                        </div>
                    </div>
                    <span class="fw-black text-success fs-6" x-text="'- ₹' + Number(bogoDiscountTotal).toFixed(2)"></span>
                </div>
            </template>

            <div class="input-group" x-show="!couponApplied">
                <input type="text" x-model="couponCode" @keydown.enter.prevent="applyCoupon()"
                    placeholder="Promo code (SAVE10, FLAT50)"
                    class="form-control font-monospace text-uppercase" style="font-size: 12px;">
                <button type="button" @click.prevent="applyCoupon()"
                    class="btn btn-outline-primary fw-bold text-uppercase px-3" style="font-size: 11px; letter-spacing: 1px;">
                    Apply
                </button>
            </div>
            
            <div class="d-flex align-items-center justify-content-between p-3 rounded-4 bg-success bg-opacity-10 border border-success border-opacity-25" x-show="couponApplied" x-cloak>
                <div class="d-flex align-items-center gap-2 text-success">
                    <i class="bi bi-check-circle-fill"></i>
                    <span class="fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 1px;" x-text="'Coupon: ' + couponCode + ' (- ₹' + Number(couponDiscount).toFixed(2) + ')'"></span>
                </div>
                <button type="button" @click.prevent="removeCoupon()" class="btn-close shadow-none fs-6"></button>
            </div>
        </div>

        <div class="p-4 d-flex flex-column gap-2 bg-light bg-opacity-50 border-bottom">
            <div class="d-flex justify-content-between text-muted fw-semibold" style="font-size: 12px;">
                <span>Subtotal</span>
                <span class="text-dark fw-bold" x-text="'₹' + Number(subtotal).toFixed(2)"></span>
            </div>
            <div class="d-flex justify-content-between text-success fw-semibold" style="font-size: 12px;" x-show="bogoDiscountTotal > 0" x-cloak>
                <div>
                    <span>BOGO Savings</span>
                    <span class="d-block text-muted" style="font-size: 10px;">Auto-applied backend offer</span>
                </div>
                <span class="fw-bold align-top" x-text="'- ₹' + Number(bogoDiscountTotal).toFixed(2)"></span>
            </div>
            <div class="d-flex justify-content-between text-success fw-semibold" style="font-size: 12px;" x-show="orderDiscountAmount > 0" x-cloak>
                <div>
                    <span>Order Discount</span>
                    <span class="d-block text-muted" style="font-size: 10px;" x-text="orderDiscountLabel"></span>
                </div>
                <span class="fw-bold align-top" x-text="'- ₹' + Number(orderDiscountAmount).toFixed(2)"></span>
            </div>
            <div class="d-flex justify-content-between text-success fw-semibold" style="font-size: 12px;" x-show="couponDiscount > 0" x-cloak>
                <div>
                    <span>Coupon Savings</span>
                    <span class="d-block text-muted" style="font-size: 10px;" x-text="'(Code: ' + couponCode + ')'"></span>
                </div>
                <span class="fw-bold align-top" x-text="'- ₹' + Number(couponDiscount).toFixed(2)"></span>
            </div>
            <div class="d-flex justify-content-between text-muted fw-semibold" style="font-size: 12px;">
                <span>GST</span>
                <span class="text-dark" x-text="'₹' + Number(taxAmount).toFixed(2)"></span>
            </div>
            <hr class="my-2 opacity-10">
            <div class="d-flex justify-content-between align-items-center">
                <span class="fw-bold text-uppercase text-dark" style="font-size: 12px; letter-spacing: 1px;">Grand Total</span>
                <span class="fs-3 fw-black text-primary lh-1" x-text="'₹' + Number(grandTotal).toFixed(2)"></span>
            </div>
        </div>

        <div class="p-4 bg-white">
            <button type="button" @click.prevent="activeTab = 'review'; isCartOpen = false" x-bind:disabled="cart.length === 0"
                class="btn btn-primary btn-lg w-100 rounded-pill fw-bold text-uppercase d-flex align-items-center justify-content-center gap-2 shadow-sm transition-all" style="font-size: 12px; letter-spacing: 1.5px;">
                <i class="bi bi-check2-square fs-5"></i>
                Review & Place Order
            </button>
        </div>
    </div>
</div>
<style>
.translate-x-100 { transform: translateX(100%); }
.translate-x-0 { transform: translateX(0); }
</style>
<?php /**PATH /home/ubuntu/metis/resources/views/customers/partials/cart-sidebar.blade.php ENDPATH**/ ?>