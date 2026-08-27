import re

with open('resources/views/orders/complaints/index.blade.php', 'r') as f:
    content = f.read()

start_marker = '<div class="modal-body p-4 bg-body-tertiary d-flex flex-column" style="overflow: hidden;">'
end_marker = '</div> <!-- End Tab 1 -->\n                            </div> <!-- End Tab Content -->\n                </div>'

new_content = """<div class="modal-body p-0 bg-body-tertiary" style="overflow: hidden;">
                    <div class="row g-0 h-100 pvm-layout">
                        <!-- Left Column: Form & Actions -->
                        <div class="col-lg-6 d-flex flex-column border-end bg-body-tertiary h-100 pvm-left">
                            <div class="p-4">
                                <!-- Order Search (Create Mode Only) -->
                                <div x-show="!isEditing" class="mb-4">
                                    <label class="form-label fw-bold text-body-emphasis mb-2 small text-uppercase"><i class="bi bi-search me-2 text-primary"></i>Lookup Order</label>
                                    <div class="input-group input-group-sm shadow-sm rounded-3 overflow-hidden">
                                        <input type="text" class="form-control border-0 bg-body px-3" x-model="searchQueryOrder" placeholder="Order ID or Mobile..." @keydown.enter.prevent="searchOrders">
                                        <button class="btn btn-primary px-3 fw-semibold" type="button" @click="searchOrders" :disabled="isSearchingOrders">
                                            <span x-show="!isSearchingOrders">Search</span>
                                            <span x-show="isSearchingOrders"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span></span>
                                        </button>
                                    </div>
                                    <div class="mt-1 text-danger small fw-medium px-1" x-show="searchOrderError" x-text="searchOrderError"></div>
                                    
                                    <!-- Search Results List -->
                                    <template x-if="fetchedOrders && fetchedOrders.length > 0">
                                        <div class="mt-2 border rounded-3 overflow-hidden shadow-sm bg-body">
                                            <ul class="list-group list-group-flush small">
                                                <template x-for="ord in fetchedOrders" :key="ord.id">
                                                    <li class="list-group-item list-group-item-action py-2 cursor-pointer" @click="selectOrderForComplaint(ord.id)">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div class="fw-bold" x-text="ord.order_no"></div>
                                                            <div class="text-muted" x-text="ord.customer?.firstname || ''"></div>
                                                            <span class="badge" :class="getStatusTheme(ord.lifecycle_status) ? 'text-bg-' + getStatusTheme(ord.lifecycle_status) + '-subtle text-' + getStatusTheme(ord.lifecycle_status) + '-emphasis' : 'text-bg-secondary-subtle'" x-text="ord.status_label"></span>
                                                        </div>
                                                    </li>
                                                </template>
                                            </ul>
                                        </div>
                                    </template>
                                </div>

                                <!-- Complaint Details Form -->
                                <div class="card border-0 shadow-sm rounded-4 mb-4">
                                    <div class="card-body p-3">
                                        <h6 class="fw-bold mb-3 d-flex align-items-center gap-2 text-body-emphasis border-bottom pb-2">
                                            <i class="bi bi-pencil-square text-warning fs-6"></i> Complaint Details
                                        </h6>
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label fw-semibold small text-uppercase text-muted" style="font-size: 0.7rem;">Subject <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control form-control-sm bg-body-secondary border-0" x-model="form.subject" required placeholder="Brief summary">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label fw-semibold small text-uppercase text-muted" style="font-size: 0.7rem;">Description <span class="text-danger">*</span></label>
                                                <textarea class="form-control form-control-sm bg-body-secondary border-0" rows="3" x-model="form.description" required placeholder="Detailed information..."></textarea>
                                            </div>
                                            
                                            <div class="col-6" x-show="!selectedOrderDetails && !isEditing">
                                                <label class="form-label fw-semibold small text-uppercase text-muted" style="font-size: 0.7rem;">Order No <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control form-control-sm bg-body-secondary border-0" x-model="form.order_no" :required="!isEditing && !selectedOrderDetails" placeholder="ORD-0001">
                                            </div>
                                            <div class="col-6" x-show="!selectedOrderDetails && !isEditing">
                                                <label class="form-label fw-semibold small text-uppercase text-muted" style="font-size: 0.7rem;">Cust ID</label>
                                                <input type="number" class="form-control form-control-sm bg-body-secondary border-0" x-model="form.customer_id">
                                            </div>

                                            <div class="col-4">
                                                <label class="form-label fw-semibold small text-uppercase text-muted" style="font-size: 0.7rem;">Category <span class="text-danger">*</span></label>
                                                <select class="form-select form-select-sm bg-body-secondary border-0" x-model="form.category" required>
                                                    <option value="other">Other</option>
                                                    <option value="delivery_delay">Delay</option>
                                                    <option value="damaged_item">Damaged</option>
                                                    <option value="missing_item">Missing</option>
                                                    <option value="wrong_item">Wrong</option>
                                                    <option value="payment_issue">Payment</option>
                                                    <option value="poor_service">Service</option>
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <label class="form-label fw-semibold small text-uppercase text-muted" style="font-size: 0.7rem;">Priority <span class="text-danger">*</span></label>
                                                <select class="form-select form-select-sm bg-body-secondary border-0" x-model="form.priority" required>
                                                    <option value="low">Low</option>
                                                    <option value="medium">Medium</option>
                                                    <option value="high">High</option>
                                                    <option value="urgent">Urgent</option>
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <label class="form-label fw-semibold small text-uppercase text-muted" style="font-size: 0.7rem;">Assignee</label>
                                                <select class="form-select form-select-sm bg-body-secondary border-0" x-model="form.assigned_to">
                                                    <option value="">Unassigned</option>
                                                    <template x-for="user in assignableUsers" :key="user.id">
                                                        <option :value="user.id" x-text="user.name"></option>
                                                    </template>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Update Status & Resolution (Edit Mode) -->
                                <template x-if="isEditing">
                                    <div class="card border-0 shadow-sm rounded-4 mb-4 border border-success border-opacity-25 bg-success bg-opacity-10">
                                        <div class="card-body p-3">
                                            <h6 class="fw-bold mb-2 text-success d-flex align-items-center gap-2" style="font-size:0.9rem;">
                                                <i class="bi bi-shield-check"></i> Status & Resolution
                                            </h6>
                                            <div class="row g-2 align-items-start">
                                                <div class="col-4">
                                                    <select class="form-select form-select-sm bg-body border-0 shadow-sm text-body-emphasis fw-bold" x-model="form.status" required>
                                                        <option value="open">Open</option>
                                                        <option value="in_progress">In Progress</option>
                                                        <option value="resolved">Resolved</option>
                                                        <option value="closed">Closed</option>
                                                    </select>
                                                </div>
                                                <div class="col-8">
                                                    <textarea class="form-control form-control-sm border-0 bg-body shadow-sm" rows="1" x-model="form.resolution_notes" placeholder="Add resolution note..."></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <!-- Activity & Communication Feed -->
                                <template x-if="isEditing">
                                    <div class="card border-0 shadow-sm rounded-4">
                                        <div class="card-header bg-primary bg-opacity-10 border-bottom-0 py-2 px-3">
                                            <h6 class="fw-bold mb-0 text-primary d-flex align-items-center gap-2" style="font-size:0.9rem;">
                                                <i class="bi bi-chat-text"></i> Activity Feed
                                            </h6>
                                        </div>
                                        <div class="card-body p-0 bg-body d-flex flex-column" style="max-height: 400px;">
                                            <div class="p-3 bg-body-tertiary overflow-auto flex-grow-1 border-bottom" style="min-height:200px;">
                                                <template x-if="timelineFeed && timelineFeed.length > 0">
                                                    <div class="position-relative ms-2 ps-3 border-start border-primary border-opacity-25 border-2">
                                                        <template x-for="(item, idx) in timelineFeed" :key="idx">
                                                            <div class="position-relative mb-3">
                                                                <template x-if="item._type === 'reply'">
                                                                    <div>
                                                                        <div class="position-absolute bg-primary rounded-circle shadow-sm" style="width: 10px; height: 10px; left: -21px; top: 8px; border: 2px solid var(--bs-body-bg);"></div>
                                                                        <div class="card border-0 shadow-sm rounded-3 bg-primary bg-opacity-10 ms-1">
                                                                            <div class="card-body p-2">
                                                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                                                    <span class="fw-bold text-primary" style="font-size:0.75rem;" x-text="item.user ? (item.user.name || item.user.first_name) : 'Agent'"></span>
                                                                                    <span class="text-primary opacity-75" style="font-size: 0.65rem;" x-text="formatDateTime(item.created_at)"></span>
                                                                                </div>
                                                                                <p class="text-body-emphasis mb-0 lh-sm" style="font-size:0.8rem;" x-show="item.message" x-html="(item.message || '').replace(/\n/g, '<br>')"></p>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </template>
                                                                <template x-if="item._type === 'log'">
                                                                    <div class="ms-1 d-flex flex-column opacity-75">
                                                                        <div class="position-absolute bg-secondary rounded-circle" style="width: 8px; height: 8px; left: -20px; top: 4px; border: 1px solid var(--bs-body-bg);"></div>
                                                                        <div class="w-100 d-flex gap-2 align-items-center mb-1" style="font-size:0.7rem;">
                                                                            <span class="badge bg-secondary rounded-pill fw-medium py-0" x-text="item.status ? item.status.replace(/_/g, ' ').toUpperCase() : 'UPDATE'"></span>
                                                                            <span x-text="item.user ? (item.user.name || item.user.first_name) : 'System'"></span>
                                                                            <span class="text-muted" x-text="formatDateTime(item.created_at)"></span>
                                                                        </div>
                                                                        <template x-if="item.notes">
                                                                            <div class="d-block w-100 text-muted fst-italic lh-sm" style="font-size:0.75rem;" x-text="`Note: ${item.notes}`"></div>
                                                                        </template>
                                                                    </div>
                                                                </template>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </template>
                                                <template x-if="!timelineFeed || timelineFeed.length === 0">
                                                    <div class="text-center p-3 text-muted" style="font-size:0.8rem;">No activity history recorded yet.</div>
                                                </template>
                                            </div>
                                            <!-- Reply Input Area -->
                                            <div class="p-2 bg-body rounded-bottom-4">
                                                <div class="input-group input-group-sm shadow-sm">
                                                    <input type="text" class="form-control border-0 bg-body-tertiary" x-model="replyMessage" placeholder="Type a reply..." @keydown.enter.prevent="postReply">
                                                    <button type="button" class="btn btn-primary" @click="postReply" :disabled="!replyMessage.trim() || isReplying">
                                                        <i class="bi bi-send" x-show="!isReplying"></i>
                                                        <span x-show="isReplying" class="spinner-border spinner-border-sm"></span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Right Column: Order Preview -->
                        <div class="col-lg-6 h-100 bg-body pvm-right border-start">
                            <template x-if="!selectedOrderDetails">
                                <div class="d-flex h-100 align-items-center justify-content-center p-4">
                                    <div class="text-center opacity-50">
                                        <i class="bi bi-receipt fs-1 text-muted mb-2 d-block"></i>
                                        <h6 class="fw-bold text-muted mb-0">Order Preview</h6>
                                        <p class="small text-muted mb-0">Select an order to view its details here.</p>
                                    </div>
                                </div>
                            </template>
                            <template x-if="selectedOrderDetails">
                                <div class="p-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="fw-bold mb-0 text-body-emphasis d-flex align-items-center gap-2">
                                            <i class="bi bi-receipt text-primary fs-5"></i> Order <span x-text="selectedOrderDetails.orderNumber"></span>
                                        </h6>
                                        <span class="badge text-bg-primary-subtle text-primary-emphasis rounded-pill" x-text="selectedOrderDetails.statusLabel"></span>
                                    </div>

                                    <!-- Mini Stats -->
                                    <div class="row g-2 mb-3">
                                        <div class="col-4">
                                            <div class="card bg-primary bg-opacity-10 border-0 rounded-3 h-100">
                                                <div class="card-body p-2 text-center">
                                                    <p class="small text-primary mb-0 fw-semibold text-uppercase" style="font-size: 0.6rem;">Payment</p>
                                                    <p class="fw-bold mb-0 text-body-emphasis" style="font-size: 0.8rem;" x-text="selectedOrderDetails.paymentMethod || 'N/A'"></p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="card bg-info bg-opacity-10 border-0 rounded-3 h-100">
                                                <div class="card-body p-2 text-center">
                                                    <p class="small text-info mb-0 fw-semibold text-uppercase" style="font-size: 0.6rem;">Order Date</p>
                                                    <p class="fw-bold mb-0 text-body-emphasis" style="font-size: 0.8rem;" x-text="selectedOrderDetails.orderDate ? formatDate(selectedOrderDetails.orderDate) : 'N/A'"></p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="card bg-success bg-opacity-10 border-0 rounded-3 h-100">
                                                <div class="card-body p-2 text-center">
                                                    <p class="small text-success mb-0 fw-semibold text-uppercase" style="font-size: 0.6rem;">Total</p>
                                                    <p class="fw-bold mb-0 text-body-emphasis" style="font-size: 0.8rem;" x-text="`₹ ${formatCurrency(selectedOrderDetails.total)}`"></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Customer Info -->
                                    <div class="card border-0 shadow-sm rounded-4 mb-3 bg-body-tertiary">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <img :src="selectedOrderDetails.customer.avatar || '/assets/images/avatar-placeholder.png'" class="rounded-circle shadow-sm" width="32" height="32" alt="Customer" x-on:error="$el.src='/assets/images/avatar-placeholder.png'">
                                                <div class="lh-sm">
                                                    <h6 class="fw-bold mb-0" style="font-size: 0.85rem;" x-text="selectedOrderDetails.customer.name"></h6>
                                                    <span class="text-muted" style="font-size: 0.75rem;" x-text="selectedOrderDetails.customer.phone || selectedOrderDetails.customer.email"></span>
                                                </div>
                                            </div>
                                            <div class="row g-2 mt-1">
                                                <div class="col-6">
                                                    <p class="fw-bold text-muted text-uppercase mb-0" style="font-size: 0.6rem;">Shipping Address</p>
                                                    <p class="mb-0 text-body-emphasis lh-sm" style="font-size: 0.75rem;" x-text="selectedOrderDetails.shippingAddress ? selectedOrderDetails.shippingAddress.formatted : 'N/A'"></p>
                                                </div>
                                                <div class="col-6">
                                                    <p class="fw-bold text-muted text-uppercase mb-0" style="font-size: 0.6rem;">Fulfillment Center</p>
                                                    <p class="mb-0 text-body-emphasis lh-sm" style="font-size: 0.75rem;" x-text="selectedOrderDetails.warehouse ? selectedOrderDetails.warehouse.name : 'Unassigned'"></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Order Items -->
                                    <div class="card border-0 shadow-sm rounded-4 mb-3 overflow-hidden">
                                        <div class="card-header bg-body border-bottom py-2 px-3 d-flex justify-content-between align-items-center">
                                            <h6 class="fw-bold mb-0 text-body-emphasis d-flex align-items-center gap-2" style="font-size:0.85rem;">
                                                <i class="bi bi-box-seam text-primary"></i> Order Items
                                            </h6>
                                            <span class="badge text-bg-primary-subtle text-primary-emphasis rounded-pill" style="font-size:0.7rem;" x-text="`${selectedOrderDetails.itemCount} Items`"></span>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-borderless table-sm align-middle mb-0 text-nowrap" style="font-size:0.75rem;">
                                                <thead class="bg-body-tertiary">
                                                    <tr>
                                                        <th class="fw-semibold text-muted py-2 ps-3">Product</th>
                                                        <th class="fw-semibold text-muted py-2 text-center">Qty</th>
                                                        <th class="fw-semibold text-muted py-2 text-end pe-3">Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <template x-for="(item, idx) in selectedOrderDetails.items" :key="idx">
                                                        <tr class="border-bottom">
                                                            <td class="ps-3 py-2">
                                                                <div class="d-flex align-items-center gap-2">
                                                                    <img :src="item.image || '/assets/images/product-placeholder.svg'" class="rounded-2 shadow-sm object-fit-cover" width="32" height="32" :alt="item.name" x-on:error="$el.src='/assets/images/product-placeholder.svg'">
                                                                    <div class="text-wrap" style="max-width: 150px;">
                                                                        <p class="fw-bold text-body-emphasis mb-0 lh-sm" x-text="item.name"></p>
                                                                        <p class="text-muted mb-0 font-monospace" style="font-size: 0.65rem;" x-text="item.sku || 'No SKU'"></p>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td class="text-center py-2"><span class="badge bg-secondary bg-opacity-10 text-body-emphasis" x-text="item.quantity || 0"></span></td>
                                                            <td class="text-end pe-3 py-2 fw-bold text-primary" x-text="`₹ ${((parseFloat(item.price || 0) * parseFloat(item.quantity || 0)) - parseFloat(item.discount || 0) + parseFloat(item.tax || 0)).toFixed(2)}`"></td>
                                                        </tr>
                                                    </template>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- Returns Tracking -->
                                    <template x-if="selectedOrderDetails.original && (selectedOrderDetails.original.order_returns && selectedOrderDetails.original.order_returns.length > 0 || selectedOrderDetails.original.orderReturns && selectedOrderDetails.original.orderReturns.length > 0)">
                                        <div class="card border-0 shadow-sm rounded-4 bg-danger bg-opacity-10 border border-danger border-opacity-25 p-3 mb-3">
                                            <h6 class="fw-bold mb-2 text-danger d-flex align-items-center gap-2" style="font-size: 0.85rem;">
                                                <i class="bi bi-arrow-return-left"></i> Returns & Refunds
                                            </h6>
                                            <template x-for="(ret, i) in (selectedOrderDetails.original.order_returns || selectedOrderDetails.original.orderReturns)" :key="ret.id || i">
                                                <div class="d-flex justify-content-between align-items-center mb-1 pb-1 border-bottom border-danger border-opacity-10 last:border-0 last:pb-0 last:mb-0">
                                                    <div>
                                                        <p class="fw-bold text-body-emphasis mb-0" style="font-size:0.75rem;" x-text="ret.return_no || 'Return'"></p>
                                                        <p class="text-secondary lh-sm mb-0" style="font-size:0.7rem;">Reason: <span x-text="ret.reason || 'N/A'"></span></p>
                                                    </div>
                                                    <span class="badge bg-danger" style="font-size:0.7rem;" x-text="ret.status"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>"""

idx1 = content.find(start_marker)
idx2 = content.find(end_marker, idx1)

if idx1 != -1 and idx2 != -1:
    content = content[:idx1] + new_content + content[idx2 + len(end_marker):]
    with open('resources/views/orders/complaints/index.blade.php', 'w') as f:
        f.write(content)
    print("Success")
else:
    print("Failed to find markers")
