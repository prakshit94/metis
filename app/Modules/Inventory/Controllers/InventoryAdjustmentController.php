<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Controllers;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Warehouse;
use App\Modules\Core\Controllers\Controller;
use App\Modules\Inventory\Models\InventoryAdjustment;
use App\Modules\Inventory\Models\Stock;
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
            new Middleware('permission:inventoryadjustment-edit', only: ['update', 'approve', 'reject']),
            new Middleware('permission:inventoryadjustment-edit|inventoryadjustment-delete', only: ['bulkAction']),
            new Middleware('permission:inventoryadjustment-delete', only: ['destroy']),
        ];
    }

    public function __construct(protected InventoryService $inventoryService) {}

    public function index(Request $request): JsonResponse
    {
        $query = InventoryAdjustment::query()
            ->with(['warehouse:id,name,code'])
            ->withCount('items')
            ->whereHas('warehouse', function ($wq) use ($request) {
                if ($lobState = $request->user()?->lob_state_name) {
                    $wq->where('state', $lobState);
                }
            });
        $statsQuery = InventoryAdjustment::query()
            ->whereHas('warehouse', function ($wq) use ($request) {
                if ($lobState = $request->user()?->lob_state_name) {
                    $wq->where('state', $lobState);
                }
            });

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
        $sortDir = strtolower((string) $request->query('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (in_array($sortBy, ['id', 'reference_no', 'status', 'created_at'])) {
            $query->orderBy($sortBy, $sortDir);
        }

        $perPage = min(max((int) $request->query('per_page', 25), 1), 100);

        $paginator = $query->paginate($perPage);

        $statsRow = $statsQuery->reorder()->selectRaw("COUNT(*) as total, SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending, SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved, SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected")->first();
        $stats = [
            'total' => (int) ($statsRow->total ?? 0),
            'pending' => (int) ($statsRow->pending ?? 0),
            'approved' => (int) ($statsRow->approved ?? 0),
            'rejected' => (int) ($statsRow->rejected ?? 0),
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
        $validated = $request->validate([
            'warehouse_id' => [
                'required',
                'exists:warehouses,id',
                function ($attribute, $value, $fail) use ($request) {
                    $wh = Warehouse::find($value);
                    if (! $wh || $wh->status !== 'active') {
                        $fail('Please select an active warehouse.');
                    } elseif (($lobState = $request->user()?->lob_state_name) && $wh->state !== $lobState) {
                        $fail('Unauthorized warehouse selection.');
                    }
                }
            ],
            'reason' => 'required|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|distinct|exists:products,id',
            'items.*.new_qty' => 'required|numeric|min:0',
        ]);

        $adjustment = \DB::transaction(function () use ($validated) {
            $currentQuantities = Stock::withTrashed()->where('warehouse_id', $validated['warehouse_id'])
                ->whereIn('product_id', collect($validated['items'])->pluck('product_id'))
                ->pluck('quantity', 'product_id');

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
                    'current_qty' => $currentQuantities[$item['product_id']] ?? 0,
                    'new_qty' => $item['new_qty'],
                    'difference' => $item['new_qty'] - ($currentQuantities[$item['product_id']] ?? 0),
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
        return response()->json([
            'data' => $inventoryAdjustment->load([
                'warehouse:id,name,code',
                'user:id,name',
                'items.product:id,name,sku',
            ]),
        ]);
    }

    public function update(Request $request, InventoryAdjustment $inventoryAdjustment): JsonResponse
    {
        $validated = $request->validate([
            'warehouse_id' => [
                'required',
                'exists:warehouses,id',
                function ($attribute, $value, $fail) use ($request) {
                    $wh = Warehouse::find($value);
                    if (! $wh || $wh->status !== 'active') {
                        $fail('Please select an active warehouse.');
                    } elseif (($lobState = $request->user()?->lob_state_name) && $wh->state !== $lobState) {
                        $fail('Unauthorized warehouse selection.');
                    }
                }
            ],
            'reason' => 'required|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|distinct|exists:products,id',
            'items.*.new_qty' => 'required|numeric|min:0',
        ]);

        \DB::transaction(function () use ($validated, $inventoryAdjustment) {
            $inventoryAdjustment = InventoryAdjustment::lockForUpdate()->findOrFail($inventoryAdjustment->id);
            if ($inventoryAdjustment->status !== 'pending') {
                throw ValidationException::withMessages(['status' => 'Only pending adjustments can be edited.']);
            }
            $currentQuantities = Stock::withTrashed()->where('warehouse_id', $validated['warehouse_id'])
                ->whereIn('product_id', collect($validated['items'])->pluck('product_id'))
                ->pluck('quantity', 'product_id');

            $inventoryAdjustment->update([
                'warehouse_id' => $validated['warehouse_id'],
                'reason' => $validated['reason'],
            ]);

            $inventoryAdjustment->items()->delete();

            foreach ($validated['items'] as $item) {
                $inventoryAdjustment->items()->create([
                    'product_id' => $item['product_id'],
                    'current_qty' => $currentQuantities[$item['product_id']] ?? 0,
                    'new_qty' => $item['new_qty'],
                    'difference' => $item['new_qty'] - ($currentQuantities[$item['product_id']] ?? 0),
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
        \DB::transaction(function () use ($inventoryAdjustment) {
            $inventoryAdjustment = InventoryAdjustment::lockForUpdate()->findOrFail($inventoryAdjustment->id);
            if ($inventoryAdjustment->status !== 'pending') {
                throw ValidationException::withMessages(['status' => 'Only pending adjustments can be deleted.']);
            }
            $inventoryAdjustment->items()->delete();
            $inventoryAdjustment->delete();
        });

        return response()->json(['message' => 'Inventory adjustment deleted successfully.']);
    }

    /**
     * Bulk action for adjustments.
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|string|in:approve,reject,delete',
            'ids' => 'required|array|min:1|max:100',
            'ids.*' => 'required|integer|exists:inventory_adjustments,id',
        ]);

        $action = $validated['action'];
        $ids = $validated['ids'];

        abort_unless($request->user()?->can($action === 'delete' ? 'inventoryadjustment-delete' : 'inventoryadjustment-edit'), 403);
        $adjustments = InventoryAdjustment::with('items')->whereIn('id', $ids)->get();
        $processedCount = 0;
        $failedCount = 0;
        $errors = [];

        // Each adjustment runs in its own transaction — a failure on one does not affect others
        foreach ($adjustments as $adjustment) {
            try {
                \DB::transaction(function () use ($adjustment, $action) {
                    $adjustment = InventoryAdjustment::lockForUpdate()->findOrFail($adjustment->id);
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
                $errors[] = "Adjustment {$adjustment->reference_no}: ".$e->getMessage();
            }
        }

        if ($failedCount > 0) {
            return response()->json([
                'message' => "Processed {$processedCount} adjustment(s). Failed {$failedCount} adjustment(s).",
                'errors' => $errors,
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
        $request->validate(['reason' => 'nullable|string|max:255']);

        \DB::transaction(function () use ($request, $inventoryAdjustment) {
            $inventoryAdjustment = InventoryAdjustment::lockForUpdate()->findOrFail($inventoryAdjustment->id);
            if ($inventoryAdjustment->status !== 'pending') {
                throw ValidationException::withMessages(['status' => 'Only pending adjustments can be rejected.']);
            }
            $inventoryAdjustment->update([
                'status' => 'rejected',
                'reason' => $request->input('reason')
                    ? ($inventoryAdjustment->reason.' | Rejected: '.$request->input('reason'))
                    : $inventoryAdjustment->reason,
            ]);
        });

        return response()->json([
            'message' => 'Adjustment rejected.',
            'data' => $inventoryAdjustment->fresh(),
        ]);
    }

    /**
     * Get warehouse and product options for the adjustment form.
     */
    public function options(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('inventoryadjustment-view'), 403);
        $productQuery = Product::query()->where('status', '!=', 'draft');
        if ($search = trim((string) $request->query('q', ''))) {
            $productQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "{$search}%")
                    ->orWhere('sku', 'like', "{$search}%");
            });
        }
        $products = $productQuery->orderBy('name')->limit(50)->get(['id', 'name', 'sku']);
        $warehouseId = $request->integer('warehouse_id');

        if ($warehouseId) {
            abort_unless(Warehouse::whereKey($warehouseId)->where('status', 'active')
                ->when($request->user()?->lob_state_name, function ($query, $state) {
                    $query->where('state', $state);
                })->exists(), 403);
        }

        return response()->json([
            'warehouses' => $request->filled('q') ? [] : Warehouse::where('status', 'active')
                ->when($request->user()?->lob_state_name, function ($query, $state) {
                    $query->where('state', $state);
                })
                ->orderBy('name')->get(['id', 'name', 'code']),
            'products' => $products,
            'stocks' => $warehouseId
                ? Stock::withTrashed()->where('warehouse_id', $warehouseId)
                    ->whereIn('product_id', $products->pluck('id'))
                    ->get(['product_id', 'quantity'])->keyBy('product_id')
                : [],
        ]);
    }
}
