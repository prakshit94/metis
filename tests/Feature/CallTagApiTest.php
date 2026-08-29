<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CallTag;
use App\Modules\Users\Models\Permission;
use App\Modules\Users\Models\Role;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CallTagApiTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permissions = ['settings-view'];
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $superAdminRole = Role::findOrCreate('Super Admin', 'web');
        $superAdminRole->syncPermissions($permissions);

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin_calltags@example.com',
            'password' => Hash::make('Password123'),
            'is_active' => true,
        ]);
        $admin->assignRole('Super Admin');

        $this->actingAs($admin);
        $this->withoutVite();
    }

    public function test_can_list_admin_call_tags()
    {
        $tag = CallTag::create([
            'name' => 'Sales',
            'level' => 1,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->get('/call-tags-admin');
        $response->assertStatus(200);

        $response = $this->getJson('/call-tags');
        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Sales']);
    }

    public function test_can_create_admin_call_tag()
    {
        $response = $this->postJson('/call-tags-admin', [
            'name' => 'Support',
            'level' => 1,
            'is_active' => true,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Support']);

        $this->assertDatabaseHas('call_tags', ['name' => 'Support']);
    }

    public function test_can_update_admin_call_tag()
    {
        $tag = CallTag::create([
            'name' => 'Billing',
            'level' => 1,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->putJson("/call-tags-admin/{$tag->id}", [
            'name' => 'Billing Updates',
            'is_active' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Billing Updates']);

        $this->assertDatabaseHas('call_tags', ['id' => $tag->id, 'name' => 'Billing Updates', 'is_active' => false]);
    }

    public function test_can_delete_admin_call_tag()
    {
        $tag = CallTag::create([
            'name' => 'Technical',
            'level' => 1,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->deleteJson("/call-tags-admin/{$tag->id}");
        $response->assertStatus(200);

        $this->assertDatabaseMissing('call_tags', ['id' => $tag->id]);
    }

    public function test_can_submit_call_log()
    {
        $tag1 = CallTag::create(['name' => 'L1', 'level' => 1, 'is_active' => true, 'sort_order' => 1]);
        $tag2 = CallTag::create(['name' => 'L2', 'level' => 2, 'parent_id' => $tag1->id, 'is_active' => true, 'sort_order' => 1]);

        $response = $this->postJson('/call-logs', [
            'tag_l1_id' => $tag1->id,
            'tag_l2_id' => $tag2->id,
            'notes' => 'Customer called about issue',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['notes' => 'Customer called about issue']);

        $this->assertDatabaseHas('call_logs', [
            'tag_l1_id' => $tag1->id,
            'tag_l2_id' => $tag2->id,
            'notes' => 'Customer called about issue',
        ]);
    }
}
