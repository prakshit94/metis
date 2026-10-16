@extends('layouts.app')

@section('title', 'Roles & Permissions')
@section('page', 'roles-permissions')

@section('content')
<div class="user-management user-admin-page" x-data="rolesPermissionsTable" data-can-view-roles="{{ auth()->user()->can('role-view') ? 'true' : 'false' }}" data-can-view-permissions="{{ auth()->user()->can('permission-view') ? 'true' : 'false' }}">
    <div class="access-page-heading d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 mb-lg-5 mb-xl-6">
        <div>
            <h1 class="h3 mb-0"><i class="bi bi-shield-lock-fill text-primary me-2"></i>Roles & Permissions</h1>
            <p class="text-muted mb-0">Manage access roles, permissions, and assignments</p>
        </div>
        <div class="d-flex gap-2">
            @canany(['role-import', 'permission-import'])
            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#accessImportModal">
                <i class="bi bi-upload me-2"></i>Import
            </button>
            @endcanany
            @canany(['role-export', 'permission-export'])
            <button type="button" class="btn btn-outline-secondary" @click="exportItems()">
                <i class="bi bi-download me-2"></i>Export
            </button>
            @endcanany
            @canany(['role-create', 'permission-create'])
            <button type="button" class="btn btn-primary" @click="openCreate()">
                <i class="bi bi-plus-circle me-2"></i><span x-text="activeTab === 'roles' ? 'Add Role' : 'Add Permission'"></span>
            </button>
            @endcanany
        </div>
    </div>

    <div class="row g-4 g-lg-5 g-xl-6 mb-5 mb-lg-5 mb-xl-6">
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card border-start border-4 border-primary">
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
            <div class="card stats-card border-start border-4 border-success">
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
            <div class="card stats-card border-start border-4 border-warning">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-info bg-opacity-10 text-info me-3">
                            <i class="bi bi-link-45deg"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Assignments</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="assignmentCount"></span></div>
                            <small class="text-muted"><i class="bi bi-shuffle"></i> On the loaded roles page</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card border-start border-4 border-info">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-warning bg-opacity-10 text-warning me-3">
                            <i class="bi bi-archive"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Deleted</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="deletedCount"></span></div>
                            <small class="text-muted"><i class="bi bi-arrow-counterclockwise"></i> On the current page</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Permission Coverage & Access Distribution charts removed for clean layout --}}

    {{-- Recent Activity, System Alerts, Quick Actions removed for clean layout --}}


    <div class="card">
        <div class="card-header">
            <div class="row align-items-center g-3">
                <div class="col">
                    <h2 class="h5 card-title mb-0">Access Directory</h2>
                </div>
                <div class="col-auto">
                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                        <div class="btn-group btn-group-sm" role="group" aria-label="Directory type">
                            @can('role-view')
                            <input type="radio" class="btn-check" name="accessTab" id="rolesTab" value="roles" x-model="activeTab" @change="switchTab('roles')">
                            <label class="btn btn-outline-secondary" for="rolesTab">Roles</label>
                            @endcan
                            @can('permission-view')
                            <input type="radio" class="btn-check" name="accessTab" id="permissionsTab" value="permissions" x-model="activeTab" @change="switchTab('permissions')">
                            <label class="btn btn-outline-secondary" for="permissionsTab">Permissions</label>
                            @endcan
                        </div>
                        <div class="position-relative">
                            <input type="search" class="form-control form-control-sm" aria-label="Search roles and permissions" placeholder="Search access..." x-model="searchQuery" @input.debounce.300ms="filterItems()" style="width: 200px;">
                            <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted"></i>
                        </div>
                        <select class="form-select form-select-sm" aria-label="Access status" x-model="statusFilter" @change="filterItems()" style="width: 150px;">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="deleted">Deleted</option>
                        </select>
                        <select class="form-select form-select-sm" aria-label="Access guard" x-model="guardFilter" @change="filterItems()" style="width: 130px;">
                            <option value="">All Guards</option>
                            <option value="web">web</option>
                            <option value="api">api</option>
                        </select>
                        <select class="form-select form-select-sm" aria-label="Access rows per page" x-model.number="itemsPerPage" @change="filterItems()" style="width: 120px;">
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
            <div class="bulk-actions-bar p-3 bg-primary bg-opacity-10 border-bottom border-primary border-opacity-25" x-show="selectedItems.length > 0">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle-fill text-primary me-2"></i>
                        <span class="fw-medium text-primary"><span x-text="selectedItems.length"></span> item<span x-show="selectedItems.length !== 1">s</span> selected</span>
                    </div>
                    <div class="d-flex gap-2">
                        @canany(['role-delete', 'permission-delete'])
                        <button class="btn btn-sm btn-danger" @click="bulkAction('delete')" x-show="hasSelectedActiveItems">
                            <i class="bi bi-trash me-1"></i>Delete
                        </button>
                        @endcanany
                        @canany(['role-restore', 'permission-restore'])
                        <button class="btn btn-sm btn-success" @click="bulkAction('restore')" x-show="hasSelectedDeletedItems">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Restore
                        </button>
                        @endcanany
                        @canany(['role-permanent-delete', 'permission-permanent-delete'])
                        <button class="btn btn-sm btn-danger" @click="bulkAction('force-delete')" x-show="hasSelectedDeletedItems">
                            <i class="bi bi-trash3 me-1"></i>Permanent Delete
                        </button>
                        @endcanany
                        <button class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center px-2" @click="selectedItems = []" title="Clear selection">
                            <i class="bi bi-x-lg" style="margin-left: 7px"></i>
                        </button>
                    </div>
                </div>
            </div>

            <p class="small text-body-secondary px-3 py-2 mb-0 border-bottom">Scroll horizontally to view all details and actions.</p>
