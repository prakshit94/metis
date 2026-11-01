<?php

namespace App\Services;

use App\Modules\Inventory\Models\InventoryAdjustment;
use App\Modules\Inventory\Models\StockTransfer;
use App\Modules\Orders\Models\CreditNote;
use App\Modules\Orders\Models\Invoice;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderReturn;
use App\Modules\Orders\Models\Payment;
use App\Modules\Orders\Models\Refund;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/** Route middleware applied after binding; list queries use RecordVisibility separately. */
class EnsureRecordVisibility
{
    public function assertVisible(Model $record): void
    {
        $visibility = app(RecordVisibility::class);
        if ($record instanceof Order) {
            $visibility->assertOrder($record->id);
        } elseif ($record instanceof OrderReturn) {
            $visibility->assertOrder($record->order_id);
            $order = Order::findOrFail($record->order_id);
            if ($state = auth()->user()?->lob_state_name) {
                abort_unless($order->shipping_state === $state, 404);
            }
            if (! $visibility->globalViewer()) {
                $status = $order->status === 'pending_confirmation' ? 'pending' : $order->status;
                abort_unless(auth()->user()?->can('orders.view.'.$status), 404);
            }
        } elseif ($record instanceof StockTransfer) {
            if ($state = auth()->user()?->lob_state_name) {
                abort_unless($record->fromWarehouse?->state === $state || $record->toWarehouse?->state === $state, 404);
            }
        } elseif ($record instanceof InventoryAdjustment) {
            $visibility->assertWarehouse($record->warehouse_id);
        } elseif ($record instanceof CreditNote) {
            if ($visibility->globalViewer()) {
                return;
            }
            $orderId = $record->invoice?->order_id ?? $record->orderReturn?->order_id;
            abort_unless($orderId, 404);
            $visibility->assertOrder($orderId);
        } elseif ($record->getAttribute('order_id')) {
            $visibility->assertOrder($record->order_id);
        }
    }

    public function handle(Request $request, \Closure $next)
    {
        $models = ['OrderController' => Order::class, 'OrderReturnController' => OrderReturn::class,
            'PaymentController' => Payment::class, 'InvoiceController' => Invoice::class,
            'RefundController' => Refund::class, 'CreditNoteController' => CreditNote::class,
            'StockTransferController' => StockTransfer::class, 'InventoryAdjustmentController' => InventoryAdjustment::class];
        $class = $models[class_basename($request->route()->getControllerClass())] ?? null;
        foreach ($request->route()->parameters() as $name => $value) {
            if ($value instanceof Model) {
                $this->assertVisible($value);
            } elseif ($class && in_array($name, ['order', 'return', 'orderReturn', 'payment', 'invoice', 'refund', 'stockTransfer', 'inventoryAdjustment', 'creditNote', 'id'], true)) {
                $this->assertVisible($class::findOrFail($value));
            }
        }
        foreach (['ids', 'order_ids'] as $key) {
            $ids = $request->input($key);
            if (! is_array($ids) || ! $ids || count(array_filter($ids, fn ($id) => ! is_scalar($id))) > 0) {
                continue;
            }
            $selectedClass = $key === 'order_ids' ? Order::class : $class;
            if ($selectedClass) {
                foreach ($selectedClass::whereIn('id', $ids)->get() as $record) {
                    $this->assertVisible($record);
                }
            }
        }
        foreach (['order_id' => Order::class, 'invoice_id' => Invoice::class, 'order_return_id' => OrderReturn::class] as $key => $inputClass) {
            if ($request->filled($key) && is_scalar($request->input($key))) {
                $this->assertVisible($inputClass::findOrFail($request->input($key)));
            }
        }
        foreach (['warehouse_id', 'from_warehouse_id', 'to_warehouse_id'] as $field) {
            if ($request->filled($field) && is_scalar($request->input($field))) {
                app(RecordVisibility::class)->assertWarehouse((int) $request->input($field));
            }
        }

        return $next($request);
    }
}
