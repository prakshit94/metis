@extends('layouts.auth')

@section('title', 'Login')

@section('content')
<div class="auth-page" id="main-content">
        <div class="auth-card" x-data="loginApp()">

            <!-- Logo -->
            <div class="auth-logo">
                <img src="/assets/images/logo.svg" alt="Metis Logo" width="36" height="36">
                <span class="auth-brand-name">Metis</span>
            </div>

            <!-- Tab Switcher -->
            <div class="auth-tabs" role="tablist">
                <button class="auth-tab"
                        :class="{ active: activeTab === 'login' }"
                        @click="activeTab = 'login'"
                        role="tab"
                        :aria-selected="activeTab === 'login'"
                        id="tab-login">
                    Sign In
                </button>
                <button class="auth-tab"
                        :class="{ active: activeTab === 'register' }"
                        @click="activeTab = 'register'"
                        role="tab"
                        :aria-selected="activeTab === 'register'"
                        id="tab-register">
                    Create Account
                </button>
            </div>

            <!-- ─── LOGIN PANEL ─── -->
            <div x-show="activeTab === 'login'"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 role="tabpanel"
                 aria-labelledby="tab-login">

                <p class="auth-subtitle">Welcome back! Please sign in to your account.</p>

                <!-- Social Login -->
                <div class="d-flex gap-2 mb-4">
                    <button type="button" class="auth-social-btn flex-fill" aria-label="Sign in with Google">
                        <i class="bi bi-google text-danger"></i>
                        <span>Google</span>
                    </button>
                    <button type="button" class="auth-social-btn flex-fill" aria-label="Sign in with GitHub">
                        <i class="bi bi-github"></i>
                        <span>GitHub</span>
                    </button>
                </div>

                <div class="divider-text mb-4">or continue with email</div>

                <!-- Login Form -->
                <form @submit.prevent="submitLogin()" novalidate>
                    <div class="mb-3">
                        <label for="loginEmail" class="form-label fw-semibold">Email address</label>
                        <input type="email"
                               class="form-control"
                               id="loginEmail"
                               x-model="loginForm.email"
                               placeholder="you@example.com"
                               autocomplete="email"
                               required>
                        <div class="invalid-feedback" x-show="loginErrors.email" x-text="loginErrors.email"></div>
                    </div>
@endsection

@push('scripts')
<script type="module">

        import Alpine from 'alpinejs';

        // Theme switch (reuse global registration from main.js, but also define locally for safety)
        document.addEventListener('alpine:init', () => {
            Alpine.data('loginApp', () => ({
                activeTab: 'login',
                showPassword: false,
                showRegPassword: false,
                isSubmitting: false,

                loginForm:  { email: '', password: '', remember: false },
                loginErrors: {},

                regForm: { firstName: '', lastName: '', email: '', password: '', agreeTerms: false },
                strength: { score: 0, label: 'Weak' },

                calcStrength() {
                    const p = this.regForm.password;
                    let score = 0;
                    if (p.length >= 8)           score++;
                    if (/[a-z]/.test(p))         score++;
                    if (/[A-Z]/.test(p))         score++;
                    if (/[0-9]/.test(p))         score++;
                    if (/[^A-Za-z0-9]/.test(p))  score++;
                    const labels = ['', 'Weak', 'Weak', 'Fair', 'Good', 'Strong'];
                    this.strength = { score, label: labels[score] || 'Strong' };
                },

                async submitLogin() {
                    this.loginErrors = {};
                    if (!this.loginForm.email)    { this.loginErrors.email    = 'Email is required'; return; }
                    if (!this.loginForm.password) { this.loginErrors.password = 'Password is required'; return; }

                    this.isSubmitting = true;
                    await new Promise(r => setTimeout(r, 1200));
                    this.isSubmitting = false;

                    window.AdminApp?.notificationManager?.success('Signed in successfully! Redirecting…');
                    setTimeout(() => { window.location.href = './index.html'; }, 1500);
                },

                async submitRegister() {
                    this.isSubmitting = true;
                    await new Promise(r => setTimeout(r, 1200));
                    this.isSubmitting = false;

                    window.AdminApp?.notificationManager?.success('Account created! Welcome aboard.');
                    setTimeout(() => { window.location.href = './index.html'; }, 1500);
                },
            }));
        });
    
</script>
@endpush
