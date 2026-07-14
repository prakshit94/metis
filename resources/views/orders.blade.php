@extends('layouts.app')

@section('title', 'Order Management')
@section('page', 'orders')

@section('content')
<div class="order-management" x-data="orderTable" x-init="
    productsList = {{ json_encode($productsList->toArray()) }};
    statesList = {{ json_encode($statesList) }};
    carriersList = {{ json_encode($carriersList) }};
    allowedFilterStatuses = {{ json_encode($statusesList) }};
    init();
">
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4 mb-lg-5 mb-xl-6">
    <div>
        <h1 class="h3 mb-0">Order Management</h1>
        <p class="text-muted mb-0">Track orders, manage fulfillment, and analyze sales</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary" @click="exportOrders()">
            <i class="bi bi-download me-2"></i>Export
        </button>
        <div class="dropdown">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-upload me-2"></i>Import
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#" @click.prevent="document.getElementById('import-file').click()">
                    <i class="bi bi-file-earmark-arrow-up me-2"></i>Upload CSV
                </a></li>
                <li><a class="dropdown-item" href="{{ route('orders.import-template') }}">
                    <i class="bi bi-file-earmark-arrow-down me-2"></i>Download Template
                </a></li>
            </ul>
        </div>
        <a href="{{ route('orders.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-2"></i>New Order
        </a>
    </div>
</div>

<!-- Hidden CSV Import Form -->
<form id="import-form" action="{{ route('orders.import') }}" method="POST" enctype="multipart/form-data" class="d-none">
    @csrf
    <input type="file" name="file" id="import-file" accept=".csv,.txt" @change="handleImportFileSelect($event)">
</form>

<!-- Order Stats Widgets -->
<div class="row g-4 g-lg-5 g-xl-6 mb-5 mb-lg-5 mb-xl-6">
    <div class="col-xl-3 col-lg-6">
        <div class="card stats-card">
            <div class="card-body p-3 p-lg-4">
                <div class="d-flex align-items-center">
                    <div class="stats-icon bg-primary bg-opacity-10 text-primary me-3">
                        <i class="bi bi-bag-check"></i>
                    </div>
                    <div>
                        <p class="h6 mb-0 text-muted">Total Orders</p>
                        <div class="h3 mb-0" aria-live="polite"><span x-text="stats.total"></span></div>
                        <small class="text-success-emphasis">
                            <i class="bi bi-arrow-up"></i> +12% from last month
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6">
        <div class="card stats-card">
            <div class="card-body p-3 p-lg-4">
                <div class="d-flex align-items-center">
                    <div class="stats-icon bg-warning bg-opacity-10 text-warning me-3">
                        <i class="bi bi-clock"></i>
                    </div>
                    <div>
                        <p class="h6 mb-0 text-muted">Pending</p>
                        <div class="h3 mb-0" aria-live="polite"><span x-text="stats.pending"></span></div>
                        <small class="text-warning">
                            <i class="bi bi-exclamation-circle"></i> Needs attention
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6">
        <div class="card stats-card">
            <div class="card-body p-3 p-lg-4">
                <div class="d-flex align-items-center">
                    <div class="stats-icon bg-info bg-opacity-10 text-info me-3">
                        <i class="bi bi-truck"></i>
                    </div>
                    <div>
                        <p class="h6 mb-0 text-muted">Shipped / Dispatched</p>
                        <div class="h3 mb-0" aria-live="polite"><span x-text="stats.dispatched"></span></div>
                        <small class="text-info">
                            <i class="bi bi-arrow-right"></i> In transit
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6">
        <div class="card stats-card">
            <div class="card-body p-3 p-lg-4">
                <div class="d-flex align-items-center">
                    <div class="stats-icon bg-success bg-opacity-10 text-success me-3">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div>
                        <p class="h6 mb-0 text-muted">Revenue</p>
                        <div class="h3 mb-0" aria-live="polite"><span x-text="`$${stats.revenue.toLocaleString()}`"></span></div>
                        <small class="text-success-emphasis">
                            <i class="bi bi-arrow-up"></i> +8% from last week
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-4 g-lg-5 mb-5 mb-lg-5 mb-xl-6">
    <!-- Order Trends Chart -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="h5 card-title mb-0">Order Trends</h2>
                <div class="btn-group btn-group-sm" role="group">
                    <input type="radio" class="btn-check" name="trendsPeriod" id="trends7d" autocomplete="off" checked>
                    <label class="btn btn-outline-secondary" for="trends7d">7D</label>
                    <input type="radio" class="btn-check" name="trendsPeriod" id="trends30d" autocomplete="off">
                    <label class="btn btn-outline-secondary" for="trends30d">30D</label>
                    <input type="radio" class="btn-check" name="trendsPeriod" id="trends90d" autocomplete="off">
                    <label class="btn btn-outline-secondary" for="trends90d">90D</label>
                </div>
            </div>
            <div class="card-body p-3 p-lg-4">
                <div id="orderTrendsChart" style="height: 300px;"></div>
            </div>
        </div>
    </div>

    <!-- Order Status Distribution -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <h2 class="h5 card-title mb-0">Order Status</h2>
            </div>
            <div class="card-body p-3 p-lg-4">
                <div id="statusChart" style="height: 200px;"></div>
                <div class="mt-3">
                    <template x-for="status in statusStats" :key="status.name">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small" x-text="status.name"></span>
                            <div class="d-flex align-items-center">
                                <span class="small text-muted me-2" x-text="`${status.percentage}%`"></span>
                                <span class="small fw-medium" x-text="status.count"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Orders Table -->
