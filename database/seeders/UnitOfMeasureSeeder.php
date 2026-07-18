<?php

namespace Database\Seeders;

use App\Modules\Catalog\Models\UnitOfMeasure;
use Illuminate\Database\Seeder;

class UnitOfMeasureSeeder extends Seeder
{
    public function run(): void
    {
        $uoms = [
            ['name' => 'Piece', 'short_name' => 'pcs', 'slug' => 'piece'],
            ['name' => 'Pair', 'short_name' => 'pair', 'slug' => 'pair'],
        ];

        foreach ($uoms as $uom) {
            UnitOfMeasure::firstOrCreate(['slug' => $uom['slug']], $uom);
        }
    }
}
