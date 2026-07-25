<!-- Header -->
<header class="admin-header border-bottom shadow-sm sticky-top" role="banner" style="z-index: 1030; min-height: 70px; background: rgba(var(--bs-body-bg-rgb, 255, 255, 255), 0.85); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);">
    <nav class="navbar navbar-expand h-100 py-0" aria-label="Main navigation">
        <div class="container-fluid align-items-center h-100 px-3 px-md-4 d-flex justify-content-between">

            {{-- ── LEFT SECTION (Brand & Toggle) ────────────────── --}}
            <div class="d-flex align-items-center gap-3" style="flex: 1; min-width: 0;">
                
                {{-- SIDEBAR TOGGLE --}}
                @if(!isset($hideSidebar) || !$hideSidebar)
                <button class="btn btn-body-secondary rounded-circle p-2 d-flex align-items-center justify-content-center shadow-none text-secondary flex-shrink-0"
                        style="width: 40px; height: 40px;"
                        type="button"
                        data-sidebar-toggle
                        aria-label="Toggle sidebar"
                        aria-controls="admin-sidebar"
                        aria-expanded="false">
                    <i class="bi bi-list fs-4" aria-hidden="true"></i>
                </button>
                @endif

                {{-- BRAND --}}
                <a class="navbar-brand d-flex align-items-center gap-2 m-0" href="{{ route('dashboard') }}" aria-label="Ecommerce Admin — go to dashboard">
                    <div class="bg-primary bg-opacity-10 rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px;">
                        <img src="/assets/images/logo.svg" alt="" width="24" height="24" aria-hidden="true">
                    </div>
                    <div class="d-none d-xl-flex flex-column lh-1 text-truncate">
                        <span class="fw-bold text-body fs-5 tracking-tight">Ecommerce</span>
                        <span class="text-primary fw-bold text-uppercase" style="font-size: 9px; letter-spacing: 2px;">Admin</span>
                    </div>
                </a>
            </div>

            {{-- ── CENTER SECTION (Search) ──────────────────────── --}}
            <div class="d-none d-md-flex justify-content-center px-4" style="flex: 2; max-width: 600px;">
                <div class="position-relative w-100" x-data="{
                    searchQuery: '',
                    items: [
                        { name: 'Dashboard', path: '{{ route('dashboard') }}', icon: 'bi bi-grid-1x2-fill', group: 'Main' },
                        { name: 'Analytics', path: '{{ route('analytics') }}', icon: 'bi bi-bar-chart-line-fill', group: 'Main' },
                        { name: 'Reports', path: '{{ route('reports') }}', icon: 'bi bi-file-earmark-bar-graph-fill', group: 'Main' },
                        
                        { name: 'Orders', path: '{{ route('orders') }}', icon: 'bi bi-bag-check-fill', group: 'Sales & Marketing' },
                        { name: 'Coupon Codes', path: '{{ route('promotions.coupons') }}', icon: 'bi bi-ticket-perforated-fill', group: 'Sales & Marketing' },
                        { name: 'Offers & Deals', path: '{{ route('promotions.offers') }}', icon: 'bi bi-star-fill', group: 'Sales & Marketing' },
                        
                        { name: 'Invoices', path: '{{ route('invoices.index') }}', icon: 'bi bi-receipt', group: 'Billing & Payments' },
                        { name: 'Payments', path: '{{ route('payments.index') }}', icon: 'bi bi-credit-card', group: 'Billing & Payments' },
                        { name: 'Refunds', path: '{{ route('refunds.index') }}', icon: 'bi bi-cash-coin', group: 'Billing & Payments' },
                        { name: 'Returns', path: '{{ route('returns.index') }}', icon: 'bi bi-arrow-return-left', group: 'Billing & Payments' },
                        
                        { name: 'Shipments & Tracking', path: '{{ route('shipping.shipments') }}', icon: 'bi bi-geo-alt-fill', group: 'Logistics & Warehouses' },
                        { name: 'Shipping Services', path: '{{ route('shipping.services') }}', icon: 'bi bi-gear-wide-connected', group: 'Logistics & Warehouses' },
                        { name: 'Warehouses', path: '{{ route('catalog.warehouses') }}', icon: 'bi bi-buildings-fill', group: 'Logistics & Warehouses' },
                        
                        { name: 'Stock Levels', path: '{{ route('inventory.stock-management') }}', icon: 'bi bi-box-seam-fill', group: 'Inventory & Stock' },
                        { name: 'Stock Transfers', path: '{{ route('inventory.stock-transfers') }}', icon: 'bi bi-arrow-left-right', group: 'Inventory & Stock' },
                        { name: 'Adjustments', path: '{{ route('inventory.adjustments') }}', icon: 'bi bi-sliders2', group: 'Inventory & Stock' },
                        
                        { name: 'Products', path: '{{ route('catalog.products') }}', icon: 'bi bi-box-seam-fill', group: 'Catalog Management' },
                        { name: 'Categories', path: '{{ route('catalog.categories') }}', icon: 'bi bi-diagram-3-fill', group: 'Catalog Management' },
                        { name: 'Brands', path: '{{ route('catalog.brands') }}', icon: 'bi bi-patch-check-fill', group: 'Catalog Management' },
                        { name: 'Attributes', path: '{{ route('catalog.attributes') }}', icon: 'bi bi-sliders2', group: 'Catalog Management' },
                        { name: 'Units of Measure', path: '{{ route('catalog.uom') }}', icon: 'bi bi-rulers', group: 'Catalog Management' },
                        { name: 'Tax Rates', path: '{{ route('catalog.tax-rates') }}', icon: 'bi bi-percent', group: 'Catalog Management' },
                        { name: 'HSN Codes', path: '{{ route('catalog.hsn-codes') }}', icon: 'bi bi-upc-scan', group: 'Catalog Management' },
                        
                        { name: 'Users', path: '{{ route('users') }}', icon: 'bi bi-person-fill-gear', group: 'User & Customer Admin' },
                        { name: 'Roles & Permissions', path: '{{ route('roles-permissions') }}', icon: 'bi bi-shield-lock-fill', group: 'User & Customer Admin' },
                        { name: 'Customers', path: '{{ route('customers') }}', icon: 'bi bi-person-lines-fill', group: 'User & Customer Admin' },
                        { name: 'Villages', path: '{{ route('villages') }}', icon: 'bi bi-geo-alt-fill', group: 'User & Customer Admin' },
                        { name: 'Order Reasons', path: '{{ route('order.reasons') }}', icon: 'bi bi-list-task', group: 'User & Customer Admin' },
                        
                        { name: 'Team Chat', path: '{{ route('chat.index') }}', icon: 'bi bi-chat-text-fill', group: 'Utilities & Tools' },
                        { name: 'Messages', path: '{{ route('messages') }}', icon: 'bi bi-chat-dots-fill', group: 'Utilities & Tools' },
                        { name: 'Calendar', path: '{{ route('calendar') }}', icon: 'bi bi-calendar-week-fill', group: 'Utilities & Tools' },
                        { name: 'Files', path: '{{ route('files') }}', icon: 'bi bi-folder2-open', group: 'Utilities & Tools' },
                        { name: 'Forms', path: '{{ route('forms') }}', icon: 'bi bi-ui-checks-grid', group: 'Utilities & Tools' },
                        { name: 'Settings', path: '{{ route('settings') }}', icon: 'bi bi-gear-fill', group: 'Utilities & Tools' },
                        { name: 'Security', path: '{{ route('security') }}', icon: 'bi bi-shield-fill-check', group: 'Utilities & Tools' },
                        { name: 'Help & Support', path: '{{ route('help') }}', icon: 'bi bi-question-circle-fill', group: 'Utilities & Tools' },
                        { name: 'API Documentation', path: '/docs/api', icon: 'bi bi-file-earmark-code-fill', group: 'Utilities & Tools' }
                    ],
                    get filteredItems() {
                        if (!this.searchQuery) return [];
                        return this.items.filter(item => 
                            item.name.toLowerCase().includes(this.searchQuery.toLowerCase()) || 
                            item.group.toLowerCase().includes(this.searchQuery.toLowerCase())
                        );
                    },
                    clearSearch() {
                        this.searchQuery = '';
                    }
                }">
                    <div class="position-relative d-flex align-items-center">
                        <div class="position-absolute start-0 ps-3 text-muted d-flex align-items-center" style="z-index: 10;">
                            <i class="bi bi-search fs-5" aria-hidden="true"></i>
                        </div>
                        
                        <input type="text"
                               class="form-control form-control-lg bg-body-secondary border-0 rounded-pill shadow-none fw-semibold pe-4 w-100"
                               style="font-size: 14px; letter-spacing: 0.5px; padding-left: 3rem !important;"
                               placeholder="Search anywhere..."
                               x-model="searchQuery"
                               @keydown.escape="clearSearch()"
                               aria-label="Search pages">
                               
                        <div class="position-absolute end-0 pe-2 d-flex align-items-center" style="z-index: 10;" x-show="searchQuery.length > 0" x-cloak>
                            <button type="button" class="btn btn-sm btn-link text-muted p-1 text-decoration-none" @click="clearSearch()">
                                <i class="bi bi-x-circle-fill"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Search Results Overlay -->
                    <div x-show="searchQuery && filteredItems.length > 0" 
                         class="position-absolute bg-body border rounded shadow-lg p-2 mt-2 w-100" 
                         style="z-index: 1050; left: 0; right: 0; max-height: 350px; overflow-y: auto;"
                         x-cloak>
                        <template x-for="item in filteredItems" :key="item.path">
                            <a :href="item.path" 
                               class="dropdown-item py-2 px-3 rounded d-flex align-items-center gap-2"
                               style="font-size: 0.875rem; transition: background-color 0.15s ease;"
                               @click="clearSearch()">
                                <i :class="item.icon" class="text-primary fs-6"></i>
                                <span class="fw-semibold" x-text="item.name"></span>
                                <span class="badge text-bg-secondary ms-auto" style="font-size: 0.65rem;" x-text="item.group"></span>
                            </a>
                        </template>
                    </div>
                    <div x-show="searchQuery && filteredItems.length === 0" 
                         class="position-absolute bg-body border rounded shadow p-3 mt-2 w-100 text-center text-muted small" 
                         style="z-index: 1050; left: 0; right: 0;"
                         x-cloak>
                        No pages found matching "<span x-text="searchQuery" class="fw-bold"></span>"
                    </div>
                </div>
            </div>
            {{-- ── RIGHT ACTIONS ─────────────────────────────────── --}}
            <div class="d-flex align-items-center justify-content-end gap-2 gap-sm-3 h-100" style="flex: 1; min-width: 0;">

                {{-- Web Apps Dropdown --}}
                <div class="dropdown h-100 d-flex align-items-center">
                    <button class="btn btn-body-secondary rounded-circle p-2 d-flex align-items-center justify-content-center shadow-none text-secondary position-relative transition-all"
                            style="width: 40px; height: 40px;"
                            type="button"
                            id="webAppsMenuBtn"
                            data-bs-toggle="dropdown"
                            data-bs-display="static"
                            aria-expanded="false"
                            aria-label="Web apps">
                        <i class="bi bi-grid-3x3-gap-fill fs-5" aria-hidden="true"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end p-0 shadow-lg border-0 rounded-4 mt-3" aria-labelledby="webAppsMenuBtn" style="width: 320px;">
                        <div class="p-3 border-bottom bg-body-secondary bg-opacity-50 rounded-top-4">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h6 class="m-0 fw-bold text-body text-uppercase" style="font-size: 11px; letter-spacing: 1px;">Web Apps</h6>
                                </div>
                                <div class="col-auto">
                                    <a href="#!" class="btn btn-sm btn-link p-0 text-decoration-none fw-bold" style="font-size: 11px;">View All</a>
                                </div>
                            </div>
                        </div>

                        <div class="p-3">
                            <div class="row g-3 text-center">
                                <div class="col-4">
                                    <a class="dropdown-item p-2 rounded-3 d-flex flex-column align-items-center gap-2 hover-bg-secondary" href="#!">
                                        <img src="{{ asset('assets/images/github.png') }}" alt="Github" height="24">
                                        <span class="fw-semibold text-muted" style="font-size: 11px;">GitHub</span>
                                    </a>
                                </div>
                                <div class="col-4">
                                    <a class="dropdown-item p-2 rounded-3 d-flex flex-column align-items-center gap-2 hover-bg-secondary" href="#!">
                                        <img src="{{ asset('assets/images/bitbucket.png') }}" alt="Bitbucket" height="24">
                                        <span class="fw-semibold text-muted" style="font-size: 11px;">Bitbucket</span>
                                    </a>
                                </div>
                                <div class="col-4">
                                    <a class="dropdown-item p-2 rounded-3 d-flex flex-column align-items-center gap-2 hover-bg-secondary" href="#!">
                                        <img src="{{ asset('assets/images/dribbble.png') }}" alt="Dribbble" height="24">
                                        <span class="fw-semibold text-muted" style="font-size: 11px;">Dribbble</span>
                                    </a>
                                </div>
                                <div class="col-4">
                                    <a class="dropdown-item p-2 rounded-3 d-flex flex-column align-items-center gap-2 hover-bg-secondary" href="#!">
                                        <img src="{{ asset('assets/images/dropbox.png') }}" alt="Dropbox" height="24">
                                        <span class="fw-semibold text-muted" style="font-size: 11px;">Dropbox</span>
                                    </a>
                                </div>
                                <div class="col-4">
                                    <a class="dropdown-item p-2 rounded-3 d-flex flex-column align-items-center gap-2 hover-bg-secondary" href="#!">
                                        <img src="{{ asset('assets/images/mail_chimp.png') }}" alt="Mail Chimp" height="24">
                                        <span class="fw-semibold text-muted" style="font-size: 11px;">Mail Chimp</span>
                                    </a>
                                </div>
                                <div class="col-4">
                                    <a class="dropdown-item p-2 rounded-3 d-flex flex-column align-items-center gap-2 hover-bg-secondary" href="#!">
                                        <img src="{{ asset('assets/images/slack.png') }}" alt="Slack" height="24">
                                        <span class="fw-semibold text-muted" style="font-size: 11px;">Slack</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Shopping Cart Dropdown --}}
                @if(request()->routeIs('orders.create', 'promotions.coupons', 'promotions.offers'))
                <div class="dropdown h-100 d-flex align-items-center" x-data="headerCart">
                    <button class="btn btn-body-secondary rounded-circle p-2 d-flex align-items-center justify-content-center shadow-none text-secondary position-relative transition-all"
                            style="width: 40px; height: 40px;"
                            type="button"
                            id="cartMenuBtn"
                            data-bs-toggle="dropdown"
                            data-bs-auto-close="outside"
                            data-bs-display="static"
                            aria-expanded="false"
                            aria-label="Shopping cart">
                        <i class="bi bi-bag-fill fs-5" aria-hidden="true"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary border border-2 border-body" style="font-size: 9px; margin-top: 6px; margin-left: -10px;" x-text="items.length" x-show="items.length > 0" x-cloak></span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end p-0 shadow-lg border-0 rounded-4 mt-3" aria-labelledby="cartMenuBtn" style="width: 350px;">
                        <div class="p-3 border-bottom bg-body-secondary bg-opacity-50 rounded-top-4">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h6 class="m-0 fw-bold text-body text-uppercase" style="font-size: 11px; letter-spacing: 1px;">My Cart</h6>
                                </div>
                                <div class="col-auto">
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill fw-bold" style="font-size: 10px;"><span x-text="items.length"></span> Items</span>
                                </div>
                            </div>
                        </div>
                        <div style="max-height: 320px; overflow-y: auto;" class="custom-scrollbar">
                            <template x-if="items.length === 0">
                                <div class="p-5 text-center d-flex flex-column align-items-center opacity-75">
                                    <div class="bg-secondary bg-opacity-10 text-secondary rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                                        <i class="bi bi-bag-x fs-2"></i>
                                    </div>
                                    <h6 class="fw-bold text-body mb-1">Your cart is empty</h6>
                                    <p class="text-muted small mb-0">Browse products to add items</p>
                                </div>
                            </template>
                            <div x-show="items.length > 0" x-cloak>
                                <template x-for="(item, idx) in items" :key="item.id">
                                    <div class="d-flex align-items-center px-3 py-3 border-bottom position-relative hover-bg-secondary transition-all">
                                        <div class="bg-body-secondary border rounded-3 d-flex align-items-center justify-content-center overflow-hidden me-3 flex-shrink-0" style="width: 48px; height: 48px;">
                                            <img :src="item.image_url || '/assets/images/product-placeholder.svg'" class="w-100 h-100 object-fit-cover" alt="Product" x-on:error="$el.src='/assets/images/product-placeholder.svg'">
                                        </div>
                                        <div class="flex-grow-1" style="min-width: 0;">
                                            <h6 class="mb-1 fw-bold text-body text-truncate fs-6" x-text="item.name"></h6>
                                            <p class="mb-0 text-muted fw-semibold" style="font-size: 11px;">Qty: <span x-text="item.quantity"></span> × Rs <span x-text="parseFloat(item.price).toFixed(2)"></span></p>
                                        </div>
                                        <div class="text-end ms-2">
                                            <h6 class="mb-1 fw-black text-success" x-text="'Rs ' + (item.quantity * parseFloat(item.price)).toFixed(2)"></h6>
                                            <button type="button" class="btn btn-sm btn-link text-danger p-0 text-decoration-none" style="font-size: 11px;" @click="removeItem(idx)">Remove</button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                        <div class="p-3 border-top bg-body-secondary bg-opacity-50 rounded-bottom-4" x-show="items.length > 0" x-cloak>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="m-0 text-muted fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 1px;">Grand Total</h6>
                                <h5 class="m-0 fw-black text-primary" x-text="'Rs ' + cartGrandTotal.toFixed(2)"></h5>
                            </div>
                            <button type="button" @click="handleCartClick" class="btn btn-primary w-100 rounded-pill fw-bold text-uppercase shadow-sm d-flex align-items-center justify-content-center gap-2" style="font-size: 11px; letter-spacing: 1px;">
                                <i class="bi bi-cart-check"></i> View Cart For Checkout
                            </button>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Theme Toggle --}}
                <div x-data="themeSwitch" class="h-100 d-flex align-items-center d-none d-md-flex">
                    <button class="btn btn-body-secondary rounded-circle p-2 d-flex align-items-center justify-content-center shadow-none text-secondary position-relative transition-all"
                            style="width: 40px; height: 40px;"
                            type="button"
                            @click="toggle()"
                            data-bs-toggle="tooltip"
                            data-bs-placement="bottom"
                            :title="currentTheme === 'light' ? 'Switch to dark mode' : 'Switch to light mode'"
                            aria-label="Toggle theme">
                        <i class="bi bi-sun-fill fs-5 text-warning" x-show="currentTheme === 'light'" aria-hidden="true"></i>
                        <i class="bi bi-moon-stars-fill fs-5 text-primary" x-show="currentTheme === 'dark'" aria-hidden="true" x-cloak></i>
                    </button>
                </div>

                {{-- Fullscreen Toggle --}}
                <button class="btn btn-body-secondary rounded-circle p-2 d-flex align-items-center justify-content-center shadow-none text-secondary position-relative transition-all d-none d-lg-flex"
                        style="width: 40px; height: 40px;"
                        type="button"
                        data-fullscreen-toggle
                        data-bs-toggle="tooltip"
                        data-bs-placement="bottom"
                        title="Toggle fullscreen"
                        aria-label="Toggle fullscreen">
                    <i class="bi bi-arrows-fullscreen fs-5" aria-hidden="true"></i>
                </button>

