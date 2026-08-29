<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ReferralProgram;
use App\Modules\Users\Models\Role;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ReferralProgramApiTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin_referral@example.com',
            'password' => Hash::make('Password123'),
            'is_active' => true,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $superAdminRole = Role::findOrCreate('Super Admin', 'web');
        $admin->assignRole('Super Admin');

        $this->actingAs($admin);
        $this->withoutVite();
    }

    public function test_can_create_referral_program()
    {
        $response = $this->post('/promotions/referral-programs', [
            'name' => 'Summer Promo',
            'start_date' => '2026-06-01',
            'end_date' => '2026-08-31',
            'is_active' => true,
            'milestones' => [
                [
                    'required_referrals' => 5,
                    'reward_type' => 'wallet',
                    'reward_value' => '100',
                ],
            ],
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('referral_programs', ['name' => 'Summer Promo', 'is_active' => true]);
        $this->assertDatabaseHas('referral_program_milestones', ['required_referrals' => 5]);
    }

    public function test_can_update_referral_program()
    {
        $program = ReferralProgram::create([
            'name' => 'Old Promo',
            'is_active' => false,
        ]);

        $response = $this->put("/promotions/referral-programs/{$program->id}", [
            'name' => 'Updated Promo',
            'is_active' => true,
            'milestones' => [
                [
                    'required_referrals' => 10,
                    'reward_type' => 'coupon',
                    'reward_value' => 'ABC10',
                ],
            ],
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('referral_programs', ['name' => 'Updated Promo', 'is_active' => true]);
        $this->assertDatabaseHas('referral_program_milestones', ['required_referrals' => 10, 'reward_value' => 'ABC10']);
    }

    public function test_can_toggle_referral_program()
    {
        $program = ReferralProgram::create([
            'name' => 'Toggle Promo',
            'is_active' => false,
        ]);

        $response = $this->patch("/promotions/referral-programs/{$program->id}/toggle");
        $response->assertStatus(302);

        $this->assertDatabaseHas('referral_programs', ['id' => $program->id, 'is_active' => true]);
    }

    public function test_can_bulk_action_referral_programs()
    {
        $program1 = ReferralProgram::create(['name' => 'Promo 1', 'is_active' => false]);
        $program2 = ReferralProgram::create(['name' => 'Promo 2', 'is_active' => false]);

        $response = $this->postJson('/promotions/referral-programs/bulk-action', [
            'action' => 'activate',
            'ids' => [$program1->id, $program2->id],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('referral_programs', ['id' => $program1->id, 'is_active' => true]);
        $this->assertDatabaseHas('referral_programs', ['id' => $program2->id, 'is_active' => false]);
    }

    public function test_can_delete_referral_program()
    {
        $program = ReferralProgram::create(['name' => 'Delete Promo']);

        $response = $this->delete("/promotions/referral-programs/{$program->id}");
        $response->assertStatus(302);

        $this->assertDatabaseMissing('referral_programs', ['id' => $program->id]);
    }
}
