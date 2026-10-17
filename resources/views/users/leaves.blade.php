@extends('layouts.app')

@section('title', 'Leave Management')
@section('page', 'leaves')

@push('head')
<style>
.leave-workspace-nav { overflow-x: auto; }
.leave-workspace-nav .btn { flex-shrink: 0; white-space: nowrap; }
.leave-workspace .table-responsive > table { min-width: 760px; }
.leave-workspace .bulk-actions-bar > .d-flex { flex-wrap: wrap; gap: 1rem; }
.leave-workspace .bulk-actions-bar .d-flex { flex-wrap: wrap; }
.leave-workspace .report-summary { background: var(--bs-tertiary-bg); }
</style>
@endpush

@section('content')
@php
    $isGlobalHr = auth()->user()->hasRole(['Super Admin', 'Admin']) || auth()->user()->can('view-all-data');
@endphp
<div class="user-management leave-workspace" x-data="{ currentTab: new URLSearchParams(window.location.search).has('user_id') ? 'requests' : (['requests', 'history', 'balances', 'holidays'].includes(sessionStorage.getItem('leavesTab')) ? sessionStorage.getItem('leavesTab') : 'requests') }" x-init="$watch('currentTab', val => { sessionStorage.setItem('leavesTab', val); window.dispatchEvent(new CustomEvent('refresh-tab-' + val)); })">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <span class="text-primary small fw-semibold">TIME &amp; LEAVE</span><h1 class="h3 mt-1 mb-2">Leave workspace</h1>
            <p class="text-muted mb-0">Manage employee leave requests, approvals, balances, and holidays</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ url('/attendances') }}" class="btn btn-outline-secondary">Attendance workspace</a>
            @can('leave-create')
            <button type="button" class="btn btn-primary" x-show="currentTab === 'requests'" @click="$dispatch('open-leave-modal')">
                <i class="bi bi-plus-circle me-2"></i>Request Leave
            </button>
            <button type="button" class="btn btn-primary" x-show="currentTab === 'balances'" style="display: none;" @click="$dispatch('open-balance-modal')">
                <i class="bi bi-plus-circle me-2"></i>Add Leave Balance
            </button>
            <button type="button" class="btn btn-primary" x-show="currentTab === 'holidays'" style="display: none;" @click="$dispatch('open-holiday-modal')">
                <i class="bi bi-plus-circle me-2"></i>Add Holiday
            </button>
            @endcan
        </div>
    </div>

    <nav class="leave-workspace-nav d-flex gap-2 mb-4 p-2 border rounded-3 bg-body" aria-label="Leave workspace sections">
        <button type="button" class="btn" :class="currentTab === 'requests' ? 'btn-primary' : 'btn-light'" :aria-current="currentTab === 'requests' ? 'page' : null" @click="currentTab = 'requests'">Pending Requests</button>
        <button type="button" class="btn" :class="currentTab === 'history' ? 'btn-primary' : 'btn-light'" :aria-current="currentTab === 'history' ? 'page' : null" @click="currentTab = 'history'">History</button>
        <button type="button" class="btn" :class="currentTab === 'balances' ? 'btn-primary' : 'btn-light'" :aria-current="currentTab === 'balances' ? 'page' : null" @click="currentTab = 'balances'">Leave Balances</button>
        <button type="button" class="btn" :class="currentTab === 'holidays' ? 'btn-primary' : 'btn-light'" :aria-current="currentTab === 'holidays' ? 'page' : null" @click="currentTab = 'holidays'">Holidays</button>
    </nav>

    <!-- PENDING REQUESTS TAB -->
    <div x-show="currentTab === 'requests'" x-data="leavesTable('pending')">
        @include('users.partials.leave_table', ['title' => 'Pending Requests', 'statusFilter' => 'Pending'])
    </div>

    <!-- HISTORY TAB -->
    <div x-show="currentTab === 'history'" style="display: none;" x-data="leavesTable('history')">
        @include('users.partials.leave_table', ['title' => 'Leave History', 'statusFilter' => 'History'])
    </div>

    <!-- BALANCES TAB -->
    <div x-show="currentTab === 'balances'" style="display: none;" x-data="balancesTable">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center g-3">
                    <div class="col">
                        <h2 class="h5 card-title mb-1">Leave Balances</h2><p class="small text-muted mb-0">Review available, allocated, and used leave for employees you can access.</p>
                    </div>
                    <div class="col-auto d-flex flex-wrap gap-2">
                        <button class="btn btn-sm btn-outline-secondary" type="button" @click="loadItems()" :disabled="isLoading" title="Refresh">
                            <i class="bi bi-arrow-clockwise"></i> Refresh
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <!-- Bulk Actions -->
                <div class="bulk-actions-bar p-3 bg-primary bg-opacity-10 border-bottom border-primary border-opacity-25"
                     x-show="selectedItems.length > 0" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-check-circle-fill text-primary me-2"></i>
                            <span class="fw-medium text-primary">
                                <span x-text="selectedItems.length"></span> record(s) selected
                            </span>
                        </div>
                        <div class="d-flex gap-2">
                            @can('leave-delete')
                            <button class="btn btn-sm btn-danger" @click="bulkAction('delete')">
                                <i class="bi bi-trash me-1"></i>Delete
                            </button>
                            @endcan
                            <button class="btn btn-sm btn-outline-secondary px-2" @click="selectedItems = []" title="Clear selection">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;" class="ps-3">
                                    <input type="checkbox" class="form-check-input"
                                           @change="toggleAll($event.target.checked)"
                                           :checked="items.length > 0 && items.every(i => selectedItems.includes(String(i.id)))">
                                </th>
                                <th>Employee</th>
                                <th>Type</th>
                                <th>Total Leaves</th>
                                <th>Used Leaves</th>
                                <th>Balance</th>
                                <th style="width: 100px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="isLoading">
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                                    </td>
                                </tr>
                            </template>
                            <template x-if="!isLoading && items.length === 0">
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No balances found.</td>
                                </tr>
                            </template>
                            <template x-for="item in items" :key="item.id">
                                <tr x-show="!isLoading">
                                    <td class="ps-3">
                                        <input type="checkbox" class="form-check-input" :value="String(item.id)" x-model="selectedItems">
                                    </td>
                                    <td class="fw-medium" x-text="item.user ? item.user.name : '—'"></td>
                                    <td><span class="badge bg-secondary" x-text="item.leave_type"></span></td>
                                    <td x-text="item.total_leaves"></td>
                                    <td x-text="item.used_leaves"></td>
                                    <td class="fw-bold" :class="item.balance < 0 ? 'text-danger' : 'text-success'" x-text="item.balance"></td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></button>
                                            <ul class="dropdown-menu">
                                                @can('leave-edit')
                                                <li><a class="dropdown-item" href="#" @click.prevent="editItem(item)"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                @endcan
                                                @can('leave-delete')
                                                <li><a class="dropdown-item text-danger" href="#" @click.prevent="deleteItem(item.id)"><i class="bi bi-trash me-2"></i>Delete</a></li>
                                                @endcan
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- HOLIDAYS TAB -->
    <div x-show="currentTab === 'holidays'" style="display: none;" x-data="holidaysTable">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center g-3">
                    <div class="col">
                        <h2 class="h5 card-title mb-1">Holidays</h2><p class="small text-muted mb-0">Maintain the holiday calendar shown in attendance and leave views.</p>
                    </div>
                    <div class="col-auto d-flex flex-wrap gap-2">
                        <button class="btn btn-sm btn-outline-secondary" type="button" @click="loadItems()" :disabled="isLoading" title="Refresh">
                            <i class="bi bi-arrow-clockwise"></i> Refresh
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Name</th>
                                <th>Date</th>
                                <th>Type</th>
                                <th style="width: 100px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="isLoading">
                                <tr>
                                    <td colspan="4" class="text-center py-4">
                                        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                                    </td>
                                </tr>
                            </template>
                            <template x-if="!isLoading && items.length === 0">
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">No holidays found.</td>
                                </tr>
                            </template>
                            <template x-for="item in items" :key="item.id">
                                <tr x-show="!isLoading">
                                    <td class="ps-3 fw-medium" x-text="item.name"></td>
                                    <td x-text="new Date(item.date.split('T')[0] + 'T00:00:00').toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })"></td>
                                    <td><span class="badge bg-info" x-text="item.type"></span></td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></button>
                                            <ul class="dropdown-menu">
                                                @can('leave-edit')
                                                <li><a class="dropdown-item" href="#" @click.prevent="editItem(item)"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                @endcan
                                                @can('leave-delete')
                                                <li><a class="dropdown-item text-danger" href="#" @click.prevent="deleteItem(item.id)"><i class="bi bi-trash me-2"></i>Delete</a></li>
                                                @endcan
                                            </ul>
                                        </div>
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

