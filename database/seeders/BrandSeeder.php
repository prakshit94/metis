<?php

namespace Database\Seeders;

use App\Modules\Catalog\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        // Leading Agri-input brands available in the Indian market
        $brands = [
            // Seed companies
            ['name' => 'Mahyco',                'slug' => 'mahyco'],
            ['name' => 'Bayer Seeds',           'slug' => 'bayer-seeds'],
            ['name' => 'Syngenta Seeds',        'slug' => 'syngenta-seeds'],
            ['name' => 'Pioneer (Corteva)',     'slug' => 'pioneer-corteva'],
            ['name' => 'Nuziveedu Seeds',       'slug' => 'nuziveedu-seeds'],
            ['name' => 'Namdhari Seeds',        'slug' => 'namdhari-seeds'],
            ['name' => 'Kaveri Seed',           'slug' => 'kaveri-seed'],

            // Fertilizer companies
            ['name' => 'IFFCO',                 'slug' => 'iffco'],
            ['name' => 'Coromandel International', 'slug' => 'coromandel'],
            ['name' => 'Rashtriya Chemicals & Fertilizers (RCF)', 'slug' => 'rcf'],
            ['name' => 'Chambal Fertilisers',   'slug' => 'chambal'],
            ['name' => 'Gujarat State Fertilizers (GSFC)', 'slug' => 'gsfc'],
            ['name' => 'Yara International',    'slug' => 'yara'],
            ['name' => 'Aries Agro',            'slug' => 'aries-agro'],

            // Crop protection / pesticide companies
            ['name' => 'UPL Limited',           'slug' => 'upl'],
            ['name' => 'Bayer CropScience',     'slug' => 'bayer-crop-science'],
            ['name' => 'BASF India',            'slug' => 'basf-india'],
            ['name' => 'Dhanuka Agritech',      'slug' => 'dhanuka'],
            ['name' => 'Rallis India (Tata)',   'slug' => 'rallis'],
            ['name' => 'PI Industries',         'slug' => 'pi-industries'],
            ['name' => 'Insecticides India',    'slug' => 'insecticides-india'],
            ['name' => 'Crystal Crop Protection', 'slug' => 'crystal-crop'],
            ['name' => 'Gharda Chemicals',      'slug' => 'gharda'],

            // Agri equipment / irrigation
            ['name' => 'Jain Irrigation',       'slug' => 'jain-irrigation'],
            ['name' => 'Netafim',               'slug' => 'netafim'],
            ['name' => 'Kirloskar',             'slug' => 'kirloskar'],
            ['name' => 'Shakti Pumps',          'slug' => 'shakti-pumps'],
            ['name' => 'Honda Power',           'slug' => 'honda-power'],
            ['name' => 'Aspee Agro',            'slug' => 'aspee-agro'],

            // Organic / others
            ['name' => 'Anandi Organics',       'slug' => 'anandi-organics'],
            ['name' => 'BioWorks',              'slug' => 'bioworks'],
            ['name' => 'Multiplex',             'slug' => 'multiplex'],
            ['name' => 'Generic / Unbranded',   'slug' => 'generic-unbranded'],
        ];

        foreach ($brands as $brand) {
            Brand::firstOrCreate(['slug' => $brand['slug']], [
                'name'      => $brand['name'],
                'slug'      => $brand['slug'],
                'status'    => 'active',
                'is_active' => true,
            ]);
        }
    }
}
