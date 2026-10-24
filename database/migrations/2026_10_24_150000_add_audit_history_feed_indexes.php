<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection(config('audit.drivers.database.connection', config('database.default')))
            ->table(config('audit.drivers.database.table', 'audits'), function (Blueprint $table) {
                // Match the audit page's latest-first and event-filtered lists.
                $table->index('created_at', 'audit_history_created_idx');
                $table->index(['event', 'created_at'], 'audit_history_event_created_idx');
            });
    }

    public function down(): void
    {
        Schema::connection(config('audit.drivers.database.connection', config('database.default')))
            ->table(config('audit.drivers.database.table', 'audits'), function (Blueprint $table) {
                $table->dropIndex('audit_history_created_idx');
                $table->dropIndex('audit_history_event_created_idx');
            });
    }
};
