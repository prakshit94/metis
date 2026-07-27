<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\IrrigationType;
use App\Modules\Users\Models\Permission;
use App\Modules\Users\Models\Role;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CustomerSettingsApiTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'settings-view',
            'settings-edit',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $superAdminRole = Role::findOrCreate('Super Admin', 'web');
        $superAdminRole->syncPermissions($permissions);

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin_cust_settings@example.com',
            'password' => Hash::make('Password123'),
            'is_active' => true,
        ]);
        $admin->assignRole('Super Admin');
        $this->actingAs($admin);
        $this->withoutVite();
    }

    public function test_can_list_settings()
    {
        Crop::create(['name' => 'Test Wheat', 'is_active' => true]);
        
        $response = $this->getJson('/customer-settings');
        $response->assertStatus(200);

        $response = $this->getJson('/api/customer-settings/crop');
        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'Test Wheat']);
    }

    public function test_can_create_setting()
    {
        $response = $this->postJson('/api/customer-settings/irrigation', [
            'name' => 'Test Drip',
            'is_active' => true,
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'Test Drip']);

        $this->assertDatabaseHas('irrigation_types', ['name' => 'Test Drip']);
    }

    public function test_can_update_setting()
    {
        $crop = Crop::create(['name' => 'Test Rice', 'is_active' => true]);

        $response = $this->putJson("/api/customer-settings/crop/{$crop->id}", [
            'name' => 'Test Basmati Rice',
            'is_active' => false,
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'Test Basmati Rice']);
                 
        $this->assertDatabaseHas('crops', [
            'id' => $crop->id,
            'name' => 'Test Basmati Rice',
            'is_active' => false,
        ]);
    }

    public function test_can_delete_setting()
    {
        $crop = Crop::create(['name' => 'Test Sugarcane', 'is_active' => true]);

        $response = $this->deleteJson("/api/customer-settings/crop/{$crop->id}");
        $response->assertStatus(200);

        $this->assertDatabaseMissing('crops', ['id' => $crop->id]);
    }
}
