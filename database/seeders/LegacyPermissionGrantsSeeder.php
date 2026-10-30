<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Services\PermissionCatalogSync;
use Illuminate\Database\Seeder;

/** Explicit repair after a legacy import; never part of routine catalog refreshes. */
class LegacyPermissionGrantsSeeder extends Seeder
{
    public function run(): void
    {
        // Copy only the application's documented legacy-to-action mappings.
        // Keep custom assignments and direct-user team scopes intact.
        app(PermissionCatalogSync::class)->run(true);
        $this->command?->info('Legacy action grants synchronized; role definitions and unrelated assignments preserved.');
    }
}
