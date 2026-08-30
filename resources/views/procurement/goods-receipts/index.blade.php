@extends('layouts.app')

@section('title', 'Goods Receipts')
@section('page', 'procurement-goods-receipts')

@section('content')
<div class="goods-receipts-management" x-data="goodsReceiptsTable()" x-cloak>

    <!-- ═══════════════════════ Page Header ════════════════════════════════ -->
    <div class="d-flex justify-content-between align-items-center mb-4 mb-lg-5">
        <div>
            <h1 class="h3 mb-0">Goods Receipts</h1>
            <p class="text-muted mb-0">View and track all received inventory and supplier deliveries</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary" @click.prevent="openReceiveModal()">
                <i class="bi bi-plus-lg me-2"></i>Receive Against PO
            </button>
        </div>
    </div>

    <!-- ═══════════════════════ Stats Widgets ══════════════════════════════ -->
    <div class="row g-4 g-lg-5 mb-5">
        <div class="col-xl-4 col-lg-4 col-md-4">
            <div class="card stats-card" style="cursor: default;">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bi bi-clipboard-check-fill"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Total GRNs</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.total"></span></div>
                            <small class="text-muted">All goods receipts</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-lg-4 col-md-4">
            <div class="card stats-card" style="cursor: default;">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-success bg-opacity-10 text-success me-3">
                            <i class="bi bi-calendar-check-fill"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Received This Month</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.this_month"></span></div>
                            <small class="text-success">
                                <i class="bi bi-arrow-up me-1"></i>Current month
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-lg-4 col-md-4">
            <div class="card stats-card" style="cursor: default;">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-warning bg-opacity-10 text-warning me-3">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Pending Putaway</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.pending"></span></div>
                            <small class="text-warning">Requires action</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════ Main Table Card ═════════════════════════════ -->
    <div class="card mb-5">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="h5 card-title mb-0">Receipts Directory</h2>
                </div>
                <div class="col-auto mt-3 mt-md-0">
                    <div class="d-flex gap-2 flex-wrap justify-content-end">
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
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <!-- Bulk Actions Bar -->
            <div class="bulk-actions-bar p-3 bg-primary bg-opacity-10 border-bottom border-primary border-opacity-25" x-show="selected.length > 0" x-transition style="display: none;">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle-fill text-primary me-2"></i>
                        <span class="fw-medium text-primary">
                            <span x-text="selected.length"></span> receipt<span x-show="selected.length !== 1">s</span> selected
                        </span>
                    </div>
                    <div class="d-flex gap-2">
                        @can('goodsreceipt-delete')
                        <button class="btn btn-sm btn-outline-danger fw-medium shadow-sm bg-body" x-show="trashedFilter !== 'only'" @click="openBulkDeleteModal()">
                            <i class="bi bi-trash me-1"></i>Delete
                        </button>
                        @endcan
                        @can('goodsreceipt-restore')
                        <button class="btn btn-sm btn-outline-warning fw-medium shadow-sm bg-body" x-show="trashedFilter === 'only'" @click="openBulkRestoreModal()">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Restore
                        </button>
                        @endcan
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-nowrap">
                    <thead class="table-group-divider">
                        <tr>
                            <th class="ps-3" style="width:44px;">
                                <input type="checkbox" class="user-select-checkbox" @change="$event.isTrusted && toggleAll($event)" :checked="allSelected">
                            </th>
                            <th style="width:140px;">GRN Number</th>
                            <th>Purchase Order</th>
                            <th>Supplier</th>
                            <th>Warehouse</th>
                            <th style="width:150px;">Created At</th>
                            <th>Date Received</th>
                            <th style="width:90px;" class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Loading State -->
                        <template x-if="isLoading">
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="spinner-border text-primary" role="status"></div>
                                    <p class="text-muted small mt-2 mb-0 fw-medium">Loading goods receipts…</p>
                                </td>
                            </tr>
                        </template>

                        <!-- Empty State -->
                        <template x-if="!isLoading && items.length === 0">
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-file-earmark-x fs-2 d-block mb-2"></i>
                                    No goods receipts found matching your criteria.
                                </td>
                            </tr>
                        </template>

                        <!-- Data Rows -->
                        <template x-for="item in items" :key="item.id">
                            <tr :class="{ 'selected': selected.includes(item.id) }">
                                <td class="ps-3">
                                    <input type="checkbox" class="user-select-checkbox" :value="item.id" x-model="selected">
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <a href="#" @click.prevent="alert('View GRN details feature coming soon')" class="font-monospace fw-bold text-primary text-decoration-none custom-hover-bg rounded px-2 py-1" style="margin-left:-0.5rem;" x-text="item.grn_number"></a>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-info bg-opacity-10 text-info rounded d-flex align-items-center justify-content-center me-2 flex-shrink-0" style="width:28px;height:28px;">
                                            <i class="bi bi-receipt"></i>
                                        </div>
                                        <span class="fw-medium text-body-emphasis" x-text="item.purchase_order ? item.purchase_order.po_number : '—'"></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary bg-opacity-10 text-primary rounded d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width:38px;height:38px;">
                                            <i class="bi bi-truck"></i>
                                        </div>
                                        <div>
                                            <div class="fw-medium" x-text="(item.purchase_order && item.purchase_order.supplier) ? (item.purchase_order.supplier.company_name || item.purchase_order.supplier.firstname) : 'Unknown'"></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small d-flex align-items-center gap-2">
                                        <i class="bi bi-building text-muted"></i>
                                        <span x-text="item.warehouse ? item.warehouse.name : 'Unknown'"></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="small text-muted" x-text="item.created_at ? new Date(item.created_at).toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute:'2-digit', hour12: true }).replace(',', '') : '—'"></span>
                                </td>
                                <td>
                                    <span class="small text-muted" x-text="item.received_date ? new Date(item.received_date).toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute:'2-digit', hour12: true }).replace(',', '') : '—'"></span>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown" data-bs-boundary="window">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            @can('goodsreceipt-view')
                                            <li><a class="dropdown-item" href="#" @click.prevent="viewItem(item)"><i class="bi bi-eye me-2 text-secondary"></i> View Details</a></li>
                                            <li><a class="dropdown-item" :href="'/procurement/goods-receipts/' + item.id + '/pdf'" target="_blank"><i class="bi bi-file-pdf me-2 text-danger"></i> Download PDF</a></li>
                                            @endcan
                                            <template x-if="!item.deleted_at">
                                                <div>
                                                    @can('goodsreceipt-delete')
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item text-danger" href="#" @click.prevent="openDeleteModal(item.id)">
                                                            <i class="bi bi-trash me-2"></i> Delete
                                                        </a>
                                                    </li>
                                                    @endcan
                                                </div>
                                            </template>
                                            
                                            <template x-if="item.deleted_at">
                                                <div>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item text-warning fw-medium" href="#" @click.prevent="openRestoreModal(item.id)">
                                                            <i class="bi bi-arrow-counterclockwise me-2"></i> Restore
                                                        </a>
                                                    </li>
                                                </div>
                                            </template>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center p-3 border-top" x-show="totalPages > 1">
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item" :class="{ 'disabled': currentPage === 1 }">
                            <a class="page-link" href="#" @click.prevent="currentPage--; fetchData()">Previous</a>
                        </li>
                        <li class="page-item disabled">
                            <span class="page-link">Page <span x-text="currentPage"></span> of <span x-text="totalPages"></span></span>
                        </li>
                        <li class="page-item" :class="{ 'disabled': currentPage === totalPages }">
                            <a class="page-link" href="#" @click.prevent="currentPage++; fetchData()">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>

        </div>
    </div>

