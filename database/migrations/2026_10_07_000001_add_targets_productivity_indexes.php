<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('targets', function (Blueprint $table) {
            $table->index(
                ['targetable_type', 'targetable_id', 'period_type', 'start_date'],
                'targets_assignee_period_start_idx'
            );
        });

        Schema::table('call_logs', function (Blueprint $table) {
            $table->index(['agent_id', 'direction', 'created_at'], 'call_logs_productivity_idx');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->index(['status', 'invoice_date'], 'invoices_target_amount_idx');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index(['status', 'payment_date'], 'payments_target_collection_idx');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_target_collection_idx');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('invoices_target_amount_idx');
        });

        Schema::table('call_logs', function (Blueprint $table) {
            $table->dropIndex('call_logs_productivity_idx');
        });

        Schema::table('targets', function (Blueprint $table) {
            $table->dropIndex('targets_assignee_period_start_idx');
        });
    }
};
