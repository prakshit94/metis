<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Crop;

class CropSeeder extends Seeder
{
    public function run(): void
    {
        $crops = ['Wheat', 'Rice', 'Cotton', 'Sugarcane', 'Maize', 'Soybean', 'Gram', 'Mustard', 'Bajra', 'Jowar'];
        
        foreach ($crops as $crop) {
            Crop::firstOrCreate(['name' => $crop]);
        }
    }
}