<!-- ═══════════════════════ Modals ══════════════════════════════════════ -->

<!-- GRN Receive Goods Modal -->
    <div class="modal fade" id="receiveGoodsModal" aria-labelledby="receiveGoodsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <form @submit.prevent="submitReceiveForm" autocomplete="off">
                    <div class="modal-header bg-success-subtle border-bottom d-flex align-items-center justify-content-between p-4">
                        <h5 class="modal-title fw-bold text-success d-flex align-items-center gap-2" id="receiveGoodsModalLabel">
                            <i class="bi bi-box-arrow-in-down fs-4"></i> Receive Goods
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    <div class="modal-body p-4 bg-body-tertiary custom-scrollbar">
                        <div class="row g-4">
                            <div class="col-12 position-relative" style="z-index: 100;">
                                <div class="card mb-0 border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom">
                                            <div class="bg-success text-success bg-opacity-10 rounded-2 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                                <i class="bi bi-info-circle fs-6"></i>
                                            </div>
                                            <h6 class="mb-0 fw-bold text-uppercase text-body" style="font-size: 11px; letter-spacing: 1px;">Receipt Info</h6>
                                        </div>
                                        <div class="row g-3">
                                                                                        <div class="col-md-3">
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Select PO Number *</label>
                                                <select class="form-select form-select-sm fw-semibold" x-model="receiveForm.po_id" @change="selectPO($event.target.value)" style="font-size: 12px;" required>
                                                    <option value="">-- Select Approved PO --</option>
                                                    <template x-for="po in pendingPOs" :key="po.id">
                                                        <option :value="po.id" x-text="po.po_number"></option>
                                                    </template>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Received Date *</label>
                                                <input type="date" class="form-control form-control-sm fw-semibold" x-model="receiveForm.received_date" style="font-size: 12px;" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Internal Notes</label>
                                                <input type="text" class="form-control form-control-sm fw-semibold" x-model="receiveForm.notes" placeholder="Optional notes for GRN" style="font-size: 12px;">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 position-relative" style="z-index: 50;">
                                <div class="card mb-0 border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom">
                                            <div class="bg-success text-success bg-opacity-10 rounded-2 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                                <i class="bi bi-box-seam fs-6"></i>
                                            </div>
                                            <h6 class="mb-0 fw-bold text-uppercase text-body" style="font-size: 11px; letter-spacing: 1px;">Items to Receive</h6>
                                        </div>
                                        
                                        <div class="bg-body rounded border shadow-sm table-responsive custom-scrollbar">
                                            <table class="table table-hover align-middle mb-0 text-nowrap" style="min-width: 900px;">
                                                <thead class="table-group-divider">
                                                    <tr>
                                                        <th class="text-uppercase text-muted fw-bold" style="font-size: 10px;">Product</th>
                                                        <th class="text-uppercase text-muted fw-bold text-end" style="font-size: 10px;">Ordered Qty</th>
                                                        <th class="text-uppercase text-muted fw-bold text-end" style="font-size: 10px;">Already Rcvd</th>
                                                        <th class="text-uppercase text-muted fw-bold text-end" style="font-size: 10px; width: 120px;">Accepted Qty</th>
                                                        <th class="text-uppercase text-muted fw-bold text-end" style="font-size: 10px; width: 120px;">Rejected Qty</th>
                                                        <th class="text-uppercase text-muted fw-bold" style="font-size: 10px; width: 130px;">Batch #</th>
                                                        <th class="text-uppercase text-muted fw-bold" style="font-size: 10px; width: 130px;">Mfg Date</th>
                                                        <th class="text-uppercase text-muted fw-bold" style="font-size: 10px; width: 130px;">Expiry Date</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <template x-for="(item, index) in receiveForm.items" :key="index">
                                                        <tr>
                                                            <td>
                                                                <div class="d-flex align-items-center gap-2">
                                                                    <template x-if="item.product && item.product.image_url">
                                                                        <img :src="item.product.image_url" class="rounded object-fit-cover shadow-sm border border-secondary border-opacity-25" style="width: 28px; height: 28px;" alt="">
                                                                    </template>
                                                                    <template x-if="!item.product || !item.product.image_url">
                                                                        <div class="bg-secondary bg-opacity-10 text-secondary rounded d-flex align-items-center justify-content-center border border-secondary border-opacity-25 shadow-sm" style="width: 28px; height: 28px;">
                                                                            <i class="bi bi-box" style="font-size: 12px;"></i>
                                                                        </div>
                                                                    </template>
                                                                    <div class="d-flex flex-column">
                                                                        <span style="font-size: 12px;" class="fw-medium text-body" x-text="item.product ? item.product.name : 'Unknown'"></span>
                                                                        <span style="font-size: 9px;" class="text-muted text-uppercase" x-text="item.product ? item.product.sku : ''"></span>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td class="text-end fw-semibold text-muted" style="font-size: 12px;" x-text="item.ordered_qty"></td>
                                                            <td class="text-end fw-semibold text-primary" style="font-size: 12px;" x-text="item.previously_received"></td>
                                                            <td class="text-end">
                                                                <input type="number" step="0.01" class="form-control form-control-sm fw-semibold text-end bg-success-subtle border-success text-success" x-model="item.accepted_qty" style="font-size: 12px;" min="0">
                                                            </td>
                                                            <td class="text-end">
                                                                <input type="number" step="0.01" class="form-control form-control-sm fw-semibold text-end bg-danger-subtle border-danger text-danger" x-model="item.rejected_qty" style="font-size: 12px;" min="0">
                                                            </td>
                                                            <td>
                                                                <input type="text" class="form-control form-control-sm" x-model="item.batch_number" placeholder="e.g. B2026-01" style="font-size: 11px; font-family: monospace;">
                                                            </td>
                                                            <td>
                                                                <input type="date" class="form-control form-control-sm" x-model="item.manufacturing_date" style="font-size: 11px;">
                                                            </td>
                                                            <td>
                                                                <input type="date" class="form-control form-control-sm bg-warning-subtle" x-model="item.expiry_date" style="font-size: 11px;">
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

                    </div>
                    <div class="modal-footer bg-body-tertiary">
                        <button type="button" data-bs-dismiss="modal" class="btn text-muted fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 1px;">Cancel</button>
                        <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm" :disabled="isReceiving">
                            <span x-show="isReceiving" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            <span>Process GRN</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    
