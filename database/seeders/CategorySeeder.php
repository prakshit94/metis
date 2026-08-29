<?php

namespace Database\Seeders;

use App\Modules\Catalog\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Images stored in storage/app/public/categories/ (seeded from agri_default_images_15_categories)
        $categoryTree = [
            [
                'name' => 'Seeds',
                'slug' => 'seeds',
                'image' => 'categories/01_seeds.png',
                'children' => [
                    ['name' => 'Vegetable Seeds',   'slug' => 'vegetable-seeds'],
                    ['name' => 'Flower Seeds',       'slug' => 'flower-seeds'],
                    ['name' => 'Cotton Seeds',       'slug' => 'cotton-seeds'],
                    ['name' => 'Wheat Seeds',        'slug' => 'wheat-seeds'],
                    ['name' => 'Paddy / Rice Seeds', 'slug' => 'paddy-rice-seeds'],
                    ['name' => 'Maize / Corn Seeds', 'slug' => 'maize-corn-seeds'],
                    ['name' => 'Soybean Seeds',      'slug' => 'soybean-seeds'],
                    ['name' => 'Groundnut Seeds',    'slug' => 'groundnut-seeds'],
                    ['name' => 'Sunflower Seeds',    'slug' => 'sunflower-seeds'],
                ],
            ],
            [
                'name' => 'Plants & Saplings',
                'slug' => 'plants-saplings',
                'image' => 'categories/02_plants_saplings.png',
                'children' => [
                    ['name' => 'Fruit Plant Saplings',     'slug' => 'fruit-plant-saplings'],
                    ['name' => 'Vegetable Seedlings',      'slug' => 'vegetable-seedlings'],
                    ['name' => 'Ornamental Plants',        'slug' => 'ornamental-plants'],
                    ['name' => 'Medicinal & Herb Plants',  'slug' => 'medicinal-herb-plants'],
                ],
            ],
            [
                'name' => 'Fertilizers',
                'slug' => 'fertilizers',
                'image' => 'categories/03_fertilizers.png',
                'children' => [
                    ['name' => 'Chemical Fertilizers',  'slug' => 'chemical-fertilizers'],
                    ['name' => 'Organic Fertilizers',   'slug' => 'organic-fertilizers'],
                    ['name' => 'Bio Fertilizers',       'slug' => 'bio-fertilizers'],
                    ['name' => 'Micronutrient Fertilizers', 'slug' => 'micronutrient-fertilizers'],
                    ['name' => 'Foliar Spray Fertilizers',  'slug' => 'foliar-spray-fertilizers'],
                    ['name' => 'Water Soluble Fertilizers', 'slug' => 'water-soluble-fertilizers'],
                ],
            ],
            [
                'name' => 'Pesticides & Crop Protection',
                'slug' => 'pesticides',
                'image' => 'categories/04_pesticides.png',
                'children' => [
                    ['name' => 'Insecticides',      'slug' => 'insecticides'],
                    ['name' => 'Fungicides',         'slug' => 'fungicides'],
                    ['name' => 'Herbicides',         'slug' => 'herbicides'],
                    ['name' => 'Rodenticides',       'slug' => 'rodenticides'],
                    ['name' => 'Weedicides',         'slug' => 'weedicides'],
                    ['name' => 'Nematicides',        'slug' => 'nematicides'],
                    ['name' => 'Bio Pesticides',     'slug' => 'bio-pesticides'],
                    ['name' => 'Plant Growth Regulators', 'slug' => 'plant-growth-regulators'],
                ],
            ],
            [
                'name' => 'Farm Equipment',
                'slug' => 'farm-equipment',
                'image' => 'categories/05_farm_equipment.png',
                'children' => [
                    ['name' => 'Sprayers',           'slug' => 'sprayers'],
                    ['name' => 'Power Tillers',      'slug' => 'power-tillers'],
                    ['name' => 'Seeders & Planters', 'slug' => 'seeders-planters'],
                    ['name' => 'Weeders',            'slug' => 'weeders'],
                ],
            ],
            [
                'name' => 'Agri Tools & Implements',
                'slug' => 'agri-tools',
                'image' => 'categories/06_agri_tools.png',
                'children' => [
                    ['name' => 'Hand Tools',       'slug' => 'hand-tools'],
                    ['name' => 'Digging Tools',    'slug' => 'digging-tools'],
                    ['name' => 'Spraying Tools',   'slug' => 'spraying-tools'],
                    ['name' => 'Garden Tools',     'slug' => 'garden-tools'],
                ],
            ],
            [
                'name' => 'Irrigation & Water Management',
                'slug' => 'irrigation',
                'image' => 'categories/07_irrigation.png',
                'children' => [
                    ['name' => 'Drip Irrigation Systems', 'slug' => 'drip-irrigation'],
                    ['name' => 'Sprinkler Systems',       'slug' => 'sprinkler-systems'],
                    ['name' => 'Water Pumps',             'slug' => 'water-pumps'],
                    ['name' => 'PVC & HDPE Pipes',        'slug' => 'pvc-hdpe-pipes'],
                    ['name' => 'Hose Pipes & Fittings',   'slug' => 'hose-pipes-fittings'],
                ],
            ],
            [
                'name' => 'Grow Bags & Pots',
                'slug' => 'grow-bags-pots',
                'image' => 'categories/08_grow_bags_pots.png',
                'children' => [
                    ['name' => 'Grow Bags',     'slug' => 'grow-bags'],
                    ['name' => 'Plastic Pots',  'slug' => 'plastic-pots'],
                    ['name' => 'Terracotta Pots', 'slug' => 'terracotta-pots'],
                    ['name' => 'Nursery Trays', 'slug' => 'nursery-trays'],
                ],
            ],
            [
                'name' => 'Organic Products',
                'slug' => 'organic-products',
                'image' => 'categories/09_organic_products.png',
                'children' => [
                    ['name' => 'Organic Manure & Compost',  'slug' => 'organic-manure-compost'],
                    ['name' => 'Vermicompost',              'slug' => 'vermicompost'],
                    ['name' => 'Neem-Based Products',       'slug' => 'neem-products'],
                    ['name' => 'Organic Growth Boosters',   'slug' => 'organic-growth-boosters'],
                ],
            ],
            [
                'name' => 'Animal Feed & Nutrition',
                'slug' => 'animal-feed',
                'image' => 'categories/10_animal_feed.png',
                'children' => [
                    ['name' => 'Cattle Feed',     'slug' => 'cattle-feed'],
                    ['name' => 'Poultry Feed',    'slug' => 'poultry-feed'],
                    ['name' => 'Fish Feed',       'slug' => 'fish-feed'],
                    ['name' => 'Mineral Mixture', 'slug' => 'mineral-mixture'],
                    ['name' => 'Silage & Fodder', 'slug' => 'silage-fodder'],
                ],
            ],
            [
                'name' => 'Dairy & Poultry Products',
                'slug' => 'dairy-poultry',
                'image' => 'categories/11_dairy_poultry.png',
                'children' => [
                    ['name' => 'Dairy Equipment',     'slug' => 'dairy-equipment'],
                    ['name' => 'Poultry Equipment',   'slug' => 'poultry-equipment'],
                    ['name' => 'Incubators',          'slug' => 'incubators'],
                    ['name' => 'Milk Testing Equipment', 'slug' => 'milk-testing-equipment'],
                ],
            ],
            [
                'name' => 'Agri Machinery & Attachments',
                'slug' => 'agri-machinery',
                'image' => 'categories/12_agri_machinery.png',
                'children' => [
                    ['name' => 'Tractor Attachments',  'slug' => 'tractor-attachments'],
                    ['name' => 'Cultivators & Rotavators', 'slug' => 'cultivators-rotavators'],
                    ['name' => 'Threshers',            'slug' => 'threshers'],
                    ['name' => 'Mini Tractors',        'slug' => 'mini-tractors'],
                ],
            ],
            [
                'name' => 'Harvesting Tools',
                'slug' => 'harvesting-tools',
                'image' => 'categories/13_harvesting_tools.png',
                'children' => [
                    ['name' => 'Sickles & Scythes',    'slug' => 'sickles-scythes'],
                    ['name' => 'Harvesting Knives',    'slug' => 'harvesting-knives'],
                    ['name' => 'Pruning Shears',       'slug' => 'pruning-shears'],
                    ['name' => 'Brush Cutters',        'slug' => 'brush-cutters'],
                ],
            ],
            [
                'name' => 'Storage & Packaging',
                'slug' => 'storage-packaging',
                'image' => 'categories/14_storage_packaging.png',
                'children' => [
                    ['name' => 'Gunny Bags & Sacks',     'slug' => 'gunny-bags-sacks'],
                    ['name' => 'Grain Storage Bins',     'slug' => 'grain-storage-bins'],
                    ['name' => 'Silpaulin / Tarpaulin',  'slug' => 'silpaulin-tarpaulin'],
                    ['name' => 'Fruit & Vegetable Crates', 'slug' => 'fruit-vegetable-crates'],
                    ['name' => 'Nets & Shade Cloth',     'slug' => 'nets-shade-cloth'],
                ],
            ],
            [
                'name' => 'Other Products',
                'slug' => 'other-products',
                'image' => 'categories/15_other_products.png',
                'children' => [
                    ['name' => 'Safety & PPE Kits',    'slug' => 'safety-ppe-kits'],
                    ['name' => 'Soil Testing Kits',    'slug' => 'soil-testing-kits'],
                    ['name' => 'Crop Support Systems', 'slug' => 'crop-support-systems'],
                    ['name' => 'Agri Accessories',     'slug' => 'agri-accessories'],
                ],
            ],
        ];

        foreach ($categoryTree as $category) {
            $parent = Category::firstOrCreate(['slug' => $category['slug']], [
                'name' => $category['name'],
                'image' => $category['image'],
                'parent_id' => null,
                'status' => 'active',
                'is_active' => true,
            ]);

            if (isset($category['children'])) {
                foreach ($category['children'] as $child) {
                    Category::firstOrCreate(['slug' => $child['slug']], [
                        'name' => $child['name'],
                        // Child inherits parent image as default
                        'image' => $category['image'],
                        'parent_id' => $parent->id,
                        'status' => 'active',
                        'is_active' => true,
                    ]);
                }
            }
        }
    }
}
