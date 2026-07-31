<?php

namespace Database\Seeders;

use App\Modules\Catalog\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Watches', 'slug' => 'watches'],
            ['name' => 'Headphones', 'slug' => 'headphones'],
            ['name' => 'Clocks', 'slug' => 'clocks'],
            ['name' => 'Shoes', 'slug' => 'shoes'],
            ['name' => 'Sunglasses', 'slug' => 'sunglasses'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(['slug' => $category['slug']], [
                'name' => $category['name'],
                'image' => null,
                'status' => 'active',
                'is_active' => true,
            ]);
        }
    }
}
