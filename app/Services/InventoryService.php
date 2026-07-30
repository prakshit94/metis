<?php

declare(strict_types=1);

namespace App\Services;

use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Models\InventoryAdjustment;
use App\Modules\Inventory\Models\Stock;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Models\StockReservation;
use App\Modules\Inventory\Models\StockTransfer;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderReturn;
use App\Modules\Orders\Models\Shipment;
use App\Modules\Orders\Models\Coupon;
use App\Models\ReferralProgram;
use Illuminate\Support\Str;
use App\Notifications\LowStockNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * InventoryService – SINGLE SOURCE OF TRUTH for all stock mutations.
 */
class InventoryService
{
    // ─────────────────────────────────────────────────────────────────────────
    //  Internal helpers (must only be called inside an active transaction)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Merge any duplicate stock rows for a product/warehouse pair.
     */
    private function mergeDuplicateStocks(int $productId, int $warehouseId): void
    {
        $stocks = Stock::where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->orderBy('id')
            ->get();

        if ($stocks->count() <= 1) {
            return;
        }

        $primary = $stocks->first();
        $primary->quantity = $stocks->sum('quantity');
        $primary->reserved_qty = $stocks->sum('reserved_qty');
        $primary->committed_qty = $stocks->sum('committed_qty');
        $primary->in_transit_qty = $stocks->sum('in_transit_qty');
        $primary->save();

        DB::table('stocks')
            ->whereIn('id', $stocks->skip(1)->pluck('id')->all())
            ->delete();
    }

    /**
     * Fetch (or create) the stock row with a write-lock.
     */
    private function getStockForUpdate(int $productId, int $warehouseId): Stock
    {
        // Merge duplicates first (within the same transaction)
        $this->mergeDuplicateStocks($productId, $warehouseId);

        $stock = Stock::withTrashed()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        if ($stock) {
            if ($stock->trashed()) {
                $stock->restore();
            }

            return $stock;
        }

        $product = Product::find($productId);

        // Create a brand-new row if it doesn't exist yet.
        // Fall back to the legacy product stock value so older records can be reserved
        // even when the dedicated stocks table has not been initialized yet.
        return Stock::create([
            'warehouse_id' => $warehouseId,
            'product_id' => $productId,
            'quantity' => max(0.0, (float) ($product?->total_stock ?? 0)),
            'reserved_qty' => 0,
            'dispatched_qty' => 0,
            'committed_qty' => 0,
            'in_transit_qty' => 0,
        ]);
    }

    /**
     * Write a StockMovement audit record.
     */
    private function logMovement(
        int $productId,
        int $warehouseId,
        float $quantity,
        string $type,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $performedBy = null
    ): void {
        StockMovement::create([
            'product_id'     => $productId,
            'warehouse_id'   => $warehouseId,
            'quantity'       => $quantity,
            'type'           => $type,
            'reference_type' => $referenceType,
            'reference_id'   => $referenceId,
            'status'         => 'active',
            // Prefer explicitly passed value; fall back to HTTP auth; fall back to 0 (system)
            'performed_by'   => $performedBy ?? auth()->id() ?? 0,
        ]);
    }

