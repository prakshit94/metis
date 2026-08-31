@php
    $current = request()->route() ? request()->route()->getName() : 'dashboard';
@endphp

<!-- Sidebar -->
<aside class="admin-sidebar" id="admin-sidebar">
    <div class="sidebar-content">

        <div class="px-3 mb-2 mt-2 d-flex justify-content-between align-items-center">
            <span class="text-muted small fw-bold text-uppercase text-truncate pe-2">Navigation</span>
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

                {{-- ── COMMUNICATION (Visible to all) ──────────────── --}}
                @can('chat-view')
                <li class="nav-item">
                    <a class="nav-link {{ $current === 'chat.index' ? 'active' : '' }}" href="{{ route('chat.index') }}">
                        <i class="bi bi-chat-text-fill"></i>
                        <span class="text-truncate flex-grow-1" style="min-width: 0;">Team Chat</span>
                    </a>
                </li>
                @endcan

                {{-- ── MAIN ───────────────────────────────────────── --}}
                @canany(['dashboard-view', 'analytics-view', 'reports-view'])
                <li class="nav-item sidebar-section-label">
                    <small class="text-muted px-3 text-uppercase fw-bold">Main</small>
                </li>
                @endcanany

                @can('dashboard-view')
                <li class="nav-item">
                    <a class="nav-link {{ $current === 'dashboard' ? 'active' : '' }}" href="{{ route('dashboard') }}">
                        <i class="bi bi-grid-1x2-fill"></i>
                        <span class="text-truncate flex-grow-1" style="min-width: 0;">Dashboard</span>
                    </a>
                </li>
                @endcan
                @can('analytics-view')
                <li class="nav-item">
                    <a class="nav-link {{ $current === 'analytics' ? 'active' : '' }}" href="{{ route('analytics') }}">
                        <i class="bi bi-bar-chart-line-fill"></i>
                        <span class="text-truncate flex-grow-1" style="min-width: 0;">Analytics</span>
                    </a>
                </li>
                @endcan
                @can('reports-view')
                <li class="nav-item">
                    <a class="nav-link {{ $current === 'reports' ? 'active' : '' }}" href="{{ route('reports') }}">
                        <i class="bi bi-file-earmark-bar-graph-fill"></i>
                        <span class="text-truncate flex-grow-1" style="min-width: 0;">Reports</span>
                    </a>
                </li>
                @endcan

                {{-- ── COMMERCE & SALES ─────────────────────────── --}}
                @canany(['product-view', 'category-view', 'brand-view', 'productattribute-view', 'unitofmeasure-view', 'taxrate-view', 'hsncode-view', 'orders.view', 'invoices.view', 'payments.view', 'returns.view', 'refunds.view', 'credit-notes.view', 'complaints.view', 'coupon-view', 'promotions-view'])
                <li class="nav-item sidebar-section-label mt-3">
                    <small class="text-muted px-3 text-uppercase fw-bold">Commerce &amp; Sales</small>
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
                        <span class="text-truncate flex-grow-1" style="min-width: 0;">Catalog Management</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse {{ in_array($current, ['catalog.products', 'catalog.categories', 'catalog.brands', 'catalog.attributes', 'catalog.uom', 'catalog.tax-rates', 'catalog.hsn-codes']) ? 'show' : '' }}" id="catalogSubmenu">
                        <ul class="nav nav-submenu">
                            @can('product-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'catalog.products' ? 'active' : '' }}" href="{{ route('catalog.products') }}">
                                    <i class="bi bi-box-seam-fill"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Products</span>
                                </a>
                            </li>
                            @endcan
                            @can('category-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'catalog.categories' ? 'active' : '' }}" href="{{ route('catalog.categories') }}">
                                    <i class="bi bi-diagram-3-fill"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Categories</span>
                                </a>
                            </li>
                            @endcan
                            @can('brand-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'catalog.brands' ? 'active' : '' }}" href="{{ route('catalog.brands') }}">
                                    <i class="bi bi-patch-check-fill"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Brands</span>
                                </a>
                            </li>
                            @endcan
                            @can('productattribute-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'catalog.attributes' ? 'active' : '' }}" href="{{ route('catalog.attributes') }}">
                                    <i class="bi bi-sliders2"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Attributes</span>
                                </a>
                            </li>
                            @endcan
                            @can('unitofmeasure-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'catalog.uom' ? 'active' : '' }}" href="{{ route('catalog.uom') }}">
                                    <i class="bi bi-rulers"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Units of Measure</span>
                                </a>
                            </li>
                            @endcan
                            @can('taxrate-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'catalog.tax-rates' ? 'active' : '' }}" href="{{ route('catalog.tax-rates') }}">
                                    <i class="bi bi-percent"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Tax Rates</span>
                                </a>
                            </li>
                            @endcan
                            @can('hsncode-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'catalog.hsn-codes' ? 'active' : '' }}" href="{{ route('catalog.hsn-codes') }}">
                                    <i class="bi bi-upc-scan"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">HSN Codes</span>
                                </a>
                            </li>
                            @endcan
                        </ul>
                    </div>
                </li>
                @endcanany

                {{-- Order Management Dropdown --}}
                @canany(['orders.view', 'invoices.view', 'payments.view', 'returns.view', 'refunds.view', 'credit-notes.view', 'complaints.view'])
                <li class="nav-item">
                    <a class="nav-link {{ in_array($current, ['orders', 'invoices.index', 'payments.index', 'returns.index', 'refunds.index', 'credit-notes.index', 'complaints.index']) ? 'active' : 'collapsed' }}"
                       href="#"
                       data-bs-toggle="collapse"
                       data-bs-target="#orderManagementSubmenu"
                       aria-expanded="{{ in_array($current, ['orders', 'invoices.index', 'payments.index', 'returns.index', 'refunds.index', 'credit-notes.index', 'complaints.index']) ? 'true' : 'false' }}"
                       aria-controls="orderManagementSubmenu">
                        <i class="bi bi-cart-check-fill"></i>
                        <span class="text-truncate flex-grow-1" style="min-width: 0;">Order Management</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse {{ in_array($current, ['orders', 'invoices.index', 'payments.index', 'returns.index', 'refunds.index', 'credit-notes.index', 'complaints.index']) ? 'show' : '' }}" id="orderManagementSubmenu">
                        <ul class="nav nav-submenu">
                            @can('orders.view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'orders' ? 'active' : '' }}" href="{{ route('orders') }}">
                                    <i class="bi bi-bag-check-fill"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Orders</span>
                                </a>
                            </li>
                            @endcan
                            @can('invoices.view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'invoices.index' ? 'active' : '' }}" href="{{ route('invoices.index') }}">
                                    <i class="bi bi-receipt"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Invoices</span>
                                </a>
                            </li>
                            @endcan
                            @can('payments.view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'payments.index' ? 'active' : '' }}" href="{{ route('payments.index') }}">
                                    <i class="bi bi-credit-card"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Payments</span>
                                </a>
                            </li>
                            @endcan
                            @can('returns.view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'returns.index' ? 'active' : '' }}" href="{{ route('returns.index') }}">
                                    <i class="bi bi-arrow-return-left"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Returns</span>
                                </a>
                            </li>
                            @endcan
                            @can('complaints.view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'complaints.index' ? 'active' : '' }}" href="{{ route('complaints.index') }}">
                                    <i class="bi bi-headset"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Complaints</span>
                                </a>
                            </li>
                            @endcan
                            @can('refunds.view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'refunds.index' ? 'active' : '' }}" href="{{ route('refunds.index') }}">
                                    <i class="bi bi-cash-coin"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Refunds</span>
                                </a>
                            </li>
                            @endcan
                            @can('credit-notes.view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'credit-notes.index' ? 'active' : '' }}" href="{{ route('credit-notes.index') }}">
                                    <i class="bi bi-receipt-cutoff"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Credit Notes</span>
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
                        <span class="text-truncate flex-grow-1" style="min-width: 0;">Marketing &amp; Promos</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse {{ in_array($current, ['promotions.coupons', 'promotions.offers', 'referrals.programs.index']) ? 'show' : '' }}" id="marketingSubmenu">
                        <ul class="nav nav-submenu">
                            @can('promotions-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'promotions.offers' ? 'active' : '' }}" href="{{ route('promotions.offers') }}">
                                    <i class="bi bi-star-fill"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Offers &amp; Deals</span>
                                </a>
                            </li>
                            @endcan
                            @can('coupon-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'promotions.coupons' ? 'active' : '' }}" href="{{ route('promotions.coupons') }}">
                                    <i class="bi bi-ticket-perforated-fill"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Coupon Codes</span>
                                </a>
                            </li>
                            @endcan
                            @can('promotions-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'referrals.programs.index' ? 'active' : '' }}" href="{{ route('referrals.programs.index') }}">
                                    <i class="bi bi-diagram-3-fill"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Referral Programs</span>
                                </a>
                            </li>
                            @endcan
                        </ul>
                    </div>
                </li>
                @endcanany

                {{-- ── SUPPLY CHAIN ─────────────────────────── --}}
                @canany(['supplier-view', 'purchaseorder-view', 'goodsreceipt-view', 'stockmanagement-view', 'stocktransfer-view', 'inventoryadjustment-view', 'warehouse-dashboard-view', 'shipping-view', 'warehouse-view'])
                <li class="nav-item sidebar-section-label mt-3">
                    <small class="text-muted px-3 text-uppercase fw-bold">Supply Chain</small>
                </li>
                @endcanany

                {{-- Procurement & Inventory Dropdown --}}
                @canany(['supplier-view', 'purchaseorder-view', 'goodsreceipt-view', 'stockmanagement-view', 'stocktransfer-view', 'inventoryadjustment-view', 'warehouse-dashboard-view'])
                <li class="nav-item">
                    <a class="nav-link {{ in_array($current, ['inventory.dashboard', 'procurement.suppliers.index', 'procurement.purchase-orders.index', 'procurement.goods-receipts.index', 'inventory.stock-management', 'inventory.stock-transfers', 'inventory.adjustments']) ? 'active' : 'collapsed' }}"
                       href="#"
                       data-bs-toggle="collapse"
                       data-bs-target="#inventorySubmenu"
                       aria-expanded="{{ in_array($current, ['inventory.dashboard', 'procurement.suppliers.index', 'procurement.purchase-orders.index', 'procurement.goods-receipts.index', 'inventory.stock-management', 'inventory.stock-transfers', 'inventory.adjustments']) ? 'true' : 'false' }}"
                       aria-controls="inventorySubmenu">
                        <i class="bi bi-archive-fill"></i>
                        <span class="text-truncate flex-grow-1" style="min-width: 0;">Procurement &amp; Inventory</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse {{ in_array($current, ['inventory.dashboard', 'procurement.suppliers.index', 'procurement.purchase-orders.index', 'procurement.goods-receipts.index', 'inventory.stock-management', 'inventory.stock-transfers', 'inventory.adjustments']) ? 'show' : '' }}" id="inventorySubmenu">
                        <ul class="nav nav-submenu">
                            @can('warehouse-dashboard-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'inventory.dashboard' ? 'active' : '' }}" href="{{ route('inventory.dashboard') }}">
                                    <i class="bi bi-buildings"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Command Center</span>
                                </a>
                            </li>
                            @endcan
                            @can('supplier-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'procurement.suppliers.index' ? 'active' : '' }}" href="{{ route('procurement.suppliers.index') }}">
                                    <i class="bi bi-truck-flatbed"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Suppliers</span>
                                </a>
                            </li>
                            @endcan
                            @can('purchaseorder-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'procurement.purchase-orders.index' ? 'active' : '' }}" href="{{ route('procurement.purchase-orders.index') }}">
                                    <i class="bi bi-receipt"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Purchase Orders</span>
                                </a>
                            </li>
                            @endcan
                            @can('goodsreceipt-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'procurement.goods-receipts.index' ? 'active' : '' }}" href="{{ route('procurement.goods-receipts.index') }}">
                                    <i class="bi bi-clipboard-check"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Goods Receipts</span>
                                </a>
                            </li>
                            @endcan
                            @can('stockmanagement-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'inventory.stock-management' ? 'active' : '' }}" href="{{ route('inventory.stock-management') }}">
                                    <i class="bi bi-box-seam-fill"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Stock Levels</span>
                                </a>
                            </li>
                            @endcan
                            @can('stocktransfer-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'inventory.stock-transfers' ? 'active' : '' }}" href="{{ route('inventory.stock-transfers') }}">
                                    <i class="bi bi-arrow-left-right"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Stock Transfers</span>
                                </a>
                            </li>
                            @endcan
                            @can('inventoryadjustment-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'inventory.adjustments' ? 'active' : '' }}" href="{{ route('inventory.adjustments') }}">
                                    <i class="bi bi-sliders2"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Adjustments</span>
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
                        <span class="text-truncate flex-grow-1" style="min-width: 0;">Logistics &amp; Warehouses</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse {{ in_array($current, ['shipping.shipments', 'shipping.services', 'catalog.warehouses']) ? 'show' : '' }}" id="shippingSubmenu">
                        <ul class="nav nav-submenu">
                            @can('warehouse-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'catalog.warehouses' ? 'active' : '' }}" href="{{ route('catalog.warehouses') }}">
                                    <i class="bi bi-buildings-fill"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Warehouses</span>
                                </a>
                            </li>
                            @endcan
                            @can('shipping-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'shipping.shipments' ? 'active' : '' }}" href="{{ route('shipping.shipments') }}">
                                    <i class="bi bi-geo-alt-fill"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Shipments &amp; Tracking</span>
                                </a>
                            </li>
                            @endcan
                            @can('shipping-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'shipping.services' ? 'active' : '' }}" href="{{ route('shipping.services') }}">
                                    <i class="bi bi-gear-wide-connected"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Shipping Services</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'shipping.settings' ? 'active' : '' }}" href="{{ route('shipping.settings') }}">
                                    <i class="bi bi-sliders"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Shipping Settings</span>
                                </a>
                            </li>
                            @endcan
                        </ul>
                    </div>
                </li>
                @endcanany

                {{-- ── ADMINISTRATION ─────────────────────────────── --}}
                @canany(['user-view', 'role-view', 'department-view', 'attendance-view', 'leave-view', 'village-view', 'orderreason-view', 'settings-view'])
                <li class="nav-item sidebar-section-label mt-3">
                    <small class="text-muted px-3 text-uppercase fw-bold">Administration</small>
                </li>
                @endcanany

                {{-- CRM & People Dropdown --}}
                @canany(['user-view', 'role-view', 'department-view'])
                <li class="nav-item">
                    <a class="nav-link {{ in_array($current, ['users', 'roles-permissions', 'customers', 'customer-settings.index', 'departments']) ? 'active' : 'collapsed' }}"
                       href="#"
                       data-bs-toggle="collapse"
                       data-bs-target="#crmSubmenu"
                       aria-expanded="{{ in_array($current, ['users', 'roles-permissions', 'customers', 'customer-settings.index', 'departments']) ? 'true' : 'false' }}"
                       aria-controls="crmSubmenu">
                        <i class="bi bi-people-fill"></i>
                        <span class="text-truncate flex-grow-1" style="min-width: 0;">CRM &amp; People</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse {{ in_array($current, ['users', 'roles-permissions', 'customers', 'customer-settings.index', 'departments']) ? 'show' : '' }}" id="crmSubmenu">
                        <ul class="nav nav-submenu">
                            @role('Super Admin')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'customers' ? 'active' : '' }}" href="{{ route('customers') }}">
                                    <i class="bi bi-person-lines-fill"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Customers</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'customer-settings.index' ? 'active' : '' }}" href="{{ route('customer-settings.index') }}">
                                    <i class="bi bi-gear-wide-connected"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Customer Settings</span>
                                </a>
                            </li>
                            @endrole
                            @can('user-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'users' ? 'active' : '' }}" href="{{ route('users') }}">
                                    <i class="bi bi-person-fill-gear"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">System Users</span>
                                </a>
                            </li>
                            @endcan
                            @can('role-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'roles-permissions' ? 'active' : '' }}" href="{{ route('roles-permissions') }}">
                                    <i class="bi bi-shield-lock-fill"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Roles &amp; Permissions</span>
                                </a>
                            </li>
                            @endcan
                            @can('department-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'departments' ? 'active' : '' }}" href="{{ route('departments') }}">
                                    <i class="bi bi-diagram-3-fill"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Organization Structure</span>
                                </a>
                            </li>
                            @endcan
                        </ul>
                    </div>
                </li>
                @endcanany

                {{-- Time Management Dropdown --}}
                @canany(['attendance-view', 'leave-view'])
                <li class="nav-item">
                    <a class="nav-link {{ in_array($current, ['attendances', 'leaves']) ? 'active' : 'collapsed' }}"
                       href="#"
                       data-bs-toggle="collapse"
                       data-bs-target="#timeManagementSubmenu"
                       aria-expanded="{{ in_array($current, ['attendances', 'leaves']) ? 'true' : 'false' }}"
                       aria-controls="timeManagementSubmenu">
                        <i class="bi bi-clock-history"></i>
                        <span class="text-truncate flex-grow-1" style="min-width: 0;">Time Management</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse {{ in_array($current, ['attendances', 'leaves']) ? 'show' : '' }}" id="timeManagementSubmenu">
                        <ul class="nav nav-submenu">
                            @can('attendance-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'attendances' ? 'active' : '' }}" href="{{ route('attendances') }}">
                                    <i class="bi bi-calendar-check-fill"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Attendances</span>
                                </a>
                            </li>
                            @endcan
                            @can('leave-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'leaves' ? 'active' : '' }}" href="{{ route('leaves') }}">
                                    <i class="bi bi-calendar-minus-fill"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Leave Management</span>
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
                    <a class="nav-link {{ in_array($current, ['villages', 'order.reasons', 'call-tags.index', 'admin.audit-logs.index', 'files']) ? 'active' : 'collapsed' }}"
                       href="#"
                       data-bs-toggle="collapse"
                       data-bs-target="#systemSubmenu"
                       aria-expanded="{{ in_array($current, ['villages', 'order.reasons', 'call-tags.index', 'admin.audit-logs.index', 'files']) ? 'true' : 'false' }}"
                       aria-controls="systemSubmenu">
                        <i class="bi bi-gear-fill"></i>
                        <span class="text-truncate flex-grow-1" style="min-width: 0;">System Settings</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse {{ in_array($current, ['villages', 'order.reasons', 'call-tags.index', 'admin.audit-logs.index', 'files']) ? 'show' : '' }}" id="systemSubmenu">
                        <ul class="nav nav-submenu">
                            @can('village-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'villages' ? 'active' : '' }}" href="{{ route('villages') }}">
                                    <i class="bi bi-geo-alt-fill"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Villages</span>
                                </a>
                            </li>
                            @endcan
                            @can('orderreason-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'order.reasons' ? 'active' : '' }}" href="{{ route('order.reasons') }}">
                                    <i class="bi bi-list-task"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Order Reasons</span>
                                </a>
                            </li>
                            @endcan
                            @can('settings-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'call-tags.index' ? 'active' : '' }}" href="{{ route('call-tags.index') }}">
                                    <i class="bi bi-tags"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Call Tags</span>
                                </a>
                            </li>
                            @endcan
                            @role('Super Admin')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'admin.audit-logs.index' ? 'active' : '' }}" href="{{ route('admin.audit-logs.index') }}">
                                    <i class="bi bi-journal-medical"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Audit Logs</span>
                                </a>
                            </li>
                            @endrole
                            @can('settings-view')
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'files' ? 'active' : '' }}" href="{{ route('files') }}">
                                    <i class="bi bi-folder2-open"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">File Manager</span>
                                </a>
                            </li>
                            @endcan
                        </ul>
                    </div>
                </li>
                @endcanany

                @role('Super Admin')
                {{-- ── WORKSPACE ─────────────────────────────── --}}
                <li class="nav-item sidebar-section-label mt-3">
                    <small class="text-muted px-3 text-uppercase fw-bold">Workspace</small>
                </li>

                {{-- Utilities & Workspace Dropdown --}}
                <li class="nav-item">
                    <a class="nav-link {{ in_array($current, ['messages', 'calendar', 'forms', 'settings', 'security', 'help']) || Str::startsWith($current, 'elements') ? 'active' : 'collapsed' }}"
                       href="#"
                       data-bs-toggle="collapse"
                       data-bs-target="#toolsSubmenu"
                       aria-expanded="{{ in_array($current, ['messages', 'calendar', 'forms', 'settings', 'security', 'help']) || Str::startsWith($current, 'elements') ? 'true' : 'false' }}"
                       aria-controls="toolsSubmenu">
                        <i class="bi bi-wrench-adjustable-circle-fill"></i>
                        <span class="text-truncate flex-grow-1" style="min-width: 0;">Utilities &amp; Tools</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse {{ in_array($current, ['messages', 'calendar', 'forms', 'settings', 'security', 'help']) || Str::startsWith($current, 'elements') ? 'show' : '' }}" id="toolsSubmenu">
                        <ul class="nav nav-submenu">
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'messages' ? 'active' : '' }}" href="{{ route('messages') }}">
                                    <i class="bi bi-chat-dots-fill"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Messages</span>
                                    <span class="badge bg-danger rounded-pill ms-auto">3</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'calendar' ? 'active' : '' }}" href="{{ route('calendar') }}">
                                    <i class="bi bi-calendar-week-fill"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Calendar</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'forms' ? 'active' : '' }}" href="{{ route('forms') }}">
                                    <i class="bi bi-ui-checks-grid"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Forms</span>
                                </a>
                            </li>
                            @can('settings-view')
                            <li class="nav-item">
                                <a class="nav-link {{ Str::startsWith($current, 'elements') ? 'active' : '' }}" href="{{ route('elements') }}">
                                    <i class="bi bi-puzzle-fill"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">UI Elements</span>
                                </a>
                            </li>
                            @endcan
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'security' ? 'active' : '' }}" href="{{ route('security') }}">
                                    <i class="bi bi-shield-fill-check"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Security</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ url('/docs/api') }}" target="_blank">
                                    <i class="bi bi-file-earmark-code-fill"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">API Documentation</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'help' ? 'active' : '' }}" href="{{ route('help') }}">
                                    <i class="bi bi-question-circle-fill"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Help &amp; Support</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                @endrole

            </ul>
        </nav>
    </div>
</aside>
