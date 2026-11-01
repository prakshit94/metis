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
use Illuminate\Support\Facades\Cache;

class PromotionsController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:coupon-view', only: ['coupons', 'couponsIndex']),
            new Middleware('permission:coupon-view|promotions-view', only: ['productOptions']),
            new Middleware('permission:coupon-create', only: ['couponsStore']),
            new Middleware('permission:coupon-edit', only: ['couponsUpdate', 'couponsToggle']),
            new Middleware('permission:coupon-delete', only: ['couponsDestroy']),
            new Middleware('permission:coupon-edit|coupon-delete', only: ['couponsBulk']),
            new Middleware('permission:promotions-view', only: ['offers', 'offersIndex']),
            new Middleware('permission:promotions-create', only: ['offersStore']),
            new Middleware('permission:promotions-edit', only: ['offersUpdate', 'offersToggle']),
            new Middleware('permission:promotions-delete', only: ['offersDestroy']),
            new Middleware('permission:promotions-edit|promotions-delete', only: ['offersBulk']),
        ];
    }

    // ─── Page views ──────────────────────────────────────────────────────────

    public function productOptions(Request $request): JsonResponse
    {
        $data = $request->validate([
            'search' => 'nullable|string|max:100',
            'ids' => 'sometimes|array|max:500',
            'ids.*' => 'integer|distinct',
        ]);
        $query = Product::where('is_active', true)->whereIn('status', ['active', 'published']);
        if (! empty($data['search'])) {
            $term = '%'.$data['search'].'%';
            $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('sku', 'like', $term));
        }
        $matches = $query->orderBy('name')->limit(50)->get(['id', 'name', 'sku']);
        // Existing targets remain labelled even if a product has since become inactive.
        $selected = Product::whereIn('id', $data['ids'] ?? [])->get(['id', 'name', 'sku']);
        return response()->json(['data' => $selected->merge($matches)->unique('id')->values()]);
    }

    public function coupons()
    {
        $products = Product::where('is_active', true)->whereIn('status', ['active', 'published'])->orderBy('name')->limit(50)->get(['id', 'name', 'sku']);
        $categories = \App\Modules\Catalog\Models\Category::orderBy('name')->get(['id', 'name']);

        return view('promotions.coupons', compact('products', 'categories'));
    }

    public function offers()
    {
        $products = Product::where('is_active', true)->whereIn('status', ['active', 'published'])->orderBy('name')->limit(50)->get(['id', 'name', 'sku']);
        $categories = \App\Modules\Catalog\Models\Category::orderBy('name')->get(['id', 'name']);

        return view('promotions.offers', compact('products', 'categories'));
    }

    // ─── Coupons JSON API ─────────────────────────────────────────────────────

    public function couponsIndex(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'type' => 'nullable|in:percentage,fixed,free_shipping,free_product',
            'status' => 'nullable|in:active,inactive,expired',
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $query = Coupon::with(['creator', 'updater', 'freeProduct'])->latest();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where('code', 'like', "%$s%");
        }
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (! empty($filters['status'])) {
            if ($filters['status'] === 'expired') {
                $query->whereNotNull('expiry_date')->where('expiry_date', '<', today()->toDateString());
            } else {
                $query->where('is_active', $filters['status'] === 'active');
            }
        }

        $perPage = (int) ($filters['per_page'] ?? 15);
        $coupons = $query->paginate($perPage);
        $coupons->getCollection()->transform(function (Coupon $coupon) {
            $coupon->setAttribute('is_expired', $coupon->expiry_date !== null && $coupon->expiry_date->lt(today()));

            return $coupon;
        });

        $today = today();
        $counts = Coupon::query()->selectRaw("COUNT(*) as total,
            COUNT(CASE WHEN is_active = 1 THEN 1 END) as active,
            COUNT(CASE WHEN is_active = 0 THEN 1 END) as inactive,
            COUNT(CASE WHEN expiry_date < ? THEN 1 END) as expired,
            COUNT(CASE WHEN is_active = 1 AND expiry_date >= ? AND expiry_date <= ? THEN 1 END) as expiring_soon",
            [$today->toDateString(), $today->toDateString(), $today->copy()->addDays(7)->toDateString()]
        )->first();
        $stats = [
            'total' => (int) $counts->total,
            'active' => (int) $counts->active,
            'inactive' => (int) $counts->inactive,
            'expired' => (int) $counts->expired,
            'expiring_soon' => (int) $counts->expiring_soon,
        ];

        return response()->json([
            'data' => $coupons,
            'stats' => $stats,
        ]);
    }

    public function couponsStore(Request $request): JsonResponse
    {
        $this->validatePromotion($request, true, null);
        if ($request->input('type') === 'flat') {
            $request->merge(['type' => 'fixed']);
        }

        $data = $request->validate([
            'code' => 'required|string|max:50|unique:coupons,code',
            'type' => 'required|in:percentage,fixed,free_shipping,free_product',
            'value' => 'required_if:type,percentage,fixed|nullable|numeric|min:0',
            'min_spend' => 'nullable|numeric|min:0|max:9999999999.99|decimal:0,2',
            'max_discount' => 'nullable|numeric|min:0|max:9999999999.99|decimal:0,2',
            'applicable_categories' => 'nullable|array',
            'applicable_categories.*' => 'integer|distinct|exists:categories,id',
            'applicable_products' => 'nullable|array',
            'applicable_products.*' => 'integer|distinct|exists:products,id',
            'free_product_id' => 'required_if:type,free_product|nullable|exists:products,id',
            'free_qty' => 'required_if:type,free_product|nullable|integer|min:1',
            'expiry_date' => 'nullable|date',
            'usage_limit' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'excluded_products' => 'nullable|array',
            'excluded_products.*' => 'integer|distinct|exists:products,id',
            'excluded_categories' => 'nullable|array',
            'excluded_categories.*' => 'integer|distinct|exists:categories,id',
            'cashback_percent' => 'nullable|numeric|min:0|max:100',
            'cashback_fixed' => 'nullable|numeric|min:0|max:9999999999.99|decimal:0,2',
        ]);

        if (isset($data['applicable_categories'])) {
            $data['applicable_categories'] = array_values(array_unique($data['applicable_categories']));
        }
        if (isset($data['applicable_products'])) {
            $data['applicable_products'] = array_values(array_unique($data['applicable_products']));
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
        try {
            $coupon = Coupon::create($data);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $error) {
            throw \Illuminate\Validation\ValidationException::withMessages(['code' => 'This coupon code is already in use.']);
        }

        $this->clearCouponCaches();

        return response()->json(['message' => 'Coupon created.', 'data' => $coupon], 201);
    }

    public function couponsUpdate(Request $request, Coupon $coupon): JsonResponse
    {
        $this->validatePromotion($request, true, $coupon);
        if ($request->input('type') === 'flat') {
            $request->merge(['type' => 'fixed']);
        }

        $data = $request->validate([
            'code' => 'sometimes|required|string|max:50|unique:coupons,code,'.$coupon->id,
            'type' => 'sometimes|required|in:percentage,fixed,free_shipping,free_product',
            'value' => 'sometimes|nullable|numeric|min:0',
            'min_spend' => 'nullable|numeric|min:0|max:9999999999.99|decimal:0,2',
            'max_discount' => 'nullable|numeric|min:0|max:9999999999.99|decimal:0,2',
            'applicable_categories' => 'nullable|array',
            'applicable_categories.*' => 'integer|distinct|exists:categories,id',
            'applicable_products' => 'nullable|array',
            'applicable_products.*' => 'integer|distinct|exists:products,id',
            'free_product_id' => 'nullable|exists:products,id',
            'free_qty' => 'nullable|integer|min:1',
            'expiry_date' => 'nullable|date',
            'usage_limit' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'excluded_products' => 'nullable|array',
            'excluded_products.*' => 'integer|distinct|exists:products,id',
            'excluded_categories' => 'nullable|array',
            'excluded_categories.*' => 'integer|distinct|exists:categories,id',
            'cashback_percent' => 'nullable|numeric|min:0|max:100',
            'cashback_fixed' => 'nullable|numeric|min:0|max:9999999999.99|decimal:0,2',
        ]);

        if (array_key_exists('applicable_categories', $data)) {
            $data['applicable_categories'] = $data['applicable_categories'] ? array_values(array_unique($data['applicable_categories'])) : null;
        }
        if (array_key_exists('applicable_products', $data)) {
            $data['applicable_products'] = $data['applicable_products'] ? array_values(array_unique($data['applicable_products'])) : null;
        }

        if (array_key_exists('value', $data) || in_array($request->input('type', $coupon->type), ['free_shipping', 'free_product'])) {
            $data['value'] = ($data['value'] ?? null) !== '' && ($data['value'] ?? null) !== null ? (float) $data['value'] : 0;
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

        try {
            $coupon->update($data);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $error) {
            throw \Illuminate\Validation\ValidationException::withMessages(['code' => 'This coupon code is already in use.']);
        }

        $this->clearCouponCaches();

        return response()->json(['message' => 'Coupon updated.', 'data' => $coupon->fresh()]);
    }

    public function couponsDestroy(Coupon $coupon): JsonResponse
    {
        $coupon->delete();
        $this->clearCouponCaches();

        return response()->json(['message' => 'Coupon deleted.']);
    }

    public function couponsToggle(Coupon $coupon): JsonResponse
    {
        $coupon->update([
            'is_active' => ! $coupon->is_active,
            'status' => $coupon->is_active ? 'inactive' : 'active',
            'updated_by' => auth()->id(),
        ]);

        $this->clearCouponCaches();

        return response()->json(['message' => 'Coupon status toggled.', 'data' => $coupon->fresh()]);
    }

    public function couponsBulk(Request $request): JsonResponse
    {
        $data = $request->validate([
            'action' => 'required|in:delete,activate,deactivate',
            'ids' => 'required|array|min:1|max:500',
            'ids.*' => 'integer|exists:coupons,id',
        ]);

        $ids = $data['ids'];
        $action = $data['action'];

        $requiredPermission = $action === 'delete' ? 'coupon-delete' : 'coupon-edit';
        abort_unless($request->user()?->can($requiredPermission), 403, 'You do not have permission to '.$action.' coupons.');

        \Illuminate\Support\Facades\DB::transaction(function () use ($action, $ids) {
            if ($action === 'delete') {
                Coupon::whereIn('id', $ids)->get()->each->delete();
            } elseif ($action === 'activate') {
                Coupon::whereIn('id', $ids)->get()->each(fn ($record) => $record->update(['is_active' => true, 'status' => 'active', 'updated_by' => auth()->id()]));
            } elseif ($action === 'deactivate') {
                Coupon::whereIn('id', $ids)->get()->each(fn ($record) => $record->update(['is_active' => false, 'status' => 'inactive', 'updated_by' => auth()->id()]));
            }
        });

        $this->clearCouponCaches();

        return response()->json(['message' => count($ids).' coupon(s) '.$action.'d successfully.']);
    }

    // ─── Offers JSON API ──────────────────────────────────────────────────────

    public function offersIndex(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'type' => 'nullable|in:order_discount,bogo,free_product,category_discount',
            'status' => 'nullable|in:active,inactive,expired,scheduled',
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $query = Offer::with(['product', 'creator', 'updater'])->latest();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where('name', 'like', "%$s%");
        }
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (! empty($filters['status'])) {
            if ($filters['status'] === 'expired') {
                $query->whereNotNull('ends_at')->where('ends_at', '<', now());
            } elseif ($filters['status'] === 'scheduled') {
                $query->whereNotNull('starts_at')->where('starts_at', '>', now());
            } elseif ($filters['status'] === 'active') {
                $query->where('is_active', true);
            } elseif ($filters['status'] === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $perPage = (int) ($filters['per_page'] ?? 15);
        $offers = $query->paginate($perPage);
        $currentTime = now();
        $offers->getCollection()->transform(function (Offer $offer) use ($currentTime) {
            $offer->setAttribute('is_expired', $offer->ends_at !== null && $offer->ends_at->lt($currentTime));
            $offer->setAttribute('is_scheduled', $offer->starts_at !== null && $offer->starts_at->gt($currentTime));

            return $offer;
        });

        $totals = Offer::query()->toBase()->selectRaw("
            COUNT(*) as total,
            COALESCE(SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END), 0) as active,
            COALESCE(SUM(CASE WHEN ends_at < ? THEN 1 ELSE 0 END), 0) as expired,
            COALESCE(SUM(CASE WHEN starts_at > ? THEN 1 ELSE 0 END), 0) as scheduled,
            COALESCE(SUM(CASE WHEN type = 'bogo' THEN 1 ELSE 0 END), 0) as bogo,
            COALESCE(SUM(CASE WHEN type = 'order_discount' THEN 1 ELSE 0 END), 0) as order_discount
        ", [$currentTime, $currentTime])->first();
        $stats = [
            'total' => (int) $totals->total,
            'active' => (int) $totals->active,
            'expired' => (int) $totals->expired,
            'scheduled' => (int) $totals->scheduled,
            'bogo' => (int) $totals->bogo,
            'order_discount' => (int) $totals->order_discount,
        ];

        return response()->json(['data' => $offers, 'stats' => $stats]);
    }

    public function offersStore(Request $request): JsonResponse
    {
        $this->validatePromotion($request, false, null);
        if ($request->input('discount_type') === 'flat') {
            $request->merge(['discount_type' => 'fixed']);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:order_discount,bogo,category_discount,free_product',
            'discount_type' => 'required_if:type,order_discount,category_discount|nullable|in:percentage,fixed',
            'value' => 'required_if:type,order_discount,category_discount|nullable|numeric|min:0',
            'min_spend' => 'nullable|numeric|min:0|max:9999999999.99|decimal:0,2',
            'max_discount' => 'nullable|numeric|min:0|max:9999999999.99|decimal:0,2',
            'applicable_categories' => 'required_if:type,category_discount|nullable|array',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
            'product_id' => 'required_if:type,free_product|nullable|exists:products,id',
            'buy_qty' => 'required_if:type,bogo|nullable|integer|min:1',
            'get_qty' => 'required_if:type,bogo,free_product|nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date'.($request->filled('starts_at') ? '|after_or_equal:starts_at' : ''),
            'priority' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'excluded_products' => 'nullable|array',
            'excluded_products.*' => 'integer|distinct|exists:products,id',
            'excluded_categories' => 'nullable|array',
            'excluded_categories.*' => 'integer|distinct|exists:categories,id',
            'cashback_percent' => 'nullable|numeric|min:0|max:100',
            'cashback_fixed' => 'nullable|numeric|min:0|max:9999999999.99|decimal:0,2',
        ]);

        if (isset($data['applicable_categories'])) {
            $data['applicable_categories'] = array_values(array_unique($data['applicable_categories']));
        }

        $data['value'] = isset($data['value']) && $data['value'] !== '' ? (float) $data['value'] : 0;
        $data['min_spend'] = isset($data['min_spend']) && $data['min_spend'] !== '' ? (float) $data['min_spend'] : 0;
        $data['max_discount'] = isset($data['max_discount']) && $data['max_discount'] !== '' ? (float) $data['max_discount'] : null;
        $data['cashback_percent'] = isset($data['cashback_percent']) && $data['cashback_percent'] !== '' ? (float) $data['cashback_percent'] : null;
        $data['cashback_fixed'] = isset($data['cashback_fixed']) && $data['cashback_fixed'] !== '' ? (float) $data['cashback_fixed'] : null;

        $productIds = $request->input('product_ids', []);

        $offerData = $data;
        $offerData['applicable_products'] = empty($productIds) ? null : array_values(array_unique($productIds));

        if ($data['type'] !== 'free_product') {
            $offerData['product_id'] = null;
        }

        unset($offerData['product_ids']);
        $offerData['created_by'] = auth()->id();
        $offerData['updated_by'] = auth()->id();

        $offer = Offer::create($offerData);

        $this->clearOfferCaches();

        return response()->json(['message' => 'Offer created successfully.', 'data' => $offer->load('product')], 201);
    }

    public function offersUpdate(Request $request, Offer $offer): JsonResponse
    {
        $this->validatePromotion($request, false, $offer);
        if ($request->input('discount_type') === 'flat') {
            $request->merge(['discount_type' => 'fixed']);
        }

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|in:order_discount,bogo,category_discount,free_product',
            'discount_type' => 'required_if:type,order_discount,category_discount|nullable|in:percentage,fixed',
            'value' => 'required_if:type,order_discount,category_discount|nullable|numeric|min:0',
            'min_spend' => 'nullable|numeric|min:0|max:9999999999.99|decimal:0,2',
            'max_discount' => 'nullable|numeric|min:0|max:9999999999.99|decimal:0,2',
            'applicable_categories' => 'nullable|array',
            'applicable_categories.*' => 'integer|distinct|exists:categories,id',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
            'product_id' => 'nullable|exists:products,id',
            'buy_qty' => 'required_if:type,bogo|nullable|integer|min:1',
            'get_qty' => 'required_if:type,bogo,free_product|nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date'.($request->filled('starts_at') ? '|after_or_equal:starts_at' : ''),
            'priority' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'excluded_products' => 'nullable|array',
            'excluded_products.*' => 'integer|distinct|exists:products,id',
            'excluded_categories' => 'nullable|array',
            'excluded_categories.*' => 'integer|distinct|exists:categories,id',
            'cashback_percent' => 'nullable|numeric|min:0|max:100',
            'cashback_fixed' => 'nullable|numeric|min:0|max:9999999999.99|decimal:0,2',
        ]);

        if (array_key_exists('applicable_categories', $data)) {
            $data['applicable_categories'] = $data['applicable_categories'] ? array_values(array_unique($data['applicable_categories'])) : null;
        }

        if ($request->has('product_ids')) {
            $productIds = $request->input('product_ids', []);
            $data['applicable_products'] = empty($productIds) ? null : array_values(array_unique($productIds));
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

        $this->clearOfferCaches();

        return response()->json(['message' => 'Offer updated.', 'data' => $offer->fresh('product')]);
    }

    public function offersDestroy(Offer $offer): JsonResponse
    {
        $this->assertOffersUnused([$offer->id]);
        $offer->delete();
        $this->clearOfferCaches();

        return response()->json(['message' => 'Offer deleted.']);
    }

    public function offersToggle(Offer $offer): JsonResponse
    {
        $offer->update(['is_active' => ! $offer->is_active, 'updated_by' => auth()->id()]);

        $this->clearOfferCaches();

        return response()->json(['message' => 'Offer status toggled.', 'data' => $offer->fresh()]);
    }

    public function offersBulk(Request $request): JsonResponse
    {
        $data = $request->validate([
            'action' => 'required|in:delete,activate,deactivate',
            'ids' => 'required|array|min:1|max:500',
            'ids.*' => 'integer|exists:offers,id',
        ]);

        $ids = $data['ids'];
        $action = $data['action'];

        $requiredPermission = $action === 'delete' ? 'promotions-delete' : 'promotions-edit';
        abort_unless($request->user()?->can($requiredPermission), 403, 'You do not have permission to '.$action.' offers.');

        \Illuminate\Support\Facades\DB::transaction(function () use ($action, $ids) {
            if ($action === 'delete') {
                $this->assertOffersUnused($ids);
                Offer::whereIn('id', $ids)->get()->each->delete();
            } elseif ($action === 'activate') {
                Offer::whereIn('id', $ids)->get()->each(fn ($record) => $record->update(['is_active' => true, 'updated_by' => auth()->id()]));
            } elseif ($action === 'deactivate') {
                Offer::whereIn('id', $ids)->get()->each(fn ($record) => $record->update(['is_active' => false, 'updated_by' => auth()->id()]));
            }
        });

        $this->clearOfferCaches();

        return response()->json(['message' => count($ids).' offer(s) '.$action.'d successfully.']);
    }

    private function assertOffersUnused(array $ids): void
    {
        if (\App\Modules\Orders\Models\Order::withTrashed()->where(fn ($query) => $query->whereIn('selected_offer_id', $ids)->orWhereIn('applied_offer_id', $ids))->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['offer' => 'An order uses this offer. Deactivate it to preserve the order history.']);
        }
    }

    private function validatePromotion(Request $request, bool $isCoupon, $existing): void
    {
        if ($isCoupon && is_string($request->input('code'))) {
            $request->merge(['code' => strtoupper(trim($request->input('code')))]);
        }
        $state = array_replace($existing?->attributesToArray() ?? [], $request->all());
        if (($state['type'] ?? '') === 'flat') {
            $state['type'] = 'fixed';
        }
        if (($state['discount_type'] ?? '') === 'flat') {
            $state['discount_type'] = 'fixed';
        }
        $rules = [
            'value' => 'nullable|numeric|min:0|max:9999999999.99|decimal:0,2',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date'.(! empty($state['starts_at']) ? '|after_or_equal:starts_at' : ''),
            'applicable_categories.*' => 'integer|distinct|exists:categories,id',
        ];
        $type = $state['type'] ?? '';
        if (($isCoupon && $type === 'percentage') || (! $isCoupon && ($state['discount_type'] ?? '') === 'percentage')) {
            $rules['value'] = 'required|numeric|min:0|max:100';
        }
        if ($type === 'free_product') {
            $rules[$isCoupon ? 'free_product_id' : 'product_id'] = 'required|integer|exists:products,id';
            $rules[$isCoupon ? 'free_qty' : 'get_qty'] = 'required|integer|min:1';
        }
        if ($type === 'category_discount') {
            $rules['applicable_categories'] = 'required|array|min:1';
        }
        if ($type === 'bogo') {
            $rules['buy_qty'] = 'required|integer|min:1';
            $rules['get_qty'] = 'required|integer|min:1';
        }
        // Shipping has no authoritative charge engine in this application yet.
        if ($type === 'free_shipping' && (! $existing || $existing->type !== 'free_shipping')) {
            throw \Illuminate\Validation\ValidationException::withMessages(['type' => 'Free shipping campaigns are unavailable until shipping charges are configured.']);
        }
        validator($state, $rules)->validate();
        if (in_array($type, ['free_product', 'free_shipping'], true)) $request->merge(['value' => 0]);
        if ($request->has('ends_at') && ! $request->has('starts_at') && $existing) {
            $request->merge(['starts_at' => $existing->starts_at?->format('Y-m-d H:i:s')]);
        }
    }

    private function clearCouponCaches(): void
    {
        Cache::forget('active_coupons');
        Cache::forget('active_coupons_valid');
    }

    private function clearOfferCaches(): void
    {
        Cache::forget('active_offers_with_products');
        Cache::forget('active_offers_priority');
    }
}
