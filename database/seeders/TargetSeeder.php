<?php

namespace Database\Seeders;

use App\Http\Controllers\TargetController;
use App\Modules\Users\Models\Department;
use App\Modules\Users\Models\Team;
use App\Modules\Users\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Database\UniqueConstraintViolationException;

class TargetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::take(5)->get();
        $teams = Team::take(2)->get();
        $departments = Department::take(2)->get();

        $controller = new TargetController;

        foreach ($users as $user) {
            try {
                $controller->createTargetChain([
                    'targetable_type' => User::class,
                    'metric_type' => 'sales_revenue',
                    'period_type' => 'monthly',
                    'target_month' => (int) Carbon::now()->format('n'),
                    'target_year' => (int) Carbon::now()->format('Y'),
                    'target_amount' => 50000.00,
                    'achieved_amount' => rand(10000, 60000),
                    'status' => 'active',
                ], $user->id);
            } catch (UniqueConstraintViolationException) {
                // Already seeded for this period — skip
            }

            try {
                $controller->createTargetChain([
                    'targetable_type' => User::class,
                    'metric_type' => 'calls_made',
                    'period_type' => 'daily',
                    'start_date' => Carbon::today()->format('Y-m-d'),
                    'end_date' => Carbon::today()->format('Y-m-d'),
                    'target_amount' => 50,
                    'achieved_amount' => rand(10, 60),
                    'status' => 'active',
                ], $user->id);
            } catch (UniqueConstraintViolationException) {
                // Already seeded for today — skip
            }
        }

        foreach ($teams as $team) {
            try {
                $controller->createTargetChain([
                    'targetable_type' => Team::class,
                    'metric_type' => 'orders_count',
                    'period_type' => 'monthly',
                    'target_month' => (int) Carbon::now()->format('n'),
                    'target_year' => (int) Carbon::now()->format('Y'),
                    'target_amount' => 500,
                    'achieved_amount' => rand(200, 600),
                    'status' => 'active',
                ], $team->id);
            } catch (UniqueConstraintViolationException) {
                // Already seeded — skip
            }
        }

        foreach ($departments as $department) {
            try {
                $controller->createTargetChain([
                    'targetable_type' => Department::class,
                    'metric_type' => 'sales_revenue',
                    'period_type' => 'yearly',
                    'target_year' => (int) Carbon::now()->format('Y'),
                    'target_amount' => 1000000.00,
                    'achieved_amount' => rand(400000, 1100000),
                    'status' => 'active',
                ], $department->id);
            } catch (UniqueConstraintViolationException) {
                // Already seeded — skip
            }
        }
    }
}
