<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void { app(\App\Services\PermissionCatalogSync::class)->run(true, ['office-view', 'office-create', 'office-edit', 'office-delete']); }
    public function down(): void { /* Preserve explicit grants on rollback. */ }
};
