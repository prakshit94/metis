import re

with open('resources/views/orders/create.blade.php', 'r') as f:
    content = f.read()

# Replace the anchor tag with a button
pattern = r"""<a :href="`\{\{ route\('complaints.index'\) \}\}\?order_no=\$\{encodeURIComponent\(order\.order_no \|\| order\.order_number \|\| ''\)\}&customer_id=\$\{order\.party_id \|\| ''\}`"[\s\S]*?class="([^"]+)">[\s\S]*?<i class="bi bi-headset me-1"></i> Raise Complaint[\s\S]*?</a>"""

replacement = """<button type="button" @click="$dispatch('open-complaint-modal', { order_no: order.order_no || order.order_number || '', customer_id: order.party_id || '' })" class="\\1">
    <i class="bi bi-headset me-1"></i> Raise Complaint
</button>"""

content, count = re.subn(pattern, replacement, content)
print(f"Replaced {count} occurrences of the button.")

# Add <x-complaint-modal /> right before <!-- Action Blocked Modal -->
if '<x-complaint-modal />' not in content:
    content = content.replace('<!-- Action Blocked Modal -->', '<x-complaint-modal />\n\n<!-- Action Blocked Modal -->')

with open('resources/views/orders/create.blade.php', 'w') as f:
    f.write(content)
