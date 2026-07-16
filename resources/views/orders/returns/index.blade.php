@extends('layouts.app')
@section('title', 'Returns & Refunds')
@section('page', 'returns')

@section('content')
<div class="returns-management" x-data="returnsTable" x-init="init()">

<div class="d-flex justify-content-between align-items-center mb-4 mb-lg-5">
    <div>
        <h1 class="h3 mb-0">Returns &amp; Refunds</h1>
        <p class="text-muted mb-0">Quality check returned items and process financial settlements</p>
    </div>
    <a href="{{ route('orders') }}" class="btn btn-outline-secondary btn-sm px-3">
        <i class="bi bi-bag-check me-2"></i>Back to Orders
    </a>
</div>

{{-- Stats --}}
<div class="row g-3 g-lg-4 mb-4 mb-lg-5">
    <div class="col-6 col-lg-2">
        <div class="card stats-card h-100" @click="clearFilters()" style="cursor:pointer">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="stats-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-arrow-return-left"></i></div>
                    <div><p class="section-label mb-1">Total Returns</p><div class="h4 mb-0 fw-bold" x-text="stats.total">0</div></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="card stats-card h-100" @click="statusFilter='pending'; filterReturns()" style="cursor:pointer">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="stats-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-hourglass-split"></i></div>
                    <div><p class="section-label mb-1">Pending QC</p><div class="h4 mb-0 fw-bold" x-text="stats.pending_qc">0</div></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="card stats-card h-100" @click="statusFilter='completed'; filterReturns()" style="cursor:pointer">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="stats-icon bg-success bg-opacity-10 text-success"><i class="bi bi-check2-circle"></i></div>
                    <div><p class="section-label mb-1">Completed</p><div class="h4 mb-0 fw-bold" x-text="stats.completed">0</div></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="card stats-card h-100" @click="statusFilter='rejected'; filterReturns()" style="cursor:pointer">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="stats-icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-x-circle"></i></div>
                    <div><p class="section-label mb-1">Rejected</p><div class="h4 mb-0 fw-bold" x-text="stats.rejected">0</div></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="card stats-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="stats-icon bg-info bg-opacity-10 text-info"><i class="bi bi-cash-coin"></i></div>
                    <div><p class="section-label mb-1">Refunded</p><div class="h5 mb-0 fw-bold" x-text="formatCurrency(stats.total_refunded)">₹0</div></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="card stats-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="stats-icon" style="width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;background:rgba(99,102,241,.1)"><i class="bi bi-ticket-perforated" style="color:#6366f1;font-size:1.25rem"></i></div>
                    <div><p class="section-label mb-1">Credit Notes</p><div class="h5 mb-0 fw-bold" x-text="formatCurrency(stats.total_credited)">₹0</div></div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Returns Table + Detail Panel --}}
<div class="row g-0">

