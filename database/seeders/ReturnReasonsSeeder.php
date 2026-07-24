<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReturnReasonsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $reasons = [
            'Damaged in transit',
            'Defective product',
            'Wrong item received',
            'No longer needed',
            'Other'
        ];

        foreach ($reasons as $reason) {
            DB::table('return_reasons')->updateOrInsert(
                ['reason' => $reason],
                ['is_active' => true, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
