<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Controllers;

use App\Modules\Catalog\Models\Warehouse;
use App\Modules\Core\Controllers\Controller;
use App\Modules\Core\Models\Village;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class WarehouseController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:warehouse-view', only: ['index', 'show']),
            new Middleware('permission:warehouse-create', only: ['store']),
            new Middleware('permission:warehouse-edit', only: ['update']),
            new Middleware('permission:warehouse-delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): JsonResponse
    {


        $query = Warehouse::query()
            ->withCount('stocks as total_skus')
            ->withSum('stocks as total_physical_stock', 'quantity')
            ->withSum('stocks as total_reserved_stock', 'reserved_qty')
            ->withCount('orders as total_orders')
            ->withCount(['orders as fulfillable_orders' => function ($query) {
                $query->whereIn('status', ['confirmed', 'processing'])
                      ->orWhere(function ($q) {
                          $q->where('status', 'pending')
                            ->whereDoesntHave('items', function ($iq) {
                                $iq->whereRaw('quantity > (IFNULL((SELECT SUM(quantity - reserved_qty) FROM stocks WHERE stocks.product_id = order_items.product_id AND stocks.warehouse_id = orders.warehouse_id AND stocks.deleted_at IS NULL), 0))');
                            });
                      });
            }])
            ->withCount(['orders as unfulfillable_orders' => function ($query) {
                $query->where('status', 'pending')
                      ->whereHas('items', function ($q) {
                          $q->whereRaw('quantity > (IFNULL((SELECT SUM(quantity - reserved_qty) FROM stocks WHERE stocks.product_id = order_items.product_id AND stocks.warehouse_id = orders.warehouse_id AND stocks.deleted_at IS NULL), 0))');
                      });
            }]);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('gstin', 'like', "%{$search}%");
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $sortBy = $request->query('sort_by', 'id');
        $sortDir = $request->query('sort_dir', 'desc');

        if (in_array($sortBy, ['id', 'name', 'code', 'status', 'city', 'state'])) {
            $query->orderBy($sortBy, $sortDir);
        }

        $perPage = (int) $request->query('per_page', 15);
        $perPage = min(max($perPage, 1), 1000);

        $paginator = $query->paginate($perPage);

        return response()->json($paginator);
    }

    public function store(Request $request): JsonResponse
    {


        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:warehouses,code',
            'company_name' => 'nullable|string|max:255',
            'gstin' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'village_id' => 'nullable|integer|exists:villages,id',
            'village_name' => 'nullable|string|max:255',
            'post_office' => 'nullable|string|max:255',
            'taluka' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'pincode' => 'nullable|string|max:10',
            'is_default' => 'nullable|boolean',
            'status' => 'required|in:active,inactive',
            'email' => 'nullable|email|max:255',
            'reference_no' => 'nullable|string|max:255',
            'seed_lic_no' => 'nullable|string|max:255',
            'pesti_lic_no' => 'nullable|string|max:255',
        ]);

        // Auto-generate code if not provided
        if (empty($validated['code'])) {
            $validated['code'] = strtoupper(preg_replace('/[^A-Z0-9]/i', '', substr($validated['name'], 0, 6)));
        }

        // If a village is selected, fill in address details from it
        if (! empty($validated['village_id'])) {
            $village = Village::find($validated['village_id']);
            if ($village) {
                $validated['village_name'] = $village->village_name;
                $validated['city'] = $validated['city'] ?: ($village->taluka_name ?: $village->district_name);
                $validated['state'] = $validated['state'] ?: $village->state_name;
                $validated['pincode'] = $validated['pincode'] ?: $village->pincode;
                $validated['taluka'] = $village->taluka_name;
                $validated['post_office'] = $village->post_so_name;
            }
        }

        // If this warehouse is set as default, unset others
        if (! empty($validated['is_default'])) {
            Warehouse::where('is_default', true)->update(['is_default' => false]);
        }

        $warehouse = Warehouse::create($validated);

        return response()->json([
            'message' => 'Warehouse created successfully.',
            'data' => $warehouse,
        ], 201);
    }

    public function show(Warehouse $model): JsonResponse
    {


        return response()->json(['data' => $model]);
    }

    public function update(Request $request, $id): JsonResponse
    {


        $model = Warehouse::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => "sometimes|nullable|string|max:50|unique:warehouses,code,{$id}",
            'company_name' => 'sometimes|nullable|string|max:255',
            'gstin' => 'sometimes|nullable|string|max:20',
            'phone' => 'sometimes|nullable|string|max:20',
            'address_line_1' => 'sometimes|nullable|string|max:255',
            'address_line_2' => 'sometimes|nullable|string|max:255',
            'village_id' => 'sometimes|nullable|integer|exists:villages,id',
            'village_name' => 'sometimes|nullable|string|max:255',
            'post_office' => 'sometimes|nullable|string|max:255',
            'taluka' => 'sometimes|nullable|string|max:255',
            'city' => 'sometimes|nullable|string|max:255',
            'state' => 'sometimes|nullable|string|max:255',
            'pincode' => 'sometimes|nullable|string|max:10',
            'is_default' => 'sometimes|nullable|boolean',
            'status' => 'sometimes|required|in:active,inactive',
            'email' => 'sometimes|nullable|email|max:255',
            'reference_no' => 'sometimes|nullable|string|max:255',
            'seed_lic_no' => 'sometimes|nullable|string|max:255',
            'pesti_lic_no' => 'sometimes|nullable|string|max:255',
        ]);

        // If a village is selected, fill in address details from it
        if (! empty($validated['village_id']) && $validated['village_id'] !== $model->village_id) {
            $village = Village::find($validated['village_id']);
            if ($village) {
                $validated['village_name'] = $village->village_name;
                $validated['city'] = $validated['city'] ?: ($village->taluka_name ?: $village->district_name);
                $validated['state'] = $validated['state'] ?: $village->state_name;
                $validated['pincode'] = $validated['pincode'] ?: $village->pincode;
                $validated['taluka'] = $village->taluka_name;
                $validated['post_office'] = $village->post_so_name;
            }
        }

        // If this warehouse is being set as default, unset others
        if (! empty($validated['is_default'])) {
            Warehouse::where('id', '!=', $id)->where('is_default', true)->update(['is_default' => false]);
        }

        $model->update($validated);

        return response()->json([
            'message' => 'Warehouse updated successfully.',
            'data' => $model->fresh(),
        ]);
    }

    public function destroy($id): JsonResponse
    {


        $model = Warehouse::findOrFail($id);

        if ($model->is_default) {
            return response()->json(['message' => 'Cannot delete the default warehouse.'], 422);
        }

        $hasActiveStock = \App\Modules\Inventory\Models\Stock::where('warehouse_id', $id)
            ->where(function ($query) {
                $query->where('quantity', '>', 0)
                    ->orWhere('reserved_qty', '>', 0)
                    ->orWhere('committed_qty', '>', 0)
                    ->orWhere('in_transit_qty', '>', 0)
                    ->orWhere('dispatched_qty', '>', 0);
            })->exists();

        if ($hasActiveStock) {
            return response()->json([
                'message' => 'Cannot delete this warehouse because it contains active inventory or pending orders. Please transfer all stock and clear pending reservations before deletion.'
            ], 422);
        }

        $model->delete();

        return response()->json([
            'message' => 'Warehouse deleted successfully.',
        ]);
    }
}
