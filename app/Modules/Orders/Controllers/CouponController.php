<?php

declare(strict_types=1);

namespace App\Modules\Orders\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    /**
     * Validate a coupon code against a given subtotal.
     * Used by the Alpine.js order creation workflow.
     */
    public function validateApi(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
            'subtotal' => 'required|numeric|min:0',
            'items' => 'sometimes|array',
            'items.*.product_id' => 'required_with:items|integer|exists:products,id',
            'items.*.quantity' => 'required_with:items|numeric|min:0.01',
            'items.*.is_gift' => 'sometimes|boolean',
        ]);

        $code = strtoupper(trim($request->code));
        $subtotal = (float) $request->subtotal;

        $coupon = Coupon::where('code', $code)->first();

        if (! $coupon) {
            return response()->json(['valid' => false, 'message' => 'Invalid promo code.']);
        }

        if (! $coupon->is_active) {
            return response()->json(['valid' => false, 'message' => 'This promo code is inactive.']);
        }

        if ($coupon->expiry_date && $coupon->expiry_date < now()->startOfDay()) {
            return response()->json(['valid' => false, 'message' => 'This promo code has expired.']);
        }

        if ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) {
            return response()->json(['valid' => false, 'message' => 'This promo code usage limit has been reached.']);
        }

        $eligibleSubtotal = $subtotal;
        $cartSubtotal = $subtotal;
        if ($request->has('items')) {
            $items = $request->input('items', []);
            $productIds = collect($items)->pluck('product_id')->unique()->values();
            $products = Product::whereIn('id', $productIds)
                ->get(['id', 'category_id', 'selling_price', 'default_discount', 'default_discount_type'])
                ->keyBy('id');
            $applicableProducts = array_map('strval', $coupon->applicable_products ?? []);
            $excludedProducts = array_map('strval', $coupon->excluded_products ?? []);
            $applicableCategories = array_map('strval', $coupon->applicable_categories ?? []);
            $excludedCategories = array_map('strval', $coupon->excluded_categories ?? []);
            $eligibleSubtotal = 0.0;
            $cartSubtotal = 0.0;

            foreach ($items as $item) {
                if ((bool) ($item['is_gift'] ?? false)) {
                    continue;
                }

                $product = $products->get($item['product_id']);
                if (! $product) {
                    continue;
                }

                $quantity = (float) $item['quantity'];
                $lineSubtotal = (float) $product->selling_price * $quantity;
                $discountValue = (float) ($product->default_discount ?? 0);
                $discountType = strtolower((string) ($product->default_discount_type ?? 'percent'));
                $lineDiscount = in_array($discountType, ['percent', 'percentage'], true)
                    ? min($lineSubtotal * ($discountValue / 100), $lineSubtotal)
                    : min($discountValue * $quantity, $lineSubtotal);
                $lineSubtotal = max(0, $lineSubtotal - $lineDiscount);
                $cartSubtotal += $lineSubtotal;

                $productId = (string) $product->id;
                $categoryId = $product->category_id === null ? null : (string) $product->category_id;
                $isEligible = ($applicableProducts === [] || in_array($productId, $applicableProducts, true))
                    && ! in_array($productId, $excludedProducts, true)
                    && ($applicableCategories === [] || ($categoryId !== null && in_array($categoryId, $applicableCategories, true)))
                    && ($categoryId === null || ! in_array($categoryId, $excludedCategories, true));

                if ($isEligible) {
                    $eligibleSubtotal += $lineSubtotal;
                }
            }

            if ($eligibleSubtotal <= 0) {
                return response()->json([
                    'valid' => false,
                    'message' => 'This promo code does not apply to any products in the order.',
                ]);
            }
        }

        if ((float) $coupon->min_spend > 0 && $cartSubtotal < (float) $coupon->min_spend) {
            return response()->json([
                'valid' => false,
                'message' => 'Minimum spend of ₹'.number_format((float) $coupon->min_spend, 2).' required.',
            ]);
        }

        $discount = 0.0;
        if ($coupon->type === 'percentage') {
            $discount = $eligibleSubtotal * ((float) $coupon->value / 100);
            if ((float) $coupon->max_discount > 0 && $discount > (float) $coupon->max_discount) {
                $discount = (float) $coupon->max_discount;
            }
        } else {
            $discount = (float) $coupon->value;
        }
        $discount = min($discount, $eligibleSubtotal);

        return response()->json([
            'valid' => true,
            'discount' => (float) $discount,
            'eligible_subtotal' => (float) $eligibleSubtotal,
            'coupon' => [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'type' => $coupon->type,
                'value' => (float) $coupon->value,
                'min_spend' => (float) $coupon->min_spend,
                'max_discount' => $coupon->max_discount ? (float) $coupon->max_discount : null,
                'applicable_products' => $coupon->applicable_products,
                'excluded_products' => $coupon->excluded_products,
                'applicable_categories' => $coupon->applicable_categories,
                'excluded_categories' => $coupon->excluded_categories,
                'cashback_percent' => (float) ($coupon->cashback_percent ?? 0),
                'cashback_fixed' => (float) ($coupon->cashback_fixed ?? 0),
                'free_product_id' => $coupon->free_product_id,
                'free_qty' => $coupon->free_qty,
                'expiry_date' => $coupon->expiry_date?->toDateString(),
                'usage_limit' => $coupon->usage_limit,
                'used_count' => $coupon->used_count,
            ],
            'message' => 'Promo code applied!',
        ]);
    }
}
