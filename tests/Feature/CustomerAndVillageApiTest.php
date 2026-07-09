<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Party;
use App\Models\PartyAddress;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAndVillageApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Village $village;

    protected function setUp(): void
    {
        if (($_ENV['DB_CONNECTION'] ?? null) === 'sqlite' && ! in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('The sqlite PDO driver is not available in this PHP runtime.');
        }

        parent::setUp();

        // Create admin user and authenticate
        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'is_active' => true,
        ]);

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
        \Illuminate\Support\Facades\DB::table('services')->insert([
            'id' => 1,
            'code' => 'DELIVERY',
            'name' => 'Delivery Service',
            'is_active' => true,
        ]);
    }

    public function test_customer_crud_flow(): void
    {
        // 1. Create Customer
        $response = $this->postJson('/api/customers', [
            'firstname' => 'Ramesh',
            'lastname' => 'Patil',
            'phone' => '9876501001',
            'email' => 'ramesh@example.com',
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
        $this->assertCount(1, $response->json('data'));

        // 3. Update Customer
        $response = $this->patchJson("/api/customers/{$customerId}", [
            'firstname' => 'Ramesh Kumar',
            'lastname' => 'Patil',
            'phone' => '9876501001',
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
            'phone' => '9876501002',
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
        // 1. Create Village
        $response = $this->postJson('/api/villages', [
            'village_name' => 'Nhavi',
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
            'village_name' => 'Nhavi Budruk',
            'pincode' => '411001',
            'services' => [
                1 => [
                    'is_available' => true,
                    'priority' => 10,
                    'remarks' => 'Fast delivery',
                ]
            ]
        ]);

        $response->assertOk();
        $this->assertEquals('Nhavi Budruk', $response->json('data.village_name'));

        // 3. Search Village
        $response = $this->getJson('/api/villages/search?q=Nhavi');
        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }
}
