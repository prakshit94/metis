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
            ['name' => 'Kilogram', 'short_name' => 'kg', 'slug' => 'kilogram'],
            ['name' => 'Gram', 'short_name' => 'g', 'slug' => 'gram'],
            ['name' => 'Liter', 'short_name' => 'L', 'slug' => 'liter'],
            ['name' => 'Milliliter', 'short_name' => 'ml', 'slug' => 'milliliter'],
            ['name' => 'Ton', 'short_name' => 't', 'slug' => 'ton'],
            ['name' => 'Bag', 'short_name' => 'bag', 'slug' => 'bag'],
            ['name' => 'Bottle', 'short_name' => 'btl', 'slug' => 'bottle'],
            ['name' => 'Packet', 'short_name' => 'pkt', 'slug' => 'packet'],
        ];

        foreach ($uoms as $uom) {
            UnitOfMeasure::firstOrCreate(['slug' => $uom['slug']], [
                'name' => $uom['name'],
                'short_name' => $uom['short_name'],
                'slug' => $uom['slug'],
                'code' => $uom['short_name'],
                'is_base_unit' => false,
                'status' => 'active',
            ]);
        }
    }
}
