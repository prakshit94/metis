@extends('layouts.app')

@section('title', 'Shipments & Tracking')
@section('page', 'shipping-shipments')

@push('scripts')
<script>
    window.AppConfig = window.AppConfig || {};
    window.AppConfig.returnReasons = @json($returnReasons->pluck('reason', 'reason'));
</script>
<script src="{{ Vite::asset('resources/js/components/shipping/shipments.js') }}"></script>
@endpush

@section('content')
<div class="shipments-management" x-data="shipmentsTable" x-cloak>
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 mb-lg-5">
        <div>
            <h1 class="h3 mb-0">Shipments &amp; Tracking</h1>
            <p class="text-muted mb-0">Monitor shipment fulfillment, tracking numbers, and delivery status</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary" @click="exportData()">
                <i class="bi bi-download me-2"></i>Export CSV
            </button>
        </div>
    </div>

    <!-- Key Metrics Row -->
    <div class="row g-3 mb-4 mb-lg-5">
        <!-- Total Shipments -->
        <div class="col-xl-2 col-lg-4 col-sm-6">
            <div class="card metric-card shadow-sm border-0 rounded-4 overflow-hidden h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted mb-1 fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Total Orders</h6>
                            <div class="h4 mb-0 fw-black text-body-emphasis"><span x-text="stats.total">0</span></div>
                        </div>
                        <div class="stats-icon bg-primary bg-opacity-10 text-primary p-2 rounded-3 d-flex align-items-center justify-content-center">
                            <i class="bi bi-box-seam-fill fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Pending -->
        <div class="col-xl-2 col-lg-4 col-sm-6">
            <div class="card metric-card shadow-sm border-0 rounded-4 overflow-hidden h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted mb-1 fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Pending</h6>
                            <div class="h4 mb-0 fw-black text-body-emphasis"><span x-text="stats.pending">0</span></div>
                        </div>
                        <div class="stats-icon bg-warning bg-opacity-10 text-warning p-2 rounded-3 d-flex align-items-center justify-content-center">
                            <i class="bi bi-clock-history fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- In Transit / Dispatched -->
        <div class="col-xl-2 col-lg-4 col-sm-6">
            <div class="card metric-card shadow-sm border-0 rounded-4 overflow-hidden h-100 bg-primary bg-gradient text-white">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-white-50 mb-1 fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Dispatched</h6>
                            <div class="h4 mb-0 fw-black text-white"><span x-text="stats.in_transit">0</span></div>
                        </div>
                        <div class="stats-icon bg-white bg-opacity-25 text-white p-2 rounded-3 d-flex align-items-center justify-content-center">
                            <i class="bi bi-truck fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Delivered -->
        <div class="col-xl-2 col-lg-4 col-sm-6">
            <div class="card metric-card shadow-sm border-0 rounded-4 overflow-hidden h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted mb-1 fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Delivered</h6>
                            <div class="h4 mb-0 fw-black text-body-emphasis"><span x-text="stats.delivered">0</span></div>
                        </div>
                        <div class="stats-icon bg-success bg-opacity-10 text-success p-2 rounded-3 d-flex align-items-center justify-content-center">
                            <i class="bi bi-check-circle-fill fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Returned -->
        <div class="col-xl-2 col-lg-4 col-sm-6">
            <div class="card metric-card shadow-sm border-0 rounded-4 overflow-hidden h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted mb-1 fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Returned</h6>
                            <div class="h4 mb-0 fw-black text-body-emphasis"><span x-text="stats.returned">0</span></div>
                        </div>
                        <div class="stats-icon bg-secondary bg-opacity-10 text-secondary p-2 rounded-3 d-flex align-items-center justify-content-center">
                            <i class="bi bi-arrow-return-left fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Failed -->
        <div class="col-xl-2 col-lg-4 col-sm-6">
            <div class="card metric-card shadow-sm border-0 rounded-4 overflow-hidden h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted mb-1 fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Failed</h6>
                            <div class="h4 mb-0 fw-black text-body-emphasis"><span x-text="stats.failed">0</span></div>
                        </div>
                        <div class="stats-icon bg-danger bg-opacity-10 text-danger p-2 rounded-3 d-flex align-items-center justify-content-center">
                            <i class="bi bi-x-circle-fill fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Providers Row -->
    <div class="row g-4 mb-4">
        <!-- Service Providers -->
        <div class="col-12">
            <div class="card shadow-sm border-0 rounded-4 h-100 overflow-hidden d-flex flex-column">
                <div class="card-header bg-transparent border-bottom py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h2 class="h6 fw-bold mb-0 text-body-emphasis"><i class="bi bi-truck text-success me-2"></i>Service Providers Tracker</h2>
                    <div class="d-flex gap-2 align-items-center flex-wrap">
                        <select class="form-select form-select-sm w-auto" x-model="providerDatePreset" @change="applyProviderPreset()">
                            <option value="today">Today</option>
                            <option value="yesterday">Yesterday</option>
                            <option value="this_week">This Week</option>
                            <option value="this_month">This Month</option>
                            <option value="prev_month">Previous Month</option>
                            <option value="this_year">This Year</option>
                            <option value="custom">Custom Range</option>
                        </select>
                        <div class="input-group input-group-sm w-auto" x-show="providerDatePreset === 'custom'" style="display: none;">
                            <input type="date" class="form-control" x-model="providerFromDate" @change="calculateProviders()">
                            <span class="input-group-text bg-body-tertiary">to</span>
                            <input type="date" class="form-control" x-model="providerToDate" @change="calculateProviders()">
                        </div>
                        <div class="input-group input-group-sm" style="width: 200px;">
                            <span class="input-group-text bg-body-tertiary border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control border-start-0 ps-0" placeholder="Search providers..." x-model="providerSearchQuery" @input="providerCurrentPage = 1">
                        </div>
                        <select class="form-select form-select-sm w-auto" x-model="providerPerPage" @change="providerCurrentPage = 1">
                            <option value="5">5 / page</option>
                            <option value="10">10 / page</option>
                            <option value="20">20 / page</option>
                        </select>
                    </div>
                </div>
                <div class="card-body p-0 d-flex flex-column">
                    <!-- Provider List (scrollable) -->
                    <div class="flex-grow-1 overflow-auto p-4" style="max-height: 400px;">
                        <table class="table table-hover table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Carrier & Support</th>
                                    <th style="width: 200px;">Volume Pipeline</th>
                                    <th style="width: 200px;">Delivery Outcomes</th>
                                    <th style="width: 250px;">Performance Profile</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="provider in paginatedProviders" :key="provider.name">
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <div class="bg-primary bg-opacity-10 text-primary rounded d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                                    <i class="bi bi-truck" style="font-size: 0.9rem;"></i>
                                                </div>
                                                <div class="fw-bold text-body-emphasis" style="font-size: 0.85rem;" x-text="provider.name"></div>
                                            </div>
                                            <template x-if="provider.contact_persons && provider.contact_persons.length > 0">
                                                <div class="d-flex flex-column gap-1 mt-2 border-top pt-2 border-secondary-subtle">
                                                    <span class="small text-muted fw-bold mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px; text-transform: uppercase;">Support Contacts</span>
                                                    <template x-for="contact in provider.contact_persons" :key="contact.id">
                                                        <div class="d-flex align-items-start gap-2 small lh-sm">
                                                            <i class="bi bi-headset text-primary opacity-75 mt-1" style="font-size: 0.8rem;"></i>
                                                            <div>
                                                                <div class="fw-semibold text-primary" style="font-size: 0.8rem;" x-text="contact.name"></div>
                                                                <div class="text-muted" style="font-size: 0.75rem;" x-text="[contact.phone, contact.department].filter(Boolean).join(' · ') || 'N/A'"></div>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>
                                            <div x-show="!provider.contact_persons || provider.contact_persons.length === 0" class="text-muted small mt-2 border-top pt-2 border-secondary-subtle fst-italic">No contacts assigned</div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column gap-2">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="text-muted small"><i class="bi bi-boxes me-1 opacity-75"></i>Total</span>
                                                    <span class="fw-bold" x-text="provider.total"></span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="text-muted small"><i class="bi bi-hourglass-split me-1 text-warning opacity-75"></i>Pending</span>
                                                    <span class="fw-semibold text-warning" x-text="provider.pending"></span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="text-muted small"><i class="bi bi-truck me-1 text-primary opacity-75"></i>Dispatched</span>
                                                    <span class="fw-semibold text-primary" x-text="provider.in_transit"></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column gap-2">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="text-muted small"><i class="bi bi-check-circle me-1 text-success opacity-75"></i>Delivered</span>
                                                    <span class="fw-semibold text-success" x-text="provider.delivered"></span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="text-muted small"><i class="bi bi-arrow-return-left me-1 text-secondary opacity-75"></i>Returned</span>
                                                    <span class="fw-semibold text-secondary" x-text="provider.returned"></span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="text-muted small"><i class="bi bi-x-circle me-1 text-danger opacity-75"></i>Failed</span>
                                                    <span class="fw-semibold text-danger" x-text="provider.failed"></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="mb-3">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="small text-muted fw-medium">Delivery Rate</span>
                                                    <span class="fw-bold text-dark" style="font-size: 0.85rem;" x-text="provider.deliveryPercentage + '%'"></span>
                                                </div>
                                                <div class="progress" style="height: 6px;">
                                                    <div class="progress-bar bg-info" role="progressbar" :style="'width: ' + provider.deliveryPercentage + '%'" :aria-valuenow="provider.deliveryPercentage" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="small text-muted fw-medium">Success Score</span>
                                                    <span class="fw-bold text-success" style="font-size: 0.85rem;" x-text="provider.successScore + '%'"></span>
                                                </div>
                                                <div class="progress" style="height: 6px;">
                                                    <div class="progress-bar bg-success" role="progressbar" :style="'width: ' + provider.successScore + '%'" :aria-valuenow="provider.successScore" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <template x-if="filteredProviders.length === 0">
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted border-0">No carrier data found.</td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Provider Pagination -->
                    <div class="d-flex justify-content-between align-items-center p-3 border-top" x-show="filteredProviders.length > 0">
                        <div class="text-muted small">
                            Showing <span x-text="((providerCurrentPage - 1) * providerPerPage) + 1"></span> to 
                            <span x-text="Math.min(providerCurrentPage * providerPerPage, filteredProviders.length)"></span> of 
                            <span x-text="filteredProviders.length"></span> providers
                        </div>
                        <nav>
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item" :class="{ 'disabled': providerCurrentPage === 1 }">
                                    <a class="page-link" href="#" @click.prevent="providerCurrentPage--">Previous</a>
                                </li>
                                <template x-for="page in Array.from({length: providerTotalPages}, (_, i) => i + 1)" :key="page">
                                    <li class="page-item" :class="{ 'active': page === providerCurrentPage }">
                                        <a class="page-link" href="#" @click.prevent="providerCurrentPage = page" x-text="page"></a>
                                    </li>
                                </template>
                                <li class="page-item" :class="{ 'disabled': providerCurrentPage >= providerTotalPages }">
                                    <a class="page-link" href="#" @click.prevent="providerCurrentPage++">Next</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Directory Card -->
    <div class="card shadow-sm border-0 rounded-4 mb-5">
        <div class="card-header bg-transparent border-bottom pt-4 pb-3 px-4">
            
            <!-- Quick Filter Tabs -->
            <ul class="nav nav-tabs nav-tabs-custom border-bottom-0 mb-3" role="tablist">
                <li class="nav-item">
                    <a class="nav-link fw-semibold px-3 py-2" :class="{ 'active': statusFilter === '' }" href="#" @click.prevent="statusFilter = ''; filterData()">All Shipments <span class="badge ms-1" :class="statusFilter === '' ? 'bg-secondary' : 'bg-secondary text-white'" x-text="stats.total || 0"></span></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-bold text-primary px-3 py-2" :class="{ 'active': statusFilter === 'in_transit', 'bg-primary text-white rounded-top-2': statusFilter === 'in_transit' }" href="#" @click.prevent="statusFilter = 'in_transit'; filterData()">Dispatched <span class="badge ms-1" :class="statusFilter === 'in_transit' ? 'bg-white text-primary' : 'bg-primary text-white'" x-text="stats.in_transit || 0"></span></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-semibold px-3 py-2" :class="{ 'active text-warning border-warning border-bottom-0': statusFilter === 'pending' }" href="#" @click.prevent="statusFilter = 'pending'; filterData()">Pending <span class="badge ms-1 bg-warning text-dark" x-text="stats.pending || 0"></span></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-semibold px-3 py-2" :class="{ 'active text-success border-success border-bottom-0': statusFilter === 'delivered' }" href="#" @click.prevent="statusFilter = 'delivered'; filterData()">Delivered <span class="badge ms-1 bg-success text-white" x-text="stats.delivered || 0"></span></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-semibold px-3 py-2" :class="{ 'active text-secondary border-secondary border-bottom-0': statusFilter === 'returned' }" href="#" @click.prevent="statusFilter = 'returned'; filterData()">Returned <span class="badge ms-1 bg-secondary text-white" x-text="stats.returned || 0"></span></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-semibold px-3 py-2" :class="{ 'active text-danger border-danger border-bottom-0': statusFilter === 'failed' }" href="#" @click.prevent="statusFilter = 'failed'; filterData()">Failed <span class="badge ms-1 bg-danger text-white" x-text="stats.failed || 0"></span></a>
                </li>
            </ul>

            <div class="row align-items-center">
                <div class="col-12 col-md-auto mb-3 mb-md-0">
                    <h2 class="h5 card-title mb-0 fw-bold text-body-emphasis">Shipments Directory</h2>
                </div>
                <div class="col-12 col-md-auto ms-md-auto">
                    <div class="d-flex flex-wrap gap-2 justify-content-md-end">
                        <div class="position-relative">
                            <input type="search" 
                                   class="form-control form-control-sm border-secondary border-opacity-25" 
                                   placeholder="Search tracking..."
                                   x-model.debounce.300ms="searchQuery"
                                   @input="filterData()"
                                   style="width: 200px;">
                            <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted"></i>
                        </div>
                        <select class="form-select form-select-sm border-secondary border-opacity-25" 
                                x-model="statusFilter" 
                                @change="filterData()"
                                style="width: 150px;" x-show="false">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="in_transit">In Transit</option>
                            <option value="delivered">Delivered</option>
                            <option value="returned">Returned</option>
                            <option value="failed">Failed</option>
                        </select>

                        <select class="form-select form-select-sm border-secondary border-opacity-25"
                                x-model.number="itemsPerPage"
                                @change="filterData()"
                                style="width: 120px;">
                            <option value="10">10 / page</option>
                            <option value="15">15 / page</option>
                            <option value="20">20 / page</option>
                            <option value="25">25 / page</option>
                            <option value="50">50 / page</option>
                        </select>
                        <!-- Advanced Filters Trigger -->
                        <button class="btn btn-sm"
                                :class="hasActiveAdvancedFilters() ? 'btn-primary' : 'btn-outline-secondary border-secondary border-opacity-25'"
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
                    <!-- Date Type Filter -->
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold text-body-secondary">Date Type</label>
                        <select class="form-select form-select-sm" x-model="dateType" @change="filterData()">
                            <option value="created_at">Order Date</option>
                            <option value="shipped_at">Shipped Date</option>
                            <option value="delivered_at">Delivered Date</option>
                        </select>
                    </div>

                    <!-- Date Range From -->
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold text-body-secondary">From Date</label>
                        <input type="date" class="form-control form-control-sm" x-model="fromDate" @change="filterData()">
                    </div>

                    <!-- Date Range To -->
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold text-body-secondary">To Date</label>
                        <input type="date" class="form-control form-control-sm" x-model="toDate" @change="filterData()">
                    </div>

                    <!-- Carrier Filter -->
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-body-secondary">Carrier</label>
                        <select class="form-select form-select-sm" x-model="carrierFilter" @change="filterData()">
                            <option value="">All Carriers</option>
                            @foreach($carriersList as $carrier)
                                <option value="{{ $carrier }}">{{ $carrier }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- Reset Filters -->
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="button" class="btn btn-sm btn-outline-secondary w-100 d-inline-flex align-items-center justify-content-center" @click="clearFilters()">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <!-- Bulk Actions Bar -->
            <div class="bulk-actions-bar p-3 bg-primary bg-opacity-10 border-bottom border-primary border-opacity-25"
                 x-show="selectedItems.length > 0" x-cloak>
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle-fill text-primary me-2"></i>
                        <span class="fw-medium text-primary">
                            <span x-text="selectedItems.length"></span> shipment<span x-show="selectedItems.length !== 1">s</span> selected
                        </span>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-primary" x-show="bulkAvailableActions.canInTransit" @click="bulkAction('mark_in_transit')">
                            <i class="bi bi-truck me-1"></i>Mark In Transit
                        </button>
                        <button class="btn btn-sm btn-success" x-show="bulkAvailableActions.canDelivered" @click="bulkAction('mark_delivered')">
                            <i class="bi bi-check-circle me-1"></i>Mark Delivered
                        </button>
                        <button class="btn btn-sm btn-secondary" x-show="bulkAvailableActions.canReturned" @click="bulkAction('mark_returned')">
                            <i class="bi bi-arrow-return-left me-1"></i>Mark Returned
                        </button>
                        <button class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center px-2" @click="selectedItems = []" title="Clear selection">
                            <i class="bi bi-x-lg" style="margin-left: 7px"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;" class="ps-3">
                                <input type="checkbox" 
                                       class="user-select-checkbox"
                                       @change="$event.isTrusted && toggleAll($event.target.checked)"
                                       :checked="selectedItems.length === items.length && items.length > 0">
                            </th>
                            <th @click="sortBy('shipment_no')" class="sortable">Shipment Details</th>
                            <th>Order & Customer</th>
                            <th>Carrier & Support</th>
                            <th @click="sortBy('status')" class="sortable">Status & Timeline</th>
                            <th style="width: 120px;" class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="items.length === 0">
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <div x-show="isLoading" class="spinner-border text-primary spinner-border-sm mb-2" role="status"></div>
                                    <div x-show="!isLoading">
                                        <i class="bi bi-truck fs-2 d-block mb-2 text-muted"></i>
                                        No shipments found.
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <template x-for="item in items" :key="item.id">
                            <tr :class="{ 'table-primary': selectedItems.includes(String(item.id)) }">
                                <td class="ps-3">
                                    <input type="checkbox"
                                           class="user-select-checkbox"
                                           :value="String(item.id)"
                                           x-model="selectedItems">
                                </td>
                                <td>
                                    <div class="fw-bold text-body" x-text="item.shipment_no"></div>
                                    <div class="small text-muted mt-1 d-flex align-items-center gap-1">
                                        <i class="bi bi-upc-scan opacity-75"></i>
                                        <span class="font-monospace" x-text="item.tracking_no || 'No Tracking'"></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold text-primary mb-2" x-text="item.order ? item.order.order_no : ('ORD-' + item.order_id)"></div>
                                    <template x-if="item.order && item.order.party">
                                        <div class="d-flex align-items-start gap-2 bg-body-tertiary p-2 rounded-3 border border-secondary-subtle">
                                            <i class="bi bi-person-badge text-secondary mt-1" style="font-size: 0.85rem;"></i>
                                            <div class="lh-sm">
                                                <div class="fw-semibold text-body mb-1" style="font-size: 0.8rem;" x-text="item.order.party.name || 'Unknown Customer'"></div>
                                                <div class="text-muted font-monospace" style="font-size: 0.75rem;"><i class="bi bi-telephone-fill me-1 opacity-50"></i><span x-text="item.order.party.phone || 'N/A'"></span></div>
                                            </div>
                                        </div>
                                    </template>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <div class="bg-primary bg-opacity-10 text-primary rounded d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                            <i class="bi bi-truck" style="font-size: 0.9rem;"></i>
                                        </div>
                                        <div class="fw-bold text-body-emphasis" style="font-size: 0.85rem;" x-text="item.carrier_name || '-'"></div>
                                    </div>
                                    <template x-if="item.service && item.service.providers?.length">
                                        <div class="d-flex flex-column gap-1 border-top pt-2 border-secondary-subtle">
                                            <span class="small text-muted fw-bold mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px; text-transform: uppercase;">Support Persons</span>
                                            <template x-for="provider in item.service.providers" :key="provider.id">
                                                <div class="d-flex align-items-start gap-2 small lh-sm">
                                                    <i class="bi bi-headset text-primary opacity-75 mt-1" style="font-size: 0.8rem;"></i>
                                                    <div>
                                                        <div class="fw-semibold text-primary" style="font-size: 0.8rem;" x-text="provider.name"></div>
                                                        <div class="text-muted" style="font-size: 0.75rem;" x-text="[provider.phone, provider.department].filter(Boolean).join(' · ')"></div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </td>
                                <td>
                                    <div class="mb-2">
                                        <span class="badge rounded-pill px-3 py-1.5" 
                                              :class="{
                                                  'bg-warning-subtle text-warning border border-warning border-opacity-50': item.status === 'pending',
                                                  'bg-primary text-white shadow-sm': item.status === 'in_transit',
                                                  'bg-success-subtle text-success border border-success border-opacity-50': item.status === 'delivered',
                                                  'bg-secondary-subtle text-secondary border border-secondary border-opacity-50': item.status === 'returned',
                                                  'bg-danger-subtle text-danger border border-danger border-opacity-50': item.status === 'failed'
                                              }"
                                              x-text="item.status === 'in_transit' ? 'DISPATCHED' : item.status.toUpperCase()"></span>
                                    </div>
                                    <div class="small lh-sm text-muted">
                                        <template x-if="item.shipped_at">
                                            <div class="mb-1"><i class="bi bi-box-seam me-1 opacity-75"></i>Shipped: <span class="fw-medium text-body" x-text="new Date(item.shipped_at).toLocaleDateString()"></span></div>
                                        </template>
                                        <template x-if="item.delivered_at">
                                            <div class="mb-1"><i class="bi bi-check2-all me-1 opacity-75"></i>Delivered: <span class="fw-medium text-body" x-text="new Date(item.delivered_at).toLocaleDateString()"></span> <template x-if="item.delivered_by"><span class="fst-italic" x-text="'by ' + item.delivered_by"></span></template></div>
                                        </template>
                                        <template x-if="item.next_followup_date">
                                            <div class="mb-1 text-info"><i class="bi bi-calendar-event me-1"></i>Follow-up: <span class="fw-medium" x-text="new Date(item.next_followup_date).toLocaleDateString()"></span></div>
                                        </template>
                                        <template x-if="item.delivery_attempts > 0">
                                            <div :class="item.delivery_attempts >= 3 ? 'text-danger fw-bold' : (item.delivery_attempts === 2 ? 'text-danger' : 'text-warning')">
                                                <i class="bi bi-arrow-repeat me-1"></i>Attempts: <span class="fw-medium" x-text="item.delivery_attempts"></span>
                                            </div>
                                        </template>
                                    </div>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center gap-2" href="#" @click.prevent="openTrackingModal(item)">
                                                    <i class="bi bi-eye text-primary"></i> Track History
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center gap-2" href="#" @click.prevent="openStatusModal(item)">
                                                    <i class="bi bi-pencil-square text-info"></i> Update Status
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center gap-2" href="#" @click.prevent="openAddEventModal(item)">
                                                    <i class="bi bi-plus-circle text-success"></i> Add Event
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center gap-2" href="#" @click.prevent="openReturnModal(item)">
                                                    <i class="bi bi-arrow-return-left text-warning"></i> Return Order
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
            <div class="d-flex justify-content-between align-items-center p-3 border-top" x-show="paginator.total > 0">
                <div class="text-muted small">
                    Showing <span x-text="paginator.from"></span> to 
                    <span x-text="paginator.to"></span> of 
                    <span x-text="paginator.total"></span> results
                </div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item" :class="{ 'disabled': currentPage === 1 }">
                            <a class="page-link" href="#" @click.prevent="goToPage(currentPage - 1)">Previous</a>
                        </li>
                        <template x-for="(page, index) in visiblePages" :key="`page-${index}`">
                            <li class="page-item" :class="{ 'active': page === currentPage }">
                                <a class="page-link" href="#" @click.prevent="page !== '...' && goToPage(page)" x-text="page"></a>
                            </li>
                        </template>
                        <li class="page-item" :class="{ 'disabled': currentPage === paginator.last_page }">
                            <a class="page-link" href="#" @click.prevent="goToPage(currentPage + 1)">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="statusModal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">Update Shipment Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <form @submit.prevent="saveStatus">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                            <select class="form-select" x-model="statusForm.status" @change="onStatusChange()" required>
                                <option value="pending">Pending</option>
                                <option value="in_transit">In Transit</option>
                                <option value="delivered">Delivered</option>
                                <option value="returned">Returned</option>
                                <option value="failed">Failed</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Hub / Location</label>
                            <input type="text" class="form-control" x-model="statusForm.location" placeholder="e.g. Mumbai Gateway Hub">
                        </div>
                        <div class="row mb-3" x-show="statusForm.status === 'failed' || statusForm.status === 'pending' || statusForm.status === 'in_transit'">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Delivery Attempts</label>
                                <input type="number" class="form-control" readonly min="0" x-model.number="statusForm.delivery_attempts">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Next Follow-up Date</label>
                                <input type="date" class="form-control" x-model="statusForm.next_followup_date">
                            </div>
                        </div>
                        <div class="mb-3" x-show="statusForm.next_followup_date">
                            <label class="form-label fw-semibold">Reschedule Reason</label>
                            <select class="form-select" x-model="statusForm.reschedule_reason">
                                <option value="">Select a reason...</option>
                                <option value="Customer unavailable">Customer unavailable</option>
                                <option value="Customer requested future delivery">Customer requested future delivery</option>
                                <option value="Address issue/Incomplete">Address issue/Incomplete</option>
                                <option value="Out of delivery area/Time limit">Out of delivery area/Time limit</option>
                                <option value="Consignee refused to accept">Consignee refused to accept</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Note / Description</label>
                            <textarea class="form-control" rows="3" x-model="statusForm.description" placeholder="Optional details for tracking history"></textarea>
                        </div>
                        
                        <div x-show="statusForm.status === 'returned'" class="mt-4 pt-3 border-top border-warning border-opacity-50">
                            <h6 class="fw-bold text-warning mb-3"><i class="bi bi-arrow-return-left me-2"></i>Return Details</h6>
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-body-secondary small text-uppercase">Reason for Return <span class="text-danger">*</span></label>
                                <select class="form-select shadow-sm" x-model="returnForm.reason" :required="statusForm.status === 'returned'">
                                    <option value="" disabled selected>Select a reason...</option>
                                    @foreach($returnReasons as $reason)
                                        <option value="{{ $reason->reason }}">{{ $reason->reason }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-body-secondary small text-uppercase">Select Items to Return</label>
                                <div class="table-responsive rounded-3 border shadow-sm">
                                    <table class="table table-bordered table-sm align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="px-3 py-2 text-secondary fw-semibold">Product</th>
                                                <th class="px-3 py-2 text-secondary fw-semibold" style="width: 150px;">Qty to Return</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="(item, index) in returnItems" :key="index">
                                                <tr>
                                                    <td class="px-3" x-text="item.name"></td>
                                                    <td class="px-3">
                                                        <input type="number" class="form-control form-control-sm" x-model.number="item.requested_qty" min="0" :max="item.max_qty">
                                                        <div class="form-text mt-1 text-muted" style="font-size: 0.75rem;">Max: <span x-text="item.max_qty"></span></div>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer border-top-0 pt-0 px-0 mt-3">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="saving">
                                <span x-show="saving" class="spinner-border spinner-border-sm me-1" role="status"></span>
                                Update Status
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Tracking History Modal -->
    <div class="modal fade" id="trackingModal">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        Tracking Timeline: <span class="text-primary font-monospace" x-text="selectedShipment ? selectedShipment.shipment_no : ''"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 bg-body-tertiary bg-opacity-50">
                    <template x-if="selectedShipment">
                        <div class="card shadow-sm border-0 mb-4 rounded-4">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3">Shipment Summary</h6>
                                <div class="row g-3">
                                    <div class="col-md-6" x-show="selectedShipment.delivery_attempts > 0">
                                        <div class="text-muted small">Delivery Attempts</div>
                                        <div class="fw-semibold text-danger" x-text="selectedShipment.delivery_attempts"></div>
                                    </div>
                                    <div class="col-md-6" x-show="selectedShipment.next_followup_date">
                                        <div class="text-muted small">Next Follow-up Date</div>
                                        <div class="fw-semibold" x-text="new Date(selectedShipment.next_followup_date).toLocaleDateString()"></div>
                                    </div>
                                    <div class="col-md-6" x-show="selectedShipment.reschedule_reason">
                                        <div class="text-muted small">Reschedule Reason</div>
                                        <div class="fw-semibold" x-text="selectedShipment.reschedule_reason"></div>
                                    </div>
                                    <div class="col-md-6" x-show="selectedShipment.delivered_by">
                                        <div class="text-muted small">Delivered By</div>
                                        <div class="fw-semibold text-success" x-text="selectedShipment.delivered_by"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                    
                    <template x-if="selectedShipment?.order?.status_logs && selectedShipment.order.status_logs.length > 0">
                        <div class="mb-4">
                            <h6 class="fw-bold mb-3 ps-2">Order Status History</h6>
                            <div class="card shadow-sm border-0 rounded-4">
                                <div class="card-body p-4">
                                    <div class="timeline">
                                        <template x-for="log in selectedShipment.order.status_logs" :key="log.id">
                                            <div class="d-flex mb-4">
                                                <div class="timeline-badge bg-secondary text-white d-flex align-items-center justify-content-center rounded-circle me-3" style="width: 32px; height: 32px; flex-shrink: 0;">
                                                    <i class="bi bi-clock-history"></i>
                                                </div>
                                                <div class="timeline-panel border-bottom pb-2 w-100">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <h6 class="fw-bold mb-0 text-body text-capitalize" x-text="log.status.replace(/_/g, ' ')"></h6>
                                                        <small class="text-muted" x-text="new Date(log.created_at).toLocaleString()"></small>
                                                    </div>
                                                    <template x-if="log.user">
                                                        <div class="text-secondary small mt-1">
                                                            <i class="bi bi-person me-1"></i>by <span class="fw-medium" x-text="log.user.name"></span>
                                                        </div>
                                                    </template>
                                                    <p class="text-muted mt-2 mb-0 small" x-show="log.notes" x-text="log.notes"></p>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <h6 class="fw-bold mb-3 ps-2">Shipment Tracking History</h6>
                    <div class="card shadow-sm border-0 rounded-4">
                        <div class="card-body p-4">
                            <template x-if="trackingEvents.length === 0">
                                <div class="text-center py-4 text-muted">
                                    <i class="bi bi-journal-x fs-2 d-block mb-2"></i>
                                    No tracking events logged yet.
                                </div>
                            </template>
                            <div class="timeline">
                        <template x-for="(event, idx) in trackingEvents" :key="event.id">
                            <div class="d-flex mb-4">
                                <div class="timeline-badge bg-primary text-white d-flex align-items-center justify-content-center rounded-circle me-3" style="width: 32px; height: 32px; flex-shrink: 0;">
                                    <i class="bi bi-record-circle"></i>
                                </div>
                                <div class="timeline-panel border-bottom pb-2 w-100">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="fw-bold mb-0 text-body" x-text="event.event_name"></h6>
                                        <small class="text-muted" x-text="new Date(event.occurred_at).toLocaleString()"></small>
                                    </div>
                                    <div class="text-secondary small mt-1">
                                        <i class="bi bi-geo-alt me-1 text-danger"></i><span class="fw-medium" x-text="event.location || 'Unknown Hub'"></span>
                                    </div>
                                    <template x-if="event.reschedule_reason">
                                        <div class="alert alert-warning py-1 px-2 mt-2 mb-0 small border-warning-subtle">
                                            <i class="bi bi-exclamation-triangle me-1"></i> <span class="fw-semibold">Reason:</span> <span x-text="event.reschedule_reason"></span>
                                        </div>
                                    </template>
                                    <p class="text-muted mt-2 mb-0" x-text="event.description || ''"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Custom Event Modal -->
    <div class="modal fade" id="addEventModal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">Add Tracking Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <form @submit.prevent="saveTrackingEvent">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Event Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" x-model="eventForm.event_name" required placeholder="e.g. Package Sorted at Hub">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Location</label>
                            <input type="text" class="form-control" x-model="eventForm.location" placeholder="e.g. New Delhi Hub">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea class="form-control" rows="3" x-model="eventForm.description" placeholder="Detailed logs or update note..."></textarea>
                        </div>
                        <div class="modal-footer border-top-0 pt-0 px-0">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success" :disabled="saving">
                                <span x-show="saving" class="spinner-border spinner-border-sm me-1" role="status"></span>
                                Add Event
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Return Order Modal -->
    <div class="modal fade" id="returnOrderModal">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom-0 pt-4 pb-2 px-4">
                    <h5 class="modal-title fw-bolder d-flex align-items-center gap-2">
                        <i class="bi bi-arrow-return-left text-warning fs-4"></i>
                        Initiate Shipment Return
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 pb-2">
                    <div class="alert alert-warning border-0 bg-warning bg-opacity-10 d-flex align-items-center mb-4 rounded-3">
                        <i class="bi bi-info-circle-fill text-warning me-3 fs-5"></i>
                        <small class="text-warning-emphasis">This action will automatically log the return and sync with the main order inventory.</small>
                    </div>
                    <form @submit.prevent="submitReturn">
                        <div class="mb-4">
                            <label class="form-label fw-semibold text-body-secondary small text-uppercase">Reason for Return <span class="text-danger">*</span></label>
                            <select class="form-select form-select-lg shadow-sm border-secondary border-opacity-25 rounded-3" x-model="returnForm.reason" required>
                                <option value="" disabled selected>Select a reason...</option>
                                @foreach($returnReasons as $reason)
                                    <option value="{{ $reason->reason }}">{{ $reason->reason }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold text-body-secondary small text-uppercase">Select Items to Return</label>
                            <div class="table-responsive rounded-3 border border-secondary border-opacity-25 shadow-sm">
                                <table class="table table-bordered table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="px-3 py-2 text-secondary fw-semibold">Product</th>
                                            <th class="px-3 py-2 text-secondary fw-semibold" style="width: 150px;">Qty to Return</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="(item, index) in returnItems" :key="index">
                                            <tr>
                                                <td class="px-3" x-text="item.name"></td>
                                                <td class="px-3">
                                                    <input type="number" class="form-control form-control-sm" x-model.number="item.requested_qty" min="0" :max="item.max_qty">
                                                    <div class="form-text mt-1 text-muted" style="font-size: 0.75rem;">Max available: <span x-text="item.max_qty"></span></div>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold text-body-secondary small text-uppercase">Additional Notes</label>
                            <textarea class="form-control shadow-sm border-secondary border-opacity-25 rounded-3" rows="3" x-model="returnForm.notes" placeholder="Please provide any relevant details..."></textarea>
                        </div>
                        <div class="modal-footer border-top-0 px-0 pb-0 mt-2">
                            <button type="button" class="btn btn-outline-secondary border-secondary border-opacity-25 rounded-pill px-4 fw-semibold shadow-sm" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-warning rounded-pill px-4 fw-semibold shadow-sm text-body-emphasis d-flex align-items-center gap-2" :disabled="saving">
                                <span x-show="saving" class="spinner-border spinner-border-sm" role="status"></span>
                                <i class="bi bi-arrow-return-left" x-show="!saving"></i> Process Return
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
