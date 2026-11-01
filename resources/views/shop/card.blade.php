<article class="product-card">
    <a href="{{ route('shop.product', $product->slug) }}" class="product-art" aria-label="View {{ $product->name }}">
        @php
            $image = $product->images->first();
        @endphp
        @if($image)<img src="{{ $image->url }}" alt="{{ $product->name }}" loading="{{ $loop->index < 4 ? 'eager' : 'lazy' }}" decoding="async" onerror="this.hidden=true;this.nextElementSibling.hidden=false;"><span class="placeholder-art" aria-hidden="true" hidden>{{ mb_substr($product->name, 0, 1) }}</span>@else<span class="placeholder-art" aria-hidden="true">{{ mb_substr($product->name, 0, 1) }}</span>@endif
        @if($product->is_featured)<span class="badge">Featured pick</span>@endif
    </a>
    <div class="product-copy"><small>{{ $product->sku }}</small><a class="product-name" href="{{ route('shop.product', $product->slug) }}">{{ $product->name }}</a>
    <div class="price">₹{{ number_format($product->display_price, 2) }} @if($product->mrp > $product->display_price)<del>₹{{ number_format($product->mrp, 2) }}</del><span>{{ round(100 * (1 - $product->display_price / $product->mrp)) }}% off</span>@endif</div>
    <p class="muted">Including applicable GST</p>
    @include('shop.quantity-control')
    </div>
</article>