<!-- View GRN Modal -->
<div class="modal fade" id="viewGrnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-body-tertiary border-bottom-0 pb-3">
                <h5 class="modal-title fw-bold d-flex align-items-center">
                    <div class="bg-primary bg-opacity-10 text-primary rounded p-2 me-3 d-flex align-items-center justify-content-center">
                        <i class="bi bi-receipt"></i>
                    </div>
                    Goods Receipt Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 bg-body-tertiary" x-show="selectedGRN">
                <div class="p-4">
                    <!-- Header Cards -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <h6 class="text-muted mb-2 text-uppercase fw-bold" style="font-size: 0.75rem;">Receipt Information</h6>
                                    <div class="mb-2"><strong>GRN:</strong> <span x-text="selectedGRN?.grn_number"></span></div>
                                    <div class="mb-2"><strong>Created At:</strong> <span x-text="selectedGRN?.created_at ? new Date(selectedGRN.created_at).toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute:'2-digit', hour12: true }).replace(',', '') : ''"></span></div>
                                    <div class="mb-2"><strong>Received:</strong> <span x-text="selectedGRN?.received_date ? new Date(selectedGRN.received_date).toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute:'2-digit', hour12: true }).replace(',', '') : ''"></span></div>
                                    <div><strong>Created By:</strong> <span x-text="selectedGRN?.creator ? (selectedGRN.creator.name || (selectedGRN.creator.first_name + ' ' + (selectedGRN.creator.last_name || ''))) : 'Unknown'"></span></div>
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
                        <div class="card-header bg-body border-bottom-0 pt-4 pb-2">
                            <h6 class="mb-0 fw-bold">Received Items</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-body-secondary text-muted" style="font-size: 0.8rem;">
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
            <div class="modal-footer bg-body-tertiary border-top-0">
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
                <button type="button" class="btn-close btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
            <div class="modal-footer bg-body-tertiary border-top-0 justify-content-center py-3">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
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
                <h5 class="modal-title fw-medium d-flex align-items-center text-body">
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
            <div class="modal-footer bg-body-tertiary border-top-0 justify-content-center py-3">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning px-4" @click="submitRestoreForm" :disabled="isRestoring">
                    <span x-show="isRestoring" class="spinner-border spinner-border-sm me-2"></span>
                    Restore
                </button>
            </div>
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
[x-cloak] { display: none !important; }
</style>