<!-- Leave Modal -->
<div class="modal fade" id="leaveModal" tabindex="-1" aria-labelledby="leaveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" x-data="leaveForm">
        <form class="modal-content" @submit.prevent="saveItem()">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center" id="leaveModalLabel">
                    <i class="bi bi-calendar-minus-fill text-primary me-2"></i>
                    <span x-text="editingId ? 'Edit Leave Request' : 'Request Leave'"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" x-show="error" x-text="error" style="display: none;"></div>

                <div class="mb-3">
<label class="form-label" for="leaveUser">Employee <span class="text-danger">*</span></label>
<x-searchable-select model="form.user_id" label="Leave employee">
<select x-ref="native" aria-hidden="true" tabindex="-1" @focus="$refs.trigger.focus()" @invalid.prevent="validationMessage = $event.target.validationMessage; $refs.trigger.focus()" class="visually-hidden" id="leaveUser" x-model="form.user_id" required @disabled(!$isGlobalHr)>
                        <option value="">Select Employee</option>
                        <template x-for="user in users" :key="user.id">
                            <option :value="user.id" :selected="String(user.id) === String(form.user_id)" x-text="user.name"></option>
                        </template>
                    </select>
</x-searchable-select>
</div>

                <div class="mb-3">
<label class="form-label" for="leaveType">Leave Type <span class="text-danger">*</span></label>
                    <div class="form-text text-warning" x-show="form.user_id && userBalances.length === 0 && !isLoadingBalances">
                        No active leave balances found for this employee.
                    </div>
                </div>

                <div class="row">
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <input type="date" class="form-control" id="leaveStart" x-model="form.start_date" required>
                            <label for="leaveStart">Start Date <span class="text-danger">*</span></label>
