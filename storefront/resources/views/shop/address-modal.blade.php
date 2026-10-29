<dialog id="shop-address-dialog" class="shop-address-dialog" aria-labelledby="shop-address-title">
<form action="{{ route('shop.addresses.store') }}" method="post" data-address-form>
    @csrf
    <input type="hidden" name="village_id" value="">
    <div class="address-modal-header"><div><span class="eyebrow">ADDRESS DETAILS</span><h2 id="shop-address-title">Add a delivery address</h2></div><button type="button" class="text-button" data-address-close aria-label="Close address form">✕</button></div>
    <p class="muted">Add or edit your address details. Search the village directory to fill village, post office, taluka, district, state and PIN code.</p>
    <div class="error" role="alert" tabindex="-1" data-address-error hidden></div>
    <fieldset class="address-modal-fields"><legend>Address Details</legend><div class="form-grid">
        @include('shop.village-lookup', ['lookupId' => 'new-address-village'])
        @foreach(Storefront\Services\StorefrontAddresses::FIELDS as $field => $label)
        <div @class(['full' => $field === 'label'])><label for="new-address-{{ $field }}">{{ $label }}{{ in_array($field, ['label','address_line_1','state','pincode']) ? ' *' : '' }}</label>@if($field === 'label')
            @include('shop.address-label', ['labelId' => 'new-address-label', 'labelValue' => '', 'labelRequired' => true])
        @else
            <input id="new-address-{{ $field }}" name="{{ $field }}" maxlength="{{ $field === 'pincode' ? 6 : 255 }}" @required(in_array($field, ['label','address_line_1','state','pincode'])) @if($field === 'pincode') pattern="[0-9]{6}" inputmode="numeric" @endif>@endif</div>
        @endforeach
    </div><p class="muted">A village or city is required.</p><label class="address-default-option"><input type="checkbox" name="is_default" value="1"> Make this my default delivery address</label></fieldset>
    <div class="address-modal-footer"><button type="button" class="button" data-address-close>Cancel</button><button type="submit" class="button dark" data-address-save>Save and use address →</button></div>
</form>
</dialog>
