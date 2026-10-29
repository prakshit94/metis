@php
    $labelOptions = collect(['Home', 'Shop', 'Work', 'Office', 'Farm', 'Billing', 'Shipping', 'Other'])
        ->merge(Auth::guard('customer')->user()->customer?->addresses()->where('status', 'active')->pluck('label') ?? [])
        ->push($labelValue)->filter()->unique()->values();
@endphp
<select id="{{ $labelId }}" name="label" @required($labelRequired)>
    <option value="">Select address label</option>
    @foreach($labelOptions as $option)
        <option value="{{ $option }}" @selected($labelValue === $option)>{{ $option }}</option>
    @endforeach
</select>