<x-searchable-select model="form.leave_type" label="Leave type">
<select x-ref="native" aria-hidden="true" tabindex="-1" @focus="$refs.trigger.focus()" @invalid.prevent="validationMessage = $event.target.validationMessage; $refs.trigger.focus()" class="visually-hidden" id="leaveType" x-model="form.leave_type" required :disabled="isLoadingBalances || userBalances.length === 0">
                        <option value="">Select Leave Type</option>
                        <template x-for="balance in userBalances" :key="balance.id">
                            <option :value="balance.leave_type"
                                    x-text="`${balance.leave_type} Leave (Balance: ${balance.balance})`"
                                    :disabled="balance.balance <= 0 && balance.leave_type !== originalLeaveType"></option>
                        </template>
                    </select>
</x-searchable-select>
</div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <input type="date" class="form-control" id="leaveEnd" x-model="form.end_date" :min="form.start_date" required>
                            <label for="leaveEnd">End Date <span class="text-danger">*</span></label>
                        </div>
                    </div>
                </div>

                <div class="form-floating mb-3">
                    <textarea class="form-control" id="leaveReason" x-model="form.reason" style="height: 100px;" required></textarea>
                    <label for="leaveReason">Reason <span class="text-danger">*</span></label>
                </div>

                <div class="mb-3" x-show="editingId" @if(!$isGlobalHr) style="display: none !important;" @endif>
<label class="form-label" for="leaveStatus">Status</label>
<x-searchable-select model="form.status" label="Leave status">
<select x-ref="native" aria-hidden="true" tabindex="-1" @focus="$refs.trigger.focus()" @invalid.prevent="validationMessage = $event.target.validationMessage; $refs.trigger.focus()" class="visually-hidden" id="leaveStatus" x-model="form.status">
                        <option value="Pending">Pending</option>
                        <option value="Approved">Approved</option>
                        <option value="Rejected">Rejected</option>
                    </select>
</x-searchable-select>
</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary px-4" :disabled="saving">
                    <span x-show="saving" class="spinner-border spinner-border-sm me-2"></span>
                    <span x-text="editingId ? 'Save Changes' : 'Submit Request'"></span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Balance Modal -->
<div class="modal fade" id="balanceModal" tabindex="-1" aria-labelledby="balanceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" x-data="balanceForm">
        <form class="modal-content" @submit.prevent="saveItem()">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center" id="balanceModalLabel">
                    <i class="bi bi-wallet2 text-primary me-2"></i>
                    <span x-text="editingId ? 'Edit Balance' : 'Add Balance'"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" x-show="error" x-text="error" style="display: none;"></div>

                <div class="mb-3">
<label class="form-label" for="balanceEmployee">Employee <span class="text-danger">*</span></label>
<x-searchable-select model="form.user_id" label="Balance employee">
<select x-ref="native" aria-hidden="true" tabindex="-1" @focus="$refs.trigger.focus()" @invalid.prevent="validationMessage = $event.target.validationMessage; $refs.trigger.focus()" class="visually-hidden" id="balanceEmployee" x-model="form.user_id" required :disabled="editingId">
                        <option value="">Select Employee</option>
                        <template x-for="user in users" :key="user.id">
                            <option :value="user.id" :selected="String(user.id) === String(form.user_id)" x-text="user.name"></option>
                        </template>
                    </select>
