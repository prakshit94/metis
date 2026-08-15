<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Users\Models\User;
use App\Modules\Users\Models\LeaveBalance;

class LeaveBalanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        $defaultBalances = [
            ['leave_type' => 'Sick', 'total_leaves' => 6, 'is_active' => true],
            ['leave_type' => 'Casual', 'total_leaves' => 6, 'is_active' => true],
            ['leave_type' => 'Annual', 'total_leaves' => 12, 'is_active' => true],
            ['leave_type' => 'Paternity', 'total_leaves' => 5, 'is_active' => false],
            ['leave_type' => 'Maternity', 'total_leaves' => 180, 'is_active' => false],
            ['leave_type' => 'Marriage', 'total_leaves' => 5, 'is_active' => false],
            ['leave_type' => 'Election', 'total_leaves' => 1, 'is_active' => true],
            ['leave_type' => 'Unpaid', 'total_leaves' => 365, 'is_active' => true],
        ];

        foreach ($users as $user) {
            foreach ($defaultBalances as $balance) {
                LeaveBalance::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'leave_type' => $balance['leave_type']
                    ],
                    [
                        'total_leaves' => $balance['total_leaves'],
                        'used_leaves' => 0,
                        'is_active' => $balance['is_active']
                    ]
                );
            }
        }
    }
}
