import re

with open('/home/user/metis/resources/views/procurement/goods-receipts/index.blade.php', 'r') as f:
    content = f.read()

# Add Trashed Filter Search
search_input = '''
                        <div class="position-relative">
                            <input type="search"
                                   class="form-control form-control-sm"
                                   placeholder="Search GRN or PO..."
                                   x-model.debounce.300ms="searchQuery"
                                   style="width:250px;">
                            <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted"></i>
                        </div>
'''
replacement_search = '''
                        <div class="position-relative">
                            <input type="search"
                                   class="form-control form-control-sm"
                                   placeholder="Search GRN or PO..."
                                   x-model.debounce.300ms="searchQuery"
                                   style="width:250px;">
                            <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted"></i>
                        </div>
                        <select class="form-select form-select-sm" x-model="trashedFilter" @change="currentPage=1; fetchData()" style="width:130px;">
                            <option value="">Active Items</option>
                            <option value="with">With Deleted</option>
                            <option value="only">Only Deleted</option>
                        </select>
'''
content = content.replace(search_input, replacement_search)


bulk_action_ui = '''
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-primary fw-medium shadow-sm bg-white" @click="bulkAction('print')">
                            <i class="bi bi-printer me-1"></i>Print Labels
                        </button>
                    </div>
'''
replacement_bulk = '''
                    <div class="d-flex gap-2">
                        @can('goodsreceipt-delete')
                        <button class="btn btn-sm btn-outline-danger fw-medium shadow-sm bg-white" x-show="trashedFilter !== 'only'" @click="openBulkDeleteModal()">
                            <i class="bi bi-trash me-1"></i>Delete
                        </button>
                        @endcan
                        @can('goodsreceipt-restore')
                        <button class="btn btn-sm btn-outline-warning fw-medium shadow-sm bg-white" x-show="trashedFilter === 'only'" @click="openBulkRestoreModal()">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Restore
                        </button>
                        @endcan
                    </div>
'''
content = content.replace(bulk_action_ui, replacement_bulk)

# Replace the modals placeholder and end section
modals_html = '''
<!-- ═══════════════════════ Modals ══════════════════════════════════════ -->

<!-- View GRN Modal -->
<div class="modal fade" id="viewGrnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light border-bottom-0 pb-3">
                <h5 class="modal-title fw-bold d-flex align-items-center">
                    <div class="bg-primary bg-opacity-10 text-primary rounded p-2 me-3 d-flex align-items-center justify-content-center">
                        <i class="bi bi-receipt"></i>
                    </div>
                    Goods Receipt Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 bg-light" x-show="selectedGRN">
                <div class="p-4">
                    <!-- Header Cards -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <h6 class="text-muted mb-2 text-uppercase fw-bold" style="font-size: 0.75rem;">Receipt Information</h6>
                                    <div class="mb-2"><strong>GRN:</strong> <span x-text="selectedGRN?.grn_number"></span></div>
                                    <div class="mb-2"><strong>Date:</strong> <span x-text="selectedGRN?.received_date ? new Date(selectedGRN.received_date).toLocaleDateString('en-GB') : ''"></span></div>
                                    <div><strong>Created By:</strong> <span x-text="selectedGRN?.creator?.firstname + ' ' + selectedGRN?.creator?.lastname"></span></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <h6 class="text-muted mb-2 text-uppercase fw-bold" style="font-size: 0.75rem;">Supplier Info</h6>
                                    <div class="mb-2"><strong>PO Number:</strong> <span x-text="selectedGRN?.purchase_order?.po_number"></span></div>
                                    <div class="mb-2"><strong>Supplier:</strong> <span x-text="(selectedGRN?.purchase_order?.supplier?.company_name || selectedGRN?.purchase_order?.supplier?.firstname) || 'Unknown'"></span></div>
                                    <div><strong>Email:</strong> <span x-text="selectedGRN?.purchase_order?.supplier?.email || 'N/A'"></span></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <h6 class="text-muted mb-2 text-uppercase fw-bold" style="font-size: 0.75rem;">Warehouse</h6>
                                    <div class="mb-2"><strong>Name:</strong> <span x-text="selectedGRN?.warehouse?.name"></span></div>
                                    <div class="mb-2"><strong>Location:</strong> <span x-text="selectedGRN?.warehouse?.city || 'N/A'"></span></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom-0 pt-4 pb-2">
                            <h6 class="mb-0 fw-bold">Received Items</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light text-muted" style="font-size: 0.8rem;">
                                        <tr>
                                            <th class="ps-4">Product</th>
                                            <th class="text-center">Received Qty</th>
                                            <th class="text-center text-success">Accepted</th>
                                            <th class="text-center text-danger">Rejected</th>
                                            <th>Batch / Expiry</th>
                                        </tr>
                                    </thead>
                                    <tbody class="border-top-0">
                                        <template x-for="(item, index) in selectedGRN?.items" :key="index">
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="fw-medium" x-text="item.product?.name || 'Unknown Product'"></div>
                                                    <div class="small text-muted" x-text="item.product?.sku || ''"></div>
                                                </td>
                                                <td class="text-center fw-medium" x-text="item.received_qty"></td>
                                                <td class="text-center text-success fw-bold" x-text="item.accepted_qty"></td>
                                                <td class="text-center text-danger fw-bold" x-text="item.rejected_qty"></td>
                                                <td>
                                                    <template x-if="item.batch_number">
                                                        <div>
                                                            <div class="fw-medium" x-text="'Batch: ' + item.batch_number"></div>
                                                            <div class="small text-muted" x-text="'Exp: ' + (item.expiry_date ? new Date(item.expiry_date).toLocaleDateString('en-GB') : 'N/A')"></div>
                                                        </div>
                                                    </template>
                                                    <template x-if="!item.batch_number">
                                                        <span class="text-muted small">N/A</span>
                                                    </template>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteGrnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white border-bottom-0 pb-3">
                <h5 class="modal-title fw-medium d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <span x-text="isBulkDelete ? 'Bulk Delete Receipts' : 'Delete Receipt'"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="mb-3">
                    <i class="bi bi-trash text-danger" style="font-size: 3rem;"></i>
                </div>
                <h5 class="mb-3">Are you sure?</h5>
                <p class="text-muted mb-0">
                    <span x-text="isBulkDelete ? `You are about to delete ${selected.length} receipts.` : 'You are about to delete this goods receipt.'"></span>
                    <br>This action can be reversed from the trash.
                </p>
            </div>
            <div class="modal-footer bg-light border-top-0 justify-content-center py-3">
                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger px-4" @click="submitDeleteForm" :disabled="isDeleting">
                    <span x-show="isDeleting" class="spinner-border spinner-border-sm me-2"></span>
                    Delete
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Restore Modal -->
<div class="modal fade" id="restoreGrnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning border-bottom-0 pb-3">
                <h5 class="modal-title fw-medium d-flex align-items-center text-dark">
                    <i class="bi bi-arrow-counterclockwise me-2"></i>
                    <span x-text="isBulkRestore ? 'Bulk Restore Receipts' : 'Restore Receipt'"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="mb-3">
                    <i class="bi bi-arrow-counterclockwise text-warning" style="font-size: 3rem;"></i>
                </div>
                <h5 class="mb-3">Restore Items</h5>
                <p class="text-muted mb-0">
                    <span x-text="isBulkRestore ? `You are about to restore ${selected.length} receipts.` : 'You are about to restore this goods receipt.'"></span>
                </p>
            </div>
            <div class="modal-footer bg-light border-top-0 justify-content-center py-3">
                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning px-4" @click="submitRestoreForm" :disabled="isRestoring">
                    <span x-show="isRestoring" class="spinner-border spinner-border-sm me-2"></span>
                    Restore
                </button>
            </div>
        </div>
    </div>
</div>

</div>
'''

