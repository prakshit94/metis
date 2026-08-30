@extends('layouts.app')

@section('title', 'Dashboard')
@section('page', 'dashboard')

@section('content')
<div x-data="{ 
    activeTab: new URLSearchParams(window.location.search).has('filter') ? 'dashboard' : (localStorage.getItem('dashboard_active_tab') || 'search'),
    showAnalytics: localStorage.getItem('dashboard_show_analytics') === 'true'
}" x-init="$watch('activeTab', val => localStorage.setItem('dashboard_active_tab', val)); $watch('showAnalytics', val => localStorage.setItem('dashboard_show_analytics', val))">
    <!-- Page Header Tabs -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4 pb-3 border-bottom">
        <ul class="nav nav-pills gap-2" role="tablist">
            <li class="nav-item">
                <button class="nav-link fw-bold px-4 rounded-pill" :class="activeTab === 'search' ? 'active shadow-sm' : 'text-muted'" @click="activeTab = 'search'">
                    <i class="bi bi-search me-2"></i> Customer Search
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link fw-bold px-4 rounded-pill" :class="activeTab === 'dashboard' ? 'active shadow-sm' : 'text-muted'" @click="activeTab = 'dashboard'">
                    <i class="bi bi-grid me-2"></i> Dashboard
                </button>
            </li>
        </ul>
        <div x-show="activeTab === 'dashboard'" x-transition.opacity.duration.300ms class="d-flex align-items-center gap-2">
            <label class="text-muted small fw-bold mb-0 text-nowrap"><i class="bi bi-calendar3 me-1"></i> Date Filter:</label>
            <select x-select class="form-select form-select-sm fw-semibold shadow-sm border-0 bg-body-tertiary rounded-pill px-3" style="min-width: 140px; cursor: pointer;" onchange="window.location.href = '?filter=' + this.value">
                <option value="today" {{ ($filter ?? 'today') === 'today' ? 'selected' : '' }}>Today</option>
                <option value="yesterday" {{ ($filter ?? 'today') === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                <option value="this_week" {{ ($filter ?? 'today') === 'this_week' ? 'selected' : '' }}>This Week</option>
                <option value="this_month" {{ ($filter ?? 'today') === 'this_month' ? 'selected' : '' }}>This Month</option>
                <option value="prev_month" {{ ($filter ?? 'today') === 'prev_month' ? 'selected' : '' }}>Previous Month</option>
            </select>
            <div class="form-check form-switch m-0 ms-2 cursor-pointer d-flex align-items-center gap-2">
                <input class="form-check-input m-0" type="checkbox" role="switch" id="analyticsToggle" x-model="showAnalytics" style="cursor: pointer; width: 2.5em; height: 1.25em;">
                <label class="form-check-label small fw-bold text-muted mb-0 ms-1" for="analyticsToggle" style="cursor: pointer; padding-top: 2px;">Analytics</label>
            </div>
        </div>
    </div>

    <!-- Tab 1: Customer Search -->
    <div x-show="activeTab === 'search'" x-transition.opacity.duration.300ms>
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mt-4 bg-body-tertiary">
            <div class="card-body p-4 p-md-5 text-center">
                <div class="mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle mb-3 overflow-hidden" style="width: 80px; height: 80px;">
                        <img src="{{ asset('assets/images/farmersprofileimage.png') }}" alt="Find a Customer" class="w-100 h-100 object-fit-cover">
                    </div>
                    <h2 class="h3 fw-bold text-body mb-2">Find a Customer</h2>
                    <p class="text-muted">Search by mobile number, customer ID, or name to quickly access records.</p>
                </div>
                <div class="mx-auto position-relative" style="max-width: 650px;" x-data="customerSearchApp()">
                    <div class="input-group input-group-lg shadow-sm rounded-pill overflow-hidden bg-body border">
                        <span class="input-group-text border-0 bg-transparent fw-semibold text-body px-4" style="border-right: 1px solid var(--bs-border-color) !important;">
                            <i class="bi bi-phone me-2"></i> Mobile
                        </span>
                        <input type="text" class="form-control border-0 shadow-none px-4 bg-transparent text-body" placeholder="Enter 10-digit mobile number..." x-model="searchPhone" @keydown.enter.prevent="searchCustomer()" maxlength="10">
                        <button class="btn btn-primary px-4 px-md-5 fw-bold" type="button" @click="searchCustomer()" :disabled="isLoading">
                            <span x-show="!isLoading"><i class="bi bi-search me-1 d-none d-sm-inline"></i> Search</span>
                            <span x-show="isLoading" class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display: none;"></span>
                        </button>
                    </div>
                    <div x-show="errorMsg" x-cloak class="position-absolute top-100 start-0 w-100 mt-2 text-danger small fw-bold bg-body p-3 rounded-4 shadow-lg border border-danger border-opacity-25" style="z-index: 1000; text-align: left;">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <span x-text="errorMsg"></span>
                        </div>
                    </div>
                </div>
                <div class="mt-5 text-muted small">
                    <p class="mb-0"><i class="bi bi-info-circle me-1"></i> Ensure you select the correct search criterion for best results.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab 2: Dashboard -->
    <div x-show="activeTab === 'dashboard'" x-transition.opacity.duration.300ms x-cloak>

                <!-- Stats Cards with Alpine.js -->
                <div class="row g-4 g-lg-5 g-xl-6 mb-5 mb-lg-5 mb-xl-6">
                    <div class="col-xl-3 col-lg-6" x-data="statsCounter({{ $totalCustomers }}, 5)">
                        <div class="card metric-card visitors">
                            <div class="card-body p-3 p-lg-4">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <h6 class="text-muted mb-1">Total Customers</h6>
                                        <div class="h3 mb-0" aria-live="polite"><span x-text="value.toLocaleString()">{{ number_format($totalCustomers) }}</span></div>
                                        <small class="trend-up">
                                            <i class="bi bi-arrow-up"></i> +12.5% from last month
                                        </small>
                                    </div>
                                    <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                                        <i class="bi bi-people"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-lg-6" x-data="statsCounter({{ $totalRevenue }}, 5)">
                        <div class="card metric-card revenue">
                            <div class="card-body p-3 p-lg-4">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <h6 class="text-muted mb-1">Total Revenue</h6>
                                        <div class="h3 mb-0" aria-live="polite"><span x-text="'₹' + value.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})">₹{{ number_format($totalRevenue, 2) }}</span></div>
                                        <small class="trend-up">
                                            <i class="bi bi-arrow-up"></i> +8.2% from last month
                                        </small>
                                    </div>
                                    <div class="stats-icon bg-success bg-opacity-10 text-success">
                                        <i class="bi bi-currency-rupee"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-lg-6" x-data="statsCounter({{ $totalOrders }}, 5)">
                        <div class="card metric-card conversion">
                            <div class="card-body p-3 p-lg-4">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <h6 class="text-muted mb-1">Total Orders</h6>
                                        <div class="h3 mb-0" aria-live="polite"><span x-text="value.toLocaleString()">{{ number_format($totalOrders) }}</span></div>
                                        <small class="trend-down">
                                            <i class="bi bi-arrow-down"></i> -2.1% from last month
                                        </small>
                                    </div>
                                    <div class="stats-icon bg-warning bg-opacity-10 text-warning">
                                        <i class="bi bi-bag-check"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-lg-6" x-data="statsCounter({{ $totalProducts }}, 5)">
                        <div class="card metric-card bounce">
                            <div class="card-body p-3 p-lg-4">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <h6 class="text-muted mb-1">Total Products</h6>
                                        <div class="h3 mb-0" aria-live="polite"><span x-text="value.toLocaleString()">{{ number_format($totalProducts) }}</span></div>
                                        <small class="trend-up">
                                            <i class="bi bi-arrow-up"></i> +5.4% from last month
                                        </small>
                                    </div>
                                    <div class="stats-icon bg-info bg-opacity-10 text-info">
                                        <i class="bi bi-box-seam"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                <!-- Order Lifecycle & Performance Summary -->
                <div class="row g-4 g-lg-5 g-xl-6 mb-5 mb-lg-5 mb-xl-6">
                    <!-- Inception & Processing -->
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 border-0 bg-info bg-opacity-10 shadow-sm rounded-4">
                            <div class="card-body p-3 p-lg-4">
                                <div class="d-flex align-items-center mb-3 border-bottom border-info border-opacity-25 pb-2">
                                    <i class="bi bi-cart-plus text-info fs-5 me-2"></i>
                                    <h6 class="fw-bold text-info mb-0" style="text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px;">Pipeline</h6>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small fw-semibold">Pending</span>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-bold text-body-emphasis fs-6">{{ $orderStatusRaw['pending'] ?? 0 }}</span>
                                        <span class="badge bg-info bg-opacity-25 text-info-emphasis border border-info border-opacity-50" style="font-size: 9px;">{{ $orderStatusPercent['pending'] ?? 0 }}%</span>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small fw-semibold">Confirmed</span>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-bold text-body-emphasis fs-6">{{ $orderStatusRaw['confirmed'] ?? 0 }}</span>
                                        <span class="badge bg-info bg-opacity-25 text-info-emphasis border border-info border-opacity-50" style="font-size: 9px;">{{ $orderStatusPercent['confirmed'] ?? 0 }}%</span>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small fw-semibold">Processing</span>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-bold text-body-emphasis fs-6">{{ $orderStatusRaw['processing'] ?? 0 }}</span>
                                        <span class="badge bg-info bg-opacity-25 text-info-emphasis border border-info border-opacity-50" style="font-size: 9px;">{{ $orderStatusPercent['processing'] ?? 0 }}%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping & Transit -->
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 border-0 bg-primary bg-opacity-10 shadow-sm rounded-4">
                            <div class="card-body p-3 p-lg-4">
                                <div class="d-flex align-items-center mb-3 border-bottom border-primary border-opacity-25 pb-2">
                                    <i class="bi bi-truck text-primary fs-5 me-2"></i>
                                    <h6 class="fw-bold text-primary mb-0" style="text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px;">Logistics</h6>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small fw-semibold">Ready to Ship</span>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-bold text-body-emphasis fs-6">{{ $orderStatusRaw['ready_to_ship'] ?? 0 }}</span>
                                        <span class="badge bg-primary bg-opacity-25 text-primary-emphasis border border-primary border-opacity-50" style="font-size: 9px;">{{ $orderStatusPercent['ready_to_ship'] ?? 0 }}%</span>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small fw-semibold">Dispatched</span>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-bold text-body-emphasis fs-6">{{ $orderStatusRaw['dispatched'] ?? 0 }}</span>
                                        <span class="badge bg-primary bg-opacity-25 text-primary-emphasis border border-primary border-opacity-50" style="font-size: 9px;">{{ $orderStatusPercent['dispatched'] ?? 0 }}%</span>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small fw-semibold">Shipped</span>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-bold text-body-emphasis fs-6">{{ $orderStatusRaw['shipped'] ?? 0 }}</span>
                                        <span class="badge bg-primary bg-opacity-25 text-primary-emphasis border border-primary border-opacity-50" style="font-size: 9px;">{{ $orderStatusPercent['shipped'] ?? 0 }}%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Fulfillment Performance -->
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 border-0 bg-success bg-opacity-10 shadow-sm rounded-4">
                            <div class="card-body p-3 p-lg-4">
                                <div class="d-flex align-items-center mb-3 border-bottom border-success border-opacity-25 pb-2">
                                    <i class="bi bi-check-circle text-success fs-5 me-2"></i>
                                    <h6 class="fw-bold text-success mb-0" style="text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px;">Fulfillment</h6>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small fw-semibold">Delivered</span>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-bold text-body-emphasis fs-6">{{ number_format($totalDelivered) }}</span>
                                        <span class="badge bg-success bg-opacity-25 text-success-emphasis border border-success border-opacity-50" style="font-size: 9px;">{{ $deliveredPercent }}%</span>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small fw-semibold">Completed</span>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-bold text-body-emphasis fs-6">{{ $orderStatusRaw['completed'] ?? 0 }}</span>
                                        <span class="badge bg-success bg-opacity-25 text-success-emphasis border border-success border-opacity-50" style="font-size: 9px;">{{ $orderStatusPercent['completed'] ?? 0 }}%</span>
                                    </div>
                                </div>
                                <div class="mt-3 pt-2 border-top border-success border-opacity-25">
                                    <span class="text-muted d-block small mb-1 fw-semibold">Rev. Delivered</span>
                                    <span class="fw-bold text-success fs-5">₹{{ number_format($revDelivered, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Returns Performance -->
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 border-0 bg-danger bg-opacity-10 shadow-sm rounded-4">
                            <div class="card-body p-3 p-lg-4">
                                <div class="d-flex align-items-center mb-3 border-bottom border-danger border-opacity-25 pb-2">
                                    <i class="bi bi-x-circle text-danger fs-5 me-2"></i>
                                    <h6 class="fw-bold text-danger mb-0" style="text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px;">Exceptions</h6>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small fw-semibold">Returned</span>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-bold text-body-emphasis fs-6">{{ number_format($totalReturned) }}</span>
                                        <span class="badge bg-danger bg-opacity-25 text-danger-emphasis border border-danger border-opacity-50" style="font-size: 9px;">{{ $returnedPercent }}%</span>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small fw-semibold">Cancelled</span>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-bold text-body-emphasis fs-6">{{ $orderStatusRaw['cancelled'] ?? 0 }}</span>
                                        <span class="badge bg-danger bg-opacity-25 text-danger-emphasis border border-danger border-opacity-50" style="font-size: 9px;">{{ $orderStatusPercent['cancelled'] ?? 0 }}%</span>
                                    </div>
                                </div>
                                <div class="mt-3 pt-2 border-top border-danger border-opacity-25">
                                    <span class="text-muted d-block small mb-1 fw-semibold">Rev. Returned</span>
                                    <span class="fw-bold text-danger fs-5">₹{{ number_format($revReturned, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                </div>

                <div x-show="showAnalytics" x-transition>
                <!-- Charts Row 1 -->
                <div class="row g-4 g-lg-5 g-xl-6 mb-5 mb-lg-5 mb-xl-6">
                    <div class="col-lg-8">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h2 class="h5 card-title mb-0">Revenue Analytics</h2>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-secondary active" data-chart-period="7d">7D</button>
                                    <button type="button" class="btn btn-outline-secondary" data-chart-period="30d">30D</button>
                                    <button type="button" class="btn btn-outline-secondary" data-chart-period="90d">90D</button>
                                    <button type="button" class="btn btn-outline-secondary" data-chart-period="1y">1Y</button>
                                </div>
                            </div>
                            <div class="card-body p-3 p-lg-4">
                                <div class="chart-container" style="position: relative; overflow: hidden;">
                                    <div id="revenueChart" style="min-height: 320px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h2 class="h5 card-title mb-0">Order Status Distribution</h2>
                            </div>
                            <div class="card-body p-3 p-lg-4">
                                <div id="orderStatusChart" style="min-height: 280px;" class="mb-4"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row 2 -->
                <div class="row g-4 g-lg-5 g-xl-6 mb-5 mb-lg-5 mb-xl-6">
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h2 class="h5 card-title mb-0">User Growth (Last 7 Days)</h2>
                            </div>
                            <div class="card-body p-3 p-lg-4">
                                <div id="userGrowthChart" style="min-height: 280px;"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h2 class="h5 card-title mb-0">Storage Status</h2>
                            </div>
                            <div class="card-body p-3 p-lg-4">
                                <div id="storageStatusChart"></div>
                            </div>
                        </div>
                    </div>
                </div>

                </div> <!-- End showAnalytics wrapper -->
                <!-- Data Tables -->
                <div class="row g-4 g-lg-5 g-xl-6 mb-5 mb-lg-5 mb-xl-6">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h2 class="h5 card-title mb-0">Recent Orders</h2>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Order ID</th>
                                                <th>Customer</th>
                                                <th>Items</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody id="recent-orders-table">
                                            <!-- Orders will be injected here by dashboard.js -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Geographic Data -->
                <div class="row g-4 g-lg-5 g-xl-6 mb-5 mb-lg-5 mb-xl-6" x-show="showAnalytics" x-transition>
                    <div class="col-12">
                        <div class="card h-100">
                            <div class="card-header">
                                <h2 class="h5 card-title mb-0">Sales by Location</h2>
                            </div>
                            <div class="card-body p-3 p-lg-4">
                                <div id="salesByLocationChart" style="min-height: 400px; width: 100%;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    window.dashboardData = @json($dashboardData);

                    window.customerSearchApp = function() {
                        return {
                            searchPhone: '',
                            isLoading: false,
                            errorMsg: '',
                            searchCustomer() {
                                this.searchPhone = this.searchPhone.replace(/\D/g, '');
                                if (this.searchPhone.length !== 10) {
                                    this.errorMsg = 'Please enter exactly 10 digits.';
                                    setTimeout(() => { this.errorMsg = ''; }, 3000);
                                    return;
                                }
                                
                                this.errorMsg = '';
                                this.isLoading = true;
                                
                                fetch(`/customers/search-by-phone?phone=${this.searchPhone}`, {
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest'
                                    }
                                })
                                    .then(async res => {
                                        if (!res.ok) {
                                            if (res.status === 403) throw new Error('You do not have permission to search customers.');
                                            throw new Error('Error searching customer.');
                                        }
                                        return res.json();
                                    })
                                    .then(data => {
                                        this.isLoading = false;
                                        if (data.found && data.redirect) {
                                            window.location.href = data.redirect;
                                        } else {
                                            window.globalSearchPhone = this.searchPhone;
                                            window.dispatchEvent(new CustomEvent('open-add-customer-modal', { detail: { phone: this.searchPhone } }));
                                        }
                                    })
                                    .catch(err => {
                                        this.isLoading = false;
                                        this.errorMsg = err.message || 'Error searching customer. Please try again.';
                                        setTimeout(() => { this.errorMsg = ''; }, 3000);
                                    });
                            }
                        };
                    };
                </script>
    </div> <!-- End Tab 2 -->
</div> <!-- End x-data -->

@if(request()->has('profile_closed'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toastHTML = `
        <div class="toast show align-items-center text-bg-success border-0 shadow-lg rounded-3 mb-2" role="alert" aria-live="assertive" aria-atomic="true" style="opacity: 1; transition: opacity 0.3s ease;">
            <div class="d-flex">
                <div class="toast-body fw-bold">
                    <i class="bi bi-check-circle-fill me-2"></i>Customer profile successfully closed.
                </div>
                <button type="button" class="btn-close btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close" onclick="this.closest('.toast').remove()"></button>
            </div>
        </div>`;
        const container = document.getElementById('toast-container');
        if (container) {
            container.insertAdjacentHTML('beforeend', toastHTML);
            setTimeout(() => {
                const t = container.querySelector('.toast.show');
                if (t) {
                    t.style.opacity = '0';
                    setTimeout(() => t.remove(), 300);
                }
            }, 4000);
        }
        
        // Clean up URL so toast doesn't reappear on refresh
        if (window.history && window.history.replaceState) {
            const url = new URL(window.location.href);
            url.searchParams.delete('profile_closed');
            window.history.replaceState({}, document.title, url.toString());
        }
    });
</script>
@endif
@endsection

@push('modals')
<div class="modal fade" id="iconDemoModal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-palette me-2"></i>
                        Icon System Demo
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" x-data="iconDemo">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>Current Provider: <span class="badge bg-primary" x-text="currentProvider"></span></h6>
                            <div class="btn-group" role="group">
                                <button type="button" 
                                        class="btn btn-outline-primary"
                                        @click="switchProvider('bootstrap')"
                                        :class="{ 'active': currentProvider === 'bootstrap' }">
                                    Bootstrap Icons
                                </button>
                                <button type="button" 
                                        class="btn btn-outline-primary"
                                        @click="switchProvider('lucide')"
                                        :class="{ 'active': currentProvider === 'lucide' }">
                                    Lucide Icons
                                </button>
                            </div>
                        </div>
                    </div>


@endpush