@php
    $initialActivities = \Spatie\Activitylog\Models\Activity::with('causer')->latest()->limit(10)->get()->map(function($a) {
        return [
            'id' => $a->id,
            'description' => $a->description,
            'subject_type' => class_basename($a->subject_type),
            'causer_name' => $a->causer->name ?? 'System',
            'causer_photo' => $a->causer->photo ?? null,
            'time_ago' => $a->created_at->diffForHumans(),
            'is_read' => auth()->check() ? in_array($a->id, auth()->user()->readActivities()->pluck('activity_id')->toArray()) : false,
        ];
    });
    $initialUnreadCount = $initialActivities->where('is_read', false)->count();
@endphp
                {{-- Notifications Dropdown --}}
                <div class="dropdown h-100 d-flex align-items-center" x-data="notificationApp(@js($initialActivities), {{ $initialUnreadCount }})">
                    <button class="btn btn-body-secondary rounded-circle p-2 d-flex align-items-center justify-content-center shadow-none text-secondary position-relative transition-all"
                            style="width: 40px; height: 40px;"
                            type="button"
                            id="notificationsMenuBtn"
                            data-bs-toggle="dropdown"
                            data-bs-auto-close="outside"
                            data-bs-display="static"
                            @click="fetchActivities"
                            aria-expanded="false"
                            aria-label="Notifications">
                        <i class="bi bi-bell-fill fs-5" aria-hidden="true"></i>
                        <span x-show="count > 0" x-cloak class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-2 border-body" style="font-size: 9px; margin-top: 6px; margin-left: -10px;" x-text="count"></span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end p-0 shadow-lg border-0 rounded-4 mt-3"
                         aria-labelledby="notificationsMenuBtn"
                         style="width: 340px;">
                        <div class="bg-primary rounded-top-4 p-3 text-white">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div>
                                    <h6 class="m-0 fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 1px;">Notifications / Activity</h6>
                                    <button type="button" @click="markAsRead('all')" class="btn btn-sm btn-link text-white-50 text-decoration-none p-0 mt-1" style="font-size: 10px;">Mark all as read</button>
                                </div>
                                <span class="badge bg-body text-primary rounded-pill fw-bold" style="font-size: 10px;"><span x-text="count"></span> Recent</span>
                            </div>
                            <ul class="nav nav-tabs nav-tabs-custom border-bottom-0" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active py-1 px-3 fs-12 text-white border-0 bg-transparent fw-semibold" data-bs-toggle="tab" data-bs-target="#all-noti-tab" type="button" role="tab" aria-controls="all-noti-tab" aria-selected="true" style="opacity: 0.8;">
                                        All Activity
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link py-1 px-3 fs-12 text-white border-0 bg-transparent fw-semibold" data-bs-toggle="tab" data-bs-target="#messages-noti-tab" type="button" role="tab" aria-controls="messages-noti-tab" aria-selected="false" style="opacity: 0.8;">
                                        Messages
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link py-1 px-3 fs-12 text-white border-0 bg-transparent fw-semibold" data-bs-toggle="tab" data-bs-target="#alerts-noti-tab" type="button" role="tab" aria-controls="alerts-noti-tab" aria-selected="false" style="opacity: 0.8;">
                                        Alerts
                                    </button>
                                </li>
                            </ul>
                        </div>

                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="all-noti-tab" role="tabpanel">
                                <div style="max-height: 300px; overflow-y: auto;" class="custom-scrollbar">
                                    <template x-if="activities.length === 0">
                                        <div class="p-4 text-center opacity-75">
                                            <i class="bi bi-bell-slash fs-2 text-muted mb-2 d-block"></i>
                                            <p class="text-muted mb-0">No recent activity.</p>
                                        </div>
                                    </template>
                                    <template x-for="activity in activities" :key="activity.id">
                                        <a class="dropdown-item p-3 border-bottom d-flex align-items-start gap-3 text-wrap transition-all" href="#" @click.prevent="markAsRead(activity.id)" :class="{
                                            'bg-primary bg-opacity-10 position-relative': !activity.is_read,
                                            'hover-bg-secondary opacity-75': activity.is_read
                                        }">
                                            <!-- Unread Dot Indicator -->
                                            <template x-if="!activity.is_read">
                                                <div class="position-absolute top-50 start-0 translate-middle-y bg-primary rounded-circle ms-2 shadow-sm" style="width: 8px; height: 8px;"></div>
                                            </template>
                                            
                                            <template x-if="activity.causer_photo">
                                                <img :src="activity.causer_photo" class="rounded-circle flex-shrink-0 object-fit-cover ms-2" :class="!activity.is_read ? 'border border-2 border-primary border-opacity-25' : ''" alt="User" width="40" height="40" x-on:error="$el.src='/assets/images/product-placeholder.svg'">
                                            </template>
                                            <template x-if="!activity.causer_photo">
                                                <div class="text-primary rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 ms-2" :class="!activity.is_read ? 'bg-primary bg-opacity-25 border border-2 border-primary border-opacity-25' : 'bg-secondary bg-opacity-10 text-secondary'" style="width: 40px; height: 40px;">
                                                    <i class="bi bi-person-fill fs-5"></i>
                                                </div>
                                            </template>
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <h6 class="mb-0 fs-13" :class="!activity.is_read ? 'fw-bold text-primary' : 'fw-semibold text-body'" x-text="activity.causer_name"></h6>
                                                    <template x-if="!activity.is_read">
                                                        <span class="badge bg-primary rounded-pill shadow-sm" style="font-size: 9px;">New</span>
                                                    </template>
                                                </div>
                                                <p class="mb-1 fs-13" :class="!activity.is_read ? 'text-body fw-bold' : 'text-muted'" style="line-height: 1.4;">
                                                    <span x-text="activity.description"></span> a <b x-text="activity.subject_type"></b>.
                                                </p>
                                                <p class="mb-0 small" :class="!activity.is_read ? 'text-primary text-opacity-75 fw-semibold' : 'text-muted'"><i class="bi bi-clock me-1"></i> <span x-text="activity.time_ago"></span></p>
                                            </div>
                                        </a>
                                    </template>
                                </div>
                                <div class="p-2 text-center bg-body-secondary bg-opacity-50 rounded-bottom-4">
                                    <button type="button" class="btn btn-sm btn-link text-primary fw-bold text-decoration-none">View All Activity <i class="bi bi-arrow-right-short align-middle"></i></button>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="messages-noti-tab" role="tabpanel">
                                <div style="max-height: 300px; overflow-y: auto;" class="custom-scrollbar">
                                    
                                    <!-- Unread Message (Distinct styling) -->
                                    <a class="dropdown-item p-3 border-bottom d-flex align-items-start gap-3 text-wrap transition-all bg-primary bg-opacity-10 position-relative" href="#">
                                        <!-- Unread Dot Indicator -->
                                        <div class="position-absolute top-50 start-0 translate-middle-y bg-primary rounded-circle ms-2 shadow-sm" style="width: 8px; height: 8px;"></div>
                                        
                                        <img src="{{ asset('assets/images/users/avatar-3.jpg') }}" class="rounded-circle flex-shrink-0 object-fit-cover ms-2 border border-2 border-primary border-opacity-25" alt="James" width="40" height="40" x-on:error="$el.src='/assets/images/product-placeholder.svg'">
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <h6 class="mb-0 fw-bold text-primary fs-13">James Lemire</h6>
                                                <span class="badge bg-primary rounded-pill shadow-sm" style="font-size: 9px;">New</span>
                                            </div>
                                            <p class="mb-1 text-body fw-bold fs-13">We talked about a project on linkedin.</p>
                                            <p class="mb-0 text-primary text-opacity-75 small fw-semibold"><i class="bi bi-clock me-1"></i> 30 min ago</p>
                                        </div>
                                    </a>

                                    <!-- Read Message (Muted styling) -->
                                    <a class="dropdown-item p-3 border-bottom d-flex align-items-start gap-3 text-wrap hover-bg-secondary transition-all" href="#">
                                        <div class="bg-secondary bg-opacity-10 text-secondary rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 ms-3" style="width: 40px; height: 40px;">
                                            <i class="bi bi-person-fill fs-5"></i>
                                        </div>
                                        <div class="flex-grow-1 opacity-75">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <h6 class="mb-0 fw-semibold text-body fs-13">Sarah Smith</h6>
                                            </div>
                                            <p class="mb-1 text-muted fs-13">Can you send me the latest invoice?</p>
                                            <p class="mb-0 text-muted small"><i class="bi bi-clock me-1"></i> 2 hours ago</p>
                                        </div>
                                    </a>

                                </div>
                                <div class="p-2 text-center bg-body-secondary bg-opacity-50 rounded-bottom-4">
                                    <button type="button" class="btn btn-sm btn-link text-primary fw-bold text-decoration-none">View All Messages <i class="bi bi-arrow-right-short align-middle"></i></button>
                                </div>
                            </div>
                            <div class="tab-pane fade p-5 text-center" id="alerts-noti-tab" role="tabpanel">
                                <div class="bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 64px; height: 64px;">
                                    <i class="bi bi-bell-slash fs-2"></i>
                                </div>
                                <h6 class="fw-bold text-muted mb-0">No new alerts yet!</h6>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="vr mx-1 d-none d-md-block opacity-25"></div>

                {{-- User Menu --}}
                <div class="dropdown h-100 d-flex align-items-center">
                    <button class="btn btn-body-secondary p-1 pe-md-3 d-flex align-items-center gap-2 rounded-pill shadow-none border-0 transition-all hover-bg-secondary"
                            type="button"
                            id="userMenuBtn"
                            data-bs-toggle="dropdown"
                            data-bs-display="static"
                            aria-expanded="false"
                            aria-label="User menu">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm border border-2 border-body" style="width: 36px; height: 36px;">
                            <i class="bi bi-person-fill fs-5"></i>
                        </div>
                        <span class="d-none d-md-flex flex-column text-start ms-1 lh-1">
                            <span class="fw-bold text-body" style="font-size: 13px;">{{ Auth::user()?->name ?? 'User' }}</span>
                            <span class="text-muted fw-semibold" style="font-size: 10px;">{{ Auth::user()?->roles->first()?->name ?? 'User' }}</span>
                        </span>
                        <i class="bi bi-chevron-down text-muted ms-1 d-none d-md-inline" style="font-size: 12px;"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 mt-3 py-2"
                        aria-labelledby="userMenuBtn" style="min-width: 240px;">
                        
                        <li class="px-4 py-3 d-flex align-items-center gap-3 border-bottom mb-2">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px;">
                                <i class="bi bi-person-fill fs-3"></i>
                            </div>
                            <div style="min-width: 0;">
                                <h6 class="mb-1 fw-bold text-body text-truncate">{{ Auth::user()?->name ?? 'User' }}</h6>
                                <p class="mb-0 text-muted small text-truncate">{{ Auth::user()?->email ?? 'admin@example.com' }}</p>
                            </div>
                        </li>
                        
                        <li>
                            <a class="dropdown-item px-4 py-2 d-flex align-items-center gap-3 text-body fw-semibold hover-bg-secondary" href="#">
                                <i class="bi bi-person text-muted fs-5"></i> Profile
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item px-4 py-2 d-flex align-items-center gap-3 text-body fw-semibold hover-bg-secondary" href="#">
                                <i class="bi bi-life-preserver text-muted fs-5"></i> Help Center
                            </a>
                        </li>
                        
                        <li><hr class="dropdown-divider opacity-10 my-2"></li>
                        
                        <li>
                            <a class="dropdown-item px-4 py-2 d-flex align-items-center gap-3 text-body fw-semibold hover-bg-secondary" href="#">
                                <i class="bi bi-wallet2 text-muted fs-5"></i> 
                                <span>Wallet Balance : <span class="fw-bold text-success">$5971.67</span></span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item px-4 py-2 d-flex align-items-center gap-3 text-body fw-semibold hover-bg-secondary justify-content-between" href="{{ route('settings') }}">
                                <div class="d-flex align-items-center gap-3">
                                    <i class="bi bi-gear text-muted fs-5"></i> Settings
                                </div>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill fw-bold" style="font-size: 9px;">New</span>
                            </a>
                        </li>
                        
                        <li><hr class="dropdown-divider opacity-10 my-2"></li>
                        
                        <li>
                            <form method="POST" action="{{ route('logout') }}" class="m-0">
                                @csrf
                                <button type="submit" class="dropdown-item px-4 py-2 d-flex align-items-center gap-3 text-danger fw-bold hover-bg-danger-subtle">
                                    <i class="bi bi-box-arrow-right fs-5"></i> Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>

            </div>{{-- /.d-flex ms-auto --}}
        </div>
    </nav>
