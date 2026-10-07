<?php

namespace App\Modules\Orders\Observers;

use App\Modules\Orders\Models\Payment;
use App\Modules\Orders\Models\Order;
use App\Modules\Users\Models\User;
use App\Services\TargetAchievementService;
use Carbon\Carbon;

class PaymentObserver
{
    public function __construct(private TargetAchievementService $service) {}

    public function saved(Payment $payment): void
    {
        if (!$payment->wasRecentlyCreated && $payment->wasChanged(['order_id', 'payment_date'])) {
            $this->recalculatePreviousScope($payment);
        }
        $this->updateTargets($payment);
    }

    public function deleted(Payment $payment): void
    {
        $this->updateTargets($payment);
    }

    public function restored(Payment $payment): void
    {
        $this->updateTargets($payment);
    }

    private function updateTargets(Payment $payment): void
    {
        // Payment belongs to an order; the order's creator is the sales agent
        $payment->loadMissing('order.creator');

        $order = $payment->order;
        if (!$order) {
            return;
        }

        $user = $order->creator;
        if (!$user) {
            return;
        }

        $paymentDate = $payment->payment_date ?? $payment->created_at;
        if (!$paymentDate) {
            return;
        }

        $teamId = $this->service->resolveLobTeamId($user);

        $this->service->recalculateForUser(
            userId:       $user->id,
            departmentId: $user->department_id,
            teamId:       $teamId,
            date:         \Carbon\Carbon::parse($paymentDate),
            metricTypes:  ['payment_collection'],
        );
    }

    private function recalculatePreviousScope(Payment $payment): void
    {
        $order = Order::withTrashed()->find($payment->getRawOriginal('order_id'));
        $user = $order ? User::withTrashed()->find($order->created_by) : null;
        $date = $payment->getRawOriginal('payment_date') ?: $payment->getRawOriginal('created_at');

        if (!$user || !$date) {
            return;
        }

        $this->service->recalculateForUser(
            userId: $user->id,
            departmentId: $user->department_id,
            teamId: $this->service->resolveLobTeamId($user),
            date: Carbon::parse($date),
            metricTypes: ['payment_collection'],
        );
    }
}
