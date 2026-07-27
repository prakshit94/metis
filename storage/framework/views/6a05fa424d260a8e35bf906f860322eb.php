<?php
    $current = request()->route() ? request()->route()->getName() : 'dashboard';
?>

<!-- Sidebar -->
<aside class="admin-sidebar" id="admin-sidebar">
    <div class="sidebar-content">

        <nav class="sidebar-nav">
            <ul class="nav flex-column gap-1">

                
                <li class="nav-item sidebar-section-label">
                    <small class="text-muted px-3 text-uppercase fw-bold">Main</small>
                </li>

                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('dashboard-view')): ?>
                            <li class="nav-item">
                    <a class="nav-link <?php echo e($current === 'dashboard' ? 'active' : ''); ?>" href="<?php echo e(route('dashboard')); ?>">
                        <i class="bi bi-grid-1x2-fill"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                            <?php endif; ?>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('analytics-view')): ?>
                            <li class="nav-item">
                    <a class="nav-link <?php echo e($current === 'analytics' ? 'active' : ''); ?>" href="<?php echo e(route('analytics')); ?>">
                        <i class="bi bi-bar-chart-line-fill"></i>
                        <span>Analytics</span>
                    </a>
                </li>
                            <?php endif; ?>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('reports-view')): ?>
                            <li class="nav-item">
                    <a class="nav-link <?php echo e($current === 'reports' ? 'active' : ''); ?>" href="<?php echo e(route('reports')); ?>">
                        <i class="bi bi-file-earmark-bar-graph-fill"></i>
                        <span>Reports</span>
                    </a>
                </li>
                            <?php endif; ?>

                
                <li class="nav-item sidebar-section-label mt-3">
                    <small class="text-muted px-3 text-uppercase fw-bold">Enterprise</small>
                </li>

                
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['orders.view', 'coupon-view', 'promotions-view'])): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo e(in_array($current, ['orders', 'promotions.coupons', 'promotions.offers', 'referrals.programs.index']) ? 'active' : ''); ?>"
                       href="#"
                       data-bs-toggle="collapse"
                       data-bs-target="#salesSubmenu"
                       aria-expanded="<?php echo e(in_array($current, ['orders', 'promotions.coupons', 'promotions.offers', 'referrals.programs.index']) ? 'true' : 'false'); ?>"
                       aria-controls="salesSubmenu">
                        <i class="bi bi-shop"></i>
                        <span>Sales &amp; Marketing</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse <?php echo e(in_array($current, ['orders', 'promotions.coupons', 'promotions.offers', 'referrals.programs.index']) ? 'show' : ''); ?>" id="salesSubmenu">
                        <ul class="nav nav-submenu">
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('orders.view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'orders' ? 'active' : ''); ?>" href="<?php echo e(route('orders')); ?>">
                                    <i class="bi bi-bag-check-fill"></i>
                                    <span>Orders</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('coupon-view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'promotions.coupons' ? 'active' : ''); ?>" href="<?php echo e(route('promotions.coupons')); ?>">
                                    <i class="bi bi-ticket-perforated-fill"></i>
                                    <span>Coupon Codes</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('promotions-view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'promotions.offers' ? 'active' : ''); ?>" href="<?php echo e(route('promotions.offers')); ?>">
                                    <i class="bi bi-star-fill"></i>
                                    <span>Offers &amp; Deals</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('promotions-view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'referrals.programs.index' ? 'active' : ''); ?>" href="<?php echo e(route('referrals.programs.index')); ?>">
                                    <i class="bi bi-diagram-3-fill"></i>
                                    <span>Referral Programs</span>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </li>
                <?php endif; ?>

                
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['invoices.view', 'payments.view', 'refunds.view', 'returns.view'])): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo e(Str::startsWith($current, 'returns') || Str::startsWith($current, 'refunds') || Str::startsWith($current, 'payments') || Str::startsWith($current, 'invoices') ? 'active' : ''); ?>"
                       href="#"
                       data-bs-toggle="collapse"
                       data-bs-target="#billingSubmenu"
                       aria-expanded="<?php echo e(Str::startsWith($current, 'returns') || Str::startsWith($current, 'refunds') || Str::startsWith($current, 'payments') || Str::startsWith($current, 'invoices') ? 'true' : 'false'); ?>"
                       aria-controls="billingSubmenu">
                        <i class="bi bi-cash-stack"></i>
                        <span>Billing &amp; Payments</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse <?php echo e(Str::startsWith($current, 'returns') || Str::startsWith($current, 'refunds') || Str::startsWith($current, 'payments') || Str::startsWith($current, 'invoices') ? 'show' : ''); ?>" id="billingSubmenu">
                        <ul class="nav nav-submenu">
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('invoices.view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e(Str::startsWith($current, 'invoices') ? 'active' : ''); ?>" href="<?php echo e(route('invoices.index')); ?>">
                                    <i class="bi bi-receipt"></i>
                                    <span>Invoices</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('payments.view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e(Str::startsWith($current, 'payments') ? 'active' : ''); ?>" href="<?php echo e(route('payments.index')); ?>">
                                    <i class="bi bi-credit-card"></i>
                                    <span>Payments</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('refunds.view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e(Str::startsWith($current, 'refunds') ? 'active' : ''); ?>" href="<?php echo e(route('refunds.index')); ?>">
                                    <i class="bi bi-cash-coin"></i>
                                    <span>Refunds</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('returns.view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e(Str::startsWith($current, 'returns') ? 'active' : ''); ?>" href="<?php echo e(route('returns.index')); ?>">
                                    <i class="bi bi-arrow-return-left"></i>
                                    <span>Returns</span>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </li>
                <?php endif; ?>

                
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['shipping-view', 'warehouse-view'])): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo e(Str::startsWith($current, 'shipping') || $current === 'catalog.warehouses' ? 'active' : ''); ?>"
                       href="#"
                       data-bs-toggle="collapse"
                       data-bs-target="#shippingSubmenu"
                       aria-expanded="<?php echo e(Str::startsWith($current, 'shipping') || $current === 'catalog.warehouses' ? 'true' : 'false'); ?>"
                       aria-controls="shippingSubmenu">
                        <i class="bi bi-truck"></i>
                        <span>Logistics &amp; Warehouses</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse <?php echo e(Str::startsWith($current, 'shipping') || $current === 'catalog.warehouses' ? 'show' : ''); ?>" id="shippingSubmenu">
                        <ul class="nav nav-submenu">
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('shipping-view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'shipping.shipments' ? 'active' : ''); ?>" href="<?php echo e(route('shipping.shipments')); ?>">
                                    <i class="bi bi-geo-alt-fill"></i>
                                    <span>Shipments &amp; Tracking</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('shipping-view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'shipping.services' ? 'active' : ''); ?>" href="<?php echo e(route('shipping.services')); ?>">
                                    <i class="bi bi-gear-wide-connected"></i>
                                    <span>Shipping Services</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('warehouse-view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'catalog.warehouses' ? 'active' : ''); ?>" href="<?php echo e(route('catalog.warehouses')); ?>">
                                    <i class="bi bi-buildings-fill"></i>
                                    <span>Warehouses</span>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </li>
                <?php endif; ?>

                
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['stockmanagement-view', 'stocktransfer-view', 'inventoryadjustment-view'])): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo e(Str::startsWith($current, 'inventory') ? 'active' : ''); ?>"
                       href="#"
                       data-bs-toggle="collapse"
                       data-bs-target="#inventorySubmenu"
                       aria-expanded="<?php echo e(Str::startsWith($current, 'inventory') ? 'true' : 'false'); ?>"
                       aria-controls="inventorySubmenu">
                        <i class="bi bi-archive-fill"></i>
                        <span>Inventory &amp; Stock</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse <?php echo e(Str::startsWith($current, 'inventory') ? 'show' : ''); ?>" id="inventorySubmenu">
                        <ul class="nav nav-submenu">
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('stockmanagement-view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'inventory.stock-management' ? 'active' : ''); ?>" href="<?php echo e(route('inventory.stock-management')); ?>">
                                    <i class="bi bi-box-seam-fill"></i>
                                    <span>Stock Levels</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('stocktransfer-view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'inventory.stock-transfers' ? 'active' : ''); ?>" href="<?php echo e(route('inventory.stock-transfers')); ?>">
                                    <i class="bi bi-arrow-left-right"></i>
                                    <span>Stock Transfers</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('inventoryadjustment-view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'inventory.adjustments' ? 'active' : ''); ?>" href="<?php echo e(route('inventory.adjustments')); ?>">
                                    <i class="bi bi-sliders2"></i>
                                    <span>Adjustments</span>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </li>
                <?php endif; ?>

                
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['product-view', 'category-view', 'brand-view', 'productattribute-view', 'unitofmeasure-view', 'taxrate-view', 'hsncode-view'])): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo e(Str::startsWith($current, 'catalog') && $current !== 'catalog.warehouses' ? 'active' : ''); ?>"
                       href="#"
                       data-bs-toggle="collapse"
                       data-bs-target="#catalogSubmenu"
                       aria-expanded="<?php echo e(Str::startsWith($current, 'catalog') && $current !== 'catalog.warehouses' ? 'true' : 'false'); ?>"
                       aria-controls="catalogSubmenu">
                        <i class="bi bi-shop-window"></i>
                        <span>Catalog Management</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse <?php echo e(Str::startsWith($current, 'catalog') && $current !== 'catalog.warehouses' ? 'show' : ''); ?>" id="catalogSubmenu">
                        <ul class="nav nav-submenu">
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('product-view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'catalog.products' ? 'active' : ''); ?>" href="<?php echo e(route('catalog.products')); ?>">
                                    <i class="bi bi-box-seam-fill"></i>
                                    <span>Products</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('category-view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'catalog.categories' ? 'active' : ''); ?>" href="<?php echo e(route('catalog.categories')); ?>">
                                    <i class="bi bi-diagram-3-fill"></i>
                                    <span>Categories</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('brand-view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'catalog.brands' ? 'active' : ''); ?>" href="<?php echo e(route('catalog.brands')); ?>">
                                    <i class="bi bi-patch-check-fill"></i>
                                    <span>Brands</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('productattribute-view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'catalog.attributes' ? 'active' : ''); ?>" href="<?php echo e(route('catalog.attributes')); ?>">
                                    <i class="bi bi-sliders2"></i>
                                    <span>Attributes</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('unitofmeasure-view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'catalog.uom' ? 'active' : ''); ?>" href="<?php echo e(route('catalog.uom')); ?>">
                                    <i class="bi bi-rulers"></i>
                                    <span>Units of Measure</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('taxrate-view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'catalog.tax-rates' ? 'active' : ''); ?>" href="<?php echo e(route('catalog.tax-rates')); ?>">
                                    <i class="bi bi-percent"></i>
                                    <span>Tax Rates</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('hsncode-view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'catalog.hsn-codes' ? 'active' : ''); ?>" href="<?php echo e(route('catalog.hsn-codes')); ?>">
                                    <i class="bi bi-upc-scan"></i>
                                    <span>HSN Codes</span>
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

                
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['user-view', 'role-view', 'village-view', 'settings-view'])): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo e($current === 'users' || $current === 'roles-permissions' || $current === 'customers' || $current === 'villages' || $current === 'order.reasons' || $current === 'call-tags.index' || $current === 'customer-settings.index' ? 'active' : ''); ?>"
                       href="#"
                       data-bs-toggle="collapse"
                       data-bs-target="#peopleSubmenu"
                       aria-expanded="<?php echo e($current === 'users' || $current === 'roles-permissions' || $current === 'customers' || $current === 'villages' || $current === 'order.reasons' || $current === 'call-tags.index' || $current === 'customer-settings.index' ? 'true' : 'false'); ?>"
                       aria-controls="peopleSubmenu">
                        <i class="bi bi-people-fill"></i>
                        <span>People &amp; Admin</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse <?php echo e($current === 'users' || $current === 'roles-permissions' || $current === 'customers' || $current === 'villages' || $current === 'order.reasons' || $current === 'call-tags.index' || $current === 'customer-settings.index' ? 'show' : ''); ?>" id="peopleSubmenu">
                        <ul class="nav nav-submenu">
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('user-view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'users' ? 'active' : ''); ?>" href="<?php echo e(route('users')); ?>">
                                    <i class="bi bi-person-fill-gear"></i>
                                    <span>Users</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('role-view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'roles-permissions' ? 'active' : ''); ?>" href="<?php echo e(route('roles-permissions')); ?>">
                                    <i class="bi bi-shield-lock-fill"></i>
                                    <span>Roles &amp; Permissions</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (\Illuminate\Support\Facades\Blade::check('role', 'Super Admin')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'customers' ? 'active' : ''); ?>" href="<?php echo e(route('customers')); ?>">
                                    <i class="bi bi-person-lines-fill"></i>
                                    <span>Customers</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (\Illuminate\Support\Facades\Blade::check('role', 'Super Admin')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'customer-settings.index' ? 'active' : ''); ?>" href="<?php echo e(route('customer-settings.index')); ?>">
                                    <i class="bi bi-gear-wide-connected"></i>
                                    <span>Customer Settings</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('village-view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'villages' ? 'active' : ''); ?>" href="<?php echo e(route('villages')); ?>">
                                    <i class="bi bi-geo-alt-fill"></i>
                                    <span>Villages</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('orderreason-view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'order.reasons' ? 'active' : ''); ?>" href="<?php echo e(route('order.reasons')); ?>">
                                    <i class="bi bi-list-task"></i>
                                    <span>Order Reasons</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('settings-view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'call-tags.index' ? 'active' : ''); ?>" href="<?php echo e(route('call-tags.index')); ?>">
                                    <i class="bi bi-tags"></i>
                                    <span>Call Tags</span>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </li>
                <?php endif; ?>

                
                <li class="nav-item">
                    <a class="nav-link <?php echo e($current === 'chat.index' || $current === 'messages' || $current === 'calendar' || $current === 'files' || $current === 'forms' || Str::startsWith($current, 'elements') || $current === 'settings' || $current === 'security' || $current === 'help' ? 'active' : ''); ?>"
                       href="#"
                       data-bs-toggle="collapse"
                       data-bs-target="#toolsSubmenu"
                       aria-expanded="<?php echo e($current === 'chat.index' || $current === 'messages' || $current === 'calendar' || $current === 'files' || $current === 'forms' || Str::startsWith($current, 'elements') || $current === 'settings' || $current === 'security' || $current === 'help' ? 'true' : 'false'); ?>"
                       aria-controls="toolsSubmenu">
                        <i class="bi bi-wrench-adjustable-circle-fill"></i>
                        <span>Utilities &amp; Workspace</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse <?php echo e($current === 'chat.index' || $current === 'messages' || $current === 'calendar' || $current === 'files' || $current === 'forms' || Str::startsWith($current, 'elements') || $current === 'settings' || $current === 'security' || $current === 'help' ? 'show' : ''); ?>" id="toolsSubmenu">
                        <ul class="nav nav-submenu">
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'chat.index' ? 'active' : ''); ?>" href="<?php echo e(route('chat.index')); ?>">
                                    <i class="bi bi-chat-text-fill"></i>
                                    <span>Team Chat</span>
                                    <span class="badge bg-primary rounded-pill ms-auto">New</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'messages' ? 'active' : ''); ?>" href="<?php echo e(route('messages')); ?>">
                                    <i class="bi bi-chat-dots-fill"></i>
                                    <span>Messages</span>
                                    <span class="badge bg-danger rounded-pill ms-auto">3</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'calendar' ? 'active' : ''); ?>" href="<?php echo e(route('calendar')); ?>">
                                    <i class="bi bi-calendar-week-fill"></i>
                                    <span>Calendar</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'files' ? 'active' : ''); ?>" href="<?php echo e(route('files')); ?>">
                                    <i class="bi bi-folder2-open"></i>
                                    <span>Files</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'forms' ? 'active' : ''); ?>" href="<?php echo e(route('forms')); ?>">
                                    <i class="bi bi-ui-checks-grid"></i>
                                    <span>Forms</span>
                                </a>
                            </li>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('settings-view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e(Str::startsWith($current, 'elements') ? 'active' : ''); ?>" href="<?php echo e(route('elements')); ?>">
                                    <i class="bi bi-puzzle-fill"></i>
                                    <span>UI Elements</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'security' ? 'active' : ''); ?>" href="<?php echo e(route('security')); ?>">
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
                                <a class="nav-link <?php echo e($current === 'help' ? 'active' : ''); ?>" href="<?php echo e(route('help')); ?>">
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
<?php /**PATH /home/user/metis/resources/views/components/sidebar.blade.php ENDPATH**/ ?>