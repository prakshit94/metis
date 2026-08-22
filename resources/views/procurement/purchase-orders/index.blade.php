@extends('layouts.app')

@section('title', 'Purchase Orders')
@section('page', 'procurement-purchase-orders')

@section('content')
<div class="purchase-orders-management" x-data="purchaseOrdersTable()" x-cloak>

    <!-- ═══════════════════════ Page Header ════════════════════════════════ -->
    <div class="d-flex justify-content-between align-items-center mb-4 mb-lg-5">
        <div>
            <h1 class="h3 mb-0">Purchase Orders</h1>
            <p class="text-muted mb-0">Manage supplier purchase orders and GRNs</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary" @click="exportData()">
                <i class="bi bi-download me-2"></i>Export
            </button>
            @can('purchaseorder-create')
            <button type="button" class="btn btn-primary" @click.prevent="openCreateModal()">
                <i class="bi bi-plus-lg me-2"></i>Create PO
            </button>
            @endcan
        </div>
    </div>

    <!-- ═══════════════════════ Stats Widgets ══════════════════════════════ -->
    <div class="row g-4 g-lg-5 mb-5">
        <div class="col-xl-4 col-lg-4 col-md-4">
            <div class="card stats-card" style="cursor: default;">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bi bi-file-earmark-text-fill"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Total Orders</p>
                            <div class="h3 mb-0" aria-live="polite"><span>{{ $stats['total'] }}</span></div>
                            <small class="text-muted">All purchase orders</small>
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
                            <p class="h6 mb-0 text-muted">Pending Delivery</p>
                            <div class="h3 mb-0" aria-live="polite"><span>{{ $stats['pending'] }}</span></div>
                            <small class="text-warning">
                                <i class="bi bi-arrow-right me-1"></i>Awaiting receipt
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
                        <div class="stats-icon bg-success bg-opacity-10 text-success me-3">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Received</p>
                            <div class="h3 mb-0" aria-live="polite"><span>{{ $stats['completed'] }}</span></div>
                            <small class="text-success">Fully fulfilled</small>
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
                    <h2 class="h5 card-title mb-0">Purchase Orders Directory</h2>
                </div>
                <div class="col-auto mt-3 mt-md-0">
                    <div class="d-flex gap-2 flex-wrap justify-content-end">
                        <div class="position-relative">
                            <input type="search"
                                   class="form-control form-control-sm"
                                   placeholder="Search PO Number or Supplier..."
                                   x-model.debounce.300ms="searchQuery"
                                   style="width:250px;">
                            <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted"></i>
                        </div>
                        <select class="form-select form-select-sm" x-model="trashedFilter" style="width:120px;">
                            <option value="">Active</option>
                            <option value="only">Deleted</option>
                        </select>
                        <select class="form-select form-select-sm" x-model="statusFilter" style="width:160px;">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending Approval</option>
                            <option value="approved">Approved (Sent)</option>
                            <option value="partially_received">Partially Received</option>
                            <option value="received">Completed</option>
                            <option value="rejected">Rejected / Cancelled</option>
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
                            <span x-text="selected.length"></span> purchase order<span x-show="selected.length !== 1">s</span> selected
                        </span>
                    </div>
                    <div class="d-flex gap-2">
                        <template x-if="selected.length > 0 && items.filter(i => selected.includes(i.id) && !i.deleted_at).length > 0">
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-primary fw-medium shadow-sm bg-white" @click="downloadBulkPdf()">
                                    <i class="bi bi-file-pdf me-1"></i>Download PDFs
                                </button>
                                <template x-if="items.filter(i => selected.includes(i.id) && !i.deleted_at && i.status === 'pending').length > 0 && items.filter(i => selected.includes(i.id) && !i.deleted_at && i.status !== 'pending').length === 0">
                                    <div class="d-flex gap-2">
                                        @can('purchaseorder-approve')
                                        <button class="btn btn-sm btn-success fw-medium shadow-sm" @click="openBulkApproveModal()">
                                            <i class="bi bi-check-circle me-1"></i>Approve Selected
                                        </button>
                                        <button class="btn btn-sm btn-danger fw-medium shadow-sm" @click="openBulkRejectModal()">
                                            <i class="bi bi-x-circle me-1"></i>Reject Selected
                                        </button>
                                        @endcan
                                    </div>
                                </template>
                                @can('purchaseorder-delete')
                                <button class="btn btn-sm btn-outline-danger fw-medium shadow-sm bg-white" @click="openBulkDeleteModal()">
                                    <i class="bi bi-trash me-1"></i>Delete Selected
                                </button>
                                @endcan
                            </div>
                        </template>

                        <template x-if="selected.length > 0 && items.filter(i => selected.includes(i.id) && i.deleted_at).length > 0">
                            <button class="btn btn-sm btn-warning fw-medium shadow-sm" @click="openBulkRestoreModal()">
                                <i class="bi bi-arrow-counterclockwise me-1"></i>Restore Selected
                            </button>
                        </template>
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
                            <th style="width:120px;">PO Number</th>
                            <th>Supplier</th>
                            <th>Warehouse</th>
                            <th style="width:150px;">Created At</th>
                            <th>Expected Date</th>
                            <th>Amount</th>
                            <th style="width:140px;">Status</th>
                            <th>Attachment</th>
                            <th style="width:90px;" class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Loading State -->
                        <template x-if="isLoading">
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <div class="spinner-border text-primary" role="status"></div>
                                    <p class="text-muted small mt-2 mb-0 fw-medium">Loading purchase orders…</p>
                                </td>
                            </tr>
                        </template>

                        <!-- Empty State -->
                        <template x-if="!isLoading && items.length === 0">
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="bi bi-file-earmark-x fs-2 d-block mb-2"></i>
                                    No purchase orders found matching your criteria.
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
                                        <a href="#" @click.prevent="viewItem(item)" class="font-monospace fw-bold text-primary text-decoration-none custom-hover-bg rounded px-2 py-1" style="margin-left:-0.5rem;" x-text="item.po_number"></a>
                                        <template x-if="item.deleted_at">
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle"><i class="bi bi-trash"></i> Trashed</span>
                                        </template>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary bg-opacity-10 text-primary rounded d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width:38px;height:38px;">
                                            <i class="bi bi-truck"></i>
                                        </div>
                                        <div>
                                            <div class="fw-medium" x-text="item.supplier ? (item.supplier.company_name || item.supplier.firstname) : 'Unknown'"></div>
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
                                    <span class="small text-muted" x-text="item.expected_delivery_date ? new Date(item.expected_delivery_date).toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute:'2-digit', hour12: true }).replace(',', '') : '—'"></span>
                                </td>
                                <td>
                                    <span class="fw-medium">₹<span x-text="item.net_amount || item.total_amount || '0.00'"></span></span>
                                </td>
                                <td>
                                    <span class="badge"
                                          :class="{
                                            'bg-warning-subtle text-warning': item.status === 'pending',
                                            'bg-primary-subtle text-primary': item.status === 'approved',
                                            'bg-info-subtle text-info': item.status === 'partially_received',
                                            'bg-success-subtle text-success': item.status === 'received',
                                            'bg-danger-subtle text-danger': item.status === 'rejected'
                                          }">
                                          <span x-text="(item.status || 'pending').toUpperCase().replace('_', ' ')"></span>
                                    </span>
                                </td>
                                <td>
                                    <template x-if="item.invoice_path">
                                        <a :href="item.invoice_url" target="_blank" class="btn btn-sm btn-light border d-inline-flex align-items-center" title="View Attached Invoice">
                                            <i class="bi bi-file-earmark-pdf text-danger me-2"></i> <span class="small fw-medium">View</span>
                                        </a>
                                    </template>
                                    <template x-if="!item.invoice_path">
                                        <span class="text-muted small">—</span>
                                    </template>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown" data-bs-boundary="window">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            @can('purchaseorder-view')
                                            <li><a class="dropdown-item" href="#" @click.prevent="viewItem(item)"><i class="bi bi-eye me-2 text-secondary"></i> View Details</a></li>
                                            <li><a class="dropdown-item" :href="'/procurement/purchase-orders/' + item.id + '/pdf'" target="_blank"><i class="bi bi-file-pdf me-2 text-danger"></i> Download PDF</a></li>
                                            @endcan
                                            <template x-if="!item.deleted_at">
                                                <div>
                                                    @can('purchaseorder-approve')
                                                    <template x-if="item.status === 'pending'">
                                                        <li>
                                                            <a class="dropdown-item text-success fw-medium" href="#" @click.prevent="openApproveModal(item.id)">
                                                                <i class="bi bi-check-circle me-2"></i> Approve
                                                            </a>
                                                        </li>
                                                    </template>
                                                    <template x-if="item.status === 'pending'">
                                                        <li>
                                                            <a class="dropdown-item text-danger fw-medium" href="#" @click.prevent="openRejectModal(item)">
                                                                <i class="bi bi-x-circle me-2"></i> Reject
                                                            </a>
                                                        </li>
                                                    </template>
                                                    @endcan
                                                    @can('purchaseorder-create')
                                                    <li><a class="dropdown-item text-secondary fw-medium" href="#" @click.prevent="openInvoiceModal(item)"><i class="bi bi-file-earmark-arrow-up me-2"></i> Upload Invoice</a></li>
                                                    @endcan
                                                    @can('goodsreceipt-create')
                                                    <template x-if="item.status === 'approved' || item.status === 'partially_received'">
                                                        <li><hr class="dropdown-divider"></li>
                                                    </template>
                                                    <template x-if="item.status === 'approved' || item.status === 'partially_received'">
                                                        <li><a class="dropdown-item text-primary fw-medium" href="#" @click.prevent="openReceiveModal(item)"><i class="bi bi-box-arrow-in-down me-2"></i> Receive Goods</a></li>
                                                    </template>
                                                    @endcan
                                                    @can('purchaseorder-delete')
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

    <!-- Create PO Modal -->
    <div class="modal fade" id="createPoModal" aria-labelledby="createPoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0 bg-body-tertiary">
                    <h5 class="modal-title fw-bold" id="createPoModalLabel">Create Purchase Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3 bg-body-tertiary">
                    <form @submit.prevent="submitCreateForm" autocomplete="off">
                        <p class="small text-muted mb-3">Draft a new purchase order for suppliers</p>

                        <div class="row g-3">
                            
                            {{-- Basic Details --}}
                            <div class="col-12 position-relative" style="z-index: 100;">
                                <div class="card mb-0 border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center gap-2 pb-2 mb-2 border-bottom">
                                            <div class="bg-primary bg-opacity-10 text-primary rounded-2 d-flex align-items-center justify-content-center" style="width: 20px; height: 20px;">
                                                <i class="bi bi-info-circle" style="font-size: 10px;"></i>
                                            </div>
                                            <h6 class="mb-0 fw-bold text-uppercase text-body" style="font-size: 11px; letter-spacing: 1px;">Order Information</h6>
                                        </div>
                                        <div class="row g-2">
                                            <div class="col-md-6 position-relative" :style="showSupplierDropdown ? 'z-index: 1055;' : ''" @click.away="showSupplierDropdown = false">
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Supplier *</label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-body text-muted border-end-0"><i class="bi bi-truck"></i></span>
                                                    <div class="form-control border-start-0 ps-0 fw-semibold d-flex align-items-center bg-body cursor-pointer" @click="showSupplierDropdown = !showSupplierDropdown; if(showSupplierDropdown) setTimeout(() => $refs.supplierSearch.focus(), 50)" style="font-size: 12px; min-height: 31px;">
                                                        <span class="flex-grow-1 text-truncate" x-text="selectedSupplierName || 'Select Supplier'"></span>
                                                        <i class="bi bi-chevron-down text-muted" style="font-size: 10px;"></i>
                                                    </div>
                                                </div>
                                                <div x-show="showSupplierDropdown" x-transition class="position-absolute w-100 bg-body border rounded shadow-lg mt-1 custom-scrollbar" style="max-height: 200px; overflow-y: auto; z-index: 1050; left: 0;">
                                                    <div class="p-2 border-bottom position-sticky top-0 bg-body d-flex gap-2 align-items-center" style="z-index: 1051;">
                                                        <input x-ref="supplierSearch" type="text" class="form-control form-control-sm" x-model="supplierSearch" placeholder="Search suppliers..." style="font-size: 12px;">
                                                    </div>
                                                    <template x-for="supplier in filteredSuppliers" :key="supplier.id">
                                                        <div class="px-3 py-2 cursor-pointer custom-hover-bg d-flex align-items-center border-bottom" @click.stop="createForm.supplier_ids = [supplier.id]; showSupplierDropdown = false;">
                                                            <input type="checkbox" :checked="createForm.supplier_ids && createForm.supplier_ids.includes(supplier.id)" class="me-2" style="cursor: pointer; pointer-events: none;">
                                                            <span style="font-size: 12px;" x-text="supplier.name"></span>
                                                        </div>
                                                    </template>
                                                    <template x-if="filteredSuppliers.length === 0">
                                                        <div class="px-3 py-2 text-muted text-center" style="font-size: 11px;">No suppliers found</div>
                                                    </template>
                                                </div>
                                            </div>
                                            <div class="col-md-6 position-relative" :style="showWarehouseDropdown ? 'z-index: 1055;' : ''" @click.away="showWarehouseDropdown = false">
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Warehouse *</label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-body text-muted border-end-0"><i class="bi bi-building"></i></span>
                                                    <div class="form-control border-start-0 ps-0 fw-semibold d-flex align-items-center bg-body cursor-pointer" @click="showWarehouseDropdown = !showWarehouseDropdown; if(showWarehouseDropdown) setTimeout(() => $refs.warehouseSearch.focus(), 50)" style="font-size: 12px; min-height: 31px;">
                                                        <span class="flex-grow-1 text-truncate" x-text="selectedWarehouseName || 'Select Warehouse'"></span>
                                                        <i class="bi bi-chevron-down text-muted" style="font-size: 10px;"></i>
                                                    </div>
                                                </div>
                                                <div x-show="showWarehouseDropdown" x-transition class="position-absolute w-100 bg-body border rounded shadow-lg mt-1 custom-scrollbar" style="max-height: 200px; overflow-y: auto; z-index: 1050; left: 0;">
                                                    <div class="p-2 border-bottom position-sticky top-0 bg-body" style="z-index: 1051;">
                                                        <input x-ref="warehouseSearch" type="text" class="form-control form-control-sm" x-model="warehouseSearch" placeholder="Search..." style="font-size: 12px;">
                                                    </div>
                                                    <template x-for="warehouse in filteredWarehouses" :key="warehouse.id">
                                                        <div class="px-3 py-2 cursor-pointer custom-hover-bg d-flex align-items-center border-bottom" @click="selectWarehouse(warehouse)">
                                                            <input type="checkbox" :checked="createForm.warehouse_id === warehouse.id" class="me-2" style="cursor: pointer; pointer-events: none;">
                                                            <span style="font-size: 12px;" x-text="warehouse.name"></span>
                                                        </div>
                                                    </template>
                                                    <template x-if="filteredWarehouses.length === 0">
                                                        <div class="px-3 py-2 text-muted text-center" style="font-size: 11px;">No warehouses found</div>
                                                    </template>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Expected Delivery</label>
                                                <input type="date" class="form-control form-control-sm fw-semibold" x-model="createForm.expected_delivery_date" style="font-size: 12px;">
                                            </div>
                                            <div class="col-md-8">
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Notes</label>
                                                <input type="text" class="form-control form-control-sm fw-semibold" x-model="createForm.notes" placeholder="Optional notes for this purchase order..." style="font-size: 12px;">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Line Items --}}
                            <div class="col-12 position-relative" style="z-index: 50;">
                                <div class="card mb-0 border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center gap-2 pb-2 mb-2 border-bottom">
                                            <div class="bg-primary bg-opacity-10 text-primary rounded-2 d-flex align-items-center justify-content-center" style="width: 20px; height: 20px;">
                                                <i class="bi bi-box-seam" style="font-size: 10px;"></i>
                                            </div>
                                            <h6 class="mb-0 fw-bold text-uppercase text-body" style="font-size: 11px; letter-spacing: 1px;">Line Items</h6>
                                        </div>
                                        
                                        <div class="bg-body rounded border shadow-sm table-responsive custom-scrollbar" :class="{'overflow-visible': createForm.items.some(i => i._showDropdown)}" style="min-height: 250px;">
                                            <table class="table table-hover align-middle mb-0 text-nowrap" style="min-width: 800px;">
                                                <thead class="table-group-divider">
                                                    <tr>
                                                        <th class="text-uppercase text-muted fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">Product *</th>
                                                        <th class="text-uppercase text-muted fw-bold" style="font-size: 10px; letter-spacing: 0.5px; width: 100px;">Qty *</th>
                                                        <th class="text-uppercase text-muted fw-bold" style="font-size: 10px; letter-spacing: 0.5px; width: 120px;">Unit Price *</th>
                                                        <th class="text-uppercase text-muted fw-bold" style="font-size: 10px; letter-spacing: 0.5px; width: 150px;">Tax (%)</th>
                                                        <th class="text-uppercase text-muted fw-bold" style="font-size: 10px; letter-spacing: 0.5px; width: 120px;">Discount (₹)</th>
                                                        <th class="text-uppercase text-muted fw-bold" style="font-size: 10px; letter-spacing: 0.5px; width: 150px;">Total</th>
                                                        <th style="width: 50px;"></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <template x-for="(item, index) in createForm.items" :key="index">
                                                        <tr>
                                                            <td class="position-relative">
                                                                <div :id="'prodSearchToggle_'+index" class="form-control form-control-sm fw-semibold d-flex align-items-center bg-body cursor-pointer position-relative" @click="if(!item._showDropdown) { item._showDropdown = true; setTimeout(() => document.getElementById('prodSearch_'+index).focus(), 50) } else { item._showDropdown = false; }" style="font-size: 12px; min-height: 31px; padding-right: 25px;">
                                                                    <template x-if="item.product_id && getProductObj(item.product_id)?.image">
                                                                        <img :src="getProductObj(item.product_id).image" class="rounded object-fit-cover shadow-sm border border-secondary border-opacity-25 me-2 flex-shrink-0" style="width: 18px; height: 18px;" alt="">
                                                                    </template>
                                                                    <span class="flex-grow-1 text-truncate" x-text="getProductName(item.product_id) || 'Select Product'"></span>
                                                                    <i class="bi bi-chevron-down text-muted position-absolute" style="font-size: 10px; right: 8px;"></i>
                                                                </div>
                                                                <div x-show="item._showDropdown" x-transition class="position-absolute bg-body border rounded shadow-lg mt-1 custom-scrollbar" style="top: 100%; left: 0; width: 100%; min-width: 250px; max-height: 250px; overflow-y: auto; z-index: 1060;" @click.outside="item._showDropdown = false">
                                                                    <div class="p-2 border-bottom position-sticky top-0 bg-body" style="z-index: 1061;">
                                                                        <input :id="'prodSearch_'+index" type="text" class="form-control form-control-sm" x-model="item._search" placeholder="Search product..." style="font-size: 12px;">
                                                                    </div>
                                                                    <template x-for="product in filteredProducts(item)" :key="product.id">
                                                                        <div class="px-3 py-2 cursor-pointer custom-hover-bg d-flex align-items-center border-bottom" @click="selectProduct(item, product)">
                                                                            <input type="checkbox" :checked="item.product_id === product.id" class="me-2 flex-shrink-0" style="cursor: pointer; pointer-events: none;">
                                                                            <template x-if="product.image">
                                                                                <img :src="product.image" class="rounded me-2 object-fit-cover border shadow-sm flex-shrink-0" style="width: 28px; height: 28px;" alt="">
                                                                            </template>
                                                                            <template x-if="!product.image">
                                                                                <div class="bg-secondary bg-opacity-10 text-secondary rounded d-flex align-items-center justify-content-center me-2 border border-secondary border-opacity-25 shadow-sm flex-shrink-0" style="width: 28px; height: 28px;">
                                                                                    <i class="bi bi-box" style="font-size: 12px;"></i>
                                                                                </div>
                                                                            </template>
                                                                            <div class="d-flex flex-column text-truncate">
                                                                                <span style="font-size: 12px; line-height: 1.2;" class="fw-medium text-body text-truncate" x-text="product.name"></span>
                                                                                <span style="font-size: 9px;" class="text-muted text-uppercase text-truncate" x-text="product.sku"></span>
                                                                            </div>
                                                                        </div>
                                                                    </template>
                                                                    <template x-if="filteredProducts(item).length === 0">
                                                                        <div class="px-3 py-2 text-muted text-center" style="font-size: 11px;">No products found</div>
                                                                    </template>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <input type="number" step="0.01" class="form-control form-control-sm fw-semibold" x-model="item.quantity" style="font-size: 12px;" required>
                                                            </td>
                                                            <td>
                                                                <input type="number" step="0.01" class="form-control form-control-sm fw-semibold" x-model="item.unit_price" style="font-size: 12px;" required>
                                                            </td>
                                                            <td>
                                                                <select class="form-select form-select-sm fw-semibold" x-model.number="item.tax_rate" style="font-size: 12px;">
                                                                    <option value="0">0%</option>
                                                                    @foreach($taxRates as $tax)
                                                                        <option value="{{ $tax->rate }}">{{ $tax->name }} ({{ number_format($tax->rate, 2) }}%)</option>
                                                                    @endforeach
                                                                </select>
                                                            </td>
                                                            <td>
                                                                <input type="number" step="0.01" min="0" class="form-control form-control-sm fw-semibold" x-model="item.discount_amount" style="font-size: 12px;">
                                                            </td>
                                                            <td class="text-end fw-bold text-primary" style="font-size: 13px;">
                                                                ₹<span x-text="(((item.quantity || 0) * (item.unit_price || 0)) * (1 + (item.tax_rate || 0) / 100) - (item.discount_amount || 0)).toFixed(2)"></span>
                                                            </td>
                                                            <td class="text-center">
                                                                <button type="button" class="btn btn-sm btn-link text-danger" @click="removeLineItem(index)" x-show="createForm.items.length > 1" title="Remove Item">
                                                                    <i class="bi bi-trash-fill fs-5"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    </template>
                                                </tbody>
                                                <tfoot class="table-group-divider">
                                                    <tr>
                                                        <td colspan="7" class="py-2 border-bottom-0">
                                                            <button type="button" class="btn btn-sm btn-outline-primary fw-bold px-3 py-1" style="font-size: 11px; letter-spacing: 0.5px;" @click="addLineItem()">
                                                                <i class="bi bi-plus-circle-fill me-1"></i> ADD ROW
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="5" class="text-end text-muted small py-1 border-0">Sub Total:</td>
                                                        <td colspan="2" class="text-end py-1 border-0 fw-medium">
                                                            ₹<span x-text="createForm.items.reduce((sum, item) => sum + ((item.quantity || 0) * (item.unit_price || 0)), 0).toFixed(2)"></span>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="5" class="text-end text-muted small py-1 border-0">Total Tax:</td>
                                                        <td colspan="2" class="text-end py-1 border-0 fw-medium">
                                                            ₹<span x-text="createForm.items.reduce((sum, item) => sum + (((item.quantity || 0) * (item.unit_price || 0)) * ((item.tax_rate || 0) / 100)), 0).toFixed(2)"></span>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="5" class="text-end text-muted small py-1 border-0">Total Discount:</td>
                                                        <td colspan="2" class="text-end py-1 border-0 text-danger fw-medium">
                                                            -₹<span x-text="createForm.items.reduce((sum, item) => sum + parseFloat(item.discount_amount || 0), 0).toFixed(2)"></span>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="5" class="text-end fw-bold py-2">Grand Total:</td>
                                                        <td colspan="2" class="text-end fw-bold text-primary fs-6 py-2">
                                                            ₹<span x-text="createForm.items.reduce((sum, item) => sum + (((item.quantity || 0) * (item.unit_price || 0)) * (1 + (item.tax_rate || 0) / 100) - (item.discount_amount || 0)), 0).toFixed(2)"></span>
                                                        </td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer border-top-0 pt-0 mt-4 px-0">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm" :disabled="isSubmitting">
                                <span x-show="isSubmitting" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                <span>Create Purchase Order</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

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
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">PO Number</label>
                                                <div class="form-control form-control-sm fw-semibold bg-body text-primary" x-text="receiveForm.po_number" style="font-size: 12px;" disabled></div>
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

    <!-- Reject PO Modal -->
    <div class="modal fade" id="rejectPoModal" aria-labelledby="rejectPoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0 bg-body-tertiary">
                    <h5 class="modal-title fw-bold text-danger" id="rejectPoModalLabel">Reject Purchase Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form @submit.prevent="submitRejectForm" autocomplete="off">
                    <div class="modal-body pt-3 bg-body-tertiary">
                        <p class="text-muted small mb-3">Please provide a reason for rejecting this purchase order. This action cannot be undone.</p>
                        <div class="mb-3">
                            <label class="form-label fw-bold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 1px;">Rejection Reason *</label>
                            <textarea class="form-control" x-model="rejectForm.rejection_reason" rows="3" required placeholder="Enter the reason for rejection..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-body-tertiary border-top-0">
                        <button type="button" data-bs-dismiss="modal" class="btn text-muted fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 1px;">Cancel</button>
                        <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold shadow-sm" :disabled="isRejecting">
                            <span x-show="isRejecting" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            <span>Confirm Rejection</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Approve PO Modal -->
    <div class="modal fade" id="approvePoModal" aria-labelledby="approvePoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0 bg-body-tertiary">
                    <h5 class="modal-title fw-bold text-success" id="approvePoModalLabel">Approve Purchase Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form @submit.prevent="submitApproveForm" autocomplete="off">
                    <div class="modal-body pt-3 bg-body-tertiary">
                        <p class="text-muted small mb-0">Are you sure you want to approve the selected Purchase Order<span x-show="isBulkApprove">s</span>? This will transition the order<span x-show="isBulkApprove">s</span> to an approved state and allow goods receipt processing.</p>
                    </div>
                    <div class="modal-footer bg-body-tertiary border-top-0">
                        <button type="button" data-bs-dismiss="modal" class="btn text-muted fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 1px;">Cancel</button>
                        <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm" :disabled="isApproving">
                            <span x-show="isApproving" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            <span>Confirm Approval</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View PO Modal -->
    <div class="modal fade" id="viewPoModal" aria-labelledby="viewPoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0 bg-body-tertiary">
                    <h5 class="modal-title fw-bold" id="viewPoModalLabel">Purchase Order Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3 bg-body-tertiary" x-show="selectedPO">
                    <template x-if="selectedPO">
                        <div>
                            <div class="row mb-4">
                                <div class="col-sm-6">
                                    <h6 class="text-muted text-uppercase mb-1" style="font-size: 10px; letter-spacing: 1px;">PO Number</h6>
                                    <p class="fw-bold fs-5 mb-0" x-text="selectedPO.po_number"></p>
                                </div>
                                <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
                                    <h6 class="text-muted text-uppercase mb-1" style="font-size: 10px; letter-spacing: 1px;">Status</h6>
                                    <div class="d-flex gap-2 justify-content-sm-end">
                                        <span class="badge" 
                                              :class="{
                                                'bg-warning-subtle text-warning': selectedPO.status === 'pending',
                                                'bg-primary-subtle text-primary': selectedPO.status === 'approved',
                                                'bg-info-subtle text-info': selectedPO.status === 'partially_received',
                                                'bg-success-subtle text-success': selectedPO.status === 'received',
                                                'bg-danger-subtle text-danger': selectedPO.status === 'rejected'
                                              }" 
                                              x-text="(selectedPO.status || 'pending').toUpperCase().replace('_', ' ')"></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-body">
                                            <h6 class="text-muted text-uppercase mb-3" style="font-size: 11px; letter-spacing: 1px;"><i class="bi bi-truck me-2"></i>Supplier Info</h6>
                                            <p class="mb-1 fw-medium" x-text="selectedPO.supplier ? (selectedPO.supplier.company_name || selectedPO.supplier.firstname + ' ' + (selectedPO.supplier.lastname || '')) : 'Unknown'"></p>
                                            
                                            <div class="small text-muted mb-2">
                                                <template x-if="selectedPO.supplier && selectedPO.supplier.gst_no">
                                                    <div><strong>GSTIN:</strong> <span x-text="selectedPO.supplier.gst_no"></span></div>
                                                </template>
                                                <template x-if="selectedPO.supplier && selectedPO.supplier.pan_no">
                                                    <div><strong>PAN:</strong> <span x-text="selectedPO.supplier.pan_no"></span></div>
                                                </template>
                                            </div>

                                            <p class="mb-0 text-muted small">
                                                <i class="bi bi-telephone me-1"></i> <span x-text="selectedPO.supplier ? selectedPO.supplier.phone : 'N/A'"></span><br>
                                                <i class="bi bi-envelope me-1"></i> <span x-text="selectedPO.supplier ? selectedPO.supplier.email : 'N/A'"></span>
                                            </p>

                                            <div class="mt-2 text-muted small" x-show="selectedPO.supplier && selectedPO.supplier.address_line_1">
                                                <strong>Address:</strong>
                                                <div class="mt-1 ps-2 border-start border-2 border-secondary border-opacity-25">
                                                    <div><span class="fw-medium text-secondary" style="font-size: 10.5px;">Line 1:</span> <span x-text="selectedPO.supplier.address_line_1"></span></div>
                                                    <template x-if="selectedPO.supplier.address_line_2">
                                                        <div><span class="fw-medium text-secondary" style="font-size: 10.5px;">Line 2:</span> <span x-text="selectedPO.supplier.address_line_2"></span></div>
                                                    </template>
                                                    <template x-if="selectedPO.supplier.village_name">
                                                        <div><span class="fw-medium text-secondary" style="font-size: 10.5px;">Village:</span> <span x-text="selectedPO.supplier.village_name"></span></div>
                                                    </template>
                                                    <template x-if="selectedPO.supplier.taluka">
                                                        <div><span class="fw-medium text-secondary" style="font-size: 10.5px;">Taluka:</span> <span x-text="selectedPO.supplier.taluka"></span></div>
                                                    </template>
                                                    <template x-if="selectedPO.supplier.city">
                                                        <div><span class="fw-medium text-secondary" style="font-size: 10.5px;">District/City:</span> <span x-text="selectedPO.supplier.city"></span></div>
                                                    </template>
                                                    <template x-if="selectedPO.supplier.state || selectedPO.supplier.pincode">
                                                        <div><span class="fw-medium text-secondary" style="font-size: 10.5px;">State/PIN:</span> <span x-text="selectedPO.supplier.state || ''"></span> - <span x-text="selectedPO.supplier.pincode || ''"></span></div>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-body">
                                            <h6 class="text-muted text-uppercase mb-3" style="font-size: 11px; letter-spacing: 1px;"><i class="bi bi-building me-2"></i>Delivery Info (Warehouse)</h6>
                                            <p class="mb-1 fw-medium" x-text="selectedPO.warehouse ? selectedPO.warehouse.name : 'Unknown'"></p>
                                            
                                            <div class="small text-muted mb-2">
                                                <template x-if="selectedPO.warehouse && selectedPO.warehouse.gstin">
                                                    <div><strong>GSTIN:</strong> <span x-text="selectedPO.warehouse.gstin"></span></div>
                                                </template>
                                            </div>

                                            <p class="mb-0 text-muted small">
                                                <i class="bi bi-telephone me-1"></i> <span x-text="selectedPO.warehouse ? selectedPO.warehouse.phone : 'N/A'"></span><br>
                                                <i class="bi bi-envelope me-1"></i> <span x-text="selectedPO.warehouse ? selectedPO.warehouse.email : 'N/A'"></span>
                                            </p>

                                            <div class="mt-2 text-muted small" x-show="selectedPO.warehouse && selectedPO.warehouse.address_line_1">
                                                <strong>Address:</strong>
                                                <div class="mt-1 ps-2 border-start border-2 border-secondary border-opacity-25">
                                                    <div><span class="fw-medium text-secondary" style="font-size: 10.5px;">Line 1:</span> <span x-text="selectedPO.warehouse.address_line_1"></span></div>
                                                    <template x-if="selectedPO.warehouse.address_line_2">
                                                        <div><span class="fw-medium text-secondary" style="font-size: 10.5px;">Line 2:</span> <span x-text="selectedPO.warehouse.address_line_2"></span></div>
                                                    </template>
                                                    <template x-if="selectedPO.warehouse.village_name">
                                                        <div><span class="fw-medium text-secondary" style="font-size: 10.5px;">Village:</span> <span x-text="selectedPO.warehouse.village_name"></span></div>
                                                    </template>
                                                    <template x-if="selectedPO.warehouse.taluka">
                                                        <div><span class="fw-medium text-secondary" style="font-size: 10.5px;">Taluka:</span> <span x-text="selectedPO.warehouse.taluka"></span></div>
                                                    </template>
                                                    <template x-if="selectedPO.warehouse.city">
                                                        <div><span class="fw-medium text-secondary" style="font-size: 10.5px;">District/City:</span> <span x-text="selectedPO.warehouse.city"></span></div>
                                                    </template>
                                                    <template x-if="selectedPO.warehouse.state || selectedPO.warehouse.pincode">
                                                        <div><span class="fw-medium text-secondary" style="font-size: 10.5px;">State/PIN:</span> <span x-text="selectedPO.warehouse.state || ''"></span> - <span x-text="selectedPO.warehouse.pincode || ''"></span></div>
                                                    </template>
                                                </div>
                                            </div>

                                            <p class="mb-1 text-muted small mt-2"><strong>Created At:</strong> <span x-text="selectedPO.created_at ? new Date(selectedPO.created_at).toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute:'2-digit', hour12: true }).replace(',', '') : 'N/A'"></span></p>
                                            <p class="mb-0 text-muted small"><strong>Expected Delivery:</strong> <span x-text="selectedPO.expected_delivery_date ? new Date(selectedPO.expected_delivery_date).toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute:'2-digit', hour12: true }).replace(',', '') : 'N/A'"></span></p>
                                            
                                            <template x-if="selectedPO.invoice_path">
                                                <div class="mt-3">
                                                    <a :href="selectedPO.invoice_url" target="_blank" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center">
                                                        <i class="bi bi-file-earmark-pdf me-2"></i> View Attached Invoice
                                                    </a>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <template x-if="selectedPO.status === 'rejected' && selectedPO.rejection_reason">
                                <div class="alert alert-danger shadow-sm border-0 mb-4">
                                    <h6 class="alert-heading fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Rejection Reason</h6>
                                    <p class="mb-0 small" x-text="selectedPO.rejection_reason"></p>
                                </div>
                            </template>

                            <h6 class="text-muted text-uppercase mb-2" style="font-size: 11px; letter-spacing: 1px;">Order Items</h6>
                            <div class="table-responsive bg-body rounded shadow-sm">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Product</th>
                                            <th class="text-end">Qty</th>
                                            <th class="text-end">Unit Price</th>
                                            <th class="text-end">Tax (%)</th>
                                            <th class="text-end">Discount</th>
                                            <th class="text-end">Net Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="item in selectedPO.items" :key="item.id">
                                            <tr>
                                                <td>
                                                    <div class="fw-medium" x-text="item.product ? item.product.name : 'Unknown'"></div>
                                                    <div class="small text-muted" x-text="item.product ? item.product.sku : ''"></div>
                                                </td>
                                                <td class="text-end" x-text="item.quantity"></td>
                                                <td class="text-end">₹<span x-text="item.unit_price"></span></td>
                                                <td class="text-end"><span x-text="item.tax_rate || '0.00'"></span>%</td>
                                                <td class="text-end text-danger">-₹<span x-text="item.discount_amount || '0.00'"></span></td>
                                                <td class="text-end fw-medium">₹<span x-text="item.net_amount || item.total_price"></span></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <td colspan="5" class="text-end text-muted small">Sub Total:</td>
                                            <td class="text-end">₹<span x-text="selectedPO.total_amount"></span></td>
                                        </tr>
                                        <tr>
                                            <td colspan="5" class="text-end text-muted small">Tax Amount:</td>
                                            <td class="text-end">₹<span x-text="selectedPO.tax_amount || '0.00'"></span></td>
                                        </tr>
                                        <tr>
                                            <td colspan="5" class="text-end text-muted small">Discount:</td>
                                            <td class="text-end text-danger">-₹<span x-text="selectedPO.discount_amount || '0.00'"></span></td>
                                        </tr>
                                        <tr>
                                            <td colspan="5" class="text-end fw-bold">Grand Total:</td>
                                            <td class="text-end fw-bold text-primary fs-6">₹<span x-text="selectedPO.net_amount || selectedPO.total_amount"></span></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            
                            <template x-if="selectedPO.notes">
                                <div class="mt-4">
                                    <h6 class="text-muted text-uppercase mb-2" style="font-size: 11px; letter-spacing: 1px;">Internal Notes</h6>
                                    <p class="small text-muted bg-body rounded p-3 border" x-text="selectedPO.notes"></p>
                                </div>
                            </template>

                        </div>
                    </template>
                </div>
                <div class="modal-footer bg-body-tertiary border-top-0 d-flex justify-content-between">
                    <div class="d-flex gap-2">
                        <div x-show="selectedPO" style="display: none;">
                            <a :href="'/procurement/purchase-orders/' + selectedPO.id + '/pdf'" target="_blank" class="btn btn-outline-primary rounded-pill px-4 fw-bold shadow-sm bg-white">
                                <i class="bi bi-file-pdf me-1"></i> Download PDF
                            </a>
                        </div>
                        @can('purchaseorder-delete')
                        <template x-if="selectedPO && selectedPO.status === 'pending' && !selectedPO.deleted_at">
                            <div>
                                <button type="button" class="btn btn-outline-danger rounded-pill px-4 fw-bold shadow-sm bg-white" @click="
                                    bootstrap.Modal.getInstance(document.getElementById('viewPoModal')).hide();
                                    setTimeout(() => { openDeleteModal(selectedPO.id); }, 300);
                                ">
                                    <i class="bi bi-trash me-1"></i> Delete
                                </button>
                            </div>
                        </template>
                        <template x-if="selectedPO && selectedPO.deleted_at">
                            <div>
                                <button type="button" class="btn btn-warning rounded-pill px-4 fw-bold shadow-sm" @click="
                                    bootstrap.Modal.getInstance(document.getElementById('viewPoModal')).hide();
                                    setTimeout(() => { openRestoreModal(selectedPO.id); }, 300);
                                ">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Restore
                                </button>
                            </div>
                        </template>
                        @endcan
                    </div>
                    
                    @can('purchaseorder-approve')
                    <template x-if="selectedPO && selectedPO.status === 'pending' && !selectedPO.deleted_at">
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-danger rounded-pill px-4 fw-bold shadow-sm" @click="
                                bootstrap.Modal.getInstance(document.getElementById('viewPoModal')).hide();
                                setTimeout(() => { openRejectModal(selectedPO); }, 300);
                            ">
                                <i class="bi bi-x-circle me-1"></i> Reject
                            </button>
                            <button type="button" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm" @click="
                                bootstrap.Modal.getInstance(document.getElementById('viewPoModal')).hide();
                                setTimeout(() => { openApproveModal(selectedPO.id); }, 300);
                            ">
                                <i class="bi bi-check-circle me-1"></i> Approve
                            </button>
                        </div>
                    </template>
                    @endcan
                </div>
            </div>
        </div>
    </div>


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

    <!-- Delete PO Modal -->
    <div class="modal fade" id="deletePoModal" aria-labelledby="deletePoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0 bg-body-tertiary">
                    <h5 class="modal-title fw-bold text-danger" id="deletePoModalLabel">Delete Purchase Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form @submit.prevent="submitDeleteForm" autocomplete="off">
                    <div class="modal-body pt-3 bg-body-tertiary">
                        <p class="text-muted small mb-0">Are you sure you want to delete the selected Purchase Order<span x-show="isBulkDelete">s</span>? This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer bg-body-tertiary border-top-0">
                        <button type="button" data-bs-dismiss="modal" class="btn text-muted fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 1px;">Cancel</button>
                        <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold shadow-sm" :disabled="isDeleting">
                            <span x-show="isDeleting" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            <span>Confirm Deletion</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Restore PO Modal -->
    <div class="modal fade" id="restorePoModal" aria-labelledby="restorePoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0 bg-body-tertiary">
                    <h5 class="modal-title fw-bold text-warning" id="restorePoModalLabel">Restore Purchase Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form @submit.prevent="submitRestoreForm" autocomplete="off">
                    <div class="modal-body pt-3 bg-body-tertiary">
                        <p class="text-muted small mb-0">Are you sure you want to restore the selected Purchase Order<span x-show="isBulkRestore">s</span>?</p>
                    </div>
                    <div class="modal-footer bg-body-tertiary border-top-0">
                        <button type="button" data-bs-dismiss="modal" class="btn text-muted fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 1px;">Cancel</button>
                        <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold shadow-sm" :disabled="isRestoring">
                            <span x-show="isRestoring" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            <span>Confirm Restore</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        .custom-hover-bg:hover { background-color: rgba(var(--bs-primary-rgb), 0.1); }
        .cursor-pointer { cursor: pointer; }
    </style>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('purchaseOrdersTable', () => ({
        items: [],
        isLoading: false,
        searchQuery: '',
        statusFilter: '',
        trashedFilter: '',
        currentPage: 1,
        totalPages: 1,
        selected: [],
        get allSelected() { return this.items.length > 0 && this.selected.length === this.items.length; },
        
        init() {
            this.fetchData();
            this.$watch('searchQuery', () => { this.currentPage = 1; this.fetchData(); });
            this.$watch('statusFilter', () => { this.currentPage = 1; this.fetchData(); });
            this.$watch('trashedFilter', () => { this.currentPage = 1; this.fetchData(); });
        },
        
        fetchData() {
            this.isLoading = true;
            let url = new URL(window.location.href);
            url.searchParams.set('page', this.currentPage);
            if (this.searchQuery) url.searchParams.set('search', this.searchQuery);
            if (this.statusFilter) url.searchParams.set('status', this.statusFilter);
            if (this.trashedFilter) url.searchParams.set('trashed', this.trashedFilter);
            
            fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(res => res.json())
                .then(data => {
                    this.items = data.data;
                    this.currentPage = data.current_page;
                    this.totalPages = data.last_page;
                })
                .catch(err => console.error(err))
                .finally(() => this.isLoading = false);
        },

        toggleAll(e) {
            this.selected = e.target.checked ? this.items.map(i => i.id) : [];
        },
        
        showNotification(msg, type = 'success') {
            if (window.AdminApp && window.AdminApp.notificationManager) {
                if (type === 'success') window.AdminApp.notificationManager.success(msg);
                else if (type === 'error') window.AdminApp.notificationManager.error(msg);
                else if (type === 'info') window.AdminApp.notificationManager.info(msg);
                else window.AdminApp.notificationManager.success(msg);
            } else {
                let container = document.getElementById("toast-container");
                if (!container) {
                    container = document.createElement("div");
                    container.id = "toast-container";
                    container.className = "toast-container position-fixed top-0 end-0 p-3";
                    container.style.zIndex = "1090";
                    document.body.appendChild(container);
                }
                const toast = document.createElement("div");
                const bsType = type === 'error' ? 'danger' : (type === 'info' ? 'info' : 'success');
                toast.className = `toast align-items-center text-bg-${bsType} border-0 show mb-2 shadow-sm`;
                toast.setAttribute("role", "alert");
                toast.innerHTML = `<div class="d-flex"><div class="toast-body fw-semibold">${msg}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
                container.appendChild(toast);
                setTimeout(() => toast.remove(), 5000);
            }
        },
        
        suppliersList: {!! $suppliers->map(function($s) { return ['id' => $s->id, 'name' => $s->company_name ?: $s->firstname]; })->toJson() !!},
        warehousesList: {!! $warehouses->toJson() !!},
        productsList: {!! $products->map(function($p) { return ['id' => $p->id, 'name' => $p->name, 'sku' => $p->sku, 'image' => $p->image_url, 'supplier_id' => $p->supplier_id]; })->toJson() !!},
        
        supplierSearch: '',
        showSupplierDropdown: false,
        get filteredSuppliers() {
            if (!this.supplierSearch) return this.suppliersList;
            return this.suppliersList.filter(s => (s.name || '').toLowerCase().includes(this.supplierSearch.toLowerCase()));
        },
        get selectedSupplierName() {
            if (!this.createForm.supplier_ids || this.createForm.supplier_ids.length === 0) return '';
            if (this.createForm.supplier_ids.length === this.suppliersList.length) return 'All Suppliers';
            if (this.createForm.supplier_ids.length === 1) {
                const s = this.suppliersList.find(s => s.id === this.createForm.supplier_ids[0]);
                return s ? (s.name || '') : '';
            }
            return this.createForm.supplier_ids.length + ' Suppliers Selected';
        },
        toggleSupplier(id) {
            const idx = this.createForm.supplier_ids.indexOf(id);
            if (idx > -1) this.createForm.supplier_ids.splice(idx, 1);
            else this.createForm.supplier_ids.push(id);
        },
        toggleAllSuppliers() {
            if (this.createForm.supplier_ids.length === this.suppliersList.length) {
                this.createForm.supplier_ids = [];
            } else {
                this.createForm.supplier_ids = this.suppliersList.map(s => s.id);
            }
        },

        warehouseSearch: '',
        showWarehouseDropdown: false,
        get filteredWarehouses() {
            if (!this.warehouseSearch) return this.warehousesList;
            return this.warehousesList.filter(w => (w.name || '').toLowerCase().includes(this.warehouseSearch.toLowerCase()));
        },
        get selectedWarehouseName() {
            const w = this.warehousesList.find(w => w.id === this.createForm.warehouse_id);
            return w ? w.name : '';
        },
        selectWarehouse(warehouse) {
            this.createForm.warehouse_id = warehouse.id;
            this.showWarehouseDropdown = false;
        },

        filteredProducts(item) {
            let list = this.productsList;
            if (this.createForm.supplier_ids && this.createForm.supplier_ids.length > 0) {
                list = list.filter(p => this.createForm.supplier_ids.includes(p.supplier_id));
            }
            if (!item._search) return list;
            return list.filter(p => (p.name || '').toLowerCase().includes(item._search.toLowerCase()) || (p.sku && p.sku.toLowerCase().includes(item._search.toLowerCase())));
        },
        getProductObj(id) {
            return this.productsList.find(p => p.id === id);
        },
        getProductName(id) {
            const p = this.getProductObj(id);
            return p ? `${p.name || ''} (${p.sku || ''})` : '';
        },
        selectProduct(item, product) {
            item.product_id = product.id;
            item.unit_price = parseFloat(product.purchase_price) || 0;
            item.tax_rate = product.tax_rate ? parseFloat(product.tax_rate.rate) : 0;
            item.discount_amount = parseFloat(product.default_discount) || 0;
            item._showDropdown = false;
        },

        createForm: {
            supplier_ids: [],
            warehouse_id: '',
            expected_delivery_date: '',
            notes: '',
            items: [{ product_id: '', quantity: 1, unit_price: 0, tax_rate: 0, discount_amount: 0, _showDropdown: false, _search: '' }]
        },
        isSubmitting: false,

        openCreateModal() {
            this.createForm = {
                supplier_ids: [], warehouse_id: '', expected_delivery_date: new Date().toISOString().split('T')[0], notes: '',
                items: [{ product_id: '', quantity: 1, unit_price: 0, tax_rate: 0, discount_amount: 0, _showDropdown: false, _search: '' }]
            };
            this.supplierSearch = '';
            this.warehouseSearch = '';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('createPoModal')).show();
        },
        
        addLineItem() {
            this.createForm.items.push({ product_id: '', quantity: 1, unit_price: 0, tax_rate: 0, discount_amount: 0, _showDropdown: false, _search: '' });
        },
        
        removeLineItem(index) {
            if (this.createForm.items.length > 1) {
                this.createForm.items.splice(index, 1);
            }
        },

        async submitCreateForm() {
            this.isSubmitting = true;
            try {
                const payloads = [];
                for (let supplierId of this.createForm.supplier_ids) {
                    const supplierItems = this.createForm.items.filter(item => {
                        const product = this.getProductObj(item.product_id);
                        return product && product.supplier_id === supplierId;
                    });
                    if (supplierItems.length > 0) {
                        payloads.push({
                            supplier_id: supplierId,
                            warehouse_id: this.createForm.warehouse_id,
                            expected_delivery_date: this.createForm.expected_delivery_date,
                            notes: this.createForm.notes,
                            items: supplierItems.map(i => ({
                                product_id: i.product_id,
                                quantity: i.quantity,
                                unit_price: i.unit_price,
                                tax_rate: i.tax_rate,
                                discount_amount: i.discount_amount
                            }))
                        });
                    }
                }
                
                if (payloads.length === 0) {
                    this.showNotification("No valid line items found for the selected suppliers.", "error");
                    this.isSubmitting = false;
                    return;
                }

                const promises = payloads.map(payload => 
                    fetch('{{ route("procurement.purchase-orders.store") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(payload)
                    }).then(res => res.json().then(data => ({ status: res.ok, data })))
                );
                
                const results = await Promise.all(promises);
                const failures = results.filter(r => !r.status);
                
                if (failures.length === 0) {
                    this.showNotification("Purchase Orders created successfully.", "success");
                    bootstrap.Modal.getInstance(document.getElementById('createPoModal')).hide();
                    this.fetchData();
                } else {
                    this.showNotification(failures.length + ' purchase order(s) failed. ' + (failures[0].data.message || 'Validation failed.'), "error");
                }
            } catch (error) {
                console.error(error);
                this.showNotification('An error occurred. Please try again.', "error");
            } finally {
                this.isSubmitting = false;
            }
        },
        selectedPO: null,
        
        viewItem(item) {
            this.selectedPO = item;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('viewPoModal')).show();
        },
        
        exportData() {
            this.showNotification('Exporting data...', "info");
        },

        receiveForm: {
            po_id: '',
            po_number: '',
            received_date: new Date().toISOString().split('T')[0],
            notes: '',
            items: []
        },
        isReceiving: false,

        openReceiveModal(po) {
            this.receiveForm.po_id = po.id;
            this.receiveForm.po_number = po.po_number;
            this.receiveForm.received_date = new Date().toISOString().split('T')[0];
            this.receiveForm.notes = '';
            
            this.receiveForm.items = po.items.map(i => {
                return {
                    purchase_order_item_id: i.id,
                    product: i.product,
                    ordered_qty: i.quantity,
                    previously_received: i.received_qty || 0,
                    accepted_qty: Math.max(0, i.quantity - (i.received_qty || 0)),
                    rejected_qty: 0,
                    batch_number: '',
                    manufacturing_date: '',
                    expiry_date: '',
                };
            });
            bootstrap.Modal.getOrCreateInstance(document.getElementById('receiveGoodsModal')).show();
        },

        async submitReceiveForm() {
            this.isReceiving = true;
            try {
                const response = await fetch(`/procurement/purchase-orders/${this.receiveForm.po_id}/receive`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                    body: JSON.stringify(this.receiveForm)
                });
                const data = await response.json();
                if (response.ok) {
                    bootstrap.Modal.getInstance(document.getElementById('receiveGoodsModal')).hide();
                    this.fetchData();
                    this.showNotification(data.message || 'GRN processed successfully.', "success");
                } else {
                    this.showNotification(data.message || 'Failed to process GRN.', "error");
                }
            } catch (err) {
                console.error(err);
                this.showNotification('An error occurred.', "error");
            } finally {
                this.isReceiving = false;
            }
        },

        isDeleting: false,
        isBulkDelete: false,
        deleteForm: {
            po_id: ''
        },


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

        openDeleteModal(poId) {
            this.isBulkDelete = false;
            this.deleteForm.po_id = poId;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('deletePoModal')).show();
        },

        openBulkDeleteModal() {
            if (this.selected.length === 0) return;
            this.isBulkDelete = true;
            this.deleteForm.po_id = '';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('deletePoModal')).show();
        },

        async submitDeleteForm() {
            if (this.isDeleting) return;
            this.isDeleting = true;
            try {
                let url, method, bodyData;
                if (this.isBulkDelete) {
                    url = `/procurement/purchase-orders/bulk`;
                    method = 'POST';
                    bodyData = {
                        action: 'delete',
                        ids: this.selected
                    };
                } else {
                    url = `/procurement/purchase-orders/${this.deleteForm.po_id}`;
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
                    bootstrap.Modal.getInstance(document.getElementById('deletePoModal')).hide();
                    this.selected = [];
                    this.allSelected = false;
                    this.fetchData();
                    this.showNotification(data.message || 'PO deleted successfully.', "success");
                } else {
                    this.showNotification(data.message || 'Failed to delete PO.', "error");
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
        restoreForm: {
            po_id: ''
        },

        downloadBulkPdf() {
            if (this.selected.length === 0) return;
            window.open(`/procurement/purchase-orders/bulk-pdf?ids=${this.selected.join(',')}`, '_blank');
        },

        openRestoreModal(poId) {
            this.isBulkRestore = false;
            this.restoreForm.po_id = poId;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('restorePoModal')).show();
        },

        openBulkRestoreModal() {
            if (this.selected.length === 0) return;
            this.isBulkRestore = true;
            this.restoreForm.po_id = '';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('restorePoModal')).show();
        },

        async submitRestoreForm() {
            if (this.isRestoring) return;
            this.isRestoring = true;
            try {
                let url, method, bodyData;
                if (this.isBulkRestore) {
                    url = `/procurement/purchase-orders/bulk`;
                    method = 'POST';
                    bodyData = {
                        action: 'restore',
                        ids: this.selected
                    };
                } else {
                    url = `/procurement/purchase-orders/${this.restoreForm.po_id}/restore`;
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
                    bootstrap.Modal.getInstance(document.getElementById('restorePoModal')).hide();
                    this.selected = [];
                    this.allSelected = false;
                    this.fetchData();
                    this.showNotification(data.message || 'PO restored successfully.', "success");
                } else {
                    this.showNotification(data.message || 'Failed to restore PO.', "error");
                }
            } catch (err) {
                console.error(err);
                this.showNotification('An error occurred.', "error");
            } finally {
                this.isRestoring = false;
            }
        },

        isApproving: false,
        isBulkApprove: false,
        approveForm: {
            po_id: ''
        },
        
        isRejecting: false,
        rejectForm: {
            po_id: '',
            rejection_reason: ''
        },

        openApproveModal(poId) {
            this.isBulkApprove = false;
            this.approveForm.po_id = poId;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('approvePoModal')).show();
        },

        openBulkApproveModal() {
            if (this.selected.length === 0) return;
            this.isBulkApprove = true;
            this.approveForm.po_id = '';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('approvePoModal')).show();
        },

        async submitApproveForm() {
            if (this.isApproving) return;
            this.isApproving = true;
            try {
                let url, bodyData;
                if (this.isBulkApprove) {
                    url = `/procurement/purchase-orders/bulk`;
                    bodyData = {
                        action: 'approve',
                        ids: this.selected
                    };
                } else {
                    url = `/procurement/purchase-orders/${this.approveForm.po_id}/approve`;
                    bodyData = {};
                }

                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                    body: JSON.stringify(bodyData)
                });
                const data = await response.json();
                if (response.ok) {
                    bootstrap.Modal.getInstance(document.getElementById('approvePoModal')).hide();
                    this.selected = [];
                    this.allSelected = false;
                    this.fetchData();
                    this.showNotification(data.message || 'PO approved successfully.', "success");
                } else {
                    this.showNotification(data.message || 'Failed to approve PO.', "error");
                }
            } catch (err) {
                console.error(err);
                this.showNotification('An error occurred.', "error");
            } finally {
                this.isApproving = false;
            }
        },

        isBulkReject: false,

        openRejectModal(po) {
            this.isBulkReject = false;
            this.rejectForm.po_id = po.id;
            this.rejectForm.rejection_reason = '';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('rejectPoModal')).show();
        },

        openBulkRejectModal() {
            this.isBulkReject = true;
            this.rejectForm.rejection_reason = '';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('rejectPoModal')).show();
        },

        async submitRejectForm() {
            if (this.isRejecting) return;
            this.isRejecting = true;
            try {
                let url, bodyData;
                if (this.isBulkReject) {
                    url = `/procurement/purchase-orders/bulk`;
                    bodyData = {
                        action: 'reject',
                        ids: this.selected,
                        rejection_reason: this.rejectForm.rejection_reason
                    };
                } else {
                    url = `/procurement/purchase-orders/${this.rejectForm.po_id}/reject`;
                    bodyData = {
                        rejection_reason: this.rejectForm.rejection_reason
                    };
                }

                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                    body: JSON.stringify(bodyData)
                });
                const data = await response.json();
                if (response.ok) {
                    bootstrap.Modal.getInstance(document.getElementById('rejectPoModal')).hide();
                    this.selected = [];
                    this.allSelected = false;
                    this.fetchData();
                    this.showNotification(data.message || 'PO rejected successfully.', "success");
                } else {
                    this.showNotification(data.message || 'Failed to reject PO.', "error");
                }
            } catch (err) {
                console.error(err);
                this.showNotification('An error occurred.', "error");
            } finally {
                this.isRejecting = false;
            }
        },

        async bulkAction(action) {
            if (this.selected.length === 0) return;
            
            try {
                const response = await fetch(`/procurement/purchase-orders/bulk`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                    body: JSON.stringify({
                        action: action,
                        ids: this.selected
                    })
                });
                const data = await response.json();
                if (response.ok) {
                    this.selected = [];
                    this.allSelected = false;
                    this.fetchData();
                    this.showNotification(data.message || 'Action applied successfully.', "success");
                } else {
                    this.showNotification(data.message || 'Action failed.', "error");
                }
            } catch (err) {
                console.error(err);
                this.showNotification('An error occurred.', "error");
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
