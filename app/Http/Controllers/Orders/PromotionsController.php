<?php

declare(strict_types=1);

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Offer;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromotionsController extends Controller
{
    // ─── Page views ──────────────────────────────────────────────────────────

    public function coupons()
    {
        return view('promotions.coupons');
    }

    public function offers()
    {
        $products = \App\Models\Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku']);
        return view('promotions.offers', compact('products'));
    }

    // ─── Coupons JSON API ─────────────────────────────────────────────────────

    public function couponsIndex(Request $request): JsonResponse
    {
        $query = Coupon::query()->latest();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where('code', 'like', "%$s%");
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage  = min((int) $request->input('per_page', 15), 100);
        $coupons  = $query->paginate($perPage);

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
            'code'        => 'required|string|max:50|unique:coupons,code',
            'type'        => 'required|in:percentage,fixed',
            'value'       => 'required|numeric|min:0',
            'min_spend'   => 'nullable|numeric|min:0',
            'max_discount'=> 'nullable|numeric|min:0',
            'expiry_date' => 'nullable|date',
            'usage_limit' => 'nullable|integer|min:0',
            'is_active'   => 'boolean',
        ]);

        $data['code']    = strtoupper(trim($data['code']));
        $data['status']  = ($data['is_active'] ?? true) ? 'active' : 'inactive';
        $coupon          = Coupon::create($data);

        return response()->json(['message' => 'Coupon created.', 'data' => $coupon], 201);
    }

    public function couponsUpdate(Request $request, Coupon $coupon): JsonResponse
    {
        if ($request->input('type') === 'flat') {
            $request->merge(['type' => 'fixed']);
        }

        $data = $request->validate([
            'code'        => 'sometimes|required|string|max:50|unique:coupons,code,' . $coupon->id,
            'type'        => 'sometimes|required|in:percentage,fixed',
            'value'       => 'sometimes|required|numeric|min:0',
            'min_spend'   => 'nullable|numeric|min:0',
            'max_discount'=> 'nullable|numeric|min:0',
            'expiry_date' => 'nullable|date',
            'usage_limit' => 'nullable|integer|min:0',
            'is_active'   => 'boolean',
        ]);

        if (isset($data['code'])) {
            $data['code'] = strtoupper(trim($data['code']));
        }
        if (isset($data['is_active'])) {
            $data['status'] = $data['is_active'] ? 'active' : 'inactive';
        }

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
            'is_active' => !$coupon->is_active,
            'status'    => $coupon->is_active ? 'inactive' : 'active',
        ]);
        return response()->json(['message' => 'Coupon status toggled.', 'data' => $coupon->fresh()]);
    }

    public function couponsBulk(Request $request): JsonResponse
    {
        $data = $request->validate([
            'action' => 'required|in:delete,activate,deactivate',
            'ids'    => 'required|array|min:1',
            'ids.*'  => 'integer|exists:coupons,id',
        ]);

        $ids    = $data['ids'];
        $action = $data['action'];

        if ($action === 'delete') {
            Coupon::whereIn('id', $ids)->delete();
        } elseif ($action === 'activate') {
            Coupon::whereIn('id', $ids)->update(['is_active' => true, 'status' => 'active']);
        } elseif ($action === 'deactivate') {
            Coupon::whereIn('id', $ids)->update(['is_active' => false, 'status' => 'inactive']);
        }

        return response()->json(['message' => count($ids) . ' coupon(s) ' . $action . 'd successfully.']);
    }

    // ─── Offers JSON API ──────────────────────────────────────────────────────

    public function offersIndex(Request $request): JsonResponse
    {
        $query = Offer::with('product')->latest();

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
        $offers  = $query->paginate($perPage);

        return response()->json(['data' => $offers]);
    }

    public function offersStore(Request $request): JsonResponse
    {
        if ($request->input('discount_type') === 'flat') {
            $request->merge(['discount_type' => 'fixed']);
        }

        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'type'          => 'required|in:order_discount,bogo',
            'discount_type' => 'required_if:type,order_discount|nullable|in:percentage,fixed',
            'value'         => 'required_if:type,order_discount|nullable|numeric|min:0',
            'min_spend'     => 'nullable|numeric|min:0',
            'max_discount'  => 'nullable|numeric|min:0',
            'product_ids'   => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
            'buy_qty'       => 'required_if:type,bogo|nullable|integer|min:1',
            'get_qty'       => 'required_if:type,bogo|nullable|integer|min:1',
            'starts_at'     => 'nullable|date',
            'ends_at'       => 'nullable|date|after_or_equal:starts_at',
            'priority'      => 'nullable|integer|min:0',
            'is_active'     => 'boolean',
        ]);

        $productIds = $request->input('product_ids', []);
        
        if (empty($productIds)) {
            // Create a single global offer
            $offerData = $data;
            unset($offerData['product_ids']);
            $offerData['product_id'] = null;
            $offer = Offer::create($offerData);
            return response()->json(['message' => 'Global offer created.', 'data' => $offer->load('product')], 201);
        }

        // Bulk create offers for each selected product
        $createdOffers = collect();
        foreach ($productIds as $pId) {
            $offerData = $data;
            unset($offerData['product_ids']);
            $offerData['product_id'] = $pId;
            $createdOffers->push(Offer::create($offerData)->load('product'));
        }

        return response()->json(['message' => 'Offers created for selected products.', 'data' => $createdOffers->first()], 201);
    }

    public function offersUpdate(Request $request, Offer $offer): JsonResponse
    {
        if ($request->input('discount_type') === 'flat') {
            $request->merge(['discount_type' => 'fixed']);
        }

        $data = $request->validate([
            'name'          => 'sometimes|required|string|max:255',
            'type'          => 'sometimes|required|in:order_discount,bogo',
            'discount_type' => 'required_if:type,order_discount|nullable|in:percentage,fixed',
            'value'         => 'required_if:type,order_discount|nullable|numeric|min:0',
            'min_spend'     => 'nullable|numeric|min:0',
            'max_discount'  => 'nullable|numeric|min:0',
            'product_id'    => 'nullable|exists:products,id',
            'buy_qty'       => 'required_if:type,bogo|nullable|integer|min:1',
            'get_qty'       => 'required_if:type,bogo|nullable|integer|min:1',
            'starts_at'     => 'nullable|date',
            'ends_at'       => 'nullable|date',
            'priority'      => 'nullable|integer|min:0',
            'is_active'     => 'boolean',
        ]);

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
        $offer->update(['is_active' => !$offer->is_active]);
        return response()->json(['message' => 'Offer status toggled.', 'data' => $offer->fresh()]);
    }

    public function offersBulk(Request $request): JsonResponse
    {
        $data = $request->validate([
            'action' => 'required|in:delete,activate,deactivate',
            'ids'    => 'required|array|min:1',
            'ids.*'  => 'integer|exists:offers,id',
        ]);

        $ids    = $data['ids'];
        $action = $data['action'];

        if ($action === 'delete') {
            Offer::whereIn('id', $ids)->delete();
        } elseif ($action === 'activate') {
            Offer::whereIn('id', $ids)->update(['is_active' => true]);
        } elseif ($action === 'deactivate') {
            Offer::whereIn('id', $ids)->update(['is_active' => false]);
        }

        return response()->json(['message' => count($ids) . ' offer(s) ' . $action . 'd successfully.']);
    }
}
