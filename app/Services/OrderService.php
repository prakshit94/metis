<?php

namespace App\Services;

use App\Modules\Catalog\Models\Product;
use App\Modules\Customers\Models\Customer;
use App\Modules\Customers\Models\Party;
use App\Modules\Customers\Models\PartyAddress;
use App\Modules\Orders\Models\Coupon;
use App\Modules\Orders\Models\Offer;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\Shipment;
use App\Notifications\OrderCreatedNotification;
use App\Notifications\OrderStatusChangedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * OrderService – SINGLE SOURCE OF TRUTH for order creation and mutation.
 *
 * Inventory side-effects (reserve / deduct) are delegated entirely to
 * InventoryService so there is no duplication of stock logic here.
 */
class OrderService
{
    public function __construct(protected InventoryService $inventoryService) {}

    private function calculateLineDiscount(float $unitPrice, float $qty, float $discountValue, ?string $discountType): float
    {
        $itemBase = $unitPrice * $qty;
        $type = strtolower((string) $discountType);

        if ($discountValue <= 0 || $itemBase <= 0) {
            return 0.0;
        }

        if ($type === 'percent' || $type === 'percentage') {
            return $itemBase * ($discountValue / 100);
        }

        return min($discountValue * $qty, $itemBase);
    }

    private function calculateBogoDiscount(float $lineTotal, float $qty, int $buyQty, int $getQty): float
    {
        $cycle = max(1, $buyQty + $getQty);
        if ($qty < $cycle || $lineTotal <= 0) {
            return 0.0;
        }

        $freeUnits = floor($qty / $cycle) * $getQty;
        $effectiveUnit = $qty > 0 ? ($lineTotal / $qty) : 0.0;

        return min($effectiveUnit * $freeUnits, $lineTotal);
    }

