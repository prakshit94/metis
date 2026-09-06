<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;

class IndiaPostSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultOffice = [
            [
                'id' => (string) time(),
                'pickup_dropoff_office_id' => '21260024',
                'drop_off_pincode' => '600001',
                'booking_office_name' => 'Default Booking Office',
                'booking_office_pin' => '600001',
                'status' => 'active',
                'is_default' => true,
                'api_base_url' => 'https://test.cept.gov.in/beextcustomer',
                'api_username' => 'testuser',
                'api_password' => Crypt::encryptString('testpassword'),
                'bulk_customer_id' => '3000064781',
                'contract_sp_doc' => '',
                'contract_sp_parcel' => '',
                'contract_bp' => '',
                'contract_24_sp_doc' => '',
                'contract_24_spp_parspl' => '',
                'contract_48_sp_doc' => '',
                'barcode_prefix' => 'EA',
                'barcode_start' => '10000000',
                'barcode_end' => '19999999',
                'barcode_current' => '10000000',
            ]
        ];

        SystemSetting::updateOrCreate(
            ['key' => 'india_post_offices'],
            ['value' => json_encode($defaultOffice)]
        );
        
        $this->command->info('India Post default office settings seeded successfully!');
    }
}
