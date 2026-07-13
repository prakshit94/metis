<?php $__env->startSection('title', 'Reports & Analytics'); ?>
<?php $__env->startSection('page', 'reports'); ?>

<?php $__env->startSection('content'); ?>
<!-- Page Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4 mb-lg-5">
                        <div>
                            <h1 class="h3 mb-0">Reports & Analytics</h1>
                            <p class="text-muted mb-0">Generate insights and export business data</p>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary" @click="scheduleReport()">
                                <i class="bi bi-calendar-plus me-2"></i>Schedule
                            </button>
                            <button type="button" class="btn btn-outline-secondary" @click="exportData()">
                                <i class="bi bi-download me-2"></i>Export
                            </button>
                            <button type="button" class="btn btn-primary" @click="generateReport()">
                                <i class="bi bi-plus-lg me-2"></i>New Report
                            </button>
                        </div>
                    </div>

                    <!-- Reports Management Container -->
                    <div class="reports-page" x-data="reportsComponent" x-init="init()">
                        
                        <!-- Report Filters -->
                        <div class="report-filter">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label fw-medium">Date Range</label>
                                    <select class="form-select" x-model="dateRange" @change="updateDateRange()">
                                        <option value="7d">Last 7 days</option>
                                        <option value="30d">Last 30 days</option>
                                        <option value="90d">Last 90 days</option>
                                        <option value="1y">Last year</option>
                                        <option value="custom">Custom range</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-medium">Report Type</label>
                                    <select class="form-select" x-model="reportType" @change="updateReportType()">
                                        <option value="overview">Business Overview</option>
                                        <option value="sales">Sales Analytics</option>
                                        <option value="inventory">Inventory Report</option>
                                        <option value="customers">Customer Insights</option>
                                        <option value="financial">Financial Summary</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-medium">Format</label>
                                    <select class="form-select" x-model="exportFormat">
                                        <option value="pdf">PDF Report</option>
                                        <option value="excel">Excel Spreadsheet</option>
                                        <option value="csv">CSV Data</option>
                                        <option value="json">JSON Data</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button class="btn btn-primary w-100" @click="applyFilters()">
                                        <i class="bi bi-funnel me-2"></i>Apply Filters
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- KPI Overview -->
                        <div class="kpi-grid">
                            <div class="card stats-card">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <h6 class="text-muted mb-2">Total Revenue</h6>
                                            <div class="metric-value" x-text="`$${kpis.revenue.toLocaleString()}`"></div>
                                            <div class="metric-change positive">
                                                <i class="bi bi-arrow-up"></i>
                                                <span x-text="`+${kpis.revenueChange}%`"></span> vs last period
                                            </div>
                                        </div>
                                        <div class="stats-icon bg-success bg-opacity-10 text-success">
                                            <i class="bi bi-currency-dollar"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card stats-card">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <h6 class="text-muted mb-2">Orders</h6>
                                            <div class="metric-value" x-text="kpis.orders.toLocaleString()"></div>
                                            <div class="metric-change positive">
                                                <i class="bi bi-arrow-up"></i>
                                                <span x-text="`+${kpis.ordersChange}%`"></span> vs last period
                                            </div>
                                        </div>
                                        <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                                            <i class="bi bi-bag-check"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card stats-card">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <h6 class="text-muted mb-2">Customers</h6>
                                            <div class="metric-value" x-text="kpis.customers.toLocaleString()"></div>
                                            <div class="metric-change positive">
                                                <i class="bi bi-arrow-up"></i>
                                                <span x-text="`+${kpis.customersChange}%`"></span> vs last period
                                            </div>
                                        </div>
                                        <div class="stats-icon bg-info bg-opacity-10 text-info">
                                            <i class="bi bi-people"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card stats-card">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <h6 class="text-muted mb-2">Conversion Rate</h6>
                                            <div class="metric-value" x-text="`${kpis.conversionRate}%`"></div>
                                            <div class="metric-change neutral">
                                                <i class="bi bi-dash"></i>
                                                <span x-text="`${kpis.conversionChange}%`"></span> vs last period
                                            </div>
                                        </div>
                                        <div class="stats-icon bg-warning bg-opacity-10 text-warning">
                                            <i class="bi bi-graph-up-arrow"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Charts Section -->
                        <div class="row g-4 g-lg-5 mb-5">
                            <!-- Revenue Trends -->
                            <div class="col-lg-8">
                                <div class="card h-100">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h2 class="h5 card-title mb-0">Revenue Trends</h2>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <input type="radio" class="btn-check" name="revenuePeriod" id="revenue7d" autocomplete="off" checked>
                                            <label class="btn btn-outline-secondary" for="revenue7d">7D</label>
                                            <input type="radio" class="btn-check" name="revenuePeriod" id="revenue30d" autocomplete="off">
                                            <label class="btn btn-outline-secondary" for="revenue30d">30D</label>
                                            <input type="radio" class="btn-check" name="revenuePeriod" id="revenue90d" autocomplete="off">
                                            <label class="btn btn-outline-secondary" for="revenue90d">90D</label>
                                        </div>
                                    </div>
                                    <div class="card-body p-3 p-lg-4">
                                        <div id="revenueTrendsChart" class="chart-container"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Top Products -->
                            <div class="col-lg-4">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h2 class="h5 card-title mb-0">Top Products</h2>
                                    </div>
                                    <div class="card-body p-3 p-lg-4">
                                        <div id="topProductsChart" style="height: 200px;"></div>
                                        <div class="mt-3">
                                            <template x-for="product in topProducts" :key="product.name">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span class="small" x-text="product.name"></span>
                                                    <div class="d-flex align-items-center">
                                                        <span class="small text-muted me-2" x-text="`$${product.revenue}k`"></span>
                                                        <span class="small fw-medium" x-text="product.units"></span>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Analytics -->
                        <div class="row g-4 g-lg-5 mb-5">
                            <!-- Customer Acquisition -->
                            <div class="col-lg-6">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h2 class="h5 card-title mb-0">Customer Acquisition</h2>
                                    </div>
                                    <div class="card-body p-3 p-lg-4">
                                        <div id="customerAcquisitionChart" style="height: 250px;"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sales by Region -->
                            <div class="col-lg-6">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h2 class="h5 card-title mb-0">Sales by Region</h2>
                                    </div>
                                    <div class="card-body p-3 p-lg-4">
                                        <div id="regionSalesChart" style="height: 250px;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Reports -->
                        <div class="card">
                            <div class="card-header">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <h2 class="h5 card-title mb-0">Recent Reports</h2>
                                    </div>
                                    <div class="col-auto">
                                        <button class="btn btn-sm btn-outline-primary" @click="refreshReports()">
                                            <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Report Name</th>
                                                <th>Type</th>
                                                <th>Date Range</th>
                                                <th>Generated</th>
                                                <th>Status</th>
                                                <th style="width: 120px;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="report in recentReports" :key="report.id">
                                                <tr>
                                                    <td>
                                                        <div class="fw-medium" x-text="report.name"></div>
                                                        <small class="text-muted" x-text="`ID: ${report.id}`"></small>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-light text-dark" x-text="report.type"></span>
                                                    </td>
                                                    <td x-text="report.dateRange"></td>
                                                    <td x-text="report.generated"></td>
                                                    <td>
                                                        <span class="badge" 
                                                              :class="{
                                                                  'bg-success': report.status === 'ready',
                                                                  'bg-warning': report.status === 'generating',
                                                                  'bg-danger': report.status === 'failed'
                                                              }"
                                                              x-text="report.status"></span>
                                                    </td>
                                                    <td>
                                                        <div class="dropdown">
                                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                                    type="button" 
                                                                    data-bs-toggle="dropdown">
                                                                <i class="bi bi-three-dots"></i>
                                                            </button>
                                                            <ul class="dropdown-menu">
                                                                <li><a class="dropdown-item" href="#" @click="downloadReport(report)">
                                                                    <i class="bi bi-download me-2"></i>Download
                                                                </a></li>
                                                                <li><a class="dropdown-item" href="#" @click="shareReport(report)">
                                                                    <i class="bi bi-share me-2"></i>Share
                                                                </a></li>
                                                                <li><a class="dropdown-item" href="#" @click="duplicateReport(report)">
                                                                    <i class="bi bi-copy me-2"></i>Duplicate
                                                                </a></li>
                                                                <li><hr class="dropdown-divider"></li>
                                                                <li><a class="dropdown-item text-danger" href="#" @click="deleteReport(report)">
                                                                    <i class="bi bi-trash me-2"></i>Delete
                                                                </a></li>
                                                            </ul>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                    </div> <!-- End Reports Management Container -->
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script type="module" src="./scripts/components/reports.js"></script>

<script type="module" src="./scripts/main.js"></script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/ubuntu/metis/resources/views/reports.blade.php ENDPATH**/ ?>