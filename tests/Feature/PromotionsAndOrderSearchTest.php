<?php

namespace Tests\Feature;

use App\Modules\Orders\Models\Coupon;
use App\Modules\Orders\Models\Offer;
use App\Modules\Catalog\Models\Product;
use App\Modules\Users\Models\User;
use App\Modules\Catalog\Models\Warehouse;
use App\Modules\Catalog\Models\Category;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PromotionsAndOrderSearchTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles and permissions first
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        // Fetch or create the Master Admin or create a new user with Super Admin role
        $this->admin = User::where('email', 'admin@example.com')->first() ?: User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'is_active' => true,
        ]);

        if (!$this->admin->hasRole('Super Admin')) {
            $this->admin->assignRole('Super Admin');
        }
    }

    public function test_product_search_api_returns_paginated_active_products(): void
    {
        $uniq = uniqid();
        $category = Category::create(['name' => 'Seeds ' . $uniq, 'slug' => 'seeds-' . $uniq]);
        $product = Product::create([
            'name' => 'Premium Wheat Seeds ' . $uniq,
            'slug' => 'premium-wheat-seeds-' . $uniq,
            'sku' => 'WHEAT-PREM-' . $uniq,
            'category_id' => $category->id,
            'selling_price' => 500.0,
            'purchase_price' => 400.0,
            'status' => 'active',
            'is_sku_enabled' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('products.search.api', ['q' => 'Wheat']));

        $response->assertStatus(200)
            ->assertJsonFragment(['sku' => 'WHEAT-PREM-' . $uniq])
            ->assertJsonStructure([
                'data',
                'current_page',
                'total',
            ]);
    }

    public function test_coupon_validation_api(): void
    {
        $uniq = uniqid();
        $code = 'SAVE' . $uniq;
        $coupon = Coupon::create([
            'code' => $code,
            'type' => 'fixed',
            'value' => 100.00,
            'min_spend' => 500.00,
            'is_active' => true,
        ]);

        // Validation fails if spend is below min_spend
        $response = $this->actingAs($this->admin)
            ->postJson(route('coupons.validate'), [
                'code' => $code,
                'subtotal' => 400.00,
            ]);

        $response->assertStatus(200)
            ->assertJson(['valid' => false]);

        // Validation succeeds if spend is met
        $response = $this->actingAs($this->admin)
            ->postJson(route('coupons.validate'), [
                'code' => $code,
                'subtotal' => 600.00,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'valid' => true,
                'discount' => 100.00,
            ]);
    }

    public function test_coupons_crud_and_bulk_operations(): void
    {
        $uniq = uniqid();
        $code = 'WELC' . $uniq;
        // Store Coupon
        $response = $this->actingAs($this->admin)
            ->postJson(route('api.promotions.coupons.store'), [
                'code' => $code,
                'type' => 'percentage',
                'value' => 50.0,
                'is_active' => true,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('coupons', ['code' => $code]);

        $coupon = Coupon::where('code', $code)->first();

        // Update Coupon
        $response = $this->actingAs($this->admin)
            ->patchJson(route('api.promotions.coupons.update', $coupon), [
                'value' => 40.0,
            ]);

        $response->assertStatus(200);
        $this->assertEquals(40.0, (float) $coupon->fresh()->value);

        // Toggle Status
        $response = $this->actingAs($this->admin)
            ->patchJson(route('api.promotions.coupons.toggle', $coupon));

        $response->assertStatus(200);
        $this->assertFalse($coupon->fresh()->is_active);
    }

    public function test_offers_filtering_by_status(): void
    {
        $uniq = uniqid();
        
        $activeOffer = Offer::create([
            'name' => 'Active Offer ' . $uniq,
            'type' => 'order_discount',
            'discount_type' => 'percentage',
            'value' => 10.0,
            'is_active' => true,
        ]);

        $inactiveOffer = Offer::create([
            'name' => 'Inactive Offer ' . $uniq,
            'type' => 'order_discount',
            'discount_type' => 'percentage',
            'value' => 15.0,
            'is_active' => false,
        ]);

        // Filter active
        $response = $this->actingAs($this->admin)
            ->getJson(route('api.promotions.offers.index', ['status' => 'active']));

        $response->assertStatus(200);
        $data = $response->json('data.data');
        $names = array_column($data, 'name');
        $this->assertContains('Active Offer ' . $uniq, $names);
        $this->assertNotContains('Inactive Offer ' . $uniq, $names);

        // Filter inactive
        $response = $this->actingAs($this->admin)
            ->getJson(route('api.promotions.offers.index', ['status' => 'inactive']));

        $response->assertStatus(200);
        $data = $response->json('data.data');
        $names = array_column($data, 'name');
        $this->assertContains('Inactive Offer ' . $uniq, $names);
        $this->assertNotContains('Active Offer ' . $uniq, $names);
    }

    public function test_offers_usage_tracking(): void
    {
        Offer::query()->delete();

        $party = \App\Modules\Customers\Models\Party::create([
            'firstname' => 'Usage',
            'lastname' => 'Customer',
            'type' => 'customer',
            'is_active' => true,
        ]);

        $address = \App\Modules\Customers\Models\PartyAddress::create([
            'party_id' => $party->id,
            'label' => 'Primary Address',
            'address_line_1' => '123 test street',
            'city' => 'test city',
            'state' => 'test state',
            'pincode' => '400001',
            'is_default' => true,
        ]);

        $warehouse = Warehouse::create([
            'name' => 'Usage Warehouse',
            'code' => 'US-WH',
            'status' => 'active',
        ]);

        $category = Category::create(['name' => 'Seeds ' . uniqid(), 'slug' => 'seeds-' . uniqid()]);
        $product = Product::create([
            'name' => 'Test Offer Product ' . uniqid(),
            'sku' => 'TEST-OFFER-PROD',
            'slug' => 'test-offer-prod',
            'category_id' => $category->id,
            'selling_price' => 100.00,
            'purchase_price' => 50.00,
            'status' => 'active',
        ]);

        $orderOffer = Offer::create([
            'name' => 'Order Discount Offer',
            'type' => 'order_discount',
            'discount_type' => 'percentage',
            'value' => 10.0,
            'is_active' => true,
        ]);

        $bogoOffer = Offer::create([
            'name' => 'BOGO Offer',
            'type' => 'bogo',
            'discount_type' => 'fixed',
            'value' => 0.0,
            'is_active' => true,
            'buy_qty' => 1,
            'get_qty' => 1,
        ]);

        $this->assertEquals(0, $orderOffer->fresh()->used_count);
        $this->assertEquals(0, $bogoOffer->fresh()->used_count);

        $response = $this->actingAs($this->admin)
            ->postJson('/orders', [
                'type' => 'sale',
                'party_id' => $party->id,
                'warehouse_id' => $warehouse->id,
                'shipping_address_id' => $address->id,
                'billing_address_id' => $address->id,
                'order_date' => now()->toDateString(),
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 2,
                        'unit_price' => 100.00,
                        'total_amount' => 200.00,
                    ]
                ],
                'applied_offer_id' => $orderOffer->id,
                'applied_bogo_ids' => [$bogoOffer->id],
                'total_amount' => 200.00,
                'tax_amount' => 0.00,
                'discount_amount' => 20.00,
                'net_amount' => 180.00,
            ]);

        $response->assertStatus(200);
        $this->assertEquals(1, $orderOffer->fresh()->used_count);
        $this->assertEquals(1, $bogoOffer->fresh()->used_count);
    }
}

