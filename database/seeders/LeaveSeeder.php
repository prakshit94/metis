<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Carbon\Carbon;

class LeaveSeeder extends Seeder
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

        $leaves = [];
        $leaveTypes = ['Sick Leave', 'Casual Leave', 'Paid Leave'];

        for ($i = 0; $i < 15; $i++) {
            $userId = $faker->randomElement($users);
            $start = Carbon::now()->addDays(rand(-10, 10));
            $end = $start->copy()->addDays(rand(0, 3));
            $status = $faker->randomElement(['Pending', 'Approved', 'Rejected']);
            
            $approvedBy = null;
            $approvedAt = null;

            if ($status !== 'Pending') {
                $approvedBy = $users[0]; // Assuming first user is admin
                $approvedAt = Carbon::now();
            }

            $leaves[] = [
                'user_id' => $userId,
                'leave_type' => $faker->randomElement($leaveTypes),
                'start_date' => $start->format('Y-m-d'),
                'end_date' => $end->format('Y-m-d'),
                'reason' => $faker->sentence,
                'status' => $status,
                'approved_by' => $approvedBy,
                'approved_at' => $approvedAt,
                'applied_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('leaves')->insert($leaves);
    }
}
