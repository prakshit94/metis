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
    <meta name="user-id" content="{{ auth()->id() ?? '' }}">
    <meta name="user-name" content="{{ auth()->user()->name ?? '' }}">

    @php
        $defaultAvatarUrl = \App\Models\SystemSetting::where('key', 'default_image_default_avatar_jpeg')->value('value') ?: '';
    @endphp
    <meta name="default-avatar" content="{{ $defaultAvatarUrl }}">

    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="@yield('title', 'Ecommerce Admin')">
    <meta property="og:description" content="Clean and modern admin dashboard template built with Bootstrap 5">
    <meta property="og:type" content="website">

    @php
        $appLogo = \App\Models\SystemSetting::where('key', 'default_image_logo_png')->value('value');
        $brandLogo = \App\Services\BrandLogo::resolve($appLogo);
        $logoImage = $brandLogo['src'];
    @endphp
    <!-- Favicon -->
    <link rel="icon" href="{{ $logoImage }}">

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
    <link rel="manifest" href="{{ asset('manifest.json') }}">

    <!-- Vite Assets -->
    {{-- Apply saved theme immediately to prevent flash --}}
    <script>
        (function() {
            var t;
            try { t = localStorage.getItem('theme'); } catch (_) {}
            if (t !== 'light' && t !== 'dark') t = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            document.documentElement.setAttribute('data-bs-theme', t);
        })();
    </script>

    @vite(['resources/scss/main.scss', 'resources/js/main.js'])

    @stack('head')
</head>

