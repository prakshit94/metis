<?php

namespace App\Modules\Orders\Observers;

use App\Modules\Orders\Models\Order;
use App\Services\TargetAchievementService;

class OrderObserver
{
    public function __construct(private TargetAchievementService $service) {}

    public function saved(Order $order): void
    {
        $this->updateTargets($order);
    }

    public function deleted(Order $order): void
    {
        $this->updateTargets($order);
    }

    public function restored(Order $order): void
    {
        $this->updateTargets($order);
    }

    private function updateTargets(Order $order): void
    {
        // Eager-load creator to avoid lazy-load exception / null return
        $order->loadMissing('creator');
        $user = $order->creator;

        if (!$user) {
            return;
        }

        $orderDate = $order->order_date ?? $order->created_at;
        if (!$orderDate) {
            return;
        }

        // Resolve LOB team directly from pivot table — safe in observer context
        // (bypasses Spatie's session-based team scope which returns null here)
        $teamId = $this->service->resolveLobTeamId($user);

        $this->service->recalculateForUser(
            userId:       $user->id,
            departmentId: $user->department_id,
            teamId:       $teamId,
            date:         \Carbon\Carbon::parse($orderDate),
            metricTypes:  ['sales_revenue', 'orders_count'],
        );
    }
}
