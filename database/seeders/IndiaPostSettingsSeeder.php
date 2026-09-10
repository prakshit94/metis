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
        $officeId = (string) time();
        $defaultOffice = [
            [
                'id' => $officeId,
                'pickup_dropoff_office_id' => '21260024',
                'drop_off_pincode' => '600001',
                'booking_office_name' => 'Default Booking Office',
                'booking_office_pin' => '600001',
                'status' => 'active',
                'is_default' => true,
                'api_base_url' => 'https://test.cept.gov.in/beextcustomer',
                'api_username' => '9999999999',
                'api_password' => Crypt::encryptString('Dop@1234'),
                'bulk_customer_id' => '3000064781',
                'contract_sp_doc' => '41585456',
                'contract_sp_parcel' => '41585456',
                'contract_bp' => '41367422',
                'contract_24_sp_doc' => '41469430',
                'contract_24_spp_parspl' => '41918281',
                'contract_48_sp_doc' => '41471113',
            ]
        ];

        SystemSetting::updateOrCreate(
            ['key' => 'india_post_offices'],
            ['value' => json_encode($defaultOffice)]
        );

        // Seed a default active barcode range for this office
        \App\Models\IndiaPostBarcodeRange::updateOrCreate(
            ['office_id' => $officeId, 'prefix' => 'EA'],
            [
                'start_sequence' => 10000000,
                'end_sequence' => 19999999,
                'current_sequence' => 10000000,
                'status' => 'active'
            ]
        );

        $this->command->info('India Post default office and barcode range seeded successfully!');
    }
}
