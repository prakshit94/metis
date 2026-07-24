<?php
$content = file_get_contents('resources/views/orders/create.blade.php');

// 1. Add shippingFee and is_gift flag to cart items
$search1 = "shippingAddressId: '', billingAddressId: '', sameAsShipping: true, orderType: 'sale',";
$replace1 = "shippingAddressId: '', billingAddressId: '', sameAsShipping: true, orderType: 'sale', shippingFee: 0,";
$content = str_replace($search1, $replace1, $content);

// 2. Fix bogoDiscount and orderOfferDiscountAmount
// We will replace everything from `get bogoDiscount()` up to `get totalDiscount()`
$search2 = "get bogoDiscount() {
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
                if(match && !ids.includes(match.id)) {
                    const cycle = parseInt(match.buy_qty||1) + parseInt(match.get_qty||1);
                    if(item.quantity >= cycle) ids.push(match.id);
                }
            });
            return ids;
        },
        get availableOrderOffers() {
            return this.activeOffers.filter(o => o.type === 'order_discount' && (parseFloat(o.min_spend)||0) <= this.subtotal);
        },
        get sortedActiveOffers() {
            return [...this.activeOffers].sort((a,b) => {
                if (a.type !== b.type) return a.type === 'bogo' ? -1 : 1;
                return (b.priority - a.priority) || (a.id - b.id);
            });
        },
        get bestOrderOffer() {
            if (this.appliedOfferId && this.appliedOfferId !== 'none') {
                return this.availableOrderOffers.find(o => o.id === this.appliedOfferId) || null;
            }
            return null;
        },
        orderOfferDiscount(o) {
            if (!o) return 0;
            if ((parseFloat(o.min_spend)||0) > this.subtotal) return 0;
            let d = o.discount_type === 'percentage' ? this.subtotal * (parseFloat(o.value) / 100) : parseFloat(o.value);
            if ((parseFloat(o.max_discount)||0) > 0) d = Math.min(d, parseFloat(o.max_discount));
            return Math.min(d, this.subtotal);
        },
        get orderOfferDiscountAmount() {
            return this.orderOfferDiscount(this.bestOrderOffer);
        },
        get couponDiscount() {
            if (!this.couponApplied || !this.appliedCouponObj) return 0;
            const c = this.appliedCouponObj;
            if ((parseFloat(c.min_spend) || 0) > this.subtotal) return 0;
            
            // Check applicable/excluded
            let eligibleSubtotal = this.cart.reduce((t, item) => {
                if (c.applicable_products && c.applicable_products.length > 0 && !c.applicable_products.includes(item.id)) return t;
                if (c.excluded_products && c.excluded_products.length > 0 && c.excluded_products.includes(item.id)) return t;
                return t + this.lineTotal(item);
            }, 0);

            if (eligibleSubtotal <= 0) return 0;

            let d = c.type === 'percentage' ? eligibleSubtotal * (parseFloat(c.value) / 100) : parseFloat(c.value);
            if ((parseFloat(c.max_discount) || 0) > 0) d = Math.min(d, parseFloat(c.max_discount));
            return Math.min(d, eligibleSubtotal);
        },
        get totalDiscount() { return Math.min(this.subtotal, this.bogoDiscount + this.couponDiscount + this.orderOfferDiscountAmount); },
        get grandTotal() { return Math.max(0, this.subtotal - this.totalDiscount + this.taxAmount); },";

