@php
    $current = request()->route() ? request()->route()->getName() : 'dashboard';
@endphp

<!-- Sidebar -->
<aside class="admin-sidebar" id="admin-sidebar">
    <div class="sidebar-search">
        <!-- Sidebar Global Search -->
        <div class="position-relative" x-data="{
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
            <div class="position-relative">
                <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary small"></i>
                <input type="text" 
                       class="form-control form-control-sm ps-5 rounded-3 py-2" 
                       placeholder="Search tabs..." 
                       x-model="searchQuery"
                       @keydown.escape="clearSearch()">
                <button x-show="searchQuery" 
                        @click="clearSearch()" 
                        class="btn btn-link btn-sm position-absolute top-50 end-0 translate-middle-y text-muted me-2 p-0 border-0" 
                        type="button"
                        style="line-height: 1;">
                    <i class="bi bi-x-lg text-secondary"></i>
                </button>
            </div>
            
            <!-- Search Results Overlay -->
            <div x-show="searchQuery && filteredItems.length > 0" 
                 class="position-absolute bg-body border rounded shadow-lg p-2 mt-1" 
                 style="z-index: 1050; left: 0; right: 0; max-height: 250px; overflow-y: auto;"
                 x-cloak>
                <template x-for="item in filteredItems" :key="item.path">
                    <a :href="item.path" 
                       class="dropdown-item py-2 px-3 rounded d-flex align-items-center gap-2"
                       style="font-size: 0.825rem; transition: background-color 0.15s ease;"
                       @click="clearSearch()">
                        <i :class="item.icon" class="text-primary small"></i>
                        <span x-text="item.name"></span>
                        <span class="badge text-bg-secondary ms-auto" style="font-size: 0.65rem;" x-text="item.group"></span>
                    </a>
                </template>
            </div>
            <div x-show="searchQuery && filteredItems.length === 0" 
                 class="position-absolute bg-body border rounded shadow p-3 mt-1 text-center text-muted small" 
                 style="z-index: 1050; left: 0; right: 0;"
                 x-cloak>
                No tabs found
            </div>
        </div>
    </div>
    
    <div class="sidebar-content">

        <nav class="sidebar-nav">
            <ul class="nav flex-column gap-1">

                {{-- ── MAIN ───────────────────────────────────────── --}}
                <li class="nav-item sidebar-section-label">
                    <small class="text-muted px-3 text-uppercase fw-bold">Main</small>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ $current === 'dashboard' ? 'active' : '' }}" href="{{ route('dashboard') }}">
                        <i class="bi bi-grid-1x2-fill"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $current === 'analytics' ? 'active' : '' }}" href="{{ route('analytics') }}">
                        <i class="bi bi-bar-chart-line-fill"></i>
                        <span>Analytics</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $current === 'reports' ? 'active' : '' }}" href="{{ route('reports') }}">
                        <i class="bi bi-file-earmark-bar-graph-fill"></i>
                        <span>Reports</span>
                    </a>
                </li>

                {{-- ── ENTERPRISE SECTIONS ─────────────────────────── --}}
                <li class="nav-item sidebar-section-label mt-3">
                    <small class="text-muted px-3 text-uppercase fw-bold">Enterprise</small>
                </li>

                {{-- Sales & Marketing Dropdown --}}
                <li class="nav-item">
                    <a class="nav-link {{ $current === 'orders' || $current === 'promotions.coupons' || $current === 'promotions.offers' ? 'active' : '' }}"
                       href="#"
                       data-bs-toggle="collapse"
                       data-bs-target="#salesSubmenu"
                       aria-expanded="{{ $current === 'orders' || $current === 'promotions.coupons' || $current === 'promotions.offers' ? 'true' : 'false' }}"
                       aria-controls="salesSubmenu">
                        <i class="bi bi-shop"></i>
                        <span>Sales &amp; Marketing</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse {{ $current === 'orders' || $current === 'promotions.coupons' || $current === 'promotions.offers' ? 'show' : '' }}" id="salesSubmenu">
                        <ul class="nav nav-submenu">
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'orders' ? 'active' : '' }}" href="{{ route('orders') }}">
                                    <i class="bi bi-bag-check-fill"></i>
                                    <span>Orders</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'promotions.coupons' ? 'active' : '' }}" href="{{ route('promotions.coupons') }}">
                                    <i class="bi bi-ticket-perforated-fill"></i>
                                    <span>Coupon Codes</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'promotions.offers' ? 'active' : '' }}" href="{{ route('promotions.offers') }}">
                                    <i class="bi bi-star-fill"></i>
                                    <span>Offers &amp; Deals</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                {{-- Billing & Payments Dropdown --}}
                <li class="nav-item">
                    <a class="nav-link {{ Str::startsWith($current, 'returns') || Str::startsWith($current, 'refunds') || Str::startsWith($current, 'payments') || Str::startsWith($current, 'invoices') ? 'active' : '' }}"
                       href="#"
                       data-bs-toggle="collapse"
                       data-bs-target="#billingSubmenu"
                       aria-expanded="{{ Str::startsWith($current, 'returns') || Str::startsWith($current, 'refunds') || Str::startsWith($current, 'payments') || Str::startsWith($current, 'invoices') ? 'true' : 'false' }}"
                       aria-controls="billingSubmenu">
                        <i class="bi bi-cash-stack"></i>
                        <span>Billing &amp; Payments</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse {{ Str::startsWith($current, 'returns') || Str::startsWith($current, 'refunds') || Str::startsWith($current, 'payments') || Str::startsWith($current, 'invoices') ? 'show' : '' }}" id="billingSubmenu">
                        <ul class="nav nav-submenu">
                            <li class="nav-item">
                                <a class="nav-link {{ Str::startsWith($current, 'invoices') ? 'active' : '' }}" href="{{ route('invoices.index') }}">
                                    <i class="bi bi-receipt"></i>
                                    <span>Invoices</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ Str::startsWith($current, 'payments') ? 'active' : '' }}" href="{{ route('payments.index') }}">
                                    <i class="bi bi-credit-card"></i>
                                    <span>Payments</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ Str::startsWith($current, 'refunds') ? 'active' : '' }}" href="{{ route('refunds.index') }}">
                                    <i class="bi bi-cash-coin"></i>
                                    <span>Refunds</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ Str::startsWith($current, 'returns') ? 'active' : '' }}" href="{{ route('returns.index') }}">
                                    <i class="bi bi-arrow-return-left"></i>
                                    <span>Returns</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                {{-- Logistics & Warehouses Dropdown --}}
                <li class="nav-item">
                    <a class="nav-link {{ Str::startsWith($current, 'shipping') || $current === 'catalog.warehouses' ? 'active' : '' }}"
                       href="#"
                       data-bs-toggle="collapse"
                       data-bs-target="#shippingSubmenu"
                       aria-expanded="{{ Str::startsWith($current, 'shipping') || $current === 'catalog.warehouses' ? 'true' : 'false' }}"
                       aria-controls="shippingSubmenu">
                        <i class="bi bi-truck"></i>
                        <span>Logistics &amp; Warehouses</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse {{ Str::startsWith($current, 'shipping') || $current === 'catalog.warehouses' ? 'show' : '' }}" id="shippingSubmenu">
                        <ul class="nav nav-submenu">
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'shipping.shipments' ? 'active' : '' }}" href="{{ route('shipping.shipments') }}">
                                    <i class="bi bi-geo-alt-fill"></i>
                                    <span>Shipments &amp; Tracking</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'shipping.services' ? 'active' : '' }}" href="{{ route('shipping.services') }}">
                                    <i class="bi bi-gear-wide-connected"></i>
                                    <span>Shipping Services</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'catalog.warehouses' ? 'active' : '' }}" href="{{ route('catalog.warehouses') }}">
                                    <i class="bi bi-buildings-fill"></i>
                                    <span>Warehouses</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                {{-- Inventory & Stock Dropdown --}}
                <li class="nav-item">
                    <a class="nav-link {{ Str::startsWith($current, 'inventory') ? 'active' : '' }}"
                       href="#"
                       data-bs-toggle="collapse"
                       data-bs-target="#inventorySubmenu"
                       aria-expanded="{{ Str::startsWith($current, 'inventory') ? 'true' : 'false' }}"
                       aria-controls="inventorySubmenu">
                        <i class="bi bi-archive-fill"></i>
                        <span>Inventory &amp; Stock</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse {{ Str::startsWith($current, 'inventory') ? 'show' : '' }}" id="inventorySubmenu">
                        <ul class="nav nav-submenu">
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'inventory.stock-management' ? 'active' : '' }}" href="{{ route('inventory.stock-management') }}">
                                    <i class="bi bi-box-seam-fill"></i>
                                    <span>Stock Levels</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'inventory.stock-transfers' ? 'active' : '' }}" href="{{ route('inventory.stock-transfers') }}">
                                    <i class="bi bi-arrow-left-right"></i>
                                    <span>Stock Transfers</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'inventory.adjustments' ? 'active' : '' }}" href="{{ route('inventory.adjustments') }}">
                                    <i class="bi bi-sliders2"></i>
                                    <span>Adjustments</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                {{-- Catalog Management Dropdown --}}
                <li class="nav-item">
                    <a class="nav-link {{ Str::startsWith($current, 'catalog') && $current !== 'catalog.warehouses' ? 'active' : '' }}"
                       href="#"
                       data-bs-toggle="collapse"
                       data-bs-target="#catalogSubmenu"
                       aria-expanded="{{ Str::startsWith($current, 'catalog') && $current !== 'catalog.warehouses' ? 'true' : 'false' }}"
                       aria-controls="catalogSubmenu">
                        <i class="bi bi-shop-window"></i>
                        <span>Catalog Management</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse {{ Str::startsWith($current, 'catalog') && $current !== 'catalog.warehouses' ? 'show' : '' }}" id="catalogSubmenu">
                        <ul class="nav nav-submenu">
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'catalog.products' ? 'active' : '' }}" href="{{ route('catalog.products') }}">
                                    <i class="bi bi-box-seam-fill"></i>
                                    <span>Products</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'catalog.categories' ? 'active' : '' }}" href="{{ route('catalog.categories') }}">
                                    <i class="bi bi-diagram-3-fill"></i>
                                    <span>Categories</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'catalog.brands' ? 'active' : '' }}" href="{{ route('catalog.brands') }}">
                                    <i class="bi bi-patch-check-fill"></i>
                                    <span>Brands</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'catalog.attributes' ? 'active' : '' }}" href="{{ route('catalog.attributes') }}">
                                    <i class="bi bi-sliders2"></i>
                                    <span>Attributes</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'catalog.uom' ? 'active' : '' }}" href="{{ route('catalog.uom') }}">
                                    <i class="bi bi-rulers"></i>
                                    <span>Units of Measure</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'catalog.tax-rates' ? 'active' : '' }}" href="{{ route('catalog.tax-rates') }}">
                                    <i class="bi bi-percent"></i>
                                    <span>Tax Rates</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'catalog.hsn-codes' ? 'active' : '' }}" href="{{ route('catalog.hsn-codes') }}">
                                    <i class="bi bi-upc-scan"></i>
                                    <span>HSN Codes</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                {{-- ── PEOPLE & ADMIN ─────────────────────────────── --}}
                <li class="nav-item sidebar-section-label mt-3">
                    <small class="text-muted px-3 text-uppercase fw-bold">Administration</small>
                </li>

                {{-- People & Admin Dropdown --}}
                <li class="nav-item">
                    <a class="nav-link {{ $current === 'users' || $current === 'roles-permissions' || $current === 'customers' || $current === 'villages' ? 'active' : '' }}"
                       href="#"
                       data-bs-toggle="collapse"
                       data-bs-target="#peopleSubmenu"
                       aria-expanded="{{ $current === 'users' || $current === 'roles-permissions' || $current === 'customers' || $current === 'villages' ? 'true' : 'false' }}"
                       aria-controls="peopleSubmenu">
                        <i class="bi bi-people-fill"></i>
                        <span>People &amp; Admin</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse {{ $current === 'users' || $current === 'roles-permissions' || $current === 'customers' || $current === 'villages' ? 'show' : '' }}" id="peopleSubmenu">
                        <ul class="nav nav-submenu">
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'users' ? 'active' : '' }}" href="{{ route('users') }}">
                                    <i class="bi bi-person-fill-gear"></i>
                                    <span>Users</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'roles-permissions' ? 'active' : '' }}" href="{{ route('roles-permissions') }}">
                                    <i class="bi bi-shield-lock-fill"></i>
                                    <span>Roles &amp; Permissions</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'customers' ? 'active' : '' }}" href="{{ route('customers') }}">
                                    <i class="bi bi-person-lines-fill"></i>
                                    <span>Customers</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'villages' ? 'active' : '' }}" href="{{ route('villages') }}">
                                    <i class="bi bi-geo-alt-fill"></i>
                                    <span>Villages</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                {{-- Utilities & Workspace Dropdown --}}
                <li class="nav-item">
                    <a class="nav-link {{ $current === 'messages' || $current === 'calendar' || $current === 'files' || $current === 'forms' || Str::startsWith($current, 'elements') || $current === 'settings' || $current === 'security' || $current === 'help' ? 'active' : '' }}"
                       href="#"
                       data-bs-toggle="collapse"
                       data-bs-target="#toolsSubmenu"
                       aria-expanded="{{ $current === 'messages' || $current === 'calendar' || $current === 'files' || $current === 'forms' || Str::startsWith($current, 'elements') || $current === 'settings' || $current === 'security' || $current === 'help' ? 'true' : 'false' }}"
                       aria-controls="toolsSubmenu">
                        <i class="bi bi-wrench-adjustable-circle-fill"></i>
                        <span>Utilities &amp; Workspace</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse {{ $current === 'messages' || $current === 'calendar' || $current === 'files' || $current === 'forms' || Str::startsWith($current, 'elements') || $current === 'settings' || $current === 'security' || $current === 'help' ? 'show' : '' }}" id="toolsSubmenu">
                        <ul class="nav nav-submenu">
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'messages' ? 'active' : '' }}" href="{{ route('messages') }}">
                                    <i class="bi bi-chat-dots-fill"></i>
                                    <span>Messages</span>
                                    <span class="badge bg-danger rounded-pill ms-auto">3</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'calendar' ? 'active' : '' }}" href="{{ route('calendar') }}">
                                    <i class="bi bi-calendar-week-fill"></i>
                                    <span>Calendar</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'files' ? 'active' : '' }}" href="{{ route('files') }}">
                                    <i class="bi bi-folder2-open"></i>
                                    <span>Files</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'forms' ? 'active' : '' }}" href="{{ route('forms') }}">
                                    <i class="bi bi-ui-checks-grid"></i>
                                    <span>Forms</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ Str::startsWith($current, 'elements') ? 'active' : '' }}" href="{{ route('elements') }}">
                                    <i class="bi bi-puzzle-fill"></i>
                                    <span>UI Elements</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'settings' ? 'active' : '' }}" href="{{ route('settings') }}">
                                    <i class="bi bi-gear-fill"></i>
                                    <span>Settings</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'security' ? 'active' : '' }}" href="{{ route('security') }}">
                                    <i class="bi bi-shield-fill-check"></i>
                                    <span>Security</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/docs/api" target="_blank">
                                    <i class="bi bi-file-earmark-code-fill"></i>
                                    <span>API Documentation</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'help' ? 'active' : '' }}" href="{{ route('help') }}">
                                    <i class="bi bi-question-circle-fill"></i>
                                    <span>Help &amp; Support</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

            </ul>
        </nav>
    </div>
</aside>
