@extends('layouts.app')

@section('title', 'Roles & Permissions')
@section('page', 'roles-permissions')

@section('content')
<div class="user-management" x-data="rolesPermissionsTable">
    <div class="d-flex justify-content-between align-items-center mb-4 mb-lg-5 mb-xl-6">
        <div>
            <h1 class="h3 mb-0">Roles & Permissions</h1>
            <p class="text-muted mb-0">Manage access roles, permissions, and assignments</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#accessImportModal">
                <i class="bi bi-upload me-2"></i>Import
            </button>
            <button type="button" class="btn btn-outline-secondary" @click="exportItems()">
                <i class="bi bi-download me-2"></i>Export
            </button>
            <button type="button" class="btn btn-primary" @click="openCreate()">
                <i class="bi bi-plus-circle me-2"></i><span x-text="activeTab === 'roles' ? 'Add Role' : 'Add Permission'"></span>
            </button>
        </div>
    </div>

    <div class="row g-4 g-lg-5 g-xl-6 mb-5 mb-lg-5 mb-xl-6">
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bi bi-shield-lock-fill"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Total Roles</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="roleStats.total"></span></div>
                            <small class="text-muted"><i class="bi bi-key"></i> Access groups</small>
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
                            <i class="bi bi-check2-square"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Permissions</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="permissionStats.total"></span></div>
                            <small class="text-muted"><i class="bi bi-list-check"></i> Capability rules</small>
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
                            <i class="bi bi-link-45deg"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Assignments</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="assignmentCount"></span></div>
                            <small class="text-muted"><i class="bi bi-shuffle"></i> Role permissions</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-warning bg-opacity-10 text-warning me-3">
                            <i class="bi bi-archive"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Deleted</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="deletedCount"></span></div>
                            <small class="text-muted"><i class="bi bi-arrow-counterclockwise"></i> Restorable items</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 g-lg-5 g-xl-6 mb-5 mb-lg-5 mb-xl-6">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="h5 card-title mb-0">Permission Coverage</h2>
                    <div class="btn-group btn-group-sm" role="group" aria-label="Access trend period">
                        <input type="radio" class="btn-check" name="accessPeriod" id="access7d" autocomplete="off" value="7" x-model="activityPeriod" @change="setActivityPeriod(7)">
                        <label class="btn btn-outline-secondary" for="access7d">7D</label>
                        <input type="radio" class="btn-check" name="accessPeriod" id="access30d" autocomplete="off" value="30" x-model="activityPeriod" @change="setActivityPeriod(30)">
                        <label class="btn btn-outline-secondary" for="access30d">30D</label>
                        <input type="radio" class="btn-check" name="accessPeriod" id="access90d" autocomplete="off" value="90" x-model="activityPeriod" @change="setActivityPeriod(90)">
                        <label class="btn btn-outline-secondary" for="access90d">90D</label>
                    </div>
                </div>
                <div class="card-body p-3 p-lg-4">
                    <div id="accessCoverageChart" style="width: 100%; overflow: hidden;"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <h2 class="h5 card-title mb-0">Access Distribution</h2>
                </div>
                <div class="card-body p-3 p-lg-4">
                    <div class="mb-4">
                        <h6 class="text-muted mb-3">By Guard</h6>
                        <div id="guardDistributionChart"></div>
                    </div>
                    <div>
                        <h6 class="text-muted mb-3">Top Roles</h6>
                        <template x-for="role in topRoles" :key="role.id">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small" x-text="role.name"></span>
                                <span class="small text-muted" x-text="`${role.permissions_count || 0} permissions`"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 g-lg-5 g-xl-6 mb-5 mb-lg-5 mb-xl-6">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="h5 card-title mb-0">Recent Activity</h2>
                    <button class="btn btn-sm btn-outline-secondary" type="button" @click="loadCurrent()" :disabled="isLoading" title="Refresh">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="activity-feed" style="max-height: 350px; overflow-y: auto;">
                        <template x-for="activity in recentActivities" :key="activity.id">
                            <div class="d-flex p-3 border-bottom">
                                <div class="flex-shrink-0 me-3">
                                    <div class="activity-icon" :class="`bg-${activity.type === 'deleted' ? 'danger' : 'primary'} bg-opacity-10`">
                                        <i :class="`bi bi-${activity.icon} text-${activity.type === 'deleted' ? 'danger' : 'primary'}`"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between">
                                        <p class="mb-1 small">
                                            <strong x-text="activity.name"></strong>
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
        <div class="col-lg-6">
            <div class="row g-4 g-lg-4 h-100">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h2 class="h5 card-title mb-0">System Alerts</h2>
                            <span class="badge bg-danger rounded-pill" x-text="systemAlerts.length"></span>
                        </div>
                        <div class="card-body p-0">
                            <template x-for="alert in systemAlerts.slice(0, 3)" :key="alert.id">
                                <div class="alert mb-0 border-0 rounded-0" :class="`alert-${alert.type}`">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="alert-heading mb-1" x-text="alert.title"></h6>
                                            <p class="mb-0 small" x-text="alert.message"></p>
                                        </div>
                                        <small class="text-muted" x-text="alert.time"></small>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card">
                        <div class="card-header"><h2 class="h5 card-title mb-0">Quick Actions</h2></div>
                        <div class="card-body">
                            <div class="row g-2 g-lg-3">
                                <div class="col-6">
                                    <button class="btn btn-outline-primary btn-sm w-100" type="button" @click="activeTab = 'roles'; openCreate()">
                                        <i class="bi bi-shield-plus me-1"></i>Add Role
                                    </button>
                                </div>
                                <div class="col-6">
                                    <button class="btn btn-outline-success btn-sm w-100" type="button" @click="activeTab = 'permissions'; openCreate()">
                                        <i class="bi bi-plus-square me-1"></i>Add Permission
                                    </button>
                                </div>
                                <div class="col-6">
                                    <button class="btn btn-outline-info btn-sm w-100" type="button" @click="exportItems()">
                                        <i class="bi bi-download me-1"></i>Export
                                    </button>
                                </div>
                                <div class="col-6">
                                    <button class="btn btn-outline-secondary btn-sm w-100" type="button" @click="loadCurrent()">
                                        <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="row align-items-center g-3">
                <div class="col">
                    <h2 class="h5 card-title mb-0">Access Directory</h2>
                </div>
                <div class="col-auto">
                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                        <div class="btn-group btn-group-sm" role="group" aria-label="Directory type">
                            <input type="radio" class="btn-check" name="accessTab" id="rolesTab" value="roles" x-model="activeTab" @change="switchTab('roles')">
                            <label class="btn btn-outline-secondary" for="rolesTab">Roles</label>
                            <input type="radio" class="btn-check" name="accessTab" id="permissionsTab" value="permissions" x-model="activeTab" @change="switchTab('permissions')">
                            <label class="btn btn-outline-secondary" for="permissionsTab">Permissions</label>
                        </div>
                        <div class="position-relative">
                            <input type="search" class="form-control form-control-sm" placeholder="Search access..." x-model="searchQuery" @input="filterItems()" style="width: 200px;">
                            <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted"></i>
                        </div>
                        <select class="form-select form-select-sm" x-model="statusFilter" @change="filterItems()" style="width: 150px;">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="deleted">Deleted</option>
                        </select>
                        <select class="form-select form-select-sm" x-model="guardFilter" @change="filterItems()" style="width: 130px;">
                            <option value="">All Guards</option>
                            <option value="web">web</option>
                            <option value="api">api</option>
                        </select>
                        <select class="form-select form-select-sm" x-model.number="itemsPerPage" @change="filterItems()" style="width: 120px;">
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
            <div class="bulk-actions-bar p-3 bg-primary bg-opacity-10 border-bottom border-primary border-opacity-25" x-show="selectedItems.length > 0">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle-fill text-primary me-2"></i>
                        <span class="fw-medium text-primary"><span x-text="selectedItems.length"></span> item<span x-show="selectedItems.length !== 1">s</span> selected</span>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-danger" @click="bulkAction('delete')" x-show="hasSelectedActiveItems">
                            <i class="bi bi-trash me-1"></i>Delete
                        </button>
                        <button class="btn btn-sm btn-success" @click="bulkAction('restore')" x-show="hasSelectedDeletedItems">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Restore
                        </button>
                        <button class="btn btn-sm btn-danger" @click="bulkAction('force-delete')" x-show="hasSelectedDeletedItems">
                            <i class="bi bi-trash3 me-1"></i>Permanent Delete
                        </button>
                        <button class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center px-2" @click="selectedItems = []" title="Clear selection">
                            <i class="bi bi-x-lg" style="margin-left: 7px"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;">
                                <div class="form-check m-0">
                                    <input class="form-check-input" type="checkbox" @change="$event.isTrusted && toggleAll($event.target.checked)" :checked="selectedItems.length === currentItems.length && currentItems.length > 0">
                                </div>
                            </th>
                            <th scope="col" role="button" tabindex="0" @click="sortBy('name')" @keydown.enter.prevent="sortBy('name')" @keydown.space.prevent="sortBy('name')" :aria-sort="sortField === 'name' ? (sortDirection === 'asc' ? 'ascending' : 'descending') : 'none'" class="sortable">
                                Name
                                <i class="bi bi-arrow-up" x-show="sortField === 'name' && sortDirection === 'asc'" aria-hidden="true"></i>
                                <i class="bi bi-arrow-down" x-show="sortField === 'name' && sortDirection === 'desc'" aria-hidden="true"></i>
                            </th>
                            <th scope="col">Guard</th>
                            <th scope="col" x-show="activeTab === 'roles'">Permissions</th>
                            <th scope="col" x-show="activeTab === 'permissions'">Roles</th>
                            <th scope="col">Status</th>
                            <th scope="col">Updated</th>
                            <th style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="item in currentItems" :key="`${activeTab}-${item.id}`">
                            <tr :class="{ 'table-active': selectedItems.includes(item.id) }">
                                <td>
                                    <div class="form-check m-0">
                                        <input class="form-check-input" type="checkbox" :value="item.id" :checked="selectedItems.includes(item.id)" @change="toggleItem(item.id)">
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="stats-icon bg-primary bg-opacity-10 text-primary me-2" style="width: 32px; height: 32px;">
                                            <i class="bi" :class="activeTab === 'roles' ? 'bi-shield-lock' : 'bi-key'"></i>
                                        </div>
                                        <div>
                                            <div class="fw-medium" x-text="item.name"></div>
                                            <small class="text-muted" x-text="'ID: ' + item.id"></small>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge bg-secondary" x-text="item.guard_name"></span></td>
                                <td x-show="activeTab === 'roles'">
                                    <div class="d-flex flex-column gap-1">
                                        <span class="badge bg-info align-self-start" x-text="`${item.permissions_count || 0} permissions`"></span>
                                        <div class="d-flex flex-wrap gap-1" x-show="item.permissionGroups.length > 0">
                                            <template x-for="group in item.permissionGroups.slice(0, 4)" :key="`${item.id}-${group.key}`">
                                                <span class="badge text-bg-light border">
                                                    <i class="bi me-1" :class="`bi-${group.icon}`"></i>
                                                    <span x-text="group.label"></span>
                                                    <span class="text-muted" x-text="`(${group.items.length})`"></span>
                                                </span>
                                            </template>
                                            <span class="badge text-bg-secondary" x-show="item.permissionGroups.length > 4" x-text="`+${item.permissionGroups.length - 4} groups`"></span>
                                        </div>
                                    </div>
                                </td>
                                <td x-show="activeTab === 'permissions'">
                                    <span class="badge bg-info" x-text="`${item.roles_count || 0} roles`"></span>
                                </td>
                                <td>
                                    <span class="badge" :class="item.isDeleted ? 'bg-danger' : 'bg-success'" x-text="item.isDeleted ? 'deleted' : 'active'"></span>
                                </td>
                                <td x-text="item.updatedAt"></td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Access actions">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="#" @click.prevent="viewItem(item)">
                                                    <i class="bi bi-eye me-2"></i>View Details
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="#" @click.prevent="editItem(item)" x-show="!item.isDeleted">
                                                    <i class="bi bi-pencil me-2"></i>Edit
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item text-danger" href="#" @click.prevent="deleteItem(item)" x-show="!item.isDeleted">
                                                    <i class="bi bi-trash me-2"></i>Delete
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item text-success" href="#" @click.prevent="restoreItem(item)" x-show="item.isDeleted">
                                                    <i class="bi bi-arrow-counterclockwise me-2"></i>Restore
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item text-danger" href="#" @click.prevent="forceDeleteItem(item)" x-show="item.isDeleted">
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

            <div class="d-flex justify-content-between align-items-center p-3">
                <div class="text-muted">
                    Showing <span x-text="pageFrom"></span> to <span x-text="pageTo"></span> of <span x-text="totalItems"></span> results
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
</div>

