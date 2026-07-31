<?php

namespace Database\Seeders;

use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\HsnCode;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\TaxRate;
use App\Modules\Catalog\Models\UnitOfMeasure;
use App\Modules\Catalog\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'name' => 'Luxury Automatic Watch',
                'sku' => 'WATCH-001',
                'category_slug' => 'watches',
                'brand_slug' => 'rolex',
                'uom_slug' => 'piece',
                'hsn_code' => '9101',
                'tax_rate' => 18,
                'purchase_price' => 5000,
                'mrp' => 8000,
                'selling_price' => 7500,
                'description' => 'Premium luxury watch.',
                'image_source' => 'watch.jpg',
                'barcode' => '8901111222233',
                'weight' => '0.5 kg',
                'application_instructions' => 'Wind manually if not worn for 48 hours.',
                'grade' => 'A',
                'attributes' => [
                    ['name' => 'Color', 'type' => 'color', 'value' => 'Silver', 'color_code' => '#C0C0C0'],
                    ['name' => 'Material', 'type' => 'text', 'value' => 'Stainless Steel', 'color_code' => null],
                ],
            ],
            [
                'name' => 'Noise Cancelling Headphones',
                'sku' => 'HPHONE-001',
                'category_slug' => 'headphones',
                'brand_slug' => 'sony',
                'uom_slug' => 'piece',
                'hsn_code' => '8518',
                'tax_rate' => 18,
                'purchase_price' => 1500,
                'mrp' => 2500,
                'selling_price' => 2000,
                'description' => 'High quality noise cancelling headphones.',
                'image_source' => 'headphone.jpg',
                'barcode' => '8902222333344',
                'weight' => '0.3 kg',
                'application_instructions' => 'Charge fully before first use.',
                'grade' => 'B',
                'attributes' => [
                    ['name' => 'Color', 'type' => 'color', 'value' => 'Black', 'color_code' => '#000000'],
                    ['name' => 'Connectivity', 'type' => 'text', 'value' => 'Bluetooth 5.0', 'color_code' => null],
                ],
            ],
            [
                'name' => 'Vintage Wall Clock',
                'sku' => 'CLOCK-001',
                'category_slug' => 'clocks',
                'brand_slug' => 'casio',
                'uom_slug' => 'piece',
                'hsn_code' => '9105',
                'tax_rate' => 12,
                'purchase_price' => 500,
                'mrp' => 1000,
                'selling_price' => 800,
                'description' => 'Classic vintage wall clock.',
                'barcode' => '8903333444455',
                'weight' => '1.2 kg',
                'application_instructions' => 'Mount on a sturdy wall hook.',
                'grade' => 'C',
                'attributes' => [
                    ['name' => 'Material', 'type' => 'text', 'value' => 'Wood', 'color_code' => null],
                    ['name' => 'Color', 'type' => 'color', 'value' => 'Brown', 'color_code' => '#8B4513'],
                ],
            ],
            [
                'name' => 'Running Shoes',
                'sku' => 'SHOES-001',
                'category_slug' => 'shoes',
                'brand_slug' => 'nike',
                'uom_slug' => 'pair',
                'hsn_code' => '6403',
                'tax_rate' => 12,
                'purchase_price' => 2000,
                'mrp' => 4000,
                'selling_price' => 3500,
                'description' => 'Comfortable sports running shoes.',
                'image_source' => 'shose.jpg',
                'barcode' => '8904444555566',
                'weight' => '0.8 kg',
                'application_instructions' => 'Clean with a damp cloth; do not machine wash.',
                'grade' => 'A',
                'attributes' => [
                    ['name' => 'Size', 'type' => 'text', 'value' => '9', 'color_code' => null],
                    ['name' => 'Color', 'type' => 'color', 'value' => 'Red', 'color_code' => '#FF0000'],
                ],
            ],
            [
                'name' => 'Aviator Sunglasses',
                'sku' => 'SUNGL-001',
                'category_slug' => 'sunglasses',
                'brand_slug' => 'ray-ban',
                'uom_slug' => 'piece',
                'hsn_code' => '9004',
                'tax_rate' => 18,
                'purchase_price' => 1000,
                'mrp' => 1800,
                'selling_price' => 1500,
                'description' => 'Stylish aviator sunglasses.',
                'image_source' => 'sunglasses.jpg',
                'barcode' => '8905555666677',
                'weight' => '0.1 kg',
                'application_instructions' => 'Store in case when not in use.',
                'grade' => 'B',
                'attributes' => [
                    ['name' => 'Frame Color', 'type' => 'color', 'value' => 'Gold', 'color_code' => '#FFD700'],
                    ['name' => 'Lens Color', 'type' => 'color', 'value' => 'Green', 'color_code' => '#008000'],
                ],
            ],
        ];

        $warehouse = Warehouse::where('code', 'MAIN-ECOM')->first();

        foreach ($products as $item) {
            $category = Category::where('slug', $item['category_slug'])->first();
            $brand = Brand::where('slug', $item['brand_slug'])->first();
            $uom = UnitOfMeasure::where('slug', $item['uom_slug'])->first();
            $hsn = HsnCode::where('code', $item['hsn_code'])->first();
            $tax = TaxRate::where('rate', $item['tax_rate'])->first();
            
            $imagePath = null;
            if (isset($item['image_source'])) {
                $sourcePath = base_path('database/seeders/images/products/' . $item['image_source']);
                if (file_exists($sourcePath)) {
                    $productsDir = storage_path('app/public/products');
                    if (!is_dir($productsDir)) {
                        mkdir($productsDir, 0755, true);
                    }
                    $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);
                    $filename = Str::slug($item['sku']) . '-' . time() . '.' . $extension;
                    copy($sourcePath, $productsDir . '/' . $filename);
                    $imagePath = 'products/' . $filename;
                }
            }

            $product = Product::firstOrCreate(
                ['sku' => $item['sku']],
                [
                    'name' => $item['name'],
                    'slug' => Str::slug($item['name']),
                    'category_id' => $category?->id,
                    'brand_id' => $brand?->id,
                    'tax_rate_id' => $tax?->id,
                    'hsn_code_id' => $hsn?->id,
                    'uom_id' => $uom?->id,
                    'default_warehouse_id' => $warehouse?->id,
                    'purchase_price' => $item['purchase_price'],
                    'mrp' => $item['mrp'],
                    'selling_price' => $item['selling_price'],
                    'min_stock_level' => 5,
                    'manage_stock' => true,
                    'is_sku_enabled' => true,
                    'status' => 'published',
                    'is_active' => true,
                    'description' => $item['description'],
                    'image_path' => $imagePath,
                    'barcode' => $item['barcode'] ?? null,
                    'weight' => $item['weight'] ?? null,
                    'application_instructions' => $item['application_instructions'] ?? null,
                    'grade' => $item['grade'] ?? null,
                    'default_discount' => 0,
                    'default_discount_type' => 'percent',
                ]
            );

            if (isset($item['attributes'])) {
                $attributeValueIds = [];
                foreach ($item['attributes'] as $attrData) {
                    $attribute = \App\Modules\Catalog\Models\ProductAttribute::firstOrCreate(
                        ['name' => $attrData['name']],
                        ['type' => $attrData['type'], 'is_filterable' => true, 'status' => 'active']
                    );
                    $attributeValue = \App\Modules\Catalog\Models\ProductAttributeValue::firstOrCreate(
                        ['product_attribute_id' => $attribute->id, 'value' => $attrData['value']],
                        ['color_code' => $attrData['color_code'] ?? null, 'status' => 'active']
                    );
                    $attributeValueIds[] = $attributeValue->id;
                }
                $product->attributeValues()->sync($attributeValueIds);
            }
        }
    }
}
