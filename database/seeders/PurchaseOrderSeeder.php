<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Carbon\Carbon;
use Illuminate\Support\Str;

class PurchaseOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('en_IN');
        $suppliers = DB::table('suppliers')->pluck('id')->toArray();
        $warehouses = DB::table('warehouses')->pluck('id')->toArray();
        $products = DB::table('products')->pluck('id')->toArray();
        $users = DB::table('users')->pluck('id')->toArray();

        if (empty($suppliers) || empty($warehouses) || empty($products)) {
            return;
        }

        $adminUserId = $users[0] ?? null;

        for ($i = 1; $i <= 10; $i++) {
            $poId = DB::table('purchase_orders')->insertGetId([
                'po_number' => 'PO-' . date('Ym') . '-' . str_pad((string)$i, 4, '0', STR_PAD_LEFT),
                'supplier_id' => $faker->randomElement($suppliers),
                'warehouse_id' => $faker->randomElement($warehouses),
                'status' => 'received',
                'approved_by' => $adminUserId,
                'approved_at' => Carbon::now()->subDays(rand(5, 15)),
                'expected_delivery_date' => Carbon::now()->subDays(rand(1, 4)),
                'total_amount' => 0,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'net_amount' => 0,
                'notes' => 'Seeded PO',
                'created_by' => $adminUserId,
                'updated_by' => $adminUserId,
                'created_at' => Carbon::now()->subDays(rand(10, 20)),
                'updated_at' => Carbon::now()->subDays(rand(10, 20)),
            ]);

            $totalAmt = 0;
            $itemsCount = rand(2, 5);
            $poItems = [];
            for ($j = 0; $j < $itemsCount; $j++) {
                $qty = rand(50, 200);
                $price = rand(100, 1000);
                $total = $qty * $price;
                $totalAmt += $total;

                $poItems[] = [
                    'purchase_order_id' => $poId,
                    'product_id' => $faker->randomElement($products),
                    'quantity' => $qty,
                    'received_qty' => $qty,
                    'unit_price' => $price,
                    'tax_rate' => 0,
                    'tax_amount' => 0,
                    'discount_amount' => 0,
                    'total_price' => $total,
                    'net_amount' => $total,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('purchase_order_items')->insert($poItems);
            
            DB::table('purchase_orders')->where('id', $poId)->update([
                'total_amount' => $totalAmt,
                'net_amount' => $totalAmt,
            ]);

            // Create Goods Receipt
            $grnId = DB::table('goods_receipts')->insertGetId([
                'grn_number' => 'GRN-' . date('Ym') . '-' . str_pad((string)$i, 4, '0', STR_PAD_LEFT),
                'purchase_order_id' => $poId,
                'warehouse_id' => $faker->randomElement($warehouses),
                'received_date' => Carbon::now()->subDays(rand(1, 3)),
                'status' => 'completed',
                'notes' => 'Seeded GRN',
                'created_by' => $adminUserId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $grnItems = [];
            $poItemIds = DB::table('purchase_order_items')->where('purchase_order_id', $poId)->get();
            foreach ($poItemIds as $poi) {
                $grnItems[] = [
                    'goods_receipt_id' => $grnId,
                    'purchase_order_item_id' => $poi->id,
                    'product_id' => $poi->product_id,
                    'batch_number' => 'BATCH-' . strtoupper(Str::random(6)),
                    'manufacturing_date' => Carbon::now()->subMonths(2),
                    'expiry_date' => Carbon::now()->addMonths(12),
                    'received_qty' => $poi->quantity,
                    'accepted_qty' => $poi->quantity,
                    'rejected_qty' => 0,
                    'notes' => 'All good',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('goods_receipt_items')->insert($grnItems);
        }
    }
}
