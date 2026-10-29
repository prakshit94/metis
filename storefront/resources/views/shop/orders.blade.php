@extends('shop.layout')
@section('title', 'My orders · Metis Market')
@section('content')
<section class="orders-page compact-orders">
    <div class="orders-heading"><div><span class="eyebrow">YOUR CUSTOMER ACCOUNT</span><h1>My orders</h1><p class="muted">Your purchases, all in one place.</p></div><a href="{{ route('shop.index') }}">Continue shopping ↗</a></div>
    @forelse($orders as $order)
        @php
            $orderStatus = \Storefront\Services\OrderStatus::canonical($order->status);
            $orderItems = collect($items[$order->id] ?? []);
        @endphp
        <article class="order-record compact-order" aria-labelledby="order-reference-{{ $order->id }}">
            <div class="compact-order-top">
                <div class="compact-order-reference"><h2 id="order-reference-{{ $order->id }}">{{ $order->order_no }}</h2><time datetime="{{ \Carbon\Carbon::parse($order->created_at)->toIso8601String() }}">{{ \Carbon\Carbon::parse($order->created_at)->format('d M Y, h:i A') }}</time></div>
                <span class="status status-{{ \Storefront\Services\OrderStatus::tone($orderStatus) }}">{{ \Storefront\Services\OrderStatus::label($orderStatus) }}</span>
                <div class="compact-order-total"><small>Total</small><strong>₹{{ number_format($order->net_amount, 2) }}</strong></div>
            </div>
            <div class="compact-order-meta"><span>Placed by: <b>{{ $placedBy[$order->id] ?? 'Not recorded' }}</b></span><span>Payment on delivery</span><span>Paid <b>₹{{ number_format($order->total_paid, 2) }}</b></span>
                @if($orderStatus === 'scheduled' && ($order->future_order_date ?? null))<span>Scheduled for <b>{{ $order->future_order_date }}</b></span>@endif
            </div>
            <details class="compact-order-details">
                <summary><span class="compact-order-preview"><b>{{ $orderItems->count() }} {{ $orderItems->count() === 1 ? 'item' : 'items' }}</b><span>{{ $orderItems->take(2)->map(fn ($item) => ($item->name ?: 'Product').' × '.(float) $item->quantity)->implode(' · ') }}{{ $orderItems->count() > 2 ? ' · +'.($orderItems->count() - 2).' more' : '' }}</span></span><span class="order-details-toggle"><span class="details-closed">View details</span><span class="details-open">Hide details</span><span aria-hidden="true">⌄</span></span></summary>
                <div class="compact-order-body">
                    <div class="compact-order-items"><h3>Order items</h3>
                        @foreach($orderItems as $item)
                            <div class="order-line"><span>{{ $item->name ?: 'Product' }} <small> × {{ (float) $item->quantity }}</small></span><b>₹{{ number_format($item->total_amount, 2) }}</b></div>
                        @endforeach
                    </div>
                    <div class="compact-order-addresses">
                        <h3>Delivery address</h3><p><b>{{ Auth::guard('customer')->user()?->customer?->name }}</b><br>{{ implode(', ', array_filter([$order->shipping_address_line_1, $order->shipping_address_line_2, $order->shipping_village_name, $order->shipping_post_office, $order->shipping_taluka, $order->shipping_district, $order->shipping_city, $order->shipping_state, $order->shipping_pincode])) }}</p>
                        @if($order->billing_address_line_1)
                            <h3>Billing address</h3><p>{{ implode(', ', array_filter([$order->billing_address_line_1, $order->billing_address_line_2, $order->billing_village_name, $order->billing_post_office, $order->billing_taluka, $order->billing_district, $order->billing_city, $order->billing_state, $order->billing_pincode])) }}</p>
                        @endif
                    </div>
                    @if(count($logs[$order->id] ?? []))
                        <div class="compact-order-updates"><h3>Order updates</h3><ol>
                            @foreach($logs[$order->id] as $log)<li><strong>{{ \Storefront\Services\OrderStatus::label($log->status) }}</strong><time datetime="{{ \Carbon\Carbon::parse($log->created_at)->toIso8601String() }}">{{ \Carbon\Carbon::parse($log->created_at)->format('d M Y, h:i A') }}</time></li>@endforeach
                        </ol></div>
                    @endif
                    @foreach(\Storefront\Services\OrderStatus::customerActions($orderStatus) as $action)
                        <form action="{{ route('shop.orders.status', $order->id) }}" method="post" class="order-customer-action">
                            @csrf
                            <input type="hidden" name="status" value="{{ $action }}"><input type="hidden" name="expected_status" value="{{ $order->status }}">
                            <label for="order-action-reason-{{ $order->id }}-{{ $action }}">{{ $action === 'cancelled' ? 'Reason for cancellation' : 'Reason for return' }}</label>
                            <input id="order-action-reason-{{ $order->id }}-{{ $action }}" name="notes" maxlength="1000" required placeholder="Tell us the reason">
                            <button class="button" type="submit">{{ $action === 'cancelled' ? 'Cancel this order' : 'Request a return' }}</button>
                        </form>
                    @endforeach
                </div>
            </details>
        </article>
    @empty
        <div class="empty"><h2>Your story starts with a good find.</h2><p>You haven’t placed any orders yet.</p><a class="button dark" href="{{ route('shop.index') }}">Start shopping →</a></div>
    @endforelse
    <div class="pagination">@if($orders->previousPageUrl())<a href="{{ $orders->previousPageUrl() }}">← Previous</a>@endif @if($orders->nextPageUrl())<a href="{{ $orders->nextPageUrl() }}">Next →</a>@endif</div>
</section>
@endsection
