<?php

namespace Tests\Feature\Shipping;

use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\Shipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndiaPostWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_updates_shipment_to_delivered()
    {
        // Setup
        $order = Order::factory()->create(['status' => 'dispatched']);
        $shipment = Shipment::create([
            'order_id' => $order->id,
            'shipment_no' => 'SHP-123',
            'carrier_name' => 'India Post',
            'tracking_no' => 'ET21433001XIN',
            'status' => 'in_transit',
        ]);

        // Act
        $response = $this->postJson(route('api.webhooks.india-post'), [
            'article_number' => 'ET21433001XIN',
            'event_code' => 'ITEM_DELIVERED',
            'event_office_name' => 'Test Location',
        ]);

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseHas('shipments', [
            'id' => $shipment->id,
            'status' => 'delivered',
        ]);

        $this->assertDatabaseHas('shipment_tracking_events', [
            'shipment_id' => $shipment->id,
            'status' => 'delivered',
            'location' => 'Test Location',
        ]);
    }
}
