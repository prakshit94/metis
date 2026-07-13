<?php $__env->startSection('title', 'Coupon Codes'); ?>
<?php $__env->startSection('page', 'promotions.coupons'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid p-4" x-data="couponsModule()" x-init="init()">

    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><i class="bi bi-ticket-perforated-fill me-2 text-primary"></i>Coupon Codes</h1>
            <p class="text-muted mb-0 small">Create and manage discount coupon codes for customers</p>
        </div>
        <button class="btn btn-primary" @click="openModal()">
            <i class="bi bi-plus-lg me-1"></i> New Coupon
        </button>
    </div>

    
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-primary bg-opacity-10 p-3"><i class="bi bi-ticket-perforated-fill text-primary fs-5"></i></div>
                    <div><div class="fw-bold fs-4" x-text="stats.total">0</div><small class="text-muted">Total Coupons</small></div>
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
                    <div class="rounded-circle bg-warning bg-opacity-10 p-3"><i class="bi bi-hourglass-split text-warning fs-5"></i></div>
                    <div><div class="fw-bold fs-4" x-text="stats.expiring_soon">0</div><small class="text-muted">Expiring Soon</small></div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-danger bg-opacity-10 p-3"><i class="bi bi-x-circle-fill text-danger fs-5"></i></div>
                    <div><div class="fw-bold fs-4" x-text="stats.inactive">0</div><small class="text-muted">Inactive</small></div>
                </div>
            </div>
        </div>
    </div>

    
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-bottom d-flex flex-wrap gap-2 align-items-center py-3">
            <div class="input-group" style="max-width:260px">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control" placeholder="Search coupon code…" x-model="search" @input.debounce.400ms="fetchCoupons()" />
            </div>
            <select class="form-select" style="max-width:160px" x-model="filterStatus" @change="fetchCoupons()">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
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
                        <th>Code</th>
                        <th>Type</th>
                        <th>Value</th>
                        <th>Min Spend</th>
                        <th>Usage</th>
                        <th>Expiry</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="loading">
                        <tr><td colspan="9" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>
                    </template>
                    <template x-if="!loading && coupons.length === 0">
                        <tr><td colspan="9" class="text-center py-5 text-muted"><i class="bi bi-ticket-perforated fs-1 d-block mb-2"></i>No coupons found</td></tr>
                    </template>
                    <template x-for="c in coupons" :key="c.id">
                        <tr>
                            <td><input type="checkbox" class="form-check-input" :value="c.id" x-model="selected"></td>
                            <td><code class="badge bg-dark fs-6 px-3 py-2" x-text="c.code"></code></td>
                            <td><span class="badge" :class="c.type === 'percentage' ? 'bg-info text-dark' : 'bg-secondary'" x-text="c.type === 'percentage' ? '% Off' : 'Flat ₹'"></span></td>
                            <td class="fw-semibold" x-text="c.type === 'percentage' ? c.value + '%' : '₹' + parseFloat(c.value).toFixed(2)"></td>
                            <td x-text="c.min_spend > 0 ? '₹' + parseFloat(c.min_spend).toFixed(2) : '—'"></td>
                            <td>
                                <span x-text="(c.used_count || 0)"></span>
                                <span class="text-muted" x-show="c.usage_limit">/ <span x-text="c.usage_limit"></span></span>
                            </td>
                            <td>
                                <span x-show="!c.expiry_date" class="text-muted">Never</span>
                                <span x-show="c.expiry_date" x-text="c.expiry_date" class="small"></span>
                            </td>
                            <td>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" :checked="c.is_active" @change="toggleStatus(c)">
                                </div>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary me-1" @click="openModal(c)" title="Edit"><i class="bi bi-pencil-fill"></i></button>
                                <button class="btn btn-sm btn-outline-danger" @click="deleteCoupon(c)" title="Delete"><i class="bi bi-trash-fill"></i></button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        
        <div class="card-footer bg-transparent d-flex justify-content-between align-items-center">
            <small class="text-muted">Showing <span x-text="from"></span>–<span x-text="to"></span> of <span x-text="total"></span></small>
            <div class="d-flex gap-1">
                <button class="btn btn-sm btn-outline-secondary" @click="page--; fetchCoupons()" :disabled="page <= 1"><i class="bi bi-chevron-left"></i></button>
                <button class="btn btn-sm btn-outline-secondary" @click="page++; fetchCoupons()" :disabled="page >= lastPage"><i class="bi bi-chevron-right"></i></button>
            </div>
        </div>
    </div>

    
    <div class="modal fade" id="couponModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold"><i class="bi bi-ticket-perforated-fill me-2 text-primary"></i><span x-text="form.id ? 'Edit Coupon' : 'New Coupon Code'"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <template x-if="formError">
                        <div class="alert alert-danger small py-2 mb-3" x-text="formError"></div>
                    </template>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Coupon Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase font-monospace" x-model="form.code" placeholder="e.g. SAVE20" style="letter-spacing:2px">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Discount Type <span class="text-danger">*</span></label>
                            <select class="form-select" x-model="form.type">
                                <option value="percentage">Percentage (%)</option>
                                <option value="flat">Flat Amount (₹)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Discount Value <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text" x-text="form.type === 'percentage' ? '%' : '₹'"></span>
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
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Expiry Date</label>
                            <input type="date" class="form-control" x-model="form.expiry_date">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Usage Limit</label>
                            <input type="number" class="form-control" x-model="form.usage_limit" min="0" placeholder="Unlimited">
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="couponActive" x-model="form.is_active">
                                <label class="form-check-label fw-semibold" for="couponActive">Active</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary" @click="saveCoupon()" :disabled="saving">
                        <span x-show="saving" class="spinner-border spinner-border-sm me-1"></span>
                        <span x-text="form.id ? 'Update Coupon' : 'Create Coupon'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
function couponsModule() {
    return {
        coupons: [], loading: false, saving: false,
        search: '', filterStatus: '', page: 1, lastPage: 1,
        total: 0, from: 0, to: 0,
        selected: [], stats: { total: 0, active: 0, inactive: 0, expiring_soon: 0 },
        form: { id: null, code: '', type: 'percentage', value: '', min_spend: '', max_discount: '', expiry_date: '', usage_limit: '', is_active: true },
        formError: null,

        get allSelected() { return this.coupons.length > 0 && this.selected.length === this.coupons.length; },

        async init() { await this.fetchCoupons(); },

        async fetchCoupons() {
            this.loading = true;
            try {
                const params = new URLSearchParams({ search: this.search, status: this.filterStatus, per_page: 15, page: this.page });
                const res = await fetch(`/api/promotions/coupons?${params}`, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                const json = await res.json();
                const d = json.data;
                this.coupons = d.data || [];
                this.total = d.total || 0; this.from = d.from || 0; this.to = d.to || 0; this.lastPage = d.last_page || 1;
                this.stats.total = this.total;
                this.stats.active = this.coupons.filter(c => c.is_active).length;
                this.stats.inactive = this.coupons.filter(c => !c.is_active).length;
                this.stats.expiring_soon = this.coupons.filter(c => { if (!c.expiry_date) return false; const d = new Date(c.expiry_date); const n = new Date(); return d > n && (d - n) / 86400000 <= 7; }).length;
            } catch (e) { console.error(e); } finally { this.loading = false; }
        },

        openModal(c = null) {
            this.formError = null;
            if (c) {
                this.form = { id: c.id, code: c.code, type: c.type, value: c.value, min_spend: c.min_spend || '', max_discount: c.max_discount || '', expiry_date: c.expiry_date || '', usage_limit: c.usage_limit || '', is_active: c.is_active };
            } else {
                this.form = { id: null, code: '', type: 'percentage', value: '', min_spend: '', max_discount: '', expiry_date: '', usage_limit: '', is_active: true };
            }
            new bootstrap.Modal(document.getElementById('couponModal')).show();
        },

        async saveCoupon() {
            this.saving = true; this.formError = null;
            try {
                const url = this.form.id ? `/api/promotions/coupons/${this.form.id}` : '/api/promotions/coupons';
                const method = this.form.id ? 'PATCH' : 'POST';
                const res = await fetch(url, { method, headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' }, body: JSON.stringify(this.form) });
                const json = await res.json();
                if (!res.ok) { this.formError = Object.values(json.errors || {}).flat().join(' ') || json.message; return; }
                bootstrap.Modal.getInstance(document.getElementById('couponModal'))?.hide();
                this.fetchCoupons();
                window.dispatchEvent(new CustomEvent('notify', { detail: { type: 'success', message: json.message } }));
            } catch (e) { this.formError = 'An error occurred.'; } finally { this.saving = false; }
        },

        async toggleStatus(c) {
            const res = await fetch(`/api/promotions/coupons/${c.id}/toggle`, { method: 'PATCH', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' } });
            if (res.ok) this.fetchCoupons();
        },

        async deleteCoupon(c) {
            if (!confirm(`Delete coupon "${c.code}"?`)) return;
            await fetch(`/api/promotions/coupons/${c.id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' } });
            this.fetchCoupons();
        },

        toggleAll(e) { this.selected = e.target.checked ? this.coupons.map(c => c.id) : []; },

        async bulkAction(action) {
            if (!this.selected.length) return;
            if (action === 'delete' && !confirm(`Delete ${this.selected.length} coupon(s)?`)) return;
            await fetch('/api/promotions/coupons/bulk-action', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' }, body: JSON.stringify({ action, ids: this.selected }) });
            this.selected = []; this.fetchCoupons();
        },
    };
}
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/ubuntu/metis/resources/views/promotions/coupons.blade.php ENDPATH**/ ?>