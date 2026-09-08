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
                'email' => 'contact@metis.example.com',
                'reference_no' => 'WH-REF-001',
                'seed_lic_no' => 'SL-1001',
                'pesti_lic_no' => 'PL-1001',
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
                'name' => 'Gujarat Fulfillment Center',
                'code' => 'GJ-FC-01',
                'company_name' => 'Metis Western Logistics',
                'gstin' => '24DDDDD3333D4Z8',
                'phone' => '+91-7766554433',
                'email' => 'gujarat@metis.example.com',
                'reference_no' => 'WH-REF-004',
                'seed_lic_no' => 'SL-1004',
                'pesti_lic_no' => 'PL-1004',
                'address' => 'Sarkhej-Bavla Highway',
                'address_line_1' => 'Plot No 8, Changodar',
                'address_line_2' => 'Sarkhej-Bavla Highway',
                'village_name' => 'Changodar',
                'post_office' => 'Changodar',
                'taluka' => 'Sanand',
                'city' => 'Ahmedabad',
                'state' => 'Gujarat',
                'pincode' => '382213',
                'status' => 'active',
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Rajasthan Supply Hub',
                'code' => 'RJ-SH-01',
                'company_name' => 'Metis Northern Logistics',
                'gstin' => '08EEEEE4444E5Z9',
                'phone' => '+91-6655443322',
                'email' => 'rajasthan@metis.example.com',
                'reference_no' => 'WH-REF-005',
                'seed_lic_no' => 'SL-1005',
                'pesti_lic_no' => 'PL-1005',
                'address' => 'VKI Area, Jaipur',
                'address_line_1' => 'Plot 42, VKI Area',
                'address_line_2' => 'Sikar Road',
                'village_name' => 'Jaipur',
                'post_office' => 'VKI Area',
                'taluka' => 'Jaipur',
                'city' => 'Jaipur',
                'state' => 'Rajasthan',
                'pincode' => '302013',
                'status' => 'active',
                'is_default' => false,
                'is_active' => true,
            ],
        ];

        foreach ($warehouses as $warehouse) {
            Warehouse::updateOrCreate(['code' => $warehouse['code']], $warehouse);
        }
    }
}
