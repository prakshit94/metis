<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <!-- Meta Tags -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta name="description" content="<?php echo $__env->yieldContent('description', 'Modern Bootstrap 5 Admin Template - Clean, responsive dashboard'); ?>">
    <meta name="keywords" content="bootstrap, admin, dashboard, template, modern, responsive">
    <meta name="author" content="Bootstrap Admin Template">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <meta name="user-name" content="<?php echo e(auth()->user()->name ?? ''); ?>">

    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?php echo $__env->yieldContent('title', 'Ecommerce Admin'); ?>">
    <meta property="og:description" content="Clean and modern admin dashboard template built with Bootstrap 5">
    <meta property="og:type" content="website">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/assets/icons/favicon.svg">
    <link rel="icon" type="image/png" href="/assets/icons/favicon.png">

    <!-- Preconnect to external domains -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Title -->
    <title><?php echo $__env->yieldContent('title', 'Dashboard'); ?> - Ecommerce Admin</title>

    <!-- Theme Color -->
    <meta name="theme-color" content="#6366f1">

    <!-- PWA Manifest -->
    <link rel="manifest" href="/manifest.json">

    <!-- Vite Assets -->
    
    <script>
        (function() {
            var t = localStorage.getItem('theme') ||
                    (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-bs-theme', t);
        })();
    </script>

    <?php echo app('Illuminate\Foundation\Vite')(['resources/scss/main.scss', 'resources/js/main.js']); ?>

    <?php echo $__env->yieldPushContent('head'); ?>
</head>

<body data-page="<?php echo $__env->yieldContent('page', 'dashboard'); ?>" class="admin-layout">
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <!-- Loading Screen -->
    <div id="loading-screen" class="loading-screen">
        <div class="loading-spinner">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>

    <!-- Main Wrapper -->
    <div class="admin-wrapper" id="admin-wrapper">

        <?php
            $hasSidebarPermission = auth()->check() && (auth()->user()->hasRole('Super Admin') || auth()->user()->can('sidebar-view'));
            $hideSidebar = (isset($hideSidebar) && $hideSidebar) || !$hasSidebarPermission;
        ?>

        <!-- Header -->
        <?php echo $__env->make('components.header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <!-- Sidebar -->
        <?php if(!$hideSidebar): ?>
            <?php echo $__env->make('components.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php endif; ?>

        <!-- Sidebar Backdrop (mobile overlay) -->
        <div class="sidebar-backdrop" aria-hidden="true"></div>

        <!-- Main Content -->
        <main id="main-content" class="admin-main" style="<?php echo e($hideSidebar ? 'margin-left: 0;' : ''); ?>">
            <div class="container-fluid p-4 p-lg-5">
                
                <?php if(!request()->routeIs('dashboard') && !request()->routeIs('inventory.dashboard')): ?>
                    <?php
                        $segmentMap = [
                            'orders' => 'Order Management',
                            'invoices' => 'Order Management',
                            'payments' => 'Order Management',
                            'refunds' => 'Order Management',
                            'returns' => 'Order Management',
                            'credit-notes' => 'Order Management',
                            'order-reasons' => 'Order Management',
                            'catalog' => 'Catalog Management',
                            'promotions' => 'Sales & Marketing',
                            'inventory' => 'Inventory & Stock',
                            'procurement' => 'Procurement',
                            'shipping' => 'Logistics & Warehouses',
                            'customers' => 'User & Customer Admin',
                            'users' => 'User & Customer Admin',
                            'roles-permissions' => 'User & Customer Admin',
                            'villages' => 'User & Customer Admin',
                            'customer-settings' => 'User & Customer Admin',
                            'call-tags-admin' => 'User & Customer Admin',
                            'chat' => 'Utilities & Tools',
                            'messages' => 'Utilities & Tools',
                            'calendar' => 'Utilities & Tools',
                            'files' => 'Utilities & Tools',
                            'forms' => 'Utilities & Tools',
                            'security' => 'Utilities & Tools',
                            'admin' => 'Utilities & Tools',
                        ];
                        $firstSegment = request()->segment(1);
                        $moduleName = $segmentMap[$firstSegment] ?? 'Home';
                    ?>
                    <nav aria-label="breadcrumb" class="mb-4">
                        <ol class="breadcrumb m-0 fs-14 fw-semibold">
                            <li class="breadcrumb-item text-muted">
                                <?php if($moduleName === 'Home'): ?>
                                    <a href="<?php echo e(route('dashboard')); ?>" class="text-decoration-none text-muted"><i class="bi bi-house-door-fill me-1"></i>Home</a>
                                <?php else: ?>
                                    <?php echo e($moduleName); ?>

                                <?php endif; ?>
                            </li>
                            <?php $__currentLoopData = request()->segments(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $segment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php if(!$loop->last): ?>
                                    <li class="breadcrumb-item text-capitalize"><a href="<?php echo e(url(implode('/', array_slice(request()->segments(), 0, $loop->iteration)))); ?>" class="text-decoration-none text-muted"><?php echo e(str_replace('-', ' ', $segment)); ?></a></li>
                                <?php else: ?>
                                    <li class="breadcrumb-item active text-capitalize text-primary" aria-current="page"><?php echo e(str_replace('-', ' ', $segment)); ?></li>
                                <?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ol>
                    </nav>
                <?php endif; ?>
                
                <?php echo $__env->yieldContent('content'); ?>
            </div>
        </main>

        <!-- Footer -->
        <?php echo $__env->make('components.footer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    </div><!-- /.admin-wrapper -->

    <?php if(config('chat.enabled')): ?>
        <?php echo $__env->make('partials.chat_widget', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php endif; ?>

    <!-- Toast Container -->
    <div aria-live="polite" aria-atomic="true" class="toast-stack position-fixed end-0 p-3">
        <div id="toast-container"></div>
    </div>

    <?php echo $__env->yieldPushContent('modals'); ?>
    <?php echo $__env->yieldPushContent('scripts'); ?>
    <script>
        document.addEventListener('show.bs.dropdown', function (event) {
            var responsiveContainer = event.target.closest('.table-responsive');
            if (responsiveContainer) {
                responsiveContainer.style.overflow = 'visible';
            }
        });
        
        document.addEventListener('hide.bs.dropdown', function (event) {
            var responsiveContainer = event.target.closest('.table-responsive');
            if (responsiveContainer) {
                responsiveContainer.style.overflow = '';
            }
        });

        // Check for pending toasts from previous pages
        document.addEventListener('DOMContentLoaded', function() {
            var pendingToast = sessionStorage.getItem('pending_toast');
            if (pendingToast) {
                // Wait a tiny bit for AdminApp to be fully initialized
                setTimeout(function() {
                    if (window.AdminApp && window.AdminApp.notificationManager) {
                        window.AdminApp.notificationManager.success(pendingToast);
                    }
                }, 100);
                sessionStorage.removeItem('pending_toast');
            }
        });
    </script>
</body>
</html>
<?php /**PATH /home/user/metis/resources/views/layouts/app.blade.php ENDPATH**/ ?>