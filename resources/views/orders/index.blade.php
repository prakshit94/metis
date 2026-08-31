@extends('layouts.app')

@section('title', 'Order Management')
@section('page', 'orders')

@section('content')
<div class="order-management" x-data="orderTable" x-init="
    productsList = {{ json_encode($productsList->toArray()) }};
    statesList = {{ json_encode($statesList) }};
    districtsList = {{ json_encode($districtsList ?? []) }};
    talukasList = {{ json_encode($talukasList ?? []) }};
    villagesList = {{ json_encode($villagesList ?? []) }};
    carriersList = {{ json_encode($carriersList) }};
    warehousesList = {{ json_encode($warehousesList ?? []) }};
    allowedFilterStatuses = {{ json_encode($statusesList) }};
    init();
">
    <div x-data="{ showAnalytics: localStorage.getItem('orders_show_analytics') === 'true' }" x-init="$watch('showAnalytics', val => localStorage.setItem('orders_show_analytics', val))">
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4 mb-lg-5 mb-xl-6">
    <div>
        <h1 class="h3 mb-0">Order Management</h1>
        <p class="text-muted mb-0">Track orders, manage fulfillment, and analyze sales</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <!-- Analytics Toggle -->
        <div class="form-check form-switch m-0 cursor-pointer d-flex align-items-center gap-2">
            <input class="form-check-input m-0" type="checkbox" role="switch" id="ordersAnalyticsToggle" x-model="showAnalytics" style="cursor: pointer; width: 2.5em; height: 1.25em;">
            <label class="form-check-label small fw-bold text-muted mb-0 ms-1" for="ordersAnalyticsToggle" style="cursor: pointer; padding-top: 2px;">Analytics</label>
        </div>
        
        <!-- Warehouse Ops Toggle -->
        <div class="form-check form-switch m-0 me-2 pe-3 border-end cursor-pointer d-none d-md-flex align-items-center gap-2" x-show="warehouseStats && warehouseStats.length > 0" x-cloak>
            <input class="form-check-input m-0" type="checkbox" role="switch" id="warehouseStatsToggleHeader" x-model="showWarehouseStats" style="cursor: pointer; width: 2.5em; height: 1.25em;">
            <label class="form-check-label small fw-bold text-muted mb-0 ms-1 cursor-pointer user-select-none" for="warehouseStatsToggleHeader" style="padding-top: 2px;">Warehouse Ops</label>
        </div>
        @can('orders.export')
        <button type="button" class="btn btn-outline-secondary" @click="exportOrders()">
            <i class="bi bi-download me-2"></i>Export
        </button>
        @endcan
        @can('orders.import')
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
        @endcan
        @can('orders.create')
        <a href="{{ route('orders.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-2"></i>New Order
        </a>
        @endcan
    </div>
</div>

<!-- Hidden CSV Import Form -->
<form id="import-form" action="{{ route('orders.import') }}" method="POST" enctype="multipart/form-data" class="d-none">
    @csrf
    <input type="file" name="file" id="import-file" accept=".csv,.txt" @change="handleImportFileSelect($event)">
</form>

<!-- Order Stats Widgets -->
<div class="row row-cols-1 row-cols-md-2 row-cols-xl-6 g-4 g-lg-5 g-xl-6 mb-5 mb-lg-5 mb-xl-6">
    <div class="col">
        <div class="card stats-card h-100">
            <div class="card-body p-3 p-lg-4">
                <div class="d-flex flex-column align-items-start">
                    <div class="stats-icon text-bg-primary-subtle text-primary-emphasis mb-3">
                        <i class="bi bi-bag-check"></i>
                    </div>
                    <div class="w-100" style="min-width: 0;">
                        <p class="h6 mb-0 text-muted" title="Total Orders">Total Orders</p>
                        <div class="h3 mb-0" aria-live="polite"><span x-text="(stats.total || 0) - (stats.future_order || 0)"></span></div>
                        <small class="text-muted d-block text-wrap" style="word-break: break-all;" x-text="'Value: ' + formatCurrency((stats.revenue || 0) - (stats.future_order_amount || 0))"></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @can('orders.view.future_order')
    <div class="col">
        <div class="card stats-card h-100">
            <div class="card-body p-3 p-lg-4">
                <div class="d-flex flex-column align-items-start">
                    <div class="stats-icon text-bg-info-subtle text-info-emphasis mb-3">
                        <i class="bi bi-calendar-event"></i>
                    </div>
                    <div class="w-100" style="min-width: 0;">
                        <p class="h6 mb-0 text-muted" title="Future Order">Future Order</p>
                        <div class="h3 mb-0" aria-live="polite"><span x-text="stats.future_order"></span></div>
                        <small class="text-info d-block text-wrap" style="word-break: break-all;" x-text="'Value: ' + formatCurrency(stats.future_order_amount)"></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endcan
    @can('orders.view.pending_confirmation')
    <div class="col">
        <div class="card stats-card h-100">
            <div class="card-body p-3 p-lg-4">
                <div class="d-flex flex-column align-items-start">
                    <div class="stats-icon text-bg-warning-subtle text-warning-emphasis mb-3">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div class="w-100" style="min-width: 0;">
                        <p class="h6 mb-0 text-muted"  title="Pending Confirmation">Pending Confirmation</p>
                        <div class="h3 mb-0" aria-live="polite"><span x-text="stats.pending_confirmation"></span></div>
                        <small class="text-warning d-block text-wrap" style="word-break: break-all;" x-text="'Value: ' + formatCurrency(stats.pending_confirmation_amount)"></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endcan
    @can('orders.view.pending')
    <div class="col">
        <div class="card stats-card h-100">
            <div class="card-body p-3 p-lg-4">
                <div class="d-flex flex-column align-items-start">
                    <div class="stats-icon text-bg-warning-subtle text-warning-emphasis mb-3">
                        <i class="bi bi-clock"></i>
                    </div>
                    <div class="w-100" style="min-width: 0;">
                        <p class="h6 mb-0 text-muted" title="Pending">Pending</p>
                        <div class="h3 mb-0" aria-live="polite"><span x-text="stats.pending"></span></div>
                        <small class="text-warning d-block text-wrap" style="word-break: break-all;" x-text="'Value: ' + formatCurrency(stats.pending_amount)"></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endcan
    @can('orders.view.confirmed')
    <div class="col">
        <div class="card stats-card h-100">
            <div class="card-body p-3 p-lg-4">
                <div class="d-flex flex-column align-items-start">
                    <div class="stats-icon text-bg-info-subtle text-info-emphasis mb-3">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="w-100" style="min-width: 0;">
                        <p class="h6 mb-0 text-muted" title="Confirmed">Confirmed</p>
                        <div class="h3 mb-0" aria-live="polite"><span x-text="stats.confirmed"></span></div>
                        <small class="text-info d-block text-wrap" style="word-break: break-all;" x-text="'Value: ' + formatCurrency(stats.confirmed_amount)"></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endcan
    @can('orders.view.processing')
    <div class="col">
        <div class="card stats-card h-100">
            <div class="card-body p-3 p-lg-4">
                <div class="d-flex flex-column align-items-start">
                    <div class="stats-icon text-bg-secondary-subtle text-secondary-emphasis mb-3">
                        <i class="bi bi-gear"></i>
                    </div>
                    <div class="w-100" style="min-width: 0;">
                        <p class="h6 mb-0 text-muted" title="Processing">Processing</p>
                        <div class="h3 mb-0" aria-live="polite"><span x-text="stats.processing"></span></div>
                        <small class="text-secondary d-block text-wrap" style="word-break: break-all;" x-text="'Value: ' + formatCurrency(stats.processing_amount)"></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endcan
    @can('orders.view.ready_to_ship')
    <div class="col">
        <div class="card stats-card h-100">
            <div class="card-body p-3 p-lg-4">
                <div class="d-flex flex-column align-items-start">
                    <div class="stats-icon text-bg-dark-subtle text-body-emphasis-emphasis mb-3">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <div class="w-100" style="min-width: 0;">
                        <p class="h6 mb-0 text-muted" title="Ready to Ship">Ready to Ship</p>
                        <div class="h3 mb-0" aria-live="polite"><span x-text="stats.ready_to_ship"></span></div>
                        <small class="text-body-emphasis d-block text-wrap" style="word-break: break-all;" x-text="'Value: ' + formatCurrency(stats.ready_to_ship_amount)"></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endcan
    @can('orders.view.dispatched')
    <div class="col">
        <div class="card stats-card h-100">
            <div class="card-body p-3 p-lg-4">
                <div class="d-flex flex-column align-items-start">
                    <div class="stats-icon text-bg-info-subtle text-info-emphasis mb-3">
                        <i class="bi bi-truck"></i>
                    </div>
                    <div class="w-100" style="min-width: 0;">
                        <p class="h6 mb-0 text-muted" title="Dispatched">Dispatched</p>
                        <div class="h3 mb-0" aria-live="polite"><span x-text="stats.dispatched"></span></div>
                        <small class="text-info d-block text-wrap" style="word-break: break-all;" x-text="'Value: ' + formatCurrency(stats.dispatched_amount)"></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endcan
    @can('orders.view.delivered')
    <div class="col">
        <div class="card stats-card h-100">
            <div class="card-body p-3 p-lg-4">
                <div class="d-flex flex-column align-items-start">
                    <div class="stats-icon text-bg-success-subtle text-success-emphasis mb-3">
                        <i class="bi bi-currency-rupee"></i>
                    </div>
                    <div class="w-100" style="min-width: 0;">
                        <p class="h6 mb-0 text-muted" title="Delivered">Delivered</p>
                        <div class="h3 mb-0" aria-live="polite"><span x-text="stats.delivered"></span></div>
                        <small class="text-success-emphasis d-block text-wrap" style="word-break: break-all;" x-text="'Value: ' + formatCurrency(stats.delivered_amount)"></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endcan
    @can('orders.view.cancelled')
    <div class="col">
        <div class="card stats-card h-100">
            <div class="card-body p-3 p-lg-4">
                <div class="d-flex flex-column align-items-start">
                    <div class="stats-icon text-bg-danger-subtle text-danger-emphasis mb-3">
                        <i class="bi bi-x-circle"></i>
                    </div>
                    <div class="w-100" style="min-width: 0;">
                        <p class="h6 mb-0 text-muted" title="Cancelled">Cancelled</p>
                        <div class="h3 mb-0" aria-live="polite"><span x-text="stats.cancelled"></span></div>
                        <small class="text-danger d-block text-wrap" style="word-break: break-all;" x-text="'Value: ' + formatCurrency(stats.cancelled_amount)"></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endcan
    @can('orders.view.return_requested')
    <div class="col">
        <div class="card stats-card h-100">
            <div class="card-body p-3 p-lg-4">
                <div class="d-flex flex-column align-items-start">
                    <div class="stats-icon text-bg-warning-subtle text-warning-emphasis mb-3">
                        <i class="bi bi-arrow-return-left"></i>
                    </div>
                    <div class="w-100" style="min-width: 0;">
                        <p class="h6 mb-0 text-muted"  title="Return Requested">Return Requested</p>
                        <div class="h3 mb-0" aria-live="polite"><span x-text="stats.return_requested"></span></div>
                        <small class="text-warning d-block text-wrap" style="word-break: break-all;" x-text="'Value: ' + formatCurrency(stats.return_requested_amount)"></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endcan
    @can('orders.view.returned')
    <div class="col">
        <div class="card stats-card h-100">
            <div class="card-body p-3 p-lg-4">
                <div class="d-flex flex-column align-items-start">
                    <div class="stats-icon text-bg-secondary-subtle text-secondary-emphasis mb-3">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </div>
                    <div class="w-100" style="min-width: 0;">
                        <p class="h6 mb-0 text-muted" title="Returned">Returned</p>
                        <div class="h3 mb-0" aria-live="polite"><span x-text="stats.returned"></span></div>
                        <small class="text-secondary d-block text-wrap" style="word-break: break-all;" x-text="'Value: ' + formatCurrency(stats.returned_amount)"></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endcan
</div>

<!-- Charts Row -->
<div x-show="showAnalytics" x-transition.opacity.duration.300ms>
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
</div> <!-- End showAnalytics Wrapper -->

