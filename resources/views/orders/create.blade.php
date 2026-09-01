@extends('layouts.app')
@section('title', 'Create Order')
@section('page', 'orders.create')

@section('content')
    <script>
        window.__INITIAL_ORDER_CUSTOMER__ = @json($initialCustomer ? $initialCustomer->toArray() : null);
        window.__INITIAL_ORDER_TO_EDIT__ = @json($initialOrder ? $initialOrder->toArray() : null);
    </script>

    <div x-data="createOrderApp(window.__INITIAL_ORDER_CUSTOMER__, window.__INITIAL_ORDER_TO_EDIT__)"
         @call-log-added.window="if(customerDetails) { isCallLoggedOrClosed = true; window.location.href = '{{ route('dashboard') }}'; }">
    
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4 mb-lg-5 mb-xl-6">
        <div>
            <h1 class="h3 mb-1 d-flex align-items-center">
                <template x-if="isConfirmMode"><i class="bi bi-check-circle me-2"></i> Confirm Order</template>
                <template x-if="!isConfirmMode"><i class="bi bi-cart-check me-2"></i> <span x-text="editingOrderId ? 'Edit Order' : 'Create New Order'"></span></template>
                <template x-if="editingOrderId && originalOrder">
                    <span class="ms-2 text-primary">#<span x-text="originalOrder.order_no"></span></span>
                </template>
            </h1>
            <div class="text-body-secondary mb-0 d-flex align-items-center flex-wrap gap-2 mt-2">
                <span x-show="!editingOrderId && !isConfirmMode" x-cloak>Manage customer profile, add products to cart, and process order checkout.</span>
                <template x-if="editingOrderId && originalOrder">
                    <div class="d-flex align-items-center flex-wrap gap-3" x-cloak>
                        <div>
                            <span class="badge text-bg-warning" x-show="!isConfirmMode">Edit Mode</span>
                            <span class="badge text-bg-info" x-show="isConfirmMode">Confirmation Mode</span>
                        </div>
                        <div class="vr bg-secondary opacity-25"></div>
                        <div><i class="bi bi-tag me-1"></i><strong>Status:</strong> <span class="text-capitalize ms-1" x-text="(originalOrder.status || '').replace(/_/g, ' ')"></span></div>
                        <div class="vr bg-secondary opacity-25"></div>
                        <div><i class="bi bi-calendar3 me-1"></i><strong>Date:</strong> <span class="ms-1" x-text="new Date(originalOrder.order_date).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year:'numeric' })"></span></div>
                        <div class="vr bg-secondary opacity-25"></div>
                        <div><i class="bi bi-currency-rupee me-1"></i><strong>Net Total:</strong> <span class="ms-1" x-text="parseFloat(originalOrder.net_amount).toFixed(2)"></span></div>
                    </div>
                </template>
            </div>
        </div>
        <div class="d-flex gap-2">
            <template x-if="editingOrderId || isConfirmMode">
                <a href="{{ route('orders') }}" data-bypass="true" class="btn btn-outline-danger shadow-sm" @click="isCallLoggedOrClosed = true">
                    <i class="bi bi-x-circle me-1"></i> <span x-text="isConfirmMode ? 'Cancel Confirmation' : 'Cancel Edit Mode'"></span>
                </a>
            </template>
            @can('skip-call-log')
            <button type="button" class="btn btn-outline-danger shadow-sm" x-show="customerDetails" @click="isCallLoggedOrClosed = true; window.location.href = '{{ route('dashboard') }}'" title="Bypass Call Logging">
                <i class="bi bi-door-closed me-1"></i> Close Profile
            </button>
            @endcan
            <button type="button" class="btn btn-primary shadow-sm" x-show="customerDetails" @click="$dispatch('open-call-tagging-modal', {customerId: customerDetails ? customerDetails.id : null})">
                <i class="bi bi-headset me-2"></i> Log Call & Close Profile
            </button>
        </div>
    </div>

    {{-- Alert --}}
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-4">
        <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div @customer-updated.window="loadAddresses()" @toggle-cart-sidebar.window="isCartSidebarOpen = !isCartSidebarOpen" class="row g-4">
        <div :class="isCartSidebarOpen ? 'col-xl-8' : 'col-xl-12'" style="transition: all 0.3s ease;">
            {{-- Confirmation Details (Visible only in Confirm Mode) --}}
            <template x-if="isConfirmMode && originalOrder">
                <div class="card shadow-sm border-0 mb-4 bg-info bg-opacity-10 border-info border-opacity-25">
                    <div class="card-header bg-transparent border-bottom-0 py-3 px-4">
                        <h5 class="mb-0 fw-bold text-info-emphasis"><i class="bi bi-info-circle me-2"></i>Confirmation Details</h5>
                    </div>
                    <div class="card-body p-4 pt-0 row">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <template x-if="originalOrder.scheduled_confirmation_date">
                                <div class="alert bg-body shadow-sm mb-0 rounded-3 border-0">
                                    <div class="d-flex align-items-center mb-1">
                                        <i class="bi bi-calendar-event fs-5 me-2 text-info"></i>
                                        <h6 class="fw-bold text-info-emphasis mb-0">Currently Scheduled</h6>
                                    </div>
                                    <div class="small ms-4 ps-1 text-body">
                                        <div class="mb-1"><strong class="text-body-emphasis">Date:</strong> <span class="fw-medium text-body" x-text="new Date(originalOrder.scheduled_confirmation_date).toLocaleString('en-IN', { day: '2-digit', month: 'short', year:'numeric', hour: '2-digit', minute:'2-digit', hour12: true })"></span></div>
                                        <div><strong class="text-body-emphasis">Previous Attempts:</strong> <span class="badge bg-warning text-body-emphasis ms-1" x-text="originalOrder.confirmation_attempts || 0"></span></div>
                                    </div>
                                </div>
                            </template>
                            <template x-if="!originalOrder.scheduled_confirmation_date">
                                <div class="text-body-secondary small fst-italic">No future confirmation scheduled.</div>
                            </template>
                        </div>
                        
                        <div class="col-md-6">
                            <template x-if="originalOrder.status_logs && originalOrder.status_logs.length > 0">
                                <div>
                                    <h6 class="fw-bold mb-3 text-body-emphasis border-bottom border-info border-opacity-25 pb-2">
                                        <i class="bi bi-clock-history me-2"></i>Status History
                                    </h6>
                                    <div class="position-relative ms-2 ps-3 border-start border-info border-opacity-50 border-2" style="max-height: 200px; overflow-y: auto;">
                                        <template x-for="log in originalOrder.status_logs" :key="log.id">
                                            <div class="position-relative mb-3">
                                                <div class="position-absolute bg-info rounded-circle" style="width: 10px; height: 10px; left: -22px; top: 5px;"></div>
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <p class="fw-bold text-body-emphasis mb-0 small text-capitalize" x-text="log.status.replace(/_/g, ' ')"></p>
                                                        <p class="text-body-secondary mb-0" style="font-size: 0.75rem;">
                                                            <span x-text="new Date(log.created_at).toLocaleString('en-IN', { day: '2-digit', month: 'short', year:'numeric', hour: '2-digit', minute:'2-digit', hour12: true })"></span>
                                                            <template x-if="log.user">
                                                                <span> &bull; by <span x-text="log.user.name"></span></span>
                                                            </template>
                                                        </p>
                                                        <p class="text-secondary small mt-1 lh-sm mb-0" x-show="log.notes" x-text="log.notes"></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                            <template x-if="!originalOrder.status_logs || originalOrder.status_logs.length === 0">
                                <div class="text-body-secondary small fst-italic">No status history available.</div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>

            <div id="customer-workspace" class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-transparent border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 fw-bold"><i class="bi bi-person-badge me-2 text-primary"></i>Customer Workspace</h5>
                    </div>
                    <div class="d-flex align-items-center gap-3" x-show="customerDetails" x-cloak>
                        <div class="form-check form-switch cursor-pointer ms-2 mb-0 d-flex align-items-center" title="Toggle Workspace">
                            <input class="form-check-input mt-0 me-2 shadow-sm" type="checkbox" role="switch" id="workspaceToggleBtn" x-model="showCustomerWorkspace" style="cursor: pointer;">
                            <label class="form-check-label fw-bold text-body-secondary text-uppercase mb-0" for="workspaceToggleBtn" style="cursor: pointer; font-size: 10px; letter-spacing: 0.5px;" x-text="showCustomerWorkspace ? 'Hide Profile' : 'View Profile'"></label>
                        </div>
                        @can('customer-edit')
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-sm hover-shadow transition-all" @click="$dispatch('open-add-customer-modal', {customer: customerDetails})">
                            <i class="bi bi-pencil-square me-1"></i>Edit Profile
                        </button>
                        @endcan
                    </div>
                </div>
                <div class="card-body p-4 p-lg-4" x-show="showCustomerWorkspace">
                    <div class="card border shadow-sm mb-4" x-show="customerDetails" x-cloak>

                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="d-flex align-items-start gap-4 flex-wrap">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-circle bg-primary text-white fw-bold d-flex align-items-center justify-content-center shadow-sm overflow-hidden" style="width: 48px; height: 48px;">
                                            <template x-if="customerDetails.avatar">
                                                <img :src="'/storage/' + customerDetails.avatar" class="w-100 h-100 object-fit-cover" :alt="customerDisplayName">
                                            </template>
                                            <template x-if="!customerDetails.avatar">
                                                <img src="{{ asset('assets/images/farmersprofileimage.png') }}" class="w-100 h-100 object-fit-cover" :alt="customerDisplayName">
                                            </template>
                                        </div>
                                        <div>
                                            <h5 class="mb-1 fw-bold" x-text="customerDisplayName"></h5>
                                            <div class="small text-body-secondary d-flex align-items-center gap-2">
                                                <span x-text="customerDetails.party_code"></span>
                                                <span class="badge text-bg-success-subtle text-success-emphasis" x-text="customerDetails.status || 'Active'"></span>
                                                <span class="badge text-bg-info-subtle text-info-emphasis" x-show="customerDetails.kyc_completed">KYC Verified</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Referral Stats in Header -->
                                    <div class="d-flex gap-3 align-items-start border-start ps-3 ms-1 border-secondary border-opacity-25" x-show="customerDetails.referral_code || customerDetails?.referrer || customerDetails?.total_farmers_referred > 0" x-cloak>
                                        <!-- Referral Code -->
                                        <div x-show="customerDetails.referral_code" style="min-width: 110px;">
                                            <span class="text-body-secondary d-block fw-bold text-uppercase" style="font-size: 9px; letter-spacing: 0.5px;">Referral Code</span>
                                            <div class="d-flex align-items-center gap-2 mt-1">
                                                <span class="fw-bold text-primary font-monospace bg-primary bg-opacity-10 px-2 py-1 rounded" style="letter-spacing: 1px; font-size: 11px;" x-text="customerDetails.referral_code"></span>
                                            </div>
                                        </div>
                                        
                                        <!-- Referrer Details -->
                                        <div x-show="customerDetails?.referrer" class="border-start ps-3 border-secondary border-opacity-25" style="min-width: 140px;">
                                            <span class="text-body-secondary d-block fw-bold text-uppercase" style="font-size: 9px; letter-spacing: 0.5px;">Referred By</span>
                                            <div class="d-flex flex-column mt-1">
                                                <span class="fw-bold text-body-emphasis lh-1" style="font-size: 11px;" x-text="(customerDetails?.referrer?.firstname || '') + ' ' + (customerDetails?.referrer?.lastname || '')"></span>
                                                <span class="text-body-secondary mt-1 lh-1" style="font-size: 10px;"><i class="bi bi-telephone text-primary opacity-75 me-1" style="font-size: 9px;"></i><span x-text="customerDetails?.referrer?.phone"></span></span>
                                            </div>
                                        </div>

                                        <!-- Downline Stats -->
                                        <div x-data="{ showReferralsList: false }" x-show="customerDetails?.total_farmers_referred > 0" class="border-start ps-3 border-secondary border-opacity-25 position-relative" style="min-width: 130px;">
                                            <span class="text-body-secondary d-block fw-bold text-uppercase" style="font-size: 9px; letter-spacing: 0.5px;">Referred Network</span>
                                            <button type="button" @click="showReferralsList = !showReferralsList" class="btn btn-sm btn-outline-success border-0 text-start p-0 d-flex align-items-center gap-1 mt-1">
                                                <span class="fw-bold" style="font-size: 11px;"><i class="bi bi-people me-1"></i><span x-text="customerDetails?.total_farmers_referred || 0"></span> Farmers</span>
                                                <i class="bi" :class="showReferralsList ? 'bi-chevron-up' : 'bi-chevron-down'" style="font-size: 10px;"></i>
                                            </button>
                                            
                                            <!-- Absolute Dropdown -->
                                            <div x-show="showReferralsList" @click.away="showReferralsList = false" class="position-absolute shadow-lg rounded bg-body border border-success border-opacity-25 z-3 p-1 mt-2" style="width: 250px; left: 0; max-height: 200px; overflow-y: auto;" x-cloak>
                                                <template x-for="(ref, index) in customerDetails.referrals" :key="ref.id">
                                                    <div class="d-flex flex-column py-2 px-2 border-bottom border-success border-opacity-10 bg-success bg-opacity-10 rounded mb-1">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <span class="text-success-emphasis fw-bold text-truncate" style="font-size: 10px; max-width: 65%;">
                                                                <span class="text-success opacity-75 me-1" x-text="(index + 1) + '.'"></span><span x-text="(ref.firstname || '') + ' ' + (ref.lastname || '')"></span>
                                                            </span>
                                                            <span class="text-body-secondary" style="font-size: 10px;"><i class="bi bi-telephone text-success opacity-50 me-1" style="font-size: 8px;"></i><span x-text="ref.phone"></span></span>
                                                        </div>
                                                        <div class="mt-1" style="padding-left: 12px;" x-show="ref.addresses && ref.addresses.length > 0 && ref.addresses[0].village">
                                                            <span class="text-body-secondary d-flex align-items-center gap-1 text-truncate" style="font-size: 9px; max-width: 100%;">
                                                                <i class="bi bi-geo-alt-fill text-success opacity-50" style="font-size: 8px;"></i>
                                                                <span x-text="[ref.addresses[0]?.village?.village_name, ref.addresses[0]?.village?.taluka_name, ref.addresses[0]?.village?.district_name].filter(Boolean).join(', ')"></span>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Complaints Stats in Header -->
                                    <div class="d-flex gap-3 align-items-start border-start ps-3 border-secondary border-opacity-25" x-show="customerDetails.total_complaints > 0" x-cloak>
                                        <div style="min-width: 110px;">
                                            <span class="text-body-secondary d-block fw-bold text-uppercase" style="font-size: 9px; letter-spacing: 0.5px;">Complaints</span>
                                            <div class="d-flex align-items-center gap-1 mt-1">
                                                <span class="badge text-bg-warning-subtle border border-warning text-warning-emphasis shadow-sm" style="font-size: 10px;">
                                                    <i class="bi bi-exclamation-circle me-1"></i> Total: <span x-text="customerDetails.total_complaints"></span>
                                                </span>
                                                <template x-if="customerDetails.active_complaints > 0">
                                                    <span class="badge bg-danger shadow-sm" style="font-size: 10px;">
                                                        <i class="bi bi-activity me-1"></i> Active: <span x-text="customerDetails.active_complaints"></span>
                                                    </span>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex flex-column align-items-end gap-2">
                                    <div x-show="customerDetails.created_at" class="badge text-bg-primary-subtle text-primary-emphasis fw-medium" style="font-size: 10px; letter-spacing: 0.5px;">
                                        <i class="bi bi-clock-history me-1"></i> Since: <span x-text="new Date(customerDetails.created_at).toLocaleDateString()"></span> 
                                        (<span x-text="customerDetails.created_at ? Math.max(0, Math.floor((new Date() - new Date(customerDetails.created_at)) / 86400000)) : 0"></span> days)
                                    </div>
                                    <div x-show="customerDetails.updated_at" class="badge text-bg-secondary-subtle text-secondary-emphasis fw-medium" style="font-size: 10px; letter-spacing: 0.5px;">
                                        <i class="bi bi-activity me-1"></i> Active: <span x-text="customerDetails.updated_at ? Math.max(0, Math.floor((new Date() - new Date(customerDetails.updated_at)) / 86400000)) : 0"></span> days ago
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row g-3 small mt-2">
                                <!-- Contact Profile -->
                                <div class="col-md-6 col-lg-3">
                                    <div class="card h-100 border-0 bg-primary bg-opacity-10 shadow-sm rounded-4">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-center mb-2 border-bottom border-primary border-opacity-25 pb-1">
                                                <i class="bi bi-person-lines-fill text-primary me-2"></i>
                                                <h6 class="fw-bold text-primary mb-0" style="text-transform: uppercase; font-size: 11px;">Contact</h6>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mb-1" x-show="customerDetails.phone"><span class="text-body-secondary small">Phone</span><span class="fw-bold text-body-emphasis" style="font-size: 11px;"><i class="bi bi-telephone text-primary me-1" style="font-size: 9px;"></i><span x-text="customerDetails.phone"></span></span></div>
                                            <div class="d-flex justify-content-between align-items-center mb-1" x-show="customerDetails.alternatemobile"><span class="text-body-secondary small">Alt Phone</span><span class="fw-medium text-body-emphasis" style="font-size: 11px;" x-text="customerDetails.alternatemobile"></span></div>
                                            <div class="d-flex justify-content-between align-items-center mb-1" x-show="customerDetails.email"><span class="text-body-secondary small">Email</span><span class="fw-medium text-body-emphasis text-truncate d-inline-block text-end" style="max-width: 130px; font-size: 11px;" :title="customerDetails.email"><span x-text="customerDetails.email"></span></span></div>
                                            <div class="d-flex justify-content-between align-items-center mb-1" x-show="customerDetails.relative_name"><span class="text-body-secondary small">Relative</span><span class="fw-medium text-body-emphasis text-end" style="font-size: 11px;" x-text="customerDetails.relative_name + ' (' + customerDetails.relative_phone + ')'"></span></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Business Details -->
                                <div class="col-md-6 col-lg-3" x-show="customerDetails.category === 'business'" x-cloak>
                                    <div class="card h-100 border-0 bg-info bg-opacity-10 shadow-sm rounded-4">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-center mb-2 border-bottom border-info border-opacity-25 pb-1">
                                                <i class="bi bi-building text-info me-2"></i>
                                                <h6 class="fw-bold text-info mb-0" style="text-transform: uppercase; font-size: 11px;">Business & Identity</h6>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mb-1" x-show="customerDetails.company_name"><span class="text-body-secondary small">Company</span><span class="fw-bold text-body-emphasis text-truncate ms-2" style="font-size: 11px;" x-text="customerDetails.company_name"></span></div>
                                            <div class="d-flex justify-content-between align-items-center mb-1" x-show="customerDetails.category"><span class="text-body-secondary small">Category</span><span class="fw-medium text-body-emphasis text-capitalize"><span class="badge bg-info text-body-emphasis bg-opacity-25" style="font-size: 9px;" x-text="customerDetails.category"></span></span></div>
                                            <div class="d-flex justify-content-between align-items-center mb-1" x-show="customerDetails.gst_no"><span class="text-body-secondary small">GST No</span><span class="fw-medium text-body-emphasis text-uppercase font-monospace" style="font-size: 11px;" x-text="customerDetails.gst_no"></span></div>
                                            <div class="d-flex justify-content-between align-items-center mb-1" x-show="customerDetails.pan_no"><span class="text-body-secondary small">PAN No</span><span class="fw-medium text-body-emphasis text-uppercase font-monospace" style="font-size: 11px;" x-text="customerDetails.pan_no"></span></div>
                                            <div class="d-flex justify-content-between align-items-center mb-1" x-show="customerDetails.aadhaar_last4"><span class="text-body-secondary small">Aadhaar</span><span class="fw-medium text-body-emphasis font-monospace" style="font-size: 11px;" x-text="'**' + customerDetails.aadhaar_last4"></span></div>
                                            <div class="text-center mt-2" x-show="!customerDetails.company_name && !customerDetails.gst_no && !customerDetails.pan_no && !customerDetails.category && !customerDetails.aadhaar_last4"><span class="text-body-secondary fst-italic small" style="font-size: 10px;">No business details</span></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Agriculture Snapshot -->
                                <div class="col-md-6 col-lg-3">
                                    <div class="card h-100 border-0 bg-success bg-opacity-10 shadow-sm rounded-4">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-center mb-2 border-bottom border-success border-opacity-25 pb-1">
                                                <i class="bi bi-tree text-success me-2"></i>
                                                <h6 class="fw-bold text-success mb-0" style="text-transform: uppercase; font-size: 11px;">Agriculture</h6>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mb-1"><span class="text-body-secondary small">Land Area</span><span class="fw-bold text-body-emphasis" style="font-size: 11px;"><span x-text="customerDetails.land_area || '0'"></span> <span x-text="customerDetails.land_unit || ''"></span></span></div>
                                            <div class="d-flex justify-content-between align-items-start mb-1" x-show="customerDetails.crops && customerDetails.crops.length > 0">
                                                <span class="text-body-secondary small text-nowrap me-2 mt-1">Crops</span>
                                                <div class="d-flex gap-1 flex-wrap justify-content-end mt-1">
                                                    <template x-for="crop in (customerDetails.crops || [])"><span class="badge text-bg-success-subtle text-success-emphasis border border-success border-opacity-50" style="font-size: 9px;" x-text="crop"></span></template>
                                                </div>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-start mb-1" x-show="customerDetails.irrigation_type && customerDetails.irrigation_type.length > 0">
                                                <span class="text-body-secondary small text-nowrap me-2 mt-1">Irrigation</span>
                                                <div class="d-flex gap-1 flex-wrap justify-content-end mt-1">
                                                    <template x-for="type in (customerDetails.irrigation_type || [])"><span class="badge bg-success text-white" style="font-size: 9px;" x-text="type"></span></template>
                                                </div>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-start mb-1" x-show="customerDetails.tags && customerDetails.tags.length > 0">
                                                <span class="text-body-secondary small text-nowrap me-2 mt-1">Tags</span>
                                                <div class="d-flex gap-1 flex-wrap justify-content-end mt-1">
                                                    <template x-for="tag in (customerDetails.tags || [])"><span class="badge text-bg-secondary-subtle text-secondary-emphasis" style="font-size: 9px;" x-text="tag"></span></template>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Financial Snapshot -->
                                <div class="col-md-6 col-lg-3">
                                    <div class="card h-100 border-0 bg-warning bg-opacity-10 shadow-sm rounded-4">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-center mb-2 border-bottom border-warning border-opacity-25 pb-1">
                                                <i class="bi bi-wallet2 text-warning-emphasis me-2"></i>
                                                <h6 class="fw-bold text-warning-emphasis mb-0" style="text-transform: uppercase; font-size: 11px;">Financial</h6>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mb-1"><span class="text-body-secondary small">Limit</span><span class="fw-bold text-body-emphasis" style="font-size: 11px;">₹ <span x-text="Number(customerDetails.credit_limit || 0).toLocaleString('en-IN', {minimumFractionDigits: 2})"></span></span></div>
                                            <div class="d-flex justify-content-between align-items-center mb-1"><span class="text-body-secondary small">Outstanding</span><span class="fw-bold" style="font-size: 11px;" :class="Number(customerDetails.outstanding_balance) > 0 ? 'text-danger' : 'text-success'">₹ <span x-text="Number(customerDetails.outstanding_balance || 0).toLocaleString('en-IN', {minimumFractionDigits: 2})"></span></span></div>
                                            <div class="d-flex justify-content-between align-items-center mb-1"><span class="text-body-secondary small">Wallet</span><span class="fw-bold text-success" style="font-size: 11px;">₹ <span x-text="Number(customerDetails.wallet_balance || 0).toLocaleString('en-IN', {minimumFractionDigits: 2})"></span></span></div>
                                            
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="text-body-secondary small">Cr. Days</span><span class="fw-medium text-body-emphasis" style="font-size: 11px;" x-text="(customerDetails.credit_days || '0') + ' Days'"></span>
                                            </div>
                                            
                                            <div class="d-flex justify-content-between align-items-center mb-1 pt-1 border-top border-warning border-opacity-25">
                                                <span class="text-body-secondary small">Total Orders</span><span class="fw-bold text-primary" style="font-size: 11px;" x-text="Math.max(customerDetails.orders_count || 0, (customerDetails.orders || []).length)"></span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mb-1" x-show="customerDetails.orders && customerDetails.orders.length > 0" x-cloak>
                                                <span class="text-body-secondary small">Total Revenue</span>
                                                <span class="fw-bold text-primary" style="font-size: 11px;">₹ <span x-text="(customerDetails.orders || []).filter(o => o.lifecycle_status !== 'cancelled').reduce((sum, o) => sum + Number(o.net_amount), 0).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span></span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mb-1" x-show="customerDetails.orders && customerDetails.orders.length > 0" x-cloak>
                                                <span class="text-body-secondary small">Delivered / Rev</span>
                                                <span class="fw-medium text-success" style="font-size: 10px;">
                                                    <span x-text="(customerDetails.orders || []).filter(o => o.lifecycle_status === 'delivered').length"></span> | ₹ <span x-text="(customerDetails.orders || []).filter(o => o.lifecycle_status === 'delivered').reduce((sum, o) => sum + Number(o.net_amount), 0).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                                                </span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mb-1" x-show="customerDetails.orders && customerDetails.orders.length > 0" x-cloak>
                                                <span class="text-body-secondary small">Returned / Rev</span>
                                                <span class="fw-medium text-danger" style="font-size: 10px;">
                                                    <span x-text="(customerDetails.orders || []).filter(o => o.lifecycle_status === 'returned').length"></span> | ₹ <span x-text="(customerDetails.orders || []).filter(o => o.lifecycle_status === 'returned').reduce((sum, o) => sum + Number(o.net_amount), 0).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                                                </span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mb-1 mt-1 pt-1 border-top border-warning border-opacity-25" x-show="customerDetails.orders?.length > 0 || (customerDetails.last_purchase_at && customerDetails.last_purchase_at !== '0000-00-00 00:00:00')" x-cloak>
                                                <span class="text-body-secondary small">Last Order</span>
                                                <span class="fw-medium text-body-emphasis" style="font-size: 11px;" x-text="customerDetails.orders?.length > 0 ? new Date(customerDetails.orders[0].created_at).toLocaleDateString() : (new Date(customerDetails.last_purchase_at).getFullYear() > 2000 ? new Date(customerDetails.last_purchase_at).toLocaleDateString() : 'N/A')"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>


                                <!-- Notes, Status & Complaints -->
                                <div class="col-12 mt-2 pt-2 border-top" x-show="customerDetails.internal_notes || customerDetails.is_blacklisted">
                                    <div class="d-flex flex-wrap gap-3 align-items-center">
                                        <div x-show="customerDetails.is_blacklisted"><span class="badge bg-danger"><i class="bi bi-slash-circle me-1"></i>Blacklisted</span></div>
                                        <div x-show="customerDetails.internal_notes">
                                            <span class="text-body-secondary me-1 fw-bold" style="font-size: 11px; text-transform: uppercase;">Notes:</span>
                                            <span class="fw-medium text-danger" x-text="customerDetails.internal_notes"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- Addresses Section --}}
                    {{-- Addresses Section --}}
                    <div id="addresses-section" x-show="partyId" x-cloak class="mt-4 pt-4 border-top transition-all">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h6 class="fw-bold mb-0 text-body fs-5"><i class="bi bi-geo-alt-fill me-2 text-primary"></i>Shipping Addresses</h6>
                            @can('customeraddress-create')
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-4 shadow-sm hover-shadow transition-all" @click="$dispatch('open-address-modal', {customerId: partyId})">
                                <i class="bi bi-plus-lg me-2"></i>Add Address
                            </button>
                            @endcan
                        </div>
                        
                        <div class="row g-4">
                            <template x-for="addr in addresses" :key="addr.id">
                                    <div class="col-md-6 col-lg-4">
                                        <div class="w-100 h-100 cursor-pointer" style="display:block;" @click="shippingAddressId = addr.id">
                                            <div class="card h-100 border shadow-sm transition-all" :class="shippingAddressId == addr.id ? 'border-primary bg-primary bg-opacity-10' : 'bg-body'">
                                                <div class="card-body p-3 position-relative">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <div>
                                                            <span class="badge bg-secondary me-1" x-text="addr.label || 'Address'"></span>
                                                            <span x-show="addr.is_default" class="badge bg-success"><i class="bi bi-star-fill me-1"></i>Default</span>
                                                        </div>
                                                        @can('customeraddress-edit')
                                                        <button type="button" class="btn btn-sm btn-outline-secondary border rounded-circle shadow-sm position-absolute d-flex align-items-center justify-content-center" style="top: 12px; right: 12px; width: 28px; height: 28px; z-index: 20;" @click.stop.prevent="$dispatch('open-address-modal', {customerId: partyId, address: addr})">
                                                            <i class="bi bi-pencil text-primary" style="font-size: 12px;"></i>
                                                        </button>
                                                        @endcan
                                                    </div>
                                                    <p class="mb-1 small fw-bold" x-text="addr.address_line_1"></p>
                                                    <p class="mb-1 small text-body-secondary" x-show="addr.address_line_2" x-text="addr.address_line_2"></p>
                                                    <p class="mb-1 small text-body-secondary" x-show="addr.village" x-text="[addr.village?.village_name ? 'Vill: '+addr.village?.village_name : null, addr.village?.post_so_name ? 'PO: '+addr.village?.post_so_name : null, addr.village?.taluka_name ? 'Ta: '+addr.village?.taluka_name : null, addr.village?.district_name ? 'Dist: '+addr.village?.district_name : null].filter(Boolean).join(', ')"></p>
                                                    <p class="mb-0 small text-body-secondary fw-medium">
                                                        <span x-show="addr.city" x-text="addr.city + ', '"></span>
                                                        <span x-show="addr.state" x-text="addr.state"></span>
                                                        <span x-show="addr.pincode" x-text="'- ' + addr.pincode"></span>
                                                    </p>
                                                    <div class="mt-3 pt-2 border-top">
                                                        <div class="small text-body-secondary fw-semibold text-uppercase mb-1" style="font-size: 10px; letter-spacing: .5px;">Available services</div>
                                                        <div class="d-flex flex-wrap gap-1" x-show="availableServices(addr).length">
                                                            <template x-for="(service, index) in availableServices(addr)" :key="service.id">
                                                                <span class="badge text-bg-success" x-text="`${Number(service.pivot?.priority) > 0 ? Number(service.pivot.priority) : index + 1}. ${service.name}`"></span>
                                                            </template>
                                                        </div>
                                                        <span class="small text-body-secondary" x-show="!availableServices(addr).length">No service available for this address</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                            </template>
                            <template x-if="addresses.length === 0">
                                <div class="col-12">
                                    <div class="alert alert-light border border-secondary border-opacity-25 rounded-4 d-flex align-items-center mb-0 p-4 shadow-sm bg-body-tertiary">
                                        <div class="bg-secondary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-4" style="width: 50px; height: 50px;">
                                            <i class="bi bi-info-circle text-body-secondary fs-4"></i>
                                        </div>
                                        <div>
                                            <p class="mb-1 fw-bold fs-6 text-body">No addresses found.</p>
                                            <p class="mb-0 small text-body-secondary">Please add a shipping address to continue with your order.</p>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Same as Shipping Toggle --}}
                        <div class="mt-4 form-check cursor-pointer d-flex align-items-center gap-2">
                            <input class="form-check-input mt-0" type="checkbox" id="sameAsShippingToggle" x-model="sameAsShipping" style="cursor: pointer;">
                            <label class="form-check-label small fw-bold text-body-secondary text-uppercase mt-1" for="sameAsShippingToggle" style="cursor: pointer; font-size: 11px; letter-spacing: 1px;">Billing address same as Shipping address</label>
                        </div>

                        {{-- Billing Address Section --}}
                        <div x-show="!sameAsShipping" x-cloak class="mt-4 pt-4 border-top transition-all">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h6 class="fw-bold mb-0 text-body fs-5"><i class="bi bi-receipt me-2 text-primary"></i>Billing Addresses</h6>
                            </div>
                            
                            <div class="row g-4">
                                <template x-for="addr in addresses" :key="addr.id">
                                    <div class="col-md-6 col-lg-4">
                                        <div class="w-100 h-100 cursor-pointer" style="display:block;" @click="billingAddressId = addr.id">
                                            <div class="card h-100 border shadow-sm transition-all" :class="billingAddressId == addr.id ? 'border-primary bg-primary bg-opacity-10' : 'bg-body'">
                                                <div class="card-body p-3 position-relative">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <div>
                                                            <span class="badge bg-secondary me-1" x-text="addr.label || 'Address'"></span>
                                                            <span x-show="addr.is_default" class="badge bg-success"><i class="bi bi-star-fill me-1"></i>Default</span>
                                                        </div>
                                                        @can('customeraddress-edit')
                                                        <button type="button" class="btn btn-sm btn-outline-secondary border rounded-circle shadow-sm position-absolute d-flex align-items-center justify-content-center" style="top: 12px; right: 12px; width: 28px; height: 28px; z-index: 20;" @click.stop.prevent="$dispatch('open-address-modal', {customerId: partyId, address: addr})">
                                                            <i class="bi bi-pencil text-primary" style="font-size: 12px;"></i>
                                                        </button>
                                                        @endcan
                                                    </div>
                                                    <p class="mb-1 small fw-bold" x-text="addr.address_line_1"></p>
                                                    <p class="mb-1 small text-body-secondary" x-show="addr.address_line_2" x-text="addr.address_line_2"></p>
                                                    <p class="mb-1 small text-body-secondary" x-show="addr.village" x-text="[addr.village?.village_name ? 'Vill: '+addr.village?.village_name : null, addr.village?.post_so_name ? 'PO: '+addr.village?.post_so_name : null, addr.village?.taluka_name ? 'Ta: '+addr.village?.taluka_name : null, addr.village?.district_name ? 'Dist: '+addr.village?.district_name : null].filter(Boolean).join(', ')"></p>
                                                    <p class="mb-0 small text-body-secondary fw-medium">
                                                        <span x-show="addr.city" x-text="addr.city + ', '"></span>
                                                        <span x-show="addr.state" x-text="addr.state"></span>
                                                        <span x-show="addr.pincode" x-text="'- ' + addr.pincode"></span>
                                                    </p>
                                                    <div class="mt-3 pt-2 border-top">
                                                        <div class="small text-body-secondary fw-semibold text-uppercase mb-1" style="font-size: 10px; letter-spacing: .5px;">Available services</div>
                                                        <div class="d-flex flex-wrap gap-1" x-show="availableServices(addr).length">
                                                            <template x-for="service in availableServices(addr)" :key="service.id">
                                                                <span class="badge text-bg-success" x-text="service.name"></span>
                                                            </template>
                                                        </div>
                                                        <span class="small text-body-secondary" x-show="!availableServices(addr).length">No service available for this address</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="addresses.length === 0">
                                    <div class="col-12">
                                        <div class="alert alert-light border border-secondary border-opacity-25 rounded-4 d-flex align-items-center mb-0 p-4 shadow-sm bg-body-tertiary">
                                            <div class="bg-secondary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-4" style="width: 50px; height: 50px;">
                                                <i class="bi bi-info-circle text-body-secondary fs-4"></i>
                                            </div>
                                            <div>
                                                <p class="mb-1 fw-bold fs-6 text-body">No addresses found.</p>
                                                <p class="mb-0 small text-body-secondary">Please add a billing address to continue with your order.</p>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm border-0 mt-4">
                        <div class="card-body p-3 p-lg-4">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                                <div>
                                    <div class="small fw-bold text-body-secondary text-uppercase mb-1" style="font-size: 11px; letter-spacing: 1px;">Warehouse</div>
                                    <h6 class="mb-0 fw-bold">Select fulfillment warehouse</h6>
                                </div>
                                <select x-select class="form-select fw-bold" style="max-width: 260px;" x-model="warehouseId" @change="handleWarehouseChange($event)">
                                    <option value="">Select Warehouse</option>
                                    @foreach($warehouses as $w)
                                    <option value="{{ $w->id }}" data-state="{{ $w->state }}">{{ $w->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- Product Search Card --}}
            <div id="catalog-section" class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-transparent border-bottom py-3">
                    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
                        <span class="fw-bold fs-5"><i class="bi bi-search me-2 text-info"></i>Product Catalog</span>
                        <div class="d-flex flex-wrap gap-2 flex-grow-1 justify-content-md-end align-items-center">

                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm" :class="viewMode === 'grid' ? 'btn-primary' : 'btn-outline-primary'" @click="viewMode = 'grid'" title="Grid View"><i class="bi bi-grid"></i></button>
                                <button type="button" class="btn btn-sm" :class="viewMode === 'table' ? 'btn-primary' : 'btn-outline-primary'" @click="viewMode = 'table'" title="Table View"><i class="bi bi-list-ul"></i></button>
                            </div>
                            <div class="position-relative" style="max-width:240px; width:100%;">
                                <input type="search" class="form-control pe-5" placeholder="Search SKU, name..." x-model="productQuery" @input.debounce.350ms="searchProducts(true)">
                                <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-3 text-body-secondary"></i>
                            </div>
                            <select x-select class="form-select" style="max-width:180px" x-model="warehouseId" @change="handleWarehouseChange($event)">
                                <option value="">Select Warehouse</option>
                                @foreach($warehouses as $w)
                                <option value="{{ $w->id }}">{{ $w->name }}</option>
                                @endforeach
                            </select>
                            <select x-select class="form-select" style="max-width:140px" x-model="stockFilter" @change="searchProducts(true)">
                                <option value="available">In Stock</option>
                                <option value="">All Stock</option>
                                <option value="out_of_stock">Out of Stock</option>
                            </select>
                            <select x-select class="form-select" style="max-width:160px" x-model="categoryFilter" @change="searchProducts(true)">
                                <option value="">All Categories</option>
                                @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @foreach($cat->children as $child)
                                <option value="{{ $child->id }}">— {{ $child->name }}</option>
                                @endforeach
                                @endforeach
                            </select>
                            <select class="form-select" style="max-width:110px" x-model="perPage" @change="searchProducts(true)" title="Items per page">
                                <option value="5">5 / page</option>
                                <option value="10">10 / page</option>
                                <option value="15">15 / page</option>
                                <option value="20">20 / page</option>
                                <option value="25">25 / page</option>
                                <option value="30">30 / page</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    {{-- Loading --}}
                    <template x-if="searching">
                        <div class="text-center py-5"><div class="spinner-border text-primary"></div></div>
                    </template>
                    {{-- Empty --}}
                    <template x-if="!searching && filteredProducts.length === 0">
                        <div class="text-center py-5 text-body-secondary"><i class="bi bi-box-seam fs-1 d-block mb-2"></i>No products found</div>
                    </template>
                    {{-- Product Grid / Table --}}
                    <div class="p-3" x-show="!searching && filteredProducts.length > 0">
                        {{-- Grid View --}}
                        <div class="row g-3" x-show="viewMode === 'grid'">
                            <template x-for="p in filteredProducts" :key="p.id">
                                <div class="col-sm-6 col-md-4">
                                    <div class="card h-100 border shadow-sm transition-all" x-data="{ isHovered: false }" @mouseenter="isHovered = true" @mouseleave="isHovered = false" :style="isHovered ? 'position: relative; z-index: 1050;' : ''" :class="{'border-primary bg-primary bg-opacity-10': isInCart(p.id), 'bg-body': !isInCart(p.id), 'opacity-50': !isSkuEnabled(p) || getMaxAllowedStock(p) <= 0}">
                                        <div class="card-body p-3">
                                            <div class="d-flex gap-2 mb-3">
                                                <div x-show="p.grade" 
                                                     class="badge border shadow-sm rounded-2 d-flex flex-column align-items-center justify-content-center flex-shrink-0" 
                                                     style="width: 28px; height: 34px; font-size: 11px; padding: 2px;"
                                                     :class="{'bg-success-subtle text-success-emphasis border-success': p.grade === 'A', 'bg-warning-subtle text-warning-emphasis border-warning': p.grade === 'B', 'bg-danger-subtle text-danger-emphasis border-danger': p.grade === 'C', 'bg-dark-subtle text-body-emphasis-emphasis border-dark': !['A','B','C'].includes(p.grade)}"
                                                     :title="'Grade ' + p.grade"
                                                     x-cloak>
                                                    <i class="bi bi-star-fill text-warning" style="font-size: 10px; line-height: 1; margin-bottom: 2px;"></i>
                                                    <span x-text="p.grade" style="line-height: 1; font-weight: 800;"></span>
                                                </div>
                                                <div class="position-relative cursor-pointer" @click="openProductModal(p)">
                                                    <img :src="p.image_url || '/assets/images/product-placeholder.svg'" class="rounded border bg-body" style="width:60px;height:60px;object-fit:cover;flex-shrink:0" x-on:error="$el.src='/assets/images/product-placeholder.svg'">
                                                    <div x-show="isInCart(p.id)" class="position-absolute top-0 start-100 translate-middle p-1 bg-success border border-light rounded-circle text-white d-flex align-items-center justify-content-center" style="width: 20px; height: 20px; font-size: 10px;">
                                                        <i class="bi bi-check"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1" style="min-width: 0;">
                                                    <div class="fw-bold text-truncate text-body cursor-pointer text-primary-hover mb-1" :title="p.name" x-text="p.name" @click="openProductModal(p)"></div>
                                                    <div class="small text-body-secondary text-truncate mb-1" style="font-size: 10px;">
                                                        <span x-show="p.category && p.category.name" class="me-2"><i class="bi bi-tag-fill me-1 text-primary opacity-50"></i><span x-text="p.category.name"></span></span>
                                                        <span x-show="p.brand && p.brand.name"><i class="bi bi-award-fill me-1 text-warning opacity-75"></i><span x-text="p.brand.name"></span></span>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-1 mt-1">
                                                        <span class="badge text-bg-secondary-subtle text-secondary-emphasis" style="font-size: 9px;" x-text="p.sku"></span>
                                                        <span x-show="p.weight" class="badge bg-body-secondary border text-body-secondary" style="font-size: 9px;" x-text="p.weight"></span>
                                                    </div>
                                                    <div class="text-body-tertiary text-truncate mt-1" style="font-size: 10px;" x-show="p.description" :title="p.description" x-text="p.description"></div>
                                                </div>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mb-2 px-2 py-1 bg-body-tertiary rounded">
                                                <span class="fw-bold text-primary fs-5" x-text="'₹ ' + (parseFloat(p.selling_price) * (1 + (parseFloat(p.tax_rate)||0)/100)).toFixed(2)"></span>
                                                <div>
                                                    <span class="badge" :class="getWarehouseStock(p) > 10 ? 'bg-success' : (getWarehouseStock(p) > 0 ? 'bg-warning text-body' : 'bg-danger')" x-text="'Stock: ' + parseFloat(getWarehouseStock(p))"></span>
                                                    <span x-show="p.allow_overselling" class="badge text-bg-warning-subtle text-warning-emphasis ms-1"><i class="bi bi-infinity"></i> <span x-text="getOversellStock(p)"></span></span>
                                                </div>
                                            </div>
                                            <div class="d-flex flex-wrap gap-1 mb-3" x-show="getProductPromotions(p).length > 0">
                                                <div class="position-relative" x-data="{ showTooltip: false }" @mouseenter="showTooltip = true" @mouseleave="showTooltip = false">
                                                    <span class="badge border text-bg-primary-subtle text-primary-emphasis border-primary" style="font-size: 10px; cursor: pointer;">
                                                        <i class="bi bi-tags me-1"></i> View Offers (<span x-text="getProductPromotions(p).length"></span>)
                                                    </span>
                                                    <div x-show="showTooltip" x-transition.opacity class="position-absolute" style="bottom: 100%; z-index: 9999; left: 0; margin-bottom: 8px; width: 280px; cursor: default;" x-cloak>
                                                        <div class="card border border-secondary-subtle shadow-lg rounded-3 overflow-hidden">
                                                            <div class="card-header bg-body-tertiary border-bottom border-secondary-subtle py-2 px-3 d-flex align-items-center justify-content-between">
                                                                <span class="fw-bold text-body" style="font-size: 12px;"><i class="bi bi-tags-fill me-1 text-primary"></i> Applicable Offers</span>
                                                                <span class="badge bg-primary rounded-pill" x-text="getProductPromotions(p).length"></span>
                                                            </div>
                                                            <div class="card-body p-0" style="max-height: 220px; overflow-y: auto;">
                                                                <template x-for="(promo, index) in getProductPromotions(p)">
                                                                    <div class="p-2 px-3 border-bottom border-secondary-subtle transition-all">
                                                                        <div class="d-flex align-items-start gap-2">
                                                                            <div class="rounded d-flex align-items-center justify-content-center flex-shrink-0 mt-1" :class="'bg-' + promo.color + ' bg-opacity-10 text-' + promo.color" style="width: 24px; height: 24px;">
                                                                                <i class="bi" :class="promo.icon"></i>
                                                                            </div>
                                                                            <div>
                                                                                <div class="fw-bold text-body mb-1" style="font-size: 12px; line-height: 1.3;" x-text="promo.title"></div>
                                                                                <div class="text-body-secondary" style="font-size: 11px; line-height: 1.4;" x-html="promo.tooltip"></div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </template>
                                                            </div>
                                                        </div>
                                                        <!-- Tooltip Arrow -->
                                                        <div class="position-absolute bg-body border-bottom border-end border-secondary-subtle" style="width: 12px; height: 12px; transform: rotate(45deg); bottom: -6px; left: 20px; z-index: -1;"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="d-flex flex-column gap-2 mt-auto">
                                                <div class="input-group shadow-sm flex-nowrap" style="min-height: 38px;">
                                                    <button class="btn btn-outline-secondary px-2 flex-shrink-0" type="button" @click="if(p._qty > 1) p._qty--" :disabled="!canAddToCart(p)"><i class="bi bi-dash"></i></button>
                                                    <input type="number" class="form-control text-center fw-bold px-1 no-spinners flex-grow-1" x-model.number="p._qty" min="1" :max="getMaxAllowedStock(p) || 9999" placeholder="Qty" :disabled="!canAddToCart(p)" style="min-width: 0;">
                                                    <button class="btn btn-outline-secondary px-2 flex-shrink-0" type="button" @click="if(p._qty < (getMaxAllowedStock(p) || 9999)) p._qty++" :disabled="!canAddToCart(p)"><i class="bi bi-plus"></i></button>
                                                </div>
                                                <button class="btn btn-sm w-100 shadow-sm d-flex align-items-center justify-content-center gap-2 transition-all fw-bold text-nowrap" style="min-height: 38px;" :class="isInCart(p.id) ? 'btn-primary' : 'btn-outline-primary'" @click="addToCart(p)" :title="isInCart(p.id) ? 'Add more' : 'Add to cart'" :disabled="!canAddToCart(p)">
                                                    <i class="bi fs-5" :class="isInCart(p.id) ? 'bi-cart-plus-fill' : 'bi-cart-plus'"></i>
                                                    <span x-text="!isSkuEnabled(p) ? 'Disabled' : (!canAddToCart(p) ? 'Out of Stock' : (isInCart(p.id) ? 'Add More' : 'Add'))" style="font-size: 13px;"></span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                        
                        {{-- Table View --}}
                        <div class="table-responsive" x-show="viewMode === 'table'">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Product Details</th>
                                        <th style="min-width: 150px;">Pricing & Offers</th>
                                        <th style="min-width: 150px;">Inventory</th>
                                        <th style="width: 140px;" class="text-end pe-4">Order Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="p in filteredProducts" :key="'tbl-'+p.id">
                                        <tr :class="{'bg-primary bg-opacity-10': isInCart(p.id), 'opacity-50': !isSkuEnabled(p) || getMaxAllowedStock(p) <= 0}">
                                            <td class="align-middle">
                                                <div class="d-flex align-items-start gap-3">
                                                    <div class="position-relative flex-shrink-0 cursor-pointer" @click="openProductModal(p)">
                                                        <img :src="p.image_url || '/assets/images/product-placeholder.svg'" 
                                                             class="rounded border shadow-sm object-fit-cover bg-body" 
                                                             style="width: 48px; height: 48px;" 
                                                             :alt="p.name"
                                                             x-on:error="$el.src='/assets/images/product-placeholder.svg'">
                                                        <div x-show="p.grade" 
                                                             class="position-absolute top-100 start-50 translate-middle badge border shadow-sm rounded-pill px-2 d-flex align-items-center" 
                                                             style="font-size: 9px; padding-top: 2px; padding-bottom: 2px;"
                                                             :class="{'bg-success-subtle text-success-emphasis border-success': p.grade === 'A', 'bg-warning-subtle text-warning-emphasis border-warning': p.grade === 'B', 'bg-danger-subtle text-danger-emphasis border-danger': p.grade === 'C', 'bg-dark-subtle text-body-emphasis-emphasis border-dark': !['A','B','C'].includes(p.grade)}"
                                                             :title="'Grade ' + p.grade"
                                                             x-cloak>
                                                            <i class="bi bi-star-fill text-warning me-1" style="font-size: 8px;"></i><span x-text="p.grade" style="font-weight: 800;"></span>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex flex-column min-w-0 pt-1">
                                                        <a href="#" class="fw-bold text-decoration-none text-body-emphasis text-truncate mb-1 cursor-pointer" style="max-width: 220px;" @click.prevent="openProductModal(p)" x-text="p.name"></a>
                                                        <div class="small text-body-secondary text-truncate mb-1" style="font-size: 10px;">
                                                            <span x-show="p.category && p.category.name" class="me-2"><i class="bi bi-tag-fill me-1 text-primary opacity-50"></i><span x-text="p.category.name"></span></span>
                                                            <span x-show="p.brand && p.brand.name"><i class="bi bi-award-fill me-1 text-warning opacity-75"></i><span x-text="p.brand.name"></span></span>
                                                        </div>
                                                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                                            <span class="badge bg-secondary bg-opacity-10 text-secondary-emphasis border border-secondary border-opacity-25" style="font-size: 10px; padding: 0.25em 0.5em;" x-text="'SKU: ' + p.sku"></span>
                                                            <span x-show="p.weight" class="badge bg-body-secondary border text-body-secondary" style="font-size: 9px;" x-text="p.weight"></span>
                                                            <span class="badge" 
                                                                  :class="{
                                                                      'bg-success': ['published', 'active'].includes(p.status),
                                                                      'bg-secondary': p.status === 'draft',
                                                                      'bg-warning': ['pending', 'out_of_stock'].includes(p.status)
                                                                  }"
                                                                  x-text="p.status"></span>
                                                        </div>
                                                        <div x-show="getProductPromotions(p).length > 0">
                                                            <div class="position-relative d-inline-block" x-data="{ showTooltip: false }" @mouseenter="showTooltip = true" @mouseleave="showTooltip = false">
                                                                <button type="button" class="btn btn-sm btn-outline-success rounded-pill py-0 px-2 d-inline-flex align-items-center gap-1 bg-body" style="font-size: 10px;">
                                                                    <i class="bi bi-gift-fill"></i> <span x-text="getProductPromotions(p).length + ' Offers'"></span>
                                                                </button>
                                                                <div x-show="showTooltip" x-transition.opacity class="position-absolute" style="top: 100%; z-index: 9999; left: 0; margin-top: 8px; width: 280px; cursor: default;" x-cloak>
                                                                    <div class="card border border-secondary-subtle shadow-lg rounded-3 overflow-hidden">
                                                                        <div class="card-header bg-body-tertiary border-bottom border-secondary-subtle py-2 px-3 d-flex align-items-center justify-content-between">
                                                                            <span class="fw-bold text-body" style="font-size: 12px;"><i class="bi bi-tags-fill me-1 text-primary"></i> Applicable Offers</span>
                                                                            <span class="badge bg-primary rounded-pill" x-text="getProductPromotions(p).length"></span>
                                                                        </div>
                                                                        <div class="card-body p-0" style="max-height: 220px; overflow-y: auto;">
                                                                            <template x-for="(promo, index) in getProductPromotions(p)">
                                                                                <div class="p-2 px-3 border-bottom border-secondary-subtle transition-all">
                                                                                    <div class="d-flex align-items-start gap-2">
                                                                                        <div class="rounded d-flex align-items-center justify-content-center flex-shrink-0 mt-1" :class="'bg-' + promo.color + ' bg-opacity-10 text-' + promo.color" style="width: 24px; height: 24px;">
                                                                                            <i class="bi" :class="promo.icon"></i>
                                                                                        </div>
                                                                                        <div>
                                                                                            <div class="fw-bold text-body mb-1" style="font-size: 12px; line-height: 1.3;" x-text="promo.title"></div>
                                                                                            <div class="text-body-secondary" style="font-size: 11px; line-height: 1.4;" x-html="promo.tooltip"></div>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </template>
                                                                        </div>
                                                                    </div>
                                                                    <!-- Tooltip Arrow -->
                                                                    <div class="position-absolute bg-body border-top border-start border-secondary-subtle" style="width: 12px; height: 12px; transform: rotate(45deg); top: -6px; left: 20px; z-index: -1;"></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="align-middle">
                                                <div class="d-flex flex-column gap-2">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 text-decoration-line-through" x-show="p.mrp > (parseFloat(p.selling_price) * (1 + (parseFloat(p.tax_rate)||0)/100))" x-text="'₹' + parseFloat(p.mrp).toFixed(2)"></span>
                                                        <span class="badge bg-success text-white fw-bold shadow-sm" style="font-size: 13px;" x-text="'₹' + (parseFloat(p.selling_price) * (1 + (parseFloat(p.tax_rate)||0)/100)).toFixed(2)"></span>
                                                        <div class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 ms-1" x-show="p.default_discount > 0" style="font-size: 10px;"><span x-text="p.default_discount"></span><span x-text="p.default_discount_type === 'percent' ? '%' : ' Rs'"></span> OFF</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="align-middle">
                                                <div class="d-flex flex-column gap-2">
                                                    <div class="p-2 bg-body-tertiary rounded border shadow-sm w-100" style="min-width: 160px; max-width: 200px;">
                                                        <div class="d-flex justify-content-between align-items-center mb-1 border-bottom border-secondary border-opacity-10 pb-1">
                                                            <span class="text-muted fw-medium" style="font-size: 9px; letter-spacing: 0.5px;">AVAILABLE FOR SELL</span>
                                                            <span class="badge" 
                                                                  :class="{
                                                                      'bg-success bg-opacity-10 text-success border border-success border-opacity-25': (getWarehouseStock(p) + getOversellStock(p)) > (p.min_stock_level || 10),
                                                                      'bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25': (getWarehouseStock(p) + getOversellStock(p)) > 0 && (getWarehouseStock(p) + getOversellStock(p)) <= (p.min_stock_level || 10),
                                                                      'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25': (getWarehouseStock(p) + getOversellStock(p)) <= 0
                                                                  }"
                                                                  x-text="getWarehouseStock(p) + getOversellStock(p)"></span>
                                                        </div>
                                                        <div class="d-flex justify-content-between align-items-center" style="font-size: 10px;">
                                                            <template x-if="p.warehouse_stocks && p.warehouse_stocks.length > 0 && !p.warehouse_stocks.some(w => String(w.warehouse_id) === String(warehouseId))">
                                                                <span class="text-danger"><i class="bi bi-x-circle me-1"></i>Not in warehouse</span>
                                                            </template>
                                                            <template x-if="!(p.warehouse_stocks && p.warehouse_stocks.length > 0 && !p.warehouse_stocks.some(w => String(w.warehouse_id) === String(warehouseId)))">
                                                                <span class="text-muted">Physical: <span class="fw-bold text-body-emphasis" x-text="parseFloat(getWarehouseStock(p))"></span></span>
                                                            </template>
                                                            <span x-show="p.allow_overselling" class="text-warning fw-bold" title="Overselling Allowed" x-text="'+' + getOversellStock(p) + ' (OS)'"></span>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex flex-wrap gap-1" style="max-width: 180px;">
                                                        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25" style="font-size:9px;" x-show="isSkuEnabled(p)" title="SKU Enabled"><i class="bi bi-upc-scan me-1"></i>SKU On</span>
                                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25" style="font-size:9px;" x-show="!isSkuEnabled(p)" title="SKU Disabled"><i class="bi bi-upc-scan me-1"></i>SKU Off</span>
                                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25" style="font-size:9px;" x-show="p.batch_tracking" title="Batch Tracking"><i class="bi bi-layers me-1"></i>Batch</span>
                                                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25" style="font-size:9px;" x-show="p.expiry_tracking" title="Expiry Tracking"><i class="bi bi-calendar-x me-1"></i>Expiry</span>
                                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25" style="font-size:9px;" x-show="p.allow_overselling" title="Allow Overselling"><i class="bi bi-arrow-down-up me-1"></i>Oversell</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-end pe-4 align-middle">
                                                <div class="d-flex flex-column gap-2 ms-auto" style="max-width: 130px;">
                                                    <div class="input-group input-group-sm shadow-sm flex-nowrap" style="min-height: 32px;">
                                                        <button class="btn btn-outline-secondary px-2 flex-shrink-0" type="button" @click="if(p._qty > 1) p._qty--" :disabled="!canAddToCart(p)"><i class="bi bi-dash"></i></button>
                                                        <input type="number" class="form-control text-center fw-bold px-1 no-spinners flex-grow-1" x-model.number="p._qty" min="1" :max="getMaxAllowedStock(p) || 9999" placeholder="Qty" :disabled="!canAddToCart(p)" style="min-width: 0;">
                                                        <button class="btn btn-outline-secondary px-2 flex-shrink-0" type="button" @click="if(p._qty < (getMaxAllowedStock(p) || 9999)) p._qty++" :disabled="!canAddToCart(p)"><i class="bi bi-plus"></i></button>
                                                    </div>
                                                    <button class="btn btn-sm w-100 shadow-sm d-flex align-items-center justify-content-center gap-2 transition-all fw-bold text-nowrap" style="min-height: 32px;" :class="isInCart(p.id) ? 'btn-primary' : 'btn-outline-primary'" @click="addToCart(p)" :title="isInCart(p.id) ? 'Add more' : 'Add to cart'" :disabled="!canAddToCart(p)">
                                                        <i class="bi" :class="isInCart(p.id) ? 'bi-cart-plus-fill' : 'bi-cart-plus'"></i>
                                                        <span x-text="!isSkuEnabled(p) ? 'Disabled' : (!canAddToCart(p) ? 'Out of Stock' : (isInCart(p.id) ? 'Add More' : 'Add'))" style="font-size: 11px;"></span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    {{-- Pagination --}}
                    <div class="d-flex justify-content-between align-items-center px-3 pb-3 border-top pt-3" x-show="productTotal > 0">
                        <small class="text-body-secondary"><span x-text="productFrom"></span>–<span x-text="productTo"></span> of <span x-text="productTotal"></span></small>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-primary btn-sm" x-show="cart.length > 0 && !isCartSidebarOpen" @click="isCartSidebarOpen = true" x-cloak>
                                <i class="bi bi-cart-check"></i> View Cart & Checkout (<span x-text="cart.length"></span>)
                            </button>
                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-sm btn-outline-secondary" @click="productPage--; searchProducts()" :disabled="productPage <= 1"><i class="bi bi-chevron-left"></i></button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" @click="productPage++; searchProducts()" :disabled="productPage >= productLastPage"><i class="bi bi-chevron-right"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Shopping Cart header --}}
            <div class="row align-items-center gy-3 mb-4 mt-2" x-show="false">
                <div class="col-sm">
                    <h4 class="mb-0 fw-black text-body d-flex align-items-center gap-2"><div class="text-bg-primary-subtle text-primary-emphasis rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;"><i class="bi bi-cart3"></i></div> Shopping Cart (<span x-text="cart.length" class="text-primary"></span>)</h4>
                </div>
                <div class="col-sm-auto" x-show="cart.length > 0">
                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 shadow-sm hover-shadow" @click="cart = []">
                        <i class="bi bi-trash3 me-1"></i> Clear Cart
                    </button>
                </div>
            </div>

            {{-- Cart Items List (Glossy Style) --}}
            <div class="mb-4 space-y-3" x-show="false">
                {{-- Empty Cart --}}
                <template x-if="cart.length === 0">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body d-flex flex-column align-items-center justify-content-center text-center p-5 opacity-50">
                            <div class="bg-body-secondary rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                <i class="bi bi-bag fs-1 text-body-secondary"></i>
                            </div>
                            <h5 class="fw-bold text-uppercase tracking-widest mb-2">Cart is empty</h5>
                            <p class="small text-body-secondary mb-3">Browse products and click <strong>Add</strong> to begin.</p>
                        </div>
                    </div>
                </template>

                {{-- Cart Item Cards --}}
                <template x-for="(item, idx) in cart" :key="item.id + '_' + (item.is_gift ? item.gift_source : 'paid')">
                    <div class="card border shadow-sm mb-3">
                        <div class="d-flex align-items-start gap-3 p-3">
                            <div class="rounded-3 bg-body-tertiary border flex-shrink-0 d-flex align-items-center justify-content-center overflow-hidden" style="width: 70px; height: 70px;">
                                <img :src="item.image_url || '/assets/images/product-placeholder.svg'" class="w-100 h-100 object-fit-cover" x-on:error="$el.src='/assets/images/product-placeholder.svg'">
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex align-items-start justify-content-between gap-2">
                                    <div class="min-w-0">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <h6 class="fw-bold text-truncate mb-0" x-text="item.name"></h6>
                                            <template x-if="item.is_gift">
                                                <span class="badge text-bg-success-subtle text-success-emphasis border-opacity-25 px-2 py-1" style="font-size: 0.6rem; line-height: 1;"><i class="bi bi-gift-fill me-1"></i>Free</span>
                                            </template>
                                        </div>
                                        <div class="font-monospace text-body-secondary" style="font-size: 11px;" x-text="item.sku"></div>
                                    </div>
                                    <template x-if="!item.is_gift">
                                        <button type="button" @click.prevent="cart.splice(idx,1)" class="btn btn-sm btn-outline-secondary text-body-secondary hover-danger rounded-3 p-1 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 28px; height: 28px;" title="Remove">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </template>
                                </div>
                                <div class="d-flex align-items-center justify-content-between mt-2">
                                    <span class="text-body-secondary fw-medium" style="font-size: 12px;" x-text="'₹ ' + Number(item.price).toFixed(2) + ' × ' + item.quantity"></span>
                                    <span class="fw-bold text-success fs-6" x-text="'₹ ' + Number(lineTotal(item)).toFixed(2)"></span>
                                </div>
                                <div class="d-flex align-items-center justify-content-between mt-1" x-show="item.taxRate > 0">
                                    <span class="text-body-secondary" style="font-size: 11px;" x-text="'+ GST ' + item.taxRate + '%'"></span>
                                    <span class="text-body-secondary" style="font-size: 11px;" x-text="'₹ ' + Number(lineTotal(item) * (item.taxRate / 100)).toFixed(2)"></span>
                                </div>
                            </div>
                        </div>
                        <div class="px-3 pb-3 d-flex flex-wrap align-items-center gap-3">
                            <div class="d-flex align-items-center bg-body-secondary border rounded-3 p-1 flex-shrink-0">
                                <template x-if="!item.is_gift">
                                    <button type="button" @click.prevent="updateQty(idx,-1)" class="btn btn-sm btn-link text-body text-decoration-none fw-bold p-0 d-flex align-items-center justify-content-center hover-bg-body rounded" style="width: 28px; height: 28px;">
                                        <i class="bi bi-dash"></i>
                                    </button>
                                </template>
                                <span class="fw-bold text-center" style="width: 32px; font-size: 13px;" x-text="item.quantity"></span>
                                <template x-if="!item.is_gift">
                                    <button type="button" @click.prevent="updateQty(idx,1)" class="btn btn-sm btn-link text-body text-decoration-none fw-bold p-0 d-flex align-items-center justify-content-center hover-bg-body rounded" style="width: 28px; height: 28px;">
                                        <i class="bi bi-plus"></i>
                                    </button>
                                </template>
                            </div>
                            <div class="flex-grow-1 min-w-0 d-flex justify-content-end align-items-center gap-2">
                                <template x-if="item.discountValue > 0">
                                        <div class="badge bg-success bg-opacity-10 border border-success border-opacity-25 text-success d-flex align-items-center gap-1 px-2 py-1 rounded-3">
                                            <i class="bi bi-tag-fill"></i>
                                            <span class="fw-bold" style="font-size: 11px;" x-text="(['flat', 'amount', 'fixed'].includes((item.discountType || '').toLowerCase()) ? '₹ ' : '') + Number(item.discountValue).toFixed(item.discountValue % 1 === 0 ? 0 : 2) + (['flat', 'amount', 'fixed'].includes((item.discountType || '').toLowerCase()) ? ' off' : '% off')"></span>
                                        </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- RIGHT: Cart Summary + Calculations + Offers + Place Order (Glossy Style) --}}
        <div class="col-xl-4" x-show="isCartSidebarOpen" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-x-0" x-transition:leave-end="opacity-0 translate-x-4">
            <div class="sticky-side-div" style="position: sticky; top: 24px;">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-transparent border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0 fw-bold"><i class="bi bi-cart3 me-2 text-primary"></i>Shopping Cart (<span x-text="cart.length" class="text-primary"></span>)</h5>
                            <p class="mb-0 small text-body-secondary">Pinned summary for the order you’re building.</p>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger" x-show="cart.length > 0" @click="cart = []">
                            <i class="bi bi-trash3 me-1"></i> Clear
                        </button>
                    </div>
                    <div class="card-body p-3 p-lg-4">
                        <template x-if="cart.length === 0">
                            <div class="text-center py-4 text-body-secondary">
                                <i class="bi bi-bag fs-1 d-block mb-2"></i>
                                Cart is empty
                            </div>
                        </template>
                        <template x-for="(item, idx) in cart" :key="item.id + '_' + (item.is_gift ? item.gift_source : 'paid')">
                            <div class="card border shadow-sm mb-3">
                                <div class="d-flex align-items-start gap-3 p-3">
                                    <div class="rounded-3 bg-body-tertiary border flex-shrink-0 d-flex align-items-center justify-content-center overflow-hidden" style="width: 70px; height: 70px;">
                                        <img :src="item.image_url || '/assets/images/product-placeholder.svg'" class="w-100 h-100 object-fit-cover" x-on:error="$el.src='/assets/images/product-placeholder.svg'">
                                    </div>
                                    <div class="flex-grow-1" style="min-width: 0;">
                                        <div class="d-flex align-items-start justify-content-between gap-2">
                                            <div style="min-width: 0;">
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <h6 class="fw-bold text-truncate mb-0" x-text="item.name"></h6>
                                                    <template x-if="item.is_gift">
                                                        <span class="badge text-bg-success-subtle text-success-emphasis border-opacity-25 px-2 py-1" style="font-size: 0.6rem; line-height: 1;"><i class="bi bi-gift-fill me-1"></i>Free</span>
                                                    </template>
                                                </div>
                                                <div class="font-monospace text-body-secondary text-truncate" style="font-size: 11px;" x-text="item.sku"></div>
                                            </div>
                                            <template x-if="!item.is_gift">
                                                <button type="button" @click.prevent="cart.splice(idx,1)" class="btn btn-sm btn-outline-secondary text-body-secondary hover-danger rounded-3 p-1 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 28px; height: 28px;" title="Remove">
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            </template>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between mt-2">
                                            <span class="text-body-secondary fw-medium" style="font-size: 12px;" x-text="'₹ ' + Number(item.price).toFixed(2) + ' × ' + item.quantity"></span>
                                            <span class="fw-bold text-success fs-6" x-text="'₹ ' + Number(lineTotal(item)).toFixed(2)"></span>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between mt-1" x-show="item.taxRate > 0">
                                            <span class="text-body-secondary" style="font-size: 11px;" x-text="'+ GST ' + item.taxRate + '%'"></span>
                                            <span class="text-body-secondary" style="font-size: 11px;" x-text="'₹ ' + Number(itemTaxAmount(item)).toFixed(2)"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="px-3 pb-3 d-flex flex-wrap align-items-center gap-3">
                                    <div class="d-flex align-items-center bg-body-secondary border rounded-3 p-1 flex-shrink-0">
                                        <template x-if="!item.is_gift">
                                            <button type="button" @click.prevent="updateQty(idx,-1)" class="btn btn-sm btn-link text-body text-decoration-none fw-bold p-0 d-flex align-items-center justify-content-center hover-bg-body rounded" style="width: 28px; height: 28px;">
                                                <i class="bi bi-dash"></i>
                                            </button>
                                        </template>
                                        <span class="fw-bold text-center" style="width: 32px; font-size: 13px;" x-text="item.quantity"></span>
                                        <template x-if="!item.is_gift">
                                            <button type="button" @click.prevent="updateQty(idx,1)" class="btn btn-sm btn-link text-body text-decoration-none fw-bold p-0 d-flex align-items-center justify-content-center hover-bg-body rounded" style="width: 28px; height: 28px;">
                                                <i class="bi bi-plus"></i>
                                            </button>
                                        </template>
                                    </div>
                                    <div class="flex-grow-1 d-flex justify-content-end align-items-center gap-2" style="min-width: 0;">
                                        <template x-if="item.discountValue > 0">
                                            <div class="badge bg-success bg-opacity-10 border border-success border-opacity-25 text-success d-flex align-items-center gap-1 px-2 py-1 rounded-3">
                                                <i class="bi bi-tag-fill"></i>
                                                <span class="fw-bold" style="font-size: 11px;" x-text="(['flat', 'amount', 'fixed'].includes((item.discountType || '').toLowerCase()) ? '₹ ' : '') + Number(item.discountValue).toFixed(item.discountValue % 1 === 0 ? 0 : 2) + (['flat', 'amount', 'fixed'].includes((item.discountType || '').toLowerCase()) ? ' off' : '% off')"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-4 space-y-4">
                        
                        {{-- ── Promotions & Offers ── --}}
                        <div class="mb-4">
                            <button type="button" class="btn btn-outline-primary w-100 border p-3 d-flex align-items-center justify-content-between shadow-sm bg-body-tertiary" data-bs-toggle="modal" data-bs-target="#promotionsModal">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="text-bg-primary-subtle text-primary-emphasis rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 shadow-sm" style="width: 40px; height: 40px;">
                                        <i class="bi bi-tag-fill fs-5"></i>
                                    </div>
                                    <div class="text-start">
                                        <p class="mb-0 fw-bold fs-6">View Promos & Offers</p>
                                        <p class="mb-0 text-body-secondary small" x-text="(activeOffers.length + activeCoupons.length) + ' available'"></p>
                                    </div>
                                </div>
                                <i class="bi bi-chevron-right text-body-secondary"></i>
                            </button>
                        </div>

                        {{-- Applied Promotions Tags --}}
                        <div class="space-y-3 mb-4" x-show="bestOrderOffer || couponApplied || bogoDiscount > 0" x-cloak>
                            
                            {{-- Offer applied --}}
                            <template x-if="bestOrderOffer">
                                <div class="d-flex align-items-center justify-content-between gap-3 px-3 py-2 rounded-4 bg-success bg-opacity-10 border border-success border-opacity-25 shadow-sm transition-all hover-shadow">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="text-bg-success-subtle text-success-emphasis-emphasis rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;">
                                            <i class="bi bi-check-lg fs-5"></i>
                                        </div>
                                        <div>
                                            <p class="mb-0 fw-bold text-success-emphasis fs-6" x-text="bestOrderOffer.name"></p>
                                            <p class="mb-0 fw-semibold text-success opacity-75 small" x-text="'Saving ₹ ' + Number(orderOfferDiscountAmount).toFixed(2)"></p>
                                        </div>
                                    </div>
                                    <button type="button" @click.prevent="appliedOfferId = 'none'" class="btn btn-sm btn-outline-secondary text-body-secondary hover-danger rounded-circle p-0 d-flex align-items-center justify-content-center shadow-sm" style="width: 28px; height: 28px;">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                            </template>

                            {{-- Coupon applied --}}
                            <template x-if="couponApplied">
                                <div class="d-flex align-items-center justify-content-between gap-3 px-3 py-2 rounded-4 bg-success bg-opacity-10 border border-success border-opacity-25 shadow-sm transition-all hover-shadow">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="text-bg-success-subtle text-success-emphasis-emphasis rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;">
                                            <i class="bi bi-check-lg fs-5"></i>
                                        </div>
                                        <div>
                                            <p class="mb-0 fw-bold text-success-emphasis fs-6" x-text="'Coupon: ' + couponCode"></p>
                                            <p class="mb-0 fw-semibold text-success opacity-75 small">
                                                <template x-if="appliedCouponObj && appliedCouponObj.type === 'free_shipping'">
                                                    <span>Free Shipping Applied</span>
                                                </template>
                                                <template x-if="appliedCouponObj && appliedCouponObj.type === 'free_product'">
                                                    <span>Free Gift Applied</span>
                                                </template>
                                                <template x-if="appliedCouponObj && appliedCouponObj.type !== 'free_shipping' && appliedCouponObj.type !== 'free_product'">
                                                    <span x-text="'Saving ₹ ' + Number(couponDiscount).toFixed(2)"></span>
                                                </template>
                                            </p>
                                        </div>
                                    </div>
                                    <button type="button" @click.prevent="removeCoupon()" class="btn btn-sm btn-outline-secondary text-body-secondary hover-danger rounded-circle p-0 d-flex align-items-center justify-content-center shadow-sm" style="width: 28px; height: 28px;">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                            </template>

                            {{-- BOGO (auto-applied) --}}
                            <template x-if="bogoDiscount > 0">
                                <div class="d-flex align-items-center justify-content-between gap-3 px-3 py-2 rounded-4 bg-info bg-opacity-10 border border-info border-opacity-25 shadow-sm transition-all hover-shadow">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="text-bg-info-subtle text-info-emphasis-emphasis rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;">
                                            <i class="bi bi-lightning-charge-fill fs-5"></i>
                                        </div>
                                        <div>
                                            <p class="mb-0 fw-bold text-info-emphasis fs-6">BOGO Savings</p>
                                            <p class="mb-0 text-info opacity-75 small" x-text="appliedBogoOfferNames"></p>
                                        </div>
                                    </div>
                                    <span class="fw-bold text-info-emphasis fs-6" x-text="'- ₹ ' + Number(bogoDiscount).toFixed(2)"></span>
                                </div>
                            </template>

                        </div>

                        <hr class="border-secondary opacity-10">

                        {{-- Order Summary Calculations --}}
                        <div class="space-y-2 mb-4">
                            <div class="d-flex justify-content-between fw-medium text-body-secondary" style="font-size: 13px;">
                                <span>Subtotal</span>
                                <span class="text-body fw-bold" x-text="'₹ ' + Number(subtotal).toFixed(2)"></span>
                            </div>
                            
                            <div class="d-flex justify-content-between fw-medium" :class="bogoDiscount > 0 ? 'text-success' : 'text-body-secondary'" style="font-size: 13px;">
                                <div>
                                    <span>BOGO Savings</span>
                                    <span class="text-body-secondary d-block" style="font-size: 10px;" x-text="appliedBogoOfferNames"></span>
                                </div>
                                <span class="fw-bold align-top" x-text="'- ₹ ' + Number(bogoDiscount).toFixed(2)"></span>
                            </div>

                            <div class="d-flex justify-content-between fw-medium" :class="orderOfferDiscountAmount > 0 ? 'text-success' : 'text-body-secondary'" style="font-size: 13px;">
                                <div>
                                    <span>Order Discount</span>
                                    <span class="text-body-secondary d-block" style="font-size: 10px;" x-text="bestOrderOffer ? bestOrderOffer.name : 'No active offer'"></span>
                                </div>
                                <span class="fw-bold align-top" x-text="'- ₹ ' + Number(orderOfferDiscountAmount).toFixed(2)"></span>
                            </div>

                            <div class="d-flex justify-content-between fw-medium" :class="appliedCouponObj !== null ? 'text-success' : 'text-body-secondary'" style="font-size: 13px;">
                                <div>
                                    <span>Coupon Savings</span>
                                    <span class="text-body-secondary d-block" style="font-size: 10px;" x-text="appliedCouponObj ? '(Code: ' + couponCode + ')' : 'No coupon applied'"></span>
                                </div>
                                <span class="fw-bold align-top" x-show="appliedCouponObj && (appliedCouponObj.type === 'free_shipping' || appliedCouponObj.type === 'free_product')" x-text="appliedCouponObj.type === 'free_shipping' ? 'Free Shipping' : 'Free Gift'"></span>
                                <span class="fw-bold align-top" x-show="!appliedCouponObj || (appliedCouponObj.type !== 'free_shipping' && appliedCouponObj.type !== 'free_product')" x-text="'- ₹ ' + Number(couponDiscount).toFixed(2)"></span>
                            </div>

                            <div class="d-flex justify-content-between fw-medium text-body-secondary" style="font-size: 13px;">
                                <span>GST</span>
                                <span class="text-body" x-text="'₹ ' + Number(taxAmount).toFixed(2)"></span>
                            </div>

                            <hr class="border-secondary opacity-10 my-3">

                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-uppercase tracking-widest text-body" style="font-size: 14px;">Grand Total</span>
                                <span class="fw-black text-primary fs-3" x-text="'₹ ' + Number(grandTotal).toFixed(2)"></span>
                            </div>

                            <template x-if="customerDetails && Number(customerDetails.wallet_balance) > 0">
                                <div class="mt-4 p-3 bg-body-tertiary rounded-4 border shadow-sm transition-all" :class="useWalletBalance ? 'border-primary' : ''">
                                    <div class="form-check form-switch d-flex align-items-center justify-content-between gap-3 p-0 m-0 cursor-pointer" @click="useWalletBalance = !useWalletBalance">
                                        <div>
                                            <label class="form-check-label fw-bold mb-0 text-body-emphasis" style="cursor: pointer;">Redeem Cashback Wallet</label>
                                            <div class="small text-body-secondary mt-1" style="font-size: 11px;">
                                                Available Balance: <span class="text-success" x-text="'₹ ' + Number(customerDetails.wallet_balance).toFixed(2)"></span> 
                                            </div>
                                        </div>
                                        <input class="form-check-input fs-4 m-0" type="checkbox" role="switch" x-model="useWalletBalance" @click.stop>
                                    </div>
                                    <div x-show="useWalletBalance" x-cloak class="mt-3 pt-3 border-top">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="fw-bold text-uppercase tracking-widest text-primary" style="font-size: 14px;">Net Payable</span>
                                            <span class="fw-black text-primary fs-4" x-text="'₹ ' + Math.max(0, Number(grandTotal) - Number(customerDetails.wallet_balance)).toFixed(2)"></span>
                                        </div>
                                        <div class="small text-body-secondary mt-1" x-show="(Number(grandTotal) - Number(customerDetails.wallet_balance)) < 0">
                                            * Remaining wallet: ₹ <span x-text="Math.abs(Number(grandTotal) - Number(customerDetails.wallet_balance)).toFixed(2)"></span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Schedule Order Toggle (Merged) --}}
                        <div class="mb-4 bg-body-tertiary rounded-4 p-3 border shadow-sm transition-all" :class="orderStatus === 'future_order' ? 'border-warning' : ''">
                            <div class="form-check form-switch d-flex align-items-center justify-content-between gap-3 p-0 m-0 cursor-pointer" @click="orderStatus = (orderStatus === 'future_order' ? 'pending' : 'future_order')">
                                <div>
                                    <label class="form-check-label fw-bold mb-0 text-body-emphasis" for="futureOrderSwitch">Schedule Future Order</label>
                                    <div class="small text-body-secondary mt-1" style="font-size: 11px;">Save as a draft for later.</div>
                                </div>
                                <input class="form-check-input fs-4 m-0" type="checkbox" role="switch" id="futureOrderSwitch" :checked="orderStatus === 'future_order'" @change="orderStatus = $event.target.checked ? 'future_order' : 'pending'" @click.stop>
                            </div>
                            <div x-show="orderStatus === 'future_order'" x-cloak class="mt-3 pt-3 border-top">
                                <label class="form-label fw-semibold small text-body-secondary text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Delivery Date</label>
                                <input type="date" class="form-control form-control-sm border-warning bg-warning bg-opacity-10 fw-bold" :min="new Date().toISOString().split('T')[0]" x-model="futureOrderDate" required>
                            </div>
                        </div>

                        {{-- Action Panel --}}
                        <div x-show="isConfirmMode" x-cloak class="mb-4 bg-info bg-opacity-10 border border-info border-opacity-25 rounded-4 p-3 shadow-sm">
                            <h6 class="fw-bold text-info-emphasis mb-3"><i class="bi bi-check-circle-fill me-2"></i>Confirmation Options</h6>
                            
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="confirmAction" id="actionConfirmNow" value="now" x-model="confirmAction">
                                <label class="form-check-label fw-semibold text-body-emphasis" for="actionConfirmNow">Confirm Immediately</label>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="confirmAction" id="actionSchedule" value="schedule" x-model="confirmAction">
                                <label class="form-check-label fw-semibold text-body-emphasis" for="actionSchedule">Schedule for Future Confirmation</label>
                            </div>
                            
                            <div x-show="confirmAction === 'schedule'" x-cloak x-transition class="mb-3">
                                <label class="form-label fw-semibold small text-body-secondary text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Reason for Reschedule <span class="text-danger">*</span></label>
                                <select x-select class="form-select form-select-sm mb-2" x-model="scheduleReason">
                                    <option value="" disabled selected>Select a reason...</option>
                                    <template x-for="reason in rescheduleReasons" :key="reason.id">
                                        <option :value="reason.reason" x-text="reason.reason"></option>
                                    </template>
                                </select>
                                
                                <label class="form-label fw-semibold small text-body-secondary text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Scheduled Date <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control form-control-sm" x-model="scheduledConfirmDate" :min="new Date().toISOString().slice(0,16)">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-semibold small text-body-secondary text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Internal Notes (Optional)</label>
                                <textarea class="form-control form-control-sm" rows="2" x-model="confirmNotes" placeholder="Any additional notes..."></textarea>
                            </div>
                            
                            <button type="button" @click.prevent="submitConfirmation()" :disabled="placing || cart.length === 0 || !partyId || !warehouseId || (confirmAction === 'schedule' && (!scheduleReason || !scheduledConfirmDate))"
                                class="btn btn-info text-white w-100 py-3 fw-bold text-uppercase shadow-sm position-relative overflow-hidden" style="letter-spacing: 1px;">
                                <span x-show="placing" class="spinner-border spinner-border-sm me-2"></span>
                                <i x-show="!placing" class="bi bi-check2-all me-2 fs-5 align-middle"></i>
                                <span x-text="confirmAction === 'schedule' ? 'Save Schedule' : 'Submit Confirmation'" class="align-middle"></span>
                            </button>
                        </div>
                        
                        <button x-show="!isConfirmMode" type="button" @click.prevent="placeOrder()" :disabled="placing || cart.length === 0 || !partyId || !warehouseId"
                            class="btn btn-primary w-100 py-3 fw-bold text-uppercase shadow-sm position-relative overflow-hidden" style="letter-spacing: 1px;">
                            <span x-show="placing" class="spinner-border spinner-border-sm me-2"></span>
                            <i x-show="!placing" class="bi bi-check-circle-fill me-2 fs-5 align-middle"></i>
                            <span x-text="editingOrderId ? 'Update Order' : (orderStatus === 'future_order' ? 'Save Future Order' : 'Complete Order')" class="align-middle"></span>
                        </button>
                        
                        <template x-if="formErrors.length">
                            <div class="alert alert-danger mt-3 mb-0 p-3 shadow-sm small border-0 text-bg-danger-subtle text-danger-emphasis-emphasis">
                                <div class="d-flex align-items-center gap-2 mb-2 fw-bold"><i class="bi bi-exclamation-triangle-fill"></i> Please fix the following errors:</div>
                                <ul class="mb-0 ps-3">
                                    <template x-for="e in formErrors" :key="e"><li x-text="e"></li></template>
                                </ul>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-5 overflow-hidden glass-panel mt-2 mb-4" x-show="partyId" x-cloak>
        <div class="card-header bg-body-tertiary border-bottom-0 p-3 p-lg-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm rounded-pill px-4 fw-bold" :class="bottomTab === 'history' ? 'btn-primary' : 'btn-outline-secondary'" @click="bottomTab = 'history'; expandedOrderId = null">Order History</button>
                    <button type="button" class="btn btn-sm rounded-pill px-4 fw-bold" :class="bottomTab === 'future' ? 'btn-primary' : 'btn-outline-secondary'" @click="bottomTab = 'future'; expandedOrderId = null">Future Orders</button>
                    <button type="button" class="btn btn-sm rounded-pill px-4 fw-bold" :class="bottomTab === 'tags' ? 'btn-primary' : 'btn-outline-secondary'" @click="bottomTab = 'tags'; expandedOrderId = null">Tagging</button>
                </div>
                <div class="text-lg-end">
                    <h5 class="mb-1 fw-bold text-body-emphasis"><i class="bi bi-layers me-2 text-primary"></i>Order Center</h5>
                    <p class="mb-0 small text-body-secondary">Tap an order to expand its details.</p>
                </div>
            </div>
        </div>
        <div class="card-body p-4 p-lg-4">
            <template x-if="bottomTab === 'history'">
                <div class="card border-0 shadow-sm rounded-4 bg-body overflow-hidden">
                    <template x-if="historyOrders && historyOrders.length > 0">
                        <div class="list-group list-group-flush border-top" style="max-height: 500px; overflow-y: auto;">
                            <div class="list-group-item bg-body-tertiary d-none d-lg-flex text-body-secondary small fw-bold text-uppercase py-3 sticky-top" style="z-index: 1; letter-spacing: 0.5px; font-size: 0.75rem;">
                                <div class="col-3 ps-4">Order Details</div>
                                <div class="col-3">Logistics & Items</div>
                                <div class="col-3">Status</div>
                                <div class="col-3 text-end pe-5">Total Amount</div>
                            </div>
                            <template x-for="(order, index) in historyOrders" :key="'history-' + order.id">
                                <div class="list-group-item p-0 border-bottom">
                                    <div @click="toggleOrderDetails(order.id)" class="d-flex flex-column flex-lg-row align-items-lg-center p-3 px-lg-0 py-lg-3 hover-bg-body transition-all" style="cursor: pointer;">
                                        
                                        <div class="col-12 col-lg-3 ps-lg-4 mb-3 mb-lg-0">
                                            <div class="fw-bold text-primary mb-1">
                                                <span class="text-secondary opacity-75 me-1" x-text="(index + 1) + '.'"></span>
                                                <span x-text="order.order_no || order.order_number || ('Order #' + order.id)"></span>
                                            </div>
                                            <div class="small text-body-secondary d-flex flex-wrap align-items-center gap-1">
                                                <i class="bi bi-calendar-event opacity-75"></i> <span x-text="order.order_date ? new Date(order.order_date).toLocaleDateString() : 'No date'"></span>
                                                <span x-show="order.order_date" class="mx-1">•</span>
                                                <span x-show="order.order_date" x-text="new Date(order.order_date).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})"></span>
                                            </div>
                                        </div>
                                        
                                        <div class="col-12 col-lg-3 mb-3 mb-lg-0">
                                            <div class="fw-medium text-body-secondary small mb-1">
                                                <span class="fw-bold text-body-emphasis" x-text="(order.items ? order.items.length : 0) + ' items'"></span>
                                                <span class="mx-1">•</span>
                                                <span x-text="order.warehouse?.name || 'N/A'"></span>
                                            </div>
                                            <div class="small text-body-secondary" x-show="order.creator"><i class="bi bi-person me-1 opacity-75"></i><span x-text="'by ' + (order.creator?.first_name ? (order.creator.first_name + ' ' + (order.creator.last_name || '')) : (order.creator?.name || ''))"></span></div>
                                        </div>
                                        
                                        <div class="col-12 col-lg-3 mb-3 mb-lg-0">
                                            <span class="badge rounded-pill px-3 py-1.5" :class="`text-bg-${getStatusTheme(order.lifecycle_status || order.status)}-subtle text-${getStatusTheme(order.lifecycle_status || order.status)}-emphasis border border-${getStatusTheme(order.lifecycle_status || order.status)}-subtle`" x-text="order.status_label || order.lifecycle_status || order.status || 'Pending'"></span>
                                        </div>
                                        
                                        <div class="col-12 col-lg-3 text-lg-end pe-lg-4 d-flex align-items-center justify-content-between justify-content-lg-end">
                                            <span class="d-inline-block d-lg-none text-body-secondary small me-2 fw-medium">Total:</span>
                                            <div class="d-flex align-items-center">
                                                <div class="fw-bolder fs-6 text-body-emphasis" x-text="'₹ ' + Number(order.net_amount || 0).toFixed(2)"></div>
                                                <i class="bi ms-3 text-body-secondary transition-all" :class="expandedOrderId === order.id ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div x-show="expandedOrderId === order.id" x-cloak x-collapse>
                                        <div class="bg-body-tertiary">
                                                <div class="p-4 border-bottom border-top border-primary border-3 border-start-0 border-end-0 border-bottom-0 shadow-inner bg-body-tertiary">
                                                    <div class="bg-body rounded-4 border shadow-sm overflow-hidden">
                                                        <div class="row g-0">
                                                            <div class="col-lg-7 p-3 border-end-lg border-bottom border-bottom-lg-0 d-flex flex-column">
                                                                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start mb-3 pb-3 border-bottom border-secondary border-opacity-10">
                                                                    <div>
                                                                        <div class="fw-bold text-body-emphasis small text-uppercase mb-1" style="letter-spacing: 0.5px;"><i class="bi bi-clock me-1 opacity-75"></i>Order Placed</div>
                                                                        <div class="small text-body-secondary" style="font-size: 0.75rem;" x-text="order.order_date ? new Date(order.order_date).toLocaleString() : 'No date'"></div>
                                                                    </div>
                                                                    <div class="mt-2 mt-md-0 text-md-end" x-show="order.creator">
                                                                        <div class="fw-bold text-body-emphasis small text-uppercase mb-1" style="letter-spacing: 0.5px;"><i class="bi bi-person me-1 opacity-75"></i>Created By</div>
                                                                        <div class="small text-primary fw-medium" style="font-size: 0.75rem;" x-text="(order.creator?.first_name ? (order.creator.first_name + ' ' + (order.creator.last_name || '')) : (order.creator?.name || ''))"></div>
                                                                    </div>
                                                                </div>
                                                                
                                                                <div class="row g-3 mt-auto">
                                                                    <div class="col-md-6" :class="{'border-end-md border-secondary border-opacity-10': order.billing_address_line_1 && (order.billing_address_line_1 !== order.shipping_address_line_1)}">
                                                                        <div class="d-flex align-items-center mb-1">
                                                                            <i class="bi bi-truck text-body-secondary me-2 opacity-75"></i>
                                                                            <div class="fw-bold text-body-emphasis" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;">Shipping Address</div>
                                                                        </div>
                                                                        <div class="small text-body-secondary lh-sm" style="font-size: 0.75rem;" x-text="order.shipping_address_line_1 ? [order.shipping_address_line_1, order.shipping_address_line_2, order.shipping_village_name, order.shipping_post_office, order.shipping_taluka, order.shipping_city, order.shipping_district, order.shipping_state, order.shipping_pincode].filter(Boolean).join(', ') : 'Not available'">
                                                                        </div>
                                                                    </div>
                                                                    
                                                                    <div class="col-md-6" x-show="order.billing_address_line_1 && (order.billing_address_line_1 !== order.shipping_address_line_1)">
                                                                        <div class="d-flex align-items-center mb-1">
                                                                            <i class="bi bi-receipt text-body-secondary me-2 opacity-75"></i>
                                                                            <div class="fw-bold text-body-emphasis" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;">Billing Address</div>
                                                                        </div>
                                                                        <div class="small text-body-secondary lh-sm" style="font-size: 0.75rem;" x-text="order.billing_address_line_1 ? [order.billing_address_line_1, order.billing_address_line_2, order.billing_village_name, order.billing_post_office, order.billing_taluka, order.billing_city, order.billing_district, order.billing_state, order.billing_pincode].filter(Boolean).join(', ') : 'Not available'">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="col-lg-5 p-3 bg-primary bg-opacity-10 d-flex flex-column justify-content-center">
                                                                <div class="d-flex justify-content-between align-items-center mb-1 small">
                                                                    <span class="text-body-secondary fw-medium" style="font-size: 0.75rem;">Subtotal</span>
                                                                    <span class="text-body-emphasis fw-semibold" style="font-size: 0.75rem;" x-text="'₹ ' + Number(order.total_amount || 0).toFixed(2)"></span>
                                                                </div>
                                                                <div class="d-flex justify-content-between align-items-center mb-1 small" x-show="Number(order.tax_amount || 0) > 0">
                                                                    <span class="text-body-secondary fw-medium" style="font-size: 0.75rem;">GST</span>
                                                                    <span class="text-body-emphasis fw-semibold" style="font-size: 0.75rem;" x-text="'+₹ ' + Number(order.tax_amount || 0).toFixed(2)"></span>
                                                                </div>
                                                                <div class="d-flex justify-content-between align-items-center mb-2 small" x-show="Number(order.discount_amount || 0) > 0">
                                                                    <span class="text-success fw-medium" style="font-size: 0.75rem;">Total Discount</span>
                                                                    <span class="text-success fw-bold" style="font-size: 0.75rem;" x-text="'-₹ ' + Number(order.discount_amount || 0).toFixed(2)"></span>
                                                                </div>
                                                                
                                                                <div class="d-flex flex-wrap gap-1 mb-2">
                                                                    <div class="badge text-bg-primary-subtle text-primary-emphasis border-opacity-25 px-2 py-0" style="font-size: 0.65rem;" x-show="order.applied_offer?.name" x-text="'Offer: ' + order.applied_offer?.name"></div>
                                                                    <template x-for="offerName in getAppliedItemOffers(order)" :key="offerName">
                                                                        <div class="badge text-bg-info-subtle text-info-emphasis border-opacity-25 px-2 py-0" style="font-size: 0.65rem;" x-text="'Item Offer: ' + offerName"></div>
                                                                    </template>
                                                                    <div class="badge text-bg-success-subtle text-success-emphasis border-opacity-25 px-2 py-0" style="font-size: 0.65rem;" x-show="order.coupon_code" x-text="'Coupon: ' + order.coupon_code"></div>
                                                                </div>
                                                                
                                                                <div class="pt-2 border-top border-primary border-opacity-25 d-flex justify-content-between align-items-center mt-auto">
                                                                    <span class="fw-bold text-primary text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">Net Total</span>
                                                                    <span class="fw-bolder fs-6 text-primary" x-text="'₹ ' + Number(order.net_amount || 0).toFixed(2)"></span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="border-top border-primary border-opacity-10 bg-body">
                                                        <div class="list-group list-group-flush">
                                                            <div class="list-group-item bg-body-tertiary d-none d-md-flex align-items-center text-body-secondary fw-bold text-uppercase py-1" style="letter-spacing: 0.5px; font-size: 0.65rem;">
                                                                <div class="col-5 ps-2">Item Details</div>
                                                                <div class="col-2 text-center">Qty</div>
                                                                <div class="col-2 text-end">Price</div>
                                                                <div class="col-3 text-end pe-2">Total</div>
                                                            </div>
                                                            <template x-for="(item, idx) in (order.items || [])" :key="'history-item-' + order.id + '-' + item.id">
                                                                <div class="list-group-item d-flex flex-column flex-md-row align-items-md-center py-2 hover-bg-body transition-all">
                                                                    <div class="col-12 col-md-5 d-flex flex-column justify-content-center mb-1 mb-md-0 ps-md-2">
                                                                        <div class="fw-semibold text-body-emphasis mb-0" style="font-size: 0.8rem;">
                                                                            <span class="text-secondary opacity-75 me-1" x-text="(idx + 1) + '.'"></span>
                                                                            <span x-text="item.product?.name || item.product_name || 'Product'"></span>
                                                                            <span x-show="Number(item.unit_price || 0) === 0 || Number(item.total_amount || 0) === 0" class="badge bg-success bg-opacity-10 border border-success border-opacity-25 text-success ms-1 px-1 py-0 rounded-pill" style="font-size: 0.55rem;">Free Gift</span>
                                                                        </div>
                                                                        <div class="small text-body-secondary d-flex flex-wrap align-items-center gap-2 mt-0" style="font-size: 0.7rem;">
                                                                            <span><i class="bi bi-upc-scan opacity-75"></i> <span x-text="item.product?.sku || item.sku || 'N/A'"></span></span>
                                                                            <span class="border-start ps-2" x-show="Number(item.tax_amount || 0) > 0">
                                                                                GST: <span class="fw-medium text-body-secondary" x-text="'₹ ' + Number(item.tax_amount || 0).toFixed(2)"></span> 
                                                                                <span x-text="'(' + (item.tax_rate || 0) + '%)'"></span>
                                                                            </span>
                                                                        </div>
                                                                        <div class="small text-info fw-medium mt-0" style="font-size: 0.65rem;" x-show="getSingleItemOffer(item)">
                                                                            <i class="bi bi-tag-fill me-1"></i>Applied: <span x-text="getSingleItemOffer(item)"></span>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-12 col-md-2 text-md-center mb-1 mb-md-0">
                                                                        <span class="d-inline-block d-md-none text-body-secondary small me-2 fw-medium">Qty:</span>
                                                                        <span class="badge text-bg-secondary-subtle text-secondary-emphasis border-opacity-25 px-2 py-1 rounded-pill fw-bold" style="font-size: 0.7rem;" x-text="item.quantity + 'x'"></span>
                                                                    </div>
                                                                    <div class="col-12 col-md-2 text-md-end mb-1 mb-md-0">
                                                                        <span class="d-inline-block d-md-none text-body-secondary small me-2 fw-medium">Price:</span>
                                                                        <div class="fw-medium text-body-secondary" style="font-size: 0.75rem;" x-text="'₹ ' + Number(item.unit_price || 0).toFixed(2)"></div>
                                                                        <div x-show="Number(item.discount_amount || 0) > 0" class="text-success fw-bold d-flex align-items-center justify-content-md-end mt-0" style="font-size: 0.65rem;">
                                                                            <i class="bi bi-tag-fill me-1 opacity-75"></i>-<span x-text="'₹ ' + Number(item.discount_amount || 0).toFixed(2)"></span>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-12 col-md-3 text-md-end pe-md-2 mt-1 mt-md-0 pt-1 pt-md-0 border-top border-md-0">
                                                                        <span class="d-inline-block d-md-none text-body-secondary small me-2 fw-medium">Total:</span>
                                                                        <div class="fw-bolder text-body-emphasis" style="font-size: 0.85rem;" x-text="'₹ ' + Number(item.total_amount || 0).toFixed(2)"></div>
                                                                    </div>
                                                                </div>
                                                            </template>
                                                            <template x-if="!order.items || order.items.length === 0">
                                                                <div class="list-group-item text-center text-body-secondary py-3 small">
                                                                    No order items found.
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex justify-content-end gap-2 p-3 bg-body-tertiary border-top">
                                                        @can('complaints.create')
                                                        <a :href="`{{ route('complaints.index') }}?order_no=${encodeURIComponent(order.order_no || order.order_number || '')}&customer_id=${order.party_id || ''}`"
                                                           class="btn btn-sm btn-outline-warning rounded-pill px-4 fw-bold">
                                                            <i class="bi bi-headset me-1"></i> Raise Complaint
                                                        </a>
                                                        @endcan
                                                        @can('orders.edit')
                                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-4 fw-bold" @click="editOrder(order.id)" x-show="!['delivered', 'cancelled', 'returned', 'shipped', 'dispatched'].includes(order.status || order.lifecycle_status)">
                                                            <i class="bi bi-pencil-square me-1"></i> Edit Order
                                                        </button>
                                                        @endcan
                                                        @can('orders.cancel')
                                                        <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-4 fw-bold" @click="cancelOrder(order.id, order.order_no || order.order_number)" x-show="!['delivered', 'cancelled', 'returned'].includes(order.status || order.lifecycle_status)">
                                                            <i class="bi bi-x-circle me-1"></i> Cancel Order
                                                        </button>
                                                        @endcan
                                                    </div>
                                                </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                    <template x-if="!historyOrders || !historyOrders.length">
                        <div class="alert alert-secondary border-0 mb-0 p-5 text-center text-body-secondary">
                            <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                            No order history available.
                        </div>
                    </template>
                </div>
            </template>

            <template x-if="bottomTab === 'future'">
                <div class="card border-0 shadow-sm rounded-4 bg-body overflow-hidden">
                    <template x-if="futureOrders && futureOrders.length > 0">
                        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                            <table class="table table-hover table-sm align-middle mb-0" style="font-size: 0.85rem;">
                                <thead class="table-secondary sticky-top" style="z-index: 1;">
                                    <tr>
                                        <th scope="col" class="text-nowrap ps-4 py-2 border-bottom-0">Order #</th>
                                        <th scope="col" class="text-nowrap py-2 border-bottom-0">Future Date</th>
                                        <th scope="col" class="text-nowrap py-2 border-bottom-0">Status</th>
                                        <th scope="col" class="text-nowrap py-2 border-bottom-0">Warehouse & Items</th>
                                        <th scope="col" class="text-nowrap text-end pe-4 py-2 border-bottom-0">Total</th>
                                    </tr>
                                </thead>
                                <template x-for="(order, index) in futureOrders" :key="'future-' + order.id">
                                    <tbody class="border-top-0 border-bottom">
                                        <tr @click="toggleOrderDetails(order.id)" class="transition-all" style="cursor: pointer;">
                                            <td class="text-nowrap ps-4 py-2 fw-bold text-body-emphasis">
                                                <span class="text-secondary opacity-75 me-1" x-text="(index + 1) + '.'"></span>
                                                <span x-text="order.order_no || order.order_number || ('Order #' + order.id)"></span>
                                            </td>
                                            <td class="text-nowrap py-2">
                                                <div class="fw-medium text-body-emphasis" x-text="order.future_order_date ? new Date(order.future_order_date).toLocaleDateString() : 'No future date'"></div>
                                                <div class="small text-body-secondary" x-show="order.future_order_date" x-text="new Date(order.future_order_date).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})"></div>
                                                <div class="small text-body-secondary mt-1" x-show="order.creator" x-text="'by ' + (order.creator?.first_name ? (order.creator.first_name + ' ' + (order.creator.last_name || '')) : (order.creator?.name || ''))"></div>
                                            </td>
                                            <td class="py-2">
                                                <span class="badge rounded-pill px-2" :class="`text-bg-${getStatusTheme(order.lifecycle_status || order.status)}-subtle text-${getStatusTheme(order.lifecycle_status || order.status)}-emphasis border border-${getStatusTheme(order.lifecycle_status || order.status)}-subtle`" x-text="order.status_label || order.lifecycle_status || order.status || 'Pending'"></span>
                                            </td>
                                            <td class="py-2 text-body-secondary">
                                                <span x-text="order.warehouse?.name ? order.warehouse.name : 'N/A'"></span>
                                                <span class="mx-1">•</span>
                                                <span x-text="(order.items ? order.items.length : 0) + ' items'"></span>
                                            </td>
                                            <td class="text-end pe-4 py-2 fw-bold text-body-emphasis" x-text="'₹ ' + Number(order.net_amount || 0).toFixed(2)"></td>
                                        </tr>
                                        <tr x-show="expandedOrderId === order.id" x-cloak>
                                            <td colspan="5" class="p-0 border-0 bg-body-tertiary">
                                                <div class="p-4 border-bottom border-top border-primary border-3 border-start-0 border-end-0 border-bottom-0 shadow-sm">
                                                    <div class="row g-3 mb-4">
                                                        <div class="col-lg-4">
                                                            <div class="p-3 rounded-3 bg-body border h-100 shadow-sm">
                                                                <div class="fw-bold text-body-emphasis mb-1 small text-uppercase" style="letter-spacing: 0.5px;">Order Info</div>
                                                                <div class="small text-body-secondary mb-1"><i class="bi bi-clock me-1"></i> <span x-text="order.order_date ? new Date(order.order_date).toLocaleString() : 'No date'"></span></div>
                                                                <div class="small text-primary fw-medium" x-show="order.creator"><i class="bi bi-person me-1"></i> <span x-text="'Created by ' + (order.creator?.first_name ? (order.creator.first_name + ' ' + (order.creator.last_name || '')) : (order.creator?.name || ''))"></span></div>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-4">
                                                            <div class="p-3 rounded-3 bg-body border h-100 shadow-sm">
                                                                <div class="fw-bold text-body-emphasis mb-1 small text-uppercase" style="letter-spacing: 0.5px;">Shipping</div>
                                                                <div class="small text-body-secondary" x-text="order.shipping_address_line_1 ? [order.shipping_address_line_1, order.shipping_address_line_2, order.shipping_village_name, order.shipping_post_office, order.shipping_taluka, order.shipping_city, order.shipping_district, order.shipping_state, order.shipping_pincode].filter(Boolean).join(', ') : 'Not available'"></div>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-4">
                                                            <div class="p-3 rounded-3 bg-body border h-100 shadow-sm">
                                                                <div class="fw-bold text-body-emphasis mb-1 small text-uppercase" style="letter-spacing: 0.5px;">Billing</div>
                                                                <div class="small text-body-secondary" x-text="order.billing_address_line_1 ? [order.billing_address_line_1, order.billing_address_line_2, order.billing_village_name, order.billing_post_office, order.billing_taluka, order.billing_city, order.billing_district, order.billing_state, order.billing_pincode].filter(Boolean).join(', ') : 'Same as shipping'"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="bg-body rounded-3 border overflow-hidden shadow-sm">
                                                        <table class="table table-sm align-middle mb-0" style="font-size: 0.8rem;">
                                                            <thead class="table-light">
                                                                <tr class="text-body-secondary">
                                                                    <th class="ps-3 py-2 fw-medium">Item</th>
                                                                    <th class="py-2 fw-medium">SKU</th>
                                                                    <th class="text-center py-2 fw-medium">Qty</th>
                                                                    <th class="text-end py-2 fw-medium">Price</th>
                                                                    <th class="text-end pe-3 py-2 fw-medium">Total</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <template x-for="item in (order.items || [])" :key="'future-item-' + order.id + '-' + item.id">
                                                                    <tr>
                                                                        <td class="fw-semibold text-body-emphasis ps-3 py-2" x-text="item.product?.name || item.product_name || 'Product'"></td>
                                                                        <td class="text-body-secondary py-2" x-text="item.product?.sku || item.sku || 'N/A'"></td>
                                                                        <td class="text-center fw-semibold py-2" x-text="item.quantity"></td>
                                                                        <td class="text-end text-body-secondary py-2" x-text="'₹ ' + Number(item.unit_price || 0).toFixed(2)"></td>
                                                                        <td class="text-end fw-bold text-body-emphasis pe-3 py-2" x-text="'₹ ' + Number(item.total_amount || 0).toFixed(2)"></td>
                                                                    </tr>
                                                                </template>
                                                                <template x-if="!order.items || order.items.length === 0">
                                                                    <tr>
                                                                        <td colspan="5" class="text-center text-body-secondary py-3">No order items found.</td>
                                                                    </tr>
                                                                </template>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                    <div class="d-flex justify-content-end gap-2 mt-3 pt-3 border-top">
                                                        @can('complaints.create')
                                                        <a :href="`{{ route('complaints.index') }}?order_no=${encodeURIComponent(order.order_no || order.order_number || '')}&customer_id=${order.party_id || ''}`"
                                                           class="btn btn-sm btn-outline-warning rounded-pill px-4 fw-bold">
                                                            <i class="bi bi-headset me-1"></i> Raise Complaint
                                                        </a>
                                                        @endcan
                                                        @can('orders.edit')
                                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-4 fw-bold" @click="editOrder(order.id)" x-show="!['delivered', 'cancelled', 'returned', 'shipped', 'dispatched'].includes(order.status || order.lifecycle_status)">
                                                            <i class="bi bi-pencil-square me-1"></i> Edit Order
                                                        </button>
                                                        @endcan
                                                        @can('orders.cancel')
                                                        <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-4 fw-bold" @click="cancelOrder(order.id, order.order_no || order.order_number)" x-show="!['delivered', 'cancelled', 'returned'].includes(order.status || order.lifecycle_status)">
                                                            <i class="bi bi-x-circle me-1"></i> Cancel Order
                                                        </button>
                                                        @endcan
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </template>
                            </table>
                        </div>
                    </template>
                    <template x-if="!futureOrders || !futureOrders.length">
                        <div class="alert alert-secondary border-0 mb-0 p-5 text-center text-body-secondary">
                            <i class="bi bi-calendar-x fs-2 d-block mb-2 opacity-50"></i>
                            No future orders scheduled.
                        </div>
                    </template>
                </div>
            </template>

            <template x-if="bottomTab === 'tags'">
                    <div class="card border-0 shadow-sm rounded-4 bg-body overflow-hidden">
                        <template x-if="customerDetails && customerDetails.call_logs && customerDetails.call_logs.length > 0">
                            <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                                <table class="table table-hover table-sm align-middle mb-0" style="font-size: 0.85rem;">
                                    <thead class="table-light sticky-top" style="z-index: 1;">
                                        <tr>
                                            <th scope="col" class="text-nowrap ps-4 py-2 border-bottom-0">Date & Time</th>
                                            <th scope="col" class="text-nowrap py-2 border-bottom-0">Agent</th>
                                            <th scope="col" class="text-nowrap py-2 border-bottom-0">Classification Path</th>
                                            <th scope="col" class="py-2 border-bottom-0">Notes & Details</th>
                                        </tr>
                                    </thead>
                                    <tbody class="border-top-0">
                                        <template x-for="(log, index) in customerDetails.call_logs" :key="'log-' + log.id">
                                            <tr>
                                                <td class="text-nowrap ps-4 text-body-secondary py-2">
                                                    <div class="d-flex align-items-start gap-2">
                                                        <span class="text-secondary opacity-75 fw-bold" x-text="(index + 1) + '.'"></span>
                                                        <div>
                                                            <div class="text-body-emphasis" x-text="new Date(log.created_at).toLocaleString(undefined, {month:'short', day:'numeric', year:'numeric'})"></div>
                                                            <div class="small" x-text="new Date(log.created_at).toLocaleString(undefined, {hour:'2-digit', minute:'2-digit'})"></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-nowrap fw-medium py-2">
                                                    <i class="bi bi-person text-body-secondary me-1"></i>
                                                    <span x-text="(log.agent && log.agent.name) ? log.agent.name : 'Unknown'"></span>
                                                </td>
                                                <td class="py-2">
                                                    <div class="d-flex align-items-center flex-wrap gap-1">
                                                        <span class="badge text-bg-primary-subtle text-primary-emphasis px-2" x-text="(log.tag_l1 && log.tag_l1.name) ? log.tag_l1.name : 'N/A'"></span>
                                                        <i class="bi bi-caret-right-fill text-body-secondary" style="font-size: 8px;" x-show="log.tag_l2"></i>
                                                        <span class="badge text-bg-info-subtle text-info-emphasis px-2" x-show="log.tag_l2" x-text="log.tag_l2 ? log.tag_l2.name : ''"></span>
                                                        <i class="bi bi-caret-right-fill text-body-secondary" style="font-size: 8px;" x-show="log.tag_l3"></i>
                                                        <span class="badge text-bg-success-subtle text-success-emphasis px-2" x-show="log.tag_l3" x-text="log.tag_l3 ? log.tag_l3.name : ''"></span>
                                                    </div>
                                                </td>
                                                <td class="py-2">
                                                    <div class="text-truncate text-body-secondary fst-italic mb-1" style="max-width: 350px;" :title="log.notes" x-show="log.notes">
                                                        <i class="bi bi-chat-text text-body-secondary me-1"></i> <span x-text="log.notes"></span>
                                                    </div>
                                                    <div class="d-flex flex-wrap gap-2 mt-1" x-show="log.metas && log.metas.length > 0">
                                                        <template x-for="meta in log.metas" :key="meta.id">
                                                            <span class="badge bg-body-secondary text-body-secondary border fw-normal py-1 px-2">
                                                                <span x-text="meta.key.replace(/_/g, ' ')" class="text-capitalize opacity-75"></span>: 
                                                                <span class="fw-medium text-body-emphasis" x-text="(function(v) {
                                                                    try {
                                                                        let p = typeof v === 'string' ? JSON.parse(v) : v;
                                                                        return Array.isArray(p) ? p.join(', ') : p;
                                                                    } catch(e) { return v; }
                                                                })(meta.value)"></span>
                                                            </span>
                                                        </template>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </template>
                        <template x-if="!customerDetails || !customerDetails.call_logs || customerDetails.call_logs.length === 0">
                            <div class="alert alert-secondary border-0 mb-0 p-5 text-center text-body-secondary">
                                <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                                No call history available for this customer.
                            </div>
                        </template>
                    </div>
            </template>
        </div>
    </div>

    {{-- Promotions Modal --}}
    <div class="modal fade" id="promotionsModal">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-body-tertiary border-bottom-0 p-4">
                    <h5 class="modal-title fw-bold text-body-emphasis d-flex align-items-center gap-2">
                        <div class="text-bg-primary-subtle text-primary-emphasis rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                            <i class="bi bi-tag-fill fs-6"></i>
                        </div>
                        Promotions & Offers
                    </h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <ul class="nav nav-tabs nav-fill border-bottom-0 bg-body-tertiary px-3" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-semibold py-3 border-0 border-bottom border-3 border-transparent" data-bs-toggle="tab" data-bs-target="#tab-offers" type="button" role="tab" onclick="this.classList.add('border-primary'); this.parentElement.nextElementSibling.firstElementChild.classList.remove('border-primary')">
                                Offers <span class="badge bg-secondary ms-1 rounded-pill" x-text="activeOffers.length"></span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-semibold py-3 border-0 border-bottom border-3 border-transparent" data-bs-toggle="tab" data-bs-target="#tab-coupons" type="button" role="tab" onclick="this.classList.add('border-primary'); this.parentElement.previousElementSibling.firstElementChild.classList.remove('border-primary')">
                                Coupons <span class="badge bg-secondary ms-1 rounded-pill" x-text="activeCoupons.length"></span>
                            </button>
                        </li>
                    </ul>
                    <div class="tab-content p-4 bg-body">
                        
                        {{-- Offers Tab --}}
                        <div class="tab-pane fade show active" id="tab-offers" role="tabpanel">
                            <template x-if="activeOffers.length === 0">
                                <div class="text-center py-4 text-body-secondary">
                                    <i class="bi bi-gift fs-1 mb-2 d-block opacity-50"></i>
                                    <p class="mb-0 fw-medium">No offers available for your current cart.</p>
                                </div>
                            </template>
                            <div class="space-y-3">
                                <template x-for="offer in sortedActiveOffers" :key="offer.id">
                                    <div class="card border-2 rounded-4 transition-all hover-shadow" 
                                         :class="['bogo', 'free_product'].includes(offer.type) ? 'border-info border-opacity-25 bg-info bg-opacity-10' : ((bestOrderOffer && bestOrderOffer.id === offer.id) ? 'border-success bg-success bg-opacity-10' : (orderOfferDiscount(offer) > 0 ? 'border-secondary border-opacity-10 bg-body-tertiary cursor-pointer' : 'border-secondary border-opacity-10 bg-body-secondary opacity-75'))" 
                                         @click="if(['order_discount', 'category_discount'].includes(offer.type) && orderOfferDiscount(offer) > 0) appliedOfferId = ((bestOrderOffer && bestOrderOffer.id === offer.id) ? 'none' : offer.id)">
                                        <div class="card-body p-3 d-flex align-items-center justify-content-between gap-3">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="border border-dashed border-2 rounded-3 p-2 bg-body text-center d-flex flex-column justify-content-center align-items-center" style="min-width: 90px; height: 90px;">
                                                    <template x-if="['bogo', 'free_product'].includes(offer.type)">
                                                        <div>
                                                            <i class="bi bi-gift-fill text-info fs-3 d-block mb-1"></i>
                                                            <span class="badge text-bg-info-subtle text-info-emphasis w-100" x-text="offer.type === 'bogo' ? 'BOGO' : 'GIFT'"></span>
                                                        </div>
                                                    </template>
                                                    <template x-if="['order_discount', 'category_discount'].includes(offer.type)">
                                                        <div>
                                                            <h5 class="fw-black text-body-emphasis mb-1" x-text="offer.discount_type === 'percentage' ? parseFloat(offer.value) + '%' : '₹ ' + parseFloat(offer.value)"></h5>
                                                            <span class="badge text-bg-primary-subtle text-primary-emphasis w-100">OFF</span>
                                                        </div>
                                                    </template>
                                                </div>
                                                
                                                <div class="ps-2">
                                                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                                        <h6 class="fw-bold mb-0" :class="(appliedOfferId === offer.id || ['bogo', 'free_product'].includes(offer.type)) ? 'text-body-emphasis' : 'text-body'" x-text="offer.name"></h6>
                                                        <span class="badge text-bg-secondary-subtle text-secondary-emphasis-emphasis rounded-pill px-2 py-0.5 small" style="font-size: 0.7rem;" x-text="'Priority: ' + offer.priority"></span>
                                                    </div>
                                                    
                                                    {{-- Common Rules --}}
                                                    <div class="mb-2 pe-3 ps-2 border-start border-2 border-secondary border-opacity-25">
                                                        <div x-show="offer.type === 'bogo'">
                                                            <p class="mb-1 small text-body-secondary" x-text="'Buy ' + offer.buy_qty + ' Get ' + offer.get_qty + ' Free on ' + offer.product_name"></p>
                                                        </div>
                                                        <div x-show="offer.type === 'free_product'">
                                                            <p class="mb-1 small text-body-secondary" x-text="'Free Gift: ' + offer.product_name"></p>
                                                        </div>
                                                        <div x-show="['order_discount', 'category_discount'].includes(offer.type)">
                                                            <p class="mb-1 small text-body-secondary" x-show="offer.max_discount > 0" x-text="'Max Discount: ₹ ' + Number(offer.max_discount).toFixed(2)"></p>
                                                        </div>
                                                        <p class="mb-1 small text-body-secondary" x-show="offer.min_spend > 0" x-text="'Min. Spend: ₹ ' + Number(offer.min_spend).toFixed(2)"></p>
                                                        <p class="mb-0 small text-body-secondary" x-show="offer.ends_at" x-text="'Valid till ' + new Date(offer.ends_at).toLocaleDateString()"></p>
                                                    </div>

                                                    {{-- Savings/Unlock Status --}}
                                                    <div x-show="['bogo', 'free_product'].includes(offer.type)">
                                                        <p class="mb-0 small text-info"><i class="bi bi-lightning-charge-fill me-1"></i>Auto-applied to eligible items</p>
                                                    </div>
                                                    <div x-show="['order_discount', 'category_discount'].includes(offer.type)">
                                                        <div x-show="orderOfferDiscount(offer) > 0">
                                                            <p class="mb-0 small fw-medium">You save: <span class="text-success" x-text="'₹ ' + Number(orderOfferDiscount(offer)).toFixed(2)"></span></p>
                                                        </div>
                                                        <div x-show="orderOfferDiscount(offer) === 0">
                                                            <p class="mb-0 small text-danger"><i class="bi bi-info-circle me-1"></i>Add <span x-text="'₹ ' + Number(offer.min_spend).toFixed(2)"></span> to cart to unlock</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="flex-shrink-0">
                                                {{-- Auto Applied Badge --}}
                                                <template x-if="['bogo', 'free_product'].includes(offer.type)">
                                                    <span class="badge text-bg-info-subtle text-info-emphasis-emphasis rounded-pill px-3 py-2 fw-medium">Active</span>
                                                </template>
                                                
                                                {{-- Order Discount Actions --}}
                                                <template x-if="['order_discount', 'category_discount'].includes(offer.type)">
                                                    <div>
                                                        <template x-if="(bestOrderOffer && bestOrderOffer.id === offer.id)">
                                                            <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm mx-auto" style="width: 28px; height: 28px;">
                                                                <i class="bi bi-check fs-5"></i>
                                                            </div>
                                                        </template>
                                                        <template x-if="!(bestOrderOffer && bestOrderOffer.id === offer.id)">
                                                            <button class="btn btn-sm rounded-pill px-3 fw-medium" 
                                                                    :class="orderOfferDiscount(offer) === 0 ? 'btn-outline-secondary text-body-secondary border' : 'btn-outline-secondary'" 
                                                                    :disabled="orderOfferDiscount(offer) === 0">
                                                                Apply
                                                            </button>
                                                        </template>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                        
                        {{-- Coupons Tab --}}
                        <div class="tab-pane fade" id="tab-coupons" role="tabpanel">
                            
                            {{-- Manual Entry --}}
                            <div class="d-flex align-items-center gap-2 mb-4 p-3 bg-body-secondary rounded-4 border">
                                <i class="bi bi-ticket-perforated text-body-secondary fs-5 ms-1"></i>
                                <input type="text" x-model="couponInputTemp" @keydown.enter.prevent="applyCoupon(couponInputTemp)" placeholder="Enter promo code..." class="form-control border-0 bg-transparent shadow-none font-monospace text-uppercase fw-bold">
                                <button type="button" @click.prevent="applyCoupon(couponInputTemp)" class="btn btn-primary rounded-pill fw-bold text-uppercase tracking-widest px-4 flex-shrink-0 shadow-sm">
                                    Apply
                                </button>
                            </div>

                            <hr class="border-secondary opacity-10 mb-4">

                            <h6 class="fw-bold text-body-secondary text-uppercase tracking-widest small mb-3">Available Coupons</h6>
                            
                            <template x-if="activeCoupons.length === 0">
                                <div class="text-center py-4 text-body-secondary">
                                    <p class="mb-0 fw-medium">No coupons currently available.</p>
                                </div>
                            </template>

                            <div class="space-y-3">
                                <template x-for="c in activeCoupons" :key="c.id">
                                    <div class="card border-2 rounded-4 transition-all hover-shadow cursor-pointer" :class="(couponApplied && couponCode === c.code) ? 'border-success bg-success bg-opacity-10' : 'border-secondary border-opacity-10 bg-body-tertiary'" @click="applyCoupon(c.code)">
                                        <div class="card-body p-3 d-flex align-items-center justify-content-between gap-3">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="border border-dashed border-2 rounded-3 p-2 bg-body text-center d-flex flex-column justify-content-center align-items-center" style="min-width: 90px; height: 90px;">
                                                    <h5 class="fw-black text-body-emphasis mb-1" x-text="c.type === 'percentage' ? parseFloat(c.value) + '%' : '₹ ' + parseFloat(c.value)"></h5>
                                                    <span class="badge text-bg-primary-subtle text-primary-emphasis w-100">OFF</span>
                                                </div>
                                                <div class="ps-2">
                                                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                                        <code class="fw-black text-body-emphasis fs-6 d-block" x-text="c.code"></code>
                                                    </div>
                                                    <div class="pe-3 ps-2 border-start border-2 border-secondary border-opacity-25">
                                                        <p class="mb-1 small text-body-secondary" x-show="c.min_spend > 0" x-text="'Min. Spend: ₹ ' + Number(c.min_spend).toFixed(2)"></p>
                                                        <p class="mb-1 small text-body-secondary" x-show="c.max_discount > 0" x-text="'Max Discount: ₹ ' + Number(c.max_discount).toFixed(2)"></p>
                                                        <p class="mb-1 small text-body-secondary" x-show="c.usage_limit > 0" x-text="'Remaining Uses: ' + Math.max(0, c.usage_limit - c.used_count)"></p>
                                                        <p class="mb-0 small text-body-secondary" x-show="c.expiry_date" x-text="'Valid till ' + new Date(c.expiry_date).toLocaleDateString()"></p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex-shrink-0">
                                                <template x-if="couponApplied && couponCode === c.code">
                                                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 28px; height: 28px;">
                                                        <i class="bi bi-check fs-5"></i>
                                                    </div>
                                                </template>
                                                <template x-if="!(couponApplied && couponCode === c.code)">
                                                    <button class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-medium">Apply</button>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Product Details Modal -->
    <div class="modal fade" id="productDetailsModal" aria-labelledby="productDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content bg-body border-0 shadow-lg" x-show="selectedProductForModal">
                <!-- Header -->
                <div class="modal-header bg-body-tertiary border-bottom py-3 px-4">
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <h5 class="modal-title fw-bold mb-0" id="productDetailsModalLabel" x-text="selectedProductForModal ? selectedProductForModal.name : ''"></h5>
                        <span class="badge text-bg-secondary-subtle text-secondary-emphasis" x-text="selectedProductForModal ? selectedProductForModal.sku : ''"></span>
                        <span class="badge" :class="selectedProductForModal && selectedProductForModal.status === 'published' ? 'bg-success' : 'bg-warning text-body-emphasis'" x-text="selectedProductForModal ? selectedProductForModal.status : ''"></span>
                        <span class="spinner-border spinner-border-sm text-primary ms-2" role="status" x-show="productModalLoading"></span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Body: scrollable single column -->
                <div class="modal-body p-0" style="overflow-y: auto;">
                    <div class="row g-0" style="min-height: 100%;">
                        <!-- Left: Image & Meta (fixed panel) -->
                        <div class="col-md-4 bg-body-tertiary border-end p-3" style="position: sticky; top: 0; height: fit-content; align-self: flex-start;">
                            <div class="card border border-secondary border-opacity-25 mb-3 rounded-4 overflow-hidden position-relative" style="aspect-ratio:1;width:100%;">
                                <img :src="selectedProductForModal ? (selectedProductForModal.image_url || '/assets/images/product-placeholder.svg') : ''" class="w-100 h-100 object-fit-cover" x-on:error="$el.src='/assets/images/product-placeholder.svg'">
                                <span class="position-absolute top-0 end-0 m-2 badge bg-success shadow-sm" x-show="selectedProductForModal && selectedProductForModal.default_discount > 0" x-text="selectedProductForModal ? selectedProductForModal.default_discount + (selectedProductForModal.default_discount_type === 'percent' ? '%' : '') + ' OFF' : ''"></span>
                            </div>
                            <div x-show="selectedProductForModal">
                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    <span x-show="selectedProductForModal && selectedProductForModal.category" class="badge text-bg-primary-subtle text-primary-emphasis border-opacity-25 py-2 px-3"><i class="bi bi-tag-fill me-1"></i><span x-text="selectedProductForModal ? selectedProductForModal.category : ''"></span></span>
                                    <span x-show="selectedProductForModal && selectedProductForModal.brand" class="badge text-bg-dark-subtle text-body-emphasis-emphasis border py-2 px-3"><i class="bi bi-award-fill me-1"></i><span x-text="selectedProductForModal ? selectedProductForModal.brand : ''"></span></span>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-borderless small mb-0 text-body-secondary">
                                        <tbody>
                                            <tr x-show="selectedProductForModal && selectedProductForModal.weight"><th class="ps-0" style="width:100px;">Weight</th><td x-text="selectedProductForModal ? selectedProductForModal.weight : ''"></td></tr>
                                            <tr x-show="selectedProductForModal && selectedProductForModal.uom"><th class="ps-0">UOM</th><td x-text="selectedProductForModal ? selectedProductForModal.uom : ''"></td></tr>
                                            <tr x-show="selectedProductForModal && selectedProductForModal.grade"><th class="ps-0">Grade</th><td x-text="selectedProductForModal ? selectedProductForModal.grade : ''"></td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Right: All Data Cards (scrolls with modal-body) -->
                        <div class="col-md-8 p-3">

                            <!-- Pricing Card -->
                            <div class="card mb-3 border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom border-secondary border-opacity-25">
                                        <div class="text-bg-primary-subtle text-primary-emphasis rounded-2 d-flex align-items-center justify-content-center" style="width:24px;height:24px;"><i class="bi bi-tag-fill" style="font-size:12px;"></i></div>
                                        <h6 class="mb-0 fw-bold text-uppercase text-body" style="font-size:11px;letter-spacing:1px;">Pricing Breakdown</h6>
                                    </div>
                                    <div class="row g-2 mb-2">
                                        <div class="col-4 border-end border-secondary border-opacity-25">
                                            <label class="form-label mb-1 fw-bold text-body-secondary text-uppercase d-block" style="font-size:9px;">Selling Price (Inc. GST)</label>
                                            <div class="fw-black text-primary" style="font-size:18px;" x-text="selectedProductForModal ? '₹ ' + (parseFloat(selectedProductForModal.selling_price||0) * (1 + (parseFloat(selectedProductForModal.tax_rate)||0)/100)).toFixed(2) : ''"></div>
                                            <div class="text-body-secondary text-decoration-line-through" style="font-size:10px;" x-show="selectedProductForModal && selectedProductForModal.mrp > (parseFloat(selectedProductForModal.selling_price||0) * (1 + (parseFloat(selectedProductForModal.tax_rate)||0)/100))" x-text="selectedProductForModal ? 'MRP ₹ ' + parseFloat(selectedProductForModal.mrp||0).toFixed(2) : ''"></div>
                                        </div>
                                        <div class="col-4 border-end border-secondary border-opacity-25 ps-3">
                                            <label class="form-label mb-1 fw-bold text-body-secondary text-uppercase d-block" style="font-size:9px;">Purchase Price</label>
                                            <div class="fw-bold text-body-emphasis" style="font-size:14px;" x-text="selectedProductForModal ? '₹ ' + parseFloat(selectedProductForModal.purchase_price||0).toFixed(2) : ''"></div>
                                        </div>
                                        <div class="col-4 ps-3">
                                            <label class="form-label mb-1 fw-bold text-body-secondary text-uppercase d-block" style="font-size:9px;">Profit Margin</label>
                                            <div class="fw-bold text-success" style="font-size:14px;" x-text="selectedProductForModal && selectedProductForModal.selling_price > 0 && selectedProductForModal.purchase_price > 0 ? (((selectedProductForModal.selling_price - selectedProductForModal.purchase_price) / selectedProductForModal.purchase_price) * 100).toFixed(1) + '%' : 'N/A'"></div>
                                        </div>
                                    </div>
                                    <div class="row g-2 pt-2 border-top border-secondary border-opacity-25">
                                        <div class="col-6 border-end border-secondary border-opacity-25">
                                            <label class="form-label mb-1 fw-bold text-body-secondary text-uppercase d-block" style="font-size:9px;">Taxes</label>
                                            <div class="fw-bold text-body-emphasis" style="font-size:13px;" x-text="selectedProductForModal && selectedProductForModal.tax_rate > 0 ? (selectedProductForModal.tax_rate + '%') : 'No Tax'"></div>
                                        </div>
                                        <div class="col-6 ps-3">
                                            <label class="form-label mb-1 fw-bold text-body-secondary text-uppercase d-block" style="font-size:9px;">HSN / SAC Code</label>
                                            <div class="fw-bold text-body-emphasis" style="font-size:13px;" x-text="selectedProductForModal ? (selectedProductForModal.hsn_code || 'Not Set') : ''"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Inventory & Specs Row -->
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <div class="card h-100 border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-center gap-2 pb-2 mb-2 border-bottom border-secondary border-opacity-25">
                                                <div class="bg-warning bg-opacity-10 text-warning rounded-2 d-flex align-items-center justify-content-center" style="width:24px;height:24px;"><i class="bi bi-box-seam-fill" style="font-size:12px;"></i></div>
                                                <h6 class="mb-0 fw-bold text-uppercase text-body" style="font-size:11px;letter-spacing:1px;">Inventory</h6>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom border-secondary border-opacity-25">
                                                <div>
                                                    <div class="fw-bold text-body-emphasis" style="font-size:16px;" x-text="selectedProductForModal ? (parseFloat(selectedProductForModal.available_stock) + ' ' + (selectedProductForModal.uom || 'Units')) : ''"></div>
                                                    <div class="text-body-secondary" style="font-size:10px;">Available to Order</div>
                                                </div>
                                                <span class="badge" style="font-size:10px;" :class="selectedProductForModal && selectedProductForModal.available_stock > (selectedProductForModal.min_stock_level || 10) ? 'bg-success' : (selectedProductForModal && selectedProductForModal.available_stock > 0 ? 'bg-warning text-body-emphasis' : 'bg-danger')" x-text="selectedProductForModal && selectedProductForModal.available_stock > 0 ? 'In Stock' : 'Out of Stock'"></span>
                                            </div>
                                            <div class="row text-center g-1 mb-2">
                                                <div class="col-4"><div class="fw-semibold" style="font-size:13px;" x-text="selectedProductForModal ? (selectedProductForModal.physical_available || selectedProductForModal.stock) : 0"></div><div class="text-body-secondary" style="font-size:9px;">Physical</div></div>
                                                <div class="col-4 border-start border-end border-secondary border-opacity-25"><div class="fw-semibold text-warning" style="font-size:13px;" x-text="selectedProductForModal ? ((selectedProductForModal.reserved_qty || 0) + (selectedProductForModal.pending_qty || 0)) : 0"></div><div class="text-body-secondary" style="font-size:9px;">Reserved</div></div>
                                                <div class="col-4"><div class="fw-semibold text-danger" style="font-size:13px;" x-text="selectedProductForModal ? selectedProductForModal.min_stock_level : 0"></div><div class="text-body-secondary" style="font-size:9px;">Min Level</div></div>
                                            </div>
                                            <label class="form-label mb-1 fw-bold text-body-secondary text-uppercase d-block" style="font-size:9px;">Tracking</label>
                                            <div class="list-group list-group-flush border border-secondary border-opacity-25 rounded-3">
                                                <div class="list-group-item d-flex justify-content-between align-items-center px-2 py-1 bg-transparent"><span class="text-body-secondary" style="font-size:10px;"><i class="bi bi-box me-1"></i>Batch</span><span class="badge" style="font-size:9px;" :class="selectedProductForModal && selectedProductForModal.batch_tracking ? 'bg-success' : 'bg-secondary'" x-text="selectedProductForModal && selectedProductForModal.batch_tracking ? 'ON' : 'OFF'"></span></div>
                                                <div class="list-group-item d-flex justify-content-between align-items-center px-2 py-1 bg-transparent"><span class="text-body-secondary" style="font-size:10px;"><i class="bi bi-calendar-x me-1"></i>Expiry</span><span class="badge" style="font-size:9px;" :class="selectedProductForModal && selectedProductForModal.expiry_tracking ? 'bg-success' : 'bg-secondary'" x-text="selectedProductForModal && selectedProductForModal.expiry_tracking ? 'ON' : 'OFF'"></span></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card h-100 border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-center gap-2 pb-2 mb-2 border-bottom border-secondary border-opacity-25">
                                                <div class="text-bg-info-subtle text-info-emphasis rounded-2 d-flex align-items-center justify-content-center" style="width:24px;height:24px;"><i class="bi bi-list-stars" style="font-size:12px;"></i></div>
                                                <h6 class="mb-0 fw-bold text-uppercase text-body" style="font-size:11px;letter-spacing:1px;">Technical Specs</h6>
                                            </div>
                                            <div x-show="productModalLoading" class="text-center p-3 text-body-secondary"><div class="spinner-border spinner-border-sm mb-1" role="status"></div><div style="font-size:10px;">Loading...</div></div>
                                            <div x-show="!productModalLoading">
                                                <template x-if="selectedProductForModal && selectedProductForModal.attributes && selectedProductForModal.attributes.length > 0">
                                                    <table class="table table-sm table-hover mb-0" style="font-size:11px;">
                                                        <tbody>
                                                            <template x-for="attr in selectedProductForModal.attributes" :key="attr.id">
                                                                <tr><th class="ps-2 text-body-secondary w-50 border-0" x-text="attr.attribute"></th><td class="pe-2 fw-semibold text-end border-0"><div class="d-flex align-items-center justify-content-end gap-1"><span x-show="attr.color_code" class="rounded-circle border" :style="'width:8px;height:8px;background-color:'+attr.color_code"></span><span x-text="attr.value"></span></div></td></tr>
                                                            </template>
                                                        </tbody>
                                                    </table>
                                                </template>
                                                <template x-if="!selectedProductForModal || !selectedProductForModal.attributes || selectedProductForModal.attributes.length === 0">
                                                    <div class="text-center text-body-secondary py-3" style="font-size:10px;">No specifications recorded.</div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Details & Usage -->
                            <div class="card mb-3 border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom border-secondary border-opacity-25">
                                        <div class="text-bg-secondary-subtle text-secondary-emphasis rounded-2 d-flex align-items-center justify-content-center" style="width:24px;height:24px;"><i class="bi bi-file-text-fill" style="font-size:12px;"></i></div>
                                        <h6 class="mb-0 fw-bold text-uppercase text-body" style="font-size:11px;letter-spacing:1px;">Details & Usage</h6>
                                    </div>
                                    <div x-show="productModalLoading" class="text-center p-3 text-body-secondary"><div class="spinner-border spinner-border-sm" role="status"></div></div>
                                    <div x-show="!productModalLoading" class="row g-3">
                                        <div class="col-md-6 border-end border-secondary border-opacity-25">
                                            <label class="form-label mb-1 fw-bold text-body-secondary text-uppercase d-block" style="font-size:9px;">Product Description</label>
                                            <div style="font-size:11px;" x-show="selectedProductForModal && selectedProductForModal.description" x-html="selectedProductForModal ? selectedProductForModal.description : ''"></div>
                                            <div style="font-size:11px;" x-show="!selectedProductForModal || !selectedProductForModal.description" class="text-body-secondary fst-italic">No description available.</div>
                                        </div>
                                        <div class="col-md-6 ps-3">
                                            <label class="form-label mb-1 fw-bold text-body-secondary text-uppercase d-block" style="font-size:9px;">Application / Dosage</label>
                                            <div style="font-size:11px;" x-show="selectedProductForModal && selectedProductForModal.application_instructions" x-html="selectedProductForModal ? selectedProductForModal.application_instructions : ''"></div>
                                            <div style="font-size:11px;" x-show="!selectedProductForModal || !selectedProductForModal.application_instructions" class="text-body-secondary fst-italic">No instructions available.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Promotions -->
                            <div x-show="productModalOffers && productModalOffers.length > 0" class="card mb-3 border border-primary border-opacity-25 shadow-sm rounded-4 bg-primary-subtle">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center gap-2 pb-2 mb-2 border-bottom border-primary border-opacity-25">
                                        <div class="bg-primary text-white rounded-2 d-flex align-items-center justify-content-center" style="width:24px;height:24px;"><i class="bi bi-stars" style="font-size:12px;"></i></div>
                                        <h6 class="mb-0 fw-bold text-uppercase text-primary" style="font-size:11px;letter-spacing:1px;">Available Promotions</h6>
                                    </div>
                                    <div class="row g-2">
                                        <template x-for="offer in productModalOffers" :key="'pmo-'+offer.id">
                                            <div class="col-md-6">
                                                <div class="d-flex align-items-center gap-2 p-2 rounded-3 border border-primary border-opacity-10 bg-body">
                                                    <div class="text-bg-primary-subtle text-primary-emphasis rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:28px;height:28px;"><i class="bi bi-tag-fill" style="font-size:12px;"></i></div>
                                                    <div class="min-w-0">
                                                        <div class="fw-bold text-body-emphasis text-truncate" style="font-size:11px;" x-text="offer.name"></div>
                                                        <div class="text-body-secondary text-truncate" style="font-size:9px;" x-text="offer.type === 'percentage' ? offer.value + '% off' : 'Flat ₹ ' + offer.value + ' off'"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <!-- end col-md-8 -->
                    </div>
                    <!-- end row -->
                </div>
                <!-- end modal-body -->

                <!-- Footer -->
                <div class="modal-footer bg-body-tertiary border-top p-3 d-flex justify-content-between align-items-center">
                    <div class="text-body-secondary small">
                        <span x-show="selectedProductForModal && isInCart(selectedProductForModal.id)" class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i>Currently in cart</span>
                    </div>
                    <div>
                        <button type="button" class="btn btn-outline-secondary me-2 fw-medium" data-bs-dismiss="modal">Close</button>
                        <button x-show="selectedProductForModal" type="button" class="btn btn-primary fw-bold px-4" @click="selectedProductForModal && addToCart(selectedProductForModal); bootstrap.Modal.getInstance(document.getElementById('productDetailsModal')).hide();">
                            <i class="bi" :class="selectedProductForModal && isInCart(selectedProductForModal.id) ? 'bi-cart-plus-fill' : 'bi-cart-plus'"></i>
                            <span x-text="selectedProductForModal && isInCart(selectedProductForModal.id) ? 'Add More' : 'Add to Cart'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════ Cancel Order Modal ═══════════════════════════ -->
    <div class="modal fade" id="cancelOrderModal" aria-labelledby="cancelOrderModalLabel">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold" id="cancelOrderModalLabel">
                        <i class="bi bi-x-circle me-2 text-danger"></i>Cancel Order <span class="text-danger" x-text="cancelModalOrder?.orderNumber"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="alert alert-danger bg-danger bg-opacity-10 border-danger border-opacity-25 shadow-sm rounded-3">
                        <div class="d-flex align-items-center mb-1">
                            <i class="bi bi-exclamation-triangle-fill fs-5 me-2 text-danger"></i>
                            <h6 class="fw-bold text-danger mb-0">Warning</h6>
                        </div>
                        <div class="small ms-4 ps-1 text-danger-emphasis">
                            Are you sure you want to cancel this order? This action will release inventory.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Reason for Cancellation <span class="text-danger">*</span></label>
                        <select x-select class="form-select" x-model="cancelReason">
                            <option value="" disabled selected>Select a reason...</option>
                            @foreach($cancelReasons ?? [] as $reason)
                                <option value="{{ $reason->reason }}">{{ $reason->reason }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Internal Notes (Optional)</label>
                        <textarea class="form-control" rows="2" x-model="cancelNotes" placeholder="Any additional notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-danger" @click="submitCancelOrder()">Confirm Cancel</button>
                </div>
            </div>
        </div>
    </div>

    </div>

<style>
    /* Hide number input spin buttons */
    .no-spinners::-webkit-inner-spin-button,
    .no-spinners::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    .no-spinners {
        -moz-appearance: textfield;
    }

    /* Product Details Modal — two-column layout */
    #productDetailsModal .modal-body > .row {
        display: flex;
        flex-wrap: nowrap;
        align-items: flex-start;
    }
    #productDetailsModal .modal-body > .row > .col-md-4 {
        min-width: 300px;
        max-width: 300px;
    }
    #productDetailsModal .modal-body > .row > .col-md-8 {
        flex: 1;
        min-width: 0;
    }
