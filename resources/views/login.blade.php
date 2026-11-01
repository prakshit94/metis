@extends('layouts.auth')

@section('title', 'Sign In')
@section('description', 'Sign in to your Ecommerce Admin account')
@section('page', 'login')

@section('content')
@php
    $loginBg = \App\Models\SystemSetting::where('key', 'login_background_image')->value('value');
    if ($loginBg) {
        $loginBgPath = parse_url($loginBg, PHP_URL_PATH) ?: $loginBg;
        $loginBgQuery = parse_url($loginBg, PHP_URL_QUERY);
        $loginBg = $loginBgPath . ($loginBgQuery ? '?' . $loginBgQuery : '');
    }
    $bgImage = $loginBg ? $loginBg : asset('assets/images/background.png');

    $appLogo = \App\Models\SystemSetting::where('key', 'default_image_logo_png')->value('value');
    $brandLogo = \App\Services\BrandLogo::resolve($appLogo);
    $logoImage = $brandLogo['src'];
@endphp
<div class="login-page auth-layout auth-layout--login" x-data="loginApp()" :class="{ 'login-panel-collapsed': !panelOpen }" @pageshow.window="isSubmitting = false" style="--auth-background-image: url('{{ $bgImage }}');">
    <button type="button" x-cloak x-show="!panelOpen" x-ref="restorePanel" class="btn btn-primary auth-restore-panel" @click="togglePanel()" aria-controls="main-content" :aria-expanded="panelOpen.toString()"><i class="bi bi-layout-sidebar-reverse me-2" aria-hidden="true"></i>Show sign in</button>
    <main class="auth-main" id="main-content" tabindex="-1" x-show="panelOpen" x-transition.opacity.duration.200ms>
        <div class="auth-topbar">
            <button type="button" class="btn btn-sm btn-outline-secondary" @click="togglePanel()" aria-label="Hide sign in panel" aria-controls="main-content" :aria-expanded="panelOpen.toString()" x-cloak><span aria-hidden="true">‹</span> Hide sign in</button>
            <button type="button" class="btn btn-sm btn-outline-secondary login-theme-toggle" data-theme-toggle aria-label="Toggle colour theme">
                <i class="bi bi-sun-fill theme-icon-light" aria-hidden="true"></i>
                <i class="bi bi-moon-stars-fill theme-icon-dark d-none" aria-hidden="true"></i>
            </button>
        </div>

        <div class="auth-body">
            <a class="auth-brand auth-form-brand" href="{{ route('dashboard') }}" aria-label="Metis home">
                <img src="{{ $logoImage }}" alt="Metis logo" fetchpriority="high" decoding="async" onerror="this.onerror=null;this.src='{{ $brandLogo['fallback'] }}';">
            </a>

            @if (session('error'))
                <div class="alert alert-danger py-2 px-3 small" role="alert">{{ session('error') }}</div>
            @endif

            <form class="auth-form" method="POST" action="{{ route('login.submit') }}" id="loginForm" :aria-busy="isSubmitting.toString()" @submit="if (isSubmitting) { $event.preventDefault(); } else { isSubmitting = true; }" autocomplete="on">
                @csrf
                <div class="mb-3">
                    <label for="loginEmail" class="form-label">Email address</label>
                    <input type="email"
                           class="form-control @error('email') is-invalid @enderror"
                           id="loginEmail"
                           name="email"
                           value="{{ old('email', request()->cookie('remembered_email')) }}"
                           placeholder="you@company.com"
                           autocomplete="username"
                           inputmode="email"
                           autocapitalize="none"
                           spellcheck="false"
                           @error('email') aria-invalid="true" aria-describedby="loginEmailError" @enderror
                           autofocus
                           required>
                    @error('email')
                        <div id="loginEmailError" class="invalid-feedback" role="alert">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="loginPassword" class="form-label">Password</label>
                    <div class="auth-password">
                        <input type="password" :type="showPassword ? 'text' : 'password'"
                               class="form-control @error('password') is-invalid @enderror"
                               id="loginPassword"
                               name="password"
                               placeholder="Enter your password"
                               autocomplete="current-password"
                               @error('password') aria-invalid="true" aria-describedby="loginPasswordError" @enderror
                               required>
                        <button type="button" class="auth-password-toggle"
                                @click="showPassword = !showPassword"
                                aria-label="Show password"
                                aria-controls="loginPassword"
                                :aria-pressed="showPassword.toString()"
                                :aria-label="showPassword ? 'Hide password' : 'Show password'">
                            <i class="bi" aria-hidden="true" :class="showPassword ? 'bi-eye-slash' : 'bi-eye'"></i>
                        </button>
                    </div>
                    @error('password')
                        <div id="loginPasswordError" class="invalid-feedback d-block" role="alert">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" id="rememberMe" name="remember" value="1"
                           {{ old('remember', request()->cookie('remembered_email') ? true : false) ? 'checked' : '' }}
                           x-model="remember">
                    <label class="form-check-label small" for="rememberMe">Keep me logged in</label>
                </div>

                <button type="submit" class="btn btn-primary auth-submit-btn w-100" id="loginSubmitBtn" :disabled="isSubmitting">
                    <span x-show.important="!isSubmitting">Sign in</span>
                    <span x-show.important="isSubmitting" style="display: none;">
                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        Authenticating…
                    </span>
                </button>
            </form>
        </div>
    </main>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('loginApp', () => ({
            panelOpen: true,
            togglePanel() {
                this.panelOpen = !this.panelOpen;
                this.$nextTick(() => {
                    if (this.panelOpen) document.getElementById('loginEmail')?.focus();
                    else this.$refs.restorePanel.focus();
                });
            },
            showPassword: false,
            isSubmitting: false,

            // Sign-in state
            remember: {{ old('remember', request()->cookie('remembered_email') ? true : false) ? 'true' : 'false' }},

            // Reset spinner if server returned a validation error
            init() {
                @if ($errors->any())
                    this.isSubmitting = false;
                @endif
            },
        }));
    });
</script>
@endpush
