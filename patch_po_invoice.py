import re

with open('/home/user/metis/resources/views/procurement/purchase-orders/index.blade.php', 'r') as f:
    content = f.read()

# 1. Add "Upload Invoice" action menu item
action_menu = '''                                                    @can('goodsreceipt-create')
                                                    <template x-if="item.status === 'approved' || item.status === 'partially_received'">
                                                        <li><hr class="dropdown-divider"></li>
                                                    </template>'''
new_action_menu = '''                                                    @can('purchaseorder-create')
                                                    <li><a class="dropdown-item text-secondary fw-medium" href="#" @click.prevent="openInvoiceModal(item)"><i class="bi bi-file-earmark-arrow-up me-2"></i> Upload Invoice</a></li>
                                                    @endcan
                                                    @can('goodsreceipt-create')
                                                    <template x-if="item.status === 'approved' || item.status === 'partially_received'">
                                                        <li><hr class="dropdown-divider"></li>
                                                    </template>'''
content = content.replace(action_menu, new_action_menu)


# 2. Add #uploadInvoiceModal HTML
modal_html = '''
    <!-- Upload Invoice Modal -->
    <div class="modal fade" id="uploadInvoiceModal" aria-labelledby="uploadInvoiceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-bottom-0 pb-0 bg-body-tertiary">
                    <h5 class="modal-title fw-bold" id="uploadInvoiceModalLabel">Upload Invoice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3 bg-body-tertiary">
                    <form @submit.prevent="submitInvoiceForm">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-muted small text-uppercase">Select File (PDF, PNG, JPG)</label>
                            <input type="file" class="form-control" x-ref="invoiceFile" accept=".pdf,.png,.jpg,.jpeg" required>
                        </div>
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="isUploading">
                                <span x-show="isUploading" class="spinner-border spinner-border-sm me-2"></span>
                                Upload
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
'''
content = content.replace('    <!-- Delete PO Modal -->', modal_html + '\n    <!-- Delete PO Modal -->')


# 3. Add Attached Invoice details in View Modal
view_modal_html = '''                                            <p class="mb-0 text-muted small"><strong>Expected Delivery:</strong> <span x-text="selectedPO.expected_delivery_date ? new Date(selectedPO.expected_delivery_date).toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute:'2-digit', hour12: true }).replace(',', '') : 'N/A'"></span></p>
                                        </div>'''

new_view_modal_html = '''                                            <p class="mb-0 text-muted small"><strong>Expected Delivery:</strong> <span x-text="selectedPO.expected_delivery_date ? new Date(selectedPO.expected_delivery_date).toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute:'2-digit', hour12: true }).replace(',', '') : 'N/A'"></span></p>
                                            
                                            <template x-if="selectedPO.invoice_path">
                                                <div class="mt-3">
                                                    <a :href="selectedPO.invoice_url" target="_blank" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center">
                                                        <i class="bi bi-file-earmark-pdf me-2"></i> View Attached Invoice
                                                    </a>
                                                </div>
                                            </template>
                                        </div>'''
content = content.replace(view_modal_html, new_view_modal_html)


# 4. Add JS to Alpine Component
js_code = '''
        invoiceForm: { po_id: null },
        isUploading: false,
        openInvoiceModal(po) {
            this.invoiceForm.po_id = po.id;
            if(this.$refs.invoiceFile) this.$refs.invoiceFile.value = null;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('uploadInvoiceModal')).show();
        },
        async submitInvoiceForm() {
            const file = this.$refs.invoiceFile.files[0];
            if (!file) return;
            this.isUploading = true;
            try {
                const formData = new FormData();
                formData.append('invoice', file);
                
                const response = await fetch(`/procurement/purchase-orders/${this.invoiceForm.po_id}/invoice`, {
                    method: 'POST',
                    headers: { 
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') 
                    },
                    body: formData
                });
                
                const data = await response.json();
                if (response.ok) {
                    bootstrap.Modal.getInstance(document.getElementById('uploadInvoiceModal')).hide();
                    this.fetchData();
                    this.showNotification(data.message || 'Invoice uploaded successfully.', "success");
                } else {
                    this.showNotification(data.message || 'Failed to upload invoice.', "error");
                }
            } catch (err) {
                console.error(err);
                this.showNotification('An error occurred.', "error");
            } finally {
                this.isUploading = false;
            }
        },
'''

# Find the end of openDeleteModal and inject
end_idx = content.find('        openDeleteModal(poId) {')
if end_idx != -1:
    content = content[:end_idx] + js_code + '\n' + content[end_idx:]

with open('/home/user/metis/resources/views/procurement/purchase-orders/index.blade.php', 'w') as f:
    f.write(content)

