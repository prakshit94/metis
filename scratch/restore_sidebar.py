content = """@php
    $current = request()->route() ? request()->route()->getName() : 'dashboard';
@endphp

<!-- Sidebar -->
<aside class="admin-sidebar" id="admin-sidebar">
    <div class="sidebar-content">

        <div class="px-3 mb-2 mt-2 d-flex justify-content-between align-items-center">
            <span class="text-muted small fw-bold text-uppercase">Navigation</span>
            <button class="btn btn-sm btn-link text-muted p-0 text-decoration-none" id="toggle-all-menus" title="Toggle all menus">
                <i class="bi bi-arrows-expand"></i>
            </button>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var btn = document.getElementById('toggle-all-menus');
                if(btn) {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        var collapses = document.querySelectorAll('.admin-sidebar .collapse');
                        var anyOpen = Array.from(collapses).some(c => c.classList.contains('show'));
                        
                        collapses.forEach(c => {
                            var bsCollapse = window.bootstrap.Collapse.getInstance(c);
                            if (!bsCollapse) {
                                bsCollapse = new window.bootstrap.Collapse(c, {toggle: false});
                            }
                            if (anyOpen) {
                                bsCollapse.hide();
                            } else {
                                bsCollapse.show();
                            }
                        });
                    });
                }
            });
        </script>

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

                {{-- ── COMMERCE & SALES ─────────────────────────── --}}
                <li class="nav-item sidebar-section-label mt-3">
                    <small class="text-muted px-3 text-uppercase fw-bold">Commerce &amp; Sales</small>
                </li>

                {{-- Order Management Dropdown --}}
                @canany(['orders.view', 'invoices.view', 'payments.view', 'returns.view', 'refunds.view', 'credit-notes.view'])
                <li class="nav-item">
                    <a class="nav-link {{ in_array($current, ['orders', 'invoices.index', 'payments.index', 'returns.index', 'refunds.index', 'credit-notes.index']) ? 'active' : 'collapsed' }}"
                       href="#"
                       data-bs-toggle="collapse"
                       data-bs-target="#orderManagementSubmenu"
                       aria-expanded="{{ in_array($current, ['orders', 'invoices.index', 'payments.index', 'returns.index', 'refunds.index', 'credit-notes.index']) ? 'true' : 'false' }}"
                       aria-controls="orderManagementSubmenu">
                        <i class="bi bi-cart-check-fill"></i>
                        <span>Order Management</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse {{ in_array($current, ['orders', 'invoices.index', 'payments.index', 'returns.index', 'refunds.index', 'credit-notes.index']) ? 'show' : '' }}" id="orderManagementSubmenu">
                        <ul class="nav nav-submenu">
                            @can('orders.view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'orders' ? 'active' : '' }}" href="{{ route('orders') }}">
                                    <i class="bi bi-bag-check-fill"></i>
                                    <span>Orders</span>
                                </a>
                            </li>
                            @endcan
                            @can('invoices.view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'invoices.index' ? 'active' : '' }}" href="{{ route('invoices.index') }}">
                                    <i class="bi bi-receipt"></i>
                                    <span>Invoices</span>
                                </a>
                            </li>
                            @endcan
                            @can('payments.view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'payments.index' ? 'active' : '' }}" href="{{ route('payments.index') }}">
                                    <i class="bi bi-credit-card"></i>
                                    <span>Payments</span>
                                </a>
                            </li>
                            @endcan
                            @can('returns.view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'returns.index' ? 'active' : '' }}" href="{{ route('returns.index') }}">
                                    <i class="bi bi-arrow-return-left"></i>
                                    <span>Returns</span>
                                </a>
                            </li>
                            @endcan
                            @can('refunds.view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'refunds.index' ? 'active' : '' }}" href="{{ route('refunds.index') }}">
                                    <i class="bi bi-cash-coin"></i>
                                    <span>Refunds</span>
                                </a>
                            </li>
                            @endcan
                            @can('credit-notes.view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'credit-notes.index' ? 'active' : '' }}" href="{{ route('credit-notes.index') }}">
                                    <i class="bi bi-receipt-cutoff"></i>
                                    <span>Credit Notes</span>
                                </a>
                            </li>
                            @endcan
                        </ul>
                    </div>
                </li>
                @endcanany

                {{-- Marketing & Promotions Dropdown --}}
                @canany(['coupon-view', 'promotions-view'])
                <li class="nav-item">
                    <a class="nav-link {{ in_array($current, ['promotions.coupons', 'promotions.offers', 'referrals.programs.index']) ? 'active' : 'collapsed' }}"
                       href="#"
                       data-bs-toggle="collapse"
                       data-bs-target="#marketingSubmenu"
                       aria-expanded="{{ in_array($current, ['promotions.coupons', 'promotions.offers', 'referrals.programs.index']) ? 'true' : 'false' }}"
                       aria-controls="marketingSubmenu">
                        <i class="bi bi-megaphone-fill"></i>
                        <span>Marketing &amp; Promos</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse {{ in_array($current, ['promotions.coupons', 'promotions.offers', 'referrals.programs.index']) ? 'show' : '' }}" id="marketingSubmenu">
                        <ul class="nav nav-submenu">
                            @can('promotions-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'promotions.offers' ? 'active' : '' }}" href="{{ route('promotions.offers') }}">
                                    <i class="bi bi-star-fill"></i>
                                    <span>Offers &amp; Deals</span>
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
                                <a class="nav-link {{ $current === 'referrals.programs.index' ? 'active' : '' }}" href="{{ route('referrals.programs.index') }}">
                                    <i class="bi bi-diagram-3-fill"></i>
                                    <span>Referral Programs</span>
                                </a>
                            </li>
                            @endcan
                        </ul>
                    </div>
                </li>
                @endcanany

                {{-- ── SUPPLY CHAIN ─────────────────────────── --}}
                <li class="nav-item sidebar-section-label mt-3">
                    <small class="text-muted px-3 text-uppercase fw-bold">Supply Chain</small>
                </li>

                {{-- Procurement & Inventory Dropdown --}}
                @canany(['purchaseorder-view', 'goodsreceipt-view', 'stockmanagement-view', 'stocktransfer-view', 'inventoryadjustment-view'])
                <li class="nav-item">
                    <a class="nav-link {{ in_array($current, ['procurement.purchase-orders.index', 'procurement.goods-receipts.index', 'inventory.stock-management', 'inventory.stock-transfers', 'inventory.adjustments']) ? 'active' : 'collapsed' }}"
                       href="#"
                       data-bs-toggle="collapse"
                       data-bs-target="#inventorySubmenu"
                       aria-expanded="{{ in_array($current, ['procurement.purchase-orders.index', 'procurement.goods-receipts.index', 'inventory.stock-management', 'inventory.stock-transfers', 'inventory.adjustments']) ? 'true' : 'false' }}"
                       aria-controls="inventorySubmenu">
                        <i class="bi bi-archive-fill"></i>
                        <span>Procurement &amp; Inventory</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse {{ in_array($current, ['procurement.purchase-orders.index', 'procurement.goods-receipts.index', 'inventory.stock-management', 'inventory.stock-transfers', 'inventory.adjustments']) ? 'show' : '' }}" id="inventorySubmenu">
                        <ul class="nav nav-submenu">
                            @can('purchaseorder-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'procurement.purchase-orders.index' ? 'active' : '' }}" href="{{ route('procurement.purchase-orders.index') }}">
                                    <i class="bi bi-receipt"></i>
                                    <span>Purchase Orders</span>
                                </a>
                            </li>
                            @endcan
                            @can('goodsreceipt-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'procurement.goods-receipts.index' ? 'active' : '' }}" href="{{ route('procurement.goods-receipts.index') }}">
                                    <i class="bi bi-clipboard-check"></i>
                                    <span>Goods Receipts</span>
                                </a>
                            </li>
                            @endcan
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

                {{-- Logistics & Warehouses Dropdown --}}
                @canany(['shipping-view', 'warehouse-view'])
                <li class="nav-item">
                    <a class="nav-link {{ in_array($current, ['shipping.shipments', 'shipping.services', 'catalog.warehouses']) ? 'active' : 'collapsed' }}"
                       href="#"
                       data-bs-toggle="collapse"
                       data-bs-target="#shippingSubmenu"
                       aria-expanded="{{ in_array($current, ['shipping.shipments', 'shipping.services', 'catalog.warehouses']) ? 'true' : 'false' }}"
                       aria-controls="shippingSubmenu">
                        <i class="bi bi-truck"></i>
                        <span>Logistics &amp; Warehouses</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse {{ in_array($current, ['shipping.shipments', 'shipping.services', 'catalog.warehouses']) ? 'show' : '' }}" id="shippingSubmenu">
                        <ul class="nav nav-submenu">
                            @can('warehouse-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'catalog.warehouses' ? 'active' : '' }}" href="{{ route('catalog.warehouses') }}">
                                    <i class="bi bi-buildings-fill"></i>
                                    <span>Warehouses</span>
                                </a>
                            </li>
                            @endcan
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
                        </ul>
                    </div>
                </li>
                @endcanany

                {{-- Catalog Management Dropdown --}}
                @canany(['product-view', 'category-view', 'brand-view', 'productattribute-view', 'unitofmeasure-view', 'taxrate-view', 'hsncode-view'])
                <li class="nav-item">
                    <a class="nav-link {{ in_array($current, ['catalog.products', 'catalog.categories', 'catalog.brands', 'catalog.attributes', 'catalog.uom', 'catalog.tax-rates', 'catalog.hsn-codes']) ? 'active' : 'collapsed' }}"
                       href="#"
                       data-bs-toggle="collapse"
                       data-bs-target="#catalogSubmenu"
                       aria-expanded="{{ in_array($current, ['catalog.products', 'catalog.categories', 'catalog.brands', 'catalog.attributes', 'catalog.uom', 'catalog.tax-rates', 'catalog.hsn-codes']) ? 'true' : 'false' }}"
                       aria-controls="catalogSubmenu">
                        <i class="bi bi-shop-window"></i>
                        <span>Catalog Management</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse {{ in_array($current, ['catalog.products', 'catalog.categories', 'catalog.brands', 'catalog.attributes', 'catalog.uom', 'catalog.tax-rates', 'catalog.hsn-codes']) ? 'show' : '' }}" id="catalogSubmenu">
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

                {{-- ── ADMINISTRATION ─────────────────────────────── --}}
                <li class="nav-item sidebar-section-label mt-3">
                    <small class="text-muted px-3 text-uppercase fw-bold">Administration</small>
                </li>

                {{-- CRM & People Dropdown --}}
                @canany(['user-view', 'role-view'])
                <li class="nav-item">
                    <a class="nav-link {{ in_array($current, ['users', 'roles-permissions', 'customers', 'customer-settings.index']) ? 'active' : 'collapsed' }}"
                       href="#"
                       data-bs-toggle="collapse"
                       data-bs-target="#crmSubmenu"
                       aria-expanded="{{ in_array($current, ['users', 'roles-permissions', 'customers', 'customer-settings.index']) ? 'true' : 'false' }}"
                       aria-controls="crmSubmenu">
                        <i class="bi bi-people-fill"></i>
                        <span>CRM &amp; People</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse {{ in_array($current, ['users', 'roles-permissions', 'customers', 'customer-settings.index']) ? 'show' : '' }}" id="crmSubmenu">
                        <ul class="nav nav-submenu">
                            @role('Super Admin')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'customers' ? 'active' : '' }}" href="{{ route('customers') }}">
                                    <i class="bi bi-person-lines-fill"></i>
                                    <span>Customers</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'customer-settings.index' ? 'active' : '' }}" href="{{ route('customer-settings.index') }}">
                                    <i class="bi bi-gear-wide-connected"></i>
                                    <span>Customer Settings</span>
                                </a>
                            </li>
                            @endrole
                            @can('user-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'users' ? 'active' : '' }}" href="{{ route('users') }}">
                                    <i class="bi bi-person-fill-gear"></i>
                                    <span>System Users</span>
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
                        </ul>
                    </div>
                </li>
                @endcanany

                {{-- System Settings Dropdown --}}
                @canany(['village-view', 'orderreason-view', 'settings-view'])
                <li class="nav-item">
                    <a class="nav-link {{ in_array($current, ['villages', 'order.reasons', 'call-tags.index', 'admin.audit-logs.index']) ? 'active' : 'collapsed' }}"
                       href="#"
                       data-bs-toggle="collapse"
                       data-bs-target="#systemSubmenu"
                       aria-expanded="{{ in_array($current, ['villages', 'order.reasons', 'call-tags.index', 'admin.audit-logs.index']) ? 'true' : 'false' }}"
                       aria-controls="systemSubmenu">
                        <i class="bi bi-gear-fill"></i>
                        <span>System Settings</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse {{ in_array($current, ['villages', 'order.reasons', 'call-tags.index', 'admin.audit-logs.index']) ? 'show' : '' }}" id="systemSubmenu">
                        <ul class="nav nav-submenu">
                            @can('village-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'villages' ? 'active' : '' }}" href="{{ route('villages') }}">
                                    <i class="bi bi-geo-alt-fill"></i>
                                    <span>Villages</span>
                                </a>
                            </li>
                            @endcan
                            @can('orderreason-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'order.reasons' ? 'active' : '' }}" href="{{ route('order.reasons') }}">
                                    <i class="bi bi-list-task"></i>
                                    <span>Order Reasons</span>
                                </a>
                            </li>
                            @endcan
                            @can('settings-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'call-tags.index' ? 'active' : '' }}" href="{{ route('call-tags.index') }}">
                                    <i class="bi bi-tags"></i>
                                    <span>Call Tags</span>
                                </a>
                            </li>
                            @endcan
                            @role('Super Admin')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'admin.audit-logs.index' ? 'active' : '' }}" href="{{ route('admin.audit-logs.index') }}">
                                    <i class="bi bi-journal-medical"></i>
                                    <span>Audit Logs</span>
                                </a>
                            </li>
                            @endrole
                        </ul>
                    </div>
                </li>
                @endcanany

                {{-- ── WORKSPACE ─────────────────────────────── --}}
                <li class="nav-item sidebar-section-label mt-3">
                    <small class="text-muted px-3 text-uppercase fw-bold">Workspace</small>
                </li>

                {{-- Utilities & Workspace Dropdown --}}
                <li class="nav-item">
                    <a class="nav-link {{ in_array($current, ['chat.index', 'messages', 'calendar', 'files', 'forms', 'settings', 'security', 'help']) || Str::startsWith($current, 'elements') ? 'active' : 'collapsed' }}"
                       href="#"
                       data-bs-toggle="collapse"
                       data-bs-target="#toolsSubmenu"
                       aria-expanded="{{ in_array($current, ['chat.index', 'messages', 'calendar', 'files', 'forms', 'settings', 'security', 'help']) || Str::startsWith($current, 'elements') ? 'true' : 'false' }}"
                       aria-controls="toolsSubmenu">
                        <i class="bi bi-wrench-adjustable-circle-fill"></i>
                        <span>Utilities &amp; Tools</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse {{ in_array($current, ['chat.index', 'messages', 'calendar', 'files', 'forms', 'settings', 'security', 'help']) || Str::startsWith($current, 'elements') ? 'show' : '' }}" id="toolsSubmenu">
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
"""

with open('resources/views/components/sidebar.blade.php', 'w') as f:
    f.write(content)
