<?php

namespace Database\Seeders;

use App\Modules\Catalog\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $warehouses = [
            [
                'name' => 'Main Ecommerce Warehouse',
                'code' => 'MAIN-ECOM',
                'company_name' => 'Metis Retail Pvt Ltd',
                'gstin' => '22AAAAA0000A1Z5',
                'phone' => '+91-9876543210',
                'address' => 'Plot 45, Phase 2, Industrial Area, Sector 62',
                'address_line_1' => 'Plot 45, Phase 2',
                'address_line_2' => 'Industrial Area, Sector 62',
                'village_name' => 'Noida',
                'post_office' => 'Sector 62',
                'taluka' => 'Noida',
                'city' => 'Noida',
                'state' => 'Uttar Pradesh',
                'pincode' => '201309',
                'status' => 'active',
                'is_default' => true,
                'is_active' => true,
            ],
            [
                'name' => 'South Zone Fulfillment Center',
                'code' => 'SZ-FC-01',
                'company_name' => 'Metis South Logistics Ltd',
                'gstin' => '29BBBBB1111B2Z6',
                'phone' => '+91-9988776655',
                'address' => 'Block C, Electronic City, Phase 1',
                'address_line_1' => 'Block C',
                'address_line_2' => 'Electronic City, Phase 1',
                'village_name' => 'Electronic City',
                'post_office' => 'Electronic City',
                'taluka' => 'Bangalore South',
                'city' => 'Bengaluru',
                'state' => 'Karnataka',
                'pincode' => '560100',
                'status' => 'active',
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'name' => 'West Zone Distribution Hub',
                'code' => 'WZ-DH-02',
                'company_name' => 'Metis Western Logistics',
                'gstin' => '27CCCCC2222C3Z7',
                'phone' => '+91-8877665544',
                'address' => 'Gala No 12, Logistics Park, Bhiwandi',
                'address_line_1' => 'Gala No 12, Logistics Park',
                'address_line_2' => 'Mumbai-Nashik Highway',
                'village_name' => 'Bhiwandi',
                'post_office' => 'Bhiwandi',
                'taluka' => 'Bhiwandi',
                'city' => 'Thane',
                'state' => 'Maharashtra',
                'pincode' => '421302',
                'status' => 'active',
                'is_default' => false,
                'is_active' => true,
            ]
        ];

        foreach ($warehouses as $warehouse) {
            Warehouse::updateOrCreate(['code' => $warehouse['code']], $warehouse);
        }
    }
}
