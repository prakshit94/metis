<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\HsnCode;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductAttribute;
use App\Modules\Catalog\Models\ProductAttributeValue;
use App\Modules\Catalog\Models\TaxRate;
use App\Modules\Catalog\Models\UnitOfMeasure;
use App\Modules\Catalog\Models\Warehouse;
use App\Modules\Inventory\Models\Supplier;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            // ── Seeds ──────────────────────────────────────────────────────
            [
                'name' => 'Premium Hybrid Cotton Seeds (450g)',
                'sku' => 'SEED-COT-001',
                'category_slug' => 'cotton-seeds',
                'brand_slug' => 'mahyco',
                'uom_slug' => 'gram',
                'hsn_code' => '1209.99',
                'tax_rate' => 5,
                'purchase_price' => 500,
                'mrp' => 850,
                'selling_price' => 720,
                'description' => 'High-yield BG-II hybrid cotton seeds with bollworm resistance.',
                'barcode' => '8901111222233',
                'weight_g' => 450,
                'length_cm' => 15,
                'width_cm' => 10,
                'height_cm' => 5,
                'application_instructions' => 'Sow 1 packet/acre in well-drained loamy soil. Row spacing 90×60 cm.',
                'grade' => 'A',
                'image_source' => '01_seeds.png',
                'batch_tracking' => false,
                'expiry_tracking' => true,
                'allow_overselling' => false,
                'overselling_qty' => 0,
                'attributes' => [
                    ['name' => 'Crop Type',            'type' => 'select', 'value' => 'Kharif'],
                    ['name' => 'Hybrid / Open Pollinated', 'type' => 'select', 'value' => 'Hybrid'],
                ],
            ],
            [
                'name' => 'Wheat Seeds HD-2967 (5 kg)',
                'sku' => 'SEED-WHT-001',
                'category_slug' => 'wheat-seeds',
                'brand_slug' => 'nuziveedu-seeds',
                'uom_slug' => 'kilogram',
                'hsn_code' => '1209.99',
                'tax_rate' => 5,
                'purchase_price' => 180,
                'mrp' => 280,
                'selling_price' => 240,
                'description' => 'HD-2967 high-yielding, rust-resistant wheat variety for Rabi season.',
                'barcode' => '8901111222234',
                'weight_g' => 5000,
                'length_cm' => 40,
                'width_cm' => 30,
                'height_cm' => 10,
                'application_instructions' => 'Sow 100 kg/acre. Germination in 5–6 days. Irrigate 5–6 times.',
                'grade' => 'A',
                'image_source' => '01_seeds.png',
                'batch_tracking' => false,
                'expiry_tracking' => true,
                'allow_overselling' => false,
                'overselling_qty' => 0,
                'attributes' => [
                    ['name' => 'Crop Type', 'type' => 'select', 'value' => 'Rabi'],
                    ['name' => 'Maturity Days', 'type' => 'text', 'value' => '120–130 days'],
                ],
            ],

            // ── Fertilizers ────────────────────────────────────────────────
            [
                'name' => 'IFFCO Urea 45 kg Bag',
                'sku' => 'FERT-UREA-001',
                'category_slug' => 'chemical-fertilizers',
                'brand_slug' => 'iffco',
                'uom_slug' => 'bag',
                'hsn_code' => '3102',
                'tax_rate' => 5,
                'purchase_price' => 240,
                'mrp' => 280,
                'selling_price' => 266,
                'description' => 'Granular urea 46% N. India\'s most used nitrogenous fertilizer.',
                'barcode' => '8902222333344',
                'weight_g' => 45000,
                'length_cm' => 80,
                'width_cm' => 50,
                'height_cm' => 15,
                'application_instructions' => 'Apply 65 kg/acre as top dressing at vegetative stage. Avoid broadcasting before rain.',
                'grade' => 'A',
                'image_source' => '03_fertilizers.png',
                'batch_tracking' => true,
                'expiry_tracking' => false,
                'allow_overselling' => false,
                'overselling_qty' => 0,
                'attributes' => [
                    ['name' => 'Fertilizer Type',    'type' => 'select', 'value' => 'Inorganic / Chemical'],
                    ['name' => 'N-P-K Ratio',        'type' => 'text',   'value' => '46-0-0'],
                    ['name' => 'Application Method', 'type' => 'select', 'value' => 'Top Dressing'],
                ],
            ],
            [
                'name' => 'Organic NPK Fertilizer Granules 50 kg',
                'sku' => 'FERT-NPK-001',
                'category_slug' => 'organic-fertilizers',
                'brand_slug' => 'anandi-organics',
                'uom_slug' => 'bag',
                'hsn_code' => '3101',
                'tax_rate' => 0,
                'purchase_price' => 1200,
                'mrp' => 1600,
                'selling_price' => 1400,
                'description' => 'Balanced organic NPK formula enriched with micronutrients.',
                'barcode' => '8902222333345',
                'weight_g' => 50000,
                'application_instructions' => 'Apply 50 kg/acre before sowing as basal dose.',
                'grade' => 'B',
                'image_source' => '03_fertilizers.png',
                'batch_tracking' => false,
                'expiry_tracking' => false,
                'allow_overselling' => false,
                'overselling_qty' => 0,
                'attributes' => [
                    ['name' => 'Fertilizer Type',    'type' => 'select', 'value' => 'Organic'],
                    ['name' => 'N-P-K Ratio',        'type' => 'text',   'value' => '4-4-4'],
                    ['name' => 'Application Method', 'type' => 'select', 'value' => 'Soil Application'],
                ],
            ],

            // ── Crop Protection ────────────────────────────────────────────
            [
                'name' => 'Systemic Fungicide – UPL Saaf 1 kg',
                'sku' => 'CHEM-FUN-001',
                'category_slug' => 'fungicides',
                'brand_slug' => 'upl',
                'uom_slug' => 'kilogram',
                'hsn_code' => '3808.92',
                'tax_rate' => 18,
                'purchase_price' => 510,
                'mrp' => 750,
                'selling_price' => 680,
                'description' => 'Carbendazim 12% + Mancozeb 63% WP. Controls powdery mildew, blast & tikka.',
                'barcode' => '8903333444455',
                'weight_g' => 1000,
                'application_instructions' => 'Mix 2 g/L of water. Spray at 7–10 day intervals.',
                'grade' => 'A',
                'image_source' => '04_pesticides.png',
                'batch_tracking' => true,
                'expiry_tracking' => true,
                'allow_overselling' => false,
                'overselling_qty' => 0,
                'attributes' => [
                    ['name' => 'Formulation Type', 'type' => 'select', 'value' => 'WP (Wettable Powder)'],
                    ['name' => 'Mode of Action',   'type' => 'select', 'value' => 'Systemic'],
                    ['name' => 'Active Ingredient', 'type' => 'text',  'value' => 'Carbendazim 12% + Mancozeb 63%'],
                ],
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

            // ── Resolve default image from seeders/images/products/ ──────────
            $imagePath = null;
            if (isset($item['image_source'])) {
                $sourcePath = base_path('database/seeders/images/products/'.$item['image_source']);
                if (file_exists($sourcePath)) {
                    $productsDir = storage_path('app/public/products');
                    if (! is_dir($productsDir)) {
                        mkdir($productsDir, 0755, true);
                    }
                    $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);
                    $filename = Str::slug($item['sku']).'-'.time().'.'.$extension;
                    copy($sourcePath, $productsDir.'/'.$filename);
                    $imagePath = 'products/'.$filename;
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
                    'status' => 'active',
                    'is_active' => true,
                    'description' => $item['description'],
                    'image_path' => $imagePath,
                    'barcode' => $item['barcode'] ?? null,
                    'weight_g' => $item['weight_g'] ?? rand(100, 2000),
                    'length_cm' => $item['length_cm'] ?? rand(10, 50),
                    'width_cm' => $item['width_cm'] ?? rand(10, 50),
                    'height_cm' => $item['height_cm'] ?? rand(5, 30),
                    'application_instructions' => $item['application_instructions'] ?? null,
                    'grade' => $item['grade'] ?? null,
                    'default_discount' => 0,
                    'default_discount_type' => 'percent',
                    'batch_tracking' => $item['batch_tracking'] ?? false,
                    'expiry_tracking' => $item['expiry_tracking'] ?? false,
                    'allow_overselling' => true,
                    'overselling_qty' => 10,
                ]
            );

            // ── Sync product attributes ───────────────────────────────────────
            if (isset($item['attributes'])) {
                $attributeValueIds = [];
                foreach ($item['attributes'] as $attrData) {
                    $attribute = ProductAttribute::firstOrCreate(
                        ['name' => $attrData['name']],
                        ['type' => $attrData['type'], 'is_filterable' => true, 'status' => 'active']
                    );
                    $attributeValue = ProductAttributeValue::firstOrCreate(
                        ['product_attribute_id' => $attribute->id, 'value' => $attrData['value']],
                        ['color_code' => $attrData['color_code'] ?? null, 'status' => 'active']
                    );
                    $attributeValueIds[] = $attributeValue->id;
                }
                $product->attributeValues()->sync($attributeValueIds);
            }

            // ── Seed initial stock ────────────────────────────────────────────
            if ($product->default_warehouse_id && class_exists(InventoryService::class)) {
                try {
                    app(InventoryService::class)->setStock(
                        $product->id,
                        $product->default_warehouse_id,
                        10
                    );
                } catch (\Exception $e) {
                    // Fail silently if service requirements differ in runtime environments
                }
            }
        }
    }
}
