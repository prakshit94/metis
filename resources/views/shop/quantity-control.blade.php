    @php
        $quantity = (int) session('shop.cart.'.$product->id, 0);
        $minimum = max(1, (int) $product->min_order_qty);
        $maximum = min(1000, (int) ($product->max_order_qty ?? 1000));
    @endphp
    <form class="quick-cart" action="{{ route('shop.cart.update') }}" method="post" data-quantity="{{ $quantity }}" data-min="{{ $minimum }}" data-max="{{ $maximum }}" aria-label="Cart controls for {{ $product->name }}">
        @csrf
        <input type="hidden" name="product_id" value="{{ $product->id }}">
        <button class="add-cart" name="quantity" value="{{ $minimum }}" @if($quantity) hidden @endif>Add to cart <span aria-hidden="true">+</span></button>
        <div class="cart-stepper" @if(!$quantity) hidden @endif>
            <button name="quantity" value="{{ $quantity <= $minimum ? 0 : $quantity - 1 }}" data-step="minus" aria-label="Decrease quantity of {{ $product->name }}">−</button>
            <output aria-live="polite">{{ $quantity }}</output>
            <button name="quantity" value="{{ min($maximum, $quantity + 1) }}" data-step="plus" aria-label="Increase quantity of {{ $product->name }}" @disabled($quantity >= $maximum)>+</button>
        </div>
        <p class="cart-error" role="alert" hidden></p>
    </form>
