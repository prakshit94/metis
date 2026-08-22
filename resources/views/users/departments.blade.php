@extends('layouts.app')

@section('title', '🏢 Organization Structure')
@section('page', 'departments')

@section('content')
<div class="user-management" x-data="organizationTable()" x-init="init()">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 mb-lg-5 mb-xl-6">
        <div>
            <h1 class="h3 mb-0"><i class="bi bi-diagram-3-fill text-primary me-2"></i>Organization Structure</h1>
            <p class="text-muted mb-0">Manage departments, designations, and employment types</p>
        </div>
        <div class="d-flex gap-2">
            @can('department-create')
            <button type="button" class="btn btn-primary" @click="openCreateModal()">
                <i class="bi bi-plus-circle me-2"></i><span x-text="'Add ' + tabTitleSingular"></span>
            </button>
            @endcan
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4 mb-lg-5 border-bottom">
        <li class="nav-item">
            <a class="nav-link cursor-pointer" :class="{ 'active fw-bold': activeTab === 'departments', 'text-muted': activeTab !== 'departments' }" @click.prevent="switchTab('departments')">
                <i class="bi bi-building me-2"></i>Departments
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link cursor-pointer" :class="{ 'active fw-bold': activeTab === 'designations', 'text-muted': activeTab !== 'designations' }" @click.prevent="switchTab('designations')">
                <i class="bi bi-person-badge me-2"></i>Designations
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link cursor-pointer" :class="{ 'active fw-bold': activeTab === 'employment_types', 'text-muted': activeTab !== 'employment_types' }" @click.prevent="switchTab('employment_types')">
                <i class="bi bi-briefcase me-2"></i>Employment Types
            </a>
        <li class="nav-item">
            <a class="nav-link cursor-pointer" :class="{ 'active fw-bold': activeTab === 'org_chart', 'text-muted': activeTab !== 'org_chart' }" @click.prevent="switchTab('org_chart')">
                <i class="bi bi-diagram-3 me-2"></i>Org Chart
            </a>
        </li>
    </ul>

    <!-- Stats Widgets -->
    <div class="row g-4 g-lg-5 g-xl-6 mb-5 mb-lg-5 mb-xl-6" x-show="activeTab !== 'org_chart'">
        <div class="col-xl-4 col-lg-4">
            <div class="card stats-card">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-primary bg-opacity-10 text-primary me-3 fs-3 rounded p-2">
                            <i class="bi bi-list-check"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Total Records</p>
                            <div class="h3 mb-0 fw-bold text-body-emphasis" x-text="stats.total"></div>
                            <small class="text-primary-emphasis" x-text="tabTitle"></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-lg-4">
            <div class="card stats-card">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-success bg-opacity-10 text-success me-3 fs-3 rounded p-2">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Active</p>
                            <div class="h3 mb-0 fw-bold text-body-emphasis" x-text="stats.active"></div>
                            <small class="text-success-emphasis">Currently available</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-lg-4">
            <div class="card stats-card">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-danger bg-opacity-10 text-danger me-3 fs-3 rounded p-2">
                            <i class="bi bi-x-circle-fill"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Disabled</p>
                            <div class="h3 mb-0 fw-bold text-body-emphasis" x-text="stats.inactive"></div>
                            <small class="text-danger-emphasis">Hidden from dropdowns</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table Container -->
    <div class="card" x-show="activeTab !== 'org_chart'">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="h5 card-title mb-0" x-text="tabTitle + ' Directory'"></h2>
                </div>
                <div class="col-auto">
                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                        <div class="position-relative">
                            <input type="search" class="form-control form-control-sm" placeholder="Search..." x-model="searchQuery" @input.debounce.300ms="filterItems()" style="width: 200px;">
                            <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted"></i>
                        </div>
                        <select x-select class="form-select form-select-sm" x-model="statusFilter" @change="filterItems()" style="width: 150px;">
                            <option value="">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Disabled</option>
                        </select>
                        <select x-select class="form-select form-select-sm" x-model.number="itemsPerPage" @change="filterItems()" style="width: 120px;">
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
            <div class="bulk-actions-bar p-3 bg-primary bg-opacity-10 border-bottom border-primary border-opacity-25" x-show="selectedItems.length > 0" x-cloak>
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-primary"></i>
                        <span class="fw-medium text-primary">
                            <strong x-text="selectedItems.length"></strong> item(s) selected
                        </span>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @can('department-edit')
                        <button class="btn btn-sm btn-success" @click="bulkAction('activate')" :disabled="isLoading" title="Activate selected">
                            <i class="bi bi-check-circle me-1"></i>Activate
                        </button>
                        <button class="btn btn-sm btn-warning" @click="bulkAction('deactivate')" :disabled="isLoading" title="Deactivate selected">
                            <i class="bi bi-x-circle me-1"></i>Deactivate
                        </button>
                        @endcan
                        @can('department-delete')
                        <button class="btn btn-sm btn-danger" @click="bulkAction('delete')" :disabled="isLoading" title="Delete selected">
                            <i class="bi bi-trash me-1"></i>Delete
                        </button>
                        @endcan
                        <button class="btn btn-sm btn-outline-secondary" @click="selectedItems = []" title="Clear selection">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;" class="ps-3">
                                <input type="checkbox" class="form-check-input border-secondary" style="cursor: pointer;" @change="toggleAll($event.target.checked)" :checked="paginatedItems.length > 0 && paginatedItems.every(r => selectedItems.includes(String(r.id)))">
                            </th>
                            <th scope="col" role="button" @click="sortBy('id')" class="sortable" style="width: 80px;">
                                <i class="bi bi-hash me-1 text-secondary"></i>ID
                                <i class="bi bi-arrow-up" x-show="sortField === 'id' && sortDirection === 'asc'"></i>
                                <i class="bi bi-arrow-down" x-show="sortField === 'id' && sortDirection === 'desc'"></i>
                            </th>
                            <th scope="col" x-show="activeTab === 'departments'">Code</th>
                            <th scope="col" role="button" @click="sortBy('name')" class="sortable">
                                <i class="bi bi-chat-text me-1 text-secondary"></i>Name
                                <i class="bi bi-arrow-up" x-show="sortField === 'name' && sortDirection === 'asc'"></i>
                                <i class="bi bi-arrow-down" x-show="sortField === 'name' && sortDirection === 'desc'"></i>
                            </th>
                            <th scope="col" x-show="activeTab === 'departments'">Manager</th>
                            <th scope="col" role="button" @click="sortBy('is_active')" class="sortable">
                                <i class="bi bi-info-circle me-1 text-secondary"></i>Status
                                <i class="bi bi-arrow-up" x-show="sortField === 'is_active' && sortDirection === 'asc'"></i>
                                <i class="bi bi-arrow-down" x-show="sortField === 'is_active' && sortDirection === 'desc'"></i>
                            </th>
                            <th scope="col" role="button" @click="sortBy('created_at')" class="sortable">
                                <i class="bi bi-calendar-event me-1 text-secondary"></i>Created At
                                <i class="bi bi-arrow-up" x-show="sortField === 'created_at' && sortDirection === 'asc'"></i>
                                <i class="bi bi-arrow-down" x-show="sortField === 'created_at' && sortDirection === 'desc'"></i>
                            </th>
                            <th style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="isLoading">
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="mt-2 text-muted mb-0">Loading data...</p>
                                </td>
                            </tr>
                        </template>
                        <template x-if="!isLoading && paginatedItems.length === 0">
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    No records found matching current criteria.
                                </td>
                            </tr>
                        </template>
                        <template x-for="item in paginatedItems" :key="item.id">
                            <tr :class="{ 'table-active': selectedItems.includes(String(item.id)) }">
                                <td class="ps-3">
                                    <input type="checkbox" class="form-check-input border-secondary" style="cursor: pointer;" :value="String(item.id)" x-model="selectedItems">
                                </td>
                                <td>
                                    <span class="fw-medium text-body-emphasis" x-text="item.id"></span>
                                </td>
                                <td x-show="activeTab === 'departments'">
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle" x-text="item.code || 'N/A'"></span>
                                </td>
                                <td>
                                    <span class="fw-semibold text-body" x-text="item.name"></span>
                                </td>
                                <td x-show="activeTab === 'departments'">
                                    <span x-text="item.manager ? item.manager.name : '—'"></span>
                                </td>
                                <td>
                                    <span class="badge" 
                                          :class="{
                                              'bg-success bg-opacity-25 text-success border border-success border-opacity-50': item.is_active,
                                              'bg-danger bg-opacity-25 text-danger border border-danger border-opacity-50': !item.is_active
                                          }"
                                          x-text="item.is_active ? 'ACTIVE' : 'DISABLED'"></span>
                                </td>
                                <td x-text="new Date(item.created_at).toLocaleDateString()"></td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <template x-if="activeTab === 'departments'">
                                                <li>
                                                    <a class="dropdown-item" href="#" @click.prevent="viewDepartment(item)">
                                                        <i class="bi bi-eye me-2"></i>View
                                                    </a>
                                                </li>
                                            </template>
                                            @can('department-edit')
                                            <li>
                                                <a class="dropdown-item" href="#" @click.prevent="openEditModal(item)">
                                                    <i class="bi bi-pencil-square me-2"></i>Edit
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="#" @click.prevent="toggleActive(item)">
                                                    <i class="bi me-2" :class="item.is_active ? 'bi-x-circle text-warning' : 'bi-check-circle text-success'"></i>
                                                    <span x-text="item.is_active ? 'Deactivate' : 'Activate'"></span>
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            @endcan
                                            @can('department-delete')
                                            <li>
                                                <a class="dropdown-item text-danger" href="#" @click.prevent="deleteItem(item)">
                                                    <i class="bi bi-trash me-2"></i>Delete
                                                </a>
                                            </li>
                                            @endcan
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center p-3 border-top">
                <div class="text-muted small">
                    Showing <span x-text="totalItems === 0 ? 0 : (currentPage - 1) * itemsPerPage + 1"></span> to 
                    <span x-text="Math.min(currentPage * itemsPerPage, totalItems)"></span> of 
                    <span x-text="totalItems"></span> results
                </div>
                <nav x-show="totalPages > 1">
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item" :class="{ 'disabled': currentPage === 1 }">
                            <a class="page-link" href="#" @click.prevent="goToPage(currentPage - 1)">Previous</a>
                        </li>
                        <template x-for="(page, index) in visiblePages" :key="`page-${index}`">
                            <li class="page-item" :class="{ 'active': page === currentPage, 'disabled': page === '...' }">
                                <a class="page-link" href="#" @click.prevent="page !== '...' && goToPage(page)" x-text="page"></a>
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

    <!-- Org Chart Container -->
    <div class="card" x-show="activeTab === 'org_chart'" x-cloak x-data="orgChartData()" x-init="initOrgChart()">
        <div class="card-header border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="bi bi-diagram-3-fill text-primary me-2"></i>Company Hierarchy</h5>
                <div>
                    <button class="btn btn-sm btn-outline-secondary" @click="fetchOrgChart()"><i class="bi bi-arrow-clockwise me-1"></i>Refresh</button>
                </div>
            </div>
        </div>
        <div class="card-body p-0 overflow-auto bg-body-tertiary" style="min-height: 500px;">
            <div x-show="loading" class="d-flex justify-content-center align-items-center h-100 p-5">
                <div class="spinner-border text-primary" role="status"></div>
            </div>
            
            <div x-show="!loading" class="p-4 org-tree">
                <!-- CEO / Standalone Users -->
                <div class="d-flex flex-column align-items-center mb-5" x-show="chartData.standalone_users && chartData.standalone_users.length > 0">
                    <template x-for="user in chartData.standalone_users" :key="'su-'+user.id">
                        <div class="org-node text-center mb-3">
                            <div class="card shadow-sm border-primary border-2" style="width: 250px; border-radius: 10px;">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center justify-content-center mb-2">
                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold fs-5 shadow-sm" style="width: 48px; height: 48px;">
                                            <span x-text="user.name.charAt(0)"></span>
                                        </div>
                                    </div>
                                    <h6 class="fw-bold mb-1" x-text="user.name"></h6>
                                    <p class="text-muted small mb-0" x-text="user.designation || 'No Designation'"></p>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Departments Tree -->
                <ul class="tree-wrapper d-flex justify-content-center gap-4 list-unstyled p-0 m-0">
                    <template x-for="dept in chartData.departments" :key="'dept-'+dept.id">
                        <li class="tree-node position-relative d-flex flex-column align-items-center">
                            <!-- Department Node -->
                            <div class="org-node text-center position-relative z-1">
                                <div class="card shadow-sm border-0" style="width: 260px; border-radius: 10px;">
                                    <div class="card-header bg-primary text-white py-2 px-3 border-bottom-0" style="border-radius: 10px 10px 0 0;">
                                        <h6 class="mb-0 fw-bold text-truncate" x-text="dept.name"></h6>
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center" x-show="dept.manager">
                                            <div class="rounded-circle bg-secondary bg-opacity-10 text-secondary d-flex align-items-center justify-content-center fw-bold me-2" style="width: 32px; height: 32px;">
                                                <span x-text="dept.manager ? dept.manager.name.charAt(0) : ''"></span>
                                            </div>
                                            <div class="text-start flex-grow-1 overflow-hidden">
                                                <div class="fw-semibold small text-truncate" x-text="dept.manager ? dept.manager.name : ''"></div>
                                                <div class="text-muted" style="font-size: 0.65rem;">Department Head</div>
                                            </div>
                                        </div>
                                        <div x-show="!dept.manager" class="text-muted small py-2">No Manager Assigned</div>
                                        
                                        <hr class="my-2 border-secondary border-opacity-25">
                                        
                                        <div class="d-flex justify-content-between text-muted" style="font-size: 0.7rem;">
                                            <span><i class="bi bi-people-fill me-1"></i><span x-text="dept.users_count !== undefined ? dept.users_count : 0"></span> members</span>
                                            <span><i class="bi bi-diagram-2-fill me-1"></i><span x-text="dept.children ? dept.children.length : 0"></span> sub-depts</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Recursive Child Rendering (only 1 level deep for simplicity in UI, fully nested if needed) -->
                            <template x-if="dept.children && dept.children.length > 0">
                                <ul class="tree-wrapper d-flex justify-content-center gap-3 list-unstyled p-0 m-0 mt-4 position-relative">
                                    <div class="tree-line position-absolute top-0 start-50 translate-middle-x" style="width: 2px; height: 24px; background-color: var(--bs-border-color); margin-top: -24px;"></div>
                                    <template x-for="child in dept.children" :key="'child-'+child.id">
                                        <li class="tree-node position-relative mt-4">
                                            <div class="tree-line position-absolute start-50 translate-middle-x" style="width: 2px; height: 24px; background-color: var(--bs-border-color); top: -24px;"></div>
                                            <div class="org-node text-center position-relative z-1">
                                                <div class="card shadow-sm border-0" style="width: 220px; border-radius: 10px;">
                                                    <div class="card-header bg-info text-white py-2 px-3 border-bottom-0" style="border-radius: 10px 10px 0 0;">
                                                        <h6 class="mb-0 fw-semibold text-truncate fs-6" x-text="child.name"></h6>
                                                    </div>
                                                    <div class="card-body p-2">
                                                        <div class="fw-medium small text-truncate" x-text="child.manager ? child.manager.name : 'No Head'"></div>
                                                        <div class="text-muted" style="font-size: 0.65rem;"><span x-text="child.users_count !== undefined ? child.users_count : 0"></span> members</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                    </template>
                                </ul>
                            </template>
                        </li>
                    </template>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
    /* Basic Org Tree Styling */
    .tree-wrapper > .tree-node::before {
        content: '';
        position: absolute;
        top: -24px;
        left: 50%;
        width: 100%;
        height: 2px;
        background-color: var(--bs-border-color);
        z-index: 0;
    }
    .tree-wrapper > .tree-node:first-child::before {
        left: 50%;
        width: 50%;
    }
    .tree-wrapper > .tree-node:last-child::before {
        left: 0;
        width: 50%;
    }
    .tree-wrapper > .tree-node:only-child::before {
        display: none;
    }