<!-- Warehouse Operations Overview -->
<div class="mb-5 mb-lg-5 mb-xl-6" x-show="showWarehouseStats" x-transition x-cloak>
    <div class="d-flex align-items-center mb-3 gap-3">
        <h2 class="h5 mb-0 fw-bold d-flex align-items-center text-nowrap">
            <i class="bi bi-buildings text-primary me-2 fs-4"></i>Warehouse Operations Overview
        </h2>
        <div class="flex-grow-1 border-bottom border-secondary-subtle"></div>
        <select x-select class="form-select form-select-sm w-auto" x-model="visibleWarehouseStat" aria-label="Toggle Warehouse Visibility">
            <option value="">All Warehouses</option>
            <template x-for="wh in warehousesList" :key="wh.id">
                <option :value="wh.name" x-text="wh.name"></option>
            </template>
        </select>
    </div>
    
    <div class="row g-4">
        <template x-for="(wh, idx) in warehouseStats" :key="idx">
            <div class="col-12" x-show="!visibleWarehouseStat || visibleWarehouseStat === wh.name" x-transition>
                <div class="card shadow-sm border-0 rounded-4 overflow-hidden position-relative">
                    <!-- Left accent line -->
                    <div class="position-absolute top-0 bottom-0 start-0 bg-primary" style="width: 4px;"></div>
                    
                    <div class="card-body p-4">
                        <div class="row align-items-center g-4">
                            <!-- Info Section -->
                            <div class="col-lg-3 col-md-12 border-end border-secondary-subtle pe-lg-4 mb-4 mb-lg-0 pb-4 pb-lg-0">
                                <h3 class="h5 fw-bold mb-3 text-body-emphasis" x-text="wh.name"></h3>
                                <div class="d-flex align-items-center gap-3 mb-4">
                                    <div class="badge text-bg-primary-subtle text-primary-emphasis-subtle py-2 px-3 fs-6 rounded-pill" x-text="`${wh.total} Orders`"></div>
                                </div>
                                <div class="p-3 bg-body-tertiary bg-opacity-50 rounded-4 border border-secondary-subtle mb-3 shadow-sm">
                                    <span class="d-block text-muted small fw-medium mb-1 text-uppercase tracking-wider">Total Value</span>
                                    <span class="fs-4 fw-bold text-success d-block" x-text="`₹ ${formatCurrency(wh.total_amount)}`"></span>
                                </div>
                                
                                <div class="px-1">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="small text-muted fw-bold text-uppercase tracking-wide" style="font-size: 0.7rem;">Delivery Rate</span>
                                        <span class="small fw-bold text-success" x-text="`${wh.total > 0 ? Math.round((wh.delivered / wh.total) * 100) : 0}%`"></span>
                                    </div>
                                    <div class="progress rounded-pill bg-success bg-opacity-10" style="height: 6px;">
                                        <div class="progress-bar bg-success rounded-pill" role="progressbar" :style="`width: ${wh.total > 0 ? (wh.delivered / wh.total) * 100 : 0}%`"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Status Breakdown Section -->
                            <div class="col-lg-9 col-md-12 ps-lg-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <p class="text-muted small fw-bold text-uppercase tracking-wide mb-0" style="font-size: 0.75rem; letter-spacing: 0.5px;">Fulfillment Pipeline</p>
                                    
                                    <!-- Exceptions / Returns Badge -->
                                    @can('orders.view.return_requested')
                                    <div class="badge bg-danger bg-opacity-10 border border-danger border-opacity-25 text-danger-emphasis py-1 px-3 rounded-pill d-flex align-items-center gap-2" x-show="wh.return_requested > 0" x-transition>
                                        <i class="bi bi-exclamation-triangle-fill"></i>
                                        <span x-text="`${wh.return_requested} Return(s) Req (₹ ${formatCurrency(wh.return_requested_amount)})`"></span>
                                    </div>
                                    @endcan
                                </div>

                                <!-- Horizontal Pipeline -->
                                <div class="d-flex align-items-stretch flex-nowrap overflow-x-auto pb-2 gap-2" style="scrollbar-width: thin;">
                                    
                                    <!-- Pending -->
                                    @can('orders.view.pending')
                                    <div class="flex-fill p-3 rounded-4 bg-warning bg-opacity-10 border border-warning border-opacity-25 d-flex flex-column text-center position-relative transition-hover" style="min-width: 130px;">
                                        <span class="small text-warning-emphasis fw-bold mb-2 text-uppercase tracking-wide" style="font-size: 0.65rem;">Pending</span>
                                        <span class="fw-bold fs-4 text-warning-emphasis lh-1 mb-1 d-block" x-text="wh.pending"></span>
                                        <small class="text-warning-emphasis opacity-75 fw-medium" style="font-size: 0.75rem;" x-text="`₹ ${formatCurrency(wh.pending_amount)}`"></small>
                                        <i class="bi bi-caret-right-fill position-absolute top-50 start-100 translate-middle text-warning opacity-50 d-none d-sm-block" style="font-size: 1.5rem; transform: translate(-50%, -50%) !important; z-index: 2;"></i>
                                    </div>
                                    @endcan

                                    <!-- Confirmed -->
                                    @can('orders.view.confirmed')
                                    <div class="flex-fill p-3 rounded-4 bg-info bg-opacity-10 border border-info border-opacity-25 d-flex flex-column text-center position-relative transition-hover" style="min-width: 130px;">
                                        <span class="small text-info-emphasis fw-bold mb-2 text-uppercase tracking-wide" style="font-size: 0.65rem;">Confirmed</span>
                                        <span class="fw-bold fs-4 text-info-emphasis lh-1 mb-1 d-block" x-text="wh.confirmed"></span>
                                        <small class="text-info-emphasis opacity-75 fw-medium" style="font-size: 0.75rem;" x-text="`₹ ${formatCurrency(wh.confirmed_amount)}`"></small>
                                        <i class="bi bi-caret-right-fill position-absolute top-50 start-100 translate-middle text-info opacity-50 d-none d-sm-block" style="font-size: 1.5rem; transform: translate(-50%, -50%) !important; z-index: 2;"></i>
                                    </div>
                                    @endcan

                                    <!-- Processing -->
                                    @can('orders.view.processing')
                                    <div class="flex-fill p-3 rounded-4 bg-primary bg-opacity-10 border border-primary border-opacity-25 d-flex flex-column text-center position-relative transition-hover" style="min-width: 130px;">
                                        <span class="small text-primary-emphasis fw-bold mb-2 text-uppercase tracking-wide" style="font-size: 0.65rem;">Processing</span>
                                        <span class="fw-bold fs-4 text-primary-emphasis lh-1 mb-1 d-block" x-text="wh.processing"></span>
                                        <small class="text-primary-emphasis opacity-75 fw-medium" style="font-size: 0.75rem;" x-text="`₹ ${formatCurrency(wh.processing_amount)}`"></small>
                                        <i class="bi bi-caret-right-fill position-absolute top-50 start-100 translate-middle text-primary opacity-50 d-none d-sm-block" style="font-size: 1.5rem; transform: translate(-50%, -50%) !important; z-index: 2;"></i>
                                    </div>
                                    @endcan

                                    <!-- Ready -->
                                    @can('orders.view.ready_to_ship')
                                    <div class="flex-fill p-3 rounded-4 bg-warning-subtle bg-opacity-50 border border-warning border-opacity-25 d-flex flex-column text-center position-relative transition-hover" style="min-width: 130px;">
                                        <span class="small text-warning-emphasis fw-bold mb-2 text-uppercase tracking-wide" style="font-size: 0.65rem;">Ready</span>
                                        <span class="fw-bold fs-4 text-warning-emphasis lh-1 mb-1 d-block" x-text="wh.ready_to_ship"></span>
                                        <small class="text-warning-emphasis opacity-75 fw-medium" style="font-size: 0.75rem;" x-text="`₹ ${formatCurrency(wh.ready_to_ship_amount)}`"></small>
                                        <i class="bi bi-caret-right-fill position-absolute top-50 start-100 translate-middle text-warning opacity-50 d-none d-sm-block" style="font-size: 1.5rem; transform: translate(-50%, -50%) !important; z-index: 2;"></i>
                                    </div>
                                    @endcan

                                    <!-- Dispatched -->
                                    @can('orders.view.dispatched')
                                    <div class="flex-fill p-3 rounded-4 bg-secondary bg-opacity-10 border border-secondary border-opacity-25 d-flex flex-column text-center position-relative transition-hover" style="min-width: 130px;">
                                        <span class="small text-secondary-emphasis fw-bold mb-2 text-uppercase tracking-wide" style="font-size: 0.65rem;">Dispatched</span>
                                        <span class="fw-bold fs-4 text-secondary-emphasis lh-1 mb-1 d-block" x-text="wh.dispatched"></span>
                                        <small class="text-secondary-emphasis opacity-75 fw-medium" style="font-size: 0.75rem;" x-text="`₹ ${formatCurrency(wh.dispatched_amount)}`"></small>
                                        <i class="bi bi-caret-right-fill position-absolute top-50 start-100 translate-middle text-secondary opacity-50 d-none d-sm-block" style="font-size: 1.5rem; transform: translate(-50%, -50%) !important; z-index: 2;"></i>
                                    </div>
                                    @endcan

                                    <!-- Delivered -->
                                    @can('orders.view.delivered')
                                    <div class="flex-fill p-3 rounded-4 bg-success bg-opacity-10 border border-success border-opacity-25 d-flex flex-column text-center transition-hover" style="min-width: 130px;">
                                        <span class="small text-success-emphasis fw-bold mb-2 text-uppercase tracking-wide" style="font-size: 0.65rem;">Delivered</span>
                                        <span class="fw-bold fs-4 text-success-emphasis lh-1 mb-1 d-block" x-text="wh.delivered"></span>
                                        <small class="text-success-emphasis opacity-75 fw-medium" style="font-size: 0.75rem;" x-text="`₹ ${formatCurrency(wh.delivered_amount)}`"></small>
                                    </div>
                                    @endcan
                                </div>
                                
                                <!-- Non-Lifecycle Statuses -->
                                <div class="d-flex gap-3 mt-3">
                                    @can('orders.view.returned')
                                    <div class="d-flex align-items-center gap-2" x-show="wh.returned > 0">
                                        <div class="bg-secondary rounded-circle" style="width: 8px; height: 8px;"></div>
                                        <span class="small text-secondary-emphasis fw-medium">Returned: <span x-text="wh.returned"></span></span>
                                    </div>
                                    @endcan
                                    @can('orders.view.cancelled')
                                    <div class="d-flex align-items-center gap-2" x-show="wh.cancelled > 0">
                                        <div class="bg-dark rounded-circle" style="width: 8px; height: 8px;"></div>
                                        <span class="small text-body-emphasis-emphasis fw-medium">Cancelled: <span x-text="wh.cancelled"></span></span>
                                    </div>
                                    @endcan
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
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
                        <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-2 text-muted z-1" style="font-size: 0.85rem;"></i>
                        <input type="search" 
                               class="form-control form-control-sm ps-4" 
                               placeholder="Search orders..."
                               x-model="searchQuery"
                               @input="filterOrders()"
                               style="width: 200px;">
                    </div>
                    
                    <!-- Status Filter -->
                    @can('orders.filter_status')
                    <div class="position-relative" @click.away="showStatusDropdown = false" :style="showStatusDropdown ? 'z-index: 1050;' : ''">
                        <div class="form-control form-control-sm d-flex flex-wrap align-items-center gap-1" style="min-height: 31px; cursor: pointer; width: 150px;" @click="showStatusDropdown = !showStatusDropdown">
                            <template x-if="statusFilter.length === 0">
                                <span class="text-body-secondary" style="font-size: 13px;">All Statuses</span>
                            </template>
                            <template x-if="statusFilter.length > 0">
                                <div class="d-flex flex-wrap align-items-center gap-1 w-100" style="padding-right: 15px;">
                                    <template x-for="status in statusFilter.slice(0, 1)" :key="status">
                                        <div class="badge text-bg-primary-subtle text-primary-emphasis d-flex align-items-center gap-1 border border-primary-subtle">
                                            <span x-text="status.charAt(0).toUpperCase() + status.slice(1).replace(/_/g, ' ')" style="font-size: 11px;"></span>
                                            <i class="bi bi-x cursor-pointer" @click.stop="toggleFilter('status', status)" style="font-size: 13px;"></i>
                                        </div>
                                    </template>
                                    <template x-if="statusFilter.length > 1">
                                        <span class="badge bg-secondary rounded-pill" style="font-size: 11px;" x-text="'+' + (statusFilter.length - 1)"></span>
                                    </template>
                                </div>
                            </template>
                            <i class="bi bi-chevron-down position-absolute text-muted" style="right: 8px; font-size: 12px; top: 50%; transform: translateY(-50%);"></i>
                        </div>
                        <div x-show="showStatusDropdown" class="position-absolute bg-body border rounded shadow-lg mt-1" style="max-height: 250px; overflow-y: auto; z-index: 1050; min-width: 180px; right: 0;">
                            <div class="px-3 py-2 cursor-pointer border-bottom bg-body-tertiary d-flex align-items-center" @click.stop="toggleAllFilter('status')">
                                <input type="checkbox" :checked="statusFilter.length > 0 && statusFilter.length === allowedFilterStatuses.length" class="me-2" style="cursor: pointer;">
                                <span style="font-size: 12px; font-weight: bold;">Select All</span>
                            </div>
                            <template x-for="status in allowedFilterStatuses" :key="status">
                                <div class="px-3 py-1 cursor-pointer custom-hover-bg d-flex align-items-center" @click.stop="toggleFilter('status', status)">
                                    <input type="checkbox" :checked="statusFilter.includes(status)" class="me-2" style="cursor: pointer;">
                                    <span style="font-size: 12px;" x-text="status.charAt(0).toUpperCase() + status.slice(1).replace(/_/g, ' ')"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                    @endcan
                    
                    <!-- Date Range -->
                    @can('orders.filter_date')
                    <select x-select class="form-select form-select-sm" 
                            x-model="dateFilter" 
                            @change="filterOrders()"
                            style="width: 150px;">
                        <option value="">All Dates</option>
                        <option value="today">Today</option>
                        <option value="yesterday">Yesterday</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                        <option value="prev_month">Previous Month</option>
                    </select>
                    @endcan

                    <!-- Items Per Page -->
                    <select x-select class="form-select form-select-sm"
                            x-model.number="itemsPerPage"
                            @change="filterOrders()"
                            style="width: 120px;">
                        <option value="10">10 / page</option>
                        <option value="15">15 / page</option>
                        <option value="20">20 / page</option>
                        <option value="25">25 / page</option>
                        <option value="50">50 / page</option>
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
                @can('orders.filter_product')
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-body-secondary">Product</label>
                    <select x-select class="form-select form-select-sm" x-model="productFilter" @change="filterOrders()">
                        <option value="">All Products</option>
                        <template x-for="product in productsList" :key="product.id">
                            <option :value="product.id" x-text="`${product.name} (${product.sku})`"></option>
                        </template>
                    </select>
                </div>
                @endcan
                
                <!-- Fulfillment Filter -->
                @can('orders.filter_fulfillment')
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-body-secondary">Fulfillment Status</label>
                    <select x-select class="form-select form-select-sm" x-model="fulfillmentFilter" @change="filterOrders()">
                        <option value="">All</option>
                        <option value="fulfillable">Fulfillable</option>
                        <option value="unfulfillable">Unfulfillable</option>
                    </select>
                </div>
                @endcan

                <!-- Carrier Filter -->
                @can('orders.filter_carrier')
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-body-secondary">Carrier</label>
                    <select x-select class="form-select form-select-sm" x-model="carrierFilter" @change="filterOrders()">
                        <option value="">All Carriers</option>
                        <template x-for="carrier in carriersList" :key="carrier">
                            <option :value="carrier" x-text="carrier"></option>
                        </template>
                    </select>
                </div>
                @endcan

                <!-- Warehouse Filter -->
                @can('orders.filter_warehouse')
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-body-secondary">Warehouse</label>
                    <select x-select class="form-select form-select-sm" x-model="warehouseFilter" @change="filterOrders()">
                        <option value="">All Warehouses</option>
                        <template x-for="warehouse in warehousesList" :key="warehouse.id">
                            <option :value="warehouse.id" x-text="warehouse.name"></option>
                        </template>
                    </select>
                </div>
                @endcan

                <!-- Date Range From -->
                @can('orders.filter_date')
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-body-secondary">From Date</label>
                    <input type="date" class="form-control form-control-sm" x-model="fromDate" @change="filterOrders()">
                </div>

                <!-- Date Range To -->
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-body-secondary">To Date</label>
                    <input type="date" class="form-control form-control-sm" x-model="toDate" @change="filterOrders()">
                </div>
                @endcan

                <!-- State Filter -->
                @can('orders.filter_state')
                <div class="col-md-3 position-relative" @click.away="showStateDropdown = false" :style="showStateDropdown ? 'z-index: 1050;' : ''">
                    <label class="form-label small fw-semibold text-body-secondary">
                        State <span class="badge bg-secondary rounded-pill ms-1" style="font-size: 0.65rem;" x-text="stateFilter.length + ' / ' + Object.keys(statesList).length"></span>
                    </label>
                    <div class="form-control form-control-sm d-flex flex-wrap align-items-center gap-1" style="min-height: 31px; cursor: text;" @click="showStateDropdown = true; $refs.stateSearch.focus()">
                        <template x-for="state in stateFilter" :key="state">
                            <div class="badge text-bg-primary-subtle text-primary-emphasis d-flex align-items-center gap-1 border border-primary-subtle">
                                <span x-text="state" style="font-size: 11px;"></span>
                                <i class="bi bi-x cursor-pointer" @click.stop="toggleFilter('state', state)" style="font-size: 13px;"></i>
                            </div>
                        </template>
                        <div class="flex-grow-1 position-relative" style="min-width: 50px;">
                            <input x-ref="stateSearch" type="text" x-model="stateSearch" @focus="showStateDropdown = true" placeholder="Search States..." class="border-0 w-100 bg-transparent text-body" style="font-size: 12px; outline: none !important; box-shadow: none;">
                        </div>
                    </div>
                    <div x-show="showStateDropdown && filteredStates.length > 0" class="position-absolute w-100 bg-body border rounded shadow-lg mt-1" style="max-height: 200px; overflow-y: auto; z-index: 1050;">
                        <div class="px-3 py-2 cursor-pointer border-bottom bg-body-tertiary d-flex align-items-center" @click.stop="toggleAllFilter('state')">
                            <input type="checkbox" :checked="stateFilter.length > 0 && stateFilter.length === Object.keys(statesList).length" class="me-2" style="cursor: pointer;">
                            <span style="font-size: 12px; font-weight: bold;">Select All</span>
                        </div>
                        <template x-for="state in filteredStates" :key="state">
                            <div class="px-3 py-1 cursor-pointer custom-hover-bg d-flex align-items-center" @click.stop="toggleFilter('state', state)">
                                <input type="checkbox" :checked="stateFilter.includes(state)" class="me-2" style="cursor: pointer;">
                                <span style="font-size: 12px;" x-text="state"></span>
                            </div>
                        </template>
                    </div>
                </div>
                @endcan

                <!-- District Filter -->
                @can('orders.filter_district')
                <div class="col-md-3 position-relative" @click.away="showDistrictDropdown = false" :style="showDistrictDropdown ? 'z-index: 1050;' : ''">
                    <label class="form-label small fw-semibold text-body-secondary">
                        District <span class="badge bg-secondary rounded-pill ms-1" style="font-size: 0.65rem;" x-text="districtFilter.length + ' / ' + Object.keys(districtsList).length"></span>
                    </label>
                    <div class="form-control form-control-sm d-flex flex-wrap align-items-center gap-1" style="min-height: 31px; cursor: text;" @click="showDistrictDropdown = true; $refs.districtSearch.focus()">
                        <template x-for="district in districtFilter" :key="district">
                            <div class="badge text-bg-primary-subtle text-primary-emphasis d-flex align-items-center gap-1 border border-primary-subtle">
                                <span x-text="district" style="font-size: 11px;"></span>
                                <i class="bi bi-x cursor-pointer" @click.stop="toggleFilter('district', district)" style="font-size: 13px;"></i>
                            </div>
                        </template>
                        <div class="flex-grow-1 position-relative" style="min-width: 50px;">
                            <input x-ref="districtSearch" type="text" x-model="districtSearch" @focus="showDistrictDropdown = true" placeholder="Search Districts..." class="border-0 w-100 bg-transparent text-body" style="font-size: 12px; outline: none !important; box-shadow: none;">
                        </div>
                    </div>
                    <div x-show="showDistrictDropdown && filteredDistricts.length > 0" class="position-absolute w-100 bg-body border rounded shadow-lg mt-1" style="max-height: 200px; overflow-y: auto; z-index: 1050;">
                        <div class="px-3 py-2 cursor-pointer border-bottom bg-body-tertiary d-flex align-items-center" @click.stop="toggleAllFilter('district')">
                            <input type="checkbox" :checked="districtFilter.length > 0 && districtFilter.length === Object.keys(districtsList).length" class="me-2" style="cursor: pointer;">
                            <span style="font-size: 12px; font-weight: bold;">Select All</span>
                        </div>
                        <template x-for="district in filteredDistricts" :key="district">
                            <div class="px-3 py-1 cursor-pointer custom-hover-bg d-flex align-items-center" @click.stop="toggleFilter('district', district)">
                                <input type="checkbox" :checked="districtFilter.includes(district)" class="me-2" style="cursor: pointer;">
                                <span style="font-size: 12px;" x-text="district"></span>
                            </div>
                        </template>
                    </div>
                </div>
                @endcan

                <!-- Taluka Filter -->
                @can('orders.filter_taluka')
                <div class="col-md-3 position-relative" @click.away="showTalukaDropdown = false" :style="showTalukaDropdown ? 'z-index: 1050;' : ''">
                    <label class="form-label small fw-semibold text-body-secondary">
                        Taluka <span class="badge bg-secondary rounded-pill ms-1" style="font-size: 0.65rem;" x-text="talukaFilter.length + ' / ' + Object.keys(talukasList).length"></span>
                    </label>
                    <div class="form-control form-control-sm d-flex flex-wrap align-items-center gap-1" style="min-height: 31px; cursor: text;" @click="showTalukaDropdown = true; $refs.talukaSearch.focus()">
                        <template x-for="taluka in talukaFilter" :key="taluka">
                            <div class="badge text-bg-primary-subtle text-primary-emphasis d-flex align-items-center gap-1 border border-primary-subtle">
                                <span x-text="taluka" style="font-size: 11px;"></span>
                                <i class="bi bi-x cursor-pointer" @click.stop="toggleFilter('taluka', taluka)" style="font-size: 13px;"></i>
                            </div>
                        </template>
                        <div class="flex-grow-1 position-relative" style="min-width: 50px;">
                            <input x-ref="talukaSearch" type="text" x-model="talukaSearch" @focus="showTalukaDropdown = true" placeholder="Search Talukas..." class="border-0 w-100 bg-transparent text-body" style="font-size: 12px; outline: none !important; box-shadow: none;">
                        </div>
                    </div>
                    <div x-show="showTalukaDropdown && filteredTalukas.length > 0" class="position-absolute w-100 bg-body border rounded shadow-lg mt-1" style="max-height: 200px; overflow-y: auto; z-index: 1050;">
                        <div class="px-3 py-2 cursor-pointer border-bottom bg-body-tertiary d-flex align-items-center" @click.stop="toggleAllFilter('taluka')">
                            <input type="checkbox" :checked="talukaFilter.length > 0 && talukaFilter.length === Object.keys(talukasList).length" class="me-2" style="cursor: pointer;">
                            <span style="font-size: 12px; font-weight: bold;">Select All</span>
                        </div>
                        <template x-for="taluka in filteredTalukas" :key="taluka">
                            <div class="px-3 py-1 cursor-pointer custom-hover-bg d-flex align-items-center" @click.stop="toggleFilter('taluka', taluka)">
                                <input type="checkbox" :checked="talukaFilter.includes(taluka)" class="me-2" style="cursor: pointer;">
                                <span style="font-size: 12px;" x-text="taluka"></span>
                            </div>
                        </template>
                    </div>
                </div>
                @endcan

                <!-- Village Filter -->
                @can('orders.filter_village')
                <div class="col-md-3 position-relative" @click.away="showVillageDropdown = false" :style="showVillageDropdown ? 'z-index: 1050;' : ''">
                    <label class="form-label small fw-semibold text-body-secondary">
                        Village <span class="badge bg-secondary rounded-pill ms-1" style="font-size: 0.65rem;" x-text="villageFilter.length + ' / ' + Object.keys(villagesList).length"></span>
                    </label>
                    <div class="form-control form-control-sm d-flex flex-wrap align-items-center gap-1" style="min-height: 31px; cursor: text;" @click="showVillageDropdown = true; $refs.villageSearch.focus()">
                        <template x-for="village in villageFilter" :key="village">
                            <div class="badge text-bg-primary-subtle text-primary-emphasis d-flex align-items-center gap-1 border border-primary-subtle">
                                <span x-text="village" style="font-size: 11px;"></span>
                                <i class="bi bi-x cursor-pointer" @click.stop="toggleFilter('village', village)" style="font-size: 13px;"></i>
                            </div>
                        </template>
                        <div class="flex-grow-1 position-relative" style="min-width: 50px;">
                            <input x-ref="villageSearch" type="text" x-model="villageSearch" @focus="showVillageDropdown = true" placeholder="Search Villages..." class="border-0 w-100 bg-transparent text-body" style="font-size: 12px; outline: none !important; box-shadow: none;">
                        </div>
                    </div>
                    <div x-show="showVillageDropdown && filteredVillages.length > 0" class="position-absolute w-100 bg-body border rounded shadow-lg mt-1" style="max-height: 200px; overflow-y: auto; z-index: 1050;">
                        <div class="px-3 py-2 cursor-pointer border-bottom bg-body-tertiary d-flex align-items-center" @click.stop="toggleAllFilter('village')">
                            <input type="checkbox" :checked="villageFilter.length > 0 && villageFilter.length === Object.keys(villagesList).length" class="me-2" style="cursor: pointer;">
                            <span style="font-size: 12px; font-weight: bold;">Select All</span>
                        </div>
                        <template x-for="village in filteredVillages" :key="village">
                            <div class="px-3 py-1 cursor-pointer custom-hover-bg d-flex align-items-center" @click.stop="toggleFilter('village', village)">
                                <input type="checkbox" :checked="villageFilter.includes(village)" class="me-2" style="cursor: pointer;">
                                <span style="font-size: 12px;" x-text="village"></span>
                            </div>
                        </template>
                    </div>
                </div>
                @endcan

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
                    <span class="badge text-bg-primary-subtle text-primary-emphasis small d-none d-md-inline"
                          x-text="'Next: ' + [
                            bulkAvailableActions.canConfirm ? 'Confirm' : null,
                            bulkAvailableActions.canProcess ? 'Process' : null,
                            bulkAvailableActions.canDispatch ? 'Dispatch' : null,
                            bulkAvailableActions.canDeliver ? 'Deliver' : null,
                          ].filter(Boolean).join(', ') || 'No transitions'">
                    </span>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @can('orders.bulk_status')
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
                    <button class="btn btn-sm btn-warning"
                            x-show="bulkAvailableActions.canDispatch"
                            x-transition
                            @click="bulkUpdateStatus('dispatched')"
                            title="Move ready-to-ship orders → Dispatched">
                        <i class="bi bi-box-arrow-right me-1"></i>Dispatch
                    </button>
                    @can('orders.deliver')
                    <button class="btn btn-sm btn-success"
                            x-show="bulkAvailableActions.canDeliver"
                            x-transition
                            @click="bulkUpdateStatus('delivered')"
                            title="Move dispatched orders → Delivered">
                        <i class="bi bi-check2-all me-1"></i>Deliver
                    </button>
                    @endcan
                    @endcan

                    {{-- Separator before non-lifecycle actions --}}
                    <div class="vr" x-show="bulkAvailableActions.canCancel"></div>

                    @can('orders.export')
                    <button class="btn btn-sm btn-outline-info" 
                            @click="exportSelectedOrders()" 
                            title="Export Selected to CSV">
                        <i class="bi bi-download me-1"></i>Export CSV
                    </button>
                    @endcan

                    @can('orders.bulk_cancel')
                    {{-- Cancel (always shown if any selected order is cancellable) --}}
                    <button class="btn btn-sm btn-outline-danger"
                            x-show="bulkAvailableActions.canCancel"
                            x-transition
                            @click="bulkUpdateStatus('cancelled')"
                            title="Cancel selected orders">
                        <i class="bi bi-x-circle me-1"></i>Cancel
                    </button>
                    @endcan



                    @can('orders.bulk_print')
                    <div class="dropdown d-inline-block" x-show="bulkDocumentActions.canPrint" x-transition>
                        <button class="btn btn-sm btn-dark dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-printer me-1"></i>Print
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#" @click.prevent="bulkPrint('invoice')">
                                <i class="bi bi-file-pdf me-2"></i>Print Bulk Invoices
                            </a></li>
                            <li><a class="dropdown-item" href="#" @click.prevent="bulkPrint('cod')">
                                <i class="bi bi-file-earmark-pdf me-2"></i>Print Bulk COD
                            </a></li>
                        </ul>
                    </div>
                    @endcan

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
                                   style="cursor: pointer; appearance: auto; -webkit-appearance: checkbox;"
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
                        <tr :class="{ 'selected': selectedOrders.includes(String(order.id)), 'bg-danger bg-opacity-10 border-danger border-opacity-25': order.isUnfulfillable }">
                            <td>
                                <input type="checkbox" 
                                       class="form-check-input"
                                       style="cursor: pointer; appearance: auto; -webkit-appearance: checkbox;"
                                       :value="String(order.id)"
                                       x-model="selectedOrders">
                            </td>
                            <td>
                                <div class="fw-medium" x-text="order.orderNumber"></div>
                                <div class="d-flex align-items-center flex-wrap gap-1 mt-1">
                                    <small class="text-muted" x-text="'ID: ' + order.id"></small>
                                    <template x-if="order.warehouse">
                                        <span class="badge text-bg-secondary-subtle text-secondary-emphasis-subtle ms-1" style="font-size: 0.65rem;" title="Fulfillment Warehouse">
                                            <i class="bi bi-building me-1"></i><span x-text="order.warehouse.name"></span>
                                        </span>
                                    </template>
                                </div>
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
                                <div class="order-items small cursor-pointer custom-hover-bg p-2 rounded border border-transparent hover-border-secondary-subtle transition-all" @click="viewItems(order)" title="Click to view items">
                                    <div class="fw-medium d-flex align-items-center flex-wrap gap-1">
                                        <i class="bi bi-box-seam text-secondary me-1"></i>
                                        <span x-text="order.itemCount + ' item' + (order.itemCount > 1 ? 's' : '')"></span>
                                        <template x-if="order.isUnfulfillable">
                                            <span class="badge bg-danger ms-1" style="font-size: 0.65rem;"><i class="bi bi-exclamation-triangle-fill me-1"></i>Unfulfillable</span>
                                        </template>
                                    </div>
                                    <small class="text-muted d-block mt-1" style="max-width: 200px;" x-text="order.items.length > 0 ? order.items[0].name + (order.itemCount > 1 ? ' +' + (order.itemCount - 1) + ' more' : '') : '—'"></small>
                                </div>
                            </td>
                            <td class="fw-medium small" x-text="`₹ ${order.total}`"></td>
                            <td>
                                <span class="badge small" 
                                      :class="`text-bg-${getStatusTheme(order.status)}`"
                                      x-text="order.statusLabel"></span>
                                <template x-if="order.status === 'pending_confirmation' && order.scheduledConfirmDate">
                                    <div class="mt-1" style="font-size: 0.7rem;">
                                        <div class="text-primary fw-semibold" title="Scheduled Confirmation Date">
                                            <i class="bi bi-calendar-event me-1"></i>
                                            <span x-text="new Date(order.scheduledConfirmDate).toLocaleString('en-IN', { day: '2-digit', month: 'short', hour: '2-digit', minute:'2-digit', hour12: true })"></span>
                                        </div>
                                        <div class="text-muted mt-1" x-show="order.confirmAttempts > 0">
                                            <i class="bi bi-arrow-repeat me-1"></i>Attempts: <span class="fw-bold" x-text="order.confirmAttempts"></span>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="order.shipment?.next_followup_date && (order.status === 'dispatched' || order.status === 'shipped')">
                                    <div class="mt-1" style="font-size: 0.7rem;">
                                        <div class="text-warning-emphasis fw-semibold" title="Scheduled Delivery Date">
                                            <i class="bi bi-calendar-event me-1 text-warning"></i>
                                            <span x-text="new Date(order.shipment.next_followup_date).toLocaleString('en-IN', { day: '2-digit', month: 'short', hour: '2-digit', minute:'2-digit', hour12: true })"></span>
                                        </div>
                                        <div class="text-muted mt-1" x-show="order.shipment.delivery_attempts > 0">
                                            <i class="bi bi-arrow-repeat me-1 text-danger"></i>Attempts: <span class="fw-bold text-danger" x-text="order.shipment.delivery_attempts"></span>
                                        </div>
                                        <div class="text-muted mt-1" style="max-width: 150px;" x-show="order.shipment.reschedule_reason" :title="order.shipment.reschedule_reason">
                                            <i class="bi bi-info-circle me-1"></i><span x-text="order.shipment.reschedule_reason"></span>
                                        </div>
                                    </div>
                                </template>
                            </td>
                            <td>
                                <template x-if="!order.isDraft">
                                    <div>
                                        <div class="small fw-medium" x-text="order.orderDate ? new Date(order.orderDate).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }) : '—'"></div>
                                        <small class="text-muted" x-text="order.orderDate ? new Date(order.orderDate).toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', hour12: true }) : ''"></small>
                                    </div>
                                </template>
                                <template x-if="order.isDraft">
                                    <div class="d-flex flex-column gap-1">
                                        <div>
                                            <div class="small fw-medium" x-text="order.orderDate ? new Date(order.orderDate).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }) : '—'"></div>
                                            <small class="text-muted" x-text="order.orderDate ? new Date(order.orderDate).toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', hour12: true }) : ''"></small>
                                        </div>
                                        <div class="border-top border-warning-subtle pt-1 mt-1">
                                            <div class="small fw-bold text-warning-emphasis d-flex align-items-center gap-1">
                                                <i class="bi bi-clock-history"></i>
                                                <span x-text="order.futureOrderDate ? new Date(order.futureOrderDate).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }) : 'Pending'"></span>
                                            </div>
                                            <span class="badge rounded-pill text-bg-warning-subtle text-warning-emphasis border border-warning-subtle" style="font-size: 9px; line-height: 1;">Scheduled For</span>
                                        </div>
                                    </div>
                                </template>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                            type="button" 
                                            data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        @can('orders.view')
                                        <li><a class="dropdown-item" href="#" @click.prevent="viewOrder(order)">
                                            <i class="bi bi-eye me-2"></i>View Details
                                        </a></li>
                                        @endcan
                                        <template x-if="!['cancelled', 'delivered', 'returned'].includes(order.status)">
                                            @can('orders.edit')
                                            <li><a class="dropdown-item" href="#" @click.prevent="editOrder(order)">
                                                <i class="bi bi-pencil-square me-2"></i>Edit Order
                                            </a></li>
                                            @endcan
                                        </template>
                                        
                                        <!-- Context Actions -->
                                        <template x-if="order.status === 'pending' || order.status === 'pending_confirmation'">
                                            @can('orders.confirm')
                                            <li><a class="dropdown-item" href="#" @click.prevent="confirmOrder(order)">
                                                <i class="bi bi-check-circle me-2"></i>Confirm Order
                                            </a></li>
                                            @endcan
                                        </template>
                                        <template x-if="order.status === 'confirmed'">
                                            @can('orders.processing')
                                            <li><a class="dropdown-item" href="#" @click.prevent="processOrder(order)">
                                                <i class="bi bi-gear me-2"></i>Process Order
                                            </a></li>
                                            @endcan
                                        </template>
                                        <template x-if="order.status === 'processing'">
                                            @can('orders.ship')
                                            <li><a class="dropdown-item" href="#" @click.prevent="openShipModal(order)">
                                                <i class="bi bi-truck me-2"></i>Ship (Ready to Ship)
                                            </a></li>
                                            @endcan
                                        </template>
                                        <template x-if="order.status === 'ready_to_ship'">
                                            @can('orders.dispatch')
                                            <li><a class="dropdown-item" href="#" @click.prevent="dispatchOrder(order)">
                                                <i class="bi bi-send me-2"></i>Dispatch
                                            </a></li>
                                            @endcan
                                        </template>
                                        <template x-if="order.status === 'dispatched' || order.status === 'shipped'">
                                            @can('orders.deliver')
                                            <li><a class="dropdown-item" href="#" @click.prevent="deliverOrder(order)">
                                                <i class="bi bi-check2-all me-2"></i>Deliver
                                            </a></li>
                                            @endcan
                                        </template>
                                        <template x-if="['delivered', 'dispatched', 'shipped'].includes(order.status)">
                                            @can('orders.return')
                                            <li><a class="dropdown-item text-warning" href="#" @click.prevent="returnOrder(order)">
                                                <i class="bi bi-arrow-return-left me-2"></i>Return Order
                                            </a></li>
                                            @endcan
                                        </template>
                                        <template x-if="['pending', 'pending_confirmation', 'confirmed', 'processing', 'ready_to_ship'].includes(order.status)">
                                            @can('orders.cancel')
                                            <li><a class="dropdown-item text-danger" href="#" @click.prevent="cancelOrder(order)">
                                                <i class="bi bi-x-circle me-2"></i>Cancel Order
                                            </a></li>
                                            @endcan
                                        </template>
                                        @can('orders.revert_status')
                                        <li><a class="dropdown-item" href="#" @click.prevent="revertStatus(order)">
                                            <i class="bi bi-arrow-left-right me-2"></i>Revert Status
                                        </a></li>
                                        @endcan
                                        <li><hr class="dropdown-divider"></li>
                                        @can('orders.invoice_pdf')
                                        <li><a class="dropdown-item" href="#" @click.prevent="printInvoice(order)">
                                            <i class="bi bi-file-pdf me-2"></i>Print Invoice
                                        </a></li>
                                        @endcan
                                        @can('orders.cod')
                                        <li><a class="dropdown-item" href="#" @click.prevent="printCOD(order)">
                                            <i class="bi bi-file-earmark-pdf me-2"></i>Print COD Receipt
                                        </a></li>
                                        @endcan

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
<div class="modal fade order-detail-modal" id="orderDetailModal" aria-labelledby="orderDetailModalLabel" aria-hidden="true" style="z-index: 1070;">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-0 rounded-4" x-show="selectedOrder">
            <template x-if="selectedOrder">
                <div class="d-flex flex-column h-100 bg-body rounded-4 overflow-hidden">
                    
                    <!-- Header with Gradient and Status -->
                    <div class="modal-header border-bottom-0 pb-4 pt-4 px-4 px-lg-5 bg-body-tertiary">
                        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between w-100 gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-body-secondary text-primary p-3 rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                                    <i class="bi bi-receipt fs-3"></i>
                                </div>
                                <div>
                                    <h4 class="modal-title fw-bolder mb-1" id="orderDetailModalLabel" style="letter-spacing: -0.5px;">
                                        Order <span class="text-primary" x-text="selectedOrder.orderNumber"></span>
                                    </h4>
                                    <div class="d-flex align-items-center flex-wrap gap-2 mt-1">
                                        <p class="text-muted small mb-0 d-flex align-items-center gap-2">
                                            <i class="bi bi-calendar3"></i> <span x-text="selectedOrder.orderDate ? new Date(selectedOrder.orderDate).toLocaleString('en-US', { dateStyle: 'medium', timeStyle: 'short' }) : 'N/A'"></span>
                                        </p>
                                        <template x-if="selectedOrder.warehouse">
                                            <span class="badge text-bg-secondary-subtle text-secondary-emphasis-subtle" title="Fulfillment Warehouse">
                                                <i class="bi bi-building me-1"></i><span x-text="selectedOrder.warehouse.name"></span>
                                            </span>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <span class="badge rounded-pill px-4 py-2 fs-6 shadow-sm" 
                                      :class="`bg-${getStatusTheme(selectedOrder.status)}-subtle text-${getStatusTheme(selectedOrder.status)}-emphasis border border-${getStatusTheme(selectedOrder.status)}-subtle`">
                                    <i class="bi bi-circle-fill me-2" style="font-size: 0.5rem; vertical-align: middle;"></i>
                                    <span x-text="selectedOrder.statusLabel"></span>
                                </span>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                        </div>
                    </div>

                    <div class="modal-body p-0" style="overflow-y: auto;">
                        <div class="row g-0" style="min-height: 100%;">
                            
                            <!-- Left Column: Details & Items -->
                            <div class="col-lg-8 p-4 p-lg-5 bg-body-tertiary">
                                <!-- Quick Stats Row -->
                                <div class="row g-3 mb-4">
                                    <div class="col-sm-4">
                                        <div class="card h-100 border-0 shadow-sm rounded-4">
                                            <div class="card-body p-3 d-flex align-items-center gap-3">
                                                <div class="text-bg-primary-subtle text-primary-emphasis p-2 rounded-3"><i class="bi bi-credit-card fs-5"></i></div>
                                                <div>
                                                    <p class="small text-muted mb-0 fw-semibold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">Payment</p>
                                                    <p class="fw-bold mb-0 text-body-emphasis" x-text="selectedOrder.paymentMethod || 'N/A'"></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="card h-100 border-0 shadow-sm rounded-4">
                                            <div class="card-body p-3 d-flex align-items-center gap-3">
                                                <div class="text-bg-success-subtle text-success-emphasis p-2 rounded-3"><i class="bi bi-tag fs-5"></i></div>
                                                <div>
                                                    <p class="small text-muted mb-0 fw-semibold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">Order Type</p>
                                                    <p class="fw-bold mb-0 text-body-emphasis text-capitalize" x-text="selectedOrder.type || 'Sale'"></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="card h-100 border-0 shadow-sm rounded-4">
                                            <div class="card-body p-3 d-flex align-items-center gap-3">
                                                <div class="text-bg-info-subtle text-info-emphasis p-2 rounded-3"><i class="bi bi-person-badge fs-5"></i></div>
                                                <div>
                                                    <p class="small text-muted mb-0 fw-semibold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">Created By</p>
                                                    <p class="fw-bold mb-0 text-body-emphasis" x-text="selectedOrder.createdBy.name"></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Customer Info -->
                                <div class="card border-0 shadow-sm rounded-4 mb-4">
                                    <div class="card-header bg-body border-bottom-0 pt-4 pb-0 px-4">
                                        <h6 class="fw-bold mb-0 text-body-emphasis d-flex align-items-center gap-2">
                                            <i class="bi bi-person-hearts text-danger fs-5"></i> Customer & Fulfillment
                                        </h6>
                                    </div>
                                    <div class="card-body p-4">
                                        <div class="row g-4">
                                            <div class="col-md-6 border-end-md">
                                                <div class="d-flex align-items-start gap-3 mb-3">
                                                    <img :src="selectedOrder.customer.avatar" class="rounded-circle shadow-sm" width="48" height="48" alt="Customer">
                                                    <div>
                                                        <h6 class="fw-bold mb-1" x-text="selectedOrder.customer.name"></h6>
                                                        <p class="text-muted small mb-1 d-flex align-items-center gap-1"><i class="bi bi-envelope"></i> <span x-text="selectedOrder.customer.email"></span></p>
                                                        <p class="text-muted small mb-1 d-flex align-items-center gap-1"><i class="bi bi-telephone"></i> <span x-text="selectedOrder.customer.phone || 'N/A'"></span></p>
                                                        <template x-if="selectedOrder.customer.secondaryPhone">
                                                            <p class="text-muted small mb-1 d-flex align-items-center gap-1"><i class="bi bi-telephone-plus"></i> <span x-text="selectedOrder.customer.secondaryPhone"></span></p>
                                                        </template>
                                                        <template x-if="selectedOrder.customer.relativeName">
                                                            <p class="text-muted small mb-1 d-flex align-items-center gap-1"><i class="bi bi-people"></i> <span x-text="selectedOrder.customer.relativeName"></span> <span x-show="selectedOrder.customer.relativePhone" x-text="`(${selectedOrder.customer.relativePhone})`"></span></p>
                                                        </template>
                                                        <template x-if="selectedOrder.customer.company">
                                                            <p class="text-muted small mb-1 d-flex align-items-center gap-1"><i class="bi bi-building"></i> <span x-text="selectedOrder.customer.company"></span></p>
                                                        </template>
                                                        <template x-if="selectedOrder.customer.pan">
                                                            <p class="text-muted small mb-1 d-flex align-items-center gap-1"><i class="bi bi-card-text"></i> PAN: <span x-text="selectedOrder.customer.pan"></span></p>
                                                        </template>
                                                        <template x-if="selectedOrder.customer.gstin">
                                                            <p class="text-muted small mb-0 d-flex align-items-center gap-1"><i class="bi bi-receipt"></i> GSTIN: <span x-text="selectedOrder.customer.gstin"></span></p>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <p class="fw-bold small text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Shipping Address</p>
                                                    <p class="small mb-0 text-body-emphasis" x-text="selectedOrder.shippingAddress ? selectedOrder.shippingAddress.formatted : 'N/A'"></p>
                                                </div>
                                                <div>
                                                    <p class="fw-bold small text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Billing Address</p>
                                                    <p class="small mb-0 text-body-emphasis" x-text="selectedOrder.billingAddress ? selectedOrder.billingAddress.formatted : 'N/A'"></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Order Items Table -->
                                <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                                    <div class="card-header bg-body border-bottom pt-4 pb-3 px-4 d-flex justify-content-between align-items-center">
                                        <h6 class="fw-bold mb-0 text-body-emphasis d-flex align-items-center gap-2">
                                            <i class="bi bi-box-seam text-primary fs-5"></i> Order Items
                                        </h6>
                                        <span class="badge text-bg-primary-subtle text-primary-emphasis rounded-pill px-3" x-text="`${selectedOrder.itemCount} Items`"></span>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-borderless table-hover align-middle mb-0 text-nowrap">
                                            <thead class="bg-body-tertiary">
                                                <tr>
                                                    <th class="fw-semibold text-muted small py-3 ps-4">Product Details</th>
                                                    <th class="fw-semibold text-muted small py-3 text-end">Price</th>
                                                    <th class="fw-semibold text-muted small py-3 text-center">Qty</th>
                                                    <th class="fw-semibold text-muted small py-3 text-end">Discount</th>
                                                    <th class="fw-semibold text-muted small py-3 text-end">Tax</th>
                                                    <th class="fw-semibold text-muted small py-3 text-end pe-4">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <template x-for="(item, idx) in selectedOrder.items" :key="idx">
                                                    <tr class="border-bottom">
                                                        <td class="ps-4 py-3">
                                                            <div class="d-flex align-items-center gap-3">
                                                                <img :src="item.image || '/assets/images/product-placeholder.svg'"
                                                                     class="rounded-3 shadow-sm object-fit-cover"
                                                                     width="48"
                                                                     height="48"
                                                                     :alt="item.name"
                                                                     x-on:error="$el.src='/assets/images/product-placeholder.svg'">
                                                                <div>
                                                                    <p class="fw-bold text-body-emphasis mb-0" x-text="item.name"></p>
                                                                    <p class="text-muted small mb-0 font-monospace" style="font-size: 0.75rem;" x-text="item.sku || 'No SKU'"></p>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="text-end py-3">
                                                            <span class="text-body-emphasis fw-medium" x-text="`₹ ${parseFloat(item.price || 0).toFixed(2)}`"></span>
                                                        </td>
                                                        <td class="text-center py-3">
                                                            <span class="badge bg-secondary bg-opacity-10 text-body-emphasis px-2 py-1 rounded-3" x-text="item.quantity || 0"></span>
                                                        </td>
                                                        <td class="text-end py-3 small">
                                                            <div class="text-success fw-medium mb-1" x-show="parseFloat(item.discount || 0) > 0" x-text="`-₹ ${parseFloat(item.discount || 0).toFixed(2)}`"></div>
                                                            <template x-if="parseFloat(item.discount || 0) > 0 && item.discountBadgeLabel">
                                                                <div class="badge bg-success bg-opacity-10 border border-success border-opacity-25 text-success d-inline-flex align-items-center gap-1 px-2 py-1 rounded-3 mt-1">
                                                                    <i class="bi bi-tag-fill"></i>
                                                                    <span class="fw-bold" style="font-size: 11px;" x-text="item.discountBadgeLabel"></span>
                                                                </div>
                                                            </template>
                                                            <div class="text-muted" x-show="!item.discount || parseFloat(item.discount) == 0">—</div>
                                                        </td>
                                                        <td class="text-end py-3 small">
                                                            <div class="text-muted fw-medium" x-text="`+₹ ${parseFloat(item.tax || 0).toFixed(2)}`"></div>
                                                            <div class="text-muted opacity-75" style="font-size: 0.7rem;" x-show="parseFloat(item.taxRate || 0) > 0" x-text="`(${parseFloat(item.taxRate).toFixed(0)}%)`"></div>
                                                        </td>
                                                        <td class="text-end pe-4 py-3">
                                                            <span class="fw-bold text-primary" x-text="`₹ ${((parseFloat(item.price || 0) * parseFloat(item.quantity || 0)) - parseFloat(item.discount || 0) + parseFloat(item.tax || 0)).toFixed(2)}`"></span>
                                                        </td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Financial Summary -->
                                <div class="card border-0 shadow-sm rounded-4">
                                    <div class="card-body p-4 bg-body rounded-4">
                                        <div class="row justify-content-end">
                                            <div class="col-md-6 col-lg-5">
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span class="text-muted fw-medium">Subtotal</span>
                                                    <span class="text-body-emphasis fw-bold" x-text="`₹ ${formatCurrency(selectedOrder.subtotal)}`"></span>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <div>
                                                        <span class="text-muted fw-medium">Discount</span>
                                                        <span x-show="selectedOrder.couponCode" class="badge bg-success ms-2 rounded-pill" x-text="selectedOrder.couponCode"></span>
                                                        <span x-show="selectedOrder.appliedOfferName" class="text-muted d-block" style="font-size: 10px;" x-text="selectedOrder.appliedOfferName"></span>
                                                    </div>
                                                    <span class="text-success fw-bold" x-text="`-₹ ${formatCurrency(selectedOrder.discountTotal)}`"></span>
                                                </div>
                                                <div class="d-flex justify-content-between mb-3 border-bottom pb-3">
                                                    <span class="text-muted fw-medium">Tax</span>
                                                    <span class="text-body-emphasis fw-bold" x-text="`₹ ${formatCurrency(selectedOrder.taxTotal)}`"></span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="text-body-emphasis fw-bolder fs-5">Grand Total</span>
                                                    <span class="text-primary fw-bolder fs-4" x-text="`₹ ${formatCurrency(selectedOrder.total)}`"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Right Column: Warehouse / Logistics / Timeline -->
                            <div class="col-lg-4 p-4 p-lg-5 border-start bg-body" style="height: fit-content; align-self: flex-start;">
                                
                                <!-- Document Actions -->
                                <div class="d-flex flex-wrap gap-2 mb-4 w-100">
                                    <template x-if="selectedOrder.invoice">
                                        <div class="d-flex flex-wrap gap-2 w-100">
                                            @can('orders.invoice_pdf')
                                            <button class="btn btn-primary flex-grow-1 shadow-sm rounded-pill fw-semibold py-2 transition-all hover-shadow" @click="printInvoice(selectedOrder)">
                                                <i class="bi bi-file-earmark-pdf me-2"></i>Print Invoice
                                            </button>
                                            @endcan
                                            @can('orders.cod')
                                            <button class="btn btn-info text-white flex-grow-1 shadow-sm rounded-pill fw-semibold py-2 transition-all hover-shadow" @click="printCOD(selectedOrder)">
                                                <i class="bi bi-file-earmark-pdf me-2"></i>COD Receipt
                                            </button>
                                            @endcan
                                        </div>
                                    </template>
                                    <template x-if="!selectedOrder.invoice">
                                        @can('orders.generate_invoice')
                                        <button class="btn btn-primary flex-grow-1 shadow-sm rounded-pill fw-semibold py-2 transition-all hover-shadow" @click="generateAndPrintInvoice(selectedOrder)">
                                            <i class="bi bi-receipt-cutoff me-2"></i>Generate Invoice & Print
                                        </button>
                                        @endcan
                                    </template>

                                </div>
                                
                                <!-- Order Actions -->
                                <div class="card border-0 shadow-sm rounded-4 mb-4 bg-primary bg-opacity-10 border border-primary border-opacity-25">
                                    <div class="card-body p-3">
                                        <h6 class="fw-bold mb-3 text-primary" style="font-size: 0.8rem; text-transform: uppercase;">
                                            <i class="bi bi-lightning-charge me-1"></i> Order Actions
                                        </h6>
                                        <div class="d-flex flex-wrap gap-2">
                                            <button class="btn btn-sm btn-outline-secondary flex-grow-1 shadow-sm fw-semibold border-secondary border-opacity-25" @click="editOrder(selectedOrder)">
                                                <i class="bi bi-pencil-square me-1"></i>Edit
                                            </button>
                                            <template x-if="['pending', 'pending_confirmation'].includes(selectedOrder.status)">
                                                @can('orders.confirm')
                                                <button class="btn btn-sm btn-primary flex-grow-1 shadow-sm fw-semibold" @click="confirmOrder(selectedOrder)">
                                                    <i class="bi bi-check-circle me-1"></i>Confirm
                                                </button>
                                                @endcan
                                            </template>
                                            <template x-if="selectedOrder.status === 'confirmed'">
                                                @can('orders.processing')
                                                <button class="btn btn-sm btn-info text-white flex-grow-1 shadow-sm fw-semibold" @click="processOrder(selectedOrder)">
                                                    <i class="bi bi-gear me-1"></i>Process
                                                </button>
                                                @endcan
                                            </template>
                                            <template x-if="selectedOrder.status === 'processing'">
                                                @can('orders.ship')
                                                <button class="btn btn-sm btn-warning text-body-emphasis flex-grow-1 shadow-sm fw-semibold" @click="openShipModal(selectedOrder)">
                                                    <i class="bi bi-truck me-1"></i>Ready to Ship
                                                </button>
                                                @endcan
                                            </template>
                                            <template x-if="selectedOrder.status === 'ready_to_ship'">
                                                @can('orders.dispatch')
                                                <button class="btn btn-sm btn-primary flex-grow-1 shadow-sm fw-semibold" @click="dispatchOrder(selectedOrder)">
                                                    <i class="bi bi-send me-1"></i>Dispatch
                                                </button>
                                                @endcan
                                            </template>
                                            <template x-if="selectedOrder.status === 'dispatched' || selectedOrder.status === 'shipped'">
                                                @can('orders.deliver')
                                                <button class="btn btn-sm btn-success flex-grow-1 shadow-sm fw-semibold" @click="deliverOrder(selectedOrder)">
                                                    <i class="bi bi-check2-all me-1"></i>Deliver
                                                </button>
                                                @endcan
                                            </template>
                                            <template x-if="['delivered', 'dispatched', 'shipped'].includes(selectedOrder.status)">
                                                @can('orders.return')
                                                <button class="btn btn-sm btn-outline-warning flex-grow-1 shadow-sm fw-semibold" @click="returnOrder(selectedOrder)">
                                                    <i class="bi bi-arrow-return-left me-1"></i>Return
                                                </button>
                                                @endcan
                                            </template>
                                            <template x-if="['pending', 'pending_confirmation', 'confirmed', 'processing', 'ready_to_ship'].includes(selectedOrder.status)">
                                                @can('orders.cancel')
                                                <button class="btn btn-sm btn-outline-danger flex-grow-1 shadow-sm fw-semibold" @click="cancelOrder(selectedOrder)">
                                                    <i class="bi bi-x-circle me-1"></i>Cancel
                                                </button>
                                                @endcan
                                            </template>
                                        </div>
                                    </div>
                                </div>


                                <!-- Logistics / Warehouse -->
                                <div class="card border-0 shadow-sm rounded-4 mb-4">
                                    <div class="card-header bg-body border-bottom-0 pt-4 pb-2 px-4">
                                        <h6 class="fw-bold mb-0 text-body-emphasis d-flex align-items-center gap-2">
                                            <i class="bi bi-building text-secondary fs-5"></i> Fulfillment Center
                                        </h6>
                                    </div>
                                    <div class="card-body p-4 pt-2">
                                        <div class="p-3 bg-body-tertiary rounded-4 border">
                                            <p class="mb-1 fw-bold text-body-emphasis" x-text="selectedOrder.warehouse ? selectedOrder.warehouse.name : 'Unassigned'"></p>
                                            <p class="small text-muted mb-2 lh-sm" x-text="selectedOrder.warehouse ? selectedOrder.warehouse.address : 'N/A'"></p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="badge bg-secondary bg-opacity-10 text-body-emphasis rounded-pill fw-medium px-2 py-1"><i class="bi bi-telephone-fill me-1"></i> <span x-text="selectedOrder.warehouse ? selectedOrder.warehouse.phone : 'N/A'"></span></span>
                                                <template x-if="selectedOrder.warehouse && selectedOrder.warehouse.gstin && selectedOrder.warehouse.gstin !== 'N/A'">
                                                    <span class="badge text-bg-info-subtle text-info-emphasis rounded-pill fw-medium px-2 py-1"><i class="bi bi-receipt me-1"></i> <span x-text="selectedOrder.warehouse.gstin"></span></span>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Shipment Tracking -->
                                <template x-if="selectedOrder.shipment">
                                    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-primary bg-opacity-10 border border-primary border-opacity-25">
                                        <div class="card-body p-4">
                                            <h6 class="fw-bold mb-3 text-primary d-flex align-items-center gap-2">
                                                <i class="bi bi-truck fs-5"></i> Shipping Details
                                            </h6>
                                            <template x-if="selectedOrder.shipment">
                                                <div class="bg-body p-3 rounded-4 shadow-sm mb-3">
                                                    <p class="small text-muted mb-1 text-uppercase fw-semibold" style="font-size: 0.65rem;">Tracking Number</p>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <p class="fw-bold text-body-emphasis font-monospace mb-0 fs-6" x-text="selectedOrder.shipment.trackingNo"></p>
                                                        <span class="badge text-bg-info-subtle text-info-emphasis border-opacity-25 rounded-pill" x-text="selectedOrder.shipment.carrier"></span>
                                                    </div>
                                                </div>
                                            </template>

                                            <template x-if="selectedOrder.assignedService">
                                                <div class="bg-body p-3 rounded-4 shadow-sm mb-3">
                                                    <p class="small text-muted mb-2 text-uppercase fw-semibold" style="font-size: 0.65rem;">Assigned Service</p>
                                                    <div class="border rounded-3 p-3">
                                                        <div class="d-flex justify-content-between align-items-start gap-2">
                                                            <div>
                                                                <p class="fw-bold text-body-emphasis mb-1" x-text="selectedOrder.assignedService.name"></p>
                                                                <p class="small text-muted mb-0" x-show="selectedOrder.assignedService.code" x-text="`Code: ${selectedOrder.assignedService.code}`"></p>
                                                                <p class="small text-secondary mb-0 mt-1" x-show="selectedOrder.assignedService.description" x-text="selectedOrder.assignedService.description"></p>
                                                            </div>
                                                            <span class="badge text-bg-primary-subtle text-primary-emphasis rounded-pill" x-text="`Priority ${selectedOrder.assignedService.priority}`"></span>
                                                        </div>
                                                        <div class="mt-2 pt-2 border-top" x-show="selectedOrder.assignedService.providers.length">
                                                            <p class="small text-muted mb-1">Mapped service providers</p>
                                                            <template x-for="provider in selectedOrder.assignedService.providers" :key="`${selectedOrder.assignedService.name}-${provider.name}-${provider.phone}`">
                                                                <div class="small text-body-emphasis">
                                                                    <i class="bi bi-person-fill me-1"></i><span x-text="provider.name"></span>
                                                                    <template x-if="provider.phone"><span class="text-muted ms-2"><i class="bi bi-telephone-fill me-1"></i><span x-text="provider.phone"></span></span></template>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                            
                                            <template x-if="selectedOrder.shipment.events && selectedOrder.shipment.events.length">
                                                <div class="position-relative ms-2 ps-3 border-start border-primary border-opacity-25 border-2">
                                                    <template x-for="(event, i) in selectedOrder.shipment.events" :key="event.id">
                                                        <div class="position-relative mb-3">
                                                            <div class="position-absolute bg-primary rounded-circle" style="width: 10px; height: 10px; left: -22px; top: 5px;"></div>
                                                            <p class="fw-bold text-body-emphasis mb-0 small" x-text="event.status"></p>
                                                            <p class="text-muted mb-0" style="font-size: 0.75rem;" x-text="formatDateTime(event.created_at)"></p>
                                                            <p class="text-secondary small mt-1 lh-sm" x-text="event.description || event.remark"></p>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>

                                <!-- Invoice Details -->
                                <template x-if="selectedOrder.invoice">
                                    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-secondary bg-opacity-10 border border-secondary border-opacity-25">
                                        <div class="card-body p-4">
                                            <h6 class="fw-bold mb-3 text-secondary d-flex align-items-center gap-2">
                                                <i class="bi bi-receipt fs-5"></i> Invoice Details
                                            </h6>
                                            <div class="bg-body p-3 rounded-4 shadow-sm">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span class="small text-muted">Invoice No:</span>
                                                    <span class="fw-bold text-body-emphasis" x-text="selectedOrder.invoice.number"></span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span class="small text-muted">Date:</span>
                                                    <span class="fw-medium small" x-text="formatDate(selectedOrder.invoice.date)"></span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span class="small text-muted">Status:</span>
                                                    <span class="badge bg-secondary" x-text="selectedOrder.invoice.status"></span>
                                                </div>
                                                <hr class="my-2">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="small text-muted">Paid:</span>
                                                    <span class="text-success fw-bold" x-text="`₹ ${selectedOrder.invoice.paid.toFixed(2)}`"></span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="small text-muted">Due:</span>
                                                    <span class="text-danger fw-bold" x-text="`₹ ${selectedOrder.invoice.due.toFixed(2)}`"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <!-- Payments Tracking -->
                                <template x-if="selectedOrder.payments && selectedOrder.payments.length > 0">
                                    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-success bg-opacity-10 border border-success border-opacity-25">
                                        <div class="card-body p-4">
                                            <h6 class="fw-bold mb-3 text-success d-flex align-items-center gap-2">
                                                <i class="bi bi-cash-stack fs-5"></i> Payments
                                            </h6>
                                            <div class="position-relative ms-2 ps-3 border-start border-success border-opacity-25 border-2">
                                                <template x-for="(payment, i) in selectedOrder.payments" :key="payment.id">
                                                    <div class="position-relative mb-3">
                                                        <div class="position-absolute bg-success rounded-circle" style="width: 10px; height: 10px; left: -22px; top: 5px;"></div>
                                                        <div class="d-flex justify-content-between align-items-start">
                                                            <div>
                                                                <p class="fw-bold text-body-emphasis mb-0 small" x-text="`₹ ${payment.amount}`"></p>
                                                                <p class="text-muted mb-0" style="font-size: 0.75rem;" x-text="formatDateTime(payment.date)"></p>
                                                                <p class="text-secondary small mt-1 lh-sm mb-0">
                                                                    <span x-text="payment.method"></span>
                                                                    <span x-show="payment.transactionId && payment.transactionId !== 'N/A'" x-text="` | Txn: ${payment.transactionId}`"></span>
                                                                </p>
                                                            </div>
                                                            <span class="badge" 
                                                                  :class="{
                                                                    'bg-success': payment.status === 'completed',
                                                                    'bg-warning': payment.status === 'pending' || payment.status === 'authorized',
                                                                    'bg-danger': payment.status === 'failed' || payment.status === 'refunded'
                                                                  }"
                                                                  x-text="payment.statusLabel"></span>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <!-- Returns Tracking -->
                                <template x-if="selectedOrder.original && (selectedOrder.original.order_returns && selectedOrder.original.order_returns.length > 0 || selectedOrder.original.orderReturns && selectedOrder.original.orderReturns.length > 0)">
                                    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-danger bg-opacity-10 border border-danger border-opacity-25">
                                        <div class="card-body p-4">
                                            <h6 class="fw-bold mb-3 text-danger d-flex align-items-center gap-2">
                                                <i class="bi bi-arrow-return-left fs-5"></i> Returns & Refunds
                                            </h6>
                                            <div class="position-relative ms-2 ps-3 border-start border-danger border-opacity-25 border-2">
                                                <template x-for="(ret, i) in (selectedOrder.original.order_returns || selectedOrder.original.orderReturns)" :key="ret.id || i">
                                                    <div class="position-relative mb-3">
                                                        <div class="position-absolute bg-danger rounded-circle" style="width: 10px; height: 10px; left: -22px; top: 5px;"></div>
                                                        <div class="d-flex justify-content-between align-items-start">
                                                            <div>
                                                                <p class="fw-bold text-body-emphasis mb-0 small" x-text="ret.return_no || 'Return'"></p>
                                                                <p class="text-muted mb-0" style="font-size: 0.75rem;" x-text="formatDateTime(ret.created_at)"></p>
                                                                <p class="text-secondary small mt-1 lh-sm mb-0">Reason: <span x-text="ret.reason || 'N/A'"></span></p>
                                                                <p class="text-danger small fw-medium mt-1 mb-0" x-show="ret.refund_amount > 0">Refund: ₹ <span x-text="ret.refund_amount"></span></p>
                                                            </div>
                                                            <span class="badge bg-danger" x-text="ret.status"></span>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <!-- Order Status Timeline -->
                                <template x-if="selectedOrder.original && selectedOrder.original.status_logs && selectedOrder.original.status_logs.length > 0">
                                    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-secondary bg-opacity-10 border border-secondary border-opacity-25">
                                        <div class="card-body p-4">
                                            <h6 class="fw-bold mb-3 text-secondary d-flex align-items-center gap-2">
                                                <i class="bi bi-clock-history fs-5"></i> Order Status Timeline
                                            </h6>
                                            <div class="position-relative ms-2 ps-3 border-start border-secondary border-opacity-25 border-2">
                                                <template x-for="log in selectedOrder.original.status_logs" :key="log.id">
                                                    <div class="position-relative mb-3">
                                                        <div class="position-absolute bg-secondary rounded-circle" style="width: 10px; height: 10px; left: -22px; top: 5px;"></div>
                                                        <div class="d-flex justify-content-between align-items-start">
                                                            <div>
                                                                <p class="fw-bold text-body-emphasis mb-0 small text-capitalize" x-text="log.status.replace(/_/g, ' ')"></p>
                                                                <p class="text-muted mb-0" style="font-size: 0.75rem;" x-text="formatDateTime(log.created_at)"></p>
                                                                <p class="text-secondary small mt-1 lh-sm mb-0" x-show="log.notes" x-text="log.notes"></p>
                                                                <p class="text-secondary opacity-75" style="font-size: 0.7rem; margin-top: 2px;" x-show="log.user">
                                                                    <i class="bi bi-person me-1"></i><span x-text="log.user.name || (log.user.first_name + ' ' + (log.user.last_name || ''))"></span>
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

