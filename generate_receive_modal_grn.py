import re

with open('/home/user/metis/resources/views/procurement/purchase-orders/index.blade.php', 'r') as f:
    po_content = f.read()

# Extract receiveGoodsModal HTML
modal_start = po_content.find('<!-- GRN Receive Goods Modal -->')
modal_end = po_content.find('<!-- Reject PO Modal -->')
receive_modal_html = po_content[modal_start:modal_end]

# Extract receiveForm js methods
js_start = po_content.find('receiveForm: {')
js_end = po_content.find('openDeleteModal(poId) {')
receive_js = po_content[js_start:js_end]


# Modify the HTML to include a <select> for PO
select_html = '''                                            <div class="col-md-3">
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Select PO Number *</label>
                                                <select class="form-select form-select-sm fw-semibold" x-model="receiveForm.po_id" @change="selectPO($event.target.value)" style="font-size: 12px;" required>
                                                    <option value="">-- Select Approved PO --</option>
                                                    <template x-for="po in pendingPOs" :key="po.id">
                                                        <option :value="po.id" x-text="po.po_number"></option>
                                                    </template>
                                                </select>
                                            </div>'''

receive_modal_html = re.sub(
    r'<div class="col-md-3">\s*<label[^>]*>PO Number</label>\s*<div[^>]*></div>\s*</div>', 
    select_html, 
    receive_modal_html
)

# Modify the JS
# We need to add selectPO(id) method
new_js = '''pendingPOs: @json($pendingPOs),
        selectPO(id) {
            const po = this.pendingPOs.find(p => p.id == id);
            if (!po) return;
            this.receiveForm.po_id = po.id;
            this.receiveForm.po_number = po.po_number;
            this.receiveForm.supplier_name = po.supplier ? (po.supplier.company_name || po.supplier.firstname) : 'Unknown';
            this.receiveForm.warehouse_name = po.warehouse ? po.warehouse.name : 'Unknown';
            this.receiveForm.items = po.items.map(item => ({
                purchase_order_item_id: item.id,
                product_name: item.product ? item.product.name : 'Unknown Product',
                sku: item.product ? item.product.sku : '',
                batch_tracking: item.product ? item.product.batch_tracking : false,
                ordered: parseFloat(item.quantity),
                previously_received: parseFloat(item.received_qty) || 0,
                pending: parseFloat(item.quantity) - (parseFloat(item.received_qty) || 0),
                accepted_qty: 0,
                rejected_qty: 0,
                notes: '',
                batch_number: '',
                manufacturing_date: '',
                expiry_date: '',
            }));
        },
        openReceiveModal() {
            this.receiveForm.po_id = null;
            this.receiveForm.po_number = '';
            this.receiveForm.received_date = new Date().toISOString().split('T')[0];
            this.receiveForm.notes = '';
            this.receiveForm.items = [];
            this.receiveForm.supplier_name = '';
            this.receiveForm.warehouse_name = '';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('receiveGoodsModal')).show();
        },
        '''

# Replace openReceiveModal in the extracted JS
receive_js = re.sub(r'openReceiveModal\(po\) \{.*?(?=async submitReceiveForm)', new_js, receive_js, flags=re.DOTALL)
# Make sure we don't accidentally leave out receiveForm definition.
# Wait, receive_js already has receiveForm: { ... }.
# Let's write the new JS replacing everything perfectly.

