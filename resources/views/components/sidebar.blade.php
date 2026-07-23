@php
    $current = request()->route() ? request()->route()->getName() : 'dashboard';
@endphp

<!-- Sidebar -->
<aside class="admin-sidebar" id="admin-sidebar">
    <div class="sidebar-content">

        <nav class="sidebar-nav">
            <ul class="nav flex-column gap-1">

                {{-- ── MAIN ───────────────────────────────────────── --}}
                <li class="nav-item sidebar-section-label">
                    <small class="text-muted px-3 text-uppercase fw-bold">Main</small>
                </li>

                @can('dashboard-view')
                            <li class="nav-item">
                    <a class="nav-link {{ $current === 'dashboard' ? 'active' : '' }}" href="{{ route('dashboard') }}">
                        <i class="bi bi-grid-1x2-fill"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                            @endcan
                @can('analytics-view')
                            <li class="nav-item">
                    <a class="nav-link {{ $current === 'analytics' ? 'active' : '' }}" href="{{ route('analytics') }}">
                        <i class="bi bi-bar-chart-line-fill"></i>
                        <span>Analytics</span>
                    </a>
                </li>
                            @endcan
                @can('reports-view')
                            <li class="nav-item">
                    <a class="nav-link {{ $current === 'reports' ? 'active' : '' }}" href="{{ route('reports') }}">
                        <i class="bi bi-file-earmark-bar-graph-fill"></i>
                        <span>Reports</span>
                    </a>
                </li>
                            @endcan

                {{-- ── ENTERPRISE SECTIONS ─────────────────────────── --}}
                <li class="nav-item sidebar-section-label mt-3">
                    <small class="text-muted px-3 text-uppercase fw-bold">Enterprise</small>
                </li>

                {{-- Sales & Marketing Dropdown --}}
                @canany(['orders.view', 'coupon-view', 'promotions-view'])
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
                            @can('orders.view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'orders' ? 'active' : '' }}" href="{{ route('orders') }}">
                                    <i class="bi bi-bag-check-fill"></i>
                                    <span>Orders</span>
                                </a>
                            </li>
                            @endcan
                            @can('coupon-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'promotions.coupons' ? 'active' : '' }}" href="{{ route('promotions.coupons') }}">
                                    <i class="bi bi-ticket-perforated-fill"></i>
                                    <span>Coupon Codes</span>
                                </a>
                            </li>
                            @endcan
                            @can('promotions-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'promotions.offers' ? 'active' : '' }}" href="{{ route('promotions.offers') }}">
                                    <i class="bi bi-star-fill"></i>
                                    <span>Offers &amp; Deals</span>
                                </a>
                            </li>
                            @endcan
                        </ul>
                    </div>
                </li>
                @endcanany

                {{-- Billing & Payments Dropdown --}}
                @canany(['invoices.view', 'payments.view', 'refunds.view', 'returns.view'])
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
                            @can('invoices.view')
                            <li class="nav-item">
                                <a class="nav-link {{ Str::startsWith($current, 'invoices') ? 'active' : '' }}" href="{{ route('invoices.index') }}">
                                    <i class="bi bi-receipt"></i>
                                    <span>Invoices</span>
                                </a>
                            </li>
                            @endcan
                            @can('payments.view')
                            <li class="nav-item">
                                <a class="nav-link {{ Str::startsWith($current, 'payments') ? 'active' : '' }}" href="{{ route('payments.index') }}">
                                    <i class="bi bi-credit-card"></i>
                                    <span>Payments</span>
                                </a>
                            </li>
                            @endcan
                            @can('refunds.view')
                            <li class="nav-item">
                                <a class="nav-link {{ Str::startsWith($current, 'refunds') ? 'active' : '' }}" href="{{ route('refunds.index') }}">
                                    <i class="bi bi-cash-coin"></i>
                                    <span>Refunds</span>
                                </a>
                            </li>
                            @endcan
                            @can('returns.view')
                            <li class="nav-item">
                                <a class="nav-link {{ Str::startsWith($current, 'returns') ? 'active' : '' }}" href="{{ route('returns.index') }}">
                                    <i class="bi bi-arrow-return-left"></i>
                                    <span>Returns</span>
                                </a>
                            </li>
                            @endcan
                        </ul>
                    </div>
                </li>
                @endcanany

                {{-- Logistics & Warehouses Dropdown --}}
                @canany(['shipping-view', 'warehouse-view'])
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
                            @can('shipping-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'shipping.shipments' ? 'active' : '' }}" href="{{ route('shipping.shipments') }}">
                                    <i class="bi bi-geo-alt-fill"></i>
                                    <span>Shipments &amp; Tracking</span>
                                </a>
                            </li>
                            @endcan
                            @can('shipping-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'shipping.services' ? 'active' : '' }}" href="{{ route('shipping.services') }}">
                                    <i class="bi bi-gear-wide-connected"></i>
                                    <span>Shipping Services</span>
                                </a>
                            </li>
                            @endcan
                            @can('warehouse-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'catalog.warehouses' ? 'active' : '' }}" href="{{ route('catalog.warehouses') }}">
                                    <i class="bi bi-buildings-fill"></i>
                                    <span>Warehouses</span>
                                </a>
                            </li>
                            @endcan
                        </ul>
                    </div>
                </li>
                @endcanany

                {{-- Inventory & Stock Dropdown --}}
                @canany(['stockmanagement-view', 'stocktransfer-view', 'inventoryadjustment-view'])
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
                            @can('stockmanagement-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'inventory.stock-management' ? 'active' : '' }}" href="{{ route('inventory.stock-management') }}">
                                    <i class="bi bi-box-seam-fill"></i>
                                    <span>Stock Levels</span>
                                </a>
                            </li>
                            @endcan
                            @can('stocktransfer-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'inventory.stock-transfers' ? 'active' : '' }}" href="{{ route('inventory.stock-transfers') }}">
                                    <i class="bi bi-arrow-left-right"></i>
                                    <span>Stock Transfers</span>
                                </a>
                            </li>
                            @endcan
                            @can('inventoryadjustment-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'inventory.adjustments' ? 'active' : '' }}" href="{{ route('inventory.adjustments') }}">
                                    <i class="bi bi-sliders2"></i>
                                    <span>Adjustments</span>
                                </a>
                            </li>
                            @endcan
                        </ul>
                    </div>
                </li>
                @endcanany

                {{-- Catalog Management Dropdown --}}
                @canany(['product-view', 'category-view', 'brand-view', 'productattribute-view', 'unitofmeasure-view', 'taxrate-view', 'hsncode-view'])
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
                            @can('product-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'catalog.products' ? 'active' : '' }}" href="{{ route('catalog.products') }}">
                                    <i class="bi bi-box-seam-fill"></i>
                                    <span>Products</span>
                                </a>
                            </li>
                            @endcan
                            @can('category-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'catalog.categories' ? 'active' : '' }}" href="{{ route('catalog.categories') }}">
                                    <i class="bi bi-diagram-3-fill"></i>
                                    <span>Categories</span>
                                </a>
                            </li>
                            @endcan
                            @can('brand-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'catalog.brands' ? 'active' : '' }}" href="{{ route('catalog.brands') }}">
                                    <i class="bi bi-patch-check-fill"></i>
                                    <span>Brands</span>
                                </a>
                            </li>
                            @endcan
                            @can('productattribute-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'catalog.attributes' ? 'active' : '' }}" href="{{ route('catalog.attributes') }}">
                                    <i class="bi bi-sliders2"></i>
                                    <span>Attributes</span>
                                </a>
                            </li>
                            @endcan
                            @can('unitofmeasure-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'catalog.uom' ? 'active' : '' }}" href="{{ route('catalog.uom') }}">
                                    <i class="bi bi-rulers"></i>
                                    <span>Units of Measure</span>
                                </a>
                            </li>
                            @endcan
                            @can('taxrate-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'catalog.tax-rates' ? 'active' : '' }}" href="{{ route('catalog.tax-rates') }}">
                                    <i class="bi bi-percent"></i>
                                    <span>Tax Rates</span>
                                </a>
                            </li>
                            @endcan
                            @can('hsncode-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'catalog.hsn-codes' ? 'active' : '' }}" href="{{ route('catalog.hsn-codes') }}">
                                    <i class="bi bi-upc-scan"></i>
                                    <span>HSN Codes</span>
                                </a>
                            </li>
                            @endcan
                        </ul>
                    </div>
                </li>
                @endcanany

                {{-- ── PEOPLE & ADMIN ─────────────────────────────── --}}
                <li class="nav-item sidebar-section-label mt-3">
                    <small class="text-muted px-3 text-uppercase fw-bold">Administration</small>
                </li>

                {{-- People & Admin Dropdown --}}
                @canany(['user-view', 'role-view', 'customer-view', 'village-view'])
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
                            @can('user-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'users' ? 'active' : '' }}" href="{{ route('users') }}">
                                    <i class="bi bi-person-fill-gear"></i>
                                    <span>Users</span>
                                </a>
                            </li>
                            @endcan
                            @can('role-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'roles-permissions' ? 'active' : '' }}" href="{{ route('roles-permissions') }}">
                                    <i class="bi bi-shield-lock-fill"></i>
                                    <span>Roles &amp; Permissions</span>
                                </a>
                            </li>
                            @endcan
                            @can('customer-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'customers' ? 'active' : '' }}" href="{{ route('customers') }}">
                                    <i class="bi bi-person-lines-fill"></i>
                                    <span>Customers</span>
                                </a>
                            </li>
                            @endcan
                            @can('village-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'villages' ? 'active' : '' }}" href="{{ route('villages') }}">
                                    <i class="bi bi-geo-alt-fill"></i>
                                    <span>Villages</span>
                                </a>
                            </li>
                            @endcan
                        </ul>
                    </div>
                </li>
                @endcanany

                {{-- Utilities & Workspace Dropdown --}}
                <li class="nav-item">
                    <a class="nav-link {{ $current === 'chat.index' || $current === 'messages' || $current === 'calendar' || $current === 'files' || $current === 'forms' || Str::startsWith($current, 'elements') || $current === 'settings' || $current === 'security' || $current === 'help' ? 'active' : '' }}"
                       href="#"
                       data-bs-toggle="collapse"
                       data-bs-target="#toolsSubmenu"
                       aria-expanded="{{ $current === 'chat.index' || $current === 'messages' || $current === 'calendar' || $current === 'files' || $current === 'forms' || Str::startsWith($current, 'elements') || $current === 'settings' || $current === 'security' || $current === 'help' ? 'true' : 'false' }}"
                       aria-controls="toolsSubmenu">
                        <i class="bi bi-wrench-adjustable-circle-fill"></i>
                        <span>Utilities &amp; Workspace</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse {{ $current === 'chat.index' || $current === 'messages' || $current === 'calendar' || $current === 'files' || $current === 'forms' || Str::startsWith($current, 'elements') || $current === 'settings' || $current === 'security' || $current === 'help' ? 'show' : '' }}" id="toolsSubmenu">
                        <ul class="nav nav-submenu">
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'chat.index' ? 'active' : '' }}" href="{{ route('chat.index') }}">
                                    <i class="bi bi-chat-text-fill"></i>
                                    <span>Team Chat</span>
                                    <span class="badge bg-primary rounded-pill ms-auto">New</span>
                                </a>
                            </li>
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
                            @can('settings-view')
                            <li class="nav-item">
                                <a class="nav-link {{ Str::startsWith($current, 'elements') ? 'active' : '' }}" href="{{ route('elements') }}">
                                    <i class="bi bi-puzzle-fill"></i>
                                    <span>UI Elements</span>
                                </a>
                            </li>
                            @endcan
                            @can('settings-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'settings' ? 'active' : '' }}" href="{{ route('settings') }}">
                                    <i class="bi bi-gear-fill"></i>
                                    <span>Settings</span>
                                </a>
                            </li>
                            @endcan
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
