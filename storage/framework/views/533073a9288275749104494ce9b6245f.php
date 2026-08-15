<?php $__env->startSection('title', '🏖️ Leave Management'); ?>
<?php $__env->startSection('page', 'leaves'); ?>

<?php $__env->startSection('content'); ?>
<div class="user-management" x-data="leavesTable">
    <div class="d-flex justify-content-between align-items-center mb-4 mb-lg-5 mb-xl-6">
        <div>
            <h1 class="h3 mb-0"><i class="bi bi-calendar-minus-fill text-primary me-2"></i>Leave Management</h1>
            <p class="text-muted mb-0">Manage employee leave requests, approvals, and balances</p>
        </div>
        <div class="d-flex gap-2">
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('leave-create')): ?>
            <button type="button" class="btn btn-primary" @click="openCreate()">
                <i class="bi bi-plus-circle me-2"></i>Request Leave
            </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="row align-items-center g-3">
                <div class="col">
                    <h2 class="h5 card-title mb-0">Leave Requests</h2>
                </div>
                <div class="col-auto">
                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                        <button class="btn btn-sm btn-outline-secondary" type="button" @click="loadItems()" :disabled="isLoading" title="Refresh">
                            <i class="bi bi-arrow-clockwise"></i> Refresh
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <!-- Bulk Actions Bar -->
            <div class="bulk-actions-bar p-3 bg-primary bg-opacity-10 border-bottom border-primary border-opacity-25"
                 x-show="selectedItems.length > 0" style="display: none;">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle-fill text-primary me-2"></i>
                        <span class="fw-medium text-primary">
                            <span x-text="selectedItems.length"></span> leave request<span x-show="selectedItems.length !== 1">s</span> selected
                        </span>
                    </div>
                    <div class="d-flex gap-2">
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('leave-edit')): ?>
                        <button class="btn btn-sm btn-success" @click="bulkAction('approve')">
                            <i class="bi bi-check-circle me-1"></i>Approve
                        </button>
                        <button class="btn btn-sm btn-warning" @click="bulkAction('reject')">
                            <i class="bi bi-x-circle me-1"></i>Reject
                        </button>
                        <?php endif; ?>
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('leave-delete')): ?>
                        <button class="btn btn-sm btn-danger" @click="bulkAction('delete')">
                            <i class="bi bi-trash me-1"></i>Delete
                        </button>
                        <?php endif; ?>
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
                            <th style="width: 50px;" class="ps-3">
                                <input type="checkbox" class="form-check-input"
                                       @change="toggleAll($event.target.checked)"
                                       :checked="items.length > 0 && items.every(i => selectedItems.includes(String(i.id)))">
                            </th>
                            <th scope="col" class="ps-2">Employee</th>
                            <th scope="col">Type</th>
                            <th scope="col">Duration</th>
                            <th scope="col">Reason</th>
                            <th scope="col">Status</th>
                            <th scope="col">Applied On</th>
                            <th style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="isLoading">
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <template x-if="!isLoading && items.length === 0">
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    No leave requests found.
                                </td>
                            </tr>
                        </template>
                        <template x-for="item in items" :key="item.id">
                            <tr>
                                <td class="ps-3">
                                    <input type="checkbox" class="form-check-input" :value="String(item.id)" x-model="selectedItems">
                                </td>
                                <td class="ps-2 fw-medium">
                                    <span x-text="item.user ? item.user.name : '—'"></span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary" x-text="item.leave_type"></span>
                                </td>
                                <td>
                                    <div class="small">
                                        <span class="text-muted">From:</span> <span x-text="new Date(item.start_date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })"></span><br>
                                        <span class="text-muted">To:</span> <span x-text="new Date(item.end_date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })"></span><br>
                                        <span class="fw-medium text-primary"><span x-text="Math.max(1, Math.ceil((new Date(item.end_date) - new Date(item.start_date)) / (1000 * 60 * 60 * 24)) + 1)"></span> Days</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-truncate d-inline-block" style="max-width: 150px;" x-text="item.reason" :title="item.reason"></span>
                                </td>
                                <td>
                                    <span class="badge" 
                                          :class="{
                                              'bg-success': item.status === 'Approved',
                                              'bg-danger': item.status === 'Rejected',
                                              'bg-warning text-dark': item.status === 'Pending'
                                          }" 
                                          x-text="item.status"></span>
                                    <template x-if="item.status !== 'Pending' && item.approver && item.approved_at">
                                        <div class="mt-1" style="font-size: 0.75rem;">
                                            <span class="text-muted">By:</span> <span class="fw-medium text-body" x-text="item.approver.name"></span><br>
                                            <span class="text-muted">On:</span> <span class="text-body" x-text="new Date(item.approved_at).toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })"></span>
                                        </div>
                                    </template>
                                </td>
                                <td>
                                    <span x-text="new Date(item.created_at).toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })"></span>
                                    <template x-if="item.applier">
                                        <div class="mt-1" style="font-size: 0.75rem;">
                                            <span class="text-muted">By:</span> <span class="fw-medium text-body" x-text="item.applier.name"></span>
                                        </div>
                                    </template>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('leave-edit')): ?>
                                            <li>
                                                <a class="dropdown-item" href="#" @click.prevent="editItem(item)">
                                                    <i class="bi bi-pencil me-2"></i>Edit / Update Status
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <?php endif; ?>
                                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('leave-delete')): ?>
                                            <li>
                                                <a class="dropdown-item text-danger" href="#" @click.prevent="deleteItem(item.id)">
                                                    <i class="bi bi-trash me-2"></i>Delete
                                                </a>
                                            </li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center p-3 border-top" x-show="totalItems > 0" style="display: none;">
                <div class="text-muted small">
                    Showing <span x-text="pageFrom"></span> to
                    <span x-text="pageTo"></span> of
                    <span x-text="totalItems"></span> results
                </div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item" :class="{ 'disabled': currentPage === 1 }">
                            <a class="page-link" href="#" @click.prevent="goToPage(currentPage - 1)">Previous</a>
                        </li>
                        <template x-for="(page, index) in visiblePages" :key="`${page}-${index}`">
                            <li class="page-item" :class="{ 'active': page === currentPage }">
                                <template x-if="page === '...'">
                                    <span class="page-link px-2 text-muted">...</span>
                                </template>
                                <template x-if="page !== '...'">
                                    <a class="page-link" href="#" @click.prevent="goToPage(page)" x-text="page"></a>
                                </template>
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

