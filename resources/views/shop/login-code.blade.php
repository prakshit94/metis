@extends('shop.layout')
@section('title', 'Enter your sign-in code · Metis Market')
@section('content')
<section class="login-panel">
    <div class="login-intro"><span class="eyebrow">ONE CODE. YOU’RE IN.</span><h1>Check your<br><em>inbox.</em></h1><p>Use the code sent to your verified customer email to finish signing in.</p><span class="login-symbol" aria-hidden="true">m.</span></div>
    <div class="login-form">
        <span class="eyebrow">EMAIL CODE SIGN-IN</span><h2>Enter your sign-in code.</h2>
        <p>If <strong>{{ $email }}</strong> belongs to an eligible customer account, we’ve sent a six-digit code. Check your inbox and spam folder.</p>
        <form action="{{ route('shop.login.code.verify') }}" method="post">
            @csrf
            <label for="code">Six-digit code</label>
            <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" minlength="6" maxlength="6" required autofocus>
            <button class="button dark code-submit">Verify and sign in →</button>
        </form>
        <p>The sign-in attempt expires five minutes after the first code request.</p>
        <form action="{{ route('shop.login.code.resend') }}" method="post">@csrf<button class="text-button">Resend code</button></form>
        @if($resendIn > 0)<p class="muted">You can request another code in {{ $resendIn }} seconds.</p>@endif
        <form action="{{ route('shop.login.code.cancel') }}" method="post">@csrf<button class="text-button">Use another email or sign-in method →</button></form>
    </div>
</section>
@endsection
