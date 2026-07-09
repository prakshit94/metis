<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Facades\DB;

class VillageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Optimized for 80,000+ records using LazyCollection and Bulk Insert
     */
    public function run(): void
    {
        $filePath = database_path('villages.csv');
        
        if (!file_exists($filePath)) {
            $this->command->error("CSV file not found at: {$filePath}");
            return;
        }

        $this->command->info('Starting Village import (this may take a minute)...');

        // Disable query log to save memory during massive insert
        DB::connection()->disableQueryLog();

        LazyCollection::make(function () use ($filePath) {
            $handle = fopen($filePath, 'r');
            if ($handle) {
                // Skip Header
                fgetcsv($handle);
                
                while (($line = fgetcsv($handle)) !== false) {
                    yield $line;
                }
                
                fclose($handle);
            }
        })
        ->chunk(2000) // Process 2000 records at a time for optimal MySQL performance
        ->each(function ($chunk): void {
            $data = $chunk->map(function ($row) {
                if (count($row) < 2) {
                    return null;
                }
                return [
                    'village_name'    => $row[0],
                    'normalized_name' => strtolower(trim($row[0])), // Manual since DB::table bypasses mutator
                    'pincode'         => $row[1],
                    'post_so_name'    => ($row[2] ?? '') === '#N/A' ? null : ($row[2] ?? null),
                    'taluka_name'     => ($row[3] ?? '') === '#N/A' ? null : ($row[3] ?? null),
                    'district_name'   => ($row[4] ?? '') === '#N/A' ? null : ($row[4] ?? null),
                    'state_name'      => ($row[5] ?? '') === '#N/A' ? null : ($row[5] ?? null),
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ];
            })->filter()->toArray();

            DB::table('villages')->insert($data);
        });

        $this->command->info('Village import completed successfully!');
    }
}