<div :class="selectedReturn ? 'col-lg-5' : 'col-12'">
{{-- Table Card --}}
<div class="card" :class="selectedReturn ? 'rounded-end-0 border-end-0' : ''">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="h5 card-title mb-0">Returns Directory</h2>
            </div>
            <div class="col-auto">
                <div class="d-flex flex-wrap gap-2 justify-content-end">
                    <div class="position-relative">
                        <input type="search"
                               class="form-control form-control-sm"
                               placeholder="Search returns..."
                               x-model="searchQuery"
                               @input="filterReturns()"
                               style="width: 200px;">
                        <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted"></i>
                    </div>
                    <select class="form-select form-select-sm" x-model="statusFilter" @change="filterReturns()" style="width: 150px;">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="received">Received</option>
                        <option value="qc_in_progress">QC In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="rejected">Rejected</option>
                    </select>
                    <select class="form-select form-select-sm" x-model="financialFilter" @change="filterReturns()" style="width: 160px;">
                        <option value="">All Financial</option>
                        <option value="pending">Refund Pending</option>
                        <option value="partial_refund">Partial Refund</option>
                        <option value="fully_refunded">Fully Refunded</option>
                        <option value="credited">Credited</option>
                    </select>
                    <button class="btn btn-sm btn-outline-secondary" @click="clearFilters()" title="Reset">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card-body p-0">
        {{-- Bulk Actions Bar --}}
        <div class="bulk-actions-bar p-3 bg-primary bg-opacity-10 border-bottom border-primary border-opacity-25"
             x-show="selectedReturns.length > 0" x-transition>
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-check-circle-fill text-primary"></i>
                    <span class="fw-medium text-primary">
                        <strong x-text="selectedReturns.length"></strong> return(s) selected
                    </span>
                </div>
                <button class="btn btn-sm btn-outline-secondary" @click="selectedReturns = []">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40px;">
                            <input type="checkbox"
                                   class="form-check-input"
                                   style="cursor: pointer; appearance: auto; -webkit-appearance: checkbox;"
                                   @change="toggleAll($event.target.checked)"
                                   :checked="selectedReturns.length === returns.length && returns.length > 0">
                        </th>
                        <th scope="col" role="button" tabindex="0"
                            @click="sortBy('return_no')" @keydown.enter.prevent="sortBy('return_no')"
                            :aria-sort="sortField === 'return_no' ? (sortDirection === 'asc' ? 'ascending' : 'descending') : 'none'"
                            class="sortable">
                            Return #
                            <i class="bi bi-arrow-up" x-show="sortField === 'return_no' && sortDirection === 'asc'" aria-hidden="true"></i>
                            <i class="bi bi-arrow-down" x-show="sortField === 'return_no' && sortDirection === 'desc'" aria-hidden="true"></i>
                        </th>
                        <th scope="col">Customer</th>
                        <th scope="col">Items</th>
                        <th scope="col">Refund Amt</th>
                        <th scope="col">Status</th>
                        <th scope="col" role="button" tabindex="0"
                            @click="sortBy('created_at')" @keydown.enter.prevent="sortBy('created_at')"
                            :aria-sort="sortField === 'created_at' ? (sortDirection === 'asc' ? 'ascending' : 'descending') : 'none'"
                            class="sortable">
                            Date
                            <i class="bi bi-arrow-up" x-show="sortField === 'created_at' && sortDirection === 'asc'" aria-hidden="true"></i>
                            <i class="bi bi-arrow-down" x-show="sortField === 'created_at' && sortDirection === 'desc'" aria-hidden="true"></i>
                        </th>
                        <th style="width: 100px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="r in returns" :key="r.id">
                        <tr :class="{ 'selected': selectedReturns.includes(String(r.id)) }">
                            <td>
                                <input type="checkbox"
                                       class="form-check-input"
                                       style="cursor: pointer; appearance: auto; -webkit-appearance: checkbox;"
                                       :value="String(r.id)"
                                       x-model="selectedReturns">
                            </td>
                            <td>
                                <div class="fw-medium" x-text="r.return_no"></div>
                                <small class="text-muted font-monospace" x-text="r.order_no"></small>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center me-2 fw-bold flex-shrink-0"
                                         style="width:32px;height:32px;font-size:.75rem"
                                         x-text="r.customer.name.charAt(0).toUpperCase()"></div>
                                    <div>
                                        <div class="fw-medium small" x-text="r.customer.name"></div>
                                        <small class="text-muted text-capitalize" x-text="r.reason"></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="order-items small">
                                    <div class="fw-medium" x-text="r.items.length + ' item' + (r.items.length !== 1 ? 's' : '')"></div>
                                    <small class="text-muted" x-text="r.items.length > 0 ? r.items[0].product.name + (r.items.length > 1 ? ' +' + (r.items.length-1) + ' more' : '') : '—'"></small>
                                </div>
                            </td>
                            <td>
                                <div class="fw-medium small" x-text="formatCurrency(r.refund_amount)"></div>
                                <small :style="`color:${getFinancialStatusColor(r.financial_status)}`"
                                       x-text="r.financial_status.replace(/_/g,' ').replace(/\b\w/g,l=>l.toUpperCase())"></small>
                            </td>
                            <td>
                                <span class="badge small"
                                      :style="`background-color:${getStatusColor(r.status)}; color:#fff`"
                                      x-text="r.status.replace(/_/g,' ').replace(/\b\w/g,l=>l.toUpperCase())"></span>
                            </td>
                            <td>
                                <div class="small fw-medium" x-text="r.created_at ? new Date(r.created_at).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }) : '—'"></div>
                                <small class="text-muted" x-text="r.created_at ? new Date(r.created_at).toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', hour12: true }) : ''"></small>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="#" @click.prevent="viewReturnDetails(r)">
                                            <i class="bi bi-eye me-2"></i>View / QC
                                        </a></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <template x-if="returns.length === 0 && !isLoading">
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No returns found matching current criteria.
                            </td>
                        </tr>
                    </template>
                    <template x-if="isLoading">
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <div class="spinner-border spinner-border-sm text-primary me-2"></div>Loading returns...
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center p-3 border-top">
            <div class="small text-muted">
                Page <span class="fw-semibold text-body-emphasis" x-text="currentPage"></span>
                of <span class="fw-semibold text-body-emphasis" x-text="totalPages"></span>
                &nbsp;&bull;&nbsp;<span x-text="totalReturns"></span> total
            </div>
            <div class="d-flex gap-1">
                <button class="btn btn-sm btn-light border rounded-3 px-3" :disabled="currentPage <= 1" @click="currentPage--; loadReturns()">
                    <i class="bi bi-chevron-left small"></i>
                </button>
                <button class="btn btn-sm btn-light border rounded-3 px-3" :disabled="currentPage >= totalPages" @click="currentPage++; loadReturns()">
                    <i class="bi bi-chevron-right small"></i>
                </button>
            </div>
        </div>
    </div>
