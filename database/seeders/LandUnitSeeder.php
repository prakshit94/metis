<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LandUnit;

class LandUnitSeeder extends Seeder
{
    public function run(): void
    {
        $landUnits = ['Acre', 'Hectare', 'Bigha', 'Guntha', 'Kanal', 'Marla'];
        
        foreach ($landUnits as $unit) {
            LandUnit::firstOrCreate(['name' => $unit]);
        }
    }
}
