<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Modules\Users\Models\User;
use App\Modules\Customers\Models\Customer;
use App\Modules\Core\Models\Village;
class AddressTest extends TestCase {
    public function testAddressCreation() {
        $user = User::first();
        $customer = Customer::create([
            'firstname' => 'Test', 'lastname' => 'Test', 'status' => 'active', 'type' => 'customer'
        ]);
        $village = Village::first();
        $response = $this->actingAs($user)->postJson("/api/customers/{$customer->id}/addresses", [
            'label' => 'Home', 'is_default' => '1', 'status' => 'active', 'address_line_1' => '123 Test St',
            'village_id' => $village->id
        ]);
        file_put_contents('address_test_output.txt', $response->getContent());
    }
}
