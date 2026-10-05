import re

with open("resources/views/catalog/products/index.blade.php", "r") as f:
    content = f.read()

# 1. Re-apply step="0.01"
content = content.replace('x-model="form.default_discount" step="1"', 'x-model="form.default_discount" step="0.01"')

# 2. Add <div class="invalid-feedback">This field is required.</div>
# form.name
content = content.replace('x-model="form.name" required placeholder="e.g. Wireless Noise Cancelling Headphones">\n', 'x-model="form.name" required placeholder="e.g. Wireless Noise Cancelling Headphones">\n<div class="invalid-feedback">This field is required.</div>\n')

# category_id
content = re.sub(r'(x-model="form.category_id" required>[\s\S]*?</select>)', r'\1\n<div class="invalid-feedback">This field is required.</div>', content)

# form.sku
content = re.sub(r'(id="skuToggle">\n\s*</div>\n\s*</div>)', r'\1\n<div class="invalid-feedback">This field is required.</div>', content)

# purchase_price (label needs *)
content = content.replace('<label class="form-label fw-medium text-muted small">Purchase Price</label>', '<label class="form-label fw-medium text-muted small">Purchase Price <span class="text-danger">*</span></label>')
content = re.sub(r'(x-model="form.purchase_price".*?>)', r'\1\n<div class="invalid-feedback">This field is required.</div>', content)

# selling_price_inc_gst
content = re.sub(r'(x-model="form.selling_price_inc_gst".*?>)', r'\1\n<div class="invalid-feedback">This field is required.</div>', content)

# tax_rate_id
content = re.sub(r'(x-model="form.tax_rate_id" required>[\s\S]*?</select>)', r'\1\n<div class="invalid-feedback">This field is required.</div>', content)

# hsn_code_id
content = re.sub(r'(x-model="form.hsn_code_id" required>[\s\S]*?</select>)', r'\1\n<div class="invalid-feedback">This field is required.</div>', content)

# uom_id
content = re.sub(r'(x-model="form.uom_id" required>[\s\S]*?</select>)', r'\1\n<div class="invalid-feedback">This field is required.</div>', content)

# min_stock_level
content = re.sub(r'(x-model="form.min_stock_level".*?>)', r'\1\n<div class="invalid-feedback">This field is required.</div>', content)

# default_warehouse_id
content = re.sub(r'(x-model="form.default_warehouse_id" required>[\s\S]*?</select>)', r'\1\n<div class="invalid-feedback">This field is required.</div>', content)

# status
content = re.sub(r'(x-model="form.status" required>[\s\S]*?</select>)', r'\1\n<div class="invalid-feedback">This field is required.</div>', content)


with open("resources/views/catalog/products/index.blade.php", "w") as f:
    f.write(content)
