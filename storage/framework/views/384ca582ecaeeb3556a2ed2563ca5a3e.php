<?php $__env->startSection('page', 'dashboard'); ?>
<?php $__env->startSection('title', 'Dashboard - Modern Bootstrap Admin'); ?>

<?php $__env->startSection('content'); ?>

            <div class="container-fluid p-4 p-lg-5">
                <!-- Page Header -->
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
                    <div>
                        <h1 class="h3 mb-0">Dashboard</h1>
                        <p class="text-muted mb-0">Welcome back! Here's what's happening.</p>
                    </div>
                    <div class="d-flex gap-2 flex-shrink-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newItemModal" aria-label="New Item">
                            <i class="bi bi-plus-lg me-2" aria-hidden="true"></i>
                            <span class="d-none d-sm-inline">New Item</span>
                        </button>
                        <button type="button" class="btn btn-outline-secondary"
                                data-bs-toggle="tooltip"
                                title="Refresh data">
                            <i class="bi bi-arrow-clockwise icon-hover"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary d-none d-sm-inline-block"
                                data-bs-toggle="tooltip"
                                title="Export data">
                            <i class="bi bi-download icon-hover"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary d-none d-sm-inline-block"
                                data-bs-toggle="tooltip"
                                title="Settings">
                            <i class="bi bi-gear icon-hover"></i>
                        </button>
                    </div>
                </div>

                <!-- Stats Cards with Alpine.js -->
                <div class="row g-3 g-lg-4 mb-4">
                    <div class="col-sm-6 col-xl-3" x-data="statsCounter(12426, 5)">
                        <div class="card stats-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                                            <i class="bi bi-people"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <p class="h6 mb-0 text-muted">Total Users</p>
                                        <div class="h3 mb-0" aria-live="polite" data-stat-value><span x-text="value.toLocaleString()">12,426</span></div>
                                        <small class="text-success-emphasis">
                                            <i class="bi bi-arrow-up"></i> +12.5%
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6 col-xl-3">
                        <div class="card stats-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="stats-icon bg-success bg-opacity-10 text-success">
                                            <i class="bi bi-graph-up"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <p class="h6 mb-0 text-muted">Revenue</p>
                                        <h3 class="mb-0">$54,320</h3>
                                        <small class="text-success-emphasis">
                                            <i class="bi bi-arrow-up"></i> +8.2%
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6 col-xl-3">
                        <div class="card stats-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="stats-icon bg-warning bg-opacity-10 text-warning">
                                            <i class="bi bi-bag-check"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <p class="h6 mb-0 text-muted">Orders</p>
                                        <h3 class="mb-0">1,852</h3>
                                        <small class="text-danger-emphasis">
                                            <i class="bi bi-arrow-down"></i> -2.1%
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6 col-xl-3">
                        <div class="card stats-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="stats-icon bg-info bg-opacity-10 text-info">
                                            <i class="bi bi-clock-history"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <p class="h6 mb-0 text-muted">Avg. Response</p>
                                        <h3 class="mb-0">2.3s</h3>
                                        <small class="text-success-emphasis">
                                            <i class="bi bi-arrow-up"></i> +5.4%
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chart Section -->
                <div class="row g-4 mb-4">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h2 class="h5 card-title mb-0">Revenue Overview</h2>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-primary active" data-chart-period="7d">7D</button>
                                    <button type="button" class="btn btn-outline-primary" data-chart-period="30d">30D</button>
                                    <button type="button" class="btn btn-outline-primary" data-chart-period="90d">90D</button>
                                    <button type="button" class="btn btn-outline-primary" data-chart-period="1y">1Y</button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="revenueChart" style="min-height: 320px;"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h2 class="h5 card-title mb-0">Recent Activity</h2>
                            </div>
                            <div class="card-body">
                                <div class="activity-feed">
                                    <div class="activity-item">
                                        <div class="activity-icon bg-primary bg-opacity-10 text-primary">
                                            <i class="bi bi-person-plus"></i>
                                        </div>
                                        <div class="activity-content">
                                            <p class="mb-1">New user registered</p>
                                            <small class="text-muted">2 minutes ago</small>
                                        </div>
                                    </div>
                                    <div class="activity-item">
                                        <div class="activity-icon bg-success bg-opacity-10 text-success">
                                            <i class="bi bi-bag-check"></i>
                                        </div>
                                        <div class="activity-content">
                                            <p class="mb-1">Order #1234 completed</p>
                                            <small class="text-muted">5 minutes ago</small>
                                        </div>
                                    </div>
                                    <div class="activity-item">
                                        <div class="activity-icon bg-warning bg-opacity-10 text-warning">
                                            <i class="bi bi-exclamation-triangle"></i>
                                        </div>
                                        <div class="activity-content">
                                            <p class="mb-1">Server maintenance scheduled</p>
                                            <small class="text-muted">1 hour ago</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Charts Row -->
                <div class="row g-4 mb-4">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h2 class="h5 card-title mb-0">User Growth (Last 7 Days)</h2>
                            </div>
                            <div class="card-body">
                                <div id="userGrowthChart" style="min-height: 280px;"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h2 class="h5 card-title mb-0">Order Status Distribution</h2>
                            </div>
                            <div class="card-body">
                                <div id="orderStatusChart" style="min-height: 280px;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- New Widgets Row -->
                <div class="row g-4 mb-4">
                    <!-- Recent Orders -->
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h2 class="h5 card-title mb-0">Recent Orders</h2>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Order ID</th>
                                                <th>Customer</th>
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

                    <!-- Storage Status -->
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h2 class="h5 card-title mb-0">Storage Status</h2>
                            </div>
                            <div class="card-body">
                                <div id="storageStatusChart"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sales by Location -->
                <div class="row g-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h2 class="h5 card-title mb-0">Sales by Location</h2>
                            </div>
                            <div class="card-body">
                                <div id="salesByLocationChart" style="min-height: 400px; width: 100%;"></div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        
<?php $__env->stopSection(); ?>

<?php $__env->startSection('modals'); ?>
<!-- Footer -->
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/ubuntu/Bootstrap-Admin-Template/resources/views/pages/dashboard.blade.php ENDPATH**/ ?>