<fieldset class="saved-delivery-addresses"><legend>Billing address *</legend>
<div class="saved-address-grid">
    @forelse($addresses as $billingAddress)
        <label class="saved-address-card">
            <input type="radio" name="billing_address_choice" value="{{ $billingAddress['id'] }}" data-billing-choice @checked((string) old('billing_address_choice') === (string) $billingAddress['id'])>
            <span><span class="saved-address-heading"><strong>{{ $billingAddress['label'] }}</strong>@if($billingAddress['is_default'])<small class="address-default">Default</small>@endif</span>
            <span class="saved-address-copy">{{ implode(', ', array_filter(Illuminate\Support\Arr::only($billingAddress, ['address_line_1','address_line_2','village_name','post_office','taluka','district','city','state','pincode']))) }}</span></span>
        </label>
    @empty
        <p class="muted" data-no-addresses>No saved billing addresses yet. Use Add address above to save one.</p>
    @endforelse
</div>
</fieldset>
<p class="muted">Select a saved billing address, or use Add address above to save another.</p>
