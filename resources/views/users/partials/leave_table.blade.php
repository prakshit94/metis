<div class="card">
    <div class="card-header">
        <div class="row align-items-center g-3">
            <div class="col">
                <h2 class="h5 card-title mb-1">{{ $title }}</h2>
                <p class="small text-muted mb-0">{{ $statusFilter === 'Pending' ? 'Review leave dates, reasons, and submission details before making a decision.' : 'Browse processed requests and their approval or rejection details.' }}</p>
            </div>
            <div class="col-auto d-flex flex-wrap gap-2">
                @can('leave-export')<button class="btn btn-sm btn-outline-primary" type="button" @click="exportReport()" :disabled="isLoading">Export CSV</button>@endcan
                <button class="btn btn-sm btn-outline-secondary" type="button" @click="loadItems()" :disabled="isLoading" title="Refresh">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
            </div>
        </div>
    </div>
    <div class="card-body border-bottom report-summary">
        <div class="row g-3" aria-label="Leave report totals" aria-live="polite">
            <div class="col-6 col-lg-3"><span class="small text-muted">Matching requests</span><strong class="fs-5 d-block" x-text="summary.requests"></strong></div>
            <div class="col-6 col-lg-3"><span class="small text-muted">Requested days</span><strong class="fs-5 d-block" x-text="summary.requested_days"></strong></div>
            <div class="col-6 col-lg-3"><span class="small text-muted">Approved</span><strong class="fs-5 text-success d-block" x-text="summary.approved"></strong></div>
            <div class="col-6 col-lg-3"><span class="small text-muted">Rejected</span><strong class="fs-5 text-danger d-block" x-text="summary.rejected"></strong></div>
        </div>
    </div>
    <div class="card-body border-bottom">
        <div class="row g-3 align-items-end">
            <div class="col-md-6 col-xl-4"><label class="form-label small">Search requests</label><input type="search" class="form-control form-control-sm" x-model="search" @input.debounce.350ms="filterItems()" placeholder="Employee, employee ID, leave type or reason" aria-label="Search {{ $title }}"></div>
            <div class="col-md-6 col-xl-4"><label class="form-label small">Employee</label>
                <x-searchable-select model="employeeFilter" label="Leave report employee" small>
                    <select x-ref="native" class="visually-hidden" tabindex="-1" aria-hidden="true" x-model="employeeFilter" @change="filterItems()">
                        <option value="">All visible employees</option><template x-if="employeeFilter && !employeeOptions.some(employee => String(employee.id) === String(employeeFilter))"><option :value="employeeFilter" selected x-text="'Employee #' + employeeFilter"></option></template><template x-for="employee in employeeOptions" :key="employee.id"><option :value="employee.id" x-text="employee.name + (employee.employee_id ? ' (' + employee.employee_id + ')' : '')"></option></template>
                    </select>
                </x-searchable-select>
            </div>
            <div class="col-md-6 col-xl-4"><label class="form-label small">Leave type</label>
                <x-searchable-select model="typeFilter" label="Leave report type" small>
                    <select x-ref="native" class="visually-hidden" tabindex="-1" aria-hidden="true" x-model="typeFilter" @change="filterItems()"><option value="">All leave types</option><template x-for="type in leaveTypes" :key="type"><option :value="type" x-text="type"></option></template></select>
                </x-searchable-select>
            </div>
            <div class="col-sm-6 col-xl-3"><label class="form-label small">Leave dates from</label><input type="date" class="form-control form-control-sm" aria-label="Leave report from date" x-model="fromDate" @change="filterItems()"></div>
            <div class="col-sm-6 col-xl-3"><label class="form-label small">Leave dates through</label><input type="date" class="form-control form-control-sm" aria-label="Leave report to date" x-model="toDate" :min="fromDate" @change="filterItems()"></div>
            <div class="col-md-3" x-show="mode === 'history'"><label class="form-label small">Decision</label>
                <x-searchable-select model="statusFilter" label="Leave report decision" small><select x-ref="native" class="visually-hidden" tabindex="-1" aria-hidden="true" x-model="statusFilter" @change="filterItems()"><option value="">All decisions</option><option>Approved</option><option>Rejected</option></select></x-searchable-select>
            </div>
            <div class="col-sm-6 col-xl-3"><label class="form-label small">Rows</label>
                <x-searchable-select model="perPage" label="Leave report page size" small><select x-ref="native" class="visually-hidden" tabindex="-1" aria-hidden="true" x-model="perPage" @change="filterItems()"><option value="15">15</option><option value="25">25</option><option value="50">50</option><option value="100">100</option></select></x-searchable-select>
            </div>
            <div class="col-12 d-flex gap-3 align-items-center flex-wrap">
                <label class="form-check-label"><input type="checkbox" class="form-check-input me-1" x-model="directTeam" @change="filterItems()"> My direct reportees</label>
                <button type="button" class="btn btn-sm btn-outline-secondary" @click="resetFilters()">Reset filters</button>
            </div>
        </div>
    </div>
    <p class="small text-muted px-3 py-2 mb-0 border-bottom">Date filters include overlapping requests. Day totals use each request’s full duration.</p>
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
                    @can('leave-approve')
                    <button class="btn btn-sm btn-success" @click="bulkAction('approve')" x-show="canReviewSelection">
                        <i class="bi bi-check-circle me-1"></i>Approve
                    </button>
                    <button class="btn btn-sm btn-warning" @click="bulkAction('reject')" x-show="canReviewSelection">
                        <i class="bi bi-x-circle me-1"></i>Reject
                    </button>
                    @endcan
                    @can('leave-delete')
                    <button class="btn btn-sm btn-danger" @click="bulkAction('delete')" x-show="canDeleteSelection">
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
                                   :checked="items.some(i => canManage(i)) && items.filter(i => canManage(i)).every(i => selectedItems.includes(String(i.id)))" :disabled="!items.some(i => canManage(i))" aria-label="Select pending requests on this page">
                        </th>
                        <th scope="col" class="ps-2">Employee</th>
                        <th scope="col">Type</th>
                        <th scope="col">Duration</th>
                        <th scope="col">Reason</th>
                        <th scope="col">Status</th>
                        <th scope="col">{{ $statusFilter === 'Pending' ? 'Requested Date' : 'Applied On' }}</th>
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
                        <tr x-show="!isLoading">
                            <td class="ps-3">
                                <input type="checkbox" class="form-check-input" :value="String(item.id)" x-model="selectedItems" x-show="canManage(item)">
                            </td>
                            <td class="ps-2 fw-medium">
                                <button type="button" class="btn btn-link p-0 text-start text-decoration-none" @click="viewItem(item)" x-text="item.user ? item.user.name : '—'"></button><small class="d-block text-muted" x-text="item.user?.employee_id || ''"></small>
                            </td>
                            <td>
                                <span class="badge bg-secondary" x-text="item.leave_type"></span>
                            </td>
                            <td>
                                <div class="small">
                                    <span class="text-muted">From:</span> <span x-text="new Date(item.start_date + 'T00:00:00').toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })"></span><br>
                                    <span class="text-muted">To:</span> <span x-text="new Date(item.end_date + 'T00:00:00').toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })"></span><br>
                                    <span class="fw-medium text-primary"><span x-text="Math.max(1, Math.round((new Date(item.end_date) - new Date(item.start_date)) / (1000 * 60 * 60 * 24)) + 1)"></span> <span x-text="item.start_date === item.end_date ? 'Day' : 'Days'"></span></span>
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
                                          'bg-warning text-body-emphasis': item.status === 'Pending'
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
                                <span class="text-nowrap" x-text="item.created_at ? new Date(item.created_at).toLocaleString('en-GB', { timeZone: @js(config('app.timezone')), day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—'"></span>
                                <template x-if="item.applier">
                                    <div class="mt-1" style="font-size: 0.75rem;">
                                        <span class="text-muted">By:</span> <span class="fw-medium text-body" x-text="item.applier.name"></span>
                                    </div>
                                </template>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Actions" x-show="canManage(item)">
                                        <i class="bi bi-three-dots"></i>
                                    </button>
                                    <span class="text-muted small" x-show="item.status !== 'Pending'" title="Processed leaves cannot be modified">
                                        <i class="bi bi-lock-fill text-secondary"></i>
                                    </span>
                                    <ul class="dropdown-menu">
                                        @can('leave-edit')
                                        <li x-show="@js($isGlobalHr) || String(item.user_id) === '{{ auth()->id() }}'">
                                            <a class="dropdown-item" href="#" @click.prevent="editItem(item)">
                                                <i class="bi bi-pencil me-2"></i>Edit
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        @endcan
                                        @can('leave-approve')
                                        <li x-show="canReview(item)"><button class="dropdown-item text-success" type="button" @click="reviewItem(item, 'Approved')">Approve</button></li>
                                        <li x-show="canReview(item)"><button class="dropdown-item text-warning" type="button" @click="reviewItem(item, 'Rejected')">Reject</button></li>
                                        @endcan
                                        @can('leave-delete')
                                        <li>
                                            <a x-show="@js($isGlobalHr) || String(item.user_id) === '{{ auth()->id() }}'" class="dropdown-item text-danger" href="#" @click.prevent="deleteItem(item.id)">
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