<div class="modal fade" id="leaveModal" tabindex="-1" aria-labelledby="leaveModalLabel">
    <div class="modal-dialog modal-dialog-centered" x-data="leaveForm">
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
                    <select class="form-select" id="leaveType" x-model="form.leave_type" required>
                        <option value="Sick">Sick Leave</option>
                        <option value="Casual">Casual Leave</option>
                        <option value="Annual">Annual Leave</option>
                        <option value="Maternity">Maternity Leave</option>
                        <option value="Paternity">Paternity Leave</option>
                        <option value="Unpaid">Unpaid Leave</option>
                    </select>
                    <label for="leaveType">Leave Type <span class="text-danger">*</span></label>
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

<?php $__env->startPush('scripts'); ?>
<script>
    document.addEventListener('alpine:init', () => {
        let modalInstance = null;
        
        Alpine.data('leavesTable', () => ({
            items: [],
            selectedItems: [],
            isLoading: true,
            currentPage: 1,
            totalPages: 1,
            totalItems: 0,
            perPage: 15,
            
            init() {
                this.loadItems();
                modalInstance = new bootstrap.Modal(document.getElementById('leaveModal'));
                
                window.addEventListener('leave-saved', () => {
                    this.loadItems();
                    if(modalInstance) modalInstance.hide();
                });
            },
            
            async loadItems() {
                this.isLoading = true;
                try {
                    const res = await fetch(`/api/leaves?page=${this.currentPage}&per_page=${this.perPage}`);
                    if (res.ok) {
                        const json = await res.json();
                        this.items = json.data || [];
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
                const delta = 2;
                const range = [];
                for (let i = Math.max(2, this.currentPage - delta); i <= Math.min(this.totalPages - 1, this.currentPage + delta); i++) {
                    range.push(i);
                }
                const result = [];
                if (this.currentPage - delta > 2) result.push(1, '...');
                else result.push(1);
                result.push(...range);
                if (this.currentPage + delta < this.totalPages - 1) result.push('...', this.totalPages);
                else if (this.totalPages > 1) result.push(this.totalPages);
                return result.filter((v, i, a) => a.indexOf(v) === i && (typeof v === 'string' || v <= this.totalPages));
            },
            
            get pageFrom() {
                return this.totalItems === 0 ? 0 : (this.currentPage - 1) * this.perPage + 1;
            },
            
            get pageTo() {
                return Math.min(this.currentPage * this.perPage, this.totalItems);
            },
            
            openCreate() {
                window.dispatchEvent(new CustomEvent('open-leave-modal', { detail: null }));
                modalInstance.show();
            },
            
            editItem(item) {
                window.dispatchEvent(new CustomEvent('open-leave-modal', { detail: item }));
                modalInstance.show();
            },
            
            async deleteItem(id) {
                const result = await Swal.fire({
                    title: 'Delete Leave Request?',
                    text: 'Are you sure you want to delete this leave request?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: 'Yes, delete it'
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
                    if(res.ok) {
                        this.loadItems();
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to delete leave request.' });
                    }
                } catch(e) {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'An unexpected error occurred.' });
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
                
                const actionNames = { approve: 'Approve', reject: 'Reject', delete: 'Delete' };
                const isDelete = action === 'delete';
                
                const result = await Swal.fire({
                    title: `${actionNames[action]} Leave Requests?`,
                    text: `Are you sure you want to ${actionNames[action].toLowerCase()} ${this.selectedItems.length} selected leave request(s)?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: isDelete ? '#dc3545' : '#0d6efd',
                    confirmButtonText: `Yes, ${actionNames[action].toLowerCase()} them`
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
                        body: JSON.stringify({ action: action, ids: this.selectedItems })
                    });
                    
                    if (res.ok) {
                        this.selectedItems = [];
                        this.loadItems();
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Bulk action failed.' });
                    }
                } catch(e) {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'An unexpected error occurred.' });
                }
            }
        }));
        
        Alpine.data('leaveForm', () => ({
            editingId: null,
            saving: false,
            error: null,
            users: [],
            form: {
                user_id: '',
                leave_type: 'Sick',
                start_date: '',
                end_date: '',
                reason: '',
                status: 'Pending'
            },
            
            init() {
                this.loadUsers();
                window.addEventListener('open-leave-modal', (e) => {
                    this.error = null;
                    if (e.detail) {
                        this.editingId = e.detail.id;
                        this.form.user_id = e.detail.user_id;
                        this.form.leave_type = e.detail.leave_type;
                        this.form.start_date = e.detail.start_date;
                        this.form.end_date = e.detail.end_date;
                        this.form.reason = e.detail.reason;
                        this.form.status = e.detail.status;
                    } else {
                        this.editingId = null;
                        this.form.user_id = '<?php echo e(auth()->id()); ?>';
                        this.form.leave_type = 'Sick';
                        this.form.start_date = '';
                        this.form.end_date = '';
                        this.form.reason = '';
                        this.form.status = 'Pending';
                    }
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
    });
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/user/metis/resources/views/users/leaves.blade.php ENDPATH**/ ?>