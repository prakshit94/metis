<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        $users = DB::table('users')->pluck('id')->toArray();

        if (empty($users)) {
            return;
        }

        $attendances = [];
        $startDate = Carbon::now()->subDays(7);

        foreach ($users as $userId) {
            for ($i = 0; $i < 7; $i++) {
                $date = $startDate->copy()->addDays($i);

                // Skip Sundays
                if ($date->isSunday()) {
                    continue;
                }

                $status = $faker->randomElement(['Present', 'Present', 'Present', 'Present', 'Absent', 'Half-Day', 'Late']);
                $checkIn = null;
                $checkOut = null;

                if ($status !== 'Absent') {
                    $checkIn = Carbon::createFromTime(
                        $status === 'Late' ? rand(10, 11) : rand(8, 9),
                        rand(0, 59)
                    )->format('H:i:s');

                    if ($status === 'Half-Day') {
                        $checkOut = Carbon::createFromTime(rand(13, 14), rand(0, 59))->format('H:i:s');
                    } else {
                        $checkOut = Carbon::createFromTime(rand(17, 19), rand(0, 59))->format('H:i:s');
                    }
                }

                $attendances[] = [
                    'user_id' => $userId,
                    'date' => $date->format('Y-m-d'),
                    'check_in' => $checkIn,
                    'check_out' => $checkOut,
                    'status' => $status,
                    'notes' => $faker->optional(0.2)->sentence,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('attendances')->insert($attendances);
    }
}
