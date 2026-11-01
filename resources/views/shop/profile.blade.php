@extends('shop.layout')
@section('title', 'My profile · Metis Market')
@section('content')
<section class="profile-page">
    <a class="profile-back" href="{{ route('shop.index') }}">← Continue shopping</a>
    <div class="section-heading"><div><span class="eyebrow">YOUR CUSTOMER ACCOUNT</span><h1>My profile</h1><p>Your account details, all in one place.</p></div><a class="button dark" href="{{ route('shop.orders') }}">View my orders ↗</a></div>
    <div class="profile-grid">
        <aside class="profile-identity"><div class="profile-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($account->customer->name, 0, 1)) }}</div><h2>{{ $account->customer->name }}</h2><p>{{ $account->email }}</p><span class="profile-verified">✓ Email verified</span></aside>
        <div class="profile-details"><span class="eyebrow">PERSONAL DETAILS</span><h2>Edit your profile</h2>
            <form action="{{ route('shop.profile.update') }}" method="post" class="profile-edit">
                @csrf @method('PATCH')
                <div class="form-grid">
                    <div><label for="profile-firstname">First name</label><input id="profile-firstname" name="firstname" autocomplete="given-name" maxlength="100" required value="{{ old('firstname', $account->customer->firstname) }}"></div>
                    <div><label for="profile-middlename">Middle name <span class="muted">(optional)</span></label><input id="profile-middlename" name="middlename" autocomplete="additional-name" maxlength="100" value="{{ old('middlename', $account->customer->middlename) }}"></div>
                    <div><label for="profile-lastname">Last name <span class="muted">(optional)</span></label><input id="profile-lastname" name="lastname" autocomplete="family-name" maxlength="100" value="{{ old('lastname', $account->customer->lastname) }}"></div>
                    <div><label for="profile-phone">Phone number</label><input id="profile-phone" type="tel" name="phone" autocomplete="tel" minlength="10" maxlength="20" required value="{{ old('phone', $account->customer->phone) }}"></div>
                    <div class="full"><label for="profile-email">Primary email · Read only</label><input id="profile-email" type="email" readonly aria-describedby="profile-email-help" value="{{ $account->email }}"><p id="profile-email-help" class="muted">This email is linked to your account and cannot be changed here. Contact your branch for account identity changes.</p></div>
                </div>
                <div class="profile-edit-actions"><button class="button dark">Save changes</button><a href="{{ route('shop.profile') }}">Cancel</a></div>
            </form>
            <p class="muted profile-member">Member since {{ $account->created_at?->format('d M Y') ?? 'Not available' }}</p>
        </div>
    </div>
<div class="profile-details" data-profile-addresses><h2>Address Details</h2><p class="muted">Manage the same saved delivery and billing addresses used at checkout.</p><button type="button" class="button" data-address-open>Add address</button><div data-address-book>@include('shop.address-book', ['addresses' => app(App\Storefront\Services\StorefrontAddresses::class)->available($account->customer)])</div></div>
</section>
@include('shop.address-modal')
<script src="{{ asset('storefront/shop-address.js') }}?v={{ filemtime(public_path('storefront/shop-address.js')) }}" defer></script>
@endsection
