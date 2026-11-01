@extends('layouts.auth')
@section('title', 'Verify sign in')
@section('page', 'login')
@section('content')
@php
    $background = \App\Models\SystemSetting::where('key', 'login_background_image')->value('value') ?: asset('assets/images/background.png');
    $authLogo = \App\Services\BrandLogo::resolve(\App\Models\SystemSetting::where('key', 'default_image_logo_png')->value('value'));
    $logo = $authLogo['src'];
@endphp
<div class="login-page" style="--auth-background-image: url('{{ $background }}');">
    <main class="auth-main" id="main-content">
        <div class="auth-body">
            <a class="auth-brand auth-form-brand" href="{{ route('login') }}"><img src="{{ $logo }}" alt="Metis logo" decoding="async" onerror="this.onerror=null;this.src='{{ $authLogo['fallback'] }}';"><span>Metis</span></a>
            <div class="auth-heading"><h1>Verify your sign in</h1><p>{{ $method === 'email' ? 'Enter the code sent to ' . $email_hint . ', or one unused recovery code.' : 'Enter a six-digit authenticator code or one unused recovery code.' }} The sign-in request expires after five minutes.</p></div>
            @if(session('status'))<div class="alert alert-info" role="status">{{ session('status') }}</div>@endif
            @if(session('error'))<div class="alert alert-danger" role="alert">{{ session('error') }}</div>@endif
            <form x-data="{ submitting: false }" @pageshow.window="submitting = false" @submit="if (submitting) { $event.preventDefault(); } else { submitting = true; }" :aria-busy="submitting.toString()" method="POST" action="{{ route('login.two-factor.verify') }}" class="auth-form">
                @csrf
                <label for="twoFactorCode" class="form-label">Verification or recovery code</label>
                <input id="twoFactorCode" class="form-control mb-3" type="text" name="code" autocomplete="one-time-code" autocapitalize="none" spellcheck="false" maxlength="64" required autofocus>
                <p class="small text-muted">{{ $method === 'email' ? 'Email codes expire in five minutes. Each code can be used once for this sign-in request.' : 'Each code can be used once. If you just used an authenticator code, wait for the next one.' }}</p>
                <button type="submit" :disabled="submitting" class="btn btn-primary auth-submit-btn w-100">Verify and sign in</button>
            </form>
            @if($method === 'email')
                <form x-data="{ submitting: false }" @pageshow.window="submitting = false" @submit="if (submitting) { $event.preventDefault(); } else { submitting = true; }" :aria-busy="submitting.toString()" method="POST" action="{{ route('login.two-factor.email') }}" class="mt-3">@csrf<button type="submit" :disabled="submitting" class="btn btn-outline-secondary w-100">Resend verification email</button><p class="small text-muted mt-2">Wait 60 seconds between requests. Check your spam folder. Resending replaces the previous code.</p></form>
            @endif
            <a href="{{ route('login') }}" class="mt-3 text-center">Back to sign in</a>
        </div>
    </main>
    <aside class="auth-aside" aria-label="Account protection"><div class="auth-aside-inner"><p class="auth-quote">An extra step to protect your workspace.</p><p>Your sign-in completes only after verification.</p></div></aside>
</div>
@endsection