<div class="card">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="h5 card-title mb-0">Orders Directory</h2>
            </div>
            <div class="col-auto">
                <div class="d-flex flex-wrap gap-2 justify-content-end">
                    <!-- Search -->
                    <div class="position-relative">
                        <input type="search" 
                               class="form-control form-control-sm" 
                               placeholder="Search orders..."
                               x-model="searchQuery"
                               @input="filterOrders()"
                               style="width: 200px;">
                        <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted"></i>
                    </div>
                    
                    <!-- Status Filter -->
                    <select class="form-select form-select-sm" 
                            x-model="statusFilter" 
                            @change="filterOrders()"
                            style="width: 150px;">
                        <option value="">All Statuses</option>
                        <template x-for="status in allowedFilterStatuses" :key="status">
                            <option :value="status" x-text="status.charAt(0).toUpperCase() + status.slice(1).replace(/_/g, ' ')"></option>
                        </template>
                    </select>
                    
                    <!-- Date Range -->
                    <select class="form-select form-select-sm" 
                            x-model="dateFilter" 
                            @change="filterOrders()"
                            style="width: 150px;">
                        <option value="">All Dates</option>
                        <option value="today">Today</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                    </select>

                    <!-- Advanced Filters Trigger -->
                    <button class="btn btn-sm"
                            :class="hasActiveAdvancedFilters() ? 'btn-primary' : 'btn-outline-secondary'"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#advancedFilters"
                            aria-expanded="false">
                        <i class="bi bi-funnel me-1"></i>Filters
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Collapsible Advanced Filters Drawer -->
    <div class="collapse" id="advancedFilters">
        <div class="p-3 bg-body-tertiary border-top border-bottom border-secondary-subtle">
            <div class="row g-3">
                <!-- Product Filter -->
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-body-secondary">Product</label>
                    <select class="form-select form-select-sm" x-model="productFilter" @change="filterOrders()">
                        <option value="">All Products</option>
                        <template x-for="product in productsList" :key="product.id">
                            <option :value="product.id" x-text="`${product.name} (${product.sku})`"></option>
                        </template>
                    </select>
                </div>
                
                <!-- Fulfillment Filter -->
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-body-secondary">Fulfillment Status</label>
                    <select class="form-select form-select-sm" x-model="fulfillmentFilter" @change="filterOrders()">
                        <option value="">All</option>
                        <option value="fulfillable">Fulfillable</option>
                        <option value="unfulfillable">Unfulfillable</option>
                    </select>
                </div>

                <!-- Carrier Filter -->
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-body-secondary">Carrier</label>
                    <select class="form-select form-select-sm" x-model="carrierFilter" @change="filterOrders()">
                        <option value="">All Carriers</option>
                        <template x-for="carrier in carriersList" :key="carrier">
                            <option :value="carrier" x-text="carrier"></option>
                        </template>
                    </select>
                </div>

                <!-- Date Range From -->
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-body-secondary">From Date</label>
                    <input type="date" class="form-control form-control-sm" x-model="fromDate" @change="filterOrders()">
                </div>

                <!-- Date Range To -->
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-body-secondary">To Date</label>
                    <input type="date" class="form-control form-control-sm" x-model="toDate" @change="filterOrders()">
                </div>

                <!-- State Filter -->
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-body-secondary">State</label>
                    <select class="form-select form-select-sm" x-model="stateFilter" @change="filterOrders()">
                        <option value="">All States</option>
                        <template x-for="state in statesList" :key="state">
                            <option :value="state" x-text="state"></option>
                        </template>
                    </select>
                </div>

                <!-- District Filter -->
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-body-secondary">District</label>
                    <select class="form-select form-select-sm" x-model="districtFilter" @change="filterOrders()">
                        <option value="">All Districts</option>
                        <template x-for="district in districtsList" :key="district">
                            <option :value="district" x-text="district"></option>
                        </template>
                    </select>
                </div>

                <!-- Taluka Filter -->
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-body-secondary">Taluka</label>
                    <select class="form-select form-select-sm" x-model="talukaFilter" @change="filterOrders()">
                        <option value="">All Talukas</option>
                        <template x-for="taluka in talukasList" :key="taluka">
                            <option :value="taluka" x-text="taluka"></option>
                        </template>
                    </select>
                </div>

                <!-- Village Filter -->
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-body-secondary">Village</label>
                    <select class="form-select form-select-sm" x-model="villageFilter" @change="filterOrders()">
                        <option value="">All Villages</option>
                        <template x-for="village in villagesList" :key="village">
                            <option :value="village" x-text="village"></option>
                        </template>
                    </select>
                </div>

                <!-- Reset Filters -->
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" class="btn btn-sm btn-outline-secondary w-100 d-inline-flex align-items-center justify-content-center" @click="clearFilters()">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card-body p-0">
        <!-- Bulk Actions Bar -->
        <div class="bulk-actions-bar p-3 bg-primary bg-opacity-10 border-bottom border-primary border-opacity-25" x-show="selectedOrders.length > 0" x-transition>
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-check-circle-fill text-primary"></i>
                    <span class="fw-medium text-primary">
                        <strong x-text="selectedOrders.length"></strong> order(s) selected
                    </span>
                    <span class="badge bg-primary bg-opacity-25 text-primary small d-none d-md-inline"
                          x-text="'Next: ' + [
                            bulkAvailableActions.canConfirm ? 'Confirm' : null,
                            bulkAvailableActions.canProcess ? 'Process' : null,
                            bulkAvailableActions.canReadyToShip ? 'Ready to Ship' : null,
                            bulkAvailableActions.canDispatch ? 'Dispatch' : null,
                            bulkAvailableActions.canDeliver ? 'Deliver' : null,
                          ].filter(Boolean).join(', ') || 'No transitions'">
                    </span>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    {{-- Next-step lifecycle buttons - only shown when relevant --}}
                    <button class="btn btn-sm btn-primary"
                            x-show="bulkAvailableActions.canConfirm"
                            x-transition
                            @click="bulkUpdateStatus('confirmed')"
                            title="Move pending orders → Confirmed">
                        <i class="bi bi-check-circle me-1"></i>Confirm
                    </button>
                    <button class="btn btn-sm btn-secondary"
                            x-show="bulkAvailableActions.canProcess"
                            x-transition
                            @click="bulkUpdateStatus('processing')"
                            title="Move confirmed orders → Processing">
                        <i class="bi bi-arrow-clockwise me-1"></i>Process
                    </button>
                    <button class="btn btn-sm btn-info text-white"
                            x-show="bulkAvailableActions.canReadyToShip"
                            x-transition
                            @click="bulkUpdateStatus('ready_to_ship')"
                            title="Move processing orders → Ready to Ship">
                        <i class="bi bi-truck me-1"></i>Ready to Ship
                    </button>
                    <button class="btn btn-sm btn-warning"
                            x-show="bulkAvailableActions.canDispatch"
                            x-transition
                            @click="bulkUpdateStatus('dispatched')"
                            title="Move ready-to-ship orders → Dispatched">
                        <i class="bi bi-box-arrow-right me-1"></i>Dispatch
                    </button>
                    <button class="btn btn-sm btn-success"
                            x-show="bulkAvailableActions.canDeliver"
                            x-transition
                            @click="bulkUpdateStatus('delivered')"
                            title="Move dispatched orders → Delivered">
                        <i class="bi bi-check2-all me-1"></i>Deliver
                    </button>

                    {{-- Separator before non-lifecycle actions --}}
                    <div class="vr" x-show="bulkAvailableActions.canCancel || true"></div>

                    {{-- Cancel (always shown if any selected order is cancellable) --}}
                    <button class="btn btn-sm btn-outline-danger"
                            x-show="bulkAvailableActions.canCancel"
                            x-transition
                            @click="bulkUpdateStatus('cancelled')"
                            title="Cancel selected orders">
                        <i class="bi bi-x-circle me-1"></i>Cancel
                    </button>

                    {{-- Verification & Print (always available for any selection) --}}
                    <button class="btn btn-sm btn-outline-warning" @click="openBulkVerificationModal()"
                            title="Log a verification call for selected orders">
                        <i class="bi bi-telephone me-1"></i>Verify Call
                    </button>
                    <div class="dropdown d-inline-block">
                        <button class="btn btn-sm btn-dark dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-printer me-1"></i>Print
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#" @click.prevent="bulkPrint('invoice')">
                                <i class="bi bi-file-pdf me-2"></i>Invoice PDF
                            </a></li>
                            <li><a class="dropdown-item" href="#" @click.prevent="bulkPrint('cod')">
                                <i class="bi bi-file-earmark-pdf me-2"></i>COD PDF
                            </a></li>
                        </ul>
                    </div>

                    {{-- Deselect all --}}
                    <button class="btn btn-sm btn-outline-secondary" @click="selectedOrders = []"
                            title="Clear selection">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40px;">
                            <input type="checkbox" 
                                   class="form-check-input" 
                                   @change="toggleAll($event.target.checked)"
                                   :checked="selectedOrders.length === orders.length && orders.length > 0">
                        </th>
                        <th scope="col"
                            role="button"
                            tabindex="0"
                            @click="sortBy('order_no')"
                            @keydown.enter.prevent="sortBy('order_no')"
                            @keydown.space.prevent="sortBy('order_no')"
                            :aria-sort="sortField === 'order_no' ? (sortDirection === 'asc' ? 'ascending' : 'descending') : 'none'"
                            class="sortable">
                            Order #
                            <i class="bi bi-arrow-up" x-show="sortField === 'order_no' && sortDirection === 'asc'" aria-hidden="true"></i>
                            <i class="bi bi-arrow-down" x-show="sortField === 'order_no' && sortDirection === 'desc'" aria-hidden="true"></i>
                        </th>
                        <th scope="col">Placed By</th>
                        <th scope="col">Items</th>
                        <th scope="col"
                            role="button"
                            tabindex="0"
                            @click="sortBy('net_amount')"
                            @keydown.enter.prevent="sortBy('net_amount')"
                            @keydown.space.prevent="sortBy('net_amount')"
                            :aria-sort="sortField === 'net_amount' ? (sortDirection === 'asc' ? 'ascending' : 'descending') : 'none'"
                            class="sortable">
                            Total
                            <i class="bi bi-arrow-up" x-show="sortField === 'net_amount' && sortDirection === 'asc'" aria-hidden="true"></i>
                            <i class="bi bi-arrow-down" x-show="sortField === 'net_amount' && sortDirection === 'desc'" aria-hidden="true"></i>
                        </th>
                        <th scope="col">Status</th>
                        <th scope="col"
                            role="button"
                            tabindex="0"
                            @click="sortBy('order_date')"
                            @keydown.enter.prevent="sortBy('order_date')"
                            @keydown.space.prevent="sortBy('order_date')"
                            :aria-sort="sortField === 'order_date' ? (sortDirection === 'asc' ? 'ascending' : 'descending') : 'none'"
                            class="sortable">
                            Order Placed
                            <i class="bi bi-arrow-up" x-show="sortField === 'order_date' && sortDirection === 'asc'" aria-hidden="true"></i>
                            <i class="bi bi-arrow-down" x-show="sortField === 'order_date' && sortDirection === 'desc'" aria-hidden="true"></i>
                        </th>
                        <th style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="order in orders" :key="order.id">
                        <tr :class="{ 'selected': selectedOrders.includes(order.id) }">
                            <td>
                                <input type="checkbox" 
                                       class="form-check-input" 
                                       :value="order.id"
                                       :checked="selectedOrders.includes(order.id)"
                                       @change="toggleOrder(order.id)">
                            </td>
                            <td>
                                <div class="fw-medium" x-text="order.orderNumber"></div>
                                <small class="text-muted" x-text="'ID: ' + order.id"></small>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img :src="order.createdBy.avatar"
                                         class="rounded-circle me-2"
                                         width="32"
                                         height="32"
                                         :alt="order.createdBy.name">
                                    <div>
                                        <div class="fw-medium small" x-text="order.createdBy.name"></div>
                                        <small class="text-muted" x-text="order.createdBy.email"></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="order-items small">
                                    <div class="fw-medium" x-text="order.itemCount + ' item' + (order.itemCount > 1 ? 's' : '')"></div>
                                    <small class="text-muted" x-text="order.items.length > 0 ? order.items[0].name + (order.itemCount > 1 ? ' +' + (order.itemCount - 1) + ' more' : '') : '—'"></small>
                                </div>
                            </td>
                            <td class="fw-medium small" x-text="`$${order.total}`"></td>
                            <td>
                                <span class="badge small" 
                                      :style="`background-color: ${getStatusColor(order.status)}; color: #fff`"
                                      x-text="order.status.charAt(0).toUpperCase() + order.status.slice(1).replace(/_/g, ' ')"></span>
                            </td>
                            <td>
                                <div class="small fw-medium" x-text="order.orderDate ? new Date(order.orderDate).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }) : '—'"></div>
                                <small class="text-muted" x-text="order.orderDate ? new Date(order.orderDate).toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', hour12: true }) : ''"></small>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                            type="button" 
                                            data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="#" @click.prevent="viewOrder(order)">
                                            <i class="bi bi-eye me-2"></i>View Details
                                        </a></li>
                                        
                                        <!-- Context Actions -->
                                        <template x-if="order.status === 'pending'">
                                            <li><a class="dropdown-item" href="#" @click.prevent="confirmOrder(order)">
                                                <i class="bi bi-check-circle me-2"></i>Confirm Order
                                            </a></li>
                                        </template>
                                        <template x-if="order.status === 'confirmed'">
                                            <li><a class="dropdown-item" href="#" @click.prevent="processOrder(order)">
                                                <i class="bi bi-gear me-2"></i>Process Order
                                            </a></li>
                                        </template>
                                        <template x-if="order.status === 'processing'">
                                            <li><a class="dropdown-item" href="#" @click.prevent="openShipModal(order)">
                                                <i class="bi bi-truck me-2"></i>Ship (Ready to Ship)
                                            </a></li>
                                        </template>
                                        <template x-if="order.status === 'ready_to_ship'">
                                            <li><a class="dropdown-item" href="#" @click.prevent="dispatchOrder(order)">
                                                <i class="bi bi-send me-2"></i>Dispatch
                                            </a></li>
                                        </template>
                                        <template x-if="order.status === 'dispatched' || order.status === 'shipped'">
                                            <li><a class="dropdown-item" href="#" @click.prevent="deliverOrder(order)">
                                                <i class="bi bi-check2-all me-2"></i>Deliver
                                            </a></li>
                                        </template>
                                        <template x-if="['delivered', 'dispatched', 'shipped'].includes(order.status)">
                                            <li><a class="dropdown-item text-warning" href="#" @click.prevent="returnOrder(order)">
                                                <i class="bi bi-arrow-return-left me-2"></i>Return Order
                                            </a></li>
                                        </template>
                                        <template x-if="['pending', 'confirmed', 'processing', 'ready_to_ship'].includes(order.status)">
                                            <li><a class="dropdown-item text-danger" href="#" @click.prevent="cancelOrder(order)">
                                                <i class="bi bi-x-circle me-2"></i>Cancel Order
                                            </a></li>
                                        </template>
                                        <li><a class="dropdown-item" href="#" @click.prevent="revertStatus(order)">
                                            <i class="bi bi-arrow-left-right me-2"></i>Revert Status
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="#" @click.prevent="openVerificationModal(order)">
                                            <i class="bi bi-telephone me-2"></i>Log Verification
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" @click.prevent="printInvoice(order)">
                                            <i class="bi bi-file-pdf me-2"></i>Print Invoice
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" @click.prevent="printCOD(order)">
                                            <i class="bi bi-file-earmark-pdf me-2"></i>Print COD Receipt
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" @click.prevent="printReceipt(order)">
                                            <i class="bi bi-receipt me-2"></i>Print Receipt
                                        </a></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <template x-if="orders.length === 0">
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No orders found matching current criteria.
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center p-3 border-top">
            <div class="text-muted small">
                Showing <span x-text="(currentPage - 1) * itemsPerPage + 1"></span> to 
                <span x-text="Math.min(currentPage * itemsPerPage, totalOrders)"></span> of 
                <span x-text="totalOrders"></span> results
            </div>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item" :class="{ 'disabled': currentPage === 1 }">
                        <a class="page-link" href="#" @click.prevent="goToPage(currentPage - 1)">Previous</a>
                    </li>
                    <template x-for="(page, index) in visiblePages" :key="`page-${index}`">
                        <li class="page-item" :class="{ 'active': page === currentPage, 'disabled': page === '...' }">
                            <a class="page-link" href="#" @click.prevent="page !== '...' && goToPage(page)" x-text="page"></a>
                        </li>
                    </template>
                    <li class="page-item" :class="{ 'disabled': currentPage === totalPages }">
                        <a class="page-link" href="#" @click.prevent="goToPage(currentPage + 1)">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- ═══════════════════════ Order Details Modal ═══════════════════════════ -->
