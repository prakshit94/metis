<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <!-- Meta Tags -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta name="description" content="@yield('description', 'Modern Bootstrap 5 Admin Template - Clean, responsive dashboard')">
    <meta name="keywords" content="bootstrap, admin, dashboard, template, modern, responsive">
    <meta name="author" content="Bootstrap Admin Template">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-name" content="{{ auth()->user()->name ?? '' }}">

    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="@yield('title', 'Ecommerce Admin')">
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
    <title>@yield('title', 'Dashboard') - Ecommerce Admin</title>

    <!-- Theme Color -->
    <meta name="theme-color" content="#6366f1">

    <!-- PWA Manifest -->
    <link rel="manifest" href="/manifest.json">

    <!-- Vite Assets -->
    {{-- Apply saved theme immediately to prevent flash --}}
    <script>
        (function() {
            var t = localStorage.getItem('theme') ||
                    (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-bs-theme', t);
        })();
    </script>

    @vite(['resources/scss/main.scss', 'resources/js/main.js'])

    @stack('head')
</head>

<body data-page="@yield('page', 'dashboard')" class="admin-layout">
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

        @php
            $hasSidebarPermission = auth()->check() && (auth()->user()->hasRole('Super Admin') || auth()->user()->can('sidebar-view'));
            $hideSidebar = (isset($hideSidebar) && $hideSidebar) || !$hasSidebarPermission;
        @endphp

        <!-- Header -->
        @include('components.header')

        <!-- Sidebar -->
        @if(!$hideSidebar)
            @include('components.sidebar')
        @endif

        <!-- Sidebar Backdrop (mobile overlay) -->
        <div class="sidebar-backdrop" aria-hidden="true"></div>

        <!-- Main Content -->
        <main id="main-content" class="admin-main" style="{{ $hideSidebar ? 'margin-left: 0;' : '' }}">
            <div class="container-fluid p-4 p-lg-5">
                {{-- ACTIVE PATH BREADCRUMBS --}}
                @if(!request()->routeIs('dashboard') && !request()->routeIs('inventory.dashboard'))
                    @php
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
                    @endphp
                    <nav aria-label="breadcrumb" class="mb-4">
                        <ol class="breadcrumb m-0 fs-14 fw-semibold">
                            <li class="breadcrumb-item text-muted">
                                @if($moduleName === 'Home')
                                    <a href="{{ route('dashboard') }}" class="text-decoration-none text-muted"><i class="bi bi-house-door-fill me-1"></i>Home</a>
                                @else
                                    {{ $moduleName }}
                                @endif
                            </li>
                            @foreach(request()->segments() as $segment)
                                @if(!$loop->last)
                                    <li class="breadcrumb-item text-capitalize"><a href="{{ url(implode('/', array_slice(request()->segments(), 0, $loop->iteration))) }}" class="text-decoration-none text-muted">{{ str_replace('-', ' ', $segment) }}</a></li>
                                @else
                                    <li class="breadcrumb-item active text-capitalize text-primary" aria-current="page">{{ str_replace('-', ' ', $segment) }}</li>
                                @endif
                            @endforeach
                        </ol>
                    </nav>
                @endif
                
                @yield('content')
            </div>
        </main>

        <!-- Footer -->
        @include('components.footer')

    </div><!-- /.admin-wrapper -->

    @if(config('chat.enabled'))
        @include('partials.chat_widget')
    @endif

    <!-- Toast Container -->
    <div aria-live="polite" aria-atomic="true" class="toast-stack position-fixed end-0 p-3">
        <div id="toast-container"></div>
    </div>

    @stack('modals')
    @stack('scripts')
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