<div class="table-responsive directory-table-region" role="region" aria-label="Roles and permissions directory" tabindex="0">
                <table class="access-directory-table table table-hover align-middle mb-0">
                    <thead class="table-active">
                        <tr>
                            <th style="width: 50px;" class="ps-3">
                                <input type="checkbox" aria-label="Select all access entries on this page"
                                       class="user-select-checkbox"
                                       @change="$event.isTrusted && toggleAll($event.target.checked)"
                                       :checked="selectedItems.length === currentItems.length && currentItems.length > 0">
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
                        <tr x-show="isLoading" x-cloak><td colspan="7" class="text-center py-4" role="status">Loading directory…</td></tr>
                        <tr x-show="!isLoading && currentItems.length === 0" x-cloak><td colspan="7" class="text-center text-body-secondary py-4" role="status">No entries match the current filters.</td></tr>
                        <template x-for="item in currentItems" :key="`${activeTab}-${item.id}`">
                            <tr x-show="!isLoading" :class="{ 'table-active': selectedItems.includes(item.id) }">
                                <td class="ps-3">
                                    <input type="checkbox" :aria-label="'Select ' + item.name"
                                           class="user-select-checkbox"
                                           :value="item.id"
                                           :checked="selectedItems.includes(item.id)"
                                           @change="toggleItem(item.id)">
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
                                <td><span class="badge text-bg-secondary" x-text="item.guard_name"></span></td>
                                <td x-show="activeTab === 'roles'">
                                    <div class="d-flex flex-column gap-1">
                                        <span class="badge text-bg-info align-self-start" x-text="`${item.permissions_count || 0} permissions`"></span>
                                        <div class="d-flex flex-wrap gap-1" x-show="item.permissionGroups.length > 0">
                                            <template x-for="group in item.permissionGroups.slice(0, 4)" :key="`${item.id}-${group.key}`">
                                                <span class="badge bg-body-tertiary text-body border">
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
                                    <span class="badge text-bg-info" x-text="`${item.roles_count || 0} roles`"></span>
                                </td>
                                <td>
                                    <span class="badge" :class="item.isDeleted ? 'text-bg-danger' : 'text-bg-success'" x-text="item.isDeleted ? 'deleted' : 'active'"></span>
                                </td>
                                <td x-text="item.updatedAt"></td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Access actions" :aria-label="'Actions for ' + item.name">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            @canany(['role-view', 'permission-view'])
                                            <li>
                                                <a class="dropdown-item" href="#" @click.prevent="viewItem(item)">
                                                    <i class="bi bi-eye me-2"></i>View Details
                                                </a>
                                            </li>
                                            @endcanany
                                            @canany(['role-edit', 'permission-edit'])
                                            <li>
                                                <a class="dropdown-item" href="#" @click.prevent="editItem(item)" x-show="!item.isDeleted">
                                                    <i class="bi bi-pencil me-2"></i>Edit
                                                </a>
                                                <a class="dropdown-item" href="#" @click.prevent="cloneRole(item)" x-show="!item.isDeleted && activeTab === 'roles'">
                                                    <i class="bi bi-copy me-2 text-info"></i>Clone
                                                </a>
                                            </li>
                                            @endcanany
                                            <li><hr class="dropdown-divider"></li>
                                            @canany(['role-delete', 'permission-delete'])
                                            <li>
                                                <a class="dropdown-item text-danger" href="#" @click.prevent="deleteItem(item)" x-show="!item.isDeleted">
                                                    <i class="bi bi-trash me-2"></i>Delete
                                                </a>
                                            </li>
                                            @endcanany
                                            @canany(['role-restore', 'permission-restore'])
                                            <li>
                                                <a class="dropdown-item text-success" href="#" @click.prevent="restoreItem(item)" x-show="item.isDeleted">
                                                    <i class="bi bi-arrow-counterclockwise me-2"></i>Restore
                                                </a>
                                            </li>
                                            @endcanany
                                            @canany(['role-permanent-delete', 'permission-permanent-delete'])
                                            <li>
                                                <a class="dropdown-item text-danger" href="#" @click.prevent="forceDeleteItem(item)" x-show="item.isDeleted">
                                                    <i class="bi bi-trash3 me-2"></i>Permanent Delete
                                                </a>
                                            </li>
                                            @endcanany
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center p-3">
                <div class="text-muted">
                    Showing <span x-text="pageFrom"></span> to <span x-text="pageTo"></span> of <span x-text="totalItems"></span> results
                </div>
                <nav>
                    <ul class="pagination pagination-sm flex-wrap mb-0">
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
    <div class="modal-dialog modal-dialog-scrollable access-dialog" x-data="accessForm">
        <form class="modal-content" @submit.prevent="saveItem()">
                <div class="modal-header">
                    <div class="min-w-0"><h2 class="h5 modal-title d-flex align-items-center" id="accessModalLabel">
                        <i class="bi me-2" :class="type === 'roles' ? 'bi-shield-lock text-primary' : 'bi-key text-primary'"></i>
                        <span x-text="title"></span>
                    </h2><p class="small text-body-secondary mb-0 mt-1" x-text="type === 'roles' ? 'Set role details and choose the permissions it grants.' : 'Set permission details and choose its assigned roles.'"></p></div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <div class="access-detail-field">
                                <label class="form-label fw-semibold" for="accessName"><span x-text="type === 'roles' ? 'Role name' : 'Permission name'"></span> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="accessName" x-model="form.name" maxlength="100" required :placeholder="type === 'roles' ? 'e.g. Sales Manager' : 'e.g. user-create'" autocomplete="off">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="access-detail-field">
                                <label class="form-label fw-semibold" for="accessGuard">Authentication guard</label>
                                <input type="text" class="form-control" id="accessGuard" x-model="form.guard_name" maxlength="100" placeholder="web" aria-describedby="accessGuardHelp">
                                <div id="accessGuardHelp" class="form-text">Use the guard configured for this role or permission.</div>
                            </div>
                        </div>
                        <template x-if="type === 'roles'">
                            <div class="col-12">
                                <h3 class="h6 fw-semibold mb-1">Permissions</h3><p class="small text-body-secondary mb-3">Choose individual permissions or select the visible permissions in a group.</p>
                                <div class="permission-toolbar d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                                    <div class="permission-search input-group border rounded">
                                        <span class="input-group-text bg-transparent border-0 text-muted"><i class="bi bi-search"></i></span>
                                        <input type="text" class="form-control border-0 shadow-none ps-0" id="rolePermissionSearch" aria-label="Search role permissions" placeholder="Search by permission or module..." x-model="permissionSearch">
                                    </div>
                                    <div class="permission-selection-tools d-flex flex-wrap gap-2 align-items-center">
                                        <span class="badge text-bg-primary-subtle text-primary-emphasis" role="status" x-text="`${form.permissions.length} / ${permissions.length} selected`"></span>
                                        <button type="button" class="btn btn-sm btn-outline-primary" @click="selectAllPermissions()">
                                            <i class="bi bi-check-all me-1" aria-hidden="true"></i>Select all permissions
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" @click="clearPermissions()" x-show="form.permissions.length > 0">
                                            <i class="bi bi-x-circle me-1" aria-hidden="true"></i>Clear selection
                                        </button>
                                    </div>
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
                                        <div class="permission-groups" x-show="groupedPermissions.length > 0">
                                            <template x-for="group in groupedPermissions" :key="group.key">
                                                <details class="permission-group border rounded-3" open>
                                                    <summary class="permission-group-summary">
                                                        <span class="permission-group-icon text-primary bg-primary-subtle rounded-3"><i class="bi" :class="`bi-${group.icon}`" aria-hidden="true"></i></span>
                                                        <span class="permission-group-title fw-semibold" x-text="group.label"></span>
                                                        <span class="small text-body-secondary" x-text="`${selectedPermissionCount(group)} / ${group.items.length} selected`"></span>
                                                    </summary>
                                                    <div class="permission-group-body border-top">
                                                        <label class="d-inline-flex align-items-center gap-2 mb-3 fw-medium small cursor-pointer">
                                                            <input class="form-check-input m-0" type="checkbox"
                                                                   :checked="isPermissionGroupSelected(group)"
                                                                   :indeterminate="selectedPermissionCount(group) > 0 && !isPermissionGroupSelected(group)"
                                                                   @change="togglePermissionGroup(group)">
                                                            <span>Select visible group permissions</span>
                                                        </label>
                                                        <template x-for="(subGroup, index) in group.subGroups" :key="index">
                                                            <div :class="index > 0 ? 'mt-3 pt-3 border-top' : ''">
                                                                <h4 class="small fw-semibold text-body-secondary mb-3" x-text="subGroup.label" x-show="group.subGroups.length > 1 || subGroup.label !== group.label"></h4>
                                                                <div class="permission-options-grid">
                                                                    <template x-for="permission in subGroup.items" :key="permission.id">
                                                                        <label class="permission-option d-flex align-items-start gap-2 rounded-3 border p-3" :class="isPermissionSelected(permission.name) ? 'border-primary bg-primary-subtle' : 'border-secondary-subtle'">
                                                                            <input class="form-check-input flex-shrink-0" type="checkbox" :value="permission.name" x-model="form.permissions">
                                                                            <span class="min-w-0">
                                                                                <span class="fw-medium text-body d-block" x-text="permission.actionLabel"></span>
                                                                                <span class="small text-body-secondary permission-code d-block mt-1" x-text="permission.name"></span>
                                                                            </span>
                                                                        </label>
                                                                    </template>
                                                                </div>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </details>
                                            </template>
                                        </div>
                                        <div class="border rounded p-4 text-muted text-center" x-show="groupedPermissions.length === 0">
                                            <i class="bi bi-shield-x fs-2 d-block mb-2"></i>
                                            <span x-text="permissionSearch ? 'No permissions match your search. Try another name or module.' : 'No permissions available. Create permissions first, then add them to this role.'"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <template x-if="type === 'permissions'">
                            <div class="col-12 mt-3">
                                <label class="form-label fw-semibold">Assign to Roles (Optional)</label>
                                <p class="text-muted small mb-3">Select which roles should immediately be granted this permission.</p>
                                
                                <div class="row g-2 border rounded p-3 bg-body-tertiary" style="max-height: 250px; overflow-y: auto;">
                                    <template x-if="rolesList.length === 0">
                                        <div class="text-muted text-center w-100 py-3">No roles available.</div>
                                    </template>
                                    <template x-for="r in rolesList" :key="r">
                                        <div class="col-md-4 col-sm-6">
                                            <div class="form-check">
                                                <input class="form-check-input shadow-sm" type="checkbox" :value="r" :id="'role_' + r" x-model="form.roles">
                                                <label class="form-check-label text-truncate w-100 fw-medium" style="font-size: 0.9rem;" :for="'role_' + r" x-text="r" :title="r"></label>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
                <div class="modal-footer gap-2">
                    <span class="access-footer-summary small text-body-secondary me-auto" x-show="type === 'roles'" x-text="`${form.permissions.length} permissions selected`"></span>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    @canany(['role-create', 'role-edit', 'permission-create', 'permission-edit'])
                    <button type="submit" class="btn btn-primary px-4" :disabled="saving">
                        <span x-show="saving" class="spinner-border spinner-border-sm me-2"></span>
                        <span x-text="saving ? 'Saving…' : (editingId ? 'Save changes' : (type === 'roles' ? 'Create role' : 'Create permission'))"></span>
                    </button>
                    @endcanany
                </div>
        </form>
    </div>
