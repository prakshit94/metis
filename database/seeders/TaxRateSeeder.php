<?php

namespace Database\Seeders;

use App\Modules\Catalog\Models\TaxRate;
use Illuminate\Database\Seeder;

class TaxRateSeeder extends Seeder
{
    public function run(): void
    {
        $taxRates = [
            ['name' => 'GST 0%', 'rate' => 0],
            ['name' => 'GST 5%', 'rate' => 5],
            ['name' => 'GST 12%', 'rate' => 12],
            ['name' => 'GST 18%', 'rate' => 18],
            ['name' => 'GST 28%', 'rate' => 28],
        ];

        foreach ($taxRates as $tax) {
            TaxRate::firstOrCreate(['name' => $tax['name']], $tax);
        }
    }
}