<div class="modal fade order-detail-modal" id="orderDetailModal" tabindex="-1" aria-labelledby="orderDetailModalLabel">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content" x-show="selectedOrder">
            <template x-if="selectedOrder">
                <div class="d-flex flex-column h-100">
                    <div class="modal-header border-bottom-0 pb-0">
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="modal-title fw-bold" id="orderDetailModalLabel">
                                <i class="bi bi-receipt me-2 text-primary"></i>Order Details: <span x-text="selectedOrder.orderNumber"></span>
                            </h5>
                            <span class="badge" 
                                  :style="`background-color: ${getStatusColor(selectedOrder.status)}; color: #fff`"
                                  x-text="selectedOrder.status.charAt(0).toUpperCase() + selectedOrder.status.slice(1).replace(/_/g, ' ')"></span>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <div class="row g-4">
                            <!-- Left Column: Details & Items -->
                            <div class="col-lg-8">
                                <!-- Order Overview -->
                                <div class="card mb-4 border-light-subtle bg-body-tertiary">
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <p class="small text-muted mb-1">Order Date</p>
                                                <p class="fw-semibold mb-0" x-text="selectedOrder.orderDate ? new Date(selectedOrder.orderDate).toLocaleString() : 'N/A'"></p>
                                            </div>
                                            <div class="col-md-4">
                                                <p class="small text-muted mb-1">Order Type</p>
                                                <p class="fw-semibold mb-0 text-capitalize" x-text="selectedOrder.type || 'sale'"></p>
                                            </div>
                                            <div class="col-md-4">
                                                <p class="small text-muted mb-1">Payment Method</p>
                                                <p class="fw-semibold mb-0" x-text="selectedOrder.paymentMethod || 'N/A'"></p>
                                            </div>
                                            <div class="col-md-4">
                                                <p class="small text-muted mb-1">Subtotal</p>
                                                <p class="fw-semibold mb-0" x-text="`$${selectedOrder.subtotal.toFixed(2)}`"></p>
                                            </div>
                                            <div class="col-md-4">
                                                <p class="small text-muted mb-1">Discount</p>
                                                <p class="fw-semibold mb-0 text-success" x-text="`-$${selectedOrder.discountTotal.toFixed(2)}`"></p>
                                            </div>
                                            <div class="col-md-4">
                                                <p class="small text-muted mb-1">Tax</p>
                                                <p class="fw-semibold mb-0" x-text="`$${selectedOrder.taxTotal.toFixed(2)}`"></p>
                                            </div>
                                            <div class="col-md-4">
                                                <p class="small text-muted mb-1">Net Total</p>
                                                <p class="fw-semibold mb-0 text-primary" x-text="`$${selectedOrder.total.toFixed(2)}`"></p>
                                            </div>
                                            <div class="col-md-4">
                                                <p class="small text-muted mb-1">Created By</p>
                                                <p class="fw-semibold mb-0" x-text="selectedOrder.createdBy.name"></p>
                                            </div>
                                            <div class="col-md-4">
                                                <p class="small text-muted mb-1">Updated By</p>
                                                <p class="fw-semibold mb-0" x-text="selectedOrder.updatedBy"></p>
                                            </div>
                                            <template x-if="selectedOrder.couponCode">
                                                <div class="col-md-4">
                                                    <p class="small text-muted mb-1">Coupon Code</p>
                                                    <p class="fw-semibold mb-0" x-text="selectedOrder.couponCode"></p>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <!-- Customer Info -->
                                <div class="card mb-4 border-light-subtle bg-body-tertiary">
                                    <div class="card-body">
                                        <h6 class="fw-bold mb-3 text-uppercase text-muted small">Customer Details</h6>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <p class="small text-muted mb-1">Customer Name</p>
                                                <p class="fw-semibold mb-0" x-text="selectedOrder.customer.name"></p>
                                            </div>
                                            <div class="col-md-6">
                                                <p class="small text-muted mb-1">Email / Phone</p>
                                                <p class="fw-semibold mb-0" x-text="`${selectedOrder.customer.email} / ${selectedOrder.customer.phone || 'N/A'}`"></p>
                                            </div>
                                            <div class="col-md-12">
                                                <p class="small text-muted mb-1">Shipping Address</p>
                                                <p class="mb-0 small" x-text="selectedOrder.shippingAddress ? selectedOrder.shippingAddress.formatted : 'N/A'"></p>
                                            </div>
                                            <div class="col-md-12">
                                                <p class="small text-muted mb-1">Billing Address</p>
                                                <p class="mb-0 small" x-text="selectedOrder.billingAddress ? selectedOrder.billingAddress.formatted : 'N/A'"></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card mb-4 border-light-subtle bg-body-tertiary" x-show="selectedOrder.shipment || selectedOrder.invoice">
                                    <div class="card-body">
                                        <div class="row g-4">
                                            <div class="col-md-6" x-show="selectedOrder.shipment">
                                                <h6 class="fw-bold mb-3 text-uppercase text-muted small">Shipment Details</h6>
                                                <div class="small">
                                                    <div class="mb-2"><span class="text-muted">Shipment No:</span> <span class="fw-semibold" x-text="selectedOrder.shipment.no"></span></div>
                                                    <div class="mb-2"><span class="text-muted">Carrier:</span> <span class="fw-semibold" x-text="selectedOrder.shipment.carrier"></span></div>
                                                    <div class="mb-2"><span class="text-muted">Tracking:</span> <span class="fw-semibold font-monospace" x-text="selectedOrder.shipment.trackingNo"></span></div>
                                                    <div class="mb-2"><span class="text-muted">Status:</span> <span class="fw-semibold text-capitalize" x-text="selectedOrder.shipment.status.replace(/_/g, ' ')"></span></div>
                                                    <div class="mb-2"><span class="text-muted">Shipped At:</span> <span class="fw-semibold" x-text="formatDateTime(selectedOrder.shipment.shippedAt)"></span></div>
                                                    <div><span class="text-muted">Delivered At:</span> <span class="fw-semibold" x-text="formatDateTime(selectedOrder.shipment.deliveredAt)"></span></div>
                                                </div>
                                                <div class="mt-3" x-show="selectedOrder.shipment.events && selectedOrder.shipment.events.length">
                                                    <p class="small text-muted mb-2">Tracking Events</p>
                                                    <div class="border rounded bg-white p-2" style="max-height: 180px; overflow-y: auto;">
                                                        <template x-for="event in selectedOrder.shipment.events" :key="event.id">
                                                            <div class="mb-2 pb-2 border-bottom small">
                                                                <div class="d-flex justify-content-between">
                                                                    <span class="fw-semibold" x-text="event.status || 'Event'"></span>
                                                                    <small class="text-muted" x-text="formatDateTime(event.created_at)"></small>
                                                                </div>
                                                                <div class="text-muted" x-text="event.description || event.remark || 'No description provided.'"></div>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6" x-show="selectedOrder.invoice">
                                                <h6 class="fw-bold mb-3 text-uppercase text-muted small">Invoice Details</h6>
                                                <div class="small">
                                                    <div class="mb-2"><span class="text-muted">Invoice No:</span> <span class="fw-semibold" x-text="selectedOrder.invoice.number"></span></div>
                                                    <div class="mb-2"><span class="text-muted">Invoice Date:</span> <span class="fw-semibold" x-text="formatDateTime(selectedOrder.invoice.date)"></span></div>
                                                    <div class="mb-2"><span class="text-muted">Status:</span> <span class="fw-semibold text-capitalize" x-text="selectedOrder.invoice.status.replace(/_/g, ' ')"></span></div>
                                                    <div class="mb-2"><span class="text-muted">Invoice Total:</span> <span class="fw-semibold" x-text="`$${selectedOrder.invoice.total.toFixed(2)}`"></span></div>
                                                    <div class="mb-2"><span class="text-muted">Invoice Tax:</span> <span class="fw-semibold" x-text="`$${selectedOrder.invoice.tax.toFixed(2)}`"></span></div>
                                                    <div class="mb-2"><span class="text-muted">Paid:</span> <span class="fw-semibold text-success" x-text="`$${selectedOrder.invoice.paid.toFixed(2)}`"></span></div>
                                                    <div><span class="text-muted">Due:</span> <span class="fw-semibold text-danger" x-text="`$${selectedOrder.invoice.due.toFixed(2)}`"></span></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Items Table -->
                                <h6 class="fw-bold mb-3 text-uppercase text-muted small">Order Items</h6>
                                <div class="table-responsive">
                                    <table class="table table-bordered align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Product</th>
                                                <th style="width: 120px;">SKU</th>
                                                <th class="text-end" style="width: 100px;">Qty</th>
                                                <th class="text-end" style="width: 120px;">Price</th>
                                                <th class="text-end" style="width: 120px;">Discount</th>
                                                <th class="text-end" style="width: 120px;">Tax</th>
                                                <th class="text-end" style="width: 140px;">Net Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="(item, idx) in selectedOrder.items" :key="idx">
                                                <tr>
                                                    <td class="fw-medium small" x-text="item.name"></td>
                                                    <td class="small text-muted font-monospace" x-text="item.sku || '—'"></td>
                                                    <td class="text-end" x-text="item.quantity"></td>
                                                    <td class="text-end" x-text="`$${parseFloat(item.price).toFixed(2)}`"></td>
                                                    <td class="text-end" x-text="`$${parseFloat(item.discount).toFixed(2)}`"></td>
                                                    <td class="text-end" x-text="`$${parseFloat(item.tax).toFixed(2)}`"></td>
                                                    <td class="text-end fw-semibold text-primary" x-text="`$${parseFloat(item.net).toFixed(2)}`"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                        <tfoot>
                                            <tr class="table-light fw-bold">
                                                <td colspan="6" class="text-end">Total Amount:</td>
                                                <td class="text-end text-primary" x-text="`$${selectedOrder.total.toFixed(2)}`"></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- Right Column: Warehouse / Payments / Verification -->
                            <div class="col-lg-4 border-start">
                                <!-- Action Buttons Inside Details -->
                                <div class="mb-4 d-grid gap-2">
                                    <button class="btn btn-outline-secondary btn-sm" @click="printInvoice(selectedOrder)">
                                        <i class="bi bi-file-pdf me-2"></i>Invoice PDF
                                    </button>
                                    <button class="btn btn-outline-secondary btn-sm" @click="printCOD(selectedOrder)">
                                        <i class="bi bi-file-earmark-pdf me-2"></i>COD Receipt
                                    </button>
                                    <button class="btn btn-outline-secondary btn-sm" @click="printReceipt(selectedOrder)">
                                        <i class="bi bi-receipt me-2"></i>Thermal Receipt
                                    </button>
                                </div>

                                <div class="card mb-4 border-light-subtle">
                                    <div class="card-body">
                                        <h6 class="fw-bold text-uppercase text-muted small mb-3">Warehouse</h6>
                                        <p class="mb-1 fw-semibold" x-text="selectedOrder.warehouse ? selectedOrder.warehouse.name : 'N/A'"></p>
                                        <p class="small text-muted mb-1" x-text="selectedOrder.warehouse ? selectedOrder.warehouse.address : 'N/A'"></p>
                                        <p class="small text-muted mb-0" x-text="selectedOrder.warehouse ? `Phone: ${selectedOrder.warehouse.phone} | GST: ${selectedOrder.warehouse.gstin}` : ''"></p>
                                    </div>
                                </div>

                                <div class="card mb-4 border-light-subtle" x-show="selectedOrder.payments && selectedOrder.payments.length">
                                    <div class="card-body">
                                        <h6 class="fw-bold text-uppercase text-muted small mb-3">Payments</h6>
                                        <template x-for="payment in selectedOrder.payments" :key="payment.id">
                                            <div class="border rounded p-2 mb-2 bg-light">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="fw-semibold" x-text="payment.no"></span>
                                                    <span class="badge bg-secondary text-capitalize" x-text="payment.status"></span>
                                                </div>
                                                <div class="small text-muted">
                                                    <div x-text="`Amount: $${payment.amount.toFixed(2)}`"></div>
                                                    <div x-text="`Method: ${payment.method}`"></div>
                                                    <div x-text="`Date: ${formatDateTime(payment.date)}`"></div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <!-- Verification Call History -->
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="fw-bold text-uppercase text-muted small mb-0">Verification History</h6>
                                    <button class="btn btn-xs btn-outline-primary py-0 px-2" style="font-size: 11px" @click="openVerificationModal(selectedOrder)">
                                        + Add Call
                                    </button>
                                </div>

                                <div class="verification-timeline" style="max-height: 300px; overflow-y: auto;">
                                    <template x-for="log in (selectedOrder.verificationLogs || selectedOrder.original.verification_logs || [])" :key="log.id">
                                        <div class="p-2 border rounded mb-2 bg-light">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="badge bg-secondary" x-text="log.outcome_label || log.outcome"></span>
                                                <small class="text-muted" x-text="formatDateTime(log.created_at)"></small>
                                            </div>
                                            <p class="small mb-1 text-dark" x-text="log.remark || 'No remark added.'"></p>
                                            <div class="d-flex justify-content-between align-items-center mt-1" style="font-size: 11px">
                                                <span class="text-muted" x-text="`By: ${log.user ? log.user.name : 'System'}`"></span>
                                                <template x-if="log.follow_up_at">
                                                    <span class="text-warning fw-semibold">
                                                        <i class="bi bi-bell-fill me-1"></i>Follow-up: <span x-text="new Date(log.follow_up_at).toLocaleDateString()"></span>
                                                    </span>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                    <template x-if="!(selectedOrder.verificationLogs || selectedOrder.original.verification_logs || []).length">
                                        <p class="text-muted small text-center my-3">No verification calls logged.</p>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

