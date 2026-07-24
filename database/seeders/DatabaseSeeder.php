<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            CategorySeeder::class,
            BrandSeeder::class,
            UnitOfMeasureSeeder::class,
            TaxRateSeeder::class,
            HsnCodeSeeder::class,
            WarehouseSeeder::class,
            ProductSeeder::class,
            StockSeeder::class,
            VillageSeeder::class,
            PartyDataSeeder::class,
            ServiceSeeder::class,
            ReturnReasonsSeeder::class,
            RescheduleReasonsSeeder::class,
            DeliveryFailureReasonsSeeder::class,
            PromotionSeeder::class,
        ]);
    }
}
