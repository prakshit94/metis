import re
from pathlib import Path

file_path = '/home/ubuntu/metis/resources/views/orders/create.blade.php'
content = Path(file_path).read_text(encoding='utf-8')

# 1. Remove checkout-step-2
# The section starts with <div id="checkout-step-2"... and ends before <div @customer-updated.window...
start_marker = '<div id="checkout-step-2"'
end_marker = '<div @customer-updated.window="loadAddresses()"'

start_idx = content.find(start_marker)
end_idx = content.find(end_marker)
if start_idx != -1 and end_idx != -1:
    content = content[:start_idx] + content[end_idx:]

# 2. Update the main layout wrapper
wrapper_old = '<div @customer-updated.window="loadAddresses()" class="row g-4" x-show="!showCheckoutReview" x-cloak>'
wrapper_new = '<div @customer-updated.window="loadAddresses()" class="row g-4">'
content = content.replace(wrapper_old, wrapper_new)

# 3. Update Action Panel Button
btn_old = '@click.prevent="openCheckoutReview()"'
btn_new = '@click.prevent="placeOrder()"'
content = content.replace(btn_old, btn_new)

# 4. Remove JS parts
# Remove showCheckoutReview: !!initialOrder,
content = re.sub(r'\bshowCheckoutReview\s*:\s*!!initialOrder\s*,', '', content)

# Remove step review logic from init
init_remove_pattern = r'''const step = new URLSearchParams\(window\.location\.search\)\.get\('step'\);[\s\S]*?this\.showCheckoutReview = true;[\s\S]*?\}\);[\s\S]*?\}'''
content = re.sub(init_remove_pattern, '', content)

# Remove openCheckoutReview, closeCheckoutReview, confirmCheckout functions
funcs_remove_pattern = r'''openCheckoutReview\(\)\s*\{[\s\S]*?\},[\s\S]*?closeCheckoutReview\(\)\s*\{[\s\S]*?\},[\s\S]*?async confirmCheckout\(\)\s*\{[\s\S]*?\},'''
content = re.sub(funcs_remove_pattern, '', content)

Path(file_path).write_text(content, encoding='utf-8')
print("Successfully processed HTML & JS.")
