<?php

namespace App\Services;

use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\Shipment;
use Illuminate\Support\Facades\DB;

class CarrierTrackingService
{
    public function apply(Shipment $shipment, string $status, $occurredAt = null): void
    {
        DB::transaction(function () use ($shipment, $status, $occurredAt) {
            $order = Order::lockForUpdate()->find($shipment->order_id);
            if (! $order || in_array($order->status, ['cancelled', 'returned', 'return_requested'], true)) {
                return;
            }
            $originalStatus = $order->status;
            $shipment = Shipment::lockForUpdate()->findOrFail($shipment->id);
            if ($shipment->status === 'delivered') {
                return;
            }
            if ($order->status === 'ready_to_ship') {
                app(InvoiceService::class)->generateForOrder($order);
                app(InventoryService::class)->dispatchOrder($order);
            }
            $order->refresh();
            if ($status === 'delivered') {
                if ($order->status === 'dispatched') {
                    app(InventoryService::class)->deliverOrder($order);
                } elseif ($order->status !== 'delivered') {
                    return;
                }
                $shipment->update(['status' => 'delivered', 'delivered_at' => $occurredAt ?? now()]);
            } elseif ($status === 'in_transit' && $order->status === 'dispatched') {
                $shipment->update(['status' => 'in_transit', 'shipped_at' => $shipment->shipped_at ?? now()]);
            }
            $order->refresh();
            if ($order->status !== $originalStatus) {
                $order->statusLogs()->create(['status' => $order->status, 'notes' => 'Status synchronized from carrier tracking.', 'changed_by' => auth()->id()]);
            }
        }, 3);
    }
}
