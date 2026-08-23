<?php

namespace Database\Seeders;

use App\Modules\Catalog\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categoryTree = [
            ['name' => 'Watches', 'slug' => 'watches', 'children' => [
                ['name' => 'Analog Watches', 'slug' => 'analog-watches'],
                ['name' => 'Smart Watches', 'slug' => 'smart-watches'],
            ]],
            ['name' => 'Headphones', 'slug' => 'headphones', 'children' => [
                ['name' => 'Wireless Headphones', 'slug' => 'wireless-headphones'],
                ['name' => 'Wired Earphones', 'slug' => 'wired-earphones'],
            ]],
            ['name' => 'Shoes', 'slug' => 'shoes', 'children' => [
                ['name' => 'Running Shoes', 'slug' => 'running-shoes'],
                ['name' => 'Formal Shoes', 'slug' => 'formal-shoes'],
            ]],
        ];

        foreach ($categoryTree as $category) {
            $parent = Category::firstOrCreate(['slug' => $category['slug']], [
                'name' => $category['name'],
                'image' => 'https://via.placeholder.com/150x150?text=' . urlencode($category['name']),
                'parent_id' => null,
                'status' => 'active',
                'is_active' => true,
            ]);

            if (isset($category['children'])) {
                foreach ($category['children'] as $child) {
                    Category::firstOrCreate(['slug' => $child['slug']], [
                        'name' => $child['name'],
                        'image' => 'https://via.placeholder.com/150x150?text=' . urlencode($child['name']),
                        'parent_id' => $parent->id,
                        'status' => 'active',
                        'is_active' => true,
                    ]);
                }
            }
        }
    }
}
