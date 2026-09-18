{ 
    activeTab: @if(session('active_tab')) '{{ session('active_tab') }}' @else localStorage.getItem('customer_active_tab_{{ $customer->id }}') || 'overview' @endif,
    editingOrderId: null,
    editingOrderDetails: null,
    editingAddress: null,
    deletingAddress: null,
    villageSearch: '',
    villages: [],
    searchingVillages: false,
    productSearchQuery: '',
    productSearchResults: [],
    searchingProducts: false,
    productPage: 1,
    productPerPage: 15,
    productTotal: 0,
    productFrom: 0,
    productTo: 0,
    productLastPage: 1,
    productStockFilter: 'available',
    productCategoryFilter: '',
    cart: [],
    activeOffers: @js(($activeOffers ?? collect())->map(fn($offer) => [
        'id' => $offer->id,
        'name' => $offer->name,
        'type' => $offer->type,
        'discount_type' => $offer->discount_type,
        'value' => (float) $offer->value,
        'min_spend' => (float) $offer->min_spend,
        'max_discount' => $offer->max_discount !== null ? (float) $offer->max_discount : null,
        'product_id' => $offer->product_id,
        'buy_qty' => (int) $offer->buy_qty,
        'get_qty' => (int) $offer->get_qty,
        'priority' => (int) $offer->priority,
        'product_name' => $offer->product?->name,
    ])->values()),
    showSummary: false,
    selectedWarehouseId: '{{ $warehouses->first()?->id ?? '' }}',
    selectedBillingAddressId: '{{ $customer->addresses->where('is_default', true)->first()?->id ?? $customer->addresses->first()?->id ?? '' }}',
    selectedShippingAddressId: '{{ $customer->addresses->where('is_default', true)->first()?->id ?? $customer->addresses->first()?->id ?? '' }}',
    sameAsBilling: localStorage.getItem('customer_same_as_billing_{{ $customer->id }}') === 'false' ? false : true,
    
    orderType: 'sale',
    orderDate: '{{ date('Y-m-d') }}',
    orderStatus: 'pending',
    futureOrderDate: '',
    useWalletBalance: false,
    placing: false,
    formErrors: [],
    wallet_balance: {{ $customer->wallet_balance ?? 0 }},

    
    editOrder(order) {
        this.editingOrderId = order.id;
        this.editingOrderDetails = order;
        
        this.cart = (order.items || []).map(item => {
            const productDiscountType = item.product?.default_discount_type || (parseFloat(item.discount_amount) > 0 ? 'flat' : 'percent');
            const productDiscountValue = item.product?.default_discount != null
                ? parseFloat(item.product.default_discount) || 0
                : (productDiscountType === 'percent' ? 0 : ((parseFloat(item.discount_amount) || 0) / Math.max(parseInt(item.quantity) || 1, 1)));

            return {
                id: item.product_id,
                name: item.product?.name || 'Unknown Product',
                sku: item.product?.sku || '',
                price: parseFloat(item.unit_price) || 0,
                image_url: item.product?.image_url || '',
                quantity: parseInt(item.quantity) || 1,
                available: 999, 
                taxRate: parseFloat(item.product?.tax_rate?.rate ?? item.product?.taxRate?.rate ?? item.tax_rate) || 0,
                discountType: productDiscountType,
                discountValue: productDiscountValue
            };
        });
        
        if (order.billing_address_id) this.selectedBillingAddressId = order.billing_address_id;
        if (order.shipping_address_id) {
            this.selectedShippingAddressId = order.shipping_address_id;
            this.sameAsBilling = (order.billing_address_id == order.shipping_address_id);
        } else {
            this.sameAsBilling = true;
        }
        
        if (order.warehouse_id) this.selectedWarehouseId = order.warehouse_id;
        
        this.activeTab = 'order';
        this.notify('info', 'Order loaded for editing. You can now modify the cart.');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    },
    
    cancelEditOrder() {
        this.editingOrderId = null;
        this.editingOrderDetails = null;
        this.cart = [];
        this.activeTab = 'history';
        this.notify('info', 'Edit mode cancelled.');
    },

    init() {
        @if(request()->has('edit_order'))
            // Auto-load order editing from external routes (like Global Orders page)
            setTimeout(() => {
                const autoEditId = {{ request()->query('edit_order') }};
                const orders = window['customerOrders_{{ $customer->id }}'] || [];
                const orderToEdit = orders.find(o => o.id === autoEditId);
                if (orderToEdit) {
                    this.editOrder(orderToEdit);
                } else {
                    this.notify('error', 'Order not found or access denied.');
                }
            }, 300); // Wait for the DOM and window.customerOrders script to fully load
        @endif

        window.addEventListener('edit-order', (e) => {
            const orderId = e.detail;
            const orders = window['customerOrders_{{ $customer->id }}'] || [];
            const order = orders.find(o => o.id === orderId);
            if (order) this.editOrder(order);
        });

        // Watch for billing address changes to sync shipping if 'sameAsBilling' is active
        this.$watch('selectedBillingAddressId', (val) => {
            if (this.sameAsBilling) this.selectedShippingAddressId = val;
        });
        
        // Watch for sameAsBilling toggle
        this.$watch('sameAsBilling', (val) => {
            localStorage.setItem('customer_same_as_billing_{{ $customer->id }}', val);
            if (val) this.selectedShippingAddressId = this.selectedBillingAddressId;
        });

        // Clear cart ONLY if an order was successfully placed or updated
        @if(session('success') && (str_contains(session('success'), 'Order') || str_contains(session('success'), 'order')))
            localStorage.removeItem('customer_cart_{{ $customer->id }}');
            localStorage.removeItem('customer_active_tab_{{ $customer->id }}');
            localStorage.removeItem('customer_same_as_billing_{{ $customer->id }}');
            localStorage.removeItem('customer_applied_offer_{{ $customer->id }}');
            this.editingOrderId = null;
            this.editingOrderDetails = null;
            this.cart = [];
            this.appliedOrderOfferId = null;
            // Force a slight delay to ensure watchers don't override this with stale data
            setTimeout(() => {
                localStorage.removeItem('customer_cart_{{ $customer->id }}');
                localStorage.removeItem('customer_applied_offer_{{ $customer->id }}');
            }, 100);
        @endif

        // Load cart from localStorage
        const savedCart = localStorage.getItem('customer_cart_{{ $customer->id }}');
        if (savedCart) {
            try {
                this.cart = JSON.parse(savedCart);
            } catch (e) {
                console.error('Failed to parse saved cart');
            }
        }
        
        const savedOfferId = localStorage.getItem('customer_applied_offer_{{ $customer->id }}');
        if (savedOfferId) {
            this.appliedOrderOfferId = parseInt(savedOfferId, 10);
        }
        
        // Watch cart for changes and save to localStorage
        this.$watch('cart', (value) => {
            localStorage.setItem('customer_cart_{{ $customer->id }}', JSON.stringify(value));
            // If offer becomes invalid because of subtotal drop, it will naturally yield 0 discount, but let's clear it if availableOrderOffers doesn't have it
            setTimeout(() => {
                if (this.appliedOrderOfferId && !this.availableOrderOffers.find(o => o.id === this.appliedOrderOfferId)) {
                    this.appliedOrderOfferId = null;
                }
            }, 50);
        });
        
        this.$watch('appliedOrderOfferId', (value) => {
            if (value) {
                localStorage.setItem('customer_applied_offer_{{ $customer->id }}', value);
            } else {
                localStorage.removeItem('customer_applied_offer_{{ $customer->id }}');
            }
        });
        
        // Watch activeTab and save to localStorage
        this.$watch('activeTab', (val) => {
            if (val !== 'close') {
                localStorage.setItem('customer_active_tab_{{ $customer->id }}', val);
            }
        });

        this.searchProducts();
    },
    closeCustomerProfile() {
        const message = 'Profile tagged and closed successfully.';
        window.location.href = '{{ route('dashboard') }}?success=' + encodeURIComponent(message);
    },
    async searchProducts(resetPage = false) {
        if (resetPage) this.productPage = 1;
        this.searchingProducts = true;
        try {
            const params = new URLSearchParams({
                q: this.productSearchQuery,
                stock: this.productStockFilter,
                category: this.productCategoryFilter,
                perPage: this.productPerPage,
                page: this.productPage,
                exclude_order_id: this.editingOrderId || '',
            });
            const res = await fetch(`/products-search-api?${params}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) throw new Error('Network error');
            const json = await res.json();
            
            // Initialize temp inputs: _disc & _discType come from the product's pre-set defaults
            this.productSearchResults = (json.data || []).map(p => ({
                ...p,
                _qty: 1,
                _disc: parseFloat(p.default_discount) || 0,
                _discType: p.default_discount_type || 'percent'  // locked to product default type
            }));
            
            this.productTotal     = json.total || 0;
            this.productFrom      = json.from  || 0;
            this.productTo        = json.to    || 0;
            this.productLastPage  = json.last_page || 1;
        } catch (e) {
            this.notify('error', 'Failed to fetch products');
        } finally {
            this.searchingProducts = false;
        }
    },
    notify(type, message) {
        window.dispatchEvent(new CustomEvent('notify', { detail: { type, message } }));
    },
    async evaluateFreeProducts() {
        let expectedGifts = [];
        
        if (this.cart.some(item => !item.is_gift)) {
            const fpOffers = this.activeOffers.filter(o => o.type === 'free_product' && o.product_id);
            fpOffers.forEach(o => {
                if (this.subtotal >= (parseFloat(o.min_spend)||0)) {
                    const apps = typeof o.applicable_products === 'string' ? JSON.parse(o.applicable_products) : o.applicable_products;
                    const cats = typeof o.applicable_categories === 'string' ? JSON.parse(o.applicable_categories) : o.applicable_categories;
                    
                    let triggerQty = 0;
                    if ((apps && apps.length > 0) || (cats && cats.length > 0)) {
                        this.cart.forEach(item => {
                            if (item.is_gift) return;
                            if (apps && apps.length > 0 && (apps.includes(item.id) || apps.includes(String(item.id)))) {
                                triggerQty += parseInt(item.quantity) || 0;
                            } else if (cats && cats.length > 0 && (cats.includes(item.category_id) || cats.includes(String(item.category_id)))) {
                                triggerQty += parseInt(item.quantity) || 0;
                            }
                        });
                        
                        if (triggerQty > 0) {
                            const buyQty = parseInt(o.buy_qty) || 1;
                            const cycles = Math.floor(triggerQty / buyQty);
                            if (cycles > 0) {
                                expectedGifts.push({ product_id: o.product_id, qty: cycles * (parseInt(o.get_qty) || 1), source: 'offer_' + o.id });
                            }
                        }
                    } else {
                        expectedGifts.push({ product_id: o.product_id, qty: parseInt(o.get_qty)||1, source: 'offer_' + o.id });
                    }
                }
            });
            if (this.couponApplied && this.appliedCouponObj && this.appliedCouponObj.type === 'free_product' && this.appliedCouponObj.free_product_id) {
                if (this.subtotal >= (parseFloat(this.appliedCouponObj.min_spend)||0)) {
                    expectedGifts.push({ product_id: this.appliedCouponObj.free_product_id, qty: parseInt(this.appliedCouponObj.free_qty)||1, source: 'coupon_' + this.appliedCouponObj.code });
                }
            }
        }
        const validSources = expectedGifts.map(g => g.source);
        let cleanedCart = this.cart.filter(item => !item.is_gift || validSources.includes(item.gift_source));
        for (const gift of expectedGifts) {
            const existing = cleanedCart.find(i => i.is_gift && i.gift_source === gift.source);
            if (existing) {
                if (existing.quantity !== gift.qty) existing.quantity = gift.qty;
            } else {
                const productObj = this.productSearchResults.find(p => p.id === gift.product_id);
                if (productObj) {
                    cleanedCart.push({
                        id: productObj.id, name: productObj.name, sku: productObj.sku, price: productObj.selling_price, image_url: productObj.image_url,
                        quantity: gift.qty, available: 999, taxRate: 0, discountValue: productObj.selling_price, discountType: 'amount', category_id: productObj.category_id, is_gift: true, gift_source: gift.source
                    });
                }
            }
        }
        if (JSON.stringify(cleanedCart) !== JSON.stringify(this.cart)) {
            this.cart = cleanedCart;
        }
    },
    getBogoMatch(id) {
        return this.activeBogoOffers.find(o => Number(o.product_id) === Number(id));
    },
    calculateAutoBogoQty(id, newQty, delta) {
        const match = this.getBogoMatch(id);
        if (!match) return newQty;
        
        const buyQty = parseInt(match.buy_qty)||1;
        const getQty = parseInt(match.get_qty)||1;
        const cycle = buyQty + getQty;
        
        if (delta > 0) {
            let completeCycles = Math.floor(newQty / cycle);
            let remainder = newQty % cycle;
            if (remainder >= buyQty) {
                return (completeCycles * cycle) + buyQty + getQty;
            }
        } else if (delta < 0) {
            let completeCycles = Math.floor(newQty / cycle);
            let remainder = newQty % cycle;
            if (remainder >= buyQty) {
                return (completeCycles * cycle) + buyQty - 1;
            }
        }
        return newQty;
    },
    
    addToCartWithOptions(product) {
        const qty = parseInt(product._qty) || 1;
        const discValue = parseFloat(product._disc) || 0;
        const discType = product._discType || 'percent';
        
        if (qty <= 0) {
            this.notify('warning', 'Please enter a valid quantity');
            return;
        }
        
        const existingIndex = this.cart.findIndex(i => i.id === product.id);
        const existingQty = existingIndex !== -1 ? this.cart[existingIndex].quantity : 0;
        
        if (existingQty + qty > product.available_stock && product.available_stock !== 999) {
            this.notify('warning', 'Insufficient stock available');
            return;
        }

        if (existingIndex !== -1) {
            this.cart[existingIndex].quantity += qty;
            if (discValue > 0) {
                this.cart[existingIndex].discountValue = discValue;
                this.cart[existingIndex].discountType = discType;
            }
            this.notify('success', `Updated ${product.name} in cart`);
        } else {
            this.cart.push({
                id: product.id,
                name: product.name,
                sku: product.sku,
                price: product.selling_price || 0,
                image_url: product.image_url,
                quantity: qty,
                available: product.available_stock || 999,
                taxRate: parseFloat(product.tax_rate) || 0,
                discountType: discType,
                discountValue: discValue,
                category_id: product.category_id || null,
                is_gift: false
            });
            this.notify('success', `Added ${product.name} to cart`);
        }
        
        product._qty = 1;
        product._disc = 0;
        this.evaluateFreeProducts();
    },
    addToCart(product) {
        this.addToCartWithOptions({
            ...product,
            _qty: 1,
            _disc: 0,
            _discType: 'percent'
        });
    },
    addByBarcode(product) {
        if (!product) return;
        this.addToCartWithOptions({
            ...product,
            _qty: 1,
            _disc: product.default_discount || 0,
            _discType: product.default_discount_type || 'flat'
        });
    },
    updateCartQty(index, delta) {
        const item = this.cart[index];
        if (!item) return;
        if (item.is_gift) return; // Skip updating gift items manually
        const newQty = item.quantity + delta;
        if (newQty <= 0) {
            this.removeFromCart(index);
        } else {
            const finalQty = this.calculateAutoBogoQty(item.id, newQty, delta);
            if (item.available !== 999 && finalQty > item.available) {
                this.notify('error', 'Cannot add more, stock limit reached!');
                item.quantity = item.available;
            } else {
                item.quantity = finalQty;
            }
            this.evaluateFreeProducts();
        }
    },
    removeFromCart(index) {
        const item = this.cart[index];
        this.cart.splice(index, 1);
        if (item) this.notify('info', `Removed ${item.name} from cart`);
        this.evaluateFreeProducts();
    },
    isCartOpen: false,
    couponCode: '',
    couponApplied: false,
    couponDiscount: 0,
    isFlatDiscount(type) {
        return ['flat', 'amount', 'fixed'].includes(String(type || '').toLowerCase());
    },
    get activeOrderOffers() {
        return (this.activeOffers || []).filter(offer => offer.type === 'order_discount');
    },
    get activeBogoOffers() {
        return (this.activeOffers || []).filter(offer => offer.type === 'bogo');
    },
    itemDiscountAmount(item) {
        const base = (parseFloat(item.price) || 0) * (parseFloat(item.quantity) || 0);
        const value = parseFloat(item.discountValue) || 0;

        if (value <= 0 || base <= 0) return 0;

        if (!this.isFlatDiscount(item.discountType)) {
            return Math.min(base * (value / 100), base);
        }

        return Math.min(value * (parseFloat(item.quantity) || 0), base);
    },
    itemLineTotal(item) {
        const base = (parseFloat(item.price) || 0) * (parseFloat(item.quantity) || 0);
        return Math.max(0, base - this.itemDiscountAmount(item));
    },
    itemTaxAmount(item) {
        return this.itemLineTotal(item) * ((parseFloat(item.taxRate) || 0) / 100);
    },
    get bogoDiscountTotal() {
        return 0; // Handled via evaluateFreeProducts
    },
    get appliedBogoIds() {
        const bogos = this.activeBogoOffers.sort((a,b)=>(b.priority - a.priority) || (a.id - b.id));
        const ids = [];
        this.cart.forEach(item => {
            const match = bogos.find(o=> Number(o.product_id)===Number(item.id)) || bogos.find(o=> !o.product_id);
            if (!match) return;
            
            if ((parseFloat(match.min_spend) || 0) > this.subtotal) return;

            const buyQty = parseInt(match.buy_qty)||1;
            const getQty = parseInt(match.get_qty)||1;
            const cycle = buyQty + getQty;
            const qty = parseInt(item.quantity)||0;
            if(qty >= buyQty) ids.push(match.id);
        });
        return [...new Set(ids)];
    },
    get subtotal() {
        return this.cart.reduce((t, item) => t + this.itemLineTotal(item), 0);
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
    appliedOrderOfferId: null,
    isOffersModalOpen: false,
    
    get availableOrderOffers() {
        return this.activeOrderOffers
            .map(offer => ({ ...offer, computed_discount: this.orderOfferDiscount(offer) }))
            .filter(offer => offer.computed_discount > 0)
            .sort((a, b) => (b.computed_discount - a.computed_discount) || (a.id - b.id));
    },
    
    get bestOrderOffer() {
        if (!this.appliedOrderOfferId) return null;
        return this.availableOrderOffers.find(o => o.id === this.appliedOrderOfferId) || null;
    },
    
    applyOrderOffer(offerId) {
        this.appliedOrderOfferId = offerId;
        this.isOffersModalOpen = false;
        this.notify('success', 'Offer applied successfully');
    },
    
    removeOrderOffer() {
        this.appliedOrderOfferId = null;
        this.notify('info', 'Offer removed');
    },
    get orderDiscountAmount() {
        return this.bestOrderOffer ? this.bestOrderOffer.computed_discount : 0;
    },
    get orderDiscountLabel() {
        if (!this.bestOrderOffer) return '';
        if (this.bestOrderOffer.discount_type === 'percentage') {
            return `${this.bestOrderOffer.name} (${this.bestOrderOffer.value}% off)`;
        }

        return `${this.bestOrderOffer.name} (Flat ₹ ${Number(this.bestOrderOffer.value).toFixed(2)})`;
    },
    get taxAmount() {
        return this.cart.reduce((t, item) => t + this.itemTaxAmount(item), 0);
    },
    get totalDiscount() {
        return Math.min(this.subtotal, this.bogoDiscountTotal + this.orderDiscountAmount + this.couponDiscount);
    },
    get grandTotal() {
        return Math.max(0, this.subtotal - this.totalDiscount + this.taxAmount);
    },
    applyCoupon() {
        const code = this.couponCode.toUpperCase().trim();
        if (!code) return;
        
        const csrfToken = document.querySelector('meta[name=csrf-token]').getAttribute('content');
        fetch('{{ route('coupons.validate') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                code: code,
                subtotal: this.subtotal
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.valid) {
                this.couponDiscount = data.discount;
                this.couponApplied = true;
                this.notify('success', data.message);
            } else {
                this.couponDiscount = 0;
                this.couponApplied = false;
                this.notify('error', data.message || 'Invalid promo code');
            }
        })
        .catch(error => {
            console.error(error);
            this.notify('error', 'Failed to validate promo code');
        });
    },
    removeCoupon() { 
        this.couponCode = ''; 
        this.couponDiscount = 0; 
        this.couponApplied = false; 
        this.notify('info', 'Coupon removed');
    },
    openAddModal() {
        this.editingAddress = null;
        this.resetVillageSearch();
        $dispatch('open-modal', { name: 'address-modal' });
    },
    openEditModal(address) {
        this.editingAddress = { ...address };
        this.resetVillageSearch();
        if (address && address.village) {
            this.editingAddress.village_name = address.village.village_name || address.village.name;
            this.editingAddress.post_office = address.village.post_so_name || address.village.post_office;
            this.editingAddress.taluka = address.village.taluka_name || address.village.taluka;
            this.editingAddress.district = address.village.district_name || address.village.district;
            this.editingAddress.city = address.village.district_name || address.village.district || address.village.city;
            this.editingAddress.state = (address.village && address.village.state_name) ? address.village.state_name : (address.state || '');
            this.editingAddress.pincode = address.village.pincode;
            this.villageSearch = this.editingAddress.village_name;
        } else if (address) {
            // Fallback for direct address fields if village object is missing
            this.editingAddress.city = address.city || address.district;
        }
        $dispatch('open-modal', { name: 'address-modal' });
    },
    openDeleteModal(address) {
        this.deletingAddress = address;
        $dispatch('open-modal', { name: 'delete-address-modal' });
    },
    resetVillageSearch() {
        this.villageSearch = '';
        this.villages = [];
    },
    async searchVillages() {
        if (this.villageSearch.length < 3) {
            this.villages = [];
            return;
        }
        this.searchingVillages = true;
        try {
            const res = await fetch(`/villages-search?q=${this.villageSearch}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) throw new Error('Network response was not ok');
            const data = await res.json();
            this.villages = data.data || [];
        } catch (e) {
            console.error('Village search failed');
        } finally {
            this.searchingVillages = false;
        }
    },
    selectVillage(v) {
        if (!this.editingAddress) {
            this.editingAddress = {
                label: 'Home',
                address_line_1: '',
                address_line_2: '',
                village_id: v.id,
                village_name: v.name,
                post_office: v.post_office || '',
                taluka: v.taluka || '',
                district: v.district || '',
                state: v.state || '',
                pincode: v.pincode || '',
                city: v.district || '',
                status: 'active',
                is_default: false
            };
        } else {
            this.editingAddress.village_id = v.id;
            this.editingAddress.village_name = v.name || v.village_name;
            this.editingAddress.post_office = v.post_office || v.post_so_name || '';
            this.editingAddress.taluka = v.taluka || v.taluka_name || '';
            this.editingAddress.district = v.district || v.district_name || '';
            this.editingAddress.state = v.state_name || v.state || '';
            this.editingAddress.pincode = v.pincode || '';
            this.editingAddress.city = v.district || v.district_name || '';
        }
        this.villages = [];
        this.villageSearch = v.name;
    },
    
    buildCartPayload() {
        return this.cart.map(item => {
            const base = (parseFloat(item.price)||0) * (parseInt(item.quantity)||0);
            const disc = this.itemLineTotal(item) < base ? base - this.itemLineTotal(item) : 0;
            const tax = this.itemLineTotal(item) * ((parseFloat(item.taxRate)||0)/100);
            return { 
                product_id: item.id, 
                quantity: item.quantity, 
                unit_price: item.price, 
                discount_amount: parseFloat(disc.toFixed(2)), 
                tax_amount: parseFloat(tax.toFixed(2)), 
                total_amount: parseFloat(this.itemLineTotal(item).toFixed(2)),
                is_gift: item.is_gift ? 1 : 0,
                gift_source: item.gift_source || null
            };
        });
    },

    async placeOrder() {
        this.formErrors = [];
        if (!this.selectedWarehouseId) { this.formErrors.push('Please select a warehouse.'); return; }
        if (!this.selectedShippingAddressId) { this.formErrors.push('Please select a shipping address.'); return; }
        if (!this.sameAsBilling && !this.selectedBillingAddressId) { this.formErrors.push('Please select a billing address.'); return; }
        if (this.cart.length === 0) { this.formErrors.push('Cart is empty.'); return; }
        if (this.orderStatus === 'future_order' && !this.futureOrderDate) { this.formErrors.push('Please set future order date.'); return; }

        this.placing = true;
        try {
            const payload = {
                type: this.orderType,
                party_id: {{ $customer->id }},
                warehouse_id: this.selectedWarehouseId,
                shipping_address_id: this.selectedShippingAddressId || null,
                billing_address_id: this.sameAsBilling ? (this.selectedShippingAddressId || null) : (this.selectedBillingAddressId || null),
                order_date: this.orderDate,
                items: this.buildCartPayload(),
                status: this.orderStatus,
                future_order_date: this.orderStatus === 'future_order' ? this.futureOrderDate : null,
                coupon_code: this.couponApplied ? this.couponCode : null,
                applied_offer_id: (this.appliedOrderOfferId && this.appliedOrderOfferId !== 'none') ? this.appliedOrderOfferId : null,
                applied_bogo_ids: this.appliedBogoIds,
                total_amount: parseFloat(this.subtotal.toFixed(2)),
                tax_amount: parseFloat(this.taxAmount.toFixed(2)),
                discount_amount: parseFloat(this.totalDiscount.toFixed(2)),
                net_amount: parseFloat(this.grandTotal.toFixed(2)),
                use_wallet_balance: this.useWalletBalance ? 1 : 0,
            };
            const url = this.editingOrderId ? `/orders/${this.editingOrderId}` : '/orders';
            const res = await fetch(url, { 
                method: this.editingOrderId ? 'PUT' : 'POST', 
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept': 'application/json'
                }, 
                body: JSON.stringify(payload) 
            });
            const json = await res.json();
            if (!res.ok) {
                this.formErrors = Object.values(json.errors||{}).flat();
                if (!this.formErrors.length && json.message) this.formErrors.push(json.message);
                if (this.formErrors.length) {
                    this.notify('error', this.formErrors[0]);
                }
                return;
            }
            
            // Success
            localStorage.removeItem('customer_cart_{{ $customer->id }}');
            localStorage.removeItem('customer_active_tab_{{ $customer->id }}');
            localStorage.removeItem('customer_applied_offer_{{ $customer->id }}');
            this.cart = [];
            this.appliedOrderOfferId = null;
            
            const successMessage = this.editingOrderId ? 'Order updated successfully!' : 'Order placed successfully!';

            // IMMEDIATE CONFIRMATION STEP
            try {
                const confirmPayload = {
                    action: this.orderStatus === 'future_order' ? 'schedule' : 'confirm',
                    reason: this.orderStatus === 'future_order' ? 'future_order' : null,
                    scheduled_date: this.orderStatus === 'future_order' ? this.futureOrderDate : null,
                    notes: 'Order placed via Customer Profile'
                };
                const confirmRes = await fetch(`/orders/${json.order?.id || json.data?.id || this.editingOrderId}/confirm`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify(confirmPayload)
                });
                if (!confirmRes.ok) {
                    console.warn('Order saved, but confirmation failed.');
                }
            } catch(e) { console.warn('Error confirming order:', e); }
            this.notify('success', successMessage);
            
            if (this.editingOrderId) {
                this.editingOrderId = null;
                this.editingOrderDetails = null;
            }
            
            this.activeTab = 'history';
            
            // Wait a moment then reload to ensure order history reflects new order
            setTimeout(() => {
                window.location.reload();
            }, 1000);
            
        } catch(e) { 
            this.formErrors.push('An unexpected error occurred.'); 
            this.notify('error', 'An unexpected error occurred.');
        } finally { 
            this.placing = false; 
        }
    }
}
