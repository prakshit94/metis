<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\HsnCode;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
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
            ['name' => 'Electronics', 'slug' => 'electronics', 'children' => [
                ['name' => 'Phones', 'slug' => 'phones'],
                ['name' => 'Computers', 'slug' => 'computers'],
            ]],
            ['name' => 'Clothing', 'slug' => 'clothing', 'children' => [
                ['name' => 'Men', 'slug' => 'clothing-men'],
                ['name' => 'Women', 'slug' => 'clothing-women'],
            ]],
            ['name' => 'Books', 'slug' => 'books', 'children' => [
                ['name' => 'Technical', 'slug' => 'books-technical'],
            ]],
            ['name' => 'Home & Garden', 'slug' => 'home', 'children' => [
                ['name' => 'Garden Tools', 'slug' => 'garden-tools'],
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
            ['name' => 'Metis Select', 'slug' => 'metis-select'],
            ['name' => 'Northline', 'slug' => 'northline'],
            ['name' => 'Apex', 'slug' => 'apex'],
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
            ['code' => '1001', 'description' => 'General merchandise'],
            ['code' => '2002', 'description' => 'Apparel and textiles'],
            ['code' => '3003', 'description' => 'Books and stationery'],
        ])->map(fn (array $row) => HsnCode::firstOrCreate(['code' => $row['code']], $row));

        $warehouses = collect([
            ['name' => 'Main Warehouse', 'code' => 'MAIN'],
            ['name' => 'Secondary Warehouse', 'code' => 'SEC'],
        ])->map(fn (array $row) => Warehouse::firstOrCreate(['code' => $row['code']], $row));

        $attributes = collect([
            ['name' => 'Color', 'type' => 'color', 'values' => [
                ['value' => 'Red', 'color_code' => '#ef4444'],
                ['value' => 'Blue', 'color_code' => '#3b82f6'],
            ]],
            ['name' => 'Size', 'type' => 'text', 'values' => [
                ['value' => 'S'],
                ['value' => 'M'],
                ['value' => 'L'],
            ]],
        ])->map(function (array $row) {
            $attribute = ProductAttribute::firstOrCreate(
                ['name' => $row['name']],
                ['type' => $row['type'], 'status' => 'active'],
            );

            foreach ($row['values'] as $valueRow) {
                ProductAttributeValue::firstOrCreate(
                    [
                        'product_attribute_id' => $attribute->id,
                        'value' => $valueRow['value'],
                    ],
                    [
                        'color_code' => $valueRow['color_code'] ?? null,
                        'status' => 'active',
                    ],
                );
            }

            return $attribute;
        });

        $examples = [
            ['name' => 'iPhone 14 Pro', 'sku' => 'IPHONE14-PRO', 'category' => 'electronics', 'price' => 999.99, 'stock' => 45, 'status' => 'published', 'description' => 'Latest flagship smartphone', 'purchase_price' => 799.99, 'mrp' => 1099.99, 'grade' => 'A'],
            ['name' => 'Cotton T-Shirt', 'sku' => 'TSHIRT-COTTON', 'category' => 'clothing', 'price' => 24.99, 'stock' => 156, 'status' => 'published', 'description' => 'Soft cotton tee', 'purchase_price' => 11.99, 'mrp' => 29.99, 'grade' => 'B'],
            ['name' => 'JavaScript Guide', 'sku' => 'BOOK-JS-GUIDE', 'category' => 'books', 'price' => 39.99, 'stock' => 8, 'status' => 'draft', 'description' => 'Programming reference', 'purchase_price' => 18.99, 'mrp' => 44.99, 'grade' => 'C'],
            ['name' => 'Garden Tool Set', 'sku' => 'GARDEN-TOOLS', 'category' => 'home', 'price' => 89.99, 'stock' => 0, 'status' => 'pending', 'description' => 'All-in-one garden set', 'purchase_price' => 42.99, 'mrp' => 99.99, 'grade' => 'D'],
        ];

        foreach ($examples as $index => $example) {
            $category = $categories->firstWhere('slug', $example['category']);
            $brand = $brands[$index % $brands->count()];
            $uom = $uoms[0];
            $taxRate = $taxRates[$index % $taxRates->count()];
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
                    'purchase_price' => $example['purchase_price'] ?? round($example['price'] * 0.7, 2),
                    'mrp' => $example['mrp'] ?? round($example['price'] * 1.15, 2),
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
                    'grade' => $example['grade'] ?? null,
                    'default_discount' => 0,
                    'default_discount_type' => 'percent',
                ],
            );

            if ($product->default_warehouse_id) {
                app(\App\Services\InventoryService::class)->setStock($product->id, $product->default_warehouse_id, (float) $example['stock']);
            }
        }
    }
}
