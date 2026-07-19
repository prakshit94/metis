<?php $__env->startSection('title', '👥 User Management'); ?>
<?php $__env->startSection('page', 'users'); ?>

<?php $__env->startSection('content'); ?>
<div class="user-management" x-data="userTable">
<!-- Page Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4 mb-lg-5 mb-xl-6">
                        <div>
                            <h1 class="h3 mb-0"><i class="bi bi-people-fill text-primary me-2"></i>User Management</h1>
                            <p class="text-muted mb-0">Manage users, roles, and permissions</p>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#importModal">
                                <i class="bi bi-upload me-2"></i>Import Users
                            </button>
                            <button type="button" class="btn btn-outline-secondary" x-on:click="exportUsers()">
                                <i class="bi bi-download me-2"></i>Export
                            </button>
                            <button type="button" class="btn btn-primary" @click="openCreateUser()">
                                <i class="bi bi-person-plus me-2"></i>Add User
                            </button>
                        </div>
                    </div>

                    <!-- Users Management Container -->
                    <div>
                        
                        <!-- User Stats Widgets -->
                        <div class="row g-4 g-lg-5 g-xl-6 mb-5 mb-lg-5 mb-xl-6">
                            <div class="col-xl-3 col-lg-6">
                                <div class="card stats-card">
                                    <div class="card-body p-3 p-lg-4">
                                        <div class="d-flex align-items-center">
                                            <div class="stats-icon bg-primary bg-opacity-10 text-primary me-3">
                                                <i class="bi bi-people-fill"></i>
                                            </div>
                                            <div>
                                                <p class="h6 mb-0 text-muted">Total Users</p>
                                                <div class="h3 mb-0" aria-live="polite"><span x-text="stats.total"></span></div>
                                                <small class="text-success-emphasis">
                                                    <i class="bi bi-arrow-up"></i> +12% from last month
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-lg-6">
                                <div class="card stats-card">
                                    <div class="card-body p-3 p-lg-4">
                                        <div class="d-flex align-items-center">
                                            <div class="stats-icon bg-success bg-opacity-10 text-success me-3">
                                                <i class="bi bi-person-check-fill"></i>
                                            </div>
                                            <div>
                                                <p class="h6 mb-0 text-muted">Active Users</p>
                                                <div class="h3 mb-0" aria-live="polite"><span x-text="stats.active"></span></div>
                                                <small class="text-success-emphasis">
                                                    <i class="bi bi-arrow-up"></i> +8% from last week
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-lg-6">
                                <div class="card stats-card">
                                    <div class="card-body p-3 p-lg-4">
                                        <div class="d-flex align-items-center">
                                            <div class="stats-icon bg-info bg-opacity-10 text-info me-3">
                                                <i class="bi bi-person-plus-fill"></i>
                                            </div>
                                            <div>
                                                <p class="h6 mb-0 text-muted">New This Month</p>
                                                <div class="h3 mb-0" aria-live="polite"><span x-text="stats.newThisMonth"></span></div>
                                                <small class="text-success-emphasis">
                                                    <i class="bi bi-arrow-up"></i> +15% growth
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-lg-6">
                                <div class="card stats-card">
                                    <div class="card-body p-3 p-lg-4">
                                        <div class="d-flex align-items-center">
                                            <div id="activeUserChart" style="min-height: 40px; width: 50px;"></div>
                                            <div class="ms-3">
                                                <p class="h6 mb-0 text-muted">Active Rate</p>
                                                <div class="h3 mb-0" aria-live="polite"><span x-text="`${Math.round(stats.activePercentage)}%`"></span></div>
                                                <small class="text-muted">
                                                    <i class="bi bi-clock"></i> Last 24h
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Enhanced Analytics Widgets Row -->
                        <div class="row g-4 g-lg-5 g-xl-6 mb-5 mb-lg-5 mb-xl-6">
                            <!-- User Growth Chart -->
                            <div class="col-lg-8">
                                <div class="card h-100">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h2 class="h5 card-title mb-0">User Registration Trends</h2>
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
                                        <div id="userGrowthChart" style="width: 100%; overflow: hidden;"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Role & Department Distribution -->
                            <div class="col-lg-4">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h2 class="h5 card-title mb-0">User Distribution</h2>
                                    </div>
                                    <div class="card-body p-3 p-lg-4">
                                        <!-- Role Distribution -->
                                        <div class="mb-4">
                                            <h6 class="text-muted mb-3">By Role</h6>
                                            <div id="roleDistributionChart"></div>
                                        </div>
                                        
                                        <!-- Department Breakdown -->
                                        <div>
                                            <h6 class="text-muted mb-3">By Department</h6>
                                            <template x-for="dept in departmentStats" :key="dept.name">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span class="small" x-text="dept.name"></span>
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress me-2" style="width: 60px; height: 6px;">
                                                            <div class="progress-bar" 
                                                                 :style="`width: ${dept.percentage}%; background-color: ${dept.color}`"></div>
                                                        </div>
                                                        <span class="small text-muted" x-text="dept.count"></span>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Activity & Alerts Row -->
                        <div class="row g-4 g-lg-5 g-xl-6 mb-5 mb-lg-5 mb-xl-6">
                            <!-- Recent User Activity -->
                            <div class="col-lg-6">
                                <div class="card h-100">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h2 class="h5 card-title mb-0">Recent Activity</h2>
                                        <button class="btn btn-sm btn-outline-secondary" type="button" @click="loadUsers()" :disabled="isLoading" title="Refresh users">
                                            <i class="bi bi-arrow-clockwise"></i>
                                        </button>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="activity-feed" style="max-height: 350px; overflow-y: auto;">
                                            <template x-for="activity in recentActivities" :key="activity.id">
                                                <div class="d-flex p-3 border-bottom">
                                                    <div class="flex-shrink-0 me-3">
                                                        <div class="activity-icon" 
                                                             :class="`bg-${activity.type === 'login' ? 'success' : activity.type === 'logout' ? 'secondary' : activity.type === 'register' ? 'primary' : 'warning'} bg-opacity-10`">
                                                            <i :class="`bi bi-${activity.icon} text-${activity.type === 'login' ? 'success' : activity.type === 'logout' ? 'secondary' : activity.type === 'register' ? 'primary' : 'warning'}`"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <div class="d-flex justify-content-between">
                                                            <p class="mb-1 small">
                                                                <strong x-text="activity.user"></strong> 
                                                                <span x-text="activity.action"></span>
                                                            </p>
                                                            <small class="text-muted" x-text="activity.time"></small>
                                                        </div>
                                                        <small class="text-muted" x-text="activity.details"></small>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- System Alerts & Quick Actions -->
                            <div class="col-lg-6">
                                <div class="row g-4 g-lg-4 h-100">
                                    <!-- System Alerts -->
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <h2 class="h5 card-title mb-0">System Alerts</h2>
                                                <span class="badge bg-danger rounded-pill" x-text="systemAlerts.length"></span>
                                            </div>
                                            <div class="card-body p-0">
                                                <template x-for="alert in systemAlerts.slice(0, 3)" :key="alert.id">
                                                    <div class="alert mb-0 border-0 border-start-0 rounded-0" 
                                                         :class="`alert-${alert.type}`">
                                                        <div class="d-flex justify-content-between align-items-start">
                                                            <div>
                                                                <h6 class="alert-heading mb-1" x-text="alert.title"></h6>
                                                                <p class="mb-0 small" x-text="alert.message"></p>
                                                            </div>
                                                            <small class="text-muted" x-text="alert.time"></small>
                                                        </div>
                                                    </div>
                                                </template>
                                                <template x-if="systemAlerts.length === 0">
                                                    <div class="text-center p-4 text-muted">
                                                        <i class="bi bi-check-circle-fill text-success fs-1"></i>
                                                        <p class="mb-0 mt-2">All systems operational</p>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Quick Actions -->
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="card-header">
                                                <h2 class="h5 card-title mb-0">Quick Actions</h2>
                                            </div>
                                            <div class="card-body p-3 p-lg-4">
                                                <div class="row g-2 g-lg-3">
                                                    <div class="col-6">
                                                        <button class="btn btn-outline-primary btn-sm w-100" 
                                                                type="button" @click="openCreateUser()">
                                                            <i class="bi bi-person-plus me-1"></i>
                                                            Add User
                                                        </button>
                                                    </div>
                                                    <div class="col-6">
                                                        <button class="btn btn-outline-info btn-sm w-100"
                                                                data-bs-toggle="modal" data-bs-target="#importModal">
                                                            <i class="bi bi-upload me-1"></i>
                                                            Import
                                                        </button>
                                                    </div>
                                                    <div class="col-6">
                                                        <button class="btn btn-outline-success btn-sm w-100"
                                                                @click="exportUsers()">
                                                            <i class="bi bi-download me-1"></i>
                                                            Export
                                                        </button>
                                                    </div>
                                                    <div class="col-6">
                                                        <button class="btn btn-outline-warning btn-sm w-100"
                                                                @click="sendBulkInvites()">
                                                            <i class="bi bi-envelope me-1"></i>
                                                            Invites
                                                        </button>
                                                    </div>
                                                    <div class="col-12">
                                                        <button class="btn btn-outline-secondary btn-sm w-100"
                                                                @click="generateReport()">
                                                            <i class="bi bi-file-earmark-text me-1"></i>
                                                            Generate Report
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Users Table -->
                        <div class="card">
                            <div class="card-header">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <h2 class="h5 card-title mb-0">Users Directory</h2>
                                    </div>
                                    <div class="col-auto">
                                                <div class="d-flex flex-wrap gap-2 justify-content-end">
                                            <!-- Search -->
                                            <div class="position-relative">
                                                <input type="search" 
                                                       class="form-control form-control-sm" 
                                                       placeholder="Search users..."
                                                       x-model="searchQuery"
                                                       @input="filterUsers()"
                                                       style="width: 200px;">
                                                <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted"></i>
                                            </div>
                                            
                                            <!-- Status Filter -->
                                                <select class="form-select form-select-sm" 
                                                    x-model="statusFilter" 
                                                    @change="filterUsers()"
                                                    style="width: 150px;">
                                                <option value="">All Status</option>
                                                <option value="active">Active</option>
                                                <option value="inactive">Inactive</option>
                                                <option value="deleted">Deleted</option>
                                            </select>
                                            
                                            <!-- Role Filter -->
                                            <select class="form-select form-select-sm" 
                                                    x-model="roleFilter" 
                                                    @change="filterUsers()"
                                                    style="width: 150px;">
                                                <option value="">All Roles</option>
                                                <template x-for="role in availableRoles" :key="role.id ?? role.name">
                                                    <option :value="role.name" x-text="role.name"></option>
                                                </template>
                                            </select>
                                            
                                            <!-- Page Size -->
                                            <select class="form-select form-select-sm"
                                                    x-model.number="itemsPerPage"
                                                    @change="filterUsers()"
                                                    style="width: 120px;">
                                                <option value="10">10 / page</option>
                                                <option value="25">25 / page</option>
                                                <option value="50">50 / page</option>
                                                <option value="100">100 / page</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <!-- Bulk Actions Bar -->
                                <div class="bulk-actions-bar p-3 bg-primary bg-opacity-10 border-bottom border-primary border-opacity-25"
                                     x-show="selectedUsers.length > 0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-check-circle-fill text-primary me-2"></i>
                                            <span class="fw-medium text-primary">
                                                <span x-text="selectedUsers.length"></span> user<span x-show="selectedUsers.length !== 1">s</span> selected
                                            </span>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-sm btn-success" @click="bulkAction('activate')" x-show="hasSelectedActiveUsers">
                                                <i class="bi bi-check-circle me-1"></i>Activate
                                            </button>
                                            <button class="btn btn-sm btn-warning" @click="bulkAction('deactivate')" x-show="hasSelectedActiveUsers">
                                                <i class="bi bi-x-circle me-1"></i>Deactivate
                                            </button>
                                            <button class="btn btn-sm btn-danger" @click="bulkAction('delete')" x-show="hasSelectedActiveUsers">
                                                <i class="bi bi-trash me-1"></i>Delete
                                            </button>
                                            <button class="btn btn-sm btn-success" @click="bulkAction('restore')" x-show="hasSelectedDeletedUsers">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i>Restore
                                            </button>
                                            <button class="btn btn-sm btn-danger" @click="bulkAction('force-delete')" x-show="hasSelectedDeletedUsers">
                                                <i class="bi bi-trash3 me-1"></i>Permanent Delete
                                            </button>
                                            <button class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center px-2" @click="selectedUsers = []" title="Clear selection">
                                                <i class="bi bi-x-lg" style="margin-left: 7px"></i>
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
                                                           class="user-select-checkbox"
                                                           @change="$event.isTrusted && toggleAll($event.target.checked)"
                                                           :checked="selectedUsers.length === filteredUsers.length && filteredUsers.length > 0">
                                                </th>
                                                <th scope="col"
                                                    role="button"
                                                    tabindex="0"
                                                    @click="sortBy('name')"
                                                    @keydown.enter.prevent="sortBy('name')"
                                                    @keydown.space.prevent="sortBy('name')"
                                                    :aria-sort="sortField === 'name' ? (sortDirection === 'asc' ? 'ascending' : 'descending') : 'none'"
                                                    class="sortable">
                                                    Name
                                                    <i class="bi bi-arrow-up" x-show="sortField === 'name' && sortDirection === 'asc'" aria-hidden="true"></i>
                                                    <i class="bi bi-arrow-down" x-show="sortField === 'name' && sortDirection === 'desc'" aria-hidden="true"></i>
                                                </th>
                                                <th scope="col"
                                                    role="button"
                                                    tabindex="0"
                                                    @click="sortBy('email')"
                                                    @keydown.enter.prevent="sortBy('email')"
                                                    @keydown.space.prevent="sortBy('email')"
                                                    :aria-sort="sortField === 'email' ? (sortDirection === 'asc' ? 'ascending' : 'descending') : 'none'"
                                                    class="sortable">
                                                    Email
                                                    <i class="bi bi-arrow-up" x-show="sortField === 'email' && sortDirection === 'asc'" aria-hidden="true"></i>
                                                    <i class="bi bi-arrow-down" x-show="sortField === 'email' && sortDirection === 'desc'" aria-hidden="true"></i>
                                                </th>
                                                <th scope="col">Phone</th>
                                                <th scope="col">Department</th>
                                                <th scope="col">Role</th>
                                                <th scope="col">Status</th>
                                                <th scope="col"
                                                    role="button"
                                                    tabindex="0"
                                                    @click="sortBy('lastActive')"
                                                    @keydown.enter.prevent="sortBy('lastActive')"
                                                    @keydown.space.prevent="sortBy('lastActive')"
                                                    :aria-sort="sortField === 'lastActive' ? (sortDirection === 'asc' ? 'ascending' : 'descending') : 'none'"
                                                    class="sortable">
                                                    Last Active
                                                    <i class="bi bi-arrow-up" x-show="sortField === 'lastActive' && sortDirection === 'asc'" aria-hidden="true"></i>
                                                    <i class="bi bi-arrow-down" x-show="sortField === 'lastActive' && sortDirection === 'desc'" aria-hidden="true"></i>
                                                </th>
                                                <th style="width: 120px;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="user in paginatedUsers" :key="user.id">
                                                <tr :class="{ 'selected': selectedUsers.includes(user.id) }">
                                                    <td>
                                                        <input type="checkbox"
                                                               class="user-select-checkbox"
                                                               :value="user.id"
                                                               :checked="selectedUsers.includes(user.id)"
                                                               @change="toggleUser(user.id)">
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <img :src="user.avatar" 
                                                                 class="rounded-circle me-2" 
                                                                 width="32" 
                                                                 height="32"
                                                                 :alt="user.name">
                                                            <div>
                                                                <div class="fw-medium" x-text="user.name || '—'"></div>
                                                                <small class="text-muted" x-text="'ID: ' + user.id"></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td x-text="user.email"></td>
                                                    <td x-text="user.phone || '—'"></td>
                                                    <td x-text="user.department || '—'"></td>
                                                    <td>
                                                        <span class="badge" 
                                                              :class="roleBadgeClass(user.role)"
                                                              x-text="user.roleLabel"></span>
                                                    </td>
                                                    <td>
                                                        <span class="badge" 
                                                              :class="{
                                                                  'bg-danger': user.status === 'deleted',
                                                                  'bg-success': user.status === 'active',
                                                                  'bg-secondary': user.status === 'inactive',
                                                              }"
                                                              x-text="user.status"></span>
                                                    </td>
                                                    <td x-text="user.lastActive"></td>
                                                    <td>
                                                        <div class="dropdown">
                                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                                                    type="button"
                                                                    data-bs-toggle="dropdown"
                                                                    aria-expanded="false"
                                                                    title="User actions">
                                                                <i class="bi bi-three-dots"></i>
                                                            </button>
                                                            <ul class="dropdown-menu">
                                                                <li>
                                                                    <a class="dropdown-item" href="#" @click.prevent="viewUser(user)">
                                                                        <i class="bi bi-eye me-2"></i>View Profile
                                                                    </a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item" href="#" @click.prevent="editUser(user)" x-show="!user.isDeleted">
                                                                        <i class="bi bi-pencil me-2"></i>Edit
                                                                    </a>
                                                                </li>
                                                                <li><hr class="dropdown-divider"></li>
                                                                <li>
                                                                    <a class="dropdown-item text-danger" href="#" @click.prevent="deleteUser(user)" x-show="!user.isDeleted">
                                                                        <i class="bi bi-trash me-2"></i>Delete
                                                                    </a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item text-success" href="#" @click.prevent="restoreUser(user)" x-show="user.isDeleted">
                                                                        <i class="bi bi-arrow-counterclockwise me-2"></i>Restore
                                                                    </a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item text-danger" href="#" @click.prevent="forceDeleteUser(user)" x-show="user.isDeleted">
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
                                <div class="d-flex justify-content-between align-items-center p-3">
                                    <div class="text-muted">
                                        Showing <span x-text="pageFrom"></span> to
                                        <span x-text="pageTo"></span> of
                                        <span x-text="totalUsers"></span> results
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
                        
                    </div> <!-- End Users Management Container -->


