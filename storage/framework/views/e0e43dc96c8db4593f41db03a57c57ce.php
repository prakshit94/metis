<?php $__env->startSection('title', 'Customer Management'); ?>
<?php $__env->startSection('page', 'customers'); ?>

<?php $__env->startSection('content'); ?>
<div class="customer-management" x-data="customerTable" x-init="init()">
    <!-- Page Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4 pb-3 border-bottom">
        <div>
            <h1 class="h3 fw-bold mb-0">Customer Management</h1>
            <p class="text-muted mb-0">Manage farmers, corporate customers, and their addresses</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="bi bi-upload me-2"></i>Import Customers
            </button>
            <button type="button" class="btn btn-outline-secondary rounded-pill px-3" x-on:click="exportCustomers()">
                <i class="bi bi-download me-2"></i>Export
            </button>
            <button type="button" class="btn btn-primary rounded-pill px-4" @click="$dispatch('open-add-customer-modal')">
                <i class="bi bi-person-plus me-2"></i>Add Customer
            </button>
        </div>
    </div>

    <!-- Customer Stats Widgets -->
    <div class="row g-4 g-lg-5 g-xl-6 mb-5 mb-lg-5 mb-xl-6">
        <div class="col-xl-3 col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 stats-card h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Total Customers</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.total"></span></div>
                            <small class="text-success-emphasis">
                                <i class="bi bi-arrow-up"></i> +14% from last month
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 stats-card h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-success bg-opacity-10 text-success me-3">
                            <i class="bi bi-person-check-fill"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Active Accounts</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.active"></span></div>
                            <small class="text-success-emphasis">
                                <i class="bi bi-arrow-up"></i> +5% from last week
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 stats-card h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-warning bg-opacity-10 text-warning me-3">
                            <i class="bi bi-shield-exclamation"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Blacklisted</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.blacklisted"></span></div>
                            <small class="text-danger-emphasis">
                                Urgent review needed
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 stats-card h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div id="activeCustomerChart" style="min-height: 40px; width: 50px;"></div>
                        <div class="ms-3">
                            <p class="h6 mb-0 text-muted">Kyc Rate</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="`${Math.round(stats.kycPercentage)}%`"></span></div>
                            <small class="text-muted">
                                <i class="bi bi-patch-check"></i> Completed
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Analytics Widgets Row -->
    <div class="row g-4 g-lg-5 g-xl-6 mb-5 mb-lg-5 mb-xl-6">
        <!-- Customer Growth Chart -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="h5 card-title mb-0">Customer Registration Trends</h2>
                    <div class="btn-group btn-group-sm" role="group" aria-label="Registration trend period">
                        <input type="radio" class="btn-check" name="growthPeriod" id="growth7d" autocomplete="off" value="7" x-model="growthPeriod" @change="setGrowthPeriod(7)">
                        <label class="btn btn-outline-secondary" for="growth7d">7D</label>
                        <input type="radio" class="btn-check" name="growthPeriod" id="growth30d" autocomplete="off" value="30" x-model="growthPeriod" @change="setGrowthPeriod(30)">
                        <label class="btn btn-outline-secondary" for="growth30d">30D</label>
                        <input type="radio" class="btn-check" name="growthPeriod" id="growth90d" autocomplete="off" value="90" x-model="growthPeriod" @change="setGrowthPeriod(90)">
                        <label class="btn btn-outline-secondary" for="growth90d">90D</label>
                    </div>
                </div>
                <div class="card-body p-3 p-lg-4">
                    <div id="customerGrowthChart" style="width: 100%; overflow: hidden;"></div>
                </div>
            </div>
        </div>

        <!-- Category & Crop Distribution -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header">
                    <h2 class="h5 card-title mb-0">Customer Profiling</h2>
                </div>
                <div class="card-body p-3 p-lg-4">
                    <!-- Category Distribution -->
                    <div class="mb-4">
                        <h6 class="text-muted mb-3">By Category</h6>
                        <div id="categoryDistributionChart"></div>
                    </div>
                    
                    <!-- Crop Breakdown -->
                    <div>
                        <h6 class="text-muted mb-3">Top Crops Grown</h6>
                        <template x-for="crop in cropStats" :key="crop.name">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small" x-text="crop.name"></span>
                                <div class="d-flex align-items-center">
                                    <div class="progress me-2" style="width: 60px; height: 6px;">
                                        <div class="progress-bar" 
                                             :style="`width: ${crop.percentage}%; background-color: ${crop.color}`"></div>
                                    </div>
                                    <span class="small text-muted" x-text="crop.count"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table Container -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="h5 card-title mb-0">Customers Directory</h2>
                </div>
                <div class="col-auto">
                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                        <!-- Search -->
                        <div class="position-relative">
                            <input type="search" 
                                   class="form-control form-control-sm" 
                                   placeholder="Search customers..."
                                   x-model="searchQuery"
                                   @input.debounce.300ms="searchCustomers()"
                                   style="width: 200px; padding-right: 30px;">
                            <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted"></i>
                        </div>
                        
                        <!-- Status Filter -->
                        <select class="form-select form-select-sm" 
                                x-model="statusFilter" 
                                @change="filterCustomers()"
                                style="width: 150px;">
                            <option value="">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                            <option value="deleted">Deleted</option>
                        </select>
                        
                        <!-- Category Filter -->
                        <select class="form-select form-select-sm" 
                                x-model="categoryFilter" 
                                @change="filterCustomers()"
                                style="width: 150px;">
                            <option value="">All Categories</option>
                            <option value="individual">Individual</option>
                            <option value="business">Business</option>
                        </select>

                        <!-- Items Per Page -->
                        <select class="form-select form-select-sm"
                                x-model.number="itemsPerPage"
                                @change="filterCustomers()"
                                style="width: 120px;">
                            <option value="15">15 / page</option>
                            <option value="30">30 / page</option>
                            <option value="50">50 / page</option>
                            <option value="100">100 / page</option>
                        </select>
                        
                        <button class="btn btn-sm btn-outline-secondary" type="button" @click="resetFilters()">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">

            <!-- Bulk Actions Banner -->
            <div class="bulk-actions-bar p-3 bg-primary bg-opacity-10 border-bottom border-primary border-opacity-25" 
                 x-show="selectedCustomers.length > 0">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle-fill text-primary me-2"></i>
                        <span class="fw-medium text-primary">
                            <span x-text="selectedCustomers.length"></span> customer<span x-show="selectedCustomers.length !== 1">s</span> selected
                        </span>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-success" @click="bulkAction('activate')" x-show="hasSelectedActiveCustomers">
                            <i class="bi bi-check-circle me-1"></i>Activate
                        </button>
                        <button class="btn btn-sm btn-warning" @click="bulkAction('deactivate')" x-show="hasSelectedActiveCustomers">
                            <i class="bi bi-x-circle me-1"></i>Deactivate
                        </button>
                        <button class="btn btn-sm btn-danger" @click="bulkAction('delete')" x-show="hasSelectedActiveCustomers">
                            <i class="bi bi-trash me-1"></i>Delete
                        </button>
                        <button class="btn btn-sm btn-success" @click="bulkAction('restore')" x-show="hasSelectedDeletedCustomers">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Restore
                        </button>
                        <button class="btn btn-sm btn-danger" @click="bulkAction('force-delete')" x-show="hasSelectedDeletedCustomers">
                            <i class="bi bi-trash3 me-1"></i>Permanent Delete
                        </button>
                        <button class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center px-2" @click="selectedCustomers = []" title="Clear selection">
                            <i class="bi bi-x-lg" style="margin-left: 7px"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="table-responsive mt-2">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;" class="border-0 ps-4 rounded-start">
                                <input type="checkbox" 
                                       class="user-select-checkbox" 
                                       :checked="customers.length > 0 && selectedCustomers.length === customers.length"
                                       @change="toggleAll($event.target.checked)">
                            </th>
                            <th @click="sort('name')" style="cursor: pointer;" class="border-0">
                                Name <i class="bi" :class="getSortIcon('name')"></i>
                            </th>
                            <th class="border-0">Code</th>
                            <th class="border-0">Category</th>
                            <th class="border-0">Phone</th>
                            <th class="border-0">Outstanding</th>
                            <th class="border-0">KYC</th>
                            <th class="border-0">Status</th>
                            <th class="border-0">Joined</th>
                            <th style="width: 80px;" class="border-0 pe-4 rounded-end"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Loading State -->
                        <tr x-show="isLoading">
                            <td colspan="10" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status"></div>
                                <p class="mt-2 text-muted mb-0">Loading customers...</p>
                            </td>
                        </tr>

                        <!-- Empty State -->
                        <tr x-show="!isLoading && customers.length === 0">
                            <td colspan="10" class="text-center py-5">
                                <i class="bi bi-people text-muted display-4"></i>
                                <p class="mt-2 fw-semibold mb-1">No customers found</p>
                                <p class="text-muted small mb-0">Try adjusting your filters or search query.</p>
                            </td>
                        </tr>

                        <!-- Data Rows -->
                        <template x-for="c in customers" :key="c.id">
                            <tr class="user-row" :class="{'table-active': selectedCustomers.includes(c.id), 'opacity-50': c.isDeleted}">
                                <td class="ps-4">
                                    <input type="checkbox" 
                                           class="user-select-checkbox" 
                                           :value="c.id" :checked="selectedCustomers.includes(c.id)"
                                           @change="toggleCustomer(c.id)">
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar bg-primary bg-opacity-10 text-primary rounded-circle me-3 d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;" x-text="c.initials"></div>
                                        <div>
                                            <a :href="'/customers/' + c.id" class="fw-semibold text-body text-decoration-none hover-primary" x-text="c.name"></a>
                                            <div class="small text-muted" x-text="c.email || 'No email'"></div>
                                        </div>
                                    </div>
                                </td>
                                <td x-text="c.party_code"></td>
                                <td>
                                    <span class="badge bg-body-secondary text-body-secondary text-capitalize border" x-text="c.category || 'individual'"></span>
                                </td>
                                <td x-text="c.phone || '—'"></td>
                                <td>
                                    <span class="fw-medium text-body" x-text="c.formattedOutstanding"></span>
                                </td>
                                <td>
                                    <span class="badge" 
                                          :class="c.kyc_completed ? 'bg-success bg-opacity-10 text-success' : 'bg-warning bg-opacity-10 text-warning'"
                                          x-text="c.kyc_completed ? 'Verified' : 'Pending'"></span>
                                </td>
                                <td>
                                    <span class="badge"
                                          :class="{
                                              'bg-success': c.status === 'active',
                                              'bg-secondary': c.status === 'inactive',
                                              'bg-danger': c.status === 'suspended' || c.status === 'deleted',
                                          }"
                                          x-text="c.status"></span>
                                </td>
                                <td x-text="c.joinDate"></td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                                type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" :href="'/customers/' + c.id">
                                                    <i class="bi bi-eye me-2"></i>View Profile
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="#" @click.prevent="editCustomer(c)" x-show="!c.isDeleted">
                                                    <i class="bi bi-pencil me-2"></i>Edit Details
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item text-danger" href="#" @click.prevent="deleteCustomer(c)" x-show="!c.isDeleted">
                                                    <i class="bi bi-trash me-2"></i>Delete
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item text-success" href="#" @click.prevent="restoreCustomer(c)" x-show="c.isDeleted">
                                                    <i class="bi bi-arrow-counterclockwise me-2"></i>Restore
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item text-danger" href="#" @click.prevent="forceDeleteCustomer(c)" x-show="c.isDeleted">
                                                    <i class="bi bi-trash3 me-2"></i>Permanent Delete
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
            <div class="d-flex justify-content-between align-items-center p-3 border-top" x-show="customers.length > 0">
                <div class="text-muted small">
                    Showing <span x-text="pageFrom"></span> to <span x-text="pageTo"></span> of <span x-text="totalCustomers"></span> results
                </div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item" :class="{ 'disabled': currentPage === 1 }">
                            <a class="page-link" href="#" @click.prevent="goToPage(currentPage - 1)">Previous</a>
                        </li>
                        <template x-for="(page, index) in visiblePages" :key="`${page}-${index}`">
                            <li class="page-item" :class="{ 'active': page === currentPage }">
                                <a class="page-link" href="#" @click.prevent="goToPage(page)" x-text="page"></a>
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


