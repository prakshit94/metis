<?php

namespace Database\Seeders;

use App\Modules\Catalog\Models\HsnCode;
use Illuminate\Database\Seeder;

class HsnCodeSeeder extends Seeder
{
    public function run(): void
    {
        /*
         * HSN Codes for Agri E-commerce Products (India GST)
         * Sources: Indian Customs Tariff / GST rate schedule
         */
        $hsnCodes = [
            // ── Seeds ───────────────────────────────────────────────────────
            ['code' => '1209', 'description' => 'Seeds, fruit & spores used for sowing (general)'],
            ['code' => '1209.10', 'description' => 'Sugar beet seed'],
            ['code' => '1209.21', 'description' => 'Lucerne / alfalfa seed'],
            ['code' => '1209.91', 'description' => 'Vegetable seeds for sowing'],
            ['code' => '1209.99', 'description' => 'Other seeds for sowing (cotton, wheat, maize, sunflower etc.)'],

            // ── Live plants & saplings ───────────────────────────────────────
            ['code' => '0601', 'description' => 'Bulbs, tubers, tuberous roots, corms, crowns & rhizomes'],
            ['code' => '0602', 'description' => 'Other live plants, cuttings, slips, mushroom spawn'],

            // ── Fertilizers ──────────────────────────────────────────────────
            ['code' => '3101', 'description' => 'Animal / vegetable fertilizers – single or mixed'],
            ['code' => '3102', 'description' => 'Mineral / chemical nitrogenous fertilizers (Urea, Ammonium Nitrate)'],
            ['code' => '3103', 'description' => 'Mineral / chemical phosphatic fertilizers (SSP, DAP)'],
            ['code' => '3104', 'description' => 'Mineral / chemical potassic fertilizers (MOP)'],
            ['code' => '3105', 'description' => 'NPK complex fertilizers & other mixed fertilizers'],

            // ── Micronutrients & Bio-fertilizers ────────────────────────────
            ['code' => '3824', 'description' => 'Micronutrient mixtures, chelated micro-nutrients, bio-stimulants'],
            ['code' => '3002', 'description' => 'Bio-fertilizers: Rhizobium, Azotobacter, PSB cultures'],

            // ── Crop Protection (Pesticides) ─────────────────────────────────
            ['code' => '3808', 'description' => 'Insecticides, fungicides, herbicides, rodenticides, plant growth regulators (general)'],
            ['code' => '3808.91', 'description' => 'Insecticides – technical grade & formulations'],
            ['code' => '3808.92', 'description' => 'Fungicides & bactericides'],
            ['code' => '3808.93', 'description' => 'Herbicides, anti-sprouting products & plant growth regulators'],
            ['code' => '3808.94', 'description' => 'Disinfectants for agri use'],

            // ── Animal Feed ──────────────────────────────────────────────────
            ['code' => '2302', 'description' => 'Bran, sharps & residues from milling (cattle/poultry feed ingredient)'],
            ['code' => '2309', 'description' => 'Preparations used in animal feeding (compound feed, mineral mixture)'],
            ['code' => '1214', 'description' => 'Swedes, mangolds, hay, clover, fodder, silage'],

            // ── Agri Equipment & Machinery ───────────────────────────────────
            ['code' => '8201', 'description' => 'Hand tools: spades, shovels, mattocks, picks, hoes, forks, rakes'],
            ['code' => '8432', 'description' => 'Agricultural, horticultural or forestry machinery for soil preparation (tiller, rotavator, plough)'],
            ['code' => '8433', 'description' => 'Harvesting / threshing machinery; lawn / sports-ground mowers'],
            ['code' => '8436', 'description' => 'Other agricultural, horticultural machinery (seeders, planters)'],
            ['code' => '8424', 'description' => 'Mechanical appliances for projecting liquids / powders (sprayers, dusters)'],

            // ── Irrigation ────────────────────────────────────────────────────
            ['code' => '8413', 'description' => 'Pumps for liquids (water pumps for irrigation)'],
            ['code' => '3917', 'description' => 'Tubes, pipes & hoses of plastics (HDPE / PVC irrigation pipes)'],
            ['code' => '3926', 'description' => 'Other plastic articles (drip emitters, connectors, grow bags, pots)'],
            ['code' => '8479', 'description' => 'Drip irrigation systems & sprinkler systems (complete sets)'],

            // ── Storage & Packaging ───────────────────────────────────────────
            ['code' => '6305', 'description' => 'Sacks & bags – jute, polypropylene (gunny bags / PP bags)'],
            ['code' => '3923', 'description' => 'Articles for conveyance / packing of goods (plastic crates, bins)'],
            ['code' => '5608', 'description' => 'Knotted netting of twine, cordage or rope; shade nets, fishing nets'],
            ['code' => '5911', 'description' => 'Technical textile articles – tarpaulin, silpaulin'],

            // ── Safety & PPE ──────────────────────────────────────────────────
            ['code' => '3926.20', 'description' => 'Articles of apparel & accessories of plastics (PPE kit parts, aprons)'],
            ['code' => '6210', 'description' => 'Garments made up of fabrics – protective suits / overalls'],

            // ── Soil testing / miscellaneous ─────────────────────────────────
            ['code' => '3822', 'description' => 'Diagnostic / laboratory reagents – soil testing kits'],
            ['code' => '9027', 'description' => 'Instruments for physical / chemical analysis (moisture meters, EC testers)'],
        ];

        foreach ($hsnCodes as $hsn) {
            HsnCode::firstOrCreate(['code' => $hsn['code']], [
                'code'        => $hsn['code'],
                'description' => $hsn['description'],
                'status'      => 'active',
            ]);
        }
    }
}
