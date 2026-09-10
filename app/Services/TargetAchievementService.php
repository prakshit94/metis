<?php

namespace App\Services;

use App\Models\Target;
use App\Modules\Orders\Models\Invoice;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\Payment;
use App\Modules\Users\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TargetAchievementService
{
    /**
     * Recalculate and persist the achieved_amount for a single Target.
     * Covers all supported metric types. Fires no model events during save
     * to avoid observer loops.
     */
    public function recalculate(Target $target): void
    {
        $achieved = match ($target->metric_type) {
            'sales_revenue'      => $this->calcSalesRevenue($target),
            'orders_count'       => $this->calcOrdersCount($target),
            'invoice_collection' => $this->calcInvoiceCollection($target),
            'payment_collection' => $this->calcPaymentCollection($target),
            'calls_made'         => $this->calcCallsMade($target),
            default              => null,
        };

        if ($achieved === null) {
            return;
        }

        $achieved = round($achieved, 2);

        if (round((float) $target->achieved_amount, 2) !== $achieved) {
            $target->achieved_amount = $achieved;
            // Disable model events during save to prevent infinite observer loops
            Target::withoutEvents(fn () => $target->save());
        }
    }

    // ─── Metric calculators ───────────────────────────────────────────────────

    /** Sum of net_amount (fallback: total_amount) of valid orders. */
    private function calcSalesRevenue(Target $target): float
    {
        $orders = $this->buildOrdersQuery($target)
            ->with('orderReturns') // eager-load to avoid N+1 in lifecycleStatus()
            ->get();

        $total = 0.0;
        foreach ($orders as $order) {
            if (!$this->isExcludedLifecycle($order->lifecycleStatus())) {
                $total += (float) ($order->net_amount ?? $order->total_amount ?? 0);
            }
        }
        return $total;
    }

    /** Count of valid (non-cancelled/returned/future) orders. */
    private function calcOrdersCount(Target $target): float
    {
        $orders = $this->buildOrdersQuery($target)
            ->with('orderReturns')
            ->get();

        $count = 0;
        foreach ($orders as $order) {
            if (!$this->isExcludedLifecycle($order->lifecycleStatus())) {
                $count++;
            }
        }
        return (float) $count;
    }

    /** Sum of invoice net_amount (regardless of payment status) within the date range. */
    private function calcInvoiceCollection(Target $target): float
    {
        $userIds = $this->resolveUserIds($target);
        if (empty($userIds)) {
            return 0.0;
        }

        return (float) Invoice::whereHas('order', fn ($q) => $q->whereIn('created_by', $userIds))
            ->whereBetween('invoice_date', [
                $target->start_date->copy()->startOfDay(),
                $target->end_date->copy()->endOfDay(),
            ])
            ->sum('net_amount');
    }

    /** Sum of completed payment amounts within the date range. */
    private function calcPaymentCollection(Target $target): float
    {
        $userIds = $this->resolveUserIds($target);
        if (empty($userIds)) {
            return 0.0;
        }

        return (float) Payment::where('status', 'completed')
            ->whereHas('order', fn ($q) => $q->whereIn('created_by', $userIds))
            ->whereBetween('payment_date', [
                $target->start_date->copy()->startOfDay(),
                $target->end_date->copy()->endOfDay(),
            ])
            ->sum('amount');
    }

    /**
     * Calls made — summed from CallLog where agent_id matches the target's users.
     */
    private function calcCallsMade(Target $target): float
    {
        $userIds = $this->resolveUserIds($target);
        if (empty($userIds)) {
            return 0.0;
        }

        return (float) \App\Models\CallLog::whereIn('agent_id', $userIds)
            ->whereBetween('created_at', [
                $target->start_date->copy()->startOfDay(),
                $target->end_date->copy()->endOfDay(),
            ])
            ->count();
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function isExcludedLifecycle(string $status): bool
    {
        return in_array($status, ['cancelled', 'returned', 'return_requested', 'future_order']);
    }

    /**
     * Build the base Order query scoped to the target's assignee and date range.
     */
    private function buildOrdersQuery(Target $target)
    {
        $userIds = $this->resolveUserIds($target);

        if (empty($userIds)) {
            // Return a query that yields no results
            return Order::whereRaw('1 = 0');
        }

        return Order::whereIn('created_by', $userIds)
            ->whereBetween('order_date', [
                $target->start_date->copy()->startOfDay(),
                $target->end_date->copy()->endOfDay(),
            ]);
    }

    /**
     * Resolve the user IDs that count toward this target based on targetable type.
     *
     * @return int[]
     */
    public function resolveUserIds(Target $target): array
    {
        if ($target->targetable_type === \App\Modules\Users\Models\Team::class) {
            return DB::table('model_has_roles')
                ->where('team_id', $target->targetable_id)
                ->where('model_type', User::class)
                ->pluck('model_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
        }

        if ($target->targetable_type === \App\Modules\Users\Models\Department::class) {
            return User::where('department_id', $target->targetable_id)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        // User target
        return [(int) $target->targetable_id];
    }

    /**
     * Find all targets overlapping a given date for the given user entities,
     * then recalculate each one. Called by observers on Order/Invoice/Payment events.
     *
     * @param int      $userId
     * @param int|null $departmentId
     * @param int|null $teamId         LOB team ID (from model_has_roles pivot)
     * @param Carbon   $date           The event date to scope targets by
     * @param string[] $metricTypes    Restrict to these metric types (empty = all)
     */
    public function recalculateForUser(
        int $userId,
        ?int $departmentId,
        ?int $teamId,
        Carbon $date,
        array $metricTypes = []
    ): void {
        $entities = [
            ['type' => User::class, 'id' => $userId],
        ];

        if ($departmentId) {
            $entities[] = ['type' => \App\Modules\Users\Models\Department::class, 'id' => $departmentId];
        }

        if ($teamId) {
            $entities[] = ['type' => \App\Modules\Users\Models\Team::class, 'id' => $teamId];
        }

        foreach ($entities as $entity) {
            $query = Target::where('targetable_type', $entity['type'])
                ->where('targetable_id', $entity['id'])
                ->where('start_date', '<=', $date->toDateString())
                ->where('end_date', '>=', $date->toDateString());

            if (!empty($metricTypes)) {
                $query->whereIn('metric_type', $metricTypes);
            }

            foreach ($query->get() as $target) {
                $this->recalculate($target);
            }
        }
    }

    /**
     * Resolve the LOB team ID for a user directly from the pivot table,
     * bypassing Spatie's session-based team context (safe for observers/commands).
     */
    public function resolveLobTeamId(User $user): ?int
    {
        $row = DB::table('model_has_roles')
            ->where('model_type', User::class)
            ->where('model_id', $user->id)
            ->whereNotNull('team_id')
            ->first();

        return $row ? (int) $row->team_id : null;
    }
}
