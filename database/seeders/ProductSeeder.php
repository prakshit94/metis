<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\HsnCode;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\TaxRate;
use App\Modules\Catalog\Models\UnitOfMeasure;
use App\Modules\Catalog\Models\Warehouse;
use App\Modules\Inventory\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'name' => 'Premium Hybrid Cotton Seeds',
                'sku' => 'SEED-COT-001',
                'category_slug' => 'seeds',
                'brand_slug' => 'mahyco',
                'uom_slug' => 'packet',
                'hsn_code' => '1209',
                'tax_rate' => 5,
                'purchase_price' => 500,
                'mrp' => 800,
                'selling_price' => 700,
                'description' => 'High yield hybrid cotton seeds suitable for various soils.',
                'barcode' => '8901111222233',
                'weight' => '1 kg',
                'application_instructions' => 'Sow in well drained soil.',
                'grade' => 'A',
                'attributes' => [
                    ['name' => 'Crop Type', 'type' => 'text', 'value' => 'Cotton', 'color_code' => null],
                    ['name' => 'Season', 'type' => 'text', 'value' => 'Kharif', 'color_code' => null],
                ],
                'batch_tracking' => true,
                'expiry_tracking' => true,
                'allow_overselling' => false,
                'overselling_qty' => 0,
            ],
            [
                'name' => 'Organic NPK Fertilizer',
                'sku' => 'FERT-NPK-001',
                'category_slug' => 'fertilizers',
                'brand_slug' => 'upl-agro',
                'uom_slug' => 'bag',
                'hsn_code' => '3101',
                'tax_rate' => 12,
                'purchase_price' => 1200,
                'mrp' => 1600,
                'selling_price' => 1400,
                'description' => 'Balanced nitrogen, phosphorus, and potassium formula.',
                'barcode' => '8902222333344',
                'weight' => '50 kg',
                'application_instructions' => 'Apply 50kg per acre during vegetative growth.',
                'grade' => 'B',
                'attributes' => [
                    ['name' => 'Formulation', 'type' => 'text', 'value' => 'Granular', 'color_code' => null],
                    ['name' => 'Organic', 'type' => 'text', 'value' => 'Yes', 'color_code' => null],
                ],
                'batch_tracking' => true,
                'expiry_tracking' => false,
                'allow_overselling' => false,
                'overselling_qty' => 0,
            ],
            [
                'name' => 'Systemic Fungicide 1L',
                'sku' => 'CHEM-FUN-001',
                'category_slug' => 'crop-protection',
                'brand_slug' => 'syngenta-crop',
                'uom_slug' => 'liter',
                'hsn_code' => '3808',
                'tax_rate' => 18,
                'purchase_price' => 800,
                'mrp' => 1200,
                'selling_price' => 1000,
                'description' => 'Broad spectrum systemic fungicide for crop protection.',
                'barcode' => '8903333444455',
                'weight' => '1.2 kg',
                'application_instructions' => 'Mix 2ml per liter of water.',
                'grade' => 'A',
                'attributes' => [
                    ['name' => 'Chemical Type', 'type' => 'text', 'value' => 'Fungicide', 'color_code' => null],
                    ['name' => 'Form', 'type' => 'text', 'value' => 'Liquid', 'color_code' => null],
                ],
                'batch_tracking' => true,
                'expiry_tracking' => true,
                'allow_overselling' => false,
                'overselling_qty' => 0,
            ],
            [
                'name' => 'Drip Irrigation Pipe 16mm',
                'sku' => 'MACH-DRIP-001',
                'category_slug' => 'machinery',
                'brand_slug' => 'upl-agro',
                'uom_slug' => 'piece',
                'hsn_code' => '3917',
                'tax_rate' => 18,
                'purchase_price' => 2000,
                'mrp' => 3500,
                'selling_price' => 3000,
                'description' => 'High durability 16mm inline drip lateral pipe.',
                'barcode' => '8904444555566',
                'weight' => '15 kg',
                'application_instructions' => 'Lay along crop rows, spacing as per crop requirement.',
                'grade' => 'A',
                'attributes' => [
                    ['name' => 'Material', 'type' => 'text', 'value' => 'PVC', 'color_code' => null],
                    ['name' => 'Length', 'type' => 'text', 'value' => '400m', 'color_code' => null],
                ],
                'batch_tracking' => false,
                'expiry_tracking' => false,
                'allow_overselling' => false,
                'overselling_qty' => 0,
            ],
        ];

        $warehouse = Warehouse::where('code', 'MAIN-ECOM')->first() ?? Warehouse::first();

        foreach ($products as $item) {
            $category = Category::where('slug', $item['category_slug'])->first() ?? Category::first();
            $brand = Brand::where('slug', $item['brand_slug'])->first() ?? Brand::first();
            $uom = UnitOfMeasure::where('slug', $item['uom_slug'])->first() ?? UnitOfMeasure::first();
            $hsn = HsnCode::where('code', $item['hsn_code'])->first() ?? HsnCode::first();
            $tax = TaxRate::where('rate', $item['tax_rate'])->first() ?? TaxRate::first();
            $supplier = Supplier::inRandomOrder()->first();
            
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
                    'supplier_id' => $supplier?->id,
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
                    'batch_tracking' => $item['batch_tracking'] ?? false,
                    'expiry_tracking' => $item['expiry_tracking'] ?? false,
                    'allow_overselling' => $item['allow_overselling'] ?? false,
                    'overselling_qty' => $item['overselling_qty'] ?? 0,
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

            if ($product->default_warehouse_id && class_exists(\App\Services\InventoryService::class)) {
                try {
                    // Seed some initial stock
                    app(\App\Services\InventoryService::class)->setStock($product->id, $product->default_warehouse_id, 100);
                } catch (\Exception $e) {
                    // Fail silently if service requirements differ in runtime environments
                }
            }
        }
    }
}
