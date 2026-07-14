<?php
    $current = request()->route() ? request()->route()->getName() : 'dashboard';
?>

<!-- Sidebar -->
<aside class="admin-sidebar" id="admin-sidebar">
    <div class="sidebar-content">
        <nav class="sidebar-nav">
            <ul class="nav flex-column">

                
                <li class="nav-item sidebar-section-label">
                    <small class="text-muted px-3 text-uppercase fw-bold">Main</small>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?php echo e($current === 'dashboard' ? 'active' : ''); ?>" href="<?php echo e(route('dashboard')); ?>">
                        <i class="bi bi-grid-1x2-fill"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo e($current === 'analytics' ? 'active' : ''); ?>" href="<?php echo e(route('analytics')); ?>">
                        <i class="bi bi-bar-chart-line-fill"></i>
                        <span>Analytics</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo e($current === 'reports' ? 'active' : ''); ?>" href="<?php echo e(route('reports')); ?>">
                        <i class="bi bi-file-earmark-bar-graph-fill"></i>
                        <span>Reports</span>
                    </a>
                </li>

                
                <li class="nav-item sidebar-section-label mt-3">
                    <small class="text-muted px-3 text-uppercase fw-bold">Operations</small>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?php echo e($current === 'orders' ? 'active' : ''); ?>" href="<?php echo e(route('orders')); ?>">
                        <i class="bi bi-bag-check-fill"></i>
                        <span>Orders</span>
                    </a>
                </li>

                
                <li class="nav-item">
                    <a class="nav-link <?php echo e(Str::startsWith($current, 'shipping') ? 'active' : ''); ?>"
                       href="#"
                       data-bs-toggle="collapse"
                       data-bs-target="#shippingSubmenu"
                       aria-expanded="<?php echo e(Str::startsWith($current, 'shipping') ? 'true' : 'false'); ?>"
                       aria-controls="shippingSubmenu">
                        <i class="bi bi-truck"></i>
                        <span>Shipping &amp; Logistics</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse <?php echo e(Str::startsWith($current, 'shipping') ? 'show' : ''); ?>" id="shippingSubmenu">
                        <ul class="nav nav-submenu">
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'shipping.shipments' ? 'active' : ''); ?>" href="<?php echo e(route('shipping.shipments')); ?>">
                                    <i class="bi bi-geo-alt-fill"></i>
                                    <span>Shipments &amp; Tracking</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'shipping.services' ? 'active' : ''); ?>" href="<?php echo e(route('shipping.services')); ?>">
                                    <i class="bi bi-gear-wide-connected"></i>
                                    <span>Shipping Services</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                
                <li class="nav-item">
                    <a class="nav-link <?php echo e(Str::startsWith($current, 'inventory') ? 'active' : ''); ?>"
                       href="#"
                       data-bs-toggle="collapse"
                       data-bs-target="#inventorySubmenu"
                       aria-expanded="<?php echo e(Str::startsWith($current, 'inventory') ? 'true' : 'false'); ?>"
                       aria-controls="inventorySubmenu">
                        <i class="bi bi-archive-fill"></i>
                        <span>Inventory</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse <?php echo e(Str::startsWith($current, 'inventory') ? 'show' : ''); ?>" id="inventorySubmenu">
                        <ul class="nav nav-submenu">
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'inventory.stock-management' ? 'active' : ''); ?>" href="<?php echo e(route('inventory.stock-management')); ?>">
                                    <i class="bi bi-box-seam-fill"></i>
                                    <span>Stock Levels</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'inventory.stock-transfers' ? 'active' : ''); ?>" href="<?php echo e(route('inventory.stock-transfers')); ?>">
                                    <i class="bi bi-arrow-left-right"></i>
                                    <span>Stock Transfers</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'inventory.adjustments' ? 'active' : ''); ?>" href="<?php echo e(route('inventory.adjustments')); ?>">
                                    <i class="bi bi-sliders2"></i>
                                    <span>Adjustments</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                
                <li class="nav-item">
                    <a class="nav-link <?php echo e(Str::startsWith($current, 'catalog') ? 'active' : ''); ?>"
                       href="#"
                       data-bs-toggle="collapse"
                       data-bs-target="#catalogSubmenu"
                       aria-expanded="<?php echo e(Str::startsWith($current, 'catalog') ? 'true' : 'false'); ?>"
                       aria-controls="catalogSubmenu">
                        <i class="bi bi-shop-window"></i>
                        <span>Catalog</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse <?php echo e(Str::startsWith($current, 'catalog') ? 'show' : ''); ?>" id="catalogSubmenu">
                        <ul class="nav nav-submenu">
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'catalog.products' ? 'active' : ''); ?>" href="<?php echo e(route('catalog.products')); ?>">
                                    <i class="bi bi-box-seam-fill"></i>
                                    <span>Products</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'catalog.categories' ? 'active' : ''); ?>" href="<?php echo e(route('catalog.categories')); ?>">
                                    <i class="bi bi-diagram-3-fill"></i>
                                    <span>Categories</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'catalog.brands' ? 'active' : ''); ?>" href="<?php echo e(route('catalog.brands')); ?>">
                                    <i class="bi bi-patch-check-fill"></i>
                                    <span>Brands</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'catalog.attributes' ? 'active' : ''); ?>" href="<?php echo e(route('catalog.attributes')); ?>">
                                    <i class="bi bi-sliders2"></i>
                                    <span>Attributes</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'catalog.uom' ? 'active' : ''); ?>" href="<?php echo e(route('catalog.uom')); ?>">
                                    <i class="bi bi-rulers"></i>
                                    <span>Units of Measure</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'catalog.tax-rates' ? 'active' : ''); ?>" href="<?php echo e(route('catalog.tax-rates')); ?>">
                                    <i class="bi bi-percent"></i>
                                    <span>Tax Rates</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'catalog.hsn-codes' ? 'active' : ''); ?>" href="<?php echo e(route('catalog.hsn-codes')); ?>">
                                    <i class="bi bi-upc-scan"></i>
                                    <span>HSN Codes</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'catalog.warehouses' ? 'active' : ''); ?>" href="<?php echo e(route('catalog.warehouses')); ?>">
                                    <i class="bi bi-buildings-fill"></i>
                                    <span>Warehouses</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <hr class="dropdown-divider bg-secondary opacity-25 mx-3 my-2">
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'promotions.coupons' ? 'active' : ''); ?>" href="<?php echo e(route('promotions.coupons')); ?>">
                                    <i class="bi bi-ticket-perforated-fill"></i>
                                    <span>Coupon Codes</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'promotions.offers' ? 'active' : ''); ?>" href="<?php echo e(route('promotions.offers')); ?>">
                                    <i class="bi bi-star-fill"></i>
                                    <span>Offers &amp; Deals</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>



                
                <li class="nav-item sidebar-section-label mt-3">
                    <small class="text-muted px-3 text-uppercase fw-bold">People</small>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?php echo e($current === 'users' ? 'active' : ''); ?>" href="<?php echo e(route('users')); ?>">
                        <i class="bi bi-people-fill"></i>
                        <span>Users</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo e($current === 'roles-permissions' ? 'active' : ''); ?>" href="<?php echo e(route('roles-permissions')); ?>">
                        <i class="bi bi-shield-lock-fill"></i>
                        <span>Roles &amp; Permissions</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo e($current === 'customers' ? 'active' : ''); ?>" href="<?php echo e(route('customers')); ?>">
                        <i class="bi bi-person-lines-fill"></i>
                        <span>Customers</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo e($current === 'villages' ? 'active' : ''); ?>" href="<?php echo e(route('villages')); ?>">
                        <i class="bi bi-geo-alt-fill"></i>
                        <span>Villages</span>
                    </a>
                </li>

                
                <li class="nav-item sidebar-section-label mt-3">
                    <small class="text-muted px-3 text-uppercase fw-bold">Communication</small>
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

                
                <li class="nav-item sidebar-section-label mt-3">
                    <small class="text-muted px-3 text-uppercase fw-bold">Content</small>
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

                
                <li class="nav-item">
                    <a class="nav-link <?php echo e(Str::startsWith($current, 'elements') ? 'active' : ''); ?>"
                       href="#"
                       data-bs-toggle="collapse"
                       data-bs-target="#elementsSubmenu"
                       aria-expanded="<?php echo e(Str::startsWith($current, 'elements') ? 'true' : 'false'); ?>"
                       aria-controls="elementsSubmenu">
                        <i class="bi bi-puzzle-fill"></i>
                        <span>Elements</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse <?php echo e(Str::startsWith($current, 'elements') ? 'show' : ''); ?>" id="elementsSubmenu">
                        <ul class="nav nav-submenu">
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'elements' ? 'active' : ''); ?>" href="<?php echo e(route('elements')); ?>">
                                    <i class="bi bi-grid-3x3-gap-fill"></i>
                                    <span>Overview</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'elements-buttons' ? 'active' : ''); ?>" href="<?php echo e(route('elements-buttons')); ?>">
                                    <i class="bi bi-hand-index-thumb-fill"></i>
                                    <span>Buttons</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'elements-alerts' ? 'active' : ''); ?>" href="<?php echo e(route('elements-alerts')); ?>">
                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                    <span>Alerts</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'elements-badges' ? 'active' : ''); ?>" href="<?php echo e(route('elements-badges')); ?>">
                                    <i class="bi bi-tag-fill"></i>
                                    <span>Badges</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'elements-cards' ? 'active' : ''); ?>" href="<?php echo e(route('elements-cards')); ?>">
                                    <i class="bi bi-credit-card-2-front-fill"></i>
                                    <span>Cards</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'elements-modals' ? 'active' : ''); ?>" href="<?php echo e(route('elements-modals')); ?>">
                                    <i class="bi bi-window-stack"></i>
                                    <span>Modals</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'elements-forms' ? 'active' : ''); ?>" href="<?php echo e(route('elements-forms')); ?>">
                                    <i class="bi bi-input-cursor-text"></i>
                                    <span>Forms</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e($current === 'elements-tables' ? 'active' : ''); ?>" href="<?php echo e(route('elements-tables')); ?>">
                                    <i class="bi bi-table"></i>
                                    <span>Tables</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                
                <li class="nav-item sidebar-section-label mt-3">
                    <small class="text-muted px-3 text-uppercase fw-bold">Admin</small>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?php echo e($current === 'settings' ? 'active' : ''); ?>" href="<?php echo e(route('settings')); ?>">
                        <i class="bi bi-gear-fill"></i>
                        <span>Settings</span>
                    </a>
                </li>
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
        </nav>
    </div>
</aside>
<?php /**PATH /home/ubuntu/metis/resources/views/components/sidebar.blade.php ENDPATH**/ ?>