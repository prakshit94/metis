<?php

namespace App\Modules\Orders\Observers;

use App\Modules\Orders\Models\Order;
use App\Modules\Users\Models\User;
use App\Services\TargetAchievementService;
use Carbon\Carbon;

class OrderObserver
{
    public function __construct(private TargetAchievementService $service) {}

    public function saved(Order $order): void
    {
        if (!$order->wasRecentlyCreated && $order->wasChanged(['created_by', 'order_date'])) {
            $this->recalculatePreviousScope($order);
        }
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

    private function recalculatePreviousScope(Order $order): void
    {
        $user = User::withTrashed()->find($order->getRawOriginal('created_by'));
        $date = $order->getRawOriginal('order_date') ?: $order->getRawOriginal('created_at');

        if (!$user || !$date) {
            return;
        }

        $this->service->recalculateForUser(
            userId: $user->id,
            departmentId: $user->department_id,
            teamId: $this->service->resolveLobTeamId($user),
            date: Carbon::parse($date),
            metricTypes: ['sales_revenue', 'orders_count'],
        );
    }
}
