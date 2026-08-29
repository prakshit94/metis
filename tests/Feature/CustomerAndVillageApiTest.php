<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Warehouse;
use App\Modules\Core\Models\Village;
use App\Modules\Customers\Models\Customer;
use App\Modules\Customers\Models\PartyAddress;
use App\Modules\Users\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerAndVillageApiTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected Village $village;

    protected function setUp(): void
    {
        if (($_ENV['DB_CONNECTION'] ?? null) === 'sqlite' && ! in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('The sqlite PDO driver is not available in this PHP runtime.');
        }

        parent::setUp();

        // Create admin user and authenticate
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin_'.uniqid().'@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $this->admin->assignRole('Super Admin');

        $this->actingAs($this->admin);

        // Seed a village
        $this->village = Village::create([
            'village_name' => 'Kawatha',
            'pincode' => '440001',
            'post_so_name' => 'Nagpur SO',
            'taluka_name' => 'Kamptee',
            'district_name' => 'Nagpur',
            'state_name' => 'Maharashtra',
        ]);

        // Seed a mock service for mappings constraint
        if (! DB::table('services')->where('id', 1)->orWhere('code', 'DELIVERY')->exists()) {
            DB::table('services')->insert([
                'id' => 1,
                'code' => 'DELIVERY',
                'name' => 'Delivery Service',
                'is_active' => true,
            ]);
        }
    }

    public function test_customer_crud_flow(): void
    {
        $phone = '987'.rand(1000000, 9999999);
        $email = 'ramesh_'.uniqid().'@example.com';

        // 1. Create Customer
        $response = $this->postJson('/api/customers', [
            'firstname' => 'Ramesh',
            'lastname' => 'Patil',
            'phone' => $phone,
            'email' => $email,
            'category' => 'individual',
            'crops' => ['Wheat', 'Cotton'],
            'irrigation_type' => ['Drip'],
            'status' => 'active',
        ]);

        $response->assertCreated();
        $customerId = $response->json('data.id');
        $this->assertNotNull($customerId);
        $this->assertEquals('Ramesh', $response->json('data.firstname'));

        // 2. Read Customer List
        $response = $this->getJson('/api/customers');
        $response->assertOk();
        $this->assertTrue(collect($response->json('data'))->contains('id', $customerId));

        // 3. Update Customer
        $response = $this->patchJson("/api/customers/{$customerId}", [
            'firstname' => 'Ramesh Kumar',
            'lastname' => 'Patil',
            'phone' => $phone,
            'category' => 'individual',
            'status' => 'active',
        ]);

        $response->assertOk();
        $this->assertEquals('Ramesh Kumar', $response->json('data.firstname'));

        // 4. Toggle Active
        $response = $this->patchJson("/api/customers/{$customerId}/toggle-active");
        $response->assertOk();
        $this->assertFalse($response->json('is_active'));

        // 5. Delete Customer (soft delete)
        $response = $this->deleteJson("/api/customers/{$customerId}");
        $response->assertOk();
        $this->assertSoftDeleted('parties', ['id' => $customerId]);

        // 6. Restore Customer
        $response = $this->patchJson("/api/customers/{$customerId}/restore");
        $response->assertOk();
        $this->assertDatabaseHas('parties', ['id' => $customerId, 'deleted_at' => null]);

        // 7. Force Delete Customer
        $response = $this->deleteJson("/api/customers/{$customerId}/force");
        $response->assertOk();
        $this->assertDatabaseMissing('parties', ['id' => $customerId]);
    }

    public function test_customer_address_management(): void
    {
        $customer = Customer::create([
            'firstname' => 'Suresh',
            'lastname' => 'Sharma',
            'phone' => '987'.rand(1000000, 9999999),
            'type' => 'customer',
            'category' => 'individual',
            'status' => 'active',
        ]);

        // 1. Add Address
        $response = $this->postJson("/api/customers/{$customer->id}/addresses", [
            'label' => 'Farm',
            'address_line_1' => 'Survey No. 88',
            'village_id' => $this->village->id,
            'city' => 'Nagpur',
            'state' => 'Maharashtra',
            'pincode' => '440001',
            'is_default' => true,
            'status' => 'active',
        ]);

        $response->assertCreated();
        $addressId = $response->json('data.id');
        $this->assertNotNull($addressId);

        // 2. Update Address
        $response = $this->patchJson("/api/customers/{$customer->id}/addresses/{$addressId}", [
            'label' => 'Primary Farm',
            'address_line_1' => 'Survey No. 88 (New)',
            'village_id' => $this->village->id,
            'city' => 'Nagpur',
            'state' => 'Maharashtra',
            'pincode' => '440001',
            'is_default' => true,
            'status' => 'active',
        ]);

        $response->assertOk();
        $this->assertEquals('Primary Farm', $response->json('data.label'));

        // 3. Delete Address
        $response = $this->deleteJson("/api/customers/{$customer->id}/addresses/{$addressId}");
        $response->assertOk();
        $this->assertSoftDeleted('party_addresses', ['id' => $addressId]);
    }

    public function test_village_management_and_search(): void
    {
        $name = 'Nhavi_'.uniqid();

        // 1. Create Village
        $response = $this->postJson('/api/villages', [
            'village_name' => $name,
            'pincode' => '411001',
            'post_so_name' => 'Pune SO',
            'taluka_name' => 'Haveli',
            'district_name' => 'Pune',
            'state_name' => 'Maharashtra',
        ]);

        $response->assertCreated();
        $villageId = $response->json('data.id');

        // 2. Update Village & Service Mappings
        $response = $this->patchJson("/api/villages/{$villageId}", [
            'village_name' => $name.' Budruk',
            'pincode' => '411001',
            'services' => [
                1 => [
                    'is_available' => true,
                    'priority' => 10,
                    'remarks' => 'Fast delivery',
                ],
            ],
        ]);

        $response->assertOk();
        $this->assertEquals($name.' Budruk', $response->json('data.village_name'));

        // 3. Search Village
        $response = $this->getJson('/api/villages/search?q='.$name);
        $response->assertOk();
        $this->assertTrue(collect($response->json('data'))->contains('id', $villageId));
    }

    public function test_customer_profile_web_view(): void
    {
        $customer = Customer::create([
            'firstname' => 'Test',
            'lastname' => 'Customer',
            'phone' => '987'.rand(1000000, 9999999),
            'type' => 'customer',
            'category' => 'individual',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->get(route('customers.show', $customer));
        $response->assertOk();
        $response->assertViewIs('customers.show');
        $response->assertViewHas('customer');
    }

    public function test_customer_place_order_web(): void
    {
        $customer = Customer::create([
            'firstname' => 'Test',
            'lastname' => 'Customer',
            'phone' => '987'.rand(1000000, 9999999),
            'type' => 'customer',
            'category' => 'individual',
            'status' => 'active',
        ]);

        $address = PartyAddress::create([
            'party_id' => $customer->id,
            'label' => 'Farm',
            'address_line_1' => 'Survey 10',
            'city' => 'Nagpur',
            'state' => 'Maharashtra',
            'pincode' => '440001',
            'status' => 'active',
        ]);

        $warehouse = Warehouse::create([
            'name' => 'Main Warehouse '.uniqid(),
            'code' => 'WH-'.uniqid(),
            'status' => 'active',
        ]);

        $category = Category::create(['name' => 'Seeds '.uniqid(), 'slug' => 'seeds-'.uniqid()]);
        $product = Product::create([
            'name' => 'Wheat '.uniqid(),
            'slug' => 'wheat-'.uniqid(),
            'sku' => 'WHEAT-'.uniqid(),
            'category_id' => $category->id,
            'selling_price' => 100.0,
            'purchase_price' => 80.0,
            'status' => 'active',
            'is_sku_enabled' => true,
        ]);

        $cart = json_encode([
            [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'quantity' => 2,
                'price' => 100.0,
                'hsn_code' => '',
                'tax_rate' => 18,
            ],
        ]);

        $response = $this->actingAs($this->admin)->post(route('customers.orders.place', $customer), [
            'cart' => $cart,
            'tax_amount' => 36.0,
            'subtotal' => 200.0,
            'grand_total' => 236.0,
            'warehouse_id' => $warehouse->id,
            'address_id' => $address->id,
        ]);

        $response->assertRedirect(route('customers.show', $customer));
        $response->assertSessionHas('success', 'Order placed successfully!');
        $response->assertSessionHas('active_tab', 'history');

        $this->assertDatabaseHas('orders', [
            'party_id' => $customer->id,
            'net_amount' => 200.0,
        ]);
    }
}