<!-- ═══════════════════════ Create Shipment Modal ═══════════════════════════ -->
<div class="modal fade" id="createShipmentModal" aria-labelledby="createShipmentModalLabel">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
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
                    <select x-select class="form-select" x-model="shipCarrierName">
                        <option value="" disabled selected>Select carrier...</option>
                        <template x-for="carrier in shipCarrierOptions" :key="carrier.name">
                            <option :value="carrier.name" x-text="carrier.priority === null ? carrier.name : `${carrier.name} (Priority: ${carrier.priority})`"></option>
                        </template>
                    </select>
                </div>
                <div class="mb-3" x-show="shipCarrierName !== 'India Post'">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label fw-semibold mb-0">Tracking Number <span class="text-danger">*</span></label>
                        <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none small" @click="shipTrackingNo = 'TRK-' + Math.random().toString(36).substring(2, 12).toUpperCase()">
                            <i class="bi bi-magic me-1"></i>Generate Demo ID
                        </button>
                    </div>
                    <input type="text" class="form-control font-monospace" x-model="shipTrackingNo" placeholder="Enter tracking number (e.g. TRK-12345678)" :required="shipCarrierName !== 'India Post'">
                </div>
                <div class="mb-3" x-show="shipCarrierName === 'India Post'" style="display: none;">
                    <div class="alert alert-info py-2 px-3 mb-0 d-flex align-items-center">
                        <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                        <span class="small">Tracking ID and shipping cost will be automatically calculated and retrieved from the India Post API.</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" @click="shipOrder()">Ship Order</button>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════ CSV Import Preview Modal ═══════════════════════════ -->
