<?php

declare(strict_types=1);

namespace App\Modules\Orders\Controllers;

use App\Modules\Core\Controllers\Controller;
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
        $data = $request->validate([
            'code' => 'required|string|max:50',
            'type' => 'sometimes|in:sale,purchase',
            'party_id' => 'nullable|integer|exists:parties,id',
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'selected_offer_id' => 'nullable|integer|exists:offers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|numeric|gt:0',
            'items.*.is_gift' => 'sometimes|boolean',
            'items.*.gift_source' => 'nullable|string',
        ]);
        // Existing cart gifts belong to the previous selection; derive the new benefits from purchased lines.
        $data['items'] = array_values(array_filter($data['items'], fn ($item) => empty($item['is_gift'])));
        try {
            $quote = \Illuminate\Support\Facades\DB::transaction(fn () => app(\App\Services\OrderService::class)
                ->recalculateAndValidate(array_replace($data, ['coupon_code' => $data['code'], 'selected_offer_id' => $data['selected_offer_id'] ?? null])));
        } catch (\Illuminate\Validation\ValidationException $error) {
            return response()->json(['valid' => false, 'message' => collect($error->errors())->flatten()->first()]);
        }
        $coupon = Coupon::where('code', $quote['coupon_code'])->firstOrFail();
        return response()->json([
            'valid' => true,
            'discount' => $quote['coupon_discount'],
            'coupon' => $coupon->only(['id', 'code', 'type', 'value', 'min_spend', 'max_discount', 'applicable_products', 'excluded_products', 'applicable_categories', 'excluded_categories', 'cashback_percent', 'cashback_fixed', 'free_product_id', 'free_qty', 'expiry_date', 'usage_limit', 'used_count', 'recipient_party_id']),
            'message' => 'Promo code applied!',
        ]);
    }
}
