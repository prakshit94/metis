<?php

namespace Tests\Unit\Shipping;

use Tests\TestCase;
use App\Services\Shipping\Providers\IndiaPostProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class IndiaPostProviderTest extends TestCase
{
    public function test_authenticate_caches_and_returns_token()
    {
        Http::fake([
            '*/v1/access/login' => Http::response([
                'success' => true,
                'data' => [
                    'access_token' => 'fake_token',
                    'expires_in' => 3600
                ]
            ], 200)
        ]);

        Cache::shouldReceive('remember')
            ->once()
            ->with('india_post_access_token', \Mockery::any(), \Mockery::type('Closure'))
            ->andReturn('fake_token');

        $provider = new IndiaPostProvider();
        
        $token = $provider->authenticate();

    }

    public function test_create_shipment_calculates_dimensions_and_weight()
    {
        Http::fake([
            '*/v1/access/login' => Http::response(['success' => true, 'data' => ['access_token' => 'token']], 200),
            '*/process-articles/*' => Http::response([
                'success' => true, 
                'valid_articles' => [['barcode_no' => 'AWB123', 'tariff' => 150]]
            ], 200)
        ]);

        $provider = new IndiaPostProvider();

        $product = new \App\Modules\Catalog\Models\Product([
            'weight_g' => 200,
            'length_cm' => 15,
            'width_cm' => 20,
            'height_cm' => 5,
        ]);

        $orderItem = new \App\Modules\Orders\Models\OrderItem([
            'quantity' => 2
        ]);
        // Mock the relationship
        $orderItem->setRelation('product', $product);

        $order = new \App\Modules\Orders\Models\Order();
        $order->setRelation('items', collect([$orderItem]));
        
        \Illuminate\Database\Eloquent\Model::unguard();
        $address = new \App\Modules\Customers\Models\PartyAddress([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_line_1' => '123 Main St',
            'city' => 'Delhi',
            'postal_code' => '110001',
            'phone_number' => '1234567890'
        ]);
        $order->setRelation('address', $address);

        $result = $provider->createShipment($order);

        $this->assertEquals('AWB123', $result['tracking_number']);
        $this->assertEquals(400, $result['actual_weight_g']);
        $this->assertEquals(15, $result['length_cm']);
        $this->assertEquals(20, $result['width_cm']);
        $this->assertEquals(20, $result['height_cm']); // 10 (base) + 5 * 2 quantity
    }
}
