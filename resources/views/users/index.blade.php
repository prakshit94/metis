@extends('layouts.app')

@section('title', 'User Management')
@section('page', 'users')

@section('content')
<div class="user-management" x-data="userTable">
    <div>
<!-- Page Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4 mb-lg-5 mb-xl-6">
                        <div>
                            <h1 class="h3 mb-0"><i class="bi bi-people-fill text-primary me-2"></i>User Management</h1>
                            <p class="text-muted mb-0">Manage users, roles, and permissions</p>
                        </div>
                        <div class="d-flex align-items-center gap-2">

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

                        <!-- Analytics section removed -->
                        <div style="display:none;">
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
                                                <option value="15">15 / page</option>
                                                <option value="20">20 / page</option>
                                                <option value="25">25 / page</option>
                                                <option value="50">50 / page</option>
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
                                            <button class="btn btn-sm btn-success" @click="bulkAction('activate')" x-show="canBulkActivate">
                                                <i class="bi bi-check-circle me-1"></i>Activate
                                            </button>
                                            <button class="btn btn-sm btn-warning" @click="bulkAction('deactivate')" x-show="canBulkDeactivate">
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
                                                    User Identity
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
                                                    Contact Info
                                                    <i class="bi bi-arrow-up" x-show="sortField === 'email' && sortDirection === 'asc'" aria-hidden="true"></i>
                                                    <i class="bi bi-arrow-down" x-show="sortField === 'email' && sortDirection === 'desc'" aria-hidden="true"></i>
                                                </th>
                                                <th scope="col">Employment</th>
                                                <th scope="col">Location</th>
                                                <th scope="col">Status</th>
                                                <th scope="col"
                                                    role="button"
                                                    tabindex="0"
                                                    @click="sortBy('lastActive')"
                                                    @keydown.enter.prevent="sortBy('lastActive')"
                                                    @keydown.space.prevent="sortBy('lastActive')"
                                                    :aria-sort="sortField === 'lastActive' ? (sortDirection === 'asc' ? 'ascending' : 'descending') : 'none'"
                                                    class="sortable">
                                                    Activity
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
                                                            <div class="position-relative me-3">
                                                                <img :src="user.photo || user.avatar || (user.gender === 'Male' ? '/assets/images/default_male.png' : (user.gender === 'Female' ? '/assets/images/default_female.png' : '/assets/images/default_avatar.jpeg'))" 
                                                                     class="rounded-circle shadow-sm" 
                                                                     width="42" 
                                                                     height="42"
                                                                     :alt="user.name"
                                                                     style="object-fit: cover;">
                                                                <span class="position-absolute bottom-0 end-0 border border-2 border-body rounded-circle d-flex align-items-center justify-content-center" 
                                                                      :class="user.is_online ? 'bg-success text-white' : 'bg-secondary text-white'" 
                                                                      style="width: 18px; height: 18px; font-size: 9px; right: -2px !important; bottom: -2px !important;"
                                                                      :title="user.is_online ? 'Online via ' + user.device_type : 'Offline'">
                                                                    <i :class="user.device_type === 'Mobile' ? 'bi bi-phone' : 'bi bi-laptop'"></i>
                                                                </span>
                                                            </div>
                                                            <div>
                                                                <a href="#" class="fw-bold text-decoration-none text-primary d-block mb-1" @click.prevent="viewUser(user)" x-text="user.name || '—'"></a>
                                                                <small class="text-muted bg-body-tertiary px-2 py-1 rounded" style="font-size: 0.75rem;"><i class="bi bi-person-badge me-1"></i><span x-text="user.employee_id ? user.employee_id : 'Sys ID: ' + user.id"></span></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex flex-column gap-1">
                                                            <div class="text-body d-flex align-items-center"><i class="bi bi-envelope text-muted me-2"></i> <span x-text="user.email || '—'"></span></div>
                                                            <div class="text-muted small d-flex align-items-center"><i class="bi bi-telephone text-muted me-2"></i> <span x-text="user.phone || '—'"></span></div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex flex-column gap-2 align-items-start">
                                                            <span class="badge shadow-sm" :class="roleBadgeClass(user.role)" x-text="user.roleLabel"></span>
                                                            <small class="text-muted d-flex align-items-center"><i class="bi bi-diagram-3 me-1"></i> <span x-text="user.department || '—'"></span></small>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center text-body">
                                                            <i class="bi bi-geo-alt text-danger me-2"></i>
                                                            <span x-text="user.village_name || user.city || '—'"></span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge px-3 py-2" 
                                                              :class="{
                                                                  'bg-danger-subtle text-danger-emphasis': user.status === 'deleted',
                                                                  'bg-success-subtle text-success-emphasis': user.status === 'active',
                                                                  'bg-secondary-subtle text-secondary-emphasis': user.status === 'inactive',
                                                              }"
                                                              x-text="user.status"></span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex flex-column gap-1">
                                                            <div class="text-body small d-flex align-items-center" title="Last Active">
                                                                <i class="bi bi-clock-history text-muted me-2"></i> <span x-text="user.lastActive"></span>
                                                            </div>
                                                            <div class="text-muted small d-flex align-items-center" title="Last Login">
                                                                <i class="bi bi-box-arrow-in-right text-muted me-2"></i> 
                                                                <span x-text="user.last_login_at === 'Never' ? 'Never logged in' : user.last_login_at"></span>
                                                                <span x-show="user.last_login_at !== 'Never'" class="ms-1">
                                                                    (<i class="bi" :class="user.device_type === 'Mobile' ? 'bi-phone' : 'bi-laptop'"></i> <span x-text="user.device_type"></span>)
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </td>
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
                                                                @can('user-edit')
                                                                <li>
                                                                    <a class="dropdown-item" href="#" @click.prevent="editUser(user)" x-show="!user.isDeleted">
                                                                        <i class="bi bi-pencil me-2"></i>Edit
                                                                    </a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item" href="#" @click.prevent="impersonateUser(user)" x-show="!user.isDeleted && user.id !== {{ auth()->id() }}">
                                                                        <i class="bi bi-box-arrow-in-right me-2 text-warning"></i> Impersonate
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
            <div class="modal-header border-bottom-0 pb-0" x-show="!isViewMode">
                <h5 class="modal-title fw-bold" id="userModalLabel">
                    <span x-text="editingUserId ? 'Edit User' : 'Add New User'"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body pt-3">
                <!-- REDESIGNED VIEW PROFILE -->
                <div x-show="isViewMode" style="display: none;" class="view-profile-container pb-4">
                    <!-- Profile Header (Simplified for Theme Compatibility) -->
                    <div class="d-flex align-items-start justify-content-between mb-4 pb-4 border-bottom">
                        <div class="d-flex align-items-center gap-4">
                            <div class="position-relative">
                                <img :src="form.photo || (form.gender === 'Male' ? '/assets/images/default_male.png' : (form.gender === 'Female' ? '/assets/images/default_female.png' : '/assets/images/default_avatar.jpeg'))" class="rounded-circle border border-3 shadow-sm bg-body-tertiary" style="width: 110px; height: 110px; object-fit: cover; border-color: var(--bs-border-color) !important;" alt="Profile Picture">
                                <span class="position-absolute bottom-0 end-0 p-2 border border-2 rounded-circle shadow-sm" :class="form.is_active ? 'bg-success' : 'bg-secondary'" style="width: 22px; height: 22px; right: 6px !important; bottom: 6px !important; border-color: var(--bs-body-bg) !important;"></span>
                            </div>
                            <div>
                                <h3 class="mb-1 fw-bold text-body" x-text="`${form.first_name || ''} ${form.middle_name || ''} ${form.last_name || ''}`.trim()"></h3>
                                <div class="text-muted mb-2 d-flex align-items-center gap-2" style="font-size: 0.95rem;">
                                    <span class="fw-medium text-body d-flex align-items-center gap-1"><i class="bi bi-briefcase text-muted"></i> <span x-text="form.designation || 'No Designation'"></span></span>
                                    <span class="text-muted">•</span>
                                    <span class="d-flex align-items-center gap-1"><i class="bi bi-geo-alt text-muted"></i> <span x-text="form.city || form.district || 'Location Unknown'"></span></span>
                                </div>
                                <span class="badge bg-primary-subtle text-primary-emphasis rounded-pill px-3 py-1 fw-medium border border-primary-subtle" x-text="form.role"></span>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    <div class="row g-4 mt-2">
                        <!-- Left Column: Core Identity & Contact -->
                        <div class="col-lg-4">
                            <div class="card border-0 shadow-sm mb-4 bg-body-tertiary">
                                <div class="card-header bg-transparent border-0 pt-4 pb-0">
                                    <h6 class="fw-bold text-uppercase text-muted mb-0" style="letter-spacing: 0.5px; font-size: 0.8rem;"><i class="bi bi-person-badge me-2"></i>Core Identity</h6>
                                </div>
                                <div class="card-body">
                                    <ul class="list-unstyled mb-0">
                                        <li class="d-flex align-items-center mb-4">
                                            <div class="bg-primary-subtle text-primary-emphasis rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <i class="bi bi-hash fs-5"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted d-block fw-medium" style="font-size: 0.75rem;">Employee ID</small>
                                                <span class="fw-semibold text-body" x-text="form.employee_id || '—'"></span>
                                            </div>
                                        </li>
                                        <li class="d-flex align-items-center mb-4">
                                            <div class="bg-info-subtle text-info-emphasis rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <i class="bi bi-envelope-fill fs-5"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted d-block fw-medium" style="font-size: 0.75rem;">Email Address</small>
                                                <a :href="`mailto:${form.email}`" class="fw-semibold text-body text-decoration-none" x-text="form.email || '—'"></a>
                                            </div>
                                        </li>
                                        <li class="d-flex align-items-center mb-4">
                                            <div class="bg-success-subtle text-success-emphasis rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <i class="bi bi-telephone-fill fs-5"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted d-block fw-medium" style="font-size: 0.75rem;">Phone Number</small>
                                                <a :href="`tel:${form.phone}`" class="fw-semibold text-body text-decoration-none" x-text="form.phone || '—'"></a>
                                            </div>
                                        </li>
                                        <li class="d-flex align-items-center">
                                            <div class="bg-warning-subtle text-warning-emphasis rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <i class="bi bi-shield-check fs-5"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted d-block fw-medium" style="font-size: 0.75rem;">Account Status</small>
                                                <span class="badge rounded-pill px-3 py-1 mt-1" :class="form.is_active ? 'bg-success-subtle text-success-emphasis' : 'bg-secondary-subtle text-secondary-emphasis'" x-text="form.is_active ? 'Active' : 'Inactive'"></span>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            
                            <!-- Emergency Contact -->
                            <div class="card border-0 shadow-sm bg-danger-subtle border border-danger-subtle">
                                <div class="card-body p-4">
                                    <h6 class="fw-bold text-danger-emphasis mb-3 d-flex align-items-center gap-2"><i class="bi bi-heart-pulse-fill"></i> Emergency Contact</h6>
                                    <div class="mb-3">
                                        <small class="text-danger-emphasis text-opacity-75 d-block mb-1 fw-medium" style="font-size: 0.75rem;">Contact Name</small>
                                        <div class="fw-semibold text-body-emphasis" x-text="form.emergency_contact_name || 'No contact provided'"></div>
                                    </div>
                                    <div>
                                        <small class="text-danger-emphasis text-opacity-75 d-block mb-1 fw-medium" style="font-size: 0.75rem;">Phone Number</small>
                                        <div class="fw-semibold text-body-emphasis" x-text="form.emergency_contact_phone || '—'"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Details grid -->
                        <div class="col-lg-8">
                            <!-- Personal Details -->
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-header bg-transparent border-bottom pt-4 pb-3 px-4">
                                    <h6 class="fw-bold text-uppercase text-primary-emphasis mb-0" style="letter-spacing: 0.5px; font-size: 0.8rem;"><i class="bi bi-person-vcard me-2"></i>Personal Information</h6>
                                </div>
                                <div class="card-body p-4">
                                    <div class="row g-4">
                                        <div class="col-sm-4">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="bg-body-secondary rounded p-2 text-body-secondary"><i class="bi bi-calendar-event"></i></div>
                                                <div>
                                                    <small class="text-muted text-uppercase d-block fw-semibold mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">Date of Birth</small>
                                                    <span class="fw-semibold text-body" x-text="form.date_of_birth ? new Date(form.date_of_birth).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : '—'"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="bg-body-secondary rounded p-2 text-body-secondary"><i class="bi bi-gender-ambiguous"></i></div>
                                                <div>
                                                    <small class="text-muted text-uppercase d-block fw-semibold mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">Gender</small>
                                                    <span class="fw-semibold text-body" x-text="form.gender || '—'"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="bg-body-secondary rounded p-2 text-danger"><i class="bi bi-droplet-fill"></i></div>
                                                <div>
                                                    <small class="text-muted text-uppercase d-block fw-semibold mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">Blood Group</small>
                                                    <span class="fw-semibold text-body" x-text="form.blood_group || '—'"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Employment Details -->
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-header bg-transparent border-bottom pt-4 pb-3 px-4">
                                    <h6 class="fw-bold text-uppercase text-success-emphasis mb-0" style="letter-spacing: 0.5px; font-size: 0.8rem;"><i class="bi bi-briefcase me-2"></i>Employment Details</h6>
                                </div>
                                <div class="card-body p-4">
                                    <div class="row g-4">
                                        <div class="col-sm-6">
                                            <div class="p-3 bg-body-tertiary rounded-3 h-100 border border-secondary-subtle">
                                                <small class="text-muted text-uppercase d-block fw-semibold mb-2" style="font-size: 0.7rem; letter-spacing: 0.5px;">Department</small>
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="bi bi-diagram-3 text-success"></i>
                                                    <span class="fw-semibold text-body" x-text="departments.find(d => d.id == form.department_id)?.name || '—'"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="p-3 bg-body-tertiary rounded-3 h-100 border border-secondary-subtle">
                                                <small class="text-muted text-uppercase d-block fw-semibold mb-2" style="font-size: 0.7rem; letter-spacing: 0.5px;">Designation</small>
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="bi bi-person-workspace text-success"></i>
                                                    <span class="fw-semibold text-body" x-text="form.designation || '—'"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="p-3 bg-body-tertiary rounded-3 h-100 border border-secondary-subtle">
                                                <small class="text-muted text-uppercase d-block fw-semibold mb-2" style="font-size: 0.7rem; letter-spacing: 0.5px;">Manager</small>
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="bi bi-person-check text-success"></i>
                                                    <span class="fw-semibold text-body" x-text="managers.find(m => m.id == form.manager_id)?.name || '—'"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="p-3 bg-body-tertiary rounded-3 h-100 border border-secondary-subtle">
                                                <small class="text-muted text-uppercase d-block fw-semibold mb-2" style="font-size: 0.7rem; letter-spacing: 0.5px;">Employment Status</small>
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <span class="badge bg-info-subtle text-info-emphasis px-3 py-2" x-text="form.employment_type || '—'"></span>
                                                    <small class="text-muted">Joined <span x-text="form.joining_date ? new Date(form.joining_date).toLocaleString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—'"></span></small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Address Details -->
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-transparent border-bottom pt-4 pb-3 px-4">
                                    <h6 class="fw-bold text-uppercase text-info-emphasis mb-0" style="letter-spacing: 0.5px; font-size: 0.8rem;"><i class="bi bi-geo-alt me-2"></i>Address Details</h6>
                                </div>
                                <div class="card-body p-4">
                                    <div class="row g-4">
                                        <div class="col-sm-6">
                                            <small class="text-muted text-uppercase d-block fw-semibold mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">Address Line 1</small>
                                            <span class="fw-semibold text-body" x-text="form.address_line_1 || '—'"></span>
                                        </div>
                                        <div class="col-sm-6">
                                            <small class="text-muted text-uppercase d-block fw-semibold mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">Address Line 2</small>
                                            <span class="fw-semibold text-body" x-text="form.address_line_2 || '—'"></span>
                                        </div>
                                        <div class="col-sm-4">
                                            <small class="text-muted text-uppercase d-block fw-semibold mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">Village / City</small>
                                            <span class="fw-semibold text-body" x-text="form.village_name || form.city || '—'"></span>
                                        </div>
                                        <div class="col-sm-4">
                                            <small class="text-muted text-uppercase d-block fw-semibold mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">Post Office</small>
                                            <span class="fw-semibold text-body" x-text="form.post_office || '—'"></span>
                                        </div>
                                        <div class="col-sm-4">
                                            <small class="text-muted text-uppercase d-block fw-semibold mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">Taluka</small>
                                            <span class="fw-semibold text-body" x-text="form.taluka || '—'"></span>
                                        </div>
                                        <div class="col-sm-4">
                                            <small class="text-muted text-uppercase d-block fw-semibold mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">District</small>
                                            <span class="fw-semibold text-body" x-text="form.district || '—'"></span>
                                        </div>
                                        <div class="col-sm-4">
                                            <small class="text-muted text-uppercase d-block fw-semibold mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">State</small>
                                            <span class="fw-semibold text-body" x-text="form.state || '—'"></span>
                                        </div>
                                        <div class="col-sm-4">
                                            <small class="text-muted text-uppercase d-block fw-semibold mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">Pincode</small>
                                            <span class="fw-semibold text-body" x-text="form.pincode || '—'"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <form id="userModalForm" @submit.prevent="saveUser()" x-show="!isViewMode" autocomplete="off">
                    <fieldset :disabled="isViewMode">
                    <div class="row g-3">
                        <!-- Left Column -->
                        <div class="col-lg-8">
                            <!-- Card 1: Personal Information -->
                            <div class="card border-0 shadow-sm mb-3 bg-body-tertiary">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
                                        <i class="bi bi-person-lines-fill text-primary fs-5 me-2"></i>
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
                                                <option value="Male">Male</option>
                                                <option value="Female">Female</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-medium text-muted small">Blood Group</label>
                                            <select x-select data-no-search class="form-select form-select-sm" x-model="form.blood_group">
                                                <option value="">Unknown</option>
                                                <option value="A+">A+</option>
                                                <option value="A-">A-</option>
                                                <option value="B+">B+</option>
                                                <option value="B-">B-</option>
                                                <option value="AB+">AB+</option>
                                                <option value="AB-">AB-</option>
                                                <option value="O+">O+</option>
                                                <option value="O-">O-</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Card 2: Contact Information -->
                            <div class="card border-0 shadow-sm mb-3 bg-body-tertiary">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
                                        <i class="bi bi-telephone-fill text-info fs-5 me-2"></i>
                                        <h6 class="card-title mb-0 fw-bold">Contact Information</h6>
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
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Card 3: Address Details -->
                            <div class="card border-0 shadow-sm mb-3 bg-body-tertiary" style="z-index: 10;">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
                                        <i class="bi bi-geo-alt-fill text-success fs-5 me-2"></i>
                                        <h6 class="card-title mb-0 fw-bold">Address Details</h6>
                                    </div>
                                    <div class="row g-3">
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
                                            <div class="col-12 d-none">
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
                            
                            <!-- Card 4: Employment Details -->
                            <div class="card border-0 shadow-sm mb-3 bg-body-tertiary">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
                                        <i class="bi bi-briefcase-fill text-warning fs-5 me-2"></i>
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
                                            <select x-select data-no-search class="form-select form-select-sm" x-model="form.designation">
                                                <option value="">None</option>
                                                <template x-for="desig in designations.filter(d => d.is_active)" :key="desig.id">
                                                    <option :value="desig.name" x-text="desig.name"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-medium text-muted small">Employment Type</label>
                                            <select x-select data-no-search class="form-select form-select-sm" x-model="form.employment_type">
                                                <option value="">None</option>
                                                <template x-for="emp in employmentTypes.filter(e => e.is_active)" :key="emp.id">
                                                    <option :value="emp.name" x-text="emp.name"></option>
                                                </template>
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
                            <!-- Card 5: Profile Photo -->
                            <div class="card border-0 shadow-sm mb-3 bg-body-tertiary">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
                                        <i class="bi bi-image text-secondary fs-5 me-2"></i>
                                        <h6 class="card-title mb-0 fw-bold">Profile Photo</h6>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <div class="border border-dashed rounded p-3 text-center bg-body-secondary d-flex flex-column align-items-center justify-content-center" style="min-height: 150px; border-style: dashed !important;">
                                                <div class="mb-2">
                                                    <template x-if="form.photo">
                                                        <img :src="form.photo" alt="Preview" class="rounded-circle border shadow-sm" style="width: 80px; height: 80px; object-fit: cover;">
                                                    </template>
                                                    <template x-if="!form.photo">
                                                        <img :src="form.gender === 'Male' ? '/assets/images/default_male.png' : (form.gender === 'Female' ? '/assets/images/default_female.png' : '/assets/images/default_avatar.jpeg')" alt="Default Avatar" class="rounded-circle border shadow-sm" style="width: 80px; height: 80px; object-fit: cover; opacity: 0.5;">
                                                    </template>
                                                </div>
                                                <input type="file" class="form-control form-control-sm" accept="image/*" @change="handlePhotoUpload($event)">
                                                <small class="text-muted mt-1" style="font-size: 0.7rem;">Click to upload (Max 2MB)</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Card 6: Account Settings -->
                            <div class="card border-0 shadow-sm mb-3 bg-body-tertiary">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
                                        <i class="bi bi-shield-lock-fill text-danger fs-5 me-2"></i>
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
                                        <div class="col-12 mt-2">
                                            <label class="form-label fw-medium text-muted small">Assigned Team (LOB/State)</label>
                                            <select x-select data-no-search class="form-select form-select-sm" x-model="form.team_id">
                                                <option value="">Global / Master Context</option>
                                                @if(isset($teams))
                                                    @foreach($teams as $team)
                                                        <option value="{{ $team->id }}">{{ $team->name }}</option>
                                                    @endforeach
                                                @endif
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
                                        <div class="col-12 mt-3 border-top pt-3">
                                            <div class="p-2 border rounded bg-body-secondary">
                                                <div class="form-check form-switch m-0 d-flex align-items-center justify-content-between">
                                                    <label class="form-check-label fw-medium small" for="userActiveSwitch">Active Account</label>
                                                    <input class="form-check-input m-0" type="checkbox" role="switch" x-model="form.is_active" id="userActiveSwitch">
                                                </div>
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
            <div class="modal-footer border-top-0 pt-0" x-show="!isViewMode">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="userModalForm" class="btn btn-primary px-4" :disabled="saving">
                    <span x-show="saving" class="spinner-border spinner-border-sm me-1"></span>
                    <span x-text="editingUserId ? 'Save Changes' : 'Create User'"></span>
                </button>
            </div>
        </div>
    </div>