    private function calculateOfferDiscount(float $subtotal, Offer $offer): float
    {
        if ($subtotal <= 0 || (float) $offer->min_spend > $subtotal) {
            return 0.0;
        }

        $discount = $offer->discount_type === 'percentage'
            ? $subtotal * ((float) $offer->value / 100)
            : (float) $offer->value;

        if ((float) $offer->max_discount > 0) {
            $discount = min($discount, (float) $offer->max_discount);
        }

        return min($discount, $subtotal);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Create
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Create a new order (sale or purchase).
     */
    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            // Generate order number: {daily_seq}-{MMDDYYYY}-{HHMM}
            $today = now();
            $datePart = $today->format('dmY');   // DDMMYYYY
            $todayStart = $today->copy()->startOfDay();
            $todayEnd = $today->copy()->endOfDay();
            // Atomic sequence: lock the last inserted row so concurrent requests get distinct counts
            $dailyCount = Order::whereBetween('created_at', [$todayStart, $todayEnd])
                ->lockForUpdate()
                ->count() + 1;
            $seq = str_pad((string) $dailyCount, 2, '0', STR_PAD_LEFT);
            $orderNo = "ORD-{$datePart}-{$seq}";

            $shippingAddressFields = [];
            if (! empty($data['shipping_address_id'])) {
                $shippingAddressFields = $this->mapAddressFields(PartyAddress::find($data['shipping_address_id']), 'shipping');
            }
            $billingAddressFields = [];
            if (! empty($data['billing_address_id'])) {
                $billingAddressFields = $this->mapAddressFields(PartyAddress::find($data['billing_address_id']), 'billing');
            }

            $orderPayload = array_merge([
                'order_no' => $orderNo,
                'type' => $data['type'],
                'party_id' => $data['party_id'],
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'order_date' => $data['order_date'] ?? now(),
                'total_amount' => $data['total_amount'] ?? 0,
                'tax_amount' => $data['tax_amount'] ?? 0,
                'discount_amount' => $data['discount_amount'] ?? 0,
                'coupon_code' => $data['coupon_code'] ?? null,
                'applied_offer_id' => $data['applied_offer_id'] ?? null,
                'net_amount' => $data['net_amount'] ?? 0,
                'status' => 'pending',
                'is_draft' => $data['is_draft'] ?? false,
                'future_order_date' => $data['future_order_date'] ?? null,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ], $shippingAddressFields, $billingAddressFields);

            $order = Order::create($orderPayload);

            foreach ($data['items'] as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'discount_amount' => $item['discount_amount'] ?? 0,
                    'tax_amount' => $item['tax_amount'] ?? 0,
                    'total_amount' => $item['total_amount']
                        ?? (($item['quantity'] * $item['unit_price']) - ($item['discount_amount'] ?? 0)),
                ]);
            }

            $order->load('items');
            $itemsTotal = (float) $order->items->sum('total_amount');
            $taxAmount = (float) ($data['tax_amount'] ?? 0);
            $discount = (float) ($data['discount_amount'] ?? 0);

            $order->update([
                'total_amount' => $data['total_amount'] ?? $itemsTotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discount,
                'net_amount' => $data['net_amount'] ?? max(0, $itemsTotal - $discount + $taxAmount),
            ]);

            if ($order->coupon_code) {
                Coupon::where('code', $order->coupon_code)->increment('used_count');
            }

            if ($order->applied_offer_id) {
                Offer::where('id', $order->applied_offer_id)->increment('used_count');
            }

            if (! empty($data['applied_bogo_ids'])) {
                Offer::whereIn('id', $data['applied_bogo_ids'])->increment('used_count');
            }

            if (! empty($data['use_wallet_balance'])) {
                $party = Party::find($data['party_id']);
                if ($party && (float) $party->outstanding_balance > 0.0) {
                    // outstanding_balance is a positive credit — subtract from net payable
                    $netPayable = $order->net_amount - (float) $party->outstanding_balance;
                    if ($netPayable <= 0) {
                        // Wallet covers the full order — deduct only the order amount from wallet
                        $party->outstanding_balance = abs($netPayable);
                        $order->update(['net_amount' => 0]);
                    } else {
                        // Wallet partially covers the order — drain wallet, reduce payable
                        $party->outstanding_balance = 0;
                        $order->update(['net_amount' => $netPayable]);
                    }
                    $party->save();
                }
            }

            // Dispatch Notification (fail-safe: never break core flow)
            try {
                $admins = \App\Modules\Users\Models\User::role(['Admin', 'Super Admin', 'Sales Admin'])->get();
                Notification::send($admins, new OrderCreatedNotification($order->order_no, (float) $order->net_amount, clone $order->party ? clone $order->party->name : 'Unknown'));
            } catch (\Throwable) {
                // Silently fail — notification delivery is non-critical
            }

            return $order->refresh();
        }, 3);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Customer "Place Order" (cart-based)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Recalculate order totals and validate coupon code.
     * Must be called inside a database transaction if a coupon is being updated.
     */
    private function recalculateAndValidate(array $data, ?string $lastCouponCode = null): array
    {
        $cart = json_decode($data['cart'], true);
        if (empty($cart)) {
            throw ValidationException::withMessages([
                'cart' => 'Cart is empty or contains invalid items.',
            ]);
        }

        // Load products to verify prices
        $productIds = array_filter(array_column($cart, 'id'));
        $products = Product::whereIn('id', $productIds)
            ->with('taxRate')
            ->get()
            ->keyBy('id');

        $activeOffers = Offer::active()
            ->where(function ($query) use ($productIds) {
                $query->whereNull('product_id')
                    ->orWhereIn('product_id', $productIds);
            })
            ->orderByDesc('priority')
            ->orderBy('id')
            ->get();

        $orderOffers = $activeOffers->where('type', 'order_discount')->values();
        $bogoOffers = $activeOffers->where('type', 'bogo')->groupBy('product_id');

        $items = [];
        $subtotal = 0.0;
        $taxAmount = 0.0;
        $totalItemDiscount = 0.0;
        $bogoDiscount = 0.0;

        foreach ($cart as $item) {
            if (empty($item['id']) || ! isset($item['quantity']) || (float) $item['quantity'] <= 0) {
                continue;
            }

            $product = $products->get($item['id']);
            if (! $product) {
                throw ValidationException::withMessages([
                    'cart' => "Product with ID {$item['id']} is invalid or does not exist.",
                ]);
            }

            if (!in_array($product->status, ['active', 'published']) || !$product->is_active) {
                throw ValidationException::withMessages([
                    'cart' => "Product '{$product->name}' is currently unavailable.",
                ]);
            }

            $qty = (float) $item['quantity'];
            $unitPrice = (float) $product->selling_price;
            $itemBase = $unitPrice * $qty;

            // Product-level discount
            $discountValue = (float) ($product->default_discount ?? 0);
            $discountType = $product->default_discount_type ?? 'percent';
            $itemDisc = $this->calculateLineDiscount($unitPrice, $qty, $discountValue, $discountType);

            $itemTotal = $itemBase - $itemDisc;

            // Recalculate tax
            $taxRateVal = (float) ($product->taxRate?->rate ?? 0);
            $itemTax = $itemTotal * ($taxRateVal / 100);

            $subtotal += $itemTotal;
            $totalItemDiscount += $itemDisc;
            $taxAmount += $itemTax;

            $items[] = [
                'product_id' => $product->id,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'tax_rate' => $taxRateVal,
                'discount_amount' => $itemDisc,
                'tax_amount' => $itemTax,
                'total_amount' => $itemTotal,
            ];
        }

        $appliedBogoIds = [];
        // Calculate BOGO on second pass to respect minimum spend
        foreach ($items as $item) {
            $productBogoOffer = $bogoOffers->get($item['product_id'])?->first()
                             ?? $bogoOffers->get('')?->first();

            if ($productBogoOffer && $subtotal >= ((float) $productBogoOffer->min_spend ?? 0)) {
                $disc = $this->calculateBogoDiscount(
                    $item['total_amount'],
                    $item['quantity'],
                    (int) $productBogoOffer->buy_qty,
                    (int) $productBogoOffer->get_qty
                );
                if ($disc > 0) {
                    $bogoDiscount += $disc;
                    $appliedBogoIds[] = $productBogoOffer->id;
                }
            }
        }
        $appliedBogoIds = array_unique($appliedBogoIds);

        if (empty($items)) {
            throw ValidationException::withMessages([
                'cart' => 'Cart is empty or contains invalid items.',
            ]);
        }

        // Validate coupon code
        $couponDiscount = 0.0;
        $couponCode = null;
        if (! empty($data['coupon_code'])) {
            $code = strtoupper(trim($data['coupon_code']));
            $coupon = Coupon::where('code', $code)
                ->lockForUpdate()
                ->first();

            if (! $coupon) {
                throw ValidationException::withMessages([
                    'coupon_code' => 'Invalid promo code.',
                ]);
            }

            if (! $coupon->is_active) {
                throw ValidationException::withMessages([
                    'coupon_code' => 'This promo code is inactive.',
                ]);
            }

            if ($coupon->expiry_date && $coupon->expiry_date < now()->startOfDay()) {
                throw ValidationException::withMessages([
                    'coupon_code' => 'This promo code has expired.',
                ]);
            }

            $alreadyUsedThisCoupon = $lastCouponCode !== null
                && strcasecmp($lastCouponCode, $code) === 0;

            $currentUsedCount = (int) $coupon->used_count;
            if ($coupon->usage_limit && $currentUsedCount >= $coupon->usage_limit && ! $alreadyUsedThisCoupon) {
                throw ValidationException::withMessages([
                    'coupon_code' => 'This promo code usage limit has been reached.',
                ]);
            }

            if ($coupon->min_spend > 0 && $subtotal < $coupon->min_spend) {
                throw ValidationException::withMessages([
                    'coupon_code' => 'Minimum spend of ₹'.number_format($coupon->min_spend, 2).' required.',
                ]);
            }

            if ($coupon->type === 'percentage') {
                $couponDiscount = $subtotal * ($coupon->value / 100);
                if ($coupon->max_discount > 0 && $couponDiscount > $coupon->max_discount) {
                    $couponDiscount = (float) $coupon->max_discount;
                }
            } else {
                $couponDiscount = (float) $coupon->value;
            }

            $couponDiscount = min($couponDiscount, $subtotal);
            $couponCode = $coupon->code;
        }

        $appliedOfferId = ! empty($data['applied_offer_id']) ? (int) $data['applied_offer_id'] : null;
        $bestOrderOffer = null;
        $orderDiscount = 0.0;

        if ($appliedOfferId) {
            $bestOrderOffer = $orderOffers->firstWhere('id', $appliedOfferId);
            if ($bestOrderOffer) {
                $eligibleSubtotal = $subtotal;
                if ($bestOrderOffer->product_id) {
                    $eligibleSubtotal = 0.0;
                    foreach ($items as $item) {
                        if ($item['product_id'] == $bestOrderOffer->product_id) {
                            $eligibleSubtotal += $item['total_amount'];
                        }
                    }
                }
                $discount = $this->calculateOfferDiscount($eligibleSubtotal, $bestOrderOffer);
                if ($discount > 0) {
                    $orderDiscount = $discount;
                } else {
                    $bestOrderOffer = null;
                }
            }
        }

        $totalDiscount = $bogoDiscount + $orderDiscount + $couponDiscount;

        if ($totalDiscount > $subtotal) {
            $totalDiscount = $subtotal;
        }

        $grandTotal = max(0.0, $subtotal - $totalDiscount + $taxAmount);

        return [
            'items' => $items,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'bogo_discount' => $bogoDiscount,
            'order_discount_amount' => $orderDiscount,
            'coupon_discount' => $couponDiscount,
            'total_discount' => $totalDiscount,
            'grand_total' => $grandTotal,
            'coupon_code' => $couponCode,
            'order_offer_name' => $bestOrderOffer?->name,
            'applied_offer_id' => $bestOrderOffer?->id,
            'applied_bogo_ids' => $appliedBogoIds,
        ];
    }

    /**
     * Place a new customer order from the cart payload.
     */
    public function placeCustomerOrder(Party $customer, array $data): Order
    {
        if (empty($data['warehouse_id'])) {
            throw ValidationException::withMessages([
                'warehouse_id' => 'A warehouse must be selected to place an order.',
            ]);
        }

        $shippingAddr = PartyAddress::find($data['address_id']);
        $billingAddr = PartyAddress::find($data['billing_address_id'] ?? $data['address_id']);

        return DB::transaction(function () use ($customer, $data, $shippingAddr, $billingAddr) {
            $calc = $this->recalculateAndValidate($data);

            $orderPayload = array_merge([
                'type' => 'sale',
                'party_id' => $customer->id,
                'warehouse_id' => $data['warehouse_id'],
                'order_date' => now(),
                'total_amount' => $calc['subtotal'],
                'tax_amount' => $calc['tax_amount'],
                'discount_amount' => $calc['total_discount'],
                'coupon_code' => $calc['coupon_code'],
                'applied_offer_id' => $calc['applied_offer_id'],
                'net_amount' => $calc['grand_total'],
                'items' => $calc['items'],
                'is_draft' => $data['is_draft'] ?? false,
                'future_order_date' => $data['future_order_date'] ?? null,
                'applied_bogo_ids' => $calc['applied_bogo_ids'],
            ], $this->mapAddressFields($shippingAddr, 'shipping'), $this->mapAddressFields($billingAddr, 'billing'));

            $order = $this->createOrder($orderPayload);

            return $order;
        }, 3);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Customer "Update Order" (cart-based)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Update an existing customer order from the cart payload.
     */
    public function updateCustomerOrder(Order $order, array $data): Order
    {
        $shippingAddressId = $data['shipping_address_id'] ?? $data['address_id'] ?? null;
        $billingAddressId = $data['billing_address_id'] ?? $shippingAddressId;
        $shippingAddr = $shippingAddressId ? PartyAddress::find($shippingAddressId) : null;
        $billingAddr = $billingAddressId ? PartyAddress::find($billingAddressId) : null;

        return DB::transaction(function () use ($order, $data, $shippingAddr, $billingAddr) {
            // Reload with lock
            $order = Order::with('items')->lockForUpdate()->findOrFail($order->id);

            if (! in_array($order->status, ['pending', 'confirmed'], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Only pending or confirmed orders can be updated.',
                ]);
            }

            $lastCouponCode = $order->coupon_code;
            $lastOfferId = $order->applied_offer_id;

            $usingCartPayload = array_key_exists('cart', $data);

            if ($usingCartPayload) {
                $calc = $this->recalculateAndValidate($data, $lastCouponCode);

                // If already confirmed, release all reservations before recalculating
                if ($order->status === 'confirmed' && $order->type === 'sale' && $order->warehouse_id) {
                    foreach ($order->items as $item) {
                        $this->inventoryService->releaseReservedStock(
                            (int) $item->product_id,
                            (int) $order->warehouse_id,
                            (float) $item->quantity,
                            $order->id,
                            'cancelled'
                        );
                    }
                }

                $orderPayload = array_merge([
                    'warehouse_id' => $data['warehouse_id'] ?? $order->warehouse_id,
                    'total_amount' => $calc['subtotal'],
                    'tax_amount' => $calc['tax_amount'],
                    'discount_amount' => $calc['total_discount'],
                    'coupon_code' => $calc['coupon_code'],
                    'applied_offer_id' => $calc['applied_offer_id'],
                    'net_amount' => $calc['grand_total'],
                    'is_draft' => isset($data['is_draft']) ? (bool) $data['is_draft'] : $order->is_draft,
                    'future_order_date' => array_key_exists('future_order_date', $data) ? $data['future_order_date'] : $order->future_order_date,
                    'updated_by' => auth()->id(),
                ], $this->mapAddressFields($shippingAddr, 'shipping'), $this->mapAddressFields($billingAddr, 'billing'));

                $order->update($orderPayload);

                $order->items()->delete();
                foreach ($calc['items'] as $item) {
                    $order->items()->create($item);
                }

                if ($order->status === 'confirmed' && $order->type === 'sale' && $order->warehouse_id) {
                    $order->load('items');
                    foreach ($order->items as $item) {
                        $this->inventoryService->reserveStock(
                            (int) $item->product_id,
                            (int) $order->warehouse_id,
                            (float) $item->quantity,
                            $order->id
                        );
                    }
                }

                $newCouponCode = $calc['coupon_code'];
                if ($newCouponCode !== $lastCouponCode) {
                    if ($lastCouponCode) {
                    Coupon::where('code', $lastCouponCode)->where('used_count', '>', 0)->decrement('used_count');
                    }
                    if ($newCouponCode) {
                        Coupon::where('code', $newCouponCode)->increment('used_count');
                    }
                }

                if (($calc['applied_offer_id'] ?? null) !== $lastOfferId) {
                    if ($lastOfferId) {
                        Offer::where('id', $lastOfferId)->decrement('used_count');
                    }
                    if (! empty($calc['applied_offer_id'])) {
                        Offer::where('id', $calc['applied_offer_id'])->increment('used_count');
                    }
                }

                return $order->refresh();
            }

            if (empty($data['items']) || ! is_array($data['items'])) {
                throw ValidationException::withMessages([
                    'items' => 'Order items are required.',
                ]);
            }

            $normalizedItems = [];
            foreach ($data['items'] as $item) {
                if (empty($item['product_id']) || (float) ($item['quantity'] ?? 0) <= 0) {
                    continue;
                }

                $normalizedItems[] = [
                    'product_id' => (int) $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                    'quantity' => (float) $item['quantity'],
                    'unit_price' => (float) ($item['unit_price'] ?? 0),
                    'tax_rate' => (float) ($item['tax_rate'] ?? 0),
                    'discount_amount' => (float) ($item['discount_amount'] ?? 0),
                    'tax_amount' => (float) ($item['tax_amount'] ?? 0),
                    'total_amount' => (float) ($item['total_amount'] ?? 0),
                ];
            }

            if (empty($normalizedItems)) {
                throw ValidationException::withMessages([
                    'items' => 'Order items are required.',
                ]);
            }

            $subtotal = array_reduce($normalizedItems, fn ($carry, $item) => $carry + (float) $item['total_amount'], 0.0);
            $taxAmount = (float) ($data['tax_amount'] ?? array_reduce($normalizedItems, fn ($carry, $item) => $carry + (float) $item['tax_amount'], 0.0));
            $discountAmount = (float) ($data['discount_amount'] ?? array_reduce($normalizedItems, fn ($carry, $item) => $carry + (float) $item['discount_amount'], 0.0));
            $netAmount = (float) ($data['net_amount'] ?? max(0, $subtotal - $discountAmount + $taxAmount));
            $newCouponCode = $data['coupon_code'] ?? null;
            $newOfferId = $data['applied_offer_id'] ?? null;

            if ($order->status === 'confirmed' && $order->type === 'sale' && $order->warehouse_id) {
                foreach ($order->items as $item) {
                    $this->inventoryService->releaseReservedStock(
                        (int) $item->product_id,
                        (int) $order->warehouse_id,
                        (float) $item->quantity,
                        $order->id,
                        'cancelled'
                    );
                }
            }

            $orderPayload = array_merge([
                'warehouse_id' => $data['warehouse_id'] ?? $order->warehouse_id,
                'total_amount' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'coupon_code' => $newCouponCode,
                'applied_offer_id' => $newOfferId,
                'net_amount' => $netAmount,
                'is_draft' => isset($data['is_draft']) ? (bool) $data['is_draft'] : $order->is_draft,
                'future_order_date' => array_key_exists('future_order_date', $data) ? $data['future_order_date'] : $order->future_order_date,
                'updated_by' => auth()->id(),
            ], $this->mapAddressFields($shippingAddr, 'shipping'), $this->mapAddressFields($billingAddr, 'billing'));

            $order->update($orderPayload);

            $order->items()->delete();
            foreach ($normalizedItems as $item) {
                $order->items()->create($item);
            }

            if ($order->status === 'confirmed' && $order->type === 'sale' && $order->warehouse_id) {
                $order->load('items');
                foreach ($order->items as $item) {
                    $this->inventoryService->reserveStock(
                        (int) $item->product_id,
                        (int) $order->warehouse_id,
                        (float) $item->quantity,
                        $order->id
                    );
                }
            }

            if ($newCouponCode !== $lastCouponCode) {
                if ($lastCouponCode) {
                    Coupon::where('code', $lastCouponCode)->where('used_count', '>', 0)->decrement('used_count');
                }
                if ($newCouponCode) {
                    Coupon::where('code', $newCouponCode)->increment('used_count');
                }
            }

            if ($newOfferId !== $lastOfferId) {
                if ($lastOfferId) {
                    Offer::where('id', $lastOfferId)->decrement('used_count');
                }
                if ($newOfferId) {
                    Offer::where('id', $newOfferId)->increment('used_count');
                }
            }

            return $order->refresh();
        }, 3);
    }

    public function updateStatus(Order $order, string $status): Order
    {
        $allowedStatuses = ['processing', 'delivered'];

        if (! in_array($status, $allowedStatuses)) {
            throw ValidationException::withMessages([
                'status' => "Status '{$status}' cannot be updated via this method.",
            ]);
        }

        $order->update([
            'status' => $status,
            'updated_by' => auth()->id(),
        ]);

        if ($status === 'processing') {
            $order->loadMissing(['shipments', 'shippingAddress.village.services']);
            if ($order->shipments->isEmpty()) {
                $carrierName = null;
                if ($order->shippingAddress && $order->shippingAddress->village) {
                    $service = $order->shippingAddress->village->services
                        ->where('is_active', true)
                        ->where('pivot.is_available', true)
                        ->sortBy('pivot.priority')
                        ->first();

                    if ($service) {
                        $carrierName = $service->name;
                    }
                }

                Shipment::create([
                    'shipment_no' => Shipment::generateShipmentNo($order),
                    'order_id' => $order->id,
                    'status' => 'pending',
                    'carrier_name' => $carrierName,
                ]);
            }
        }

        if ($status === 'delivered') {
            foreach ($order->shipments as $shipment) {
                if ($shipment->status !== 'delivered') {
                    $shipment->update([
                        'status' => 'delivered',
                        'delivered_at' => now(),
                    ]);
                }
            }
        }
        // Dispatch Notification (fail-safe: never break core flow)
        try {
            $admins = \App\Modules\Users\Models\User::role(['Admin', 'Super Admin', 'Support'])->get();
            Notification::send($admins, new OrderStatusChangedNotification($order->order_no, $status));
        } catch (\Throwable) {
            // Silently fail — notification delivery is non-critical
        }

        return $order->refresh();
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Build the items array from the decoded cart JSON.
     */
    private function buildItemsFromCart(array $cart): array
    {
        $items = [];

        foreach ($cart as $item) {
            if (empty($item['id']) || ! isset($item['quantity']) || (float) $item['quantity'] <= 0 || ! isset($item['price'])) {
                continue;
            }

            $itemBase = (float) $item['price'] * (float) $item['quantity'];
            $itemDisc = $this->calculateLineDiscount(
                (float) $item['price'],
                (float) $item['quantity'],
                (float) ($item['discountValue'] ?? 0),
                $item['discountType'] ?? null
            );

            $items[] = [
                'product_id' => $item['id'],
                'quantity' => (float) $item['quantity'],
                'unit_price' => (float) $item['price'],
                'tax_rate' => (float) ($item['taxRate'] ?? 0),
                'discount_amount' => $itemDisc,
                'tax_amount' => (float) ($item['tax_amount'] ?? 0),
                'total_amount' => $itemBase - $itemDisc,
            ];
        }

        return $items;
    }

    /**
     * Format a PartyAddress model to a single-line string.
     */
    protected function formatAddress(?PartyAddress $address): ?string
    {
        if (! $address) {
            return null;
        }

        $parts = array_filter([
            $address->label,
            $address->address_line_1,
            $address->address_line_2,
            $address->village?->village_name,
            $address->village?->district_name,
            $address->village?->state_name,
            $address->village?->pincode,
        ]);

        return implode(', ', $parts) ?: null;
    }

    /**
     * Map a PartyAddress model to a flat array for the order table.
     */
    protected function mapAddressFields(?PartyAddress $address, string $prefix): array
    {
        if (! $address) {
            return [];
        }

        $address->loadMissing('village');

        return [
            "{$prefix}_address_id" => $address->id,
            "{$prefix}_address_line_1" => $address->address_line_1,
            "{$prefix}_address_line_2" => $address->address_line_2,
            "{$prefix}_village_id" => $address->village_id,
            "{$prefix}_village_name" => $address->village_name ?? $address->village?->village_name,
            "{$prefix}_post_office" => $address->post_office ?? $address->village?->post_so_name,
            "{$prefix}_taluka" => $address->taluka ?? $address->village?->taluka_name,
            "{$prefix}_district" => $address->district ?? $address->village?->district_name,
            "{$prefix}_city" => $address->city,
            "{$prefix}_state" => $address->state ?? $address->village?->state_name,
            "{$prefix}_pincode" => $address->pincode ?? $address->village?->pincode,
        ];
    }

    /**
     * Fetch a full order for receipt printing.
     */
    public function getOrderForReceipt(int $orderId): Order
    {
        return Order::with(['party', 'items.product', 'warehouse', 'appliedOffer'])
            ->findOrFail($orderId);
    }
}
