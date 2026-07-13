<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Offer;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Category;
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
}
