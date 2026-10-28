<?php

use App\Services\PermissionCatalogSync;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionCatalogSync::class)->run();
    }

    public function down(): void
    {
        // Preserve administrators' permission assignments on rollback.
    }
};