</style>

<!-- Add / Edit Modal -->
<div class="modal fade" id="itemModal">
    <div class="modal-dialog modal-dialog-centered" :class="{ 'modal-lg': window.Alpine.$data(document.querySelector('[x-data^=\'organizationTable\']')).activeTab === 'departments' }">
        <div class="modal-content border-0 shadow-lg rounded-4" x-data="itemForm">
            <form @submit.prevent="saveItem()">
                <div class="modal-header bg-light border-bottom-0 pb-3 pt-4 px-4">
                    <h5 class="modal-title fw-bold d-flex align-items-center">
                        <i class="bi bi-diagram-3 text-primary fs-4 me-2"></i>
                        <span x-text="editingId ? 'Edit ' + getTabTitle() : 'Add New ' + getTabTitle()"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body bg-light pt-0 px-4">
                    <div class="alert alert-danger" x-show="error" x-text="error" style="display: none;"></div>
                    
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body p-4">
                            <div class="row g-3">
                                <div :class="getTab() === 'departments' ? 'col-md-6' : 'col-12'">
                                    <label class="form-label fw-medium text-muted small">Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-sm" x-model="form.name" required>
                                </div>
                                
                                <template x-if="getTab() === 'departments'">
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium text-muted small">Department Code</label>
                                        <input type="text" class="form-control form-control-sm" x-model="form.code" placeholder="e.g. ENG-01">
                                    </div>
                                </template>
                                
                                <template x-if="getTab() === 'departments'">
                                    <div class="col-md-12">
                                        <label class="form-label fw-medium text-muted small">Department Head / Manager</label>
                                        <select class="form-select form-select-sm" x-model="form.manager_id">
                                            <option value="">None</option>
                                            <template x-for="user in users" :key="user.id">
                                                <option :value="user.id" x-text="user.name"></option>
                                            </template>
                                        </select>
                                    </div>
                                </template>

                                <template x-if="getTab() === 'departments'">
                                    <div class="col-md-12">
                                        <label class="form-label fw-medium text-muted small">Parent Department</label>
                                        <select class="form-select form-select-sm" x-model="form.parent_id">
                                            <option value="">None (Top Level)</option>
                                            <template x-for="dept in items.filter(i => i.id !== editingId)" :key="dept.id">
                                                <option :value="dept.id" x-text="dept.name"></option>
                                            </template>
                                        </select>
                                    </div>
                                </template>

                                <template x-if="getTab() === 'departments'">
                                    <div class="col-md-12">
                                        <label class="form-label fw-medium text-muted small">Description</label>
                                        <textarea class="form-control form-control-sm" x-model="form.description" rows="3"></textarea>
                                    </div>
                                </template>

                                <div class="col-12 mt-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" style="cursor: pointer;" type="checkbox" id="itemActive" x-model="form.is_active">
                                        <label class="form-check-label fw-semibold" for="itemActive">Active Status</label>
                                        <div class="form-text small text-muted">If inactive, this will be hidden from selections.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4" :disabled="saving">
                        <span x-show="saving" class="spinner-border spinner-border-sm me-2"></span>
                        <span x-text="editingId ? 'Save Changes' : 'Create'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Department Modal -->
