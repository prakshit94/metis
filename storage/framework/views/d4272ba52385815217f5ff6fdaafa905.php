<?php $__env->startSection('title', 'Shipments & Tracking'); ?>
<?php $__env->startSection('page', 'shipping-shipments'); ?>

<?php $__env->startSection('content'); ?>
<div class="shipments-management" x-data="shipmentsTable" x-cloak>
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 mb-lg-5">
        <div>
            <h1 class="h3 mb-0">Shipments &amp; Tracking</h1>
            <p class="text-muted mb-0">Monitor shipment fulfillment, tracking numbers, and delivery status</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary" @click="exportData()">
                <i class="bi bi-download me-2"></i>Export CSV
            </button>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row g-4 mb-4 mb-lg-5">
        <!-- Total Shipments -->
        <div class="col-xl-3 col-sm-6">
            <div class="card stats-card">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bi bi-box-seam-fill"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Total Shipments</p>
                            <div class="h3 mb-0" x-text="stats.total">0</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Pending -->
        <div class="col-xl-3 col-sm-6">
            <div class="card stats-card">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-warning bg-opacity-10 text-warning me-3">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Pending</p>
                            <div class="h3 mb-0" x-text="stats.pending">0</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- In Transit -->
        <div class="col-xl-3 col-sm-6">
            <div class="card stats-card">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bi bi-arrow-right-short"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">In Transit</p>
                            <div class="h3 mb-0" x-text="stats.in_transit">0</div>
                            <small class="text-primary-emphasis">Includes dispatched parcels</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Delivered -->
        <div class="col-xl-4 col-sm-6">
            <div class="card stats-card">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-success bg-opacity-10 text-success me-3">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Delivered</p>
                            <div class="h3 mb-0" x-text="stats.delivered">0</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Returned -->
        <div class="col-xl-4 col-sm-6">
            <div class="card stats-card">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-secondary bg-opacity-10 text-secondary me-3">
                            <i class="bi bi-arrow-return-left"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Returned</p>
                            <div class="h3 mb-0" x-text="stats.returned">0</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Failed -->
        <div class="col-xl-4 col-sm-6">
            <div class="card stats-card">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-danger bg-opacity-10 text-danger me-3">
                            <i class="bi bi-x-circle-fill"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Failed</p>
                            <div class="h3 mb-0" x-text="stats.failed">0</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Directory Card -->
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="h5 card-title mb-0">Shipments Directory</h2>
                </div>
                <div class="col-auto">
                    <div class="d-flex gap-2">
                        <div class="position-relative">
                            <input type="search" 
                                   class="form-control form-control-sm" 
                                   placeholder="Search..."
                                   x-model.debounce.300ms="searchQuery"
                                   @input="filterData()"
                                   style="width: 250px;">
                            <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted"></i>
                        </div>
                        <select class="form-select form-select-sm" 
                                x-model="statusFilter" 
                                @change="filterData()"
                                style="width: 150px;">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="in_transit">In Transit</option>
                            <option value="delivered">Delivered</option>
                            <option value="returned">Returned</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th @click="sortBy('shipment_no')" class="sortable ps-4">Shipment No</th>
                            <th>Order No</th>
                            <th>Carrier</th>
                            <th>Tracking No</th>
                            <th @click="sortBy('status')" class="sortable">Status</th>
                            <th @click="sortBy('shipped_at')" class="sortable">Shipped At</th>
                            <th @click="sortBy('delivered_at')" class="sortable">Delivered At</th>
                            <th style="width: 120px;" class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="items.length === 0">
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <div x-show="isLoading" class="spinner-border text-primary spinner-border-sm mb-2" role="status"></div>
                                    <div x-show="!isLoading">
                                        <i class="bi bi-truck fs-2 d-block mb-2 text-muted"></i>
                                        No shipments found.
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <template x-for="item in items" :key="item.id">
                            <tr>
                                <td class="ps-4 fw-medium text-dark" x-text="item.shipment_no"></td>
                                <td class="fw-semibold text-secondary" x-text="item.order ? item.order.order_number : ('Order #' + item.order_id)"></td>
                                <td x-text="item.carrier_name || '-'"></td>
                                <td class="font-monospace" x-text="item.tracking_no || '-'"></td>
                                <td>
                                    <span class="badge rounded-pill px-3 py-1.5" 
                                          :class="{
                                              'bg-warning-subtle text-warning': item.status === 'pending',
                                              'bg-primary-subtle text-primary': item.status === 'in_transit',
                                              'bg-success-subtle text-success': item.status === 'delivered',
                                              'bg-secondary-subtle text-secondary': item.status === 'returned',
                                              'bg-danger-subtle text-danger': item.status === 'failed'
                                          }"
                                          x-text="item.status.toUpperCase()"></span>
                                </td>
                                <td x-text="item.shipped_at ? new Date(item.shipped_at).toLocaleString() : '-'"></td>
                                <td x-text="item.delivered_at ? new Date(item.delivered_at).toLocaleString() : '-'"></td>
                                <td class="text-end pe-4">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center gap-2" href="#" @click.prevent="openTrackingModal(item)">
                                                    <i class="bi bi-eye text-primary"></i> Track History
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center gap-2" href="#" @click.prevent="openStatusModal(item)">
                                                    <i class="bi bi-pencil-square text-info"></i> Update Status
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center gap-2" href="#" @click.prevent="openAddEventModal(item)">
                                                    <i class="bi bi-plus-circle text-success"></i> Add Event
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

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center p-3 border-top" x-show="paginator.total > 0">
                <div class="text-muted small">
                    Showing <span x-text="paginator.from"></span> to 
                    <span x-text="paginator.to"></span> of 
                    <span x-text="paginator.total"></span> results
                </div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item" :class="{ 'disabled': currentPage === 1 }">
                            <a class="page-link" href="#" @click.prevent="goToPage(currentPage - 1)">Previous</a>
                        </li>
                        <template x-for="(page, index) in visiblePages" :key="`page-${index}`">
                            <li class="page-item" :class="{ 'active': page === currentPage }">
                                <a class="page-link" href="#" @click.prevent="page !== '...' && goToPage(page)" x-text="page"></a>
                            </li>
                        </template>
                        <li class="page-item" :class="{ 'disabled': currentPage === paginator.last_page }">
                            <a class="page-link" href="#" @click.prevent="goToPage(currentPage + 1)">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">Update Shipment Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <form @submit.prevent="saveStatus">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                            <select class="form-select" x-model="statusForm.status" required>
                                <option value="pending">Pending</option>
                                <option value="in_transit">In Transit</option>
                                <option value="delivered">Delivered</option>
                                <option value="returned">Returned</option>
                                <option value="failed">Failed</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Hub / Location</label>
                            <input type="text" class="form-control" x-model="statusForm.location" placeholder="e.g. Mumbai Gateway Hub">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Note / Description</label>
                            <textarea class="form-control" rows="3" x-model="statusForm.description" placeholder="Optional details for tracking history"></textarea>
                        </div>
                        <div class="modal-footer border-top-0 pt-0 px-0">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="saving">
                                <span x-show="saving" class="spinner-border spinner-border-sm me-1" role="status"></span>
                                Update Status
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Tracking History Modal -->
    <div class="modal fade" id="trackingModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        Tracking Timeline: <span class="text-primary font-monospace" x-text="selectedShipment ? selectedShipment.shipment_no : ''"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <template x-if="trackingEvents.length === 0">
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-journal-x fs-2 d-block mb-2"></i>
                            No tracking events logged yet.
                        </div>
                    </template>
                    <div class="timeline">
                        <template x-for="(event, idx) in trackingEvents" :key="event.id">
                            <div class="d-flex mb-4">
                                <div class="timeline-badge bg-primary text-white d-flex align-items-center justify-content-center rounded-circle me-3" style="width: 32px; height: 32px; flex-shrink: 0;">
                                    <i class="bi bi-record-circle"></i>
                                </div>
                                <div class="timeline-panel border-bottom pb-2 w-100">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="fw-bold mb-0 text-dark" x-text="event.event_name"></h6>
                                        <small class="text-muted" x-text="new Date(event.occurred_at).toLocaleString()"></small>
                                    </div>
                                    <div class="text-secondary small mt-1">
                                        <i class="bi bi-geo-alt me-1 text-danger"></i><span class="fw-medium" x-text="event.location || 'Unknown Hub'"></span>
                                    </div>
                                    <p class="text-muted mt-2 mb-0" x-text="event.description || ''"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Custom Event Modal -->
    <div class="modal fade" id="addEventModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">Add Tracking Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <form @submit.prevent="saveTrackingEvent">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Event Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" x-model="eventForm.event_name" required placeholder="e.g. Package Sorted at Hub">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Location</label>
                            <input type="text" class="form-control" x-model="eventForm.location" placeholder="e.g. New Delhi Hub">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea class="form-control" rows="3" x-model="eventForm.description" placeholder="Detailed logs or update note..."></textarea>
                        </div>
                        <div class="modal-footer border-top-0 pt-0 px-0">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success" :disabled="saving">
                                <span x-show="saving" class="spinner-border spinner-border-sm me-1" role="status"></span>
                                Add Event
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/ubuntu/metis/resources/views/shipping/shipments.blade.php ENDPATH**/ ?>