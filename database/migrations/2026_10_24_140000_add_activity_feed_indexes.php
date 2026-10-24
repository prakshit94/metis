<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection(config('activitylog.database_connection'))
            ->table(config('activitylog.table_name', 'activity_log'), function (Blueprint $table) {
                $table->index('created_at', 'activity_feed_created_idx');
                $table->index(['causer_id', 'created_at'], 'activity_feed_causer_created_idx');
            });
    }

    public function down(): void
    {
        Schema::connection(config('activitylog.database_connection'))
            ->table(config('activitylog.table_name', 'activity_log'), function (Blueprint $table) {
                $table->dropIndex('activity_feed_created_idx');
                $table->dropIndex('activity_feed_causer_created_idx');
            });
    }
};
