@extends('shop.layout')
@section('title', 'Customer sign in · Metis Market')
@section('content')
<section class="login-panel">
    <div class="login-intro">
        <span class="eyebrow">YOUR EVERYDAY STARTS HERE</span>
        <h1>Welcome<br>to your<br><em>marketplace.</em></h1>
        <p>Sign in to place orders and keep track of your purchases.</p>
        <span class="login-symbol" aria-hidden="true">m.</span>
    </div>
    <form class="login-form" method="post" action="{{ route('shop.login.submit') }}">
        @csrf
        <input type="hidden" name="return_to" value="{{ $returnTo ?? 'shop' }}">
        <input type="hidden" name="login_method" value="{{ $loginMethod }}">
        <span class="eyebrow">CUSTOMER ACCOUNT</span>
        <h2>Good to see you.</h2>
        <nav class="login-methods" aria-label="Choose how to sign in">
            <a href="{{ route('shop.login', ['return_to' => $returnTo, 'method' => 'password']) }}" class="{{ $loginMethod === 'password' ? 'selected' : '' }}" @if($loginMethod === 'password') aria-current="page" @endif>Password</a>
            <a href="{{ route('shop.login', ['return_to' => $returnTo, 'method' => 'code']) }}" class="{{ $loginMethod === 'code' ? 'selected' : '' }}" @if($loginMethod === 'code') aria-current="page" @endif>Email code</a>
        </nav>
        <p>{{ $loginMethod === 'code' ? 'We’ll email a sign-in code to your verified customer email. No password needed.' : 'Sign in with your existing customer account to continue shopping.' }}</p>
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="username" maxlength="255" required>
        @if($loginMethod === 'password')
            <label for="password">Password</label>
            <input type="password" id="password" name="password" autocomplete="current-password" required>
        @endif
        <button class="button dark">{{ $loginMethod === 'code' ? 'Send sign-in code →' : 'Sign in →' }}</button>
        <div class="account-help"><b>New here or need help signing in?</b><p>Create a customer account below, or contact support for help with an existing account.</p></div>
        <a href="{{ route('shop.register') }}">Create an account →</a>
    </form>
</section>
<form class="verification-form" action="{{ route('shop.verification.resend') }}" method="post">@csrf<label for="resend-email">Need a new account-verification email?</label><input id="resend-email" name="email" type="email" placeholder="Your email address" required><button class="text-button">Resend verification</button></form>
@endsection
