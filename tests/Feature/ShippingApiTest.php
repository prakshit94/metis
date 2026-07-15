<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Orders\Models\Order;
use App\Modules\Users\Models\Permission;
use App\Modules\Users\Models\Role;
use App\Modules\Orders\Models\Shipment;
use App\Modules\Catalog\Models\Service;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ShippingApiTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected function setUp(): void
    {
        if (($_ENV['DB_CONNECTION'] ?? null) === 'sqlite' && ! in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('The sqlite PDO driver is not available in this PHP runtime.');
        }

        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Seed roles & permissions
        $permissions = [
            'shipping.view',
            'shipping.manage',
        ];
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $adminRole = Role::findOrCreate('Super Admin', 'web');
        $adminRole->syncPermissions($permissions);

        // Create Admin user
        $this->admin = User::create([
            'name' => 'Shipping Admin',
            'email' => 'shipping-admin@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $this->admin->assignRole('Super Admin');

        $this->actingAs($this->admin);
    }

    public function test_shipping_services_crud_flow(): void
    {
        // 1. Create a Shipping Service
        $response = $this->postJson('/api/shipping/services', [
            'code' => 'BLUEDART',
            'name' => 'Blue Dart Express',
            'description' => 'Fastest domestic delivery service',
            'is_active' => true,
        ]);

        $response->assertCreated();
        $serviceId = $response->json('service.id');
        $this->assertNotNull($serviceId);
        $this->assertEquals('BLUEDART', $response->json('service.code'));

        // 2. List Shipping Services
        $response = $this->getJson('/api/shipping/services');
        $response->assertOk();
        $this->assertTrue(collect($response->json('data'))->contains('id', $serviceId));

        // 3. Update Shipping Service
        $response = $this->patchJson("/api/shipping/services/{$serviceId}", [
            'code' => 'BLUEDART_EXP',
            'name' => 'Blue Dart Express Pro',
            'description' => 'Updated domestic delivery description',
            'is_active' => true,
        ]);

        $response->assertOk();
        $this->assertEquals('BLUEDART_EXP', $response->json('service.code'));
        $this->assertEquals('Blue Dart Express Pro', $response->json('service.name'));

        // 4. Toggle Active Status
        $response = $this->postJson("/api/shipping/services/{$serviceId}/toggle");
        $response->assertOk();
        $this->assertFalse((bool)$response->json('service.is_active'));

        // 5. Delete Shipping Service
        $response = $this->deleteJson("/api/shipping/services/{$serviceId}");
        $response->assertOk();
        $this->assertDatabaseMissing('services', ['id' => $serviceId]);
    }

    public function test_shipments_and_tracking_flow(): void
    {
        // Create Mock Order
        $order = Order::create([
            'order_no' => 'ORD-TEST12345',
            'status' => 'processing',
            'net_amount' => 120.00,
            'order_date' => now(),
        ]);

        // Create Shipment
        $shipment = Shipment::create([
            'order_id' => $order->id,
            'shipment_no' => 'SHP-MOCK123',
            'carrier_name' => 'DHL',
            'tracking_no' => 'TRK-DHL987654',
            'status' => 'pending',
        ]);

        // 1. List Shipments
        $response = $this->getJson('/api/shipping/shipments');
        $response->assertOk();
        $this->assertTrue(collect($response->json('data'))->contains('id', $shipment->id));

        // 2. Retrieve Tracking Events (empty initially)
        $response = $this->getJson("/api/shipping/shipments/{$shipment->id}/tracking");
        $response->assertOk();
        $this->assertCount(0, $response->json('events'));

        // 3. Update Shipment Status (to Shipped)
        $response = $this->postJson("/api/shipping/shipments/{$shipment->id}/status", [
            'status' => 'shipped',
            'location' => 'Main Sorting Hub',
            'description' => 'Package has been dispatched from sorting hub',
        ]);

        $response->assertOk();
        $this->assertEquals('in_transit', $response->json('shipment.status'));

        // Verify Order status was updated to dispatched too
        $this->assertEquals('dispatched', $order->fresh()->status);

        // 4. Add a custom tracking event
        $response = $this->postJson("/api/shipping/shipments/{$shipment->id}/tracking-event", [
            'event_name' => 'Custom Transit Event',
            'location' => 'Transit Hub B',
            'description' => 'Custom details about the transfer route',
        ]);

        $response->assertCreated();

        // 5. Verify the events are logged
        $response = $this->getJson("/api/shipping/shipments/{$shipment->id}/tracking");
        $response->assertOk();
        $this->assertCount(2, $response->json('events'));
        
        $eventNames = collect($response->json('events'))->pluck('event_name')->toArray();
        $this->assertContains('Custom Transit Event', $eventNames);
        $this->assertContains('Status Updated to In_transit', $eventNames);
    }
}
