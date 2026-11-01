<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Metis Market · Everyday essentials, thoughtfully chosen')</title>
    <link rel="stylesheet" href="{{ asset('storefront/shop.css') }}?v={{ sha1_file(public_path('storefront/shop.css')) }}">
</head>
<body class="{{ request()->routeIs('shop.index') ? 'shop-home' : '' }}">
<a class="skip-link" href="#main-content">Skip to content</a>
<div class="shop-sticky-header" data-shop-sticky-header>
<header class="header">
    <a class="brand shop-brand-logo" href="{{ route('shop.index') }}" aria-label="Shop home"><img src="{{ route('shop.brand-logo') }}" alt="Company logo" width="64" height="48" onerror="this.onerror=null;this.src='{{ asset('storefront/brand-logo.png') }}';"></a>
    <form class="search" action="{{ route('shop.index') }}" role="search"><span aria-hidden="true">⌕</span><input aria-label="Search products" name="q" type="search" maxlength="100" placeholder="Search for products, essentials and more" value="{{ request('q') }}"><button>Search</button></form>
    <nav aria-label="Account navigation">
        @php($shopAccount = Auth::guard('customer')->user())
        @if($shopAccount)
            <details class="account-dropdown">
                <summary class="account-link"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/></svg><span>{{ $shopAccount->customer?->name ?? 'My account' }}</span><span class="account-chevron" aria-hidden="true">⌄</span></summary>
                <div class="account-menu">
                    <div class="account-menu-heading"><strong>{{ $shopAccount->customer?->name ?? 'My account' }}</strong><small>{{ $shopAccount->email }}</small></div>
                    <a href="{{ route('shop.profile') }}" @if(request()->routeIs('shop.profile')) aria-current="page" @endif>My profile <span aria-hidden="true">↗</span></a>
                    <a href="{{ route('shop.orders') }}" @if(request()->routeIs('shop.orders')) aria-current="page" @endif>My orders <span aria-hidden="true">↗</span></a>
                    <a href="{{ route('shop.cart') }}">My cart <span aria-hidden="true">↗</span></a>
                    <form action="{{ route('shop.logout') }}" method="post">@csrf<button>Sign out <span aria-hidden="true">→</span></button></form>
                </div>
            </details>
        @else
            <a class="account-link" href="{{ route('shop.login', ['return_to' => request()->routeIs('shop.cart') ? 'cart' : 'shop']) }}"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/></svg>Customer login</a>
        @endif
        <a class="cart-link" href="{{ route('shop.cart') }}"><svg class="cart-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 3h3l2.5 12h11l2-8H6"/><path d="M8 15l-1 3h12"/><circle cx="9" cy="21" r="1"/><circle cx="18" cy="21" r="1"/></svg> Cart <span data-cart-count aria-live="polite" aria-atomic="true">{{ array_sum(session('shop.cart', [])) }}</span></a>
    </nav>
</header>
@if(request()->routeIs('shop.index', 'shop.cart', 'shop.product'))
    @include('shop.category-navigation')
@endif
</div>
<main id="main-content" tabindex="-1">
    @if(session('success'))<div class="notice" role="status">{{ session('success') }}</div>@endif
    @if(session('cart_notice'))<div class="notice" role="status">{{ session('cart_notice') }}</div>@endif
    @if($errors->any())<div class="error" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    @yield('content')
</main>
<section class="assurances"><div><b>Carefully selected</b><span>Products for your everyday needs</span></div><div><b>Shop with confidence</b><span>Secure customer account access</span></div><div><b>Pay on delivery</b><span>Place your order, pay when it arrives</span></div></section>
<footer><a class="brand shop-brand-logo" href="{{ route('shop.index') }}" aria-label="Shop home"><img src="{{ route('shop.brand-logo') }}" alt="Company logo" width="64" height="48" onerror="this.onerror=null;this.src='{{ asset('storefront/brand-logo.png') }}';"></a><p>Your everyday marketplace.</p><a href="{{ route('login') }}">Staff sign in ↗</a><span>© {{ date('Y') }} Metis Market</span></footer>
<div class="cart-feedback" role="status" aria-live="polite" hidden></div>
<script src="{{ asset('storefront/shop-cart.js') }}?v={{ filemtime(public_path('storefront/shop-cart.js')) }}" defer></script>
<script src="{{ asset('storefront/shop-account.js') }}?v={{ filemtime(public_path('storefront/shop-account.js')) }}" defer></script>
</body>
</html>