    private function ensurePositive(float $quantity, string $field = 'quantity'): void
    {
        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                $field => 'Quantity must be greater than zero.',
            ]);
        }
    }

    /**
     * Synchronize product status based on aggregate inventory levels.
     */
    private function syncProductStatus(int $productId): void
    {
        $product = Product::find($productId);
        if (! $product) {
            return;
        }

        // Never auto-activate a draft product
        if ($product->status === 'draft') {
            $product->saveQuietly();

            return;
        }

        $totalAvailable = Stock::where('product_id', $productId)
            ->get()
            ->sum(fn ($s) => (float) $s->quantity - (float) $s->reserved_qty);

        $newStatus = $product->status;

        if ($totalAvailable <= 0 && ! $product->allow_overselling) {
            $newStatus = 'out_of_stock';
        } else {
            // If it was out of stock but now has stock or overselling is enabled, activate it
            if ($product->status === 'out_of_stock') {
                $newStatus = 'published';
            }
        }

        if ($newStatus !== $product->status) {
            $product->status = $newStatus;
        }

        $product->saveQuietly();
    }

    /**
     * Update in-transit quantity for a product/warehouse pair.
     */
    private function adjustInTransitQuantity(int $productId, int $warehouseId, float $quantityDelta): Stock
    {
        $stock = $this->getStockForUpdate($productId, $warehouseId);
        $stock->in_transit_qty = max(0.0, (float) $stock->in_transit_qty + $quantityDelta);
        $stock->save();

        return $stock->refresh();
    }

    public function ensureWarehouseStockCoverage(int $warehouseId): void
    {
        DB::transaction(function () use ($warehouseId) {
            $productIds = Product::where('status', '!=', 'draft')->pluck('id');

            $existingStocks = Stock::withTrashed()
                ->where('warehouse_id', $warehouseId)
                ->whereIn('product_id', $productIds)
                ->get(['id', 'product_id', 'deleted_at']);

            $existingProductIds = $existingStocks->pluck('product_id')->toArray();
            $trashedStocks = $existingStocks->filter(fn ($s) => $s->trashed());

            if ($trashedStocks->isNotEmpty()) {
                Stock::withTrashed()->whereIn('id', $trashedStocks->pluck('id'))->restore();
            }

            $missingIds = array_diff($productIds->toArray(), $existingProductIds);

            if (! empty($missingIds)) {
                $now = now();
                $insertData = [];
                foreach ($missingIds as $productId) {
                    $insertData[] = [
                        'product_id' => $productId,
                        'warehouse_id' => $warehouseId,
                        'quantity' => 0,
                        'reserved_qty' => 0,
                        'dispatched_qty' => 0,
                        'committed_qty' => 0,
                        'in_transit_qty' => 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                foreach (array_chunk($insertData, 500) as $chunk) {
                    Stock::insert($chunk);
                }
            }
        }, 3);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Public read helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Get (or create) a stock record – safe for read-only use.
     */
    public function getStock(int $productId, int $warehouseId): Stock
    {
        return DB::transaction(function () use ($productId, $warehouseId) {
            return $this->getStockForUpdate($productId, $warehouseId);
        }, 3);
    }

    /**
     * Available qty = total qty − reserved qty.
     */
    public function getAvailableQty(int $productId, int $warehouseId): float
    {
        $stock = Stock::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        if (! $stock) {
            return 0.0;
        }

        return max(0.0, (float) $stock->quantity - (float) $stock->reserved_qty);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Stock mutations (each owns its own transaction)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Hard-set a stock quantity (used by adjustments & imports).
     */
    public function setStock(int $productId, int $warehouseId, float $newQuantity, ?float $newDamagedQuantity = null): Stock
    {
        if ($newQuantity < 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Stock quantity cannot be negative.',
            ]);
        }

        if ($newDamagedQuantity !== null && $newDamagedQuantity < 0) {
            throw ValidationException::withMessages([
                'damaged_qty' => 'Damaged quantity cannot be negative.',
            ]);
        }

        return DB::transaction(function () use ($productId, $warehouseId, $newQuantity, $newDamagedQuantity) {
            $stock = $this->getStockForUpdate($productId, $warehouseId);

            if ($stock->reserved_qty > $newQuantity) {
                throw ValidationException::withMessages([
                    'quantity' => 'New quantity cannot be lower than reserved stock.',
                ]);
            }

            $diff = $newQuantity - (float) $stock->quantity;
            $stock->quantity = $newQuantity;

            if ($newDamagedQuantity !== null) {
                $diffDamaged = $newDamagedQuantity - (float) $stock->damaged_qty;
                $stock->damaged_qty = $newDamagedQuantity;

                if ($diffDamaged != 0) {
                    $this->logMovement(
                        $productId,
                        $warehouseId,
                        abs($diffDamaged),
                        $diffDamaged > 0 ? 'damage' : 'adjustment'
                    );
                }
            }

            $stock->save();

            if ($diff != 0) {
                $this->logMovement(
                    $productId,
                    $warehouseId,
                    abs($diff),
                    'adjustment'
                );
            }

            $this->syncProductStatus($productId);

            return $stock->refresh();
        }, 3);
    }

    /**
     * Add stock (e.g. purchase received, transfer in).
     */
    public function addStock(
        int $productId,
        int $warehouseId,
        float $quantity,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): Stock {
        $this->ensurePositive($quantity);

        return DB::transaction(function () use ($productId, $warehouseId, $quantity, $referenceType, $referenceId) {
            $stock = $this->getStockForUpdate($productId, $warehouseId);
            $stock->quantity = (float) $stock->quantity + $quantity;
            $stock->save();

            $this->logMovement($productId, $warehouseId, $quantity, 'in', $referenceType, $referenceId);

            $this->syncProductStatus($productId);

            return $stock->refresh();
        }, 3);
    }

    /**
     * Deduct stock (e.g. sale shipped, transfer out).
     */
    public function deductStock(
        int $productId,
        int $warehouseId,
        float $quantity,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): Stock {
        $this->ensurePositive($quantity);

        return DB::transaction(function () use ($productId, $warehouseId, $quantity, $referenceType, $referenceId) {
            $stock = $this->getStockForUpdate($productId, $warehouseId);

            if ((float) $stock->quantity < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => 'Insufficient stock quantity.',
                ]);
            }

            $newQty = (float) $stock->quantity - $quantity;

            if ($stock->reserved_qty > $newQty) {
                throw ValidationException::withMessages([
                    'quantity' => 'Cannot deduct below reserved stock.',
                ]);
            }

            $stock->quantity = $newQty;
            $stock->save();

            $this->logMovement($productId, $warehouseId, $quantity, 'out', $referenceType, $referenceId);

            $this->syncProductStatus($productId);

            // Dispatch Low Stock Notification if quantity is below threshold (fail-safe)
            if ($newQty <= 5) {
                try {
                    $admins = \App\Modules\Users\Models\User::role(['Admin', 'Super Admin', 'Inventory Manager'])->get();
                    $productName = $stock->product->name ?? 'Unknown Product';
                    $warehouseName = clone $stock->warehouse ? clone $stock->warehouse->name : 'Unknown Warehouse';
                    Notification::send($admins, new LowStockNotification($productName, (int) $newQty, $warehouseName));
                } catch (\Throwable) {
                    // Silently fail — notification delivery is non-critical
                }
            }

            return $stock->refresh();
        }, 3);
    }

    /**
     * Reserve stock for a confirmed sale order.
     */
    public function reserveStock(
        int $productId,
        int $warehouseId,
        float $quantity,
        ?int $orderId = null
    ): Stock {
        $this->ensurePositive($quantity);

        return DB::transaction(function () use ($productId, $warehouseId, $quantity, $orderId) {
            $stock = $this->getStockForUpdate($productId, $warehouseId);
            $rawAvailable = (float) $stock->quantity - (float) $stock->reserved_qty;
            $maxReservable = $rawAvailable;

            if ($maxReservable < $quantity) {
                $productName = Product::where('id', $productId)->value('name') ?? "ID: {$productId}";
                throw ValidationException::withMessages([
                    'quantity' => "Not enough stock for product '{$productName}'. Available: {$maxReservable}, Requested: {$quantity}.",
                ]);
            }

            $stock->reserved_qty = (float) $stock->reserved_qty + $quantity;
            $stock->save();

            // Write a reservation record
            StockReservation::create([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'order_id' => $orderId,
                'quantity' => $quantity,
                'status' => 'active',
            ]);

            $this->logMovement($productId, $warehouseId, $quantity, 'reserve', Order::class, $orderId);

            $this->syncProductStatus($productId);

            return $stock->refresh();
        }, 3);
    }

    /**
     * Release a reservation.
     */
    public function releaseReservedStock(
        int $productId,
        int $warehouseId,
        float $quantity,
        ?int $orderId = null,
        string $reason = 'cancelled'
    ): Stock {
        $this->ensurePositive($quantity);

        return DB::transaction(function () use ($productId, $warehouseId, $quantity, $orderId, $reason) {
            $stock = $this->getStockForUpdate($productId, $warehouseId);

            if ((float) $stock->reserved_qty < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => 'Reserved stock cannot go below zero.',
                ]);
            }

            $stock->reserved_qty = (float) $stock->reserved_qty - $quantity;
            $stock->save();

            // Mark the linked reservation record
            if ($orderId) {
                StockReservation::where('order_id', $orderId)
                    ->where('product_id', $productId)
                    ->where('warehouse_id', $warehouseId)
                    ->where('status', 'active')
                    ->orderBy('id')
                    ->first()
                    ?->update(['status' => $reason === 'used' ? 'used' : 'cancelled']);
            }

            $this->logMovement($productId, $warehouseId, $quantity, 'release', Order::class, $orderId);

            $this->syncProductStatus($productId);

            return $stock->refresh();
        }, 3);
    }

    /**
     * Inner work of a transfer.
     */
    private function _executeTransfer(
        int $productId,
        int $fromWarehouseId,
        int $toWarehouseId,
        float $quantity,
        ?int $transferId = null
    ): void {
        // Lock both rows in deterministic order to prevent deadlocks
        $ids = [$fromWarehouseId, $toWarehouseId];
        sort($ids);
        $lockedStocks = [];
        foreach ($ids as $wid) {
            $lockedStocks[$wid] = $this->getStockForUpdate($productId, $wid);
        }

        $from = $lockedStocks[$fromWarehouseId];
        if ((float) $from->quantity < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => 'Insufficient stock to transfer.',
            ]);
        }
        $from->quantity = (float) $from->quantity - $quantity;
        $from->save();

        $to = $this->getStockForUpdate($productId, $toWarehouseId);
        $to->quantity = (float) $to->quantity + $quantity;
        $to->save();

        $this->logMovement($productId, $fromWarehouseId, $quantity, 'transfer', StockTransfer::class, $transferId);
        $this->logMovement($productId, $toWarehouseId, $quantity, 'in', StockTransfer::class, $transferId);
        $this->syncProductStatus($productId);
    }

    /**
     * Transfer stock between two warehouses atomically.
     */
    public function transferStock(
        int $productId,
        int $fromWarehouseId,
        int $toWarehouseId,
        float $quantity,
        ?int $transferId = null
    ): void {
        $this->ensurePositive($quantity);

        DB::transaction(function () use ($productId, $fromWarehouseId, $toWarehouseId, $quantity, $transferId) {
            $this->_executeTransfer($productId, $fromWarehouseId, $toWarehouseId, $quantity, $transferId);
        }, 3);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  High-level order lifecycle
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Confirm a pending sale order.
     */
    public function confirmOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order = Order::with('items')->lockForUpdate()->findOrFail($order->id);

            if ($order->status !== 'pending' || $order->is_draft) {
                throw ValidationException::withMessages([
                    'status' => 'Only active pending orders can be confirmed. Future orders must become pending first.',
                ]);
            }

            if (! $order->warehouse_id) {
                throw ValidationException::withMessages([
                    'warehouse_id' => 'Order must have a warehouse assigned before confirmation.',
                ]);
            }

            if ($order->type === 'sale') {
                foreach ($order->items as $item) {
                    $this->reserveStock(
                        (int) $item->product_id,
                        (int) $order->warehouse_id,
                        (float) $item->quantity,
                        $order->id
                    );
                }
            }

            $order->update(['status' => 'confirmed', 'updated_by' => auth()->id()]);

            foreach ($order->items as $item) {
                $this->syncProductStatus((int) $item->product_id);
            }
        }, 3);
    }

    /**
     * Cancel an order.
     */
    public function cancelOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order = Order::with(['items'])->lockForUpdate()->findOrFail($order->id);

            if (in_array($order->status, array_merge(Order::inTransitStatuses(), ['delivered', 'cancelled']), true)) {
                throw ValidationException::withMessages([
                    'status' => 'This order cannot be cancelled.',
                ]);
            }

            if (in_array($order->status, ['confirmed', 'processing', 'ready_to_ship'], true)
                && $order->type === 'sale'
                && $order->warehouse_id) {
                foreach ($order->items as $item) {
                    $productId = (int) $item->product_id;
                    $warehouseId = (int) $order->warehouse_id;
                    $qty = (float) $item->quantity;

                    $stock = $this->getStockForUpdate($productId, $warehouseId);
                    $releaseQty = min($qty, (float) $stock->reserved_qty);

                    if ($releaseQty > 0) {
                        $stock->reserved_qty = (float) $stock->reserved_qty - $releaseQty;
                        $stock->save();

                        StockReservation::where('order_id', $order->id)
                            ->where('product_id', $productId)
                            ->where('warehouse_id', $warehouseId)
                            ->where('status', 'active')
                            ->orderBy('id')
                            ->first()
                            ?->update(['status' => 'cancelled']);

                        $this->logMovement($productId, $warehouseId, $releaseQty, 'release', Order::class, $order->id);
                    }
                }
            }

            $order->update(['status' => 'cancelled', 'updated_by' => auth()->id()]);

            $invoice = $order->invoices()->latest()->first();
            if ($invoice && $invoice->status !== 'cancelled') {
                $invoice->update(['status' => 'cancelled']);
            }

            $shipment = $order->shipments()->latest()->first();
            if ($shipment && $shipment->status !== 'cancelled') {
                $shipment->update(['status' => 'cancelled']);
            }

            foreach ($order->items as $item) {
                $this->syncProductStatus((int) $item->product_id);
            }
        }, 3);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Adjustment & Transfer lifecycle
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Apply an approved stock adjustment.
     */
    public function applyAdjustment(InventoryAdjustment $adjustment): void
    {
        DB::transaction(function () use ($adjustment) {
            $adjustment = InventoryAdjustment::with('items')
                ->lockForUpdate()
                ->findOrFail($adjustment->id);

            if ($adjustment->status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => 'Only pending adjustments can be approved.',
                ]);
            }

            foreach ($adjustment->items as $item) {
                $productId = (int) $item->product_id;
                $warehouseId = (int) $adjustment->warehouse_id;
                $newQty = (float) $item->new_qty;

                $stock = $this->getStockForUpdate($productId, $warehouseId);

                if ($stock->reserved_qty > $newQty) {
                    throw ValidationException::withMessages([
                        'quantity' => "New qty for product ID {$productId} is below reserved qty.",
                    ]);
                }

                $diff = $newQty - (float) $stock->quantity;
                $stock->quantity = $newQty;
                $stock->save();

                $this->logMovement(
                    $productId,
                    $warehouseId,
                    abs($diff),
                    'adjustment',
                    InventoryAdjustment::class,
                    $adjustment->id
                );
            }

            $adjustment->update(['status' => 'approved']);

            foreach ($adjustment->items as $item) {
                $this->syncProductStatus((int) $item->product_id);
            }
        }, 3);
    }

    /**
     * Mark a stock transfer as sent and move the transferred quantity into transit.
     */
    public function sendTransfer(StockTransfer $transfer): void
    {
        DB::transaction(function () use ($transfer) {
            $transfer = StockTransfer::with('items')
                ->lockForUpdate()
                ->findOrFail($transfer->id);

            if ($transfer->status !== 'draft') {
                throw ValidationException::withMessages([
                    'status' => 'Only draft transfers can be sent.',
                ]);
            }

            foreach ($transfer->items as $item) {
                $productId   = (int) $item->product_id;
                $warehouseId = (int) $transfer->from_warehouse_id;
                $qty         = (float) $item->quantity;

                $stock = $this->getStockForUpdate($productId, $warehouseId);

                // Validate sufficient physical stock before sending
                if ((float) $stock->quantity < $qty) {
                    throw ValidationException::withMessages([
                        'quantity' => "Insufficient stock for product ID {$productId} in source warehouse.",
                    ]);
                }

                // Deduct from source warehouse quantity immediately on dispatch
                $stock->quantity     = max(0.0, (float) $stock->quantity - $qty);
                $stock->in_transit_qty = (float) $stock->in_transit_qty + $qty;
                $stock->save();

                $this->logMovement($productId, $warehouseId, $qty, 'out', StockTransfer::class, $transfer->id);
                $this->syncProductStatus($productId);
            }

            $transfer->update([
                'status'  => 'sent',
                'sent_at' => now(),
            ]);
        }, 3);
    }

    /**
     * Receive a stock transfer.
     */
    public function receiveTransfer(StockTransfer $transfer): void
    {
        DB::transaction(function () use ($transfer) {
            $transfer = StockTransfer::with('items')
                ->lockForUpdate()
                ->findOrFail($transfer->id);

            if ($transfer->status !== 'sent') {
                throw ValidationException::withMessages([
                    'status' => 'Only sent transfers can be received.',
                ]);
            }

            foreach ($transfer->items as $item) {
                $productId   = (int) $item->product_id;
                $fromWh      = (int) $transfer->from_warehouse_id;
                $toWh        = (int) $transfer->to_warehouse_id;
                $qty         = (float) $item->quantity;

                // Clear in_transit from source (quantity was already deducted in sendTransfer)
                $fromStock = $this->getStockForUpdate($productId, $fromWh);
                $fromStock->in_transit_qty = max(0.0, (float) $fromStock->in_transit_qty - $qty);
                $fromStock->save();

                // Add to destination warehouse
                $toStock = $this->getStockForUpdate($productId, $toWh);
                $toStock->quantity = (float) $toStock->quantity + $qty;
                $toStock->save();

                $this->logMovement($productId, $toWh, $qty, 'in', StockTransfer::class, $transfer->id);
                $this->syncProductStatus($productId);
                $this->syncProductStatus($productId); // sync for both warehouses
            }

            $transfer->update([
                'status'      => 'received',
                'received_at' => now(),
            ]);
        }, 3);
    }

    /**
     * Cancel a stock transfer.
     */
    public function cancelSentTransfer(StockTransfer $transfer): void
    {
        DB::transaction(function () use ($transfer) {
            $transfer = StockTransfer::with('items')
                ->lockForUpdate()
                ->findOrFail($transfer->id);

            if ($transfer->status === 'draft') {
                // Draft: nothing was deducted yet, just cancel
                $transfer->update(['status' => 'cancelled']);
                return;
            }

            if ($transfer->status !== 'sent') {
                throw ValidationException::withMessages([
                    'status' => 'Only draft or sent transfers can be cancelled.',
                ]);
            }

            // Sent: source quantity was deducted in sendTransfer — restore it
            foreach ($transfer->items as $item) {
                $productId   = (int) $item->product_id;
                $warehouseId = (int) $transfer->from_warehouse_id;
                $qty         = (float) $item->quantity;

                $stock = $this->getStockForUpdate($productId, $warehouseId);
                $stock->quantity       = (float) $stock->quantity + $qty;
                $stock->in_transit_qty = max(0.0, (float) $stock->in_transit_qty - $qty);
                $stock->save();

                $this->logMovement($productId, $warehouseId, $qty, 'in', StockTransfer::class, $transfer->id);
                $this->syncProductStatus($productId);
            }

            $transfer->update(['status' => 'cancelled']);
        }, 3);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Revert transitions
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Revert a confirmed/processing sale order back to pending.
     */
    public function revertOrderToPending(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order = Order::with(['items'])->lockForUpdate()->findOrFail($order->id);

            if (! in_array($order->status, ['confirmed', 'processing', 'cancelled', 'ready_to_ship'], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Cannot revert this order to pending.',
                ]);
            }

            if (in_array($order->status, ['confirmed', 'processing', 'ready_to_ship'], true)
                && $order->type === 'sale'
                && $order->warehouse_id) {
                foreach ($order->items as $item) {
                    $productId = (int) $item->product_id;
                    $warehouseId = (int) $order->warehouse_id;
                    $qty = (float) $item->quantity;

                    $stock = $this->getStockForUpdate($productId, $warehouseId);
                    $releaseQty = min($qty, (float) $stock->reserved_qty);

                    if ($releaseQty > 0) {
                        $stock->reserved_qty = (float) $stock->reserved_qty - $releaseQty;
                        $stock->save();

                        StockReservation::where('order_id', $order->id)
                            ->where('product_id', $productId)
                            ->where('warehouse_id', $warehouseId)
                            ->where('status', 'active')
                            ->orderBy('id')
                            ->first()
                            ?->update(['status' => 'cancelled']);

                        $this->logMovement($productId, $warehouseId, $releaseQty, 'release', Order::class, $order->id);
                        $this->syncProductStatus($productId);
                    }
                }
            }

            $order->update(['status' => 'pending', 'updated_by' => auth()->id()]);
        }, 3);
    }

    /**
     * Revert a shipped order back to processing.
     */
    public function revertOrderToProcessing(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order = Order::with(['items'])->lockForUpdate()->findOrFail($order->id);

            if (! in_array($order->status, array_merge(Order::inTransitStatuses(), ['ready_to_ship']), true)) {
                throw ValidationException::withMessages([
                    'status' => 'Only dispatched or ready to ship orders can be reverted.',
                ]);
            }

            $statusWasPhysical = in_array($order->status, Order::inTransitStatuses(), true);
            $revertToStatus = $statusWasPhysical ? 'ready_to_ship' : 'processing';

            if ($statusWasPhysical) {
                if ($order->type === 'sale' && $order->warehouse_id) {
                    foreach ($order->items as $item) {
                        $productId = (int) $item->product_id;
                        $warehouseId = (int) $order->warehouse_id;
                        $qty = (float) $item->quantity;

                        $stock = $this->getStockForUpdate($productId, $warehouseId);
                        $stock->quantity = (float) $stock->quantity + $qty;
                        $stock->reserved_qty = (float) $stock->reserved_qty + $qty;
                        $stock->dispatched_qty = max(0.0, (float) $stock->dispatched_qty - $qty);
                        $stock->save();

                        StockReservation::where('order_id', $order->id)
                            ->where('product_id', $productId)
                            ->where('warehouse_id', $warehouseId)
                            ->update(['status' => 'active']);

                        $this->logMovement($productId, $warehouseId, $qty, 'in', Order::class, $order->id);
                        $this->syncProductStatus($productId);
                    }
                } elseif ($order->type === 'purchase' && $order->warehouse_id) {
                    foreach ($order->items as $item) {
                        $productId = (int) $item->product_id;
                        $warehouseId = (int) $order->warehouse_id;
                        $qty = (float) $item->quantity;

                        $stock = $this->getStockForUpdate($productId, $warehouseId);
                        $stock->quantity = max(0.0, (float) $stock->quantity - $qty);
                        $stock->save();

                        $this->logMovement($productId, $warehouseId, $qty, 'out', Order::class, $order->id);
                        $this->syncProductStatus($productId);
                    }
                }
            }

            $order->update(['status' => $revertToStatus, 'updated_by' => auth()->id()]);

            $shipment = $order->shipments()->first();
            if ($shipment) {
                if ($revertToStatus === 'ready_to_ship') {
                    $shipment->update([
                        'status' => 'pending',
                        'shipped_at' => null,
                    ]);
                } else {
                    $shipment->update([
                        'status' => 'pending',
                        'shipped_at' => null,
                    ]);
                }
            }
        }, 3);
    }

    /**
     * Mark order as ready to ship.
     */
    public function readyToShipOrder(Order $order, ?string $carrierName = null, ?string $trackingNo = null): void
    {
        DB::transaction(function () use ($order, $carrierName, $trackingNo) {
            $order = Order::lockForUpdate()->findOrFail($order->id);

            if ($order->status !== 'processing') {
                throw ValidationException::withMessages([
                    'status' => 'Only processing orders can be marked as ready to ship.',
                ]);
            }

            $order->update(['status' => 'ready_to_ship', 'updated_by' => auth()->id()]);

            $shipment = $order->shipments()->first();
            if (! $shipment) {
                $shipment = new Shipment([
                    'shipment_no' => Shipment::generateShipmentNo(),
                    'order_id' => $order->id,
                ]);
            }
            $shipment->fill([
                'carrier_name' => $carrierName,
                'tracking_no' => $trackingNo,
                'status' => 'pending',
            ]);
            $shipment->save();
        }, 3);
    }

    /**
     * Dispatch the order.
     */
    public function dispatchOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order = Order::with(['items'])->lockForUpdate()->findOrFail($order->id);

            if ($order->status !== 'ready_to_ship') {
                throw ValidationException::withMessages([
                    'status' => 'Only orders in ready to ship status can be dispatched.',
                ]);
            }

            if (! $order->warehouse_id) {
                throw ValidationException::withMessages([
                    'warehouse_id' => 'Order must have a warehouse assigned.',
                ]);
            }

            foreach ($order->items as $item) {
                $productId = (int) $item->product_id;
                $warehouseId = (int) $order->warehouse_id;
                $qty = (float) $item->quantity;

                if ($order->type === 'sale') {
                    $stock = $this->getStockForUpdate($productId, $warehouseId);

                    if ((float) $stock->reserved_qty < $qty) {
                        throw ValidationException::withMessages([
                            'quantity' => "Reserved stock mismatch for product ID {$productId}.",
                        ]);
                    }

                    $product = Product::find($productId);
                    if (! $product?->allow_overselling && (float) $stock->quantity < $qty) {
                        throw ValidationException::withMessages([
                            'quantity' => "Insufficient physical stock for product ID {$productId}.",
                        ]);
                    }

                    $stock->reserved_qty = (float) $stock->reserved_qty - $qty;
                    $stock->quantity = (float) $stock->quantity - $qty;
                    $stock->dispatched_qty = (float) $stock->dispatched_qty + $qty;
                    $stock->save();

                    StockReservation::where('order_id', $order->id)
                        ->where('product_id', $productId)
                        ->where('warehouse_id', $warehouseId)
                        ->where('status', 'active')
                        ->orderBy('id')
                        ->first()
                        ?->update(['status' => 'used']);

                    $this->logMovement($productId, $warehouseId, $qty, 'out', Order::class, $order->id);

                } else {
                    $stock = $this->getStockForUpdate($productId, $warehouseId);
                    $stock->quantity = (float) $stock->quantity + $qty;
                    $stock->save();

                    $this->logMovement($productId, $warehouseId, $qty, 'in', Order::class, $order->id);
                }
            }

            $order->update(['status' => 'dispatched', 'updated_by' => auth()->id()]);

            $shipment = $order->shipments()->first();
            if ($shipment) {
                $shipment->update([
                    'status' => 'in_transit',
                    'shipped_at' => now(),
                ]);
            }

            foreach ($order->items as $item) {
                $this->syncProductStatus((int) $item->product_id);
            }
        }, 3);
    }

    /**
     * Deliver Order.
     */
    public function deliverOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $wasPhysical = $order->type === 'sale'
                && in_array($order->status, Order::inTransitStatuses(), true);

            if ($wasPhysical) {
                $order->loadMissing('items');

                foreach ($order->items as $item) {
                    $productId = (int) $item->product_id;
                    $warehouseId = (int) $order->warehouse_id;
                    $qty = (float) $item->quantity;

                    $stock = $this->getStockForUpdate($productId, $warehouseId);

                    $stock->dispatched_qty = max(0.0, (float) $stock->dispatched_qty - $qty);
                    $stock->save();
                }
            }

            $order->update(['status' => 'delivered', 'updated_by' => auth()->id()]);

            // Advanced Referral Reward Logic
            $party = \App\Modules\Customers\Models\Party::find($order->party_id);
            if ($party && $party->referred_by) {
                // If it's 1, it means the current one is the only delivered order.
                $deliveredCount = Order::where('party_id', $party->id)->where('status', 'delivered')->count();
                if ($deliveredCount === 1) {
                    $alreadyRewarded = DB::table('referral_rewards')->where('referred_id', $party->id)->exists();
                    if (!$alreadyRewarded) {
                        $referrer = \App\Modules\Customers\Models\Party::find($party->referred_by);
                        
                        if ($referrer) {
                            // Calculate referrer's total successful referrals
                            // A successful referral is someone they referred who has at least one delivered order.
                            $successfulReferrals = \App\Modules\Customers\Models\Party::where('referred_by', $referrer->id)
                                ->whereHas('orders', function ($q) {
                                    $q->where('status', 'delivered');
                                })->count();

                            // Find Active Program
                            $activeProgram = ReferralProgram::with('milestones')
                                ->where('is_active', true)
                                ->where(function($q) {
                                    $q->whereNull('start_date')->orWhere('start_date', '<=', now());
                                })
                                ->where(function($q) {
                                    $q->whereNull('end_date')->orWhere('end_date', '>=', now());
                                })->first();

                            $rewardGranted = false;
                            
                            if ($activeProgram) {
                                // Find base milestone (0 = every referral) and specific milestone for current count
                                $baseMilestone = $activeProgram->milestones->where('required_referrals', 0)->first();
                                $specificMilestone = $activeProgram->milestones->where('required_referrals', $successfulReferrals)->first();

                                $milestonesToReward = array_filter([$baseMilestone, $specificMilestone]);

                                foreach ($milestonesToReward as $milestone) {
                                    $rewardAmount = $milestone->reward_type === 'wallet' ? (float) $milestone->reward_value : 0;

                                    // Insert reward record first as 'pending' to allow atomic wallet update
                                    $rewardId = DB::table('referral_rewards')->insertGetId([
                                        'referrer_id'         => $referrer->id,
                                        'referred_id'         => $party->id,
                                        'order_id'            => $order->id,
                                        'referral_program_id' => $activeProgram->id,
                                        'milestone_id'        => $milestone->id,
                                        'reward_type'         => $milestone->reward_type,
                                        'reward_amount'       => $rewardAmount,
                                        'reward_value'        => $milestone->reward_type !== 'wallet' ? $milestone->reward_value : null,
                                        'status'              => 'pending',
                                        'created_at'          => now(),
                                        'updated_at'          => now(),
                                    ]);

                                    if ($milestone->reward_type === 'wallet') {
                                        $referrer->outstanding_balance += $rewardAmount;
                                        $referrer->save();
                                    } elseif ($milestone->reward_type === 'coupon') {
                                        $couponCode = 'REF-' . strtoupper(Str::random(8));
                                        Coupon::create([
                                            'code'        => $couponCode,
                                            'type'        => 'fixed',
                                            'value'       => (float) $milestone->reward_value,
                                            'usage_limit' => 1,
                                            'expiry_date' => now()->addMonths(6), // Referral coupons expire in 6 months
                                            'is_active'   => true,
                                            'status'      => 'active',
                                        ]);
                                    } elseif ($milestone->reward_type === 'product') {
                                        $couponCode = 'GIFT-' . strtoupper(Str::random(8));
                                        Coupon::create([
                                            'code'                => $couponCode,
                                            'type'                => 'percentage',
                                            'value'               => 100.00,
                                            'usage_limit'         => 1,
                                            'expiry_date'         => now()->addMonths(6),
                                            'applicable_products' => [$milestone->reward_value],
                                            'is_active'           => true,
                                            'status'              => 'active',
                                        ]);
                                    }

                                    // Mark as completed only after all side-effects succeed
                                    DB::table('referral_rewards')->where('id', $rewardId)->update([
                                        'status'     => 'completed',
                                        'updated_at' => now(),
                                    ]);

                                    $rewardGranted = true;
                                }
                            }
                        }
                    }
                }
            }

            $shipment = $order->shipments()->first();
            if ($shipment) {
                $shipment->update([
                    'status' => 'delivered',
                    'delivered_at' => now(),
                    'delivered_by' => auth()->user()?->name ?? 'System',
                ]);
            }
        }, 3);
    }

    /**
     * Undo deliverOrder.
     */
    public function revertDeliveredToDispatched(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order = Order::with(['items'])->lockForUpdate()->findOrFail($order->id);

            if ($order->status !== 'delivered') {
                throw ValidationException::withMessages([
                    'status' => 'Only delivered orders can be reverted to dispatched.',
                ]);
            }

            if ($order->type === 'sale' && $order->warehouse_id) {
                $order->loadMissing('items');
                foreach ($order->items as $item) {
                    $productId = (int) $item->product_id;
                    $warehouseId = (int) $order->warehouse_id;
                    $qty = (float) $item->quantity;

                    $stock = $this->getStockForUpdate($productId, $warehouseId);
                    $stock->dispatched_qty = (float) $stock->dispatched_qty + $qty;
                    $stock->save();

                    $this->syncProductStatus($productId);
                }
            }

            $order->update(['status' => 'dispatched', 'updated_by' => auth()->id()]);

            $this->revokeReferralReward($order);

            $shipment = $order->shipments()->first();
            if ($shipment) {
                $shipment->update([
                    'status' => 'in_transit',
                    'delivered_at' => null,
                ]);
            }
        }, 3);
    }

    /**
     * Return a delivered or dispatched order.
     */
    public function returnOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order = Order::with(['items'])->lockForUpdate()->findOrFail($order->id);

            if (! in_array($order->status, ['delivered', 'dispatched', 'shipped'], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Only delivered or dispatched orders can be marked as returned.',
                ]);
            }

            if ($order->type === 'sale' && $order->warehouse_id) {
                // Determine already restocked items via completed RMAs to prevent double-restocking
                $alreadyRestocked = DB::table('order_return_items')
                    ->join('order_returns', 'order_return_items.order_return_id', '=', 'order_returns.id')
                    ->where('order_returns.order_id', $order->id)
                    ->where('order_returns.status', 'completed')
                    ->select('order_return_items.product_id', DB::raw('SUM(order_return_items.restocked_qty + order_return_items.damaged_qty) as total_restocked'))
                    ->groupBy('order_return_items.product_id')
                    ->pluck('total_restocked', 'product_id');

                foreach ($order->items as $item) {
                    $productId = (int) $item->product_id;
                    $warehouseId = (int) $order->warehouse_id;
                    $qty = (float) $item->quantity;

                    $previouslyRestocked = (float) ($alreadyRestocked[$productId] ?? 0);
                    $qtyToRestock = max(0.0, $qty - $previouslyRestocked);

                    $stock = $this->getStockForUpdate($productId, $warehouseId);

                    if ($qtyToRestock > 0) {
                        $stock->quantity = (float) $stock->quantity + $qtyToRestock;
                        $this->logMovement($productId, $warehouseId, $qtyToRestock, 'in', Order::class, $order->id);
                    }

                    if (in_array($order->status, Order::inTransitStatuses(), true)) {
                        $stock->dispatched_qty = max(0.0, (float) $stock->dispatched_qty - $qty);
                    }

                    $stock->save();

                    $this->syncProductStatus($productId);
                }
            }

            $order->update(['status' => 'returned', 'updated_by' => auth()->id()]);

            $this->revokeReferralReward($order);

            $invoice = $order->invoices()->latest()->first();
            if ($invoice && $invoice->status !== 'cancelled') {
                $invoice->update(['status' => 'cancelled']);
            }

            $shipment = $order->shipments()->first();
            if ($shipment) {
                $shipment->update([
                    'status' => 'returned',
                ]);
            }
        }, 3);
    }

    /**
     * Process a return item after Quality Check (QC).
     */
    public function processReturnItem(
        int $productId,
        int $warehouseId,
        float $restockQty,
        float $damageQty,
        ?int $orderReturnId = null
    ): void {
        DB::transaction(function () use ($productId, $warehouseId, $restockQty, $damageQty, $orderReturnId) {
            if ($restockQty > 0 || $damageQty > 0) {
                $stock = $this->getStockForUpdate($productId, $warehouseId);

                if ($restockQty > 0) {
                    $stock->quantity = (float) $stock->quantity + $restockQty;
                    $this->logMovement($productId, $warehouseId, $restockQty, 'in', OrderReturn::class, $orderReturnId);
                }

                if ($damageQty > 0) {
                    $stock->damaged_qty = (float) $stock->damaged_qty + $damageQty;
                    $this->logMovement($productId, $warehouseId, $damageQty, 'damage', OrderReturn::class, $orderReturnId);
                }

                $stock->save();
                $this->syncProductStatus($productId);
            }
        }, 3);
    }

    /**
     * Revoke a referral reward if the order is returned or reverted.
     */
    protected function revokeReferralReward(Order $order): void
    {
        $rewards = DB::table('referral_rewards')
            ->where('order_id', $order->id)
            ->where('status', 'completed')
            ->get();

        foreach ($rewards as $reward) {
            if ($reward->reward_type === 'wallet') {
                $referrer = \App\Modules\Customers\Models\Party::find($reward->referrer_id);
                if ($referrer) {
                    $referrer->outstanding_balance = max(0, $referrer->outstanding_balance - $reward->reward_amount);
                    $referrer->save();
                }
            } elseif (in_array($reward->reward_type, ['coupon', 'product'])) {
                // Deactivate the auto-generated referral/gift coupon so it can no longer be used
                Coupon::where('order_id', $order->id)
                    ->orWhere(function ($q) use ($reward) {
                        // Match by prefix since we don't store the coupon code on the reward row
                        $prefix = $reward->reward_type === 'coupon' ? 'REF-' : 'GIFT-';
                        $q->where('code', 'like', $prefix . '%')
                          ->where('is_active', true)
                          ->where('usage_limit', 1)
                          ->where('used_count', 0);
                    })
                    ->update(['is_active' => false, 'status' => 'inactive']);
            }

            DB::table('referral_rewards')->where('id', $reward->id)->update([
                'status'     => 'revoked',
                'updated_at' => now(),
            ]);
        }
    }
}
