<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Orders\Models\Coupon;
use App\Modules\Orders\Models\Offer;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Category;
use Illuminate\Support\Facades\DB;

class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        // Fetch some products and categories to use for our rules
        $products = Product::inRandomOrder()->limit(5)->pluck('id')->toArray();
        $categories = Category::inRandomOrder()->limit(3)->pluck('id')->toArray();

        $freeProduct = Product::inRandomOrder()->first();

        // ----------------------------------------------------
        // 1. SEED COUPONS
        // ----------------------------------------------------
        DB::table('coupons')->delete();

        $coupons = [
            // Standard Fixed Discount
            [
                'code' => 'WELCOME50',
                'type' => 'fixed',
                'value' => 50.00,
                'min_spend' => 500.00,
                'max_discount' => null,
                'applicable_categories' => null,
                'applicable_products' => null,
                'excluded_categories' => null,
                'excluded_products' => null,
                'free_product_id' => null,
                'free_qty' => 0,
                'expiry_date' => now()->addMonths(3)->toDateString(),
                'usage_limit' => 1000,
                'used_count' => 0,
                'status' => 'active',
                'is_active' => true,
                'cashback_percent' => null,
                'cashback_fixed' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Percentage Discount with max cap
            [
                'code' => 'SUMMER20',
                'type' => 'percentage',
                'value' => 20.00, // 20%
                'min_spend' => 1000.00,
                'max_discount' => 500.00,
                'applicable_categories' => json_encode($categories), // Specific categories
                'applicable_products' => null,
                'excluded_categories' => null,
                'excluded_products' => null,
                'free_product_id' => null,
                'free_qty' => 0,
                'expiry_date' => now()->addDays(30)->toDateString(),
                'usage_limit' => 500,
                'used_count' => 0,
                'status' => 'active',
                'is_active' => true,
                'cashback_percent' => 5.00, // 5% cashback
                'cashback_fixed' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Free Shipping Coupon
            [
                'code' => 'FREESHIP',
                'type' => 'free_shipping',
                'value' => 0,
                'min_spend' => 2000.00,
                'max_discount' => null,
                'applicable_categories' => null,
                'applicable_products' => null,
                'excluded_categories' => null,
                'excluded_products' => null,
                'free_product_id' => null,
                'free_qty' => 0,
                'expiry_date' => now()->addYear()->toDateString(),
                'usage_limit' => null, // unlimited
                'used_count' => 0,
                'status' => 'active',
                'is_active' => true,
                'cashback_percent' => null,
                'cashback_fixed' => 50.00, // Rs 50 flat cashback
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Free Product Coupon
            [
                'code' => 'BOGOGIFT',
                'type' => 'free_product',
                'value' => 0,
                'min_spend' => 5000.00,
                'max_discount' => null,
                'applicable_categories' => null,
                'applicable_products' => json_encode($products), // Requires these products
                'excluded_categories' => null,
                'excluded_products' => null,
                'free_product_id' => $freeProduct ? $freeProduct->id : null,
                'free_qty' => 1,
                'expiry_date' => now()->addDays(15)->toDateString(),
                'usage_limit' => 100,
                'used_count' => 0,
                'status' => 'active',
                'is_active' => true,
                'cashback_percent' => null,
                'cashback_fixed' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        Coupon::insert($coupons);

        // ----------------------------------------------------
        // 2. SEED OFFERS
        // ----------------------------------------------------
        DB::table('offers')->delete();

        $offers = [
            // Order Level Discount
            [
                'name' => 'Diwali Mega Sale',
                'type' => 'order_discount',
                'discount_type' => 'percentage',
                'value' => 15.00, // 15% off whole order
                'min_spend' => 5000.00,
                'max_discount' => 2000.00,
                'applicable_categories' => null,
                'applicable_products' => null,
                'excluded_categories' => null,
                'excluded_products' => null,
                'product_id' => null,
                'buy_qty' => 1,
                'get_qty' => 1,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addDays(10),
                'priority' => 10,
                'is_active' => true,
                'used_count' => 0,
                'cashback_percent' => 10.00, // 10% cashback
                'cashback_fixed' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Buy 2 Get 1 Free (BOGO)
            [
                'name' => 'Buy 2 Get 1 Free T-Shirts',
                'type' => 'bogo',
                'discount_type' => null,
                'value' => 0,
                'min_spend' => 0,
                'max_discount' => null,
                'applicable_categories' => json_encode($categories), 
                'applicable_products' => null,
                'excluded_categories' => null,
                'excluded_products' => null,
                'product_id' => null,
                'buy_qty' => 2,
                'get_qty' => 1,
                'starts_at' => now(),
                'ends_at' => now()->addMonths(1),
                'priority' => 5,
                'is_active' => true,
                'used_count' => 0,
                'cashback_percent' => null,
                'cashback_fixed' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Buy 1 Get 1 Free (Specific Product BOGO)
            [
                'name' => 'BOGO Sneaker Deal',
                'type' => 'bogo',
                'discount_type' => null,
                'value' => 0,
                'min_spend' => 0,
                'max_discount' => null,
                'applicable_categories' => null,
                'applicable_products' => json_encode($products), // Requires these specific products
                'excluded_categories' => null,
                'excluded_products' => null,
                'product_id' => null,
                'buy_qty' => 1,
                'get_qty' => 1,
                'starts_at' => now(),
                'ends_at' => now()->addMonths(1),
                'priority' => 6,
                'is_active' => true,
                'used_count' => 0,
                'cashback_percent' => null,
                'cashback_fixed' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Category specific flat discount
            [
                'name' => 'Electronics Fest - Rs 1000 Off',
                'type' => 'category_discount',
                'discount_type' => 'fixed',
                'value' => 1000.00,
                'min_spend' => 10000.00,
                'max_discount' => null,
                'applicable_categories' => json_encode($categories), 
                'applicable_products' => null,
                'excluded_categories' => null,
                'excluded_products' => null,
                'product_id' => null,
                'buy_qty' => 1,
                'get_qty' => 1,
                'starts_at' => now()->subWeek(),
                'ends_at' => now()->addWeeks(2),
                'priority' => 20, // Higher priority applies first
                'is_active' => true,
                'used_count' => 0,
                'cashback_percent' => null,
                'cashback_fixed' => 100.00, // Rs 100 flat cashback

                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Free Gift Product with Order
            [
                'name' => 'Free Earphones with Mobile',
                'type' => 'free_product',
                'discount_type' => null,
                'value' => 0,
                'min_spend' => 0,
                'max_discount' => null,
                'applicable_categories' => null,
                'applicable_products' => json_encode($products), // Buy any of these
                'excluded_categories' => null,
                'excluded_products' => null,
                'product_id' => $freeProduct ? $freeProduct->id : null, // Get this free
                'buy_qty' => 1,
                'get_qty' => 1,
                'starts_at' => now(),
                'ends_at' => now()->addDays(5),
                'priority' => 15,
                'is_active' => true,
                'used_count' => 0,
                'cashback_percent' => null,
                'cashback_fixed' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Free Gift Product with Category Purchase
            [
                'name' => 'Free Mug on Groceries',
                'type' => 'free_product',
                'discount_type' => null,
                'value' => 0,
                'min_spend' => 0,
                'max_discount' => null,
                'applicable_categories' => json_encode($categories), // Buy any item from these categories
                'applicable_products' => null, 
                'excluded_categories' => null,
                'excluded_products' => null,
                'product_id' => $freeProduct ? $freeProduct->id : null, // Get this free
                'buy_qty' => 1,
                'get_qty' => 1,
                'starts_at' => now(),
                'ends_at' => now()->addDays(5),
                'priority' => 16,
                'is_active' => true,
                'used_count' => 0,
                'cashback_percent' => null,
                'cashback_fixed' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        Offer::insert($offers);
    }
}
