<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\HsnCode;
use App\Models\Product;
use App\Models\TaxRate;
use App\Models\UnitOfMeasure;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categoryTree = [
            ['name' => 'Seeds', 'slug' => 'seeds', 'children' => [
                ['name' => 'Cotton Seeds', 'slug' => 'cotton-seeds'],
                ['name' => 'Wheat Seeds', 'slug' => 'wheat-seeds'],
            ]],
            ['name' => 'Fertilizers', 'slug' => 'fertilizers', 'children' => [
                ['name' => 'Organic Fertilizers', 'slug' => 'organic-fertilizers'],
                ['name' => 'Chemical Fertilizers', 'slug' => 'chemical-fertilizers'],
            ]],
            ['name' => 'Crop Protection', 'slug' => 'crop-protection', 'children' => [
                ['name' => 'Pesticides', 'slug' => 'pesticides'],
                ['name' => 'Fungicides', 'slug' => 'fungicides'],
            ]],
            ['name' => 'Farm Machinery', 'slug' => 'machinery', 'children' => [
                ['name' => 'Irrigation Tools', 'slug' => 'irrigation-tools'],
            ]],
        ];

        $categories = collect($categoryTree)->map(function (array $row) {
            $parent = Category::firstOrCreate(['slug' => $row['slug']], [
                'name' => $row['name'],
                'status' => 'active',
            ]);

            foreach ($row['children'] as $child) {
                Category::firstOrCreate(['slug' => $child['slug']], [
                    'name' => $child['name'],
                    'parent_id' => $parent->id,
                    'status' => 'active',
                ]);
            }

            return $parent;
        });

        $brands = collect([
            ['name' => 'UPL Agro', 'slug' => 'upl-agro'],
            ['name' => 'Syngenta Crop', 'slug' => 'syngenta-crop'],
            ['name' => 'Mahyco', 'slug' => 'mahyco'],
        ])->map(fn (array $row) => Brand::firstOrCreate(['slug' => $row['slug']], $row));

        $uoms = collect([
            ['name' => 'Piece', 'short_name' => 'pcs', 'slug' => 'piece'],
            ['name' => 'Kilogram', 'short_name' => 'kg', 'slug' => 'kilogram'],
            ['name' => 'Liter', 'short_name' => 'ltr', 'slug' => 'liter'],
        ])->map(fn (array $row) => UnitOfMeasure::firstOrCreate(['slug' => $row['slug']], $row));

        $taxRates = collect([
            ['name' => 'GST 0%', 'rate' => 0],
            ['name' => 'GST 5%', 'rate' => 5],
            ['name' => 'GST 12%', 'rate' => 12],
            ['name' => 'GST 18%', 'rate' => 18],
        ])->map(fn (array $row) => TaxRate::firstOrCreate(['name' => $row['name']], $row));

        $hsnCodes = collect([
            ['code' => '1209', 'description' => 'Seeds for sowing'],
            ['code' => '3101', 'description' => 'Animal or vegetable fertilizers'],
            ['code' => '3808', 'description' => 'Insecticides, rodenticides, fungicides'],
        ])->map(fn (array $row) => HsnCode::firstOrCreate(['code' => $row['code']], $row));

        $warehouses = collect([
            ['name' => 'Main Warehouse', 'code' => 'MAIN'],
            ['name' => 'Secondary Warehouse', 'code' => 'SEC'],
        ])->map(fn (array $row) => Warehouse::firstOrCreate(['code' => $row['code']], $row));

        $examples = [
            ['name' => 'Mahyco BG-II Cotton Seeds', 'sku' => 'SEED-COT-MHY01', 'category' => 'cotton-seeds', 'price' => 850.00, 'stock' => 500, 'status' => 'published', 'description' => 'High-yielding hybrid cotton seeds.', 'purchase_price' => 620.00, 'mrp' => 990.00, 'grade' => 'A', 'tax' => 5],
            ['name' => 'UPL Saaf Fungicide 1KG', 'sku' => 'PROT-FUN-UPL01', 'category' => 'fungicides', 'price' => 680.00, 'stock' => 120, 'status' => 'published', 'description' => 'Systemic and contact fungicide.', 'purchase_price' => 510.00, 'mrp' => 750.00, 'grade' => 'A', 'tax' => 18],
            ['name' => 'Premium NPK Fertilizer 50KG', 'sku' => 'FERT-NPK-PREM', 'category' => 'chemical-fertilizers', 'price' => 1450.00, 'stock' => 300, 'status' => 'published', 'description' => 'Balanced nitrogen, phosphorus, and potassium formula.', 'purchase_price' => 1100.00, 'mrp' => 1600.00, 'grade' => 'B', 'tax' => 12],
            ['name' => 'Drip Irrigation Inline Pipe 400M', 'sku' => 'MACH-DRIP-PIPE', 'category' => 'irrigation-tools', 'price' => 4200.00, 'stock' => 45, 'status' => 'published', 'description' => 'High durability 16mm inline drip lateral pipe.', 'purchase_price' => 3100.00, 'mrp' => 4800.00, 'grade' => 'A', 'tax' => 18],
        ];

        foreach ($examples as $index => $example) {
            $category = Category::where('slug', $example['category'])->first() ?? $categories->first();
            $brand = $brands[$index % $brands->count()];
            $uom = $uoms[$index % $uoms->count()];
            $taxRate = TaxRate::where('rate', $example['tax'])->first() ?? $taxRates->first();
            $hsn = $hsnCodes[$index % $hsnCodes->count()];
            $warehouse = $warehouses[0];

            $product = Product::firstOrCreate(
                ['sku' => $example['sku']],
                [
                    'name' => $example['name'],
                    'slug' => Str::slug($example['name']),
                    'category_id' => $category?->id,
                    'brand_id' => $brand?->id,
                    'tax_rate_id' => $taxRate?->id,
                    'hsn_code_id' => $hsn?->id,
                    'uom_id' => $uom?->id,
                    'default_warehouse_id' => $warehouse?->id,
                    'purchase_price' => $example['purchase_price'],
                    'mrp' => $example['mrp'],
                    'selling_price' => $example['price'],
                    'stock_quantity' => $example['stock'],
                    'min_stock_level' => 10,
                    'allow_overselling' => false,
                    'overselling_qty' => 0,
                    'manage_stock' => true,
                    'is_sku_enabled' => true,
                    'status' => $example['status'],
                    'is_active' => $example['status'] === 'published',
                    'description' => $example['description'],
                    'grade' => $example['grade'],
                    'default_discount' => 0,
                    'default_discount_type' => 'percent',
                ],
            );

            if ($product->default_warehouse_id && class_exists(\App\Services\InventoryService::class)) {
                try {
                    app(\App\Services\InventoryService::class)->setStock($product->id, $product->default_warehouse_id, (float) $example['stock']);
                } catch (\Exception $e) {
                    // Fail silently if service requirements differ in runtime environments
                }
            }
        }
    }
}