</div>


{{-- ═══════════════════════ Import Users Modal ═══════════════════════════════ --}}
<div class="modal fade" id="importModal" aria-labelledby="importModalLabel">
    <div class="modal-dialog modal-xl" x-data="importForm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">
                    <i class="bi bi-upload me-2"></i>Import Users
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3 d-flex align-items-center justify-content-between bg-body-tertiary border p-3 rounded">
                    <div>
                        <span class="fw-semibold">Import Users</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" @click="downloadTemplate()">
                        <i class="bi bi-download me-1"></i>Download Template
                    </button>
                </div>

                <div class="alert alert-info mb-3">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Expected Columns:</strong>
                    <div x-text="headers.join(', ')" class="small mt-1 text-break"></div>
                    <small class="d-block mt-2 opacity-75">You can download the template above to ensure your columns match exactly.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Select CSV File</label>
                    <input type="file" id="importFileInput" class="form-control" accept=".csv"
                           @change="handleFile($event)">
                </div>

                <template x-if="previewRows.length > 0">
                    <div class="mb-3">
                        <h6 class="fw-semibold mb-2">Data Preview <span class="badge bg-secondary ms-1" x-text="parsedRows.length + ' rows found'"></span></h6>
                        <div class="table-responsive border rounded" style="max-height: 250px; overflow-y: auto;">
                            <table class="table table-sm table-hover mb-0" style="white-space: nowrap;">
                                <thead class="table-secondary">
                                    <tr>
                                        <template x-for="header in parsedHeaders" :key="header">
                                            <th x-text="header" class="text-truncate" style="max-width: 150px;"></th>
                                        </template>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(row, idx) in previewRows" :key="idx">
                                        <tr>
                                            <template x-for="(cell, cIdx) in row" :key="cIdx">
                                                <td x-text="cell" class="text-truncate text-muted" style="max-width: 150px;"></td>
                                            </template>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        <div class="small text-muted mt-1" x-show="parsedRows.length > 3">Showing first 3 rows of <span x-text="parsedRows.length"></span> rows.</div>
                    </div>
                </template>
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
                <button type="button" class="btn btn-primary px-4" @click="importUsers()" :disabled="importing || parsedRows.length === 0">
                    <span x-show="importing" class="spinner-border spinner-border-sm me-1"></span>
                    <span x-text="importing ? 'Processing...' : 'Process Import'"></span>
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