<body data-page="@yield('page', 'dashboard')" class="admin-layout">
    @if(Session::has('impersonated_by'))
    <div id="impersonation-banner" class="alert alert-warning text-center rounded-0 mb-0 d-flex flex-wrap justify-content-center align-items-center shadow-sm w-100" style="position: fixed; top: 0; left: 0; right: 0; z-index: 1060; min-height: 40px;" x-data="{
        stopping: false,
        error: '',
        resizeHandler: null,
        initBanner() {
            this.resizeHandler = () => document.documentElement.style.setProperty('--banner-h', this.$el.offsetHeight + 'px');
            this.resizeHandler();
            window.addEventListener('resize', this.resizeHandler);
        },
        destroy() { window.removeEventListener('resize', this.resizeHandler); },
        async stopImpersonating() {
            if (this.stopping) return;
            this.stopping = true;
            this.error = '';
            try {
                const response = await fetch('/api/security/stop-impersonate', {
                    method: 'POST', credentials: 'same-origin',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '', 'Accept': 'application/json' }
                });
                if (response.status === 401 || response.status === 419) {
                    window.location.href = '/admin/login';
                    return;
                }
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Unable to stop impersonating. Please try again.');
                if (!data.redirect) throw new Error('The server did not return a destination. Please reload and try again.');
                window.location.href = data.redirect;
            } catch (error) {
                this.error = error.message || 'Unable to stop impersonating. Please try again.';
                this.$nextTick(() => this.resizeHandler());
            } finally {
                this.stopping = false;
            }
        }
    }" x-init="initBanner()">
        <span class="me-3 fs-6"><i class="bi bi-box-arrow-in-right me-2"></i> You are currently impersonating <strong>{{ auth()->user()->name ?? '' }}</strong>.</span>
        <button type="button" class="btn btn-sm btn-dark fw-bold px-3" @click="stopImpersonating()" :disabled="stopping" :aria-busy="stopping">
            <span x-text="stopping ? 'Returning…' : 'Stop Impersonating'">Stop Impersonating</span>
        </button>
        <span class="small text-danger w-100" role="alert" x-show="error" x-text="error" x-cloak></span>
    </div>
    <style>
        :root { --banner-h: 40px; }
        body { padding-top: var(--banner-h) !important; }
        .admin-header { top: var(--banner-h) !important; }
        .admin-sidebar { 
            top: calc(var(--header-height) + var(--banner-h)) !important; 
            height: calc(100vh - var(--header-height) - var(--banner-h)) !important; 
        }
    </style>
    @endif
    <a href="#main-content" class="skip-link">Skip to main content</a>


    <!-- Loading Screen -->
    <div id="loading-screen" class="loading-screen">
        <div class="loading-spinner">
            <div class="brand-logo-container rounded-4 d-flex align-items-center justify-content-center mb-3 shadow-sm" style="width: 64px; height: 64px; background-color: var(--bs-body-bg);">
                <img src="{{ $logoImage }}" data-fallback-src="{{ $brandLogo['fallback'] }}" onerror="this.onerror=null;this.src=this.dataset.fallbackSrc;" alt="Loading Logo" class="w-100 h-100 object-fit-contain p-1">
            </div>
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>

    @include('partials.loading-screen-script')

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
                @if(!View::hasSection('hideBreadcrumbs') && !request()->routeIs('dashboard') && !request()->routeIs('inventory.dashboard'))
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

    {{-- @if(config('chat.enabled'))
        @include('partials.chat_widget')
    @endif --}}

    <!-- Toast Container -->
    <div aria-live="polite" aria-atomic="true" class="toast-stack position-fixed end-0 p-3">
        <div id="toast-container"></div>
    </div>

    @stack('modals')
    @stack('scripts')
    <script>


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
    <script>
        // Clipboard API requires a secure context in many browsers. Run the
        // execCommand fallback during the original click so HTTP deployments do
        // not lose the browser's transient user activation while awaiting a denial.
        window.copyText = async function(value) {
            var text = value == null ? '' : String(value);
            if (!text) return false;

            var clipboardWrite = null;
            if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                try {
                    // Invoke the native API in the click's activation window.
                    clipboardWrite = Promise.resolve(navigator.clipboard.writeText(text))
                        .then(function() { return true; }, function() { return false; });
                } catch (error) {
                    // The synchronous fallback below handles restricted contexts.
                }
            }

            var textArea = document.createElement('textarea');
            var activeElement = document.activeElement;
            var selection = document.getSelection();
            var ranges = [];
            if (selection) {
                for (var i = 0; i < selection.rangeCount; i++) ranges.push(selection.getRangeAt(i).cloneRange());
            }

            textArea.value = text;
            textArea.setAttribute('readonly', '');
            textArea.setAttribute('aria-hidden', 'true');
            textArea.tabIndex = -1;
            textArea.style.position = 'fixed';
            textArea.style.left = '0';
            textArea.style.top = '0';
            textArea.style.width = '1px';
            textArea.style.height = '1px';
            textArea.style.padding = '0';
            textArea.style.border = '0';
            textArea.style.opacity = '0';
            textArea.style.pointerEvents = 'none';
            // Bootstrap's focus trap redirects focus away from body children
            // while a modal is open, preventing selection on HTTP deployments.
            var copyHost = activeElement && typeof activeElement.closest === 'function'
                ? activeElement.closest('.modal.show, dialog[open]') : null;
            copyHost = copyHost || document.querySelector('.modal.show, dialog[open]') || document.body;
            copyHost.appendChild(textArea);
            var fallbackSucceeded = false;
            try {
                textArea.focus();
                textArea.select();
                textArea.setSelectionRange(0, textArea.value.length);
                fallbackSucceeded = typeof document.execCommand === 'function'
                    && document.execCommand('copy') === true;
            } catch (error) {
                fallbackSucceeded = false;
            } finally {
                textArea.remove();
                try {
                    if (activeElement && typeof activeElement.focus === 'function') activeElement.focus();
                    if (selection) {
                        selection.removeAllRanges();
                        ranges.forEach(function(range) { selection.addRange(range); });
                    }
                } catch (error) {
                    // Clipboard success should not depend on focus restoration.
                }
            }

            if (fallbackSucceeded) return true;
            if (clipboardWrite) {
                try {
                    if (await clipboardWrite) return true;
                } catch (error) {
                    // Both clipboard methods failed.
                }
            }
            window.alert('Copy failed. Select the text and press Ctrl+C (Windows/Linux) or Command+C (Mac).');
            return false;
        };
    </script>
</body>
</html>
