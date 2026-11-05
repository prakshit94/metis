@extends('layouts.app')

@section('title', 'Dashboard')
@section('page', 'dashboard')

@push('head')
<style>
.business-dashboard .card { border-radius: .875rem; }
.business-dashboard .card-header { padding: 1rem 1.25rem; background: var(--bs-body-bg); border-radius: .875rem .875rem 0 0; }
.business-dashboard .dashboard-toolbar { background: var(--bs-body-bg); border: 1px solid var(--bs-border-color); border-radius: .875rem; padding: 1rem 1.25rem; }
.business-dashboard .dashboard-toolbar form { flex: 1 1 400px; }
.business-dashboard .dashboard-toolbar select[name=filter] { width: auto; min-width: 160px; }
.business-dashboard .dashboard-section-title { font-size: 1.1rem; font-weight: 600; margin-bottom: 1rem; }
.business-dashboard .metric-card .h3, .business-dashboard .sales-value-metric strong { font-variant-numeric: tabular-nums; overflow-wrap: anywhere; }
.business-dashboard .sales-report-table { min-width: 1060px; }
.business-dashboard .sales-report-table th, .business-dashboard .sales-report-table td { padding: .875rem 1rem; }
.business-dashboard .sales-report-table tbody th { background: var(--bs-body-bg); position: sticky; left: 0; z-index: 1; }
.business-dashboard .sales-report-table thead th:first-child { position: sticky; left: 0; z-index: 2; background: var(--bs-tertiary-bg); }
.business-dashboard .sales-report-footer { background: var(--bs-tertiary-bg); border-radius: 0 0 .875rem .875rem; }
.business-dashboard .sales-group-tabs { display: flex; flex-wrap: wrap; gap: .5rem; }
.business-dashboard .sales-group-tabs .btn { border-radius: .5rem !important; }
.business-dashboard .sales-report-help { background: var(--bs-tertiary-bg); border: 1px solid var(--bs-border-color); border-radius: .5rem; padding: .5rem .75rem; }
@media (max-width: 767px) {
    .business-dashboard .dashboard-toolbar { padding: 1rem; }
    .business-dashboard .dashboard-toolbar form { flex-basis: 100%; }
    .business-dashboard .dashboard-toolbar-actions { width: 100%; justify-content: space-between; }
    .business-dashboard .dashboard-toolbar form > select { flex: 1; }
    .business-dashboard .dashboard-toolbar form > div { width: 100%; }
    .business-dashboard .dashboard-toolbar input[type=date] { flex: 1 1 140px; min-width: 0; }
    .business-dashboard .sales-report-search { max-width: none !important; width: 100%; }
    .business-dashboard .sales-report-footer nav { width: 100%; justify-content: space-between; }
    .business-dashboard .pagination { flex-wrap: wrap; gap: .25rem; }
}
.business-dashboard .customer-search-panel { max-width: 780px; margin: clamp(1rem, 4vw, 3rem) auto; border: 1px solid var(--bs-border-color); border-radius: 1.25rem; background: var(--bs-body-bg); box-shadow: 0 .5rem 2rem rgba(var(--bs-primary-rgb), .06); overflow: hidden; }
.business-dashboard .customer-search-accent { height: 4px; background: var(--bs-primary); }
.business-dashboard .customer-search-body { padding: clamp(1.25rem, 4vw, 3rem); }
.business-dashboard .customer-search-avatar { width: 64px; height: 64px; border-radius: 1rem; overflow: hidden; background: var(--bs-tertiary-bg); }
.business-dashboard .customer-search-controls { display: flex; gap: .75rem; align-items: stretch; }
.business-dashboard .customer-phone-field { display: flex; align-items: center; flex: 1; min-width: 0; border: 1px solid var(--bs-border-color); background: var(--bs-body-bg); border-radius: .75rem; padding: 0 1rem; transition: border-color .15s, box-shadow .15s; }
.business-dashboard .customer-phone-field:focus-within { border-color: var(--bs-primary); box-shadow: 0 0 0 .2rem rgba(var(--bs-primary-rgb), .12); }
.business-dashboard .customer-phone-field input { min-width: 0; min-height: 54px; font-size: 1.05rem; font-variant-numeric: tabular-nums; background: transparent; border: 0; box-shadow: none; padding: .75rem; }
.business-dashboard .customer-search-submit { border-radius: .75rem; min-height: 54px; padding: .75rem 1.5rem; font-weight: 600; }
.business-dashboard .customer-search-error { border-radius: .75rem; padding: .75rem 1rem; background: rgba(var(--bs-danger-rgb), .06); border: 1px solid rgba(var(--bs-danger-rgb), .2); }
@media (max-width: 575px) {
    .business-dashboard .customer-search-controls { flex-direction: column; }
    .business-dashboard .customer-search-submit { width: 100%; }
    .business-dashboard .customer-search-heading { font-size: 1.5rem; }
}
.business-dashboard .dashboard-info-button { font-size: .85rem; line-height: 1; border-radius: 50%; text-transform: none; letter-spacing: normal; vertical-align: middle; }
.business-dashboard .dashboard-info-button:focus-visible { outline: 2px solid var(--bs-primary); outline-offset: 2px; }
</style>
<script>
window.dashboardDateFilter = function(period, from, to) {
    return {
        period, from, to,
        get maxTo() {
            if (!this.from) return '';
            const start = new Date(this.from + 'T12:00:00Z');
            if (!Number.isFinite(+start)) return '';
            const nextMonth = new Date(Date.UTC(start.getUTCFullYear(), start.getUTCMonth()+1, 1, 12));
            const lastDay = new Date(Date.UTC(start.getUTCFullYear(), start.getUTCMonth()+2, 0, 12)).getUTCDate();
            nextMonth.setUTCDate(Math.min(start.getUTCDate(),lastDay));
            nextMonth.setUTCDate(nextMonth.getUTCDate()-1);
            return nextMonth.toISOString().slice(0,10);
        },
        clampDates() {
            if (this.to > this.maxTo) this.to = this.maxTo;
            if (this.to < this.from) this.to = this.from;
        }
    };
};
</script>
@endpush

