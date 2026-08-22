@extends('layouts.auth')

@section('title', 'Sign In')
@section('description', 'Sign in to your Ecommerce Admin account')
@section('page', 'login')

@section('content')
<div class="auth-page" id="main-content">
    <div class="auth-card" x-data="loginApp()">

        {{-- ── Logo ───────────────────────────────────────────────────────── --}}
        <div class="auth-logo mb-4">
            <svg width="36" height="36" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <rect width="36" height="36" rx="10" fill="url(#ecommerce-grad)"/>
                <path d="M10 26V12l8 8 8-8v14" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                <defs>
                    <linearGradient id="ecommerce-grad" x1="0" y1="0" x2="36" y2="36" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#6366f1"/>
                        <stop offset="1" stop-color="#8b5cf6"/>
                    </linearGradient>
                </defs>
            </svg>
            <span class="auth-brand-name">Ecommerce</span>
        </div>

        {{-- ════════════════════════════════════════════════════════════════ --}}
        {{-- ── SIGN-IN PANEL ─────────────────────────────────────────────── --}}
        {{-- ════════════════════════════════════════════════════════════════ --}}
        <div>
            <p class="auth-subtitle">Welcome back! Please sign in to your account.</p>

            {{-- ── Server-side session flash / validation errors ─────────── --}}
            @if (session('error'))
                <div class="alert alert-danger d-flex align-items-center gap-2 mb-3 py-2 px-3" role="alert">
                    <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger d-flex align-items-start gap-2 mb-3 py-2 px-3" role="alert">
                    <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
                    <div>
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- ── Login Form (posts to real Laravel route) ─────────────── --}}
            <form method="POST" action="{{ route('login.submit') }}" id="loginForm" @submit="isSubmitting = true">
                @csrf

                {{-- Email ------------------------------------------------- --}}
                <div class="mb-4">
                    <label for="loginEmail" class="form-label fw-semibold small text-muted">Email address</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-body-secondary border-end-0 text-muted px-3"><i class="bi bi-envelope"></i></span>
                        <input type="email"
                               class="form-control bg-body border-start-0 ps-0 shadow-none fs-6 @error('email') is-invalid @enderror"
                               id="loginEmail"
                               name="email"
                               value="{{ old('email', request()->cookie('remembered_email')) }}"
                               placeholder="you@example.com"
                               autocomplete="email"
                               autofocus
                               required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Password ----------------------------------------------- --}}
                <div class="mb-4">
                    <label for="loginPassword" class="form-label fw-semibold small text-muted">Password</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-body-secondary border-end-0 text-muted px-3"><i class="bi bi-lock"></i></span>
                        <input :type="showPassword ? 'text' : 'password'"
                               class="form-control bg-body border-start-0 border-end-0 ps-0 shadow-none fs-6 @error('password') is-invalid @enderror"
                               id="loginPassword"
                               name="password"
                               placeholder="••••••••"
                               autocomplete="current-password"
                               required>
                        <button class="btn btn-outline-secondary border-start-0 password-toggle px-3"
                                type="button"
                                @click="showPassword = !showPassword"
                                :aria-label="showPassword ? 'Hide password' : 'Show password'">
                            <i :class="showPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                        </button>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Remember Me -------------------------------------------- --}}
                <div class="mb-4 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2 mb-0">
                        <input class="auth-checkbox"
                               type="checkbox"
                               id="rememberMe"
                               name="remember"
                               value="1"
                               {{ old('remember') ? 'checked' : '' }}
                               x-model="remember">
                        <label class="text-secondary" for="rememberMe">
                            Remember me
                        </label>
                    </div>
                    <span class="auth-remember-hint" x-show="remember" x-transition>
                        <i class="bi bi-shield-check text-success"></i>
                        <small class="text-success">30 days</small>
                    </span>
                </div>

                {{-- Submit ------------------------------------------------- --}}
                <button type="submit"
                        class="btn btn-primary w-100 auth-submit-btn py-3 fs-6 rounded-3 mt-2"
                        id="loginSubmitBtn"
                        :disabled="isSubmitting">
                    <span x-show="!isSubmitting" class="d-flex align-items-center justify-content-center gap-2">
                        <i class="bi bi-box-arrow-in-right fs-5"></i>
                        Sign In
                    </span>
                    <span x-show="isSubmitting" style="display: none;" class="d-flex align-items-center justify-content-center gap-2">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        Signing in…
                    </span>
                </button>
            </form>
        </div>
    </div>{{-- /.auth-card --}}
</div>{{-- /.auth-page --}}
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
