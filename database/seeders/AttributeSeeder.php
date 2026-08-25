<?php

namespace Database\Seeders;

use App\Modules\Catalog\Models\ProductAttribute;
use App\Modules\Catalog\Models\ProductAttributeValue;
use Illuminate\Database\Seeder;

class AttributeSeeder extends Seeder
{
    public function run(): void
    {
        /*
         * Agri E-commerce Product Attributes
         * type: 'select' | 'text' | 'number' | 'boolean' | 'color'
         */
        $attributes = [
            // ── Seeds ─────────────────────────────────────────────────────────
            [
                'name'          => 'Crop Type',
                'type'          => 'select',
                'is_filterable' => true,
                'status'        => 'active',
                'values'        => ['Kharif', 'Rabi', 'Zaid', 'Perennial'],
            ],
            [
                'name'          => 'Seed Variety',
                'type'          => 'text',
                'is_filterable' => false,
                'status'        => 'active',
                'values'        => [],
            ],
            [
                'name'          => 'Seed Treatment',
                'type'          => 'select',
                'is_filterable' => true,
                'status'        => 'active',
                'values'        => ['Untreated', 'Fungicide Treated', 'Insecticide Treated', 'Bio-treated'],
            ],
            [
                'name'          => 'Germination Rate (%)',
                'type'          => 'text',
                'is_filterable' => false,
                'status'        => 'active',
                'values'        => [],
            ],
            [
                'name'          => 'Maturity Days',
                'type'          => 'text',
                'is_filterable' => false,
                'status'        => 'active',
                'values'        => [],
            ],
            [
                'name'          => 'Hybrid / Open Pollinated',
                'type'          => 'select',
                'is_filterable' => true,
                'status'        => 'active',
                'values'        => ['Hybrid', 'Open Pollinated (OP)', 'Composite', 'GM / BT'],
            ],

            // ── Fertilizers ────────────────────────────────────────────────────
            [
                'name'          => 'Fertilizer Type',
                'type'          => 'select',
                'is_filterable' => true,
                'status'        => 'active',
                'values'        => ['Organic', 'Inorganic / Chemical', 'Bio-fertilizer', 'Micronutrient', 'Foliar Spray', 'Water Soluble'],
            ],
            [
                'name'          => 'N-P-K Ratio',
                'type'          => 'text',
                'is_filterable' => false,
                'status'        => 'active',
                'values'        => [],
            ],
            [
                'name'          => 'Application Method',
                'type'          => 'select',
                'is_filterable' => true,
                'status'        => 'active',
                'values'        => ['Soil Application', 'Foliar Spray', 'Drip / Fertigation', 'Broadcasting', 'Basal Dose', 'Top Dressing'],
            ],
            [
                'name'          => 'Suitable For Crops',
                'type'          => 'text',
                'is_filterable' => false,
                'status'        => 'active',
                'values'        => [],
            ],

            // ── Pesticides / Crop Protection ────────────────────────────────────
            [
                'name'          => 'Formulation Type',
                'type'          => 'select',
                'is_filterable' => true,
                'status'        => 'active',
                'values'        => [
                    'EC (Emulsifiable Concentrate)',
                    'WP (Wettable Powder)',
                    'WDG / WG (Water Dispersible Granule)',
                    'SC (Suspension Concentrate)',
                    'SL (Soluble Concentrate)',
                    'GR (Granules)',
                    'DP (Dust Powder)',
                    'ULV (Ultra Low Volume)',
                ],
            ],
            [
                'name'          => 'Active Ingredient',
                'type'          => 'text',
                'is_filterable' => false,
                'status'        => 'active',
                'values'        => [],
            ],
            [
                'name'          => 'Target Pest / Disease',
                'type'          => 'text',
                'is_filterable' => false,
                'status'        => 'active',
                'values'        => [],
            ],
            [
                'name'          => 'Mode of Action',
                'type'          => 'select',
                'is_filterable' => true,
                'status'        => 'active',
                'values'        => ['Systemic', 'Contact', 'Translaminar', 'Protective', 'Eradicant', 'Pre-emergent', 'Post-emergent'],
            ],
            [
                'name'          => 'PHI (Pre-Harvest Interval in days)',
                'type'          => 'text',
                'is_filterable' => false,
                'status'        => 'active',
                'values'        => [],
            ],
            [
                'name'          => 'CIB Registration No.',
                'type'          => 'text',
                'is_filterable' => false,
                'status'        => 'active',
                'values'        => [],
            ],

            // ── Equipment / Tools ────────────────────────────────────────────────
            [
                'name'          => 'Material',
                'type'          => 'select',
                'is_filterable' => true,
                'status'        => 'active',
                'values'        => ['Stainless Steel', 'Iron / Mild Steel', 'Aluminium', 'HDPE Plastic', 'PVC', 'Fiberglass', 'Rubber', 'Galvanized Iron'],
            ],
            [
                'name'          => 'Power Source',
                'type'          => 'select',
                'is_filterable' => true,
                'status'        => 'active',
                'values'        => ['Manual', 'Battery Operated', 'Petrol Engine', 'Diesel Engine', 'Electric Motor', 'Solar Powered'],
            ],
            [
                'name'          => 'Tank / Container Capacity',
                'type'          => 'text',
                'is_filterable' => false,
                'status'        => 'active',
                'values'        => [],
            ],
            [
                'name'          => 'Flow Rate / Output',
                'type'          => 'text',
                'is_filterable' => false,
                'status'        => 'active',
                'values'        => [],
            ],

            // ── Irrigation ────────────────────────────────────────────────────────
            [
                'name'          => 'Pipe Diameter',
                'type'          => 'select',
                'is_filterable' => true,
                'status'        => 'active',
                'values'        => ['12 mm', '16 mm', '20 mm', '25 mm', '32 mm', '40 mm', '50 mm', '63 mm', '75 mm', '90 mm', '110 mm'],
            ],
            [
                'name'          => 'Discharge Rate (LPH)',
                'type'          => 'text',
                'is_filterable' => false,
                'status'        => 'active',
                'values'        => [],
            ],

            // ── Packaging / General ─────────────────────────────────────────────
            [
                'name'          => 'Pack Size',
                'type'          => 'select',
                'is_filterable' => true,
                'status'        => 'active',
                'values'        => [
                    '50g', '100g', '250g', '500g', '1 kg', '5 kg', '10 kg', '25 kg', '50 kg',
                    '100 ml', '250 ml', '500 ml', '1 L', '5 L', '10 L', '20 L', '200 L',
                    '1 bag', '5 bags', '10 bags',
                ],
            ],
            [
                'name'          => 'Color',
                'type'          => 'color',
                'is_filterable' => true,
                'status'        => 'active',
                'values'        => ['Black', 'White', 'Green', 'Blue', 'Red', 'Yellow', 'Grey'],
            ],
            [
                'name'          => 'Shelf Life / Expiry',
                'type'          => 'text',
                'is_filterable' => false,
                'status'        => 'active',
                'values'        => [],
            ],
            [
                'name'          => 'Country of Origin',
                'type'          => 'select',
                'is_filterable' => true,
                'status'        => 'active',
                'values'        => ['India', 'China', 'Israel', 'Netherlands', 'USA', 'Germany', 'Japan'],
            ],
            [
                'name'          => 'Certification',
                'type'          => 'select',
                'is_filterable' => true,
                'status'        => 'active',
                'values'        => ['FSSAI Approved', 'BIS Certified', 'ISI Mark', 'ISO 9001', 'NPOP Organic', 'PGS India Organic', 'None'],
            ],
        ];

        foreach ($attributes as $attrData) {
            $values = $attrData['values'] ?? [];
            unset($attrData['values']);

            $attribute = ProductAttribute::firstOrCreate(
                ['name' => $attrData['name']],
                $attrData
            );

            foreach ($values as $value) {
                ProductAttributeValue::firstOrCreate(
                    ['product_attribute_id' => $attribute->id, 'value' => $value],
                    [
                        'product_attribute_id' => $attribute->id,
                        'value'                => $value,
                        'status'               => 'active',
                    ]
                );
            }
        }
    }
}
