import re

# --- Purchase Orders ---
with open('/home/user/metis/resources/views/procurement/purchase-orders/index.blade.php', 'r') as f:
    po_content = f.read()

old_po_td = '''<p class="mb-0 text-muted small mt-2"><strong>Expected Delivery:</strong> <span x-text="selectedPO.expected_delivery_date ? new Date(selectedPO.expected_delivery_date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }).replace(/ /g, '-') : 'N/A'"></span></p>'''

new_po_td = '''<p class="mb-1 text-muted small mt-2"><strong>Created At:</strong> <span x-text="selectedPO.created_at ? new Date(selectedPO.created_at).toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute:'2-digit', hour12: true }).replace(',', '') : 'N/A'"></span></p>
                                            <p class="mb-0 text-muted small"><strong>Expected Delivery:</strong> <span x-text="selectedPO.expected_delivery_date ? new Date(selectedPO.expected_delivery_date).toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute:'2-digit', hour12: true }).replace(',', '') : 'N/A'"></span></p>'''

po_content = po_content.replace(old_po_td, new_po_td)

with open('/home/user/metis/resources/views/procurement/purchase-orders/index.blade.php', 'w') as f:
    f.write(po_content)


# --- Goods Receipts ---
with open('/home/user/metis/resources/views/procurement/goods-receipts/index.blade.php', 'r') as f:
    grn_content = f.read()

old_grn_td = '''<div class="mb-2"><strong>Date:</strong> <span x-text="selectedGRN?.received_date ? new Date(selectedGRN.received_date).toLocaleDateString('en-GB') : ''"></span></div>'''

new_grn_td = '''<div class="mb-2"><strong>Created At:</strong> <span x-text="selectedGRN?.created_at ? new Date(selectedGRN.created_at).toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute:'2-digit', hour12: true }).replace(',', '') : ''"></span></div>
                                    <div class="mb-2"><strong>Received:</strong> <span x-text="selectedGRN?.received_date ? new Date(selectedGRN.received_date).toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute:'2-digit', hour12: true }).replace(',', '') : ''"></span></div>'''

grn_content = grn_content.replace(old_grn_td, new_grn_td)

with open('/home/user/metis/resources/views/procurement/goods-receipts/index.blade.php', 'w') as f:
    f.write(grn_content)

