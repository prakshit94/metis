<?php

namespace Database\Seeders;

use App\Modules\Catalog\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            ['name' => 'Rolex', 'slug' => 'rolex'],
            ['name' => 'Sony', 'slug' => 'sony'],
            ['name' => 'Casio', 'slug' => 'casio'],
            ['name' => 'Nike', 'slug' => 'nike'],
            ['name' => 'Ray-Ban', 'slug' => 'ray-ban'],
        ];

        foreach ($brands as $brand) {
            Brand::firstOrCreate(['slug' => $brand['slug']], [
                'name' => $brand['name'],
                'slug' => $brand['slug'],
                'image' => null,
                'logo' => null,
                'status' => 'active',
                'is_active' => true,
            ]);
        }
    }
}
