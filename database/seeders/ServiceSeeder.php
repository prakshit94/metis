<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Catalog\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'code' => 'FEDEX_EXPRESS',
                'name' => 'FedEx Express',
                'description' => 'Priority overnight and 2-day express delivery service by FedEx. Ideal for time-sensitive shipments.',
                'is_active' => true,
            ],
            [
                'code' => 'DHL_ECOMMERCE',
                'name' => 'DHL eCommerce',
                'description' => 'Cost-effective parcel delivery for eCommerce businesses. Pan-India coverage with real-time tracking.',
                'is_active' => true,
            ],
            [
                'code' => 'BLUEDART_APEX',
                'name' => 'BlueDart Apex',
                'description' => 'Premium domestic air express service by BlueDart. Delivers to 220+ countries and territories.',
                'is_active' => true,
            ],
            [
                'code' => 'DELHIVERY_SURFACE',
                'name' => 'Delhivery Surface',
                'description' => 'Economical surface freight option for bulk shipments across India. Best for non-urgent deliveries.',
                'is_active' => true,
            ],
            [
                'code' => 'UPS_STANDARD',
                'name' => 'UPS Standard',
                'description' => 'Reliable ground delivery service by UPS. Guaranteed delivery windows with full shipment visibility.',
                'is_active' => true,
            ],
            [
                'code' => 'XPRESSBEES_AIR',
                'name' => 'Xpressbees Air',
                'description' => 'Fast air delivery across major Indian metros and tier-2 cities. Next-day delivery for priority orders.',
                'is_active' => true,
            ],
            [
                'code' => 'ECOM_EXPRESS',
                'name' => 'Ecom Express',
                'description' => 'End-to-end eCommerce logistics tailored for D2C brands. Includes COD collection and reverse logistics.',
                'is_active' => false,
            ],
            [
                'code' => 'DTDC_LITE',
                'name' => 'DTDC Lite',
                'description' => 'Budget-friendly courier service for standard parcels. Wide network covering 17,500+ pin codes.',
                'is_active' => false,
            ],
            [
                'code' => 'INDIA_POST',
                'name' => 'India Post',
                'description' => 'Government-backed postal service for deep rural and remote deliveries. Integrated with physical dimensions.',
                'is_active' => true,
            ],
        ];

        foreach ($services as $service) {
            Service::firstOrCreate(['code' => $service['code']], $service);
        }
    }
}