<div class="modal fade" id="leaveDetails-{{ $statusFilter === 'Pending' ? 'pending' : 'history' }}" tabindex="-1" aria-label="Leave request details">
    <div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Leave request details</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body" x-show="viewingItem">
            <h6 x-text="viewingItem?.user?.name || 'Employee'"></h6>
            <p class="text-muted" x-text="[viewingItem?.user?.employee_id, viewingItem?.user?.designation].filter(Boolean).join(' · ')"></p>
            <p><strong x-text="viewingItem?.leave_type"></strong> · <span x-text="viewingItem?.start_date"></span> — <span x-text="viewingItem?.end_date"></span> · <span class="badge bg-secondary" x-text="viewingItem?.status"></span></p>
            <h6>Reason</h6><p class="text-break" style="white-space: pre-wrap;" x-text="viewingItem?.reason || 'No reason provided.'"></p>
            <p class="small text-muted">Applied by <span x-text="viewingItem?.applier?.name || '—'"></span> · <span x-text="viewingItem?.created_at ? new Date(viewingItem.created_at).toLocaleString() : ''"></span></p>
            <p class="small text-muted" x-show="viewingItem?.approved_at">Processed by <span x-text="viewingItem?.approver?.name || '—'"></span> · <span x-text="viewingItem?.approved_at ? new Date(viewingItem.approved_at).toLocaleString() : ''"></span></p>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            @can('leave-approve')
            <button type="button" class="btn btn-success" x-show="viewingItem && canReview(viewingItem)" @click="reviewItem(viewingItem, 'Approved')">Approve</button>
            <button type="button" class="btn btn-warning" x-show="viewingItem && canReview(viewingItem)" @click="reviewItem(viewingItem, 'Rejected')">Reject</button>
            @endcan
        </div>
    </div></div>
</div>
