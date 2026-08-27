@extends('layouts.auth')

@section('title', 'Sign In')
@section('description', 'Sign in to your Ecommerce Admin account')
@section('page', 'login')

@section('content')
@php
    $loginBg = \App\Models\SystemSetting::where('key', 'login_background_image')->value('value');
    if ($loginBg) {
        $loginBg = parse_url($loginBg, PHP_URL_PATH) ?: $loginBg;
    }
    $bgImage = $loginBg ? $loginBg : asset('assets/images/background.png');
@endphp
<div class="auth-page position-relative min-vh-100 d-flex align-items-center justify-content-center py-5" id="main-content" style="background: url('{{ $bgImage }}') center center / cover no-repeat fixed !important;">
    
    <!-- Overlay for better contrast with the background image -->
    <div class="position-absolute top-0 start-0 w-100 h-100" style="background: rgba(0, 0, 0, 0.35);"></div>

    <!-- Theme-aware Glassmorphism Card (Now Transparent & Smaller) -->
    <div class="auth-card position-relative z-1 shadow-lg border border-secondary-subtle mx-3" x-data="loginApp()" style="background: transparent; backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border-radius: 20px; max-width: 380px; width: 100%; padding: 2.5rem 2rem;">



        {{-- ════════════════════════════════════════════════════════════════ --}}
        {{-- ── SIGN-IN PANEL ─────────────────────────────────────────────── --}}
        {{-- ════════════════════════════════════════════════════════════════ --}}
        <div>
            {{-- ── Server-side session flash / validation errors ─────────── --}}
            @if (session('error'))
                <div class="alert alert-danger d-flex align-items-center gap-2 mb-4 py-3 px-3 border-0 rounded-3 shadow-sm" role="alert">
                    <i class="bi bi-exclamation-octagon-fill fs-5"></i>
                    <span class="fw-semibold">{{ session('error') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger d-flex align-items-start gap-2 mb-4 py-3 px-3 border-0 rounded-3 shadow-sm" role="alert">
                    <i class="bi bi-exclamation-octagon-fill fs-5 mt-1"></i>
                    <div>
                        @foreach ($errors->all() as $error)
                            <div class="fw-medium mb-1">{{ $error }}</div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- ── Login Form (posts to real Laravel route) ─────────────── --}}
            <form method="POST" action="{{ route('login.submit') }}" id="loginForm" @submit="isSubmitting = true">
                @csrf

                {{-- Email ------------------------------------------------- --}}
                <div class="mb-4">
                    <label for="loginEmail" class="form-label fw-bold text-body small" style="letter-spacing: 0.3px;">Email Address</label>
                    <div class="input-group input-group-lg rounded-3 overflow-hidden border border-secondary-subtle focus-within-border-primary transition-all bg-body-tertiary">
                        <span class="input-group-text bg-transparent border-0 text-secondary px-3"><i class="bi bi-envelope"></i></span>
                        <input type="email"
                               class="form-control bg-transparent border-0 shadow-none fs-6 fw-medium text-body @error('email') is-invalid @enderror"
                               id="loginEmail"
                               name="email"
                               value="{{ old('email', request()->cookie('remembered_email')) }}"
                               placeholder="name@company.com"
                               autocomplete="email"
                               autofocus
                               required>
                    </div>
                    @error('email')
                        <div class="text-danger small mt-2 fw-medium"><i class="bi bi-info-circle me-1"></i>{{ $message }}</div>
                    @enderror
                </div>

                {{-- Password ----------------------------------------------- --}}
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="loginPassword" class="form-label fw-bold text-body small mb-0" style="letter-spacing: 0.3px;">Password</label>
                    </div>
                    <div class="input-group input-group-lg rounded-3 overflow-hidden border border-secondary-subtle focus-within-border-primary transition-all bg-body-tertiary">
                        <span class="input-group-text bg-transparent border-0 text-secondary px-3"><i class="bi bi-lock"></i></span>
                        <input :type="showPassword ? 'text' : 'password'"
                               class="form-control bg-transparent border-0 shadow-none fs-6 fw-medium text-body @error('password') is-invalid @enderror"
                               id="loginPassword"
                               name="password"
                               placeholder="••••••••"
                               autocomplete="current-password"
                               required>
                        <button class="btn btn-link text-secondary text-decoration-none border-0 px-3 hover-text-primary"
                                type="button"
                                @click="showPassword = !showPassword"
                                :aria-label="showPassword ? 'Hide password' : 'Show password'">
                            <i :class="showPassword ? 'bi bi-eye-slash-fill' : 'bi bi-eye-fill'"></i>
                        </button>
                    </div>
                    @error('password')
                        <div class="text-danger small mt-2 fw-medium"><i class="bi bi-info-circle me-1"></i>{{ $message }}</div>
                    @enderror
                </div>

                {{-- Remember Me -------------------------------------------- --}}
                <div class="mb-4 pb-2 d-flex align-items-center justify-content-between">
                    <div class="form-check d-flex align-items-center gap-2">
                        <input class="form-check-input mt-0 border-secondary"
                               type="checkbox"
                               id="rememberMe"
                               name="remember"
                               value="1"
                               {{ old('remember') ? 'checked' : '' }}
                               x-model="remember"
                               style="width: 1.25em; height: 1.25em; cursor: pointer;">
                        <label class="form-check-label fw-medium text-secondary" for="rememberMe" style="cursor: pointer;">
                            Keep me logged in
                        </label>
                    </div>
                </div>

                {{-- Submit ------------------------------------------------- --}}
                <button type="submit"
                        class="btn btn-primary w-100 py-3 rounded-3 shadow-sm fw-bold fs-6 transition-all position-relative overflow-hidden group"
                        id="loginSubmitBtn"
                        :disabled="isSubmitting"
                        style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); border: none;">
                    
                    <!-- Hover Effect -->
                    <div class="position-absolute top-0 start-0 w-100 h-100 bg-white opacity-0 transition-all group-hover-opacity-25"></div>

                    <span x-show="!isSubmitting" class="d-flex align-items-center justify-content-center gap-2 position-relative z-1">
                        Sign In to Account
                        <i class="bi bi-arrow-right fw-bold fs-5 ms-1 transition-all group-hover-translate-x"></i>
                    </span>
                    <span x-show="isSubmitting" style="display: none;" class="d-flex align-items-center justify-content-center gap-2 position-relative z-1">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        Authenticating…
                    </span>
                </button>
            </form>
        </div>
    </div>{{-- /.auth-card --}}

</div>{{-- /.auth-page --}}

<style>
/* Utilities for Login */
.focus-within-border-primary:focus-within {
    border-color: #6366f1 !important;
    background-color: var(--bs-body-bg) !important;
    box-shadow: 0 0 0 0.25rem rgba(99, 102, 241, 0.15);
}
.group:hover .group-hover-opacity-25 {
    opacity: 0.15 !important;
}
.group:hover .group-hover-translate-x {
    transform: translateX(4px);
}
.hover-text-primary:hover {
    color: #6366f1 !important;
}
</style>
@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('loginApp', () => ({
            showPassword: false,
            isSubmitting: false,

            // Sign-in state
            remember: {{ old('remember') ? 'true' : 'false' }},

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
