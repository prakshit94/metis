<?php

namespace App\Modules\Orders\Observers;

use App\Modules\Orders\Models\Payment;
use App\Services\TargetAchievementService;

class PaymentObserver
{
    public function __construct(private TargetAchievementService $service) {}

    public function saved(Payment $payment): void
    {
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
}
