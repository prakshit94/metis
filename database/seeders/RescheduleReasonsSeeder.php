<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RescheduleReasonsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $reasons = [
            'Customer Not Reachable',
            'Waiting for Payment Confirmation',
            'Customer Requested Delay',
            'Stock Verification Pending',
            'Other'
        ];

        foreach ($reasons as $reason) {
            DB::table('reschedule_reasons')->updateOrInsert(
                ['reason' => $reason],
                ['is_active' => true, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
