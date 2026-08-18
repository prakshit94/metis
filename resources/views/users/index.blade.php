@extends('layouts.app')

@section('title', '👥 User Management')
@section('page', 'users')

@section('content')
<div class="user-management" x-data="userTable">
    <div x-data="{ showAnalytics: localStorage.getItem('users_show_analytics') !== 'false' }" x-init="$watch('showAnalytics', val => localStorage.setItem('users_show_analytics', val))">
<!-- Page Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4 mb-lg-5 mb-xl-6">
                        <div>
                            <h1 class="h3 mb-0"><i class="bi bi-people-fill text-primary me-2"></i>User Management</h1>
                            <p class="text-muted mb-0">Manage users, roles, and permissions</p>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <!-- Analytics Toggle -->
                            <div class="form-check form-switch m-0 me-2 pe-3 border-end cursor-pointer d-flex align-items-center gap-2">
                                <input class="form-check-input m-0" type="checkbox" role="switch" id="usersAnalyticsToggle" x-model="showAnalytics" style="cursor: pointer; width: 2.5em; height: 1.25em;">
                                <label class="form-check-label small fw-bold text-muted mb-0 ms-1" for="usersAnalyticsToggle" style="cursor: pointer; padding-top: 2px;">Analytics</label>
                            </div>
                            @can('user-import')
                            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#importModal">
                                <i class="bi bi-upload me-2"></i>Import Users
                            </button>
                            @endcan
                            @can('user-export')
                            <button type="button" class="btn btn-outline-secondary" x-on:click="exportUsers()">
                                <i class="bi bi-download me-2"></i>Export
                            </button>
                            @endcan
                            @can('user-create')
                            <button type="button" class="btn btn-primary" @click="openCreateUser()">
                                <i class="bi bi-person-plus me-2"></i>Add User
                            </button>
                            @endcan
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
                        <div x-show="showAnalytics" x-transition.opacity.duration.300ms>
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
                                                    @can('user-create')
                                                    <div class="col-6">
                                                        <button class="btn btn-outline-primary btn-sm w-100" 
                                                                type="button" @click="openCreateUser()">
                                                            <i class="bi bi-person-plus me-1"></i>
                                                            Add User
                                                        </button>
                                                    </div>
                                                    @endcan
                                                    @can('user-import')
                                                    <div class="col-6">
                                                        <button class="btn btn-outline-info btn-sm w-100"
                                                                data-bs-toggle="modal" data-bs-target="#importModal">
                                                            <i class="bi bi-upload me-1"></i>
                                                            Import
                                                        </button>
                                                    </div>
                                                    @endcan
                                                    @can('user-export')
                                                    <div class="col-6">
                                                        <button class="btn btn-outline-success btn-sm w-100"
                                                                @click="exportUsers()">
                                                            <i class="bi bi-download me-1"></i>
                                                            Export
                                                        </button>
                                                    </div>
                                                    @endcan
                                                    @can('user-invite')
                                                    <div class="col-6">
                                                        <button class="btn btn-outline-warning btn-sm w-100"
                                                                @click="sendBulkInvites()">
                                                            <i class="bi bi-envelope me-1"></i>
                                                            Invites
                                                        </button>
                                                    </div>
                                                    @endcan
                                                    @can('user-report')
                                                    <div class="col-12">
                                                        <button class="btn btn-outline-secondary btn-sm w-100"
                                                                @click="generateReport()">
                                                            <i class="bi bi-file-earmark-text me-1"></i>
                                                            Generate Report
                                                        </button>
                                                    </div>
                                                    @endcan
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </div> <!-- End Analytics Wrapper -->

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
                                                <select x-select data-no-search class="form-select form-select-sm" 
                                                    x-model="statusFilter" 
                                                    @change="filterUsers()"
                                                    style="width: 150px;">
                                                <option value="">All Status</option>
                                                <option value="active">Active</option>
                                                <option value="inactive">Inactive</option>
                                                <option value="deleted">Deleted</option>
                                            </select>
                                            
                                            <!-- Role Filter -->
                                            <select x-select data-no-search class="form-select form-select-sm" 
                                                    x-model="roleFilter" 
                                                    @change="filterUsers()"
                                                    style="width: 150px;">
                                                <option value="">All Roles</option>
                                                <template x-for="role in availableRoles" :key="role.id ?? role.name">
                                                    <option :value="role.name" x-text="role.name"></option>
                                                </template>
                                            </select>
                                            
                                            <!-- Page Size -->
                                            <select x-select data-no-search class="form-select form-select-sm"
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
                                            @can('user-activate')
                                            <button class="btn btn-sm btn-success" @click="bulkAction('activate')" x-show="hasSelectedActiveUsers">
                                                <i class="bi bi-check-circle me-1"></i>Activate
                                            </button>
                                            <button class="btn btn-sm btn-warning" @click="bulkAction('deactivate')" x-show="hasSelectedActiveUsers">
                                                <i class="bi bi-x-circle me-1"></i>Deactivate
                                            </button>
                                            @endcan
                                            @can('user-delete')
                                            <button class="btn btn-sm btn-danger" @click="bulkAction('delete')" x-show="hasSelectedActiveUsers">
                                                <i class="bi bi-trash me-1"></i>Delete
                                            </button>
                                            @endcan
                                            @can('user-restore')
                                            <button class="btn btn-sm btn-success" @click="bulkAction('restore')" x-show="hasSelectedDeletedUsers">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i>Restore
                                            </button>
                                            @endcan
                                            @can('user-permanent-delete')
                                            <button class="btn btn-sm btn-danger" @click="bulkAction('force-delete')" x-show="hasSelectedDeletedUsers">
                                                <i class="bi bi-trash3 me-1"></i>Permanent Delete
                                            </button>
                                            @endcan
                                            <button class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center px-2" @click="selectedUsers = []" title="Clear selection">
                                                <i class="bi bi-x-lg" style="margin-left: 7px"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Table -->
                                <div class="table-responsive" style="min-height: 350px;">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 50px;" class="ps-3">
                                                    <input type="checkbox" 
                                                           class="user-select-checkbox"
                                                           @change="toggleAll($event.target.checked)"
                                                           :checked="paginatedUsers.length > 0 && paginatedUsers.every(u => selectedUsers.includes(String(u.id)))">
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
                                                <th scope="col">Location</th>
                                                <th scope="col">Role</th>
                                                <th scope="col">Online</th>
                                                <th scope="col">Status</th>
                                                <th scope="col">Last Login</th>
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
                                                <tr :class="{ 'table-active': selectedUsers.includes(String(user.id)) }">
                                                    <td class="ps-3">
                                                        <input type="checkbox"
                                                               class="user-select-checkbox"
                                                               :value="String(user.id)"
                                                               x-model="selectedUsers">
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <img :src="user.photo || user.avatar" 
                                                                 class="rounded-circle me-2" 
                                                                 width="32" 
                                                                 height="32"
                                                                 :alt="user.name"
                                                                 style="object-fit: cover;">
                                                            <div>
                                                                <div class="fw-medium" x-text="user.name || '—'"></div>
                                                                <small class="text-muted" x-text="user.employee_id ? 'Emp ID: ' + user.employee_id : 'Sys ID: ' + user.id"></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td x-text="user.email"></td>
                                                    <td x-text="user.phone || '—'"></td>
                                                    <td x-text="user.department || '—'"></td>
                                                    <td x-text="user.village_name || user.city || '—'"></td>
                                                    <td>
                                                        <span class="badge" 
                                                              :class="roleBadgeClass(user.role)"
                                                              x-text="user.roleLabel"></span>
                                                    </td>
                                                    <td>
                                                        <span class="badge"
                                                              :class="user.is_online ? 'bg-success' : 'bg-secondary'"
                                                              x-text="user.is_online ? 'Online' : 'Offline'"></span>
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
                                                    <td>
                                                        <div x-text="user.last_login_at"></div>
                                                        <small class="text-muted" x-show="user.last_login_at !== 'Never'">
                                                            <i class="bi" :class="user.device_type === 'Mobile' ? 'bi-phone' : 'bi-laptop'"></i>
                                                            <span x-text="user.device_type"></span>
                                                        </small>
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
                                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                                @can('user-view')
                                                                <li>
                                                                    <a class="dropdown-item" href="#" @click.prevent="viewUser(user)">
                                                                        <i class="bi bi-eye me-2"></i>View Profile
                                                                    </a>
                                                                </li>
                                                                @endcan
                                                                @can('user-edit')
                                                                <li>
                                                                    <a class="dropdown-item" href="#" @click.prevent="editUser(user)" x-show="!user.isDeleted">
                                                                        <i class="bi bi-pencil me-2"></i>Edit
                                                                    </a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item" href="#" @click.prevent="$dispatch('open-change-password-modal', { userId: user.id })" x-show="!user.isDeleted">
                                                                        <i class="bi bi-key me-2"></i>Change Password
                                                                    </a>
                                                                </li>
                                                                @endcan
                                                                @can('user-activate')
                                                                <li>
                                                                    <a class="dropdown-item" href="#" @click.prevent="toggleActive(user)" x-show="!user.isDeleted">
                                                                        <i class="bi me-2" :class="user.is_active ? 'bi-x-circle' : 'bi-check-circle'"></i><span x-text="user.is_active ? 'Deactivate' : 'Activate'"></span>
                                                                    </a>
                                                                </li>
                                                                @endcan
                                                                <li><hr class="dropdown-divider"></li>
                                                                @can('user-delete')
                                                                <li>
                                                                    <a class="dropdown-item text-danger" href="#" @click.prevent="deleteUser(user)" x-show="!user.isDeleted">
                                                                        <i class="bi bi-trash me-2"></i>Delete
                                                                    </a>
                                                                </li>
                                                                @endcan
                                                                @can('user-restore')
                                                                <li>
                                                                    <a class="dropdown-item text-success" href="#" @click.prevent="restoreUser(user)" x-show="user.isDeleted">
                                                                        <i class="bi bi-arrow-counterclockwise me-2"></i>Restore
                                                                    </a>
                                                                </li>
                                                                @endcan
                                                                @can('user-permanent-delete')
                                                                <li>
                                                                    <a class="dropdown-item text-danger" href="#" @click.prevent="forceDeleteUser(user)" x-show="user.isDeleted">
                                                                        <i class="bi bi-trash3 me-2"></i>Permanent Delete
                                                                    </a>
                                                                </li>
                                                                @endcan
                                                            </ul>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </template>
                                            
                                            <!-- Loading State -->
                                            <tr x-show="isLoading" style="display: none;">
                                                <td colspan="12" class="text-center py-5">
                                                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                                    <span class="text-muted">Loading users...</span>
                                                </td>
                                            </tr>
                                            
                                            <!-- Empty State -->
                                            <tr x-show="!isLoading && paginatedUsers.length === 0" style="display: none;">
                                                <td colspan="12" class="text-center py-5 text-muted">
                                                    <i class="bi bi-people fs-1 d-block mb-2"></i>
                                                    <p class="mb-0">No users found matching your criteria.</p>
                                                </td>
                                            </tr>
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
    </div> <!-- End showAnalytics wrapper -->

