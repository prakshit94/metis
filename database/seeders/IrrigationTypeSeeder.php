<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\IrrigationType;

class IrrigationTypeSeeder extends Seeder
{
    public function run(): void
    {
        $irrigationTypes = ['Drip', 'Sprinkler', 'Canal', 'Tube Well', 'Rainfed', 'River Pump'];
        
        foreach ($irrigationTypes as $type) {
            IrrigationType::firstOrCreate(['name' => $type]);
        }
    }
}
