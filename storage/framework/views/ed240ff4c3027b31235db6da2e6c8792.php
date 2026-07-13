<?php $__env->startSection('title', 'Offers & Deals'); ?>
<?php $__env->startSection('page', 'promotions.offers'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid p-4" x-data="offersModule()" x-init="init()">

    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><i class="bi bi-star-fill me-2 text-warning"></i>Offers &amp; Deals</h1>
            <p class="text-muted mb-0 small">Create BOGO deals and order-level discount offers</p>
        </div>
        <button class="btn btn-primary" @click="openModal()">
            <i class="bi bi-plus-lg me-1"></i> New Offer
        </button>
    </div>

    
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-primary bg-opacity-10 p-3"><i class="bi bi-star-fill text-primary fs-5"></i></div>
                    <div><div class="fw-bold fs-4" x-text="stats.total">0</div><small class="text-muted">Total Offers</small></div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-success bg-opacity-10 p-3"><i class="bi bi-check-circle-fill text-success fs-5"></i></div>
                    <div><div class="fw-bold fs-4" x-text="stats.active">0</div><small class="text-muted">Active</small></div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-info bg-opacity-10 p-3"><i class="bi bi-arrow-repeat text-info fs-5"></i></div>
                    <div><div class="fw-bold fs-4" x-text="stats.bogo">0</div><small class="text-muted">BOGO Offers</small></div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-warning bg-opacity-10 p-3"><i class="bi bi-tag-fill text-warning fs-5"></i></div>
                    <div><div class="fw-bold fs-4" x-text="stats.order_discount">0</div><small class="text-muted">Order Discounts</small></div>
                </div>
            </div>
        </div>
    </div>

    
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-bottom d-flex flex-wrap gap-2 align-items-center py-3">
            <div class="input-group" style="max-width:260px">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control" placeholder="Search offers…" x-model="search" @input.debounce.400ms="fetchOffers()" />
            </div>
            <select class="form-select" style="max-width:180px" x-model="filterType" @change="fetchOffers()">
                <option value="">All Types</option>
                <option value="order_discount">Order Discount</option>
                <option value="bogo">BOGO</option>
            </select>
            <div class="ms-auto d-flex gap-2">
                <template x-if="selected.length">
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-success" @click="bulkAction('activate')"><i class="bi bi-check-circle me-1"></i>Activate</button>
                        <button class="btn btn-sm btn-outline-secondary" @click="bulkAction('deactivate')"><i class="bi bi-pause-circle me-1"></i>Pause</button>
                        <button class="btn btn-sm btn-outline-danger" @click="bulkAction('delete')"><i class="bi bi-trash me-1"></i>Delete</button>
                    </div>
                </template>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase small">
                    <tr>
                        <th style="width:40px"><input type="checkbox" class="form-check-input" @change="toggleAll($event)" :checked="allSelected"></th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Discount</th>
                        <th>Min Spend</th>
                        <th>Product</th>
                        <th>Valid</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="loading">
                        <tr><td colspan="10" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>
                    </template>
                    <template x-if="!loading && offers.length === 0">
                        <tr><td colspan="10" class="text-center py-5 text-muted"><i class="bi bi-star fs-1 d-block mb-2"></i>No offers found</td></tr>
                    </template>
                    <template x-for="o in offers" :key="o.id">
                        <tr>
                            <td><input type="checkbox" class="form-check-input" :value="o.id" x-model="selected"></td>
                            <td class="fw-semibold" x-text="o.name"></td>
                            <td>
                                <span class="badge" :class="o.type === 'bogo' ? 'bg-info text-dark' : 'bg-primary'" x-text="o.type === 'bogo' ? 'BOGO' : 'Order Discount'"></span>
                            </td>
                            <td x-text="o.discount_type === 'percentage' ? o.value + '%' : '₹' + parseFloat(o.value).toFixed(2)"></td>
                            <td x-text="o.min_spend > 0 ? '₹' + parseFloat(o.min_spend).toFixed(2) : '—'"></td>
                            <td>
                                <span x-show="o.product" class="badge bg-light text-dark border" x-text="o.product?.name || '—'"></span>
                                <span x-show="!o.product" class="text-muted">Any</span>
                            </td>
                            <td class="small text-muted">
                                <span x-show="!o.starts_at && !o.ends_at">Always</span>
                                <span x-show="o.starts_at || o.ends_at">
                                    <span x-text="o.starts_at ? o.starts_at.substring(0,10) : '∞'"></span>
                                    → <span x-text="o.ends_at ? o.ends_at.substring(0,10) : '∞'"></span>
                                </span>
                            </td>
                            <td><span class="badge bg-secondary" x-text="o.priority || 0"></span></td>
                            <td>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" :checked="o.is_active" @change="toggleStatus(o)">
                                </div>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary me-1" @click="openModal(o)"><i class="bi bi-pencil-fill"></i></button>
                                <button class="btn btn-sm btn-outline-danger" @click="deleteOffer(o)"><i class="bi bi-trash-fill"></i></button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-transparent d-flex justify-content-between align-items-center">
            <small class="text-muted">Showing <span x-text="from"></span>–<span x-text="to"></span> of <span x-text="total"></span></small>
            <div class="d-flex gap-1">
                <button class="btn btn-sm btn-outline-secondary" @click="page--; fetchOffers()" :disabled="page <= 1"><i class="bi bi-chevron-left"></i></button>
                <button class="btn btn-sm btn-outline-secondary" @click="page++; fetchOffers()" :disabled="page >= lastPage"><i class="bi bi-chevron-right"></i></button>
            </div>
        </div>
    </div>

    
    <div class="modal fade" id="offerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold"><i class="bi bi-star-fill me-2 text-warning"></i><span x-text="form.id ? 'Edit Offer' : 'New Offer'"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <template x-if="formError">
                        <div class="alert alert-danger small py-2 mb-3" x-text="formError"></div>
                    </template>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Offer Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" x-model="form.name" placeholder="e.g. Summer Sale 20%">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Offer Type <span class="text-danger">*</span></label>
                            <select class="form-select" x-model="form.type">
                                <option value="order_discount">Order Discount</option>
                                <option value="bogo">Buy X Get Y (BOGO)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Discount Type <span class="text-danger">*</span></label>
                            <select class="form-select" x-model="form.discount_type">
                                <option value="percentage">Percentage (%)</option>
                                <option value="flat">Flat Amount (₹)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Value <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text" x-text="form.discount_type === 'percentage' ? '%' : '₹'"></span>
                                <input type="number" class="form-control" x-model="form.value" min="0" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Min Spend</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" class="form-control" x-model="form.min_spend" min="0" step="0.01" placeholder="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Max Discount</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" class="form-control" x-model="form.max_discount" min="0" step="0.01" placeholder="Unlimited">
                            </div>
                        </div>
                        
                        <template x-if="form.type === 'bogo'">
                            <div class="col-12">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Buy Qty</label>
                                        <input type="number" class="form-control" x-model="form.buy_qty" min="1">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Get Qty Free</label>
                                        <input type="number" class="form-control" x-model="form.get_qty" min="1">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Product ID</label>
                                        <input type="number" class="form-control" x-model="form.product_id" placeholder="Leave blank for any">
                                    </div>
                                </div>
                            </div>
                        </template>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Starts At</label>
                            <input type="datetime-local" class="form-control" x-model="form.starts_at">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Ends At</label>
                            <input type="datetime-local" class="form-control" x-model="form.ends_at">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Priority</label>
                            <input type="number" class="form-control" x-model="form.priority" min="0" placeholder="0 = lowest">
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="offerActive" x-model="form.is_active">
                                <label class="form-check-label fw-semibold" for="offerActive">Active</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary" @click="saveOffer()" :disabled="saving">
                        <span x-show="saving" class="spinner-border spinner-border-sm me-1"></span>
                        <span x-text="form.id ? 'Update Offer' : 'Create Offer'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
function offersModule() {
    return {
        offers: [], loading: false, saving: false,
        search: '', filterType: '', page: 1, lastPage: 1,
        total: 0, from: 0, to: 0,
        selected: [], stats: { total: 0, active: 0, bogo: 0, order_discount: 0 },
        form: { id: null, name: '', type: 'order_discount', discount_type: 'percentage', value: '', min_spend: '', max_discount: '', product_id: '', buy_qty: 1, get_qty: 1, starts_at: '', ends_at: '', priority: 0, is_active: true },
        formError: null,

        get allSelected() { return this.offers.length > 0 && this.selected.length === this.offers.length; },

        async init() { await this.fetchOffers(); },

        async fetchOffers() {
            this.loading = true;
            try {
                const params = new URLSearchParams({ search: this.search, type: this.filterType, per_page: 15, page: this.page });
                const res = await fetch(`/api/promotions/offers?${params}`, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                const json = await res.json();
                const d = json.data;
                this.offers = d.data || [];
                this.total = d.total || 0; this.from = d.from || 0; this.to = d.to || 0; this.lastPage = d.last_page || 1;
                this.stats.total = this.total;
                this.stats.active = this.offers.filter(o => o.is_active).length;
                this.stats.bogo = this.offers.filter(o => o.type === 'bogo').length;
                this.stats.order_discount = this.offers.filter(o => o.type === 'order_discount').length;
            } catch (e) { console.error(e); } finally { this.loading = false; }
        },

        openModal(o = null) {
            this.formError = null;
            if (o) {
                this.form = { id: o.id, name: o.name, type: o.type, discount_type: o.discount_type, value: o.value, min_spend: o.min_spend || '', max_discount: o.max_discount || '', product_id: o.product_id || '', buy_qty: o.buy_qty || 1, get_qty: o.get_qty || 1, starts_at: o.starts_at ? o.starts_at.substring(0,16) : '', ends_at: o.ends_at ? o.ends_at.substring(0,16) : '', priority: o.priority || 0, is_active: o.is_active };
            } else {
                this.form = { id: null, name: '', type: 'order_discount', discount_type: 'percentage', value: '', min_spend: '', max_discount: '', product_id: '', buy_qty: 1, get_qty: 1, starts_at: '', ends_at: '', priority: 0, is_active: true };
            }
            new bootstrap.Modal(document.getElementById('offerModal')).show();
        },

        async saveOffer() {
            this.saving = true; this.formError = null;
            try {
                const url = this.form.id ? `/api/promotions/offers/${this.form.id}` : '/api/promotions/offers';
                const method = this.form.id ? 'PATCH' : 'POST';
                const res = await fetch(url, { method, headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' }, body: JSON.stringify(this.form) });
                const json = await res.json();
                if (!res.ok) { this.formError = Object.values(json.errors || {}).flat().join(' ') || json.message; return; }
                bootstrap.Modal.getInstance(document.getElementById('offerModal'))?.hide();
                this.fetchOffers();
                window.dispatchEvent(new CustomEvent('notify', { detail: { type: 'success', message: json.message } }));
            } catch (e) { this.formError = 'An error occurred.'; } finally { this.saving = false; }
        },

        async toggleStatus(o) {
            const res = await fetch(`/api/promotions/offers/${o.id}/toggle`, { method: 'PATCH', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' } });
            if (res.ok) this.fetchOffers();
        },

        async deleteOffer(o) {
            if (!confirm(`Delete offer "${o.name}"?`)) return;
            await fetch(`/api/promotions/offers/${o.id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' } });
            this.fetchOffers();
        },

        toggleAll(e) { this.selected = e.target.checked ? this.offers.map(o => o.id) : []; },

        async bulkAction(action) {
            if (!this.selected.length) return;
            if (action === 'delete' && !confirm(`Delete ${this.selected.length} offer(s)?`)) return;
            await fetch('/api/promotions/offers/bulk-action', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' }, body: JSON.stringify({ action, ids: this.selected }) });
            this.selected = []; this.fetchOffers();
        },
    };
}
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/ubuntu/metis/resources/views/promotions/offers.blade.php ENDPATH**/ ?>