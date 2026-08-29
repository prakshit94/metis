<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StockTransferSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        $warehouses = DB::table('warehouses')->pluck('id')->toArray();
        $products = DB::table('products')->pluck('id')->toArray();

        if (count($warehouses) < 2 || empty($products)) {
            return;
        }

        for ($i = 1; $i <= 5; $i++) {
            $from = $faker->randomElement($warehouses);
            $to = $faker->randomElement(array_filter($warehouses, fn ($w) => $w !== $from));

            $transferId = DB::table('stock_transfers')->insertGetId([
                'transfer_no' => 'TRN-'.date('Ym').'-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'from_warehouse_id' => $from,
                'to_warehouse_id' => $to,
                'status' => 'received',
                'sent_at' => Carbon::now()->subDays(rand(2, 5)),
                'received_at' => Carbon::now()->subDays(rand(1, 2)),
                'created_at' => Carbon::now()->subDays(rand(2, 5)),
                'updated_at' => Carbon::now()->subDays(rand(1, 2)),
            ]);

            $items = [];
            for ($j = 0; $j < rand(1, 3); $j++) {
                $items[] = [
                    'stock_transfer_id' => $transferId,
                    'product_id' => $faker->randomElement($products),
                    'quantity' => rand(10, 50),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('stock_transfer_items')->insert($items);
        }
    }
}
