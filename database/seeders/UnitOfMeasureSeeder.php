<?php

namespace Database\Seeders;

use App\Modules\Catalog\Models\UnitOfMeasure;
use Illuminate\Database\Seeder;

class UnitOfMeasureSeeder extends Seeder
{
    public function run(): void
    {
        $uoms = [
            // Weight - dry goods (seeds, fertilizers, grain)
            ['name' => 'Gram',          'short_name' => 'g',      'slug' => 'gram',          'is_base_unit' => false],
            ['name' => 'Kilogram',      'short_name' => 'kg',     'slug' => 'kilogram',      'is_base_unit' => true],
            ['name' => 'Quintal',       'short_name' => 'qtl',    'slug' => 'quintal',       'is_base_unit' => false],
            ['name' => 'Metric Tonne',  'short_name' => 'MT',     'slug' => 'metric-tonne',  'is_base_unit' => false],

            // Volume - liquid products (pesticides, fertilizers, irrigation)
            ['name' => 'Milliliter',    'short_name' => 'ml',     'slug' => 'milliliter',    'is_base_unit' => false],
            ['name' => 'Liter',         'short_name' => 'L',      'slug' => 'liter',         'is_base_unit' => true],

            // Count / Packaging units
            ['name' => 'Piece',         'short_name' => 'pcs',    'slug' => 'piece',         'is_base_unit' => false],
            ['name' => 'Packet',        'short_name' => 'pkt',    'slug' => 'packet',        'is_base_unit' => false],
            ['name' => 'Bag',           'short_name' => 'bag',    'slug' => 'bag',           'is_base_unit' => false],
            ['name' => 'Bottle',        'short_name' => 'btl',    'slug' => 'bottle',        'is_base_unit' => false],
            ['name' => 'Can',           'short_name' => 'can',    'slug' => 'can',           'is_base_unit' => false],
            ['name' => 'Drum',          'short_name' => 'drum',   'slug' => 'drum',          'is_base_unit' => false],
            ['name' => 'Set',           'short_name' => 'set',    'slug' => 'set',           'is_base_unit' => false],
            ['name' => 'Box',           'short_name' => 'box',    'slug' => 'box',           'is_base_unit' => false],
            ['name' => 'Roll',          'short_name' => 'roll',   'slug' => 'roll',          'is_base_unit' => false],
            ['name' => 'Bundle',        'short_name' => 'bndl',   'slug' => 'bundle',        'is_base_unit' => false],
            ['name' => 'Dozen',         'short_name' => 'doz',    'slug' => 'dozen',         'is_base_unit' => false],
            ['name' => 'Pair',          'short_name' => 'pair',   'slug' => 'pair',          'is_base_unit' => false],

            // Area / Land measurement (for irrigation, tarpaulin, shade nets)
            ['name' => 'Square Meter',  'short_name' => 'sqm',    'slug' => 'square-meter',  'is_base_unit' => false],
            ['name' => 'Meter',         'short_name' => 'm',      'slug' => 'meter',         'is_base_unit' => false],
            ['name' => 'Acre',          'short_name' => 'acre',   'slug' => 'acre',          'is_base_unit' => false],
        ];

        foreach ($uoms as $uom) {
            UnitOfMeasure::firstOrCreate(['slug' => $uom['slug']], [
                'name' => $uom['name'],
                'short_name' => $uom['short_name'],
                'slug' => $uom['slug'],
                'code' => $uom['short_name'],
                'is_base_unit' => $uom['is_base_unit'],
                'status' => 'active',
            ]);
        }
    }
}