$replace2 = "get bogoDiscount() {
            const bogos = this.activeOffers
                .filter(o=>o.type==='bogo')
                .sort((a,b)=>(b.priority - a.priority) || (a.id - b.id));
            return this.cart.reduce((t,item)=>{
                if(item.is_gift) return t; // Skip gift items
                const match = bogos.find(o=> Number(o.product_id)===Number(item.id)) || bogos.find(o=> !o.product_id);
                if(!match) return t;
                
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
                if(item.is_gift) return;
                const match = bogos.find(o=> Number(o.product_id)===Number(item.id)) || bogos.find(o=> !o.product_id);
                if(match && !ids.includes(match.id)) {
                    const cycle = parseInt(match.buy_qty||1) + parseInt(match.get_qty||1);
                    if(item.quantity >= cycle) ids.push(match.id);
                }
            });
            return ids;
        },
        get availableOrderOffers() {
            return this.activeOffers.filter(o => ['order_discount', 'category_discount'].includes(o.type) && (parseFloat(o.min_spend)||0) <= this.subtotal);
        },
        get sortedActiveOffers() {
            return [...this.activeOffers].sort((a,b) => {
                if (a.type !== b.type) return (a.type === 'bogo' || a.type === 'free_product') ? -1 : 1;
                return (b.priority - a.priority) || (a.id - b.id);
            });
        },
        get bestOrderOffer() {
            if (this.appliedOfferId && this.appliedOfferId !== 'none') {
                return this.availableOrderOffers.find(o => o.id === this.appliedOfferId) || null;
            }
            return null;
        },
        orderOfferDiscount(o) {
            if (!o || !['order_discount', 'category_discount'].includes(o.type)) return 0;
            if ((parseFloat(o.min_spend)||0) > this.subtotal) return 0;
            
            let eligibleSubtotal = this.subtotal;
            if (o.type === 'category_discount' && o.applicable_categories && o.applicable_categories.length > 0) {
                const cats = typeof o.applicable_categories === 'string' ? JSON.parse(o.applicable_categories) : o.applicable_categories;
                eligibleSubtotal = this.cart.reduce((t, item) => {
                    if (item.is_gift) return t;
                    if (cats.includes(item.category_id) || cats.includes(String(item.category_id))) {
                        return t + this.lineTotal(item);
                    }
                    return t;
                }, 0);
            }
            
            if (eligibleSubtotal <= 0) return 0;

            let d = o.discount_type === 'percentage' ? eligibleSubtotal * (parseFloat(o.value) / 100) : parseFloat(o.value);
            if ((parseFloat(o.max_discount)||0) > 0) d = Math.min(d, parseFloat(o.max_discount));
            return Math.min(d, eligibleSubtotal);
        },
        get orderOfferDiscountAmount() {
            return this.orderOfferDiscount(this.bestOrderOffer);
        },
        get couponDiscount() {
            if (!this.couponApplied || !this.appliedCouponObj) return 0;
            const c = this.appliedCouponObj;
            if (c.type === 'free_shipping' || c.type === 'free_product') return 0; // Handled outside discount math
            if ((parseFloat(c.min_spend) || 0) > this.subtotal) return 0;
            
            // Check applicable/excluded
            let eligibleSubtotal = this.cart.reduce((t, item) => {
                if (item.is_gift) return t; // gifts don't get discounts
                const apps = typeof c.applicable_products === 'string' ? JSON.parse(c.applicable_products) : c.applicable_products;
                const excs = typeof c.excluded_products === 'string' ? JSON.parse(c.excluded_products) : c.excluded_products;
                const appCats = typeof c.applicable_categories === 'string' ? JSON.parse(c.applicable_categories) : c.applicable_categories;
                if (apps && apps.length > 0 && !apps.includes(item.id) && !apps.includes(String(item.id))) return t;
                if (excs && excs.length > 0 && (excs.includes(item.id) || excs.includes(String(item.id)))) return t;
                if (appCats && appCats.length > 0 && !appCats.includes(item.category_id) && !appCats.includes(String(item.category_id))) return t;
                return t + this.lineTotal(item);
            }, 0);

            if (eligibleSubtotal <= 0) return 0;

            let d = c.type === 'percentage' ? eligibleSubtotal * (parseFloat(c.value) / 100) : parseFloat(c.value);
            if ((parseFloat(c.max_discount) || 0) > 0) d = Math.min(d, parseFloat(c.max_discount));
            return Math.min(d, eligibleSubtotal);
        },
        get totalDiscount() { return Math.min(this.subtotal, this.bogoDiscount + this.couponDiscount + this.orderOfferDiscountAmount); },
        get grandTotal() { 
            let shipping = this.shippingFee;
            if (this.couponApplied && this.appliedCouponObj && this.appliedCouponObj.type === 'free_shipping') {
                if (this.subtotal >= (parseFloat(this.appliedCouponObj.min_spend) || 0)) {
                    shipping = 0;
                }
            }
            return Math.max(0, this.subtotal - this.totalDiscount + this.taxAmount + shipping); 
        },";

$content = str_replace($search2, $replace2, $content);

// 3. Prevent addToCart from implicitly adding BOGO items. We now rely on explicit gift logic.
// In `addToCart(p)`, remove the BOGO injection entirely.
$search3 = "            // Auto-BOGO Quantity Injection
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
            }";
$content = str_replace($search3, '', $content);

