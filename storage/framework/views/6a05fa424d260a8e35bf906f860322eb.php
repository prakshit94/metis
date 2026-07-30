<?php
    $current = request()->route() ? request()->route()->getName() : 'dashboard';
?>

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

                
                <li class="nav-item sidebar-section-label">
                    <small class="text-muted px-3 text-uppercase fw-bold">Main</small>
                </li>

                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('dashboard-view')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo e($current === 'dashboard' ? 'active' : ''); ?>" href="<?php echo e(route('dashboard')); ?>">
                        <i class="bi bi-grid-1x2-fill"></i>
                        <span class="text-truncate flex-grow-1" style="min-width: 0;">Dashboard</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('analytics-view')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo e($current === 'analytics' ? 'active' : ''); ?>" href="<?php echo e(route('analytics')); ?>">
                        <i class="bi bi-bar-chart-line-fill"></i>
                        <span class="text-truncate flex-grow-1" style="min-width: 0;">Analytics</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('reports-view')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo e($current === 'reports' ? 'active' : ''); ?>" href="<?php echo e(route('reports')); ?>">
                        <i class="bi bi-file-earmark-bar-graph-fill"></i>
                        <span class="text-truncate flex-grow-1" style="min-width: 0;">Reports</span>
                    </a>
                </li>
                <?php endif; ?>

                
                <li class="nav-item sidebar-section-label mt-3">
                    <small class="text-muted px-3 text-uppercase fw-bold">Commerce &amp; Sales</small>
                </li>

                
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['orders.view', 'invoices.view', 'payments.view', 'returns.view', 'refunds.view', 'credit-notes.view'])): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo e(in_array($current, ['orders', 'invoices.index', 'payments.index', 'returns.index', 'refunds.index', 'credit-notes.index']) ? 'active' : 'collapsed'); ?>"
                       href="#"
                       data-bs-toggle="collapse"
                       data-bs-target="#orderManagementSubmenu"
                       aria-expanded="<?php echo e(in_array($current, ['orders', 'invoices.index', 'payments.index', 'returns.index', 'refunds.index', 'credit-notes.index']) ? 'true' : 'false'); ?>"
                       aria-controls="orderManagementSubmenu">
                        <i class="bi bi-cart-check-fill"></i>
                        <span class="text-truncate flex-grow-1" style="min-width: 0;">Order Management</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse <?php echo e(in_array($current, ['orders', 'invoices.index', 'payments.index', 'returns.index', 'refunds.index', 'credit-notes.index']) ? 'show' : ''); ?>" id="orderManagementSubmenu">
                        <ul class="nav nav-submenu">
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('orders.view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'orders' ? 'active' : ''); ?>" href="<?php echo e(route('orders')); ?>">
                                    <i class="bi bi-bag-check-fill"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Orders</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('invoices.view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'invoices.index' ? 'active' : ''); ?>" href="<?php echo e(route('invoices.index')); ?>">
                                    <i class="bi bi-receipt"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Invoices</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('payments.view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'payments.index' ? 'active' : ''); ?>" href="<?php echo e(route('payments.index')); ?>">
                                    <i class="bi bi-credit-card"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Payments</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('returns.view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'returns.index' ? 'active' : ''); ?>" href="<?php echo e(route('returns.index')); ?>">
                                    <i class="bi bi-arrow-return-left"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Returns</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('refunds.view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'refunds.index' ? 'active' : ''); ?>" href="<?php echo e(route('refunds.index')); ?>">
                                    <i class="bi bi-cash-coin"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Refunds</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('credit-notes.view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'credit-notes.index' ? 'active' : ''); ?>" href="<?php echo e(route('credit-notes.index')); ?>">
                                    <i class="bi bi-receipt-cutoff"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Credit Notes</span>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </li>
                <?php endif; ?>

                
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['coupon-view', 'promotions-view'])): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo e(in_array($current, ['promotions.coupons', 'promotions.offers', 'referrals.programs.index']) ? 'active' : 'collapsed'); ?>"
                       href="#"
                       data-bs-toggle="collapse"
                       data-bs-target="#marketingSubmenu"
                       aria-expanded="<?php echo e(in_array($current, ['promotions.coupons', 'promotions.offers', 'referrals.programs.index']) ? 'true' : 'false'); ?>"
                       aria-controls="marketingSubmenu">
                        <i class="bi bi-megaphone-fill"></i>
                        <span class="text-truncate flex-grow-1" style="min-width: 0;">Marketing &amp; Promos</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse <?php echo e(in_array($current, ['promotions.coupons', 'promotions.offers', 'referrals.programs.index']) ? 'show' : ''); ?>" id="marketingSubmenu">
                        <ul class="nav nav-submenu">
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('promotions-view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'promotions.offers' ? 'active' : ''); ?>" href="<?php echo e(route('promotions.offers')); ?>">
                                    <i class="bi bi-star-fill"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Offers &amp; Deals</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('coupon-view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'promotions.coupons' ? 'active' : ''); ?>" href="<?php echo e(route('promotions.coupons')); ?>">
                                    <i class="bi bi-ticket-perforated-fill"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Coupon Codes</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('promotions-view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'referrals.programs.index' ? 'active' : ''); ?>" href="<?php echo e(route('referrals.programs.index')); ?>">
                                    <i class="bi bi-diagram-3-fill"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Referral Programs</span>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </li>
                <?php endif; ?>

                
                <li class="nav-item sidebar-section-label mt-3">
                    <small class="text-muted px-3 text-uppercase fw-bold">Supply Chain</small>
                </li>

                
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['purchaseorder-view', 'goodsreceipt-view', 'stockmanagement-view', 'stocktransfer-view', 'inventoryadjustment-view'])): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo e(in_array($current, ['procurement.purchase-orders.index', 'procurement.goods-receipts.index', 'inventory.stock-management', 'inventory.stock-transfers', 'inventory.adjustments']) ? 'active' : 'collapsed'); ?>"
                       href="#"
                       data-bs-toggle="collapse"
                       data-bs-target="#inventorySubmenu"
                       aria-expanded="<?php echo e(in_array($current, ['procurement.purchase-orders.index', 'procurement.goods-receipts.index', 'inventory.stock-management', 'inventory.stock-transfers', 'inventory.adjustments']) ? 'true' : 'false'); ?>"
                       aria-controls="inventorySubmenu">
                        <i class="bi bi-archive-fill"></i>
                        <span class="text-truncate flex-grow-1" style="min-width: 0;">Procurement &amp; Inventory</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse <?php echo e(in_array($current, ['procurement.purchase-orders.index', 'procurement.goods-receipts.index', 'inventory.stock-management', 'inventory.stock-transfers', 'inventory.adjustments']) ? 'show' : ''); ?>" id="inventorySubmenu">
                        <ul class="nav nav-submenu">
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('purchaseorder-view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'procurement.purchase-orders.index' ? 'active' : ''); ?>" href="<?php echo e(route('procurement.purchase-orders.index')); ?>">
                                    <i class="bi bi-receipt"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Purchase Orders</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('goodsreceipt-view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'procurement.goods-receipts.index' ? 'active' : ''); ?>" href="<?php echo e(route('procurement.goods-receipts.index')); ?>">
                                    <i class="bi bi-clipboard-check"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Goods Receipts</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('stockmanagement-view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'inventory.stock-management' ? 'active' : ''); ?>" href="<?php echo e(route('inventory.stock-management')); ?>">
                                    <i class="bi bi-box-seam-fill"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Stock Levels</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('stocktransfer-view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'inventory.stock-transfers' ? 'active' : ''); ?>" href="<?php echo e(route('inventory.stock-transfers')); ?>">
                                    <i class="bi bi-arrow-left-right"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Stock Transfers</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('inventoryadjustment-view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'inventory.adjustments' ? 'active' : ''); ?>" href="<?php echo e(route('inventory.adjustments')); ?>">
                                    <i class="bi bi-sliders2"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Adjustments</span>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </li>
                <?php endif; ?>

                
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['shipping-view', 'warehouse-view'])): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo e(in_array($current, ['shipping.shipments', 'shipping.services', 'catalog.warehouses']) ? 'active' : 'collapsed'); ?>"
                       href="#"
                       data-bs-toggle="collapse"
                       data-bs-target="#shippingSubmenu"
                       aria-expanded="<?php echo e(in_array($current, ['shipping.shipments', 'shipping.services', 'catalog.warehouses']) ? 'true' : 'false'); ?>"
                       aria-controls="shippingSubmenu">
                        <i class="bi bi-truck"></i>
                        <span class="text-truncate flex-grow-1" style="min-width: 0;">Logistics &amp; Warehouses</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse <?php echo e(in_array($current, ['shipping.shipments', 'shipping.services', 'catalog.warehouses']) ? 'show' : ''); ?>" id="shippingSubmenu">
                        <ul class="nav nav-submenu">
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('warehouse-view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'catalog.warehouses' ? 'active' : ''); ?>" href="<?php echo e(route('catalog.warehouses')); ?>">
                                    <i class="bi bi-buildings-fill"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Warehouses</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('shipping-view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'shipping.shipments' ? 'active' : ''); ?>" href="<?php echo e(route('shipping.shipments')); ?>">
                                    <i class="bi bi-geo-alt-fill"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Shipments &amp; Tracking</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('shipping-view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'shipping.services' ? 'active' : ''); ?>" href="<?php echo e(route('shipping.services')); ?>">
                                    <i class="bi bi-gear-wide-connected"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Shipping Services</span>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </li>
                <?php endif; ?>

                
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['product-view', 'category-view', 'brand-view', 'productattribute-view', 'unitofmeasure-view', 'taxrate-view', 'hsncode-view'])): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo e(in_array($current, ['catalog.products', 'catalog.categories', 'catalog.brands', 'catalog.attributes', 'catalog.uom', 'catalog.tax-rates', 'catalog.hsn-codes']) ? 'active' : 'collapsed'); ?>"
                       href="#"
                       data-bs-toggle="collapse"
                       data-bs-target="#catalogSubmenu"
                       aria-expanded="<?php echo e(in_array($current, ['catalog.products', 'catalog.categories', 'catalog.brands', 'catalog.attributes', 'catalog.uom', 'catalog.tax-rates', 'catalog.hsn-codes']) ? 'true' : 'false'); ?>"
                       aria-controls="catalogSubmenu">
                        <i class="bi bi-shop-window"></i>
                        <span class="text-truncate flex-grow-1" style="min-width: 0;">Catalog Management</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse <?php echo e(in_array($current, ['catalog.products', 'catalog.categories', 'catalog.brands', 'catalog.attributes', 'catalog.uom', 'catalog.tax-rates', 'catalog.hsn-codes']) ? 'show' : ''); ?>" id="catalogSubmenu">
                        <ul class="nav nav-submenu">
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('product-view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'catalog.products' ? 'active' : ''); ?>" href="<?php echo e(route('catalog.products')); ?>">
                                    <i class="bi bi-box-seam-fill"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Products</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('category-view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'catalog.categories' ? 'active' : ''); ?>" href="<?php echo e(route('catalog.categories')); ?>">
                                    <i class="bi bi-diagram-3-fill"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Categories</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('brand-view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'catalog.brands' ? 'active' : ''); ?>" href="<?php echo e(route('catalog.brands')); ?>">
                                    <i class="bi bi-patch-check-fill"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Brands</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('productattribute-view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'catalog.attributes' ? 'active' : ''); ?>" href="<?php echo e(route('catalog.attributes')); ?>">
                                    <i class="bi bi-sliders2"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Attributes</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('unitofmeasure-view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'catalog.uom' ? 'active' : ''); ?>" href="<?php echo e(route('catalog.uom')); ?>">
                                    <i class="bi bi-rulers"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Units of Measure</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('taxrate-view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'catalog.tax-rates' ? 'active' : ''); ?>" href="<?php echo e(route('catalog.tax-rates')); ?>">
                                    <i class="bi bi-percent"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Tax Rates</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('hsncode-view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'catalog.hsn-codes' ? 'active' : ''); ?>" href="<?php echo e(route('catalog.hsn-codes')); ?>">
                                    <i class="bi bi-upc-scan"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">HSN Codes</span>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </li>
                <?php endif; ?>

                
                <li class="nav-item sidebar-section-label mt-3">
                    <small class="text-muted px-3 text-uppercase fw-bold">Administration</small>
                </li>

                
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['user-view', 'role-view'])): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo e(in_array($current, ['users', 'roles-permissions', 'customers', 'customer-settings.index']) ? 'active' : 'collapsed'); ?>"
                       href="#"
                       data-bs-toggle="collapse"
                       data-bs-target="#crmSubmenu"
                       aria-expanded="<?php echo e(in_array($current, ['users', 'roles-permissions', 'customers', 'customer-settings.index']) ? 'true' : 'false'); ?>"
                       aria-controls="crmSubmenu">
                        <i class="bi bi-people-fill"></i>
                        <span class="text-truncate flex-grow-1" style="min-width: 0;">CRM &amp; People</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse <?php echo e(in_array($current, ['users', 'roles-permissions', 'customers', 'customer-settings.index']) ? 'show' : ''); ?>" id="crmSubmenu">
                        <ul class="nav nav-submenu">
                            <?php if (\Illuminate\Support\Facades\Blade::check('role', 'Super Admin')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'customers' ? 'active' : ''); ?>" href="<?php echo e(route('customers')); ?>">
                                    <i class="bi bi-person-lines-fill"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Customers</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'customer-settings.index' ? 'active' : ''); ?>" href="<?php echo e(route('customer-settings.index')); ?>">
                                    <i class="bi bi-gear-wide-connected"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Customer Settings</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('user-view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'users' ? 'active' : ''); ?>" href="<?php echo e(route('users')); ?>">
                                    <i class="bi bi-person-fill-gear"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">System Users</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('role-view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'roles-permissions' ? 'active' : ''); ?>" href="<?php echo e(route('roles-permissions')); ?>">
                                    <i class="bi bi-shield-lock-fill"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Roles &amp; Permissions</span>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </li>
                <?php endif; ?>

                
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['village-view', 'orderreason-view', 'settings-view'])): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo e(in_array($current, ['villages', 'order.reasons', 'call-tags.index', 'admin.audit-logs.index']) ? 'active' : 'collapsed'); ?>"
                       href="#"
                       data-bs-toggle="collapse"
                       data-bs-target="#systemSubmenu"
                       aria-expanded="<?php echo e(in_array($current, ['villages', 'order.reasons', 'call-tags.index', 'admin.audit-logs.index']) ? 'true' : 'false'); ?>"
                       aria-controls="systemSubmenu">
                        <i class="bi bi-gear-fill"></i>
                        <span class="text-truncate flex-grow-1" style="min-width: 0;">System Settings</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse <?php echo e(in_array($current, ['villages', 'order.reasons', 'call-tags.index', 'admin.audit-logs.index']) ? 'show' : ''); ?>" id="systemSubmenu">
                        <ul class="nav nav-submenu">
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('village-view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'villages' ? 'active' : ''); ?>" href="<?php echo e(route('villages')); ?>">
                                    <i class="bi bi-geo-alt-fill"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Villages</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('orderreason-view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'order.reasons' ? 'active' : ''); ?>" href="<?php echo e(route('order.reasons')); ?>">
                                    <i class="bi bi-list-task"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Order Reasons</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('settings-view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'call-tags.index' ? 'active' : ''); ?>" href="<?php echo e(route('call-tags.index')); ?>">
                                    <i class="bi bi-tags"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Call Tags</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (\Illuminate\Support\Facades\Blade::check('role', 'Super Admin')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'admin.audit-logs.index' ? 'active' : ''); ?>" href="<?php echo e(route('admin.audit-logs.index')); ?>">
                                    <i class="bi bi-journal-medical"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Audit Logs</span>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </li>
                <?php endif; ?>

                
                <li class="nav-item sidebar-section-label mt-3">
                    <small class="text-muted px-3 text-uppercase fw-bold">Workspace</small>
                </li>

                
                <li class="nav-item">
                    <a class="nav-link <?php echo e(in_array($current, ['chat.index', 'messages', 'calendar', 'files', 'forms', 'settings', 'security', 'help']) || Str::startsWith($current, 'elements') ? 'active' : 'collapsed'); ?>"
                       href="#"
                       data-bs-toggle="collapse"
                       data-bs-target="#toolsSubmenu"
                       aria-expanded="<?php echo e(in_array($current, ['chat.index', 'messages', 'calendar', 'files', 'forms', 'settings', 'security', 'help']) || Str::startsWith($current, 'elements') ? 'true' : 'false'); ?>"
                       aria-controls="toolsSubmenu">
                        <i class="bi bi-wrench-adjustable-circle-fill"></i>
                        <span class="text-truncate flex-grow-1" style="min-width: 0;">Utilities &amp; Tools</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse <?php echo e(in_array($current, ['chat.index', 'messages', 'calendar', 'files', 'forms', 'settings', 'security', 'help']) || Str::startsWith($current, 'elements') ? 'show' : ''); ?>" id="toolsSubmenu">
                        <ul class="nav nav-submenu">
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'chat.index' ? 'active' : ''); ?>" href="<?php echo e(route('chat.index')); ?>">
                                    <i class="bi bi-chat-text-fill"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Team Chat</span>
                                    <span class="badge bg-primary rounded-pill ms-auto">New</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'messages' ? 'active' : ''); ?>" href="<?php echo e(route('messages')); ?>">
                                    <i class="bi bi-chat-dots-fill"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Messages</span>
                                    <span class="badge bg-danger rounded-pill ms-auto">3</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'calendar' ? 'active' : ''); ?>" href="<?php echo e(route('calendar')); ?>">
                                    <i class="bi bi-calendar-week-fill"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Calendar</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'files' ? 'active' : ''); ?>" href="<?php echo e(route('files')); ?>">
                                    <i class="bi bi-folder2-open"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Files</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'forms' ? 'active' : ''); ?>" href="<?php echo e(route('forms')); ?>">
                                    <i class="bi bi-ui-checks-grid"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Forms</span>
                                </a>
                            </li>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('settings-view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e(Str::startsWith($current, 'elements') ? 'active' : ''); ?>" href="<?php echo e(route('elements')); ?>">
                                    <i class="bi bi-puzzle-fill"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">UI Elements</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'security' ? 'active' : ''); ?>" href="<?php echo e(route('security')); ?>">
                                    <i class="bi bi-shield-fill-check"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Security</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/docs/api" target="_blank">
                                    <i class="bi bi-file-earmark-code-fill"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">API Documentation</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'help' ? 'active' : ''); ?>" href="<?php echo e(route('help')); ?>">
                                    <i class="bi bi-question-circle-fill"></i>
                                    <span class="text-truncate flex-grow-1" style="min-width: 0;">Help &amp; Support</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

            </ul>
        </nav>
    </div>
</aside>
<?php /**PATH /home/user/metis/resources/views/components/sidebar.blade.php ENDPATH**/ ?>