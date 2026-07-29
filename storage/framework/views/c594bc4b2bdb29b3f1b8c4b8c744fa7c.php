<?php $__env->startSection('title', '📦 Goods Receipts (GRN)'); ?>
<?php $__env->startSection('page', 'procurement-goods-receipts'); ?>

<?php $__env->startSection('content'); ?>
<div class="user-management" x-data="goodsReceiptsTable()">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 mb-lg-5 mb-xl-6">
        <div>
            <h1 class="h3 mb-0"><i class="bi bi-box-seam-fill text-primary me-2"></i>Goods Receipts (GRN)</h1>
            <p class="text-muted mb-0">View and track all received inventory and supplier deliveries</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?php echo e(route('procurement.purchase-orders.index')); ?>" class="btn btn-primary">
                <i class="bi bi-plus-lg me-2"></i>Receive Against PO
            </a>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row g-4 g-lg-5 g-xl-6 mb-5 mb-lg-5 mb-xl-6">
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bi bi-clipboard-check-fill"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Total GRNs</p>
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
                            <i class="bi bi-calendar-check-fill"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Received This Month</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.this_month"></span></div>
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
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Pending Putaway</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.pending"></span></div>
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
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Discrepancies</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.discrepancies"></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Container -->
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="h5 card-title mb-0">Receipts Directory</h2>
                </div>
                <div class="col-auto">
                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                        <div class="position-relative">
                            <input type="search" class="form-control form-control-sm" placeholder="Search GRN/PO..." x-model.debounce.500ms="searchQuery" style="width: 200px;">
                            <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted"></i>
                        </div>
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
                        <button class="btn btn-sm btn-success" @click="bulkAction('approve')"><i class="bi bi-check-circle me-1"></i>Approve</button>
                        <button class="btn btn-sm btn-primary" @click="bulkAction('print')"><i class="bi bi-printer me-1"></i>Print Labels</button>
                        <button class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center px-2" @click="selected = []" title="Clear selection">
                            <i class="bi bi-x-lg" style="margin-left: 7px"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="text-uppercase small">
                        <tr>
                            <th style="width:40px"><input type="checkbox" class="user-select-checkbox" @change="$event.isTrusted && toggleAll($event)" :checked="allSelected"></th>
                            <th>GRN Number</th>
                            <th>Purchase Order</th>
                            <th>Supplier</th>
                            <th>Warehouse</th>
                            <th>Date Received</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="isLoading">
                            <tr><td colspan="7" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>
                        </template>
                        <template x-if="!isLoading && items.length === 0">
                            <tr><td colspan="7" class="text-center py-5 text-muted"><i class="bi bi-box-seam fs-1 d-block mb-2"></i>No goods receipts found</td></tr>
                        </template>
                        <template x-for="item in items" :key="item.id">
                            <tr :class="{ 'selected': selected.includes(item.id) }">
                                <td><input type="checkbox" class="user-select-checkbox" :value="item.id" x-model="selected"></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="p-2 rounded-circle me-3 d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary" style="width: 38px; height: 38px;">
                                            <i class="fs-5 bi bi-clipboard-check-fill"></i>
                                        </div>
                                        <div>
                                            <code class="badge bg-body-secondary text-body-emphasis fs-6 px-3 py-2 font-monospace" x-text="item.grn_number"></code>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge rounded-pill px-3 py-2 fw-medium border bg-info-subtle text-info-emphasis border-info-subtle">
                                        <i class="bi bi-receipt me-1"></i>
                                        <span x-text="item.purchase_order ? item.purchase_order.po_number : '—'"></span>
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-bold text-body-emphasis fs-6" x-text="(item.purchase_order && item.purchase_order.supplier) ? (item.purchase_order.supplier.company_name || item.purchase_order.supplier.firstname) : 'Unknown'"></span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <div class="bg-body-tertiary text-primary border rounded px-2 py-1 fw-bold small">
                                            <i class="bi bi-buildings me-1"></i>
                                            <span x-text="item.warehouse ? item.warehouse.name : 'Unknown'"></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-muted small" style="font-size: 11px;">
                                        <i class="bi bi-calendar-event me-1"></i><span x-text="item.received_date"></span>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="#" @click.prevent="alert('View GRN details feature coming soon')">
                                                    <i class="bi bi-eye me-2"></i>View Details
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
                        <li class="page-item" :class="{ 'disabled': currentPage <= 1 }">
                            <a class="page-link" href="#" @click.prevent="currentPage--; fetchData()">Previous</a>
                        </li>
                        <li class="page-item" :class="{ 'disabled': currentPage >= totalPages }">
                            <a class="page-link" href="#" @click.prevent="currentPage++; fetchData()">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<style>
.custom-hover-bg:hover { background-color: rgba(var(--bs-primary-rgb), 0.1); }
input[type="text"]:focus, input[type="search"]:focus {
    box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.15) !important;
    border-color: var(--bs-primary) !important;
}
.cursor-pointer { cursor: pointer; }
</style>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('goodsReceiptsTable', () => ({
        items: [],
        isLoading: false,
        searchQuery: '',
        currentPage: 1,
        totalPages: 1,
        total: 0, from: 0, to: 0,
        selected: [],
        stats: { total: <?php echo e($stats['total'] ?? 0); ?>, this_month: <?php echo e($stats['this_month'] ?? 0); ?>, pending: 0, discrepancies: 0 },
        
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
        
        bulkAction(action) {
            if (!this.selected.length) return;
            alert(`Bulk action ${action} on ${this.selected.length} items (UI Scaffold)`);
            this.selected = [];
        }
    }));
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/user/metis/resources/views/procurement/goods-receipts/index.blade.php ENDPATH**/ ?>