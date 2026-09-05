<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Users\Models\Team;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teams = [
            [
                'name' => 'Gujarat (GJ)',
                'code' => 'GJ',
                'state_name' => 'Gujarat',
                'description' => 'State Line of Business - Gujarat',
                'is_active' => true,
            ],
            [
                'name' => 'Rajasthan (RJ)',
                'code' => 'RJ',
                'state_name' => 'Rajasthan',
                'description' => 'State Line of Business - Rajasthan',
                'is_active' => true,
            ],
        ];


        foreach ($teams as $teamData) {
            Team::firstOrCreate(
                ['code' => $teamData['code']],
                $teamData
            );
        }

        $this->command->info('✔ Teams (State LOBs) seeded successfully.');
    }
}