</x-searchable-select>
</div>

                <div class="mb-3">
<label class="form-label" for="balanceType">Leave Type <span class="text-danger">*</span></label>
<x-searchable-select model="form.leave_type" label="Balance leave type">
<select x-ref="native" aria-hidden="true" tabindex="-1" @focus="$refs.trigger.focus()" @invalid.prevent="validationMessage = $event.target.validationMessage; $refs.trigger.focus()" class="visually-hidden" id="balanceType" x-model="form.leave_type" required :disabled="editingId">
                        <template x-if="editingId && !['Sick', 'Casual', 'Annual', 'Maternity', 'Paternity', 'Unpaid', 'CO'].includes(form.leave_type)">
                            <option :value="form.leave_type" selected x-text="form.leave_type + ' Leave'"></option>
                        </template>
                        <option value="Sick">Sick Leave</option>
                        <option value="Casual">Casual Leave</option>
                        <option value="Annual">Annual Leave</option>
                        <option value="Maternity">Maternity Leave</option>
                        <option value="Paternity">Paternity Leave</option>
                        <option value="Unpaid">Unpaid Leave</option>
                        <option value="CO">Compensatory Off (CO)</option>
                    </select>
</x-searchable-select>
</div>

                <div class="row">
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <input type="number" step="0.5" class="form-control" id="balanceTotal" x-model="form.total_leaves" required min="0">
                            <label for="balanceTotal">Total Leaves <span class="text-danger">*</span></label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <input type="number" step="0.5" class="form-control" id="balanceUsed" x-model="form.used_leaves" required min="0">
                            <label for="balanceUsed">Used Leaves <span class="text-danger">*</span></label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary px-4" :disabled="saving">
                    <span x-show="saving" class="spinner-border spinner-border-sm me-2"></span> Save
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Holiday Modal -->
<div class="modal fade" id="holidayModal" tabindex="-1" aria-labelledby="holidayModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" x-data="holidayForm">
        <form class="modal-content" @submit.prevent="saveItem()">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center" id="holidayModalLabel">
                    <i class="bi bi-calendar-event text-primary me-2"></i>
                    <span x-text="editingId ? 'Edit Holiday' : 'Add Holiday'"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" x-show="error" x-text="error" style="display: none;"></div>

                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="holidayName" x-model="form.name" required>
                    <label for="holidayName">Holiday Name <span class="text-danger">*</span></label>
                </div>

                <div class="form-floating mb-3">
                    <input type="date" class="form-control" id="holidayDate" x-model="form.date" required>
                    <label for="holidayDate">Date <span class="text-danger">*</span></label>
                </div>

                <div class="mb-3">
<label class="form-label" for="holidayType">Type <span class="text-danger">*</span></label>
<x-searchable-select model="form.type" label="Holiday type">
<select x-ref="native" aria-hidden="true" tabindex="-1" @focus="$refs.trigger.focus()" @invalid.prevent="validationMessage = $event.target.validationMessage; $refs.trigger.focus()" class="visually-hidden" id="holidayType" x-model="form.type" required>
                        <option value="National">National</option>
                        <option value="Optional">Optional</option>
                        <option value="Company">Company</option>
                    </select>
