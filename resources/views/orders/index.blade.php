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
    carrierProvidersMap = {{ json_encode($carrierProvidersMap) }};
    warehousesList = {{ json_encode($warehousesList ?? []) }};
    allowedFilterStatuses = {{ json_encode($statusesList) }};
    init();
">
    <div x-data="{ showAnalytics: localStorage.getItem('orders_show_analytics') === 'true' }" x-init="$watch('showAnalytics', val => localStorage.setItem('orders_show_analytics', val))">
<!-- Page Header -->
<div class="d-flex flex-column flex-md-row justify-content-end align-items-md-center mb-4 gap-3">
    <div class="d-flex flex-wrap align-items-center gap-2">
        <!-- Refresh Button -->
        <button type="button" class="btn btn-outline-primary shadow-sm bg-body-tertiary" onclick="window.location.reload()" data-bs-toggle="tooltip" title="Refresh data">
            <i class="bi bi-arrow-clockwise icon-hover"></i>
        </button>
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
        @canany(['orders.import', 'orders.deliver', 'orders.return'])
        <div class="dropdown">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" title="Import New Orders">
                <i class="bi bi-upload me-2"></i>Import Orders
            </button>
            <ul class="dropdown-menu">

                @can('orders.deliver')
                <li><hr class="dropdown-divider"></li>
                <li><h6 class="dropdown-header">Bulk Deliver Orders</h6></li>
                <li>
                    <a class="dropdown-item" href="#" @click.prevent="document.getElementById('import-deliver-file').click()">
                        <i class="bi bi-box-seam me-2"></i>Upload Deliver CSV
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="{{ route('orders.import-deliver-template') }}">
                        <i class="bi bi-file-earmark-arrow-down me-2"></i>Download Deliver Template
                    </a>
                </li>
                @endcan
                @can('orders.return')
                <li><hr class="dropdown-divider"></li>
                <li><h6 class="dropdown-header">Bulk Return Orders</h6></li>
                <li>
                    <a class="dropdown-item" href="#" @click.prevent="document.getElementById('import-return-file').click()">
                        <i class="bi bi-arrow-return-left me-2"></i>Upload Return CSV
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="{{ route('orders.import-return-template') }}">
                        <i class="bi bi-file-earmark-arrow-down me-2"></i>Download Return Template
                    </a>
                </li>
                @endcan
            </ul>
        </div>
        @endcanany
        @can('orders.create')
        <a href="{{ route('orders.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-2"></i>New Order
        </a>
        @endcan
    </div>
</div>




<form id="import-deliver-form" action="{{ route('orders.import-deliver') }}" method="POST" enctype="multipart/form-data" class="d-none">
    @csrf
    <input type="file" name="file" id="import-deliver-file" accept=".csv,.txt" @change="handleImportDeliverSelect($event)">
</form>

<form id="import-return-form" action="{{ route('orders.import-return') }}" method="POST" enctype="multipart/form-data" class="d-none">
    @csrf
    <input type="file" name="file" id="import-return-file" accept=".csv,.txt" @change="handleImportReturnSelect($event)">
</form>