@section('content')
<div class="business-dashboard" x-data="{
    activeTab: new URLSearchParams(window.location.search).has('phone') ? 'search' : new URLSearchParams(window.location.search).has('filter') ? 'dashboard' : (localStorage.getItem('dashboard_active_tab') || 'search'),
    showAnalytics: localStorage.getItem('dashboard_show_analytics') === 'true'
}" x-init="$watch('activeTab', val => localStorage.setItem('dashboard_active_tab', val)); $watch('showAnalytics', val => localStorage.setItem('dashboard_show_analytics', val))">
    <!-- Page Header Tabs -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-3">
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
    </div>
    <div x-show="activeTab === 'dashboard'" x-transition.opacity.duration.300ms x-cloak>
        <div class="dashboard-toolbar dashboard-filters d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <form method="GET" action="{{ route('dashboard') }}" class="d-flex flex-wrap align-items-center gap-2" x-data="dashboardDateFilter(@js($filter), @js($reportPeriod['from']), @js($reportPeriod['to']))">
            <label for="dashboardDateFilter" class="text-muted small fw-bold mb-0 text-nowrap"><i class="bi bi-calendar3 me-1"></i> Date Filter:</label>
            <select id="dashboardDateFilter" class="form-select form-select-sm fw-semibold shadow-sm border-0 bg-body-tertiary rounded-pill ps-3" style="min-width: 140px; cursor: pointer;" name="filter" x-model="period" @change="if (period !== 'custom') $nextTick(() => $el.form.requestSubmit()); else clampDates()">
                <option value="today" {{ ($filter ?? 'today') === 'today' ? 'selected' : '' }}>Today</option>
                <option value="yesterday" {{ ($filter ?? 'today') === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                <option value="this_week" {{ ($filter ?? 'today') === 'this_week' ? 'selected' : '' }}>This Week</option>
                <option value="this_month" {{ ($filter ?? 'today') === 'this_month' ? 'selected' : '' }}>This Month</option>
                <option value="prev_month" {{ ($filter ?? 'today') === 'prev_month' ? 'selected' : '' }}>Previous Month</option>
                <option value="this_year" {{ ($filter ?? 'today') === 'this_year' ? 'selected' : '' }}>This Year</option>
                <option value="last_7_days" {{ $filter === 'last_7_days' ? 'selected' : '' }}>Last 7 days</option>
                <option value="last_30_days" {{ $filter === 'last_30_days' ? 'selected' : '' }}>Last 30 days</option>
                <option value="custom" {{ $filter === 'custom' ? 'selected' : '' }}>Custom dates</option>
            </select>
            <template x-if="period === 'custom'">
            <div class="d-flex flex-wrap gap-2">
                <input type="date" name="from" class="form-control form-control-sm w-auto" aria-label="Dashboard from date" x-model="from" @change="clampDates()" required>
                <input type="date" name="to" class="form-control form-control-sm w-auto" aria-label="Dashboard to date (maximum one month)" x-model="to" :min="from" :max="maxTo" required>
                <button class="btn btn-sm btn-primary" type="submit">Apply</button>
                <span class="small text-muted align-self-center">Maximum 1 month</span>
            </div>
            </template>
            </form>
            <div class="dashboard-toolbar-actions d-flex flex-wrap align-items-center gap-3">
            <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3 shadow-sm bg-body-tertiary" onclick="window.location.reload()" data-bs-toggle="tooltip" title="Refresh data">
                <i class="bi bi-arrow-clockwise icon-hover"></i>
            </button>
            <div class="form-check form-switch m-0 cursor-pointer d-flex align-items-center gap-2">
                <input class="form-check-input m-0" type="checkbox" role="switch" id="analyticsToggle" x-model="showAnalytics" style="cursor: pointer; width: 2.5em; height: 1.25em;">
                <label class="form-check-label small fw-bold text-muted mb-0 ms-1" for="analyticsToggle" style="cursor: pointer; padding-top: 2px;">Analytics</label>
            </div>
            </div>
        </div>
    </div>

    <!-- Tab 1: Customer Search -->
    <div x-show="activeTab === 'search'" x-transition.opacity.duration.300ms>
        <section class="customer-search-panel" aria-labelledby="customer-search-heading">
            <div class="customer-search-accent" aria-hidden="true"></div>
            <div class="customer-search-body">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="customer-search-avatar flex-shrink-0">
                        <img src="{{ asset('assets/images/farmersprofileimage.png') }}" alt="" class="w-100 h-100 object-fit-cover">
                    </div>
                    <div>
                        <h2 id="customer-search-heading" class="customer-search-heading h3 fw-bold mb-1">Find a Customer</h2>
                        <p class="text-muted mb-0">Search using their mobile number.</p>
                    </div>
                </div>
                <div x-data="customerSearchApp()">
                    <label for="customer-search-phone" class="form-label fw-semibold">Mobile number</label>
                    <div class="customer-search-controls">
                        <div class="customer-phone-field">
                            <i class="bi bi-phone text-primary fs-5" aria-hidden="true"></i>
                            <input id="customer-search-phone" type="text" class="form-control text-body" placeholder="Enter 10-digit number" x-model="searchPhone" @keydown.enter.prevent="searchCustomer()" inputmode="numeric" autocomplete="tel-national" minlength="10" maxlength="10" pattern="[0-9]{10}" aria-label="10-digit mobile number" :aria-invalid="!!errorMsg" aria-describedby="customer-phone-help customer-phone-error">
                        </div>
                        <button class="btn btn-primary customer-search-submit" type="button" @click="searchCustomer()" :disabled="isLoading" :aria-busy="isLoading">
                            <span x-show="!isLoading"><i class="bi bi-search me-2" aria-hidden="true"></i>Find customer</span>
                            <span x-show="isLoading" x-cloak><span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Searching…</span>
                        </button>
                    </div>
                    <p id="customer-phone-help" class="small text-muted mt-2 mb-0">Exactly 10 digits, without spaces or a country code.</p>
                    <div id="customer-phone-error" role="alert" x-show="errorMsg" x-cloak class="customer-search-error mt-3 text-danger small">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-exclamation-circle mt-1" aria-hidden="true"></i>
                            <span x-text="errorMsg"></span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Tab 2: Dashboard -->
    <div x-show="activeTab === 'dashboard'" x-transition.opacity.duration.300ms x-cloak>

                <h2 class="dashboard-section-title">Summary <x-dashboard-info title="Summary" description="Figures use your existing access permissions and selected date range. Orders are filtered by the date they were placed; new customers use the profile creation date. Each card has its own inclusion rules." /></h2>
                <!-- Stats Cards with Alpine.js -->
                <div class="row g-3 mb-4">
                    <div class="col-xl-3 col-lg-6" x-data="statsCounter({{ $totalCustomers }}, 5)">
                        <div class="card metric-card visitors h-100 border-start border-4 border-primary">
                            <div class="card-body p-3 p-lg-4">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <h6 class="text-muted mb-1">New customers <x-dashboard-info title="New customers" description="Count of customer profiles created during the selected dates that are visible under your existing customer access permissions." /></h6>
                                        <div class="h3 mb-0" aria-live="polite"><span x-text="value.toLocaleString()">{{ number_format($totalCustomers) }}</span></div>
                                        <small class="text-muted">For selected dates</small>
                                    </div>
                                    <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                                        <i class="bi bi-people"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-lg-6" x-data="statsCounter({{ $totalRevenue }}, 5)">
                        <div class="card metric-card revenue h-100 border-start border-4 border-success">
                            <div class="card-body p-3 p-lg-4">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <h6 class="text-muted mb-1" title="Same as Net sales in the Sales summary. Excludes cancelled, future and returned orders, and orders with completed returns. Wallet payments are shown separately.">Net sales <x-dashboard-info title="Net sales" description="Sum of stored net payable for sales orders in the selected dates. Excludes cancelled, future and returned orders, and any order with a completed return. Wallet used is not added. This is the same total as Net sales in Sales summary, not cash collected." /></h6>
                                        <div class="h3 mb-0" aria-live="polite"><span x-text="'₹' + value.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})">₹{{ number_format($totalRevenue, 2) }}</span></div>
                                        <small class="text-muted">For selected dates</small>
                                    </div>
                                    <div class="stats-icon bg-success bg-opacity-10 text-success">
                                        <i class="bi bi-currency-rupee"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-lg-6" x-data="statsCounter({{ $totalOrders }}, 5)">
                        <div class="card metric-card conversion h-100 border-start border-4 border-warning">
                            <div class="card-body p-3 p-lg-4">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <h6 class="text-muted mb-1">Order count <x-dashboard-info title="Order count" description="Count of visible orders placed during the selected dates, excluding future orders. Cancelled and returned orders remain in this count. This count has different inclusion rules from Net sales." /></h6>
                                        <div class="h3 mb-0" aria-live="polite"><span x-text="value.toLocaleString()">{{ number_format($totalOrders) }}</span></div>
                                        <small class="text-muted">For selected dates</small>
                                    </div>
                                    <div class="stats-icon bg-warning bg-opacity-10 text-warning">
                                        <i class="bi bi-bag-check"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-lg-6">
                        <div class="card metric-card bounce h-100 border-start border-4 border-info">
                            <div class="card-body p-3 p-lg-4">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <h6 class="text-muted mb-1">Product variants / Units <x-dashboard-info title="Product variants / Units" description="First number: distinct product and variant combinations on sales orders. Second number: total item quantity, displayed as a whole number. Excludes cancelled and returned statuses, but includes future orders within the selected order-date range. Completed returns do not deduct item quantities here." /></h6>
                                        <div class="h3 mb-0" aria-live="polite">
                                            <span x-data="statsCounter({{ $totalVariants }}, 5)" x-text="value.toLocaleString()">{{ number_format($totalVariants) }}</span>
                                            <span class="text-muted fs-5 fw-normal mx-1">/</span>
                                            <span x-data="statsCounter({{ $totalProducts }}, 5)" x-text="value.toLocaleString()">{{ number_format($totalProducts) }}</span>
                                        </div>
                                        <small class="text-muted">For selected dates</small>
                                    </div>
                                    <div class="stats-icon bg-info bg-opacity-10 text-info">
                                        <i class="bi bi-box-seam"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div> <!-- End stats row -->

                <!-- Order Lifecycle & Performance Summary -->
                <div class="row g-3 mb-4">
                    <!-- Inception & Processing -->
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 border-start border-4 border-info bg-info bg-opacity-10 shadow-sm rounded-4">
                            <div class="card-body p-3 p-lg-4">
                                <div class="d-flex align-items-center mb-3 border-bottom border-info border-opacity-25 pb-2">
                                    <i class="bi bi-cart-plus text-info fs-5 me-2"></i>
                                    <h6 class="fw-bold text-info mb-0" style="text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px;">Order progress <x-dashboard-info title="Order progress" description="Counts orders by their current status in the selected order-date range. Orders with a completed return are moved from their current status into Returned for these status counts. Each percentage is the status count divided by Order count, rounded to a whole percent; zero orders gives 0%." /></h6>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small fw-semibold">Pending</span>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-bold text-body-emphasis fs-6">{{ $orderStatusRaw['pending'] ?? 0 }}</span>
                                        <span class="badge bg-secondary bg-opacity-25 text-secondary-emphasis border border-secondary border-opacity-50" style="font-size: 9px;">{{ $orderStatusPercent['pending'] ?? 0 }}%</span>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small fw-semibold">Pending Confirmation</span>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-bold text-body-emphasis fs-6">{{ $orderStatusRaw['pending_confirmation'] ?? 0 }}</span>
                                        <span class="badge bg-warning bg-opacity-25 text-warning-emphasis border border-warning border-opacity-50" style="font-size: 9px;">{{ $orderStatusPercent['pending_confirmation'] ?? 0 }}%</span>
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
                                        <span class="badge bg-primary bg-opacity-25 text-primary-emphasis border border-primary border-opacity-50" style="font-size: 9px;">{{ $orderStatusPercent['processing'] ?? 0 }}%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping & Transit -->
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 border-start border-4 border-primary bg-primary bg-opacity-10 shadow-sm rounded-4">
                            <div class="card-body p-3 p-lg-4">
                                <div class="d-flex align-items-center mb-3 border-bottom border-primary border-opacity-25 pb-2">
                                    <i class="bi bi-truck text-primary fs-5 me-2"></i>
                                    <h6 class="fw-bold text-primary mb-0" style="text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px;">Shipping <x-dashboard-info title="Shipping" description="Counts orders currently Ready to Ship or Dispatched in the selected order-date range, after moving completed-return orders into Returned. Each percentage is that status count divided by Order count, rounded to a whole percent." /></h6>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small fw-semibold">Ready to Ship</span>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-bold text-body-emphasis fs-6">{{ $orderStatusRaw['ready_to_ship'] ?? 0 }}</span>
                                        <span class="badge bg-dark bg-opacity-25 text-dark-emphasis border border-dark border-opacity-50" style="font-size: 9px;">{{ $orderStatusPercent['ready_to_ship'] ?? 0 }}%</span>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small fw-semibold">Dispatched</span>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-bold text-body-emphasis fs-6">{{ $orderStatusRaw['dispatched'] ?? 0 }}</span>
                                        <span class="badge bg-info bg-opacity-25 text-info-emphasis border border-info border-opacity-50" style="font-size: 9px;">{{ $orderStatusPercent['dispatched'] ?? 0 }}%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Fulfillment Performance -->
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 border-start border-4 border-success bg-success bg-opacity-10 shadow-sm rounded-4">
                            <div class="card-body p-3 p-lg-4">
                                <div class="d-flex align-items-center mb-3 border-bottom border-success border-opacity-25 pb-2">
                                    <i class="bi bi-check-circle text-success fs-5 me-2"></i>
                                    <h6 class="fw-bold text-success mb-0" style="text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px;">Delivery <x-dashboard-info title="Delivery" description="Delivered combines delivered and legacy completed orders without a completed return. Completed is the separate legacy status count and may overlap Delivered. Rev. Delivered sums stored net payable for the Delivered group. Delivered percentage = Delivered divided by Order count, rounded to a whole percent." /></h6>
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

                    <!-- Returns & Exceptions -->
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 border-start border-4 border-danger bg-danger bg-opacity-10 shadow-sm rounded-4">
                            <div class="card-body p-3 p-lg-4">
                                <div class="d-flex align-items-center mb-3 border-bottom border-danger border-opacity-25 pb-2">
                                    <i class="bi bi-x-circle text-danger fs-5 me-2"></i>
                                    <h6 class="fw-bold text-danger mb-0" style="text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px;">Orders needing attention <x-dashboard-info title="Orders needing attention" description="Returned counts distinct orders with at least one completed return, including partial returns. Rev. Returned sums their full stored net payable, not the returned item value or refund amount. Cancelled uses the current cancelled count after completed-return reclassification. Percentages divide each count by Order count and round to a whole percent." /></h6>
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

                @include('dashboard.partials.order-values')

                <!-- Data Tables -->
                <div class="row g-3 mb-4">
                    <div class="col-lg-12">
                        <div class="card" x-data="dashboardTableApp()">
                            <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                                <h2 class="h5 card-title mb-0">Orders <x-dashboard-info title="Orders" description="Recent lists visible orders in the selected order-date range except future orders, including cancelled and returned orders. Future lists scheduled orders using the existing future-order rules. Amount shows stored net payable. Search and pagination narrow the displayed list only; they do not recalculate summary cards." /></h2>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="position-relative">
                                        <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-2 text-muted z-1" style="font-size: 0.85rem;"></i>
                                        <input type="search" class="form-control form-control-sm ps-4" aria-label="Search orders" placeholder="Search orders..." x-model="searchQuery" style="width: 200px;">
                                    </div>
                                    <select aria-label="Orders per page" class="form-select form-select-sm" x-model.number="itemsPerPage" style="width: auto;">
                                        <option value="5">5 / page</option>
                                        <option value="10">10 / page</option>
                                        <option value="20">20 / page</option>
                                    </select>
                                    <ul class="nav nav-pills card-header-pills m-0">
                                        <li class="nav-item">
                                            <button class="nav-link btn-sm py-1 px-3 rounded-pill" :class="tableTab === 'recent' ? 'active shadow-sm' : 'text-muted'" @click="tableTab = 'recent'">Recent</button>
                                        </li>
                                        <li class="nav-item ms-2">
                                            <button class="nav-link btn-sm py-1 px-3 rounded-pill" :class="tableTab === 'future' ? 'active shadow-sm' : 'text-muted'" @click="tableTab = 'future'">Future</button>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive dashboard-orders-scroll" tabindex="0" role="region" aria-label="Orders table">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Order ID</th>
                                                <th>Customer</th>
                                                <th>Items</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                                <th>Order Placed</th>
                                                <th>Created By</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="(order, index) in paginatedRecords" :key="order.id">
                                                <tr>
                                                    <td class="text-nowrap">
                                                        <span class="text-secondary opacity-75 me-2" x-text="((currentPage - 1) * itemsPerPage) + index + 1 + '.'"></span>
                                                        <a href="#" class="fw-bold text-decoration-none text-primary align-middle" @click.prevent="$dispatch('fetch-order', order.id)" x-text="order.order_no || order.id"></a>
                                                        <button type="button" class="btn btn-sm btn-link text-muted p-0 ms-2 align-middle" 
                                                                title="Copy Order ID" aria-label="Copy Order ID"
                                                                @click.prevent="copyText(order.order_no || order.id).then((success) => { if (!success) return; $el.innerHTML = '<i class=\'bi bi-check2 text-success\'></i>'; setTimeout(() => $el.innerHTML = '<i class=\'bi bi-clipboard\'></i>', 1500); })">
                                                            <i class="bi bi-clipboard"></i>
                                                        </button>
                                                    </td>
                                                    <td>
                                                        <div class="fw-medium text-body-emphasis" x-text="order.customer"></div>
                                                        <div class="text-muted small" x-text="order.phone || '—'"></div>
                                                    </td>
                                                    <td class="position-relative" x-data="{ showTooltip: false }" @mouseenter="showTooltip = true" @mouseleave="showTooltip = false">
                                                        <div class="text-truncate text-muted small cursor-pointer" style="max-width: 200px;" :title="(order.items || []).map(item => item.name + ' × ' + item.qty).join('\n')">
                                                             <span x-text="order.items && order.items.length > 0 ? order.items[0].name + (order.items.length > 1 ? ' (+' + (order.items.length - 1) + ' more)' : '') : '—'"></span>
                                                        </div>
                                                        <div x-show="showTooltip" 
                                                             x-transition.opacity
                                                             class="position-absolute bg-body border rounded shadow-lg z-3 p-2" 
                                                             style="bottom: 100%; left: 0; min-width: 250px; margin-bottom: 5px;"
                                                             x-cloak>
                                                             <table class="table table-sm table-bordered mb-0 text-start" style="font-size: 0.75rem;">
                                                                 <thead class="table-light">
                                                                     <tr><th>Product</th><th class="text-center" style="width: 50px;">Qty</th></tr>
                                                                 </thead>
                                                                 <tbody>
                                                                     <template x-for="item in order.items">
                                                                         <tr>
                                                                             <td class="text-wrap" x-text="item.name"></td>
                                                                             <td class="text-center" x-text="item.qty"></td>
                                                                         </tr>
                                                                     </template>
                                                                 </tbody>
                                                             </table>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div x-text="order.amount" class="fw-medium"></div>
                                                        <span :class="'badge bg-opacity-10 mt-1 ' + order.payment.class.replace('bg-', 'text-') + ' border border-' + order.payment.class.replace('bg-', '') + ' border-opacity-25'" x-text="order.payment.text" style="font-size: 0.7rem;"></span>
                                                    </td>
                                                    <td>
                                                        <span :class="'badge ' + order.status.class" x-text="order.status.text"></span>
                                                        <template x-if="order.status.tooltip">
                                                            <i class="bi bi-info-circle-fill text-muted fs-6 cursor-pointer ms-2 d-inline-block align-middle" :title="order.status.tooltip" data-bs-toggle="tooltip"></i>
                                                        </template>
                                                    </td>
                                                    <td>
                                                        <template x-if="tableTab === 'recent'">
                                                            <span x-text="order.date"></span>
                                                        </template>
                                                        <template x-if="tableTab === 'future'">
                                                            <div>
                                                                <div x-text="order.placed_date"></div>
                                                                <div class="mt-1">
                                                                    <span class="badge bg-primary-subtle text-primary-emphasis border border-primary border-opacity-25" style="font-size:0.7rem;">
                                                                        <i class="bi bi-calendar-event me-1"></i>Scheduled: <span x-text="order.scheduled_for"></span>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </template>
                                                    </td>
                                                    <td>
                                                        <span class="text-muted small fw-medium" x-text="order.creator"></span>
                                                    </td>
                                                </tr>
                                            </template>
                                            <template x-if="paginatedRecords.length === 0">
                                                <tr>
                                                    <td colspan="7" class="text-center text-muted py-4 small">
                                                        <i class="bi bi-inbox me-2"></i>No orders found.
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <!-- Pagination -->
                            <div class="card-footer bg-body-tertiary border-0 py-3" x-show="totalPages > 1" x-cloak>
                                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                                    <div class="text-muted small fw-medium">
                                        Showing <span class="text-body-emphasis fw-bold" x-text="filteredRecords.length > 0 ? ((currentPage - 1) * itemsPerPage) + 1 : 0"></span> 
                                        to <span class="text-body-emphasis fw-bold" x-text="Math.min(currentPage * itemsPerPage, filteredRecords.length)"></span> 
                                        of <span class="text-body-emphasis fw-bold" x-text="filteredRecords.length"></span> orders
                                    </div>
                                    <nav aria-label="Orders pagination">
                                        <ul class="pagination pagination-sm mb-0">
                                            <li class="page-item" :class="{'disabled': currentPage === 1}">
                                                <button class="page-link" aria-label="Previous orders page" @click="changePage(currentPage - 1)" :disabled="currentPage === 1">
                                                    <i class="bi bi-chevron-left"></i>
                                                </button>
                                            </li>
                                            
                                            <template x-for="page in totalPages" :key="page">
                                                <li class="page-item" :class="{'active': currentPage === page}" x-show="Math.abs(currentPage - page) < 3 || page === 1 || page === totalPages">
                                                    <button class="page-link" @click="changePage(page)" x-text="page"></button>
                                                </li>
                                            </template>
                                            
                                            <li class="page-item" :class="{'disabled': currentPage === totalPages}">
                                                <button class="page-link" aria-label="Next orders page" @click="changePage(currentPage + 1)" :disabled="currentPage === totalPages">
                                                    <i class="bi bi-chevron-right"></i>
                                                </button>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div x-show="showAnalytics" x-transition>
                <!-- Charts Row 1 -->
                <div class="row g-3 mb-4">
                    <div class="col-lg-8">
                        <div class="card h-100 border-start border-4 border-success">
                            <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
                                <h2 class="h5 card-title mb-0">Sales trend <x-dashboard-info title="Sales trend" description="Sums stored net payable by order date, excluding cancelled, returned and future statuses. Uses the selected dashboard dates intersected with the chart window. The profit series is an estimate of 20% of that amount. This chart does not apply the Sales summary completed-return exclusion and may differ from Net sales." /></h2>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-secondary active" data-chart-period="7d">7 days</button>
                                    <button type="button" class="btn btn-outline-secondary" data-chart-period="30d">30 days</button>
                                    <button type="button" class="btn btn-outline-secondary" data-chart-period="90d">90 days</button>
                                    <button type="button" class="btn btn-outline-secondary" data-chart-period="1y">1 year</button>
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
                        <div class="card h-100 border-start border-4 border-warning">
                            <div class="card-header">
                                <h2 class="h5 card-title mb-0">Orders by status <x-dashboard-info title="Orders by status" description="Completed = delivered + legacy completed. Processing = processing + ready to ship + dispatched. Pending = pending + confirmed. Cancelled = cancelled + returned. Completed-return orders are first moved into Returned. Other statuses are not shown in this chart." /></h2>
                            </div>
                            <div class="card-body p-3 p-lg-4">
                                <div id="orderStatusChart" style="min-height: 280px;" class="mb-4"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row 2 -->
                <div class="row g-3 mb-4">
                    <div class="col-lg-6">
                        <div class="card h-100 border-start border-4 border-primary">
                            <div class="card-header">
                                <h2 class="h5 card-title mb-0">New users · Last 7 days <x-dashboard-info title="New users · Last 7 days" description="Counts new customer profiles by creation date for the most recent 7 days in the customer-growth series, within the selected dashboard dates and your customer access scope. These are customers, not staff accounts." /></h2>
                            </div>
                            <div class="card-body p-3 p-lg-4">
                                <div id="userGrowthChart" style="min-height: 280px;"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card h-100 border-start border-4 border-info">
                            <div class="card-header">
                                <h2 class="h5 card-title mb-0">Storage usage <x-dashboard-info title="Storage usage" description="This chart currently displays a fixed demo value of 76%. It is not calculated from live disk usage or warehouse stock." /></h2>
                            </div>
                            <div class="card-body p-3 p-lg-4">
                                <div id="storageStatusChart"></div>
                            </div>
                        </div>
                    </div>
                </div>

                </div> <!-- End showAnalytics wrapper -->
                <!-- Geographic Data -->
                <div class="row g-3 mb-4" x-show="showAnalytics" x-transition>
                    <div class="col-12">
                        <div class="card h-100 border-start border-4 border-secondary">
                            <div class="card-header">
                                <h2 class="h5 card-title mb-0">Sales by state <x-dashboard-info title="Sales by state" description="Shows the top 10 locations by stored net payable, excluding cancelled, returned and future statuses. Location uses shipping district first, then city, then state when the earlier field is null. Despite the section title, entries can be districts or cities. Completed returns do not receive a separate exclusion here." /></h2>
                            </div>
                            <div class="card-body p-3 p-lg-4">
                                <div id="salesByLocationChart" style="min-height: 400px; width: 100%;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    window.dashboardData = @json($dashboardData);

                    window.dashboardTableApp = function() {
                        return {
                            tableTab: 'recent',
                            itemsPerPage: 10,
                            currentPage: 1,
                            searchQuery: '',
                            
                            get allRecords() {
                                return this.tableTab === 'recent' 
                                    ? window.dashboardData.recentOrders 
                                    : window.dashboardData.futureOrders;
                            },
                            
                            get filteredRecords() {
                                if (!this.searchQuery) return this.allRecords;
                                const q = this.searchQuery.toLowerCase();
                                return this.allRecords.filter(o => 
                                    (o.order_no || o.id || '').toString().toLowerCase().includes(q) ||
                                    (o.customer || '').toLowerCase().includes(q) ||
                                    (o.phone || '').toLowerCase().includes(q)
                                );
                            },
                            
                            get totalPages() {
                                return Math.ceil(this.filteredRecords.length / this.itemsPerPage) || 1;
                            },
                            
                            get paginatedRecords() {
                                const start = (this.currentPage - 1) * this.itemsPerPage;
                                return this.filteredRecords.slice(start, start + this.itemsPerPage);
                            },
                            
                            changePage(page) {
                                if (page >= 1 && page <= this.totalPages) {
                                    this.currentPage = page;
                                }
                            },
                            
                            init() {
                                this.$watch('tableTab', () => { this.currentPage = 1; this.searchQuery = ''; });
                                this.$watch('searchQuery', () => { this.currentPage = 1; });
                                this.$watch('itemsPerPage', () => { this.currentPage = 1; });
                            }
                        }
                    };

                    window.customerSearchApp = function() {
                        return {
                            searchPhone: '',
                            isLoading: false,
                            errorMsg: '',
                            init() {
                                const url = new URL(window.location.href);
                                const phone = url.searchParams.get('phone');
                                if (phone === null) return;

                                this.searchPhone = phone;
                                // Consume the link once so cancelling the modal or refreshing does not reopen it.
                                url.searchParams.delete('phone');
                                window.history.replaceState(window.history.state, '', url.toString());
                                // Wait until the global creation modal has registered its Alpine listener.
                                this.$nextTick(() => this.searchCustomer());
                            },
                            searchCustomer() {
                                if (this.isLoading) return;
                                if (!/^[0-9]{10}$/.test(this.searchPhone) || this.searchPhone.length !== 10) {
                                    this.errorMsg = 'Please enter exactly 10 digits.';
                                    return;
                                }
                                
                                this.errorMsg = '';
                                this.isLoading = true;
                                
                                fetch(`/admin/customers/search-by-phone?phone=${this.searchPhone}`, {
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

<div class="modal fade" id="dashboardCalculationHelp" tabindex="-1" aria-labelledby="dashboardCalculationHelpTitle" aria-describedby="dashboardCalculationHelpText" aria-hidden="true" x-data="{ helpTitle: '', helpDescription: '' }" x-init="$el.addEventListener('show.bs.modal', (event) => { helpTitle = event.relatedTarget?.dataset.helpTitle || 'Calculation details'; helpDescription = event.relatedTarget?.dataset.helpDescription || 'Select an information icon to view the rules for that value.'; })">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4">
            <div class="modal-header align-items-start">
                <h2 class="modal-title h5" id="dashboardCalculationHelpTitle" x-text="helpTitle"></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close calculation details"></button>
            </div>
            <div class="modal-body"><p id="dashboardCalculationHelpText" class="mb-0" x-text="helpDescription" style="line-height: 1.7;"></p></div>
            <div class="modal-footer"><button type="button" class="btn btn-primary" data-bs-dismiss="modal">Got it</button></div>
        </div>
    </div>
</div>

@endsection

@push('modals')
<div x-data="orderTable" @fetch-order.window="fetchAndShowOrder($event.detail)">
    @include('orders.partials.modals')
</div>
<div class="modal fade" id="iconDemoModal" tabindex="-1" aria-hidden="true">
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

                </div>
            </div>
        </div>
</div>
@endpush