</header>

<x-add-customer-modal />

<style>
.hover-bg-secondary:hover { background-color: var(--bs-secondary-bg) !important; }
.hover-bg-danger-subtle:hover { background-color: rgba(var(--bs-danger-rgb), 0.1) !important; }
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(var(--bs-body-color-rgb), 0.1); border-radius: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(var(--bs-body-color-rgb), 0.2); }

/* Fix Nav Tab Active States in dropdown */
.nav-tabs-custom .nav-link.active {
    opacity: 1 !important;
    border-bottom: 2px solid white !important;
}
</style>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('headerCart', () => ({
        items: [],
        cartGrandTotal: 0,
        init() {
            this.loadCart();
            this.loadCartTotal();
            this.syncCustomerContext();
            window.addEventListener('storage', (e) => {
                if (e.key === 'ecommerce_create_order_cart') this.loadCart();
                if (e.key === 'ecommerce_create_order_cart_total') this.loadCartTotal();
            });
            window.addEventListener('cart-updated', () => this.loadCart());
            window.addEventListener('cart-total-updated', (e) => {
                if (e.detail !== undefined) {
                    this.cartGrandTotal = parseFloat(e.detail) || 0;
                } else {
                    this.loadCartTotal();
                }
            });
        },
        handleCartClick() {
            if (document.querySelector('[x-data^="createOrderApp"]')) {
                window.dispatchEvent(new CustomEvent('toggle-cart-sidebar'));
                const btn = document.getElementById('cartMenuBtn');
                if (btn && window.bootstrap && window.bootstrap.Dropdown) {
                    const dropdown = window.bootstrap.Dropdown.getInstance(btn);
                    if (dropdown) dropdown.hide();
                }
            } else {
                window.location.href = this.checkoutHref();
            }
        },
        syncCustomerContext() {
            const match = window.location.pathname.match(/^\/customers\/(\d+)(?:\/|$)/);
            if (match && match[1]) {
                localStorage.setItem('ecommerce_active_customer_id', match[1]);
            }
        },
        loadCart() {
            try {
                this.items = JSON.parse(localStorage.getItem('ecommerce_create_order_cart')) || [];
            } catch (e) {
                this.items = [];
            }
        },
        loadCartTotal() {
            this.cartGrandTotal = parseFloat(localStorage.getItem('ecommerce_create_order_cart_total')) || 0;
        },
        checkoutHref() {
            const pathMatch = window.location.pathname.match(/^\/customers\/(\d+)(?:\/|$)/);
            const customerId = (pathMatch && pathMatch[1]) || localStorage.getItem('ecommerce_active_customer_id');
            if (customerId) {
                return `/orders/create?customer_id=${encodeURIComponent(customerId)}&step=review`;
            }
            return '{{ route('orders.create') }}';
        },
        removeItem(index) {
            this.items.splice(index, 1);
            localStorage.setItem('ecommerce_create_order_cart', JSON.stringify(this.items));
            window.dispatchEvent(new CustomEvent('cart-updated'));
        }
    }));



    window.notificationApp = function(initialActivities, initialCount) {
        return {
            activities: initialActivities || [],
            count: initialCount || 0,
            init() {
                // Periodically fetch updates every 3 seconds for instant-like feel
                setInterval(() => {
                    this.fetchActivities();
                }, 3000);
            },
            fetchActivities() {
                fetch('/api/activities/recent', {
                    headers: { 
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    },
                    credentials: 'same-origin'
                })
                .then(res => res.json())
                .then(data => {
                    if (data && data.activities) {
                        this.activities = data.activities;
                        this.count = data.count;
                    }
                })
                .catch(err => console.error('Failed to fetch activities', err));
            },
            markAsRead(id) {
                if (id === 'all') {
                    this.activities.forEach(a => a.is_read = true);
                    this.count = 0;
                } else {
                    const activity = this.activities.find(a => a.id === id);
                    if (activity && !activity.is_read) {
                        activity.is_read = true;
                        this.count = Math.max(0, this.count - 1);
                    }
                }
                
                fetch(`/api/activities/${id}/read`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                }).catch(err => console.error('Failed to mark as read', err));
            }
        };
    };
});
</script>
@endpush
