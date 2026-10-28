<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Services\PermissionCatalogSync;
use Illuminate\Database\Seeder;

/** Safe catalog refresh for existing installations; deliberately does not reset role grants. */
class FeaturePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionCatalogSync::class)->run(false);
        $this->command?->info('Feature permission catalog updated; existing role and user assignments preserved.');
    }
}
