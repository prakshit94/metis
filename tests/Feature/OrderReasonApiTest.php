<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Orders\Models\CancelReason;
use App\Modules\Orders\Models\DeliveryFailureReason;
use App\Modules\Orders\Models\RescheduleReason;
use App\Modules\Users\Models\Permission;
use App\Modules\Users\Models\Role;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class OrderReasonApiTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'orderreason-view',
            'orderreason-create',
            'orderreason-edit',
            'orderreason-delete',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $superAdminRole = Role::findOrCreate('Super Admin', 'web');
        $superAdminRole->syncPermissions($permissions);

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin_order_reasons@example.com',
            'password' => Hash::make('Password123'),
            'is_active' => true,
        ]);
        $admin->assignRole('Super Admin');
        $this->actingAs($admin);
        $this->withoutVite();
    }

    public function test_can_list_reasons()
    {
        CancelReason::create(['reason' => 'Customer requested', 'is_active' => true]);

        $response = $this->getJson('/order-reasons');
        $response->assertStatus(200);

        $response = $this->getJson('/api/order-reasons/cancel');
        $response->assertStatus(200)
            ->assertJsonFragment(['reason' => 'Customer requested']);
    }

    public function test_can_create_reason()
    {
        $response = $this->postJson('/api/order-reasons/return', [
            'reason' => 'Damaged item',
            'is_active' => true,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['reason' => 'Damaged item']);

        $this->assertDatabaseHas('return_reasons', ['reason' => 'Damaged item']);
    }

    public function test_can_update_reason()
    {
        $reason = RescheduleReason::create(['reason' => 'Not available', 'is_active' => true]);

        $response = $this->putJson("/api/order-reasons/reschedule/{$reason->id}", [
            'reason' => 'Customer requested reschedule',
            'is_active' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['reason' => 'Customer requested reschedule']);

        $this->assertDatabaseHas('reschedule_reasons', [
            'id' => $reason->id,
            'reason' => 'Customer requested reschedule',
            'is_active' => false,
        ]);
    }

    public function test_can_delete_reason()
    {
        $reason = DeliveryFailureReason::create(['reason' => 'Wrong address', 'is_active' => true]);

        $response = $this->deleteJson("/api/order-reasons/failure/{$reason->id}");
        $response->assertStatus(200);

        $this->assertDatabaseMissing('delivery_failure_reasons', ['id' => $reason->id]);
    }
}