<div class="modal fade" id="viewDepartmentModal">
    <div class="modal-dialog modal-dialog-centered modal-xl" x-data="viewDepartmentData">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-light border-bottom-0 pb-3 pt-4 px-4">
                <h5 class="modal-title d-flex align-items-center fw-bold">
                    <i class="bi bi-building text-primary fs-4 me-2"></i>
                    <span x-text="department ? department.name : 'Loading...'"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light pt-0 px-4">
                <template x-if="loading">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="text-muted mt-2">Loading details...</p>
                    </div>
                </template>
                <template x-if="!loading && department">
                    <div class="row g-4 pb-4">
                        <div class="col-md-4">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body">
                                    <h6 class="text-muted small fw-bold text-uppercase mb-3">Overview</h6>
                                    
                                    <div class="mb-3">
                                        <div class="small text-muted mb-1">Department Code</div>
                                        <div class="fw-medium" x-text="department.code || 'N/A'"></div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="small text-muted mb-1">Status</div>
                                        <span class="badge" :class="department.is_active ? 'bg-success' : 'bg-danger'" x-text="department.is_active ? 'Active' : 'Inactive'"></span>
                                    </div>
                                    <div class="mb-3">
                                        <div class="small text-muted mb-1">Description</div>
                                        <div class="small" x-text="department.description || 'No description provided.'"></div>
                                    </div>
                                    <hr>
                                    <div class="mb-3">
                                        <div class="small text-muted mb-1">Department Head</div>
                                        <div class="d-flex align-items-center mt-2" x-show="department.manager">
                                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                                <i class="bi bi-person-fill fs-5"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold" x-text="department.manager ? department.manager.name : ''"></div>
                                                <div class="small text-muted" x-text="department.manager ? (department.manager.designation || 'Manager') : ''"></div>
                                            </div>
                                        </div>
                                        <div x-show="!department.manager" class="text-muted small font-italic">No manager assigned</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body">
                                    <h6 class="text-muted small fw-bold text-uppercase mb-3 d-flex justify-content-between align-items-center">
                                        <span>Team Members</span>
                                        <span class="badge bg-primary rounded-pill" x-text="department.users ? department.users.length : 0"></span>
                                    </h6>
                                    
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Designation</th>
                                                    <th>Employment Type</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <template x-if="!department.users || department.users.length === 0">
                                                    <tr><td colspan="4" class="text-center py-4 text-muted">No members assigned to this department.</td></tr>
                                                </template>
                                                <template x-for="user in department.users" :key="user.id">
                                                    <tr>
                                                        <td>
                                                            <div class="fw-medium" x-text="user.name"></div>
                                                            <div class="small text-muted" x-text="user.email"></div>
                                                        </td>
                                                        <td>
                                                            <span x-text="user.designation || '—'"></span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-info bg-opacity-10 text-info border border-info-subtle" x-text="user.employment_type || '—'"></span>
                                                        </td>
                                                        <td>
                                                            <span class="badge" :class="user.is_active ? 'bg-success' : 'bg-danger'" x-text="user.is_active ? 'Active' : 'Inactive'"></span>
                                                        </td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    async function apiFetch(url, options = {}) {
        const { headers, ...rest } = options;
        const response = await fetch(url, {
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content ?? "",
                ...(headers || {})
            },
            ...rest
        });
        const text = await response.text();
        const data = text ? JSON.parse(text) : {};
        if (!response.ok) {
            const error = data?.errors ? Object.values(data.errors).flat().join(" ") : "";
            throw new Error(error || data?.message || data?.error || "Request failed");
        }
        return data;
    }

    function showToast(message, type = "success") {
        const container = document.getElementById("toast-container") || document.createElement("div");
        if (!document.getElementById("toast-container")) {
            container.id = "toast-container";
            container.className = "toast-container position-fixed top-0 end-0 p-3";
            document.body.appendChild(container);
        }
        const toast = document.createElement("div");
        toast.className = `toast align-items-center text-bg-${type} border-0 show mb-2`;
        toast.setAttribute("role", "alert");
        toast.innerHTML = `
        <div class="d-flex">
          <div class="toast-body">
            <i class="bi ${
                type === 'success' ? 'bi-check-circle-fill' : 
                type === 'danger' ? 'bi-x-circle-fill' : 
                type === 'warning' ? 'bi-exclamation-triangle-fill' : 
                'bi-info-circle-fill'
            } me-2"></i><span></span>
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>`;
        toast.querySelector(".toast-body span").textContent = message;
        container.appendChild(toast);
        setTimeout(() => toast.remove(), 5000);
    }

    document.addEventListener('alpine:init', () => {
        Alpine.data('organizationTable', () => ({
            activeTab: 'departments',
            items: [],
            searchQuery: '',
            statusFilter: '',
            itemsPerPage: 15,
            currentPage: 1,
            sortField: 'created_at',
            sortDirection: 'desc',
            selectedItems: [],
            isLoading: false,
            modalInstance: null,

            init() {
                this.modalInstance = new bootstrap.Modal(document.getElementById('itemModal'));
                this.fetchItems();
                
                window.addEventListener('item-saved', () => {
                    this.fetchItems();
                    if(this.modalInstance) this.modalInstance.hide();
                });
            },

            get tabTitle() {
                const map = {
                    departments: 'Departments',
                    designations: 'Designations',
                    employment_types: 'Employment Types'
                };
                return map[this.activeTab] || 'Records';
            },

            get tabTitleSingular() {
                const map = {
                    departments: 'Department',
                    designations: 'Designation',
                    employment_types: 'Employment Type'
                };
                return map[this.activeTab] || 'Record';
            },

            get stats() {
                return {
                    total: this.items.length,
                    active: this.items.filter(r => r.is_active).length,
                    inactive: this.items.filter(r => !r.is_active).length
                };
            },

            get filteredItems() {
                let r = this.items;
                if (this.searchQuery) {
                    const q = this.searchQuery.toLowerCase();
                    r = r.filter(i => (i.name && i.name.toLowerCase().includes(q)) || String(i.id).includes(q));
                }
                if (this.statusFilter === 'active') {
                    r = r.filter(i => i.is_active);
                } else if (this.statusFilter === 'inactive') {
                    r = r.filter(i => !i.is_active);
                }
                r.sort((a, b) => {
                    let s = a[this.sortField], i = b[this.sortField];
                    if (typeof s === 'string') s = s.toLowerCase();
                    if (typeof i === 'string') i = i.toLowerCase();
                    if (s < i) return this.sortDirection === 'asc' ? -1 : 1;
                    if (s > i) return this.sortDirection === 'asc' ? 1 : -1;
                    return 0;
                });
                return r;
            },

            get paginatedItems() {
                const start = (this.currentPage - 1) * this.itemsPerPage;
                return this.filteredItems.slice(start, start + this.itemsPerPage);
            },

            get totalItems() {
                return this.filteredItems.length;
            },

            get totalPages() {
                return Math.ceil(this.totalItems / this.itemsPerPage) || 1;
            },

            get visiblePages() {
                if (this.totalPages <= 1) return [1];
                const p = [1];
                if (this.totalPages <= 7) {
                    for (let i = 2; i <= this.totalPages; i++) p.push(i);
                } else {
                    if (this.currentPage > 3) p.push('...');
                    const start = Math.max(2, this.currentPage - 1);
                    const end = Math.min(this.totalPages - 1, this.currentPage + 1);
                    for (let i = start; i <= end; i++) p.push(i);
                    if (this.currentPage < this.totalPages - 2) p.push('...');
                    p.push(this.totalPages);
                }
                return p;
            },

            goToPage(p) {
                if (p >= 1 && p <= this.totalPages) this.currentPage = p;
            },

            sortBy(field) {
                if (this.sortField === field) {
                    this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
                } else {
                    this.sortField = field;
                    this.sortDirection = 'asc';
                }
                this.currentPage = 1;
            },

            switchTab(tab) {
                this.activeTab = tab;
                this.searchQuery = '';
                this.statusFilter = '';
                this.selectedItems = [];
                this.currentPage = 1;
                if (tab === 'org_chart') {
                    window.dispatchEvent(new CustomEvent('load-org-chart'));
                } else {
                    this.fetchItems();
                }
            },

            filterItems() {
                this.currentPage = 1;
                this.selectedItems = [];
            },

            toggleAll(checked) {
                this.selectedItems = checked ? this.paginatedItems.map(r => String(r.id)) : [];
            },

            async fetchItems() {
                this.isLoading = true;
                this.items = [];
                try {
                    let url = this.activeTab === 'departments' 
                        ? `/api/departments?per_page=1000` // Fetch all for clientside filtering
                        : `/api/hr-settings/${this.activeTab}`;
                        
                    const res = await apiFetch(url);
                    if (this.activeTab === 'departments' && res.data) {
                        this.items = res.data;
                    } else if (res.data) {
                        this.items = res.data;
                    }
                } catch (e) {
                    showToast(e.message || "Failed to load records.", "danger");
                } finally {
                    this.isLoading = false;
                }
            },

            openCreateModal() {
                window.dispatchEvent(new CustomEvent('open-item-modal', { detail: null }));
                this.modalInstance.show();
            },

            openEditModal(item) {
                window.dispatchEvent(new CustomEvent('open-item-modal', { detail: item }));
                this.modalInstance.show();
            },

            viewDepartment(item) {
                window.dispatchEvent(new CustomEvent('open-view-department', { detail: item.id }));
                new bootstrap.Modal(document.getElementById('viewDepartmentModal')).show();
            },

            async toggleActive(item) {
                try {
                    let url = this.activeTab === 'departments' 
                        ? `/api/departments/${item.id}` 
                        : `/api/hr-settings/${this.activeTab}/${item.id}/toggle`;
                        
                    if (this.activeTab === 'departments') {
                        // Department Controller uses PUT for update
                        const res = await apiFetch(url, { 
                            method: "PUT",
                            body: JSON.stringify({ is_active: !item.is_active })
                        });
                        item.is_active = res.data.is_active;
                    } else {
                        // HR Settings controller uses PATCH for toggle
                        const res = await apiFetch(url, { method: "PATCH" });
                        item.is_active = res.is_active;
                    }
                    showToast("Status toggled.");
                } catch (e) {
                    showToast(e.message || "Failed to toggle status.", "danger");
                    item.is_active = !item.is_active;
                }
            },

            async deleteItem(item) {
                const result = await Swal.fire({
                    title: 'Delete Record?',
                    text: `Are you sure you want to delete ${item.name}?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: 'Yes, delete it'
                });
                
                if (!result.isConfirmed) return;
                
                try {
                    let url = this.activeTab === 'departments' 
                        ? `/api/departments/${item.id}` 
                        : `/api/hr-settings/${this.activeTab}/${item.id}`;
                        
                    await apiFetch(url, { method: "DELETE" });
                    Swal.fire({ icon: 'success', title: 'Deleted!', text: 'Record has been deleted.', timer: 2000, showConfirmButton: false });
                    this.fetchItems();
                } catch(e) {
                    Swal.fire({ icon: 'error', title: 'Error', text: e.message || 'Failed to delete.' });
                }
            },

            async bulkAction(action) {
                if (this.selectedItems.length === 0) return;
                const result = await Swal.fire({
                    title: 'Bulk Action',
                    text: `Are you sure you want to ${action} ${this.selectedItems.length} records?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: action === 'delete' ? '#dc3545' : '#0d6efd',
                    confirmButtonText: 'Yes, proceed'
                });
                if (!result.isConfirmed) return;
                
                let success = 0, fail = 0;
                for (const id of this.selectedItems) {
                    try {
                        let url = this.activeTab === 'departments' 
                            ? `/api/departments/${id}` 
                            : `/api/hr-settings/${this.activeTab}/${id}`;
                            
                        if (action === "delete") {
                            await apiFetch(url, { method: "DELETE" });
                        } else {
                            const r = this.items.find(e => String(e.id) === id);
                            if (!r || (action === "activate" && r.is_active) || (action === "deactivate" && !r.is_active)) continue;
                            
                            if (this.activeTab === 'departments') {
                                await apiFetch(url, { method: "PUT", body: JSON.stringify({ is_active: !r.is_active }) });
                            } else {
                                await apiFetch(`${url}/toggle`, { method: "PATCH" });
                            }
                        }
                        success++;
                    } catch (e) {
                        fail++;
                    }
                }
                showToast(`Bulk action complete. Success: ${success}, Fail: ${fail}.`, fail > 0 ? "warning" : "success");
                this.selectedItems = [];
                this.fetchItems();
            }
        }));
        
        Alpine.data('itemForm', () => ({
            editingId: null,
            saving: false,
            error: null,
            users: [],
            form: {
                name: '',
                code: '',
                description: '',
                manager_id: '',
                parent_id: '',
                is_active: true
            },
            
            init() {
                this.loadUsers();
                window.addEventListener('open-item-modal', (e) => {
                    this.error = null;
                    if (e.detail) {
                        this.editingId = e.detail.id;
                        this.form.name = e.detail.name;
                        this.form.code = e.detail.code || '';
                        this.form.description = e.detail.description || '';
                        this.form.manager_id = e.detail.manager_id || '';
                        this.form.parent_id = e.detail.parent_id || '';
                        this.form.is_active = e.detail.is_active === undefined ? true : !!e.detail.is_active;
                    } else {
                        this.editingId = null;
                        this.form.name = '';
                        this.form.code = '';
                        this.form.description = '';
                        this.form.manager_id = '';
                        this.form.parent_id = '';
                        this.form.is_active = true;
                    }
                });
            },

            getTab() {
                return window.Alpine.$data(document.querySelector('[x-data^="organizationTable"]')).activeTab;
            },

            getTabTitle() {
                return window.Alpine.$data(document.querySelector('[x-data^="organizationTable"]')).tabTitleSingular;
            },
            
            async loadUsers() {
                try {
                    const res = await apiFetch('/api/users?per_page=1000');
                    if (res.data) this.users = res.data;
                } catch(e) {}
            },
            
            async saveItem() {
                this.saving = true;
                this.error = null;
                const tab = this.getTab();
                
                try {
                    const method = this.editingId ? 'PUT' : 'POST';
                    const url = this.editingId 
                        ? (tab === 'departments' ? `/api/departments/${this.editingId}` : `/api/hr-settings/${tab}/${this.editingId}`) 
                        : (tab === 'departments' ? '/api/departments' : `/api/hr-settings/${tab}`);
                    
                    const payload = { ...this.form };
                    if (tab !== 'departments') {
                        delete payload.code;
                        delete payload.description;
                        delete payload.manager_id;
                        delete payload.parent_id;
                    }

                    await apiFetch(url, {
                        method: method,
                        body: JSON.stringify(payload)
                    });
                    
                    window.dispatchEvent(new CustomEvent('item-saved'));
                    showToast(`Saved successfully.`);
                } catch(e) {
                    this.error = e.message || 'Error saving record.';
                }
                
                this.saving = false;
            }
        }));

        Alpine.data('viewDepartmentData', () => ({
            department: null,
            loading: false,
            
            init() {
                window.addEventListener('open-view-department', (e) => {
                    this.fetchDepartment(e.detail);
                });
            },
            
            async fetchDepartment(id) {
                this.loading = true;
                this.department = null;
                try {
                    const res = await apiFetch(`/api/departments/${id}`);
                    if (res.data) {
                        this.department = res.data;
                    }
                } catch (e) {
                    showToast(e.message || 'Failed to load department details.', "danger");
                }
                this.loading = false;
            }
        }));

        Alpine.data('orgChartData', () => ({
            chartData: { departments: [], standalone_users: [] },
            loading: false,

            initOrgChart() {
                window.addEventListener('load-org-chart', () => {
                    if (this.chartData.departments.length === 0) {
                        this.fetchOrgChart();
                    }
                });
            },

            async fetchOrgChart() {
                this.loading = true;
                try {
                    const res = await apiFetch('/api/org-chart');
                    this.chartData = res;
                } catch (e) {
                    showToast(e.message || 'Failed to load org chart.', 'danger');
                }
                this.loading = false;
            }
        }));
    });
</script>
@endpush
@endsection
