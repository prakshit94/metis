<?php

namespace App\Console\Commands;

use App\Modules\Users\Models\Attendance;
use App\Modules\Users\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceRolloverCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:rollover';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Closes attendance logs at end of day and starts a new log for active sessions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today()->toDateString();

        // Find all attendance records that are still open and are from a previous date
        $openAttendances = Attendance::whereNull('check_out')
            ->where('date', '<', $today)
            ->get();

        $count = $openAttendances->count();
        if ($count === 0) {
            $this->info('No open attendance records found for previous days.');

            return;
        }

        foreach ($openAttendances as $attendance) {
            // Close the previous day's attendance
            $attendance->check_out = '23:59:59';
            $attendance->save();

            // Force user to log in manually the next day by clearing their session
            $user = User::find($attendance->user_id);
            if ($user) {
                // Clear mobile/API tokens
                $user->tokens()->delete();

                // Clear web session
                DB::table('sessions')
                    ->where('user_id', $user->id)
                    ->delete();
            }
        }

        $this->info("Successfully rolled over {$count} attendance records.");
    }
}
