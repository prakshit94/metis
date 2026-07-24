<?php
$content = file_get_contents('resources/views/promotions/coupons.blade.php');

$search1 = "form: { id: null, code: '', type: 'percentage', value: '', min_spend: '', max_discount: '', expiry_date: '', usage_limit: '', is_active: true },";
$replace1 = "form: { id: null, code: '', type: 'percentage', value: '', min_spend: '', max_discount: '', free_product_id: '', free_qty: 1, expiry_date: '', usage_limit: '', is_active: true },";
$content = str_replace($search1, $replace1, $content);

$search1b = "this.form = { id: null, code: '', type: 'percentage', value: '', min_spend: '', max_discount: '', expiry_date: '', usage_limit: '', is_active: true };";
$replace1b = "this.form = { id: null, code: '', type: 'percentage', value: '', min_spend: '', max_discount: '', free_product_id: '', free_qty: 1, expiry_date: '', usage_limit: '', is_active: true };";
$content = str_replace($search1b, $replace1b, $content);

$search1c = "this.form = { id: c.id, code: c.code, type: c.type, value: c.value, min_spend: c.min_spend || '', max_discount: c.max_discount || '', expiry_date: c.expiry_date || '', usage_limit: c.usage_limit || '', is_active: c.is_active };";
$replace1c = "this.form = { id: c.id, code: c.code, type: c.type, value: c.value, min_spend: c.min_spend || '', max_discount: c.max_discount || '', free_product_id: c.free_product_id || '', free_qty: c.free_qty || 1, expiry_date: c.expiry_date || '', usage_limit: c.usage_limit || '', is_active: c.is_active };";
$content = str_replace($search1c, $replace1c, $content);

$search2 = '<option value="percentage">Percentage (%)</option>
                                                <option value="flat">Flat Amount (Rs )</option>';
$replace2 = '<option value="percentage">Percentage (%)</option>
                                                <option value="flat">Flat Amount (Rs )</option>
                                                <option value="free_shipping">Free Shipping</option>
                                                <option value="free_product">Free Product</option>';
$content = str_replace($search2, $replace2, $content);

$search3 = 'x-show="form.type === \'percentage\' || form.type === \'flat\'"';
$replace3 = 'x-show="form.type === \'percentage\' || form.type === \'flat\'"';
// Actually, it doesn't have an x-show for value currently, let's wrap it or just leave it.
// The existing value input is:
$search4 = '<div class="col-md-6">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Discount Value *</label>';
$replace4 = '<div class="col-md-6" x-show="form.type === \'percentage\' || form.type === \'flat\'">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Discount Value *</label>';
$content = str_replace($search4, $replace4, $content);

$search5 = 'margin on bulk orders (e.g., 50% off up to max of Rs 1000).</div>
                                        </div>
                                    </div>
                                </div>';
$replace5 = 'margin on bulk orders (e.g., 50% off up to max of Rs 1000).</div>
                                        </div>
                                        
                                        <div class="col-md-6" x-show="form.type === \'free_product\'">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Gift Product ID *</label>
                                            <input type="number" class="form-control form-control-sm fw-semibold" x-model="form.free_product_id">
                                            <small class="text-muted d-block mt-1" style="font-size: 10px;">ID of the free product.</small>
                                        </div>
                                        <div class="col-md-6" x-show="form.type === \'free_product\'">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Free Qty *</label>
                                            <input type="number" class="form-control form-control-sm fw-semibold" x-model="form.free_qty" min="1">
                                            <small class="text-muted d-block mt-1" style="font-size: 10px;">How many free units to give.</small>
                                        </div>
                                    </div>
                                </div>';
$content = str_replace($search5, $replace5, $content);

file_put_contents('resources/views/promotions/coupons.blade.php', $content);
echo "Coupons Modal patched.\n";
