<?php

namespace App\Modules\Orders\Observers;

use App\Modules\Orders\Models\OrderReturn;
use App\Services\TargetAchievementService;
use Carbon\Carbon;

class OrderReturnObserver
{
    public function __construct(private TargetAchievementService $service) {}

    public function saved(OrderReturn $return): void
    {
        $this->updateTargets($return);
    }

    public function deleted(OrderReturn $return): void
    {
        $this->updateTargets($return);
    }

    private function updateTargets(OrderReturn $return): void
    {
        $return->loadMissing('order.creator');
        $order = $return->order;
        $user = $order?->creator;
        $date = $order?->order_date ?? $order?->created_at;

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
