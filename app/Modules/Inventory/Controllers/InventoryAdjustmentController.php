<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Controllers;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Warehouse;
use App\Modules\Core\Controllers\Controller;
use App\Modules\Inventory\Models\InventoryAdjustment;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InventoryAdjustmentController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:inventoryadjustment-view', only: ['index', 'show', 'options']),
            new Middleware('permission:inventoryadjustment-create', only: ['store']),
            new Middleware('permission:inventoryadjustment-edit', only: ['update', 'bulkAction']),
            new Middleware('permission:inventoryadjustment-delete', only: ['destroy']),
        ];
    }

    public function __construct(protected InventoryService $inventoryService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('product-view');

        $query = InventoryAdjustment::query()
            ->with(['warehouse:id,name,code'])
            ->withCount('items');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('reference_no', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%")
                    ->orWhereHas('warehouse', fn ($w) => $w->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($warehouseId = $request->query('warehouse_id')) {
            $query->where('warehouse_id', $warehouseId);
        }

        $sortBy = $request->query('sort_by', 'id');
        $sortDir = $request->query('sort_dir', 'desc');

        if (in_array($sortBy, ['id', 'reference_no', 'status', 'created_at'])) {
            $query->orderBy($sortBy, $sortDir);
        }

        $perPage = min(max((int) $request->query('per_page', 25), 1), 1000);

        $paginator = $query->paginate($perPage);

        $stats = [
            'total' => InventoryAdjustment::count(),
            'pending' => InventoryAdjustment::where('status', 'pending')->count(),
            'approved' => InventoryAdjustment::where('status', 'approved')->count(),
            'rejected' => InventoryAdjustment::where('status', 'rejected')->count(),
        ];

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
            'stats' => $stats,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('product-create');

        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'reason' => 'required|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.current_qty' => 'required|numeric|min:0',
            'items.*.new_qty' => 'required|numeric|min:0',
        ]);

        $adjustment = \DB::transaction(function () use ($validated) {
            $adjustment = InventoryAdjustment::create([
                'reference_no' => 'ADJ-'.strtoupper(Str::random(8)),
                'warehouse_id' => $validated['warehouse_id'],
                'reason' => $validated['reason'],
                'status' => 'pending',
                'adjusted_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                $adjustment->items()->create([
                    'product_id' => $item['product_id'],
                    'current_qty' => $item['current_qty'],
                    'new_qty' => $item['new_qty'],
                    'difference' => $item['new_qty'] - $item['current_qty'],
                ]);
            }

            return $adjustment;
        });

        return response()->json([
            'message' => 'Inventory adjustment created successfully.',
            'data' => $adjustment->load(['warehouse:id,name,code', 'items.product:id,name,sku']),
        ], 201);
    }

    public function show(InventoryAdjustment $inventoryAdjustment): JsonResponse
    {
        $this->authorize('product-view');

        return response()->json([
            'data' => $inventoryAdjustment->load(['warehouse:id,name,code', 'items.product:id,name,sku']),
        ]);
    }

    public function update(Request $request, InventoryAdjustment $inventoryAdjustment): JsonResponse
    {
        $this->authorize('product-edit');

        if ($inventoryAdjustment->status !== 'pending') {
            return response()->json(['message' => 'Only pending adjustments can be edited.'], 422);
        }

        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'reason' => 'required|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.current_qty' => 'required|numeric|min:0',
            'items.*.new_qty' => 'required|numeric|min:0',
        ]);

        \DB::transaction(function () use ($validated, $inventoryAdjustment) {
            $inventoryAdjustment->update([
                'warehouse_id' => $validated['warehouse_id'],
                'reason' => $validated['reason'],
            ]);

            $inventoryAdjustment->items()->delete();

            foreach ($validated['items'] as $item) {
                $inventoryAdjustment->items()->create([
                    'product_id' => $item['product_id'],
                    'current_qty' => $item['current_qty'],
                    'new_qty' => $item['new_qty'],
                    'difference' => $item['new_qty'] - $item['current_qty'],
                ]);
            }
        });

        return response()->json([
            'message' => 'Inventory adjustment updated successfully.',
            'data' => $inventoryAdjustment->fresh()->load(['warehouse:id,name,code', 'items.product:id,name,sku']),
        ]);
    }

    public function destroy(InventoryAdjustment $inventoryAdjustment): JsonResponse
    {
        $this->authorize('product-delete');

        if ($inventoryAdjustment->status !== 'pending') {
            return response()->json(['message' => 'Only pending adjustments can be deleted.'], 422);
        }

        $inventoryAdjustment->items()->delete();
        $inventoryAdjustment->delete();

        return response()->json(['message' => 'Inventory adjustment deleted successfully.']);
    }

    /**
     * Bulk action for adjustments.
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|string|in:approve,reject,delete',
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:inventory_adjustments,id',
        ]);

        $action = $validated['action'];
        $ids = $validated['ids'];

        if ($action === 'delete') {
            $this->authorize('product-delete');
        } else {
            $this->authorize('product-edit');
        }

        $adjustments = InventoryAdjustment::whereIn('id', $ids)->get();
        $processedCount = 0;
        $failedCount = 0;
        $errors = [];

        // Each adjustment runs in its own transaction — a failure on one does not affect others
        foreach ($adjustments as $adjustment) {
            try {
                \DB::transaction(function () use ($adjustment, $action) {
                    if ($action === 'approve') {
                        $this->inventoryService->applyAdjustment($adjustment);
                    } elseif ($action === 'reject') {
                        if ($adjustment->status !== 'pending') {
                            throw new \Exception('Only pending adjustments can be rejected.');
                        }
                        $adjustment->update(['status' => 'rejected']);
                    } elseif ($action === 'delete') {
                        if ($adjustment->status !== 'pending') {
                            throw new \Exception('Only pending adjustments can be deleted.');
                        }
                        $adjustment->items()->delete();
                        $adjustment->delete();
                    }
                });
                $processedCount++;
            } catch (\Throwable $e) {
                $failedCount++;
                $errors[] = "Adjustment {$adjustment->reference_no}: " . $e->getMessage();
            }
        }

        if ($failedCount > 0) {
            return response()->json([
                'message' => "Processed {$processedCount} adjustment(s). Failed {$failedCount} adjustment(s).",
                'errors'  => $errors,
            ], 422);
        }

        return response()->json([
            'message' => "Successfully processed {$processedCount} adjustment(s).",
        ]);
    }

    /**
     * Approve and apply an inventory adjustment.
     */
    public function approve(InventoryAdjustment $inventoryAdjustment): JsonResponse
    {
        $this->authorize('product-edit');

        try {
            $this->inventoryService->applyAdjustment($inventoryAdjustment);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json([
            'message' => 'Adjustment approved and stock levels updated.',
            'data' => $inventoryAdjustment->fresh(),
        ]);
    }

    /**
     * Reject a pending adjustment.
     */
    public function reject(Request $request, InventoryAdjustment $inventoryAdjustment): JsonResponse
    {
        $this->authorize('product-edit');

        if ($inventoryAdjustment->status !== 'pending') {
            return response()->json(['message' => 'Only pending adjustments can be rejected.'], 422);
        }

        $request->validate(['reason' => 'nullable|string|max:255']);

        $inventoryAdjustment->update([
            'status' => 'rejected',
            'reason' => $request->input('reason')
                ? ($inventoryAdjustment->reason.' | Rejected: '.$request->input('reason'))
                : $inventoryAdjustment->reason,
        ]);

        return response()->json([
            'message' => 'Adjustment rejected.',
            'data' => $inventoryAdjustment->fresh(),
        ]);
    }

    /**
     * Get warehouse and product options for the adjustment form.
     */
    public function options(): JsonResponse
    {
        $this->authorize('product-view');

        return response()->json([
            'warehouses' => Warehouse::where('status', 'active')->orderBy('name')->get(['id', 'name', 'code']),
            'products' => Product::where('status', '!=', 'draft')->orderBy('name')->get(['id', 'name', 'sku']),
        ]);
    }
}
