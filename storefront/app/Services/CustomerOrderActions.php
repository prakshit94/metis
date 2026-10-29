<?php

namespace Storefront\Services;

use App\Modules\Customers\Models\Customer;
use App\Modules\Orders\Controllers\OrderReturnController;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderReturnItem;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Storefront\Models\CustomerAccount;

final class CustomerOrderActions
{
    public function apply(CustomerAccount $account, int $id, array $data): void
    {
        DB::transaction(function () use ($account, $id, $data) {
            $customer = Customer::whereKey($account->customer_id)->lockForUpdate()->firstOrFail();
            abort_unless(CustomerAccount::storefrontEligible()->whereKey($account->id)->lockForUpdate()->exists(), 403);
            $order = Order::where('party_id', $customer->id)->whereKey($id)->lockForUpdate()->firstOrFail();
            if ($order->status !== $data['expected_status'] || ! in_array($data['status'], OrderStatus::customerActions($order->status), true)) {
                throw ValidationException::withMessages(['status' => 'This order has changed or this action is unavailable. Reload your orders.']);
            }
            if ($data['status'] === 'cancelled') {
                app(InventoryService::class)->cancelOrder($order);
            } else {
                // Reuse the existing return workflow so the staff Returns page
                // receives a real pending return and its quantities, not just a status.
                $items = $order->items()->get()->groupBy('product_id')->map(function ($lines, $productId) use ($order) {
                    $requested = OrderReturnItem::where('product_id', $productId)
                        ->whereHas('orderReturn', fn ($query) => $query->where('order_id', $order->id)->where('status', '!=', 'rejected'))
                        ->sum('requested_qty');

                    return ['product_id' => $productId, 'requested_qty' => max(0, (float) $lines->sum('quantity') - (float) $requested)];
                })->filter(fn ($item) => $item['requested_qty'] > 0)->values()->all();
                $request = Request::create('/internal/customer-return', 'POST', [
                    'reason' => mb_substr($data['notes'], 0, 255), 'notes' => $data['notes'], 'items' => $items,
                ], [], [], ['HTTP_ACCEPT' => 'application/json']);
                app(OrderReturnController::class)->store($request, $order);

                return;
            }
            $order->statusLogs()->create(['status' => $data['status'], 'notes' => $data['notes'], 'changed_by' => null]);
        }, 3);
    }
}