@endsection

@push('scripts')
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
        stats: { total: {{ $stats['total'] ?? 0 }}, this_month: {{ $stats['this_month'] ?? 0 }}, pending: {{ $stats['pending'] ?? 0 }}, discrepancies: {{ $stats['discrepancies'] ?? 0 }} },
        trashedFilter: '',
        
        get allSelected() { return this.items.length > 0 && this.selected.length === this.items.length; },
        
        init() {
            this.fetchData();
            this.$watch('searchQuery', () => { this.currentPage = 1; this.fetchData(); });
        },
        

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
                product: item.product,
                ordered_qty: parseFloat(item.quantity),
                previously_received: parseFloat(item.received_qty) || 0,
                accepted_qty: Math.max(0, parseFloat(item.quantity) - (parseFloat(item.received_qty) || 0)),
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
                if(item.product && item.product.batch_tracking) {
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
                    <button type="button" class="btn-close btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
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

    }));
});

// Fix for Bootstrap dropdowns getting cut off inside table-responsive
document.addEventListener('show.bs.dropdown', function (e) {
    if (e.target.closest('.table-responsive')) {
        e.target.closest('.table-responsive').style.overflow = 'visible';
    }
});
document.addEventListener('hide.bs.dropdown', function (e) {
    if (e.target.closest('.table-responsive')) {
        e.target.closest('.table-responsive').style.overflow = '';
    }
});
</script>
@endpush