<!-- ═══════════════════════ Create Shipment Modal ═══════════════════════════ -->
<div class="modal fade" id="createShipmentModal" tabindex="-1" aria-labelledby="createShipmentModalLabel">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="createShipmentModalLabel">
                    <i class="bi bi-truck me-2 text-primary"></i>Ship Order <span class="text-primary" x-text="shipOrderNo"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-3">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Select Carrier <span class="text-danger">*</span></label>
                    <select class="form-select" x-model="shipCarrierName">
                        <option value="" disabled selected>Select carrier...</option>
                        <template x-for="carrier in carriersList" :key="carrier">
                            <option :value="carrier" x-text="carrier"></option>
                        </template>
                    </select>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label fw-semibold mb-0">Tracking Number <span class="text-danger">*</span></label>
                        <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none small" @click="shipTrackingNo = 'TRK-' + Math.random().toString(36).substring(2, 12).toUpperCase()">
                            <i class="bi bi-magic me-1"></i>Generate Demo ID
                        </button>
                    </div>
                    <input type="text" class="form-control font-monospace" x-model="shipTrackingNo" placeholder="Enter tracking number (e.g. TRK-12345678)" required>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" @click="shipOrder()">Ship Order</button>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════ Verification Call Modal ═══════════════════════════ -->
