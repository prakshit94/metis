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
        // Migrate legacy draft orders to explicit future_order status
        \Illuminate\Support\Facades\DB::table('orders')
            ->where('is_draft', 1)
            ->where('status', 'pending')
            ->update(['status' => 'future_order']);

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('is_draft');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('is_draft')->default(false)->index();
        });

        // Revert future_order back to pending + is_draft
        \Illuminate\Support\Facades\DB::table('orders')
            ->where('status', 'future_order')
            ->update(['status' => 'pending', 'is_draft' => 1]);
    }
};
