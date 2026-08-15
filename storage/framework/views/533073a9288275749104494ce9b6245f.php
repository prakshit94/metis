<?php $__env->startSection('title', '🏖️ Leave Management'); ?>
<?php $__env->startSection('page', 'leaves'); ?>

<?php $__env->startSection('content'); ?>
<div class="user-management" x-data="{ currentTab: sessionStorage.getItem('leavesTab') || 'requests' }" x-init="$watch('currentTab', val => { sessionStorage.setItem('leavesTab', val); window.dispatchEvent(new CustomEvent('refresh-tab-' + val)); })">
    <div class="d-flex justify-content-between align-items-center mb-4 mb-lg-5 mb-xl-6">
        <div>
            <h1 class="h3 mb-0"><i class="bi bi-calendar-minus-fill text-primary me-2"></i>Leave Management</h1>
            <p class="text-muted mb-0">Manage employee leave requests, approvals, balances, and holidays</p>
        </div>
        <div class="d-flex gap-2">
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('leave-create')): ?>
            <button type="button" class="btn btn-primary" x-show="currentTab === 'requests'" @click="$dispatch('open-leave-modal')">
                <i class="bi bi-plus-circle me-2"></i>Request Leave
            </button>
            <button type="button" class="btn btn-primary" x-show="currentTab === 'balances'" style="display: none;" @click="$dispatch('open-balance-modal')">
                <i class="bi bi-plus-circle me-2"></i>Add Leave Balance
            </button>
            <button type="button" class="btn btn-primary" x-show="currentTab === 'holidays'" style="display: none;" @click="$dispatch('open-holiday-modal')">
                <i class="bi bi-plus-circle me-2"></i>Add Holiday
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link" :class="{ 'active': currentTab === 'requests' }" href="#" @click.prevent="currentTab = 'requests'">
                <i class="bi bi-envelope-open me-2"></i>Pending Requests
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" :class="{ 'active': currentTab === 'history' }" href="#" @click.prevent="currentTab = 'history'">
                <i class="bi bi-clock-history me-2"></i>History
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" :class="{ 'active': currentTab === 'balances' }" href="#" @click.prevent="currentTab = 'balances'">
                <i class="bi bi-wallet2 me-2"></i>Leave Balances
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" :class="{ 'active': currentTab === 'holidays' }" href="#" @click.prevent="currentTab = 'holidays'">
                <i class="bi bi-calendar-event me-2"></i>Holidays
            </a>
        </li>
    </ul>

    <!-- PENDING REQUESTS TAB -->
    <div x-show="currentTab === 'requests'" x-data="leavesTable('pending')">
        <?php echo $__env->make('users.partials.leave_table', ['title' => 'Pending Requests', 'statusFilter' => 'Pending'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>

    <!-- HISTORY TAB -->
    <div x-show="currentTab === 'history'" style="display: none;" x-data="leavesTable('history')">
        <?php echo $__env->make('users.partials.leave_table', ['title' => 'Leave History', 'statusFilter' => 'History'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>

    <!-- BALANCES TAB -->
    <div x-show="currentTab === 'balances'" style="display: none;" x-data="balancesTable">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center g-3">
                    <div class="col">
                        <h2 class="h5 card-title mb-0">Leave Balances</h2>
                    </div>
                    <div class="col-auto">
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
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('leave-delete')): ?>
                            <button class="btn btn-sm btn-danger" @click="bulkAction('delete')">
                                <i class="bi bi-trash me-1"></i>Delete
                            </button>
                            <?php endif; ?>
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
                                <tr>
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
                                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('leave-edit')): ?>
                                                <li><a class="dropdown-item" href="#" @click.prevent="editItem(item)"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <?php endif; ?>
                                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('leave-delete')): ?>
                                                <li><a class="dropdown-item text-danger" href="#" @click.prevent="deleteItem(item.id)"><i class="bi bi-trash me-2"></i>Delete</a></li>
                                                <?php endif; ?>
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
                        <h2 class="h5 card-title mb-0">Holidays List</h2>
                    </div>
                    <div class="col-auto">
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
                                <tr>
                                    <td class="ps-3 fw-medium" x-text="item.name"></td>
                                    <td x-text="new Date(item.date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })"></td>
                                    <td><span class="badge bg-info" x-text="item.type"></span></td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></button>
                                            <ul class="dropdown-menu">
                                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('leave-edit')): ?>
                                                <li><a class="dropdown-item" href="#" @click.prevent="editItem(item)"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <?php endif; ?>
                                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('leave-delete')): ?>
                                                <li><a class="dropdown-item text-danger" href="#" @click.prevent="deleteItem(item.id)"><i class="bi bi-trash me-2"></i>Delete</a></li>
                                                <?php endif; ?>
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
<div class="modal fade" id="leaveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" x-data="leaveForm">
        <form class="modal-content" @submit.prevent="saveItem()">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center">
                    <i class="bi bi-calendar-minus-fill text-primary me-2"></i>
                    <span x-text="editingId ? 'Edit Leave Request' : 'Request Leave'"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" x-show="error" x-text="error" style="display: none;"></div>
                
                <div class="form-floating mb-3">
                    <select class="form-select" id="leaveUser" x-model="form.user_id" required>
                        <option value="">Select Employee</option>
                        <template x-for="user in users" :key="user.id">
                            <option :value="user.id" x-text="user.name"></option>
                        </template>
                    </select>
                    <label for="leaveUser">Employee <span class="text-danger">*</span></label>
                </div>
                
                <div class="form-floating mb-3">
                    <select class="form-select" id="leaveType" x-model="form.leave_type" required :disabled="isLoadingBalances || userBalances.length === 0">
                        <option value="">Select Leave Type</option>
                        <template x-for="balance in userBalances" :key="balance.id">
                            <option :value="balance.leave_type" 
                                    x-text="`${balance.leave_type} Leave (Balance: ${balance.balance})`"
                                    :disabled="balance.balance <= 0 && balance.leave_type !== originalLeaveType"></option>
                        </template>
                    </select>
                    <label for="leaveType">Leave Type <span class="text-danger">*</span></label>
                    <div class="form-text text-warning" x-show="form.user_id && userBalances.length === 0 && !isLoadingBalances">
                        No active leave balances found for this employee.
                    </div>
                </div>

                <div class="row">
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <input type="date" class="form-control" id="leaveStart" x-model="form.start_date" required>
                            <label for="leaveStart">Start Date <span class="text-danger">*</span></label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <input type="date" class="form-control" id="leaveEnd" x-model="form.end_date" required>
                            <label for="leaveEnd">End Date <span class="text-danger">*</span></label>
                        </div>
                    </div>
                </div>

                <div class="form-floating mb-3">
                    <textarea class="form-control" id="leaveReason" x-model="form.reason" style="height: 100px;" required></textarea>
                    <label for="leaveReason">Reason <span class="text-danger">*</span></label>
                </div>

                <div class="form-floating mb-3" x-show="editingId">
                    <select class="form-select" id="leaveStatus" x-model="form.status">
                        <option value="Pending">Pending</option>
                        <option value="Approved">Approved</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                    <label for="leaveStatus">Status</label>
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
<div class="modal fade" id="balanceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" x-data="balanceForm">
        <form class="modal-content" @submit.prevent="saveItem()">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center">
                    <i class="bi bi-wallet2 text-primary me-2"></i>
                    <span x-text="editingId ? 'Edit Balance' : 'Add Balance'"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" x-show="error" x-text="error" style="display: none;"></div>
                
                <div class="form-floating mb-3">
                    <select class="form-select" x-model="form.user_id" required :disabled="editingId">
                        <option value="">Select Employee</option>
                        <template x-for="user in users" :key="user.id">
                            <option :value="user.id" x-text="user.name"></option>
                        </template>
                    </select>
                    <label>Employee <span class="text-danger">*</span></label>
                </div>

                <div class="form-floating mb-3">
                    <select class="form-select" x-model="form.leave_type" required :disabled="editingId">
                        <option value="Sick">Sick Leave</option>
                        <option value="Casual">Casual Leave</option>
                        <option value="Annual">Annual Leave</option>
                        <option value="Maternity">Maternity Leave</option>
                        <option value="Paternity">Paternity Leave</option>
                        <option value="Unpaid">Unpaid Leave</option>
                    </select>
                    <label>Leave Type <span class="text-danger">*</span></label>
                </div>

                <div class="row">
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <input type="number" step="0.5" class="form-control" x-model="form.total_leaves" required min="0">
                            <label>Total Leaves <span class="text-danger">*</span></label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <input type="number" step="0.5" class="form-control" x-model="form.used_leaves" required min="0">
                            <label>Used Leaves <span class="text-danger">*</span></label>
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
<div class="modal fade" id="holidayModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" x-data="holidayForm">
        <form class="modal-content" @submit.prevent="saveItem()">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center">
                    <i class="bi bi-calendar-event text-primary me-2"></i>
                    <span x-text="editingId ? 'Edit Holiday' : 'Add Holiday'"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" x-show="error" x-text="error" style="display: none;"></div>
                
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" x-model="form.name" required>
                    <label>Holiday Name <span class="text-danger">*</span></label>
                </div>

                <div class="form-floating mb-3">
                    <input type="date" class="form-control" x-model="form.date" required>
                    <label>Date <span class="text-danger">*</span></label>
                </div>

                <div class="form-floating mb-3">
                    <select class="form-select" x-model="form.type" required>
                        <option value="National">National</option>
                        <option value="Optional">Optional</option>
                        <option value="Company">Company</option>
                    </select>
                    <label>Type <span class="text-danger">*</span></label>
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

