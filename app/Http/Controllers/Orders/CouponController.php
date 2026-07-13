<?php

declare(strict_types=1);

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
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
            'code'     => 'required|string',
            'subtotal' => 'required|numeric|min:0',
        ]);

        $code    = strtoupper(trim($request->code));
        $subtotal = (float) $request->subtotal;

        $coupon = Coupon::where('code', $code)->first();

        if (!$coupon) {
            return response()->json(['valid' => false, 'message' => 'Invalid promo code.']);
        }

        if (!$coupon->is_active) {
            return response()->json(['valid' => false, 'message' => 'This promo code is inactive.']);
        }

        if ($coupon->expiry_date && $coupon->expiry_date < now()->startOfDay()) {
            return response()->json(['valid' => false, 'message' => 'This promo code has expired.']);
        }

        if ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) {
            return response()->json(['valid' => false, 'message' => 'This promo code usage limit has been reached.']);
        }

        if ((float) $coupon->min_spend > 0 && $subtotal < (float) $coupon->min_spend) {
            return response()->json([
                'valid'   => false,
                'message' => 'Minimum spend of ₹' . number_format((float) $coupon->min_spend, 2) . ' required.',
            ]);
        }

        if ($coupon->type === 'percentage') {
            $discount = $subtotal * ($coupon->value / 100);
            if ((float) $coupon->max_discount > 0 && $discount > (float) $coupon->max_discount) {
                $discount = (float) $coupon->max_discount;
            }
        } else {
            $discount = (float) $coupon->value;
        }

        $discount = min($discount, $subtotal);

        return response()->json([
            'valid'    => true,
            'discount' => round($discount, 2),
            'message'  => 'Promo code applied! You save ₹' . number_format($discount, 2) . '.',
        ]);
    }
}