// 4. Implement auto-injection for `free_product` in `init()` watcher.
$search4 = "            this.$watch('cart', v => {
                localStorage.setItem('ecommerce_create_order_cart', JSON.stringify(v));
                window.dispatchEvent(new CustomEvent('cart-updated'));
                if (v.length === 0) {
                    this.removeCoupon();
                    this.appliedOfferId = null;
                    this.isCartSidebarOpen = false;
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
            });";
$replace4 = "            this.$watch('cart', async (v) => {
                localStorage.setItem('ecommerce_create_order_cart', JSON.stringify(v));
                window.dispatchEvent(new CustomEvent('cart-updated'));
                if (v.length === 0) {
                    this.removeCoupon();
                    this.appliedOfferId = null;
                    this.isCartSidebarOpen = false;
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
                await this.evaluateFreeProducts();
            });";
$content = str_replace($search4, $replace4, $content);

// 5. Add evaluateFreeProducts method
$search5 = "        isInCart(id) { return this.cart.some(i => i.id === id); },";
$replace5 = "        async evaluateFreeProducts() {
            // Find what free products should be in cart
            let expectedGifts = [];
            
            // Check Offers of type free_product
            const fpOffers = this.activeOffers.filter(o => o.type === 'free_product' && o.product_id);
            fpOffers.forEach(o => {
                if (this.subtotal >= (parseFloat(o.min_spend)||0)) {
                    // Check applicable products trigger
                    const apps = typeof o.applicable_products === 'string' ? JSON.parse(o.applicable_products) : o.applicable_products;
                    let hasTrigger = true;
                    if (apps && apps.length > 0) {
                        hasTrigger = this.cart.some(item => !item.is_gift && (apps.includes(item.id) || apps.includes(String(item.id))));
                    }
                    if (hasTrigger) {
                        expectedGifts.push({ product_id: o.product_id, qty: parseInt(o.get_qty)||1, source: 'offer_' + o.id });
                    }
                }
            });
            
            // Check Coupons of type free_product
            if (this.couponApplied && this.appliedCouponObj && this.appliedCouponObj.type === 'free_product' && this.appliedCouponObj.free_product_id) {
                if (this.subtotal >= (parseFloat(this.appliedCouponObj.min_spend)||0)) {
                    expectedGifts.push({ product_id: this.appliedCouponObj.free_product_id, qty: parseInt(this.appliedCouponObj.free_qty)||1, source: 'coupon_' + this.appliedCouponObj.code });
                }
            }
            
            // Clean up existing gifts that shouldn't be there
            const validSources = expectedGifts.map(g => g.source);
            let cleanedCart = this.cart.filter(item => !item.is_gift || validSources.includes(item.gift_source));
            
            // Inject missing gifts
            for (const gift of expectedGifts) {
                const existing = cleanedCart.find(i => i.is_gift && i.gift_source === gift.source);
                if (existing) {
                    if (existing.quantity !== gift.qty) existing.quantity = gift.qty;
                } else {
                    // Fetch product details if not already loaded
                    const productObj = this.products.find(p => p.id === gift.product_id) || await this.fetchProductDetails(gift.product_id);
                    if (productObj) {
                        cleanedCart.push({
                            id: productObj.id,
                            name: productObj.name,
                            sku: productObj.sku,
                            price: productObj.selling_price,
                            image_url: productObj.image_url,
                            quantity: gift.qty,
                            available: productObj.available_stock,
                            taxRate: 0,
                            discountValue: productObj.selling_price, // 100% off
                            discountType: 'amount',
                            category_id: productObj.category_id,
                            is_gift: true,
                            gift_source: gift.source
                        });
                        window.dispatchEvent(new CustomEvent('notify', { detail: { type: 'success', message: `Free Gift added: ${productObj.name}` }}));
                    }
                }
            }
            
            if (JSON.stringify(cleanedCart) !== JSON.stringify(this.cart)) {
                this.cart = cleanedCart;
            }
        },
        async fetchProductDetails(id) {
            try {
                const res = await fetch(`/products/${id}`, { headers: {'Accept':'application/json'} });
                const json = await res.json();
                return json.data;
            } catch(e) { return null; }
        },
        isInCart(id) { return this.cart.some(i => i.id === id); },";
$content = str_replace($search5, $replace5, $content);

file_put_contents('resources/views/orders/create.blade.php', $content);
echo "Cart math patched.\n";