</div>
</div>

{{-- Detail Panel --}}
<div class="col-lg-7" x-show="selectedReturn" x-cloak>
    <div class="card h-100 rounded-start-0 detail-panel">
        <div class="card-header px-4 py-3" x-show="selectedReturn">
            <div class="d-flex justify-content-between align-items-start gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h5 class="fw-bold mb-0 font-monospace text-primary" x-text="selectedReturn?.return_no"></h5>
                        <span class="badge" :style="`background-color:${getStatusColor(selectedReturn?.status)};color:#fff`"
                              x-text="(selectedReturn?.status||'').replace(/_/g,' ').replace(/\b\w/g,l=>l.toUpperCase())"></span>
                    </div>
                    <p class="text-muted small mb-0">
                        Order: <span class="fw-semibold font-monospace" x-text="selectedReturn?.order_no"></span>
                        &bull; <span x-text="selectedReturn?.customer?.name"></span>
                    </p>
                </div>
                <button class="btn btn-sm btn-light border flex-shrink-0 rounded-circle"
                        style="width:32px;height:32px;padding:0" @click="closeReturnDetails()">
                    <i class="bi bi-x small"></i>
                </button>
            </div>
        </div>

        <div class="card-body p-0 panel-scroll-body" x-show="selectedReturn">
            <div class="p-4">
                {{-- QC --}}
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="stats-icon bg-primary bg-opacity-10 text-primary" style="width:32px;height:32px;border-radius:8px;font-size:.85rem">
                                <i class="bi bi-clipboard2-check"></i>
                            </div>
                            <div>
                                <p class="fw-semibold mb-0">Quality Check</p>
                                <p class="section-label mb-0">Assign received qty to restock or damage</p>
                            </div>
                        </div>
                        <button class="btn btn-sm btn-primary px-3"
                                x-show="selectedReturn?.status !== 'completed' && selectedReturn?.status !== 'rejected'"
                                @click="processQc()">
                            <i class="bi bi-check-lg me-1"></i>Submit QC
                        </button>
                        <span x-show="selectedReturn?.status === 'completed'"
                              class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2">
                            <i class="bi bi-check-circle me-1"></i>QC Done
                        </span>
                    </div>
                    <div class="card border-0 bg-body-tertiary rounded-3">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0" style="font-size:.82rem">
                                <thead>
                                    <tr class="border-bottom">
                                        <th class="ps-3 py-2 border-0 fw-semibold text-secondary" style="font-size:.72rem">PRODUCT</th>
                                        <th class="py-2 border-0 fw-semibold text-secondary text-center" style="font-size:.72rem">REQ</th>
                                        <th class="py-2 border-0 fw-semibold text-secondary text-center" style="font-size:.72rem">RECV</th>
                                        <th class="py-2 border-0 fw-semibold text-success text-center" style="font-size:.72rem">RESTOCK</th>
                                        <th class="py-2 border-0 fw-semibold text-danger text-center" style="font-size:.72rem">DAMAGE</th>
                                        <th class="pe-3 py-2 border-0 fw-semibold text-secondary" style="font-size:.72rem">NOTES</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="item in (selectedReturn?.items || [])" :key="item.id">
                                        <tr>
                                            <td class="ps-3 py-2 align-middle">
                                                <div class="fw-medium" x-text="item.product.name" style="max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"></div>
                                                <div class="text-muted font-monospace" style="font-size:.7rem" x-text="item.product.sku"></div>
                                            </td>
                                            <td class="text-center align-middle fw-bold" x-text="item.requested_qty"></td>
                                            <td class="text-center align-middle" style="width:70px">
                                                <input type="number" class="form-control form-control-sm text-center"
                                                       x-model.number="item.received_qty" :disabled="selectedReturn?.status==='completed'" min="0" :max="item.requested_qty">
                                            </td>
                                            <td class="text-center align-middle" style="width:70px">
                                                <input type="number" class="form-control form-control-sm text-center border-success"
                                                       x-model.number="item.restocked_qty" :disabled="selectedReturn?.status==='completed'" min="0">
                                            </td>
                                            <td class="text-center align-middle" style="width:70px">
                                                <input type="number" class="form-control form-control-sm text-center border-danger"
                                                       x-model.number="item.damaged_qty" :disabled="selectedReturn?.status==='completed'" min="0">
                                            </td>
                                            <td class="pe-3 align-middle" style="min-width:110px">
                                                <input type="text" class="form-control form-control-sm"
                                                       x-model="item.qc_notes" :disabled="selectedReturn?.status==='completed'" placeholder="Notes…">
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <hr class="opacity-25">

                {{-- Financial --}}
                <div>
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="stats-icon bg-success bg-opacity-10 text-success" style="width:32px;height:32px;border-radius:8px;font-size:.85rem">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                        <div>
                            <p class="fw-semibold mb-0">Financial Settlement</p>
                            <p class="section-label mb-0">Issue refund or credit note</p>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-4"><div class="finance-tile"><div class="tile-label">Total Paid</div><div class="tile-value text-body-emphasis" x-text="formatCurrency(selectedReturn?.totalPaid)"></div></div></div>
                        <div class="col-4"><div class="finance-tile"><div class="tile-label">Refunded</div><div class="tile-value text-danger" x-text="formatCurrency(selectedReturn?.refund_amount)"></div></div></div>
                        <div class="col-4"><div class="finance-tile"><div class="tile-label">Credit Note</div><div class="tile-value text-success" x-text="formatCurrency(selectedReturn?.credit_note_amount)"></div></div></div>
                    </div>

                    <div x-show="selectedReturn?.status === 'completed'">
                        <div class="card border-0 bg-body-tertiary rounded-3">
                            <div class="card-body p-4">
                                <div class="row g-3 align-items-end">
                                    <div class="col-sm-4">
                                        <label class="form-label small fw-semibold">Action</label>
                                        <select class="form-select form-select-sm" x-model="financeAction">
                                            <option value="refund">Issue Refund</option>
                                            <option value="credit_note">Credit Note</option>
                                        </select>
                                    </div>
                                    <div class="col-sm-4">
                                        <label class="form-label small fw-semibold">Amount <span class="text-danger">*</span></label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">₹</span>
                                            <input type="number" class="form-control" x-model.number="financeAmount" min="0" step="0.01" placeholder="0.00">
                                        </div>
                                    </div>
                                    <div x-show="financeAction === 'refund'" class="col-sm-4">
                                        <label class="form-label small fw-semibold">Method</label>
                                        <select class="form-select form-select-sm" x-model="financeMethod">
                                            <option value="upi">UPI</option>
                                            <option value="card">Card</option>
                                            <option value="bank_transfer">Bank Transfer</option>
                                            <option value="cash">Cash</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <button class="btn btn-warning fw-semibold px-4" @click="processFinance()">
                                            <i class="bi bi-send me-2"></i>Process <span x-text="financeAction==='refund' ? 'Refund' : 'Credit Note'"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div x-show="selectedReturn?.status !== 'completed'" class="alert alert-info d-flex align-items-center gap-2 py-2 small rounded-3 mb-0">
                        <i class="bi bi-info-circle-fill flex-shrink-0"></i>
                        Complete QC above to unlock financial settlement.
                    </div>

                    <div class="mt-4" x-show="selectedReturn?.refunds && selectedReturn?.refunds.length > 0">
                        <p class="section-label mb-2">Refund History</p>
                        <template x-for="ref in (selectedReturn?.refunds || [])" :key="ref.id">
                            <div class="refund-history-item">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-cash-coin text-success"></i>
                                    <div>
                                        <div class="fw-semibold" x-text="formatCurrency(ref.amount)"></div>
                                        <div class="section-label" x-text="(ref.payment_method||'').replace(/_/g,' ').toUpperCase()"></div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-success-subtle text-success border border-success-subtle small rounded-pill" x-text="ref.status"></span>
                                    <div class="text-muted mt-1" style="font-size:.72rem" x-text="formatDate(ref.created_at)"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</div>{{-- /row --}}
</div>
@endsection

