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
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            // ── Seeds ──────────────────────────────────────────────────────
            [
                'name'                    => 'Premium Hybrid Cotton Seeds (450g)',
                'sku'                     => 'SEED-COT-001',
                'category_slug'           => 'cotton-seeds',
                'brand_slug'              => 'mahyco',
                'uom_slug'                => 'gram',
                'hsn_code'                => '1209.99',
                'tax_rate'                => 5,
                'purchase_price'          => 500,
                'mrp'                     => 850,
                'selling_price'           => 720,
                'description'             => 'High-yield BG-II hybrid cotton seeds with bollworm resistance.',
                'barcode'                 => '8901111222233',
                'weight'                  => '450 g',
                'weight_g'                => 450,
                'length_cm'               => 15,
                'width_cm'                => 10,
                'height_cm'               => 5,
                'application_instructions'=> 'Sow 1 packet/acre in well-drained loamy soil. Row spacing 90×60 cm.',
                'grade'                   => 'A',
                'image_source'            => '01_seeds.png',
                'batch_tracking'          => false,
                'expiry_tracking'         => true,
                'allow_overselling'       => false,
                'overselling_qty'         => 0,
                'attributes'              => [
                    ['name' => 'Crop Type',            'type' => 'select', 'value' => 'Kharif'],
                    ['name' => 'Hybrid / Open Pollinated', 'type' => 'select', 'value' => 'Hybrid'],
                ],
            ],
            [
                'name'                    => 'Wheat Seeds HD-2967 (5 kg)',
                'sku'                     => 'SEED-WHT-001',
                'category_slug'           => 'wheat-seeds',
                'brand_slug'              => 'nuziveedu-seeds',
                'uom_slug'                => 'kilogram',
                'hsn_code'                => '1209.99',
                'tax_rate'                => 5,
                'purchase_price'          => 180,
                'mrp'                     => 280,
                'selling_price'           => 240,
                'description'             => 'HD-2967 high-yielding, rust-resistant wheat variety for Rabi season.',
                'barcode'                 => '8901111222234',
                'weight'                  => '5 kg',
                'weight_g'                => 5000,
                'length_cm'               => 40,
                'width_cm'                => 30,
                'height_cm'               => 10,
                'application_instructions'=> 'Sow 100 kg/acre. Germination in 5–6 days. Irrigate 5–6 times.',
                'grade'                   => 'A',
                'image_source'            => '01_seeds.png',
                'batch_tracking'          => false,
                'expiry_tracking'         => true,
                'allow_overselling'       => false,
                'overselling_qty'         => 0,
                'attributes'              => [
                    ['name' => 'Crop Type', 'type' => 'select', 'value' => 'Rabi'],
                    ['name' => 'Maturity Days', 'type' => 'text', 'value' => '120–130 days'],
                ],
            ],

            // ── Fertilizers ────────────────────────────────────────────────
            [
                'name'                    => 'IFFCO Urea 45 kg Bag',
                'sku'                     => 'FERT-UREA-001',
                'category_slug'           => 'chemical-fertilizers',
                'brand_slug'              => 'iffco',
                'uom_slug'                => 'bag',
                'hsn_code'                => '3102',
                'tax_rate'                => 5,
                'purchase_price'          => 240,
                'mrp'                     => 280,
                'selling_price'           => 266,
                'description'             => 'Granular urea 46% N. India\'s most used nitrogenous fertilizer.',
                'barcode'                 => '8902222333344',
                'weight'                  => '45 kg',
                'weight_g'                => 45000,
                'length_cm'               => 80,
                'width_cm'                => 50,
                'height_cm'               => 15,
                'application_instructions'=> 'Apply 65 kg/acre as top dressing at vegetative stage. Avoid broadcasting before rain.',
                'grade'                   => 'A',
                'image_source'            => '03_fertilizers.png',
                'batch_tracking'          => true,
                'expiry_tracking'         => false,
                'allow_overselling'       => false,
                'overselling_qty'         => 0,
                'attributes'              => [
                    ['name' => 'Fertilizer Type',    'type' => 'select', 'value' => 'Inorganic / Chemical'],
                    ['name' => 'N-P-K Ratio',        'type' => 'text',   'value' => '46-0-0'],
                    ['name' => 'Application Method', 'type' => 'select', 'value' => 'Top Dressing'],
                ],
            ],
            [
                'name'                    => 'Organic NPK Fertilizer Granules 50 kg',
                'sku'                     => 'FERT-NPK-001',
                'category_slug'           => 'organic-fertilizers',
                'brand_slug'              => 'anandi-organics',
                'uom_slug'                => 'bag',
                'hsn_code'                => '3101',
                'tax_rate'                => 0,
                'purchase_price'          => 1200,
                'mrp'                     => 1600,
                'selling_price'           => 1400,
                'description'             => 'Balanced organic NPK formula enriched with micronutrients.',
                'barcode'                 => '8902222333345',
                'weight'                  => '50 kg',
                'application_instructions'=> 'Apply 50 kg/acre before sowing as basal dose.',
                'grade'                   => 'B',
                'image_source'            => '03_fertilizers.png',
                'batch_tracking'          => false,
                'expiry_tracking'         => false,
                'allow_overselling'       => false,
                'overselling_qty'         => 0,
                'attributes'              => [
                    ['name' => 'Fertilizer Type',    'type' => 'select', 'value' => 'Organic'],
                    ['name' => 'N-P-K Ratio',        'type' => 'text',   'value' => '4-4-4'],
                    ['name' => 'Application Method', 'type' => 'select', 'value' => 'Soil Application'],
                ],
            ],

            // ── Crop Protection ────────────────────────────────────────────
            [
                'name'                    => 'Systemic Fungicide – UPL Saaf 1 kg',
                'sku'                     => 'CHEM-FUN-001',
                'category_slug'           => 'fungicides',
                'brand_slug'              => 'upl',
                'uom_slug'                => 'kilogram',
                'hsn_code'                => '3808.92',
                'tax_rate'                => 18,
                'purchase_price'          => 510,
                'mrp'                     => 750,
                'selling_price'           => 680,
                'description'             => 'Carbendazim 12% + Mancozeb 63% WP. Controls powdery mildew, blast & tikka.',
                'barcode'                 => '8903333444455',
                'weight'                  => '1 kg',
                'application_instructions'=> 'Mix 2 g/L of water. Spray at 7–10 day intervals.',
                'grade'                   => 'A',
                'image_source'            => '04_pesticides.png',
                'batch_tracking'          => true,
                'expiry_tracking'         => true,
                'allow_overselling'       => false,
                'overselling_qty'         => 0,
                'attributes'              => [
                    ['name' => 'Formulation Type', 'type' => 'select', 'value' => 'WP (Wettable Powder)'],
                    ['name' => 'Mode of Action',   'type' => 'select', 'value' => 'Systemic'],
                    ['name' => 'Active Ingredient', 'type' => 'text',  'value' => 'Carbendazim 12% + Mancozeb 63%'],
                ],
            ],
            [
                'name'                    => 'Imidacloprid 17.8% SL Insecticide 500 ml',
                'sku'                     => 'CHEM-INS-001',
                'category_slug'           => 'insecticides',
                'brand_slug'              => 'dhanuka',
                'uom_slug'                => 'milliliter',
                'hsn_code'                => '3808.91',
                'tax_rate'                => 18,
                'purchase_price'          => 280,
                'mrp'                     => 430,
                'selling_price'           => 380,
                'description'             => 'Systemic insecticide for sucking pest control (aphid, whitefly, jassid).',
                'barcode'                 => '8903333444456',
                'weight'                  => '500 ml',
                'application_instructions'=> 'Dilute 0.5 ml/L of water. Spray at first sign of infestation.',
                'grade'                   => 'A',
                'image_source'            => '04_pesticides.png',
                'batch_tracking'          => true,
                'expiry_tracking'         => true,
                'allow_overselling'       => false,
                'overselling_qty'         => 0,
                'attributes'              => [
                    ['name' => 'Formulation Type', 'type' => 'select', 'value' => 'SL (Soluble Concentrate)'],
                    ['name' => 'Mode of Action',   'type' => 'select', 'value' => 'Systemic'],
                    ['name' => 'Active Ingredient', 'type' => 'text',  'value' => 'Imidacloprid 17.8%'],
                ],
            ],

            // ── Irrigation ─────────────────────────────────────────────────
            [
                'name'                    => 'Drip Irrigation Inline Pipe 16mm 400m Roll',
                'sku'                     => 'MACH-DRIP-001',
                'category_slug'           => 'drip-irrigation',
                'brand_slug'              => 'jain-irrigation',
                'uom_slug'                => 'roll',
                'hsn_code'                => '3917',
                'tax_rate'                => 18,
                'purchase_price'          => 3100,
                'mrp'                     => 4800,
                'selling_price'           => 4200,
                'description'             => 'High-durability LLDPE 16mm inline drip lateral pipe. 30 cm spacing, 4 LPH output.',
                'barcode'                 => '8904444555566',
                'weight'                  => '15 kg',
                'application_instructions'=> 'Lay along crop rows. Flush weekly. Use 150-mesh filter at header.',
                'grade'                   => 'A',
                'image_source'            => '07_irrigation.png',
                'batch_tracking'          => false,
                'expiry_tracking'         => false,
                'allow_overselling'       => false,
                'overselling_qty'         => 0,
                'attributes'              => [
                    ['name' => 'Pipe Diameter', 'type' => 'select', 'value' => '16 mm'],
                    ['name' => 'Material',      'type' => 'select', 'value' => 'HDPE Plastic'],
                ],
            ],
            [
                'name'                    => 'Shakti 1HP Centrifugal Water Pump',
                'sku'                     => 'IRRI-PUMP-001',
                'category_slug'           => 'water-pumps',
                'brand_slug'              => 'shakti-pumps',
                'uom_slug'                => 'piece',
                'hsn_code'                => '8413',
                'tax_rate'                => 18,
                'purchase_price'          => 4200,
                'mrp'                     => 6500,
                'selling_price'           => 5800,
                'description'             => '1 HP single-phase centrifugal pump. Max head 30m. CI impeller, brass port.',
                'barcode'                 => '8904444555567',
                'weight'                  => '8 kg',
                'application_instructions'=> 'Prime before start. Mount on stable platform. Use ISI wire & starter.',
                'grade'                   => 'A',
                'image_source'            => '07_irrigation.png',
                'batch_tracking'          => false,
                'expiry_tracking'         => false,
                'allow_overselling'       => false,
                'overselling_qty'         => 0,
                'attributes'              => [
                    ['name' => 'Power Source', 'type' => 'select', 'value' => 'Electric Motor'],
                    ['name' => 'Material',     'type' => 'select', 'value' => 'Iron / Mild Steel'],
                ],
            ],

            // ── Farm Equipment / Tools ──────────────────────────────────────
            [
                'name'                    => 'Aspee 16L Knapsack Manual Sprayer',
                'sku'                     => 'TOOL-SPR-001',
                'category_slug'           => 'sprayers',
                'brand_slug'              => 'aspee-agro',
                'uom_slug'                => 'piece',
                'hsn_code'                => '8424',
                'tax_rate'                => 18,
                'purchase_price'          => 640,
                'mrp'                     => 999,
                'selling_price'           => 890,
                'description'             => '16L knapsack sprayer with brass nozzle. Ideal for field crops and orchards.',
                'barcode'                 => '8905555666677',
                'weight'                  => '2.1 kg',
                'application_instructions'=> 'Fill up to 16L mark. Pump 8–10 strokes to pressurise. Spray uniformly.',
                'grade'                   => 'B',
                'image_source'            => '05_farm_equipment.png',
                'batch_tracking'          => false,
                'expiry_tracking'         => false,
                'allow_overselling'       => false,
                'overselling_qty'         => 0,
                'attributes'              => [
                    ['name' => 'Power Source',            'type' => 'select', 'value' => 'Manual'],
                    ['name' => 'Tank / Container Capacity', 'type' => 'text', 'value' => '16 L'],
                ],
            ],

            // ── Organic Products ────────────────────────────────────────────
            [
                'name'                    => 'Vermicompost Organic Manure 25 kg',
                'sku'                     => 'ORG-VERM-001',
                'category_slug'           => 'vermicompost',
                'brand_slug'              => 'anandi-organics',
                'uom_slug'                => 'bag',
                'hsn_code'                => '3101',
                'tax_rate'                => 0,
                'purchase_price'          => 190,
                'mrp'                     => 320,
                'selling_price'           => 280,
                'description'             => 'NPOP-certified vermicompost. Improves soil texture and microbial activity.',
                'barcode'                 => '8906666777788',
                'weight'                  => '25 kg',
                'application_instructions'=> 'Apply 2–4 t/acre. Mix into top 6–8 inches before planting.',
                'grade'                   => 'A',
                'image_source'            => '09_organic_products.png',
                'batch_tracking'          => false,
                'expiry_tracking'         => false,
                'allow_overselling'       => true,
                'overselling_qty'         => 100,
                'attributes'              => [
                    ['name' => 'Fertilizer Type',  'type' => 'select', 'value' => 'Organic'],
                    ['name' => 'Certification',    'type' => 'select', 'value' => 'NPOP Organic'],
                ],
            ],

            // ── Animal Feed ─────────────────────────────────────────────────
            [
                'name'                    => 'Cattle Compound Feed Pellets 50 kg',
                'sku'                     => 'AFEED-CAT-001',
                'category_slug'           => 'cattle-feed',
                'brand_slug'              => 'coromandel',
                'uom_slug'                => 'bag',
                'hsn_code'                => '2309',
                'tax_rate'                => 0,
                'purchase_price'          => 950,
                'mrp'                     => 1350,
                'selling_price'           => 1200,
                'description'             => 'Balanced pellet feed for crossbred & HF dairy cattle. 22% CP, 70% TDN.',
                'barcode'                 => '8907777888899',
                'weight'                  => '50 kg',
                'application_instructions'=> 'Feed 3–4 kg/day per adult animal. Provide fresh water continuously.',
                'grade'                   => 'B',
                'image_source'            => '10_animal_feed.png',
                'batch_tracking'          => true,
                'expiry_tracking'         => true,
                'allow_overselling'       => false,
                'overselling_qty'         => 0,
                'attributes'              => [
                    ['name' => 'Pack Size', 'type' => 'select', 'value' => '50 kg'],
                ],
            ],

            // ── Storage & Packaging ─────────────────────────────────────────
            [
                'name'                    => 'PP Woven Gunny Bags 50 kg (Pack of 50)',
                'sku'                     => 'STOR-GUN-001',
                'category_slug'           => 'gunny-bags-sacks',
                'brand_slug'              => 'generic-unbranded',
                'uom_slug'                => 'packet',
                'hsn_code'                => '6305',
                'tax_rate'                => 5,
                'purchase_price'          => 550,
                'mrp'                     => 850,
                'selling_price'           => 750,
                'description'             => 'Virgin PP woven sacks. UV treated. 50 kg load capacity. Pack of 50.',
                'barcode'                 => '8908888999900',
                'weight'                  => '4 kg',
                'application_instructions'=> 'Store in cool dry place. Do not stack more than 12 bags high.',
                'grade'                   => 'B',
                'image_source'            => '14_storage_packaging.png',
                'batch_tracking'          => false,
                'expiry_tracking'         => false,
                'allow_overselling'       => true,
                'overselling_qty'         => 20,
                'attributes'              => [
                    ['name' => 'Material', 'type' => 'select', 'value' => 'HDPE Plastic'],
                    ['name' => 'Pack Size', 'type' => 'select', 'value' => '50 kg'],
                ],
            ],

            // ── Grow Bags & Pots ────────────────────────────────────────────
            [
                'name'                    => 'HDPE Grow Bag 24×24 inch (Pack of 10)',
                'sku'                     => 'GRWB-HDPE-001',
                'category_slug'           => 'grow-bags',
                'brand_slug'              => 'generic-unbranded',
                'uom_slug'                => 'packet',
                'hsn_code'                => '3926',
                'tax_rate'                => 12,
                'purchase_price'          => 230,
                'mrp'                     => 400,
                'selling_price'           => 350,
                'description'             => 'UV-stabilised 200-micron HDPE grow bags. Ideal for tomato, capsicum & herbs.',
                'barcode'                 => '8909999000011',
                'weight'                  => '1.2 kg',
                'application_instructions'=> 'Fill with 1:1:1 mix of soil, compost & cocopeat. Ensure drainage holes.',
                'grade'                   => 'B',
                'image_source'            => '08_grow_bags_pots.png',
                'batch_tracking'          => false,
                'expiry_tracking'         => false,
                'allow_overselling'       => true,
                'overselling_qty'         => 50,
                'attributes'              => [
                    ['name' => 'Material', 'type' => 'select', 'value' => 'HDPE Plastic'],
                ],
            ],
        ];

        $warehouse = Warehouse::where('code', 'MAIN-ECOM')->first() ?? Warehouse::first();

        foreach ($products as $item) {
            $category = Category::where('slug', $item['category_slug'])->first() ?? Category::first();
            $brand    = Brand::where('slug', $item['brand_slug'])->first() ?? Brand::first();
            $uom      = UnitOfMeasure::where('slug', $item['uom_slug'])->first() ?? UnitOfMeasure::first();
            $hsn      = HsnCode::where('code', $item['hsn_code'])->first() ?? HsnCode::first();
            $tax      = TaxRate::where('rate', $item['tax_rate'])->first() ?? TaxRate::first();
            $supplier = Supplier::inRandomOrder()->first();

            // ── Resolve default image from seeders/images/products/ ──────────
            $imagePath = null;
            if (isset($item['image_source'])) {
                $sourcePath = base_path('database/seeders/images/products/' . $item['image_source']);
                if (file_exists($sourcePath)) {
                    $productsDir = storage_path('app/public/products');
                    if (!is_dir($productsDir)) {
                        mkdir($productsDir, 0755, true);
                    }
                    $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);
                    $filename  = Str::slug($item['sku']) . '-' . time() . '.' . $extension;
                    copy($sourcePath, $productsDir . '/' . $filename);
                    $imagePath = 'products/' . $filename;
                }
            }

            $product = Product::firstOrCreate(
                ['sku' => $item['sku']],
                [
                    'name'                     => $item['name'],
                    'slug'                     => Str::slug($item['name']),
                    'category_id'              => $category?->id,
                    'brand_id'                 => $brand?->id,
                    'tax_rate_id'              => $tax?->id,
                    'hsn_code_id'              => $hsn?->id,
                    'uom_id'                   => $uom?->id,
                    'default_warehouse_id'     => $warehouse?->id,
                    'supplier_id'              => $supplier?->id,
                    'purchase_price'           => $item['purchase_price'],
                    'mrp'                      => $item['mrp'],
                    'selling_price'            => $item['selling_price'],
                    'min_stock_level'          => 5,
                    'manage_stock'             => true,
                    'is_sku_enabled'           => true,
                    'status'                   => 'active',
                    'is_active'                => true,
                    'description'              => $item['description'],
                    'image_path'               => $imagePath,
                    'barcode'                  => $item['barcode'] ?? null,
                    'weight'                   => $item['weight'] ?? null,
                    'weight_g'                 => $item['weight_g'] ?? (isset($item['weight']) ? (float) filter_var($item['weight'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) * (str_contains(strtolower($item['weight']), 'kg') ? 1000 : 1) : rand(100, 2000)),
                    'length_cm'                => $item['length_cm'] ?? rand(10, 50),
                    'width_cm'                 => $item['width_cm'] ?? rand(10, 50),
                    'height_cm'                => $item['height_cm'] ?? rand(5, 30),
                    'application_instructions' => $item['application_instructions'] ?? null,
                    'grade'                    => $item['grade'] ?? null,
                    'default_discount'         => 0,
                    'default_discount_type'    => 'percent',
                    'batch_tracking'           => $item['batch_tracking'] ?? false,
                    'expiry_tracking'          => $item['expiry_tracking'] ?? false,
                    'allow_overselling'        => $item['allow_overselling'] ?? false,
                    'overselling_qty'          => $item['overselling_qty'] ?? 0,
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
            if ($product->default_warehouse_id && class_exists(\App\Services\InventoryService::class)) {
                try {
                    app(\App\Services\InventoryService::class)->setStock(
                        $product->id,
                        $product->default_warehouse_id,
                        100
                    );
                } catch (\Exception $e) {
                    // Fail silently if service requirements differ in runtime environments
                }
            }
        }
    }
}
