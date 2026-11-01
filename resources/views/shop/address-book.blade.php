<div class="saved-address-grid">
@forelse($addresses as $savedAddress)
<article class="saved-address-card"><div><strong>{{ $savedAddress['label'] }}</strong> @if($savedAddress['is_default'])<small class="address-default">Default</small>@endif
<p>{{ implode(', ', array_filter(Illuminate\Support\Arr::only($savedAddress, ['address_line_1','address_line_2','village_name','post_office','taluka','district','city','state','pincode']))) }}</p>
<button type="button" class="text-button" data-address-edit="{{ json_encode($savedAddress) }}" data-address-url="{{ route('shop.addresses.update', $savedAddress['id']) }}">Edit address</button></div></article>
@empty<p class="muted">No saved addresses yet.</p>@endforelse
</div>
