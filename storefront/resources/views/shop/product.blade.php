@extends('shop.layout')
@section('title', $product->name.' · Metis Market')
@section('content')
<nav class="breadcrumbs" aria-label="Breadcrumb"><a href="{{ route('shop.index') }}">Shop</a><span> / </span><span>{{ $product->name }}</span></nav>
<section class="product-detail premium-detail">
    <div class="detail-gallery">@forelse($product->images as $image)<a class="detail-image" href="{{ route('shop.image', [$product->id, $image->id]) }}" target="_blank" rel="noopener"><img src="{{ route('shop.image', [$product->id, $image->id]) }}" alt="{{ $product->name }} — image {{ $loop->iteration }}" @if(!$loop->first) loading="lazy" @endif></a>@empty<div class="detail-placeholder" aria-label="No product image available">{{ mb_substr($product->name, 0, 1) }}</div>@endforelse</div>
    <div class="detail-copy"><span class="eyebrow">THE EVERYDAY COLLECTION</span>@if($product->is_featured)<span class="detail-featured">Featured pick</span>@endif<h1>{{ $product->name }}</h1><p class="muted">SKU · {{ $product->sku }}</p>
        <div class="detail-price-panel"><div class="price large">₹{{ number_format($product->display_price, 2) }} @if($product->mrp > $product->display_price)<del>₹{{ number_format($product->mrp, 2) }}</del><span>{{ round(100 * (1 - $product->display_price / $product->mrp)) }}% off</span>@endif</div><p class="muted">Including applicable GST. Product and order discounts are applied in your shopping bag. No additional shipping charge.</p></div>
        <p class="description">{{ $product->description ?: 'A considered addition to your everyday essentials.' }}</p>
        <div class="detail-purchase"><div><b>Add to your everyday</b><p class="muted">Minimum {{ max(1, $product->min_order_qty) }} · Maximum {{ min(1000, $product->max_order_qty ?? 1000) }} per order</p></div>@include('shop.quantity-control')<a class="view-bag" href="{{ route('shop.cart') }}">View your bag →</a></div>
        <div class="detail-benefits"><div><b>Pay on delivery</b><span>No online payment required</span></div><div><b>Branch fulfilment</b><span>Availability confirmed at checkout</span></div></div>
    </div>
    <section class="product-information"><span class="eyebrow">THE FINER DETAILS</span><h2>Know your product</h2><dl class="product-specs">@foreach(['sku' => 'Product code', 'product_type' => 'Product type', 'country_of_origin' => 'Country of origin', 'weight_g' => 'Weight (g)', 'length_cm' => 'Length (cm)', 'width_cm' => 'Width (cm)', 'height_cm' => 'Height (cm)', 'grade' => 'Grade'] as $key => $label)@if($product->$key !== null && $product->$key !== '')<div><dt>{{ $label }}</dt><dd>{{ $product->$key }}</dd></div>@endif
@endforeach
<div><dt>Currency</dt><dd>{{ $product->currency ?: 'INR' }}</dd></div></dl>@if($product->application_instructions)<h3>How to use</h3><p class="description">{{ $product->application_instructions }}</p>@endif</section>
</section>
@endsection
