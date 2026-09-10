<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TargetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = \App\Modules\Users\Models\User::take(5)->get();
        $teams = \App\Modules\Users\Models\Team::take(2)->get();
        $departments = \App\Modules\Users\Models\Department::take(2)->get();

        $controller = new \App\Http\Controllers\TargetController();

        foreach ($users as $user) {
            $controller->createTargetChain([
                'targetable_type' => \App\Modules\Users\Models\User::class,
                'metric_type' => 'sales_revenue',
                'period_type' => 'monthly',
                'target_month' => (int) \Carbon\Carbon::now()->format('n'),
                'target_year' => (int) \Carbon\Carbon::now()->format('Y'),
                'target_amount' => 50000.00,
                'achieved_amount' => rand(10000, 60000),
                'status' => 'active',
            ], $user->id);
            
            $controller->createTargetChain([
                'targetable_type' => \App\Modules\Users\Models\User::class,
                'metric_type' => 'calls_made',
                'period_type' => 'daily',
                'start_date' => \Carbon\Carbon::today()->format('Y-m-d'),
                'end_date' => \Carbon\Carbon::today()->format('Y-m-d'),
                'target_amount' => 50,
                'achieved_amount' => rand(10, 60),
                'status' => 'active',
            ], $user->id);
        }

        foreach ($teams as $team) {
            $controller->createTargetChain([
                'targetable_type' => \App\Modules\Users\Models\Team::class,
                'metric_type' => 'orders_count',
                'period_type' => 'monthly',
                'target_month' => (int) \Carbon\Carbon::now()->format('n'),
                'target_year' => (int) \Carbon\Carbon::now()->format('Y'),
                'target_amount' => 500,
                'achieved_amount' => rand(200, 600),
                'status' => 'active',
            ], $team->id);
        }

        foreach ($departments as $department) {
            $controller->createTargetChain([
                'targetable_type' => \App\Modules\Users\Models\Department::class,
                'metric_type' => 'sales_revenue',
                'period_type' => 'yearly',
                'target_year' => (int) \Carbon\Carbon::now()->format('Y'),
                'target_amount' => 1000000.00,
                'achieved_amount' => rand(400000, 1100000),
                'status' => 'active',
            ], $department->id);
        }
    }
}
