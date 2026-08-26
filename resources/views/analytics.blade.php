@extends('layouts.app')

@section('title', 'Analytics Dashboard')
@section('page', 'analytics')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<style>
    /* Prevent layout shifting during Alpine load */
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div class="container-fluid p-4 p-lg-4" x-data="erpAnalyticsDashboard()" x-init="initDashboard()">
    
    <!-- Page Header & Global Filter -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-0">Analytics Dashboard</h1>
            <p class="text-body-secondary mb-0">Real-time comprehensive ERP business insights.</p>
        </div>
        
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            <!-- Loading Indicator -->
            <div x-show="loading" class="spinner-border text-primary spinner-border-sm me-2" role="status" x-cloak>
                <span class="visually-hidden">Loading...</span>
            </div>
            
            <!-- Date Filter Dropdown -->
            <select class="form-select form-select-sm" x-model="period" @change="handlePeriodChange()" style="min-width: 150px;">
                <option value="today">Today</option>
                <option value="yesterday">Yesterday</option>
                <option value="7d">Last 7 Days</option>
                <option value="30d">Last 30 Days</option>
                <option value="this_month">This Month</option>
                <option value="last_month">Last Month</option>
                <option value="3m">Last 3 Months</option>
                <option value="6m">Last 6 Months</option>
                <option value="this_year">This Year</option>
                <option value="custom">Custom Range</option>
            </select>
            <!-- Limit Dropdown -->
            <select class="form-select form-select-sm" x-model="limit" @change="handleLimitChange()" style="min-width: 90px;" data-bs-toggle="tooltip" title="Rows to display">
                <option value="5">Top 5</option>
                <option value="10">Top 10</option>
                <option value="15">Top 15</option>
                <option value="20">Top 20</option>
            </select>
            
            <button type="button" class="btn btn-outline-primary btn-sm" @click="fetchData()" :disabled="loading" data-bs-toggle="tooltip" title="Refresh data">
                <i class="bi bi-arrow-clockwise icon-hover"></i>
            </button>
        </div>
    </div>

    <!-- Custom Date Range Picker (Conditional) -->
    <div class="card mb-4" x-show="period === 'custom'" x-cloak x-transition>
        <div class="card-body py-2">
            <div class="row g-3 align-items-center">
                <div class="col-auto">
                    <label class="col-form-label fw-bold">From:</label>
                </div>
                <div class="col-auto">
                    <input type="date" class="form-control form-control-sm" x-model="customFrom" :max="customTo">
                </div>
                <div class="col-auto">
                    <label class="col-form-label fw-bold">To:</label>
                </div>
                <div class="col-auto">
                    <input type="date" class="form-control form-control-sm" x-model="customTo" :min="customFrom">
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary btn-sm" @click="fetchData()" :disabled="!customFrom || !customTo || loading">Apply Filter</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Alert -->
    <div class="alert alert-danger" x-show="error" x-cloak x-transition>
        <i class="bi bi-exclamation-triangle me-2"></i> <span x-text="error"></span>
    </div>

    <!-- KPI Section: Core Financials -->
    <div class="row g-3 g-lg-4 mb-4">
        
        <!-- Total Sales -->
        <div class="col-sm-6 col-xl-3">
            <div class="card stats-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="stats-icon bg-success bg-opacity-10 text-success">
                                <i class="bi bi-cart-check"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="h6 mb-0 text-body-secondary">Total Sales</p>
                            <div class="h3 mb-0" x-text="formatCurrency(data.kpis.totalSales)"></div>
                            <small class="text-body-secondary"><span x-text="data.kpis.totalSalesCount"></span> Orders</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Purchases -->
        <div class="col-sm-6 col-xl-3">
            <div class="card stats-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="stats-icon bg-warning bg-opacity-10 text-warning">
                                <i class="bi bi-bag"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="h6 mb-0 text-body-secondary">Total Purchase</p>
                            <div class="h3 mb-0" x-text="formatCurrency(data.kpis.totalPurchase)"></div>
                            <small class="text-body-secondary"><span x-text="data.kpis.totalPurchaseCount"></span> Orders</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inward Payments (Income) -->
        <div class="col-sm-6 col-xl-3">
            <div class="card stats-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="stats-icon bg-info bg-opacity-10 text-info">
                                <i class="bi bi-wallet2"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="h6 mb-0 text-body-secondary">Income (Inward)</p>
                            <div class="h3 mb-0" x-text="formatCurrency(data.kpis.inwardPayments)"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Outward Payments (Expense) -->
        <div class="col-sm-6 col-xl-3">
            <div class="card stats-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="stats-icon bg-danger bg-opacity-10 text-danger">
                                <i class="bi bi-cash-stack"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="h6 mb-0 text-body-secondary">Expense (Outward)</p>
                            <div class="h3 mb-0" x-text="formatCurrency(data.kpis.outwardPayments)"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- KPI Section: Outstanding & Inventory -->
    <div class="row g-3 g-lg-4 mb-4">
        
        <!-- Sales Outstanding -->
        <div class="col-sm-6 col-xl-3">
            <div class="card stats-card h-100 border-start border-4 border-warning">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="stats-icon bg-warning bg-opacity-10 text-warning">
                                <i class="bi bi-exclamation-circle"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="h6 mb-0 text-body-secondary">Sales Outstanding</p>
                            <div class="h3 mb-0 text-warning" x-text="formatCurrency(data.kpis.salesOutstanding)"></div>
                            <small class="text-body-secondary"><span x-text="data.kpis.salesOutstandingCount"></span> Unpaid Invoices</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Purchase Outstanding -->
        <div class="col-sm-6 col-xl-3">
            <div class="card stats-card h-100 border-start border-4 border-danger">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="stats-icon bg-danger bg-opacity-10 text-danger">
                                <i class="bi bi-clock-history"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="h6 mb-0 text-body-secondary">Purchase Outstanding</p>
                            <div class="h3 mb-0 text-danger" x-text="formatCurrency(data.kpis.purchaseOutstanding)"></div>
                            <small class="text-body-secondary">Payables Due</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customers -->
        <div class="col-sm-6 col-xl-3">
            <div class="card stats-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                                <i class="bi bi-people"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="h6 mb-0 text-body-secondary">Customers</p>
                            <div class="h3 mb-0" x-text="data.kpis.newCustomers"></div>
                            <small class="text-success">New</small>
                            <small class="text-body-secondary ms-2">/ <span x-text="data.kpis.existingCustomers"></span> Existing</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inventory Health -->
        <div class="col-sm-6 col-xl-3">
            <div class="card stats-card h-100">
                <div class="card-body">
                    <p class="h6 mb-3 text-body-secondary">Inventory Health</p>
                    <div class="d-flex justify-content-between text-center mt-3">
                        <div>
                            <h4 class="mb-0 text-success" x-text="data.kpis.inStock"></h4>
                            <small class="text-body-secondary">In Stock</small>
                        </div>
                        <div class="border-start px-3">
                            <h4 class="mb-0 text-warning" x-text="data.kpis.lowStock"></h4>
                            <small class="text-body-secondary">Low Stock</small>
                        </div>
                        <div class="border-start ps-3">
                            <h4 class="mb-0 text-danger" x-text="data.kpis.zeroStock"></h4>
                            <small class="text-body-secondary">Out of Stock</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="row g-4 mb-4">
        <!-- Sales vs Purchase Trend -->
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header">
                    <h2 class="h5 card-title mb-0">Sales vs Purchase Trend</h2>
                </div>
                <div class="card-body">
                    <div id="trendChart" style="min-height: 320px;"></div>
                </div>
            </div>
        </div>

        <!-- State-wise Sales -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <h2 class="h5 card-title mb-0">State-wise Sales</h2>
                </div>
                <div class="card-body">
                    <div id="stateWiseChart" style="min-height: 320px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tables Section -->
    <div class="row g-4 mb-4">
        
        <!-- Product Performance -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="h5 card-title mb-0">Product Performance</h2>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-primary" :class="{'active': showBestSelling}" @click="showBestSelling = true">Best Selling</button>
                        <button type="button" class="btn btn-outline-primary" :class="{'active': !showBestSelling}" @click="showBestSelling = false">Least Selling</button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th class="text-end">Qty Sold</th>
                                    <th class="text-end">Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-if="showBestSelling">
                                    <template x-for="item in data.tables.bestSelling" :key="item.id">
                                        <tr>
                                            <td><span class="fw-medium text-body-emphasis" x-text="item.name"></span></td>
                                            <td><small class="text-body-secondary" x-text="item.sku"></small></td>
                                            <td class="text-end" x-text="parseFloat(item.total_qty).toLocaleString()"></td>
                                            <td class="text-end fw-semibold text-success" x-text="formatCurrency(item.total_revenue)"></td>
                                        </tr>
                                    </template>
                                </template>
                                <template x-if="showBestSelling && data.tables.bestSelling.length === 0">
                                    <tr><td colspan="4" class="text-center text-body-secondary py-4">No data available</td></tr>
                                </template>

                                <template x-if="!showBestSelling">
                                    <template x-for="item in data.tables.leastSelling" :key="item.id">
                                        <tr>
                                            <td><span class="fw-medium text-body-emphasis" x-text="item.name"></span></td>
                                            <td><small class="text-body-secondary" x-text="item.sku"></small></td>
                                            <td class="text-end" x-text="parseFloat(item.total_qty).toLocaleString()"></td>
                                            <td class="text-end fw-semibold text-danger" x-text="formatCurrency(item.total_revenue)"></td>
                                        </tr>
                                    </template>
                                </template>
                                <template x-if="!showBestSelling && data.tables.leastSelling.length === 0">
                                    <tr><td colspan="4" class="text-center text-body-secondary py-4">No data available</td></tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Low Stock Alerts -->
        <div class="col-lg-6">
            <div class="card h-100 border-top border-4 border-warning">
                <div class="card-header">
                    <h2 class="h5 card-title mb-0 text-warning"><i class="bi bi-exclamation-triangle me-2"></i> Low Stock Alerts</h2>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                        <table class="table table-hover mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Product</th>
                                    <th>Warehouse</th>
                                    <th class="text-end">Current Qty</th>
                                    <th class="text-end">Min Level</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="item in data.tables.lowStockProducts" :key="item.id + '_' + item.warehouse_id">
                                    <tr>
                                        <td>
                                            <span class="fw-medium text-body-emphasis" x-text="item.product_name"></span><br>
                                            <small class="text-body-secondary" x-text="item.sku"></small>
                                        </td>
                                        <td><span x-text="item.warehouse_name"></span></td>
                                        <td class="text-end">
                                            <span class="badge bg-danger-subtle text-danger" x-show="item.quantity <= 0" x-text="parseFloat(item.quantity).toLocaleString()"></span>
                                            <span class="badge bg-warning-subtle text-warning" x-show="item.quantity > 0" x-text="parseFloat(item.quantity).toLocaleString()"></span>
                                        </td>
                                        <td class="text-end" x-text="parseFloat(item.min_stock_level).toLocaleString()"></td>
                                    </tr>
                                </template>
                                <template x-if="data.tables.lowStockProducts.length === 0">
                                    <tr><td colspan="4" class="text-center text-body-secondary py-4">No low stock items</td></tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
    </div>

    <!-- Parties & Due Invoices Row -->
    <div class="row g-4 mb-4">
        <!-- Top Customers & Vendors -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="h5 card-title mb-0">Top Parties</h2>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-primary" :class="{'active': showTopCustomers}" @click="showTopCustomers = true">Customers</button>
                        <button type="button" class="btn btn-outline-primary" :class="{'active': !showTopCustomers}" @click="showTopCustomers = false">Vendors</button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th class="text-end">Orders</th>
                                    <th class="text-end">Total Volume</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-if="showTopCustomers">
                                    <template x-for="item in data.tables.topCustomers" :key="item.id">
                                        <tr>
                                            <td>
                                                <span class="fw-medium text-body-emphasis" x-text="item.name"></span><br>
                                                <small class="text-body-secondary" x-text="item.phone"></small>
                                            </td>
                                            <td class="text-end" x-text="item.order_count"></td>
                                            <td class="text-end fw-semibold text-primary" x-text="formatCurrency(item.total_value)"></td>
                                        </tr>
                                    </template>
                                </template>
                                <template x-if="showTopCustomers && data.tables.topCustomers.length === 0">
                                    <tr><td colspan="3" class="text-center text-body-secondary py-4">No data available</td></tr>
                                </template>

                                <template x-if="!showTopCustomers">
                                    <template x-for="item in data.tables.topVendors" :key="item.id">
                                        <tr>
                                            <td>
                                                <span class="fw-medium text-body-emphasis" x-text="item.company_name || item.name"></span><br>
                                            </td>
                                            <td class="text-end" x-text="item.po_count"></td>
                                            <td class="text-end fw-semibold text-primary" x-text="formatCurrency(item.total_value)"></td>
                                        </tr>
                                    </template>
                                </template>
                                <template x-if="!showTopCustomers && data.tables.topVendors.length === 0">
                                    <tr><td colspan="3" class="text-center text-body-secondary py-4">No data available</td></tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Unpaid / Due Invoices -->
        <div class="col-lg-6">
            <div class="card h-100 border-top border-4 border-danger">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="h5 card-title mb-0 text-danger"><i class="bi bi-receipt me-2"></i> Unpaid / Due</h2>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-danger" :class="{'active': showSalesDue}" @click="showSalesDue = true">Sales Due</button>
                        <button type="button" class="btn btn-outline-danger" :class="{'active': !showSalesDue}" @click="showSalesDue = false">Purchase Due</button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                        <table class="table table-hover mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Document No</th>
                                    <th>Party</th>
                                    <th class="text-end">Due Date</th>
                                    <th class="text-end">Amount Due</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-if="showSalesDue">
                                    <template x-for="item in data.tables.salesInvoiceDue" :key="item.id">
                                        <tr>
                                            <td>
                                                <span class="fw-medium text-body-emphasis" x-text="item.invoice_no"></span><br>
                                                <small class="badge bg-warning-subtle text-warning" x-text="item.status.toUpperCase()"></small>
                                            </td>
                                            <td><span class="text-body-secondary" x-text="item.customer_name"></span></td>
                                            <td class="text-end text-danger" x-text="item.due_date ? new Date(item.due_date).toLocaleDateString() : 'N/A'"></td>
                                            <td class="text-end fw-semibold text-danger" x-text="formatCurrency(item.net_amount)"></td>
                                        </tr>
                                    </template>
                                </template>
                                <template x-if="showSalesDue && data.tables.salesInvoiceDue.length === 0">
                                    <tr><td colspan="4" class="text-center text-success py-4"><i class="bi bi-check-circle me-1"></i> All sales invoices are paid</td></tr>
                                </template>

                                <template x-if="!showSalesDue">
                                    <template x-for="item in data.tables.purchaseDue" :key="item.id">
                                        <tr>
                                            <td>
                                                <span class="fw-medium text-body-emphasis" x-text="item.po_number"></span><br>
                                                <small class="badge bg-warning-subtle text-warning" x-text="item.status.toUpperCase()"></small>
                                            </td>
                                            <td><span class="text-body-secondary" x-text="item.supplier_name"></span></td>
                                            <td class="text-end text-danger" x-text="item.expected_delivery_date ? new Date(item.expected_delivery_date).toLocaleDateString() : 'N/A'"></td>
                                            <td class="text-end fw-semibold text-danger" x-text="formatCurrency(item.net_amount)"></td>
                                        </tr>
                                    </template>
                                </template>
                                <template x-if="!showSalesDue && data.tables.purchaseDue.length === 0">
                                    <tr><td colspan="4" class="text-center text-success py-4"><i class="bi bi-check-circle me-1"></i> All purchase orders are resolved</td></tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function erpAnalyticsDashboard() {
    return {
        period: 'this_month',
        customFrom: '',
        customTo: '',
        limit: 10,
        loading: false,
        error: null,
        
        showBestSelling: true,
        showTopCustomers: true,
        showSalesDue: true,

        trendChartInstance: null,
        stateChartInstance: null,

        data: {
            kpis: {
                totalSales: 0, totalSalesCount: 0,
                totalPurchase: 0, totalPurchaseCount: 0,
                inwardPayments: 0, outwardPayments: 0,
                salesOutstanding: 0, salesOutstandingCount: 0,
                purchaseOutstanding: 0,
                newCustomers: 0, existingCustomers: 0,
                inStock: 0, lowStock: 0, zeroStock: 0
            },
            charts: {
                salesTrend: [], purchaseTrend: [], stateWiseSales: []
            },
            tables: {
                topCustomers: [], bestSelling: [], leastSelling: [], lowStockProducts: [], topVendors: [], purchaseDue: [], salesInvoiceDue: []
            }
        },

        initDashboard() {
            this.fetchData();
        },

        handlePeriodChange() {
            if (this.period !== 'custom') {
                this.fetchData();
            }
        },

        handleLimitChange() {
            this.fetchData();
        },

        formatCurrency(val) {
            if (val === null || val === undefined || isNaN(val)) return '₹0.00';
            return new Intl.NumberFormat('en-IN', {
                style: 'currency',
                currency: 'INR',
                minimumFractionDigits: 2
            }).format(val);
        },

        getCssVar(name) {
            return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
        },

        async fetchData() {
            this.loading = true;
            this.error = null;
            try {
                let url = `/analytics/data?period=${this.period}&limit=${this.limit}`;
                if (this.period === 'custom') {
                    if (!this.customFrom || !this.customTo) return;
                    url += `&from=${this.customFrom}&to=${this.customTo}`;
                }

                const response = await fetch(url, {
                    headers: { 'Accept': 'application/json' }
                });
                
                if (!response.ok) throw new Error('Network response was not ok');
                const json = await response.json();
                
                if (json.error) {
                    this.error = json.error;
                }
                
                this.data.kpis = { ...this.data.kpis, ...json.kpis };
                this.data.charts = { ...this.data.charts, ...json.charts };
                this.data.tables = { ...this.data.tables, ...json.tables };
                
                this.renderTrendChart();
                this.renderStateChart();

            } catch (err) {
                console.error(err);
                this.error = "Failed to load dashboard data. Please try again.";
            } finally {
                this.loading = false;
            }
        },

        renderTrendChart() {
            const salesData = this.data.charts.salesTrend || [];
            const purchaseData = this.data.charts.purchaseTrend || [];
            
            const dateSet = new Set([
                ...salesData.map(d => d.day),
                ...purchaseData.map(d => d.day)
            ]);
            
            const dates = Array.from(dateSet).sort();
            
            const salesSeries = dates.map(date => {
                const found = salesData.find(d => d.day === date);
                return found ? found.revenue : 0;
            });

            const purchaseSeries = dates.map(date => {
                const found = purchaseData.find(d => d.day === date);
                return found ? found.expense : 0;
            });

            const textColor = this.getCssVar('--bs-body-color') || '#6c757d';
            
            const options = {
                series: [{
                    name: 'Sales Revenue',
                    data: salesSeries
                }, {
                    name: 'Purchase Expense',
                    data: purchaseSeries
                }],
                chart: {
                    type: 'area',
                    height: 320,
                    toolbar: { show: false },
                    fontFamily: 'inherit',
                },
                colors: [this.getCssVar('--bs-success') || '#20c997', this.getCssVar('--bs-warning') || '#ffc107'],
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: 2 },
                xaxis: {
                    categories: dates,
                    labels: { style: { colors: textColor } },
                    tooltip: { enabled: false }
                },
                yaxis: {
                    labels: {
                        style: { colors: textColor },
                        formatter: (val) => "₹" + (val / 1000).toFixed(1) + "k"
                    }
                },
                tooltip: {
                    y: { formatter: (val) => this.formatCurrency(val) }
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.35,
                        opacityTo: 0.05,
                        stops: [0, 90, 100]
                    }
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'right'
                }
            };

            if (this.trendChartInstance) {
                this.trendChartInstance.destroy();
            }

            this.trendChartInstance = new ApexCharts(document.querySelector("#trendChart"), options);
            this.trendChartInstance.render();
        },

        renderStateChart() {
            const stateData = this.data.charts.stateWiseSales || [];
            
            const textColor = this.getCssVar('--bs-body-color') || '#6c757d';
            
            const options = {
                series: [{
                    name: 'Revenue',
                    data: stateData.map(d => d.revenue)
                }],
                chart: {
                    type: 'bar',
                    height: 320,
                    toolbar: { show: false },
                    fontFamily: 'inherit',
                },
                colors: [this.getCssVar('--bs-primary') || '#0d6efd'],
                plotOptions: {
                    bar: {
                        borderRadius: 4,
                        horizontal: true,
                        distributed: true
                    }
                },
                dataLabels: { enabled: false },
                xaxis: {
                    categories: stateData.map(d => d.state || 'Unknown'),
                    labels: {
                        style: { colors: textColor },
                        formatter: (val) => "₹" + (val / 1000).toFixed(0) + "k"
                    }
                },
                yaxis: {
                    labels: { style: { colors: textColor, fontWeight: 500 } }
                },
                tooltip: {
                    y: { formatter: (val) => this.formatCurrency(val) }
                },
                legend: { show: false }
            };

            if (this.stateChartInstance) {
                this.stateChartInstance.destroy();
            }

            this.stateChartInstance = new ApexCharts(document.querySelector("#stateWiseChart"), options);
            this.stateChartInstance.render();
        }
    }
}
</script>
@endpush
