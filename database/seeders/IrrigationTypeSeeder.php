<?php

namespace Database\Seeders;

use App\Models\IrrigationType;
use Illuminate\Database\Seeder;

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