<!-- Order Stats Widgets & Analytics -->
<div x-show="showAnalytics" x-transition.opacity.duration.300ms x-cloak>
    <!-- Order Stats Scrollable Row -->
    <div class="d-flex flex-nowrap overflow-x-auto gap-3 pb-3 mb-4 hide-scrollbar" style="scrollbar-width: thin;">
        <div class="flex-shrink-0" style="width: 260px;">
            <div class="card stats-card h-100 shadow-sm rounded-4 border-start border-4 border-primary">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex flex-column align-items-start">
                        <div class="stats-icon text-bg-primary-subtle text-primary-emphasis mb-3 rounded-3 p-2">
                            <i class="bi bi-bag-check fs-4"></i>
                        </div>
                        <div class="w-100" style="min-width: 0;">
                            <p class="h6 mb-0 text-muted fw-semibold text-uppercase" style="font-size: 0.75rem;" title="Total Orders">Total Orders</p>
                            <div class="h3 mb-0 fw-bold" aria-live="polite"><span x-text="(stats.total || 0) - (stats.future_order || 0)"></span></div>
                            <small class="text-muted d-block text-wrap fw-medium mt-1" style="word-break: break-all; font-size: 0.8rem;" x-text="'Value: ' + formatCurrency((stats.revenue || 0) - (stats.future_order_amount || 0))"></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @can('orders.view.future_order')
        <div class="flex-shrink-0" style="width: 260px;">
            <div class="card stats-card h-100 shadow-sm rounded-4 border-start border-4 border-info">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex flex-column align-items-start">
                        <div class="stats-icon text-bg-info-subtle text-info-emphasis mb-3 rounded-3 p-2">
                            <i class="bi bi-calendar-event fs-4"></i>
                        </div>
                        <div class="w-100" style="min-width: 0;">
                            <p class="h6 mb-0 text-muted fw-semibold text-uppercase" style="font-size: 0.75rem;" title="Future Order">Future Order</p>
                            <div class="h3 mb-0 fw-bold" aria-live="polite"><span x-text="stats.future_order"></span></div>
                            <small class="text-info d-block text-wrap fw-medium mt-1" style="word-break: break-all; font-size: 0.8rem;" x-text="'Value: ' + formatCurrency(stats.future_order_amount)"></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endcan
        @can('orders.view.pending')
        <div class="flex-shrink-0" style="width: 260px;">
            <div class="card stats-card h-100 shadow-sm rounded-4 border-start border-4 border-warning">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex flex-column align-items-start">
                        <div class="stats-icon text-bg-warning-subtle text-warning-emphasis mb-3 rounded-3 p-2">
                            <i class="bi bi-clock fs-4"></i>
                        </div>
                        <div class="w-100" style="min-width: 0;">
                            <p class="h6 mb-0 text-muted fw-semibold text-uppercase" style="font-size: 0.75rem;" title="Pending">Pending</p>
                            <div class="h3 mb-0 fw-bold" aria-live="polite"><span x-text="stats.pending"></span></div>
                            <small class="text-warning d-block text-wrap fw-medium mt-1" style="word-break: break-all; font-size: 0.8rem;" x-text="'Value: ' + formatCurrency(stats.pending_amount)"></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endcan
        @can('orders.view.pending')
        <div class="flex-shrink-0" style="width: 260px;">
            <div class="card stats-card h-100 shadow-sm rounded-4 border-start border-4 border-danger">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex flex-column align-items-start">
                        <div class="stats-icon text-bg-danger-subtle text-danger-emphasis mb-3 rounded-3 p-2">
                            <i class="bi bi-x-octagon fs-4"></i>
                        </div>
                        <div class="w-100" style="min-width: 0;">
                            <p class="h6 mb-0 text-muted fw-semibold text-uppercase text-truncate" style="font-size: 0.75rem;" title="Unfulfillable (OOS)">Unfulfillable (OOS)</p>
                            <div class="h3 mb-0 fw-bold" aria-live="polite"><span x-text="stats.unfulfillable"></span></div>
                            <small class="text-danger d-block text-wrap fw-medium mt-1" style="word-break: break-all; font-size: 0.8rem;" x-text="'Value: ' + formatCurrency(stats.unfulfillable_amount)"></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endcan
        @can('orders.view.pending_confirmation')
        <div class="flex-shrink-0" style="width: 260px;">
            <div class="card stats-card h-100 shadow-sm rounded-4 border-start border-4 border-warning">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex flex-column align-items-start">
                        <div class="stats-icon text-bg-warning-subtle text-warning-emphasis mb-3 rounded-3 p-2">
                            <i class="bi bi-hourglass-split fs-4"></i>
                        </div>
                        <div class="w-100" style="min-width: 0;">
                            <p class="h6 mb-0 text-muted fw-semibold text-uppercase" style="font-size: 0.75rem;" title="Pending Confirmation">Pending Confirmation</p>
                            <div class="h3 mb-0 fw-bold" aria-live="polite"><span x-text="stats.pending_confirmation"></span></div>
                            <small class="text-warning d-block text-wrap fw-medium mt-1" style="word-break: break-all; font-size: 0.8rem;" x-text="'Value: ' + formatCurrency(stats.pending_confirmation_amount)"></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endcan
        @can('orders.view.confirmed')
        <div class="flex-shrink-0" style="width: 260px;">
            <div class="card stats-card h-100 shadow-sm rounded-4 border-start border-4 border-info">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex flex-column align-items-start">
                        <div class="stats-icon text-bg-info-subtle text-info-emphasis mb-3 rounded-3 p-2">
                            <i class="bi bi-check-circle fs-4"></i>
                        </div>
                        <div class="w-100" style="min-width: 0;">
                            <p class="h6 mb-0 text-muted fw-semibold text-uppercase" style="font-size: 0.75rem;" title="Confirmed">Confirmed</p>
                            <div class="h3 mb-0 fw-bold" aria-live="polite"><span x-text="stats.confirmed"></span></div>
                            <small class="text-info d-block text-wrap fw-medium mt-1" style="word-break: break-all; font-size: 0.8rem;" x-text="'Value: ' + formatCurrency(stats.confirmed_amount)"></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endcan
        @can('orders.view.processing')
        <div class="flex-shrink-0" style="width: 260px;">
            <div class="card stats-card h-100 shadow-sm rounded-4 border-start border-4 border-secondary">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex flex-column align-items-start">
                        <div class="stats-icon text-bg-secondary-subtle text-secondary-emphasis mb-3 rounded-3 p-2">
                            <i class="bi bi-gear fs-4"></i>
                        </div>
                        <div class="w-100" style="min-width: 0;">
                            <p class="h6 mb-0 text-muted fw-semibold text-uppercase" style="font-size: 0.75rem;" title="Processing">Processing</p>
                            <div class="h3 mb-0 fw-bold" aria-live="polite"><span x-text="stats.processing"></span></div>
                            <small class="text-secondary d-block text-wrap fw-medium mt-1" style="word-break: break-all; font-size: 0.8rem;" x-text="'Value: ' + formatCurrency(stats.processing_amount)"></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endcan
        @can('orders.view.ready_to_ship')
        <div class="flex-shrink-0" style="width: 260px;">
            <div class="card stats-card h-100 shadow-sm rounded-4 border-start border-4 border-dark">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex flex-column align-items-start">
                        <div class="stats-icon text-bg-dark-subtle text-body-emphasis mb-3 rounded-3 p-2">
                            <i class="bi bi-box-seam fs-4"></i>
                        </div>
                        <div class="w-100" style="min-width: 0;">
                            <p class="h6 mb-0 text-muted fw-semibold text-uppercase" style="font-size: 0.75rem;" title="Ready to Ship">Ready to Ship</p>
                            <div class="h3 mb-0 fw-bold" aria-live="polite"><span x-text="stats.ready_to_ship"></span></div>
                            <small class="text-body-emphasis d-block text-wrap fw-medium mt-1" style="word-break: break-all; font-size: 0.8rem;" x-text="'Value: ' + formatCurrency(stats.ready_to_ship_amount)"></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endcan
        @can('orders.view.dispatched')
        <div class="flex-shrink-0" style="width: 260px;">
            <div class="card stats-card h-100 shadow-sm rounded-4 border-start border-4 border-info">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex flex-column align-items-start">
                        <div class="stats-icon text-bg-info-subtle text-info-emphasis mb-3 rounded-3 p-2">
                            <i class="bi bi-truck fs-4"></i>
                        </div>
                        <div class="w-100" style="min-width: 0;">
                            <p class="h6 mb-0 text-muted fw-semibold text-uppercase" style="font-size: 0.75rem;" title="Dispatched">Dispatched</p>
                            <div class="h3 mb-0 fw-bold" aria-live="polite"><span x-text="stats.dispatched"></span></div>
                            <small class="text-info d-block text-wrap fw-medium mt-1" style="word-break: break-all; font-size: 0.8rem;" x-text="'Value: ' + formatCurrency(stats.dispatched_amount)"></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endcan
        @can('orders.view.dispatched')
        <div class="flex-shrink-0" style="width: 260px;">
            <div class="card stats-card h-100 shadow-sm rounded-4 border-start border-4 border-warning">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex flex-column align-items-start">
                        <div class="stats-icon text-bg-warning-subtle text-warning-emphasis mb-3 rounded-3 p-2">
                            <i class="bi bi-exclamation-triangle fs-4"></i>
                        </div>
                        <div class="w-100" style="min-width: 0;">
                            <p class="h6 mb-0 text-muted fw-semibold text-uppercase text-truncate" style="font-size: 0.75rem;" title="Delivery Attempted">Delivery Attempted</p>
                            <div class="h3 mb-0 fw-bold" aria-live="polite"><span x-text="stats.delivery_attempted"></span></div>
                            <small class="text-warning d-block text-wrap fw-medium mt-1" style="word-break: break-all; font-size: 0.8rem;" x-text="'Value: ' + formatCurrency(stats.delivery_attempted_amount)"></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endcan
        @can('orders.view.delivered')
        <div class="flex-shrink-0" style="width: 260px;">
            <div class="card stats-card h-100 shadow-sm rounded-4 border-start border-4 border-success">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex flex-column align-items-start">
                        <div class="stats-icon text-bg-success-subtle text-success-emphasis mb-3 rounded-3 p-2">
                            <i class="bi bi-currency-rupee fs-4"></i>
                        </div>
                        <div class="w-100" style="min-width: 0;">
                            <p class="h6 mb-0 text-muted fw-semibold text-uppercase" style="font-size: 0.75rem;" title="Delivered">Delivered</p>
                            <div class="h3 mb-0 fw-bold" aria-live="polite"><span x-text="stats.delivered"></span></div>
                            <small class="text-success-emphasis d-block text-wrap fw-medium mt-1" style="word-break: break-all; font-size: 0.8rem;" x-text="'Value: ' + formatCurrency(stats.delivered_amount)"></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endcan
        @can('orders.view.return_requested')
        <div class="flex-shrink-0" style="width: 260px;">
            <div class="card stats-card h-100 shadow-sm rounded-4 border-start border-4 border-warning">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex flex-column align-items-start">
                        <div class="stats-icon text-bg-warning-subtle text-warning-emphasis mb-3 rounded-3 p-2">
                            <i class="bi bi-arrow-return-left fs-4"></i>
                        </div>
                        <div class="w-100" style="min-width: 0;">
                            <p class="h6 mb-0 text-muted fw-semibold text-uppercase" style="font-size: 0.75rem;" title="Return Requested">Return Requested</p>
                            <div class="h3 mb-0 fw-bold" aria-live="polite"><span x-text="stats.return_requested"></span></div>
                            <small class="text-warning d-block text-wrap fw-medium mt-1" style="word-break: break-all; font-size: 0.8rem;" x-text="'Value: ' + formatCurrency(stats.return_requested_amount)"></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endcan
        @can('orders.view.returned')
        <div class="flex-shrink-0" style="width: 260px;">
            <div class="card stats-card h-100 shadow-sm rounded-4 border-start border-4 border-secondary">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex flex-column align-items-start">
                        <div class="stats-icon text-bg-secondary-subtle text-secondary-emphasis mb-3 rounded-3 p-2">
                            <i class="bi bi-arrow-counterclockwise fs-4"></i>
                        </div>
                        <div class="w-100" style="min-width: 0;">
                            <p class="h6 mb-0 text-muted fw-semibold text-uppercase" style="font-size: 0.75rem;" title="Returned">Returned</p>
                            <div class="h3 mb-0 fw-bold" aria-live="polite"><span x-text="stats.returned"></span></div>
                            <small class="text-secondary d-block text-wrap fw-medium mt-1" style="word-break: break-all; font-size: 0.8rem;" x-text="'Value: ' + formatCurrency(stats.returned_amount)"></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endcan
        @can('orders.view.cancelled')
        <div class="flex-shrink-0" style="width: 260px;">
            <div class="card stats-card h-100 shadow-sm rounded-4 border-start border-4 border-danger">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex flex-column align-items-start">
                        <div class="stats-icon text-bg-danger-subtle text-danger-emphasis mb-3 rounded-3 p-2">
                            <i class="bi bi-x-circle fs-4"></i>
                        </div>
                        <div class="w-100" style="min-width: 0;">
                            <p class="h6 mb-0 text-muted fw-semibold text-uppercase" style="font-size: 0.75rem;" title="Cancelled">Cancelled</p>
                            <div class="h3 mb-0 fw-bold" aria-live="polite"><span x-text="stats.cancelled"></span></div>
                            <small class="text-danger d-block text-wrap fw-medium mt-1" style="word-break: break-all; font-size: 0.8rem;" x-text="'Value: ' + formatCurrency(stats.cancelled_amount)"></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endcan
    </div>

