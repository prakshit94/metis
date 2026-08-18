<?php

namespace Database\Seeders;

use App\Modules\Catalog\Models\HsnCode;
use Illuminate\Database\Seeder;

class HsnCodeSeeder extends Seeder
{
    public function run(): void
    {
        $hsnCodes = [
            ['code' => '9101', 'description' => 'Wrist-watches, pocket-watches'],
            ['code' => '8518', 'description' => 'Headphones and earphones'],
            ['code' => '9105', 'description' => 'Other clocks'],
            ['code' => '6403', 'description' => 'Footwear with outer soles of rubber'],
            ['code' => '9004', 'description' => 'Spectacles, goggles and the like'],
        ];

        foreach ($hsnCodes as $hsn) {
            HsnCode::firstOrCreate(['code' => $hsn['code']], [
                'code' => $hsn['code'],
                'description' => $hsn['description'],
                'status' => 'active',
            ]);
        }
    }
}
