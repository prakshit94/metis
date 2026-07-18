<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Models\StockTransfer;
use App\Modules\Catalog\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class StockTransferController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:stocktransfer-view', only: ['index', 'show', 'options']),
            new Middleware('permission:stocktransfer-create', only: ['store']),
            new Middleware('permission:stocktransfer-edit', only: ['update', 'bulkAction']),
            new Middleware('permission:stocktransfer-delete', only: ['destroy']),
        ];
    }

    public function __construct(protected InventoryService $inventoryService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('product-view');

        $query = StockTransfer::query()
            ->with(['fromWarehouse:id,name,code', 'toWarehouse:id,name,code'])
            ->withCount('items');

        if ($search = $request->query('search')) {
            $query->where('transfer_no', 'like', "%{$search}%");
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $sortBy  = $request->query('sort_by', 'id');
        $sortDir = $request->query('sort_dir', 'desc');

        if (in_array($sortBy, ['id', 'transfer_no', 'status', 'created_at'])) {
            $query->orderBy($sortBy, $sortDir);
        }

        $perPage = min(max((int) $request->query('per_page', 25), 1), 1000);

        $paginator = $query->paginate($perPage);

        $stats = [
            'total'    => StockTransfer::count(),
            'draft'    => StockTransfer::where('status', 'draft')->count(),
            'pending'  => StockTransfer::where('status', 'sent')->count(),
            'received' => StockTransfer::where('status', 'received')->count(),
        ];

        return response()->json([
            'data'  => $paginator->items(),
            'meta'  => [
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
            ],
            'stats' => $stats,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('product-create');

        $validated = $request->validate([
            'from_warehouse_id'      => 'required|exists:warehouses,id',
            'to_warehouse_id'        => 'required|exists:warehouses,id|different:from_warehouse_id',
            'items'                  => 'required|array|min:1',
            'items.*.product_id'     => 'required|exists:products,id',
            'items.*.quantity'       => 'required|numeric|min:0.01',
        ]);

        $transfer = \DB::transaction(function () use ($validated) {
            $transfer = StockTransfer::create([
                'transfer_no'       => 'TRF-' . strtoupper(Str::random(10)),
                'from_warehouse_id' => $validated['from_warehouse_id'],
                'to_warehouse_id'   => $validated['to_warehouse_id'],
                'status'            => 'draft',
            ]);

            foreach ($validated['items'] as $item) {
                $transfer->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                ]);
            }

            return $transfer;
        });

        return response()->json([
            'message' => 'Stock transfer created successfully.',
            'data'    => $transfer->load(['fromWarehouse:id,name,code', 'toWarehouse:id,name,code', 'items.product:id,name,sku']),
        ], 201);
    }

    public function show(StockTransfer $stockTransfer): JsonResponse
    {
        $this->authorize('product-view');

        return response()->json([
            'data' => $stockTransfer->load(['fromWarehouse:id,name,code', 'toWarehouse:id,name,code', 'items.product:id,name,sku']),
        ]);
    }

    public function update(Request $request, StockTransfer $stockTransfer): JsonResponse
    {
        $this->authorize('product-edit');

        if ($stockTransfer->status !== 'draft') {
            return response()->json(['message' => 'Only draft transfers can be edited.'], 422);
        }

        $validated = $request->validate([
            'from_warehouse_id'      => 'required|exists:warehouses,id',
            'to_warehouse_id'        => 'required|exists:warehouses,id|different:from_warehouse_id',
            'items'                  => 'required|array|min:1',
            'items.*.product_id'     => 'required|exists:products,id',
            'items.*.quantity'       => 'required|numeric|min:0.01',
        ]);

        \DB::transaction(function () use ($validated, $stockTransfer) {
            $stockTransfer->update([
                'from_warehouse_id' => $validated['from_warehouse_id'],
                'to_warehouse_id'   => $validated['to_warehouse_id'],
            ]);

            $stockTransfer->items()->delete();

            foreach ($validated['items'] as $item) {
                $stockTransfer->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                ]);
            }
        });

        return response()->json([
            'message' => 'Stock transfer updated successfully.',
            'data'    => $stockTransfer->fresh()->load(['fromWarehouse:id,name,code', 'toWarehouse:id,name,code', 'items.product:id,name,sku']),
        ]);
    }

    public function destroy(StockTransfer $stockTransfer): JsonResponse
    {
        $this->authorize('product-delete');

        if ($stockTransfer->status !== 'draft') {
            return response()->json(['message' => 'Only draft transfers can be deleted.'], 422);
        }

        $stockTransfer->items()->delete();
        $stockTransfer->delete();

        return response()->json(['message' => 'Stock transfer deleted successfully.']);
    }

    /**
     * Bulk action for transfers.
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|string|in:send,receive,cancel,delete',
            'ids'    => 'required|array|min:1',
            'ids.*'  => 'required|integer|exists:stock_transfers,id',
        ]);

        $action = $validated['action'];
        $ids = $validated['ids'];

        if ($action === 'delete') {
            $this->authorize('product-delete');
        } else {
            $this->authorize('product-edit');
        }

        $transfers = StockTransfer::whereIn('id', $ids)->get();
        $processedCount = 0;
        $failedCount = 0;
        $errors = [];

        \DB::transaction(function () use ($transfers, $action, &$processedCount, &$failedCount, &$errors) {
            foreach ($transfers as $transfer) {
                try {
                    if ($action === 'send') {
                        $this->inventoryService->sendTransfer($transfer);
                    } elseif ($action === 'receive') {
                        $this->inventoryService->receiveTransfer($transfer);
                    } elseif ($action === 'cancel') {
                        $this->inventoryService->cancelSentTransfer($transfer);
                    } elseif ($action === 'delete') {
                        if ($transfer->status !== 'draft') {
                            throw new \Exception("Only draft transfers can be deleted.");
                        }
                        $transfer->items()->delete();
                        $transfer->delete();
                    }
                    $processedCount++;
                } catch (\Throwable $e) {
                    $failedCount++;
                    $errors[] = "Transfer {$transfer->transfer_no}: " . $e->getMessage();
                }
            }
        });

        if ($failedCount > 0) {
            return response()->json([
                'message' => "Processed {$processedCount} transfer(s). Failed {$failedCount} transfer(s).",
                'errors' => $errors,
            ], 422);
        }

        return response()->json([
            'message' => "Successfully processed {$processedCount} transfer(s).",
        ]);
    }

    /**
     * Mark transfer as sent (dispatched from source warehouse).
     */
    public function send(StockTransfer $stockTransfer): JsonResponse
    {
        $this->authorize('product-edit');
        try {
            $this->inventoryService->sendTransfer($stockTransfer);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first(),
                'errors'  => $e->errors(),
            ], 422);
        }

        return response()->json([
            'message' => 'Transfer marked as sent.',
            'data'    => $stockTransfer->fresh(),
        ]);
    }

    /**
     * Receive a sent transfer (commits stock movement via InventoryService).
     */
    public function receive(StockTransfer $stockTransfer): JsonResponse
    {
        $this->authorize('product-edit');

        try {
            $this->inventoryService->receiveTransfer($stockTransfer);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first(),
                'errors'  => $e->errors(),
            ], 422);
        }

        return response()->json([
            'message' => 'Transfer received and stock updated.',
            'data'    => $stockTransfer->fresh(),
        ]);
    }

    /**
     * Cancel a draft or sent transfer.
     */
    public function cancel(StockTransfer $stockTransfer): JsonResponse
    {
        $this->authorize('product-edit');

        try {
            $this->inventoryService->cancelSentTransfer($stockTransfer);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first(),
                'errors'  => $e->errors(),
            ], 422);
        }

        return response()->json([
            'message' => 'Transfer cancelled.',
            'data'    => $stockTransfer->fresh(),
        ]);
    }

    /**
     * Get warehouse and product options for the transfer form.
     */
    public function options(): JsonResponse
    {
        $this->authorize('product-view');

        return response()->json([
            'warehouses' => Warehouse::where('status', 'active')->orderBy('name')->get(['id', 'name', 'code', 'is_default']),
            'products'   => Product::where('status', '!=', 'draft')->orderBy('name')->get(['id', 'name', 'sku']),
        ]);
    }
}