</x-searchable-select>
</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary px-4" :disabled="saving">
                    <span x-show="saving" class="spinner-border spinner-border-sm me-2"></span> Save
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    async function hrFetch(url, options = {}) {
        const response = await fetch(url, { ...options, headers: { Accept: 'application/json', ...(options.headers || {}) } });
        if (response.redirected || response.status === 401 || response.status === 419) {
            throw new Error('Your session has expired. Reload the page and sign in again.');
        }
        if (!response.ok) {
            const data = await response.json().catch(() => ({}));
            throw new Error(data.errors ? Object.values(data.errors).flat().join(' ') : data.message || 'Request failed. Please retry.');
        }
        return response;
    }
    async function hrAll(url) {
        const items = [];
        let page = 1, lastPage = 1;
        do {
            const separator = url.includes('?') ? '&' : '?';
            const response = await hrFetch(`${url}${separator}per_page=100&page=${page}`);
            const data = await response.json();
            items.push(...(data.data || []));
            lastPage = data.last_page || 1;
            page++;
        } while (page <= lastPage);
        return items;
    }
    async function hrUsers() {
        try { return await hrAll('/api/users'); }
        catch { return [@json(['id' => auth()->id(), 'name' => auth()->user()->name])]; }
    }
    function hrFailure(error) {
        window.Swal.fire({ icon: 'error', title: 'Unable to complete request', text: error.message || 'Please retry.' });
    }
    async function hrConfirmDelete() {
        return (await window.Swal.fire({ title: 'Delete this record?', text: 'This action cannot be undone.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete', focusCancel: true })).isConfirmed;
    }
    document.addEventListener('alpine:init', () => {
        let leaveModalInstance = null;
        let balanceModalInstance = null;
        let holidayModalInstance = null;

        Alpine.data('leavesTable', (mode) => ({
            items: [],
            selectedItems: [],
            isLoading: true,
            _requestId: 0,
            currentPage: 1,
            totalPages: 1,
            totalItems: 0,
            perPage: 15,
            mode: mode,
            search: '', employeeFilter: /^\d+$/.test(new URLSearchParams(window.location.search).get('user_id') || '') ? new URLSearchParams(window.location.search).get('user_id') : '', typeFilter: '', statusFilter: '',
            fromDate: '', toDate: '', directTeam: false,
            employeeOptions: [], leaveTypes: [], viewingItem: null,
            summary: { requests: 0, requested_days: 0, pending: 0, approved: 0, rejected: 0 },

            init() {
                this.loadItems();
                if (!leaveModalInstance) {
                    leaveModalInstance = bootstrap.Modal.getOrCreateInstance(document.getElementById('leaveModal'));
                }

                window.addEventListener('leave-saved', () => {
                    this.loadItems();
                    if(leaveModalInstance) leaveModalInstance.hide();
                });


                window.addEventListener('refresh-tab-' + (this.mode === 'pending' ? 'requests' : 'history'), () => {
                    this.loadItems();
                });
            },

            async loadItems() {
                const requestId = this._requestId = (this._requestId || 0) + 1;
                this.isLoading = true;
                try {
                    const url = '/api/leaves?' + this.reportParams();

                    const res = await hrFetch(url);
                    if (res.ok) {
                        const json = await res.json();
                        let data = json.data || [];

                        if (requestId !== this._requestId) return;
                        if (json.total > 0 && this.currentPage > json.last_page) { this.currentPage = json.last_page; return this.loadItems(); }
                        this.summary = json.summary || this.summary;
                        this.employeeOptions = json.filter_options?.employees || [];
                        this.leaveTypes = json.filter_options?.leave_types || [];
                        this.items = data;
                        this.selectedItems = [];
                        this.currentPage = json.current_page || 1;
                        this.totalPages = json.last_page || 1;
                        this.totalItems = json.total || 0;
                    }
                } catch(e) {
                    if (requestId === this._requestId) hrFailure(e);
                }
                if (requestId === this._requestId) this.isLoading = false;
            },

            reportParams() {
                const params = new URLSearchParams({ page: this.currentPage, per_page: this.perPage });
                if (this.mode === 'pending') params.set('status', 'Pending');
                else { params.set('history', '1'); if (this.statusFilter) params.set('status', this.statusFilter); }
                if (this.search.trim()) params.set('search', this.search.trim());
                if (this.employeeFilter) params.set('user_id', this.employeeFilter);
                if (this.typeFilter) params.set('leave_type', this.typeFilter);
                if (this.fromDate) params.set('from_date', this.fromDate);
                if (this.toDate) params.set('to_date', this.toDate);
                if (this.directTeam) params.set('team', 'direct');
                return params;
            },
            filterItems() { this.currentPage = 1; this.selectedItems = []; this.loadItems(); },
            resetFilters() {
                this.search = ''; this.employeeFilter = ''; this.typeFilter = ''; this.statusFilter = '';
                this.fromDate = ''; this.toDate = ''; this.directTeam = false; this.filterItems();
            },
            exportReport() { window.open('/api/leaves/export?' + this.reportParams(), '_blank'); },
            viewItem(item) {
                this.viewingItem = item;
                bootstrap.Modal.getOrCreateInstance(document.getElementById('leaveDetails-' + this.mode)).show();
            },
            canReview(item) { return item.status === 'Pending' && Boolean(item.can_review); },
            get canReviewSelection() {
                const selected = this.items.filter(item => this.selectedItems.includes(String(item.id)));
                return selected.length > 0 && selected.every(item => this.canReview(item));
            },
            get canDeleteSelection() {
                const selected = this.items.filter(item => this.selectedItems.includes(String(item.id)));
                return selected.length > 0 && selected.every(item => {{ $isGlobalHr ? 'true' : 'false' }} || String(item.user_id) === '{{ auth()->id() }}');
            },
            async reviewItem(item, status) {
                if (!this.canReview(item)) return;
                const result = await window.Swal.fire({ title: status === 'Approved' ? 'Approve leave?' : 'Reject leave?', text: item.user?.name || 'Employee leave request', icon: 'question', showCancelButton: true });
                if (!result.isConfirmed) return;
                try {
                    await hrFetch(`/api/leaves/${item.id}/status`, {
                        method: 'PATCH', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        body: JSON.stringify({ status })
                    });
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('leaveDetails-' + this.mode)).hide();
                    window.dispatchEvent(new CustomEvent('leave-saved'));
                } catch (error) { hrFailure(error); }
            },

            goToPage(page) {
                if (page >= 1 && page <= this.totalPages) {
                    this.currentPage = page;
                    this.loadItems();
                }
            },

            get visiblePages() {
                const pages = [1];
                for (let page = Math.max(2, this.currentPage - 2); page <= Math.min(this.totalPages - 1, this.currentPage + 2); page++) pages.push(page);
                if (pages[1] > 2) pages.splice(1, 0, '...');
                if (this.totalPages > 1) { if (pages[pages.length - 1] < this.totalPages - 1) pages.push('...'); pages.push(this.totalPages); }
                return pages;
            },

            get pageFrom() {
                return this.totalItems === 0 ? 0 : (this.currentPage - 1) * this.perPage + 1;
            },

            get pageTo() {
                return Math.min(this.currentPage * this.perPage, this.totalItems);
            },

            canManage(item) {
                return item.status === 'Pending' && (this.canReview(item) || {{ $isGlobalHr ? 'true' : 'false' }} || String(item.user_id) === '{{ auth()->id() }}');
            },

            editItem(item) {
                window.dispatchEvent(new CustomEvent('open-leave-modal-detail', { detail: item }));
            },

            async deleteItem(id) {
                const result = await window.Swal.fire({
                    title: 'Are you sure?',
                    text: 'Are you sure you want to delete this leave request?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete it',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#dc3545',
                    reverseButtons: true,
                    focusCancel: true
                });
                if (!result.isConfirmed) return;
                try {
                    const res = await hrFetch(`/api/leaves/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });
                    if (res.ok) {
                        this.loadItems();
                    }
                } catch(e) {
                    hrFailure(e);
                }
            },

            toggleAll(checked) {
                if (checked) {
                    this.selectedItems = this.items.filter(i => this.canManage(i)).map(i => String(i.id));
                } else {
                    this.selectedItems = [];
                }
            },

            async bulkAction(action) {
                if (this.selectedItems.length === 0) return;
                let message = 'Are you sure you want to perform this action?';
                let confirmBtnText = 'Yes, proceed';
                let confirmBtnColor = '#0d6efd';

                if (action === 'delete') {
                    message = 'Are you sure you want to delete the selected leave requests?';
                    confirmBtnText = 'Yes, delete them';
                    confirmBtnColor = '#dc3545';
                } else if (action === 'approve') {
                    message = 'Are you sure you want to approve the selected leave requests?';
                    confirmBtnText = 'Yes, approve them';
                    confirmBtnColor = '#198754';
                } else if (action === 'reject') {
                    message = 'Are you sure you want to reject the selected leave requests?';
                    confirmBtnText = 'Yes, reject them';
                    confirmBtnColor = '#ffc107'; // bootstrap warning
                }

                const result = await window.Swal.fire({
                    title: 'Are you sure?',
                    text: message,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: confirmBtnText,
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: confirmBtnColor,
                    reverseButtons: true,
                    focusCancel: true
                });

                if (!result.isConfirmed) return;

                try {
                    const res = await hrFetch('/api/leaves/bulk-action', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ action: action, ids: Array.from(this.selectedItems).map(id => parseInt(id, 10)) })
                    });

                    if (res.ok) {
                        this.selectedItems = [];
                        this.loadItems();
                    }
                } catch(e) {
                    hrFailure(e);
                }
            }
        }));

        Alpine.data('balancesTable', () => ({
            items: [],
            selectedItems: [],
            isLoading: true,
            _requestId: 0,

            init() {
                this.loadItems();
                if (!balanceModalInstance) {
                    balanceModalInstance = bootstrap.Modal.getOrCreateInstance(document.getElementById('balanceModal'));
                }

                window.addEventListener('balance-saved', () => {
                    this.loadItems();
                    if(balanceModalInstance) balanceModalInstance.hide();
                });

                window.addEventListener('open-balance-modal', () => {
                    window.dispatchEvent(new CustomEvent('reset-balance-form'));
                    balanceModalInstance.show();
                });

                window.addEventListener('refresh-tab-balances', () => {
                    this.loadItems();
                });
            },

            async loadItems() {
                const requestId = this._requestId = (this._requestId || 0) + 1;
                this.isLoading = true;
                try {
                    const items = await hrAll('/api/leave-balances');
                    if (requestId !== this._requestId) return;
                    this.items = items;
                    if (this.selectedItems) this.selectedItems = [];
                } catch(e) { hrFailure(e); }
                if (requestId === this._requestId) this.isLoading = false;
            },

            editItem(item) {
                window.dispatchEvent(new CustomEvent('edit-balance-form', { detail: item }));
                balanceModalInstance.show();
            },

            async deleteItem(id) {
                if (!await hrConfirmDelete()) return;
                try {
                    const res = await hrFetch(`/api/leave-balances/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });
                    if(res.ok) {
                        this.loadItems();
                    }
                } catch(e) { hrFailure(e); }
            },

            toggleAll(checked) {
                if (checked) {
                    this.selectedItems = this.items.filter(i => i.status === undefined || i.status === 'Pending').map(i => String(i.id));
                } else {
                    this.selectedItems = [];
                }
            },

            async bulkAction(action) {
                if (this.selectedItems.length === 0) return;
                if (!await hrConfirmDelete()) return;
                try {
                    const res = await hrFetch('/api/leave-balances/bulk-action', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ action: action, ids: Array.from(this.selectedItems).map(id => parseInt(id, 10)) })
                    });

                    if (res.ok) {
                        this.selectedItems = [];
                        this.loadItems();
                    }
                } catch(e) { hrFailure(e); }
            }
        }));

        Alpine.data('holidaysTable', () => ({
            items: [],
            isLoading: true,
            _requestId: 0,

            init() {
                this.loadItems();
                if (!holidayModalInstance) {
                    holidayModalInstance = bootstrap.Modal.getOrCreateInstance(document.getElementById('holidayModal'));
                }

                window.addEventListener('holiday-saved', () => {
                    this.loadItems();
                    if(holidayModalInstance) holidayModalInstance.hide();
                });

                window.addEventListener('open-holiday-modal', () => {
                    window.dispatchEvent(new CustomEvent('reset-holiday-form'));
                    holidayModalInstance.show();
                });

                window.addEventListener('refresh-tab-holidays', () => {
                    this.loadItems();
                });
            },

            async loadItems() {
                const requestId = this._requestId = (this._requestId || 0) + 1;
                this.isLoading = true;
                try {
                    const items = await hrAll('/api/holidays');
                    if (requestId !== this._requestId) return;
                    this.items = items;
                    if (this.selectedItems) this.selectedItems = [];
                } catch(e) { hrFailure(e); }
                if (requestId === this._requestId) this.isLoading = false;
            },

            editItem(item) {
                window.dispatchEvent(new CustomEvent('edit-holiday-form', { detail: item }));
                holidayModalInstance.show();
            },

            async deleteItem(id) {
                if (!await hrConfirmDelete()) return;
                try {
                    const res = await hrFetch(`/api/holidays/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });
                    if(res.ok) {
                        this.loadItems();
                    }
                } catch(e) { hrFailure(e); }
            }
        }));

        Alpine.data('leaveForm', () => ({
            editingId: null,
            originalLeaveType: null,
            saving: false,
            error: null,
            users: [],
            userBalances: [],
            isLoadingBalances: false,
            _balanceRequestId: 0,
            form: {
                user_id: '', leave_type: '', start_date: '', end_date: '', reason: '', status: 'Pending'
            },

            init() {
                this.loadUsers();
                window.addEventListener('open-leave-modal', () => {
                    this.error = null;
                    this.editingId = null;
                    this.originalLeaveType = null;
                    this.form.user_id = '{{ auth()->id() }}';
                    this.form.leave_type = 'Sick';
                    this.form.start_date = '';
                    this.form.end_date = '';
                    this.form.reason = '';
                    this.form.status = 'Pending';
                    this.fetchBalances(this.form.user_id);
                    leaveModalInstance.show();
                });
                window.addEventListener('open-leave-modal-detail', (e) => {
                    this.error = null;
                    this.editingId = e.detail.id;
                    this.originalLeaveType = e.detail.leave_type;
                    this.form = { ...e.detail };
                    if (this.form.start_date) this.form.start_date = this.form.start_date.split('T')[0].split(' ')[0];
                    if (this.form.end_date) this.form.end_date = this.form.end_date.split('T')[0].split(' ')[0];
                    this.fetchBalances(this.form.user_id);
                    leaveModalInstance.show();
                });

                this.$watch('form.user_id', (value) => {
                    this.fetchBalances(value);
                });
            },

            async fetchBalances(userId) {
                const requestId = this._balanceRequestId = (this._balanceRequestId || 0) + 1;
                const prevType = this.form.leave_type;
                this.userBalances = [];
                if (!userId) { this.isLoadingBalances = false; return; }

                this.isLoadingBalances = true;
                try {
                    const res = await hrFetch(`/api/leave-balances?user_id=${userId}&is_active=1&per_page=100`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    if (res.ok) {
                        const json = await res.json();
                        if (requestId !== this._balanceRequestId) return;
                        this.userBalances = json.data || [];
                        this.$nextTick(() => {
                            if (prevType) this.form.leave_type = prevType;
                        });
                    }
                } catch (e) {
                    if (requestId === this._balanceRequestId) this.error = e.message;
                }
                if (requestId === this._balanceRequestId) this.isLoadingBalances = false;
            },

            async loadUsers() {
                this.users = await hrUsers();
            },

            async saveItem() {
                if (this.saving) return;
                this.saving = true;
                this.error = null;

                try {
                    const method = this.editingId ? 'PUT' : 'POST';
                    const url = this.editingId ? `/api/leaves/${this.editingId}` : '/api/leaves';

                    const res = await hrFetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(this.form)
                    });

                    if (res.ok) {
                        window.dispatchEvent(new CustomEvent('leave-saved'));
                    } else {
                        const err = await res.json();
                        this.error = err.message || 'Validation error.';
                    }
                } catch(e) {
                    this.error = e.message || 'Network error saving record.';
                }

                this.saving = false;
            }
        }));

        Alpine.data('balanceForm', () => ({
            editingId: null,
            saving: false,
            error: null,
            users: [],
            form: {
                user_id: '', leave_type: 'Sick', total_leaves: 0, used_leaves: 0
            },

            init() {
                this.loadUsers();
                window.addEventListener('reset-balance-form', () => {
                    this.error = null;
                    this.editingId = null;
                    this.form = { user_id: '', leave_type: 'Sick', total_leaves: 0, used_leaves: 0 };
                });
                window.addEventListener('edit-balance-form', (e) => {
                    this.error = null;
                    this.editingId = e.detail.id;
                    this.form = { ...e.detail };
                });
            },

            async loadUsers() {
                this.users = await hrUsers();
            },

            async saveItem() {
                if (this.saving) return;
                this.saving = true;
                this.error = null;

                try {
                    const method = this.editingId ? 'PUT' : 'POST';
                    const url = this.editingId ? `/api/leave-balances/${this.editingId}` : '/api/leave-balances';

                    const res = await hrFetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(this.form)
                    });

                    if (res.ok) {
                        window.dispatchEvent(new CustomEvent('balance-saved'));
                    } else {
                        const err = await res.json();
                        this.error = err.message || 'Validation error.';
                    }
                } catch(e) {
                    this.error = e.message || 'Network error saving record.';
                }

                this.saving = false;
            }
        }));

        Alpine.data('holidayForm', () => ({
            editingId: null,
            saving: false,
            error: null,
            form: {
                name: '', date: '', type: 'National'
            },

            init() {
                window.addEventListener('reset-holiday-form', () => {
                    this.error = null;
                    this.editingId = null;
                    this.form = { name: '', date: '', type: 'National' };
                });
                window.addEventListener('edit-holiday-form', (e) => {
                    this.error = null;
                    this.editingId = e.detail.id;
                    this.form = { ...e.detail };
                    // Fix date format for input type="date"
                    if (this.form.date) {
                        this.form.date = this.form.date.split('T')[0];
                    }
                });
            },

            async saveItem() {
                if (this.saving) return;
                this.saving = true;
                this.error = null;

                try {
                    const method = this.editingId ? 'PUT' : 'POST';
                    const url = this.editingId ? `/api/holidays/${this.editingId}` : '/api/holidays';

                    const res = await hrFetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(this.form)
                    });

                    if (res.ok) {
                        window.dispatchEvent(new CustomEvent('holiday-saved'));
                    } else {
                        const err = await res.json();
                        this.error = err.message || 'Validation error.';
                    }
                } catch(e) {
                    this.error = e.message || 'Network error saving record.';
                }

                this.saving = false;
            }
        }));
    });
</script>
@endpush
@endsection
