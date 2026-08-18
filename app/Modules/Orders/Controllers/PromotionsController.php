<?php

declare(strict_types=1);

namespace App\Modules\Orders\Controllers;

use App\Modules\Catalog\Models\Product;
use App\Modules\Core\Controllers\Controller;
use App\Modules\Orders\Models\Coupon;
use App\Modules\Orders\Models\Offer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class PromotionsController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:coupon-view', only: ['coupons', 'couponsIndex']),
            new Middleware('permission:coupon-create', only: ['couponsStore']),
            new Middleware('permission:coupon-edit', only: ['couponsUpdate', 'couponsToggle', 'couponsBulk']),
            new Middleware('permission:coupon-delete', only: ['couponsDestroy', 'couponsBulk']),
            new Middleware('permission:promotions-view', only: ['offers', 'offersIndex']),
            new Middleware('permission:promotions-create', only: ['offersStore']),
            new Middleware('permission:promotions-edit', only: ['offersUpdate', 'offersToggle', 'offersBulk']),
            new Middleware('permission:promotions-delete', only: ['offersDestroy', 'offersBulk']),
        ];
    }

    // ─── Page views ──────────────────────────────────────────────────────────

    public function coupons()
    {
        $products = Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku']);

        return view('promotions.coupons', compact('products'));
    }

    public function offers()
    {
        $products = Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku']);

        return view('promotions.offers', compact('products'));
    }

    // ─── Coupons JSON API ─────────────────────────────────────────────────────

    public function couponsIndex(Request $request): JsonResponse
    {
        $query = Coupon::with(['creator', 'updater'])->latest();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where('code', 'like', "%$s%");
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $coupons = $query->paginate($perPage);

        $now = now();
        $stats = [
            'total' => Coupon::count(),
            'active' => Coupon::where('is_active', true)->count(),
            'inactive' => Coupon::where('is_active', false)->count(),
            'expiring_soon' => Coupon::where('is_active', true)
                ->whereNotNull('expiry_date')
                ->where('expiry_date', '<=', $now->copy()->addDays(7))
                ->where('expiry_date', '>', $now)
                ->count(),
        ];

        return response()->json([
            'data' => $coupons,
            'stats' => $stats,
        ]);
    }

    public function couponsStore(Request $request): JsonResponse
    {
        if ($request->input('type') === 'flat') {
            $request->merge(['type' => 'fixed']);
        }

        $data = $request->validate([
            'code' => 'required|string|max:50|unique:coupons,code',
            'type' => 'required|in:percentage,fixed,free_shipping,free_product',
            'value' => 'required_if:type,percentage,fixed|numeric|min:0',
            'min_spend' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'applicable_categories' => 'nullable|array',
            'applicable_products' => 'nullable|array',
            'free_product_id' => 'required_if:type,free_product|nullable|exists:products,id',
            'free_qty' => 'required_if:type,free_product|nullable|integer|min:1',
            'expiry_date' => 'nullable|date',
            'usage_limit' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'cashback_percent' => 'nullable|numeric|min:0|max:100',
            'cashback_fixed' => 'nullable|numeric|min:0',
        ]);

        if (isset($data['applicable_categories'])) {
            $data['applicable_categories'] = json_encode($data['applicable_categories']);
        }
        if (isset($data['applicable_products'])) {
            $data['applicable_products'] = json_encode($data['applicable_products']);
        }

        $data['value'] = isset($data['value']) && $data['value'] !== '' ? (float) $data['value'] : 0;
        $data['min_spend'] = isset($data['min_spend']) && $data['min_spend'] !== '' ? (float) $data['min_spend'] : 0;
        $data['max_discount'] = isset($data['max_discount']) && $data['max_discount'] !== '' ? (float) $data['max_discount'] : null;
        $data['cashback_percent'] = isset($data['cashback_percent']) && $data['cashback_percent'] !== '' ? (float) $data['cashback_percent'] : null;
        $data['cashback_fixed'] = isset($data['cashback_fixed']) && $data['cashback_fixed'] !== '' ? (float) $data['cashback_fixed'] : null;

        $data['code'] = strtoupper(trim($data['code']));
        $data['status'] = ($data['is_active'] ?? true) ? 'active' : 'inactive';
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();
        $coupon = Coupon::create($data);

        return response()->json(['message' => 'Coupon created.', 'data' => $coupon], 201);
    }

    public function couponsUpdate(Request $request, Coupon $coupon): JsonResponse
    {
        if ($request->input('type') === 'flat') {
            $request->merge(['type' => 'fixed']);
        }

        $data = $request->validate([
            'code' => 'sometimes|required|string|max:50|unique:coupons,code,'.$coupon->id,
            'type' => 'sometimes|required|in:percentage,fixed,free_shipping,free_product',
            'value' => 'sometimes|numeric|min:0',
            'min_spend' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'applicable_categories' => 'nullable|array',
            'applicable_products' => 'nullable|array',
            'free_product_id' => 'nullable|exists:products,id',
            'free_qty' => 'nullable|integer|min:1',
            'expiry_date' => 'nullable|date',
            'usage_limit' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'cashback_percent' => 'nullable|numeric|min:0|max:100',
            'cashback_fixed' => 'nullable|numeric|min:0',
        ]);

        if (array_key_exists('applicable_categories', $data)) {
            $data['applicable_categories'] = $data['applicable_categories'] ? json_encode($data['applicable_categories']) : null;
        }
        if (array_key_exists('applicable_products', $data)) {
            $data['applicable_products'] = $data['applicable_products'] ? json_encode($data['applicable_products']) : null;
        }

        if (array_key_exists('value', $data) || in_array($request->input('type', $coupon->type), ['free_shipping', 'free_product'])) {
            $data['value'] = $data['value'] !== '' && $data['value'] !== null ? (float) $data['value'] : 0;
        }
        if (array_key_exists('min_spend', $data)) {
            $data['min_spend'] = $data['min_spend'] !== '' && $data['min_spend'] !== null ? (float) $data['min_spend'] : 0;
        }
        if (array_key_exists('max_discount', $data)) {
            $data['max_discount'] = $data['max_discount'] !== '' && $data['max_discount'] !== null ? (float) $data['max_discount'] : null;
        }
        if (array_key_exists('cashback_percent', $data)) {
            $data['cashback_percent'] = $data['cashback_percent'] !== '' && $data['cashback_percent'] !== null ? (float) $data['cashback_percent'] : null;
        }
        if (array_key_exists('cashback_fixed', $data)) {
            $data['cashback_fixed'] = $data['cashback_fixed'] !== '' && $data['cashback_fixed'] !== null ? (float) $data['cashback_fixed'] : null;
        }

        if (isset($data['code'])) {
            $data['code'] = strtoupper(trim($data['code']));
        }
        if (isset($data['is_active'])) {
            $data['status'] = $data['is_active'] ? 'active' : 'inactive';
        }
        $data['updated_by'] = auth()->id();

        $coupon->update($data);

        return response()->json(['message' => 'Coupon updated.', 'data' => $coupon->fresh()]);
    }

    public function couponsDestroy(Coupon $coupon): JsonResponse
    {
        $coupon->delete();

        return response()->json(['message' => 'Coupon deleted.']);
    }

    public function couponsToggle(Coupon $coupon): JsonResponse
    {
        $coupon->update([
            'is_active' => ! $coupon->is_active,
            'status' => $coupon->is_active ? 'inactive' : 'active',
        ]);

        return response()->json(['message' => 'Coupon status toggled.', 'data' => $coupon->fresh()]);
    }

    public function couponsBulk(Request $request): JsonResponse
    {
        $data = $request->validate([
            'action' => 'required|in:delete,activate,deactivate',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:coupons,id',
        ]);

        $ids = $data['ids'];
        $action = $data['action'];

        if ($action === 'delete') {
            Coupon::whereIn('id', $ids)->delete();
        } elseif ($action === 'activate') {
            Coupon::whereIn('id', $ids)->update(['is_active' => true, 'status' => 'active']);
        } elseif ($action === 'deactivate') {
            Coupon::whereIn('id', $ids)->update(['is_active' => false, 'status' => 'inactive']);
        }

        return response()->json(['message' => count($ids).' coupon(s) '.$action.'d successfully.']);
    }

    // ─── Offers JSON API ──────────────────────────────────────────────────────

    public function offersIndex(Request $request): JsonResponse
    {
        $query = Offer::with(['product', 'creator', 'updater'])->latest();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where('name', 'like', "%$s%");
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $offers = $query->paginate($perPage);

        return response()->json(['data' => $offers]);
    }

    public function offersStore(Request $request): JsonResponse
    {
        if ($request->input('discount_type') === 'flat') {
            $request->merge(['discount_type' => 'fixed']);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:order_discount,bogo,category_discount,free_product',
            'discount_type' => 'required_if:type,order_discount,category_discount|nullable|in:percentage,fixed',
            'value' => 'required_if:type,order_discount,category_discount|nullable|numeric|min:0',
            'min_spend' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'applicable_categories' => 'required_if:type,category_discount|nullable|array',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
            'product_id' => 'required_if:type,free_product|nullable|exists:products,id',
            'buy_qty' => 'required_if:type,bogo|nullable|integer|min:1',
            'get_qty' => 'required_if:type,bogo,free_product|nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'priority' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'cashback_percent' => 'nullable|numeric|min:0|max:100',
            'cashback_fixed' => 'nullable|numeric|min:0',
        ]);

        if (isset($data['applicable_categories'])) {
            $data['applicable_categories'] = json_encode($data['applicable_categories']);
        }
        
        $data['value'] = isset($data['value']) && $data['value'] !== '' ? (float) $data['value'] : 0;
        $data['min_spend'] = isset($data['min_spend']) && $data['min_spend'] !== '' ? (float) $data['min_spend'] : 0;
        $data['max_discount'] = isset($data['max_discount']) && $data['max_discount'] !== '' ? (float) $data['max_discount'] : null;
        $data['cashback_percent'] = isset($data['cashback_percent']) && $data['cashback_percent'] !== '' ? (float) $data['cashback_percent'] : null;
        $data['cashback_fixed'] = isset($data['cashback_fixed']) && $data['cashback_fixed'] !== '' ? (float) $data['cashback_fixed'] : null;
        
        $productIds = $request->input('product_ids', []);
        
        $offerData = $data;
        $offerData['applicable_products'] = empty($productIds) ? null : json_encode($productIds);
        
        if ($data['type'] !== 'free_product') {
            $offerData['product_id'] = null;
        }

        unset($offerData['product_ids']);
        $offerData['created_by'] = auth()->id();
        $offerData['updated_by'] = auth()->id();
        
        $offer = Offer::create($offerData);

        return response()->json(['message' => 'Offer created successfully.', 'data' => $offer->load('product')], 201);
    }

    public function offersUpdate(Request $request, Offer $offer): JsonResponse
    {
        if ($request->input('discount_type') === 'flat') {
            $request->merge(['discount_type' => 'fixed']);
        }

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|in:order_discount,bogo,category_discount,free_product',
            'discount_type' => 'required_if:type,order_discount,category_discount|nullable|in:percentage,fixed',
            'value' => 'required_if:type,order_discount,category_discount|nullable|numeric|min:0',
            'min_spend' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'applicable_categories' => 'nullable|array',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
            'product_id' => 'nullable|exists:products,id',
            'buy_qty' => 'required_if:type,bogo|nullable|integer|min:1',
            'get_qty' => 'required_if:type,bogo,free_product|nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date',
            'priority' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'cashback_percent' => 'nullable|numeric|min:0|max:100',
            'cashback_fixed' => 'nullable|numeric|min:0',
        ]);

        if (array_key_exists('applicable_categories', $data)) {
            $data['applicable_categories'] = $data['applicable_categories'] ? json_encode($data['applicable_categories']) : null;
        }
        
        if ($request->has('product_ids')) {
            $productIds = $request->input('product_ids', []);
            $data['applicable_products'] = empty($productIds) ? null : json_encode($productIds);
        }
        
        if (isset($data['type']) && $data['type'] !== 'free_product') {
            $data['product_id'] = null;
        }

        if (array_key_exists('value', $data) || in_array($request->input('type', $offer->type), ['bogo', 'free_product'])) {
            $data['value'] = isset($data['value']) && $data['value'] !== '' ? (float) $data['value'] : 0;
        }
        if (array_key_exists('min_spend', $data)) {
            $data['min_spend'] = isset($data['min_spend']) && $data['min_spend'] !== '' ? (float) $data['min_spend'] : 0;
        }
        if (array_key_exists('max_discount', $data)) {
            $data['max_discount'] = isset($data['max_discount']) && $data['max_discount'] !== '' ? (float) $data['max_discount'] : null;
        }
        if (array_key_exists('cashback_percent', $data)) {
            $data['cashback_percent'] = $data['cashback_percent'] !== '' && $data['cashback_percent'] !== null ? (float) $data['cashback_percent'] : null;
        }
        if (array_key_exists('cashback_fixed', $data)) {
            $data['cashback_fixed'] = $data['cashback_fixed'] !== '' && $data['cashback_fixed'] !== null ? (float) $data['cashback_fixed'] : null;
        }

        $data['updated_by'] = auth()->id();
        unset($data['product_ids']);
        $offer->update($data);

        return response()->json(['message' => 'Offer updated.', 'data' => $offer->fresh('product')]);
    }

    public function offersDestroy(Offer $offer): JsonResponse
    {
        $offer->delete();

        return response()->json(['message' => 'Offer deleted.']);
    }

    public function offersToggle(Offer $offer): JsonResponse
    {
        $offer->update(['is_active' => ! $offer->is_active]);

        return response()->json(['message' => 'Offer status toggled.', 'data' => $offer->fresh()]);
    }

    public function offersBulk(Request $request): JsonResponse
    {
        $data = $request->validate([
            'action' => 'required|in:delete,activate,deactivate',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:offers,id',
        ]);

        $ids = $data['ids'];
        $action = $data['action'];

        if ($action === 'delete') {
            Offer::whereIn('id', $ids)->delete();
        } elseif ($action === 'activate') {
            Offer::whereIn('id', $ids)->update(['is_active' => true]);
        } elseif ($action === 'deactivate') {
            Offer::whereIn('id', $ids)->update(['is_active' => false]);
        }

        return response()->json(['message' => count($ids).' offer(s) '.$action.'d successfully.']);
    }
}
