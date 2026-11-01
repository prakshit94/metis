<?php

namespace App\Modules\Users\Services;

use App\Modules\Users\Models\Attendance;
use App\Modules\Users\Models\User;
use Illuminate\Support\Facades\DB;

class AutomaticCoRequest
{
    public function forAttendance(Attendance $attendance): void
    {
        if ($attendance->trashed() || ! $attendance->check_in || ! $attendance->check_out
            || ! in_array($attendance->status, ['Present', 'Late', 'Half-Day'], true)
            || $attendance->date->isFuture()
            || $attendance->check_in->format('H:i:s') === $attendance->check_out->format('H:i:s')) {
            return;
        }

        DB::transaction(function () use ($attendance) {
            User::whereKey($attendance->user_id)->lockForUpdate()->firstOrFail();
            $roster = DB::table('employee_rosters')->where('user_id', $attendance->user_id)
                ->where('date', $attendance->date->format('Y-m-d'))->lockForUpdate()->first();
            // A rejected request requires an explicit resubmission; edits cannot reopen decisions.
            if (! $roster || $roster->day_type !== 'Week Off' || $roster->co_status !== null) {
                return;
            }
            $session = Attendance::whereKey($attendance->id)->lockForUpdate()->first();
            if (! $session || ! $session->check_in || ! $session->check_out
                || ! in_array($session->status, ['Present', 'Late', 'Half-Day'], true)
                || $session->check_in->format('H:i:s') === $session->check_out->format('H:i:s')
                || $session->date->format('Y-m-d') !== $roster->date
                || (int) $session->user_id !== (int) $roster->user_id) {
                return;
            }
            $reason = 'Automatically requested after completed attendance on a rostered Week Off.';
            DB::table('employee_rosters')->where('id', $roster->id)->update([
                'co_status' => 'Pending', 'co_reason' => $reason, 'co_requested_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('roster_events')->insert([
                'roster_id' => $roster->id, 'actor_id' => auth()->id(), 'action' => 'Auto requested',
                'details' => json_encode(['attendance_id' => $session->id, 'reason' => $reason], JSON_THROW_ON_ERROR),
                'created_at' => now(),
            ]);
        });
    }
}
