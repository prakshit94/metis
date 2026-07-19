<?php $__env->startSection('title', 'Dashboard'); ?>
<?php $__env->startSection('page', 'dashboard'); ?>

<?php $__env->startSection('content'); ?>
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
                <div class="row g-4 g-lg-5 g-xl-6 mb-5 mb-lg-5 mb-xl-6">
                    <div class="col-xl-3 col-lg-6" x-data="statsCounter(<?php echo e($totalCustomers); ?>, 5)">
                        <div class="card metric-card visitors">
                            <div class="card-body p-3 p-lg-4">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <h6 class="text-muted mb-1">Total Customers</h6>
                                        <div class="h3 mb-0" aria-live="polite"><span x-text="value.toLocaleString()"><?php echo e(number_format($totalCustomers)); ?></span></div>
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

                    <div class="col-xl-3 col-lg-6" x-data="statsCounter(<?php echo e($totalRevenue); ?>, 5)">
                        <div class="card metric-card revenue">
                            <div class="card-body p-3 p-lg-4">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <h6 class="text-muted mb-1">Total Revenue</h6>
                                        <div class="h3 mb-0" aria-live="polite"><span x-text="'Rs ' + value.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})">Rs <?php echo e(number_format($totalRevenue, 2)); ?></span></div>
                                        <small class="trend-up">
                                            <i class="bi bi-arrow-up"></i> +8.2% from last month
                                        </small>
                                    </div>
                                    <div class="stats-icon bg-success bg-opacity-10 text-success">
                                        <i class="bi bi-currency-dollar"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-lg-6" x-data="statsCounter(<?php echo e($totalOrders); ?>, 5)">
                        <div class="card metric-card conversion">
                            <div class="card-body p-3 p-lg-4">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <h6 class="text-muted mb-1">Total Orders</h6>
                                        <div class="h3 mb-0" aria-live="polite"><span x-text="value.toLocaleString()"><?php echo e(number_format($totalOrders)); ?></span></div>
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

                    <div class="col-xl-3 col-lg-6" x-data="statsCounter(<?php echo e($totalProducts); ?>, 5)">
                        <div class="card metric-card bounce">
                            <div class="card-body p-3 p-lg-4">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <h6 class="text-muted mb-1">Total Products</h6>
                                        <div class="h3 mb-0" aria-live="polite"><span x-text="value.toLocaleString()"><?php echo e(number_format($totalProducts)); ?></span></div>
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
                </div>

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
                <div class="row g-4 g-lg-5 g-xl-6 mb-5 mb-lg-5 mb-xl-6">
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
                    window.dashboardData = <?php echo json_encode($dashboardData, 15, 512) ?>;
                </script>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('modals'); ?>
<div class="modal fade" id="iconDemoModal" tabindex="-1">
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

<div class="modal fade" id="newItemModal" tabindex="-1" aria-labelledby="newItemModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title" id="newItemModalLabel">
                        <i class="bi bi-plus-circle text-primary me-2"></i>
                        Quick Add
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" x-data="quickAddForm()">
                    <p class="text-muted small mb-4">Create a new item quickly from the dashboard.</p>

                    <!-- Item Type Selection -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">What would you like to add?</label>
                        <div class="btn-group w-100" role="group">
                            <button type="button" class="btn btn-outline-primary btn-sm"
                                    :class="{ 'active': itemType === 'task' }"
                                    @click="itemType = 'task'">
                                <i class="bi bi-check2-square"></i> Task
                            </button>
                            <button type="button" class="btn btn-outline-success btn-sm"
                                    :class="{ 'active': itemType === 'note' }"
                                    @click="itemType = 'note'">
                                <i class="bi bi-sticky"></i> Note
                            </button>
                            <button type="button" class="btn btn-outline-info btn-sm"
                                    :class="{ 'active': itemType === 'event' }"
                                    @click="itemType = 'event'">
                                <i class="bi bi-calendar-event"></i> Event
                            </button>
                            <button type="button" class="btn btn-outline-warning btn-sm"
                                    :class="{ 'active': itemType === 'reminder' }"
                                    @click="itemType = 'reminder'">
                                <i class="bi bi-bell"></i> Reminder
                            </button>
                        </div>
                    </div>

                    <!-- Title -->
                    <div class="mb-3">
                        <label for="itemTitle" class="form-label fw-semibold">Title</label>
                        <input type="text" class="form-control" id="itemTitle" x-model="title"
                               placeholder="Enter a title..." autofocus>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label for="itemDescription" class="form-label fw-semibold">Description</label>
                        <textarea class="form-control" id="itemDescription" rows="3" x-model="description"
                                  placeholder="Add some details..."></textarea>
                    </div>

                    <!-- Priority (shown for tasks) -->
                    <div class="mb-3" x-show="itemType === 'task'" x-transition>
                        <label class="form-label fw-semibold d-block">Priority</label>
                        <div class="btn-group" role="group" aria-label="Priority selection">
                            <input type="radio" class="btn-check" name="priorityRadio" id="priorityLow" value="low" x-model="priority" autocomplete="off">
                            <label class="btn btn-outline-success btn-sm" for="priorityLow">
                                <i class="bi bi-flag"></i> Low
                            </label>
                            <input type="radio" class="btn-check" name="priorityRadio" id="priorityMedium" value="medium" x-model="priority" autocomplete="off">
                            <label class="btn btn-outline-warning btn-sm" for="priorityMedium">
                                <i class="bi bi-flag-fill"></i> Medium
                            </label>
                            <input type="radio" class="btn-check" name="priorityRadio" id="priorityHigh" value="high" x-model="priority" autocomplete="off">
                            <label class="btn btn-outline-danger btn-sm" for="priorityHigh">
                                <i class="bi bi-flag-fill"></i> High
                            </label>
                        </div>
                    </div>

                    <!-- Date (shown for events/reminders) -->
                    <div class="mb-3" x-show="itemType === 'event' || itemType === 'reminder'" x-transition>
                        <label for="itemDate" class="form-label fw-semibold">Date & Time</label>
                        <input type="datetime-local" class="form-control" id="itemDate" x-model="dateTime">
                    </div>

                    <!-- Assign to (shown for tasks) -->
                    <div class="mb-3" x-show="itemType === 'task'" x-transition>
                        <label for="assignTo" class="form-label fw-semibold">Assign to</label>
                        <select class="form-select" id="assignTo" x-model="assignee">
                            <option value="">Select team member...</option>
                            <option value="john">John Doe</option>
                            <option value="jane">Jane Smith</option>
                            <option value="mike">Mike Johnson</option>
                            <option value="sarah">Sarah Williams</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" @click="saveItem()" data-bs-dismiss="modal">
                        <i class="bi bi-check-lg me-1"></i> Create Item
                    </button>
                </div>
            </div>
        </div>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/user/metis/resources/views/dashboard.blade.php ENDPATH**/ ?>