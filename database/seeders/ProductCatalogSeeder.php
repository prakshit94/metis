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
use App\Services\InventoryService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductCatalogSeeder extends Seeder
{
    public function run(): void
    {
        // ── Ensure base catalog data is present ────────────────────────────────
        $this->call([
            CategorySeeder::class,
            BrandSeeder::class,
            UnitOfMeasureSeeder::class,
            TaxRateSeeder::class,
            HsnCodeSeeder::class,
            AttributeSeeder::class,
        ]);

        // ── Sample products covering all 15 Agri categories ─────────────────
        $examples = [
            [
                'name' => 'Mahyco BG-II Bollgard Cotton Seeds (450g)',
                'sku' => 'SEED-COT-MHY01',
                'category' => 'cotton-seeds',
                'brand' => 'mahyco',
                'hsn' => '1209.99',
                'uom' => 'gram',
                'tax' => 5,
                'price' => 850.00,
                'purchase_price' => 620.00,
                'mrp' => 990.00,
                'stock' => 500,
                'min_stock_level' => 20,
                'status' => 'active',
                'grade' => 'A',
                'barcode' => '8901234567890',
                'weight' => '450 g',
                'batch_tracking' => false,
                'expiry_tracking' => true,
                'allow_overselling' => true,
                'overselling_qty' => 50,
                'description' => 'High-yielding hybrid BG-II cotton seeds with built-in bollworm resistance.',
                'application_instructions' => 'Sow 1 packet per acre in well-drained loamy soil. Row spacing: 90 cm × 60 cm.',
            ],
            [
                'name' => 'Syngenta NK 6240 Maize / Corn Hybrid Seeds (4 kg)',
                'sku' => 'SEED-MAIZE-SYN01',
                'category' => 'maize-corn-seeds',
                'brand' => 'syngenta-seeds',
                'hsn' => '1209.99',
                'uom' => 'kilogram',
                'tax' => 5,
                'price' => 1200.00,
                'purchase_price' => 880.00,
                'mrp' => 1399.00,
                'stock' => 300,
                'min_stock_level' => 15,
                'status' => 'active',
                'grade' => 'A',
                'barcode' => '8901234567891',
                'weight' => '4 kg',
                'batch_tracking' => false,
                'expiry_tracking' => true,
                'allow_overselling' => false,
                'overselling_qty' => 0,
                'description' => 'High-performance yellow flint hybrid maize seed. Suitable for kharif season.',
                'application_instructions' => 'Sow at 4 kg/acre. Spacing 60×25 cm. Germinates in 5–7 days.',
            ],
            [
                'name' => 'IFFCO DAP Fertilizer 50 kg Bag',
                'sku' => 'FERT-DAP-IFFCO50',
                'category' => 'chemical-fertilizers',
                'brand' => 'iffco',
                'hsn' => '3103',
                'uom' => 'bag',
                'tax' => 5,
                'price' => 1350.00,
                'purchase_price' => 1100.00,
                'mrp' => 1450.00,
                'stock' => 400,
                'min_stock_level' => 30,
                'status' => 'active',
                'grade' => 'A',
                'barcode' => '8909111222334',
                'weight' => '50 kg',
                'batch_tracking' => true,
                'expiry_tracking' => false,
                'allow_overselling' => false,
                'overselling_qty' => 0,
                'description' => 'Di-Ammonium Phosphate – 18:46:00. Provides nitrogen and phosphorus for robust root development.',
                'application_instructions' => 'Apply 50 kg/acre as basal dose before sowing. Mix into soil by tilling.',
            ],
            [
                'name' => 'Anandi Organics Vermicompost Manure 25 kg',
                'sku' => 'FERT-VERM-AND25',
                'category' => 'vermicompost',
                'brand' => 'anandi-organics',
                'hsn' => '3101',
                'uom' => 'bag',
                'tax' => 0,
                'price' => 280.00,
                'purchase_price' => 190.00,
                'mrp' => 320.00,
                'stock' => 600,
                'min_stock_level' => 50,
                'status' => 'active',
                'grade' => 'A',
                'barcode' => '8901122445566',
                'weight' => '25 kg',
                'batch_tracking' => false,
                'expiry_tracking' => false,
                'allow_overselling' => true,
                'overselling_qty' => 100,
                'description' => 'NPOP-certified vermicompost produced from red earthworms. Improves soil texture and microbial activity.',
                'application_instructions' => 'Apply 2–4 t/acre. Mix into top 6–8 inches of soil before planting.',
            ],
            [
                'name' => 'UPL Saaf Fungicide (Carbendazim 12% + Mancozeb 63% WP) 1 kg',
                'sku' => 'PROT-FUN-UPL01',
                'category' => 'fungicides',
                'brand' => 'upl',
                'hsn' => '3808.92',
                'uom' => 'kilogram',
                'tax' => 18,
                'price' => 680.00,
                'purchase_price' => 510.00,
                'mrp' => 750.00,
                'stock' => 120,
                'min_stock_level' => 10,
                'status' => 'active',
                'grade' => 'A',
                'barcode' => '8909876543210',
                'weight' => '1 kg',
                'batch_tracking' => true,
                'expiry_tracking' => true,
                'allow_overselling' => false,
                'overselling_qty' => 0,
                'description' => 'Systemic and contact fungicide. Controls powdery mildew, blast, tikka & leaf spot.',
                'application_instructions' => 'Mix 2 g/L of water. Spray at 7–10 day intervals at first sign of infection.',
            ],
        ];

        $warehouse = Warehouse::first();
        $supplier = Supplier::inRandomOrder()->first();

        foreach ($examples as $example) {
            $category = Category::where('slug', $example['category'])->first();
            $brand = Brand::where('slug', $example['brand'])->first();
            $uom = UnitOfMeasure::where('slug', $example['uom'])->first();
            $taxRate = TaxRate::where('rate', $example['tax'])->first();
            $hsn = HsnCode::where('code', $example['hsn'])->first();

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
                    'supplier_id' => $supplier?->id,
                    'purchase_price' => $example['purchase_price'],
                    'mrp' => $example['mrp'],
                    'selling_price' => $example['price'],
                    'min_stock_level' => $example['min_stock_level'] ?? 10,
                    'allow_overselling' => true,
                    'overselling_qty' => 10,
                    'batch_tracking' => $example['batch_tracking'],
                    'expiry_tracking' => $example['expiry_tracking'],
                    'manage_stock' => true,
                    'is_sku_enabled' => true,
                    'status' => $example['status'],
                    'is_active' => $example['status'] === 'active',
                    'description' => $example['description'],
                    'application_instructions' => $example['application_instructions'] ?? null,
                    'grade' => $example['grade'],
                    'barcode' => $example['barcode'] ?? null,
                    'weight_g' => isset($example['weight']) ? (float) filter_var($example['weight'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) * (str_contains(strtolower($example['weight']), 'kg') ? 1000 : (str_contains(strtolower($example['weight']), 'l') ? 1000 : 1)) : rand(100, 2000),
                    'image_path' => null,
                    'default_discount' => 0,
                    'default_discount_type' => 'percent',
                ],
            );

            if ($product->default_warehouse_id && class_exists(InventoryService::class)) {
                try {
                    app(InventoryService::class)->setStock(
                        $product->id,
                        $product->default_warehouse_id,
                        $example['stock'] ?? 10
                    );
                } catch (\Exception $e) {
                    // Fail silently if service requirements differ in runtime environments
                }
            }
        }
    }
}
