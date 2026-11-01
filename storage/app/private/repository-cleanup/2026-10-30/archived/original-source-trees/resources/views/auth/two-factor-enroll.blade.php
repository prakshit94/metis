@extends('layouts.auth')
@section('title', 'Set up two-step login')
@section('page', 'two-factor-enrollment')
@section('content')
@php
    $background = \App\Models\SystemSetting::where('key', 'login_background_image')->value('value') ?: asset('assets/images/background.png');
    $logo = \App\Models\SystemSetting::where('key', 'default_image_logo_png')->value('value') ?: asset('assets/images/logo.png');
@endphp
<div class="login-page" style="--auth-background-image: url('{{ $background }}');">
    <main class="auth-main" id="main-content">
        <div class="auth-body" x-data="loginEnrollment" data-enrollment-uri="{{ $enrollment_uri ?? '' }}">
            <a class="auth-brand auth-form-brand" href="{{ route('login') }}"><img src="{{ $logo }}" alt="Metis logo"><span>Metis</span></a>
            <div class="auth-heading"><h1>Set up two-step login</h1><p>Your administrator requires a second step. Choose and verify your own method before accessing the application. This sign-in request expires after five minutes.</p></div>
            @if(session('error'))<div class="alert alert-danger" role="alert">{{ session('error') }}</div>@endif
            @if(session('status'))<div class="alert alert-info" role="status">{{ session('status') }}</div>@endif
            @if($enrollment_started)
                @if($method === 'authenticator')
                    <p>Scan this QR code with your authenticator app.</p>
                    <img x-show="qr" x-cloak :src="qr || undefined" alt="Scan to add your account to an authenticator app" width="320" height="320" class="img-fluid border rounded mb-3" style="background: white;">
                    <p x-show="error" x-cloak class="small text-warning" x-text="error"></p>
                    <details class="mb-3"><summary>Use a manual setup key</summary><label for="requiredSetupKey" class="form-label mt-2">Time-based key (SHA-1, six digits, 30 seconds)</label><input id="requiredSetupKey" readonly autocomplete="off" class="form-control font-monospace" value="{{ $enrollment_secret }}"></details>
                @else
                    <p>Enter the code sent to {{ $email_hint }}. Each email code expires after five minutes.</p>
                    <form x-data="{ submitting: false }" @pageshow.window="submitting = false" @submit="if (submitting) { $event.preventDefault(); } else { submitting = true; }" :aria-busy="submitting.toString()" action="{{ route('login.two-factor.email') }}" method="POST" class="mb-3">@csrf<button class="btn btn-outline-secondary" type="submit" :disabled="submitting">Resend enrollment email</button><small class="d-block text-muted mt-2">Wait 60 seconds between requests.</small></form>
                @endif
                <form x-data="{ submitting: false }" @pageshow.window="submitting = false" @submit="if (submitting) { $event.preventDefault(); } else { submitting = true; }" :aria-busy="submitting.toString()" action="{{ route('login.two-factor.verify') }}" method="POST" class="auth-form mb-4">
                    @csrf
                    <label for="requiredEnrollmentCode" class="form-label">Six-digit verification code</label>
                    <input id="requiredEnrollmentCode" class="form-control mb-3" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required autofocus>
                    <button type="submit" :disabled="submitting" class="btn btn-primary w-100">Verify and sign in</button>
                </form>
            @endif
            <form x-data="{ submitting: false }" @pageshow.window="submitting = false" @submit="if (submitting) { $event.preventDefault(); } else { submitting = true; }" :aria-busy="submitting.toString()" action="{{ route('login.two-factor.enroll') }}" method="POST" class="auth-form">
                @csrf
                <label for="requiredEnrollmentMethod" class="form-label">{{ $enrollment_started ? 'Start again with a method' : 'Your verification method' }}</label>
                <select id="requiredEnrollmentMethod" name="method" class="form-select mb-3">
                    <option value="authenticator">Authenticator app</option>
                    <option value="email" @disabled(!$email_available)>Email verification{{ !$email_available ? ' — delivery unavailable' : '' }}</option>
                </select>
                <button type="submit" :disabled="submitting" class="btn btn-outline-primary w-100">{{ $enrollment_started ? 'Restart setup' : 'Continue setup' }}</button>
                @if(!$email_available)<p class="small text-muted mt-2">Email delivery is unavailable. You can enroll an authenticator app without email.</p>@endif
            </form>
            <a href="{{ route('login') }}" class="d-block mt-3">Back to sign in</a>
        </div>
    </main>
    <aside class="auth-aside"><div class="auth-aside-inner"><p class="auth-quote">Your account. Your verification method.</p><p>No application access is granted until your code is verified.</p></div></aside>
</div>
@endsection
