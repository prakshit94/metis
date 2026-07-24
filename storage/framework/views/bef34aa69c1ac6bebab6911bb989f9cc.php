<?php $__env->startSection('title', '🎟️ Coupon Codes'); ?>
<?php $__env->startSection('page', 'promotions.coupons'); ?>

<?php $__env->startSection('content'); ?>
<div class="user-management" x-data="couponsModule()" x-init="init()">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 mb-lg-5 mb-xl-6">
        <div>
            <h1 class="h3 mb-0"><i class="bi bi-ticket-perforated-fill text-primary me-2"></i>Coupon Codes</h1>
            <p class="text-muted mb-0">Create and manage discount coupon codes for customers</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary" @click="openModal()">
                <i class="bi bi-plus-lg me-2"></i>New Coupon
            </button>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row g-4 g-lg-5 g-xl-6 mb-5 mb-lg-5 mb-xl-6">
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bi bi-ticket-perforated-fill"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Total Coupons</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.total"></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-success bg-opacity-10 text-success me-3">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Active</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.active"></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-warning bg-opacity-10 text-warning me-3">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Expiring Soon</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.expiring_soon"></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-danger bg-opacity-10 text-danger me-3">
                            <i class="bi bi-x-circle-fill"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Inactive</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.inactive"></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="h5 card-title mb-0">Coupons Directory</h2>
                </div>
                <div class="col-auto">
                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                        <div class="position-relative">
                            <input type="search" class="form-control form-control-sm" placeholder="Search code..." x-model="search" @input.debounce.400ms="fetchCoupons()" style="width: 200px;">
                            <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted"></i>
                        </div>
                        <select class="form-select form-select-sm" x-model="filterStatus" @change="fetchCoupons()" style="width: 150px;">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <!-- Bulk Actions Bar -->
            <div class="bulk-actions-bar p-3 bg-primary bg-opacity-10 border-bottom border-primary border-opacity-25" x-show="selected.length > 0" x-cloak>
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle-fill text-primary me-2"></i>
                        <span class="fw-medium text-primary">
                            <span x-text="selected.length"></span> selected
                        </span>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-success" @click="bulkAction('activate')"><i class="bi bi-check-circle me-1"></i>Activate</button>
                        <button class="btn btn-sm btn-warning" @click="bulkAction('deactivate')"><i class="bi bi-pause-circle me-1"></i>Deactivate</button>
                        <button class="btn btn-sm btn-danger" @click="bulkAction('delete')"><i class="bi bi-trash me-1"></i>Delete</button>
                        <button class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center px-2" @click="selected = []" title="Clear selection">
                            <i class="bi bi-x-lg" style="margin-left: 7px"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class=" text-uppercase small">
                        <tr>
                            <th style="width:40px"><input type="checkbox" class="user-select-checkbox" @change="$event.isTrusted && toggleAll($event)" :checked="allSelected"></th>
                            <th>Code</th>
                            <th>Type</th>
                            <th>Value</th>
                            <th>Min Spend</th>
                            <th>Usage</th>
                            <th>Expiry</th>
                            <th>Created</th>
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
                            <tr :class="{ 'selected': selected.includes(c.id) }">
                                <td><input type="checkbox" class="user-select-checkbox" :value="c.id" x-model="selected"></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="p-2 rounded-circle me-3 d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary" style="width: 38px; height: 38px;">
                                            <i class="fs-5 bi bi-ticket-perforated-fill"></i>
                                        </div>
                                        <div>
                                            <code class="badge bg-body-secondary text-body-emphasis fs-6 px-3 py-2 font-monospace" x-text="c.code"></code>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge rounded-pill px-3 py-2 fw-medium border"
                                          :class="c.type === 'percentage' ? 'bg-info-subtle text-info-emphasis border-info-subtle' : 'bg-primary-subtle text-primary-emphasis border-primary-subtle'">
                                        <i class="bi me-1" :class="c.type === 'percentage' ? 'bi-percent' : 'bi-currency-rupee'"></i>
                                        <span x-text="c.type === 'percentage' ? 'Percentage' : 'Flat Discount'"></span>
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-bold text-body-emphasis fs-6" x-text="c.type === 'percentage' ? c.value + '%' : 'Rs ' + parseFloat(c.value).toFixed(2)"></span>
                                </td>
                                <td>
                                    <span class="fw-semibold text-secondary" x-text="c.min_spend > 0 ? 'Rs ' + parseFloat(c.min_spend).toFixed(2) : 'No Min Spend'"></span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <div class="bg-body-tertiary text-primary border rounded px-2 py-1 fw-bold small">
                                            <i class="bi bi-receipt me-1"></i>
                                            <span x-text="c.used_count || 0"></span>
                                            <span class="text-muted fw-normal" x-show="c.usage_limit">/ <span x-text="c.usage_limit"></span></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        <template x-if="!c.expiry_date">
                                            <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle px-2 py-1 align-self-start">
                                                <i class="bi bi-infinity me-1"></i>Never Expires
                                            </span>
                                        </template>
                                        <template x-if="c.expiry_date">
                                            <span class="text-muted small" style="font-size: 11px;">
                                                <i class="bi bi-clock-history me-1"></i><span x-text="formatDateTime(c.expiry_date)"></span>
                                            </span>
                                        </template>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1 text-muted small">
                                        <span><i class="bi bi-person me-1"></i><span x-text="c.creator ? c.creator.name : 'System'"></span></span>
                                        <span style="font-size: 11px;"><i class="bi bi-clock me-1"></i><span x-text="formatDateTime(c.created_at)"></span></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge rounded-pill px-3 py-2 fw-medium border cursor-pointer"
                                          :class="c.is_active ? 'bg-success-subtle text-success-emphasis border-success-subtle' : 'bg-secondary-subtle text-secondary-emphasis border-secondary-subtle'"
                                          @click="toggleStatus(c)">
                                        <span class="d-inline-block rounded-circle me-1" 
                                              :class="c.is_active ? 'bg-success' : 'bg-secondary'" 
                                              style="width: 6px; height: 6px; vertical-align: middle;"></span>
                                        <span x-text="c.is_active ? 'Active' : 'Inactive'"></span>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="#" @click.prevent="openModal(c)">
                                                    <i class="bi bi-pencil me-2"></i>Edit
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item text-danger" href="#" @click.prevent="deleteCoupon(c)">
                                                    <i class="bi bi-trash me-2"></i>Delete
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center p-3">
                <div class="text-muted small">
                    Showing <span x-text="from"></span> to <span x-text="to"></span> of <span x-text="total"></span> results
                </div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item" :class="{ 'disabled': page <= 1 }">
                            <a class="page-link" href="#" @click.prevent="page--; fetchCoupons()">Previous</a>
                        </li>
                        <li class="page-item" :class="{ 'disabled': page >= lastPage }">
                            <a class="page-link" href="#" @click.prevent="page++; fetchCoupons()">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    
    <div class="modal fade" id="couponModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                
                
                <div class="modal-header bg-body-tertiary border-bottom d-flex align-items-center justify-content-between p-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px;">
                            <i class="bi bi-ticket-perforated-fill fs-4 text-primary"></i>
                        </div>
                        <div>
                            <h4 class="mb-0 fw-bold text-body"><span x-text="form.id ? 'Edit Coupon Profile' : 'Create New Coupon'"></span></h4>
                            <p class="mb-0 small text-muted">Configure customer-redeemable coupon codes and rules</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4 bg-body-tertiary">
                    <template x-if="formError">
                        <div class="alert alert-danger small py-2 mb-3 fw-bold" x-text="formError"></div>
                    </template>
                    
                    <div class="row g-4">
                        <div class="col-12">
                            
                            
                            <div class="card mb-3 border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom">
                                        <div class="bg-primary bg-opacity-10 text-primary rounded-2 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                            <i class="bi bi-ticket fs-6 text-primary"></i>
                                        </div>
                                        <h6 class="mb-0 fw-bold text-uppercase text-body" style="font-size: 11px; letter-spacing: 1px;">Coupon Identity</h6>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Coupon Code *</label>
                                            <input type="text" class="form-control form-control-sm text-uppercase font-monospace fw-semibold" x-model="form.code" placeholder="e.g. SAVE20" style="letter-spacing:2px">
                                            <small class="text-muted d-block mt-1" style="font-size: 10px;">The promo code customers type in to redeem.</small><div class="mt-1 p-2 bg-body rounded-2 border" style="font-size: 9px;"><strong class="text-primary">Use Case:</strong> Share this code in marketing emails (e.g. WELCOME10) to track campaign success.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Discount Type *</label>
                                            <select class="form-select form-select-sm fw-semibold" x-model="form.type">
                                                <option value="percentage">Percentage (%)</option>
                                                <option value="flat">Flat Amount (Rs )</option>
                                                <option value="free_shipping">Free Shipping</option>
                                                <option value="free_product">Free Product</option>
                                            </select>
                                            <small class="text-muted d-block mt-1" style="font-size: 10px;">Choose percentage or fixed rate.</small><div class="mt-1 p-2 bg-body rounded-2 border" style="font-size: 9px;"><strong class="text-primary">Use Case:</strong> Percentage discounts scale with cart size. Flat discounts are better for maintaining predictable profit margins on high-value carts.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            
                            <div class="card mb-3 border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom">
                                        <div class="bg-success bg-opacity-10 text-success rounded-2 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                            <i class="bi bi-percent fs-6 text-success"></i>
                                        </div>
                                        <h6 class="mb-0 fw-bold text-uppercase text-body" style="font-size: 11px; letter-spacing: 1px;">Value & Limits</h6>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Discount Value *</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text" x-text="form.type === 'percentage' ? '%' : 'Rs '"></span>
                                                <input type="number" class="form-control fw-semibold" x-model="form.value" min="0" step="0.01">
                                            </div>
                                            <small class="text-muted d-block mt-1" style="font-size: 10px;">Numeric value of the discount.</small>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Min Spend</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">Rs </span>
                                                <input type="number" class="form-control fw-semibold" x-model="form.min_spend" min="0" step="0.01" placeholder="0">
                                            </div>
                                            <small class="text-muted d-block mt-1" style="font-size: 10px;">Minimum purchase requirement to unlock coupon.</small><div class="mt-1 p-2 bg-body rounded-2 border" style="font-size: 9px;"><strong class="text-primary">Use Case:</strong> Encourage customers to add more items to their cart to reach the threshold (increases Average Order Value).</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Max Discount</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">Rs </span>
                                                <input type="number" class="form-control fw-semibold" x-model="form.max_discount" min="0" step="0.01" placeholder="Unlimited">
                                            </div>
                                            <small class="text-muted d-block mt-1" style="font-size: 10px;">Maximum cap. Leave empty/0 for unlimited.</small><div class="mt-1 p-2 bg-body rounded-2 border" style="font-size: 9px;"><strong class="text-primary">Use Case:</strong> Crucial when using Percentage discounts to protect your margins on very large bulk orders (e.g. 50% off up to Rs 1000).</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            
                            <div class="card border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary" style="z-index: 10;">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom">
                                        <div class="bg-info bg-opacity-10 text-info rounded-2 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                            <i class="bi bi-calendar-event fs-6 text-info"></i>
                                        </div>
                                        <h6 class="mb-0 fw-bold text-uppercase text-body" style="font-size: 11px; letter-spacing: 1px;">Validity & Usage Limit</h6>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Expiry Date</label>
                                            <input type="date" class="form-control form-control-sm fw-semibold" x-model="form.expiry_date">
                                            <small class="text-muted d-block mt-1" style="font-size: 10px;">Date when this coupon becomes invalid.</small><div class="mt-1 p-2 bg-body rounded-2 border" style="font-size: 9px;"><strong class="text-primary">Use Case:</strong> Drive urgency by creating time-limited promotions (e.g. "Sale ends Sunday!").</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Usage Limit</label>
                                            <input type="number" class="form-control form-control-sm fw-semibold" x-model="form.usage_limit" min="0" placeholder="Unlimited">
                                            <small class="text-muted d-block mt-1" style="font-size: 10px;">Total times code can be redeemed (empty = unlimited).</small><div class="mt-1 p-2 bg-body rounded-2 border" style="font-size: 9px;"><strong class="text-primary">Use Case:</strong> Create scarcity and FOMO (e.g. "Valid only for the first 100 buyers!").</div>
                                        </div>
                                        <div class="col-12 mt-2">
                                            <div class="d-flex align-items-center gap-2 pt-2 border-top">
                                                <div class="form-check form-switch cursor-pointer">
                                                    <input class="form-check-input" type="checkbox" id="couponActive" x-model="form.is_active">
                                                    <label class="form-check-label fw-bold text-muted text-uppercase" for="couponActive" style="font-size: 9px; letter-spacing: 0.1em; cursor: pointer;">Status: Active</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                
                <div class="modal-footer bg-body-tertiary border-top d-flex justify-content-end gap-3 p-3">
                    <button type="button" data-bs-dismiss="modal" class="btn text-muted fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 1px;">Cancel</button>
                    <button class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" @click="saveCoupon()" :disabled="saving">
                        <span x-show="saving" class="spinner-border spinner-border-sm me-2"></span>
                        <span x-text="form.id ? 'Save Changes' : 'Create Coupon'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.custom-hover-bg:hover { background-color: rgba(var(--bs-primary-rgb), 0.1); }
