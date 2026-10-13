<?php

namespace Database\Seeders;

use App\Models\IndiaPostBarcodeRange;
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
        $provider = config('shipping.providers.india_post');
        $defaultOffice = [
            [
                'id' => $officeId,
                'pickup_dropoff_office_id' => $provider['pickup_dropoff_office_id'],
                'drop_off_pincode' => $provider['drop_off_pincode'],
                'booking_office_name' => $provider['booking_office_name'],
                'booking_office_pin' => $provider['booking_office_pin'],
                'status' => 'active',
                'is_default' => true,
                'api_base_url' => $provider['base_url'],
                'api_username' => $provider['username'],
                'api_password' => Crypt::encryptString($provider['password']),
                'bulk_customer_id' => $provider['bulk_customer_id'],
                'contract_sp_doc' => $provider['contracts']['SP_INLAND_DOC'],
                'contract_sp_parcel' => $provider['contracts']['SP_INLAND_PARCEL'],
                'contract_bp' => $provider['contracts']['BUSINESS_PARCEL'],
                'contract_24_sp_doc' => $provider['contracts']['24_SPEEDPOST_DOC'],
                'contract_24_spp_parspl' => $provider['contracts']['24_SPP_PARSPL'],
                'contract_48_sp_doc' => $provider['contracts']['48_SPEEDPOST_DOC'],
            ],
        ];

        SystemSetting::updateOrCreate(
            ['key' => 'india_post_offices'],
            ['value' => json_encode($defaultOffice)]
        );

        // Never seed synthetic AWB numbers in production. India Post assigns
        // ranges to the customer; configure the assigned range explicitly.
        $barcodePrefix = $provider['barcode']['prefix'];
        if ($barcodePrefix) {
            $start = (int) $provider['barcode']['start'];
            $end = (int) $provider['barcode']['end'];
            IndiaPostBarcodeRange::updateOrCreate(
                ['office_id' => $officeId, 'prefix' => $barcodePrefix],
                [
                    'start_sequence' => $start,
                    'end_sequence' => $end,
                    'current_sequence' => $start,
                    'status' => 'active',
                ]
            );
        }

        $this->command->info('India Post default office and barcode range seeded successfully!');
    }
}