<div class="modal fade user-management" id="accessModal" tabindex="-1" aria-labelledby="accessModalLabel">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" x-data="accessForm">
        <form class="modal-content border-0 shadow-lg" @submit.prevent="saveItem()">
                <div class="modal-header bg-primary bg-gradient text-white border-bottom-0 pb-4">
                    <h5 class="modal-title d-flex align-items-center" id="accessModalLabel">
                        <i class="bi me-2 fs-4" :class="type === 'roles' ? 'bi-shield-lock' : 'bi-key'"></i>
                        <span x-text="title"></span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body position-relative" style="margin-top: -15px; border-radius: 12px 12px 0 0; background: var(--bs-body-bg);">
                    <div class="row g-4 pt-2">
                        <div class="col-md-8">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="accessName" x-model="form.name" required placeholder="e.g. user-create">
                                <label for="accessName">Name <span class="text-danger">*</span></label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="accessGuard" x-model="form.guard_name" placeholder="web">
                                <label for="accessGuard">Guard</label>
                            </div>
                        </div>
                        <template x-if="type === 'roles'">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Permissions</label>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="small text-muted">
                                        <span x-text="`${form.permissions.length} selected`"></span>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="clearPermissions()" x-show="form.permissions.length > 0">
                                        <i class="bi bi-x-circle me-1"></i>Clear
                                    </button>
                                </div>
                                <template x-if="permissionsLoading">
                                    <div class="border rounded p-3 text-muted small">
                                        <span class="spinner-border spinner-border-sm me-2"></span>Loading permissions...
                                    </div>
                                </template>
                                <template x-if="!permissionsLoading && permissionsError">
                                    <div class="alert alert-warning mb-0">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        <span x-text="`Unable to load permissions: ${permissionsError}`"></span>
                                    </div>
                                </template>
                                <template x-if="!permissionsLoading && !permissionsError">
                                    <div class="permissions-container mt-3">
                                        <div class="row g-3" x-show="groupedPermissions.length > 0">
                                            <template x-for="group in groupedPermissions" :key="group.key">
                                                <div class="col-md-12">
                                                    <div class="card border-0 shadow-sm overflow-hidden h-100">
                                                        <div class="card-header bg-body-tertiary border-0 py-3 d-flex justify-content-between align-items-center cursor-pointer" 
                                                             data-bs-toggle="collapse" 
                                                             :data-bs-target="`#permission-group-${group.key}`" 
                                                             aria-expanded="true" 
                                                             :aria-controls="`permission-group-${group.key}`">
                                                            <div class="d-flex align-items-center">
                                                                <div class="stats-icon bg-primary bg-opacity-10 text-primary me-3 rounded-3" style="width: 40px; height: 40px;">
                                                                    <i class="bi fs-5" :class="`bi-${group.icon}`"></i>
                                                                </div>
                                                                <div>
                                                                    <h6 class="mb-0 fw-bold" x-text="group.label"></h6>
                                                                    <small class="text-muted" x-text="`${group.items.length} permissions`"></small>
                                                                </div>
                                                            </div>
                                                            <div class="d-flex align-items-center gap-3">
                                                                <span class="badge rounded-pill" 
                                                                      :class="selectedPermissionCount(group) > 0 ? 'bg-primary' : 'bg-secondary bg-opacity-25 text-secondary'" 
                                                                      x-text="`${selectedPermissionCount(group)} / ${group.items.length}`">
                                                                </span>
                                                                <i class="bi bi-chevron-down text-muted transition-all"></i>
                                                            </div>
                                                        </div>
                                                        <div class="collapse show" :id="`permission-group-${group.key}`">
                                                            <div class="card-body bg-body">
                                                                <div class="d-flex justify-content-end mb-3 pb-2 border-bottom">
                                                                    <div class="form-check form-switch">
                                                                        <input class="form-check-input cursor-pointer" type="checkbox" role="switch" :id="`toggle-all-${group.key}`" 
                                                                               :checked="isPermissionGroupSelected(group)" 
                                                                               @change="togglePermissionGroup(group)">
                                                                        <label class="form-check-label small fw-medium text-muted cursor-pointer" :for="`toggle-all-${group.key}`" 
                                                                               x-text="isPermissionGroupSelected(group) ? 'Deselect All' : 'Select All'"></label>
                                                                    </div>
                                                                </div>
                                                                <div class="row g-3">
                                                                    <template x-for="permission in group.items" :key="permission.id">
                                                                        <div class="col-md-6 col-lg-4">
                                                                            <div class="position-relative border rounded-3 p-3 h-100 transition-all hover-border-primary" 
                                                                                 :class="{'border-primary bg-primary bg-opacity-10': form.permissions.includes(permission.name)}">
                                                                                <div class="form-check form-switch mb-0">
                                                                                    <input class="form-check-input cursor-pointer" 
                                                                                           type="checkbox" 
                                                                                           role="switch" 
                                                                                           :id="`perm-${permission.id}`" 
                                                                                           :value="permission.name" 
                                                                                           x-model="form.permissions">
                                                                                    <label class="form-check-label w-100 cursor-pointer ps-2" :for="`perm-${permission.id}`">
                                                                                        <span class="fw-semibold d-block text-body" x-text="permission.actionLabel"></span>
                                                                                        <span class="small text-muted d-block mt-1" x-text="permission.name" style="word-break: break-all;"></span>
                                                                                    </label>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </template>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                        <div class="border rounded p-3 text-muted small" x-show="groupedPermissions.length === 0">
                                            No permissions available. Create permissions first, then add them to this role.
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
                <div class="modal-footer bg-body-tertiary border-top-0 rounded-bottom-3">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 shadow-sm" :disabled="saving">
                        <span x-show="saving" class="spinner-border spinner-border-sm me-2"></span>
                        <span x-text="editingId ? 'Save Changes' : 'Create Access'"></span>
                    </button>
                </div>
        </form>
    </div>
