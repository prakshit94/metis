<!-- Header -->
<header class="admin-header" role="banner">
    <nav class="navbar admin-navbar" aria-label="Main navigation">
        <div class="container-fluid admin-navbar-inner">

            
            <a class="admin-brand" href="<?php echo e(route('dashboard')); ?>" aria-label="Metis Admin — go to dashboard">
                <img src="/assets/images/logo.svg" alt="" width="32" height="32" aria-hidden="true">
                <span class="admin-brand-name">Metis</span>
                <span class="admin-brand-badge">Admin</span>
            </a>

            
            <button class="hamburger-menu"
                    type="button"
                    data-sidebar-toggle
                    aria-label="Toggle sidebar"
                    aria-controls="admin-sidebar"
                    aria-expanded="false">
                <i class="bi bi-layout-sidebar-inset" aria-hidden="true"></i>
            </button>

            
            <div class="header-search-wrapper flex-grow-1" x-data="searchComponent">
                <div class="header-search position-relative">
                    <i class="bi bi-search header-search-icon" aria-hidden="true"></i>
                    <input type="search"
                           class="header-search-input"
                           placeholder="Search pages, users, orders…"
                           x-model="query"
                           @input="search()"
                           @keydown.ctrl.k.prevent.window="$el.focus()"
                           data-search-input
                           aria-label="Search"
                           autocomplete="off">
                    <kbd class="header-search-kbd d-none d-lg-flex">Ctrl K</kbd>

                    
                    <div x-show="results.length > 0"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 translate-y-1"
                         class="header-search-results">
                        <template x-for="result in results" :key="result.title">
                            <a :href="result.url" class="header-search-result-item">
                                <span class="header-search-result-icon">
                                    <i class="bi bi-file-text"></i>
                                </span>
                                <span class="header-search-result-title" x-text="result.title"></span>
                                <span class="header-search-result-type" x-text="result.type"></span>
                            </a>
                        </template>
                    </div>
                </div>
            </div>

            
            <div class="header-actions">

                
                <div x-data="themeSwitch">
                    <button class="header-action-btn"
                            type="button"
                            @click="toggle()"
                            data-bs-toggle="tooltip"
                            data-bs-placement="bottom"
                            :title="currentTheme === 'light' ? 'Switch to dark mode' : 'Switch to light mode'"
                            aria-label="Toggle theme">
                        <i class="bi bi-sun-fill" x-show="currentTheme === 'light'" aria-hidden="true"></i>
                        <i class="bi bi-moon-stars-fill" x-show="currentTheme === 'dark'" aria-hidden="true"></i>
                    </button>
                </div>

                
                <button class="header-action-btn d-none d-md-flex"
                        type="button"
                        data-fullscreen-toggle
                        data-bs-toggle="tooltip"
                        data-bs-placement="bottom"
                        title="Toggle fullscreen"
                        aria-label="Toggle fullscreen">
                    <i class="bi bi-arrows-fullscreen" aria-hidden="true"></i>
                </button>

                
                <div class="dropdown" id="notifications-dropdown">
                    <button class="header-action-btn header-action-btn--notify position-relative"
                            type="button"
                            id="notificationsMenuBtn"
                            data-bs-toggle="dropdown"
                            data-bs-auto-close="outside"
                            aria-expanded="false"
                            aria-label="Notifications (3 unread)">
                        <i class="bi bi-bell-fill" aria-hidden="true"></i>
                        <span class="header-notify-dot" aria-hidden="true">3</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end header-notify-panel"
                         aria-labelledby="notificationsMenuBtn"
                         role="dialog"
                         aria-label="Notifications">
                        <div class="header-notify-header">
                            <span class="header-notify-title">Notifications</span>
                            <span class="badge bg-danger rounded-pill">3 new</span>
                        </div>
                        <div class="header-notify-list">
                            <a class="header-notify-item" href="#">
                                <span class="header-notify-avatar header-notify-avatar--success">
                                    <i class="bi bi-person-plus-fill"></i>
                                </span>
                                <span class="header-notify-body">
                                    <span class="header-notify-msg">New user registered</span>
                                    <span class="header-notify-time">2 min ago</span>
                                </span>
                                <span class="header-notify-unread-dot"></span>
                            </a>
                            <a class="header-notify-item" href="#">
                                <span class="header-notify-avatar header-notify-avatar--warning">
                                    <i class="bi bi-hdd-fill"></i>
                                </span>
                                <span class="header-notify-body">
                                    <span class="header-notify-msg">Server disk usage at 85%</span>
                                    <span class="header-notify-time">18 min ago</span>
                                </span>
                                <span class="header-notify-unread-dot"></span>
                            </a>
                            <a class="header-notify-item" href="#">
                                <span class="header-notify-avatar header-notify-avatar--info">
                                    <i class="bi bi-chat-dots-fill"></i>
                                </span>
                                <span class="header-notify-body">
                                    <span class="header-notify-msg">New message from support</span>
                                    <span class="header-notify-time">1 hr ago</span>
                                </span>
                                <span class="header-notify-unread-dot"></span>
                            </a>
                        </div>
                        <div class="header-notify-footer">
                            <a href="<?php echo e(route('messages')); ?>" class="header-notify-view-all">
                                <i class="bi bi-collection-fill me-1"></i>
                                View all notifications
                            </a>
                        </div>
                    </div>
                </div>

                
                <div class="header-divider" aria-hidden="true"></div>

                
                <div class="dropdown" id="user-menu-dropdown">
                    <button class="header-user-btn"
                            type="button"
                            id="userMenuBtn"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                            aria-label="User menu">
                        <img src="/assets/images/avatar-placeholder.svg"
                             alt="<?php echo e(Auth::user()?->name ?? 'User'); ?>"
                             width="34"
                             height="34"
                             class="header-user-avatar">
                        <span class="header-user-info d-none d-lg-flex">
                            <span class="header-user-name"><?php echo e(Auth::user()?->name ?? 'User'); ?></span>
                            <span class="header-user-role">Administrator</span>
                        </span>
                        <i class="bi bi-chevron-down header-user-chevron d-none d-lg-inline" aria-hidden="true"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end header-user-menu"
                        aria-labelledby="userMenuBtn">
                        
                        <li class="header-user-menu-info">
                            <img src="/assets/images/avatar-placeholder.svg"
                                 alt="<?php echo e(Auth::user()?->name ?? 'User'); ?>"
                                 width="40" height="40"
                                 class="header-user-menu-avatar">
                            <div>
                                <div class="header-user-menu-name"><?php echo e(Auth::user()?->name ?? 'User'); ?></div>
                                <div class="header-user-menu-email"><?php echo e(Auth::user()?->email ?? ''); ?></div>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <a class="dropdown-item header-user-menu-item" href="#">
                                <i class="bi bi-person-fill"></i>
                                <span>My Profile</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item header-user-menu-item" href="<?php echo e(route('settings')); ?>">
                                <i class="bi bi-gear-fill"></i>
                                <span>Settings</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item header-user-menu-item" href="<?php echo e(route('security')); ?>">
                                <i class="bi bi-shield-fill-check"></i>
                                <span>Security</span>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <form method="POST" action="<?php echo e(route('logout')); ?>">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="dropdown-item header-user-menu-item header-user-menu-item--danger">
                                    <i class="bi bi-box-arrow-right"></i>
                                    <span>Sign Out</span>
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>

            </div>

        </div>
    </nav>
</header>
<?php /**PATH /home/ubuntu/metis/resources/views/components/header.blade.php ENDPATH**/ ?>