</div>

<div class="modal fade" id="accessViewModal" tabindex="-1" aria-labelledby="accessViewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" x-data="accessProfile">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="modal-title fw-bold" id="accessViewModalLabel">
                        <i class="bi bi-shield-check me-2 text-primary"></i>Access Details
                    </h5>
                    <template x-if="item && item.isDeleted">
                        <span class="badge text-bg-danger">Deleted</span>
                    </template>
                    <template x-if="item && !item.isDeleted">
                        <span class="badge bg-success-subtle text-success">Active</span>
                    </template>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-3 pb-4">
                <template x-if="loading">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted">Loading details...</p>
                    </div>
                </template>
                <template x-if="!loading && item">
                    <div>
                        <div class="row g-4 mb-4">
                            <div class="col-md-12">
                                <div class="p-3 bg-body-tertiary rounded-3 h-100">
                                    <h6 class="fw-bold mb-3 text-primary"><i class="bi bi-info-circle me-2"></i>Basic Info</h6>
                                    <table class="table table-sm table-borderless mb-0">
                                        <tbody>
                                            <tr>
                                                <td class="text-muted w-25">Name</td>
                                                <td class="fw-bold fs-6" x-text="item.name || '—'"></td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">ID</td>
                                                <td class="font-monospace text-muted" x-text="item.id || '—'"></td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">Guard</td>
                                                <td x-text="item.guard_name || '—'"></td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">Created</td>
                                                <td x-text="item.createdAt || '—'"></td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">Updated</td>
                                                <td x-text="item.updatedAt || '—'"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <template x-if="type === 'roles'">
                            <div>
                                <h6 class="fw-bold mb-4 d-flex align-items-center">
                                    <i class="bi bi-ui-checks-grid text-primary me-2"></i>Assigned Permissions
                                </h6>
                                <div class="row g-4">
                                    <template x-for="group in groupedRelatedItems" :key="group.key">
                                        <div class="col-lg-6 col-12">
                                            <div class="card border-start border-4 border-danger shadow-sm h-100 element-card transition-all hover-border-primary">
                                                <div class="card-body">
                                                    <div class="d-flex align-items-center mb-3">
                                                        <div class="stats-icon bg-primary bg-opacity-10 text-primary me-3 rounded-3 d-flex justify-content-center align-items-center" style="width: 48px; height: 48px;">
                                                            <i class="bi fs-4" :class="`bi-${group.icon}`"></i>
                                                        </div>
                                                        <div>
                                                            <h5 class="card-title fw-bold mb-1" x-text="group.label"></h5>
                                                            <small class="text-muted" x-text="`${group.items.length} permissions`"></small>
                                                        </div>
                                                    </div>
                                                    <div class="mt-3">
                                                        <template x-for="(subGroup, index) in group.subGroups" :key="index">
                                                            <div :class="index > 0 ? 'mt-2 pt-2 border-top border-secondary-subtle' : ''">
                                                                <h6 class="fw-semibold text-muted mb-1" style="font-size: 0.75rem; letter-spacing: 0.05em;" x-text="subGroup.label" x-show="group.subGroups.length > 1 || subGroup.label !== group.label"></h6>
                                                                <div class="d-flex flex-wrap gap-1">
                                                                    <template x-for="entry in subGroup.items" :key="entry.id">
                                                                        <span class="badge bg-body-tertiary text-body-emphasis border fw-normal" style="font-size: 0.7rem;" x-text="entry.actionLabel || entry.name"></span>
                                                                    </template>
                                                                </div>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                <div class="text-center p-5 border rounded-4 bg-body-tertiary" x-show="groupedRelatedItems.length === 0">
                                    <i class="bi bi-shield-x fs-1 text-muted mb-3 d-block"></i>
                                    <h5 class="text-muted mb-1">No permissions assigned</h5>
                                    <p class="text-muted small mb-0">This role currently has no permissions.</p>
                                </div>
                            </div>
                        </template>

                        <template x-if="type === 'permissions'">
                            <div>
                                <h6 class="fw-bold mb-4 d-flex align-items-center">
                                    <i class="bi bi-people text-primary me-2"></i>Assigned Roles
                                </h6>
                                <div class="card border-start border-4 border-secondary shadow-sm element-card transition-all hover-border-primary">
                                    <div class="card-body p-4">
                                        <div class="d-flex flex-wrap gap-2">
                                            <template x-for="entry in relatedItems" :key="entry.id">
                                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle py-2 px-3 fw-medium" x-text="entry.name"></span>
                                            </template>
                                        </div>
                                        <div class="text-center py-4" x-show="relatedItems.length === 0">
                                            <i class="bi bi-person-x fs-1 text-muted mb-3 d-block"></i>
                                            <span class="text-muted small">This permission is not assigned to any role.</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                    </div>
                </template>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                @canany(['role-edit', 'permission-edit'])
                <button type="button" class="btn btn-primary px-4" x-show="item && !item.isDeleted" @click="editFromProfile()">
                    <i class="bi bi-pencil me-1"></i>Edit
                </button>
                @endcanany
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="accessImportModal" tabindex="-1" aria-labelledby="accessImportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" x-data="accessImportForm">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-bottom-0 pb-4">
                <h5 class="modal-title d-flex align-items-center" id="accessImportModalLabel">
                    <i class="bi bi-upload me-2 fs-4"></i>
                    <span>Import Access Records</span>
                </h5>
                <button type="button" class="btn-close btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body position-relative bg-body" style="margin-top: -15px; border-radius: 12px 12px 0 0;">
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
            <div class="modal-footer bg-body-tertiary border-top-0 rounded-bottom-3">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                @canany(['role-import', 'permission-import'])
                <button type="button" class="btn btn-primary px-4 shadow-sm" @click="importItems()" :disabled="importing || !file">
                    <span x-show="importing" class="spinner-border spinner-border-sm me-2"></span>
                    <span x-text="importing ? 'Importing...' : 'Import Records'"></span>
                </button>
                @endcanany
            </div>
        </div>
    </div>
</div>
@endsection