<!-- Charts Row -->
<div class="row g-4 g-lg-5 mb-5 mb-lg-5 mb-xl-6">
    <!-- Order Trends Chart -->
    <div class="col-lg-8">
        <div class="card h-100 border-start border-4 border-primary shadow-sm rounded-4">
            <div class="card-header bg-transparent border-bottom-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                <h2 class="h5 card-title mb-0 fw-bold">Order Trends</h2>
                <div class="btn-group btn-group-sm shadow-sm" role="group">
                    <input type="radio" class="btn-check" name="trendsPeriod" id="trends7d" autocomplete="off" checked>
                    <label class="btn btn-outline-secondary px-3" for="trends7d">7D</label>
                    <input type="radio" class="btn-check" name="trendsPeriod" id="trends30d" autocomplete="off">
                    <label class="btn btn-outline-secondary px-3" for="trends30d">30D</label>
                    <input type="radio" class="btn-check" name="trendsPeriod" id="trends90d" autocomplete="off">
                    <label class="btn btn-outline-secondary px-3" for="trends90d">90D</label>
                </div>
            </div>
            <div class="card-body p-4">
                <div id="orderTrendsChart" style="height: 300px;"></div>
            </div>
        </div>
    </div>

    <!-- Order Status Distribution -->
    <div class="col-lg-4">
        <div class="card h-100 border-start border-4 border-warning shadow-sm rounded-4">
            <div class="card-header bg-transparent border-bottom-0 pt-4 px-4">
                <h2 class="h5 card-title mb-0 fw-bold">Order Status</h2>
            </div>
            <div class="card-body p-4">
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
        <select class="form-select form-select-sm w-auto" x-tom-select x-model="visibleWarehouseStat" aria-label="Toggle Warehouse Visibility">
            <option value="">All Warehouses</option>
            @foreach($warehousesList as $wh)
                <option value="{{ $wh->name }}">{{ $wh->name }}</option>
            @endforeach
        </select>
    </div>
    
    <div class="row g-4">
        <template x-for="(wh, idx) in warehouseStats" :key="idx">
            <div class="col-12" x-show="!visibleWarehouseStat || visibleWarehouseStat === wh.name" x-transition>
                <div class="card shadow-sm border-start border-4 border-primary rounded-4 overflow-hidden position-relative">
                    <!-- Left accent line -->
                    <div class="position-absolute top-0 bottom-0 start-0 bg-primary" style="width: 4px;"></div>
                    
                    <div class="card-body p-4">
                        <div class="row align-items-center g-4">
                            <!-- Info Section -->
                            <div class="col-lg-3 col-md-12 border-end border-secondary-subtle pe-lg-4 mb-4 mb-lg-0 pb-4 pb-lg-0">
                                <h3 class="h5 fw-bold mb-3 text-body-emphasis" x-text="wh.name"></h3>
                                <div class="d-flex align-items-center gap-3 mb-4">
                                    <div class="badge text-bg-primary-subtle text-primary-emphasis py-2 px-3 fs-6 rounded-pill" x-text="`${wh.total} Orders`"></div>
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
                                        <div class="bg-secondary rounded-circle" style="width: 8px; height: 8px;"></div>
                                        <span class="small text-body-emphasis fw-medium">Cancelled: <span x-text="wh.cancelled"></span></span>
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
                <h2 class="h5 card-title mb-0">All Orders</h2>
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
                               @input="filterOrdersDebounced()"
                               style="width: 200px;">
                    </div>
                    
                    <!-- Status Filter -->
                    @can('orders.filter_status')
                    <div class="position-relative" @click.away="showStatusDropdown = false" :style="showStatusDropdown ? 'z-index: 1050;' : ''">
                        <div class="form-control form-control-sm d-flex flex-nowrap align-items-center gap-1" style="min-height: 31px; cursor: pointer; width: 150px; overflow: hidden;" @click="showStatusDropdown = !showStatusDropdown">
                            <template x-if="statusFilter.length === 0">
                                <span class="text-body-secondary" style="font-size: 13px;">No Statuses</span>
                            </template>
                            <template x-if="statusFilter.length > 0">
                                <div class="d-flex flex-nowrap align-items-center gap-1 w-100" style="padding-right: 15px;">
                                    <template x-for="status in statusFilter.slice(0, 1)" :key="status">
                                        <div class="badge text-bg-primary-subtle text-primary-emphasis d-flex align-items-center gap-1 border border-primary-subtle">
                                            <span :title="status.charAt(0).toUpperCase() + status.slice(1).replace(/_/g, ' ')" x-text="status.charAt(0).toUpperCase() + status.slice(1).replace(/_/g, ' ')" class="d-inline-block text-truncate" style="font-size: 11px; max-width: 70px; vertical-align: bottom;"></span>
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
                    <select class="form-select form-select-sm" 
                            x-tom-select
                            x-model="dateFilter" 
                            @change="filterOrders()"
                            style="width: 150px;">
                        <option value="">All Dates</option>
                        <option value="today">Today</option>
                        <option value="yesterday">Yesterday</option>
                        <option value="7d">Last 7 Days</option>
                        <option value="30d">Last 30 Days</option>
                        <option value="this_month">This Month</option>
                        <option value="last_month">Last Month</option>
                        <option value="this_year">This Year</option>
                    </select>
                    @endcan

                    <!-- Items Per Page -->
                    <select class="form-select form-select-sm"
                            x-tom-select
                            x-model.number="itemsPerPage"
                            @change="filterOrders()"
                            style="width: 120px;">
                        <option value="25">25 / page</option>
                        <option value="50">50 / page</option>
                        <option value="100">100 / page</option>
                        <option value="200">200 / page</option>
                    </select>



                    <!-- Advanced Filters Trigger -->
                    @canany(['orders.filter_product', 'orders.filter_fulfillment', 'orders.filter_carrier', 'orders.filter_warehouse', 'orders.filter_date', 'orders.filter_state', 'orders.filter_district', 'orders.filter_taluka', 'orders.filter_village'])
                    <button class="btn btn-sm"
                            :class="hasActiveAdvancedFilters() ? 'btn-primary' : 'btn-outline-secondary'"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#advancedFilters"
                            aria-expanded="false">
                        <i class="bi bi-funnel me-1"></i>Filters
                    </button>
                    @endcanany
                </div>
            </div>
        </div>
    </div>

    <!-- Collapsible Advanced Filters Drawer -->
    <div class="collapse" id="advancedFilters" x-init="if (hasActiveAdvancedFilters()) { $el.classList.add('show'); $nextTick(() => { const btn = document.querySelector('[data-bs-target=\'#advancedFilters\']'); if(btn) btn.setAttribute('aria-expanded', 'true'); }); }">
        <div class="p-3 bg-body-tertiary border-top border-bottom border-secondary-subtle">
            <div class="row g-3">
                <!-- Product Filter -->
                @can('orders.filter_product')
                <div class="col-md-3 position-relative" @click.away="showProductDropdown = false" :style="showProductDropdown ? 'z-index: 1050;' : ''">
                    <label class="form-label small fw-semibold text-body-secondary">
                        Product <span class="badge bg-secondary rounded-pill ms-1" style="font-size: 0.65rem;" x-text="productFilter.length + ' / ' + (productsList ? productsList.length : 0)"></span>
                    </label>
                    <div class="form-control form-control-sm d-flex flex-wrap align-items-center gap-1" style="min-height: 31px; cursor: text;" @click="showProductDropdown = true; $refs.productSearch.focus()">
                        <template x-for="productId in productFilter" :key="productId">
                            <div class="badge text-bg-primary-subtle text-primary-emphasis d-flex align-items-center gap-1 border border-primary-subtle">
                                <span :title="(productsList.find(p => p.id === productId) || {}).name || productId" x-text="(productsList.find(p => p.id === productId) || {}).name || productId" class="d-inline-block text-truncate" style="font-size: 11px; max-width: 120px; vertical-align: bottom;"></span>
                                <i class="bi bi-x cursor-pointer" @click.stop="toggleFilter('product', productId)" style="font-size: 13px;"></i>
                            </div>
                        </template>
                        <div class="flex-grow-1 position-relative" style="min-width: 50px;">
                            <input x-ref="productSearch" type="text" x-model="productSearch" @focus="showProductDropdown = true" placeholder="Search Products..." class="border-0 w-100 bg-transparent text-body" style="font-size: 12px; outline: none !important; box-shadow: none;">
                        </div>
                    </div>
                    <div x-show="showProductDropdown && filteredProducts.length > 0" class="position-absolute w-100 bg-body border rounded shadow-lg mt-1" style="max-height: 200px; overflow-y: auto; z-index: 1050;">
                        <div class="px-3 py-2 cursor-pointer border-bottom bg-body-tertiary d-flex align-items-center" @click.stop="toggleAllFilter('product')">
                            <input type="checkbox" :checked="productFilter.length > 0 && productFilter.length === (productsList ? productsList.length : 0)" class="me-2" style="cursor: pointer;">
                            <span style="font-size: 12px; font-weight: bold;">Select All</span>
                        </div>
                        <template x-for="product in filteredProducts" :key="product.id">
                            <div class="px-3 py-1 cursor-pointer custom-hover-bg d-flex align-items-center" @click.stop="toggleFilter('product', product.id)">
                                <input type="checkbox" :checked="productFilter.includes(product.id)" class="me-2" style="cursor: pointer;">
                                <span style="font-size: 12px;" x-text="product.name + ' (' + product.sku + ')'"></span>
                            </div>
                        </template>
                    </div>
                </div>
                @endcan
                
                <!-- Fulfillment Filter -->
                @can('orders.filter_fulfillment')
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-body-secondary">Fulfillment Status</label>
                    <select class="form-select form-select-sm" x-tom-select x-model="fulfillmentFilter" @change="filterOrders()">
                        <option value="">All</option>
                        <option value="fulfillable">Fulfillable</option>
                        <option value="unfulfillable">Unfulfillable</option>
                    </select>
                </div>
                @endcan

                <!-- Carrier Filter -->
                @can('orders.filter_carrier')
                <div class="col-md-3 position-relative" @click.away="showCarrierDropdown = false" :style="showCarrierDropdown ? 'z-index: 1050;' : ''">
                    <label class="form-label small fw-semibold text-body-secondary">
                        Carrier <span class="badge bg-secondary rounded-pill ms-1" style="font-size: 0.65rem;" x-text="carrierFilter.length + ' / ' + (carriersList ? carriersList.length : 0)"></span>
                    </label>
                    <div class="form-control form-control-sm d-flex flex-wrap align-items-center gap-1" style="min-height: 31px; cursor: text;" @click="showCarrierDropdown = true; $refs.carrierSearch.focus()">
                        <template x-for="carrier in carrierFilter" :key="carrier">
                            <div class="badge text-bg-primary-subtle text-primary-emphasis d-flex align-items-center gap-1 border border-primary-subtle">
                                <span :title="carrier" x-text="carrier" class="d-inline-block text-truncate" style="font-size: 11px; max-width: 120px; vertical-align: bottom;"></span>
                                <i class="bi bi-x cursor-pointer" @click.stop="toggleFilter('carrier', carrier)" style="font-size: 13px;"></i>
                            </div>
                        </template>
                        <div class="flex-grow-1 position-relative" style="min-width: 50px;">
                            <input x-ref="carrierSearch" type="text" x-model="carrierSearch" @focus="showCarrierDropdown = true" placeholder="Search Carriers..." class="border-0 w-100 bg-transparent text-body" style="font-size: 12px; outline: none !important; box-shadow: none;">
                        </div>
                    </div>
                    <div x-show="showCarrierDropdown && filteredCarriers.length > 0" class="position-absolute w-100 bg-body border rounded shadow-lg mt-1" style="max-height: 200px; overflow-y: auto; z-index: 1050;">
                        <div class="px-3 py-2 cursor-pointer border-bottom bg-body-tertiary d-flex align-items-center" @click.stop="toggleAllFilter('carrier')">
                            <input type="checkbox" :checked="carrierFilter.length > 0 && carrierFilter.length === (carriersList ? carriersList.length : 0)" class="me-2" style="cursor: pointer;">
                            <span style="font-size: 12px; font-weight: bold;">Select All</span>
                        </div>
                        <template x-for="carrier in filteredCarriers" :key="carrier">
                            <div class="px-3 py-1 cursor-pointer custom-hover-bg d-flex align-items-center" @click.stop="toggleFilter('carrier', carrier)">
                                <input type="checkbox" :checked="carrierFilter.includes(carrier)" class="me-2" style="cursor: pointer;">
                                <span style="font-size: 12px;" x-text="carrier"></span>
                            </div>
                        </template>
                    </div>
                </div>
                @endcan

                <!-- Warehouse Filter -->
                @can('orders.filter_warehouse')
                <div class="col-md-3 position-relative" @click.away="showWarehouseDropdown = false" :style="showWarehouseDropdown ? 'z-index: 1050;' : ''">
                    <label class="form-label small fw-semibold text-body-secondary">
                        Warehouse <span class="badge bg-secondary rounded-pill ms-1" style="font-size: 0.65rem;" x-text="warehouseFilter.length + ' / ' + (warehousesList ? warehousesList.length : 0)"></span>
                    </label>
                    <div class="form-control form-control-sm d-flex flex-wrap align-items-center gap-1" style="min-height: 31px; cursor: text;" @click="showWarehouseDropdown = true; $refs.warehouseSearch.focus()">
                        <template x-for="warehouseId in warehouseFilter" :key="warehouseId">
                            <div class="badge text-bg-primary-subtle text-primary-emphasis d-flex align-items-center gap-1 border border-primary-subtle">
                                <span :title="(warehousesList.find(w => w.id === warehouseId) || {}).name || warehouseId" x-text="(warehousesList.find(w => w.id === warehouseId) || {}).name || warehouseId" class="d-inline-block text-truncate" style="font-size: 11px; max-width: 120px; vertical-align: bottom;"></span>
                                <i class="bi bi-x cursor-pointer" @click.stop="toggleFilter('warehouse', warehouseId)" style="font-size: 13px;"></i>
                            </div>
                        </template>
                        <div class="flex-grow-1 position-relative" style="min-width: 50px;">
                            <input x-ref="warehouseSearch" type="text" x-model="warehouseSearch" @focus="showWarehouseDropdown = true" placeholder="Search Warehouses..." class="border-0 w-100 bg-transparent text-body" style="font-size: 12px; outline: none !important; box-shadow: none;">
                        </div>
                    </div>
                    <div x-show="showWarehouseDropdown && filteredWarehouses.length > 0" class="position-absolute w-100 bg-body border rounded shadow-lg mt-1" style="max-height: 200px; overflow-y: auto; z-index: 1050;">
                        <div class="px-3 py-2 cursor-pointer border-bottom bg-body-tertiary d-flex align-items-center" @click.stop="toggleAllFilter('warehouse')">
                            <input type="checkbox" :checked="warehouseFilter.length > 0 && warehouseFilter.length === (warehousesList ? warehousesList.length : 0)" class="me-2" style="cursor: pointer;">
                            <span style="font-size: 12px; font-weight: bold;">Select All</span>
                        </div>
                        <template x-for="warehouse in filteredWarehouses" :key="warehouse.id">
                            <div class="px-3 py-1 cursor-pointer custom-hover-bg d-flex align-items-center" @click.stop="toggleFilter('warehouse', warehouse.id)">
                                <input type="checkbox" :checked="warehouseFilter.includes(warehouse.id)" class="me-2" style="cursor: pointer;">
                                <span style="font-size: 12px;" x-text="warehouse.name"></span>
                            </div>
                        </template>
                    </div>
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
                                <span :title="state" x-text="state" class="d-inline-block text-truncate" style="font-size: 11px; max-width: 120px; vertical-align: bottom;"></span>
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
                                <span :title="district" x-text="district" class="d-inline-block text-truncate" style="font-size: 11px; max-width: 120px; vertical-align: bottom;"></span>
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
                                <span :title="taluka" x-text="taluka" class="d-inline-block text-truncate" style="font-size: 11px; max-width: 120px; vertical-align: bottom;"></span>
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
                                <span :title="village" x-text="village" class="d-inline-block text-truncate" style="font-size: 11px; max-width: 120px; vertical-align: bottom;"></span>
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
                @canany(['orders.filter_status', 'orders.filter_date', 'orders.filter_product', 'orders.filter_fulfillment', 'orders.filter_carrier', 'orders.filter_warehouse', 'orders.filter_state', 'orders.filter_district', 'orders.filter_taluka', 'orders.filter_village'])
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" class="btn btn-sm btn-outline-secondary w-100 d-inline-flex align-items-center justify-content-center" @click="clearFilters()">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                    </button>
                </div>
                @endcanany
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
                            @can('orders.bulk_return') bulkAvailableActions.canReturn ? 'Return' : null, @endcan
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
                    <button class="btn btn-sm btn-info"
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
                    @can('orders.deliver')
                    <button class="btn btn-sm btn-success"
                            x-show="bulkAvailableActions.canDeliver"
                            x-transition
                            @click="bulkUpdateStatus('delivered')"
                            title="Move dispatched orders → Delivered">
                        <i class="bi bi-check2-all me-1"></i>Deliver
                    </button>
                    @endcan
                    @can('orders.bulk_return')
                    <button class="btn btn-sm btn-outline-warning"
                            x-show="bulkAvailableActions.canReturn"
                            x-transition
                            @click="bulkInitiateReturn()"
                            title="Mark selected orders as Returned">
                        <i class="bi bi-arrow-return-left me-1"></i>Return
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
                    <template x-if="bulkDocumentActions.canPrint">
                    <div class="dropdown d-inline-block" x-transition>
                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
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
                    </template>
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
                        <th scope="col">Customer</th>
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
                                <div class="d-inline-flex align-items-center gap-1">
                                    <a href="#" class="fw-bold text-decoration-none text-primary" @click.prevent="viewOrder(order)" x-text="order.orderNumber"></a>
                                    <i class="bi opacity-50 cursor-pointer text-primary-hover" x-data="{ copied: false }" :class="copied ? 'bi-check-lg text-success' : 'bi-copy'" style="font-size: 0.85em;" title="Copy" @click="copyText(order.orderNumber).then((success) => { if (success) { copied = true; setTimeout(() => copied = false, 2000); } })"></i>
                                </div>
                                <div class="d-flex align-items-center flex-wrap gap-1 mt-1">
                                    <small class="text-muted" x-text="'ID: ' + order.id"></small>
                                    <template x-if="order.warehouse">
                                        <span class="badge text-bg-secondary-subtle text-secondary-emphasis ms-1" style="font-size: 0.65rem;" title="Fulfillment Warehouse">
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
                                        <small class="text-muted cursor-pointer d-inline-flex align-items-center gap-1" title="Click to copy" x-data="{ copied: false }" @click="copyText(order.createdBy.email).then((success) => { if (success) { copied = true; setTimeout(() => copied = false, 2000); } })"><span x-text="order.createdBy.email"></span><i class="bi opacity-50" :class="copied ? 'bi-check-lg text-success' : 'bi-copy'" style="font-size: 0.8em;"></i></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-medium small" x-text="order.customer.name"></div>
                                <small class="text-muted d-block mt-1" x-text="order.customer.phone || order.customer.secondaryPhone || 'No mobile number'"></small>
                            </td>
                            <td>
                                <div class="order-items small cursor-pointer custom-hover-bg p-2 rounded border border-transparent hover-border-secondary-subtle transition-all" @click="viewItems(order)" title="Click to view items">
                                    <div class="fw-medium d-flex align-items-center flex-wrap gap-1">
                                        <i class="bi bi-box-seam text-secondary me-1"></i>
                                        <span x-text="order.itemCount + ' item' + (order.itemCount > 1 ? 's' : '')"></span>
                                        <template x-if="order.isUnfulfillable">
                                            <span class="badge bg-danger ms-1 cursor-pointer" style="font-size: 0.65rem;" data-bs-toggle="tooltip" :title="'Insufficient stock for: ' + (order.items || []).filter(i => i.isOutOfStock).map(i => i.name).join(', ')">
                                                <i class="bi bi-exclamation-triangle-fill me-1"></i>Unfulfillable
                                            </span>
                                        </template>
                                    </div>
                                    <small class="text-muted d-block mt-1" style="max-width: 200px;" x-text="order.items.length > 0 ? order.items[0].name + (order.itemCount > 1 ? ' +' + (order.itemCount - 1) + ' more' : '') : '—'"></small>
                                </div>
                            </td>
                            <td class="fw-medium small" x-text="`₹ ${order.total}`"></td>
                            <td>
                                <span class="badge small" 
                                      :class="`bg-${getStatusTheme(order.status)}-subtle text-${getStatusTheme(order.status)}-emphasis border border-${getStatusTheme(order.status)}-subtle`"
                                      x-text="order.statusLabel"></span>
                                <template x-if="order.status === 'pending_confirmation' && (order.scheduledConfirmDate || order.confirmAttempts > 0)">
                                    <div class="d-inline-block ms-2" style="font-size: 0.75rem;">
                                        <template x-if="order.confirmAttempts > 0">
                                            <span class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25 rounded-pill me-1" title="Confirmation Attempts">
                                                <i class="bi bi-arrow-repeat me-1"></i><span x-text="order.confirmAttempts"></span>
                                            </span>
                                        </template>
                                        <template x-if="order.scheduledConfirmDate">
                                            <i class="bi bi-info-circle-fill text-muted fs-6 cursor-pointer" 
                                               :title="'Scheduled: ' + new Date(order.scheduledConfirmDate).toLocaleString('en-IN', { day: '2-digit', month: 'short', hour: '2-digit', minute:'2-digit', hour12: true })"
                                            ></i>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="(order.shipment?.next_followup_date || order.shipment?.delivery_attempts > 0) && (order.status === 'dispatched' || order.status === 'delivery_attempted')">
                                    <div class="d-inline-block ms-2" style="font-size: 0.75rem;">
                                        <template x-if="order.shipment?.delivery_attempts > 0">
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill me-1" title="Delivery Attempts">
                                                <i class="bi bi-arrow-repeat me-1"></i><span x-text="order.shipment.delivery_attempts"></span>
                                            </span>
                                        </template>
                                        <template x-if="order.shipment?.reschedule_reason || order.shipment?.next_followup_date">
                                            <i class="bi bi-info-circle-fill text-muted fs-6 cursor-pointer" 
                                               :title="(order.shipment?.next_followup_date ? 'Scheduled: ' + new Date(order.shipment.next_followup_date).toLocaleString('en-IN', { day: '2-digit', month: 'short', hour: '2-digit', minute:'2-digit', hour12: true }) + '\n' : '') + (order.shipment?.reschedule_reason ? 'Reason: ' + order.shipment.reschedule_reason : '')"
                                            ></i>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="order.status === 'return_requested' && order.orderReturn">
                                    <div class="d-inline-block ms-2" style="font-size: 0.75rem;">
                                        <i class="bi bi-info-circle-fill text-muted fs-6 cursor-pointer" 
                                           data-bs-toggle="tooltip"
                                           :title="(order.orderReturn.reason ? 'Reason: ' + order.orderReturn.reason : '') + (order.orderReturn.notes ? '\nNotes: ' + order.orderReturn.notes : '')"
                                        ></i>
                                    </div>
                                </template>
                                <template x-if="['cancelled', 'confirmed', 'processing', 'ready_to_ship', 'delivered', 'returned'].includes(order.status)">
                                    <div class="d-inline-block ms-2" style="font-size: 0.75rem;">
                                        <template x-if="(order.original?.status_logs || []).find(l => l.status === order.status && l.notes)">
                                            <i class="bi bi-info-circle-fill text-muted fs-6 cursor-pointer" 
                                               data-bs-toggle="tooltip"
                                               :title="(order.original.status_logs.find(l => l.status === order.status && l.notes).notes)"
                                            ></i>
                                        </template>
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
                                        <template x-if="!['cancelled', 'delivered', 'returned', 'return_requested', 'dispatched', 'delivery_attempted'].includes(order.status)">
                                            @can('orders.edit')
                                            <li><a class="dropdown-item" href="#" @click.prevent="editOrder(order)">
                                                <i class="bi bi-pencil-square me-2"></i>Edit Order
                                            </a></li>
                                            @endcan
                                        </template>
                                        
                                        <!-- Context Actions -->
                                        <template x-if="order.status === 'pending' || order.status === 'unfulfillable' || order.status === 'pending_confirmation'">
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
                                        <template x-if="order.status === 'dispatched' || order.status === 'delivery_attempted'">
                                            @can('orders.deliver')
                                            <li><a class="dropdown-item" href="#" @click.prevent="deliverOrder(order)">
                                                <i class="bi bi-check2-all me-2"></i>Deliver
                                            </a></li>
                                            @endcan
                                        </template>
                                        <template x-if="['dispatched', 'delivery_attempted'].includes(order.status)">
                                            @can('orders.return')
                                            <li><a class="dropdown-item text-warning" href="#" @click.prevent="returnOrder(order)">
                                                <i class="bi bi-arrow-return-left me-2"></i>Return Order
                                            </a></li>
                                            @endcan
                                        </template>
                                        <template x-if="['pending', 'unfulfillable', 'pending_confirmation', 'confirmed', 'processing', 'ready_to_ship'].includes(order.status)">
                                            @can('orders.cancel')
                                            <li><a class="dropdown-item text-danger" href="#" @click.prevent="cancelOrder(order)">
                                                <i class="bi bi-x-circle me-2"></i>Cancel Order
                                            </a></li>
                                            @endcan
                                        </template>
                                        @can('orders.revert_status')
                                        <template x-if="['confirmed', 'processing', 'ready_to_ship', 'dispatched', 'delivered', 'cancelled', 'return_requested'].includes(order.status)">
                                        <li><a class="dropdown-item" href="#" @click.prevent="revertStatus(order)">
                                            <i class="bi bi-arrow-left-right me-2"></i>Revert Status
                                        </a></li>
                                        </template>
                                        @endcan
                                        <li><hr class="dropdown-divider"></li>
                                        @can('orders.invoice_pdf')
                                        <li x-show="['processing', 'ready_to_ship', 'dispatched', 'delivered', 'delivery_attempted'].includes(order.lifecycle_status || order.status)"><a class="dropdown-item" href="#" @click.prevent="printInvoice(order)">
                                            <i class="bi bi-file-pdf me-2"></i>Print Invoice
                                        </a></li>
                                        @endcan
                                        @can('orders.cod')
                                        <li x-show="['processing', 'ready_to_ship', 'dispatched', 'delivered', 'delivery_attempted'].includes(order.lifecycle_status || order.status)"><a class="dropdown-item" href="#" @click.prevent="printCOD(order)">
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


@include('orders.partials.modals')
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
