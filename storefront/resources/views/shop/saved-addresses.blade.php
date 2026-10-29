@php($addressFields = array_keys(Storefront\Services\StorefrontAddresses::FIELDS))
<fieldset class="saved-delivery-addresses"><legend>Saved delivery addresses</legend>
<div class="saved-address-grid">
@forelse($addresses as $savedAddress)
<div class="saved-address-entry"><label class="saved-address-card">
    <input type="radio" name="delivery_address_choice" required value="{{ $savedAddress['id'] }}" data-saved-address="{{ json_encode(Illuminate\Support\Arr::only($savedAddress, $addressFields)) }}" @checked((string) $selectedId === (string) $savedAddress['id'])>
    <span><span class="saved-address-heading"><strong>{{ $savedAddress['label'] }}</strong>@if($savedAddress['is_default'])<small class="address-default">Default</small>@endif</span>
    <span class="saved-address-copy">{{ implode(', ', array_filter(Illuminate\Support\Arr::only($savedAddress, ['address_line_1','address_line_2','village_name','post_office','taluka','district','city','state','pincode']))) }}</span></span>
</label>
<button type="button" class="text-button" data-address-edit="{{ json_encode($savedAddress) }}" data-address-url="{{ route('shop.addresses.update', $savedAddress['id']) }}">Edit {{ $savedAddress['label'] }} address</button></div>
@empty
<p class="muted" data-no-addresses>You haven’t saved any delivery addresses yet. Add one to use it again next time.</p>
@endforelse
</div>
</fieldset>