<div class="modal fade" id="importPreviewModal" aria-labelledby="importPreviewModalLabel" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="importPreviewModalLabel">
                    <i class="bi bi-file-earmark-spreadsheet me-2 text-primary"></i>CSV Import Preview
                </h5>
                <button type="button" class="btn-close" @click="cancelImport()"></button>
            </div>
            <div class="modal-body pt-3">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle-fill me-2"></i>Please review the details below. Only valid orders (currently in "Processing" status) will be updated.
                </div>
                <div class="table-responsive" style="max-height: 400px;">
                    <table class="table table-striped table-hover table-sm small align-middle mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Order No</th>
                                <th>Customer</th>
                                <th>Current Status</th>
                                <th>Carrier (CSV)</th>
                                <th>Tracking No (CSV)</th>
                                <th>Validation</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, idx) in importRows" :key="idx">
                                <tr :class="row.is_valid ? '' : 'table-danger'">
                                    <td class="font-monospace fw-bold" x-text="row.order_no"></td>
                                    <td class="text-truncate" style="max-width: 150px;" x-text="row.customer"></td>
                                    <td>
                                        <span class="badge text-bg-secondary-subtle text-secondary-emphasis" x-text="row.current_status"></span>
                                        <template x-if="row.is_valid">
                                            <span>
                                                <i class="bi bi-arrow-right mx-1 text-muted"></i>
                                                <span class="badge text-bg-primary-subtle text-primary-emphasis" x-text="row.upcoming_status"></span>
                                            </span>
                                        </template>
                                    </td>
                                    <td x-text="row.csv_carrier"></td>
                                    <td class="font-monospace" x-text="row.csv_tracking"></td>
                                    <td>
                                        <template x-if="row.is_valid">
                                            <span class="text-success fw-medium"><i class="bi bi-check-circle me-1"></i>Valid (Will process)</span>
                                        </template>
                                        <template x-if="!row.is_valid">
                                            <span class="text-danger fw-medium">
                                                <i class="bi bi-x-circle me-1"></i>
                                                <span x-text="row.current_status === 'Not Found' ? 'Order not found' : 'Must be in Processing status'"></span>
                                            </span>
                                        </template>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0 mt-3">
                <button type="button" class="btn btn-secondary" @click="cancelImport()">Cancel</button>
                <button type="button" class="btn btn-primary" @click="confirmImport()">Confirm Import</button>
            </div>
        </div>
    </div>
