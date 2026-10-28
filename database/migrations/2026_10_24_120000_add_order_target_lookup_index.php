<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['created_by', 'order_date', 'deleted_at'], 'orders_creator_date_deleted_idx');
        });
    }

    public function down(): void
    {
        if (! Schema::hasIndex('orders', 'orders_creator_rollback_idx')) {
            Schema::table('orders', fn (Blueprint $table) => $table->index('created_by', 'orders_creator_rollback_idx'));
        }
        Schema::table('orders', function (Blueprint $table) {
            // MySQL may have replaced the implicit FK index with this composite index.
            $table->dropIndex('orders_creator_date_deleted_idx');
        });
    }
};
