<div class="full village-directory" data-village-lookup data-village-url="{{ route('shop.address.villages') }}">
    <label for="{{ $lookupId }}-search">Village directory</label>
    <div class="village-search-controls"><input id="{{ $lookupId }}-search" data-village-search type="search" maxlength="100" autocomplete="off" placeholder="Search village, post office, taluka, district, state or pincode" aria-controls="{{ $lookupId }}-results"><button type="button" class="text-button" data-village-clear>Clear search</button></div>
    <p class="muted" data-village-status role="status" aria-live="polite">Choose a village to fill the address details, or enter them below.</p>
    <div id="{{ $lookupId }}-results" class="village-results" data-village-results hidden></div>
</div>
