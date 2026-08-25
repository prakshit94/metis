<?php

namespace Database\Seeders;

use App\Modules\Catalog\Models\TaxRate;
use Illuminate\Database\Seeder;

class TaxRateSeeder extends Seeder
{
    public function run(): void
    {
        /*
         * GST slabs for agri-inputs as per Indian GST Council:
         *
         * 0%  — Raw seeds, fresh fruits, vegetables, milk, unprocessed food grains
         * 5%  — Processed food, animal feed, fertilisers, seeds for sowing (most HSN 1209),
         *        agricultural implements (some)
         * 12% — Prepared feed, some packaged processed food, select farm equipment
         * 18% — Pesticides, herbicides, fungicides, insecticides (HSN 3808),
         *        machinery & equipment, drip systems, pumps, irrigation pipes
         * 28% — Generally not applicable for agri inputs; retained for completeness
         */
        $taxRates = [
            ['name' => 'GST 0%',  'rate' => 0.00,  'description' => 'Exempt: fresh produce, raw seeds, unprocessed grains'],
            ['name' => 'GST 5%',  'rate' => 5.00,  'description' => 'Fertilizers, seeds, animal feed, select implements'],
            ['name' => 'GST 12%', 'rate' => 12.00, 'description' => 'Processed agri products, select equipment & packaging'],
            ['name' => 'GST 18%', 'rate' => 18.00, 'description' => 'Pesticides, machinery, irrigation systems, pumps'],
            ['name' => 'GST 28%', 'rate' => 28.00, 'description' => 'Luxury / non-agri items (for completeness)'],
        ];

        foreach ($taxRates as $tax) {
            TaxRate::firstOrCreate(['name' => $tax['name']], [
                'name'      => $tax['name'],
                'rate'      => $tax['rate'],
                'status'    => 'active',
                'is_active' => true,
            ]);
        }
    }
}
