@php
    $customer = Auth::guard('customer')->user()->customer;
    $addresses = $customer ? app(App\Storefront\Services\StorefrontAddresses::class)->available($customer) : collect();
    $default = $addresses->firstWhere('is_default', true) ?? $addresses->first();
    $selectedId = old('delivery_address_choice') ?: ($default['id'] ?? '');
    $selected = $addresses->firstWhere('id', (int) $selectedId);
    $fields = App\Storefront\Services\StorefrontAddresses::FIELDS;
@endphp
<div class="checkout-address">
    <div class="form-grid">
        @foreach(['name' => 'Full name', 'phone' => 'Phone number'] as $field => $label)
            <div><label for="{{ $field }}">{{ $label }} *</label><input id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $field === 'name' ? $customer?->name : $customer?->phone) }}" required maxlength="{{ $field === 'name' ? 150 : 20 }}" @if($field === 'phone') type="tel" @endif></div>
        @endforeach
    </div>
    <div class="address-section-heading"><p class="muted">Select a saved address or add a new delivery address.</p><button type="button" class="button address-add-button" data-address-open>Add address +</button></div>
    <div data-saved-addresses>@include('shop.saved-addresses')</div>
    <p class="muted" data-address-notice role="status" aria-live="polite"></p>
    @foreach($fields as $field => $label)
        <input type="hidden" name="{{ $field }}" value="{{ $selected[$field] ?? '' }}">
    @endforeach
    <section class="billing-address-section">
        <h3>Billing address</h3>
        <input type="hidden" name="billing_same_as_shipping" value="0">
        <label class="address-default-option"><input type="checkbox" name="billing_same_as_shipping" value="1" data-billing-same @checked(old('billing_same_as_shipping', '1') === '1' || old('billing_same_as_shipping') === 1)> Billing address is the same as shipping address</label>
        <div data-billing-addresses @if((string) old('billing_same_as_shipping', '1') !== '0') hidden @endif>
            @include('shop.billing-addresses')
        </div>
    </section>
</div>
