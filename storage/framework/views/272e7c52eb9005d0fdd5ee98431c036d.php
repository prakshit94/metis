<?php $__env->startSection('title', 'Dashboard'); ?>
<?php $__env->startSection('page', 'dashboard'); ?>

<?php $__env->startSection('content'); ?>
<div x-data="{ activeTab: 'search' }">
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

    </div>

    <!-- Tab 1: Customer Search -->
    <div x-show="activeTab === 'search'" x-transition.opacity.duration.300ms>
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mt-4 bg-body-tertiary">
            <div class="card-body p-4 p-md-5 text-center">
                <div class="mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle mb-3" style="width: 80px; height: 80px;">
                        <i class="bi bi-person-bounding-box fs-1"></i>
                    </div>
                    <h2 class="h3 fw-bold text-body mb-2">Find a Customer</h2>
                    <p class="text-muted">Search by mobile number, customer ID, or name to quickly access records.</p>
                </div>
                <div class="mx-auto position-relative" style="max-width: 650px;" x-data="customerSearchApp()">
                    <div class="input-group input-group-lg shadow-sm rounded-pill overflow-hidden bg-body border">
                        <select class="form-select border-0 bg-transparent fw-semibold text-body shadow-none px-4" style="max-width: 200px; cursor: pointer; border-right: 1px solid var(--bs-border-color) !important;">
                            <option value="mobile">Mobile Number</option>
                            <option value="customer_id">Customer ID</option>
                            <option value="name">Name</option>
                        </select>
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
                                        <i class="bi bi-currency-rupee"></i>
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
                                
                                fetch(`/customers/search-by-phone?phone=${this.searchPhone}`)
                                    .then(res => res.json())
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
                                        this.errorMsg = 'Error searching customer. Please try again.';
                                        setTimeout(() => { this.errorMsg = ''; }, 3000);
                                    });
                            }
                        };
                    };
                </script>
    </div> <!-- End Tab 2 -->
</div> <!-- End x-data -->
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


<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/user/metis/resources/views/dashboard.blade.php ENDPATH**/ ?>