input[type="text"]:focus, input[type="email"]:focus, input[type="number"]:focus, input[type="date"]:focus, select:focus, textarea:focus {
    box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.15) !important;
    border-color: var(--bs-primary) !important;
}
.cursor-pointer { cursor: pointer; }
</style>

<?php $__env->startPush('scripts'); ?>
<script>
function couponsModule() {
    return {
        coupons: [], loading: false, saving: false,
        search: '', filterStatus: '', page: 1, lastPage: 1,
        total: 0, from: 0, to: 0,
        selected: [], stats: { total: 0, active: 0, inactive: 0, expiring_soon: 0 },
        form: { id: null, code: '', type: 'percentage', value: '', min_spend: '', max_discount: '', free_product_id: '', free_qty: 1, expiry_date: '', usage_limit: '', is_active: true },
        formError: null,

        formatDateTime(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr.replace(' ', 'T'));
            if (isNaN(d.getTime())) return dateStr;
            return d.toLocaleString('en-IN', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        },

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
                this.form = { id: c.id, code: c.code, type: c.type, value: c.value, min_spend: c.min_spend || '', max_discount: c.max_discount || '', free_product_id: c.free_product_id || '', free_qty: c.free_qty || 1, expiry_date: c.expiry_date || '', usage_limit: c.usage_limit || '', is_active: c.is_active };
            } else {
                this.form = { id: null, code: '', type: 'percentage', value: '', min_spend: '', max_discount: '', free_product_id: '', free_qty: 1, expiry_date: '', usage_limit: '', is_active: true };
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

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/user/metis/resources/views/promotions/coupons.blade.php ENDPATH**/ ?>