<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LeadSource;

class LeadSourceSeeder extends Seeder
{
    public function run(): void
    {
        $sources = ['Referral', 'Walk-in', 'Social Media', 'Website', 'Advertisement', 'Event', 'Cold Call', 'Other'];
        
        foreach ($sources as $source) {
            LeadSource::firstOrCreate(['name' => $source]);
        }
    }
}
