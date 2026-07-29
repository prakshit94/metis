@extends('layouts.app')

@section('title', 'Purchase Orders')
@section('page', 'procurement-purchase-orders')

@section('content')
<div class="purchase-orders-management" x-data="purchaseOrdersTable" x-cloak>

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
            <button type="button" class="btn btn-primary" @click.prevent="openCreateModal()">
                <i class="bi bi-plus-lg me-2"></i>Create PO
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
                        <select class="form-select form-select-sm" x-model="statusFilter" style="width:150px;">
                            <option value="">All Statuses</option>
                            <option value="draft">Draft</option>
                            <option value="sent">Sent</option>
                            <option value="partially_received">Partially Received</option>
                            <option value="received">Received</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <!-- Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-nowrap">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width:44px;">
                                <input type="checkbox" class="user-select-checkbox" @change="toggleAll($event.target.checked)">
                            </th>
                            <th style="width:120px;">PO Number</th>
                            <th>Supplier</th>
                            <th>Warehouse</th>
                            <th>Expected Date</th>
                            <th>Amount</th>
                            <th style="width:110px;">Status</th>
                            <th style="width:90px;" class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Loading State -->
                        <template x-if="isLoading">
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="spinner-border text-primary" role="status"></div>
                                    <p class="text-muted small mt-2 mb-0 fw-medium">Loading purchase orders…</p>
                                </td>
                            </tr>
                        </template>

                        <!-- Empty State -->
                        <template x-if="!isLoading && items.length === 0">
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-file-earmark-x fs-2 d-block mb-2"></i>
                                    No purchase orders found matching your criteria.
                                </td>
                            </tr>
                        </template>

                        <!-- Data Rows -->
                        <template x-for="item in items" :key="item.id">
                            <tr>
                                <td class="ps-3">
                                    <input type="checkbox" class="user-select-checkbox" :value="item.id">
                                </td>
                                <td>
                                    <span class="font-monospace fw-medium text-primary" x-text="item.po_number"></span>
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
                                    <span class="small text-muted" x-text="item.expected_delivery_date || '—'"></span>
                                </td>
                                <td>
                                    <span class="fw-medium">₹<span x-text="item.total_amount"></span></span>
                                </td>
                                <td>
                                    <span class="badge"
                                          :class="{
                                            'bg-secondary-subtle text-secondary': item.status === 'draft',
                                            'bg-primary-subtle text-primary': item.status === 'sent',
                                            'bg-warning-subtle text-warning': item.status === 'partially_received',
                                            'bg-success-subtle text-success': item.status === 'received',
                                            'bg-danger-subtle text-danger': item.status === 'cancelled'
                                          }">
                                          <span x-text="item.status.toUpperCase().replace('_', ' ')"></span>
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li><a class="dropdown-item" href="#" @click.prevent="viewItem(item)"><i class="bi bi-eye me-2"></i> View Details</a></li>
                                            <template x-if="item.status === 'draft' || item.status === 'sent' || item.status === 'partially_received'">
                                                <li><a class="dropdown-item text-success" href="#" @click.prevent="openReceiveModal(item)"><i class="bi bi-box-arrow-in-down me-2"></i> Receive Goods</a></li>
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
    <div class="modal fade" id="createPoModal" tabindex="-1" aria-labelledby="createPoModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <form @submit.prevent="submitCreateForm" autocomplete="off">
                    
                    {{-- GLOSSY STYLE HEADER --}}
                    <div class="modal-header bg-body-tertiary border-bottom d-flex align-items-center justify-content-between p-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px;">
                                <i class="bi bi-receipt fs-4"></i>
                            </div>
                            <div>
                                <h4 class="mb-0 fw-bold text-body">Create Purchase Order</h4>
                                <p class="mb-0 small text-muted">Draft a new purchase order for suppliers</p>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4 bg-body-tertiary">
                        <div class="row g-4">
                            
                            {{-- Basic Details --}}
                            <div class="col-12 position-relative" style="z-index: 100;">
                                <div class="card mb-0 border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom">
                                            <div class="bg-primary bg-opacity-10 text-primary rounded-2 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                                <i class="bi bi-info-circle fs-6"></i>
                                            </div>
                                            <h6 class="mb-0 fw-bold text-uppercase text-body" style="font-size: 11px; letter-spacing: 1px;">Order Information</h6>
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-md-3 position-relative" @click.away="showSupplierDropdown = false">
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Supplier *</label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-body text-muted border-end-0"><i class="bi bi-truck"></i></span>
                                                    <div class="form-control border-start-0 ps-0 fw-semibold d-flex align-items-center bg-body cursor-pointer" @click="showSupplierDropdown = !showSupplierDropdown; if(showSupplierDropdown) setTimeout(() => $refs.supplierSearch.focus(), 50)" style="font-size: 12px; min-height: 31px;">
                                                        <span class="flex-grow-1 text-truncate" x-text="selectedSupplierName || 'Select Supplier'"></span>
                                                        <i class="bi bi-chevron-down text-muted" style="font-size: 10px;"></i>
                                                    </div>
                                                </div>
                                                <div x-show="showSupplierDropdown" x-transition class="position-absolute w-100 bg-body border rounded shadow-lg mt-1" style="max-height: 250px; overflow-y: auto; z-index: 1050; left: 0;">
                                                    <div class="p-2 border-bottom position-sticky top-0 bg-body" style="z-index: 1051;">
                                                        <input x-ref="supplierSearch" type="text" class="form-control form-control-sm" x-model="supplierSearch" placeholder="Search..." style="font-size: 12px;">
                                                    </div>
                                                    <template x-for="supplier in filteredSuppliers" :key="supplier.id">
                                                        <div class="px-3 py-2 cursor-pointer custom-hover-bg d-flex align-items-center border-bottom border-light" @click="selectSupplier(supplier)">
                                                            <input type="checkbox" :checked="createForm.supplier_id === supplier.id" class="me-2" style="cursor: pointer; pointer-events: none;">
                                                            <span style="font-size: 12px;" x-text="supplier.name"></span>
                                                        </div>
                                                    </template>
                                                    <template x-if="filteredSuppliers.length === 0">
                                                        <div class="px-3 py-2 text-muted text-center" style="font-size: 11px;">No suppliers found</div>
                                                    </template>
                                                </div>
                                            </div>
                                            <div class="col-md-3 position-relative" @click.away="showWarehouseDropdown = false">
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Warehouse *</label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-body text-muted border-end-0"><i class="bi bi-building"></i></span>
                                                    <div class="form-control border-start-0 ps-0 fw-semibold d-flex align-items-center bg-body cursor-pointer" @click="showWarehouseDropdown = !showWarehouseDropdown; if(showWarehouseDropdown) setTimeout(() => $refs.warehouseSearch.focus(), 50)" style="font-size: 12px; min-height: 31px;">
                                                        <span class="flex-grow-1 text-truncate" x-text="selectedWarehouseName || 'Select Warehouse'"></span>
                                                        <i class="bi bi-chevron-down text-muted" style="font-size: 10px;"></i>
                                                    </div>
                                                </div>
                                                <div x-show="showWarehouseDropdown" x-transition class="position-absolute w-100 bg-body border rounded shadow-lg mt-1" style="max-height: 250px; overflow-y: auto; z-index: 1050; left: 0;">
                                                    <div class="p-2 border-bottom position-sticky top-0 bg-body" style="z-index: 1051;">
                                                        <input x-ref="warehouseSearch" type="text" class="form-control form-control-sm" x-model="warehouseSearch" placeholder="Search..." style="font-size: 12px;">
                                                    </div>
                                                    <template x-for="warehouse in filteredWarehouses" :key="warehouse.id">
                                                        <div class="px-3 py-2 cursor-pointer custom-hover-bg d-flex align-items-center border-bottom border-light" @click="selectWarehouse(warehouse)">
                                                            <input type="checkbox" :checked="createForm.warehouse_id === warehouse.id" class="me-2" style="cursor: pointer; pointer-events: none;">
                                                            <span style="font-size: 12px;" x-text="warehouse.name"></span>
                                                        </div>
                                                    </template>
                                                    <template x-if="filteredWarehouses.length === 0">
                                                        <div class="px-3 py-2 text-muted text-center" style="font-size: 11px;">No warehouses found</div>
                                                    </template>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Expected Delivery</label>
                                                <input type="date" class="form-control form-control-sm fw-semibold" x-model="createForm.expected_delivery_date" style="font-size: 12px;">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Notes</label>
                                                <input type="text" class="form-control form-control-sm fw-semibold" x-model="createForm.notes" placeholder="Optional notes..." style="font-size: 12px;">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Line Items --}}
                            <div class="col-12 position-relative" style="z-index: 50;">
                                <div class="card mb-0 border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom">
                                            <div class="bg-indigo text-indigo bg-opacity-10 rounded-2 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; color: #6610f2;">
                                                <i class="bi bi-box-seam fs-6"></i>
                                            </div>
                                            <h6 class="mb-0 fw-bold text-uppercase text-body" style="font-size: 11px; letter-spacing: 1px;">Line Items</h6>
                                        </div>
                                        
                                        <div class="bg-body rounded border shadow-sm" style="overflow: visible; padding-bottom: 150px;">
                                            <table class="table table-hover align-middle mb-0 text-nowrap">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th class="text-uppercase text-muted fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">Product *</th>
                                                        <th class="text-uppercase text-muted fw-bold" style="font-size: 10px; letter-spacing: 0.5px; width: 120px;">Qty *</th>
                                                        <th class="text-uppercase text-muted fw-bold" style="font-size: 10px; letter-spacing: 0.5px; width: 150px;">Unit Price *</th>
                                                        <th class="text-uppercase text-muted fw-bold" style="font-size: 10px; letter-spacing: 0.5px; width: 150px;">Total</th>
                                                        <th style="width: 50px;"></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <template x-for="(item, index) in createForm.items" :key="index">
                                                        <tr>
                                                            <td class="position-relative" @click.away="item._showDropdown = false">
                                                                <div class="form-control form-control-sm fw-semibold d-flex align-items-center bg-body cursor-pointer position-relative" @click="item._showDropdown = !item._showDropdown; if(item._showDropdown) setTimeout(() => document.getElementById('prodSearch_'+index).focus(), 50)" style="font-size: 12px; min-height: 31px; padding-right: 25px;">
                                                                    <template x-if="item.product_id && getProductObj(item.product_id)?.image">
                                                                        <img :src="getProductObj(item.product_id).image" class="rounded object-fit-cover shadow-sm border border-secondary border-opacity-25 me-2 flex-shrink-0" style="width: 18px; height: 18px;" alt="">
                                                                    </template>
                                                                    <span class="flex-grow-1 text-truncate" x-text="getProductName(item.product_id) || 'Select Product'"></span>
                                                                    <i class="bi bi-chevron-down text-muted position-absolute" style="font-size: 10px; right: 8px;"></i>
                                                                </div>
                                                                <div x-show="item._showDropdown" x-transition class="position-absolute bg-body border rounded shadow-lg mt-1" style="max-height: 250px; overflow-y: auto; z-index: 1060; left: 0; min-width: 250px;">
                                                                    <div class="p-2 border-bottom position-sticky top-0 bg-body" style="z-index: 1061;">
                                                                        <input :id="'prodSearch_'+index" type="text" class="form-control form-control-sm" x-model="item._search" placeholder="Search product..." style="font-size: 12px;">
                                                                    </div>
                                                                    <template x-for="product in filteredProducts(item)" :key="product.id">
                                                                        <div class="px-3 py-2 cursor-pointer custom-hover-bg d-flex align-items-center border-bottom border-light" @click="selectProduct(item, product)">
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
                                                            <td class="text-end fw-bold text-primary" style="font-size: 13px;">
                                                                ₹<span x-text="(item.quantity * item.unit_price).toFixed(2)"></span>
                                                            </td>
                                                            <td class="text-center">
                                                                <button type="button" class="btn btn-sm btn-link text-danger" @click="removeLineItem(index)" x-show="createForm.items.length > 1" title="Remove Item">
                                                                    <i class="bi bi-trash-fill fs-5"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    </template>
                                                </tbody>
                                                <tfoot class="table-light">
                                                    <tr>
                                                        <td colspan="5" class="py-2">
                                                            <button type="button" class="btn btn-sm btn-outline-primary fw-bold px-3 py-1" style="font-size: 11px; letter-spacing: 0.5px;" @click="addLineItem()">
                                                                <i class="bi bi-plus-circle-fill me-1"></i> ADD ROW
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Form Actions --}}
                        <div class="d-flex justify-content-end gap-3 mt-4 pt-3 border-top">
                            <button type="button" data-bs-dismiss="modal" class="btn text-muted fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 1px;">Cancel</button>
                            <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" :disabled="isSubmitting">
                                <span x-show="isSubmitting" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                <span>Create Purchase Order</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- GRN Receive Goods Modal -->
    <div class="modal fade" id="receiveGoodsModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <form @submit.prevent="submitReceiveForm" autocomplete="off">
                    <div class="modal-header border-bottom border-light" style="background: linear-gradient(135deg, rgba(var(--bs-success-rgb), 0.1) 0%, rgba(var(--bs-success-rgb), 0.05) 100%);">
                        <h5 class="modal-title fw-bold text-success d-flex align-items-center gap-2">
                            <i class="bi bi-box-arrow-in-down fs-4"></i> Receive Goods
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    
                    <div class="modal-body p-4 bg-light">
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
                                        
                                        <div class="bg-body rounded border shadow-sm">
                                            <table class="table table-hover align-middle mb-0 text-nowrap">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th class="text-uppercase text-muted fw-bold" style="font-size: 10px;">Product</th>
                                                        <th class="text-uppercase text-muted fw-bold text-end" style="font-size: 10px;">Ordered Qty</th>
                                                        <th class="text-uppercase text-muted fw-bold text-end" style="font-size: 10px;">Already Rcvd</th>
                                                        <th class="text-uppercase text-muted fw-bold text-end" style="font-size: 10px; width: 120px;">Accepted Qty</th>
                                                        <th class="text-uppercase text-muted fw-bold text-end" style="font-size: 10px; width: 120px;">Rejected Qty</th>
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
                                                        </tr>
                                                    </template>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-3 mt-4 pt-3 border-top">
                            <button type="button" data-bs-dismiss="modal" class="btn text-muted fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 1px;">Cancel</button>
                            <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm" :disabled="isReceiving">
                                <span x-show="isReceiving" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                <span>Process GRN</span>
                            </button>
                        </div>
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
        currentPage: 1,
        totalPages: 1,
        
        init() {
            this.fetchData();
            this.$watch('searchQuery', () => { this.currentPage = 1; this.fetchData(); });
            this.$watch('statusFilter', () => { this.currentPage = 1; this.fetchData(); });
        },
        
        fetchData() {
            this.isLoading = true;
            let url = new URL(window.location.href);
            url.searchParams.set('page', this.currentPage);
            if (this.searchQuery) url.searchParams.set('search', this.searchQuery);
            if (this.statusFilter) url.searchParams.set('status', this.statusFilter);
            
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

        toggleAll(checked) {
            // Checkbox logic stub
        },
        
        suppliersList: {!! $suppliers->map(function($s) { return ['id' => $s->id, 'name' => $s->company_name ?: $s->firstname]; })->toJson() !!},
        warehousesList: {!! $warehouses->toJson() !!},
        productsList: {!! $products->map(function($p) { return ['id' => $p->id, 'name' => $p->name, 'sku' => $p->sku, 'image' => $p->image_url]; })->toJson() !!},
        
        supplierSearch: '',
        showSupplierDropdown: false,
        get filteredSuppliers() {
            if (!this.supplierSearch) return this.suppliersList;
            return this.suppliersList.filter(s => (s.name || '').toLowerCase().includes(this.supplierSearch.toLowerCase()));
        },
        get selectedSupplierName() {
            const s = this.suppliersList.find(s => s.id === this.createForm.supplier_id);
            return s ? (s.name || '') : '';
        },
        selectSupplier(supplier) {
            this.createForm.supplier_id = supplier.id;
            this.showSupplierDropdown = false;
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
            if (!item._search) return this.productsList;
            return this.productsList.filter(p => (p.name || '').toLowerCase().includes(item._search.toLowerCase()) || (p.sku && p.sku.toLowerCase().includes(item._search.toLowerCase())));
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
            item._showDropdown = false;
        },

        createForm: {
            supplier_id: '',
            warehouse_id: '',
            expected_delivery_date: '',
            notes: '',
            items: [{ product_id: '', quantity: 1, unit_price: 0, _showDropdown: false, _search: '' }]
        },
        isSubmitting: false,

        openCreateModal() {
            this.createForm = {
                supplier_id: '', warehouse_id: '', expected_delivery_date: '', notes: '',
                items: [{ product_id: '', quantity: 1, unit_price: 0, _showDropdown: false, _search: '' }]
            };
            this.supplierSearch = '';
            this.warehouseSearch = '';
            new bootstrap.Modal(document.getElementById('createPoModal')).show();
        },
        
        addLineItem() {
            this.createForm.items.push({ product_id: '', quantity: 1, unit_price: 0, _showDropdown: false, _search: '' });
        },
        
        removeLineItem(index) {
            if (this.createForm.items.length > 1) {
                this.createForm.items.splice(index, 1);
            }
        },

        async submitCreateForm() {
            this.isSubmitting = true;
            try {
                const response = await fetch('{{ route("procurement.purchase-orders.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(this.createForm)
                });
                
                const data = await response.json();
                
                if (response.ok) {
                    bootstrap.Modal.getInstance(document.getElementById('createPoModal')).hide();
                    this.fetchData();
                } else {
                    alert(data.message || 'Validation failed. Please check your inputs.');
                }
            } catch (error) {
                console.error(error);
                alert('An error occurred. Please try again.');
            } finally {
                this.isSubmitting = false;
            }
        },
        
        viewItem(item) {
            alert('View PO details flow will go here for ' + item.po_number);
        },
        
        exportData() {
            alert('Exporting data...');
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
                };
            });
            new bootstrap.Modal(document.getElementById('receiveGoodsModal')).show();
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
                    if (window.AdminApp && window.AdminApp.notificationManager) {
                        window.AdminApp.notificationManager.success(data.message || 'GRN processed successfully.');
                    } else {
                        alert(data.message || 'GRN processed successfully.');
                    }
                } else {
                    alert(data.message || 'Failed to process GRN.');
                }
            } catch (err) {
                console.error(err);
                alert('An error occurred.');
            } finally {
                this.isReceiving = false;
            }
        }
    }));
});
</script>
@endpush