<div x-data="customerProfile">
    <div class="modal fade" id="viewCustomerModal" tabindex="-1" aria-labelledby="viewCustomerModalLabel">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content shadow-lg border-0 rounded-4">
            <div class="modal-header border-bottom-0 pb-0 pt-4 px-4 px-lg-5">
                <h5 class="modal-title fw-bold" id="viewCustomerModalLabel">
                    <i class="bi bi-person-badge-fill me-2 text-primary"></i>Customer Profile
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-3">
                <!-- Loading State -->
                <div class="text-center py-5" x-show="loading">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">Loading customer details...</p>
                </div>

                <div x-show="!loading && customer">
                    <div class="row g-4 px-3 px-lg-4">
                        <!-- Left Panel: Customer Identity Card -->
                        <div class="col-lg-4">
                            <div class="card border-0 bg-body-tertiary rounded-4 h-100">
                                <div class="card-body p-4">
                                    <!-- Avatar & Name -->
                                    <div class="text-center mb-3">
                                        <div class="avatar bg-primary bg-opacity-10 text-primary rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center fw-bold fs-2" style="width: 80px; height: 80px;" x-text="customer?.initials"></div>
                                        <h5 class="mb-0 fw-bold text-body" x-text="customer?.name"></h5>
                                        <p class="text-muted small mb-2" x-text="customer?.party_code"></p>
                                        <div class="d-flex justify-content-center flex-wrap gap-1 mb-3">
                                            <span class="badge text-capitalize" :class="customer?.category === 'business' ? 'bg-info' : 'bg-primary'" x-text="customer?.category"></span>
                                            <span class="badge" :class="customer?.kyc_completed ? 'bg-success' : 'bg-warning'" x-text="customer?.kyc_completed ? 'KYC Verified' : 'KYC Pending'"></span>
                                            <span class="badge" :class="customer?.is_blacklisted ? 'bg-danger' : (customer?.is_active ? 'bg-success' : 'bg-secondary')" x-text="customer?.is_blacklisted ? 'Blacklisted' : (customer?.is_active ? 'Active' : 'Inactive')"></span>
                                        </div>
                                    </div>
                                    <hr class="my-2">

                                    <!-- Contact Information -->
                                    <div class="mb-1 text-muted small fw-semibold text-uppercase" style="font-size:0.65rem; letter-spacing:.06em;">Contact</div>
                                    <div class="row g-2 mb-3" style="font-size:0.82rem;">
                                        <div class="col-12">
                                            <div class="text-muted" style="font-size:0.7rem;">Phone</div>
                                            <div class="text-body fw-semibold" x-text="customer?.phone || '—'"></div>
                                        </div>
                                        <div class="col-12" x-show="customer?.alternatemobile">
                                            <div class="text-muted" style="font-size:0.7rem;">Alternate Mobile</div>
                                            <div class="text-body" x-text="customer?.alternatemobile"></div>
                                        </div>
                                        <div class="col-12" x-show="customer?.phone_number_2">
                                            <div class="text-muted" style="font-size:0.7rem;">Phone 2</div>
                                            <div class="text-body" x-text="customer?.phone_number_2"></div>
                                        </div>
                                        <div class="col-12" x-show="customer?.relative_mobile || customer?.relative_phone">
                                            <div class="text-muted" style="font-size:0.7rem;">Relative / Emergency</div>
                                            <div class="text-body" x-text="customer?.relative_mobile || customer?.relative_phone"></div>
                                        </div>
                                        <div class="col-12" x-show="customer?.email">
                                            <div class="text-muted" style="font-size:0.7rem;">Email</div>
                                            <div class="text-body text-break" x-text="customer?.email"></div>
                                        </div>
                                    </div>

                                    <!-- Business Information -->
                                    <template x-if="customer?.company_name || customer?.gst_no || customer?.pan_no">
                                        <div>
                                            <hr class="my-2">
                                            <div class="mb-1 text-muted small fw-semibold text-uppercase" style="font-size:0.65rem; letter-spacing:.06em;">Business</div>
                                            <div class="row g-2 mb-3" style="font-size:0.82rem;">
                                                <div class="col-12" x-show="customer?.company_name">
                                                    <div class="text-muted" style="font-size:0.7rem;">Company</div>
                                                    <div class="text-body fw-semibold" x-text="customer?.company_name"></div>
                                                </div>
                                                <div class="col-6" x-show="customer?.gst_no">
                                                    <div class="text-muted" style="font-size:0.7rem;">GST No</div>
                                                    <div class="text-body" x-text="customer?.gst_no"></div>
                                                </div>
                                                <div class="col-6" x-show="customer?.pan_no">
                                                    <div class="text-muted" style="font-size:0.7rem;">PAN No</div>
                                                    <div class="text-body" x-text="customer?.pan_no"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- KYC & Engagement -->
                                    <hr class="my-2">
                                    <div class="mb-1 text-muted small fw-semibold text-uppercase" style="font-size:0.65rem; letter-spacing:.06em;">KYC & Engagement</div>
                                    <div class="row g-2 mb-3" style="font-size:0.82rem;">
                                        <div class="col-6" x-show="customer?.aadhaar_last4">
                                            <div class="text-muted" style="font-size:0.7rem;">Aadhaar (Last 4)</div>
                                            <div class="text-body" x-text="'xxxx-xxxx-' + customer?.aadhaar_last4"></div>
                                        </div>
                                        <div class="col-6" x-show="customer?.kyc_verified_at">
                                            <div class="text-muted" style="font-size:0.7rem;">KYC Verified At</div>
                                            <div class="text-body" x-text="customer?.kyc_verified_at ? new Date(customer.kyc_verified_at).toLocaleDateString('en-IN') : '—'"></div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-muted" style="font-size:0.7rem;">Orders</div>
                                            <div class="text-body fw-bold" x-text="customer?.orders_count ?? '0'"></div>
                                        </div>
                                        <div class="col-6" x-show="customer?.first_purchase_at">
                                            <div class="text-muted" style="font-size:0.7rem;">First Purchase</div>
                                            <div class="text-body" x-text="customer?.first_purchase_at ? new Date(customer.first_purchase_at).toLocaleDateString('en-IN') : '—'"></div>
                                        </div>
                                        <div class="col-6" x-show="customer?.last_purchase_at">
                                            <div class="text-muted" style="font-size:0.7rem;">Last Purchase</div>
                                            <div class="text-body" x-text="customer?.last_purchase_at ? new Date(customer.last_purchase_at).toLocaleDateString('en-IN') : '—'"></div>
                                        </div>
                                        <div class="col-6" x-show="customer?.credit_valid_till">
                                            <div class="text-muted" style="font-size:0.7rem;">Credit Valid Till</div>
                                            <div class="text-body" x-text="customer?.credit_valid_till ? new Date(customer.credit_valid_till).toLocaleDateString('en-IN') : '—'"></div>
                                        </div>
                                    </div>

                                    <!-- Tags -->
                                    <template x-if="customer?.tags && customer.tags.length > 0">
                                        <div>
                                            <hr class="my-2">
                                            <div class="mb-1 text-muted small fw-semibold text-uppercase" style="font-size:0.65rem; letter-spacing:.06em;">Tags</div>
                                            <div class="d-flex flex-wrap gap-1 mb-2">
                                                <template x-for="tag in customer.tags" :key="tag">
                                                    <span class="badge bg-secondary-subtle text-secondary-emphasis" x-text="tag"></span>
                                                </template>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Internal Notes -->
                                    <template x-if="customer?.internal_notes">
                                        <div>
                                            <hr class="my-2">
                                            <div class="mb-1 text-muted small fw-semibold text-uppercase" style="font-size:0.65rem; letter-spacing:.06em;">Internal Notes</div>
                                            <p class="mb-0 bg-body-secondary p-2 rounded text-body-secondary small" x-text="customer?.internal_notes"></p>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Right Panel: Agriculture, Financials, and Addresses -->
                        <div class="col-lg-8">
                            <ul class="nav nav-pills mb-3" id="profileTabs" role="tablist">
                                <li class="nav-item">
                                    <button class="nav-link active" id="details-pill" data-bs-toggle="pill" data-bs-target="#pill-details" type="button" role="tab">
                                        <i class="bi bi-grid-3x3-gap me-1"></i>Overview
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link" id="addresses-pill" data-bs-toggle="pill" data-bs-target="#pill-addresses" type="button" role="tab">
                                        <i class="bi bi-geo-alt me-1"></i>Addresses (<span x-text="customer?.addresses?.length || 0"></span>)
                                    </button>
                                </li>
                            </ul>

                            <div class="tab-content" id="profileTabsContent">
                                <!-- Details Pill -->
                                <div class="tab-pane fade show active" id="pill-details" role="tabpanel">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="border rounded p-3 bg-body-tertiary">
                                                <div class="text-muted small mb-1"><i class="bi bi-flower2 me-2"></i>Land Area & Unit</div>
                                                <div class="fw-bold fs-5 text-body" x-text="customer?.land_area ? `${customer.land_area} ${customer.land_unit || 'acres'}` : '—'"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="border rounded p-3 bg-body-tertiary">
                                                <div class="text-muted small mb-1"><i class="bi bi-wallet2 me-2"></i>Outstanding Balance</div>
                                                <div class="fw-bold fs-5 text-danger" x-text="customer?.formattedOutstanding || 'Rs 0.00'"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="border rounded p-3 bg-body-tertiary">
                                                <div class="text-muted small mb-1"><i class="bi bi-shield-check me-2"></i>Credit Terms</div>
                                                <div class="fw-bold fs-6 text-body" x-text="`Limit: Rs ${customer?.credit_limit || 0} | Days: ${customer?.credit_days || 0}`"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="border rounded p-3 bg-body-tertiary">
                                                <div class="text-muted small mb-2"><i class="bi bi-award me-2"></i>Crops Grown</div>
                                                <div>
                                                    <template x-for="c in customer?.cropsList" :key="c">
                                                        <span class="badge bg-secondary me-1" x-text="c"></span>
                                                    </template>
                                                    <span class="text-muted small" x-show="!customer?.cropsList || customer.cropsList.length === 0">—</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="border rounded p-3 bg-body-tertiary">
                                                <div class="text-muted small mb-2"><i class="bi bi-water me-2"></i>Irrigation Types</div>
                                                <div>
                                                    <template x-for="irr in customer?.irrigationList" :key="irr">
                                                        <span class="badge bg-info bg-opacity-10 text-info me-1" x-text="irr"></span>
                                                    </template>
                                                    <span class="text-muted small" x-show="!customer?.irrigationList || customer.irrigationList.length === 0">—</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Addresses Pill -->
                                <div class="tab-pane fade" id="pill-addresses" role="tabpanel">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="fw-semibold mb-0">Customer Locations</h6>
                                        <button class="btn btn-sm btn-primary" @click="startAddAddress()">
                                            <i class="bi bi-plus me-1"></i>Add Address
                                        </button>
                                    </div>


                                    <!-- Addresses List -->
                                    <template x-if="!customer?.addresses || customer.addresses.length === 0">
                                        <p class="text-muted text-center py-3 bg-body-secondary rounded">No addresses added yet.</p>
                                    </template>

                                    <div class="row g-3">
                                        <template x-for="addr in customer?.addresses" :key="addr.id">
                                            <div class="col-md-6">
                                                <div class="card h-100" :class="addr.is_default ? 'border-primary' : 'border-light-subtle'">
                                                    <div class="card-body p-3">
                                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                                            <div>
                                                                <span class="badge bg-primary me-1" x-text="addr.label || 'Address'"></span>
                                                                <span class="badge bg-success" x-show="addr.is_default">Default</span>
                                                            </div>
                                                            <div class="dropdown">
                                                                <button class="btn btn-sm btn-link p-0 text-secondary" type="button" data-bs-toggle="dropdown">
                                                                    <i class="bi bi-three-dots-vertical"></i>
                                                                </button>
                                                                <ul class="dropdown-menu dropdown-menu-end">
                                                                    <li><a class="dropdown-item" href="#" @click.prevent="startEditAddress(addr)"><i class="bi bi-pencil me-1"></i>Edit</a></li>
                                                                    <li><a class="dropdown-item text-danger" href="#" @click.prevent="deleteAddress(addr)"><i class="bi bi-trash me-1"></i>Delete</a></li>
                                                                </ul>
                                                            </div>
                                                        </div>

                                                        <!-- Address Lines -->
                                                        <p class="mb-1 small text-body fw-semibold" x-text="addr.address_line_1"></p>
                                                        <p class="mb-1 small text-body-secondary" x-show="addr.address_line_2" x-text="addr.address_line_2"></p>

                                                        <!-- Geographic Details -->
                                                        <div class="mt-2 pt-2 border-top border-light-subtle">
                                                            <!-- With village -->
                                                            <template x-if="addr.village">
                                                                <div class="row g-1" style="font-size: 0.78rem;">
                                                                    <div class="col-6" x-show="addr.village.village_name">
                                                                        <div class="text-muted" style="font-size: 0.7rem;">Village</div>
                                                                        <div class="text-body fw-semibold" x-text="addr.village.village_name"></div>
                                                                    </div>
                                                                    <div class="col-6" x-show="addr.village.post_so_name">
                                                                        <div class="text-muted" style="font-size: 0.7rem;">Post Office</div>
                                                                        <div class="text-body" x-text="addr.village.post_so_name"></div>
                                                                    </div>
                                                                    <div class="col-4" x-show="addr.village.taluka_name">
                                                                        <div class="text-muted" style="font-size: 0.7rem;">Taluka</div>
                                                                        <div class="text-body" x-text="addr.village.taluka_name"></div>
                                                                    </div>
                                                                    <div class="col-4" x-show="addr.village.district_name">
                                                                        <div class="text-muted" style="font-size: 0.7rem;">District</div>
                                                                        <div class="text-body" x-text="addr.village.district_name"></div>
                                                                    </div>
                                                                    <div class="col-4" x-show="addr.village.state_name">
                                                                        <div class="text-muted" style="font-size: 0.7rem;">State</div>
                                                                        <div class="text-body" x-text="addr.village.state_name"></div>
                                                                    </div>
                                                                    <div class="col-4">
                                                                        <div class="text-muted" style="font-size: 0.7rem;">Pincode</div>
                                                                        <div class="text-body fw-bold" x-text="addr.village.pincode || addr.pincode || '—'"></div>
                                                                    </div>
                                                                </div>
                                                            </template>
                                                            <!-- Without village (manual entry) -->
                                                            <template x-if="!addr.village">
                                                                <div class="row g-1" style="font-size: 0.78rem;">
                                                                    <div class="col-4" x-show="addr.city">
                                                                        <div class="text-muted" style="font-size: 0.7rem;">City</div>
                                                                        <div class="text-body" x-text="addr.city"></div>
                                                                    </div>
                                                                    <div class="col-4" x-show="addr.state">
                                                                        <div class="text-muted" style="font-size: 0.7rem;">State</div>
                                                                        <div class="text-body" x-text="addr.state"></div>
                                                                    </div>
                                                                    <div class="col-4" x-show="addr.pincode">
                                                                        <div class="text-muted" style="font-size: 0.7rem;">Pincode</div>
                                                                        <div class="text-body fw-bold" x-text="addr.pincode"></div>
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
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-outline-warning" x-show="customer && !customer.isDeleted" @click="toggleActiveStatus()">
                    <i class="bi" :class="customer?.is_active ? 'bi-person-dash' : 'bi-person-check'"></i>
                    <span x-text="customer?.is_active ? 'Deactivate' : 'Activate'"></span>
                </button>
                <button type="button" class="btn btn-primary" x-show="customer && !customer.isDeleted" @click="editFromProfile()">
                    <i class="bi bi-pencil me-1"></i>Edit Profile
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

    <!-- Address Modal -->
    <div class="modal fade" id="addressModal" tabindex="-1" aria-labelledby="addressModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg border-0 rounded-4">
                <form @submit.prevent="saveAddress()">
                    <div class="modal-header border-bottom-0 pb-0 pt-4 px-4">
                        <h5 class="modal-title fw-bold" id="addressModalLabel">
                            <i class="bi bi-geo-alt-fill me-2 text-primary"></i>
                            <span x-text="editingAddressId ? 'Edit Address' : 'Add New Address'"></span>
                        </h5>
                        <button type="button" class="btn-close" @click="cancelAddressForm()"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Address Label</label>
                                <select class="form-select form-select-sm" x-model="addressForm.label" required>
                                    <option value="Home">🏠 Home</option>
                                    <option value="Office">🏢 Office</option>
                                    <option value="Farm">🌾 Farm</option>
                                    <option value="Store">🏪 Store</option>
                                    <option value="Shipping">📦 Shipping</option>
                                    <option value="Other">📍 Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Village Search</label>
                                <div class="position-relative">
                                    <input type="text" class="form-control form-control-sm" placeholder="Type 3 letters to search village..." 
                                           x-model="villageSearchQuery" @input.debounce.300ms="searchVillages()">
                                    <div class="position-absolute w-100 dropdown-menu show shadow overflow-auto" style="max-height: 200px; z-index: 1060;" x-show="villageResults.length > 0">
                                        <template x-for="v in villageResults" :key="v.id">
                                            <button type="button" class="dropdown-item w-100 text-start py-2 px-3 border-bottom border-light-subtle"
                                                    @click="selectVillage(v)">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="fw-bold text-primary" x-text="v.village_name"></span>
                                                    <span class="badge bg-secondary-subtle text-secondary-emphasis" x-text="v.pincode"></span>
                                                </div>
                                                <div class="text-muted small" style="font-size: 0.75rem; line-height: 1.4;">
                                                    <span x-show="v.post_so_name" x-text="'PO: ' + v.post_so_name + ' · '"></span>
                                                    <span x-show="v.taluka_name" x-text="'Taluka: ' + v.taluka_name + ' · '"></span>
                                                    <span x-show="v.district_name" x-text="'District: ' + v.district_name + ' · '"></span>
                                                    <span x-show="v.state_name" x-text="'State: ' + v.state_name"></span>
                                                </div>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Address Line 1</label>
                                <input type="text" class="form-control form-control-sm" x-model="addressForm.address_line_1" placeholder="Plot No, Street, Landmark" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Address Line 2</label>
                                <input type="text" class="form-control form-control-sm" x-model="addressForm.address_line_2" placeholder="Sub-locality, Area (Optional)">
                            </div>

                            <!-- Selected Village Details -->
                            <template x-if="addressForm.village_name">
                                <div class="col-12">
                                    <div class="card bg-light border-0">
                                        <div class="card-body p-3">
                                            <div class="row g-2">
                                                <div class="col-md-4">
                                                    <label class="form-label small text-muted mb-0">Village</label>
                                                    <div class="fw-semibold text-truncate small" x-text="addressForm.village_name || '—'"></div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label small text-muted mb-0">Post Office</label>
                                                    <div class="text-truncate small" x-text="addressForm.post_so_name || '—'"></div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label small text-muted mb-0">Taluka</label>
                                                    <div class="text-truncate small" x-text="addressForm.taluka_name || '—'"></div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label small text-muted mb-0">District</label>
                                                    <div class="text-truncate small" x-text="addressForm.district_name || '—'"></div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label small text-muted mb-0">State</label>
                                                    <div class="text-truncate small" x-text="addressForm.state_name || '—'"></div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label small text-muted mb-0">Pincode</label>
                                                    <div class="fw-bold small" x-text="addressForm.pincode || '—'"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" x-model="addressForm.village_id">
                                    <input type="hidden" x-model="addressForm.city">
                                    <input type="hidden" x-model="addressForm.state">
                                    <input type="hidden" x-model="addressForm.pincode">
                                </div>
                            </template>

                            <!-- Manual Input Fields (no village selected) -->
                            <template x-if="!addressForm.village_name">
                                <div class="col-12">
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <label class="form-label small fw-semibold">City</label>
                                            <input type="text" class="form-control form-control-sm" x-model="addressForm.city" placeholder="City" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-semibold">State</label>
                                            <input type="text" class="form-control form-control-sm" x-model="addressForm.state" placeholder="State" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-semibold">Pincode</label>
                                            <input type="text" class="form-control form-control-sm" x-model="addressForm.pincode" placeholder="Pincode" required>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <div class="col-12">
                                <div class="form-check mt-1">
                                    <input class="form-check-input" type="checkbox" id="defaultAddressCheck" x-model="addressForm.is_default">
                                    <label class="form-check-label small" for="defaultAddressCheck">Set as default address</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <button type="button" class="btn btn-sm btn-secondary" @click="cancelAddressForm()">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-primary" :disabled="savingAddress">
                            <span x-show="savingAddress" class="spinner-border spinner-border-sm me-1"></span>
                            Save Address
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel">
    <div class="modal-dialog modal-dialog-scrollable" x-data="importForm">
        <div class="modal-content">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="importModalLabel">
                    <i class="bi bi-upload me-2"></i>Import Customers
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-3">
                <div class="alert alert-info mb-3">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>CSV Format:</strong> firstname, middlename, lastname, email, phone, category, status, company_name, kyc_completed<br>
                    <small>Example: John, Marie, Doe, john@example.com, 9876543210, individual, active, , false</small>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Select CSV File</label>
                    <input type="file" class="form-control" accept=".csv"
                           @change="handleFile($event)">
                </div>
                <template x-if="result">
                    <div>
                        <div class="alert alert-success" x-show="result.created > 0">
                            <i class="bi bi-check-circle me-2"></i>
                            <span x-text="`${result.created} customer(s) imported successfully.`"></span>
                        </div>
                        <template x-if="result.errors.length > 0">
                            <div class="alert alert-warning">
                                <strong>Errors:</strong>
                                <ul class="mb-0 mt-1">
                                    <template x-for="(e, i) in result.errors" :key="i">
                                        <li x-text="e" class="small"></li>
                                    </template>
                                </ul>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" @click="importCustomers()" :disabled="importing || !file">
                    <span x-show="importing" class="spinner-border spinner-border-sm me-1"></span>
                    <span x-text="importing ? 'Importing…' : 'Import Customers'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<!-- We will load customers.js as an Alpine.js component -->
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/user/metis/resources/views/customers/index.blade.php ENDPATH**/ ?>