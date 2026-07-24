<?php
$content = file_get_contents('resources/views/orders/create.blade.php');

$searchOffers = <<<EOD
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
EOD;

$replaceOffers = <<<EOD
                                    <div class="card border-2 rounded-4 transition-all hover-shadow" 
                                         :class="offer.type === 'bogo' ? 'border-info border-opacity-25 bg-info bg-opacity-10' : (appliedOfferId === offer.id ? 'border-success bg-success bg-opacity-10' : (orderOfferDiscount(offer) > 0 ? 'border-secondary border-opacity-10 bg-body-tertiary cursor-pointer' : 'border-secondary border-opacity-10 bg-body-secondary opacity-75'))" 
                                         @click="if(offer.type === 'order_discount' && orderOfferDiscount(offer) > 0) appliedOfferId = (appliedOfferId === offer.id) ? 'none' : offer.id">
                                        <div class="card-body p-3 d-flex align-items-center justify-content-between gap-3">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="border border-dashed border-2 rounded-3 p-2 bg-body text-center d-flex flex-column justify-content-center align-items-center" style="min-width: 90px; height: 90px;">
                                                    <template x-if="offer.type === 'bogo'">
                                                        <div>
                                                            <i class="bi bi-gift-fill text-info fs-3 d-block mb-1"></i>
                                                            <span class="badge bg-info bg-opacity-10 text-info w-100">BOGO</span>
                                                        </div>
                                                    </template>
                                                    <template x-if="offer.type === 'order_discount'">
                                                        <div>
                                                            <h5 class="fw-black text-body-emphasis mb-1" x-text="offer.discount_type === 'percentage' ? parseFloat(offer.value) + '%' : 'Rs ' + parseFloat(offer.value)"></h5>
                                                            <span class="badge bg-primary bg-opacity-10 text-primary w-100">OFF</span>
                                                        </div>
                                                    </template>
                                                </div>
                                                
                                                <div class="ps-2">
                                                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                                        <h6 class="fw-bold mb-0" :class="(appliedOfferId === offer.id || offer.type === 'bogo') ? 'text-body-emphasis' : 'text-body'" x-text="offer.name"></h6>
                                                        <span class="badge bg-secondary bg-opacity-10 text-secondary-emphasis rounded-pill px-2 py-0.5 small" style="font-size: 0.7rem;" x-text="'Priority: ' + offer.priority"></span>
                                                    </div>
                                                    
                                                    {{-- Common Rules --}}
                                                    <div class="mb-2 pe-3 ps-2 border-start border-2 border-secondary border-opacity-25">
                                                        <div x-show="offer.type === 'bogo'">
                                                            <p class="mb-1 small text-muted" x-text="'Buy ' + offer.buy_qty + ' Get ' + offer.get_qty + ' Free on ' + offer.product_name"></p>
                                                        </div>
                                                        <div x-show="offer.type === 'order_discount'">
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
EOD;

$searchCoupons = <<<EOD
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
EOD;

$replaceCoupons = <<<EOD
                                    <div class="card border-2 rounded-4 transition-all hover-shadow cursor-pointer" :class="(couponApplied && couponCode === c.code) ? 'border-success bg-success bg-opacity-10' : 'border-secondary border-opacity-10 bg-body-tertiary'" @click="applyCoupon(c.code)">
                                        <div class="card-body p-3 d-flex align-items-center justify-content-between gap-3">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="border border-dashed border-2 rounded-3 p-2 bg-body text-center d-flex flex-column justify-content-center align-items-center" style="min-width: 90px; height: 90px;">
                                                    <h5 class="fw-black text-body-emphasis mb-1" x-text="c.type === 'percentage' ? parseFloat(c.value) + '%' : 'Rs ' + parseFloat(c.value)"></h5>
                                                    <span class="badge bg-primary bg-opacity-10 text-primary w-100">OFF</span>
                                                </div>
                                                <div class="ps-2">
                                                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                                        <code class="fw-black text-body-emphasis fs-6 d-block" x-text="c.code"></code>
                                                    </div>
                                                    <div class="pe-3 ps-2 border-start border-2 border-secondary border-opacity-25">
                                                        <p class="mb-1 small text-muted" x-show="c.min_spend > 0" x-text="'Min. Spend: Rs ' + Number(c.min_spend).toFixed(2)"></p>
                                                        <p class="mb-1 small text-muted" x-show="c.max_discount > 0" x-text="'Max Discount: Rs ' + Number(c.max_discount).toFixed(2)"></p>
                                                        <p class="mb-1 small text-muted" x-show="c.usage_limit > 0" x-text="'Remaining Uses: ' + Math.max(0, c.usage_limit - c.used_count)"></p>
                                                        <p class="mb-0 small text-muted" x-show="c.expiry_date" x-text="'Valid till ' + new Date(c.expiry_date).toLocaleDateString()"></p>
                                                    </div>
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
EOD;

$content = str_replace($searchOffers, $replaceOffers, $content);
$content = str_replace($searchCoupons, $replaceCoupons, $content);

file_put_contents('resources/views/orders/create.blade.php', $content);
echo "Layout alignment patched.\n";