<?php $__env->startPush('scripts'); ?>
<script>
    document.addEventListener('alpine:init', () => {
        let leaveModalInstance = null;
        let balanceModalInstance = null;
        let holidayModalInstance = null;
        
        Alpine.data('leavesTable', (mode) => ({
            items: [],
            selectedItems: [],
            isLoading: true,
            currentPage: 1,
            totalPages: 1,
            totalItems: 0,
            perPage: 15,
            mode: mode,
            
            init() {
                this.loadItems();
                if (!leaveModalInstance) {
                    leaveModalInstance = new bootstrap.Modal(document.getElementById('leaveModal'));
                }
                
                window.addEventListener('leave-saved', () => {
                    this.loadItems();
                    if(leaveModalInstance) leaveModalInstance.hide();
                });
                
                window.addEventListener('open-leave-modal', (e) => {
                    leaveModalInstance.show();
                });
                
                window.addEventListener('refresh-tab-' + (this.mode === 'pending' ? 'requests' : 'history'), () => {
                    this.loadItems();
                });
            },
            
            async loadItems() {
                this.isLoading = true;
                try {
                    let url = `/api/leaves?page=${this.currentPage}&per_page=${this.perPage}`;
                    if (this.mode === 'pending') {
                        url += '&status=Pending';
                    } else if (this.mode === 'history') {
                        url += '&history=1'; // Handled via status in some cases, assuming API filters all non-pending if we pass custom param or handle it in client
                    }
                    
                    const res = await fetch(url);
                    if (res.ok) {
                        const json = await res.json();
                        let data = json.data || [];
                        
                        // Client-side filtering if API doesn't support 'history=1'
                        if (this.mode === 'pending') {
                            data = data.filter(i => i.status === 'Pending');
                        } else if (this.mode === 'history') {
                            data = data.filter(i => i.status !== 'Pending');
                        }
                        
                        this.items = data;
                        this.currentPage = json.current_page || 1;
                        this.totalPages = json.last_page || 1;
                        this.totalItems = json.total || 0;
                    }
                } catch(e) {
                    console.error('Failed to load leaves', e);
                }
                this.isLoading = false;
            },
            
            goToPage(page) {
                if (page >= 1 && page <= this.totalPages) {
                    this.currentPage = page;
                    this.loadItems();
                }
            },
            
            get visiblePages() {
                return [1]; // Simplified for brevity in this example
            },
            
            get pageFrom() {
                return this.totalItems === 0 ? 0 : (this.currentPage - 1) * this.perPage + 1;
            },
            
            get pageTo() {
                return Math.min(this.currentPage * this.perPage, this.totalItems);
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
                    const res = await fetch(`/api/leaves/${id}`, {
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
                    console.error('Delete failed', e);
                }
            },
            
            toggleAll(checked) {
                if (checked) {
                    this.selectedItems = this.items.map(i => String(i.id));
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
                    const res = await fetch('/api/leaves/bulk-action', {
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
                    console.error('Bulk action failed', e);
                }
            }
        }));
        
        Alpine.data('balancesTable', () => ({
            items: [],
            selectedItems: [],
            isLoading: true,
            
            init() {
                this.loadItems();
                if (!balanceModalInstance) {
                    balanceModalInstance = new bootstrap.Modal(document.getElementById('balanceModal'));
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
                this.isLoading = true;
                try {
                    const res = await fetch(`/api/leave-balances`);
                    if (res.ok) {
                        const json = await res.json();
                        this.items = json.data || [];
                    }
                } catch(e) {}
                this.isLoading = false;
            },
            
            editItem(item) {
                window.dispatchEvent(new CustomEvent('edit-balance-form', { detail: item }));
                balanceModalInstance.show();
            },
            
            async deleteItem(id) {
                try {
                    const res = await fetch(`/api/leave-balances/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });
                    if(res.ok) {
                        this.loadItems();
                    }
                } catch(e) {}
            },
            
            toggleAll(checked) {
                if (checked) {
                    this.selectedItems = this.items.map(i => String(i.id));
                } else {
                    this.selectedItems = [];
                }
            },
            
            async bulkAction(action) {
                if (this.selectedItems.length === 0) return;
                try {
                    const res = await fetch('/api/leave-balances/bulk-action', {
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
                } catch(e) {}
            }
        }));

        Alpine.data('holidaysTable', () => ({
            items: [],
            isLoading: true,
            
            init() {
                this.loadItems();
                if (!holidayModalInstance) {
                    holidayModalInstance = new bootstrap.Modal(document.getElementById('holidayModal'));
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
                this.isLoading = true;
                try {
                    const res = await fetch(`/api/holidays`);
                    if (res.ok) {
                        const json = await res.json();
                        this.items = json.data || [];
                    }
                } catch(e) {}
                this.isLoading = false;
            },
            
            editItem(item) {
                window.dispatchEvent(new CustomEvent('edit-holiday-form', { detail: item }));
                holidayModalInstance.show();
            },
            
            async deleteItem(id) {
                try {
                    const res = await fetch(`/api/holidays/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });
                    if(res.ok) {
                        this.loadItems();
                    }
                } catch(e) {}
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
            form: {
                user_id: '', leave_type: '', start_date: '', end_date: '', reason: '', status: 'Pending'
            },
            
            init() {
                this.loadUsers();
                window.addEventListener('open-leave-modal', () => {
                    this.error = null;
                    this.editingId = null;
                    this.originalLeaveType = null;
                    this.form.user_id = '<?php echo e(auth()->id()); ?>';
                    this.form.leave_type = 'Sick';
                    this.form.start_date = '';
                    this.form.end_date = '';
                    this.form.reason = '';
                    this.form.status = 'Pending';
                });
                window.addEventListener('open-leave-modal-detail', (e) => {
                    this.error = null;
                    this.editingId = e.detail.id;
                    this.originalLeaveType = e.detail.leave_type;
                    this.form = { ...e.detail };
                    if (this.form.start_date) this.form.start_date = this.form.start_date.split('T')[0].split(' ')[0];
                    if (this.form.end_date) this.form.end_date = this.form.end_date.split('T')[0].split(' ')[0];
                    leaveModalInstance.show();
                });

                this.$watch('form.user_id', (value) => {
                    this.fetchBalances(value);
                });
            },

            async fetchBalances(userId) {
                const prevType = this.form.leave_type;
                this.userBalances = [];
                if (!userId) return;
                
                this.isLoadingBalances = true;
                try {
                    const res = await fetch(`/api/leave-balances?user_id=${userId}&is_active=1&per_page=100`);
                    if (res.ok) {
                        const json = await res.json();
                        this.userBalances = json.data || [];
                        this.$nextTick(() => {
                            if (prevType) this.form.leave_type = prevType;
                        });
                    }
                } catch (e) {
                    console.error("Failed to load balances");
                }
                this.isLoadingBalances = false;
            },
            
            async loadUsers() {
                try {
                    const res = await fetch('/api/users?per_page=100');
                    if (res.ok) {
                        const json = await res.json();
                        this.users = json.data || json;
                    }
                } catch(e) {}
            },
            
            async saveItem() {
                this.saving = true;
                this.error = null;
                
                try {
                    const method = this.editingId ? 'PUT' : 'POST';
                    const url = this.editingId ? `/api/leaves/${this.editingId}` : '/api/leaves';
                    
                    const res = await fetch(url, {
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
                    this.error = 'Network error saving record.';
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
                try {
                    const res = await fetch('/api/users?per_page=100');
                    if (res.ok) {
                        const json = await res.json();
                        this.users = json.data || json;
                    }
                } catch(e) {}
            },
            
            async saveItem() {
                this.saving = true;
                this.error = null;
                
                try {
                    const method = this.editingId ? 'PUT' : 'POST';
                    const url = this.editingId ? `/api/leave-balances/${this.editingId}` : '/api/leave-balances';
                    
                    const res = await fetch(url, {
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
                    this.error = 'Network error saving record.';
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
                this.saving = true;
                this.error = null;
                
                try {
                    const method = this.editingId ? 'PUT' : 'POST';
                    const url = this.editingId ? `/api/holidays/${this.editingId}` : '/api/holidays';
                    
                    const res = await fetch(url, {
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
                    this.error = 'Network error saving record.';
                }
                
                this.saving = false;
            }
        }));
    });
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/user/metis/resources/views/users/leaves.blade.php ENDPATH**/ ?>