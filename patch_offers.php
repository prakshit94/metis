<?php
$content = file_get_contents('resources/views/promotions/offers.blade.php');

// 1. Add categories to form state
$search1 = "form: { id: null, name: '', type: 'order_discount', discount_type: 'percentage', value: '', min_spend: '', max_discount: '', product_ids: [], buy_qty: 1, get_qty: 1, starts_at: '', ends_at: '', priority: 0, is_active: true },";
$replace1 = "form: { id: null, name: '', type: 'order_discount', discount_type: 'percentage', value: '', min_spend: '', max_discount: '', product_ids: [], product_id: '', applicable_categories: [], buy_qty: 1, get_qty: 1, starts_at: '', ends_at: '', priority: 0, is_active: true },";
$content = str_replace($search1, $replace1, $content);

$search1b = "this.form = { id: null, name: '', type: 'order_discount', discount_type: 'percentage', value: '', min_spend: '', max_discount: '', product_ids: [], buy_qty: 1, get_qty: 1, starts_at: '', ends_at: '', priority: 0, is_active: true };";
$replace1b = "this.form = { id: null, name: '', type: 'order_discount', discount_type: 'percentage', value: '', min_spend: '', max_discount: '', product_ids: [], product_id: '', applicable_categories: [], buy_qty: 1, get_qty: 1, starts_at: '', ends_at: '', priority: 0, is_active: true };";
$content = str_replace($search1b, $replace1b, $content);

$search1c = "this.form = { id: o.id, name: o.name, type: o.type, discount_type: o.discount_type, value: o.value, min_spend: o.min_spend || '', max_discount: o.max_discount || '', product_ids: o.product_id ? [o.product_id] : [], buy_qty: o.buy_qty || 1, get_qty: o.get_qty || 1, starts_at: o.starts_at ? o.starts_at.substring(0,16) : '', ends_at: o.ends_at ? o.ends_at.substring(0,16) : '', priority: o.priority || 0, is_active: o.is_active };";
$replace1c = "this.form = { id: o.id, name: o.name, type: o.type, discount_type: o.discount_type, value: o.value, min_spend: o.min_spend || '', max_discount: o.max_discount || '', product_ids: (o.type === 'bogo' && o.product_id) ? [o.product_id] : (o.type !== 'bogo' ? (typeof o.applicable_products === 'string' ? JSON.parse(o.applicable_products) : (o.applicable_products || [])) : []), product_id: o.type === 'free_product' ? o.product_id : '', applicable_categories: typeof o.applicable_categories === 'string' ? JSON.parse(o.applicable_categories) : (o.applicable_categories || []), buy_qty: o.buy_qty || 1, get_qty: o.get_qty || 1, starts_at: o.starts_at ? o.starts_at.substring(0,16) : '', ends_at: o.ends_at ? o.ends_at.substring(0,16) : '', priority: o.priority || 0, is_active: o.is_active };";
$content = str_replace($search1c, $replace1c, $content);

// 2. Add options to dropdown
$search2 = '<option value="order_discount">Order Discount</option>
                                                <option value="bogo">Buy X Get Y (BOGO)</option>';
$replace2 = '<option value="order_discount">Order Discount</option>
                                                <option value="bogo">Buy X Get Y (BOGO)</option>
                                                <option value="free_product">Free Product</option>
                                                <option value="category_discount">Category Discount</option>';
$content = str_replace($search2, $replace2, $content);

$search3 = 'Select Order Discount or BOGO.</small><div class="mt-1 p-2 bg-body rounded-2 border" style="font-size: 9px;"><strong class="text-warning">Use Case:</strong> Use "Order Discount" to apply a flat/percentage discount to the entire cart. Use "BOGO" to give away free items when customers buy specific quantities (e.g. Buy 2 Get 1 Free).</div>';
$replace3 = 'Select the promotion mechanic.</small><div class="mt-1 p-2 bg-body rounded-2 border" style="font-size: 9px;"><strong class="text-warning">Use Case:</strong> "Order Discount" for cart subtotal. "BOGO" for same-product deals. "Free Product" for gift items. "Category Discount" for category-wide sales.</div>';
$content = str_replace($search3, $replace3, $content);

$search4 = 'x-show="form.type === \'order_discount\'"';
$replace4 = 'x-show="form.type === \'order_discount\' || form.type === \'category_discount\'"';
$content = str_replace($search4, $replace4, $content);

$search5 = 'x-show="form.type === \'bogo\'"';
$replace5 = 'x-show="form.type === \'bogo\' || form.type === \'free_product\'"';
$content = str_replace($search5, $replace5, $content);

// 3. Add Free Product ID picker and Category picker in Targeting Card
$search6 = '<div class="col-12 position-relative" @click.away="showProductsDropdown = false">';
$replace6 = '<div class="col-12" x-show="form.type === \'category_discount\'">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Applicable Category IDs (Comma Separated)</label>
                                            <input type="text" class="form-control form-control-sm fw-semibold" x-model="form.applicable_categories" placeholder="e.g. 1,2,3">
                                            <small class="text-muted d-block mt-1" style="font-size: 10px;">Enter Category IDs that get the discount.</small>
                                        </div>
                                        <div class="col-12 position-relative" @click.away="showProductsDropdown = false" x-show="form.type !== \'category_discount\'">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Required Products (Trigger)</label>';
$content = str_replace($search6, $replace6, $content);

$search7 = 'Useful for clearing out old inventory or aggressively pushing a new product launch. If left empty, the offer applies to the entire catalog.</div>';
$replace7 = 'If left empty, the offer applies globally or is triggered by Min Spend.</div>
                                            
                                        <div class="col-12 mt-3" x-show="form.type === \'free_product\'">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Gift Product (Free Item) *</label>
                                            <select class="form-select form-select-sm fw-semibold" x-model="form.product_id">
                                                <option value="">Select Free Product...</option>
                                                <template x-for="p in allProducts" :key="p.id">
                                                    <option :value="p.id" x-text="p.name + \' (\' + p.sku + \')\'"></option>
                                                </template>
                                            </select>
                                            <small class="text-muted d-block mt-1" style="font-size: 10px;">The specific product given away for free.</small>
                                        </div>';
$content = str_replace($search7, $replace7, $content);

// In Javascript saveOffer: convert applicable_categories string to array
$search8 = 'async saveOffer() {
            this.saving = true; this.formError = null;
            try {
                const url = this.form.id ? `/api/promotions/offers/${this.form.id}` : \'/api/promotions/offers\';
                const method = this.form.id ? \'PATCH\' : \'POST\';';
$replace8 = 'async saveOffer() {
            this.saving = true; this.formError = null;
            try {
                let payload = JSON.parse(JSON.stringify(this.form));
                if (typeof payload.applicable_categories === "string") {
                    payload.applicable_categories = payload.applicable_categories.split(",").map(i => parseInt(i.trim())).filter(i => !isNaN(i));
                }
                const url = this.form.id ? `/api/promotions/offers/${this.form.id}` : \'/api/promotions/offers\';
                const method = this.form.id ? \'PATCH\' : \'POST\';';
$content = str_replace($search8, $replace8, $content);
$content = str_replace('body: JSON.stringify(this.form)', 'body: JSON.stringify(payload)', $content);

file_put_contents('resources/views/promotions/offers.blade.php', $content);
echo "Offers Modal patched.\n";
