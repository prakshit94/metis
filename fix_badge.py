with open('resources/views/orders/create.blade.php', 'r') as f:
    content = f.read()

old_btn = """<button type="button" @click="$dispatch('open-complaint-modal', { order_no: order.order_no || order.order_number || '', customer_id: order.party_id || '' })" class="btn btn-sm btn-outline-warning rounded-pill px-4 fw-bold">
    <i class="bi bi-headset me-1"></i> Raise Complaint
</button>"""

new_btn = """<button type="button" @click="$dispatch('open-complaint-modal', { order_no: order.order_no || order.order_number || '', customer_id: order.party_id || '' })" class="btn btn-sm btn-outline-warning rounded-pill px-4 fw-bold">
    <i class="bi bi-headset me-1"></i> Raise Complaint
    <span x-show="order.complaints_count > 0" x-cloak class="badge bg-warning text-dark border border-warning border-opacity-75 ms-1" x-text="order.complaints_count"></span>
</button>"""

content = content.replace(old_btn, new_btn)

with open('resources/views/orders/create.blade.php', 'w') as f:
    f.write(content)