{{-- ═══════════════════════ Add / Edit User Modal ═══════════════════════════ --}}
<div class="modal fade" id="userModal" aria-labelledby="userModalLabel" :class="{ 'view-mode-active': isViewMode }" x-data="userForm">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="userModalLabel">
                    <span x-text="isViewMode ? 'View Profile' : (editingUserId ? 'Edit User' : 'Add New User')"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body pt-3">
                <form id="userModalForm" @submit.prevent="saveUser()">
                    <fieldset :disabled="isViewMode">
                    <div class="row g-4">
                        <!-- Left Column -->
                        <div class="col-lg-8">
                            <!-- Card 1: Personal Info -->
                            <div class="card border-0 shadow-sm mb-4 bg-body-tertiary">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="bi bi-person-lines-fill"></i>
                                        </div>
                                        <h6 class="card-title mb-0 fw-bold">Personal Information</h6>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label fw-medium text-muted small">First Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control form-control-sm" x-model="form.first_name" placeholder="e.g. Jane" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-medium text-muted small">Middle Name</label>
                                            <input type="text" class="form-control form-control-sm" x-model="form.middle_name" placeholder="e.g. Marie">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-medium text-muted small">Last Name</label>
                                            <input type="text" class="form-control form-control-sm" x-model="form.last_name" placeholder="e.g. Smith">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-medium text-muted small">Date of Birth</label>
                                            <input type="date" class="form-control form-control-sm" x-model="form.date_of_birth">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-medium text-muted small">Gender</label>
                                            <select x-select data-no-search class="form-select form-select-sm" x-model="form.gender">
                                                <option value="">None</option>
                                                <option value="Male">Male</option>
                                                <option value="Female">Female</option>
                                                <option value="Other">Other</option>
                                                <option value="Prefer not to say">Prefer not to say</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-medium text-muted small">Blood Group</label>
                                            <input type="text" class="form-control form-control-sm" x-model="form.blood_group" placeholder="e.g. O+">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Card 2: Contact & Address -->
                            <div class="card border-0 shadow-sm mb-4 bg-body-tertiary" style="z-index: 10;" style="z-index: 10;">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-info bg-opacity-10 text-info rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="bi bi-geo-alt-fill"></i>
                                        </div>
                                        <h6 class="card-title mb-0 fw-bold">Contact & Address</h6>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-medium text-muted small">Email Address <span class="text-danger">*</span></label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-body-secondary"><i class="bi bi-envelope"></i></span>
                                                <input type="email" class="form-control form-control-sm" x-model="form.email" placeholder="jane@example.com" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-medium text-muted small">Phone Number</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-body-secondary"><i class="bi bi-telephone"></i></span>
                                                <input type="tel" class="form-control form-control-sm" x-model="form.phone" maxlength="10" minlength="10" pattern="\d{10}" oninput="this.value = this.value.replace(/[^0-9]/g, '')" placeholder="10-digit number">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-medium text-muted small">Emergency Contact Name</label>
                                            <input type="text" class="form-control form-control-sm" x-model="form.emergency_contact_name" placeholder="Name">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-medium text-muted small">Emergency Contact Phone</label>
                                            <input type="tel" class="form-control form-control-sm" x-model="form.emergency_contact_phone" placeholder="10 digits" maxlength="10" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label fw-medium text-muted small">Address Line 1</label>
                                            <input type="text" name="address_line_1" class="form-control form-control-sm" placeholder="House/Flat No., Street" x-model="form.address_line_1">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-medium text-muted small">Address Line 2</label>
                                            <input type="text" name="address_line_2" class="form-control form-control-sm" placeholder="Landmark, Area" x-model="form.address_line_2">
                                        </div>
                                        
                                        <!-- Village Search -->
                                        <div class="col-12">
                                            <label class="form-label fw-medium text-muted small">Village Search</label>
                                            <div class="position-relative">
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-body-secondary"><i class="bi bi-search"></i></span>
                                                    <input type="text" class="form-control form-control-sm" placeholder="Type 3 letters to search village..." x-model="villageSearchQuery" @input.debounce.300ms="searchVillages()">
                                                </div>
                                                <div class="position-absolute w-100 dropdown-menu show shadow overflow-auto mt-1" style="max-height: 200px; z-index: 1060;" x-show="villageResults.length > 0">
                                                    <template x-for="v in villageResults" :key="v.id">
                                                        <button type="button" class="dropdown-item py-2 px-3 border-bottom text-wrap" @click="selectVillage(v)">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <span class="fw-bold text-primary" x-text="v.village_name"></span>
                                                                <span class="badge bg-secondary" x-text="v.pincode"></span>
                                                            </div>
                                                            <div class="text-muted small">
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
                                        
                                        <!-- Selected Village Details -->
                                        <template x-if="form.village_name">
                                            <div class="col-12">
                                                <div class="card border border-info border-opacity-25 bg-info bg-opacity-10 shadow-sm mt-2">
                                                    <div class="card-body p-3">
                                                        <div class="row g-2 small">
                                                            <div class="col-md-4">
                                                                <div class="fw-bold text-muted text-uppercase" style="font-size: 10px;">Village</div>
                                                                <div class="fw-medium text-body" x-text="form.village_name || '—'"></div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="fw-bold text-muted text-uppercase" style="font-size: 10px;">Post Office</div>
                                                                <div class="fw-medium text-body" x-text="form.post_office || '—'"></div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="fw-bold text-muted text-uppercase" style="font-size: 10px;">Taluka</div>
                                                                <div class="fw-medium text-body" x-text="form.taluka || '—'"></div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="fw-bold text-muted text-uppercase" style="font-size: 10px;">District</div>
                                                                <div class="fw-medium text-body" x-text="form.district || '—'"></div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="fw-bold text-muted text-uppercase" style="font-size: 10px;">State</div>
                                                                <div class="fw-medium text-body" x-text="form.state || '—'"></div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="fw-bold text-muted text-uppercase" style="font-size: 10px;">Pincode</div>
                                                                <div class="fw-bold text-primary" x-text="form.pincode || '—'"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>

                                        <template x-if="!form.village_name">
                                            <div class="col-12">
                                                <div class="row g-3">
                                                    <div class="col-md-4">
                                                        <label class="form-label fw-medium text-muted small">City</label>
                                                        <input type="text" name="city" class="form-control form-control-sm" x-model="form.city">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label fw-medium text-muted small">State</label>
                                                        <input type="text" name="state" class="form-control form-control-sm" x-model="form.state">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label fw-medium text-muted small">Pincode</label>
                                                        <input type="text" name="pincode" class="form-control form-control-sm" x-model="form.pincode">
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <!-- Card 3: Employment Details -->
                            <div class="card border-0 shadow-sm mb-4 bg-body-tertiary">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-success bg-opacity-10 text-success rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="bi bi-briefcase-fill"></i>
                                        </div>
                                        <h6 class="card-title mb-0 fw-bold">Employment Details</h6>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-medium text-muted small">Employee ID</label>
                                            <div class="input-group input-group-sm">
                                                <input type="text" class="form-control form-control-sm" x-model="form.employee_id" placeholder="e.g. EMP-1234">
                                                <button class="btn btn-sm btn-outline-secondary bg-body" type="button" @click="generateEmployeeId" title="Auto Generate">
                                                    <i class="bi bi-magic"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-medium text-muted small">Joining Date</label>
                                            <input type="date" class="form-control form-control-sm" x-model="form.joining_date">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-medium text-muted small">Designation</label>
                                            <input type="text" class="form-control form-control-sm" x-model="form.designation" placeholder="e.g. Senior Developer">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-medium text-muted small">Employment Type</label>
                                            <select x-select data-no-search class="form-select form-select-sm" x-model="form.employment_type">
                                                <option value="Full-time">Full-time</option>
                                                <option value="Part-time">Part-time</option>
                                                <option value="Contract">Contract</option>
                                                <option value="Intern">Intern</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-medium text-muted small">Department</label>
                                            <select x-select data-no-search class="form-select form-select-sm" x-model="form.department_id">
                                                <option value="">None</option>
                                                <template x-for="dept in departments" :key="dept.id">
                                                    <option :value="dept.id" x-text="dept.name"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-medium text-muted small">Manager</label>
                                            <select x-select data-no-search class="form-select form-select-sm" x-model="form.manager_id">
                                                <option value="">None</option>
                                                <template x-for="mgr in managers" :key="mgr.id">
                                                    <option :value="mgr.id" x-text="mgr.name"></option>
                                                </template>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-lg-4">
                            <!-- Card 4: Status & Media -->
                            <div class="card border-0 shadow-sm mb-4 bg-body-tertiary">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-warning bg-opacity-10 text-warning rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="bi bi-image"></i>
                                        </div>
                                        <h6 class="card-title mb-0 fw-bold">Status & Media</h6>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <div class="p-2 border rounded bg-body-secondary">
                                                <div class="form-check form-switch m-0 d-flex align-items-center justify-content-between">
                                                    <label class="form-check-label fw-medium small" for="userActiveSwitch">Active Account</label>
                                                    <input class="form-check-input m-0" type="checkbox" role="switch" x-model="form.is_active" id="userActiveSwitch">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 mt-3">
                                            <label class="form-label fw-medium text-muted small">Profile Photo</label>
                                            <div class="border border-dashed rounded p-3 text-center bg-body-secondary d-flex flex-column align-items-center justify-content-center" style="min-height: 150px; border-style: dashed !important;">
                                                <div class="mb-2">
                                                    <template x-if="form.photo">
                                                        <img :src="form.photo" alt="Preview" class="rounded-circle border shadow-sm" style="width: 80px; height: 80px; object-fit: cover;">
                                                    </template>
                                                    <template x-if="!form.photo">
                                                        <i class="bi bi-cloud-arrow-up fs-2 text-muted"></i>
                                                    </template>
                                                </div>
                                                <input type="file" class="form-control form-control-sm" accept="image/*" @change="handlePhotoUpload($event)">
                                                <small class="text-muted mt-1" style="font-size: 0.7rem;">Click to upload (Max 2MB)</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Card 5: Account Settings -->
                            <div class="card border-0 shadow-sm mb-4 bg-body-tertiary">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-danger bg-opacity-10 text-danger rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="bi bi-shield-lock-fill"></i>
                                        </div>
                                        <h6 class="card-title mb-0 fw-bold">Account Settings</h6>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label fw-medium text-muted small">Primary Role <span class="text-danger">*</span></label>
                                            <select x-select data-no-search class="form-select form-select-sm" x-model="form.role" required :disabled="roles.length === 0">
                                                <option value="" disabled x-show="roles.length === 0">No roles available</option>
                                                <template x-for="r in roles" :key="r.id">
                                                    <option :value="r.name" x-text="r.name"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div class="col-12 mt-3 border-top pt-3">
                                            <label class="form-label fw-medium text-muted small">
                                                Password
                                                <span class="text-danger" x-show="!editingUserId">*</span>
                                            </label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-body-secondary"><i class="bi bi-key"></i></span>
                                                <input type="password" class="form-control form-control-sm" x-model="form.password" :required="!editingUserId" placeholder="Min 8 characters">
                                            </div>
                                            <small class="text-muted mt-1" x-show="editingUserId" style="font-size: 11px;">Leave blank to keep current password.</small>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-medium text-muted small">Confirm Password</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-body-secondary"><i class="bi bi-key-fill"></i></span>
                                                <input type="password" class="form-control form-control-sm" x-model="form.password_confirmation" :required="!editingUserId && form.password.length > 0" placeholder="Repeat password">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                                                    </div>
                    </div>
                    </fieldset>
                </form>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" x-text="isViewMode ? 'Close' : 'Cancel'"></button>
                <button type="submit" form="userModalForm" class="btn btn-primary px-4" :disabled="saving" x-show="!isViewMode">
                    <span x-show="saving" class="spinner-border spinner-border-sm me-1"></span>
                    <span x-text="editingUserId ? 'Save Changes' : 'Create User'"></span>
                </button>
            </div>
        </div>
    </div>
</div>


{{-- ═══════════════════════ Import Users Modal ═══════════════════════════════ --}}
<div class="modal fade" id="importModal" aria-labelledby="importModalLabel">
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

<style>
/* Seamless View Mode Styling */
.view-mode-active .form-control,
.view-mode-active .form-select,
.view-mode-active .input-group-text {
    background-color: transparent !important;
    border-color: transparent !important;
    padding-left: 0 !important;
    padding-right: 0 !important;
    box-shadow: none !important;
    color: var(--bs-body-color) !important;
    font-weight: 500 !important;
}
.view-mode-active select.form-select {
    appearance: none !important;
    -webkit-appearance: none !important;
    background-image: none !important;
}
.view-mode-active .input-group {
    border: none !important;
}
.view-mode-active textarea {
    resize: none !important;
}
.view-mode-active .form-check-input,
.view-mode-active .form-check-label {
    opacity: 0.8 !important;
}
.view-mode-active .form-control:disabled, 
.view-mode-active .form-select:disabled {
    background-color: transparent !important;
}
</style>

@endsection


