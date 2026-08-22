import re

# --- Purchase Orders ---
with open('/home/user/metis/resources/views/procurement/purchase-orders/index.blade.php', 'r') as f:
    po_content = f.read()

# Add th
po_content = po_content.replace('<th>Expected Date</th>', '<th style="width:150px;">Created At</th>\n                            <th>Expected Date</th>')

# Add td
old_expected_td = '''                                <td>
                                    <span class="small text-muted" x-text="item.expected_delivery_date ? new Date(item.expected_delivery_date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }).replace(/ /g, '-') : '—'"></span>
                                </td>'''

new_created_td = '''                                <td>
                                    <span class="small text-muted" x-text="item.created_at ? new Date(item.created_at).toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute:'2-digit', hour12: true }).replace(',', '') : '—'"></span>
                                </td>
                                <td>
                                    <span class="small text-muted" x-text="item.expected_delivery_date ? new Date(item.expected_delivery_date).toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute:'2-digit', hour12: true }).replace(',', '') : '—'"></span>
                                </td>'''
po_content = po_content.replace(old_expected_td, new_created_td)

with open('/home/user/metis/resources/views/procurement/purchase-orders/index.blade.php', 'w') as f:
    f.write(po_content)


# --- Goods Receipts ---
with open('/home/user/metis/resources/views/procurement/goods-receipts/index.blade.php', 'r') as f:
    grn_content = f.read()

# Add th
grn_content = grn_content.replace('<th>Date Received</th>', '<th style="width:150px;">Created At</th>\n                            <th>Date Received</th>')

# Add td
old_received_td = '''                                <td>
                                    <span class="small text-muted" x-text="item.received_date ? new Date(item.received_date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }).replace(/ /g, '-') : '—'"></span>
                                </td>'''

new_grn_created_td = '''                                <td>
                                    <span class="small text-muted" x-text="item.created_at ? new Date(item.created_at).toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute:'2-digit', hour12: true }).replace(',', '') : '—'"></span>
                                </td>
                                <td>
                                    <span class="small text-muted" x-text="item.received_date ? new Date(item.received_date).toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute:'2-digit', hour12: true }).replace(',', '') : '—'"></span>
                                </td>'''
grn_content = grn_content.replace(old_received_td, new_grn_created_td)

with open('/home/user/metis/resources/views/procurement/goods-receipts/index.blade.php', 'w') as f:
    f.write(grn_content)