<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" x-data="userForm">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalLabel">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form @submit.prevent="saveUser()">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" x-model="form.first_name"
                                   placeholder="e.g. Jane" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Middle Name</label>
                            <input type="text" class="form-control" x-model="form.middle_name"
                                   placeholder="e.g. Marie">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Last Name</label>
                            <input type="text" class="form-control" x-model="form.last_name"
                                   placeholder="e.g. Smith">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" x-model="form.email"
                                   placeholder="jane@example.com" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phone</label>
                            <input type="tel" class="form-control" x-model="form.phone"
                                   placeholder="+1 (555) 000-0000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Department</label>
                            <input type="text" class="form-control" x-model="form.department"
                                   placeholder="e.g. Engineering">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
                            <template x-if="rolesLoading">
                                <div class="border rounded p-2 text-muted small">
                                    <span class="spinner-border spinner-border-sm me-2"></span>Loading roles...
                                </div>
                            </template>
                            <template x-if="!rolesLoading && rolesError">
                                <div class="alert alert-warning mb-0 py-2">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    <span x-text="`Unable to load roles: ${rolesError}`"></span>
                                </div>
                            </template>
                            <template x-if="!rolesLoading && !rolesError">
                                <select class="form-select" x-model="form.role" required :disabled="roles.length === 0">
                                    <option value="" disabled x-show="roles.length === 0">No roles available</option>
                                    <template x-for="r in roles" :key="r.id">
                                        <option :value="r.name" x-text="r.name"></option>
                                    </template>
                                </select>
                            </template>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="userActiveSwitch"
                                       x-model="form.is_active">
                                <label class="form-check-label" for="userActiveSwitch">
                                    <span x-text="form.is_active ? 'Active' : 'Inactive'"></span>
                                </label>
                            </div>
                        </div>
                        <div class="col-12"><hr class="my-1"></div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                Password
                                <span class="text-danger" x-show="!editingUserId">*</span>
                                <small class="text-muted fw-normal" x-show="editingUserId">(leave blank to keep current)</small>
                            </label>
                            <input type="password" class="form-control" x-model="form.password"
                                   :required="!editingUserId"
                                   placeholder="Min 8 chars, mixed case + number"
                                   autocomplete="new-password">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Confirm Password</label>
                            <input type="password" class="form-control" x-model="form.password_confirmation"
                                   :required="!editingUserId && form.password.length > 0"
                                   placeholder="Repeat password"
                                   autocomplete="new-password">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" :disabled="saving">
                            <span x-show="saving" class="spinner-border spinner-border-sm me-1"></span>
                            <span x-text="editingUserId ? 'Save Changes' : 'Create User'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel">
    <div class="modal-dialog" x-data="importForm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">
                    <i class="bi bi-upload me-2"></i>Import Users
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mb-3">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>CSV Format:</strong> first_name, middle_name, last_name, email, role, status, phone, department<br>
                    <small>Example: Jane, Marie, Smith, jane@example.com, Admin, active, +1 555 000 0000, Engineering</small>
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
                            <span x-text="`${result.created} user(s) imported successfully.`"></span>
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
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" @click="importUsers()" :disabled="importing || !file">
                    <span x-show="importing" class="spinner-border spinner-border-sm me-1"></span>
                    <span x-text="importing ? 'Importing…' : 'Import Users'"></span>
                </button>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="viewUserModal" tabindex="-1" aria-labelledby="viewUserModalLabel">
    <div class="modal-dialog modal-lg" x-data="userProfile">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewUserModalLabel">
                    <i class="bi bi-person-circle me-2"></i>User Profile
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Loading skeleton -->
                <template x-if="loading">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted">Loading profile…</p>
                    </div>
                </template>

                <template x-if="!loading && user">
                    <div>
                        <!-- Header row -->
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <img :src="user.avatar" class="rounded-circle" width="72" height="72" :alt="user.name">
                            <div>
                                <h5 class="mb-0 fw-bold" x-text="user.name"></h5>
                                <p class="text-muted mb-1" x-text="user.email"></p>
                                <span class="badge"
                                      :class="{'bg-success':user.status==='active','bg-secondary':user.status==='inactive','bg-danger':user.status==='deleted'}"
                                      x-text="user.status"></span>
                                <span class="badge ms-1"
                                      :class="user.roleClass"
                                      x-text="user.roleLabel"></span>
                            </div>
                        </div>

                        <!-- Details grid -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <div class="card bg-body-tertiary border-0 h-100">
                                    <div class="card-body">
                                        <p class="small text-muted mb-1">First Name</p>
                                        <p class="mb-0 fw-medium" x-text="user.first_name || '—'"></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-body-tertiary border-0 h-100">
                                    <div class="card-body">
                                        <p class="small text-muted mb-1">Middle Name</p>
                                        <p class="mb-0 fw-medium" x-text="user.middle_name || '—'"></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-body-tertiary border-0 h-100">
                                    <div class="card-body">
                                        <p class="small text-muted mb-1">Last Name</p>
                                        <p class="mb-0 fw-medium" x-text="user.last_name || '—'"></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-body-tertiary border-0 h-100">
                                    <div class="card-body">
                                        <p class="small text-muted mb-1">Email</p>
                                        <p class="mb-0 fw-medium" x-text="user.email || '—'"></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-body-tertiary border-0 h-100">
                                    <div class="card-body">
                                        <p class="small text-muted mb-1">Phone</p>
                                        <p class="mb-0 fw-medium" x-text="user.phone || '—'"></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-body-tertiary border-0 h-100">
                                    <div class="card-body">
                                        <p class="small text-muted mb-1">Department</p>
                                        <p class="mb-0 fw-medium" x-text="user.department || '—'"></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-body-tertiary border-0 h-100">
                                    <div class="card-body">
                                        <p class="small text-muted mb-1">Joined</p>
                                        <p class="mb-0 fw-medium" x-text="user.joinDate"></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-body-tertiary border-0 h-100">
                                    <div class="card-body">
                                        <p class="small text-muted mb-1">Last Active</p>
                                        <p class="mb-0 fw-medium" x-text="user.lastActive"></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Login history -->
                        <h6 class="fw-semibold mb-2">Recent Login History</h6>
                        <template x-if="loginHistory.length === 0">
                            <p class="text-muted small">No login history available.</p>
                        </template>
                        <div class="table-responsive" x-show="loginHistory.length > 0">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date / Time</th>
                                        <th>Status</th>
                                        <th>IP Address</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(h, i) in loginHistory.slice(0,10)" :key="i">
                                        <tr>
                                            <td class="small" x-text="h.attempted_at ? new Date(h.attempted_at).toLocaleString() : '—'"></td>
                                            <td>
                                                <span class="badge"
                                                      :class="h.status === 'success' ? 'bg-success' : 'bg-danger'"
                                                      x-text="h.status"></span>
                                            </td>
                                            <td class="small font-monospace" x-text="h.ip_address ?? '—'"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-warning" x-show="user && !user.isDeleted" @click="toggleActive()" :disabled="saving">
                    <i class="bi" :class="user?.is_active ? 'bi-person-dash' : 'bi-person-check'"></i>
                    <span x-text="user?.is_active ? 'Deactivate' : 'Activate'"></span>
                </button>
                <button type="button" class="btn btn-primary" x-show="user && !user.isDeleted" @click="editFromProfile()">
                    <i class="bi bi-pencil me-1"></i>Edit User
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/user/metis/resources/views/users/index.blade.php ENDPATH**/ ?>