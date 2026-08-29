<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InventoryAdjustmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        $warehouses = DB::table('warehouses')->pluck('id')->toArray();
        $products = DB::table('products')->pluck('id')->toArray();
        $users = DB::table('users')->pluck('id')->toArray();

        if (empty($warehouses) || empty($products) || empty($users)) {
            return;
        }

        for ($i = 1; $i <= 5; $i++) {
            $referenceNo = 'ADJ-'.date('Ym').'-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT);

            // Skip if already seeded (prevents duplicate key on re-seed)
            if (DB::table('inventory_adjustments')->where('reference_no', $referenceNo)->exists()) {
                continue;
            }

            $adjId = DB::table('inventory_adjustments')->insertGetId([
                'reference_no' => $referenceNo,
                'warehouse_id' => $faker->randomElement($warehouses),
                'adjusted_by' => $users[0],
                'reason' => $faker->randomElement(['Stock take variance', 'Damaged goods', 'Found stock']),
                'status' => 'approved',
                'created_at' => Carbon::now()->subDays(rand(1, 10)),
                'updated_at' => now(),
            ]);

            $items = [];
            for ($j = 0; $j < rand(1, 3); $j++) {
                $currentQty = rand(10, 50);
                $newQty = $currentQty + rand(-5, 5);

                $items[] = [
                    'adjustment_id' => $adjId,
                    'product_id' => $faker->randomElement($products),
                    'current_qty' => $currentQty,
                    'new_qty' => $newQty,
                    'difference' => $newQty - $currentQty,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('inventory_adjustment_items')->insert($items);
        }
    }
}
