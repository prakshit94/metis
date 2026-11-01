<?php

namespace App\Storefront\Services;

use App\Modules\Catalog\Models\Warehouse;
use App\Modules\Customers\Models\Customer;
use App\Modules\Inventory\Models\Stock;
use App\Services\InventoryService;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Storefront\Models\CustomerAccount;
use App\Storefront\Models\Product;

final class Checkout
{
    public function place(Request $request, CustomerAccount $account): int
    {
        $data = $request->validate(['checkout_key' => 'required|uuid', 'delivery_address_choice' => 'required|integer|min:1', 'billing_same_as_shipping' => 'sometimes|boolean', 'billing_address_choice' => 'nullable|integer|min:1', 'name' => 'required|string|max:150', 'phone' => 'required|regex:/^[0-9+ ()-]{10,20}$/']);

        return DB::transaction(function () use ($request, $account, $data) {
            $customer = Customer::whereKey($account->customer_id)->lockForUpdate()->firstOrFail();
            $locked = CustomerAccount::storefrontEligible()->whereKey($account->id)->where('ordering_enabled', true)->lockForUpdate()->first();
            abort_unless($locked, 403, 'Ordering is unavailable for this account.');
            $previous = DB::table('storefront_checkouts')->where('request_key', $data['checkout_key'])->where('customer_account_id', $account->id)->first();
            if ($previous) {
                return (int) $previous->order_id;
            }
            abort_unless(is_string($request->session()->get('shop.checkout_key')) && hash_equals($request->session()->get('shop.checkout_key'), $data['checkout_key']), 419);
            $shipping = $customer->addresses()->where('status', 'active')->whereKey($data['delivery_address_choice'])->lockForUpdate()->first();
            $billing = $request->boolean('billing_same_as_shipping', true) ? $shipping : $customer->addresses()->where('status', 'active')->whereKey($data['billing_address_choice'] ?? 0)->lockForUpdate()->first();
            foreach ([$shipping, $billing] as $address) {
                if (! $address || ! $address->address_line_1 || ! $address->state || ! preg_match('/^[0-9]{6}$/', $address->pincode ?? '')) {
                    throw ValidationException::withMessages(['address' => 'Select complete shipping and billing addresses belonging to your account.']);
                }
            }
            $cart = $request->session()->get('shop.cart', []);
            if (! is_array($cart) || ! count($cart) || count($cart) > 50) {
                throw ValidationException::withMessages(['cart' => 'Your cart is empty or invalid.']);
            }
            $products = Product::with('taxRate')->whereIn('id', array_keys($cart))->storefrontAvailable()->orderBy('id')->lockForUpdate()->get();
            if ($products->count() !== count($cart)) {
                throw ValidationException::withMessages(['cart' => 'A product is no longer available. Update your cart.']);
            }
            foreach ($products as $product) {
                $qty = $cart[$product->id];
                // HTML form quantities are strings, including those in older saved carts.
                if (is_string($qty) && ctype_digit($qty) && strlen($qty) <= 4) {
                    $qty = (int) $qty;
                }
                if (! is_int($qty) || $qty < max(1, $product->min_order_qty) || $qty > min(1000, $product->max_order_qty ?? 1000) || $product->selling_price < 0) {
                    throw ValidationException::withMessages(['cart' => 'Product price or quantity limits changed. Update your cart.']);
                }
                $cart[$product->id] = $qty;
            }
            // A single existing warehouse fulfils the order; inventory remains reserved by
            // the existing confirmation workflow, never by a duplicate storefront engine.
            $warehouse = null;
            $warehouses = Warehouse::where('status', 'active')->where('is_active', true)->orderByDesc('is_default')->orderBy('id')->get();
            $stocks = Stock::whereIn('warehouse_id', $warehouses->pluck('id'))->whereIn('product_id', $products->pluck('id'))->get()->groupBy('warehouse_id');
            foreach ($warehouses as $candidate) {
                $candidateStocks = ($stocks[$candidate->id] ?? collect())->keyBy('product_id');
                if ($products->contains(function ($product) use ($candidateStocks, $cart) {
                    $stock = $candidateStocks[$product->id] ?? null;

                    return ! $stock || $stock->is_sku_enabled === false || max(0, (float) $stock->quantity - (float) $stock->reserved_qty) < $cart[$product->id];
                })) {
                    continue;
                }
                $available = true;
                foreach ($products as $product) {
                    $stock = Stock::where('product_id', $product->id)->where('warehouse_id', $candidate->id)->lockForUpdate()->first();
                    if (($stock && $stock->is_sku_enabled === false) || app(InventoryService::class)->getAvailableQty($product->id, $candidate->id) < $cart[$product->id]) {
                        $available = false;
                        break;
                    }
                }
                if ($available) {
                    $warehouse = $candidate;
                    break;
                }
            }
            if (! $warehouse) {
                throw ValidationException::withMessages(['cart' => 'No active warehouse currently has enough available stock for your complete bag. Update quantities or contact support.']);
            }
            $service = app(OrderService::class);
            $input = ['type' => 'sale', 'party_id' => $customer->id, 'warehouse_id' => $warehouse->id, 'shipping_address_id' => $shipping->id, 'billing_address_id' => $billing->id, 'items' => collect($cart)->map(fn ($quantity, $id) => ['product_id' => $id, 'quantity' => $quantity])->values()->all()];
            $calc = $service->recalculateAndValidate($input);
            if ($calc['grand_total'] > 9999999999999) {
                throw ValidationException::withMessages(['cart' => 'The order total exceeds the supported range.']);
            }
            // A refreshed quote must be accepted if prices, taxes or discounts changed.
            $quote = hash('sha256', json_encode([$calc['items'], $calc['grand_total']]));
            if (! hash_equals((string) $request->session()->get('shop.quote'), $quote)) {
                throw ValidationException::withMessages(['cart' => 'Prices or availability changed. Review your bag and place the order again.']);
            }
            $order = $service->createOrder(array_merge($input, ['items' => $calc['items'], 'total_amount' => $calc['subtotal'], 'tax_amount' => $calc['tax_amount'], 'discount_amount' => $calc['total_discount'], 'net_amount' => $calc['grand_total'], 'applied_bogo_ids' => $calc['applied_bogo_ids'], 'applied_offer_id' => $calc['applied_offer_id'] ?? null, 'selected_offer_id' => $calc['selected_offer_id'] ?? null, 'cashback_earned' => $calc['cashback_earned'] ?? 0, 'status' => 'pending']));
            DB::table('storefront_checkouts')->insert(['request_key' => $data['checkout_key'], 'customer_account_id' => $account->id, 'order_id' => $order->id, 'contact_name' => $data['name'], 'contact_phone' => $data['phone'], 'created_at' => now(), 'updated_at' => now()]);

            return $order->id;
        }, 3);
    }

    /** Present the existing engine's amounts; do not recalculate its pricing rules. */
    public function summary(?array $quote): array
    {
        $subtotal=(int)round(($quote['subtotal'] ?? 0)*100);
        $tax=(int)round(($quote['tax_amount'] ?? 0)*100);
        $discount=(int)round(($quote['total_discount'] ?? 0)*100);
        $total=(int)round(($quote['grand_total'] ?? 0)*100);
        return ['subtotal'=>$subtotal,'tax'=>$tax,'discount'=>$discount,'rounding'=>$total-$subtotal+$discount-$tax];
    }

    public function quote(array $cart): ?array
    {
        if (! $cart) {
            return null;
        }

        return app(OrderService::class)->recalculateAndValidate(['type' => 'sale', 'items' => collect($cart)->map(fn ($q, $id) => ['product_id' => $id, 'quantity' => $q])->values()->all()]);
    }
}