<div class="modal fade" id="verificationModal" tabindex="-1" aria-labelledby="verificationModalLabel">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="verificationModalLabel">
                    <i class="bi bi-telephone-outbound me-2 text-success"></i>Log Verification Call
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-3">
                <p class="small text-muted" x-show="!isBulkVerification" x-text="selectedOrder ? `Logging call outcome for order ${selectedOrder.orderNumber}` : ''"></p>
                <p class="small text-muted" x-show="isBulkVerification" x-text="`Logging call outcome for ${selectedOrders.length} selected orders`"></p>
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">Call Outcome <span class="text-danger">*</span></label>
                    <select class="form-select" x-model="verifyOutcome" required>
                        <option value="" disabled selected>Select outcome...</option>
                        <option value="call_not_picked">Call Not Picked</option>
                        <option value="customer_confirmed">Customer Confirmed Order</option>
                        <option value="mark_processing">Mark as Processing</option>
                        <option value="dispatch_order">Dispatch Order</option>
                        <option value="mark_delivered">Mark as Delivered</option>
                        <option value="reschedule_delivery">Reschedule Delivery</option>
                        <option value="next_followup_call">Next Follow-up Call</option>
                        <option value="cancel_order">Cancel Order</option>
                        <option value="return_order">Return Order</option>
                        <option value="wrong_number">Wrong Number</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">Remarks</label>
                    <textarea class="form-control" rows="3" placeholder="Customer notes, response details..." x-model="verifyRemark"></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Next Follow-up Date (Optional)</label>
                    <input type="datetime-local" class="form-control" x-model="verifyFollowUp">
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" @click="saveVerificationLog()">Save log</button>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════ CSV Import Preview Modal ═══════════════════════════ -->
<div class="modal fade" id="importPreviewModal" tabindex="-1" aria-labelledby="importPreviewModalLabel" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="importPreviewModalLabel">
                    <i class="bi bi-file-earmark-spreadsheet me-2 text-primary"></i>CSV Import Preview
                </h5>
                <button type="button" class="btn-close" @click="cancelImport()"></button>
            </div>
            <div class="modal-body pt-3">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle-fill me-2"></i>Please review the rows before finalizing the bulk import.
                </div>
                <div class="table-responsive" style="max-height: 400px;">
                    <table class="table table-striped table-sm small align-middle">
                        <thead>
                            <tr>
                                <th>Order No</th>
                                <th>Status</th>
                                <th>Reason / Validation</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, idx) in importRows" :key="idx">
                                <tr>
                                    <td class="font-monospace fw-bold" x-text="row.order_no"></td>
                                    <td>
                                        <span class="badge" 
                                              :class="row.status === 'success' || row.status === 'valid' ? 'bg-success' : 'bg-danger'"
                                              x-text="row.status"></span>
                                    </td>
                                    <td x-text="row.validation_message || row.message || 'Row looks valid.'"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-secondary" @click="cancelImport()">Cancel</button>
                <button type="button" class="btn btn-primary" @click="confirmImport()">Confirm Import</button>
            </div>
        </div>
    </div>
</div>

</div> <!-- End Order Management Container -->
@endsection

@push('scripts')
@endpush