</div>

<div class="modal fade" id="accessViewModal" tabindex="-1" aria-labelledby="accessViewModalLabel">
    <div class="modal-dialog modal-lg" x-data="accessProfile">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="accessViewModalLabel"><i class="bi bi-shield-check me-2"></i>Access Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <template x-if="loading">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted">Loading details...</p>
                    </div>
                </template>
                <template x-if="!loading && item">
                    <div>
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                                <i class="bi" :class="type === 'roles' ? 'bi-shield-lock' : 'bi-key'"></i>
                            </div>
                            <div>
                                <h5 class="mb-0 fw-bold" x-text="item.name"></h5>
                                <p class="text-muted mb-1" x-text="`Guard: ${item.guard_name}`"></p>
                                <span class="badge" :class="item.isDeleted ? 'bg-danger' : 'bg-success'" x-text="item.isDeleted ? 'deleted' : 'active'"></span>
                            </div>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <div class="card bg-body-tertiary border-0 h-100"><div class="card-body">
                                    <p class="small text-muted mb-1">ID</p>
                                    <p class="mb-0 fw-medium" x-text="item.id"></p>
                                </div></div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-body-tertiary border-0 h-100"><div class="card-body">
                                    <p class="small text-muted mb-1">Created</p>
                                    <p class="mb-0 fw-medium" x-text="item.createdAt"></p>
                                </div></div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-body-tertiary border-0 h-100"><div class="card-body">
                                    <p class="small text-muted mb-1">Updated</p>
                                    <p class="mb-0 fw-medium" x-text="item.updatedAt"></p>
                                </div></div>
                            </div>
                        </div>
                        <template x-if="type === 'roles'">
                            <div>
                                <h6 class="fw-semibold mb-2">Permissions</h6>
                                <div class="accordion" id="profilePermissionGroups">
                                    <template x-for="group in groupedRelatedItems" :key="group.key">
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" :id="`profile-permission-heading-${group.key}`">
                                                <button class="accordion-button py-2" type="button" data-bs-toggle="collapse" :data-bs-target="`#profile-permission-group-${group.key}`" aria-expanded="true" :aria-controls="`profile-permission-group-${group.key}`">
                                                    <span class="d-flex align-items-center justify-content-between w-100 pe-3">
                                                        <span>
                                                            <i class="bi me-2" :class="`bi-${group.icon}`"></i>
                                                            <span x-text="group.label"></span>
                                                        </span>
                                                        <span class="badge bg-primary-subtle text-primary" x-text="group.items.length"></span>
                                                    </span>
                                                </button>
                                            </h2>
                                            <div class="accordion-collapse collapse show" :id="`profile-permission-group-${group.key}`" :aria-labelledby="`profile-permission-heading-${group.key}`">
                                                <div class="accordion-body">
                                                    <div class="d-flex flex-wrap gap-2">
                                                        <template x-for="entry in group.items" :key="entry.id">
                                                            <span class="badge bg-primary" x-text="entry.name"></span>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                <span class="text-muted small" x-show="groupedRelatedItems.length === 0">No permissions assigned.</span>
                            </div>
                        </template>
                        <template x-if="type === 'permissions'">
                            <div>
                                <h6 class="fw-semibold mb-2">Assigned Roles</h6>
                                <div class="d-flex flex-wrap gap-2">
                                    <template x-for="entry in relatedItems" :key="entry.id">
                                        <span class="badge bg-primary" x-text="entry.name"></span>
                                    </template>
                                    <span class="text-muted small" x-show="relatedItems.length === 0">No roles assigned.</span>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" x-show="item && !item.isDeleted" @click="editFromProfile()">
                    <i class="bi bi-pencil me-1"></i>Edit
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="accessImportModal" tabindex="-1" aria-labelledby="accessImportModalLabel">
    <div class="modal-dialog" x-data="accessImportForm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="accessImportModalLabel"><i class="bi bi-upload me-2"></i>Import Access Records</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mb-3">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>CSV Format:</strong> type, name, guard_name, permissions<br>
                    <small>Example: role, Manager, web, user-view|audit-log-view</small>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Select CSV File</label>
                    <input type="file" class="form-control" accept=".csv" @change="handleFile($event)">
                </div>
                <template x-if="result">
                    <div>
                        <div class="alert alert-success" x-show="result.created > 0">
                            <i class="bi bi-check-circle me-2"></i>
                            <span x-text="`${result.created} record(s) imported successfully.`"></span>
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
                <button type="button" class="btn btn-primary" @click="importItems()" :disabled="importing || !file">
                    <span x-show="importing" class="spinner-border spinner-border-sm me-1"></span>
                    <span x-text="importing ? 'Importing...' : 'Import'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
