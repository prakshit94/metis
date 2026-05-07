<aside class="admin-sidebar" id="admin-sidebar">
            <div class="sidebar-content">
                <nav class="sidebar-nav">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(Route::is('dashboard') ? 'active' : ''); ?>" href="<?php echo e(route('dashboard')); ?>">
                                <i class="bi bi-speedometer2"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="https://dashboardpack.com/?utm_source=metis&amp;utm_medium=sidebar&amp;utm_campaign=go_pro_metis" target="_blank" rel="noopener">
                                <i class="bi bi-rocket-takeoff"></i>
                                <span>Go Pro</span>
                                <span class="badge bg-danger rounded-pill ms-auto">Hot</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(Route::is('analytics') ? 'active' : ''); ?>" href="<?php echo e(route('analytics')); ?>">
                                <i class="bi bi-graph-up"></i>
                                <span>Analytics</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(Route::is('users') ? 'active' : ''); ?>" href="<?php echo e(route('users')); ?>">
                                <i class="bi bi-people"></i>
                                <span>Users</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(Route::is('products') ? 'active' : ''); ?>" href="<?php echo e(route('products')); ?>">
                                <i class="bi bi-box"></i>
                                <span>Products</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(Route::is('orders') ? 'active' : ''); ?>" href="<?php echo e(route('orders')); ?>">
                                <i class="bi bi-bag-check"></i>
                                <span>Orders</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(Route::is('forms') ? 'active' : ''); ?>" href="<?php echo e(route('forms')); ?>">
                                <i class="bi bi-ui-checks"></i>
                                <span>Forms</span>
                                <span class="badge bg-success rounded-pill ms-auto">New</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(Route::is('elements*') ? 'active' : ''); ?>" href="#" data-bs-toggle="collapse" data-bs-target="#elementsSubmenu" aria-expanded="false">
                                <i class="bi bi-puzzle"></i>
                                <span>Elements</span>
                                <span class="badge bg-primary rounded-pill ms-2 me-2">New</span>
                                <i class="bi bi-chevron-down ms-auto"></i>
                            </a>
                            <div class="collapse" id="elementsSubmenu">
                                <ul class="nav nav-submenu">
                                    <li class="nav-item">
                                        <a class="nav-link <?php echo e(Route::is('elements') ? 'active' : ''); ?>" href="<?php echo e(route('elements')); ?>">
                                            <i class="bi bi-grid"></i>
                                            <span>Overview</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?php echo e(Route::is('elements-buttons') ? 'active' : ''); ?>" href="<?php echo e(route('elements-buttons')); ?>">
                                            <i class="bi bi-square"></i>
                                            <span>Buttons</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?php echo e(Route::is('elements-alerts') ? 'active' : ''); ?>" href="<?php echo e(route('elements-alerts')); ?>">
                                            <i class="bi bi-exclamation-triangle"></i>
                                            <span>Alerts</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?php echo e(Route::is('elements-badges') ? 'active' : ''); ?>" href="<?php echo e(route('elements-badges')); ?>">
                                            <i class="bi bi-award"></i>
                                            <span>Badges</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?php echo e(Route::is('elements-cards') ? 'active' : ''); ?>" href="<?php echo e(route('elements-cards')); ?>">
                                            <i class="bi bi-card-text"></i>
                                            <span>Cards</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?php echo e(Route::is('elements-modals') ? 'active' : ''); ?>" href="<?php echo e(route('elements-modals')); ?>">
                                            <i class="bi bi-window"></i>
                                            <span>Modals</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?php echo e(Route::is('elements-forms') ? 'active' : ''); ?>" href="<?php echo e(route('elements-forms')); ?>">
                                            <i class="bi bi-ui-checks"></i>
                                            <span>Forms</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?php echo e(Route::is('elements-tables') ? 'active' : ''); ?>" href="<?php echo e(route('elements-tables')); ?>">
                                            <i class="bi bi-table"></i>
                                            <span>Tables</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(Route::is('reports') ? 'active' : ''); ?>" href="<?php echo e(route('reports')); ?>">
                                <i class="bi bi-file-earmark-text"></i>
                                <span>Reports</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(Route::is('messages') ? 'active' : ''); ?>" href="<?php echo e(route('messages')); ?>">
                                <i class="bi bi-chat-dots"></i>
                                <span>Messages</span>
                                <span class="badge bg-danger rounded-pill ms-auto">3</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(Route::is('calendar') ? 'active' : ''); ?>" href="<?php echo e(route('calendar')); ?>">
                                <i class="bi bi-calendar-event"></i>
                                <span>Calendar</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(Route::is('files') ? 'active' : ''); ?>" href="<?php echo e(route('files')); ?>">
                                <i class="bi bi-folder2-open"></i>
                                <span>Files</span>
                            </a>
                        </li>
                        <li class="nav-item mt-3">
                            <small class="text-muted px-3 text-uppercase fw-bold">Admin</small>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(Route::is('settings') ? 'active' : ''); ?>" href="<?php echo e(route('settings')); ?>">
                                <i class="bi bi-gear"></i>
                                <span>Settings</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(Route::is('security') ? 'active' : ''); ?>" href="<?php echo e(route('security')); ?>">
                                <i class="bi bi-shield-check"></i>
                                <span>Security</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(Route::is('help') ? 'active' : ''); ?>" href="<?php echo e(route('help')); ?>">
                                <i class="bi bi-question-circle"></i>
                                <span>Help & Support</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside><?php /**PATH /home/ubuntu/Bootstrap-Admin-Template/resources/views/components/sidebar.blade.php ENDPATH**/ ?>