</style>

@push('scripts')
@php
    $offersArray = $activeOffers->map(fn($o) => [
        'id' => $o->id,
        'name' => $o->name,
        'type' => $o->type,
        'discount_type' => $o->discount_type,
        'value' => (float)$o->value,
        'min_spend' => (float)$o->min_spend,
        'max_discount' => $o->max_discount ? (float)$o->max_discount : null,
        'product_id' => $o->product_id,
        'buy_qty' => (int)$o->buy_qty,
        'get_qty' => (int)$o->get_qty,
        'ends_at' => $o->ends_at,
        'priority' => (int)$o->priority,
        'product_name' => $o->product ? $o->product->name : 'Any Product',
        'applicable_categories' => $o->applicable_categories,
        'excluded_categories' => $o->excluded_categories,
        'applicable_products' => $o->applicable_products,
        'excluded_products' => $o->excluded_products,

    ])->values()->all();
@endphp
<script>
function createOrderApp(initialCustomer = null, initialOrder = null) {
    return {
        activeTab: 'customer',
        viewMode: 'table',
        showCustomerWorkspace: true,
        isCartSidebarOpen: false, useWalletBalance: false,
        partyId: new URLSearchParams(window.location.search).get('customer_id') || '', defaultWarehouseId: '{{ $warehouses->where("is_default", true)->first()->id ?? ($warehouses->first()->id ?? "") }}', warehouseId: '{{ $warehouses->where("is_default", true)->first()->id ?? ($warehouses->first()->id ?? "") }}', previousWarehouseId: '{{ $warehouses->where("is_default", true)->first()->id ?? ($warehouses->first()->id ?? "") }}', shippingAddressId: '', billingAddressId: '', sameAsShipping: true, orderType: 'sale', shippingFee: 0,
        orderDate: (() => { const d = new Date(); const o = d.getTimezoneOffset() * 60000; return new Date(d - o).toISOString().slice(0, 19).replace('T', ' '); })(),
        orderStatus: 'pending', futureOrderDate: '',
        editingOrderId: null,
        editingOrderNo: null,
        addresses: [],
        recentOrders: [],
        products: [], productQuery: '', stockFilter: 'available', categoryFilter: '', perPage: 10,
        searching: false, productPage: 1, productLastPage: 1, productTotal: 0, productFrom: 0, productTo: 0,
        cart: [], couponCode: '', couponApplied: false, appliedCouponObj: null, appliedOfferId: null,
        placing: false, formErrors: [],
        warehouses: @json($warehouses->map(fn($w) => ['id' => $w->id, 'name' => $w->name])),
        activeOffers: @json($offersArray),
        activeCoupons: @json($activeCoupons),
        couponInputTemp: '',

        selectedProductForModal: null,
        productModalOffers: [],
        productModalLoading: false,
        productModalTab: 'overview',

        customerDetails: initialCustomer || window.__INITIAL_ORDER_CUSTOMER__ || null,
        bottomTab: 'history',
        
        expandedOrderId: null,
        
        isConfirmMode: new URLSearchParams(window.location.search).get('step') === 'confirm',
        confirmAction: 'now',
        scheduleReason: '',
        scheduledConfirmDate: '',
        confirmNotes: '',
        rescheduleReasons: @json($rescheduleReasons ?? []),
        originalOrder: window.__INITIAL_ORDER_TO_EDIT__ || null,
        
        isCallLoggedOrClosed: false,

        cancelModalOrder: null,
        cancelReason: '',
        cancelNotes: '',
        cancelReasons: @json($cancelReasons ?? []),

        async init() {
            // Intercept browser refresh/close tab
            window.addEventListener('beforeunload', (e) => {
                if (this.customerDetails && !this.isCallLoggedOrClosed) {
                    e.preventDefault();
                    e.returnValue = 'You must Log a Call before leaving this profile.';
                    return e.returnValue;
                }
            });

            // Push initial state to trap back button
            const trapBack = () => {
                if (window.history.state !== 'trap') {
                    window.history.pushState('trap', null, '');
                }
            };
            trapBack();
            
            // Re-arm the trap on first user interaction to bypass modern browser anti-hijack policies after a refresh
            document.addEventListener('click', trapBack, { once: true });
            document.addEventListener('keydown', trapBack, { once: true });

            window.addEventListener('popstate', (e) => {
                if (this.customerDetails && !this.isCallLoggedOrClosed) {
                    if (e.state !== 'trap') {
                        window.history.pushState('trap', null, '');
                        const blockedModal = window.bootstrap.Modal.getOrCreateInstance(document.getElementById('actionBlockedModal'));
                        blockedModal.show();
                        window.dispatchEvent(new CustomEvent('notify', { detail: { type: 'danger', message: 'You must Log a Call before leaving this profile.' }}));
                    }
                }
            });

            // Intercept keyboard refresh shortcuts (F5, Ctrl+R, Cmd+R) to show custom modal
            window.addEventListener('keydown', (e) => {
                if (this.customerDetails && !this.isCallLoggedOrClosed) {
                    if (e.key === 'F5' || (e.ctrlKey && e.key.toLowerCase() === 'r') || (e.metaKey && e.key.toLowerCase() === 'r')) {
                        e.preventDefault();
                        const blockedModal = window.bootstrap.Modal.getOrCreateInstance(document.getElementById('actionBlockedModal'));
                        blockedModal.show();
                    }
                }
            });

            // Actively block link clicks across the entire page (including header)
            document.addEventListener('click', (e) => {
                if (this.customerDetails && !this.isCallLoggedOrClosed) {
                    const link = e.target.closest('a');
                    if (link && link.href && !link.href.startsWith('javascript') && !link.getAttribute('href').startsWith('#') && link.target !== '_blank' && !link.hasAttribute('data-bypass')) {
                        e.preventDefault();
                        e.stopPropagation();
                        const blockedModal = new window.bootstrap.Modal(document.getElementById('actionBlockedModal'));
                        blockedModal.show();
                    }
                }
            }, true);
            
            this.searchProducts();
            if (this.customerDetails) {
                this.addresses = this.customerDetails.addresses || [];
                this.recentOrders = this.customerDetails.orders || [];
                if (this.addresses.length) {
                    this.shippingAddressId = this.addresses.find(a => a.is_default)?.id || this.addresses[0].id;
                    this.billingAddressId = this.shippingAddressId;
                }
            }
            if (this.partyId && !this.customerDetails) {
                await this.loadAddresses();
            }

            if (initialOrder) {
                this.applyOrderForEdit(initialOrder);
                localStorage.removeItem('ecommerce_create_order_cart');
                this.isCartSidebarOpen = true;
            } else {
                const saved = localStorage.getItem('ecommerce_create_order_cart');
                if (saved) {
                    try {
                        this.cart = JSON.parse(saved);
                    } catch (e) {}
                }
            }

            this.$watch('cart', async (v) => {
                localStorage.setItem('ecommerce_create_order_cart', JSON.stringify(v));
                window.dispatchEvent(new CustomEvent('cart-updated'));
                if (v.length === 0) {
                    this.removeCoupon();
                    this.appliedOfferId = null;
                    this.isCartSidebarOpen = false;
                } else {
                    if (this.couponApplied) {
                        this.applyCoupon();
                    }
                    if (this.appliedOfferId && this.appliedOfferId !== 'none' && !this.availableOrderOffers.some(o => o.id === this.appliedOfferId)) {
                        this.appliedOfferId = null;
                    }
                    if (!this.appliedOfferId && this.availableOrderOffers.length > 0) {
                        this.appliedOfferId = this.availableOrderOffers[0].id;
                    }
                }
                await this.evaluateFreeProducts();
            });

            this.$watch('shippingAddressId', (newVal) => {
                if (newVal) {
                    const selectedAddress = this.addresses.find(a => String(a.id) === String(newVal));
                    if (selectedAddress && selectedAddress.state) {
                        const whSelect = document.querySelector('select[x-model="warehouseId"]');
                        if (whSelect) {
                            const options = Array.from(whSelect.options);
                            const matchingOption = options.find(opt => opt.getAttribute('data-state') === selectedAddress.state);
                            if (matchingOption) {
                                this.warehouseId = matchingOption.value;
                                setTimeout(() => { this.searchProducts(true); }, 50);
                            }
                        }
                    }
                }
            });

            this.$watch('grandTotal', v => {
                localStorage.setItem('ecommerce_create_order_cart_total', v);
                window.dispatchEvent(new CustomEvent('cart-total-updated', { detail: v }));
            });
            localStorage.setItem('ecommerce_create_order_cart_total', this.grandTotal);

            // Listen for notify events (e.g. address saved, profile updated)
            window.addEventListener('notify', (e) => {
                const { type = 'success', message = '' } = e.detail || {};
                const container = document.getElementById('toast-container');
                if (!container || !message) return;
                const iconMap = {
                    success: 'bi-check-circle-fill',
                    error:   'bi-x-circle-fill',
                    danger:  'bi-x-circle-fill',
                    warning: 'bi-exclamation-triangle-fill',
                    info:    'bi-info-circle-fill',
                };
                const bsType = type === 'error' ? 'danger' : type;
                const id = 'toast-' + Date.now();
                const el = document.createElement('div');
                el.id = id;
                el.className = `toast align-items-center text-bg-${bsType} border-0 show mb-2`;
                el.setAttribute('role', 'alert');
                el.setAttribute('aria-live', 'assertive');
                el.setAttribute('aria-atomic', 'true');
                el.innerHTML = `<div class="d-flex">
                    <div class="toast-body fw-semibold d-flex align-items-center gap-2">
                        <i class="bi ${iconMap[type] || iconMap.info} flex-shrink-0"></i>
                        <span>${message}</span>
                    </div>
                    <button type="button" class="btn-close btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>`;
                container.appendChild(el);
                setTimeout(() => { el.classList.remove('show'); setTimeout(() => el.remove(), 400); }, 4000);
            });

            // Listen to cart changes from header dropdown or other components
            window.addEventListener('cart-updated', () => {
                const updated = localStorage.getItem('ecommerce_create_order_cart');
                if (updated) {
                    try {
                        const parsed = JSON.parse(updated);
                        if (JSON.stringify(this.cart) !== JSON.stringify(parsed)) {
                            this.cart = parsed;
                        }
                    } catch (e) {}
                } else if (this.cart.length > 0) {
                    this.cart = [];
                }
            });
        },
        
        getAppliedItemOffers(order) {
            let names = [];
            if (!order || !order.items || !this.activeOffers) return names;
            order.items.forEach(item => {
                if (Number(item.discount_amount || 0) > 0) {
                    let match = this.activeOffers.find(o => o.type === 'bogo' && (
                        (o.applicable_products && (o.applicable_products.includes(item.product_id) || o.applicable_products.includes(String(item.product_id)))) ||
                        (o.applicable_categories && (o.applicable_categories.includes(item.product?.category_id) || o.applicable_categories.includes(String(item.product?.category_id))))
                    ));
                    if (match && !names.includes(match.name)) names.push(match.name);
                }
                if (Number(item.unit_price || 0) === 0 || Number(item.total_amount || 0) === 0) {
                    let match = this.activeOffers.find(o => o.type === 'free_product' && Number(o.product_id) === Number(item.product_id));
                    if (match && !names.includes(match.name)) names.push(match.name);
                }
            });
            return names;
        },

        getSingleItemOffer(item) {
            if (!this.activeOffers) return null;
            if (Number(item.discount_amount || 0) > 0) {
                let match = this.activeOffers.find(o => o.type === 'bogo' && (
                    (o.applicable_products && (o.applicable_products.includes(item.product_id) || o.applicable_products.includes(String(item.product_id)))) ||
                    (o.applicable_categories && (o.applicable_categories.includes(item.product?.category_id) || o.applicable_categories.includes(String(item.product?.category_id))))
                ));
                if (match) return match.name;
            }
            if (Number(item.unit_price || 0) === 0 || Number(item.total_amount || 0) === 0) {
                let match = this.activeOffers.find(o => o.type === 'free_product' && Number(o.product_id) === Number(item.product_id));
                if (match) return match.name;
            }
            return null;
        },

        getBogoMatch(productId) {
            const bogos = this.activeOffers
                .filter(o => o.type === 'bogo')
                .sort((a,b)=>(b.priority - a.priority) || (a.id - b.id));
            
            const p = this.products.find(x => String(x.id) === String(productId)) || this.cart.find(x => String(x.id) === String(productId));
            const cid = p ? String(p.category_id) : null;
            return bogos.find(o => {
                let apps = o.applicable_products;
                if (typeof apps === 'string') {
                    try { apps = JSON.parse(apps); } catch(e) { apps = null; }
                }
                let cats = o.applicable_categories;
                if (typeof cats === 'string') {
                    try { cats = JSON.parse(cats); } catch(e) { cats = null; }
                }
                
                if ((!apps || apps.length === 0) && (!cats || cats.length === 0)) return true;
                
                if (apps && apps.length > 0 && (apps.includes(productId) || apps.includes(String(productId)))) return true;
                if (cats && cats.length > 0 && cid && (cats.includes(cid) || cats.includes(String(cid)))) return true;
                
                return false;
            });
        },

        getProductPromotions(p) {
            let promos = [];
            this.activeOffers.forEach(o => {
                if (o.type === 'bogo') {
                    let apps = o.applicable_products;
                    if (typeof apps === 'string') { try { apps = JSON.parse(apps); } catch(e) { apps = null; } }
                    let cats = o.applicable_categories;
                    if (typeof cats === 'string') { try { cats = JSON.parse(cats); } catch(e) { cats = null; } }
                    
                    let match = false;
                    if ((!apps || apps.length === 0) && (!cats || cats.length === 0)) match = true;
                    if (apps && apps.length > 0 && (apps.includes(p.id) || apps.includes(String(p.id)))) match = true;
                    if (cats && cats.length > 0 && (cats.includes(p.category_id) || cats.includes(String(p.category_id)))) match = true;
                    
                    if (match) {
                        promos.push({
                            title: o.name, 
                            icon: 'bi-gift-fill', 
                            color: 'info', 
                            tooltip: `Buy ${o.buy_qty} Get ${o.get_qty} Free.<br>Min Spend: ₹${o.min_spend || 0}`
                        });
                    }
                }
                if (o.type === 'free_product' && String(o.product_id) === String(p.id)) {
                    promos.push({
                        title: o.name, 
                        icon: 'bi-gift', 
                        color: 'success', 
                        tooltip: `Get ${o.get_qty} Free.<br>Min Spend: ₹${o.min_spend || 0}`
                    });
                }
                if (o.type === 'category_discount' && o.applicable_categories && o.applicable_categories.includes(String(p.category_id))) {
                    promos.push({
                        title: o.name, 
                        icon: 'bi-tags', 
                        color: 'primary', 
                        tooltip: `Category Discount: ${o.discount_type === 'percentage' ? o.value+'%' : '₹'+o.value} OFF.<br>Min Spend: ₹${o.min_spend || 0}`
                    });
                }
            });
            this.activeCoupons.forEach(c => {
                if (c.type === 'free_product' && String(c.product_id) === String(p.id)) {
                    promos.push({
                        title: `Coupon: ${c.code}`, 
                        icon: 'bi-ticket-perforated', 
                        color: 'warning', 
                        tooltip: `Free Gift with Coupon.<br>Min Spend: ₹${c.min_spend || 0}`
                    });
                }
            });
            return promos;
        },

        async loadAddresses() {
            if (!this.partyId) { this.addresses = []; this.recentOrders = []; this.customerDetails = null; return; }
            try {
                const res = await fetch(`/customers/${this.partyId}`, { headers: {'Accept':'application/json','X-Requested-With':'XMLHttpRequest'} });
                const json = await res.json();
                this.customerDetails = json.data;
                this.addresses = json.data?.addresses || [];
                this.recentOrders = json.data?.orders || [];
                if (this.addresses.length) {
                    this.shippingAddressId = this.addresses.find(a=>a.is_default)?.id || this.addresses[0].id;
                    this.billingAddressId = this.shippingAddressId;
                    
                    if (!this.warehouseId || String(this.warehouseId) === String(this.defaultWarehouseId)) {
                        const defaultAddress = this.addresses.find(a => String(a.id) === String(this.shippingAddressId));
                        if (defaultAddress && defaultAddress.state) {
                            const whSelect = document.querySelector('select[x-model="warehouseId"]');
                            if (whSelect) {
                                const options = Array.from(whSelect.options);
                                const matchingOption = options.find(opt => opt.getAttribute('data-state') === defaultAddress.state);
                                if (matchingOption) {
                                    this.warehouseId = matchingOption.value;
                                    setTimeout(() => { this.searchProducts(true); }, 50);
                                }
                            }
                        }
                    }
                }
            } catch(e) { console.error(e); }
        },

        async openProductModal(p) {
            this.selectedProductForModal = p;
            this.productModalTab = 'overview';
            this.productModalOffers = this.activeOffers.filter(o => o.product_id === p.id || o.product_id === null);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('productDetailsModal')).show();
            
            this.productModalLoading = true;
            try {
                const res = await fetch(`/api/products/${p.id}`, { headers: {'Accept':'application/json'} });
                const json = await res.json();
                if (json && json.data) {
                    this.selectedProductForModal = { ...p, ...json.data };
                }
            } catch(e) { console.error(e); }
            this.productModalLoading = false;
        },

        applyOrderForEdit(order) {
            if (!order) return;

            this.editingOrderId = order.id || null;
            this.editingOrderNo = order.order_no || order.orderNumber || null;
            this.partyId = order.party_id || this.partyId;
            this.orderType = order.type || this.orderType;
            this.warehouseId = order.warehouse_id || this.warehouseId;
            this.shippingAddressId = order.shipping_address_id || '';
            this.billingAddressId = order.billing_address_id || order.shipping_address_id || '';
            this.sameAsShipping = !order.billing_address_id || String(order.billing_address_id) === String(order.shipping_address_id);
            this.orderDate = order.order_date ? String(order.order_date).replace('T', ' ').substring(0, 19) : this.orderDate;
            this.orderStatus = order.status;
            this.futureOrderDate = order.future_order_date ? String(order.future_order_date).substring(0, 10) : '';
            this.couponCode = order.coupon_code || '';
            this.appliedOfferId = order.applied_offer_id || null;
            this.customerDetails = order.party
                ? {
                    ...(this.customerDetails || {}),
                    ...order.party,
                    addresses: order.party.addresses || this.customerDetails?.addresses || [],
                }
                : this.customerDetails;
            this.addresses = order.party?.addresses || this.addresses;
            this.recentOrders = order.party?.orders || this.recentOrders;

            this.cart = (order.items || []).map(item => {
                const quantity = Number(item.quantity || 1);
                const discountValue = quantity > 0
                    ? Number(item.discount_amount || item.discountValue || 0) / quantity
                    : 0;
                const price = Number(item.unit_price ?? item.price ?? 0);
                const is_gift = price > 0 && Math.abs(price - discountValue) < 0.01;

                return {
                    id: item.product_id || item.id,
                    name: item.product?.name || item.product_name || 'Product',
                    sku: item.product?.sku || item.sku || '',
                    price: price,
                    image_url: item.product?.image_url || (item.product?.image_path ? `/storage/${item.product.image_path}` : null),
                    quantity: quantity,
                    available: item.product?.available_stock ?? item.available ?? null,
                    taxRate: Number(item.tax_rate > 0 ? item.tax_rate : (item.taxRate || item.product?.tax_rate?.rate || item.product?.taxRate?.rate || 0)),
                    discountValue: discountValue,
                    discountType: 'flat',
                    category_id: item.product?.category_id || null,
                    is_gift: is_gift,
                    gift_source: is_gift ? 'legacy_gift' : null
                };
            });

            if (this.cart.length > 0) {
                localStorage.setItem('ecommerce_create_order_cart', JSON.stringify(this.cart));
            }
        },

        scrollToSection(sectionId) {
            const el = document.getElementById(sectionId);
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        },

        get customerDisplayName() {
            if (!this.customerDetails) {
                return this.partyId ? `Customer #${this.partyId}` : 'Selected customer';
            }
            const parts = [
                this.customerDetails.firstname || '',
                this.customerDetails.middlename || '',
                this.customerDetails.lastname || '',
            ].filter(Boolean);
            return parts.join(' ').replace(/\s+/g, ' ').trim() || this.customerDetails.company_name || this.customerDetails.name || `Customer #${this.partyId || ''}`;
        },

        get selectedWarehouseName() {
            const match = (this.warehouses || []).find(w => String(w.id) === String(this.warehouseId));
            return match ? match.name : 'Select warehouse';
        },

        getTotalWarehouseStock(p) {
            if (!p) return 0;
            if (!this.warehouseId || !p.warehouse_stocks || p.warehouse_stocks.length === 0) {
                return parseFloat(p.stock_qty || 0) - parseFloat(p.reserved_qty || 0) - parseFloat(p.pending_qty || 0);
            }
            const match = p.warehouse_stocks.find(w => String(w.warehouse_id) === String(this.warehouseId));
            return match ? parseFloat(match.available || 0) : 0;
        },

        getWarehouseStock(p) {
            if (!p) return 0;
            const totalStock = this.getTotalWarehouseStock(p);
            const cartQty = this.cart.filter(i => String(i.id) === String(p.id)).reduce((sum, item) => sum + item.quantity, 0);
            return Math.max(0, totalStock - cartQty);
        },

        async handleWarehouseChange(event) {
            let notifyWarning = false;
            let removedCount = 0;
            
            await this.searchProducts(true);

            this.cart = this.cart.map(item => {
                if (item.is_gift) return item;
                
                let p = item._product;
                if (!p) {
                    p = this.products.find(prod => String(prod.id) === String(item.id));
                }
                
                if (!p) return item;

                if (!this.isSkuEnabled(p)) {
                    item.quantity = 0;
                    return item;
                }
                
                const maxAllowed = this.getMaxAllowedStock(p);
                item.available = maxAllowed;
                
                if (item.quantity > maxAllowed) {
                    notifyWarning = true;
                    item.quantity = maxAllowed;
                }
                
                return item;
            }).filter(item => {
                if (item.quantity > 0) return true;
                if (!item.is_gift) removedCount++;
                return false;
            });
            
            if (notifyWarning) {
                window.dispatchEvent(new CustomEvent('notify',{detail:{type:'warning',message:'Cart quantities adjusted based on warehouse stock.'}}));
            }
            if (removedCount > 0) {
                window.dispatchEvent(new CustomEvent('notify',{detail:{type:'warning',message:removedCount + ' item(s) removed due to being disabled or out of stock.'}}));
            }
        },

        getOversellStock(p) {
            if (!p) return 0;
            let allow = p.allow_overselling;
            let limit = parseInt(p.overselling_qty) || 999;
            
            if (this.warehouseId && p.warehouse_stocks) {
                const match = p.warehouse_stocks.find(w => String(w.warehouse_id) === String(this.warehouseId));
                if (match && match.allow_overselling !== null) {
                    allow = match.allow_overselling;
                    limit = parseInt(match.overselling_qty) || 999;
                }
            }
            
            if (!allow) return 0;
            
            const totalStock = this.getTotalWarehouseStock(p);
            const cartQty = this.cart.filter(i => String(i.id) === String(p.id)).reduce((sum, item) => sum + item.quantity, 0);
            const usedOversell = Math.max(0, cartQty - totalStock);
            return Math.max(0, limit - usedOversell);
        },

        getMaxAllowedStock(p) {
            if (!p) return 0;
            const totalStock = this.getTotalWarehouseStock(p);
            
            let allow = p.allow_overselling;
            let limit = parseInt(p.overselling_qty) || 999;
            
            if (this.warehouseId && p.warehouse_stocks) {
                const match = p.warehouse_stocks.find(w => String(w.warehouse_id) === String(this.warehouseId));
                if (match && match.allow_overselling !== null) {
                    allow = match.allow_overselling;
                    limit = parseInt(match.overselling_qty) || 999;
                }
            }
            
            if (allow) {
                return totalStock + limit;
            }
            return totalStock;
        },

        isSkuEnabled(p) {
            if (!p) return false;
            if (this.warehouseId && p.warehouse_stocks) {
                const match = p.warehouse_stocks.find(w => String(w.warehouse_id) === String(this.warehouseId));
                if (match && match.is_sku_enabled !== null && match.is_sku_enabled !== undefined) {
                    return match.is_sku_enabled;
                }
            }
            return Boolean(p.is_sku_enabled);
        },

        canAddToCart(p) {
            if (!p || !this.isSkuEnabled(p)) return false;
            const cartQty = this.cart.filter(i => String(i.id) === String(p.id)).reduce((sum, item) => sum + item.quantity, 0);
            return cartQty < this.getMaxAllowedStock(p);
        },

        get futureOrders() {
            return (this.recentOrders || []).filter(order => order.lifecycle_status === 'future_order' || order.status_label === 'Future Order' || order.status === 'future_order');
        },

        get historyOrders() {
            return (this.recentOrders || []).filter(order => !(order.lifecycle_status === 'future_order' || order.status_label === 'Future Order' || order.status === 'future_order'));
        },

        get customerTags() {
            const tags = this.customerDetails?.tags;
            if (Array.isArray(tags)) return tags.filter(Boolean);
            if (typeof tags === 'string' && tags.trim()) {
                return tags.split(',').map(tag => tag.trim()).filter(Boolean);
            }
            return [];
        },

        toggleOrderDetails(orderId) {
            this.expandedOrderId = this.expandedOrderId === orderId ? null : orderId;
        },

        addressSummary(address) {
            if (!address) return 'Select address';
            const parts = [
                address.label,
                address.address_line_1,
                address.address_line_2,
                address.city,
                address.state,
                address.pincode,
            ].filter(Boolean);
            return parts.join(', ');
        },

        availableServices(address) {
            const today = new Date().toISOString().slice(0, 10);

            return (address?.village?.services || []).filter(service => {
                const pivot = service.pivot || {};
                const isActive = service.is_active !== false && service.is_active !== 0;
                const isAvailable = pivot.is_available === true || pivot.is_available === 1 || pivot.is_available === '1';
                const hasStarted = !pivot.serviceable_from_date || pivot.serviceable_from_date <= today;
                const hasNotEnded = !pivot.serviceable_to_date || pivot.serviceable_to_date >= today;

                return isActive && isAvailable && hasStarted && hasNotEnded;
            }).sort((a, b) => {
                const priorityA = Number(a.pivot?.priority ?? 0);
                const priorityB = Number(b.pivot?.priority ?? 0);
                return priorityA - priorityB || String(a.name).localeCompare(String(b.name));
            });
        },

        get shippingAddressSummary() {
            return this.addressSummary(this.addresses.find(a => String(a.id) === String(this.shippingAddressId)));
        },

        get billingAddressSummary() {
            if (this.sameAsShipping) return 'Same as shipping';
            return this.addressSummary(this.addresses.find(a => String(a.id) === String(this.billingAddressId)));
        },

        
        get filteredProducts() {
            if (!this.products) return [];
            return this.products.filter(p => {
                if (!this.isSkuEnabled(p)) return false;
                const maxStock = this.getMaxAllowedStock(p);
                if (this.stockFilter === 'available' && maxStock <= 0) return false;
                if (this.stockFilter === 'out_of_stock' && maxStock > 0) return false;
                return true;
            });
        },

        async searchProducts(reset = false) {
            if (reset) this.productPage = 1;
            this.searching = true;
            try {
                const p = new URLSearchParams({ q: this.productQuery, category: this.categoryFilter, perPage: this.perPage, page: this.productPage });
                const res = await fetch(`/products-search-api?${p}`, { headers: {'Accept':'application/json','X-Requested-With':'XMLHttpRequest'} });
                const json = await res.json();
                this.products = (json.data || []).map(p => ({...p, _qty: 1, _disc: parseFloat(p.default_discount)||0}));
                this.productTotal = json.total||0; this.productFrom = json.from||0; this.productTo = json.to||0; this.productLastPage = json.last_page||1;
            } catch(e) { window.dispatchEvent(new CustomEvent('notify',{detail:{type:'error',message:'Failed to load products'}})); }
            finally { this.searching = false; }
        },

        async evaluateFreeProducts() {
            let expectedGifts = [];
            
            // Only evaluate free products if there are actual items in the cart
            if (this.cart.some(item => !item.is_gift)) {
                const fpOffers = this.activeOffers.filter(o => o.type === 'free_product' && o.product_id);
                fpOffers.forEach(o => {
                    if (this.subtotal >= (parseFloat(o.min_spend)||0)) {
                        const apps = typeof o.applicable_products === 'string' ? JSON.parse(o.applicable_products) : o.applicable_products;
                        const cats = typeof o.applicable_categories === 'string' ? JSON.parse(o.applicable_categories) : o.applicable_categories;
                        
                        let triggerQty = 0;
                        if ((apps && apps.length > 0) || (cats && cats.length > 0)) {
                            this.cart.forEach(item => {
                                if (item.is_gift) return;
                                if (apps && apps.length > 0 && (apps.includes(item.id) || apps.includes(String(item.id)))) {
                                    triggerQty += parseInt(item.quantity) || 0;
                                } else if (cats && cats.length > 0 && (cats.includes(item.category_id) || cats.includes(String(item.category_id)))) {
                                    triggerQty += parseInt(item.quantity) || 0;
                                }
                            });
                            
                            if (triggerQty > 0) {
                                const buyQty = parseInt(o.buy_qty) || 1;
                                const cycles = Math.floor(triggerQty / buyQty);
                                if (cycles > 0) {
                                    expectedGifts.push({ product_id: o.product_id, qty: cycles * (parseInt(o.get_qty) || 1), source: 'offer_' + o.id });
                                }
                            }
                        } else {
                            expectedGifts.push({ product_id: o.product_id, qty: parseInt(o.get_qty)||1, source: 'offer_' + o.id });
                        }
                    }
                });
                if (this.couponApplied && this.appliedCouponObj && this.appliedCouponObj.type === 'free_product' && this.appliedCouponObj.free_product_id) {
                    if (this.subtotal >= (parseFloat(this.appliedCouponObj.min_spend)||0)) {
                        expectedGifts.push({ product_id: this.appliedCouponObj.free_product_id, qty: parseInt(this.appliedCouponObj.free_qty)||1, source: 'coupon_' + this.appliedCouponObj.code });
                    }
                }
            }
            const validSources = expectedGifts.map(g => g.source);
            let cleanedCart = this.cart.filter(item => !item.is_gift || validSources.includes(item.gift_source));
            for (const gift of expectedGifts) {
                const existing = cleanedCart.find(i => i.is_gift && i.gift_source === gift.source);
                if (existing) {
                    if (existing.quantity !== gift.qty) existing.quantity = gift.qty;
                } else {
                    const productObj = this.products.find(p => p.id === gift.product_id) || await this.fetchProductDetails(gift.product_id);
                    if (productObj) {
                        cleanedCart.push({
                            id: productObj.id, name: productObj.name, sku: productObj.sku, price: productObj.selling_price, image_url: productObj.image_url,
                            quantity: gift.qty, available: this.getWarehouseStock(productObj), taxRate: 0, discountValue: productObj.selling_price, discountType: 'amount', category_id: productObj.category_id, is_gift: true, gift_source: gift.source
                        });
                        window.dispatchEvent(new CustomEvent('notify', { detail: { type: 'success', message: `Free Gift added: ${productObj.name}` }}));
                    }
                }
            }
            if (JSON.stringify(cleanedCart) !== JSON.stringify(this.cart)) {
                this.cart = cleanedCart;
            }
        },
        async fetchProductDetails(id) {
            try {
                const res = await fetch(`/api/products/${id}`, { headers: {'Accept':'application/json'} });
                const json = await res.json();
                return json.data;
            } catch(e) { return null; }
        },
        isInCart(id) { return this.cart.some(i => i.id === id); },

        calculateAutoBogoQty(id, newQty, delta) {
            const match = this.getBogoMatch(id);
            if (!match) return newQty;
            
            const buyQty = parseInt(match.buy_qty)||1;
            const getQty = parseInt(match.get_qty)||1;
            const cycle = buyQty + getQty;
            
            if (delta > 0) {
                let completeCycles = Math.floor(newQty / cycle);
                let remainder = newQty % cycle;
                if (remainder >= buyQty) {
                    return (completeCycles * cycle) + buyQty + getQty;
                }
            } else if (delta < 0) {
                let completeCycles = Math.floor(newQty / cycle);
                let remainder = newQty % cycle;
                if (remainder >= buyQty) {
                    return (completeCycles * cycle) + buyQty - 1;
                }
            }
            return newQty;
        },

        addToCart(p) {
            let qtyToAdd = parseInt(p._qty)||1;
            const disc = parseFloat(p._disc)||0;
            if (qtyToAdd <= 0) return;

            const existing = this.cart.findIndex(i => String(i.id) === String(p.id) && !i.is_gift);
            const maxAllowed = this.getMaxAllowedStock(p);
            
            let newQty;
            if (existing >= 0) {
                newQty = this.cart[existing].quantity + qtyToAdd;
                newQty = this.calculateAutoBogoQty(p.id, newQty, qtyToAdd);
                if (maxAllowed !== null && maxAllowed !== undefined && newQty > maxAllowed) {
                    window.dispatchEvent(new CustomEvent('notify',{detail:{type:'warning',message:'Cannot exceed available stock ('+maxAllowed+')'}}));
                    return;
                }
                this.cart[existing].quantity = newQty;
            } else {
                newQty = qtyToAdd;
                newQty = this.calculateAutoBogoQty(p.id, newQty, qtyToAdd);
                if (maxAllowed !== null && maxAllowed !== undefined && newQty > maxAllowed) {
                    window.dispatchEvent(new CustomEvent('notify',{detail:{type:'warning',message:'Cannot exceed available stock ('+maxAllowed+')'}}));
                    return;
                }
                this.cart.push({ id:p.id, _product: p, name:p.name, sku:p.sku, price:p.selling_price, image_url:p.image_url, quantity:newQty, available:maxAllowed, taxRate:parseFloat(p.tax_rate)||0, discountValue:disc, discountType:p.default_discount_type||'percent', category_id:p.category_id, batch_number:'' });
            }
            window.dispatchEvent(new CustomEvent('notify',{detail:{type:'success',message:`Added ${qtyToAdd} item(s) of ${p.name} to cart`}}));
        },

        updateQty(idx, delta) {
            const item = this.cart[idx];
            if (!item) return;
            
            let newQty = item.quantity + delta;

            if (newQty <= 0) {
                this.cart.splice(idx,1);
            } else {
                newQty = this.calculateAutoBogoQty(item.id, newQty, delta);
                if (item.available !== null && item.available !== undefined && newQty > item.available) {
                    window.dispatchEvent(new CustomEvent('notify',{detail:{type:'warning',message:'Cannot exceed available stock ('+item.available+')'}}));
                    return;
                }
                item.quantity = newQty;
            }
        },

        lineTotal(item) {
            const base = (parseFloat(item.price)||0) * (parseInt(item.quantity)||0);
            const disc = parseFloat(item.discountValue)||0;
            if (disc <= 0) return base;
            const d = ['flat','amount','fixed'].includes((item.discountType||'').toLowerCase())
                ? Math.min(disc * item.quantity, base)
                : Math.min(base * (disc/100), base);
            return Math.max(0, base - d);
        },

        get subtotal() { return this.cart.reduce((t,i) => t + this.lineTotal(i), 0); },
        itemBogoDiscount(item) {
            if (item.is_gift) return 0; // Skip gifts
            const match = this.getBogoMatch(item.id);
            if(!match) return 0;
            
            // Enforce minimum spend (check against total subtotal)
            if ((parseFloat(match.min_spend) || 0) > this.subtotal) return 0;

            const buyQty = parseInt(match.buy_qty)||1;
            const getQty = parseInt(match.get_qty)||1;
            const cycle = buyQty + getQty;
            const qty = parseInt(item.quantity)||0;
            if(qty<cycle) return 0;
            const free = Math.floor(qty/cycle)*getQty;
            const eff = qty>0 ? this.lineTotal(item)/qty : 0;
            let d = Math.min(eff*free, this.lineTotal(item));
            if ((parseFloat(match.max_discount)||0) > 0) d = Math.min(d, parseFloat(match.max_discount));
            return d;
        },
        get orderEligibleSubtotal() {
            const o = this.bestOrderOffer;
            if (!o) return 0;
            if (o.type === 'category_discount' && o.applicable_categories && o.applicable_categories.length > 0) {
                const cats = typeof o.applicable_categories === 'string' ? JSON.parse(o.applicable_categories) : o.applicable_categories;
                return this.cart.reduce((t, item) => {
                    if (!item.is_gift && (cats.includes(item.category_id) || cats.includes(String(item.category_id)))) {
                        return t + Math.max(0, this.lineTotal(item) - this.itemBogoDiscount(item));
                    }
                    return t;
                }, 0);
            }
            return this.subtotal - this.bogoDiscount;
        },
        get couponEligibleSubtotal() {
            const c = this.couponApplied ? this.appliedCouponObj : null;
            if (!c || c.type === 'free_shipping' || c.type === 'free_product') return 0;
            return this.cart.reduce((t, item) => {
                if (item.is_gift) return t;
                const apps = typeof c.applicable_products === 'string' ? JSON.parse(c.applicable_products) : c.applicable_products;
                const excs = typeof c.excluded_products === 'string' ? JSON.parse(c.excluded_products) : c.excluded_products;
                const appCats = typeof c.applicable_categories === 'string' ? JSON.parse(c.applicable_categories) : c.applicable_categories;
                if (apps && apps.length > 0 && !apps.includes(item.id) && !apps.includes(String(item.id))) return t;
                if (excs && excs.length > 0 && (excs.includes(item.id) || excs.includes(String(item.id)))) return t;
                if (appCats && appCats.length > 0 && !appCats.includes(item.category_id) && !appCats.includes(String(item.category_id))) return t;
                return t + Math.max(0, this.lineTotal(item) - this.itemBogoDiscount(item));
            }, 0);
        },
        itemTaxableAmount(i) {
            const postBogo = Math.max(0, this.lineTotal(i) - this.itemBogoDiscount(i));
            let taxableAmount = postBogo;
            if (taxableAmount > 0) {
                const o = this.bestOrderOffer;
                const orderEligibleSubtotal = this.orderEligibleSubtotal;
                if (o && orderEligibleSubtotal > 0) {
                    let isEligible = true;
                    if (o.type === 'category_discount' && o.applicable_categories && o.applicable_categories.length > 0) {
                        const cats = typeof o.applicable_categories === 'string' ? JSON.parse(o.applicable_categories) : o.applicable_categories;
                        if (!cats.includes(i.category_id) && !cats.includes(String(i.category_id))) isEligible = false;
                    }
                    if (isEligible) {
                        taxableAmount -= (this.orderOfferDiscountAmount * (postBogo / orderEligibleSubtotal));
                    }
                }
                const c = this.couponApplied ? this.appliedCouponObj : null;
                const couponEligibleSubtotal = this.couponEligibleSubtotal;
                if (c && couponEligibleSubtotal > 0 && !i.is_gift) {
                    let isEligible = true;
                    const apps = typeof c.applicable_products === 'string' ? JSON.parse(c.applicable_products) : c.applicable_products;
                    const excs = typeof c.excluded_products === 'string' ? JSON.parse(c.excluded_products) : c.excluded_products;
                    const appCats = typeof c.applicable_categories === 'string' ? JSON.parse(c.applicable_categories) : c.applicable_categories;
                    if (apps && apps.length > 0 && !apps.includes(i.id) && !apps.includes(String(i.id))) isEligible = false;
                    if (excs && excs.length > 0 && (excs.includes(i.id) || excs.includes(String(i.id)))) isEligible = false;
                    if (appCats && appCats.length > 0 && !appCats.includes(i.category_id) && !appCats.includes(String(i.category_id))) isEligible = false;
                    if (isEligible) {
                        taxableAmount -= (this.couponDiscount * (postBogo / couponEligibleSubtotal));
                    }
                }
                taxableAmount = Math.max(0, taxableAmount);
            }
            return taxableAmount;
        },
        itemTaxAmount(i) {
            return this.itemTaxableAmount(i) * ((parseFloat(i.taxRate)||0)/100);
        },
        get taxAmount() { 
            return this.cart.reduce((t,i) => t + this.itemTaxAmount(i), 0); 
        },
        get bogoDiscount() {
            return this.cart.reduce((t,item) => t + this.itemBogoDiscount(item), 0);
        },
        get appliedBogoOfferNames() {
            const names = new Set();
            this.cart.forEach(item => {
                if (item.is_gift) return;
                const match = this.getBogoMatch(item.id);
                if (match && this.itemBogoDiscount(item) > 0) {
                    names.add(match.name);
                }
            });
            const arr = Array.from(names);
            return arr.length > 0 ? arr.join(', ') : 'No active offer';
        },
        get appliedBogoIds() {
            const ids = [];
            this.cart.forEach(item => {
                if (item.is_gift) return;
                const match = this.getBogoMatch(item.id);
                if(match && !ids.includes(match.id)) {
                    const cycle = parseInt(match.buy_qty||1) + parseInt(match.get_qty||1);
                    if(item.quantity >= cycle) ids.push(match.id);
                }
            });
            return ids;
        },
        get sortedActiveOffers() {
            return [...this.activeOffers].sort((a,b) => {
                if (a.type !== b.type) return (a.type === 'bogo' || a.type === 'free_product') ? -1 : 1;
                return (b.priority - a.priority) || (a.id - b.id);
            });
        },
        get availableOrderOffers() {
            return this.activeOffers.filter(o => ['order_discount', 'category_discount'].includes(o.type) && (parseFloat(o.min_spend)||0) <= this.subtotal);
        },
        orderOfferDiscount(o) {
            if (!o || !['order_discount', 'category_discount'].includes(o.type)) return 0;
            if ((parseFloat(o.min_spend)||0) > this.subtotal) return 0;
            
            let eligibleSubtotal = this.subtotal - this.bogoDiscount;
            if (o.type === 'category_discount' && o.applicable_categories && o.applicable_categories.length > 0) {
                const cats = typeof o.applicable_categories === 'string' ? JSON.parse(o.applicable_categories) : o.applicable_categories;
                eligibleSubtotal = this.cart.reduce((t, item) => {
                    if (item.is_gift) return t;
                    if (cats.includes(item.category_id) || cats.includes(String(item.category_id))) {
                        return t + Math.max(0, this.lineTotal(item) - this.itemBogoDiscount(item));
                    }
                    return t;
                }, 0);
            }
            
            if (eligibleSubtotal <= 0) return 0;

            let d = o.discount_type === 'percentage' ? eligibleSubtotal * (parseFloat(o.value) / 100) : parseFloat(o.value);
            if ((parseFloat(o.max_discount)||0) > 0) d = Math.min(d, parseFloat(o.max_discount));
            return Math.min(d, eligibleSubtotal);
        },
        get bestOrderOffer() {
            if (this.appliedOfferId && this.appliedOfferId !== 'none') {
                return this.availableOrderOffers.find(o => o.id === this.appliedOfferId) || null;
            }
            if (this.appliedOfferId === 'none') {
                return null;
            }
            
            let best = null;
            let maxVal = 0;
            this.availableOrderOffers.forEach(o => {
                let d = this.orderOfferDiscount(o);
                if (d > maxVal) {
                    maxVal = d;
                    best = o;
                }
            });
            return best;
        },
        get orderOfferDiscountAmount() {
            const best = this.bestOrderOffer;
            return best ? this.orderOfferDiscount(best) : 0;
        },
        get couponDiscount() {
            if (!this.couponApplied || !this.appliedCouponObj) return 0;
            const c = this.appliedCouponObj;
            if (c.type === 'free_shipping' || c.type === 'free_product') return 0;
            if ((parseFloat(c.min_spend) || 0) > this.subtotal) return 0;
            
            // Check applicable/excluded
            let eligibleSubtotal = this.cart.reduce((t, item) => {
                if (item.is_gift) return t;
                const apps = typeof c.applicable_products === 'string' ? JSON.parse(c.applicable_products) : c.applicable_products;
                const excs = typeof c.excluded_products === 'string' ? JSON.parse(c.excluded_products) : c.excluded_products;
                const appCats = typeof c.applicable_categories === 'string' ? JSON.parse(c.applicable_categories) : c.applicable_categories;
                if (apps && apps.length > 0 && !apps.includes(item.id) && !apps.includes(String(item.id))) return t;
                if (excs && excs.length > 0 && (excs.includes(item.id) || excs.includes(String(item.id)))) return t;
                if (appCats && appCats.length > 0 && !appCats.includes(item.category_id) && !appCats.includes(String(item.category_id))) return t;
                return t + Math.max(0, this.lineTotal(item) - this.itemBogoDiscount(item));
            }, 0);

            if (eligibleSubtotal <= 0) return 0;

            let d = c.type === 'percentage' ? eligibleSubtotal * (parseFloat(c.value) / 100) : parseFloat(c.value);
            if ((parseFloat(c.max_discount) || 0) > 0) d = Math.min(d, parseFloat(c.max_discount));
            return Math.min(d, eligibleSubtotal);
        },
        get totalDiscount() { return Math.min(this.subtotal, this.bogoDiscount + this.couponDiscount + this.orderOfferDiscountAmount); },
        get grandTotal() { 
            let shipping = this.shippingFee;
            if (this.couponApplied && this.appliedCouponObj && this.appliedCouponObj.type === 'free_shipping') {
                if (this.subtotal >= (parseFloat(this.appliedCouponObj.min_spend) || 0)) {
                    shipping = 0;
                }
            }
            return Math.max(0, this.subtotal - this.totalDiscount + this.taxAmount + shipping); 
        },

        async applyCoupon(codeToApply = null) {
            const code = (codeToApply || this.couponInputTemp || this.couponCode).toUpperCase().trim();
            if (!code) return;
            const res = await fetch('/coupons/validate', {method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content,'Accept':'application/json'}, body:JSON.stringify({code,subtotal:this.subtotal})});
            const json = await res.json();
            if (json.valid) { 
                this.couponCode = code;
                this.appliedCouponObj = json.coupon;
                this.couponApplied = true; 
                this.couponInputTemp = ''; // clear temp
                bootstrap.Modal.getInstance(document.getElementById('promotionsModal'))?.hide();
                await this.evaluateFreeProducts();
            } else { 
                this.appliedCouponObj = null;
                this.couponApplied = false; 
                await this.evaluateFreeProducts();
            }
            window.dispatchEvent(new CustomEvent('notify',{detail:{type:json.valid?'success':'error',message:json.message}}));
        },

        async removeCoupon() { this.couponCode=''; this.appliedCouponObj=null; this.couponApplied=false; await this.evaluateFreeProducts(); },

        editOrder(orderId) {
            const query = new URLSearchParams();
            if (this.partyId) query.set('customer_id', this.partyId);
            query.set('order_id', orderId);
            query.set('step', 'review');
            window.location.href = `/orders/create?${query.toString()}`;
        },

        cancelOrder(orderId, orderNo) {
            this.cancelModalOrder = { id: orderId, orderNumber: orderNo };
            this.cancelReason = '';
            this.cancelNotes = '';
            new bootstrap.Modal(document.getElementById('cancelOrderModal')).show();
        },
        async submitCancelOrder() {
            if (!this.cancelReason) {
                window.dispatchEvent(new CustomEvent('notify', { detail: { type: 'danger', message: 'Please select a reason for cancellation' }}));
                return;
            }
            try {
                const res = await fetch(`/orders/${this.cancelModalOrder.id}/cancel`, { 
                    method: 'POST', 
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ reason: this.cancelReason, notes: this.cancelNotes })
                });
                const json = await res.json();
                if (!res.ok) throw new Error(json.message || json.error || 'Failed to cancel order');
                window.dispatchEvent(new CustomEvent('notify', { detail: { type: 'success', message: json.message || 'Order cancelled successfully' }}));
                bootstrap.Modal.getInstance(document.getElementById('cancelOrderModal')).hide();
                this.loadAddresses();
            } catch (e) {
                window.dispatchEvent(new CustomEvent('notify', { detail: { type: 'danger', message: e.message }}));
            }
        },

        buildCartPayload() {
            return this.cart.map(item => {
                const base = item.quantity * (parseFloat(item.price)||0);
                const disc = this.lineTotal(item) < base ? base - this.lineTotal(item) : 0;
                const tax = this.lineTotal(item) * ((parseFloat(item.taxRate)||0)/100);
                return { 
                    product_id: item.id, 
                    quantity: item.quantity, 
                    unit_price: item.price, 
                    discount_amount: parseFloat(disc.toFixed(2)), 
                    tax_amount: parseFloat(tax.toFixed(2)), 
                    total_amount: parseFloat(this.lineTotal(item).toFixed(2)),
                    is_gift: item.is_gift ? 1 : 0,
                    gift_source: item.gift_source || null
                };
            });
        },

        async saveOrderData() {
            this.formErrors = [];
            if (!this.partyId) { this.formErrors.push('Please select a customer.'); return false; }
            if (!this.warehouseId) { this.formErrors.push('Please select a warehouse.'); return false; }
            if (!this.shippingAddressId) { this.formErrors.push('Please select a shipping address.'); return false; }
            if (!this.sameAsShipping && !this.billingAddressId) { this.formErrors.push('Please select a billing address.'); return false; }
            if (this.cart.length === 0) { this.formErrors.push('Cart is empty.'); return false; }
            if (this.orderStatus === 'future_order' && !this.futureOrderDate) { this.formErrors.push('Please set future order date.'); return false; }

            try {
                const payload = {
                    type: this.orderType,
                    party_id: this.partyId,
                    warehouse_id: this.warehouseId,
                    shipping_address_id: this.shippingAddressId || null,
                    billing_address_id: this.sameAsShipping ? (this.shippingAddressId || null) : (this.billingAddressId || null),
                    order_date: this.orderDate,
                    items: this.buildCartPayload(),
                    status: this.orderStatus,
                    future_order_date: this.orderStatus === 'future_order' ? this.futureOrderDate : null,
                    coupon_code: this.couponApplied ? this.couponCode : null,
                    applied_offer_id: this.bestOrderOffer ? this.bestOrderOffer.id : null,
                    applied_bogo_ids: this.appliedBogoIds,
                    total_amount: parseFloat(this.subtotal.toFixed(2)),
                    tax_amount: parseFloat(this.taxAmount.toFixed(2)),
                    discount_amount: parseFloat(this.totalDiscount.toFixed(2)),
                    net_amount: parseFloat(this.grandTotal.toFixed(2)),
                    use_wallet_balance: this.useWalletBalance ? 1 : 0,
                };
                const url = this.editingOrderId ? `/orders/${this.editingOrderId}` : '/orders';
                const res = await fetch(url, { 
                    method: this.editingOrderId ? 'PUT' : 'POST', 
                    headers: {
                        'Content-Type':'application/json',
                        'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content,
                        'Accept':'application/json'
                    }, 
                    body:JSON.stringify(payload) 
                });
                
                const json = await res.json();
                if (!res.ok) {
                    this.formErrors = Object.values(json.errors||{}).flat();
                    if (!this.formErrors.length && json.message) this.formErrors.push(json.message);
                    return false;
                }
                if (json && json.data && json.data.id) {
                    this.editingOrderId = json.data.id;
                }
                
                return true;
            } catch(e) {
                this.formErrors.push('An unexpected error occurred while saving the order data.');
                return false;
            }
        },

        async submitConfirmation() {
            this.formErrors = [];
            if (this.confirmAction === 'schedule') {
                if (!this.scheduleReason) this.formErrors.push('Please provide a reason for rescheduling.');
                if (!this.scheduledConfirmDate) this.formErrors.push('Please select a scheduled date.');
            }
            if (this.formErrors.length > 0) return;

            this.placing = true;
            try {
                // FIRST: Save the order changes (if any)
                const isSaved = await this.saveOrderData();
                if (!isSaved) return; // Halt if save failed

                // SECOND: Confirm the order
                const payload = {
                    action: this.confirmAction,
                    reason: this.scheduleReason,
                    scheduled_date: this.scheduledConfirmDate,
                    notes: this.confirmNotes
                };
                
                const response = await fetch(`/orders/${this.editingOrderId}/confirm`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify(payload)
                });
                
                const data = await response.json();
                
                if (!response.ok) {
                    this.formErrors = Object.values(data.errors || {}).flat();
                    if (!this.formErrors.length && data.error) this.formErrors.push(data.error);
                    if (!this.formErrors.length && data.message) this.formErrors.push(data.message);
                    if (!this.formErrors.length) this.formErrors.push('Failed to confirm order.');
                    return;
                }

                let finalMessage = this.confirmAction === 'schedule' 
                    ? 'Order updated and scheduled for follow-up!' 
                    : 'Order updated and successfully confirmed!';
                    
                this.isCallLoggedOrClosed = true;
                window.location.href = '{{ route("orders") }}?success=' + encodeURIComponent(finalMessage);
            } catch (error) {
                this.formErrors.push(error.message);
            } finally {
                this.placing = false;
            }
        },

        async placeOrder() {
            this.placing = true;
            try {
                const isSaved = await this.saveOrderData();
                if (!isSaved) return;
                
                localStorage.removeItem('ecommerce_create_order_cart');
                this.cart = [];
                const successMessage = this.editingOrderId ? 'Order updated successfully!' : 'Order placed successfully!';
                window.dispatchEvent(new CustomEvent('notify',{detail:{type:'success',message:successMessage}}));
                this.loadAddresses(); // Refresh the customer's recent orders list
                this.searchProducts(); // Refresh the products list to update inventory stock
                if (this.editingOrderId) {
                    this.editingOrderId = null;
                    this.editingOrderNo = null;
                    const url = new URL(window.location.href);
                    url.searchParams.delete('order_id');
                    url.searchParams.delete('step');
                    window.history.pushState({}, '', url);
                }
            } catch(e) { 
                this.formErrors.push('An unexpected error occurred.'); 
            } finally { 
                this.placing = false; 
            }
        },

        getStatusTheme(status) {
            if (!status) return 'secondary';
            const themes = {
                future_order: 'info',
                pending: 'warning',
                pending_confirmation: 'warning',
                confirmed: 'primary',
                processing: 'primary',
                ready_to_ship: 'primary',
                dispatched: 'primary',
                shipped: 'primary',
                delivered: 'success',
                cancelled: 'danger',
                return_requested: 'warning',
                returned: 'secondary'
            };
            return themes[status.toLowerCase()] || 'secondary';
        }
    };
}
</script>
@endpush


<x-add-customer-modal />
<x-customer-address-modal />
<x-call-tagging-modal />

<!-- Action Blocked Modal -->
<div class="modal fade" id="actionBlockedModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger bg-opacity-10 border-bottom-0 pb-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center pt-0">
                <div class="text-bg-danger-subtle text-danger-emphasis rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 64px; height: 64px;">
                    <i class="bi bi-shield-lock-fill fs-1"></i>
                </div>
                <h5 class="fw-bold mb-2 text-danger">Action Blocked</h5>
                <p class="text-body-secondary mb-0 small">You must <strong>Log a Call</strong> or explicitly <strong>Close the Profile</strong> before leaving this page.</p>
            </div>
            <div class="modal-footer border-top-0 justify-content-center pb-4 pt-0">
                <button type="button" class="btn btn-danger px-4 shadow-sm rounded-pill" data-bs-dismiss="modal">I Understand</button>
            </div>
        </div>
    </div>
</div>

@endsection
