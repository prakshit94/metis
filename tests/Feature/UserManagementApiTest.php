<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserManagementApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if (($_ENV['DB_CONNECTION'] ?? null) === 'sqlite' && ! in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('The sqlite PDO driver is not available in this PHP runtime.');
        }

        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'user-create',
            'user-view',
            'user-edit',
            'user-delete',
            'user-sync-permissions',
            'audit-log-view',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $superAdminRole = Role::findOrCreate('Super Admin', 'web');
        $superAdminRole->syncPermissions($permissions);

        $admin = $this->createUser('admin@example.com');
        $admin->assignRole('Super Admin');

        $this->actingAs($admin);
    }

    public function test_store_persists_phone_and_department(): void
    {
        $response = $this->postJson('/api/users', [
            'first_name' => 'Ada',
            'middle_name' => 'Byron',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'phone' => '+1 555 0100',
            'department' => 'Research',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.name', 'Ada Byron Lovelace')
            ->assertJsonPath('data.first_name', 'Ada')
            ->assertJsonPath('data.middle_name', 'Byron')
            ->assertJsonPath('data.last_name', 'Lovelace')
            ->assertJsonPath('data.phone', '+1 555 0100')
            ->assertJsonPath('data.department', 'Research');

        $this->assertDatabaseHas('users', [
            'name' => 'Ada Byron Lovelace',
            'first_name' => 'Ada',
            'middle_name' => 'Byron',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
            'phone' => '+1 555 0100',
            'department' => 'Research',
        ]);
    }

    public function test_index_searches_new_profile_fields_and_ignores_unsafe_sort_columns(): void
    {
        $this->createUser('ops@example.com', [
            'name' => 'Ops User',
            'first_name' => 'Operations',
            'last_name' => 'User',
            'phone' => '555-7777',
            'department' => 'Operations',
        ]);
        $this->createUser('sales@example.com', [
            'name' => 'Sales User',
            'first_name' => 'Sales',
            'last_name' => 'User',
            'department' => 'Sales',
        ]);

        $response = $this->getJson('/api/users?search=Operations&sort_by=password&sort_dir=sideways&per_page=500');

        $response
            ->assertOk()
            ->assertJsonPath('per_page', 100)
            ->assertJsonFragment(['email' => 'ops@example.com'])
            ->assertJsonMissing(['email' => 'sales@example.com']);
    }

    public function test_sync_permissions_route_is_available(): void
    {
        $user = $this->createUser('target@example.com');

        $response = $this->postJson(route('api.users.sync-permissions', $user), [
            'permissions' => ['user-create'],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.0.name', 'user-create');

        $this->assertTrue($user->fresh()->hasDirectPermission('user-create'));
    }

    public function test_bulk_delete_does_not_remove_last_super_admin(): void
    {
        $superAdmin = User::role('Super Admin')->firstOrFail();

        $response = $this->postJson(route('api.users.bulk'), [
            'action' => 'delete',
            'ids' => [$superAdmin->id],
        ]);

        $response
            ->assertForbidden()
            ->assertJsonPath('message', 'Cannot delete the last Super Admin user.');

        $this->assertDatabaseHas('users', ['id' => $superAdmin->id]);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createUser(string $email, array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => 'User ' . $email,
            'email' => $email,
            'password' => Hash::make('Password123'),
            'is_active' => true,
        ], $attributes));
    }
}
