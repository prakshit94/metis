<div class="card">
    <div class="card-header">
        <div class="row align-items-center g-3">
            <div class="col">
                <h2 class="h5 card-title mb-0"><?php echo e($title); ?></h2>
            </div>
            <div class="col-auto">
                <button class="btn btn-sm btn-outline-secondary" type="button" @click="loadItems()" :disabled="isLoading" title="Refresh">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
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
                                <input type="checkbox" class="form-check-input" :value="String(item.id)" x-model="selectedItems" x-show="item.status === 'Pending'">
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
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Actions" x-show="item.status === 'Pending'">
                                        <i class="bi bi-three-dots"></i>
                                    </button>
                                    <span class="text-muted small" x-show="item.status !== 'Pending'" title="Processed leaves cannot be modified">
                                        <i class="bi bi-lock-fill text-secondary"></i>
                                    </span>
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
<?php /**PATH /home/user/metis/resources/views/users/partials/leave_table.blade.php ENDPATH**/ ?>