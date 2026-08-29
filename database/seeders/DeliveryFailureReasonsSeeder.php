<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DeliveryFailureReasonsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $reasons = [
            'Customer unavailable',
            'Customer requested future delivery',
            'Address issue/Incomplete',
            'Out of delivery area/Time limit',
            'Consignee refused to accept',
            'Other',
        ];

        foreach ($reasons as $reason) {
            DB::table('delivery_failure_reasons')->updateOrInsert(
                ['reason' => $reason],
                ['is_active' => true, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
