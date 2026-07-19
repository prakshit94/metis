<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->string('delivered_by')->nullable()->after('delivered_at');
            $table->integer('delivery_attempts')->default(0)->after('delivered_by');
            $table->timestamp('next_followup_date')->nullable()->after('delivery_attempts');
            $table->string('reschedule_reason')->nullable()->after('next_followup_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn(['delivered_by', 'delivery_attempts', 'next_followup_date']);
        });
    }
};