</div>


<!-- ═══════════════════════ Confirm Order Modal ═══════════════════════════ -->
<div class="modal fade" id="confirmOrderModal" aria-labelledby="confirmOrderModalLabel">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="confirmOrderModalLabel">
                    <i class="bi bi-check-circle me-2 text-primary"></i>Confirm Order <span class="text-primary" x-text="confirmModalOrder?.orderNumber"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-3">
                <template x-if="confirmModalOrder?.scheduledConfirmDate">
                    <div class="alert bg-info bg-opacity-10 border border-info border-opacity-25 shadow-sm mb-4 rounded-3">
                        <div class="d-flex align-items-center mb-1">
                            <i class="bi bi-calendar-event fs-5 me-2 text-info"></i>
                            <h6 class="fw-bold text-info-emphasis mb-0">Currently Scheduled</h6>
                        </div>
                        <div class="small ms-4 ps-1 text-body">
                            <div class="mb-1"><strong class="text-body-emphasis">Date:</strong> <span class="fw-medium text-body" x-text="new Date(confirmModalOrder.scheduledConfirmDate).toLocaleString('en-IN', { day: '2-digit', month: 'short', year:'numeric', hour: '2-digit', minute:'2-digit', hour12: true })"></span></div>
                            <div><strong class="text-body-emphasis">Previous Attempts:</strong> <span class="badge bg-warning text-body-emphasis ms-1" x-text="confirmModalOrder.confirmAttempts || 0"></span></div>
                        </div>
                    </div>
                </template>

                <template x-if="confirmModalOrder?.original?.status_logs && confirmModalOrder.original.status_logs.length > 0">
                    <div class="mb-4">
                        <h6 class="fw-bold mb-3 text-body-emphasis border-bottom pb-2">
                            <i class="bi bi-clock-history me-2"></i>Status History
                        </h6>
                        <div class="position-relative ms-2 ps-3 border-start border-secondary border-opacity-25 border-2">
                            <template x-for="log in confirmModalOrder.original.status_logs" :key="log.id">
                                <div class="position-relative mb-3">
                                    <div class="position-absolute bg-secondary rounded-circle" style="width: 10px; height: 10px; left: -22px; top: 5px;"></div>
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <p class="fw-bold text-body-emphasis mb-0 small text-capitalize" x-text="log.status.replace(/_/g, ' ')"></p>
                                            <p class="text-muted mb-0" style="font-size: 0.75rem;">
                                                <span x-text="formatDateTime(log.created_at)"></span>
                                                <template x-if="log.user">
                                                    <span> &bull; by <span x-text="log.user.name"></span></span>
                                                </template>
                                            </p>
                                            <p class="text-secondary small mt-1 lh-sm mb-0" x-show="log.notes" x-text="log.notes"></p>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <div class="mb-4">
                    <p class="text-muted mb-2">How would you like to process this order?</p>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="confirmAction" id="actionConfirmNow" value="now" x-model="confirmAction">
                        <label class="form-check-label fw-semibold text-body-emphasis" for="actionConfirmNow">
                            Confirm Immediately
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="confirmAction" id="actionSchedule" value="schedule" x-model="confirmAction">
                        <label class="form-check-label fw-semibold text-body-emphasis" for="actionSchedule">
                            Schedule for Future Confirmation
                        </label>
                    </div>
                </div>

                <div x-show="confirmAction === 'schedule'" x-cloak x-transition>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-body-emphasis">Reason for Reschedule <span class="text-danger">*</span></label>
                        <select x-select class="form-select" x-model="scheduleReason">
                            <option value="" disabled selected>Select a reason...</option>
                            @foreach($rescheduleReasons as $reason)
                                <option value="{{ $reason->reason }}">{{ $reason->reason }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-body-emphasis">Follow-up / Scheduled Date <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control" x-model="scheduledConfirmDate" :min="new Date().toISOString().slice(0,16)">
                        <div class="form-text mt-1 text-muted"><i class="bi bi-info-circle me-1"></i>The order will remain pending and tracked for this date.</div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-semibold text-body-emphasis">Internal Notes (Optional)</label>
                    <textarea class="form-control" rows="2" x-model="confirmNotes" placeholder="Any additional notes regarding this action..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" @click="submitConfirmOrder()">
                    <span x-text="confirmAction === 'schedule' ? 'Save Schedule' : 'Confirm Order'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════ Deliver Order Modal ═══════════════════════════ -->
<div class="modal fade" id="deliverOrderModal" aria-labelledby="deliverOrderModalLabel">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="deliverOrderModalLabel">
                    <i class="bi bi-check2-all me-2 text-success"></i>Deliver Order <span class="text-success" x-text="deliverModalOrder?.orderNumber"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-3">
                <template x-if="deliverModalOrder?.shipment?.next_followup_date">
                    <div class="alert bg-warning bg-opacity-10 border border-warning border-opacity-25 shadow-sm mb-4 rounded-3">
                        <div class="d-flex align-items-center mb-1">
                            <i class="bi bi-calendar-event fs-5 me-2 text-warning"></i>
                            <h6 class="fw-bold text-warning-emphasis mb-0">Currently Scheduled Attempt</h6>
                        </div>
                        <div class="small ms-4 ps-1 text-body">
                            <div class="mb-1"><strong class="text-body-emphasis">Date:</strong> <span class="fw-medium text-body" x-text="new Date(deliverModalOrder.shipment.next_followup_date).toLocaleString('en-IN', { day: '2-digit', month: 'short', year:'numeric', hour: '2-digit', minute:'2-digit', hour12: true })"></span></div>
                            <div><strong class="text-body-emphasis">Previous Attempts:</strong> <span class="badge bg-danger ms-1" x-text="deliverModalOrder.shipment.delivery_attempts || 0"></span></div>
                            <div x-show="deliverModalOrder.shipment.reschedule_reason"><strong class="text-body-emphasis">Reason:</strong> <span class="fw-medium text-body" x-text="deliverModalOrder.shipment.reschedule_reason"></span></div>
                        </div>
                    </div>
                </template>

                <template x-if="deliverModalOrder?.original?.status_logs && deliverModalOrder.original.status_logs.length > 0">
                    <div class="mb-4">
                        <h6 class="fw-bold mb-3 text-body-emphasis border-bottom pb-2">
                            <i class="bi bi-clock-history me-2"></i>Status History
                        </h6>
                        <div class="position-relative ms-2 ps-3 border-start border-secondary border-opacity-25 border-2">
                            <template x-for="log in deliverModalOrder.original.status_logs" :key="log.id">
                                <div class="position-relative mb-3">
                                    <div class="position-absolute bg-secondary rounded-circle" style="width: 10px; height: 10px; left: -22px; top: 5px;"></div>
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <p class="fw-bold text-body-emphasis mb-0 small text-capitalize" x-text="log.status.replace(/_/g, ' ')"></p>
                                            <p class="text-muted mb-0" style="font-size: 0.75rem;">
                                                <span x-text="formatDateTime(log.created_at)"></span>
                                                <template x-if="log.user">
                                                    <span> &bull; by <span x-text="log.user.name"></span></span>
                                                </template>
                                            </p>
                                            <p class="text-secondary small mt-1 lh-sm mb-0" x-show="log.notes" x-text="log.notes"></p>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <div class="mb-4">
                    <p class="text-muted mb-2">How would you like to update this delivery?</p>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="deliverAction" id="actionDeliverNow" value="now" x-model="deliverAction">
                        <label class="form-check-label fw-semibold text-body-emphasis" for="actionDeliverNow">
                            Mark as Delivered
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="deliverAction" id="actionDeliverSchedule" value="schedule" x-model="deliverAction">
                        <label class="form-check-label fw-semibold text-body-emphasis" for="actionDeliverSchedule">
                            Record Failed Attempt & Reschedule
                        </label>
                    </div>
                </div>

                <div x-show="deliverAction === 'schedule'" x-cloak x-transition>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-body-emphasis">Reason for Failure <span class="text-danger">*</span></label>
                        <select x-select class="form-select" x-model="scheduleDeliveryReason">
                            <option value="" disabled selected>Select a reason...</option>
                            @foreach($deliveryFailureReasons as $reason)
                                <option value="{{ $reason->reason }}">{{ $reason->reason }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-body-emphasis">Next Follow-up Date <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control" x-model="scheduledDeliveryDate" :min="new Date().toISOString().slice(0,16)">
                    </div>
                </div>


                
                <div class="mb-3">
                    <label class="form-label fw-semibold text-body-emphasis">Internal Notes (Optional)</label>
                    <textarea class="form-control" rows="2" x-model="deliverNotes" placeholder="Any additional notes regarding this action..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" @click="submitDeliverOrder()">
                    <span x-text="deliverAction === 'schedule' ? 'Save Schedule' : 'Mark Delivered'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════ Initiate Return Modal ═══════════════════════════ -->
<div class="modal fade" id="initiateReturnModal" aria-labelledby="initiateReturnModalLabel">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="initiateReturnModalLabel">
                    <i class="bi bi-arrow-return-left me-2 text-warning"></i>Initiate Return for Order <span class="text-warning" x-text="returnModalOrder?.orderNumber"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-3">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Reason for Return <span class="text-danger">*</span></label>
                    <select x-select class="form-select" x-model="returnReason">
                        <option value="" disabled selected>Select a reason...</option>
                        @foreach($returnReasons as $reason)
                            <option value="{{ $reason->reason }}">{{ $reason->reason }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">Select Items to Return</label>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th style="width: 150px;">Qty to Return</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(item, index) in returnItems" :key="index">
                                    <tr>
                                        <td x-text="item.name"></td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm" x-model.number="item.requested_qty" min="0" :max="item.max_qty">
                                            <div class="form-text mt-0" style="font-size: 0.7rem;">Max: <span x-text="item.max_qty"></span></div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Additional Notes</label>
                    <textarea class="form-control" rows="2" x-model="returnNotes" placeholder="Any details..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" @click="submitReturn()">Initiate Return</button>
            </div>
        </div>
    </div>
</div>
<!-- ═══════════════════════ Cancel Order Modal ═══════════════════════════ -->
<div class="modal fade" id="cancelOrderModal" aria-labelledby="cancelOrderModalLabel">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="cancelOrderModalLabel">
                    <i class="bi bi-x-circle me-2 text-danger"></i>Cancel Order <span class="text-danger" x-text="cancelModalOrder?.orderNumber"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-3">
                <div class="alert alert-danger bg-danger bg-opacity-10 border-danger border-opacity-25 shadow-sm rounded-3">
                    <div class="d-flex align-items-center mb-1">
                        <i class="bi bi-exclamation-triangle-fill fs-5 me-2 text-danger"></i>
                        <h6 class="fw-bold text-danger mb-0">Warning</h6>
                    </div>
                    <div class="small ms-4 ps-1 text-danger-emphasis">
                        Are you sure you want to cancel this order? This action will release inventory.
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Reason for Cancellation <span class="text-danger">*</span></label>
                    <select x-select class="form-select" x-model="cancelReason">
                        <option value="" disabled selected>Select a reason...</option>
                        @foreach($cancelReasons as $reason)
                            <option value="{{ $reason->reason }}">{{ $reason->reason }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">Internal Notes (Optional)</label>
                    <textarea class="form-control" rows="2" x-model="cancelNotes" placeholder="Any additional notes..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-danger" @click="submitCancelOrder()">Confirm Cancel</button>
            </div>
        </div>
    </div>
</div>

    <!-- Items Breakdown Modal -->
    <div class="modal fade" id="orderItemsModal" aria-labelledby="orderItemsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <template x-if="selectedItemsOrder">
                    <div>
                        <!-- Header -->
                        <div class="modal-header border-bottom-0 pb-0 position-relative">
                            <div class="d-flex align-items-center gap-3">
                                <div class="text-bg-primary-subtle text-primary-emphasis rounded p-3 d-flex align-items-center justify-content-center">
                                    <i class="bi bi-box-seam fs-4"></i>
                                </div>
                                <div>
                                    <h4 class="modal-title fw-bolder mb-1" id="orderItemsModalLabel" style="letter-spacing: -0.5px;">
                                        Order Items
                                    </h4>
                                    <p class="text-muted mb-0 small">
                                        Order #<span class="fw-medium text-body" x-text="selectedItemsOrder.orderNumber"></span>
                                        <template x-if="selectedItemsOrder.isUnfulfillable">
                                            <span class="badge bg-danger ms-2"><i class="bi bi-exclamation-triangle-fill me-1"></i>Unfulfillable</span>
                                        </template>
                                    </p>
                                </div>
                            </div>
                            <button type="button" class="btn-close position-absolute top-0 end-0 m-4" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        
                        <!-- Body -->
                        <div class="modal-body pt-4">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Product</th>
                                            <th class="text-center">Quantity</th>
                                            <th class="text-end">Unit Price</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="(item, idx) in selectedItemsOrder.items" :key="idx">
                                            <tr :class="{ 'bg-danger bg-opacity-10': item.isOutOfStock }">
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="bg-body-secondary rounded overflow-hidden d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px; min-width: 48px;">
                                                            <template x-if="item.image">
                                                                <img :src="item.image" :alt="item.name" class="img-fluid object-fit-cover w-100 h-100">
                                                            </template>
                                                            <template x-if="!item.image">
                                                                <i class="bi bi-image text-secondary fs-4"></i>
                                                            </template>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-1 text-body-emphasis" style="font-size: 0.9rem;" x-text="item.name"></h6>
                                                            <div class="d-flex align-items-center gap-2">
                                                                <span class="badge text-bg-secondary-subtle text-secondary-emphasis-subtle font-monospace" style="font-size: 0.65rem;" x-text="item.sku"></span>
                                                                <template x-if="item.isOutOfStock">
                                                                    <span class="badge bg-danger text-white ms-1" style="font-size: 0.65rem;" x-text="`Out of Stock (Have: ${item.availableStock})`"></span>
                                                                </template>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-center fw-medium" x-text="item.quantity"></td>
                                                <td class="text-end text-muted" x-text="`₹ ${formatCurrency(item.price)}`"></td>
                                                <td class="text-end fw-medium text-body-emphasis" x-text="`₹ ${formatCurrency(item.net)}`"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Footer -->
                        <div class="modal-footer border-top-0 pt-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    </div> <!-- End showAnalytics Main Wrapper -->
</div> <!-- End Order Management Container -->
@endsection

@push('scripts')
<script>
    document.addEventListener('show.bs.dropdown', function (event) {
        var responsiveContainer = event.target.closest('.table-responsive');
        if (responsiveContainer) {
            responsiveContainer.style.overflow = 'visible';
        }
    });
    
    document.addEventListener('hide.bs.dropdown', function (event) {
        var responsiveContainer = event.target.closest('.table-responsive');
        if (responsiveContainer) {
            responsiveContainer.style.overflow = '';
        }
    });
</script>
@endpush
