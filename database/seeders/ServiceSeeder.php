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
