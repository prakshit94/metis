<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ReferralProgram;
use App\Models\ReferralProgramMilestone;

class ReferralProgramSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Standard Referral Program (Permanent)
        $standard = ReferralProgram::create([
            'name' => 'Standard Referral Program',
            'start_date' => null,
            'end_date' => null,
            'is_active' => true,
        ]);

        $standard->milestones()->createMany([
            [
                'required_referrals' => 0, // 0 = Every successful referral gets this!
                'reward_type' => 'wallet',
                'reward_value' => '100',
            ],
            [
                'required_referrals' => 5,
                'reward_type' => 'coupon',
                'reward_value' => '500', // Rs 500 fixed discount
            ],
            [
                'required_referrals' => 10,
                'reward_type' => 'product',
                'reward_value' => '2', // Free product with ID 2
            ]
        ]);

        // 2. Summer Mega Drive (Time-bound, Inactive)
        $summer = ReferralProgram::create([
            'name' => 'Summer Mega Drive 2026',
            'start_date' => '2026-06-01',
            'end_date' => '2026-08-31',
            'is_active' => false,
        ]);

        $summer->milestones()->createMany([
            [
                'required_referrals' => 1,
                'reward_type' => 'wallet',
                'reward_value' => '250', // Higher reward for summer
            ],
            [
                'required_referrals' => 3,
                'reward_type' => 'product',
                'reward_value' => '5', // Free product ID 5
            ]
        ]);
    }
}