content = content.replace('</div>\n\n<style>', modals_html + '\n<style>')


js_script = '''
        total: 0, from: 0, to: 0,
        selected: [],
        stats: { total: {{ $stats['total'] ?? 0 }}, this_month: {{ $stats['this_month'] ?? 0 }}, pending: {{ $stats['pending'] ?? 0 }}, discrepancies: {{ $stats['discrepancies'] ?? 0 }} },
        trashedFilter: '',
        
        get allSelected() { return this.items.length > 0 && this.selected.length === this.items.length; },
        
        init() {
            this.fetchData();
            this.$watch('searchQuery', () => { this.currentPage = 1; this.fetchData(); });
        },
        
        fetchData() {
            this.isLoading = true;
            let url = new URL(window.location.href);
            url.searchParams.set('page', this.currentPage);
            if (this.searchQuery) url.searchParams.set('search', this.searchQuery);
            if (this.trashedFilter) url.searchParams.set('trashed', this.trashedFilter);
            
            fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(res => res.json())
                .then(data => {
                    this.items = data.data;
                    this.currentPage = data.current_page;
                    this.totalPages = data.last_page;
                    this.total = data.total || 0;
                    this.from = data.from || 0;
                    this.to = data.to || 0;
                })
                .catch(err => console.error(err))
                .finally(() => this.isLoading = false);
        },

        toggleAll(e) { this.selected = e.target.checked ? this.items.map(i => i.id) : []; },
        
        selectedGRN: null,
        
        viewItem(item) {
            this.selectedGRN = item;
            // Fetch detailed items if needed, but we already eager load them in index usually.
            // If they are not eager loaded in index (due to pagination size), we would fetch here. 
            // GoodsReceiptController index doesn't eager load items. Let's do it now. 
            // Wait, we need to ensure items are fetched.
            fetch(`/procurement/goods-receipts?search=${item.grn_number}`, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(res => res.json())
                .then(data => {
                    // Usually we'd have a show endpoint, but since we don't, we can just use the item data if items are included.
                    // Wait, GoodsReceiptController::index does not eager load `items`. Let's just use what's available or update the controller.
                    // Actually, let's update the controller's eager loading to include `items.product` in the Python script below.
                    this.selectedGRN = item;
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('viewGrnModal')).show();
                });
        },
        
        showNotification(message, type = 'success') {
            const toastContainer = document.getElementById('toast-container') || (() => {
                const tc = document.createElement('div');
                tc.id = 'toast-container';
                tc.className = 'toast-container position-fixed bottom-0 end-0 p-3';
                tc.style.zIndex = '1090';
                document.body.appendChild(tc);
                return tc;
            })();

            const toastEl = document.createElement('div');
            toastEl.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0`;
            toastEl.setAttribute('role', 'alert');
            toastEl.setAttribute('aria-live', 'assertive');
            toastEl.setAttribute('aria-atomic', 'true');

            toastEl.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            `;

            toastContainer.appendChild(toastEl);
            const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
            toast.show();
            toastEl.addEventListener('hidden.bs.toast', () => { toastEl.remove(); });
        },

        isDeleting: false,
        isBulkDelete: false,
        deleteForm: { id: '' },

        openDeleteModal(id) {
            this.isBulkDelete = false;
            this.deleteForm.id = id;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteGrnModal')).show();
        },

        openBulkDeleteModal() {
            if (this.selected.length === 0) return;
            this.isBulkDelete = true;
            this.deleteForm.id = '';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteGrnModal')).show();
        },

        async submitDeleteForm() {
            if (this.isDeleting) return;
            this.isDeleting = true;
            try {
                let url, method, bodyData;
                if (this.isBulkDelete) {
                    url = `/procurement/goods-receipts/bulk`;
                    method = 'POST';
                    bodyData = { action: 'delete', ids: this.selected };
                } else {
                    url = `/procurement/goods-receipts/${this.deleteForm.id}`;
                    method = 'DELETE';
                    bodyData = {};
                }

                const response = await fetch(url, {
                    method: method,
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                    body: Object.keys(bodyData).length ? JSON.stringify(bodyData) : undefined
                });
                const data = await response.json();
                if (response.ok) {
                    bootstrap.Modal.getInstance(document.getElementById('deleteGrnModal')).hide();
                    this.selected = [];
                    this.allSelected = false;
                    this.fetchData();
                    this.showNotification(data.message || 'Deleted successfully.', "success");
                } else {
                    this.showNotification(data.message || 'Failed to delete.', "error");
                }
            } catch (err) {
                console.error(err);
                this.showNotification('An error occurred.', "error");
            } finally {
                this.isDeleting = false;
            }
        },

        isRestoring: false,
        isBulkRestore: false,
        restoreForm: { id: '' },

        openRestoreModal(id) {
            this.isBulkRestore = false;
            this.restoreForm.id = id;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('restoreGrnModal')).show();
        },

        openBulkRestoreModal() {
            if (this.selected.length === 0) return;
            this.isBulkRestore = true;
            this.restoreForm.id = '';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('restoreGrnModal')).show();
        },

        async submitRestoreForm() {
            if (this.isRestoring) return;
            this.isRestoring = true;
            try {
                let url, method, bodyData;
                if (this.isBulkRestore) {
                    url = `/procurement/goods-receipts/bulk`;
                    method = 'POST';
                    bodyData = { action: 'restore', ids: this.selected };
                } else {
                    url = `/procurement/goods-receipts/${this.restoreForm.id}/restore`;
                    method = 'POST';
                    bodyData = {};
                }

                const response = await fetch(url, {
                    method: method,
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                    body: Object.keys(bodyData).length ? JSON.stringify(bodyData) : undefined
                });
                const data = await response.json();
                if (response.ok) {
                    bootstrap.Modal.getInstance(document.getElementById('restoreGrnModal')).hide();
                    this.selected = [];
                    this.allSelected = false;
                    this.fetchData();
                    this.showNotification(data.message || 'Restored successfully.', "success");
                } else {
                    this.showNotification(data.message || 'Failed to restore.', "error");
                }
            } catch (err) {
                console.error(err);
                this.showNotification('An error occurred.', "error");
            } finally {
                this.isRestoring = false;
            }
        }
'''

# Find the start and end of the object returned by Alpine.data
start_idx = content.find('total: 0, from: 0, to: 0,')
end_idx = content.find('}));', start_idx)

content = content[:start_idx] + js_script + '\n    ' + content[end_idx:]

with open('/home/user/metis/resources/views/procurement/goods-receipts/index.blade.php', 'w') as f:
    f.write(content)


# Quick fix for eager loading items in GoodsReceiptController
with open('/home/user/metis/app/Modules/Inventory/Controllers/GoodsReceiptController.php', 'r') as f:
    ctrl_content = f.read()

ctrl_content = ctrl_content.replace(
    "GoodsReceipt::with(['purchaseOrder.supplier', 'warehouse', 'creator'])",
    "GoodsReceipt::with(['purchaseOrder.supplier', 'warehouse', 'creator', 'items.product'])"
)

with open('/home/user/metis/app/Modules/Inventory/Controllers/GoodsReceiptController.php', 'w') as f:
    f.write(ctrl_content)