custom_receive_js = '''
        pendingPOs: @json($pendingPOs),
        receiveForm: {
            po_id: null,
            po_number: '',
            received_date: '',
            notes: '',
            supplier_name: '',
            warehouse_name: '',
            items: []
        },
        selectPO(id) {
            const po = this.pendingPOs.find(p => p.id == id);
            if (!po) return;
            this.receiveForm.po_id = po.id;
            this.receiveForm.po_number = po.po_number;
            this.receiveForm.supplier_name = po.supplier ? (po.supplier.company_name || po.supplier.firstname) : 'Unknown';
            this.receiveForm.warehouse_name = po.warehouse ? po.warehouse.name : 'Unknown';
            this.receiveForm.items = po.items.filter(i => (parseFloat(i.quantity) - (parseFloat(i.received_qty) || 0)) > 0).map(item => ({
                purchase_order_item_id: item.id,
                product_name: item.product ? item.product.name : 'Unknown Product',
                sku: item.product ? item.product.sku : '',
                batch_tracking: item.product ? item.product.batch_tracking : false,
                ordered: parseFloat(item.quantity),
                previously_received: parseFloat(item.received_qty) || 0,
                pending: parseFloat(item.quantity) - (parseFloat(item.received_qty) || 0),
                accepted_qty: 0,
                rejected_qty: 0,
                notes: '',
                batch_number: '',
                manufacturing_date: '',
                expiry_date: '',
            }));
        },
        openReceiveModal() {
            this.receiveForm.po_id = '';
            this.receiveForm.po_number = '';
            this.receiveForm.received_date = new Date().toISOString().split('T')[0];
            this.receiveForm.notes = '';
            this.receiveForm.items = [];
            this.receiveForm.supplier_name = '';
            this.receiveForm.warehouse_name = '';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('receiveGoodsModal')).show();
        },
        async submitReceiveForm() {
            if(!this.receiveForm.po_id) {
                this.showNotification('Please select a Purchase Order.', 'error');
                return;
            }
            let isValid = true;
            for(let i=0; i<this.receiveForm.items.length; i++) {
                const item = this.receiveForm.items[i];
                if ((item.accepted_qty + item.rejected_qty) === 0) continue;
                if(item.batch_tracking) {
                    if(!item.batch_number || !item.manufacturing_date || !item.expiry_date) {
                        isValid = false;
                    }
                }
            }
            if(!isValid) {
                this.showNotification('Please fill in all batch tracking details for received items.', 'error');
                return;
            }

            try {
                this.isLoading = true;
                const response = await fetch(`/procurement/purchase-orders/${this.receiveForm.po_id}/receive`, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json', 
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(this.receiveForm)
                });
                const data = await response.json();
                if(response.ok) {
                    bootstrap.Modal.getInstance(document.getElementById('receiveGoodsModal')).hide();
                    this.fetchData();
                    this.showNotification('Goods received successfully!', "success");
                    // Optionally remove the PO from pendingPOs to prevent duplicate receives
                    this.pendingPOs = this.pendingPOs.filter(p => p.id != this.receiveForm.po_id);
                } else {
                    this.showNotification(data.message || 'Failed to receive goods.', "error");
                }
            } catch (err) {
                console.error(err);
                this.showNotification('An error occurred.', "error");
            } finally {
                this.isLoading = false;
            }
        },
'''

# Now patch goods-receipts/index.blade.php
with open('/home/user/metis/resources/views/procurement/goods-receipts/index.blade.php', 'r') as f:
    grn_content = f.read()

# 1. Header Button
grn_content = grn_content.replace(
    '<a href="{{ route(\'procurement.purchase-orders.index\') }}" class="btn btn-primary">',
    '<button type="button" class="btn btn-primary" @click.prevent="openReceiveModal()">'
).replace('</a>\n        </div>', '</button>\n        </div>')

# 2. Inject receiveGoodsModal before View GRN Modal
grn_content = grn_content.replace('<!-- View GRN Modal -->', receive_modal_html + '\n<!-- View GRN Modal -->')

# 3. Inject JS methods
js_inject_pos = grn_content.find('        fetchData() {')
grn_content = grn_content[:js_inject_pos] + custom_receive_js + '\n' + grn_content[js_inject_pos:]

with open('/home/user/metis/resources/views/procurement/goods-receipts/index.blade.php', 'w') as f:
    f.write(grn_content)

