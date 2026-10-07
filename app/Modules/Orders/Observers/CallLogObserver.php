<?php

namespace App\Modules\Orders\Observers;

use App\Models\CallLog;
use App\Modules\Users\Models\User;
use App\Services\TargetAchievementService;
use Carbon\Carbon;

class CallLogObserver
{
    public function __construct(private TargetAchievementService $service) {}

    public function saved(CallLog $callLog): void
    {
        if (!$callLog->wasRecentlyCreated && $callLog->wasChanged(['agent_id', 'created_at'])) {
            $this->recalculatePreviousScope($callLog);
        }
        $this->updateTargets($callLog);
    }

    public function deleted(CallLog $callLog): void
    {
        $this->updateTargets($callLog);
    }

    public function restored(CallLog $callLog): void
    {
        $this->updateTargets($callLog);
    }

    private function updateTargets(CallLog $callLog): void
    {
        $user = User::find($callLog->agent_id);
        $date = $callLog->created_at;

        if (!$user || !$date) {
            return;
        }

        $this->service->recalculateForUser(
            userId: $user->id,
            departmentId: $user->department_id,
            teamId: $this->service->resolveLobTeamId($user),
            date: Carbon::parse($date),
            metricTypes: ['calls_made'],
        );
    }

    private function recalculatePreviousScope(CallLog $callLog): void
    {
        $user = User::withTrashed()->find($callLog->getRawOriginal('agent_id'));
        $date = $callLog->getRawOriginal('created_at');

        if (!$user || !$date) {
            return;
        }

        $this->service->recalculateForUser(
            userId: $user->id,
            departmentId: $user->department_id,
            teamId: $this->service->resolveLobTeamId($user),
            date: Carbon::parse($date),
            metricTypes: ['calls_made'],
        );
    }
}
