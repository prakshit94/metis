<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <!-- Meta Tags -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta name="description" content="@yield('description', 'Sign in to Modern Bootstrap Admin Dashboard')">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Title -->
    <title>@yield('title', 'Login') - Ecommerce Admin</title>

    <!-- Theme Color -->
    <meta name="theme-color" content="#6366f1">

    @php
        $appLogo = \App\Models\SystemSetting::where('key', 'default_image_logo_png')->value('value');
        $brandLogo = \App\Services\BrandLogo::resolve($appLogo);
        $logoImage = $brandLogo['src'];
    @endphp
    <!-- Favicon -->
    <link rel="icon" href="{{ $logoImage }}">

    <!-- Preconnect -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <script>
        (function () {
            var theme;
            try { theme = localStorage.getItem('theme'); } catch (_) {}
            if (theme !== 'light' && theme !== 'dark') theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            document.documentElement.setAttribute('data-bs-theme', theme);
        })();
    </script>

    <!-- Vite Assets -->
    @vite(['resources/scss/main.scss', 'resources/js/main.js'])

    @stack('head')
</head>

<body data-page="@yield('page', 'login')">
    <a href="#main-content" class="skip-link">Skip to main content</a>

    @yield('content')

    <!-- Toast Container -->
    <div aria-live="polite" aria-atomic="true" class="toast-stack position-fixed end-0 p-3">
        <div id="toast-container"></div>
    </div>

    @stack('scripts')
</body>
</html>
