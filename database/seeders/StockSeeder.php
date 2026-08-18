<?php

namespace Database\Seeders;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StockSeeder extends Seeder
{
    public function run(): void
    {
        $warehouse = Warehouse::where('code', 'MAIN-ECOM')->first();
        if (!$warehouse) return;

        $products = Product::all();
        
        foreach ($products as $product) {
            DB::table('stocks')->updateOrInsert(
                [
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                ],
                [
                    'quantity' => 10,
                    'reserved_qty' => 0,
                    'dispatched_qty' => 0,
                    'committed_qty' => 0,
                    'in_transit_qty' => 0,
                    'damaged_qty' => 0,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
