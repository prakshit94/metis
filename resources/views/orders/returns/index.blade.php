@extends('layouts.app')
@section('title', 'Returns Management')
@section('page', 'returns')

@section('content')
<div class="returns-management" x-data="returnsTable()" x-init="init()">

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 fw-bold"><i class="bi bi-arrow-return-left text-primary me-2"></i>Returns &amp; QC</h1>
            <p class="text-muted mb-0 small">Inspect returned items, update stock, and process financials.</p>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stats-icon bg-primary bg-opacity-10 text-primary fs-3 rounded-3 p-2 flex-shrink-0"><i class="bi bi-box-seam"></i></div>
                        <div>
                            <p class="mb-1 small text-muted">Total Returns</p>
                            <div class="h4 mb-0 fw-bold" x-text="stats.total || '—'"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stats-icon bg-warning bg-opacity-10 text-warning fs-3 rounded-3 p-2 flex-shrink-0"><i class="bi bi-hourglass-split"></i></div>
                        <div>
                            <p class="mb-1 small text-muted">Pending QC</p>
                            <div class="h4 mb-0 fw-bold text-warning" x-text="stats.pending_qc || '—'"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stats-icon bg-success bg-opacity-10 text-success fs-3 rounded-3 p-2 flex-shrink-0"><i class="bi bi-check2-circle"></i></div>
                        <div>
                            <p class="mb-1 small text-muted">Completed</p>
                            <div class="h4 mb-0 fw-bold text-success" x-text="stats.completed || '—'"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stats-icon bg-danger bg-opacity-10 text-danger fs-3 rounded-3 p-2 flex-shrink-0"><i class="bi bi-x-circle"></i></div>
                        <div>
                            <p class="mb-1 small text-muted">Rejected</p>
                            <div class="h4 mb-0 fw-bold text-danger" x-text="stats.rejected || '—'"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Data Table Card --}}
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center g-2">
                <div class="col"><h2 class="h5 card-title mb-0">Returns Overview</h2></div>
                <div class="col-auto">
                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                        <div class="position-relative">
                            <input type="search" class="form-control form-control-sm pe-4" placeholder="Search RMA / Order…" x-model="searchQuery" @input.debounce.400ms="filterReturns()" style="width:220px;">
                            <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted small"></i>
                        </div>
                        <select class="form-select form-select-sm" x-model="statusFilter" @change="filterReturns()" style="width:160px;">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="received">Received</option>
                            <option value="qc_in_progress">QC In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="rejected">Rejected</option>
                        </select>
                        <select class="form-select form-select-sm" x-model="serviceFilter" @change="filterReturns()" style="width:160px;" x-show="shippingServices.length > 0">
                            <option value="">All Carriers</option>
                            <template x-for="svc in shippingServices" :key="svc">
                                <option :value="svc" x-text="svc"></option>
                            </template>
                        </select>
                        <button class="btn btn-sm btn-outline-secondary" @click="clearFilters()" title="Clear filters"><i class="bi bi-x-circle"></i></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-0">

            {{-- Bulk Actions Bar --}}
            <div class="px-3 py-2 border-bottom bg-primary bg-opacity-10" x-show="selectedReturns.length > 0" x-transition>
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span class="fw-medium text-primary small">
                        <i class="bi bi-check-circle-fill me-1"></i>
                        <strong x-text="selectedReturns.length"></strong> return(s) selected
                    </span>
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-sm btn-primary" @click="openBulkQcModal()" :disabled="isSubmitting">
                            <i class="bi bi-clipboard2-check me-1"></i>Process Bulk QC
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" @click="selectedReturns = []"><i class="bi bi-x-lg"></i></button>
                    </div>
                </div>
            </div>

            {{-- Loading --}}
            <div class="text-center py-5" x-show="isLoading">
                <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading…</span></div>
            </div>

            {{-- Table --}}
            <div class="table-responsive" x-show="!isLoading">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px;">
                                <input type="checkbox" class="form-check-input border-secondary" :checked="allSelected" @change="toggleAll($event.target.checked)" style="cursor:pointer;">
                            </th>
                            <th role="button" @click="sortBy('return_no')" class="user-select-none">
                                <i class="bi bi-arrow-return-left me-1 text-secondary"></i>RMA # <i class="bi ms-1" :class="sortField==='return_no'?(sortDirection==='asc'?'bi-sort-up':'bi-sort-down'):'bi-sort'"></i>
                            </th>
                            <th><i class="bi bi-hash me-1 text-secondary"></i>Order #</th>
                            <th><i class="bi bi-person me-1 text-secondary"></i>Customer</th>
                            <th><i class="bi bi-truck me-1 text-secondary"></i>Carrier</th>
                            <th><i class="bi bi-chat-left-text me-1 text-secondary"></i>Reason</th>
                            <th><i class="bi bi-box-seam me-1 text-secondary"></i>Items</th>
                            <th><i class="bi bi-shield-check me-1 text-secondary"></i>QC Status</th>
                            <th role="button" @click="sortBy('created_at')" class="user-select-none">
                                <i class="bi bi-calendar-event me-1 text-secondary"></i>Date <i class="bi ms-1" :class="sortField==='created_at'?(sortDirection==='asc'?'bi-sort-up':'bi-sort-down'):'bi-sort'"></i>
                            </th>
                            <th style="width:100px;"><i class="bi bi-lightning-charge me-1 text-secondary"></i>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="ret in returns" :key="ret.id">
                            <tr :class="{ 'table-active': selectedReturns.includes(String(ret.id)) }">
                                <td>
                                    <input type="checkbox" class="form-check-input border-secondary" :value="String(ret.id)" x-model="selectedReturns" style="cursor:pointer;">
                                </td>
                                <td><span class="fw-semibold font-monospace small" x-text="ret.return_no"></span></td>
                                <td><span class="text-muted font-monospace small" x-text="ret.order_no"></span></td>
                                <td><span class="small" x-text="ret.customer?.name"></span></td>
                                <td>
                                    <span class="badge bg-body-tertiary text-body-emphasis border small" x-text="ret.order?.shipments?.length ? ret.order.shipments[ret.order.shipments.length - 1].carrier_name || '—' : '—'"></span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary bg-opacity-25 text-body small text-capitalize"
                                          x-text="(ret.reason||'N/A').replace(/_/g,' ')"></span>
                                </td>
                                <td>
                                    <span class="badge bg-primary bg-opacity-10 text-primary">
                                        <span x-text="ret.items?.length||0"></span> item(s)
                                    </span>
                                </td>
                                <td>
                                    <span class="badge small text-white px-2 py-1"
                                          :style="`background-color: ${getStatusColor(ret.status)};`"
                                          x-text="getStatusLabel(ret.status)"></span>
                                </td>
                                <td><span class="small text-muted" x-text="formatDate(ret.created_at)"></span></td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li>
                                                <a class="dropdown-item" href="#" @click.prevent="viewReturnDetails(ret)">
                                                    <i class="bi bi-clipboard2-check me-2 text-primary"></i>Inspect QC
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <template x-if="!isLoading && returns.length === 0">
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                                    No returns found.
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top" x-show="totalPages > 1">
                <div class="text-muted small">
                    Page <span x-text="currentPage"></span> of <span x-text="totalPages"></span>
                    &nbsp;·&nbsp; <span x-text="totalReturns"></span> total
                </div>
                <nav>
                    <ul class="pagination pagination-sm mb-0 gap-1">
                        <li class="page-item" :class="{ disabled: currentPage === 1 }">
                            <a class="page-link rounded" href="#" @click.prevent="goToPage(currentPage - 1)">‹</a>
                        </li>
                        <template x-for="(page, idx) in visiblePages" :key="idx">
                            <li class="page-item" :class="{ active: page === currentPage, disabled: page === '...' }">
                                <a class="page-link rounded" href="#" @click.prevent="page !== '...' && goToPage(page)" x-text="page"></a>
                            </li>
                        </template>
                        <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                            <a class="page-link rounded" href="#" @click.prevent="goToPage(currentPage + 1)">›</a>
                        </li>
                    </ul>
                </nav>
            </div>

        </div>{{-- /card-body --}}
    </div>{{-- /card --}}

    {{-- ═══════════════════ QC Inspect Modal (inside x-data scope) ═══════════════════ --}}
    <div class="modal fade" id="qcInspectModal" data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="qcInspectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content shadow-lg border-0 rounded-4">
                <div class="d-flex flex-column h-100 bg-body rounded-4 overflow-hidden">

                    <div class="modal-header border-bottom-0 pb-4 pt-4 px-4 px-lg-5 bg-body-tertiary">
                        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between w-100 gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-body-secondary text-primary p-3 rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width:56px;height:56px;">
                                    <i class="bi bi-clipboard2-check fs-3"></i>
                                </div>
                                <div>
                                    <h5 class="modal-title fw-bold mb-1" id="qcInspectModalLabel">
                                        QC Inspection — <span class="text-primary font-monospace" x-text="selectedReturn?.return_no || ''"></span>
                                    </h5>
                                    <p class="text-muted small mb-0">
                                        Order <span class="font-monospace" x-text="selectedReturn?.order_no || ''"></span>
                                        &nbsp;·&nbsp; <span x-text="selectedReturn?.customer?.name || ''"></span>
                                        &nbsp;·&nbsp; <span x-text="selectedReturn ? formatDate(selectedReturn.created_at) : ''"></span>
                                    </p>
                                </div>
                            </div>
                            <span class="badge fs-6 ms-auto text-white px-3 py-2"
                                  :style="`background-color: ${getStatusColor(selectedReturn?.status || 'pending')};`"
                                  x-text="getStatusLabel(selectedReturn?.status || 'pending')"></span>
                        </div>
                        <button type="button" class="btn-close ms-3" @click="closeQcModal()"></button>
                    </div>

                    {{-- Modal Body --}}
                    <div class="modal-body px-4 px-lg-5 py-4">

                        {{-- Return Info Row --}}
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <div class="p-3 rounded-3 bg-body-secondary h-100">
                                    <p class="small text-muted fw-semibold text-uppercase mb-1">Reason</p>
                                    <p class="mb-0 fw-medium text-capitalize" x-text="(selectedReturn?.reason||'N/A').replace(/_/g,' ')"></p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3 rounded-3 bg-body-secondary h-100">
                                    <p class="small text-muted fw-semibold text-uppercase mb-1">Total Items</p>
                                    <p class="mb-0 fw-bold fs-5" x-text="qcItems.length || selectedReturn?.items?.length || 0"></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 rounded-3 bg-body-secondary h-100">
                                    <p class="small text-muted fw-semibold text-uppercase mb-1">Return Notes</p>
                                    <p class="mb-0 small" x-text="selectedReturn?.notes || 'None'"></p>
                                </div>
                            </div>
                        </div>

                        {{-- QC Items Table (only if pending) --}}
                        <template x-if="selectedReturn && selectedReturn.status === 'pending'">
                            <div>
                                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                                    <h6 class="fw-bold mb-0">
                                        <i class="bi bi-list-check me-2 text-primary"></i>Per-Item QC Quantities
                                    </h6>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-success" @click="markAllGood()">
                                            <i class="bi bi-check2-all me-1"></i>All Good
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" @click="markAllDamaged()">
                                            <i class="bi bi-exclamation-triangle me-1"></i>All Damaged
                                        </button>
                                    </div>
                                </div>

                                <div class="alert alert-info border-0 py-2 small mb-3" role="alert">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Set <strong>Received Qty</strong> (physically arrived), <strong>Restock Qty</strong> (goes back to inventory), and <strong>Damaged Qty</strong>. Restock + Damaged must not exceed Received.
                                </div>

                                <div class="table-responsive rounded-3 border">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="ps-3" style="width:48px;"></th>
                                                <th class="ps-2">Product</th>
                                                <th class="text-center" style="width:90px;">Requested</th>
                                                <th class="text-center" style="width:120px;">
                                                    <span style="color:#0ea5e9;font-weight:600;">Received</span>
                                                </th>
                                                <th class="text-center" style="width:120px;">
                                                    <span style="color:#10b981;font-weight:600;">Restock</span>
                                                </th>
                                                <th class="text-center" style="width:120px;">
                                                    <span style="color:#ef4444;font-weight:600;">Damaged</span>
                                                </th>
                                                <th>Notes</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="(item, idx) in qcItems" :key="item.id">
                                                <tr :class="{ 'table-danger': !qcItemValid(item) }">
                                                    <td class="ps-3" style="width:48px;">
                                                        <div class="rounded border bg-body d-flex align-items-center justify-content-center overflow-hidden flex-shrink-0" style="width:40px;height:40px;">
                                                            <template x-if="item.image_url">
                                                                <img :src="item.image_url" class="w-100 h-100" style="object-fit:cover;" x-on:error="$el.src='/assets/images/product-placeholder.svg'">
                                                            </template>
                                                            <template x-if="!item.image_url">
                                                                <i class="bi bi-box-seam text-muted"></i>
                                                            </template>
                                                        </div>
                                                    </td>
                                                    <td class="ps-2">
                                                        <div class="fw-semibold small" x-text="item.product?.name || 'Unknown'"></div>
                                                        <div class="text-muted x-small font-monospace" x-text="item.product?.sku || ''"></div>
                                                        {{-- Row error hint --}}
                                                        <div x-show="!qcItemValid(item)" class="text-danger mt-1" style="font-size:0.75rem;">
                                                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                                            <span x-show="parseFloat(item.received_qty) > parseFloat(item.requested_qty)">Received cannot exceed requested qty (<span x-text="item.requested_qty"></span>).</span>
                                                            <span x-show="parseFloat(item.received_qty) <= parseFloat(item.requested_qty) && (parseFloat(item.restocked_qty)+parseFloat(item.damaged_qty)) > parseFloat(item.received_qty)">Restock + Damaged exceeds received qty.</span>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge bg-secondary bg-opacity-25 text-body" x-text="item.requested_qty"></span>
                                                    </td>
                                                    <td class="text-center">
                                                        <input type="number"
                                                               class="form-control form-control-sm text-center"
                                                               :class="parseFloat(item.received_qty) > parseFloat(item.requested_qty) ? 'border-danger' : 'border-info'"
                                                               min="0" :max="item.requested_qty" step="1"
                                                               x-model.number="item.received_qty"
                                                               @change="onReceivedChange(item)"
                                                               style="width:90px; margin:auto;">
                                                        <div class="x-small text-muted mt-1">max: <span x-text="item.requested_qty"></span></div>
                                                    </td>
                                                    <td class="text-center">
                                                        <input type="number"
                                                               class="form-control form-control-sm text-center border-success"
                                                               min="0" :max="item.received_qty" step="1"
                                                               x-model.number="item.restocked_qty"
                                                               @change="onRestockedChange(item)"
                                                               style="width:90px; margin:auto;">
                                                    </td>
                                                    <td class="text-center">
                                                        <input type="number"
                                                               class="form-control form-control-sm text-center border-danger"
                                                               min="0" :max="item.received_qty" step="1"
                                                               x-model.number="item.damaged_qty"
                                                               @change="onDamagedChange(item)"
                                                               style="width:90px; margin:auto;">
                                                    </td>
                                                    <td>
                                                        <input type="text"
                                                               class="form-control form-control-sm"
                                                               placeholder="Optional note…"
                                                               x-model="item.qc_notes">
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>

                                {{-- Validation summary --}}
                                <template x-if="!qcFormValid && qcItems.length > 0">
                                    <div class="alert alert-warning border-0 py-2 small mt-3" role="alert">
                                        <i class="bi bi-exclamation-triangle me-1"></i>
                                        One or more rows have invalid quantities. Restock + Damaged must not exceed Received, and all values must be ≥ 0.
                                    </div>
                                </template>
                            </div>
                        </template>

                        {{-- Already processed view --}}
                        <template x-if="selectedReturn && selectedReturn.status !== 'pending'">
                            <div>
                                <h6 class="fw-bold mb-3"><i class="bi bi-list-check me-2 text-muted"></i>QC Results</h6>
                                <div class="table-responsive rounded-3 border">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="ps-3" style="width:48px;"></th>
                                                <th class="ps-2">Product</th>
                                                <th class="text-center">Requested</th>
                                                <th class="text-center">Received</th>
                                                <th class="text-center">Restocked</th>
                                                <th class="text-center">Damaged</th>
                                                <th>QC Notes</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="item in (selectedReturn?.items || [])" :key="item.id">
                                                <tr>
                                                    <td class="ps-3">
                                                        <div class="rounded border bg-body d-flex align-items-center justify-content-center overflow-hidden flex-shrink-0" style="width:40px;height:40px;">
                                                            <template x-if="item.image_url">
                                                                <img :src="item.image_url" class="w-100 h-100" style="object-fit:cover;" x-on:error="$el.src='/assets/images/product-placeholder.svg'">
                                                            </template>
                                                            <template x-if="!item.image_url">
                                                                <i class="bi bi-box-seam text-muted"></i>
                                                            </template>
                                                        </div>
                                                    </td>
                                                    <td class="ps-2">
                                                        <div class="fw-semibold small" x-text="item.product?.name || 'Unknown'"></div>
                                                        <div class="text-muted x-small font-monospace" x-text="item.product?.sku || ''"></div>
                                                    </td>
                                                    <td class="text-center"><span class="badge bg-secondary bg-opacity-25 text-body" x-text="item.quantity"></span></td>
                                                    <td class="text-center"><span class="badge bg-info bg-opacity-25 text-info" x-text="item.received_qty"></span></td>
                                                    <td class="text-center"><span class="badge bg-success bg-opacity-25 text-success" x-text="item.restocked_qty"></span></td>
                                                    <td class="text-center"><span class="badge bg-danger bg-opacity-25 text-danger" x-text="item.damaged_qty"></span></td>
                                                    <td class="text-muted small" x-text="item.qc_notes || '—'"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </template>

                    </div>{{-- /modal-body --}}

                    {{-- Modal Footer --}}
                    <div class="modal-footer bg-body-secondary border-top px-4 py-3 rounded-bottom-4">
                        <button type="button" class="btn btn-outline-secondary" @click="closeQcModal()">
                            <i class="bi bi-x-lg me-1"></i>Close
                        </button>
                        <template x-if="selectedReturn && selectedReturn.status === 'pending'">
                            <button type="button"
                                    class="btn btn-primary"
                                    @click="processQc()"
                                    :disabled="!qcFormValid || isSubmitting">
                                <span x-show="isSubmitting" class="spinner-border spinner-border-sm me-1" role="status"></span>
                                <i class="bi bi-send-check me-1" x-show="!isSubmitting"></i>
                                Submit QC &amp; Update Inventory
                            </button>
                        </template>
                    </div>

                </div>{{-- /flex-column --}}
            </div>{{-- /modal-content --}}
        </div>{{-- /modal-dialog --}}
    </div>{{-- /qcInspectModal --}}

    {{-- bulkQcModal --}}
    <div class="modal fade" id="bulkQcModal" data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="bulkQcModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content shadow-lg border-0 rounded-4">
                <div class="d-flex flex-column h-100 bg-body rounded-4 overflow-hidden">

                    <div class="modal-header border-bottom-0 pb-4 pt-4 px-4 px-lg-5 bg-body-tertiary">
                        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between w-100 gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-body-secondary text-primary p-3 rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width:56px;height:56px;">
                                    <i class="bi bi-layers-half fs-3"></i>
                                </div>
                                <div>
                                    <h5 class="modal-title fw-bold mb-1" id="bulkQcModalLabel">
                                        Bulk QC Inspection
                                    </h5>
                                    <p class="text-muted small mb-0">
                                        Processing <strong x-text="selectedReturnsForBulk?.length || 0"></strong> selected return(s)
                                    </p>
                                </div>
                            </div>
                            <span class="badge fs-6 ms-auto text-white px-3 py-2"
                                  :style="`background-color: ${getStatusColor('pending')};`"
                                  x-text="getStatusLabel('pending')"></span>
                        </div>
                        <button type="button" class="btn-close ms-3" @click="closeBulkQcModal()"></button>
                    </div>

                    {{-- Modal Body --}}
                    <div class="modal-body px-4 px-lg-5 py-4">

                        {{-- Summary alert --}}
                        <div class="alert alert-info border-0 py-2 small mb-4" role="alert">
                            <i class="bi bi-info-circle me-1"></i>
                            This process aggregates all return items across the selected returns by product. Adjust quantities below; they will be allocated to individual RMAs automatically.
                        </div>

                        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                            <h6 class="fw-bold mb-0">
                                <i class="bi bi-list-check me-2 text-primary"></i>Aggregated Products QC
                            </h6>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-success" @click="markBulkAllGood()">
                                    <i class="bi bi-check2-all me-1"></i>All Good
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" @click="markBulkAllDamaged()">
                                    <i class="bi bi-exclamation-triangle me-1"></i>All Damaged
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive rounded-3 border">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3" style="width:48px;"></th>
                                        <th class="ps-2">Product</th>
                                        <th class="text-center" style="width:90px;">Requested</th>
                                        <th class="text-center" style="width:120px;">
                                            <span style="color:#0ea5e9;font-weight:600;">Received</span>
                                        </th>
                                        <th class="text-center" style="width:120px;">
                                            <span style="color:#10b981;font-weight:600;">Restock</span>
                                        </th>
                                        <th class="text-center" style="width:120px;">
                                            <span style="color:#ef4444;font-weight:600;">Damaged</span>
                                        </th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="item in bulkQcItems" :key="item.product_id">
                                        <tr :class="{ 'table-danger': !bulkQcItemValid(item) }">
                                            <td class="ps-3" style="width:48px;">
                                                <div class="rounded border bg-body d-flex align-items-center justify-content-center overflow-hidden flex-shrink-0" style="width:40px;height:40px;">
                                                    <template x-if="item.image_url">
                                                        <img :src="item.image_url" class="w-100 h-100" style="object-fit:cover;" x-on:error="$el.src='/assets/images/product-placeholder.svg'">
                                                    </template>
                                                    <template x-if="!item.image_url">
                                                        <i class="bi bi-box-seam text-muted"></i>
                                                    </template>
                                                </div>
                                            </td>
                                            <td class="ps-2">
                                                <div class="fw-semibold small" x-text="item.product?.name || 'Unknown'"></div>
                                                <div class="text-muted x-small font-monospace" x-text="item.product?.sku || ''"></div>
                                                {{-- Row error hint --}}
                                                <div x-show="!bulkQcItemValid(item)" class="text-danger mt-1" style="font-size:0.75rem;">
                                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                                    <span x-show="parseFloat(item.received_qty) > parseFloat(item.requested_qty)">Received cannot exceed requested qty (<span x-text="item.requested_qty"></span>).</span>
                                                    <span x-show="parseFloat(item.received_qty) <= parseFloat(item.requested_qty) && (parseFloat(item.restocked_qty)+parseFloat(item.damaged_qty)) > parseFloat(item.received_qty)">Restock + Damaged exceeds received qty.</span>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-secondary bg-opacity-25 text-body" x-text="item.requested_qty"></span>
                                            </td>
                                            <td class="text-center">
                                                <input type="number"
                                                       class="form-control form-control-sm text-center"
                                                       :class="parseFloat(item.received_qty) > parseFloat(item.requested_qty) ? 'border-danger' : 'border-info'"
                                                       min="0" :max="item.requested_qty" step="1"
                                                       x-model.number="item.received_qty"
                                                       @change="onBulkReceivedChange(item)"
                                                       style="width:90px; margin:auto;">
                                                <div class="x-small text-muted mt-1">max: <span x-text="item.requested_qty"></span></div>
                                            </td>
                                            <td class="text-center">
                                                <input type="number"
                                                       class="form-control form-control-sm text-center border-success"
                                                       min="0" :max="item.received_qty" step="1"
                                                       x-model.number="item.restocked_qty"
                                                       @change="onBulkRestockedChange(item)"
                                                       style="width:90px; margin:auto;">
                                            </td>
                                            <td class="text-center">
                                                <input type="number"
                                                       class="form-control form-control-sm text-center border-danger"
                                                       min="0" :max="item.received_qty" step="1"
                                                       x-model.number="item.damaged_qty"
                                                       @change="onBulkDamagedChange(item)"
                                                       style="width:90px; margin:auto;">
                                            </td>
                                            <td>
                                                <input type="text"
                                                       class="form-control form-control-sm"
                                                       placeholder="Optional note…"
                                                       x-model="item.qc_notes">
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        {{-- Validation summary --}}
                        <template x-if="!bulkQcFormValid && (bulkQcItems || []).length > 0">
                            <div class="alert alert-warning border-0 py-2 small mt-3" role="alert">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                One or more rows have invalid quantities. Restock + Damaged must not exceed Received, and all values must be ≥ 0.
                            </div>
                        </template>

                    </div>

                    {{-- Modal Footer --}}
                    <div class="modal-footer bg-body-secondary border-top px-4 py-3 rounded-bottom-4">
                        <button type="button" class="btn btn-outline-secondary" @click="closeBulkQcModal()">
                            <i class="bi bi-x-lg me-1"></i>Close
                        </button>
                        <button type="button"
                                class="btn btn-primary"
                                @click="submitBulkQc()"
                                :disabled="!bulkQcFormValid || isSubmitting">
                            <span x-show="isSubmitting" class="spinner-border spinner-border-sm me-1" role="status"></span>
                            <i class="bi bi-send-check me-1" x-show="!isSubmitting"></i>
                            Submit Bulk QC &amp; Update Inventory
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>{{-- /bulkQcModal --}}

</div>{{-- /returns-management --}}
@endsection
