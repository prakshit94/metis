<?php

namespace Database\Seeders;

use App\Models\LandUnit;
use Illuminate\Database\Seeder;

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
